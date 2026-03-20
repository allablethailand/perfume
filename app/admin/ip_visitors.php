<?php
include 'check_permission.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../../lib/connect.php';

date_default_timezone_set('Asia/Bangkok');

$days        = isset($_GET['days']) ? max(1, (int)$_GET['days']) : 1;
$search      = trim($_GET['search']   ?? '');
$date_filter = trim($_GET['date']     ?? '');
$prov_filter = trim($_GET['province'] ?? '');
$city_filter = trim($_GET['city']     ?? '');
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

// Build WHERE
if ($date_filter !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_filter)) {
    $whereBase = "DATE(s.started_at) = ?";
    $params    = [$date_filter];
    $types     = 's';
} else {
    $whereBase = "s.started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
    $params    = [$days];
    $types     = 'i';
}

if ($search !== '') {
    $whereBase .= " AND s.ip_address LIKE ?";
    $params[]   = "%$search%";
    $types     .= 's';
}
if ($prov_filter !== '') {
    $whereBase .= " AND s.province = ?";
    $params[]   = $prov_filter;
    $types     .= 's';
}
if ($city_filter !== '') {
    $whereBase .= " AND s.city = ?";
    $params[]   = $city_filter;
    $types     .= 's';
}

// Dropdown options: จังหวัด
$provRows = $conn->query("SELECT DISTINCT province FROM analytics_sessions WHERE province != '' ORDER BY province")->fetch_all(MYSQLI_ASSOC);
// Dropdown options: อำเภอ (filtered by province if selected)
if ($prov_filter !== '') {
    $st = $conn->prepare("SELECT DISTINCT city FROM analytics_sessions WHERE city != '' AND province = ? ORDER BY city");
    $st->bind_param('s', $prov_filter);
    $st->execute();
    $cityRows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
} else {
    $cityRows = $conn->query("SELECT DISTINCT city FROM analytics_sessions WHERE city != '' ORDER BY city")->fetch_all(MYSQLI_ASSOC);
}

// Count total IPs
$sqlCount = "
    SELECT COUNT(DISTINCT s.ip_address) AS total
    FROM analytics_sessions s
    WHERE $whereBase
";
$st = $conn->prepare($sqlCount);
$st->bind_param($types, ...$params);
$st->execute();
$totalIPs = (int)$st->get_result()->fetch_assoc()['total'];
$st->close();

$totalPages = max(1, ceil($totalIPs / $limit));

// Fetch IPs with summary
$sqlList = "
    SELECT
        s.ip_address,
        MAX(c.country)                           AS country,
        MAX(c.city)                              AS city,
        MAX(s.device_type)                       AS device_type,
        MAX(s.os)                                AS os,
        MAX(s.browser)                           AS browser,
        COUNT(DISTINCT s.session_id)             AS sessions,
        COUNT(p.view_id)                         AS total_views,
        ROUND(AVG(p.time_on_page))               AS avg_time,
        SUM(p.time_on_page)                      AS total_time,
        MAX(s.last_active_at)                    AS last_seen,
        MIN(s.started_at)                        AS first_seen,
        GROUP_CONCAT(DISTINCT s.device_type SEPARATOR ',') AS devices
    FROM analytics_sessions s
    LEFT JOIN analytics_pageviews p ON p.session_id = s.session_id
    LEFT JOIN analytics_ip_cache c  ON c.ip_hash = MD5(s.ip_address)
    WHERE $whereBase
    GROUP BY s.ip_address
    ORDER BY last_seen DESC
    LIMIT ? OFFSET ?
";
$params2   = array_merge($params, [$limit, $offset]);
$types2    = $types . 'ii';
$st = $conn->prepare($sqlList);
$st->bind_param($types2, ...$params2);
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

// Stats summary
$sqlSummary = "
    SELECT
        COUNT(DISTINCT s.ip_address)   AS unique_ips,
        COUNT(DISTINCT s.session_id)   AS total_sessions,
        COUNT(p.view_id)               AS total_pageviews,
        ROUND(AVG(p.time_on_page))     AS avg_time_on_page
    FROM analytics_sessions s
    LEFT JOIN analytics_pageviews p ON p.session_id = s.session_id
    WHERE s.started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
