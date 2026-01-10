<?php
session_start();
$lang = 'th';
require_once('lib/connect.php');

if (isset($_GET['lang'])) {
    $supportedLangs = ['th', 'en', 'cn', 'jp', 'kr'];
    $newLang = $_GET['lang'];
    if (in_array($newLang, $supportedLangs)) {
        $_SESSION['lang'] = $newLang;
        $lang = $newLang;
    } else {
        unset($_SESSION['lang']);
    }
} elseif (isset($_SESSION['lang'])) {
    $lang = $_SESSION['lang'];
}

$contentTranslations = [
    'instructions_title' => [
        'th' => 'คำแนะนำ',
        'en' => 'Instructions',
        'cn' => '使用指南',
        'jp' => 'ご利用の手順',
        'kr' => '사용 지침'
    ],
    'software_subtitle' => [
        'th' => 'ซอฟต์แวร์ทำนายการกันเสียง',
        'en' => 'Software Sound Insulation Prediction',
        'cn' => '隔音预测软件',
        'jp' => '遮音予測ソフトウェア',
        'kr' => '차음 예측 소프트웨어'
    ],
    'link_win' => [
        'th' => 'ติดตั้งเวอร์ชัน 10.0.6 (Windows)',
        'en' => 'Installing Ver. 10.0.6 (Windows)',
        'cn' => '安装版本 10.0.6 (Windows)',
        'jp' => 'バージョン 10.0.6 (Windows) のインストール',
        'kr' => '버전 10.0.6 (Windows) 설치'
    ],
    'link_mac' => [
        'th' => 'ติดตั้งเวอร์ชัน 10.0.6 (Mac OSX)',
        'en' => 'Installing Ver. 10.0.6 (Mac OSX)',
        'cn' => '安装版本 10.0.6 (Mac OSX)',
        'jp' => 'バージョン 10.0.6 (Mac OSX) のインストール',
        'kr' => '버전 10.0.6 (Mac OSX) 설치'
    ],
    'link_update' => [
        'th' => 'การอัปเดตคีย์',
        'en' => 'Updating key',
        'cn' => '更新密钥',
        'jp' => 'キーの更新',
        'kr' => '키 업데이트'
    ],
    'install_title' => [
        'th' => 'คำแนะนำในการติดตั้งซอฟต์แวร์ INSUL',
        'en' => 'Instructions for INSUL Software Installation',
        'cn' => 'INSUL 软件安装指南',
        'jp' => 'INSULソフトウェアのインストール手順',
        'kr' => 'INSUL 소프트웨어 설치 지침'
    ],
    'install_pre_text' => [
        'th' => 'ก่อนการติดตั้งซอฟต์แวร์ INSUL ขอแนะนำให้ท่านปิดแอปพลิเคชันอื่นๆ ที่เปิดอยู่ทั้งหมด และเข้าสู่ระบบด้วยสิทธิ์ผู้ดูแลระบบ (administrator rights)',
        'en' => 'Before installing the INSUL software, we recommend that you close any other open applications and log in with administrator rights.',
        'cn' => '在安装 INSUL 软件之前，我们建议您关闭所有其他正在运行的应用程序，并使用管理员权限登录。',
        'jp' => 'INSULソフトウェアをインストールする前に、他の開いているアプリケーションをすべて閉じ、管理者権限でログインすることをお勧めします。',
        'kr' => 'INSUL 소프트웨어를 설치하기 전에 다른 열려 있는 응용 프로그램을 모두 닫고 관리자 권한으로 로그인하는 것이 좋습니다.'
    ],
    'how_to_install' => [
        'th' => 'วิธีการติดตั้งซอฟต์แวร์ INSUL',
        'en' => 'How to Install the INSUL Software',
        'cn' => '如何安装 INSUL 软件',
        'jp' => 'INSULソフトウェアのインストール方法',
        'kr' => 'INSUL 소프트웨어 설치 방법'
    ],
    'step1' => [
        'th' => 'เสียบ USB ที่ท่านได้รับซึ่งมีซอฟต์แวร์ที่จำเป็น โดยเฉพาะอย่างยิ่งไดรเวอร์ Sentinel® USB ซึ่งต้องติดตั้งก่อนใช้งาน',
        'en' => 'Insert the USB you received which contains the essential software, particularly the Sentinel® USB driver, which must be installed before use.',
        'cn' => '插入您收到的 USB，其中包含必要的软件，特别是 Sentinel® USB 驱动程序，该驱动程序必须在使用前安装。',
        'jp' => '受け取ったUSBを挿入してください。これには、使用前にインストールする必要があるSentinel® USBドライバーを含む、必須のソフトウェアが含まれています。',
        'kr' => '사용 전에 반드시 설치해야 하는 필수 소프트웨어, 특히 Sentinel® USB 드라이버가 포함된 USB를 삽입하십시오.'
    ],
    'step2' => [
        'th' => 'หากการติดตั้งไม่เริ่มโดยอัตโนมัติ ให้ดาวน์โหลดไฟล์',
        'en' => 'If the installation does not start automatically, download the',
        'cn' => '如果安装没有自动开始，请从以下地址下载',
        'jp' => 'インストールが自動的に開始しない場合は、',
        'kr' => '설치가 자동으로 시작되지 않으면,'
    ],
    'step2_link_text' => [
        'th' => 'จากเว็บไซต์ perfume เพื่อติดตั้งไดรเวอร์ด้วยตนเอง',
        'en' => 'file from the perfume website to manually install the driver.',
        'cn' => '文件以手动安装驱动程序。',
        'jp' => 'ファイルをダウンロードして、手動でドライバーをインストールしてください。',
        'kr' => '파일을 다운로드하여 드라이버를 수동으로 설치하십시오.'
    ],
    'step3' => [
        'th' => 'ไปที่เว็บไซต์ INSUL เพื่อดาวน์โหลดไฟล์ติดตั้งเวอร์ชันล่าสุดโดยคลิก',
        'en' => 'Go to the INSUL website to download the latest version of the installation file by clicking',
        'cn' => '请访问 INSUL 网站，点击',
        'jp' => 'INSULのウェブサイトにアクセスし、最新バージョンのインストールファイルをダウンロードするために、',
        'kr' => 'INSUL 웹사이트로 이동하여 최신 버전 설치 파일을 다운로드하려면 다음을 클릭하십시오.'
    ],
    'step3_link_text' => [
        'th' => 'ดาวน์โหลดเวอร์ชัน INSUL 9.0',
        'en' => 'download Version INSUL 9.0',
        'cn' => '下载 INSUL 9.0 版本',
        'jp' => 'バージョンINSUL 9.0をダウンロード',
        'kr' => 'INSUL 9.0 버전 다운로드'
    ],
    'step4' => [
        'th' => 'อ่านคำแนะนำบนเว็บไซต์และดับเบิลคลิกไฟล์ "Setup.exe" เพื่อเริ่มการติดตั้ง',
        'en' => 'Read the instructions on the website and double-click the "Setup.exe" file to begin the installation.',
        'cn' => '阅读网站上的说明，然后双击 "Setup.exe" 文件开始安装。',
        'jp' => 'ウェブサイトの指示を読み、「Setup.exe」ファイルをダブルクリックしてインストールを開始してください。',
        'kr' => '웹사이트의 지침을 읽고 "Setup.exe" 파일을 두 번 클릭하여 설치를 시작하십시오.'
    ]
];

