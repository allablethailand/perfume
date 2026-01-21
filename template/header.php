<?php
require_once('lib/connect.php');
global $conn;

// Language handling
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$lang = 'th';
if (isset($_GET['lang'])) {
    $supportedLangs = ['th', 'en', 'cn', 'jp', 'kr'];
    if (in_array($_GET['lang'], $supportedLangs)) {
        $_SESSION['lang'] = $_GET['lang'];
        $lang = $_GET['lang'];
    } else {
        unset($_SESSION['lang']);
    }
} elseif (isset($_SESSION['lang'])) {
    $lang = $_SESSION['lang'];
}

// Generate session_id for guest users
if (!isset($_SESSION['guest_session_id'])) {
    $_SESSION['guest_session_id'] = session_id();
}

$currentPage = basename($fragment . ".php");
$meta = [];

// ดึงข้อมูล meta tags จากตาราง metatags
$stmt = $conn->prepare("SELECT * FROM metatags WHERE page_name = ?");
$stmt->bind_param("s", $currentPage);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $meta = $result->fetch_assoc();
}
$stmt->close();

// ดึงข้อมูล logo
$logo_path = 'public/img/LOGOTRAND.png';
$logo_id_for_display = 1;

$stmt_logo = $conn->prepare("SELECT image_path FROM logo_settings WHERE id = ?");
$stmt_logo->bind_param("i", $logo_id_for_display);
$stmt_logo->execute();
$result_logo = $stmt_logo->get_result();

if ($logo_data = $result_logo->fetch_assoc()) {
    $logo_path = htmlspecialchars($logo_data['image_path']);
}
$stmt_logo->close();

// Helper function for generating links
function generateLink($link, $params = [])
{
    global $isFile;
    $url = $link . $isFile;
    $existingParams = $_GET;
    $newParams = array_merge($existingParams, $params);
    $newParams = array_filter($newParams);
    $queryString = http_build_query($newParams);
    if (!empty($queryString)) {
        $url .= '?' . $queryString;
    }
    return $url;
}

$isProtocol = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http';
$isFile = ($isProtocol === 'http') ? '.php' : '';

// Menu translations
$menuTranslations = [
    'home' => [
        'th' => 'หน้าแรก',
        'en' => 'Home',
        'cn' => '主页',
        'jp' => 'ホーム',
        'kr' => '홈'
    ],
    'products' => [
        'th' => 'สินค้า',
        'en' => 'Products',
        'cn' => '产品',
        'jp' => '製品',
        'kr' => '제품'
    ],
    'news' => [
        'th' => 'ข่าวสาร',
        'en' => 'News',
        'cn' => '新闻',
        'jp' => 'ニュース',
        'kr' => '뉴스'
    ],
    'about' => [
        'th' => 'เกี่ยวกับเรา',
        'en' => 'About',
        'cn' => '关于我们',
        'jp' => '会社概要',
        'kr' => '회사 소개'
    ],
    'contact' => [
        'th' => 'ติดต่อเรา',
        'en' => 'Contact',
        'cn' => '联系我们',
        'jp' => 'お問い合わせ',
        'kr' => '문의하기'
    ]
];

// Modal translations
$modalTranslations = [
    'please_login' => [
        'th' => 'กรุณาเข้าสู่ระบบ',
        'en' => 'Please Log In',
        'cn' => '请登录',
        'jp' => 'ログインしてください',
        'kr' => '로그인하세요'
    ],
    'email_phone' => [
        'th' => 'อีเมลหรือเบอร์โทรศัพท์',
        'en' => 'Email or Phone Number',
        'cn' => '电子邮件或电话号码',
        'jp' => 'メールまたは電話番号',
        'kr' => '이메일 또는 전화번호'
    ],
    'password' => [
        'th' => 'รหัสผ่าน',
        'en' => 'Password',
        'cn' => '密码',
        'jp' => 'パスワード',
        'kr' => '비밀번호'
    ],
    'sign_up' => [
        'th' => 'สมัครสมาชิก',
        'en' => 'Sign Up',
        'cn' => '注册',
        'jp' => '新規登録',
        'kr' => '회원가입'
    ],
    'forgot_password' => [
        'th' => 'ลืมรหัสผ่าน?',
        'en' => 'Forgot Password?',
        'cn' => '忘记密码？',
        'jp' => 'パスワードをお忘れですか？',
        'kr' => '비밀번호를 잊으셨나요?'
    ],
    'login' => [
        'th' => 'เข้าสู่ระบบ',
        'en' => 'Login',
        'cn' => '登录',
        'jp' => 'ログイン',
        'kr' => '로그인'
    ],
    'forgot_password_title' => [
        'th' => 'ลืมรหัสผ่าน?',
        'en' => 'Forgot Password?',
        'cn' => '忘记密码？',
        'jp' => 'パスワードをお忘れですか？',
        'kr' => '비밀번호를 잊으셨나요?'
    ],
    'enter_email' => [
        'th' => 'กรอกอีเมลของคุณ',
        'en' => 'Enter your email',
        'cn' => '输入您的电子邮件',
        'jp' => 'メールアドレスを入力してください',
        'kr' => '이메일을 입력하세요'
    ],
    'send_email' => [
        'th' => 'ส่งอีเมล',
        'en' => 'Send Email',
        'cn' => '发送电子邮件',
        'jp' => 'メールを送信',
        'kr' => '이메일 보내기'
    ],
    'language' => [
        'th' => 'เลือกภาษา',
        'en' => 'Choose Language',
        'cn' => '选择语言',
        'jp' => '言語を選択',
        'kr' => '언어 선택'
    ]
];

function t($key, $translations, $lang) {
    return $translations[$key][$lang] ?? $translations[$key]['en'];
}

$navbarItems = [
    ['link' => '?', 'key' => 'home'],
    ['link' => '?product', 'key' => 'products'],
    ['link' => '?news', 'key' => 'news'],
    ['link' => '?about', 'key' => 'about'],
    // ['link' => '?contact', 'key' => 'contact'],
];

// Get logo
$logo_path = 'public/img/LOGOTRAND.png';
$logo_id_for_display = 1;
$stmt_logo = $conn->prepare("SELECT image_path FROM logo_settings WHERE id = ?");
$stmt_logo->bind_param("i", $logo_id_for_display);
$stmt_logo->execute();
$result_logo = $stmt_logo->get_result();
if ($logo_data = $result_logo->fetch_assoc()) {
    $logo_path = htmlspecialchars($logo_data['image_path']);
}
$stmt_logo->close();

