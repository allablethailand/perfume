<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once('lib/connect.php');
global $conn;

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

// Translations
$translations = [
    'contact_us' => [
        'th' => 'ติดต่อเรา',
        'en' => 'Contact Us',
        'cn' => '联系我们',
        'jp' => 'お問い合わせ',
        'kr' => '문의하기'
    ],
    'get_in_touch' => [
        'th' => 'ติดต่อกับเรา',
        'en' => 'Get in Touch',
        'cn' => '联系我们',
        'jp' => 'お問い合わせください',
        'kr' => '연락하세요'
    ],
    'contact_subtitle' => [
        'th' => 'เรายินดีที่จะตอบคำถามของคุณ มีคำถามเกี่ยวกับผลิตภัณฑ์หรือบริการของเราหรือไม่? ติดต่อเราได้ที่ข้อมูลด้านล่าง',
        'en' => 'We\'re here to answer your questions. Have questions about our products or services? Contact us using the information below.',
        'cn' => '我们随时为您解答问题。对我们的产品或服务有疑问吗？请使用以下信息与我们联系。',
        'jp' => 'ご質問にお答えします。製品やサービスについてご質問がありますか？以下の情報を使用してお問い合わせください。',
        'kr' => '질문에 답변해 드리겠습니다. 제품이나 서비스에 대한 질문이 있으신가요? 아래 정보를 사용하여 문의하세요.'
    ],
    'visit_us' => [
        'th' => 'เยี่ยมชมเรา',
        'en' => 'Visit Us',
        'cn' => '访问我们',
        'jp' => '訪問する',
        'kr' => '방문하기'
    ],
    'call_us' => [
        'th' => 'โทรหาเรา',
        'en' => 'Call Us',
        'cn' => '致电我们',
        'jp' => 'お電話',
        'kr' => '전화하기'
    ],
    'email_us' => [
        'th' => 'อีเมลเรา',
        'en' => 'Email Us',
        'cn' => '发送电子邮件',
        'jp' => 'メールする',
        'kr' => '이메일 보내기'
    ],
    'business_hours' => [
        'th' => 'เวลาทำการ',
        'en' => 'Business Hours',
        'cn' => '营业时间',
        'jp' => '営業時間',
        'kr' => '영업 시간'
    ],
    'location' => [
        'th' => 'ที่อยู่',
        'en' => 'Location',
        'cn' => '位置',
        'jp' => '所在地',
        'kr' => '위치'
    ],
    'find_us' => [
        'th' => 'ค้นหาเรา',
        'en' => 'Find Us',
        'cn' => '找到我们',
        'jp' => '所在地',
        'kr' => '찾아오시는 길'
    ]
];

function ct($key, $lang) {
    global $translations;
    return $translations[$key][$lang] ?? $translations[$key]['en'];
}

