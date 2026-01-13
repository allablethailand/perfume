<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'th';

// Footer Translations
$footer_translations = [
    // Brand Description
    'brand_desc' => [
        'th' => 'น้ำหอมที่มี AI เป็นเพื่อนคู่ใจ ครั้งแรกของโลกที่ผสมผสานเทคโนโลยีปัญญาประดิษฐ์เข้ากับศิลปะแห่งกลิ่นหอม สร้างประสบการณ์ที่ไม่เหมือนใครในทุกขวด',
        'en' => 'The world\'s first AI Companion Perfume. Blending artificial intelligence with the art of fragrance, creating a unique experience in every bottle.',
        'cn' => '世界上第一款 AI 伴侣香水。将人工智能与香水艺术相结合，在每个瓶中创造独特的体验。',
        'jp' => '世界初の AI コンパニオン フレグランス。人工知能と香りの芸術を融合し、すべてのボトルにユニークな体験を生み出します。',
        'kr' => '세계 최초의 AI 컴패니언 향수. 인공 지능과 향수 예술을 결합하여 모든 병에서 독특한 경험을 만듭니다.'
    ],
    
    // About Section
    'about_us' => [
        'th' => 'เกี่ยวกับเรา',
        'en' => 'About Us',
        'cn' => '关于我们',
        'jp' => '会社概要',
        'kr' => '회사 소개'
    ],
    'our_story' => [
        'th' => 'เรื่องราวของเรา',
        'en' => 'Our Story',
        'cn' => '我们的故事',
        'jp' => '私たちのストーリー',
        'kr' => '우리의 이야기'
    ],
    'innovation' => [
        'th' => 'นวัตกรรม',
        'en' => 'Innovation',
        'cn' => '创新',
        'jp' => 'イノベーション',
        'kr' => '혁신'
    ],
    'technology' => [
        'th' => 'เทคโนโลยี AI',
        'en' => 'AI Technology',
        'cn' => 'AI 技术',
        'jp' => 'AI テクノロジー',
        'kr' => 'AI 기술'
    ],
    'careers' => [
        'th' => 'ร่วมงานกับเรา',
        'en' => 'Careers',
        'cn' => '招聘',
        'jp' => '採用情報',
        'kr' => '채용'
    ],
    
    // Products Section
    'products' => [
        'th' => 'ผลิตภัณฑ์',
        'en' => 'Products',
        'cn' => '产品',
        'jp' => '製品',
        'kr' => '제품'
    ],
    'collections' => [
        'th' => 'คอลเลคชั่น',
        'en' => 'Collections',
        'cn' => '系列',
        'jp' => 'コレクション',
        'kr' => '컬렉션'
    ],
    'limited_edition' => [
        'th' => 'ลิมิเต็ดอิดิชั่น',
        'en' => 'Limited Edition',
        'cn' => '限量版',
        'jp' => '限定版',
        'kr' => '한정판'
    ],
    'bestsellers' => [
        'th' => 'สินค้าขายดี',
        'en' => 'Bestsellers',
        'cn' => '畅销产品',
        'jp' => 'ベストセラー',
        'kr' => '베스트셀러'
    ],
    'new_arrivals' => [
        'th' => 'สินค้าใหม่',
        'en' => 'New Arrivals',
        'cn' => '新品',
        'jp' => '新着',
        'kr' => '신제품'
    ],
    
    // Customer Service
    'customer_service' => [
        'th' => 'บริการลูกค้า',
        'en' => 'Customer Service',
        'cn' => '客户服务',
        'jp' => 'カスタマーサービス',
        'kr' => '고객 서비스'
    ],
    'contact_us' => [
        'th' => 'ติดต่อเรา',
        'en' => 'Contact Us',
        'cn' => '联系我们',
        'jp' => 'お問い合わせ',
        'kr' => '문의하기'
    ],
    'shipping_delivery' => [
        'th' => 'การจัดส่ง',
        'en' => 'Shipping & Delivery',
        'cn' => '运输与配送',
        'jp' => '配送について',
        'kr' => '배송 및 배달'
    ],
    'returns' => [
        'th' => 'การคืนสินค้า',
        'en' => 'Returns & Exchanges',
        'cn' => '退货与换货',
        'jp' => '返品・交換',
        'kr' => '반품 및 교환'
    ],
    'faq' => [
        'th' => 'คำถามที่พบบ่อย',
        'en' => 'FAQ',
        'cn' => '常见问题',
        'jp' => 'よくある質問',
        'kr' => '자주 묻는 질문'
    ],
    'warranty' => [
        'th' => 'การรับประกัน',
        'en' => 'Warranty',
        'cn' => '保修',
        'jp' => '保証',
        'kr' => '보증'
    ],
    
    // Newsletter
    'newsletter_title' => [
        'th' => 'ติดตามข่าวสาร',
        'en' => 'Stay Connected',
        'cn' => '保持联系',
        'jp' => '最新情報をお届け',
        'kr' => '최신 소식 받기'
    ],
    'newsletter_desc' => [
        'th' => 'รับข่าวสารล่าสุดเกี่ยวกับผลิตภัณฑ์ใหม่ และข้อเสนอพิเศษ',
        'en' => 'Get the latest news about new products and special offers',
        'cn' => '获取有关新产品和特别优惠的最新消息',
        'jp' => '新製品や特別オファーに関する最新情報を入手',
        'kr' => '신제품 및 특별 행사에 대한 최신 소식 받기'
    ],
    'email_placeholder' => [
        'th' => 'ใส่อีเมลของคุณ',
        'en' => 'Enter your email',
        'cn' => '输入您的电子邮件',
        'jp' => 'メールアドレスを入力',
        'kr' => '이메일을 입력하세요'
    ],
    'subscribe' => [
        'th' => 'สมัครสมาชิก',
        'en' => 'Subscribe',
        'cn' => '订阅',
        'jp' => '購読する',
        'kr' => '구독하기'
    ],
    
    // Social
    'follow_us' => [
        'th' => 'ติดตามเรา',
        'en' => 'Follow Us',
        'cn' => '关注我们',
        'jp' => 'フォローする',
        'kr' => '팔로우'
    ],
    
    // Copyright
    'copyright' => [
        'th' => '© 2025 AI COMPANION PERFUME. สงวนลิขสิทธิ์',
        'en' => '© 2025 AI COMPANION PERFUME. All Rights Reserved.',
        'cn' => '© 2025 AI 伴侣香水。保留所有权利。',
        'jp' => '© 2025 AI コンパニオン フレグランス。全著作権所有。',
        'kr' => '© 2025 AI 컴패니언 향수. 모든 권리 보유.'
    ],
    'made_with_love' => [
        'th' => 'สร้างสรรค์ด้วยความรัก',
        'en' => 'Crafted with Love',
        'cn' => '用爱制作',
        'jp' => '愛を込めて作成',
        'kr' => '사랑으로 만든'
    ]
];