// Language config
$languages = [
    'th' => ['name' => 'ไทย', 'flag' => 'https://flagcdn.com/th.svg'],
    'en' => ['name' => 'English', 'flag' => 'https://flagcdn.com/gb.svg'],
    'cn' => ['name' => '中文', 'flag' => 'https://flagcdn.com/cn.svg'],
    'jp' => ['name' => '日本語', 'flag' => 'https://flagcdn.com/jp.svg'],
    'kr' => ['name' => '한국어', 'flag' => 'https://flagcdn.com/kr.svg']
];

?>

<!-- SEO Meta Tags -->
<title><?= $meta['meta_title'] ?? 'Trandar Perfume – น้ำหอมพรีเมียมพร้อม AI Companion สำหรับคนมีระดับ' ?></title>
<meta name="description" content="<?= $meta['meta_description'] ?? 'Trandar Perfume น้ำหอมพรีเมียมที่ผสาน AI เป็นเพื่อนรู้ใจ แนะนำกลิ่นเฉพาะตัว สะท้อนตัวตนและรสนิยมของคนมีระดับ' ?>">
<meta name="keywords" content="<?= $meta['meta_keywords'] ?? 'Trandar Perfume, น้ำหอมพรีเมียม, AI Perfume, น้ำหอมอัจฉริยะ, luxury perfume, personalized fragrance' ?>">
<meta name="author" content="trandar.scent">
<!-- Open Graph Meta Tags -->
<meta property="og:site_name" content="trandar.scent">
<meta property="og:title" content="<?= $meta['og_title'] ?? $meta['meta_title'] ?? 'Trandar Perfume – Premium AI Fragrance for Distinctive Lifestyle' ?>">
<meta property="og:description" content="<?= $meta['og_description'] ?? $meta['meta_description'] ?? 'สัมผัสประสบการณ์น้ำหอมพรีเมียมที่มี AI เป็นเพื่อนคู่ใจ ช่วยเลือกกลิ่นที่ใช่สำหรับไลฟ์สไตล์และตัวตนของคุณ' ?>">
<meta property="og:type" content="website">
<meta property="og:image" content="<?= $meta['og_image'] ?? 'public/img/TRANDAR-PERFUME.png' ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Favicon -->
<link rel="icon" type="image/x-icon" href="public/product_images/696089dc2eba5_1767934428.jpg">
<link href="app/css/index_.css?v=<?= time(); ?>" rel="stylesheet">
<!-- Header Styles -->
<style>
    :root {
        --luxury-black: #000000;
        --luxury-white: #ffffff;
        --transition: cubic-bezier(0.4, 0, 0.2, 1);
    }

    .header {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        z-index: 1000;
        background: #f8f8f861;
        backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        transition: all 0.4s var(--transition);
    }

    .header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 25px 80px;
        position: relative;
    }

    /* Logo - Centered on Mobile, Left on Desktop */
    .logo {
        font-family: 'Playfair Display', serif;
        font-size: 24px;
        font-weight: 600;
        letter-spacing: 0.15em;
        color: var(--luxury-black);
        text-decoration: none;
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        z-index: 10;
    }

    .logo img {
        height: 50px;
        display: block;
    }

    /* Hamburger Menu */
    .hamburger {
        display: none;
        flex-direction: column;
        gap: 5px;
        background: none;
        border: none;
        cursor: pointer;
        padding: 5px;
        z-index: 1001;
    }

    .hamburger span {
        width: 25px;
        height: 2px;
        background: var(--luxury-black);
        transition: all 0.3s ease;
    }

    .hamburger.active span:nth-child(1) {
        transform: rotate(45deg) translate(7px, 7px);
    }

    .hamburger.active span:nth-child(2) {
        opacity: 0;
    }

    .hamburger.active span:nth-child(3) {
        transform: rotate(-45deg) translate(7px, -7px);
    }

    /* Navigation */
    .nav {
        display: flex;
        gap: 30px;
    }

    .nav-link {
        font-size: 13px;
        font-weight: 400;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #000000;
        text-decoration: none;
        position: relative;
        transition: color 0.3s ease;
    }

    .nav-link::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 0;
        width: 0;
        height: 1px;
        background: var(--luxury-black);
        transition: width 0.4s var(--transition);
    }

    .nav-link:hover::after {
        width: 100%;
    }

    .header-actions {
        display: flex;
        gap: 30px;
        align-items: center;
    }

    .action-btn {
        background: none;
        border: none;
        font-size: 14px;
        cursor: pointer;
        color: var(--luxury-black);
        transition: opacity 0.3s ease;
        position: relative;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .action-btn:hover {
        opacity: 0.6;
    }

    /* AI Avatar Styles */
    #aiAvatar {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--luxury-black);
        transition: all 0.3s ease;
        display: none;
    }

    #aiAvatar:hover {
        opacity: 0.8;
        transform: translate(-50%, -50%) scale(1.1);
    }

    .cart-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: var(--luxury-black);
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        font-weight: bold;
    }

    /* ========================================
       SEARCH DROPDOWN STYLES
       ======================================== */
    .search-container {
        position: relative;
    }

    .search-dropdown {
        position: absolute;
        top: calc(100% + 15px);
        right: 0;
        background: white;
        border: 1px solid rgba(0, 0, 0, 0.1);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        min-width: 400px;
        max-width: 500px;
        border-radius: 12px;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s ease;
        z-index: 1002;
        max-height: 600px;
        overflow-y: auto;
    }

    .search-container.active .search-dropdown {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .search-input-wrapper {
        position: relative;
        padding: 20px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    }

    .search-dropdown-input {
        width: 100%;
        padding: 12px 40px 12px 15px;
        border: 2px solid #e5e5e5;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .search-dropdown-input:focus {
        outline: none;
        border-color: var(--luxury-black);
    }

    .search-icon {
        position: absolute;
        right: 35px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
        pointer-events: none;
    }

    .search-results {
        padding: 15px;
    }

    .search-loading,
    .search-empty {
        text-align: center;
        padding: 30px 20px;
        color: #999;
        font-size: 14px;
    }

    .search-section-title {
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 1px;
        text-transform: uppercase;
        color: #999;
        margin: 15px 0 10px 0;
        padding-left: 5px;
    }

    .search-item {
        display: flex;
        gap: 12px;
        padding: 10px;
        border-radius: 8px;
        text-decoration: none;
        color: inherit;
        transition: all 0.2s ease;
        margin-bottom: 8px;
    }

    .search-item:hover {
        background: #f8f8f8;
        transform: translateX(3px);
    }

    .search-item-image {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 6px;
        flex-shrink: 0;
        background: #f5f5f5;
    }

    .search-item-content {
        flex: 1;
        min-width: 0;
    }

    .search-item-title {
        font-size: 14px;
        font-weight: 500;
        color: #1a1a1a;
        margin-bottom: 4px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .search-item-desc {
        font-size: 12px;
        color: #666;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .search-item-price {
        font-size: 13px;
        font-weight: 600;
        color: var(--luxury-black);
        margin-top: 4px;
    }

    .search-view-all {
        display: block;
        text-align: center;
        padding: 12px;
        margin-top: 10px;
        background: #f8f8f8;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        color: var(--luxury-black);
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .search-view-all:hover {
        background: #ececec;
    }

    .language-switcher {
        position: relative;
    }

    .language-current {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        background: transparent;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .language-current:hover {
        opacity: 0.6;
    }

    .language-flag {
        width: 18px;
        height: 13px;
        object-fit: cover;
        border: none;
    }

    .language-name {
        font-size: 12px;
        font-weight: 400;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--luxury-black);
    }

    .language-arrow {
        font-size: 8px;
        color: var(--luxury-black);
        transition: transform 0.3s ease;
    }

    .language-switcher.active .language-arrow {
        transform: rotate(180deg);
    }

    .language-dropdown {
        position: absolute;
        top: calc(100% + 5px);
        right: 0;
        background: white;
        border: 1px solid rgba(0, 0, 0, 0.1);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        min-width: 150px;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s ease;
        z-index: 1001;
    }

    .language-switcher.active .language-dropdown {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .language-option {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        text-decoration: none;
        color: var(--luxury-black);
        transition: background-color 0.2s ease;
        font-size: 12px;
        letter-spacing: 0.05em;
    }

    .language-option:hover {
        background-color: rgba(0, 0, 0, 0.03);
    }

    .language-option.active {
        background-color: rgba(0, 0, 0, 0.05);
    }

    .language-option .language-flag {
        border: none;
    }

    .user-menu {
        position: relative;
    }

    .user-dropdown {
        position: absolute;
        top: calc(100% + 10px);
        right: 0;
        background: white;
        border: 1px solid rgba(0, 0, 0, 0.1);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        min-width: 180px;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s ease;
        z-index: 1001;
        border-radius: 4px;
    }

    .user-menu:hover .user-dropdown {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .user-dropdown a {
        display: block;
        padding: 12px 16px;
        color: var(--luxury-black);
        text-decoration: none;
        font-size: 13px;
        letter-spacing: 0.05em;
        transition: background-color 0.2s;
    }

    .user-dropdown a:hover {
        background-color: rgba(0, 0, 0, 0.03);
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(5px);
    }

    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 0;
        border: none;
        width: 90%;
        max-width: 400px;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal-header {
        padding: 20px;
        background-color: var(--luxury-black);
        color: white;
        border-radius: 8px 8px 0 0;
        position: relative;
    }

    .modal-close-sign-in,
    .modal-close-forgot-password {
        color: white;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        line-height: 0px;
    }

    .modal-close-sign-in:hover,
    .modal-close-forgot-password:hover {
        opacity: 0.7;
    }

    .modal-body {
        padding: 30px;
    }

    .modal-body h6 {
        text-align: center;
        margin-bottom: 20px;
        font-size: 18px;
        color: #333;
    }

    .form-group {
        margin-bottom: 20px;
        position: relative;
    }

    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        transition: border-color 0.3s;
        box-sizing: border-box;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--luxury-black);
    }

    .btn-login {
        width: 100%;
        padding: 12px;
        background-color: var(--luxury-black);
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 500;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        cursor: pointer;
        transition: opacity 0.3s;
    }

    .btn-login:hover {
        opacity: 0.8;
    }

    .password-toggle {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #666;
    }

    .auth-links {
        display: flex;
        justify-content: space-between;
        margin-top: 15px;
        margin-bottom: 15px;
        font-size: 13px;
    }

    .auth-links a {
        color: #666;
        text-decoration: none;
        transition: color 0.3s;
    }

    .auth-links a:hover {
        color: var(--luxury-black);
    }

    /* ========================================
       AI CHAT BUTTON STYLES
       ======================================== */
    .ai-chat-button {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 70px;
        height: 70px;
        cursor: pointer;
        z-index: 999;
        transition: all 0.3s var(--transition);
        animation: pulse 2s infinite;
    }

    .ai-chat-button:hover {
        transform: scale(1.1);
        animation: none;
    }

    .ai-avatar-container {
        position: relative;
        width: 100%;
        height: 100%;
    }

    .ai-avatar-img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--luxury-black);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        background: white;
        opacity: 0;
        transition: opacity 0.4s ease;
    }

    /* placeholder ตอนยังไม่โหลด */
    .ai-avatar-img.is-loading {
        background: linear-gradient(
            110deg,
            #f2f2f2 25%,
            #e6e6e6 37%,
            #f2f2f2 63%
        );
        background-size: 200% 100%;
        animation: shimmer 1.2s infinite;
    }

    .ai-avatar-img.is-loaded {
        opacity: 1;
        background: none;
        animation: none;
    }

    @keyframes shimmer {
        to {
            background-position-x: -200%;
        }
    }

    .ai-status-dot {
        position: absolute;
        bottom: 5px;
        right: 5px;
        width: 16px;
        height: 16px;
        background: #00ff00;
        border-radius: 50%;
        border: 3px solid white;
        box-shadow: 0 2px 8px rgba(0, 255, 0, 0.4);
    }

    @keyframes pulse {
        0%, 100% {
            box-shadow: 0 0 0 0 rgba(0, 0, 0, 0.4);
        }
        50% {
            box-shadow: 0 0 0 15px rgba(0, 0, 0, 0);
        }
    }

    /* Mobile Language Section - ซ่อนไว้ก่อน */
    .mobile-language-section {
        display: none;
    }

    /* ========================================
       MOBILE RESPONSIVE
       ======================================== */
@media (max-width: 1440px) {
    .header-content {
        padding: 20px 20px;
    }

    /* Show hamburger menu */
    .hamburger {
        display: flex;
        z-index: 1002;
    }

    /* Mobile Navigation - Drop down style */
    .nav {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        width: 100%;
        background: rgba(0, 0, 0, 0.7); 
        flex-direction: column;
        gap: 0;
        padding: 0;
        max-height: 0;
        overflow: hidden;
        opacity: 0;
        transition: max-height 0.4s var(--transition), opacity 0.3s ease;
        z-index: 999;
    }

    .nav.active {
        max-height: 600px;
        opacity: 1;
    }

    .nav-link {
        padding: 18px 30px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        font-size: 14px;
        text-align: left;
        color: #ffffff;
    }

    .nav-link:last-child {
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .nav-link::after {
        display: none;
    }

    .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.66);
    }

    /* Logo stays centered */
    .logo {
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
    }

    /* Adjust actions */
    .header-actions {
        gap: 8px;
    }

    .language-name {
        display: none;
    }

    .language-current {
        padding: 8px;
    }

    /* Remove mobile overlay (not needed for dropdown style) */
    .mobile-overlay {
        display: none !important;
    }

    .modal-content {
        width: 95%;
        margin: 10% auto;
    }

    /* ลดขนาดไอคอนในมือถือ */
    .action-btn {
        font-size: 13px;
    }

    /* AI Chat Button - Mobile */
    .ai-chat-button {
        width: 60px;
        height: 60px;
        bottom: 20px;
        right: 20px;
    }
    
    .ai-status-dot {
        width: 14px;
        height: 14px;
        bottom: 3px;
        right: 3px;
    }

    /* Search Dropdown - Mobile */
    .search-dropdown {
        min-width: 320px;
        max-width: calc(100vw - 40px);
        right: -100px;
    }
}

