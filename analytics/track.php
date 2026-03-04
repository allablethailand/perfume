<?php
require_once '../lib/connect.php'; // $conn = mysqli
$debug = [];  // เก็บ log
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) { echo json_encode(['ok' => false]); exit; }

$action     = $data['action']     ?? '';
$session_id = $data['session_id'] ?? '';
$visitor_id = $data['visitor_id'] ?? '';
if (!$session_id || !$visitor_id) { echo json_encode(['ok' => false]); exit; }

$ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '')[0])
    ?: $_SERVER['REMOTE_ADDR'];

// ── Location ──────────────────────────────────
function getLocation($ip, $conn) {
    // ถ้าเป็น private/local IP → ข้ามเลย
    if (
        $ip === '127.0.0.1' || $ip === '::1' ||
        strpos($ip, '192.168.') === 0 ||
        strpos($ip, '10.')     === 0 ||
        strpos($ip, '172.')    === 0
    ) {
        return ['country'=>'Local','province'=>'','region'=>'','city'=>'','lat'=>0,'lng'=>0];
    }

    $hash = md5($ip);

    // ดึง cache — แต่เช็คด้วยว่ามีข้อมูลจริงหรือเปล่า (country ไม่ว่าง)
    $st = $conn->prepare("SELECT * FROM analytics_ip_cache WHERE ip_hash=?");
    $st->bind_param('s', $hash);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();

    // ✅ คืน cache เฉพาะตอนที่มีข้อมูลจริง
    if ($row && !empty($row['country'])) {
        return $row;
    }

    $loc = ['country'=>'','province'=>'','region'=>'','city'=>'','lat'=>0,'lng'=>0];

    // ลอง cURL ก่อน (เพราะ allow_url_fopen อาจปิด)
    if (function_exists('curl_init')) {
        $ch = curl_init("http://ip-api.com/json/{$ip}?fields=status,country,regionName,city,lat,lon&lang=th");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_USERAGENT      => 'Analytics/1.0',
        ]);
        $res = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
    } else {
        // fallback: file_get_contents
        $ctx = stream_context_create(['http'=>['method'=>'GET','header'=>'User-Agent: Analytics/1.0','timeout'=>5]]);
        $res = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,regionName,city,lat,lon&lang=th", false, $ctx);
        $err = '';
    }

    if ($res) {
        $d = json_decode($res, true);
        // ip-api จะ return status=success ถ้าสำเร็จ
        if (!empty($d) && ($d['status'] ?? '') === 'success') {
            $loc['country']  = $d['country']    ?? '';
            $loc['province'] = $d['regionName'] ?? '';
            $loc['city']     = $d['city']       ?? '';
            $loc['lat']      = $d['lat']        ?? 0;
            $loc['lng']      = $d['lon']        ?? 0;
        }
    }

    // ✅ บันทึก cache เฉพาะตอนที่ได้ข้อมูลจริง (country ไม่ว่าง)
    if (!empty($loc['country'])) {
        $st = $conn->prepare("
            INSERT INTO analytics_ip_cache (ip_hash,country,province,region,city,lat,lng,cached_at)
            VALUES (?,?,?,?,?,?,?,NOW())
            ON DUPLICATE KEY UPDATE
                country=VALUES(country), province=VALUES(province),
                city=VALUES(city), lat=VALUES(lat), lng=VALUES(lng),
                cached_at=NOW()
        ");
        $st->bind_param('sssssdd',
            $hash, $loc['country'], $loc['province'],
            $loc['region'], $loc['city'], $loc['lat'], $loc['lng']
        );
        $st->execute();
        // ใน getLocation() หลัง curl_exec:
        $debug['ip']       = $ip;
        $debug['curl_err'] = $err ?? '';
        $debug['raw_res']  = $res;
        $debug['parsed']   = $d ?? null;
        file_put_contents('/tmp/analytics_debug.log', json_encode($debug) . "\n", FILE_APPEND);
        $st->close();
    }

    return $loc;
}

