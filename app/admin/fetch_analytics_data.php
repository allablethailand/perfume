<?php
// ============================================================
// fetch_analytics_data.php
// รับ ?section=xxx&days=yyy&refresh=1
// section: all | daily_users | source | country | region |
//          map_points | top_pages | top_products | top_projects | top_blogs | top_news |
//          device | os_browser
// days:    1 | 3 | 7 | 30 | 90 | 365
// refresh=1 บังคับเสมอ (ตาม requirement)
// ============================================================

require __DIR__ . '/../../lib/connect.php';

header('Content-Type: application/json');

// ── validate params ────────────────────────────────────────
$allowedSections = ['all','daily_users','source','country','region',
                    'map_points','top_pages','top_products','top_projects','top_blogs','top_news',
                    'device','os_browser'];
$allowedDays     = [1, 3, 7, 30, 90, 365];

$section = isset($_GET['section']) && in_array($_GET['section'], $allowedSections)
           ? $_GET['section'] : 'all';
$days    = isset($_GET['days']) ? (int)$_GET['days'] : 30;
if (!in_array($days, $allowedDays)) $days = 30;

// ── cache ──────────────────────────────────────────────────
$cacheDir  = __DIR__ . '/';
$cacheFile = $cacheDir . "analytics_cache_{$section}_{$days}.json";
$cacheTime = 300;
$errorLog  = __DIR__ . '/analytics_error.log';

$forceRefresh = isset($_GET['refresh']);
if ($forceRefresh && file_exists($cacheFile)) unlink($cacheFile);

if (!$forceRefresh && file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
    header('X-Cache: HIT');
    echo file_get_contents($cacheFile);
    exit;
}

// ── helper ─────────────────────────────────────────────────
function q($conn, $sql, $types = '', $params = []): array {
    $st = $conn->prepare($sql);
    if ($st === false) {
        throw new Exception('Prepare failed: ' . $conn->error . ' | SQL: ' . $sql);
    }
    if ($types && $params) $st->bind_param($types, ...$params);
    if (!$st->execute()) {
        throw new Exception('Execute failed: ' . $st->error);
    }
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    return $rows;
}

function mapContent(array $rows): array {
    return [
        'labels'   => array_map(fn($r) => $r['page_title'],               $rows),
        'views'    => array_map(fn($r) => (int)$r['views'],                $rows),
        'users'    => array_map(fn($r) => (int)$r['users'],                $rows),
        'avg_time' => array_map(fn($r) => round((float)$r['avg_time'], 1), $rows),
    ];
}