@media (max-width: 600px) {
    /* ซ่อนปุ่มเปลี่ยนภาษา desktop */
    .language-switcher {
        display: none !important;
    }

    /* แสดง Mobile Language Section ใน dropdown */
    .mobile-language-section {
        display: block !important;
        padding: 15px 30px;
        border-top: 1px solid rgba(255, 255, 255, 0.2);
        background: rgba(0, 0, 0, 0.3);
    }

    .mobile-language-title {
        font-size: 11px;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.6);
        margin-bottom: 12px;
        font-weight: 500;
    }

    .mobile-language-options {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }

    .mobile-lang-option {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        padding: 10px 6px;
        text-decoration: none;
        color: rgba(255, 255, 255, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 8px;
        transition: all 0.3s ease;
        background: rgba(255, 255, 255, 0.05);
    }

    .mobile-lang-option:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.4);
        transform: translateY(-2px);
    }

    .mobile-lang-option.active {
        background: rgba(255, 255, 255, 0.2);
        border-color: rgba(255, 255, 255, 0.6);
        color: #ffffff;
    }

    .mobile-lang-flag {
        width: 22px;
        height: 16px;
        object-fit: cover;
        border-radius: 2px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .mobile-lang-name {
        font-size: 9px;
        letter-spacing: 0.05em;
        text-align: center;
        font-weight: 400;
    }

    /* Search Dropdown - Small Mobile */
    .search-dropdown {
        right: -80px;
    }
}

