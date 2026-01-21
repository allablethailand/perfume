<?php
require_once('lib/connect.php');
global $conn;

// Start session for language handling
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Language handling
$lang = 'th';
if (isset($_GET['lang'])) {
    $supportedLangs = ['th', 'en', 'cn', 'jp', 'kr'];
    if (in_array($_GET['lang'], $supportedLangs)) {
        $_SESSION['lang'] = $_GET['lang'];
        $lang = $_GET['lang'];
    }
} elseif (isset($_SESSION['lang'])) {
    $lang = $_SESSION['lang'];
}

// Translation arrays
$translations = [
    'news_center' => [
        'th' => 'ศูนย์ข่าวสาร',
        'en' => 'News Center',
        'cn' => '新闻中心',
        'jp' => 'ニュースセンター',
        'kr' => '뉴스 센터'
    ],
    'latest_updates' => [
        'th' => 'ข่าวสารและอัพเดทล่าสุด',
        'en' => 'Latest News & Updates',
        'cn' => '最新新闻与更新',
        'jp' => '最新ニュースとアップデート',
        'kr' => '최신 뉴스 및 업데이트'
    ],
    'hero_desc' => [
        'th' => 'ติดตามข่าวสาร นวัตกรรม และข้อมูลเชิงลึกล่าสุดจากโลกของน้ำหอมที่ขับเคลื่อนด้วย AI',
        'en' => 'Stay updated with the latest news, innovations, and insights from our world of AI-powered fragrances.',
        'cn' => '了解我们 AI 驱动香水世界的最新新闻、创新和见解。',
        'jp' => 'AI を活用した香水の世界からの最新ニュース、イノベーション、インサイトをご覧ください。',
        'kr' => 'AI 기반 향수 세계의 최신 뉴스, 혁신 및 통찰력을 확인하세요.'
    ],
    'search_placeholder' => [
        'th' => 'ค้นหาข่าว...',
        'en' => 'Search news...',
        'cn' => '搜索新闻...',
        'jp' => 'ニュースを検索...',
        'kr' => '뉴스 검색...'
    ]
];

function tt($key, $lang) {
    global $translations;
    return $translations[$key][$lang] ?? $translations[$key]['en'];
}

$subject_col = ($lang === 'th') ? 'subject_news' : 'subject_news_' . $lang;

$breaking_query = "
    SELECT 
        news_id,
        {$subject_col} as subject
    FROM dn_news
    WHERE status = 0 AND del = 0
    ORDER BY date_create DESC
    LIMIT 5