function ft($key, $lang) {
    global $footer_translations;
    return $footer_translations[$key][$lang] ?? $footer_translations[$key]['en'];
}

// Social Media Links
$social_links = [
    ['icon' => 'fab fa-facebook-f', 'url' => '#', 'name' => 'Facebook'],
    ['icon' => 'fab fa-instagram', 'url' => '#', 'name' => 'Instagram'],
    ['icon' => 'fab fa-twitter', 'url' => '#', 'name' => 'Twitter'],
    ['icon' => 'fab fa-youtube', 'url' => '#', 'name' => 'YouTube'],
    ['icon' => 'fab fa-line', 'url' => '#', 'name' => 'Line']
];
?>

<!-- Footer Styles -->
<style>
    :root {
        --luxury-black: #000000;
        --luxury-white: #ffffff;
        --luxury-gray: #666666;
        --accent-gold: #C9A55A;
        --transition: cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* ========================================
       FOOTER - LUXURY MINIMAL
       ======================================== */
    
    .footer {
        background: var(--luxury-black);
        color: var(--luxury-white);
        position: relative;
        overflow: hidden;
    }

    .footer::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 1px;
        background: linear-gradient(90deg, 
            transparent 0%, 
            var(--accent-gold) 50%, 
            transparent 100%);
    }

    /* Newsletter Section */
    .footer-newsletter {
        padding: 80px 80px;
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .newsletter-label {
        font-size: 11px;
        font-weight: 500;
        letter-spacing: 0.25em;
        text-transform: uppercase;
        color: var(--accent-gold);
        margin-bottom: 20px;
    }

    .newsletter-title {
        font-family: 'Playfair Display', serif;
        font-size: 36px;
        font-weight: 400;
        letter-spacing: 0.02em;
        margin-bottom: 15px;
    }

    .newsletter-desc {
        font-size: 14px;
        font-weight: 300;
        color: rgba(255, 255, 255, 0.6);
        margin-bottom: 40px;
    }

    .newsletter-cta {
        display: inline-block;
        padding: 20px 60px;
        background: var(--luxury-white);
        color: var(--luxury-black);
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        letter-spacing: 0.15em;
        text-transform: uppercase;
        transition: all 0.4s ease;
        position: relative;
        overflow: hidden;
    }

    .newsletter-cta::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: var(--accent-gold);
        transition: left 0.4s ease;
        z-index: 0;
    }

    .newsletter-cta:hover::before {
        left: 0;
    }

    .newsletter-cta span {
        position: relative;
        z-index: 1;
    }

    .newsletter-cta:hover {
        color: var(--luxury-white);
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(201, 165, 90, 0.3);
    }

    /* Main Footer Content */
    .footer-main {
        padding: 80px 80px 60px;
    }

    .footer-content {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr;
        gap: 80px;
        margin-bottom: 60px;
    }

    /* Brand Column */
    .footer-brand-section {
        max-width: 400px;
    }

    .footer-brand {
        font-family: 'Playfair Display', serif;
        font-size: 32px;
        font-weight: 600;
        letter-spacing: 0.15em;
        margin-bottom: 25px;
        background: linear-gradient(135deg, #ffffff 0%, var(--accent-gold) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .footer-description {
        font-size: 14px;
        font-weight: 300;
        line-height: 1.9;
        color: rgba(255, 255, 255, 0.6);
        margin-bottom: 30px;
    }

    .footer-social {
        display: flex;
        gap: 20px;
    }

    .social-link {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: rgba(255, 255, 255, 0.6);
        text-decoration: none;
        transition: all 0.3s ease;
        font-size: 14px;
    }

    .social-link:hover {
        background: var(--luxury-white);
        color: var(--luxury-black);
        border-color: var(--luxury-white);
        transform: translateY(-3px);
    }

    /* Footer Columns */
    .footer-column {
        display: flex;
        flex-direction: column;
    }

    .footer-title {
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        margin-bottom: 30px;
        color: var(--luxury-white);
    }

    .footer-links {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .footer-link {
        font-size: 14px;
        font-weight: 300;
        color: rgba(255, 255, 255, 0.6);
        text-decoration: none;
        transition: all 0.3s ease;
        position: relative;
        padding-left: 0;
    }

    .footer-link::before {
        content: '';
        position: absolute;
        left: 0;
        bottom: -2px;
        width: 0;
        height: 1px;
        background: var(--accent-gold);
        transition: width 0.3s ease;
    }

    .footer-link:hover {
        color: var(--luxury-white);
        padding-left: 15px;
    }

    .footer-link:hover::before {
        width: 10px;
    }

    /* Footer Bottom */
    .footer-bottom {
        padding-top: 40px;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .footer-copyright {
        font-size: 12px;
        font-weight: 300;
        color: rgba(255, 255, 255, 0.4);
        letter-spacing: 0.05em;
    }

    .footer-made-with {
        font-size: 12px;
        font-weight: 300;
        color: rgba(255, 255, 255, 0.4);
        letter-spacing: 0.05em;
    }

    .footer-made-with .heart {
        color: var(--accent-gold);
        display: inline-block;
        animation: heartbeat 1.5s infinite;
    }

    @keyframes heartbeat {
        0%, 100% { transform: scale(1); }
        10%, 30% { transform: scale(1.1); }
        20%, 40% { transform: scale(1); }
    }

    /* Responsive */
    @media (max-width: 1024px) {
        .footer-newsletter,
        .footer-main {
            padding: 60px 40px;
        }

        .footer-content {
            grid-template-columns: 1fr 1fr;
            gap: 50px;
        }

        .footer-brand-section {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 768px) {
        .footer-newsletter,
        .footer-main {
            padding: 50px 30px;
        }

        .newsletter-title {
            font-size: 28px;
        }

        .newsletter-form {
            flex-direction: column;
        }

        .footer-content {
            grid-template-columns: 1fr;
            gap: 40px;
        }

        .footer-bottom {
            flex-direction: column;
            gap: 20px;
            text-align: center;
        }
    }
</style>

<!-- FOOTER -->
<footer class="footer">
    <!-- Newsletter Section -->
    <div class="footer-newsletter">
        <p class="newsletter-label"><?= ft('follow_us', $lang) ?></p>
        <h3 class="newsletter-title"><?= ft('newsletter_title', $lang) ?></h3>
        <p class="newsletter-desc"><?= ft('newsletter_desc', $lang) ?></p>
        <a href="?register&lang=<?= $lang ?>" class="newsletter-cta">
            <span><?= ft('subscribe', $lang) ?></span>
        </a>
    </div>

    <!-- Main Footer Content -->
    <div class="footer-main">
        <div class="footer-content">
            <!-- Brand Column -->
            <div class="footer-brand-section">
                <a href="?" class="logo2">
                    <img src="public/product_images/6965ad2fca016_1768271151.png" alt="Perfume Luxury Logo" style="height: 50px;">
                </a>
                <p class="footer-description">
                    <?= ft('brand_desc', $lang) ?>
                </p>
                <div class="footer-social">
                    <?php foreach ($social_links as $social): ?>
                        <a href="<?= htmlspecialchars($social['url']) ?>" 
                           class="social-link"
                           title="<?= htmlspecialchars($social['name']) ?>"
                           target="_blank"
                           rel="noopener">
                            <i class="<?= htmlspecialchars($social['icon']) ?>"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- About Column -->
            <div class="footer-column">
                <h4 class="footer-title"><?= ft('about_us', $lang) ?></h4>
                <div class="footer-links">
                    <a href="?about&lang=<?= $lang ?>" class="footer-link">
                        <?= ft('our_story', $lang) ?>
                    </a>
                    <a href="#" class="footer-link">
                        <?= ft('innovation', $lang) ?>
                    </a>
                    <a href="#" class="footer-link">
                        <?= ft('technology', $lang) ?>
                    </a>
                    <a href="#" class="footer-link">
                        <?= ft('careers', $lang) ?>
                    </a>
                </div>
            </div>

            <!-- Products Column -->
            <div class="footer-column">
                <h4 class="footer-title"><?= ft('products', $lang) ?></h4>
                <div class="footer-links">
                    <a href="?product&lang=<?= $lang ?>" class="footer-link">
                        <?= ft('collections', $lang) ?>
                    </a>
                    <a href="#" class="footer-link">
                        <?= ft('limited_edition', $lang) ?>
                    </a>
                    <a href="#" class="footer-link">
                        <?= ft('bestsellers', $lang) ?>
                    </a>
                    <a href="#" class="footer-link">
                        <?= ft('new_arrivals', $lang) ?>
                    </a>
                </div>
            </div>

            <!-- Customer Service Column -->
            <div class="footer-column">
                <h4 class="footer-title"><?= ft('customer_service', $lang) ?></h4>
                <div class="footer-links">
                    <a href="#" class="footer-link">
                        <?= ft('contact_us', $lang) ?>
                    </a>
                    <a href="#" class="footer-link">
                        <?= ft('shipping_delivery', $lang) ?>
                    </a>
                    <a href="#" class="footer-link">
                        <?= ft('returns', $lang) ?>
                    </a>
                    <a href="#" class="footer-link">
                        <?= ft('faq', $lang) ?>
                    </a>
                    <a href="#" class="footer-link">
                        <?= ft('warranty', $lang) ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <p class="footer-copyright">
                <?= ft('copyright', $lang) ?>
            </p>
            <p class="footer-made-with">
                <?= ft('made_with_love', $lang) ?> <span class="heart">♥</span>
            </p>
        </div>
    </div>
</footer>

<!-- Font Awesome (ถ้ายังไม่มี) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">