@media (max-width: 480px) {
    .logo img {
        height: 40px;
    }

    .action-btn {
        font-size: 12px;
    }

    .nav-link {
        padding: 16px 20px;
        font-size: 13px;
    }

    /* AI Chat Button - Small Mobile */
    .ai-chat-button {
        width: 55px;
        height: 55px;
        bottom: 15px;
        right: 15px;
    }
    
    .ai-status-dot {
        width: 12px;
        height: 12px;
        border: 2px solid white;
    }

    /* Search Dropdown - Very Small Mobile */
    .search-dropdown {
        min-width: 280px;
    }
}

/* ========================================
   RESPONSIVE สำหรับหน้าจอขนาด 450px หรือเล็กกว่า
   ======================================== */
@media (max-width: 450px) {
    /* ลดขนาด header actions */
    .header-actions {
        gap: 8px;
    }

    /* ลดขนาดปุ่มที่เหลือ */
    .action-btn {
        width: 28px;
        height: 28px;
        font-size: 11px;
    }

    /* ลดขนาด AI Avatar ใน header */
    #aiAvatar {
        width: 28px;
        height: 28px;
        border: 1.5px solid var(--luxury-black);
    }

    /* ลดขนาด cart badge */
    .cart-badge {
        width: 16px;
        height: 16px;
        font-size: 9px;
        top: -6px;
        right: -6px;
    }

    /* ปรับขนาด logo ให้เล็กลง */
    .logo img {
        height: 35px;
    }

    /* ลด padding ของ header */
    .header-content {
        padding: 15px 15px;
    }

    /* ปรับ hamburger ให้เล็กลง */
    .hamburger span {
        width: 22px;
        height: 2px;
    }
}

/* ========================================
   RESPONSIVE เพิ่มเติมสำหรับหน้าจอที่เล็กมาก (<380px)
   ======================================== */
@media (max-width: 380px) {
    .header-actions {
        gap: 6px;
    }

    .action-btn {
        width: 26px;
        height: 26px;
        font-size: 10px;
    }

    #aiAvatar {
        width: 26px;
        height: 26px;
    }

    .cart-badge {
        width: 14px;
        height: 14px;
        font-size: 8px;
    }

    .logo img {
        height: 32px;
    }

    .header-content {
        padding: 12px 12px;
    }
}
</style>