";
$breaking_result = $conn->query($breaking_query);
$breaking_news = [];
if ($breaking_result && $breaking_result->num_rows > 0) {
    while ($row = $breaking_result->fetch_assoc()) {
        $breaking_news[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <?php include 'template/header.php' ?>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #ffffff;
            color: #1a1a1a;
            line-height: 1.6;
        }

        /* Hero Section */
        .news-hero {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 140px 0 100px;
            position: relative;
            overflow: hidden;
        }

        .news-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="100" height="100" patternUnits="userSpaceOnUse"><path d="M 100 0 L 0 0 0 100" fill="none" stroke="rgba(255,255,255,0.03)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.5;
        }

        .news-hero-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
            position: relative;
            z-index: 1;
        }

        .news-hero-subtitle {
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #ffa719;
            margin-bottom: 20px;
            display: inline-block;
            padding: 8px 24px;
            background: rgba(255, 167, 25, 0.1);
            border-radius: 30px;
        }

        .news-hero-title {
            font-size: 64px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 20px;
            letter-spacing: -2px;
        }

        .news-hero-description {
            font-size: 20px;
            color: rgba(255, 255, 255, 0.7);
            max-width: 700px;
            line-height: 1.7;
        }

        /* Search & Filter Bar */
        .search-filter-bar {
            background: #ffffff;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.08);
            padding: 30px 40px;
            border-radius: 16px;
            margin: -50px auto 0;
            max-width: 1400px;
            position: relative;
            z-index: 10;
        }

        .search-wrapper {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .search-input-group {
            flex: 1;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 18px 60px 18px 24px;
            border: 2px solid #e5e5e5;
            border-radius: 50px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #ffa719;
            box-shadow: 0 0 0 4px rgba(255, 167, 25, 0.1);
        }

        .search-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: #ffa719;
            border: none;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }

        .search-btn:hover {
            background: #ff9500;
            transform: translateY(-50%) scale(1.05);
        }

        /* Newsletter Section */
        .newsletter-section {
            background: #000000;
            padding: 80px 40px;
            margin-top: 60px;
        }

        .newsletter-container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: center;
        }

        .newsletter-content {
            color: white;
        }

        .newsletter-icon {
            margin-bottom: 30px;
        }

        .newsletter-title {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 20px;
            letter-spacing: -1px;
        }

        .newsletter-description {
            font-size: 18px;
            line-height: 1.7;
            color: rgba(255, 255, 255, 0.7);
        }

        .newsletter-stats {
            display: flex;
            gap: 40px;
            justify-content: space-around;
            background: rgba(255, 255, 255, 0.05);
            padding: 40px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 48px;
            font-weight: 700;
            color: #ffa719;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-divider {
            width: 1px;
            background: rgba(255, 255, 255, 0.1);
        }

        /* Breaking News Ticker */
        .breaking-news {
            background: #fff;
            border-top: 1px solid #e5e5e5;
            border-bottom: 1px solid #e5e5e5;
            padding: 20px 0;
            /* margin: 80px 0 60px; */
            overflow: hidden;
        }

        .breaking-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .breaking-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 1px;
            color: #ff0000;
            white-space: nowrap;
            padding-left: 40px;
        }

        .breaking-content {
            flex: 1;
            overflow: hidden;
        }

        .breaking-track {
            display: flex;
            gap: 40px;
            animation: scroll 30s linear infinite;
            white-space: nowrap;
        }

        .breaking-track span {
            font-size: 15px;
            color: #1a1a1a;
        }

        .breaking-divider {
            color: #ffa719;
            font-weight: 700;
        }

        @keyframes scroll {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(-50%);
            }
        }

        /* Content Wrapper */
        .content-sticky {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px 0px;
        }

        /* News Grid */
        .content-news {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
            margin: 60px 0;
        }

        .box-news {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.06);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .box-news:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
        }

        .box-image {
            position: relative;
            height: 280px;
            overflow: hidden;
            background: #f5f5f5;
        }

        .box-image img,
        .box-image iframe,
        .box-image picture img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .box-news:hover .box-image img,
        .box-news:hover .box-image picture img {
            transform: scale(1.08);
        }

        .box-content {
            padding: 30px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .text-news {
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .box-content h5 {
            font-size: 22px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 15px;
            line-height: 1.4;
            transition: color 0.3s ease;
        }

        .box-news:hover .box-content h5 {
            color: #ffa719;
        }

        .box-content p {
            font-size: 15px;
            color: #666;
            line-height: 1.7;
            margin-bottom: 0;
            flex: 1;
        }

        .line-clamp {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .box-content h5.line-clamp {
            -webkit-line-clamp: 2;
        }

        /* Premium Features Section */
        .premium-features {
            background: #f8f8f8;
            padding: 100px 40px;
            /* margin: 80px 0; */
        }

        .features-container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 50px;
        }

        .feature-card {
            text-align: center;
            padding: 40px 30px;
            background: white;
            border-radius: 20px;
            transition: all 0.4s ease;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
        }

        .feature-icon {
            margin-bottom: 30px;
        }

        .feature-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 15px;
            color: #1a1a1a;
        }

        .feature-description {
            font-size: 16px;
            color: #666;
            line-height: 1.7;
        }

        /* Social Proof Section */
        .social-proof {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 100px 40px;
            position: relative;
            overflow: hidden;
        }

        .social-proof::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="dots" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23dots)"/></svg>');
        }

        .social-container {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .social-content {
            text-align: center;
            margin-bottom: 60px;
        }

        .social-title {
            font-size: 52px;
            font-weight: 700;
            color: white;
            margin-bottom: 20px;
            letter-spacing: -1px;
        }

        .social-description {
            font-size: 20px;
            color: rgba(255, 255, 255, 0.7);
        }

        .social-stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 50px;
        }

        .social-stat {
            text-align: center;
            padding: 40px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .social-number {
            font-size: 56px;
            font-weight: 700;
            color: #ffa719;
            margin-bottom: 15px;
        }

        .social-label {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.7);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 60px;
            flex-wrap: wrap;
        }

        .pagination a {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 48px;
            height: 48px;
            padding: 0 20px;
            background: white;
            color: #1a1a1a;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 2px solid transparent;
        }

        .pagination a:hover {
            background: #ffa719;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(255, 167, 25, 0.3);
        }

        .pagination a.active {
            background: #ffa719;
            color: white;
            border-color: #ffa719;
        }

        .pagination a[disabled] {
            opacity: 0.4;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .news-hero-title {
                font-size: 52px;
            }

            .newsletter-container {
                grid-template-columns: 1fr;
                gap: 50px;
            }

            .content-news {
                grid-template-columns: repeat(2, 1fr);
            }

            .features-container {
                grid-template-columns: 1fr;
                gap: 30px;
            }
        }

        @media (max-width: 768px) {
            .news-hero {
                padding: 100px 0 80px;
            }

            .news-hero-content {
                padding: 0 20px;
            }

            .news-hero-title {
                font-size: 36px;
            }

            .news-hero-description {
                font-size: 16px;
            }

            .search-filter-bar {
                padding: 20px;
                margin: -30px 20px 0;
            }

            .newsletter-section {
                padding: 60px 20px;
            }

            .newsletter-title {
                font-size: 32px;
            }

            .newsletter-stats {
                flex-direction: column;
                gap: 30px;
            }

            .stat-divider {
                display: none;
            }

            .breaking-label {
                padding-left: 20px;
            }

            .content-sticky {
                padding: 0 20px 80px;
            }

            .content-news {
                grid-template-columns: 1fr;
                gap: 30px;
                margin: 40px 0;
            }

            .box-image {
                height: 240px;
            }

            .box-content {
                padding: 24px;
            }

            .box-content h5 {
                font-size: 20px;
            }

            .premium-features {
                padding: 60px 20px;
            }

            .social-proof {
                padding: 60px 20px;
            }

            .social-title {
                font-size: 36px;
            }

            .social-stats-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }

        /* Loading Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .box-news {
            animation: fadeIn 0.6s ease forwards;
            opacity: 0;
        }

        .box-news:nth-child(1) { animation-delay: 0.1s; }
        .box-news:nth-child(2) { animation-delay: 0.2s; }
        .box-news:nth-child(3) { animation-delay: 0.3s; }
        .box-news:nth-child(4) { animation-delay: 0.4s; }
        .box-news:nth-child(5) { animation-delay: 0.5s; }
        .box-news:nth-child(6) { animation-delay: 0.6s; }
        .box-news:nth-child(7) { animation-delay: 0.7s; }
        .box-news:nth-child(8) { animation-delay: 0.8s; }
        .box-news:nth-child(9) { animation-delay: 0.9s; }
    </style>
</head>
<body>

    <?php include 'template/banner_slide.php' ?>

    <!-- Hero Section -->
    <section class="news-hero">
        <div class="news-hero-content">
            <span class="news-hero-subtitle"><?= tt('news_center', $lang) ?></span>
            <h1 class="news-hero-title"><?= tt('latest_updates', $lang) ?></h1>
            <p class="news-hero-description">
                <?= tt('hero_desc', $lang) ?>
            </p>
        </div>
    </section>

    <!-- Search & Filter Bar -->
    <div class="search-filter-bar">
        <form method="GET" action="" class="search-wrapper">
            <div class="search-input-group">
                <input 
                    type="text" 
                    name="search" 
                    class="search-input" 
                    placeholder="<?= tt('search_placeholder', $lang) ?>"
                    value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                >
                <button type="submit" class="search-btn">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 17C13.4183 17 17 13.4183 17 9C17 4.58172 13.4183 1 9 1C4.58172 1 1 4.58172 1 9C1 13.4183 4.58172 17 9 17Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M19 19L14.65 14.65" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
            <input type="hidden" name="news" value="">
            <input type="hidden" name="lang" value="<?= $lang ?>">
        </form>
    </div>

    <!-- Newsletter Subscription Section -->
    <section class="newsletter-section">
        <div class="newsletter-container">
            <div class="newsletter-content">
                <div class="newsletter-icon">
                    <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 15L30 30L50 15" stroke="#ffa719" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <rect x="5" y="10" width="50" height="35" rx="3" stroke="#ffa719" stroke-width="2"/>
                        <path d="M5 15L30 32L55 15" stroke="#ffa719" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h2 class="newsletter-title">
                    <?php 
                    echo match($lang) {
                        'en' => 'Be the First to Know',
                        'cn' => '率先了解最新动态',
                        'jp' => '最新情報をいち早くキャッチ',
                        'kr' => '가장 먼저 소식 받기',
                        default => 'รับข่าวสารก่อนใคร',
                    };
                    ?>
                </h2>
                <p class="newsletter-description">
                    <?php 
                    echo match($lang) {
                        'en' => 'Exclusive news, limited editions, and special events delivered to you first',
                        'cn' => '独家新闻、限量版和特别活动第一时间送达',
                        'jp' => '限定ニュース、限定版、特別イベントを最速でお届け',
                        'kr' => '독점 뉴스, 한정판 및 특별 이벤트를 가장 먼저 받아보세요',
                        default => 'ข่าวสารพิเศษ คอลเลคชั่นลิมิเต็ด และอีเว้นท์สุดพิเศษส่งถึงคุณก่อนใคร',
                    };
                    ?>
                </p>
            </div>
            <div class="newsletter-stats">
                <div class="stat-item">
                    <div class="stat-number">10K+</div>
                    <div class="stat-label">
                        <?php echo match($lang) {
                            'en' => 'Subscribers',
                            'cn' => '订阅者',
                            'jp' => '購読者',
                            'kr' => '구독자',
                            default => 'ผู้ติดตาม',
                        }; ?>
                    </div>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <div class="stat-number">24h</div>
                    <div class="stat-label">
                        <?php echo match($lang) {
                            'en' => 'Early Access',
                            'cn' => '提前访问',
                            'jp' => '先行アクセス',
                            'kr' => '조기 액세스',
                            default => 'เข้าถึงก่อน',
                        }; ?>
                    </div>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <div class="stat-number">100%</div>
                    <div class="stat-label">
                        <?php echo match($lang) {
                            'en' => 'Exclusive',
                            'cn' => '独家',
                            'jp' => '限定',
                            'kr' => '독점',
                            default => 'พิเศษเฉพาะ',
                        }; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Breaking News Ticker -->
<section class="breaking-news">
    <div class="breaking-container">
        <div class="breaking-label">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="10" cy="10" r="8" fill="#ff0000"/>
                <circle cx="10" cy="10" r="4" fill="#ffffff"/>
            </svg>
            <?php echo match($lang) {
                'en' => 'BREAKING',
                'cn' => '最新',
                'jp' => '速報',
                'kr' => '속보',
                default => 'ด่วน',
            }; ?>
        </div>
        <div class="breaking-content">
            <div class="breaking-track">
                <?php if (!empty($breaking_news)): ?>
                    <?php foreach ($breaking_news as $news): ?>
                        <?php 
                        $news_id_encoded = urlencode(base64_encode($news['news_id']));
                        $news_link = "?news_detail&id=" . $news_id_encoded . "&lang=" . $lang;
                        ?>
                        <a href="<?= htmlspecialchars($news_link) ?>" style="text-decoration: none; color: inherit;">
                            <span><?= htmlspecialchars($news['subject']) ?></span>
                        </a>
                        <span class="breaking-divider">•</span>
                    <?php endforeach; ?>
                    
                    <!-- Duplicate for seamless loop -->
                    <?php foreach ($breaking_news as $news): ?>
                        <?php 
                        $news_id_encoded = urlencode(base64_encode($news['news_id']));
                        $news_link = "?news_detail&id=" . $news_id_encoded . "&lang=" . $lang;
                        ?>
                        <a href="<?= htmlspecialchars($news_link) ?>" style="text-decoration: none; color: inherit;">
                            <span><?= htmlspecialchars($news['subject']) ?></span>
                        </a>
                        <span class="breaking-divider">•</span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Fallback ถ้าไม่มีข่าว -->
                    <span><?php echo match($lang) {
                        'en' => 'New AI Companion Collection launches this Friday',
                        'cn' => '新 AI 伴侣系列本周五上市',
                        'jp' => '今週金曜日に新しいAIコンパニオンコレクション発売',
                        'kr' => '이번 금요일 새로운 AI 컴패니언 컬렉션 출시',
                        default => 'คอลเลคชั่น AI Companion ใหม่เปิดตัววันศุกร์นี้',
                    }; ?></span>
                    <span class="breaking-divider">•</span>
                    <span><?php echo match($lang) {
                        'en' => 'Limited Edition: Only 500 bottles worldwide',
                        'cn' => '限量版：全球仅 500 瓶',
                        'jp' => '限定版：世界でわずか500本',
                        'kr' => '한정판: 전 세계 단 500병',
                        default => 'ลิมิเต็ด อิดิชั่น: เพียง 500 ขวดทั่วโลก',
                    }; ?></span>
                    <span class="breaking-divider">•</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
/* Breaking News Ticker - Enhanced for clickable links */
.breaking-news {
    background: #fff;
    border-top: 1px solid #e5e5e5;
    border-bottom: 1px solid #e5e5e5;
    padding: 20px 0;
    overflow: hidden;
}

