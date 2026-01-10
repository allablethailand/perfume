<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['lang'])) {
    $supportedLangs = ['en', 'cn', 'jp', 'kr'];
    $newLang = $_GET['lang'];
    if (in_array($newLang, $supportedLangs)) {
        $_SESSION['lang'] = $newLang;
    } else {
        unset($_SESSION['lang']);
    }
}
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'th';

$subjectColumn = 'subject_news';
if ($lang === 'en') {
    $subjectColumn = 'subject_news_en';
} elseif ($lang === 'cn') {
    $subjectColumn = 'subject_news_cn';
} elseif ($lang === 'jp') {
    $subjectColumn = 'subject_news_jp';
} elseif ($lang === 'kr') {
    $subjectColumn = 'subject_news_kr';
}

$menuTextColumn = 'text_' . $lang;
if (!in_array($menuTextColumn, ['text_th', 'text_en', 'text_cn', 'text_jp', 'text_kr'])) {
    $menuTextColumn = 'text_th';
}

$newsList = [];
$sql = "SELECT news_id, {$subjectColumn} FROM dn_news WHERE del = 0 ORDER BY date_create DESC LIMIT 3";

if (isset($conn)) {
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $newsList[] = $row;
        }
    }
}

$isProtocol = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http';
$isFile = ($isProtocol === 'http') ? '.php' : '';

function getSettings($conn)
{
    $settings = [
        'navbar_bg_color' => '#ff9900',
        'navbar_text_color' => '#ffffff',
        'news_ticker_display' => '1',
        'news_ticker_bg_color' => '#ffffffff',
        'news_ticker_text_color' => '#555',
        'news_ticker_title_color' => '#ff9900',
    ];
    if ($conn) {
        $sql = "SELECT setting_key, setting_value FROM dn_settings 
                WHERE setting_key IN (?, ?, ?, ?, ?, ?)";

        $required_keys = array_keys($settings);

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssssss", ...$required_keys);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $settings[$row['setting_key']] = $row['setting_value'];
                }
            }
            $stmt->close();
        }
    }

    return $settings;
}

$settings = getSettings(isset($conn) ? $conn : null);

$navbarItemsFromDB = [];
$sqlMenu = "SELECT 
                {$menuTextColumn} AS text, 
                translate_key AS 'translate', 
                link_url AS link, 
                icon_class AS icon, 
                is_dropdown AS isDropdown, 
                dropdown_id AS id, 
                is_active, 
                display_order AS 'order' 
            FROM dn_navbar_menu 
            WHERE is_active = 1 
            ORDER BY display_order ASC";