<!-- HEADER -->
<header class="header">
    <div class="header-content" style="background: #f8f8f861;">
        <!-- Hamburger Menu (Mobile) -->
        <button class="hamburger" id="hamburgerBtn" aria-label="Menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <!-- Logo (Centered) -->
        <a href="?lang=<?= $lang ?>" class="logo">
            <img src="public/product_images/696463601df83_1768186720.png" alt="Perfume Luxury Logo">
        </a>

        <!-- Navigation -->
        <nav class="nav" id="navMenu">
            <?php foreach ($navbarItems as $item): ?>
                <?php
                // สร้าง URL ใหม่โดยให้พารามิเตอร์หน้ามาก่อน lang
                if ($item['link'] === '?') {
                    $linkUrl = '?lang=' . $lang;
                } elseif ($item['link'] === '?product') {
                    $linkUrl = '?product&lang=' . $lang;
                } elseif ($item['link'] === '?news') {
                    $linkUrl = '?news&lang=' . $lang;
                } elseif ($item['link'] === '?about') {
                    $linkUrl = '?about&lang=' . $lang;
                } elseif ($item['link'] === '?contact') {
                    $linkUrl = '?contact&lang=' . $lang;
                } else {
                    $linkUrl = $item['link'] . '&lang=' . $lang;
                }
                ?>
                <a href="<?= $linkUrl ?>" class="nav-link">
                    <?= t($item['key'], $menuTranslations, $lang) ?>
                </a>
            <?php endforeach; ?>
            
            <!-- Mobile Language Section - แสดงเฉพาะใน Mobile -->
            <div class="mobile-language-section">
                <div class="mobile-language-title">
                    <?= t('language', $modalTranslations, $lang) ?>
                </div>
                <div class="mobile-language-options">
                    <?php foreach ($languages as $code => $info): ?>
                        <?php
                        // ตรวจสอบว่าอยู่หน้าไหน
                        $currentPage = '';
                        $queryParams = [];
                        
                        if (isset($_GET['product'])) {
                            $currentPage = '?product';
                        } elseif (isset($_GET['news'])) {
                            $currentPage = '?news';
                        } elseif (isset($_GET['news_detail'])) {
                            $currentPage = '?news_detail';
                            if (isset($_GET['id'])) {
                                $queryParams['id'] = $_GET['id'];
                            }
                        } elseif (isset($_GET['about'])) {
                            $currentPage = '?about';
                        } elseif (isset($_GET['privacy'])) {
                            $currentPage = '?privacy';
                        } elseif (isset($_GET['termofuse'])) {
                            $currentPage = '?termofuse';
                        } elseif (isset($_GET['contact'])) {
                            $currentPage = '?contact';
                        } elseif (isset($_GET['cart'])) {
                            $currentPage = '?cart';
                        } elseif (isset($_GET['register'])) {
                            $currentPage = '?register';
                        } elseif (isset($_GET['profile'])) {
                            $currentPage = '?profile';
                        } elseif (isset($_GET['checkout'])) {
                            $currentPage = '?checkout';
                        } elseif (isset($_GET['payment'])) {
                            $currentPage = '?payment';
                            if (isset($_GET['order_id'])) {
                                $queryParams['order_id'] = $_GET['order_id'];
                            }
                        } elseif (isset($_GET['orders'])) {
                            $currentPage = '?orders';
                        } elseif (isset($_GET['order_detail'])) {
                            $currentPage = '?order_detail';
                            if (isset($_GET['id'])) {
                                $queryParams['id'] = $_GET['id'];
                            }
                        } elseif (isset($_GET['ai_activation'])) {
                            $currentPage = '?ai_activation';
                        } else {
                            $currentPage = '?';
                        }
                        
                        $queryParams['lang'] = $code;
                        $langUrl = $currentPage . '&' . http_build_query($queryParams);
                        ?>
                        <a href="<?= $langUrl ?>"
                           class="mobile-lang-option <?= $code === $lang ? 'active' : '' ?>">
                            <img src="<?= $info['flag'] ?>" 
                                 alt="<?= $info['name'] ?>" 
                                 class="mobile-lang-flag">
                            <span class="mobile-lang-name"><?= $info['name'] ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </nav>

        <div class="header-actions">
            <!-- Language Switcher -->
            <div class="language-switcher" id="languageSwitcher">
                <div class="language-current">
                    <img src="<?= $languages[$lang]['flag'] ?>" alt="<?= $languages[$lang]['name'] ?>" class="language-flag">
                    <span class="language-name"><?= $languages[$lang]['name'] ?></span>
                    <i class="fas fa-chevron-down language-arrow"></i>
                </div>
                <div class="language-dropdown">
                    <?php foreach ($languages as $code => $info): ?>
                        <?php
                        // ตรวจสอบว่าอยู่หน้าไหน
                        $currentPage = '';
                        $queryParams = [];
                        
                        if (isset($_GET['product'])) {
                            $currentPage = '?product';
                        } elseif (isset($_GET['news'])) {
                            $currentPage = '?news';
                        } elseif (isset($_GET['news_detail'])) {
                            $currentPage = '?news_detail';
                            if (isset($_GET['id'])) {
                                $queryParams['id'] = $_GET['id'];
                            }
                        } elseif (isset($_GET['about'])) {
                            $currentPage = '?about';
                        }elseif (isset($_GET['privacy'])) {
                            $currentPage = '?privacy';
                        }elseif (isset($_GET['termofuse'])) {
                            $currentPage = '?termofuse';
                        } elseif (isset($_GET['contact'])) {
                            $currentPage = '?contact';
                        } elseif (isset($_GET['cart'])) {
                            $currentPage = '?cart';
                        } elseif (isset($_GET['register'])) {
                            $currentPage = '?register';
                        } elseif (isset($_GET['profile'])) {
                            $currentPage = '?profile';
                        } elseif (isset($_GET['checkout'])) {
                            $currentPage = '?checkout';
                        } elseif (isset($_GET['payment'])) {
                            $currentPage = '?payment';
                            if (isset($_GET['order_id'])) {
                                $queryParams['order_id'] = $_GET['order_id'];
                            }
                        } elseif (isset($_GET['orders'])) {
                            $currentPage = '?orders';
                        } elseif (isset($_GET['order_detail'])) {
                            $currentPage = '?order_detail';
                            if (isset($_GET['id'])) {
                                $queryParams['id'] = $_GET['id'];
                            }
                        } elseif (isset($_GET['ai_activation'])) {
                            $currentPage = '?ai_activation';
                        } else {
                            $currentPage = '?';
                        }
                        
                        $queryParams['lang'] = $code;
                        $langUrl = $currentPage . '&' . http_build_query($queryParams);
                        ?>
                        <a href="<?= $langUrl ?>"
                        class="language-option <?= $code === $lang ? 'active' : '' ?>">
                            <img src="<?= $info['flag'] ?>" alt="<?= $info['name'] ?>" class="language-flag">
                            <span><?= $info['name'] ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Search Button with Dropdown -->
            <div class="search-container" id="searchContainer">
                <button class="action-btn" id="searchBtn" aria-label="Search">
                    <i class="fas fa-search"></i>
                </button>
                
                <!-- Search Dropdown -->
                <div class="search-dropdown" id="searchDropdown">
                    <div class="search-input-wrapper">
                        <input 
                            type="text" 
                            id="globalSearchInput" 
                            class="search-dropdown-input" 
                            placeholder="<?php echo match($lang) {
                                'en' => 'Search news & products...',
                                'cn' => '搜索新闻和产品...',
                                'jp' => 'ニュースと製品を検索...',
                                'kr' => '뉴스 및 제품 검색...',
                                default => 'ค้นหาข่าวและสินค้า...',
                            }; ?>"
                            autocomplete="off"
                        >
                        <i class="fas fa-search search-icon"></i>
                    </div>
                    
                    <div class="search-results" id="searchResults">
                        <div class="search-loading" id="searchLoading" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i> 
                            <?php echo match($lang) {
                                'en' => 'Searching...',
                                'cn' => '搜索中...',
                                'jp' => '検索中...',
                                'kr' => '검색 중...',
                                default => 'กำลังค้นหา...',
                            }; ?>
                        </div>
                        
                        <div class="search-empty" id="searchEmpty" style="display: none;">
                            <?php echo match($lang) {
                                'en' => 'No results found',
                                'cn' => '未找到结果',
                                'jp' => '結果が見つかりません',
                                'kr' => '결과를 찾을 수 없습니다',
                                default => 'ไม่พบผลลัพธ์',
                            }; ?>
                        </div>
                        
                        <div id="newsResults"></div>
                        <div id="productResults"></div>
                    </div>
                </div>
            </div>
            
            <!-- User Menu with AI Avatar -->
            <div class="user-menu" id="userMenu">
                <button class="action-btn" id="userBtn"aria-label="User">
                    <i class="fas fa-user" id="userIcon"></i>
                    <img id="aiAvatar" src="" alt="AI Avatar">
                </button>
                <div class="user-dropdown" id="userDropdown">
                    <a href="?profile&lang=<?= $lang ?>" id="profileLink" style="display:none;">My Profile</a>
                    <a href="app/admin/index.php" id="adminLink" style="display:none;">Admin Panel</a>
                    <a href="#" id="logoutLink" style="display:none;">Logout</a>
                </div>
            </div>
            
            <!-- Cart Button -->
            <button class="action-btn" id="cartBtn" aria-label="Cart">
                <i class="fas fa-shopping-bag"></i>
                <span class="cart-badge" id="cartBadge" style="display:none;">0</span>
            </button>
        </div>
    </div>
