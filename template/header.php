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
    ['link' => '?contact', 'key' => 'contact'],
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
    'en' => ['name' => 'English', 'flag' => 'https://flagcdn.com/us.svg'],
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
<link rel="icon" type="image/x-icon" href="/origami_website/perfume//public/product_images/696089dc2eba5_1767934428.jpg">
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
        background: rgba(255, 255, 255, 0.95);
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
        color: var(--luxury-black);
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
    }

    .action-btn:hover {
        opacity: 0.6;
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
       MOBILE RESPONSIVE
       ======================================== */
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
        background: white;
        flex-direction: column;
        gap: 0;
        padding: 0;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        max-height: 0;
        overflow: hidden;
        opacity: 0;
        transition: max-height 0.4s var(--transition), opacity 0.3s ease;
        z-index: 999;
    }

    .nav.active {
        max-height: 400px;
        opacity: 1;
    }

    .nav-link {
        padding: 18px 30px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        font-size: 14px;
        text-align: left;
    }

    .nav-link:last-child {
        border-bottom: none;
    }

    .nav-link::after {
        display: none;
    }

    .nav-link:hover {
        background-color: rgba(0, 0, 0, 0.02);
    }

    /* Logo stays centered */
    .logo {
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
    }

    /* Adjust actions */
    .header-actions {
        gap: 15px;
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
}
</style>

<!-- HEADER -->
<header class="header">
    <div class="header-content">
        <!-- Hamburger Menu (Mobile) -->
        <button class="hamburger" id="hamburgerBtn" aria-label="Menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <!-- Logo (Centered) -->
        <a href="?lang=<?= $lang ?>" class="logo">
            <img src="/origami_website/perfume//public/product_images/696463601df83_1768186720.png" alt="Perfume Luxury Logo">
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
                        } elseif (isset($_GET['about'])) {
                            $currentPage = '?about';
                        } elseif (isset($_GET['contact'])) {
                            $currentPage = '?contact';
                        } elseif (isset($_GET['cart'])) {
                            $currentPage = '?cart';
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

            <button class="action-btn" aria-label="Search">
                <i class="fas fa-search"></i>
            </button>
            
            <!-- User Menu -->
            <div class="user-menu" id="userMenu">
                <button class="action-btn" id="userBtn" aria-label="User">
                    <i class="fas fa-user"></i>
                </button>
                <div class="user-dropdown" id="userDropdown">
                    <a href="?profile" id="profileLink" style="display:none;">My Profile</a>
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

    if (params.get('') === '1') {
        const modal = document.getElementById('myModal-sign-in');
        if (modal) {
            modal.style.display = 'block';
        }
    }
});

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