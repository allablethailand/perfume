<?php 
include 'check_permission.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$lang = $_SESSION['lang'] ?? 'th';
if (isset($_GET['lang'])) {
    $supportedLangs = ['th','en','cn','jp','kr'];
    $newLang = $_GET['lang'];
    $_SESSION['lang'] = in_array($newLang,$supportedLangs) ? $newLang : 'th';
    $lang = $_SESSION['lang'];
}
$translations = [
    'th' => ['title'=>'Admin Dashboard','greeting_morning'=>'อรุณสวัสดิ์','greeting_afternoon'=>'สวัสดีตอนบ่าย','greeting_evening'=>'สวัสดีตอนเย็น','dashboard_title'=>'ภาพรวมระบบ'],
    'en' => ['title'=>'Admin Dashboard','greeting_morning'=>'Good Morning','greeting_afternoon'=>'Good Afternoon','greeting_evening'=>'Good Evening','dashboard_title'=>'System Overview'],
    'cn' => ['title'=>'管理员后台','greeting_morning'=>'早上好','greeting_afternoon'=>'下午好','greeting_evening'=>'晚上好','dashboard_title'=>'系统概览'],
    'jp' => ['title'=>'管理者ダッシュボード','greeting_morning'=>'おはようございます','greeting_afternoon'=>'こんにちは','greeting_evening'=>'こんばんは','dashboard_title'=>'システム概要'],
    'kr' => ['title'=>'관리자 대시보드','greeting_morning'=>'좋은 아침입니다','greeting_afternoon'=>'안녕하세요','greeting_evening'=>'안녕하세요','dashboard_title'=>'시스템 개요'],
];
$currentLang = $translations[$lang];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $currentLang['title'] ?></title>
<link rel="icon" type="image/x-icon" href="../../public/product_images/695e0bf362d49_1767771123.jpg">
<?php include 'inc_head.php'; ?>
<link href="css/index_.css?v=<?= time(); ?>" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
:root {
  --bg:        #f5f4f0;
  --surface:   #ffffff;
  --surface2:  #faf9f6;
  --surface3:  #f0ede6;
  --border:    rgba(0,0,0,0.07);
  --border2:   rgba(0,0,0,0.13);
  --text:      #1a1612;
  --text2:     #4a3f35;
  --muted:     #9a8e84;
  --accent:    #f5630a;
  --accent2:   #ff9c42;
  --accent3:   #e84855;
  --green:     #2e9e5b;
  --r:         12px;
  --r-sm:      8px;
  --shadow:    0 2px 12px rgba(0,0,0,0.08);
  --shadow-lg: 0 6px 28px rgba(0,0,0,0.13);
  --ease:      cubic-bezier(0.4,0,0.2,1);
  --t:         0.2s var(--ease);
  --font:      'DM Sans', system-ui, sans-serif;
  --mono:      'DM Mono', monospace;
}
body { background:var(--bg) !important; color:var(--text) !important; font-family:var(--font) !important; -webkit-font-smoothing:antialiased; }

/* ── Map Hero ─────────────────────────────────── */
.map-hero { width:100%; background:var(--surface); border-bottom:1px solid var(--border); }
.map-hero-top {
  max-width:1440px; margin:0 auto; padding:18px 24px 14px;
  display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;
}
.map-title-block h4 { margin:0; font-size:.95rem; font-weight:600; color:var(--text); display:flex; align-items:center; gap:8px; }
.live-badge {
  display:inline-flex; align-items:center; gap:4px; font-size:.67rem; font-weight:500;
  color:var(--accent); background:rgba(245,99,10,0.08); border:1px solid rgba(245,99,10,0.2);
  padding:2px 8px; border-radius:20px; font-family:var(--mono); letter-spacing:.04em;
}
.live-badge::before { content:''; width:5px; height:5px; border-radius:50%; background:var(--accent); animation:blink 1.4s infinite; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }
.map-title-block p { margin:3px 0 0; font-size:.75rem; color:var(--muted); }
.zoom-hint {
  display:inline-flex; align-items:center; gap:6px; font-size:.72rem; color:var(--muted);
  background:var(--surface2); border:1px solid var(--border2); padding:5px 14px; border-radius:20px;
}
.zoom-hint b { font-family:var(--mono); color:var(--accent); }
#mapContainer { width:100%; height:430px; }
.map-footer { display:flex; flex-wrap:wrap; gap:5px 16px; padding:10px 24px; background:var(--surface3); border-top:1px solid var(--border); font-size:.72rem; color:var(--muted); }
.mleg-item { display:flex; align-items:center; gap:6px; }
.mleg-dot  { width:8px; height:8px; border-radius:50%; flex-shrink:0; }