</header>

<!-- Mobile Overlay -->
<div class="mobile-overlay" id="mobileOverlay"></div>

<!-- AI Chat Button (Floating) -->
<div id="aiChatButton" class="ai-chat-button" style="border-radius: 50%;" style="display: none;">
    <div class="ai-avatar-container">
        <img id="aiChatAvatar"
            src=""
            alt="AI Companion"
            class="ai-avatar-img is-loading">
        <div class="ai-status-dot"></div>
    </div>
</div>

<!-- Login Modal -->
<div id="myModal-sign-in" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="modal-close-sign-in">×</span>
        </div>
        <div class="modal-body">
            <h6>
                <i class="fas fa-unlock"></i> <span id="modal-title"><?= t('please_login', $modalTranslations, $lang) ?></span>
            </h6>
            <hr style="margin: 20px 0;">
            <form id="loginModal">
                <div class="form-group">
                    <input id="username" type="text" class="form-control" 
                           placeholder="<?= t('email_phone', $modalTranslations, $lang) ?>" required>
                </div>
                <div class="form-group">
                    <input id="password" type="password" class="form-control" 
                           placeholder="<?= t('password', $modalTranslations, $lang) ?>" required>
                    <span class="password-toggle" id="togglePasswordSignin">
                        <i class="fas fa-eye-slash"></i>
                    </span>
                </div>
                <div class="auth-links">
                    <a href="?register&lang=<?= $lang ?>" id="signup-link"><?= t('sign_up', $modalTranslations, $lang) ?></a>
                    <a href="#" id="myBtn-forgot-password"><?= t('forgot_password', $modalTranslations, $lang) ?></a>
                </div>
                <button type="submit" class="btn-login" id="login-btn"><?= t('login', $modalTranslations, $lang) ?></button>
            </form>
        </div>
    </div>
</div>

<!-- Forgot Password Modal -->
<div id="myModal-forgot-password" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="modal-close-forgot-password">×</span>
        </div>
        <div class="modal-body">
            <h6>
                <i class="fas fa-key"></i> <span id="forgot-title"><?= t('forgot_password_title', $modalTranslations, $lang) ?></span>
            </h6>
            <hr style="margin: 20px 0;">
            <form id="forgotModal">
                <div class="form-group">
                    <input id="forgot_email" name="forgot_email" type="email" class="form-control" 
                           placeholder="<?= t('enter_email', $modalTranslations, $lang) ?>" required>
                </div>
                <button type="button" id="submitForgot" class="btn-login"><?= t('send_email', $modalTranslations, $lang) ?></button>
            </form>
        </div>
    </div>
</div>

<!-- Pass current language to JavaScript -->
<script>
    const currentLang = '<?= $lang ?>';
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);

    if (params.get('login') === '1') {
        const modal = document.getElementById('myModal-sign-in');
        if (modal) {
            modal.style.display = 'block';
        }
    }
});

// ========================================
// GLOBAL SEARCH FUNCTIONALITY
// ========================================
let searchTimeout = null;
const searchContainer = document.getElementById('searchContainer');
const searchBtn = document.getElementById('searchBtn');
const searchInput = document.getElementById('globalSearchInput');
const searchResults = document.getElementById('searchResults');
const searchLoading = document.getElementById('searchLoading');
const searchEmpty = document.getElementById('searchEmpty');
const newsResults = document.getElementById('newsResults');
const productResults = document.getElementById('productResults');

// Toggle search dropdown
searchBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    searchContainer.classList.toggle('active');
    if (searchContainer.classList.contains('active')) {
        setTimeout(() => searchInput.focus(), 100);
    }
});

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (!searchContainer.contains(e.target)) {
        searchContainer.classList.remove('active');
    }
});

// Search input handler
searchInput.addEventListener('input', function() {
    const query = this.value.trim();
    
    clearTimeout(searchTimeout);
    
    if (query.length < 2) {
        newsResults.innerHTML = '';
        productResults.innerHTML = '';
        searchLoading.style.display = 'none';
        searchEmpty.style.display = 'none';
        return;
    }
    
    searchLoading.style.display = 'block';
    searchEmpty.style.display = 'none';
    newsResults.innerHTML = '';
    productResults.innerHTML = '';
    
    searchTimeout = setTimeout(() => {
        performSearch(query);
    }, 300);
});

function performSearch(query) {
    $.ajax({
        url: 'app/actions/global_search.php',
        type: 'GET',
        data: {
            q: query,
            lang: currentLang
        },
        dataType: 'json',
        success: function(response) {
            searchLoading.style.display = 'none';
            
            if (response.status === 'success') {
                const hasResults = response.news.length > 0 || response.products.length > 0;
                
                if (!hasResults) {
                    searchEmpty.style.display = 'block';
                    return;
                }
                
                // Display news results
                if (response.news.length > 0) {
                    displayNewsResults(response.news, query);
                }
                
                // Display product results
                if (response.products.length > 0) {
                    displayProductResults(response.products, query);
                }
            } else {
                searchEmpty.style.display = 'block';
            }
        },
        error: function() {
            searchLoading.style.display = 'none';
            searchEmpty.style.display = 'block';
        }
    });
}

function displayNewsResults(news, query) {
    const translations = {
        'th': 'ข่าวสาร',
        'en': 'News',
        'cn': '新闻',
        'jp': 'ニュース',
        'kr': '뉴스'
    };
    
    const viewAllText = {
        'th': 'ดูข่าวทั้งหมดเกี่ยวกับ "' + query + '"',
        'en': 'View all news about "' + query + '"',
        'cn': '查看所有关于 "' + query + '" 的新闻',
        'jp': '"' + query + '" に関するすべてのニュースを表示',
        'kr': '"' + query + '"에 대한 모든 뉴스 보기'
    };
    
    let html = '<div class="search-section-title">' + translations[currentLang] + '</div>';
    
    const displayCount = Math.min(news.length, 3);
    for (let i = 0; i < displayCount; i++) {
        const item = news[i];
        const encodedId = encodeURIComponent(btoa(item.news_id));
        const link = '?news_detail&id=' + encodedId + '&lang=' + currentLang;
        
        html += '<a href="' + link + '" class="search-item">';
        if (item.image) {
            html += '<img src="' + item.image + '" alt="' + item.title + '" class="search-item-image">';
        } else {
            html += '<div class="search-item-image" style="background: #f0f0f0;"></div>';
        }
        html += '<div class="search-item-content">';
        html += '<div class="search-item-title">' + item.title + '</div>';
        if (item.description) {
            html += '<div class="search-item-desc">' + item.description + '</div>';
        }
        html += '</div></a>';
    }
    
    html += '<a href="?news&s=' + encodeURIComponent(query) + '&lang=' + currentLang + '" class="search-view-all">' + 
            viewAllText[currentLang] + '</a>';
    
    newsResults.innerHTML = html;
}