";
$st = $conn->prepare($sqlSummary);
$st->bind_param('i', $days);
$st->execute();
$summary = $st->get_result()->fetch_assoc();
$st->close();

function fmtTime($sec) {
    $sec = (int)$sec;
    if ($sec <= 0) return '—';
    if ($sec < 60) return $sec . 's';
    return floor($sec/60) . 'm ' . ($sec%60) . 's';
}
function deviceIcon($d) {
    return match($d) { 'mobile'=>'📱', 'tablet'=>'💻', 'desktop'=>'🖥️', default=>'❓' };
}
function timeAgo($dt) {
    if (!$dt) return '—';
    $diff = time() - strtotime($dt);
    if ($diff < 60)   return 'เมื่อกี้';
    if ($diff < 3600) return floor($diff/60) . ' นาทีที่แล้ว';
    if ($diff < 86400)return floor($diff/3600) . ' ชม.ที่แล้ว';
    return floor($diff/86400) . ' วันที่แล้ว';
}
?>
<!DOCTYPE html>
<html lang="th">
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
  --bg:#f5f4f0; --surface:#fff; --surface2:#faf9f6; --surface3:#f0ede6;
  --border:rgba(0,0,0,0.07); --border2:rgba(0,0,0,0.13);
  --text:#1a1612; --text2:#4a3f35; --muted:#9a8e84;
  --accent:#f5630a; --accent2:#ff9c42; --green:#2e9e5b; --red:#e84855;
  --r:12px; --r-sm:8px; --shadow:0 2px 12px rgba(0,0,0,0.08);
  --shadow-lg:0 6px 28px rgba(0,0,0,0.13); --t:0.2s cubic-bezier(0.4,0,0.2,1);
  --font:'DM Sans',system-ui,sans-serif; --mono:'DM Mono',monospace;
}
*{box-sizing:border-box;margin:0;padding:0;}
body{background:var(--bg);color:var(--text);font-family:var(--font);-webkit-font-smoothing:antialiased;}

.page-wrap{max-width:1440px;margin:0 auto;padding:0 24px 60px;}

/* Header */
.pg-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;padding:28px 0 22px;}
.pg-title{font-size:1.4rem;font-weight:600;letter-spacing:-.02em;display:flex;align-items:center;gap:10px;}
.pg-sub{font-size:.75rem;color:var(--muted);margin-top:4px;}
.back-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:20px;background:var(--surface);border:1px solid var(--border);color:var(--text2);font-size:.8rem;font-weight:500;text-decoration:none;transition:var(--t);}
.back-btn:hover{border-color:var(--accent);color:var(--accent);}

/* Summary cards */
.sum-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px;}
@media(max-width:900px){.sum-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:500px){.sum-grid{grid-template-columns:1fr;}}
.sum-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:16px 20px;box-shadow:var(--shadow);}
.sum-label{font-size:.68rem;color:var(--muted);font-weight:600;letter-spacing:.08em;text-transform:uppercase;margin-bottom:6px;}
.sum-val{font-size:1.7rem;font-weight:600;font-family:var(--mono);color:var(--text);letter-spacing:-.02em;}
.sum-sub{font-size:.7rem;color:var(--muted);margin-top:3px;}

/* Toolbar */
.toolbar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:16px;}
.search-wrap{position:relative;flex:1;min-width:200px;max-width:320px;}
.search-wrap input{width:100%;padding:8px 12px 8px 36px;border-radius:20px;border:1px solid var(--border2);background:var(--surface);font-family:var(--mono);font-size:.78rem;color:var(--text);outline:none;transition:var(--t);}
.search-wrap input:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(245,99,10,0.1);}
.search-wrap::before{content:'🔍';position:absolute;left:11px;top:50%;transform:translateY(-50%);font-size:.75rem;pointer-events:none;}
.filter-pills{display:flex;gap:5px;flex-wrap:wrap;}
.pill{padding:5px 14px;border-radius:20px;border:1px solid var(--border2);background:var(--surface2);font-size:.7rem;font-weight:500;cursor:pointer;color:var(--muted);font-family:var(--mono);text-decoration:none;transition:var(--t);}
.pill:hover{border-color:var(--accent);color:var(--accent);}
.pill.active{background:var(--accent);border-color:var(--accent);color:#fff;font-weight:600;box-shadow:0 2px 8px rgba(245,99,10,.3);}
.result-info{font-size:.75rem;color:var(--muted);margin-left:auto;}

/* Table */
.table-wrap{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow);overflow:hidden;}
.tbl{width:100%;border-collapse:collapse;font-size:.82rem;}
.tbl thead th{background:var(--surface3);padding:11px 14px;text-align:left;font-size:.65rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--border2);white-space:nowrap;}
.tbl tbody tr{border-bottom:1px solid var(--border);transition:background var(--t);}
.tbl tbody tr:last-child{border-bottom:none;}
.tbl tbody tr:hover{background:rgba(245,99,10,0.04);}
.tbl td{padding:10px 14px;vertical-align:middle;}