if (isset($conn)) {
    $resultMenu = $conn->query($sqlMenu);
    if ($resultMenu && $resultMenu->num_rows > 0) {
        while ($row = $resultMenu->fetch_assoc()) {
            $row['text'] = $row['text'] ?? '';
            $row['link'] = ($row['link'] == 'index.php') ? '../' : str_replace('.php', $isFile, $row['link']);
            $row['is_active'] = (bool) $row['is_active'];
            $row['isDropdown'] = (bool) $row['isDropdown'];
            $navbarItemsFromDB[] = $row;
        }
    }
}
$activeNavbarItems = $navbarItemsFromDB;
$dropdownItems = [
    'dropdown3' => [
        'th' => [['icon' => '', 'text' => 'INSUL Software', 'translate' => 'INSUL Software', 'link' => 'INSULSoftware' . $isFile], ['icon' => '', 'text' => 'Download', 'translate' => 'Download', 'link' => 'Download' . $isFile], ['icon' => '', 'text' => 'Instructions', 'translate' => 'Instructions', 'link' => 'Instructions' . $isFile],],
        'en' => [['icon' => '', 'text' => 'INSUL Software', 'translate' => 'INSUL Software', 'link' => 'INSULSoftware' . $isFile], ['icon' => '', 'text' => 'Download', 'translate' => 'Download', 'link' => 'Download' . $isFile], ['icon' => '', 'text' => 'Instructions', 'translate' => 'Instructions', 'link' => 'Instructions' . $isFile],],
        'cn' => [['icon' => '', 'text' => 'INSUL 软件', 'translate' => 'INSUL Software', 'link' => 'INSULSoftware' . $isFile], ['icon' => '', 'text' => '下载', 'translate' => 'Download', 'link' => 'Download' . $isFile], ['icon' => '', 'text' => '使用说明', 'translate' => 'Instructions', 'link' => 'Instructions' . $isFile],],
        'jp' => [['icon' => '', 'text' => 'INSUL ソフトウェア', 'translate' => 'INSUL Software', 'link' => 'INSULSoftware' . $isFile], ['icon' => '', 'text' => 'ダウンロード', 'translate' => 'Download', 'link' => 'Download' . $isFile], ['icon' => '', 'text' => '説明書', 'translate' => 'Instructions', 'link' => 'Instructions' . $isFile],],
        'kr' => [['icon' => '', 'text' => 'INSUL 소프트웨어', 'translate' => 'INSUL Software', 'link' => 'INSULSoftware' . $isFile], ['icon' => '', 'text' => '다운로드', 'translate' => 'Download', 'link' => 'Download' . $isFile], ['icon' => '', 'text' => '지침', 'translate' => 'Instructions', 'link' => 'Instructions' . $isFile],],
    ],
    'dropdown4' => [
        'th' => [['icon' => '', 'text' => 'บทความ', 'translate' => 'blog', 'link' => 'Blog' . $isFile], ['icon' => '', 'text' => 'ความรู้ด้านเสียง', 'translate' => 'Design&Idia', 'link' => 'idia' . $isFile], ['icon' => '', 'text' => 'วีดีโอ', 'translate' => 'video', 'link' => 'Video' . $isFile],],
        'en' => [['icon' => '', 'text' => 'Articles', 'translate' => 'blog', 'link' => 'Blog' . $isFile], ['icon' => '', 'text' => 'Acoustics Knowledge', 'translate' => 'Design&Idia', 'link' => 'idia' . $isFile], ['icon' => '', 'text' => 'Video', 'translate' => 'video', 'link' => 'Video' . $isFile],],
        'cn' => [['icon' => '', 'text' => '文章', 'translate' => 'blog', 'link' => 'Blog' . $isFile], ['icon' => '', 'text' => '声学知识', 'translate' => 'Design&Idia', 'link' => 'idia' . $isFile], ['icon' => '', 'text' => '视频', 'translate' => 'video', 'link' => 'Video' . $isFile],],
        'jp' => [['icon' => '', 'text' => '記事', 'translate' => 'blog', 'link' => 'Blog' . $isFile], ['icon' => '', 'text' => '音響知識', 'translate' => 'Design&Idia', 'link' => 'idia' . $isFile], ['icon' => '', 'text' => 'ビデオ', 'translate' => 'video', 'link' => 'Video' . $isFile],],
        'kr' => [['icon' => '', 'text' => '기사', 'translate' => 'blog', 'link' => 'Blog' . $isFile], ['icon' => '', 'text' => '음향 지식', 'translate' => 'Design&Idia', 'link' => 'idia' . $isFile], ['icon' => '', 'text' => '비디오', 'translate' => 'video', 'link' => 'Video' . $isFile],],
    ],
];


$navbarBgColor = $settings['navbar_bg_color'];
$navbarTextColor = $settings['navbar_text_color'];
$newsTickerBgColor = $settings['news_ticker_bg_color'];
$newsTickerTextColor = $settings['news_ticker_text_color'];
$newsTickerTitleBgColor = $settings['news_ticker_title_color'];
$newsTickerDisplay = $settings['news_ticker_display'] == '1' ? 'block' : 'none';

?>

<style>
    :root {
        --navbar-bg-color:
            <?= $navbarBgColor ?>
        ;
        --navbar-text-color:
            <?= $navbarTextColor ?>
        ;
        --news-ticker-bg-color:
            <?= $newsTickerBgColor ?>
        ;
        --news-ticker-text-color:
            <?= $newsTickerTextColor ?>
        ;
        --news-ticker-title-bg-color:
            <?= $newsTickerTitleBgColor ?>
        ;
    }

    .navbar-desktop {
        background-color: var(--navbar-bg-color);
        position: relative;
        z-index: 999;
        padding: 6px 0;
    }

    .desktop-menu-container {
        display: flex;
        justify-content: center;
        align-items: center;
        overflow: visible;
        white-space: nowrap;
        gap: 35px;
    }

    .desktop-menu-item {
        position: relative;
        display: inline-block;
        color: var(--navbar-text-color);
        text-decoration: none;
        padding: 10px 15px;
        font-size: 20px;
        transition: background-color 0.3s;
    }

    .desktop-menu-item:hover {
        background-color: rgba(255, 255, 255, 0.2);
        border-radius: 4px;
    }

    .navbar-mobile-container {
        display: none;
        position: relative;
        background-color: var(--navbar-bg-color);
    }

    .mobile-slide-out-menu {
        background-color: var(--navbar-bg-color);
    }

    .mobile-slide-out-menu a {
        color: var(--navbar-text-color);
    }

    #navbar-news {
        position: relative;
        z-index: 998;
        display:
            <?= $newsTickerDisplay ?>
        ;
    }

    .news-ticker {
        position: relative;
        background-color: var(--news-ticker-bg-color);
        color: var(--news-ticker-text-color);
        font-weight: bold;
        z-index: 998;
        white-space: nowrap;
        font-size: 24px;
        border-radius: 0px 70px 10px 0px;
        display: flex;
        align-items: center;
        padding: 5px 0px;
    }

    .text-ticker {
        background-color: var(--news-ticker-title-bg-color);
        color: var(--navbar-text-color);
        padding: 30px 20px;
        border-radius: 0px 70px 10px 0px;
        line-height: 1;
    }

    #newsMarquee {
        flex-grow: 1;
        color: var(--news-ticker-text-color);
        padding: 0 10px;
    }

    a {
        color: var(--navbar-text-color);
    }

    a.desktop-menu-item {
        color: var(--navbar-text-color);
    }

    .desktop-dropdown-content a {
        color: #565656;
    }
