<?php
include 'check_permission.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

date_default_timezone_set('Asia/Bangkok');

$ip   = trim($_GET['ip']   ?? '');
$days = max(1, (int)($_GET['days'] ?? 1));

if (!$ip) { header('Location: ip_visitors.php'); exit; }

// ── IP Summary ─────────────────────────────────────────────
$st = $conn->prepare("
    SELECT
        s.ip_address,
        MAX(c.country)                   AS country,
        MAX(c.city)                      AS city,
        MAX(c.province)                  AS province,
        MAX(c.lat)                       AS lat,
        MAX(c.lng)                       AS lng,
        COUNT(DISTINCT s.session_id)     AS sessions,
        COUNT(p.view_id)                 AS total_views,
        SUM(p.time_on_page)              AS total_time,
        ROUND(AVG(p.time_on_page))       AS avg_time,
        MAX(s.device_type)               AS device_type,
        MAX(s.os)                        AS os,
        MAX(s.browser)                   AS browser,
        MAX(s.user_agent)                AS user_agent,
        MIN(s.started_at)                AS first_seen,
        MAX(s.last_active_at)            AS last_seen,
        GROUP_CONCAT(DISTINCT s.referrer_source SEPARATOR ',') AS sources
    FROM analytics_sessions s
    LEFT JOIN analytics_pageviews p ON p.session_id = s.session_id
    LEFT JOIN analytics_ip_cache c  ON c.ip_hash = MD5(s.ip_address)
    WHERE s.ip_address = ? AND s.started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    GROUP BY s.ip_address
");
if (!$st) { die('DB Error: ' . $conn->error); }
$st->bind_param('si', $ip, $days);
$st->execute();
$info = $st->get_result()->fetch_assoc();
$st->close();

function safeQuery($conn, $sql, $types, $params) {
    $st = $conn->prepare($sql);
    if (!$st) return null;
    $st->bind_param($types, ...$params);
    $st->execute();
    $result = $st->get_result()->fetch_assoc();
    $st->close();
    return $result;
}

$linkedUser = null;
$linked_uid = null;

$row = safeQuery($conn,
    "SELECT DISTINCT user_id FROM analytics_sessions WHERE ip_address=? AND user_id IS NOT NULL AND user_id>0 ORDER BY last_active_at DESC LIMIT 1",
    's', [$ip]);
$linked_uid = $row['user_id'] ?? null;

if (!$linked_uid) {
    $row = safeQuery($conn,
        "SELECT DISTINCT user_id FROM dn_download_logs WHERE ip_address=? AND user_id IS NOT NULL AND user_id>0 ORDER BY downloaded_at DESC LIMIT 1",
        's', [$ip]);
    $linked_uid = $row['user_id'] ?? null;
}

if (!$linked_uid) {
    $row = safeQuery($conn,
        "SELECT DISTINCT user_id FROM dn_download_logs WHERE ip_hash=MD5(?) AND user_id IS NOT NULL AND user_id>0 ORDER BY downloaded_at DESC LIMIT 1",
        's', [$ip]);
    $linked_uid = $row['user_id'] ?? null;
}

if ($linked_uid) {
    $st = $conn->prepare("SELECT user_id,first_name,last_name,email,phone_number,profile_img,date_create,verify,email_verified FROM mb_user WHERE user_id=? AND del=0 LIMIT 1");
    if ($st) {
        $st->bind_param('i', $linked_uid); $st->execute();
        $linkedUser = $st->get_result()->fetch_assoc(); $st->close();
    }
}

// ── All Sessions ───────────────────────────────────────────
$st = $conn->prepare("
    SELECT s.session_id, s.started_at, s.last_active_at, s.duration_sec, s.page_count,
           s.device_type, s.os, s.browser, s.referrer_source, s.referrer, s.user_id
    FROM analytics_sessions s
    WHERE s.ip_address = ? AND s.started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    ORDER BY s.started_at DESC
");
if (!$st) {
    $st = $conn->prepare("
        SELECT s.session_id, s.started_at, s.last_active_at, s.duration_sec, s.page_count,
               s.device_type, s.os, s.browser, s.referrer_source, s.referrer,
               NULL AS user_id
        FROM analytics_sessions s
        WHERE s.ip_address = ? AND s.started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ORDER BY s.started_at DESC
    ");
}
$st->bind_param('si', $ip, $days); $st->execute();
$sessions = $st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();

// ── Pageviews grouped by session ──────────────────────────
$sessionIds = array_column($sessions, 'session_id');
$pagesBySession = [];
if ($sessionIds) {
    $in = implode(',', array_fill(0, count($sessionIds), '?'));
    $tys = str_repeat('s', count($sessionIds));
    $st = $conn->prepare("SELECT p.session_id,p.url,p.page_title,p.referrer_url,p.entered_at,p.exited_at,p.time_on_page,p.scroll_depth,p.is_bounce FROM analytics_pageviews p WHERE p.session_id IN ($in) ORDER BY p.entered_at ASC");
    $st->bind_param($tys, ...$sessionIds); $st->execute();
    foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $pv) { $pagesBySession[$pv['session_id']][] = $pv; }
    $st->close();
}

// ── Events ────────────────────────────────────────────────
$eventsBySession = [];
if ($sessionIds) {
    $in = implode(',', array_fill(0, count($sessionIds), '?'));
    $tys = str_repeat('s', count($sessionIds));
    $st = $conn->prepare("SELECT session_id,event_name,event_target,event_value,page_url,occurred_at FROM analytics_events WHERE session_id IN ($in) ORDER BY occurred_at ASC");
    $st->bind_param($tys, ...$sessionIds); $st->execute();
    foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $ev) { $eventsBySession[$ev['session_id']][] = $ev; }
    $st->close();
}

// ── Top pages ─────────────────────────────────────────────
$st = $conn->prepare("SELECT p.url,p.page_title,COUNT(*) AS views,ROUND(AVG(p.time_on_page)) AS avg_time FROM analytics_pageviews p WHERE p.session_id IN (SELECT session_id FROM analytics_sessions WHERE ip_address=? AND started_at>=DATE_SUB(NOW(),INTERVAL ? DAY)) GROUP BY p.url,p.page_title ORDER BY views DESC LIMIT 10");
$st->bind_param('si', $ip, $days); $st->execute();
$topPages = $st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();

// ── Download history ──────────────────────────────────────
$st = $conn->prepare("
    SELECT d.log_id, d.downloaded_at, d.user_id,
           f.file_original, f.file_ext, f.file_size,
           COALESCE(u.first_name,'') AS first_name,
           COALESCE(u.last_name,'')  AS last_name,
           COALESCE(u.email,'')      AS email
    FROM dn_download_logs d
    LEFT JOIN dn_files f ON f.file_id = d.file_id
    LEFT JOIN mb_user  u ON u.user_id = d.user_id AND u.del=0
    WHERE d.ip_address=? OR d.ip_hash=MD5(?)
    ORDER BY d.downloaded_at DESC
    LIMIT 50
");
if ($st) {
    $st->bind_param('ss', $ip, $ip); $st->execute();
    $downloads = $st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();
} else {
    $st = $conn->prepare("
        SELECT d.log_id, d.downloaded_at, d.user_id,
               f.file_original, f.file_ext, f.file_size,
               COALESCE(u.first_name,'') AS first_name,
               COALESCE(u.last_name,'')  AS last_name,
               COALESCE(u.email,'')      AS email
        FROM dn_download_logs d
        LEFT JOIN dn_files f ON f.file_id = d.file_id
        LEFT JOIN mb_user  u ON u.user_id = d.user_id AND u.del=0
        WHERE d.ip_hash=MD5(?)
        ORDER BY d.downloaded_at DESC
        LIMIT 50
    ");
    if ($st) {
        $st->bind_param('s', $ip); $st->execute();
        $downloads = $st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();
    } else {
        $downloads = [];
    }
}

// ── Helpers ───────────────────────────────────────────────
function fmtTime($sec) {
    $sec=(int)$sec; if($sec<=0)return'—'; if($sec<60)return $sec.'s';
    return floor($sec/60).'m '.($sec%60).'s';
}
function shortUrl($url) {
    $u=preg_replace('#^https?://[^/]+#','',$url);
    return strlen($u)>55?substr($u,0,55).'…':($u?:'/');
}
function srcBadge($src) {
    if ($src === 'search') return '<span class="badge badge-blue">🔍 search</span>';
    if ($src === 'social') return '<span class="badge badge-purple">📱 social</span>';
    if ($src === 'direct') return '<span class="badge badge-green">🔗 direct</span>';
    return '<span class="badge badge-gray">🌐 other</span>';
}
function deviceIcon($d) {
    if ($d === 'mobile')  return '📱';
    if ($d === 'tablet')  return '💻';
    if ($d === 'desktop') return '🖥️';
    return '❓';
}
function extColor($ext) {
    $e = strtolower(trim($ext, '.'));
    if ($e === 'pdf')                return ['#fde8e8','#c62828'];
    if ($e === 'exe' || $e === 'msi') return ['#e3f0ff','#1565c0'];
    if ($e === 'zip')                return ['#fff3e0','#e65100'];
    if ($e === 'dmg')                return ['#eceff1','#37474f'];
    return ['#f3f4f6','#374151'];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>IP Detail: <?= htmlspecialchars($ip) ?></title>
<link rel="icon" type="image/x-icon" href="../public/img/q-removebg-preview1.png">
<link href="css/index_.css?v=<?= time(); ?>" rel="stylesheet">
<?php include 'inc_head.php'; ?>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/7.9.0/d3.min.js"></script>
<style>
:root{
  --bg:#f5f4f0;--surface:#fff;--surface2:#faf9f6;--surface3:#f0ede6;
  --border:rgba(0,0,0,0.07);--border2:rgba(0,0,0,0.13);
  --text:#1a1612;--text2:#4a3f35;--muted:#9a8e84;
  --accent:#f5630a;--green:#2e9e5b;--red:#e84855;--blue:#3b82f6;--purple:#8b5cf6;
  --r:12px;--shadow:0 2px 12px rgba(0,0,0,.08);
  --t:0.2s cubic-bezier(0.4,0,0.2,1);
  --font:'DM Sans',system-ui,sans-serif;--mono:'DM Mono',monospace;
}
*{box-sizing:border-box;margin:0;padding:0;}
body{background:var(--bg);color:var(--text);font-family:var(--font);-webkit-font-smoothing:antialiased;}
.wrap{max-width:1300px;margin:0 auto;padding:0 24px 60px;}
.pg-header{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:14px;padding:26px 0 20px;}
.pg-ip{font-size:1.6rem;font-weight:600;font-family:var(--mono);display:flex;align-items:center;gap:10px;}
.pg-sub{font-size:.75rem;color:var(--muted);margin-top:5px;}
.back-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:20px;background:var(--surface);border:1px solid var(--border);color:var(--text2);font-size:.8rem;font-weight:500;text-decoration:none;transition:var(--t);}
.back-btn:hover{border-color:var(--accent);color:var(--accent);}
.days-filter{display:flex;gap:5px;flex-wrap:wrap;margin-bottom:22px;}
.pill{padding:5px 14px;border-radius:20px;border:1px solid var(--border2);background:var(--surface);font-size:.7rem;font-weight:500;color:var(--muted);font-family:var(--mono);text-decoration:none;transition:var(--t);}
.pill:hover{border-color:var(--accent);color:var(--accent);}
.pill.active{background:var(--accent);border-color:var(--accent);color:#fff;font-weight:600;}

.user-banner{display:flex;align-items:center;gap:14px;padding:14px 20px;margin-bottom:16px;background:linear-gradient(135deg,#fff8f5,#fff);border:1.5px solid rgba(245,99,10,.25);border-radius:var(--r);box-shadow:0 2px 12px rgba(245,99,10,.08);}
.user-avatar{width:46px;height:46px;border-radius:50%;border:2px solid rgba(245,99,10,.2);flex-shrink:0;background:var(--surface3);display:flex;align-items:center;justify-content:center;font-size:1.3rem;overflow:hidden;}
.user-avatar img{width:100%;height:100%;object-fit:cover;}
.user-info{flex:1;min-width:0;}
.user-name{font-size:.95rem;font-weight:600;}
.user-email{font-size:.75rem;color:var(--muted);font-family:var(--mono);margin-top:2px;}
.user-badges{display:flex;gap:6px;margin-top:5px;flex-wrap:wrap;}
.user-since{font-size:.68rem;color:var(--muted);margin-left:auto;text-align:right;line-height:1.5;flex-shrink:0;}
.user-since small{display:block;font-size:.65rem;}
.no-user-banner{display:flex;align-items:center;gap:10px;padding:11px 16px;margin-bottom:16px;background:#fafafa;border:1px dashed var(--border2);border-radius:var(--r);font-size:.78rem;color:var(--muted);}

.info-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:22px;}
@media(max-width:900px){.info-grid{grid-template-columns:repeat(2,1fr);}}
.info-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:15px 18px;box-shadow:var(--shadow);}
.info-label{font-size:.65rem;color:var(--muted);font-weight:600;letter-spacing:.08em;text-transform:uppercase;margin-bottom:5px;}
.info-val{font-size:1.4rem;font-weight:600;font-family:var(--mono);}
.info-sub{font-size:.7rem;color:var(--muted);margin-top:3px;}

.two-col{display:grid;grid-template-columns:1fr 340px;gap:16px;margin-bottom:16px;}
@media(max-width:1000px){.two-col{grid-template-columns:1fr;}}
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow);overflow:hidden;}
.card-head{padding:14px 18px 12px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.card-head h3{font-size:.88rem;font-weight:600;display:flex;align-items:center;gap:7px;}
.card-head .sub{font-size:.7rem;color:var(--muted);}
.card-body{padding:16px 18px;}
.meta-list{list-style:none;}
.meta-list li{display:flex;gap:8px;padding:8px 0;border-bottom:1px solid var(--border);font-size:.78rem;}
.meta-list li:last-child{border-bottom:none;}
.meta-list .mk{color:var(--muted);width:90px;flex-shrink:0;font-size:.7rem;text-transform:uppercase;font-weight:600;letter-spacing:.05em;padding-top:1px;}
.meta-list .mv{color:var(--text2);word-break:break-all;font-family:var(--mono);font-size:.75rem;}

.dl-tbl{width:100%;border-collapse:collapse;font-size:.8rem;}
.dl-tbl th{text-align:left;padding:8px 12px;font-size:.63rem;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--border2);background:var(--surface2);}
.dl-tbl td{padding:10px 12px;border-bottom:1px solid var(--border);vertical-align:middle;}
.dl-tbl tr:last-child td{border-bottom:none;}
.dl-tbl tr:hover td{background:rgba(245,99,10,.025);}
.ext-chip{display:inline-block;font-size:.62rem;font-weight:600;padding:2px 7px;border-radius:4px;text-transform:uppercase;letter-spacing:.04em;font-family:var(--mono);}
.dl-fname{font-size:.8rem;font-weight:500;}
.dl-user-cell{font-size:.75rem;font-family:var(--mono);}
.dl-time{font-size:.7rem;color:var(--muted);font-family:var(--mono);white-space:nowrap;}
.dl-empty{padding:28px;text-align:center;color:var(--muted);font-size:.78rem;}

.top-tbl{width:100%;border-collapse:collapse;font-size:.8rem;}
.top-tbl th{text-align:left;padding:7px 10px;font-size:.63rem;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--border2);}
.top-tbl td{padding:8px 10px;border-bottom:1px solid var(--border);vertical-align:middle;}
.top-tbl tr:last-child td{border-bottom:none;}
.url-cell{font-family:var(--mono);font-size:.72rem;color:var(--accent);max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.num{font-family:var(--mono);}

.badge{display:inline-flex;align-items:center;gap:3px;padding:2px 8px;border-radius:20px;font-size:.63rem;font-weight:500;border:1px solid;}
.badge-green{color:var(--green);background:rgba(46,158,91,.09);border-color:rgba(46,158,91,.2);}
.badge-blue{color:var(--blue);background:rgba(59,130,246,.09);border-color:rgba(59,130,246,.2);}
.badge-purple{color:var(--purple);background:rgba(139,92,246,.09);border-color:rgba(139,92,246,.2);}
.badge-gray{color:var(--muted);background:var(--surface2);border-color:var(--border2);}
.badge-orange{color:var(--accent);background:rgba(245,99,10,.08);border-color:rgba(245,99,10,.2);}

.not-found{text-align:center;padding:80px 20px;color:var(--muted);}
.not-found .icon{font-size:3rem;margin-bottom:14px;}

/* ══════════════════════════════════════
   FLOW DIAGRAM STYLES
══════════════════════════════════════ */
.flow-sessions{display:flex;flex-direction:column;gap:12px;}

.flow-block{
  background:var(--surface);
  border:1px solid var(--border);
  border-radius:var(--r);
  box-shadow:var(--shadow);
  overflow:hidden;
}

.flow-head{
  display:flex;align-items:center;gap:12px;
  padding:13px 18px;cursor:pointer;user-select:none;
  transition:background var(--t);
}
.flow-head:hover{background:var(--surface2);}
.flow-num{
  font-family:var(--mono);font-size:.72rem;font-weight:600;
  background:var(--accent);color:#fff;
  padding:2px 10px;border-radius:20px;flex-shrink:0;
}
.flow-info{flex:1;display:flex;align-items:center;flex-wrap:wrap;gap:8px;}
.flow-date{font-size:.78rem;font-weight:500;}
.flow-meta{font-size:.7rem;color:var(--muted);}
.flow-arrow{font-size:.75rem;color:var(--muted);transition:transform .2s ease;flex-shrink:0;}
.flow-block.open .flow-arrow{transform:rotate(180deg);}

.flow-refbar{
  display:flex;align-items:center;gap:8px;
  padding:6px 18px;
  background:#faf9f6;border-top:1px solid rgba(0,0,0,0.06);
  font-size:.7rem;color:var(--muted);
}

.flow-canvas-wrap{
  display:none;
  border-top:1px solid rgba(0,0,0,0.07);
  background:#f0ede6;
  position:relative;
}
.flow-block.open .flow-canvas-wrap{display:block;}

.flow-svg-outer{
  width:100%;height:300px;
  overflow:hidden;cursor:grab;
  position:relative;
}
.flow-svg-outer:active{cursor:grabbing;}
.flow-svg-outer svg{display:block;}

/* Zoom controls */
.flow-zoom-btns{
  position:absolute;top:10px;right:10px;
  display:flex;gap:4px;z-index:10;
}
.flow-zoom-btn{
  width:28px;height:28px;border-radius:8px;
  border:1px solid rgba(0,0,0,0.13);background:rgba(255,255,255,0.9);
  font-size:.85rem;cursor:pointer;display:flex;
  align-items:center;justify-content:center;
  box-shadow:0 1px 6px rgba(0,0,0,.1);
  transition:all .15s;font-family:var(--mono);font-weight:700;
  backdrop-filter:blur(4px);
}
.flow-zoom-btn:hover{border-color:var(--accent);color:var(--accent);background:#fff;}

/* Tooltip */
.flow-tooltip{
  position:absolute;pointer-events:none;
  background:#1a1612;color:#fff;
  border-radius:10px;padding:10px 14px;
  font-size:.73rem;font-family:var(--font);
  box-shadow:0 8px 24px rgba(0,0,0,.3);
  max-width:240px;z-index:100;
  opacity:0;transition:opacity .12s ease;
  line-height:1.65;
}
.flow-tooltip.visible{opacity:1;}
.flow-tooltip .tt-title{font-weight:600;font-size:.78rem;margin-bottom:5px;color:#fff;}
.flow-tooltip .tt-url{font-family:var(--mono);font-size:.64rem;color:#aaa;margin-bottom:7px;word-break:break-all;}
.flow-tooltip .tt-row{display:flex;gap:6px;align-items:baseline;font-size:.7rem;color:#ccc;margin-top:2px;}
.flow-tooltip .tt-label{color:#777;min-width:55px;font-size:.65rem;}

/* Legend */
.flow-legend{
  display:flex;gap:18px;padding:8px 16px;
  border-top:1px solid rgba(0,0,0,0.07);
  background:rgba(255,255,255,0.5);
  font-size:.65rem;color:var(--muted);
  flex-wrap:wrap;
}
.flow-leg-item{display:flex;align-items:center;gap:5px;}
.flow-leg-dot{width:10px;height:10px;border-radius:3px;flex-shrink:0;}
.sidebar {
    width: 250px;
    background-color: #fafafa;
    position: fixed;
    height: 100%;
    padding-top: 20px;
    box-shadow: 2px 2px 4px 0px #e8e8ea;
    overflow-y: auto;
    z-index: 999;
}
</style>
</head>
<body>
<?php include 'template/header.php'; ?>
<div class="wrap">

<?php if (!$info): ?>
<div class="pg-header">
  <div><h1 class="pg-ip">❌ <?= htmlspecialchars($ip) ?></h1></div>
  <a href="ip_visitors.php" class="back-btn">← กลับ</a>
</div>
<div class="not-found"><div class="icon">📭</div><p>ไม่พบข้อมูลสำหรับ IP นี้</p></div>
<?php else: ?>

<div class="pg-header">
  <div>
    <h1 class="pg-ip">🌐 <?= htmlspecialchars($ip) ?></h1>
    <p class="pg-sub"><?= htmlspecialchars($info['country']??'—') ?><?= $info['city']?' · '.htmlspecialchars($info['city']):'' ?> · <?= deviceIcon($info['device_type']??'') ?> <?= htmlspecialchars($info['os']??'—') ?> / <?= htmlspecialchars($info['browser']??'—') ?></p>
  </div>
  <a href="ip_visitors.php?days=<?= $days ?>" class="back-btn">← กลับรายการ</a>
</div>

<div class="days-filter">
  <?php foreach([1=>'วันนี้',3=>'3d',7=>'7d',30=>'30d',90=>'90d'] as $d=>$label): ?>
    <a class="pill <?= $days==$d?'active':'' ?>" href="?ip=<?= urlencode($ip) ?>&days=<?= $d ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<?php if ($linkedUser): ?>
<div class="user-banner">
  <div class="user-avatar">
    <?php if (!empty($linkedUser['profile_img'])): ?><img src="<?= htmlspecialchars($linkedUser['profile_img']) ?>" alt=""><?php else: ?>👤<?php endif; ?>
  </div>
  <div class="user-info">
    <div class="user-name">
      <?= htmlspecialchars(trim($linkedUser['first_name'].' '.$linkedUser['last_name']))?:'(ไม่ระบุชื่อ)' ?>
      <span style="font-size:.7rem;color:var(--muted);font-weight:400;margin-left:6px;font-family:var(--mono)">#<?= $linkedUser['user_id'] ?></span>
    </div>
    <div class="user-email"><?= htmlspecialchars($linkedUser['email']) ?></div>
    <div class="user-badges">
      <?php if ($linkedUser['email_verified']): ?><span class="badge badge-green">✓ Email verified</span><?php endif; ?>
      <?php if ($linkedUser['verify']): ?><span class="badge badge-blue">✓ Verified</span><?php endif; ?>
      <?php if (!empty($linkedUser['phone_number'])): ?><span class="badge badge-gray">📞 <?= htmlspecialchars($linkedUser['phone_number']) ?></span><?php endif; ?>
    </div>
  </div>
  <div class="user-since">สมัครสมาชิก<small><?= $linkedUser['date_create']?date('d/m/Y',strtotime($linkedUser['date_create'])):'—' ?></small></div>
</div>
<?php else: ?>
<div class="no-user-banner">🔍 <span>IP นี้ยังไม่เชื่อมกับ user ใดๆ — ยังไม่เคย login หรือ register</span></div>
<?php endif; ?>

<div class="info-grid">
  <div class="info-card"><div class="info-label">Sessions</div><div class="info-val"><?= number_format($info['sessions']) ?></div><div class="info-sub">การเยี่ยมชม</div></div>
  <div class="info-card"><div class="info-label">Total Views</div><div class="info-val"><?= number_format($info['total_views']) ?></div><div class="info-sub">หน้าที่ถูกเปิด</div></div>
  <div class="info-card"><div class="info-label">Total Time</div><div class="info-val"><?= fmtTime($info['total_time']) ?></div><div class="info-sub">เวลารวมบนเว็บ</div></div>
  <div class="info-card"><div class="info-label">Downloads</div><div class="info-val"><?= number_format(count($downloads)) ?></div><div class="info-sub">ไฟล์ที่ดาวน์โหลด</div></div>
</div>

<div class="two-col">
  <div style="display:flex;flex-direction:column;gap:16px;">

    <!-- Download history -->
    <div class="card">
      <div class="card-head"><h3>📥 Download History</h3><span class="sub"><?= count($downloads) ?> รายการ</span></div>
      <?php if (empty($downloads)): ?>
      <div class="dl-empty">📂 ยังไม่มีการดาวน์โหลดไฟล์</div>
      <?php else: ?>
      <div style="overflow-x:auto;">
        <table class="dl-tbl">
          <thead><tr><th>ไฟล์</th><th>ผู้ใช้</th><th>เวลา</th></tr></thead>
          <tbody>
            <?php foreach ($downloads as $dl):
              $ext = strtolower(trim($dl['file_ext']??'','.'));
              [$chipBg,$chipColor] = extColor($ext);
              $lbl = strtoupper($ext?:'?');
              $hasUser = !empty($dl['email']);
            ?>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:8px;">
                  <span class="ext-chip" style="background:<?= $chipBg ?>;color:<?= $chipColor ?>;"><?= $lbl ?></span>
                  <span class="dl-fname"><?= htmlspecialchars($dl['file_original']??'(ไม่ทราบ)') ?></span>
                </div>
              </td>
              <td>
                <?php if ($hasUser): ?>
                  <div class="dl-user-cell"><?= htmlspecialchars(trim($dl['first_name'].' '.$dl['last_name']))?:'—' ?></div>
                  <div style="font-size:.65rem;color:var(--muted);font-family:var(--mono)"><?= htmlspecialchars($dl['email']) ?></div>
                <?php else: ?>
                  <span style="color:var(--muted);font-size:.72rem">Guest</span>
                <?php endif; ?>
              </td>
              <td class="dl-time"><?= $dl['downloaded_at']?date('d/m/Y H:i',strtotime($dl['downloaded_at'])):'—' ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- ══ SESSION FLOW ══════════════════════════════════ -->
    <div class="card" style="padding:14px 18px 12px;margin-bottom:0;">
      <div style="display:flex;align-items:center;justify-content:space-between;">
        <h3 style="font-size:.88rem;font-weight:600;display:flex;align-items:center;gap:7px;">🗺️ Session Flow</h3>
        <span style="font-size:.7rem;color:var(--muted);"><?= count($sessions) ?> sessions</span>
      </div>
    </div>
    <div style="height:4px"></div>

    <div class="flow-sessions">
    <?php foreach ($sessions as $si => $sess):
      $pages = $pagesBySession[$sess['session_id']] ?? [];
      $evts  = $eventsBySession[$sess['session_id']] ?? [];
      $sDate = date('d/m/Y H:i', strtotime($sess['started_at']));
      $isOpen = $si === 0 ? 'open' : '';
      $sessLoggedIn = !empty($sess['user_id']) && $sess['user_id'] > 0;
      $sessIdx = count($sessions) - $si;

      /* ── Build timeline items ── */
      $tlItems = [];
      foreach ($pages as $pv) {
        $tlItems[] = [
          'type'       => 'page',
          'ts'         => strtotime($pv['entered_at']),
          'time'       => date('H:i:s', strtotime($pv['entered_at'])),
          'title'      => $pv['page_title'] ?: preg_replace('#^https?://[^/]+#','', $pv['url']),
          'url'        => preg_replace('#^https?://[^/]+#','', $pv['url']),
          'timeOnPage' => (int)$pv['time_on_page'],
          'scroll'     => (int)($pv['scroll_depth'] ?? 0),
          'isBounce'   => !empty($pv['is_bounce']),
        ];
      }
      foreach ($evts as $ev) {
        $tlItems[] = [
          'type'   => 'event',
          'ts'     => strtotime($ev['occurred_at']),
          'time'   => date('H:i:s', strtotime($ev['occurred_at'])),
          'name'   => $ev['event_name'],
          'target' => $ev['event_target'] ?? '',
          'value'  => $ev['event_value']  ?? '',
        ];
      }
      usort($tlItems, fn($a,$b) => $a['ts'] - $b['ts']);

      /* Merge consecutive same-URL page visits */
      $merged = [];
      foreach ($tlItems as $item) {
        if ($item['type'] === 'page' && !empty($merged)) {
          $last = &$merged[count($merged)-1];
          if ($last['type'] === 'page' && $last['url'] === $item['url']) {
            $last['visits'] = ($last['visits'] ?? 1) + 1;
            $last['timeOnPage'] += $item['timeOnPage'];
            continue;
          }
        }
        $item['visits'] = 1;
        $merged[] = $item;
      }

      $flowJson = json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    ?>
    <div class="flow-block <?= $isOpen ?>" id="flow-block-<?= $si ?>">

      <div class="flow-head" onclick="toggleFlow(<?= $si ?>)">
        <span class="flow-num">Session <?= $sessIdx ?></span>
        <div class="flow-info">
          <span class="flow-date"><?= $sDate ?></span>
          <?= srcBadge($sess['referrer_source']??'direct') ?>
          <?php if ($sessLoggedIn): ?><span style="font-size:.68rem;color:var(--green);font-weight:600">🔐 logged in</span><?php endif; ?>
          <span class="flow-meta"><?= deviceIcon($sess['device_type']??'') ?> <?= htmlspecialchars($sess['os']??'') ?>/<?= htmlspecialchars($sess['browser']??'') ?></span>
          <span class="flow-meta">📄 <?= $sess['page_count'] ?> หน้า · ⏱ <?= fmtTime($sess['duration_sec']) ?></span>
        </div>
        <span class="flow-arrow">▼</span>
      </div>

      <?php if (!empty($sess['referrer'])): ?>
      <div class="flow-refbar">🔗 จาก: <span style="font-family:var(--mono);color:var(--text2)"><?= htmlspecialchars(substr($sess['referrer'],0,100)) ?></span></div>
      <?php endif; ?>

      <div class="flow-canvas-wrap" id="flow-wrap-<?= $si ?>">
        <div class="flow-svg-outer" id="flow-outer-<?= $si ?>">
          <svg id="flow-svg-<?= $si ?>"></svg>
          <div class="flow-tooltip" id="flow-tip-<?= $si ?>"></div>
        </div>
        <div class="flow-zoom-btns">
          <button class="flow-zoom-btn" onclick="flowZoom(<?= $si ?>, 1.3)" title="Zoom in">+</button>
          <button class="flow-zoom-btn" onclick="flowZoom(<?= $si ?>, 0.75)" title="Zoom out">−</button>
          <button class="flow-zoom-btn" onclick="flowReset(<?= $si ?>)" title="Reset view">⌂</button>
        </div>
        <div class="flow-legend">
          <div class="flow-leg-item"><div class="flow-leg-dot" style="background:#3ecfcf;border-radius:4px"></div>หน้าเว็บ</div>
          <div class="flow-leg-item"><div class="flow-leg-dot" style="background:#f5a623;border-radius:50%"></div>Event</div>
          <div class="flow-leg-item"><div class="flow-leg-dot" style="background:#e84855;border-radius:4px"></div>Bounce</div>
          <div class="flow-leg-item"><div class="flow-leg-dot" style="background:#7c6bff;border-radius:4px"></div>เปิดซ้ำ</div>
          <div class="flow-leg-item" style="color:var(--muted)">── ความหนาเส้น = เวลาในหน้า</div>
        </div>
      </div>

      <script>window.__fd=window.__fd||{};window.__fd[<?= $si ?>]=<?= $flowJson ?>;</script>
    </div>
    <?php endforeach; ?>
    </div>
    <!-- ══ END SESSION FLOW ══ -->

  </div>

  <!-- Sidebar -->
  <div style="display:flex;flex-direction:column;gap:16px;">
    <div class="card">
      <div class="card-head"><h3>🖥️ Device & Info</h3></div>
      <div class="card-body" style="padding:0 8px;">
        <ul class="meta-list">
          <li><span class="mk">IP</span><span class="mv"><?= htmlspecialchars($ip) ?></span></li>
          <?php if ($linkedUser): ?>
          <li><span class="mk">User</span><span class="mv" style="color:var(--accent)"><?= htmlspecialchars(trim($linkedUser['first_name'].' '.$linkedUser['last_name']))?:'—' ?><span style="color:var(--muted);font-size:.65rem"> · <?= htmlspecialchars($linkedUser['email']) ?></span></span></li>
          <?php endif; ?>
          <li><span class="mk">ประเทศ</span><span class="mv"><?= htmlspecialchars($info['country']??'—') ?></span></li>
          <li><span class="mk">จังหวัด</span><span class="mv"><?= htmlspecialchars($info['province']??'—') ?></span></li>
          <li><span class="mk">เมือง</span><span class="mv"><?= htmlspecialchars($info['city']??'—') ?></span></li>
          <li><span class="mk">OS</span><span class="mv"><?= htmlspecialchars($info['os']??'—') ?></span></li>
          <li><span class="mk">Browser</span><span class="mv"><?= htmlspecialchars($info['browser']??'—') ?></span></li>
          <li><span class="mk">Device</span><span class="mv"><?= deviceIcon($info['device_type']??'') ?> <?= htmlspecialchars($info['device_type']??'—') ?></span></li>
          <li><span class="mk">First Seen</span><span class="mv"><?= $info['first_seen']?date('d/m/Y H:i',strtotime($info['first_seen'])):'—' ?></span></li>
          <li><span class="mk">Last Seen</span><span class="mv"><?= $info['last_seen']?date('d/m/Y H:i',strtotime($info['last_seen'])):'—' ?></span></li>
        </ul>
      </div>
    </div>
    <div class="card">
      <div class="card-head"><h3>📊 Top Pages</h3><span class="sub">ของ IP นี้</span></div>
      <div style="overflow-x:auto;">
        <table class="top-tbl">
          <thead><tr><th>หน้า</th><th>Views</th><th>Avg</th></tr></thead>
          <tbody>
            <?php foreach($topPages as $tp): ?>
            <tr>
              <td>
                <div class="url-cell" title="<?= htmlspecialchars($tp['url']) ?>"><?= htmlspecialchars(shortUrl($tp['url'])) ?></div>
                <?php if ($tp['page_title']): ?><div style="font-size:.65rem;color:var(--muted)"><?= htmlspecialchars(substr($tp['page_title'],0,40)) ?></div><?php endif; ?>
              </td>
              <td><span class="num"><?= $tp['views'] ?></span></td>
              <td><span class="num"><?= fmtTime($tp['avg_time']) ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($topPages)): ?>
            <tr><td colspan="3" style="text-align:center;padding:18px;color:var(--muted);font-size:.75rem">ไม่มีข้อมูล</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php endif; ?>
</div><!-- /wrap -->

<!-- ══ D3 FLOW ENGINE ══════════════════════════════════════════ -->
<script>
(function(){
  const C = {
    page:'#3ecfcf', pageBg:'#e8fbfb',
    bounce:'#e84855', bounceBg:'#fde8ea',
    revisit:'#7c6bff', revisitBg:'#f0eeff',
    event:'#f5a623',
    link:'#c5d0dc', linkHover:'#f5630a',
    text:'#1a1612', muted:'#9a8e84',
  };
  const NW=170, NH=72, GAP=90, PAD=50, EVR=13;

  const _zb={}, _init=new Set();

  /* ── public API ── */
  window.toggleFlow = function(idx) {
    const block = document.getElementById('flow-block-'+idx);
    block.classList.toggle('open');
    if (block.classList.contains('open') && !_init.has(idx)) {
      _init.add(idx);
      setTimeout(()=>buildFlow(idx), 60);
    }
  };
  window.flowZoom  = (idx,f) => { const s=d3.select('#flow-svg-'+idx); _zb[idx]&&s.transition().duration(260).call(_zb[idx].scaleBy,f); };
  window.flowReset = (idx)   => { const s=d3.select('#flow-svg-'+idx); _zb[idx]&&s.transition().duration(340).call(_zb[idx].transform, d3.zoomIdentity.translate(PAD,0).scale(1)); };

  /* ── auto init first block ── */
  document.querySelectorAll('.flow-block.open').forEach(b=>{
    const idx = +b.id.replace('flow-block-','');
    if (!_init.has(idx)) { _init.add(idx); setTimeout(()=>buildFlow(idx),80); }
  });

  /* ══ MAIN BUILD ══════════════════════════════════════════════ */
  function buildFlow(idx) {
    const data = (window.__fd||{})[idx];
    if (!data || !data.length) return;

    const outer = document.getElementById('flow-outer-'+idx);
    const svgEl = document.getElementById('flow-svg-'+idx);
    const tipEl = document.getElementById('flow-tip-'+idx);
    if (!outer||!svgEl) return;

    const H = outer.clientHeight || 300;

    /* ── Layout ── */
    const nodes=[], links=[];
    let x=PAD, prevPageNode=null, pendingEvts=[];

    data.forEach((item,i)=>{
      if (item.type==='page') {
        /* Flush queued events as mid-link nodes */
        const evNodes=[];
        pendingEvts.forEach((ev,ei)=>{
          const en={id:'ev'+i+'_'+ei, type:'event', data:ev,
            x: x - GAP/2 + (ei-(pendingEvts.length-1)/2)*30,
            y: H/2 + (ei%2===0?40:-40), r:EVR};
          nodes.push(en);
          evNodes.push(en);
        });
        pendingEvts=[];

        const isB = item.isBounce;
        const isR = (item.visits||1)>1;
        const pn = {
          id:'page'+i, type:'page', data:item,
          x, y:H/2-NH/2, w:NW, h:NH,
          col: isB?C.bounce : isR?C.revisit : C.page,
          bg:  isB?C.bounceBg : isR?C.revisitBg : C.pageBg,
        };
        nodes.push(pn);

        if (prevPageNode) {
          const weight = Math.min(9, Math.max(2, (prevPageNode.data.timeOnPage||0)/20));
          if (evNodes.length===0) {
            links.push({s:prevPageNode, t:pn, w:weight});
          } else {
            let prev=prevPageNode;
            evNodes.forEach(ev=>{ links.push({s:prev,t:ev,w:1.5,dashed:true}); prev=ev; });
            links.push({s:prev,t:pn,w:1.5,dashed:true});
          }
        }
        prevPageNode=pn;
        x+=NW+GAP;
      } else {
        pendingEvts.push(item);
      }
    });

    /* Trailing events */
    if (pendingEvts.length && prevPageNode) {
      pendingEvts.forEach((ev,ei)=>{
        const en={id:'ev_tail'+ei, type:'event', data:ev,
          x:prevPageNode.x+NW+GAP/2, y:H/2+(ei%2===0?36:-36), r:EVR};
        nodes.push(en);
        links.push({s:prevPageNode,t:en,w:1.5,dashed:true});
      });
    }

    const totalW = Math.max(outer.clientWidth||800, x+PAD);

    /* ── D3 ── */
    d3.select(svgEl).selectAll('*').remove();
    const svg = d3.select(svgEl).attr('width',totalW).attr('height',H);
    const g   = svg.append('g');

    /* Zoom */
    const zoom = d3.zoom().scaleExtent([0.25,3])
      .on('zoom', e=>g.attr('transform',e.transform));
    _zb[idx]=zoom;
    svg.call(zoom).call(zoom.transform, d3.zoomIdentity.translate(PAD/2,0));

    /* Subtle grid bg */
    const defs = svg.append('defs');
    defs.append('pattern').attr('id','grid'+idx)
      .attr('width',28).attr('height',28).attr('patternUnits','userSpaceOnUse')
      .append('path').attr('d','M 28 0 L 0 0 0 28').attr('fill','none')
      .attr('stroke','rgba(0,0,0,0.045)').attr('stroke-width',1);
    g.append('rect').attr('width',totalW*4).attr('height',H*4)
      .attr('x',-totalW).attr('y',-H).attr('fill',`url(#grid${idx})`);

    /* Arrow */
    defs.append('marker').attr('id','arr'+idx)
      .attr('viewBox','0 -4 8 8').attr('refX',7).attr('refY',0)
      .attr('markerWidth',6).attr('markerHeight',6).attr('orient','auto')
      .append('path').attr('d','M0,-4L8,0L0,4').attr('fill',C.link);

    /* Links */
    const linkSel = g.selectAll('.fl').data(links).join('path').attr('class','fl')
      .attr('fill','none')
      .attr('stroke', C.link)
      .attr('stroke-width', d=>d.w||2)
      .attr('stroke-dasharray', d=>{
        if (d.dashed) return '5,3';
        return null;
      })
      .attr('marker-end',`url(#arr${idx})`)
      .attr('opacity',0.55)
      .attr('d', d=>{
        const sx = d.s.type==='page' ? d.s.x+d.s.w : d.s.x;
        const sy = d.s.type==='page' ? d.s.y+d.s.h/2 : d.s.y;
        const tx = d.t.type==='page' ? d.t.x : d.t.x;
        const ty = d.t.type==='page' ? d.t.y+d.t.h/2 : d.t.y;
        const mx=(sx+tx)/2;
        return `M${sx},${sy}C${mx},${sy} ${mx},${ty} ${tx},${ty}`;
      });

    /* Page nodes */
    const pgNodes = nodes.filter(n=>n.type==='page');
    const pgG = g.selectAll('.fpn').data(pgNodes).join('g').attr('class','fpn')
      .attr('transform',d=>`translate(${d.x},${d.y})`).style('cursor','pointer');

    /* Drop shadow filter */
    const flt = defs.append('filter').attr('id','shadow'+idx).attr('x','-10%').attr('y','-10%').attr('width','120%').attr('height','130%');
    flt.append('feDropShadow').attr('dx',0).attr('dy',3).attr('stdDeviation',4).attr('flood-color','rgba(0,0,0,0.12)');

    pgG.append('rect').attr('width',d=>d.w).attr('height',d=>d.h)
      .attr('rx',10).attr('fill',d=>d.bg)
      .attr('stroke',d=>d.col).attr('stroke-width',1.8)
      .attr('filter',`url(#shadow${idx})`);

    /* Top accent strip */
    pgG.append('rect').attr('width',d=>d.w).attr('height',3).attr('rx',10)
      .attr('fill',d=>d.col);

    /* Step number circle */
    pgG.append('circle').attr('cx',14).attr('cy',14).attr('r',9)
      .attr('fill',d=>d.col).attr('opacity',0.18);
    pgG.append('text').attr('x',14).attr('y',14).attr('text-anchor','middle').attr('dy','0.35em')
      .attr('fill',d=>d.col).attr('font-size',8).attr('font-weight',700).attr('font-family','monospace')
      .text((d,i)=>i+1);

    /* Visit badge */
    pgG.filter(d=>(d.data.visits||1)>1)
      .append('g').attr('transform',d=>`translate(${d.w-14},10)`)
      .call(g2=>{
        g2.append('circle').attr('r',9).attr('fill','#7c6bff');
        g2.append('text').attr('text-anchor','middle').attr('dy','0.35em')
          .attr('fill','#fff').attr('font-size',8).attr('font-weight',700).attr('font-family','monospace')
          .text(d=>'×'+(d.data.visits||1));
      });

    /* Title */
    pgG.append('text').attr('x',28).attr('y',19)
      .attr('fill',C.text).attr('font-size',10.5).attr('font-weight',600)
      .attr('font-family',"'DM Sans',sans-serif")
      .text(d=>{ const t=d.data.title||d.data.url||'—'; return t.length>17?t.slice(0,16)+'…':t; });

    /* URL */
    pgG.append('text').attr('x',10).attr('y',34)
      .attr('fill',C.muted).attr('font-size',8.5)
      .attr('font-family',"'DM Mono',monospace")
      .text(d=>{ const u=d.data.url||''; return u.length>22?u.slice(0,21)+'…':u; });

    /* Time chip */
    pgG.append('rect').attr('x',10).attr('y',40).attr('height',14).attr('rx',4)
      .attr('fill',d=>d.col).attr('opacity',0.12)
      .attr('width',d=>{
        const s=d.data.timeOnPage||0;
        const txt = s<60?s+'s':`${Math.floor(s/60)}m${s%60}s`;
        return txt.length*6+10;
      });
    pgG.append('text').attr('x',15).attr('y',50)
      .attr('fill',d=>d.col).attr('font-size',9).attr('font-weight',600)
      .attr('font-family',"'DM Mono',monospace")
      .text(d=>{ const s=d.data.timeOnPage||0; return s<60?`⏱${s}s`:`⏱${Math.floor(s/60)}m${s%60}s`; });

    /* Scroll bar */
    const SBX=10, SBY=58, SBW=NW-20, SBH=5;
    pgG.append('rect').attr('x',SBX).attr('y',SBY).attr('width',SBW).attr('height',SBH)
      .attr('rx',2.5).attr('fill','rgba(0,0,0,0.07)');
    pgG.append('rect').attr('x',SBX).attr('y',SBY).attr('height',SBH).attr('rx',2.5)
      .attr('width',d=>SBW*(d.data.scroll||0)/100)
      .attr('fill',d=>{ const sc=d.data.scroll||0; return sc>=75?'#2e9e5b':sc>=40?'#f5a623':'#e84855'; });
    pgG.append('text').attr('x',SBX+SBW+4).attr('y',SBY+4.5)
      .attr('fill',C.muted).attr('font-size',7.5).attr('font-family','monospace')
      .text(d=>(d.data.scroll||0)+'%');

    /* Hover */
    pgG.on('mouseenter',function(ev,d){
        d3.select(this).select('rect').attr('stroke-width',2.8);
        showTip(tipEl,outer,ev,d.data,'page');
      })
      .on('mousemove',(ev)=>moveTip(tipEl,outer,ev))
      .on('mouseleave',function(){
        d3.select(this).select('rect').attr('stroke-width',1.8);
        hideTip(tipEl);
      });

    /* Event nodes */
    const evG = g.selectAll('.fen').data(nodes.filter(n=>n.type==='event')).join('g')
      .attr('class','fen').attr('transform',d=>`translate(${d.x},${d.y})`).style('cursor','pointer');

    evG.append('circle').attr('r',d=>d.r).attr('fill',C.event)
      .attr('stroke','#fff').attr('stroke-width',2)
      .attr('filter',`url(#shadow${idx})`);
    evG.append('text').attr('text-anchor','middle').attr('dy','0.35em')
      .attr('font-size',11).text('⚡');

    evG.on('mouseenter',(ev,d)=>showTip(tipEl,outer,ev,d.data,'event'))
       .on('mousemove',(ev)=>moveTip(tipEl,outer,ev))
       .on('mouseleave',()=>hideTip(tipEl));

    /* ── Entrance animation ── */
    pgG.attr('opacity',0).transition().duration(320).delay((_,i)=>i*55).attr('opacity',1);
    evG.attr('opacity',0).transition().duration(220).delay(280).attr('opacity',1);
    linkSel.each(function(){
      const len=this.getTotalLength?.()??300;
      d3.select(this)
        .attr('stroke-dashoffset',len)
        .attr('stroke-dasharray',(d)=>d.dashed?`5,3,${len}`:len)
        .transition().duration(480).delay((_,i)=>i*40)
        .attr('stroke-dashoffset',0)
        .on('end',function(d){ if(!d.dashed) d3.select(this).attr('stroke-dasharray',null); });
    });
  }

  /* ── Tooltip ── */
  function showTip(el,outer,ev,data,type){
    let html='';
    if (type==='page') {
      const s=data.timeOnPage||0;
      const t=s<60?s+'s':`${Math.floor(s/60)}m ${s%60}s`;
      html=`
        <div class="tt-title">${esc(data.title||'—')}</div>
        <div class="tt-url">${esc(data.url||'')}</div>
        <div class="tt-row"><span class="tt-label">เวลาในหน้า</span><b>${t}</b></div>
        <div class="tt-row"><span class="tt-label">Scroll depth</span><b>${data.scroll||0}%</b></div>
        <div class="tt-row"><span class="tt-label">เข้าชม</span><b>${data.visits||1} ครั้ง</b></div>
        ${data.isBounce?'<div class="tt-row" style="color:#f87171;margin-top:4px">⚠ Bounce — ออกหน้าแรก</div>':''}
        <div class="tt-row" style="margin-top:4px"><span class="tt-label">เวลา</span>${data.time}</div>`;
    } else {
      html=`
        <div class="tt-title">⚡ ${esc(data.name||'—')}</div>
        ${data.target?`<div class="tt-row"><span class="tt-label">Target</span>${esc(data.target)}</div>`:''}
        ${data.value?`<div class="tt-row"><span class="tt-label">Value</span>${esc(data.value)}</div>`:''}
        <div class="tt-row" style="margin-top:4px"><span class="tt-label">เวลา</span>${data.time}</div>`;
    }
    el.innerHTML=html; el.classList.add('visible'); moveTip(el,outer,ev);
  }
  function moveTip(el,outer,ev){
    const r=outer.getBoundingClientRect();
    let x=ev.clientX-r.left+14, y=ev.clientY-r.top-14;
    if(x+250>outer.clientWidth) x-=265;
    if(y+160>outer.clientHeight) y-=170;
    el.style.left=x+'px'; el.style.top=y+'px';
  }
  function hideTip(el){ el.classList.remove('visible'); }
  function esc(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
})();
</script>
<script src="js/index_.js?v=<?= time(); ?>"></script>
</body>
</html>