/* ── Leaflet Light Theme ──────────────────────── */
.leaflet-popup-content-wrapper {
  background:#fff !important;
  border:1px solid rgba(0,0,0,0.1) !important;
  border-radius:var(--r-sm) !important;
  box-shadow:0 4px 20px rgba(0,0,0,0.12) !important;
  color:var(--text) !important;
  font-family:var(--font) !important;
  font-size:.82rem !important;
  min-width:140px;
}
.leaflet-popup-tip { background:#fff !important; }
.leaflet-popup-close-button { color:var(--muted) !important; top:6px !important; right:8px !important; }
.pop-name  { font-weight:600; color:var(--text); margin-bottom:3px; font-size:.85rem; }
.pop-sub   { font-size:.7rem; color:var(--muted); margin-bottom:5px; }
.pop-count { font-size:1.15rem; font-weight:600; color:var(--accent); font-family:var(--mono); line-height:1; }
.pop-unit  { font-size:.68rem; color:var(--muted); margin-top:1px; }
.leaflet-control-zoom a {
  background:#fff !important;
  color:var(--text2) !important;
  border-color:rgba(0,0,0,0.12) !important;
  font-weight:600;
}
.leaflet-control-zoom a:hover { background:var(--surface2) !important; color:var(--accent) !important; }
.leaflet-bar {
  border:1px solid rgba(0,0,0,0.1) !important;
  border-radius:var(--r-sm) !important;
  overflow:hidden;
  box-shadow:0 2px 10px rgba(0,0,0,0.1) !important;
}
.leaflet-control-attribution {
  background:rgba(255,255,255,0.85) !important;
  color:#888 !important;
  font-size:.65rem !important;
  backdrop-filter:blur(4px);
}
.leaflet-control-attribution a { color:#888 !important; }

/* ── Content wrap ─────────────────────────────── */
.db-wrap { max-width:1440px; margin:0 auto; padding:0 24px 48px; }

.pg-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; padding:26px 0 22px; }
.pg-greeting { font-size:1.45rem; font-weight:600; color:var(--text); margin:0; letter-spacing:-.01em; }
.pg-sub { font-size:.78rem; color:var(--muted); margin:4px 0 0; }
.clock-pill { display:flex; align-items:center; gap:8px; background:var(--surface); border:1px solid var(--border); border-radius:20px; padding:7px 16px; font-size:.78rem; color:var(--text2); font-family:var(--mono); box-shadow:var(--shadow); }
.clock-dot { width:6px; height:6px; border-radius:50%; background:var(--green); animation:blink 1.4s infinite; }

/* ── Section label ────────────────────────────── */
.sec-label { font-size:.68rem; font-weight:600; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); margin:28px 0 14px; display:flex; align-items:center; gap:10px; }
.sec-label::after { content:''; flex:1; height:1px; background:linear-gradient(to right,var(--border2),transparent); }

/* ── Grid ─────────────────────────────────────── */
.cgrid { display:grid; gap:14px; margin-bottom:14px; }
.cgrid-2 { grid-template-columns:repeat(2,1fr); }
.cgrid-3 { grid-template-columns:repeat(3,1fr); }
@media(max-width:960px)  { .cgrid-2,.cgrid-3 { grid-template-columns:1fr; } }
@media(max-width:1100px) { .cgrid-3 { grid-template-columns:repeat(2,1fr); } }
@media(max-width:700px)  { .cgrid-3 { grid-template-columns:1fr; } }

/* ── Card ─────────────────────────────────────── */
.ccard {
  background:var(--surface); border:1px solid var(--border); border-radius:var(--r);
  padding:20px; position:relative; overflow:hidden;
  animation:fadeUp .5s var(--ease) both; box-shadow:var(--shadow);
  transition:box-shadow var(--t), border-color var(--t);
}
.ccard::before {
  content:''; position:absolute; top:0; left:0; right:0; height:2px;
  background:linear-gradient(90deg,var(--accent),var(--accent2),var(--accent3));
  opacity:0; transition:opacity var(--t);
}
.ccard:hover::before { opacity:1; }
@keyframes fadeUp { from{opacity:0;transform:translateY(18px)} to{opacity:1;transform:translateY(0)} }

/* ── Card header row ──────────────────────────── */
.ch-row {
  display:flex; align-items:flex-start;
  justify-content:space-between; margin-bottom:12px; gap:8px;
}
.ch-left { flex:1; min-width:0; }
.ch-title { font-size:.86rem; font-weight:600; color:var(--text); margin:0; }
.ch-sub   { font-size:.7rem; color:var(--muted); margin:3px 0 0; }

/* ── Inline filter pills ──────────────────────── */
.card-filter {
  display:flex; align-items:center; gap:4px; flex-shrink:0; flex-wrap:wrap; justify-content:flex-end;
}
.cfbtn {
  padding:3px 10px; border-radius:20px; border:1px solid var(--border2);
  background:var(--surface2); font-size:.65rem; font-weight:500; cursor:pointer;
  color:var(--muted); font-family:var(--mono); transition:var(--t); line-height:1.6;
  white-space:nowrap;
}
.cfbtn:hover:not(.active):not(:disabled) { border-color:var(--accent); color:var(--accent); }
.cfbtn.active {
  background:var(--accent); border-color:var(--accent); color:#fff; font-weight:600;
  box-shadow:0 2px 8px rgba(245,99,10,0.3);
}
.cfbtn:disabled { opacity:.4; cursor:not-allowed; }

/* ── Loading shimmer ──────────────────────────── */
.ccanvas { position:relative; width:100%; height:230px; }
.ccanvas.h260 { height:260px; }
.ccard.loading .ccanvas::after {
  content:''; position:absolute; inset:0; border-radius:6px; z-index:5;
  background:linear-gradient(90deg,transparent 25%,rgba(245,99,10,0.07) 50%,transparent 75%);
  background-size:200% 100%; animation:shimmer .85s infinite;
}
@keyframes shimmer { from{background-position:200% 0} to{background-position:-200% 0} }

::-webkit-scrollbar { width:5px; height:5px; }
::-webkit-scrollbar-track { background:transparent; }
::-webkit-scrollbar-thumb { background:rgba(0,0,0,0.15); border-radius:3px; }

.nav-btn {
  display:inline-flex; align-items:center; gap:6px;
  padding:7px 16px; border-radius:20px;
  background:var(--surface); border:1px solid var(--border);
  color:var(--text2); font-size:.78rem; font-weight:500;
  text-decoration:none; box-shadow:var(--shadow); transition:var(--t);
}
.nav-btn:hover { border-color:var(--accent); color:var(--accent); }