function getTextByLang($key) {
    global $contentTranslations, $lang;
    return $contentTranslations[$key][$lang] ?? $contentTranslations[$key]['th'];
}
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <?php include 'template/header.php' ?>
    <?php include 'inc_head.php'?>
    <link href="app/css/index_.css?v=<?php echo time();?>" rel="stylesheet">
</head>
<body>
     <ul id="flag-dropdown-list" class="flag-dropdown" style="left: 74%;">
        </ul>
    <?php include 'template/navbar_slide.php'?>

    <div class="container my-5" style="color: #555; max-width: 90%;">
        <h2 class="text-center fw-bold mb-4"><?= getTextByLang('instructions_title') ?></h2>
        <h4 class="text-center mb-4"><?= getTextByLang('software_subtitle') ?></h4>

        <div class="mb-4">
            <p><a href="https://www.insul.co.nz/media/45381/insul10-setup-signed.msi" class="text-primary"><?= getTextByLang('link_win') ?></a></p>
            <p><a href="https://www.insul.co.nz/media/45384/Insul1006.dmg" class="text-primary"><?= getTextByLang('link_mac') ?></a></p>
            <p><a href="https://www.perfume.com/wp-content/uploads/2021/05/Instructions-for-Upgrading-the-key-RemoteUtility.pdf" class="text-primary"><?= getTextByLang('link_update') ?></a></p>
        </div>

        <hr>

        <h5 class="fw-bold mt-4"><?= getTextByLang('install_title') ?></h5>
        <p><?= getTextByLang('install_pre_text') ?></p>

        <p><strong><?= getTextByLang('how_to_install') ?></strong></p>
        <ol>
            <li>
                <?= getTextByLang('step1') ?>
            </li>
            <li>
                <?= getTextByLang('step2') ?> <a href="https://www.perfume.com/wp-content/uploads/2021/05/HASPUserSetup-1.zip" class="text-primary">HASPUserSetup.exe</a> <?= getTextByLang('step2_link_text') ?>
            </li>
            <li>
                <?= getTextByLang('step3') ?> <a href="https://www.perfume.com/wp-content/uploads/2021/05/Update90r23.zip" class="text-primary"><?= getTextByLang('step3_link_text') ?></a>
            </li>
            <li>
                <?= getTextByLang('step4') ?>
            </li>
        </ol>
    </div>

    <?php include 'template/footer.php'?>
    <script src="app/js/news/news_.js?v=<?php echo time(); ?>"></script>

    <script src="index_.js?v=<?php echo time();?>"></script>
</body>
</html>