function displayProductResults(products, query) {
    const translations = {
        'th': 'สินค้า',
        'en': 'Products',
        'cn': '产品',
        'jp': '製品',
        'kr': '제품'
    };
    
    const viewAllText = {
        'th': 'ดูสินค้าทั้งหมดเกี่ยวกับ "' + query + '"',
        'en': 'View all products about "' + query + '"',
        'cn': '查看所有关于 "' + query + '" 的产品',
        'jp': '"' + query + '" に関するすべての製品を表示',
        'kr': '"' + query + '"에 대한 모든 제품 보기'
    };
    
    let html = '<div class="search-section-title">' + translations[currentLang] + '</div>';
    
    const displayCount = Math.min(products.length, 3);
    for (let i = 0; i < displayCount; i++) {
        const item = products[i];
        const encodedId = encodeURIComponent(btoa(item.product_id));
        const link = '?product_detail&id=' + encodedId + '&lang=' + currentLang;
        
        html += '<a href="' + link + '" class="search-item">';
        if (item.image) {
            html += '<img src="' + item.image + '" alt="' + item.name + '" class="search-item-image">';
        } else {
            html += '<div class="search-item-image" style="background: #f0f0f0;"></div>';
        }
        html += '<div class="search-item-content">';
        html += '<div class="search-item-title">' + item.name + '</div>';
        if (item.description) {
            html += '<div class="search-item-desc">' + item.description + '</div>';
        }
        html += '<div class="search-item-price">฿' + parseFloat(item.price).toLocaleString('en-US', {minimumFractionDigits: 2}) + '</div>';
        html += '</div></a>';
    }
    
    html += '<a href="?product&s=' + encodeURIComponent(query) + '&lang=' + currentLang + '" class="search-view-all">' + 
            viewAllText[currentLang] + '</a>';
    
    productResults.innerHTML = html;
}

// Hamburger Menu Toggle - Drop down style
const hamburgerBtn = document.getElementById('hamburgerBtn');
const navMenu = document.getElementById('navMenu');
const mobileOverlay = document.getElementById('mobileOverlay');

if (hamburgerBtn && navMenu) {
    hamburgerBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        hamburgerBtn.classList.toggle('active');
        navMenu.classList.toggle('active');
    });

    // Close menu when clicking nav link
    const navLinks = navMenu.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            hamburgerBtn.classList.remove('active');
            navMenu.classList.remove('active');
        });
    });

    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!hamburgerBtn.contains(e.target) && !navMenu.contains(e.target)) {
            hamburgerBtn.classList.remove('active');
            navMenu.classList.remove('active');
        }
    });
}

