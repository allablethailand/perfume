<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'check_permission.php'; 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// กำหนดภาษาเริ่มต้นเป็น 'th' หากไม่มีการกำหนดใน Session
$lang = $_SESSION['lang'] ?? 'th';

// ตรวจสอบภาษาจาก URL
if (isset($_GET['lang'])) {
    $supportedLangs = ['th', 'en', 'cn', 'jp', 'kr'];
    $newLang = $_GET['lang'];
    if (in_array($newLang, $supportedLangs)) {
        // ถ้าเป็นภาษาที่รองรับ ให้บันทึกใน Session
        $_SESSION['lang'] = $newLang;
        $lang = $newLang;
    } else {
        // ถ้าเป็นค่าที่ไม่ถูกต้อง ให้ล้างค่าใน Session เพื่อใช้ค่าเริ่มต้น
        unset($_SESSION['lang']);
        $lang = 'th';
    }
}

// กำหนดข้อความทั้งหมดใน 5 ภาษา
$translations = [
    'th' => [
        'title' => 'Admin Dashboard',
        'greeting_morning' => 'อรุณสวัสดิ์',
        'greeting_afternoon' => 'สวัสดีตอนบ่าย',
        'greeting_evening' => 'สวัสดีตอนเย็น',
        'dashboard_title' => 'ผู้ใช้งาน',
        'user_card_title' => 'ผู้ใช้งาน',
        'user_card_label' => 'ทั้งหมดในระบบ',
        'product_card_title' => 'สินค้า',
        'product_card_label' => 'สินค้าทั้งหมด',
        'project_card_title' => 'โปรเจกต์',
        'project_card_label' => 'โปรเจกต์ในระบบทั้งหมด',
        'blog_card_title' => 'บทความ',
        'blog_card_label' => 'บทความทั้งหมด',
        'acoustic_card_title' => 'ความรู้เกี่ยวกับอะคูสติก',
        'acoustic_card_label' => 'ความรู้ทั้งหมด',
        'video_card_title' => 'วิดีโอ',
        'video_card_label' => 'วิดีโอทั้งหมด',
        'news_card_title' => 'ข่าวสาร',
        'news_card_label' => 'ในระบบทั้งหมด',
        'metatags_card_title' => 'แก้ไข Meta tags',
        'header_card_title' => 'แก้ไขส่วนหัว',
        'banner_card_title' => 'แบนเนอร์หน้าหลัก',
        'footer_card_title' => 'แก้ไขส่วนท้าย',
        'about_card_title' => 'แก้ไขหน้าเกี่ยวกับ',
        'service_card_title' => 'แก้ไขหน้าบริการ',
        'contact_card_title' => 'แก้ไขหน้าติดต่อ',
        'comment_card_title' => 'ความคิดเห็น',
        'comment_card_label' => 'ทั้งหมดในระบบ'
    ],
    'en' => [
        'title' => 'Admin Dashboard',
        'greeting_morning' => 'Good Morning',
        'greeting_afternoon' => 'Good Afternoon',
        'greeting_evening' => 'Good Evening',
        'dashboard_title' => 'Users',
        'user_card_title' => 'Users',
        'user_card_label' => 'Total in system',
        'product_card_title' => 'Product',
        'product_card_label' => 'Total products',
        'project_card_title' => 'Projects',
        'project_card_label' => 'Total projects in system',
        'blog_card_title' => 'Blog',
        'blog_card_label' => 'Total articles',
        'acoustic_card_title' => 'Acoustic knowledge',
        'acoustic_card_label' => 'Total knowledge',
        'video_card_title' => 'Video',
        'video_card_label' => 'Total videos',
        'news_card_title' => 'News',
        'news_card_label' => 'Total in system',
        'metatags_card_title' => 'Edit Meta tags',
        'header_card_title' => 'Edit header',
        'banner_card_title' => 'Main page banner',
        'footer_card_title' => 'Edit footer',
        'about_card_title' => 'Edit about page',
        'service_card_title' => 'Edit service page',
        'contact_card_title' => 'Edit contact page',
        'comment_card_title' => 'Comments',
        'comment_card_label' => 'Total in system'
    ],
    'cn' => [
        'title' => '管理员后台',
        'greeting_morning' => '早上好',
        'greeting_afternoon' => '下午好',
        'greeting_evening' => '晚上好',
        'dashboard_title' => '用户',
        'user_card_title' => '用户',
        'user_card_label' => '系统总数',
        'product_card_title' => '产品',
        'product_card_label' => '全部产品',
        'project_card_title' => '项目',
        'project_card_label' => '系统总项目',
        'blog_card_title' => '博客',
        'blog_card_label' => '全部文章',
        'acoustic_card_title' => '声学知识',
        'acoustic_card_label' => '全部知识',
        'video_card_title' => '视频',
        'video_card_label' => '全部视频',
        'news_card_title' => '新闻',
        'news_card_label' => '系统总数',
        'metatags_card_title' => '编辑元标签',
        'header_card_title' => '编辑页眉',
        'banner_card_title' => '主页横幅',
        'footer_card_title' => '编辑页脚',
        'about_card_title' => '编辑关于页面',
        'service_card_title' => '编辑服务页面',
        'contact_card_title' => '编辑联系页面',
        'comment_card_title' => '评论',
        'comment_card_label' => '系统总数'
    ],
    'jp' => [
        'title' => '管理者ダッシュボード',
        'greeting_morning' => 'おはようございます',
        'greeting_afternoon' => 'こんにちは',
        'greeting_evening' => 'こんばんは',
        'dashboard_title' => 'ユーザー',
        'user_card_title' => 'ユーザー',
        'user_card_label' => 'システム全体',
        'product_card_title' => '製品',
        'product_card_label' => 'すべての製品',
        'project_card_title' => 'プロジェクト',
        'project_card_label' => 'システム内の全プロジェクト',
        'blog_card_title' => 'ブログ',
        'blog_card_label' => 'すべての記事',
        'acoustic_card_title' => '音響知識',
        'acoustic_card_label' => 'すべての知識',
        'video_card_title' => 'ビデオ',
        'video_card_label' => 'すべてのビデオ',
        'news_card_title' => 'ニュース',
        'news_card_label' => 'システム全体',
        'metatags_card_title' => 'メタタグを編集',
        'header_card_title' => 'ヘッダーを編集',
        'banner_card_title' => 'メインページのバナー',
        'footer_card_title' => 'フッターを編集',
        'about_card_title' => 'Aboutページを編集',
        'service_card_title' => 'サービスページを編集',
        'contact_card_title' => '連絡先ページを編集',
        'comment_card_title' => 'コメント',
        'comment_card_label' => 'システム全体'
    ],
    'kr' => [
        'title' => '관리자 대시보드',
        'greeting_morning' => '좋은 아침입니다',
        'greeting_afternoon' => '안녕하세요',
        'greeting_evening' => '안녕하세요',
        'dashboard_title' => '사용자',
        'user_card_title' => '사용자',
        'user_card_label' => '시스템 총 사용자',
        'product_card_title' => '제품',
        'product_card_label' => '전체 제품',
        'project_card_title' => '프로젝트',
        'project_card_label' => '시스템 내 전체 프로젝트',
        'blog_card_title' => '블로그',
        'blog_card_label' => '전체 게시물',
        'acoustic_card_title' => '음향 지식',
        'acoustic_card_label' => '전체 지식',
        'video_card_title' => '비디오',
        'video_card_label' => '전체 비디오',
        'news_card_title' => '뉴스',
        'news_card_label' => '시스템 총 뉴스',
        'metatags_card_title' => '메타태그 편집',
        'header_card_title' => '헤더 편집',
        'banner_card_title' => '메인 페이지 배너',
        'footer_card_title' => '푸터 편집',
        'about_card_title' => '소개 페이지 편집',
        'service_card_title' => '서비스 페이지 편집',
        'contact_card_title' => '연락처 페이지 편집',
        'comment_card_title' => '댓글',
        'comment_card_label' => '시스템 총 댓글'
    ]
];