.breaking-container {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    gap: 30px;
}

.breaking-label {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 700;
    font-size: 14px;
    letter-spacing: 1px;
    color: #ff0000;
    white-space: nowrap;
    padding-left: 40px;
}

.breaking-content {
    flex: 1;
    overflow: hidden;
}

.breaking-track {
    display: flex;
    gap: 40px;
    animation: scroll 30s linear infinite;
    white-space: nowrap;
    align-items: center;
}

.breaking-track a {
    transition: color 0.3s ease;
}

.breaking-track a:hover span {
    color: #ffa719;
}

.breaking-track span {
    font-size: 15px;
    color: #1a1a1a;
    transition: color 0.3s ease;
}

.breaking-divider {
    color: #ffa719 !important;
    font-weight: 700;
}

@keyframes scroll {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(-50%);
    }
}

/* Pause on hover */
.breaking-content:hover .breaking-track {
    animation-play-state: paused;
}

@media (max-width: 768px) {
    .breaking-label {
        padding-left: 20px;
        font-size: 12px;
    }
    
    .breaking-track {
        gap: 30px;
    }
    
    .breaking-track span {
        font-size: 14px;
    }
}
</style>

    <!-- Main Content -->
    <div class="content-sticky">
        <div class="box-content">
            <div class="row">
                <div class="">
                    <?php include 'template/news/content.php' ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Premium Features Section -->
    <section class="premium-features">
        <div class="features-container">
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M24 4L29.09 18.26L44 20.27L33.45 29.97L36.18 44.73L24 37.27L11.82 44.73L14.55 29.97L4 20.27L18.91 18.26L24 4Z" fill="#ffa719"/>
                    </svg>
                </div>
                <h3 class="feature-title">
                    <?php echo match($lang) {
                        'en' => 'Exclusive Access',
                        'cn' => '独家访问',
                        'jp' => '限定アクセス',
                        'kr' => '독점 액세스',
                        default => 'สิทธิพิเศษเฉพาะคุณ',
                    }; ?>
                </h3>
                <p class="feature-description">
                    <?php echo match($lang) {
                        'en' => 'First to see new collections and limited editions',
                        'cn' => '率先查看新系列和限量版',
                        'jp' => '新コレクションと限定版を最初に見る',
                        'kr' => '새로운 컬렉션과 한정판을 가장 먼저 보기',
                        default => 'ดูคอลเลคชั่นใหม่และลิมิเต็ดอิดิชั่นก่อนใคร',
                    }; ?>
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="24" cy="24" r="20" stroke="#ffa719" stroke-width="2"/>
                        <path d="M24 12V24L32 28" stroke="#ffa719" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
                <h3 class="feature-title">
                    <?php echo match($lang) {
                        'en' => 'Real-Time Updates',
                        'cn' => '实时更新',
                        'jp' => 'リアルタイム更新',
                        'kr' => '실시간 업데이트',
                        default => 'อัพเดทแบบเรียลไทม์',
                    }; ?>
                </h3>
                <p class="feature-description">
                    <?php echo match($lang) {
                        'en' => 'Never miss important announcements',
                        'cn' => '永不错过重要公告',
                        'jp' => '重要なお知らせを見逃さない',
                        'kr' => '중요한 발표를 놓치지 마세요',
                        default => 'ไม่พลาดทุกข่าวสำคัญ',
                    }; ?>
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="6" y="10" width="36" height="28" rx="2" stroke="#ffa719" stroke-width="2"/>
                        <path d="M6 18H42" stroke="#ffa719" stroke-width="2"/>
                        <circle cx="15" cy="14" r="1.5" fill="#ffa719"/>
                        <circle cx="20" cy="14" r="1.5" fill="#ffa719"/>
                    </svg>
                </div>
                <h3 class="feature-title">
                    <?php echo match($lang) {
                        'en' => 'Behind the Scenes',
                        'cn' => '幕后花絮',
                        'jp' => '舞台裏',
                        'kr' => '비하인드 스토리',
                        default => 'เบื้องหลังการสร้างสรรค์',
                    }; ?>
                </h3>
                <p class="feature-description">
                    <?php echo match($lang) {
                        'en' => 'Inside look at our creative process',
                        'cn' => '深入了解我们的创作过程',
                        'jp' => '創作プロセスの内部',
                        'kr' => '창작 과정의 내부',
                        default => 'ส่องกระบวนการสร้างสรรค์',
                    }; ?>
                </p>
            </div>
        </div>
    </section>

    <!-- Social Proof Section -->
    <section class="social-proof">
        <div class="social-container">
            <div class="social-content">
                <h2 class="social-title">
                    <?php echo match($lang) {
                        'en' => 'Join Our Community',
                        'cn' => '加入我们的社区',
                        'jp' => 'コミュニティに参加',
                        'kr' => '커뮤니티 가입',
                        default => 'เข้าร่วมชุมชนของเรา',
                    }; ?>
                </h2>
                <p class="social-description">
                    <?php echo match($lang) {
                        'en' => 'Connect with fragrance enthusiasts worldwide',
                        'cn' => '与全球香水爱好者联系',
                        'jp' => '世界中の香水愛好家とつながる',
                        'kr' => '전 세계 향수 애호가들과 연결',
                        default => 'เชื่อมต่อกับคนรักน้ำหอมทั่วโลก',
                    }; ?>
                </p>
            </div>
            <div class="social-stats-grid">
                <div class="social-stat">
                    <div class="social-number">50K+</div>
                    <div class="social-label">
                        <?php echo match($lang) {
                            'en' => 'Community Members',
                            'cn' => '社区成员',
                            'jp' => 'コミュニティメンバー',
                            'kr' => '커뮤니티 회원',
                            default => 'สมาชิกชุมชน',
                        }; ?>
                    </div>
                </div>
                <div class="social-stat">
                    <div class="social-number">120+</div>
                    <div class="social-label">
                        <?php echo match($lang) {
                            'en' => 'Countries',
                            'cn' => '个国家',
                            'jp' => 'カ国',
                            'kr' => '개 국가',
                            default => 'ประเทศ',
                        }; ?>
                    </div>
                </div>
                <div class="social-stat">
                    <div class="social-number">4.9★</div>
                    <div class="social-label">
                        <?php echo match($lang) {
                            'en' => 'Average Rating',
                            'cn' => '平均评分',
                            'jp' => '平均評価',
                            'kr' => '평균 평점',
                            default => 'คะแนนเฉลี่ย',
                        }; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'template/footer.php' ?>

</body>
</html>