.ip-link{font-family:var(--mono);font-size:.83rem;font-weight:500;color:var(--accent);text-decoration:none;display:inline-flex;align-items:center;gap:5px;}
.ip-link:hover{text-decoration:underline;}

.tag{display:inline-flex;align-items:center;gap:3px;padding:2px 8px;border-radius:20px;font-size:.65rem;font-weight:500;border:1px solid;}
.tag-green{color:var(--green);background:rgba(46,158,91,.09);border-color:rgba(46,158,91,.25);}
.tag-blue{color:#3b82f6;background:rgba(59,130,246,.09);border-color:rgba(59,130,246,.25);}
.tag-orange{color:var(--accent);background:rgba(245,99,10,.08);border-color:rgba(245,99,10,.2);}
.tag-gray{color:var(--muted);background:var(--surface2);border-color:var(--border2);}

.num{font-family:var(--mono);font-size:.82rem;}
.geo-text{font-size:.75rem;color:var(--text2);}
.sub-text{font-size:.68rem;color:var(--muted);}
.last-seen{font-size:.73rem;color:var(--muted);}

/* Pagination */
.pagination{display:flex;align-items:center;justify-content:center;gap:6px;padding:20px 0 0;}
.ppage{padding:6px 12px;border-radius:8px;border:1px solid var(--border2);background:var(--surface);font-size:.75rem;font-family:var(--mono);color:var(--text2);text-decoration:none;transition:var(--t);}
.ppage:hover{border-color:var(--accent);color:var(--accent);}
.ppage.active{background:var(--accent);border-color:var(--accent);color:#fff;font-weight:600;}
.ppage.disabled{opacity:.35;pointer-events:none;}
.pinfo{font-size:.72rem;color:var(--muted);padding:0 8px;}

/* Empty */
.empty{text-align:center;padding:60px 20px;color:var(--muted);}
.empty-icon{font-size:2.5rem;margin-bottom:12px;}
</style>
</head>
<body>
<?php include 'template/header.php'; ?>

<div class="page-wrap">

  <div class="pg-header">
    <div>
      <h1 class="pg-title">🌐 IP Visitors</h1>
      <p class="pg-sub">รายชื่อ IP ที่เข้าชมเว็บไซต์ — คลิก IP เพื่อดู timeline ละเอียด</p>
    </div>
    <a href="dashboard.php" class="back-btn">← กลับ Dashboard</a>
  </div>

  <!-- Summary -->
  <div class="sum-grid">
    <div class="sum-card">
      <div class="sum-label">Unique IPs</div>
      <div class="sum-val"><?= number_format($summary['unique_ips'] ?? 0) ?></div>
      <div class="sum-sub"><?= $date_filter !== '' ? "วันที่ $date_filter" : ($days === 1 ? 'วันนี้' : "$days วันที่ผ่านมา") ?></div>
    </div>
    <div class="sum-card">
      <div class="sum-label">Total Sessions</div>
      <div class="sum-val"><?= number_format($summary['total_sessions'] ?? 0) ?></div>
      <div class="sum-sub">การเยี่ยมชมทั้งหมด</div>
    </div>
    <div class="sum-card">
      <div class="sum-label">Total Pageviews</div>
      <div class="sum-val"><?= number_format($summary['total_pageviews'] ?? 0) ?></div>
      <div class="sum-sub">หน้าทั้งหมดที่ถูกเปิด</div>
    </div>
    <div class="sum-card">
      <div class="sum-label">Avg. Time / Page</div>
      <div class="sum-val"><?= fmtTime($summary['avg_time_on_page'] ?? 0) ?></div>
      <div class="sum-sub">เวลาเฉลี่ยต่อหน้า</div>
    </div>
  </div>

  <!-- Toolbar -->
  <?php
    // helper: build query string preserving current filters
    function buildQS($overrides = []) {
        global $days, $search, $date_filter, $prov_filter, $city_filter;
        $base = [
            'days'     => $days,
            'search'   => $search,
            'date'     => $date_filter,
            'province' => $prov_filter,
            'city'     => $city_filter,
        ];
        $merged = array_merge($base, $overrides);
        $parts  = [];
        foreach ($merged as $k => $v) {
            if ($v !== '' && $v !== null) $parts[] = $k . '=' . urlencode($v);
        }
        return $parts ? '?' . implode('&', $parts) : '?';
    }
  ?>
  <form method="get" id="filterForm">
    <div class="toolbar" style="flex-wrap:wrap;gap:10px;">
      <!-- Search IP -->
      <input type="hidden" name="days" value="<?= $days ?>">
      <div class="search-wrap">
        <input type="text" name="search" placeholder="ค้นหา IP..." value="<?= htmlspecialchars($search) ?>" onchange="document.getElementById('filterForm').submit()">
      </div>

      <!-- Date picker -->
      <div style="display:flex;align-items:center;gap:6px;">
        <label style="font-size:.72rem;color:var(--muted);white-space:nowrap;">เลือกวันที่:</label>
        <input type="date" name="date" value="<?= htmlspecialchars($date_filter) ?>"
               style="padding:6px 10px;border-radius:8px;border:1px solid var(--border2);background:var(--surface);font-size:.78rem;color:var(--text);font-family:var(--mono);cursor:pointer;"
               onchange="document.getElementById('filterForm').submit()">
        <?php if ($date_filter !== ''): ?>
          <a href="<?= buildQS(['date'=>'','page'=>1]) ?>" style="font-size:.7rem;color:var(--muted);text-decoration:none;" title="ล้างวันที่">✕</a>
        <?php endif; ?>
      </div>

      <!-- Province dropdown -->
      <div style="display:flex;align-items:center;gap:6px;">
        <label style="font-size:.72rem;color:var(--muted);white-space:nowrap;">จังหวัด:</label>
        <select name="province" onchange="document.getElementById('filterForm').submit()"
                style="padding:6px 10px;border-radius:8px;border:1px solid var(--border2);background:var(--surface);font-size:.78rem;color:var(--text);font-family:var(--font);cursor:pointer;max-width:160px;">
          <option value="">ทั้งหมด</option>
          <?php foreach ($provRows as $r): ?>
            <option value="<?= htmlspecialchars($r['province']) ?>" <?= $prov_filter === $r['province'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($r['province']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- City/District dropdown -->
      <div style="display:flex;align-items:center;gap:6px;">
        <label style="font-size:.72rem;color:var(--muted);white-space:nowrap;">อำเภอ:</label>
        <select name="city" onchange="document.getElementById('filterForm').submit()"
                style="padding:6px 10px;border-radius:8px;border:1px solid var(--border2);background:var(--surface);font-size:.78rem;color:var(--text);font-family:var(--font);cursor:pointer;max-width:160px;">
          <option value="">ทั้งหมด</option>
          <?php foreach ($cityRows as $r): ?>
            <option value="<?= htmlspecialchars($r['city']) ?>" <?= $city_filter === $r['city'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($r['city']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Day range pills (hidden when date is active) -->
      <?php if ($date_filter === ''): ?>
      <div class="filter-pills">
        <?php foreach ([1=>'วันนี้', 3=>'3d', 7=>'7d', 30=>'30d', 90=>'90d'] as $d => $label): ?>
          <a class="pill <?= $days==$d?'active':'' ?>" href="<?= buildQS(['days'=>$d,'date'=>'','page'=>1]) ?>"><?= $label ?></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Clear all filters -->
      <?php if ($prov_filter !== '' || $city_filter !== '' || $date_filter !== '' || $search !== ''): ?>
        <a href="?days=<?= $days ?>" class="pill" style="background:var(--surface3);color:var(--red);border-color:rgba(232,72,85,.3);">✕ ล้างฟีลเตอร์</a>
      <?php endif; ?>

      <span class="result-info">พบ <?= number_format($totalIPs) ?> IP (หน้า <?= $page ?>/<?= $totalPages ?>)</span>
    </div>
  </form>

  <!-- Table -->
  <div class="table-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>#</th>
          <th>IP Address</th>
          <th>ตำแหน่ง</th>
          <th>อุปกรณ์</th>
          <th>Sessions</th>
          <th>Total Views</th>
          <th>Total Time</th>
          <th>Avg/Page</th>
          <th>Last Seen</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
        <tr><td colspan="9" class="empty">
          <div class="empty-icon">📭</div>
          <div>ไม่พบข้อมูล IP ในช่วงเวลานี้</div>
        </td></tr>
        <?php else: ?>
        <?php foreach ($rows as $i => $row):
          $num = $offset + $i + 1;
          $devIcon = deviceIcon($row['device_type'] ?? 'desktop');
          $country = $row['country'] ?: '—';
          $city    = $row['city']    ?: '';
          $isToday = $row['last_seen'] && strtotime($row['last_seen']) >= strtotime('today');
        ?>
        <tr>
          <td><span class="num" style="color:var(--muted)"><?= $num ?></span></td>
          <td>
            <a class="ip-link" href="ip_visitor_detail.php?ip=<?= urlencode($row['ip_address']) ?>&days=<?= $days ?>">
              🔗 <?= htmlspecialchars($row['ip_address']) ?>
            </a>
            <?php if ($isToday): ?><br><span class="tag tag-green" style="margin-top:3px">● online today</span><?php endif; ?>
          </td>
          <td>
            <div class="geo-text"><?= htmlspecialchars($country) ?></div>
            <?php if ($city): ?><div class="sub-text"><?= htmlspecialchars($city) ?></div><?php endif; ?>
          </td>
          <td>
            <span title="<?= htmlspecialchars($row['device_type']??'') ?>"><?= $devIcon ?></span>
            <div class="sub-text"><?= htmlspecialchars($row['os']??'—') ?> / <?= htmlspecialchars($row['browser']??'—') ?></div>
          </td>
          <td><span class="num"><?= number_format($row['sessions']) ?></span></td>
          <td><span class="num"><?= number_format($row['total_views']) ?></span></td>
          <td><span class="num"><?= fmtTime($row['total_time']) ?></span></td>
          <td><span class="num"><?= fmtTime($row['avg_time']) ?></span></td>
          <td>
            <div class="last-seen"><?= timeAgo($row['last_seen']) ?></div>
            <div class="sub-text"><?= date('d/m H:i', strtotime($row['last_seen'])) ?></div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div class="pagination">
    <a class="ppage <?= $page<=1?'disabled':'' ?>" href="<?= buildQS(['page'=>$page-1]) ?>">← Prev</a>
    <?php
    $start = max(1, $page-2); $end = min($totalPages, $page+2);
    if ($start > 1) { echo '<a class="ppage" href="'.buildQS(['page'=>1]).'">1</a>'; if ($start>2) echo '<span class="pinfo">…</span>'; }
    for ($p = $start; $p <= $end; $p++):
    ?><a class="ppage <?= $p==$page?'active':'' ?>" href="<?= buildQS(['page'=>$p]) ?>"><?= $p ?></a><?php endfor;
    if ($end < $totalPages) { if ($end<$totalPages-1) echo '<span class="pinfo">…</span>'; echo '<a class="ppage" href="'.buildQS(['page'=>$totalPages]).'">'. $totalPages .'</a>'; }
    ?>
    <a class="ppage <?= $page>=$totalPages?'disabled':'' ?>" href="<?= buildQS(['page'=>$page+1]) ?>">Next →</a>
  </div>
  <?php endif; ?>

</div>

<script src="js/index_.js?v=<?= time(); ?>"></script>
</body>
</html>