/* ── IP Visitors Widget ───────────────────────── */
.ip-widget {
  display:flex; align-items:stretch;
  background:var(--surface); border:1px solid var(--border2);
  border-radius:var(--r); box-shadow:var(--shadow-lg);
  text-decoration:none; color:inherit; overflow:hidden;
  min-width:230px; max-width:290px;
  transition:box-shadow var(--t), border-color var(--t), transform var(--t);
  position:relative; cursor:pointer;
  animation:fadeUp .4s var(--ease) both;
}
.ip-widget::before {
  content:''; position:absolute; top:0; left:0; right:0; height:2.5px;
  background:linear-gradient(90deg,#3ecfcf,#56CFE1,#7c6bff);
  z-index:1;
}
.ip-widget:hover {
  border-color:rgba(62,207,207,0.45);
  box-shadow:0 10px 36px rgba(62,207,207,0.2);
  transform:translateY(-2px);
}
.ip-widget-left {
  padding:14px 16px; flex:1; display:flex; flex-direction:column; justify-content:space-between; gap:2px;
}
.ip-widget-label {
  font-size:.62rem; font-weight:600; letter-spacing:.1em; text-transform:uppercase;
  color:#3ecfcf; font-family:var(--mono);
  display:flex; align-items:center; gap:5px;
}
.ip-pulse {
  width:5px; height:5px; border-radius:50%; background:#3ecfcf;
  animation:blink 1.4s infinite; flex-shrink:0;
}
.ip-widget-count {
  font-size:1.7rem; font-weight:700; color:var(--text); line-height:1;
  font-family:var(--mono); letter-spacing:-.02em; margin:5px 0 2px;
}
.ip-widget-sub { font-size:.65rem; color:var(--muted); }
.ip-widget-badge {
  display:inline-flex; align-items:center; gap:3px;
  font-size:.6rem; font-weight:600; font-family:var(--mono);
  color:#3ecfcf; background:rgba(62,207,207,0.1);
  border:1px solid rgba(62,207,207,0.25);
  padding:2px 7px; border-radius:20px; margin-top:4px; width:fit-content;
}
.ip-widget-right {
  width:100px; display:flex; align-items:flex-end;
  position:relative; overflow:hidden; flex-shrink:0;
}
.ip-sparkline {
  width:100%; height:100%; display:block;
}
.leaflet-top, .leaflet-bottom {
    position: absolute;
    z-index: 400;
    pointer-events: none;
}

/* ── AI Stat Cards ────────────────────────────── */
.ai-stat-card { display:flex; flex-direction:column; align-items:flex-start; gap:6px; }
.ai-stat-icon { width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; margin-bottom:2px; }
.ai-stat-val  { font-size:1.7rem; font-weight:700; line-height:1; font-family:var(--mono); color:var(--accent); letter-spacing:-.02em; }
.ai-stat-label{ font-size:.72rem; color:var(--muted); }
</style>
</head>

<?php
// AI Chat Stats
$chat_stats = ['total_conv' => 0, 'total_msg' => 0, 'guest_users' => 0, 'member_users' => 0];
$s = $conn->query("
    SELECT
        COUNT(DISTINCT c.conversation_id) AS total_conv,
        COALESCE(SUM(c.message_count), 0) AS total_msg,
        COUNT(DISTINCT CASE WHEN u.login_method = 'guest' THEN uc.user_id END) AS guest_users,
        COUNT(DISTINCT CASE WHEN u.login_method != 'guest' AND u.login_method IS NOT NULL THEN uc.user_id END) AS member_users
    FROM ai_chat_conversations c
    INNER JOIN user_ai_companions uc ON c.user_companion_id = uc.user_companion_id
    LEFT JOIN mb_user u ON uc.user_id = u.user_id
    WHERE c.is_active = 1
");
if ($s && $row = $s->fetch_assoc()) $chat_stats = $row;

// Conversations per day (last 30 days)
$chat_daily = [];
$d = $conn->query("
    SELECT DATE(updated_at) AS d, COUNT(*) AS n
    FROM ai_chat_conversations
    WHERE is_active = 1 AND updated_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
    GROUP BY DATE(updated_at)
    ORDER BY d ASC
");
if ($d) { while ($r = $d->fetch_assoc()) $chat_daily[$r['d']] = (int)$r['n']; }
// fill missing dates
$chat_labels = []; $chat_values = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chat_labels[] = date('d/m', strtotime($date));
    $chat_values[] = $chat_daily[$date] ?? 0;
}

// Top AI Companions by conversations
$top_ai = [];
$t = $conn->query("
    SELECT ai.ai_name_th AS name, ai.ai_avatar_url AS avatar, COUNT(c.conversation_id) AS total
    FROM ai_chat_conversations c
    INNER JOIN user_ai_companions uc ON c.user_companion_id = uc.user_companion_id
    INNER JOIN ai_companions ai ON uc.ai_id = ai.ai_id
    WHERE c.is_active = 1
    GROUP BY ai.ai_id
    ORDER BY total DESC
    LIMIT 5
");
if ($t) { while ($r = $t->fetch_assoc()) $top_ai[] = $r; }
?>

<body>
<?php include 'template/header.php'; ?>

<!-- MAP HERO -->
<div class="map-hero">
  <div class="map-hero-top">
    <div class="map-title-block">
      <h4>🗺️ User Location &nbsp;<span class="live-badge" id="mapBadge">30d</span></h4>
      <p>ซูมเข้าเพื่อดูรายละเอียด — จุดแต่ละจุด = 1 ตำแหน่ง user / คลิกจุดดูรายละเอียด</p>
    </div>
    <div class="zoom-hint" id="zoomHint">🔍 zoom&nbsp;<b>3</b>&nbsp;·&nbsp;🌏 ระดับประเทศ</div>
  </div>
  <!-- Map filter -->
  <div style="background:var(--surface2);border-top:1px solid var(--border);padding:8px 24px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
    <span style="font-size:.68rem;color:var(--muted);font-weight:600;letter-spacing:.06em;text-transform:uppercase;margin-right:4px;">📅 ช่วงเวลา</span>
    <button class="cfbtn" data-section="region" data-days="1">วันนี้</button>
    <button class="cfbtn" data-section="region" data-days="3">3d</button>
    <button class="cfbtn" data-section="region" data-days="7">7d</button>
    <button class="cfbtn active" data-section="region" data-days="30">30d</button>
    <button class="cfbtn" data-section="region" data-days="90">90d</button>
    <button class="cfbtn" data-section="region" data-days="365">1y</button>
  </div>
  <div id="mapContainer"></div>
  <div class="map-footer" id="mapLegend"></div>
</div>

<!-- CONTENT -->
<div class="db-wrap">

<?php
date_default_timezone_set('Asia/Bangkok');
$hour = (int)date('H');
if ($hour >= 5 && $hour < 12)      $greeting = $currentLang['greeting_morning'];
elseif ($hour >= 12 && $hour < 18) $greeting = $currentLang['greeting_afternoon'];
else                                $greeting = $currentLang['greeting_evening'];
$username = $_SESSION['fullname'] ?? 'Admin';
?>

  <div class="pg-header">
    <div>
      <p class="pg-greeting"><?= $greeting ?>, <?= htmlspecialchars($username) ?> 👋</p>
      <p class="pg-sub"><?= $currentLang['dashboard_title'] ?> &mdash; <?= date('l, d F Y') ?></p>
    </div>
    <div style="display:flex;align-items:center;gap:12px;">
      <!-- IP Visitors Widget -->
      <a href="ip_visitors.php" class="ip-widget" id="ipWidget">
        <div class="ip-widget-left">
          <div class="ip-widget-label"><span class="ip-pulse"></span>IP Visitors</div>
          <div class="ip-widget-count" id="ipCount">—</div>
          <div class="ip-widget-sub">ผู้เข้าชมวันนี้</div>
          <div class="ip-widget-badge" id="ipBadge">⟶ ดูรายละเอียด</div>
        </div>
        <div class="ip-widget-right">
          <svg class="ip-sparkline" id="ipSparkSvg" viewBox="0 0 100 64" preserveAspectRatio="none"></svg>
        </div>
      </a>
      <div class="clock-pill"><span class="clock-dot"></span><span id="liveClock">--:--:--</span></div>
    </div>
  </div>

  <!-- Traffic -->
  <p class="sec-label">Traffic Overview</p>
  <div class="cgrid cgrid-2">

    <!-- Daily Users -->
    <div class="ccard" style="animation-delay:.06s">
      <div class="ch-row">
        <div class="ch-left">
          <p class="ch-title">Daily Active Users</p>
          <p class="ch-sub">ผู้ใช้งานรายวัน</p>
        </div>
        <div class="card-filter">
          <button class="cfbtn" data-section="daily_users" data-days="1">วันนี้</button>
          <button class="cfbtn" data-section="daily_users" data-days="3">3d</button>
          <button class="cfbtn" data-section="daily_users" data-days="7">7d</button>
          <button class="cfbtn active" data-section="daily_users" data-days="30">30d</button>
          <button class="cfbtn" data-section="daily_users" data-days="90">90d</button>
          <button class="cfbtn" data-section="daily_users" data-days="365">1y</button>
        </div>
      </div>
      <div class="ccanvas h260"><canvas id="userChart"></canvas></div>
    </div>

    <!-- Source -->
    <div class="ccard" style="animation-delay:.12s">
      <div class="ch-row">
        <div class="ch-left">
          <p class="ch-title">User Source</p>
          <p class="ch-sub">ช่องทางการเข้าถึง</p>
        </div>
        <div class="card-filter">
          <button class="cfbtn" data-section="source" data-days="1">วันนี้</button>
          <button class="cfbtn" data-section="source" data-days="3">3d</button>
          <button class="cfbtn" data-section="source" data-days="7">7d</button>
          <button class="cfbtn active" data-section="source" data-days="30">30d</button>
          <button class="cfbtn" data-section="source" data-days="90">90d</button>
          <button class="cfbtn" data-section="source" data-days="365">1y</button>
        </div>
      </div>
      <div class="ccanvas h260"><canvas id="sourceChart"></canvas></div>
    </div>

  </div>

  <!-- Device & Platform -->
  <p class="sec-label">Device &amp; Platform</p>
  <div class="cgrid cgrid-2">

    <div class="ccard" style="animation-delay:.15s">
      <div class="ch-row">
        <div class="ch-left">
          <p class="ch-title">Device Type</p>
          <p class="ch-sub">ประเภทอุปกรณ์ที่ใช้เข้าชม</p>
        </div>
        <div class="card-filter">
          <button class="cfbtn" data-section="device" data-days="1">วันนี้</button>
          <button class="cfbtn" data-section="device" data-days="7">7d</button>
          <button class="cfbtn active" data-section="device" data-days="30">30d</button>
          <button class="cfbtn" data-section="device" data-days="90">90d</button>
          <button class="cfbtn" data-section="device" data-days="365">1y</button>
        </div>
      </div>
      <div class="ccanvas h260"><canvas id="deviceChart"></canvas></div>
    </div>

    <div class="ccard" style="animation-delay:.21s">
      <div class="ch-row">
        <div class="ch-left">
          <p class="ch-title">OS / Browser</p>
          <p class="ch-sub">ระบบปฏิบัติการและเบราว์เซอร์</p>
        </div>
        <div class="card-filter">
          <button class="cfbtn" data-section="os_browser" data-days="1">วันนี้</button>
          <button class="cfbtn" data-section="os_browser" data-days="7">7d</button>
          <button class="cfbtn active" data-section="os_browser" data-days="30">30d</button>
          <button class="cfbtn" data-section="os_browser" data-days="90">90d</button>
          <button class="cfbtn" data-section="os_browser" data-days="365">1y</button>
        </div>
      </div>
      <div class="ccanvas h260"><canvas id="osBrowserChart"></canvas></div>
    </div>

  </div>

  <!-- Top Pages -->
  <p class="sec-label">Top Pages</p>
  <div class="cgrid cgrid-2">

    <div class="ccard" style="animation-delay:.18s">
      <div class="ch-row">
        <div class="ch-left">
          <p class="ch-title">Top Pages</p>
          <p class="ch-sub">หน้าที่มีผู้เข้าชมสูงสุด</p>
        </div>
        <div class="card-filter">
          <button class="cfbtn" data-section="top_pages" data-days="1">วันนี้</button>
          <button class="cfbtn" data-section="top_pages" data-days="3">3d</button>
          <button class="cfbtn" data-section="top_pages" data-days="7">7d</button>
          <button class="cfbtn active" data-section="top_pages" data-days="30">30d</button>
          <button class="cfbtn" data-section="top_pages" data-days="90">90d</button>
          <button class="cfbtn" data-section="top_pages" data-days="365">1y</button>
        </div>
      </div>
      <div class="ccanvas"><canvas id="topPagesChart"></canvas></div>
    </div>

    <div class="ccard" style="animation-delay:.24s">
      <div class="ch-row">
        <div class="ch-left">
          <p class="ch-title">Top View Products</p>
          <p class="ch-sub">สินค้ายอดนิยม</p>
        </div>
        <div class="card-filter">
          <button class="cfbtn" data-section="top_products" data-days="1">วันนี้</button>
          <button class="cfbtn" data-section="top_products" data-days="3">3d</button>
          <button class="cfbtn" data-section="top_products" data-days="7">7d</button>
          <button class="cfbtn active" data-section="top_products" data-days="30">30d</button>
          <button class="cfbtn" data-section="top_products" data-days="90">90d</button>
          <button class="cfbtn" data-section="top_products" data-days="365">1y</button>
        </div>
      </div>
      <div class="ccanvas"><canvas id="topProductsChart"></canvas></div>
    </div>

  </div>

  <!-- AI Chat Overview -->
  <p class="sec-label">AI Companion Chat Overview</p>

  <!-- Stat Cards -->
  <div class="cgrid cgrid-3" style="margin-bottom:14px;">
    <div class="ccard ai-stat-card" style="animation-delay:.3s">
      <div class="ai-stat-icon" style="background:rgba(245,99,10,0.1); color:var(--accent);">💬</div>
      <div class="ai-stat-val"><?= number_format((int)$chat_stats['total_conv']) ?></div>
      <div class="ai-stat-label">Total Conversations</div>
    </div>
    <div class="ccard ai-stat-card" style="animation-delay:.36s">
      <div class="ai-stat-icon" style="background:rgba(62,207,207,0.1); color:#3ecfcf;">📨</div>
      <div class="ai-stat-val" style="color:#3ecfcf;"><?= number_format((int)$chat_stats['total_msg']) ?></div>
      <div class="ai-stat-label">Total Messages</div>
    </div>
    <div class="ccard ai-stat-card" style="animation-delay:.42s">
      <div class="ai-stat-icon" style="background:rgba(124,107,255,0.1); color:#7c6bff;">👥</div>
      <div class="ai-stat-val" style="color:#7c6bff;">
        <?= number_format((int)$chat_stats['guest_users']) ?>
        <span style="font-size:.85rem; font-weight:400; color:var(--muted);"> / <?= number_format((int)$chat_stats['member_users']) ?></span>
      </div>
      <div class="ai-stat-label">Guest / Member Users</div>
    </div>
  </div>

  <!-- Chart + Top AI -->
  <div class="cgrid cgrid-2">
    <div class="ccard" style="animation-delay:.48s">
      <div class="ch-row">
        <div class="ch-left">
          <p class="ch-title">Conversations / Day</p>
          <p class="ch-sub">จำนวนการสนทนา 30 วันล่าสุด</p>
        </div>
      </div>
      <div class="ccanvas h260"><canvas id="chatDailyChart"></canvas></div>
    </div>

    <div class="ccard" style="animation-delay:.54s">
      <div class="ch-row">
        <div class="ch-left">
          <p class="ch-title">Top AI Companions</p>
          <p class="ch-sub">AI ที่มีการสนทนาสูงสุด</p>
        </div>
      </div>
      <?php if (empty($top_ai)): ?>
      <p style="color:var(--muted); font-size:.82rem; text-align:center; padding:40px 0;">ยังไม่มีข้อมูล</p>
      <?php else: ?>
      <?php $max_ai = max(array_column($top_ai, 'total')) ?: 1; ?>
      <div style="display:flex; flex-direction:column; gap:14px; padding-top:4px;">
        <?php foreach ($top_ai as $ai): ?>
        <div style="display:flex; align-items:center; gap:10px;">
          <?php if (!empty($ai['avatar'])): ?>
          <img src="<?= htmlspecialchars($ai['avatar']) ?>" style="width:34px;height:34px;border-radius:50%;object-fit:cover;flex-shrink:0;border:1.5px solid var(--border2);">
          <?php else: ?>
          <div style="width:34px;height:34px;border-radius:50%;background:var(--surface3);flex-shrink:0;"></div>
          <?php endif; ?>
          <div style="flex:1; min-width:0;">
            <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
              <span style="font-size:.82rem; font-weight:500; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:140px;"><?= htmlspecialchars($ai['name']) ?></span>
              <span style="font-size:.78rem; font-family:var(--mono); color:var(--accent); flex-shrink:0;"><?= number_format((int)$ai['total']) ?></span>
            </div>
            <div style="height:5px; background:var(--surface3); border-radius:3px; overflow:hidden;">
              <div style="height:100%; width:<?= round($ai['total']/$max_ai*100) ?>%; background:linear-gradient(90deg,var(--accent),var(--accent2)); border-radius:3px; transition:width .5s;"></div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

</div><!-- /db-wrap -->

<script>
const chatLabels = <?= json_encode($chat_labels) ?>;
const chatValues = <?= json_encode($chat_values) ?>;
</script>

<script src="js/index_.js?v=<?= time(); ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// ── Live Clock ────────────────────────────────────────────────
(function tick(){
  const el = document.getElementById('liveClock');
  if (el) el.textContent = new Date().toLocaleTimeString('th-TH',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
  setTimeout(tick, 1000);
})();

// ══════════════════════════════════════════════════════════════
//  UserMap — Light Theme
//  - ใช้ tile สว่าง (CartoDB Positron)
//  - จุดสีส้ม accent เพื่อให้เห็นชัดบน background สว่าง
//  - popup / zoom control สไตล์ light
// ══════════════════════════════════════════════════════════════
class UserMap {
  constructor(id) {
    this.markers = [];

    this.map = L.map(id, {
      center: [20, 105], zoom: 3,
      minZoom: 2, maxZoom: 16,
      zoomControl: true, attributionControl: true
    });

    // ── Light tile: CartoDB Positron ──────────────────────────
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
      subdomains: 'abcd',
      maxZoom: 19,
      attribution: '&copy; <a href="https://carto.com/">CartoDB</a>'
    }).addTo(this.map);

    this.map.on('zoomend', () => this._updateHint());
    this._updateHint();
  }

  _updateHint() {
    const z  = this.map.getZoom();
    const el = document.getElementById('zoomHint');
    if (!el) return;
    let lvl = '🌏 ระดับโลก';
    if (z >= 10) lvl = '🏘️ ระดับเมือง';
    else if (z >= 6) lvl = '📍 ระดับจังหวัด / รัฐ';
    else if (z >= 4) lvl = '🗾 ระดับประเทศ';
    el.innerHTML = `🔍 zoom&nbsp;<b>${z}</b>&nbsp;·&nbsp;${lvl}`;
  }

  _label(pt) {
    return pt.city || pt.province || pt.region || pt.country || 'Unknown';
  }

  _sub(pt) {
    const label = this._label(pt);
    return [pt.province, pt.country].filter(p => p && p !== label).join(', ');
  }

  updateData(points) {
    this.markers.forEach(({ marker }) => this.map.removeLayer(marker));
    this.markers = [];

    if (!points || points.length === 0) {
      document.getElementById('mapLegend').innerHTML =
        '<span style="color:#aaa">ไม่มีข้อมูลตำแหน่งในช่วงเวลานี้</span>';
      return;
    }

    points.forEach(pt => {
      const label = this._label(pt);
      const sub   = this._sub(pt);

      // ── จุดสีส้มเห็นชัดบน light map ─────────────────────────
      const marker = L.circleMarker([pt.lat, pt.lng], {
        radius:      5,
        fillColor:   '#f5630a',         // accent orange
        color:       'rgba(255,255,255,0.8)',
        weight:      1.5,
        fillOpacity: 0.75
      });

      marker.bindPopup(`
        <p class="pop-name">${label}</p>
        ${sub ? `<p class="pop-sub">📍 ${sub}</p>` : ''}
        <p class="pop-count">${pt.count.toLocaleString()}</p>
        <p class="pop-unit">active users</p>
      `, { maxWidth: 200, closeButton: false });

      marker.on('mouseover', function () { this.openPopup(); });
      marker.addTo(this.map);
      this.markers.push({ marker, pt });
    });

    this._buildLegend(points);
  }

  _buildLegend(points) {
    const el = document.getElementById('mapLegend');
    if (!el) return;
    el.innerHTML = '';

    const sorted = [...points].sort((a, b) => b.count - a.count).slice(0, 12);

    sorted.forEach(pt => {
      const label = this._label(pt);
      const ctry  = pt.country && pt.country !== label
        ? ` <span style="color:#aaa">(${pt.country})</span>` : '';

      const div = document.createElement('div');
      div.className = 'mleg-item';
      div.style.cursor     = 'pointer';
      div.style.transition = 'opacity 0.15s';
      div.addEventListener('mouseenter', () => div.style.opacity = '0.6');
      div.addEventListener('mouseleave', () => div.style.opacity = '1');
      div.addEventListener('click', () => {
        const zoom = pt.city ? 10 : pt.province ? 8 : 5;
        this.map.flyTo([pt.lat, pt.lng], zoom, { animate: true, duration: 1.2 });
        const found = this.markers.find(m => m.pt === pt);
        if (found) setTimeout(() => found.marker.openPopup(), 1300);
      });

      div.innerHTML = `
        <div class="mleg-dot" style="background:#f5630a"></div>
        <span>${label}${ctry} <b style="color:#4a3f35">${pt.count.toLocaleString()}</b></span>
      `;
      el.appendChild(div);
    });
  }
}

let mapInstance = null;

// ══════════════════════════════════════════════════════════════
//  Chart.js defaults
// ══════════════════════════════════════════════════════════════
Chart.defaults.color = '#9a8e84';
Chart.defaults.font.family = "'DM Sans', sans-serif";
Chart.defaults.font.size = 11;
const gridOpts = { color:'rgba(0,0,0,0.05)', drawBorder:false };
const PAL = ['#f5a623','#3ecfcf','#7c6bff','#4caf7d','#ef5350','#FFD93D','#56CFE1','#c77dff','#ff6b6b','#a8e6cf'];

const chartRegistry = {};
function mkChart(id, type, cd, opts={}) {
  const el = document.getElementById(id); if (!el) return;
  if (chartRegistry[id]) chartRegistry[id].destroy();
  chartRegistry[id] = new Chart(el.getContext('2d'), {
    type, data:cd,
    options:{ responsive:true, maintainAspectRatio:false, animation:{duration:550,easing:'easeOutQuart'}, ...opts }
  });
}

function fmtT(s) { const m=Math.floor(s/60),r=Math.round(s%60); return m?`${m}m ${r}s`:`${r}s`; }
function cTip(key, data) {
  return { callbacks: { label(ctx) {
    const i = ctx.dataIndex;
    return [` Views: ${Number(ctx.parsed.x??ctx.parsed.y).toLocaleString()}`,
            ` Users: ${Number((data[key]?.users??[])[i]??0).toLocaleString()}`,
            ` Avg: ${fmtT((data[key]?.avg_time??[])[i]??0)}`];
  }}};
}

function setCardLoading(section, on) {
  document.querySelectorAll(`.cfbtn[data-section="${section}"]`).forEach(btn => {
    const card = btn.closest('.ccard, [style]');
    if (card) {
      card.classList.toggle('loading', on);
      card.querySelectorAll('.cfbtn').forEach(b => { b.disabled = on; });
    }
  });
}

// ══════════════════════════════════════════════════════════════
//  Render per section
// ══════════════════════════════════════════════════════════════
function renderSection(section, data) {
  switch (section) {

    case 'daily_users':
  mkChart('userChart', 'line', {
    labels: data.daily_users.labels,
    datasets: [
      {
        label: data.days === 1 ? 'Hourly Visitors' : 'All Visitors',
        data: data.daily_users.data,
        fill: true,
        backgroundColor: ctx => {
          const g = ctx.chart.ctx.createLinearGradient(0,0,0,280);
          g.addColorStop(0,'rgba(245,166,35,0.18)');
          g.addColorStop(1,'rgba(245,166,35,0)');
          return g;
        },
        borderColor: '#f5a623', tension: .4, borderWidth: 2,
        pointBackgroundColor: '#f5a623', pointRadius: 2, pointHoverRadius: 5
      },
      {
        label: 'Returning Visitors',
        data: data.daily_users.returning ?? [],
        fill: true,
        backgroundColor: ctx => {
          const g = ctx.chart.ctx.createLinearGradient(0,0,0,280);
          g.addColorStop(0,'rgba(62,207,207,0.13)');
          g.addColorStop(1,'rgba(62,207,207,0)');
          return g;
        },
        borderColor: '#3ecfcf', tension: .4, borderWidth: 2,
        pointBackgroundColor: '#3ecfcf', pointRadius: 2, pointHoverRadius: 5,
      }
    ]
  }, {
    scales: {
      y: { beginAtZero:true, grid:gridOpts, ticks:{color:'#9a8e84'} },
      x: { grid:{display:false}, ticks:{color:'#9a8e84', maxTicksLimit:9} }
    },
    plugins: {
      legend: {
        display: true, position: 'top', align: 'end',
        labels: {
          color:'#9a8e84', font:{size:11},
          usePointStyle:true, pointStyleWidth:16, padding:16
        }
      },
      tooltip: {
        callbacks: {
          label(ctx) {
            const val = Number(ctx.parsed.y).toLocaleString();
            return ` ${ctx.dataset.label}: ${val}`;
          }
        }
      }
    }
  });
  break;

    case 'source':
      mkChart('sourceChart','bar',{
        labels: data.source.labels,
        datasets:[{ label:'Users', data:data.source.data, backgroundColor:PAL.slice(0,data.source.data.length), borderRadius:6, borderSkipped:false }]
      },{ scales:{y:{beginAtZero:true,grid:gridOpts,ticks:{color:'#9a8e84'}},x:{grid:{display:false},ticks:{color:'#9a8e84'}}}, plugins:{legend:{display:false}} });
      break;

    case 'region': {
      const points = data.map_points ?? [];
      if (!mapInstance) mapInstance = new UserMap('mapContainer');
      mapInstance.updateData(points);
      const mb = document.getElementById('mapBadge');
      if (mb) mb.textContent = {1:'วันนี้',3:'3d',7:'7d',30:'30d',90:'90d',365:'1y'}[data.days] ?? `${data.days}d`;
      break;
    }

    case 'device': {
  const d = data.device ?? {};
  mkChart('deviceChart', 'doughnut', {
    labels: d.labels ?? [],
    datasets: [{
      data: d.data ?? [],
      backgroundColor: ['#f5630a','#3ecfcf','#7c6bff','#4caf7d'],
      borderWidth: 2,
      borderColor: '#fff',
      hoverOffset: 6
    }]
  }, {
    cutout: '62%',
    plugins: {
      legend: {
        display: true,
        position: 'right',
        labels: {
          color: '#9a8e84',
          font: { size: 11 },
          padding: 16,
          usePointStyle: true,
          generateLabels(chart) {
            const dataset = chart.data.datasets[0];
            const total   = dataset.data.reduce((a, b) => a + b, 0);
            return chart.data.labels.map((label, i) => ({
              text:        `${label}   ${dataset.data[i].toLocaleString()}  ·  ${total ? Math.round(dataset.data[i] / total * 100) : 0}%`,
              fillStyle:   dataset.backgroundColor[i],
              strokeStyle: dataset.backgroundColor[i],
              pointStyle:  'circle',
              index:       i,
              hidden:      false,
            }));
          }
        }
      },
      tooltip: {
        callbacks: {
          label(ctx) {
            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
            const pct   = total ? Math.round(ctx.parsed / total * 100) : 0;
            return ` ${ctx.label}: ${ctx.parsed.toLocaleString()} (${pct}%)`;
          }
        }
      }
    }
  });
  break;
}


    case 'os_browser': {
      const d = data.os_browser ?? {};
      mkChart('osBrowserChart', 'bar', {
        labels: d.labels ?? [],
        datasets: [{
          label: 'Sessions',
          data: d.data ?? [],
          backgroundColor: PAL.slice(0, (d.data ?? []).length),
          borderRadius: 5,
          borderSkipped: false
        }]
      }, {
        indexAxis: 'y',
        scales: {
          x: { beginAtZero:true, grid:gridOpts, ticks:{ color:'#9a8e84' } },
          y: { grid:{ display:false }, ticks:{ color:'#9a8e84' } }
        },
        plugins: { legend:{ display:false } }
      });
      break;
    }

    case 'top_pages':
      mkChart('topPagesChart','bar',{
        labels: data.top_pages.labels,
        datasets:[{ label:'Users', data:data.top_pages.data, backgroundColor:PAL.slice(0,data.top_pages.data.length), borderRadius:5, borderSkipped:false }]
      },{ scales:{y:{beginAtZero:true,grid:gridOpts,ticks:{color:'#9a8e84'}},x:{grid:{display:false},ticks:{color:'#9a8e84'}}},
          plugins:{legend:{display:false},tooltip:{callbacks:{label(ctx){const i=ctx.dataIndex;return[
            ` Users: ${Number(ctx.parsed.y).toLocaleString()}`,
            ` Views: ${Number((data.top_pages.views??[])[i]??0).toLocaleString()}`,
            ` Avg: ${fmtT((data.top_pages.avg_time??[])[i]??0)}`
          ];}}}} });
      break;

    case 'top_products':
      mkChart('topProductsChart','bar',{
        labels: data.top_products.labels,
        datasets:[{ label:'Views', data:data.top_products.data, backgroundColor:PAL.slice(0,data.top_products.data.length), borderRadius:5, borderSkipped:false }]
      },{ indexAxis:'y', scales:{x:{beginAtZero:true,grid:gridOpts,ticks:{color:'#9a8e84'}},y:{grid:{display:false},ticks:{color:'#9a8e84'}}}, plugins:{legend:{display:false},tooltip:cTip('top_products',data)} });
      break;

    case 'all':
      ['daily_users','source','region','device','os_browser',
       'top_pages','top_products']
        .forEach(s => renderSection(s, data));
      break;
  }
}

// ══════════════════════════════════════════════════════════════
//  fetchSection
// ══════════════════════════════════════════════════════════════
function fetchSection(section, days) {
  setCardLoading(section, true);
  fetch(`fetch_analytics_data.php?section=${section}&days=${days}&refresh=1`)
    .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
    .then(data => { if (data.error) { console.error(data.error); return; } renderSection(section, data); })
    .catch(err => console.error('Fetch error:', err))
    .finally(() => setCardLoading(section, false));
}

// ══════════════════════════════════════════════════════════════
//  IP Visitors Sparkline Widget
// ══════════════════════════════════════════════════════════════
(function(){
  function drawSpark(vals, total) {
    const svg   = document.getElementById('ipSparkSvg');
    const cnt   = document.getElementById('ipCount');
    const badge = document.getElementById('ipBadge');
    if (!svg) return;

    if (cnt) cnt.textContent = total >= 1000 ? (total/1000).toFixed(1)+'k' : String(total||'—');

    if (vals.length >= 2 && badge) {
      const half = Math.floor(vals.length / 2);
      const prev = vals.slice(0, half).reduce((a,b)=>a+b,0);
      const curr = vals.slice(half).reduce((a,b)=>a+b,0);
      const diff = prev > 0 ? Math.round(((curr-prev)/prev)*100) : 0;
      badge.innerHTML = diff >= 0 ? `▲ ${diff}% vs ช่วงก่อน` : `▼ ${Math.abs(diff)}% vs ช่วงก่อน`;
      badge.style.color       = diff >= 0 ? '#3ecfcf' : '#ef5350';
      badge.style.background  = diff >= 0 ? 'rgba(62,207,207,0.1)' : 'rgba(239,83,80,0.1)';
      badge.style.borderColor = diff >= 0 ? 'rgba(62,207,207,0.25)' : 'rgba(239,83,80,0.25)';
    }

    if (vals.length < 2) return;
    const W=100, H=64, padX=4, padY=8;
    const mx = Math.max(...vals, 1);
    const mn = Math.min(...vals, 0);
    const pts = vals.map((v,i) => {
      const x = padX + (i/(vals.length-1))*(W-padX*2);
      const y = H - padY - ((v-mn)/(mx-mn||1))*(H-padY*2);
      return [x.toFixed(2), y.toFixed(2)];
    });
    const polyStr = pts.map(p=>p.join(',')).join(' ');
    const lastX   = pts[pts.length-1][0];
    const lastY   = pts[pts.length-1][1];
    const areaD   = `M${pts[0].join(',')} ${pts.map(p=>'L'+p.join(',')).join(' ')} L${W-padX},${H} L${padX},${H} Z`;

    svg.innerHTML = `
      <defs>
        <linearGradient id="spGrad" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="#3ecfcf" stop-opacity="0.35"/>
          <stop offset="100%" stop-color="#3ecfcf" stop-opacity="0.02"/>
        </linearGradient>
      </defs>
      <path d="${areaD}" fill="url(#spGrad)"/>
      <polyline points="${polyStr}" fill="none" stroke="#3ecfcf" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      <circle cx="${lastX}" cy="${lastY}" r="3" fill="#3ecfcf" opacity="0.9"/>
      <circle cx="${lastX}" cy="${lastY}" r="5.5" fill="none" stroke="#3ecfcf" stroke-width="1" opacity="0.3"/>
    `;
  }

  fetch('fetch_analytics_data.php?section=daily_users&days=7')
    .then(r => r.json())
    .then(d => {
      const vals  = d.daily_users?.data ?? [];
      const today = vals.length ? vals[vals.length-1] : 0;
      drawSpark(vals.length >= 2 ? vals : [2,5,4,8,6,11,9], today);
    })
    .catch(() => drawSpark([2,5,4,8,6,11,9], 0));
})();

// ══════════════════════════════════════════════════════════════
//  Init
// ══════════════════════════════════════════════════════════════
// ── AI Chat Daily Chart ───────────────────────────────────────
(function(){
  const el = document.getElementById('chatDailyChart');
  if (!el || typeof chatLabels === 'undefined') return;
  new Chart(el.getContext('2d'), {
    type: 'bar',
    data: {
      labels: chatLabels,
      datasets: [{
        label: 'Conversations',
        data: chatValues,
        backgroundColor: ctx => {
          const g = ctx.chart.ctx.createLinearGradient(0,0,0,260);
          g.addColorStop(0,'rgba(124,107,255,0.7)');
          g.addColorStop(1,'rgba(124,107,255,0.15)');
          return g;
        },
        borderRadius: 5,
        borderSkipped: false
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      animation: { duration:550, easing:'easeOutQuart' },
      scales: {
        y: { beginAtZero:true, grid:gridOpts, ticks:{color:'#9a8e84', precision:0} },
        x: { grid:{display:false}, ticks:{color:'#9a8e84', maxTicksLimit:10} }
      },
      plugins: { legend:{ display:false } }
    }
  });
})();

document.addEventListener('DOMContentLoaded', () => {

  document.querySelectorAll('.cfbtn[data-section]').forEach(btn => {
    btn.addEventListener('click', () => {
      const section = btn.dataset.section;
      const days    = parseInt(btn.dataset.days);
      document.querySelectorAll(`.cfbtn[data-section="${section}"]`)
        .forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      fetchSection(section, days);
    });
  });

  setCardLoading('all', true);
  fetch('fetch_analytics_data.php?section=all&days=30&refresh=1')
    .then(r => r.json())
    .then(data => {
      if (data.error) { console.error(data.error); return; }
      renderSection('all', data);
    })
    .catch(err => console.error('Init fetch error:', err))
    .finally(() => {
      document.querySelectorAll('.ccard').forEach(c => c.classList.remove('loading'));
    });
});
</script>
</body>
</html>