function fetchContent($conn, $days, $urlCond, $titleCond, $limit = 10): array {
    return q($conn, "
        SELECT page_title,
               COUNT(*)                    AS views,
               COUNT(DISTINCT visitor_id)  AS users,
               AVG(time_on_page)           AS avg_time
        FROM analytics_pageviews
        WHERE entered_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
          AND ($urlCond OR $titleCond)
        GROUP BY page_title
        ORDER BY views DESC
        LIMIT $limit
    ", 'i', [$days]);
}

try {
    $response = ['section' => $section, 'days' => $days];

    // ── daily_users ─────────────────────────────
    if ($section === 'all' || $section === 'daily_users') {
        if ($days === 1) {
            $rows = q($conn, "
                SELECT DATE_FORMAT(started_at,'%H:00') AS d,
                       COUNT(DISTINCT visitor_id)      AS n
                FROM analytics_sessions
                WHERE DATE(started_at) = CURDATE()
                GROUP BY DATE_FORMAT(started_at,'%H:00')
                ORDER BY d ASC
            ");
        } else {
            $rows = q($conn, "
                SELECT DATE(started_at)           AS d,
                       COUNT(DISTINCT visitor_id) AS n
                FROM analytics_sessions
                WHERE started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(started_at)
                ORDER BY d ASC
            ", 'i', [$days]);
        }
        $response['daily_users'] = [
            'labels' => array_map(fn($r) => $days === 1 ? $r['d'] : date('d M', strtotime($r['d'])), $rows),
            'data'   => array_map(fn($r) => (int)$r['n'], $rows),
        ];
    }

    // ── source ──────────────────────────────────
    if ($section === 'all' || $section === 'source') {
        $rows = q($conn, "
            SELECT
                CASE
                    WHEN referrer LIKE '%facebook.com%'   OR referrer LIKE '%fb.com%'
                         OR referrer LIKE '%fb.me%'        THEN 'Facebook'
                    WHEN referrer LIKE '%line.me%'         OR referrer LIKE '%line.naver%'
                         OR referrer LIKE '%liff.line.me%'                                THEN 'LINE'
                    WHEN referrer LIKE '%instagram.com%'   OR referrer LIKE '%instagr.am%' THEN 'Instagram'
                    WHEN referrer LIKE '%twitter.com%'     OR referrer LIKE '%t.co%'       THEN 'Twitter / X'
                    WHEN referrer LIKE '%tiktok.com%'      OR referrer LIKE '%vm.tiktok%'  THEN 'TikTok'
                    WHEN referrer LIKE '%youtube.com%'     OR referrer LIKE '%youtu.be%'   THEN 'YouTube'
                    WHEN referrer LIKE '%google.com%'      OR referrer LIKE '%google.co.th%' THEN 'Google'
                    WHEN referrer LIKE '%bing.com%'                                         THEN 'Bing'
                    WHEN referrer LIKE '%yahoo.com%'                                        THEN 'Yahoo'
                    WHEN referrer IS NULL OR referrer = ''                                  THEN 'Direct'
                    ELSE COALESCE(NULLIF(referrer_source,''), 'Other')
                END AS s,
                COUNT(DISTINCT visitor_id) AS n
            FROM analytics_sessions
            WHERE started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY s
            ORDER BY n DESC
        ", 'i', [$days]);

        $response['source'] = [
            'labels' => array_map(fn($r) => $r['s'], $rows),
            'data'   => array_map(fn($r) => (int)$r['n'], $rows),
        ];
    }

    // ── country ─────────────────────────────────
    if ($section === 'all' || $section === 'country') {
        $rows = q($conn, "
            SELECT COALESCE(NULLIF(country,''),'Unknown') AS c,
                   COUNT(DISTINCT visitor_id)             AS n
            FROM analytics_sessions
            WHERE started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY c ORDER BY n DESC LIMIT 50
        ", 'i', [$days]);
        $response['country'] = [
            'labels' => array_map(fn($r) => $r['c'], $rows),
            'data'   => array_map(fn($r) => (int)$r['n'], $rows),
        ];
    }

    // ── region ──────────────────────────────────
    if ($section === 'all' || $section === 'region') {
        $rows = q($conn, "
            SELECT COALESCE(NULLIF(province,''),'Unknown') AS prov,
                   COALESCE(NULLIF(country,''),'Unknown')  AS ctry,
                   COUNT(DISTINCT visitor_id)              AS n
            FROM analytics_sessions
            WHERE started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
              AND province IS NOT NULL AND province != ''
            GROUP BY prov, ctry ORDER BY n DESC LIMIT 100
        ", 'i', [$days]);
        $provinceMap = [];
        foreach ($rows as $r) {
            if (in_array($r['ctry'], ['ประเทศไทย','Thailand','TH']))
                $provinceMap[$r['prov']] = (int)$r['n'];
        }
        $response['region'] = [
            'labels'  => array_map(fn($r) => $r['prov'], $rows),
            'data'    => array_map(fn($r) => (int)$r['n'], $rows),
            'country' => array_map(fn($r) => $r['ctry'], $rows),
        ];
        $response['province_map'] = $provinceMap;
    }

    // ── map_points ───────────────────────────────
    if ($section === 'all' || $section === 'region' || $section === 'map_points') {
        $rows = q($conn, "
            SELECT
                ROUND(lat, 3)              AS lat,
                ROUND(lng, 3)              AS lng,
                IFNULL(city, '')           AS city,
                IFNULL(province, '')       AS province,
                IFNULL(region, '')         AS region,
                IFNULL(country, '')        AS country,
                COUNT(DISTINCT visitor_id) AS user_count
            FROM analytics_sessions
            WHERE started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
              AND lat IS NOT NULL AND lng IS NOT NULL
              AND lat != 0 AND lng != 0
            GROUP BY
                ROUND(lat, 3), ROUND(lng, 3),
                IFNULL(city, ''), IFNULL(province, ''),
                IFNULL(region, ''), IFNULL(country, '')
            ORDER BY user_count DESC
            LIMIT 500
        ", 'i', [$days]);

        $response['map_points'] = array_map(fn($r) => [
            'lat'      => (float)$r['lat'],
            'lng'      => (float)$r['lng'],
            'count'    => (int)$r['user_count'],
            'city'     => $r['city'],
            'province' => $r['province'],
            'region'   => $r['region'],
            'country'  => $r['country'],
        ], $rows);
    }

    // ── device (by OS) ───────────────────────────
    if ($section === 'all' || $section === 'device') {
        $rows = q($conn, "
            SELECT
                CASE
                    WHEN os LIKE '%Android%'                       THEN 'Android'
                    WHEN os LIKE '%iOS%' OR os LIKE '%iPhone%'
                         OR os LIKE '%iPad%'                       THEN 'iOS'
                    WHEN os LIKE '%Windows%'                       THEN 'Windows'
                    WHEN os LIKE '%Mac%' OR os LIKE '%macOS%'      THEN 'macOS'
                    WHEN os LIKE '%Linux%' OR os LIKE '%Ubuntu%'   THEN 'Linux'
                    WHEN os LIKE '%Chrome OS%' OR os LIKE '%CrOS%' THEN 'Chrome OS'
                    WHEN os IS NULL OR os = ''                      THEN 'Unknown'
                    ELSE os
                END AS os_label,
                COUNT(DISTINCT ip_address) AS cnt
            FROM analytics_sessions
            WHERE started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY os_label
            ORDER BY cnt DESC
        ", 'i', [$days]);

        $response['device'] = [
            'labels' => array_map(fn($r) => $r['os_label'], $rows),
            'data'   => array_map(fn($r) => (int)$r['cnt'], $rows),
        ];
    }

    // ── os_browser ───────────────────────────────
    if ($section === 'all' || $section === 'os_browser') {
        $osRows = q($conn, "
            SELECT
                COALESCE(NULLIF(os,''), 'Unknown') AS label,
                COUNT(DISTINCT ip_address)         AS cnt
            FROM analytics_sessions
            WHERE started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY os
            ORDER BY cnt DESC
            LIMIT 6
        ", 'i', [$days]);

        $brRows = q($conn, "
            SELECT
                COALESCE(NULLIF(browser,''), 'Unknown') AS label,
                COUNT(DISTINCT ip_address)              AS cnt
            FROM analytics_sessions
            WHERE started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY browser
            ORDER BY cnt DESC
            LIMIT 6
        ", 'i', [$days]);

        $combined = [];
        foreach ($osRows as $r) $combined[] = ['label' => $r['label'], 'cnt' => (int)$r['cnt']];
        foreach ($brRows as $r) $combined[] = ['label' => $r['label'], 'cnt' => (int)$r['cnt']];

        $response['os_browser'] = [
            'labels' => array_column($combined, 'label'),
            'data'   => array_column($combined, 'cnt'),
        ];
    }

    // ── top_pages ────────────────────────────────
    if ($section === 'all' || $section === 'top_pages') {
        $rows = q($conn, "
            SELECT url, page_title,
                   COUNT(*)                    AS views,
                   COUNT(DISTINCT visitor_id)  AS users,
                   AVG(time_on_page)           AS avg_time
            FROM analytics_pageviews
            WHERE entered_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY url, page_title
            ORDER BY users DESC LIMIT 5
        ", 'i', [$days]);
        $response['top_pages'] = [
            'labels'   => array_map(fn($r) => $r['url'],                      $rows),
            'data'     => array_map(fn($r) => (int)$r['users'],                $rows),
            'views'    => array_map(fn($r) => (int)$r['views'],                $rows),
            'avg_time' => array_map(fn($r) => round((float)$r['avg_time'], 1), $rows),
        ];
    }

    // ── top_products ─────────────────────────────
    if ($section === 'all' || $section === 'top_products') {
        $rows = fetchContent($conn, $days,
            "url LIKE '%product_detail%' OR url LIKE '%shop_detail%'",
            "page_title LIKE '%สินค้า%' OR page_title LIKE '%Product%'"
        );
        $m = mapContent($rows);
        $response['top_products'] = array_merge($m, ['data' => $m['views']]);
    }

    // ── top_projects ─────────────────────────────
    if ($section === 'all' || $section === 'top_projects') {
        $response['top_projects'] = mapContent(fetchContent($conn, $days,
            "url LIKE '%project_detail%'",
            "page_title LIKE '%โครงการ%' OR page_title LIKE '%Project%'"
        ));
    }

    // ── top_blogs ────────────────────────────────
    if ($section === 'all' || $section === 'top_blogs') {
        $response['top_blogs'] = mapContent(fetchContent($conn, $days,
            "url LIKE '%blog_detail%' OR url LIKE '%Blog%'",
            "page_title LIKE '%บทความ%'"
        ));
    }

    // ── top_news ─────────────────────────────────
    if ($section === 'all' || $section === 'top_news') {
        $response['top_news'] = mapContent(fetchContent($conn, $days,
            "url LIKE '%news_detail%'",
            "page_title LIKE '%ข่าว%' OR page_title LIKE '%News%'"
        ));
    }

    $response['generated_at'] = date('Y-m-d H:i:s');
    $json = json_encode($response);

    file_put_contents($cacheFile, $json);

    header('X-Cache: MISS');
    echo $json;

} catch (Exception $e) {
    $logMsg = '['.date('Y-m-d H:i:s').'] Analytics Error: '.$e->getMessage().PHP_EOL;
    file_put_contents($errorLog, $logMsg, FILE_APPEND);
    if (file_exists($cacheFile)) { header('X-Cache: STALE'); echo file_get_contents($cacheFile); }
    else { http_response_code(500); echo json_encode(['error' => $e->getMessage()]); }
}