// Function to load AI Avatar
function loadAIAvatar() {
    const jwt = sessionStorage.getItem('jwt');
    
    if (!jwt) {
        // ไม่มี token แสดงไอคอน user ปกติ
        $('#aiAvatar').hide();
        $('#userIcon').show();
        return;
    }
    
    $.ajax({
        url: 'app/actions/get_user_ai_avatar.php',
        type: 'GET',
        headers: {
            'Authorization': 'Bearer ' + jwt,
            'X-Auth-Token': jwt
        },
        dataType: 'json',
        success: function(response) {
            console.log('AI Avatar response:', response);
            
            if (response.status === 'success' && response.has_ai) {
                // แสดงรูป AI avatar แทนไอคอน
                $('#userIcon').hide();
                $('#aiAvatar').attr('src', response.ai_avatar_url).show();
                
                // เพิ่ม tooltip หรือ title ถ้ามีชื่อ AI
                if (response.ai_name_th || response.ai_name_en) {
                    const aiName = currentLang === 'th' ? response.ai_name_th : response.ai_name_en;
                    $('#aiAvatar').attr('title', aiName);
                }
            } else {
                // แสดงไอคอน user ปกติ
                $('#aiAvatar').hide();
                $('#userIcon').show();
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load AI avatar:', status, error);
            // กรณี error ให้แสดงไอคอน user ปกติ
            $('#aiAvatar').hide();
            $('#userIcon').show();
        }
    });
}

// Function to check AI companion status (ไม่ redirect อัตโนมัติ)
function checkAICompanionStatus() {
    const jwt = sessionStorage.getItem('jwt');
    
    if (!jwt) {
        $('#aiChatButton').hide();
        return;
    }
    
    $.ajax({
        url: 'app/actions/check_ai_companion_status.php',
        type: 'GET',
        headers: {
            'Authorization': 'Bearer ' + jwt,
            'X-Auth-Token': jwt
        },
        dataType: 'json',
        success: function(response) {
            console.log('AI Companion Status:', response);
            
            if (response.status === 'success') {
                if (response.has_companion) {
                    // มี AI companion - แสดงปุ่ม chat
                    // เก็บสถานะไว้ใน data attribute
                    loadAIChatButton(response);
                } else {
                    // ยังไม่มี AI companion - ซ่อนปุ่ม
                    $('#aiChatButton').hide();
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to check AI companion status:', status, error);
            $('#aiChatButton').hide();
        }
    });
}

// Function to load AI Chat Button
function loadAIChatButton(companionStatus) {
    const jwt = sessionStorage.getItem('jwt');
    
    if (!jwt) {
        $('#aiChatButton').hide();
        return;
    }
    
    $.ajax({
        url: 'app/actions/get_user_ai_avatar.php',
        type: 'GET',
        headers: {
            'Authorization': 'Bearer ' + jwt,
            'X-Auth-Token': jwt
        },
        dataType: 'json',
        success: function(response) {
            console.log('AI Chat Button response:', response);
            
            if (response.status === 'success' && response.has_ai) {
                // แสดงปุ่ม AI Chat
                const img = document.getElementById('aiChatAvatar');
                img.classList.add('is-loading');
                img.style.display = 'block';

                img.onload = () => {
                    img.classList.remove('is-loading');
                    img.classList.add('is-loaded');
                };

                img.src = response.ai_avatar_url;
                $('#aiChatButton').fadeIn();
                
                // เก็บสถานะ setup ไว้ใน data attribute
                $('#aiChatButton').data('setup-completed', companionStatus.setup_completed);
                $('#aiChatButton').data('companion-id', companionStatus.data.user_companion_id);
                $('#aiChatButton').data('preferred-lang', companionStatus.data.preferred_language || 'th');
                
                // เพิ่ม click event ตามสถานะ
                $('#aiChatButton').off('click').on('click', function() {
                    const setupCompleted = $(this).data('setup-completed');
                    const companionId = $(this).data('companion-id');
                    const lang = $(this).data('preferred-lang');
                    
                    if (setupCompleted) {
                        // ถ้า setup เสร็จแล้ว -> ไป ai_chat
                        window.location.href = '?ai_chat_3d&lang=' + currentLang;
                    } else {
                        // ถ้ายัง setup ไม่เสร็จ -> ไป ai_questions
                        window.location.href = '?ai_questions&companion_id=' + companionId + '&lang=' + lang;
                    }
                });
            } else {
                // ซ่อนปุ่มถ้าไม่มี AI
                $('#aiChatButton').hide();
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load AI chat button:', status, error);
            $('#aiChatButton').hide();
        }
    });
}

// Function to load cart count
function loadCartCount() {
    const jwt = sessionStorage.getItem('jwt');
    
    const headers = {
        'Content-Type': 'application/json'
    };
    
    if (jwt) {
        headers['Authorization'] = 'Bearer ' + jwt;
        headers['X-Auth-Token'] = jwt;
    }
    
    $.ajax({
        url: 'app/actions/cart_count.php',
        type: 'GET',
        headers: headers,
        dataType: 'json',
        success: function(response) {
            console.log('Cart count response:', response);
            
            if (response.status === 'success') {
                if (response.count > 0) {
                    $('#cartBadge').text(response.count).show();
                } else {
                    $('#cartBadge').hide();
                }
            } else {
                $('#cartBadge').hide();
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load cart count:', status, error);
            console.log('Response:', xhr.responseText);
        }
    });
}

window.updateCartCount = loadCartCount;

document.addEventListener('DOMContentLoaded', function() {
    loadCartCount();
    loadAIAvatar();
    checkAICompanionStatus(); // เพิ่มการเช็คสถานะ AI companion

    // Language Switcher
    const languageSwitcher = document.getElementById('languageSwitcher');
    if (languageSwitcher) {
        languageSwitcher.addEventListener('click', function(e) {
            if (e.target.closest('.language-option')) return;
            this.classList.toggle('active');
        });

        document.addEventListener('click', function(e) {
            if (!languageSwitcher.contains(e.target)) {
                languageSwitcher.classList.remove('active');
            }
        });
    }

    // Check if user is logged in
    const jwt = sessionStorage.getItem("jwt");
    const userBtn = document.getElementById('userBtn');
    const profileLink = document.getElementById('profileLink');
    const adminLink = document.getElementById('adminLink');
    const logoutLink = document.getElementById('logoutLink');

    if (jwt) {
        fetch('app/actions/protected.php', {
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + jwt
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                const roleId = parseInt(data.data.role_id);
                profileLink.style.display = 'block';
                if (roleId === 1 || roleId === 2) {
                    adminLink.style.display = 'block';
                }
                logoutLink.style.display = 'block';
                loadCartCount();
                loadAIAvatar();
                checkAICompanionStatus(); // เช็คสถานะ AI companion หลัง verify token
            }
        })
        .catch(error => console.error("Token verification failed:", error));
    } else {
        userBtn.addEventListener('click', function() {
            document.getElementById('myModal-sign-in').style.display = 'block';
        });
    }

    // Logout functionality
    if (logoutLink) {
        logoutLink.addEventListener('click', function(e) {
            e.preventDefault();
            sessionStorage.removeItem("jwt");
            location.reload();
        });
    }

    // Cart button click
    document.getElementById('cartBtn').addEventListener('click', function() {
        window.location.href = '?cart&lang=' + currentLang;
    });

    // Modal controls
    const modalSignin = document.getElementById('myModal-sign-in');
    const modalForgot = document.getElementById('myModal-forgot-password');
    const signinModalCloseBtn = document.querySelector('.modal-close-sign-in');
    const forgotModalBtn = document.getElementById('myBtn-forgot-password');
    const forgotModalCloseBtn = document.querySelector('.modal-close-forgot-password');

    if (signinModalCloseBtn) {
        signinModalCloseBtn.addEventListener('click', function() {
            modalSignin.style.display = 'none';
        });
    }

    if (forgotModalBtn) {
        forgotModalBtn.addEventListener('click', function(e) {
            e.preventDefault();
            modalSignin.style.display = 'none';
            modalForgot.style.display = 'block';
        });
    }

    if (forgotModalCloseBtn) {
        forgotModalCloseBtn.addEventListener('click', function() {
            modalForgot.style.display = 'none';
        });
    }

    window.addEventListener('click', function(event) {
        if (event.target == modalSignin) {
            modalSignin.style.display = 'none';
        }
        if (event.target == modalForgot) {
            modalForgot.style.display = 'none';
        }
    });

    // Password toggle
    const togglePasswordSignin = document.getElementById('togglePasswordSignin');
    if (togglePasswordSignin) {
        togglePasswordSignin.addEventListener('click', function() {
            const password = document.getElementById('password');
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    }

    // Login form submission
    $('#loginModal').on('submit', function(event) {
        event.preventDefault();

        const username = $('#username').val().trim();
        const password = $('#password').val().trim();

        if (!username || !password) {
            alert('Please enter both email and password');
            return;
        }

        $.ajax({
            url: 'app/actions/check_login.php',
            type: 'POST',
            data: {
                username: username,
                password: password
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === "success") {
                    sessionStorage.setItem('jwt', response.jwt);
                    const token = sessionStorage.getItem('jwt');
                    
                    $.ajax({
                        url: 'app/actions/protected.php',
                        type: 'GET',
                        headers: {
                            'Authorization': 'Bearer ' + token
                        },
                        success: function(response) {
                            if (response.status === "success") {
                                const roleId = parseInt(response.data.role_id);

                                loadCartCount();
                                loadAIAvatar();
                                checkAICompanionStatus(); // เช็คสถานะ AI companion หลัง login

                                if (roleId === 1) {
                                    window.location.href = 'app/admin/index.php';
                                } else if (roleId === 2) {
                                    window.location.href = 'app/editer/index.php';
                                } else {
                                    location.reload();
                                }
                            } else {
                                alert(response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("Request failed:", status, error);
                            alert("An error occurred while accessing protected resource.");
                        }
                    });
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX request failed:", status, error);
                alert("An error occurred. Please try again.");
            }
        });
    });
});
</script>