// Contact Information (Static Data)
$contact_info = [
    'company_name' => [
        'th' => 'Trandar Innovation',
        'en' => 'Trandar Innovation',
        'cn' => 'Trandar Innovation',
        'jp' => 'Trandar Innovation',
        'kr' => 'Trandar Innovation'
    ],
    'address' => [
        'th' => '102 ถนนพัฒนาการ 40 เขตสวนหลวง กรุงเทพมหานคร 10250',
        'en' => '102 Phatthanakan 40, Suan Luang, Bangkok 10250, Thailand',
        'cn' => '泰国曼谷 Suan Luang Phatthanakan 40 号 102 号，邮编 10250',
        'jp' => 'タイ国バンコク、スアンルアン、パッタナカン 40、102番地 10250',
        'kr' => '태국 방콕 수안루앙 파타나칸 40, 102번지 10250'
    ],
    'phone' => '+66 2 722 7007',
    'email' => 'info@trandar.com',
    'hours_weekday' => [
        'th' => 'จันทร์ – ศุกร์ 09:00 – 18:00 น.',
        'en' => 'Monday – Friday 09:00 AM – 06:00 PM',
        'cn' => '周一至周五 09:00 AM – 06:00 PM',
        'jp' => '月曜日～金曜日 09:00 AM～06:00 PM',
        'kr' => '월요일 – 금요일 09:00 AM – 06:00 PM'
    ],
    'hours_weekend' => [
        'th' => 'เสาร์ – อาทิตย์ 10:00 – 17:00 น.',
        'en' => 'Saturday – Sunday 10:00 AM – 05:00 PM',
        'cn' => '周六至周日 10:00 AM – 05:00 PM',
        'jp' => '土曜日～日曜日 10:00 AM～05:00 PM',
        'kr' => '토요일 – 일요일 10:00 AM – 05:00 PM'
    ]
];
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
   <?php include 'template/header.php'; ?>
    
    <style>
        /* Hero Section */
        .contact-hero {
            padding: 200px 200px;
            text-align: center;
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
            color: var(--luxury-white);
            position: relative;
            overflow: hidden;
        }

        .contact-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('public/product_images/696089dc32b1a_1767934428.jpg') center/cover;
            opacity: 0.15;
            z-index: 0;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 700px;
            margin: 0 auto;
        }

        .hero-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--accent-gold);
            margin-bottom: 20px;
        }

        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 56px;
            font-weight: 400;
            line-height: 1.2;
            margin-bottom: 20px;
            letter-spacing: 0.02em;
        }

        .hero-subtitle {
            font-size: 15px;
            font-weight: 300;
            line-height: 1.8;
            color: rgba(255, 255, 255, 0.7);
            max-width: 600px;
            margin: 0 auto;
        }

        /* Contact Info Section */
        .contact-info-section {
            padding: 80px 80px 60px;
            background: var(--luxury-white);
        }

        .info-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 60px;
        }

        .info-item {
            text-align: center;
        }

        .info-icon {
            width: 50px;
            height: 50px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e0e0e0;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .info-icon svg {
            width: 22px;
            height: 22px;
            stroke: var(--luxury-black);
            transition: all 0.3s ease;
        }

        .info-item:hover .info-icon {
            border-color: var(--accent-gold);
            transform: translateY(-3px);
        }

        .info-item:hover .info-icon svg {
            stroke: var(--accent-gold);
        }

        .info-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--luxury-gray);
            margin-bottom: 12px;
        }

        .info-value {
            font-size: 14px;
            font-weight: 400;
            line-height: 1.7;
            color: var(--luxury-black);
        }

        .info-value a {
            color: var(--luxury-black);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .info-value a:hover {
            color: var(--accent-gold);
        }

        /* Map Section */
        .map-section {
            padding: 60px 80px 80px;
            background: var(--luxury-white);
        }

        .map-container {
            max-width: 1200px;
            margin: 0 auto;
            height: 500px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .map-container iframe {
            width: 100%;
            height: 100%;
            border: 0;
            filter: grayscale(0.3);
            transition: filter 0.3s ease;
        }

        .map-container:hover iframe {
            filter: grayscale(0);
        }

        /* Company Section */
        .company-section {
            padding: 80px 80px;
            background: var(--luxury-light-gray);
            text-align: center;
        }

        .company-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .company-name {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 400;
            margin-bottom: 20px;
            letter-spacing: 0.02em;
        }

        .company-address {
            font-size: 14px;
            font-weight: 300;
            line-height: 1.8;
            color: var(--luxury-gray);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .contact-hero {
                padding: 140px 60px 70px;
            }

            .hero-title {
                font-size: 44px;
            }

            .info-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 50px;
            }

            .contact-info-section,
            .map-section,
            .company-section {
                padding: 60px 60px;
            }
        }

        @media (max-width: 768px) {
            .contact-hero {
                padding: 120px 40px 60px;
            }

            .hero-title {
                font-size: 36px;
            }

            .hero-subtitle {
                font-size: 14px;
            }

            .info-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .contact-info-section,
            .map-section,
            .company-section {
                padding: 50px 40px;
            }

            .map-container {
                height: 400px;
            }

            .company-name {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>
    <!-- Hero Section -->
    <section class="contact-hero">
        <div class="hero-content">
            <p class="hero-label"><?= ct('get_in_touch', $lang) ?></p>
            <h1 class="hero-title"><?= ct('contact_us', $lang) ?></h1>
            <p class="hero-subtitle">
                <?= ct('contact_subtitle', $lang) ?>
            </p>
        </div>
    </section>

    <!-- Contact Information -->
    <section class="contact-info-section">
        <div class="info-grid">
            <div class="info-item">
                <div class="info-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                    </svg>
                </div>
                <p class="info-label"><?= ct('visit_us', $lang) ?></p>
                <p class="info-value">
                    <?= $contact_info['address'][$lang] ?>
                </p>
            </div>

            <div class="info-item">
                <div class="info-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                    </svg>
                </div>
                <p class="info-label"><?= ct('call_us', $lang) ?></p>
                <p class="info-value">
                    <a href="tel:<?= $contact_info['phone'] ?>">
                        <?= $contact_info['phone'] ?>
                    </a>
                </p>
            </div>

            <div class="info-item">
                <div class="info-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                    </svg>
                </div>
                <p class="info-label"><?= ct('email_us', $lang) ?></p>
                <p class="info-value">
                    <a href="mailto:<?= $contact_info['email'] ?>">
                        <?= $contact_info['email'] ?>
                    </a>
                </p>
            </div>

            <div class="info-item">
                <div class="info-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="info-label"><?= ct('business_hours', $lang) ?></p>
                <p class="info-value">
                    <?= $contact_info['hours_weekday'][$lang] ?><br>
                    <?= $contact_info['hours_weekend'][$lang] ?>
                </p>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
        <div class="map-container">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3875.7396441274373!2d100.62457107508995!3d13.734206386655812!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x311d61b2c53ba90d%3A0x4442a96acffee7e9!2z4LmB4LiX4Lij4LiZ4LiU4Liy4Lij4LmMIOC4reC4tOC4meC5gOC4leC4reC4o-C5jOC5gOC4meC4iuC4seC5iOC4meC5geC4meC4pSAoVHJhbmRhciBJbnRlcm5hdGlvbmFsIGNvLiBsdGQp!5e0!3m2!1sen!2sth!4v1730371693406!5m2!1sen!2sth"
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </section>

    <!-- Company Info -->
    <section class="company-section">
        <div class="company-content">
            <h2 class="company-name"><?= $contact_info['company_name'][$lang] ?></h2>
            <p class="company-address">
                <?= $contact_info['address'][$lang] ?>
            </p>
        </div>
    </section>

    <?php include 'template/footer.php'; ?>
</body>
</html>