// ── Parse UA ──────────────────────────────────
function parseUA($ua) {
    $os = match(true) {
        stripos($ua,'Android') !== false => 'Android',
        stripos($ua,'iPhone')  !== false => 'iOS',
        stripos($ua,'iPad')    !== false => 'iPadOS',
        stripos($ua,'Windows') !== false => 'Windows',
        stripos($ua,'Mac OS')  !== false => 'macOS',
        default                          => 'Other'
    };
    $br = match(true) {
        stripos($ua,'Edg')     !== false => 'Edge',
        stripos($ua,'Chrome')  !== false => 'Chrome',
        stripos($ua,'Firefox') !== false => 'Firefox',
        stripos($ua,'Safari')  !== false => 'Safari',
        default                          => 'Other'
    };
    return [$os, $br];
}

// ── Actions ───────────────────────────────────
if ($action === 'page_enter') {
    $loc = getLocation($ip, $conn);
    [$os, $browser] = parseUA($data['user_agent'] ?? '');

    $url     = substr($data['url']             ?? '', 0, 2048);
    $title   = substr($data['page_title']      ?? '', 0, 500);
    $ref_url = substr($data['referrer_url']    ?? '', 0, 2048);
    $ref_src = substr($data['referrer_source'] ?? '', 0, 100);
    $device  = $data['device_type'] ?? 'desktop';
    $ua      = substr($data['user_agent']      ?? '', 0, 500);

    $st = $conn->prepare("
        INSERT INTO analytics_sessions
            (session_id,visitor_id,ip_address,user_agent,referrer,referrer_source,
             country,province,region,city,lat,lng,device_type,os,browser,
             started_at,last_active_at,page_count)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW(),1)
        ON DUPLICATE KEY UPDATE
            last_active_at=NOW(), page_count=page_count+1
    ");
    $st->bind_param('ssssssssssddsss',
        $session_id, $visitor_id, $ip, $ua, $ref_url, $ref_src,
        $loc['country'], $loc['province'], $loc['region'], $loc['city'],
        $loc['lat'], $loc['lng'], $device, $os, $browser
    );
    $st->execute(); $st->close();

    $st = $conn->prepare("
        INSERT INTO analytics_pageviews (session_id,visitor_id,url,page_title,referrer_url,entered_at)
        VALUES (?,?,?,?,?,NOW())
    ");
    $st->bind_param('sssss', $session_id, $visitor_id, $url, $title, $ref_url);
    $st->execute(); $st->close();
}

elseif ($action === 'page_exit') {
    $url      = substr($data['url'] ?? '', 0, 2048);
    $duration = (int)($data['duration']     ?? 0);
    $scroll   = (int)($data['scroll_depth'] ?? 0);

    $st = $conn->prepare("
        UPDATE analytics_pageviews
        SET exited_at=NOW(), time_on_page=?, scroll_depth=?, is_bounce=0
        WHERE session_id=? AND url=? AND exited_at IS NULL
        ORDER BY entered_at DESC LIMIT 1
    ");
    $st->bind_param('iiss', $duration, $scroll, $session_id, $url);
    $st->execute(); $st->close();

    $st = $conn->prepare("
        UPDATE analytics_sessions
        SET duration_sec=TIMESTAMPDIFF(SECOND,started_at,NOW()), last_active_at=NOW()
        WHERE session_id=?
    ");
    $st->bind_param('s', $session_id); $st->execute(); $st->close();
}

elseif ($action === 'event') {
    $name   = substr($data['event_name']   ?? '', 0, 100);
    $target = substr($data['event_target'] ?? '', 0, 500);
    $value  = substr($data['event_value']  ?? '', 0, 500);
    $url    = substr($data['url']          ?? '', 0, 2048);

    $st = $conn->prepare("
        INSERT INTO analytics_events (session_id,visitor_id,event_name,event_target,event_value,page_url,occurred_at)
        VALUES (?,?,?,?,?,?,NOW())
    ");
    $st->bind_param('ssssss', $session_id, $visitor_id, $name, $target, $value, $url);
    $st->execute(); $st->close();
}

echo json_encode(['ok' => true]);