</style>
<div class="navbar-desktop">
    <div class="container">
        <div class="desktop-menu-container">
            <?php foreach ($activeNavbarItems as $item): ?>
                <?php if (isset($item['isDropdown']) && $item['isDropdown']): ?>
                    <div class="desktop-menu-item" style="text-decoration: none;">
                        <a href="<?php echo $item['link']; ?>" style="text-decoration: none; color:#fff;">
                            <span data-translate="<?php echo $item['translate']; ?>" lang="<?= $lang ?>"
                                style="text-decoration: none;">
                                <?php echo $item['text']; ?>
                            </span>
                            <span class="dropdown-icon" style="text-decoration: none;">
                                <i class="fas fa-caret-down"></i>
                            </span>
                        </a>
                        <div class="desktop-dropdown-content">
                            <?php if (isset($dropdownItems[$item['id']][$lang])): ?>
                                <?php foreach ($dropdownItems[$item['id']][$lang] as $dropdownItem): ?>
                                    <a href="<?php echo $dropdownItem['link']; ?>">
                                        <i class="<?php echo $dropdownItem['icon']; ?>"></i>
                                        <span data-translate="<?php echo $dropdownItem['translate']; ?>" lang="<?= $lang ?>">
                                            <?php echo $dropdownItem['text']; ?>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?php echo $item['link']; ?>" class="desktop-menu-item">
                        <i class="<?php echo $item['icon']; ?>"></i>
                        <span data-translate="<?php echo $item['translate']; ?>" lang="<?= $lang ?>">
                            <?php echo $item['text']; ?>
                        </span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<div class="navbar-mobile-container" style="padding:15px;">

</div>

<script>
    const mobileMenu = document.getElementById("mobileMenu");
    const hamburger = document.querySelector(".hamburger");

    function toggleMobileNav() {
        mobileMenu.classList.toggle("open");
    }

    function toggleMobileDropdown(id) {
        const dropdown = document.getElementById(id + "_mobile");
        if (dropdown) {
            dropdown.classList.toggle("show");
        }
    }

    document.addEventListener('click', function (event) {
        const isClickInsideMenu = mobileMenu ? mobileMenu.contains(event.target) : false;
        const isClickOnHamburger = hamburger ? hamburger.contains(event.target) : false;
        const closeBtn = document.querySelector(".close-btn");

        if (mobileMenu && mobileMenu.classList.contains("open") && !isClickInsideMenu && !isClickOnHamburger && (closeBtn ? !closeBtn.contains(event.target) : true)) {
            toggleMobileNav();
        }
    });
</script>

<div id="navbar-news">
    <div style="margin-left:5%;">
        <div class="news-ticker">
            <span class="text-ticker">
                <span class="blinking-icon"></span>
                <?php
                $newsText = [
                    'th' => 'ข่าวประจำวัน',
                    'en' => 'Daily News',
                    'cn' => '每日新闻',
                    'jp' => 'デイリーニュース',
                    'kr' => '일일 뉴스'
                ];
                echo $newsText[$lang] ?? 'ข่าวประจำวัน';
                ?>
            </span>
            <marquee id="newsMarquee" scrollamount="4" behavior="scroll" direction="left" onmouseover="this.stop();"
                onmouseout="this.start();">
                <div id="newsMarquee-link" style="display: inline;">
                    <?php foreach ($newsList as $news): ?>
                        <span style="padding: 0 50px;">
                            <a href="news.php?id=<?= $news['news_id'] ?>&lang=<?= $lang ?>"
                                style="text-decoration: none; color: var(--news-ticker-text-color);">
                                <?= htmlspecialchars($news[$subjectColumn]) ?>
                            </a>
                        </span>
                    <?php endforeach; ?>
                </div>
            </marquee>
        </div>
    </div>
</div>