// ใช้ภาษาที่เลือก (ค่าเริ่มต้นคือ 'th')
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

<style>
    .container, .container-lg, .container-md, .container-sm, .container-xl, .container-xxl {
        max-width: 1450px;
        --bs-gutter-x: 0rem;
    }
    .dashboard-wrapper {
        padding-bottom: 20px;
    }
    .dashboard-layout {
        background-color: #f5f5f5;
        border-radius: 12px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        padding: 20px;
        margin-bottom: 20px;
    }
    .dashboard-card {
        border: 1px solid transparent; 
        border-radius: 4px;
        padding: 12px 18px;
        box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.8),
                    0 2px 6px rgba(0,0,0,0.05);
        transition: 0.3s;
        min-height: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        position: relative;
        flex-direction: row;
    }
    .dashboard-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 0 0 3px rgba(255, 255, 255, 1),
                    0 4px 12px rgba(0,0,0,0.1);
    }
    .dashboard-card .card-inner {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: flex-start;
        width: 100%;
        height: 100%;
        text-align: left;
        gap: 12px;
    }
    .dashboard-card .emoji {
        font-size: 2.8rem;
        margin-right: 0;
        flex-shrink: 0;
        line-height: 1;
    }
    .dashboard-card .text-area {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        flex-grow: 1;
        /* ส่วนที่เพิ่มเข้ามาเพื่อจัดการข้อความที่ยาวเกินไป */
        max-width: calc(100% - 2.8rem - 12px); /* จำกัดความกว้างให้พอดีกับพื้นที่ที่เหลือ */
        overflow: hidden; /* ซ่อนข้อความที่เกิน */
        text-overflow: ellipsis; /* แสดงจุดไข่ปลาถ้าข้อความยาวเกิน */
        white-space: normal; /* อนุญาตให้ขึ้นบรรทัดใหม่ */
    }
    .dashboard-card .text-area h3 {
        margin: 0;
        font-size: 1.0rem;
        font-weight: bold;
        color: #fff;
        line-height: 1.2;
        /* white-space: nowrap; เปลี่ยนเป็น normal เพื่อให้ขึ้นบรรทัดใหม่ได้ */
        overflow-wrap: break-word; /* เพิ่มเพื่อให้คำยาวๆ ขึ้นบรรทัดใหม่ */
    }
    .dashboard-card .count {
        font-size: 1.6rem;
        font-weight: bold;
        color: #fff;
        line-height: 1.2;
    }
    .mb-5 {
    margin-bottom: 2rem !important;
    }
    .dashboard-card .label {
        font-size: 0.85rem;
        color: #fff;
        line-height: 1.2;
        /* white-space: nowrap; เปลี่ยนเป็น normal เพื่อให้ขึ้นบรรทัดใหม่ได้ */
        overflow-wrap: break-word; /* เพิ่มเพื่อให้คำยาวๆ ขึ้นบรรทัดใหม่ */
    }
    .dashboard-card .info-icon {
        position: absolute;
        top: 10px;
        right: 10px;
        color: rgba(255, 255, 255, 0.7);
        font-size: 1.0rem;
    }
    .dashboard-card a {
        display: flex;
        width: 100%;
        height: 100%;
        text-decoration: none;
        color: inherit;
        align-items: center;
        justify-content: center;
    }
    @media (min-width: 1200px) {
        .col-lg-2-4 {
            flex: 0 0 20%;
            max-width: 20%;
        }
    }
    .row>* {
        flex-shrink: 0;
        max-width: 100%;
        padding-right: calc(var(--bs-gutter-x) * .2);
        padding-left: calc(var(--bs-gutter-x) * .2);
        margin-top: var(--bs-gutter-y);
    }
    .announce-card, .attendance-card, .birthday-card, .calendar-card {
        background-color: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        padding: 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        overflow: hidden;
    }
    .announce-card { min-height: 300px; }
    .attendance-card, .birthday-card { min-height: 150px; }
    .calendar-card { min-height: 300px; }
    .announce-card h2 { color: #555; margin-bottom: 15px; font-size: 1.5rem; }
    .announce-card img { max-width: 100%; height: auto; border-radius: 8px; margin-bottom: 15px; }
    .announce-card .employee-info h3 { margin: 0; color: #333; font-size: 1.2rem; }
    .announce-card .employee-info p { color: #777; font-size: 0.9rem; }
    .attendance-card h4, .birthday-card h4 { color: #555; margin-bottom: 15px; font-size: 1.1rem; }
    .attendance-grid { display: flex; justify-content: space-around; width: 100%; margin-top: 15px; }
    .attendance-item { display: flex; flex-direction: column; align-items: center; }
    .attendance-item .time { font-size: 1.8rem; font-weight: bold; color: #4CAF50; margin-bottom: 5px; }
    .attendance-item.out .time { color: #FF5722; }
    .attendance-item .label { font-size: 0.8rem; color: #777; }
    .attendance-options { margin-top: 15px; }
    .attendance-options button { background-color: #eee; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; margin: 0 5px; font-size: 0.9rem; color: #555; }
    .attendance-options button.active { background-color: #007bff; color: #fff; }
    .birthday-card .emoji { font-size: 3rem; margin-bottom: 10px; }
    .birthday-card .text { font-size: 1rem; color: #555; }
    .calendar-card { background-color: #fff; border-radius: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); padding: 20px; min-height: 300px; }
    .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; color: #555; font-weight: bold; }
    .calendar-header .month-year { font-size: 1.2rem; }
    .calendar-days { display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; font-weight: bold; color: #888; margin-bottom: 10px; }
    .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; text-align: center; }
    .calendar-grid .day-number { padding: 8px 5px; border-radius: 5px; cursor: pointer; font-size: 0.9rem; color: #333; position: relative; }
    .calendar-grid .day-number:hover { background-color: #f0f0f0; }
    .calendar-grid .day-number.inactive { color: #ccc; }
    .calendar-grid .day-number.current-day { background-color: #007bff; color: #fff; font-weight: bold; }
    .calendar-grid .day-number.has-event { background-color: #FFEBEE; color: #D32F2F; font-weight: bold; }
    .calendar-grid .day-number.has-multiple-events { background-color: #E3F2FD; color: #1976D2; font-weight: bold; }
    .event-indicator { position: absolute; bottom: 2px; left: 50%; transform: translateX(-50%); width: 5px; height: 5px; background-color: #D32F2F; border-radius: 50%; }
    .multiple-event-indicator { background-color: #1976D2; }
    .calendar-legend { display: flex; justify-content: flex-end; margin-top: 15px; font-size: 0.8rem; }
    .calendar-legend-item { display: flex; align-items: center; margin-left: 15px; }
    .calendar-legend-item .color-box { width: 12px; height: 12px; border-radius: 3px; margin-right: 5px; }
    .color-box.activity { background-color: #FFEBEE; }
    .color-box.work { background-color: #E3F2FD; }
    .color-box.helpdesk { background-color: #E8F5E9; }
    .day-number.activity-event { background-color: #FFE0B2; color: #E65100; }
    .day-number.work-event { background-color: #BBDEFB; color: #1565C0; }
    .day-number.helpdesk-event { background-color: #C8E6C9; color: #2E7D32; }
    .day-number.support-helpdesk-event { background-color: #F8BBD0; color: #AD1457; }
    .dashboard-wrapper h2 {
        font-size: 1.3rem;
        font-weight: 380;
        color: #333;
        margin-bottom: 5px;
    }
    .dashboard-wrapper h3 {
        font-size: 0.6rem;
        font-weight: 300;
        color: #777;
        margin-top: 0;
        margin-bottom: 20px;
    }
    .dashboard-card-graph {
        display: flex;
        flex-direction: column;
        background-color: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        padding: 20px;
        min-height: 350px;
    }
    .chart-container {
        position: relative;
        width: 100%;
        height: 280px; /* กำหนดความสูงที่แน่นอนเพื่อป้องกันกราฟยืด */
    }
    .chart-header {
        text-align: left;
        margin-bottom: 10px;
    }
    .chart-header h4 {
        margin: 0;
        font-size: 1.2rem;
        font-weight: bold;
        color: #333;
    }
    .chart-header p {
        margin: 0;
        font-size: 0.85rem;
        color: #777;
    }
</style>

</head>
<?php
// นับจำนวนแถวทั้งหมดในตาราง mb_comments
$latestCommentId = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total_rows FROM mb_comments");
if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $latestCommentId = $row['total_rows'];
    }
}
$stmt->close();
?>

<?php
// นับจำนวนแถวทั้งหมดในตาราง dn_blog ที่ del = 0
$latestBlogId = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total_rows FROM dn_blog WHERE del = 0");
if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $latestBlogId = $row['total_rows'];
    }
}
$stmt->close();
?>

<?php
// นับจำนวนแถวทั้งหมดในตาราง mb_user ที่ del = 0
$latestUserId = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total_rows FROM mb_user WHERE del = 0");
if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $latestUserId = $row['total_rows'];
    }
}
$stmt->close();
?>

<?php
// นับจำนวนแถวทั้งหมดในตาราง dn_news ที่ del = 0
$latestNewsId = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total_rows FROM dn_news WHERE del = 0");
if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $latestNewsId = $row['total_rows'];
    }
}
$stmt->close();
?>

<?php
// นับจำนวนแถวทั้งหมดในตาราง dn_idia ที่ del = 0
$latestIdiaId = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total_rows FROM dn_idia WHERE del = 0");
if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $latestIdiaId = $row['total_rows'];
    }
}
$stmt->close();
?>

<?php
// นับจำนวนแถวทั้งหมดในตาราง logo_settings
$latestlogoId = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total_rows FROM logo_settings");
if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $latestlogoId = $row['total_rows'];
    }
}
$stmt->close();
?>

<?php
// นับจำนวนแถวทั้งหมดในตาราง videos
$latestvideosId = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total_rows FROM videos");
if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $latestvideosId = $row['total_rows'];
    }
}
$stmt->close();
?>

<?php
// นับจำนวนแถวทั้งหมดในตาราง footer_settings
$latestfooterId = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total_rows FROM footer_settings");
if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $latestfooterId = $row['total_rows'];
    }
} 
$stmt->close();
?>

<?php
// นับจำนวนแถวทั้งหมดในตาราง dn_shop ที่ del = 0
$latestShopId = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total_rows FROM dn_shop WHERE del = 0");
if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $latestShopId = $row['total_rows'];
    }
}
$stmt->close();
?>

<?php
// นับจำนวนแถวทั้งหมดในตาราง dn_project ที่ del = 0
$latestProjectId = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total_rows FROM dn_project WHERE del = 0");
if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $latestProjectId = $row['total_rows'];
    }
}
$stmt->close();
?>

<?php
// ดึงbannerล่าสุด
$latestBannersId = 0;
$stmt = $conn->prepare("SELECT MAX(id) AS max_id FROM banner");
if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $latestBannersId = $row['max_id'];
    }
}
$stmt->close();
?>

<body>

    <?php include 'template/header.php'; ?>

    <div class="dashboard-wrapper container" style="padding-top: 1em;">
    <?php
date_default_timezone_set('Asia/Bangkok');
$hour = date('H');
if ($hour >= 5 && $hour < 12) {
    $greeting = $currentLang['greeting_morning'];
} elseif ($hour >= 12 && $hour < 18) {
    $greeting = $currentLang['greeting_afternoon'];
} else {
    $greeting = $currentLang['greeting_evening'];
}
$username = $_SESSION['fullname'] ?? 'Admin';
?>
<h2 class="mb-1"><?= $greeting ?> <?= htmlspecialchars($username) ?>!</h2>
<h3 class="mb-5"><?= $currentLang['dashboard_title'] ?></h3>

    <div class="row">
    <div class="col-12 col-md-6 mb-4">
        <div class="dashboard-card-graph">
            <div class="chart-header">
                <h4>Daily Active Users</h4>
                <p>ข้อมูลผู้ใช้งานรายวันในช่วง 30 วันที่ผ่านมา</p>
            </div>
            <div class="chart-container">
                <canvas id="userChart"></canvas>
            </div>
        </div>
    </div>
      <div class="col-12 col-md-6 mb-4">
        <div class="dashboard-card-graph">
            <div class="chart-header">
                <h4>Top View Products</h4>
                <p>สินค้าที่มีผู้เข้าชมมากที่สุด</p>
            </div>
            <div class="chart-container">
                <canvas id="topProductsChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 mb-4">
        <div class="dashboard-card-graph">
            <div class="chart-header">
                <h4>User Country</h4>
                <p>จำนวนผู้ใช้งานในแต่ละประเทศ</p>
            </div>
            <div class="chart-container">
                <canvas id="countryChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 mb-4">
        <div class="dashboard-card-graph">
            <div class="chart-header">
                <h4>User Source</h4>
                <p>ที่มาของผู้ใช้งาน (เช่น Organic, Direct, Referral)</p>
            </div>
            <div class="chart-container">
                <canvas id="sourceChart"></canvas>
            </div>
        </div>
    </div>
   <div class="col-12 col-md-6 mb-4">
        <div class="dashboard-card-graph">
            <div class="chart-header">
                <h4>Top Pages</h4>
                <p>10 หน้าที่มีผู้เข้าชมสูงสุด</p>
            </div>
            <div class="chart-container">
                <canvas id="topPagesChart"></canvas>
            </div>
        </div>
    </div>
</div>

    <div class="dashboard-layout">
        <!-- <div class="row justify-content-center"> -->
        <div class="row">
    <div class="col-12 col-sm-6 col-md-4 col-lg-2-4 mb-4">
        <div class="dashboard-card" style="background-color:#ffa726;">
            <a href="set_users/edit_users.php">
                <div class="card-inner">
                    <div class="emoji">👤</div>
                    <div class="text-area">
                        <h3><?= $currentLang['user_card_title'] ?></h3>
                        <div class="count"><?= $latestUserId ?></div>
                        <div class="label"><?= $currentLang['user_card_label'] ?></div>
                    </div>
                </div>
                <div class="info-icon"><i class="fas fa-info-circle"></i></div>
            </a>
        </div>
    </div>

    <!-- <div class="col-12 col-sm-6 col-md-4 col-lg-2-4 mb-4">
        <div class="dashboard-card" style="background-color:#42a5f5;">
            <a href="set_product/list_shop.php">
                <div class="card-inner">
                    <div class="emoji">📦</div>
                    <div class="text-area">
                        <h3><?= $currentLang['product_card_title'] ?></h3>
                        <div class="count"><?= $latestShopId ?></div>
                        <div class="label"><?= $currentLang['product_card_label'] ?></div>
                    </div>
                </div>
                <div class="info-icon"><i class="fas fa-info-circle"></i></div>
            </a>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-md-4 col-lg-2-4 mb-4">
        <div class="dashboard-card" style="background-color:#26c6da;">
            <a href="set_project/list_project.php">
                <div class="card-inner">
                    <div class="emoji">📁</div>
                    <div class="text-area">
                        <h3><?= $currentLang['project_card_title'] ?></h3>
                        <div class="count"><?= $latestProjectId ?></div>
                        <div class="label"><?= $currentLang['project_card_label'] ?></div>
                    </div>
                </div>
                <div class="info-icon"><i class="fas fa-info-circle"></i></div>
            </a>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-md-4 col-lg-2-4 mb-4">
        <div class="dashboard-card" style="background-color:#ab47bc;">
            <a href="set_Blog/list_Blog.php">
                <div class="card-inner">
                    <div class="emoji">✍️</div>
                    <div class="text-area">
                        <h3><?= $currentLang['blog_card_title'] ?></h3>
                        <div class="count"><?= $latestBlogId ?></div>
                        <div class="label"><?= $currentLang['blog_card_label'] ?></div>
                    </div>
                </div>
                <div class="info-icon"><i class="fas fa-info-circle"></i></div>
            </a>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-md-4 col-lg-2-4 mb-4">
        <div class="dashboard-card" style="background-color:#78909c;">
            <a href="set_idia/list_idia.php">
                <div class="card-inner">
                    <div class="emoji">💡</div>
                    <div class="text-area">
                        <h3><?= $currentLang['acoustic_card_title'] ?></h3>
                        <div class="count"><?= $latestIdiaId ?></div>
                        <div class="label"><?= $currentLang['acoustic_card_label'] ?></div>
                    </div>
                </div>
                <div class="info-icon"><i class="fas fa-info-circle"></i></div>
            </a>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-md-4 col-lg-2-4 mb-4">
        <div class="dashboard-card" style="background-color:#8bc34a;">
            <a href="set_video/admin_video_list.php">
                <div class="card-inner">
                    <div class="emoji">🎥</div>
                    <div class="text-area">
                        <h3><?= $currentLang['video_card_title'] ?></h3>
                        <div class="count"><?= $latestvideosId ?></div>
                        <div class="label"><?= $currentLang['video_card_label'] ?></div>
                    </div>
                </div>
                <div class="info-icon"><i class="fas fa-info-circle"></i></div>
            </a>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-md-4 col-lg-2-4 mb-4">
        <div class="dashboard-card" style="background-color:#ec407a;">
            <a href="set_news/list_news.php">
                <div class="card-inner">
                    <div class="emoji">📰</div>
                    <div class="text-area">
                        <h3><?= $currentLang['news_card_title'] ?></h3>
                        <div class="count"><?= $latestNewsId ?></div>
                        <div class="label"><?= $currentLang['news_card_label'] ?></div>
                    </div>
                </div>
                <div class="info-icon"><i class="fas fa-info-circle"></i></div>
            </a>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-md-4 col-lg-2-4 mb-4">
        <div class="dashboard-card" style="background-color:#730ac9;">
            <a href="set_metatags/list_metatags.php">
                <div class="card-inner">
                    <div class="emoji">🏷️</div>
                    <div class="text-area">
                        <h3><?= $currentLang['metatags_card_title'] ?></h3>
                    </div>
                </div>
                <div class="info-icon"><i class="fas fa-info-circle"></i></div>
            </a>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-md-4 col-lg-2-4 mb-4">
        <div class="dashboard-card" style="background-color:#ff7043;">
            <a href="set_logo/edit_logo.php">
                <div class="card-inner">
                    <div class="emoji">⚙️</div>
                    <div class="text-area">
                        <h3><?= $currentLang['header_card_title'] ?></h3>
                    </div>
                </div>
                <div class="info-icon"><i class="fas fa-info-circle"></i></div>
            </a>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-md-4 col-lg-2-4 mb-4">
        <div class="dashboard-card" style="background-color:#ffca28;">
            <a href="set_banner/list_banner.php">
                <div class="card-inner">
                    <div class="emoji">🖼️</div>
                    <div class="text-area">
                        <h3><?= $currentLang['banner_card_title'] ?></h3>
                    </div>
                </div>
                <div class="info-icon"><i class="fas fa-info-circle"></i></div>
            </a>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-md-4 col-lg-2-4 mb-4">
        <div class="dashboard-card" style="background-color:#d4e157;">
            <a href="set_footer/edit_footer.php">
                <div class="card-inner">
                    <div class="emoji">⬇️</div>
                    <div class="text-area">
                        <h3><?= $currentLang['footer_card_title'] ?></h3>
                    </div>
                </div>
                <div class="info-icon"><i class="fas fa-info-circle"></i></div>
            </a>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-md-4 col-lg-2-4 mb-4">
        <div class="dashboard-card" style="background-color:#ef5350;">
            <a href="set_about/edit_about.php">
                <div class="card-inner">
                    <div class="emoji">ℹ️</div>
                    <div class="text-area">
                        <h3><?= $currentLang['about_card_title'] ?></h3>
                    </div>
                </div>
                <div class="info-icon"><i class="fas fa-info-circle"></i></div>
            </a>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-md-4 col-lg-2-4 mb-4">
        <div class="dashboard-card" style="background-color:#66bb6a;">
            <a href="set_service/edit_service.php">
                <div class="card-inner">
                    <div class="emoji">🛠️</div>
                    <div class="text-area">
                        <h3><?= $currentLang['service_card_title'] ?></h3>
                    </div>
                </div>
                <div class="info-icon"><i class="fas fa-info-circle"></i></div>
            </a>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-md-4 col-lg-2-4 mb-4">
        <div class="dashboard-card" style="background-color:#29b6f6;">
            <a href="set_contact/edit_contact.php">
                <div class="card-inner">
                    <div class="emoji">📞</div>
                    <div class="text-area">
                        <h3><?= $currentLang['contact_card_title'] ?></h3>
                    </div>
                </div>
                <div class="info-icon"><i class="fas fa-info-circle"></i></div>
            </a>
        </div>
    </div> -->

    <div class="col-12 col-sm-6 col-md-4 col-lg-2-4 mb-4">
        <div class="dashboard-card" style="background-color:#26a69a;">
            <a href="set_comment/comment_service.php">
                <div class="card-inner">
                    <div class="emoji">💬</div>
                    <div class="text-area">
                        <h3><?= $currentLang['comment_card_title'] ?></h3>
                        <div class="count"><?= $latestCommentId ?></div>
                        <div class="label"><?= $currentLang['comment_card_label'] ?></div>
                    </div>
                </div>
                <div class="info-icon"><i class="fas fa-info-circle"></i></div>
            </a>
        </div>
    </div>
</div>
    </div> <script src="js/index_.js?v=<?= time(); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    fetch('fetch_analytics_data.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok, status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                console.error('API Error:', data.error);
                return;
            }

            function createChart(chartId, chartType, chartData, chartOptions) {
                const ctx = document.getElementById(chartId);
                if (ctx) {
                    new Chart(ctx.getContext('2d'), {
                        type: chartType,
                        data: chartData,
                        options: chartOptions
                    });
                } else {
                    console.error(`Error: Canvas element with ID "${chartId}" not found.`);
                }
            }

            // ชุดสีใหม่สำหรับสไตล์ Minimalist
            const barColors = [
                '#588B8B', // เขียวอมเทา
                '#C8C8A9', // เทาอ่อนอมเหลือง
                '#F28F3B', // ส้ม
                '#A24E2E', // น้ำตาลแดง
                '#686F70', // เทาเข้ม
                '#4E8975', // เขียวอมน้ำเงิน
                '#E19D6F', // สีอิฐ
                '#C4A28A', // เบจอมเทา
                '#8C6D68', // น้ำตาลอมเทา
                '#B4B2A8'  // เทาอ่อน
            ];
            
            // กราฟที่ 1: Daily Active Users (เส้น)
            createChart('userChart', 'line', {
                labels: data.daily_users.labels,
                datasets: [{
                    label: 'Active Users (30 days)',
                    data: data.daily_users.data,
                    fill: true,
                    backgroundColor: 'rgba(88, 139, 139, 0.2)',
                    borderColor: '#588B8B',
                    tension: 0.4,
                    borderWidth: 2,
                    pointBackgroundColor: '#588B8B'
                }]
            }, {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { display: false }
                    },
                    x: {
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false },
                    title: { // เพิ่มส่วนนี้สำหรับชื่อกราฟ
                        display: true,
                        text: 'Daily Active Users (30 days)'
                    }
                }
            });

            // กราฟที่ 2: Top Pages
            createChart('topPagesChart', 'bar', {
                labels: data.top_pages.labels,
                datasets: [{
                    label: 'Top Pages',
                    data: data.top_pages.data,
                    backgroundColor: barColors.slice(0, data.top_pages.data.length),
                }]
            }, {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'x',
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { display: false }
                    },
                    x: {
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false },
                    title: { // เพิ่มส่วนนี้
                        display: true,
                        text: 'Top Pages'
                    }
                }
            });

            // กราฟที่ 3: Source
            createChart('sourceChart', 'bar', {
                labels: data.source.labels,
                datasets: [{
                    label: 'User Source',
                    data: data.source.data,
                    backgroundColor: barColors.slice(0, data.source.data.length),
                }]
            }, {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'x',
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { display: false }
                    },
                    x: {
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false },
                    title: { // เพิ่มส่วนนี้
                        display: true,
                        text: 'User Source'
                    }
                }
            });

            // กราฟที่ 4: Country
            createChart('countryChart', 'bar', {
                labels: data.country.labels,
                datasets: [{
                    label: 'User Country',
                    data: data.country.data,
                    backgroundColor: barColors.slice(0, data.country.data.length),
                }]
            }, {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'x',
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { display: false }
                    },
                    x: {
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false },
                    title: { // เพิ่มส่วนนี้
                        display: true,
                        text: 'User Country'
                    }
                }
            });

            // กราฟที่ 5: Top Products
            createChart('topProductsChart', 'bar', {
                labels: data.top_products.labels,
                datasets: [{
                    label: 'Top View Products',
                    data: data.top_products.data,
                    backgroundColor: barColors.slice(0, data.top_products.data.length),
                }]
            }, {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { display: false }
                    },
                    y: {
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false },
                    title: { // เพิ่มส่วนนี้
                        display: true,
                        text: 'Top View Products'
                    }
                }
            });
        })
        .catch(error => {
            console.error('Fetch error:', error);
        });
});
</script>
</body>
</html>