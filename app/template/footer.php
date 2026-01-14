<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$isProtocol = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http';
$isFile = ($isProtocol === 'http') ? '.php' : '';

$menuItems = [
    [
        'id' => 0,
        'icon' => 'fas fa-user-plus',
        'text' => '',
        'translate' => 'Sign_up',
        'link' => 'register' . $isFile,
        'modal_id' => ''
    ],
];

global $conn;
global $base_path;

$lang = 'th';
if (isset($_GET['lang'])) {
    $supportedLangs = ['en', 'cn', 'jp', 'kr'];
    if (in_array($_GET['lang'], $supportedLangs)) {
        $_SESSION['lang'] = $_GET['lang'];
        $lang = $_GET['lang'];
    } else {
        unset($_SESSION['lang']);
    }
} elseif (isset($_SESSION['lang'])) {
    $lang = $_SESSION['lang'];
}

$footer_settings = [];
$footer_id_for_display = 1;

$stmt_footer = $conn->prepare("SELECT 
    bg_color, footer_top_title, footer_top_title_en, footer_top_title_cn, footer_top_title_jp, footer_top_title_kr, footer_top_subtitle, footer_top_subtitle_en, footer_top_subtitle_cn, footer_top_subtitle_jp, footer_top_subtitle_kr,
    about_heading, about_heading_en, about_heading_cn, about_heading_jp, about_heading_kr, about_text, about_text_en, about_text_cn, about_text_jp, about_text_kr,
    contact_heading, contact_heading_en, contact_heading_cn, contact_heading_jp, contact_heading_kr, contact_address, contact_address_en, contact_address_cn, contact_address_jp, contact_address_kr,
    contact_phone, contact_email,
    contact_hours_wk, contact_hours_wk_en, contact_hours_wk_cn, contact_hours_wk_jp, contact_hours_wk_kr, contact_hours_sat, contact_hours_sat_en, contact_hours_sat_cn, contact_hours_sat_jp, contact_hours_sat_kr,
    social_heading, social_heading_en, social_heading_cn, social_heading_jp, social_heading_kr, social_links_json,
    copyright_text
    FROM footer_settings WHERE id = ?");
$stmt_footer->bind_param("i", $footer_id_for_display);
$stmt_footer->execute();
$result_footer = $stmt_footer->get_result();

if ($data = $result_footer->fetch_assoc()) {
    $footer_settings = $data;
    $footer_settings['social_links'] = json_decode($footer_settings['social_links_json'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $footer_settings['social_links'] = [];
        error_log("Error decoding social_links_json in footer.php: " . json_last_error_msg());
    }
} else {
    $footer_settings = [
        'bg_color' => '#393939',
        'footer_top_title' => 'ลงทะเบียน',
        'footer_top_title_en' => 'Register',
        'footer_top_title_cn' => '注册',
        'footer_top_title_jp' => '登録',
        'footer_top_title_kr' => '가입하다',
        'footer_top_subtitle' => 'สมัครรับจดหมายข่าวของเราสำหรับข่าวสารล่าสุด และข้อเสนอสุดพิเศษ',
        'footer_top_subtitle_en' => 'Subscribe to our newsletter for the latest news and special offers.',
        'footer_top_subtitle_cn' => '订阅我们的时事通讯，获取最新消息和特别优惠。',
        'footer_top_subtitle_jp' => '最新ニュースや特別オファーのニュースレターを購読してください。',
        'footer_top_subtitle_kr' => '최신 뉴스 및 특별 행사를 위한 뉴스레터를 구독하세요.',
        'about_heading' => 'เกี่ยวกับเรา',
        'about_heading_en' => 'About Us',
        'about_heading_cn' => '关于我们',
        'about_heading_jp' => '会社概要',
        'about_heading_kr' => '회사 소개',
        'about_text' => 'บริษัท แทรนดาร์ อินเตอร์เนชั่นแนล จำกัดได้ก่อตั้งขึ้นเมื่อวันที่ 1 มีนาคม 2531 เราเป็นผู้เชี่ยวชาญด้านระบบฝ้าดูดซับเสียง ผนังกั้นเสียงและฝ้าอะคูสติกทุกชนิด เรามีทีมงานและผู้เชี่ยวชาญที่พร้อมให้คำปรึกษาในการออกแบบและติดตั้ง พร้อมทั้งผลิตและจำหน่ายแผ่นอะคูสติก ผนังดูดซับเสียง ซาวน์บอร์ด ผนังกั้นเสียง แผ่นฝ้า ที่ได้มาตรฐานจากทั้งในและต่างประเทศ รวมถึงการให้บริการที่มีประสิทธิภาพจากแทรนดาร์ อะคูสติก',
        'about_text_en' => 'perfume International Co., Ltd. was founded on March 1, 1988. We are experts in sound-absorbing ceilings, soundproof walls, and all types of acoustic ceilings. We have a team and experts ready to provide advice on design and installation. We also manufacture and distribute acoustic panels, sound-absorbing walls, soundboards, and ceiling panels that meet both domestic and international standards, along with efficient services from perfume Acoustic.',
        'about_text_cn' => 'perfume International Co., Ltd. 成立于 1988 年 3 月 1 日。我们是吸音天花板、隔音墙和各种声学天花板系统的专家。我们拥有一支随时可以提供设计和安装咨询的团队和专家。我们还制造和分销符合国内和国际标准的声学板、吸音墙、隔音板和天花板，并提供来自 perfume Acoustic 的高效服务。',
        'about_text_jp' => 'perfume International Co., Ltd.は1988年3月1日に設立されました。私たちは、吸音天井、防音壁、およびあらゆる種類のアコースティック天井のシステムを専門としています。設計と設置に関するアドバイスを提供する専門家チームがいます。また、国内外の基準を満たすアコースティックパネル、吸音壁、サウンドボード、および天井パネルを製造・販売しており、perfume Acousticによる効率的なサービスを提供しています。',
        'about_text_kr' => 'perfume International Co., Ltd.는 1988년 3월 1일에 설립되었습니다. 우리는 흡음 천장, 방음벽 및 모든 유형의 음향 천장 시스템을 전문으로 합니다. 우리는 디자인 및 설치에 대한 조언을 제공할 준비가 된 팀과 전문가를 보유하고 있습니다. 또한 국내외 표준을 충족하는 음향 패널, 흡음벽, 사운드보드 및 천장 패널을 제조 및 유통하며, perfume Acoustic에서 효율적인 서비스를 제공합니다.',
        'contact_heading' => 'ติดต่อเรา',
        'contact_heading_en' => 'Contact Us',
        'contact_heading_cn' => '联系我们',
        'contact_heading_jp' => 'お問い合わせ',
        'contact_heading_kr' => '문의하기',
        'contact_address' => '102 Phatthanakan 40, Suan Luang, Bangkok 10250',
        'contact_address_en' => '102 Phatthanakan 40, Suan Luang, Bangkok 10250',
        'contact_address_cn' => '曼谷 Suan Luang Phatthanakan 40 102号，邮编 10250',
        'contact_address_jp' => '102 Phatthanakan 40, Suan Luang, Bangkok 10250',
        'contact_address_kr' => '102 Phatthanakan 40, Suan Luang, Bangkok 10250',
        'contact_phone' => '(+66)2 722 7007',
        'contact_email' => 'info@perfume.com',
        'contact_hours_wk' => 'Monday – Friday 08:30 AM – 05:00 PM',
        'contact_hours_wk_en' => 'Monday – Friday 08:30 AM – 05:00 PM',
        'contact_hours_wk_cn' => '周一至周五 08:30 AM – 05:00 PM',
        'contact_hours_wk_jp' => '月曜日～金曜日 08:30 AM～05:00 PM',
        'contact_hours_wk_kr' => '월요일 – 금요일 08:30 AM – 05:00 PM',
        'contact_hours_sat' => 'Saturday 08:30 AM – 12:00 PM',
        'contact_hours_sat_en' => 'Saturday 08:30 AM – 12:00 PM',
        'contact_hours_sat_cn' => '周六 08:30 AM – 12:00 PM',
        'contact_hours_sat_jp' => '土曜日 08:30 AM～12:00 PM',
        'contact_hours_sat_kr' => '토요일 08:30 AM – 12:00 PM',
        'social_heading' => 'Follow Us',
        'social_heading_en' => 'Follow Us',
        'social_heading_cn' => '关注我们',
        'social_heading_jp' => 'フォロー',
        'social_heading_kr' => '우리를 따르십시오',
        'social_links' => [
            ["icon" => "fab fa-facebook-f", "url" => "https://www.facebook.com/perfumeacoustic/", "color" => "#3b5998"],
            ["icon" => "fab fa-instagram", "url" => "https://www.instagram.com/perfumeacoustics/", "color" => "#e1306c"],
            ["icon" => "fab fa-youtube", "url" => "https://www.youtube.com/channel/UCewsEEtw8DOwSWoQ6ae_Uwg/", "color" => "#ff0000"],
            ["icon" => "fab fa-line", "url" => "https://lin.ee/yoSCNwF", "color" => "#00c300"],
            ["icon" => "fab fa-tiktok", "url" => "https://www.tiktok.com/@perfumeacoustics", "color" => "#000000"]
        ],
        'copyright_text' => '© 2025 perfume INTERNATIONAL CO., LTD. ALL RIGHTS RESERVED',
    ];
}
$stmt_footer->close();
function get_text($settings, $field_name, $lang)
{
    $field_en = $field_name . '_en';
    $field_cn = $field_name . '_cn';
    $field_jp = $field_name . '_jp';
    $field_kr = $field_name . '_kr';
    if ($lang === 'en' && !empty($settings[$field_en])) {
        return $settings[$field_en];
    }
    if ($lang === 'cn' && !empty($settings[$field_cn])) {
        return $settings[$field_cn];
    }
    if ($lang === 'jp' && !empty($settings[$field_jp])) {
        return $settings[$field_jp];
    }
    if ($lang === 'kr' && !empty($settings[$field_kr])) {
        return $settings[$field_kr];
    }
    return $settings[$field_name];
}

?>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<footer class="main-footer" style="background-color: <?= htmlspecialchars($footer_settings['bg_color']) ?>;">
    <div class="container text-center footer-top-section">
        <h2 class="footer-title"><?= htmlspecialchars(get_text($footer_settings, 'footer_top_title', $lang)) ?></h2>
        <p class="footer-subtitle"><?= htmlspecialchars(get_text($footer_settings, 'footer_top_subtitle', $lang)) ?></p>
        <div class="mt-4">
            <div id="auth-buttons">
                <?php foreach ($menuItems as $item): ?>
                    <a style="color: #4350e4ff;;" aria-label="สมัครสมาชิก" type="button" href="<?php echo $item['link']; ?>"
                        id="<?php echo $item['modal_id'] ?>">
                        <i class="<?php echo $item['icon']; ?>"></i>
                        <span data-translate="<?php echo $item['translate']; ?>" lang="th">
                            <?php echo $item['text']; ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="container main-footer-content">
        <div class="row">
            <div class="col-md-4 col-sm-12 mb-4 pr-md-5">
                <p class="footer-heading"><?= htmlspecialchars(get_text($footer_settings, 'about_heading', $lang)) ?>
                </p>
                <p><?= nl2br(htmlspecialchars(get_text($footer_settings, 'about_text', $lang))) ?></p>
            </div>

            <div class="col-md-4 col-sm-12 mb-4 px-md-3">
                <p class="footer-heading"><?= htmlspecialchars(get_text($footer_settings, 'contact_heading', $lang)) ?>
                </p>
                <p><?= htmlspecialchars(get_text($footer_settings, 'contact_address', $lang)) ?></p>
                <p><?= htmlspecialchars($footer_settings['contact_phone']) ?></p>
                <p><?= htmlspecialchars($footer_settings['contact_email']) ?></p>
                <p><?= htmlspecialchars(get_text($footer_settings, 'contact_hours_wk', $lang)) ?></p>
                <p><?= htmlspecialchars(get_text($footer_settings, 'contact_hours_sat', $lang)) ?></p>
            </div>

            <div class="col-md-4 col-sm-12 mb-4 pl-md-5">
                <p class="footer-heading"><?= htmlspecialchars(get_text($footer_settings, 'social_heading', $lang)) ?>
                </p>
                <div class="social-icons-group">
                    <?php if (!empty($footer_settings['social_links'])): ?>
                        <?php foreach ($footer_settings['social_links'] as $social_link): ?>
                            <a href="<?= htmlspecialchars($social_link['url']) ?>" class="social-icon"
                                style="background-color: <?= htmlspecialchars($social_link['color']) ?>;" aria-label="open company page on another app" target="_blank">
                                <i class="<?= htmlspecialchars($social_link['icon']) ?>"></i>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php
                        $noSocialText = [
                            'th' => 'ยังไม่มี Social Link กำหนดค่า.',
                            'en' => 'No Social Links configured.',
                            'cn' => '未配置社交链接。',
                            'jp' => 'ソーシャルリンクが設定されていません。',
                            'kr' => '구성된 소셜 링크가 없습니다.'
                        ];
                        ?>
                        <p class="text-muted"><?php echo $noSocialText[$lang]; ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom-bar">
        <?= htmlspecialchars($footer_settings['copyright_text']) ?>
    </div>

</footer>

<style>
    .main-footer {
        color: #ccc;
        padding: 40px 0;
    }

    .footer-top-section {
        margin-bottom: 40px;
    }

    .footer-title {
        color: #fff;
        font-weight: bold;
    }

    .footer-subtitle {
        color: #aaa;
    }

    .footer-heading {
        font-size: 25px;
        font-weight: bold;
        margin-bottom: 15px;
        color: #fff;
    }

    .main-footer-content p {
        font-size: 14px;
        line-height: 1.6;
    }

    .social-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        font-size: 22px;
        color: white;
        margin: 0 8px;
        text-decoration: none;
        transition: transform 0.2s ease-in-out;
    }

    .social-icon:hover {
        transform: translateY(-3px);
    }

    .footer-bottom-bar {
        text-align: center;
        color: #888;
        font-size: 13px;
        margin-top: 40px;
        padding-top: 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        /* Lighter border for subtlety */
    }

    @media (max-width: 767.98px) {

        .pr-md-5,
        .px-md-3,
        .pl-md-5 {
            padding-right: 15px !important;
            padding-left: 15px !important;
            margin-bottom: 20px;
        }

        .footer-heading {
            text-align: center;
        }

        .social-icons-group {
            text-align: center;
        }

        .main-footer-content p {
            text-align: center;
        }
    }
</style>