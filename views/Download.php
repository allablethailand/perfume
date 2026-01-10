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
    'windows_title' => [
        'th' => 'Insul สำหรับ Windows',
        'en' => 'Insul for Windows',
        'cn' => '适用于 Windows 的 Insul',
        'jp' => 'Windows用Insul',
        'kr' => 'Windows용 Insul'
    ],
    'windows_trial_text' => [
        'th' => 'ขณะนี้ใบอนุญาตทดลองใช้มีให้บริการเฉพาะสำหรับ Windows เท่านั้น',
        'en' => 'Trial licenses are currently only available for Windows.',
        'cn' => '试用许可证目前仅适用于 Windows。',
        'jp' => '現在、試用版ライセンスはWindowsのみでご利用いただけます。',
        'kr' => '현재 체험판 라이선스는 Windows에서만 사용할 수 있습니다.'
    ],
    'table_header_file' => [
        'th' => 'ไฟล์',
        'en' => 'File',
        'cn' => '文件',
        'jp' => 'ファイル',
        'kr' => '파일'
    ],
    'windows_download_text1' => [
        'th' => 'INSUL เวอร์ชัน 10.0.6 (Windows, ติดตั้งพื้นฐาน 10.0.6)',
        'en' => 'INSUL version 10.0.6 (Windows, base install 10.0.6)',
        'cn' => 'INSUL 10.0.6 版本 (Windows, 基础安装 10.0.6)',
        'jp' => 'INSUL バージョン 10.0.6 (Windows, ベースインストール 10.0.6)',
        'kr' => 'INSUL 버전 10.0.6 (Windows, 기본 설치 10.0.6)'
    ],
    'download_link' => [
        'th' => 'ดาวน์โหลด',
        'en' => 'Download',
        'cn' => '下载',
        'jp' => 'ダウンロード',
        'kr' => '다운로드'
    ],
    'windows_download_text2' => [
        'th' => 'อัปเดต 10.0.6 (Windows)',
        'en' => 'Update 10.0.6 (Windows)',
        'cn' => '更新 10.0.6 (Windows)',
        'jp' => 'アップデート 10.0.6 (Windows)',
        'kr' => '업데이트 10.0.6 (Windows)'
    ],
    'windows_info_text' => [
        'th' => 'การติดตั้งสำหรับ Windows รองรับ Windows 7, 8 และ 10 ฟีเจอร์บางอย่างไม่สามารถใช้งานได้บน Windows 7 และ 8 (เช่น การสร้างไฟล์ PDF ในตัว) สำหรับการติดตั้ง INSUL บน Windows ที่มีอยู่แล้ว สามารถอัปเดตได้โดยใช้ปุ่ม "ตรวจสอบการอัปเดต" (Check for Updates) บนหน้าต่าง About ของ INSUL หรือใช้ลิงก์ด้านบน หากต้องการใช้งาน INSUL ในโหมดทดลองใช้ (เฉพาะ Windows) โปรดติดต่อตัวแทนจำหน่ายในพื้นที่ของท่านเพื่อขอรับคีย์ใบอนุญาตชั่วคราว',
        'en' => 'Windows installations support Windows 7, 8 and 10. Certain features are not available on Windows 7 and 8 (e.g. in-built PDF generation). For existing Windows INSUL installations, update using the “Check for Updates” button on the INSUL About window or use the link above. To run INSUL in trial mode (Windows only), contact your local distributor to obtain a temporary license key.',
        'cn' => 'Windows 安装支持 Windows 7、8 和 10。某些功能在 Windows 7 和 8 上不可用（例如，内置的 PDF 生成）。对于现有的 Windows INSUL 安装，可以使用 INSUL “关于” 窗口上的“检查更新”按钮或使用上面的链接进行更新。要在试用模式下运行 INSUL（仅限 Windows），请联系您当地的经销商以获取临时许可证密钥。',
        'jp' => 'Windows版は、Windows 7、8、および10をサポートしています。Windows 7および8では、一部の機能（例：内蔵PDF生成）は利用できません。既存のWindows版INSULを更新するには、INSULの「About」ウィンドウにある「Check for Updates」ボタンを使用するか、上記のリンクを使用してください。INSULを試用版モードで実行するには（Windowsのみ）、お近くの販売代理店に連絡して一時的なライセンスキーを入手してください。',
        'kr' => 'Windows 설치는 Windows 7, 8 및 10을 지원합니다. 특정 기능은 Windows 7 및 8에서 사용할 수 없습니다(예: 내장 PDF 생성). 기존 Windows INSUL 설치의 경우, INSUL "정보" 창의 "업데이트 확인" 버튼을 사용하거나 위 링크를 사용하여 업데이트하십시오. INSUL을 체험판 모드(Windows만 해당)로 실행하려면 지역 대리점에 연락하여 임시 라이선스 키를 받으십시오.'
    ],
    'mac_title' => [
        'th' => 'Insul สำหรับ Mac',
        'en' => 'Insul for Mac',
        'cn' => '适用于 Mac 的 Insul',
        'jp' => 'Mac用Insul',
        'kr' => 'Mac용 Insul'
    ],
    'mac_trial_text' => [
        'th' => 'ขณะนี้ใบอนุญาตทดลองใช้ยังไม่มีให้บริการสำหรับ Mac',
        'en' => 'Trial licenses are not currently available on Mac.',
        'cn' => '试用许可证目前不适用于 Mac。',
        'jp' => '現在、Macでは試用版ライセンスはご利用いただけません。',
        'kr' => '현재 Mac에서는 체험판 라이선스를 사용할 수 없습니다.'
    ],
    'mac_download_text1' => [
        'th' => 'INSUL เวอร์ชัน 10.0.6 (Mac OSX, ติดตั้งแบบเต็ม)',
        'en' => 'INSUL version 10.0.6 (Mac OSX, full install)',
        'cn' => 'INSUL 10.0.6 版本 (Mac OSX, 完整安装)',
        'jp' => 'INSUL バージョン 10.0.6 (Mac OSX, 完全インストール)',
        'kr' => 'INSUL 버전 10.0.6 (Mac OSX, 전체 설치)'
    ],
    'mac_download_text2' => [
        'th' => 'INSUL เวอร์ชัน 10.0.6 (HFS) (สำหรับ MacOS 10.12 Sierra และเวอร์ชันก่อนหน้า)',
        'en' => 'INSUL version 10.0.6 (HFS) (For MacOS 10.12 Sierra and earlier)',
        'cn' => 'INSUL 10.0.6 版本 (HFS) (适用于 MacOS 10.12 Sierra 及更早版本)',
        'jp' => 'INSUL バージョン 10.0.6 (HFS) (MacOS 10.12 Sierra以前用)',
        'kr' => 'INSUL 버전 10.0.6 (HFS) (MacOS 10.12 Sierra 및 이전 버전용)'
    ],
    'hasp_drivers_title' => [
        'th' => 'ไดรเวอร์คีย์ HASP',
        'en' => 'HASP key Drivers',
        'cn' => 'HASP 密钥驱动程序',
        'jp' => 'HASPキードライバー',
        'kr' => 'HASP 키 드라이버'
    ],
    'driver_win' => [
        'th' => 'ไดรเวอร์ Sentinel Key สำหรับ Windows',
        'en' => 'Sentinel Key driver for Windows',
        'cn' => '适用于 Windows 的 Sentinel 密钥驱动程序',
        'jp' => 'Windows用Sentinelキードライバー',
        'kr' => 'Windows용 Sentinel 키 드라이버'
    ],
    'driver_mac' => [
        'th' => 'ไดรเวอร์ Sentinel Key สำหรับ Mac',
        'en' => 'Sentinel Key driver for Mac',
        'cn' => '适用于 Mac 的 Sentinel 密钥驱动程序',
        'jp' => 'Mac用Sentinelキードライバー',
        'kr' => 'Mac용 Sentinel 키 드라이버'
    ],
    'update_instructions' => [
        'th' => 'คำแนะนำสำหรับการอัปเกรดคีย์ของคุณ',
        'en' => 'Instructions for upgrading your key',
        'cn' => '升级密钥的说明',
        'jp' => 'キーのアップグレード手順',
        'kr' => '키 업그레이드 지침'
    ],
    'remote_utility' => [
        'th' => 'ยูทิลิตีอัปเดตรีโมท (Windows)',
        'en' => 'Remote Update Utility (Windows)',
        'cn' => '远程更新实用程序 (Windows)',
        'jp' => 'リモートアップデートユーティリティ (Windows)',
        'kr' => '원격 업데이트 유틸리티 (Windows)'
    ],
    'documents_title' => [
        'th' => 'เอกสาร',
        'en' => 'Documents',
        'cn' => '文件',
        'jp' => 'ドキュメント',
        'kr' => '문서'
    ],
    'doc_release_notes' => [
        'th' => 'บันทึกการเปิดตัว INSUL เวอร์ชัน 9.0',
        'en' => 'INSUL version 9.0 Release notes',
        'cn' => 'INSUL 9.0 版本发布说明',
        'jp' => 'INSUL バージョン 9.0 リリースノート',
        'kr' => 'INSUL 버전 9.0 릴리스 노트'
    ],
    'doc_manual' => [
        'th' => 'คู่มือ INSUL เวอร์ชัน 9.0',
        'en' => 'INSUL version 9.0 Manual',
        'cn' => 'INSUL 9.0 版本手册',
        'jp' => 'INSUL バージョン 9.0 マニュアル',
        'kr' => 'INSUL 버전 9.0 매뉴얼'
    ],
    'doc_trial_install' => [
        'th' => 'คำแนะนำในการติดตั้งเวอร์ชันทดลองใช้ 9.0 (Windows)',
        'en' => 'Instructions for installing a trial of version 9.0 (Windows)',
        'cn' => '安装 9.0 试用版（Windows）的说明',
        'jp' => '試用版バージョン 9.0 (Windows) のインストール手順',
        'kr' => '버전 9.0 체험판 설치 지침 (Windows)'
    ],
    'doc_single_user_install_win' => [
        'th' => 'คำแนะนำในการติดตั้งเวอร์ชันผู้ใช้เดียว 9.0 (Windows)',
        'en' => 'Instructions for installing single user version 9.0 (Windows)',
        'cn' => '安装 9.0 单用户版（Windows）的说明',
        'jp' => 'シングルユーザー版バージョン 9.0 (Windows) のインストール手順',
        'kr' => '단일 사용자 버전 9.0 설치 지침 (Windows)'
    ],
    'doc_single_user_install_mac' => [
        'th' => 'คำแนะนำในการติดตั้งเวอร์ชันผู้ใช้เดียว 9.0 (Mac OSX)',
        'en' => 'Instructions for Installing single user version 9.0 (Mac OSX)',
        'cn' => '安装 9.0 单用户版（Mac OSX）的说明',
        'jp' => 'シングルユーザー版バージョン 9.0 (Mac OSX) のインストール手順',
        'kr' => '단일 사용자 버전 9.0 설치 지침 (Mac OSX)'
    ],
    'doc_upgrade_8_to_9' => [
        'th' => 'คำแนะนำในการอัปเกรดจากเวอร์ชัน 8.0 (Windows)',
        'en' => 'Instructions for upgrading from version 8.0 (Windows)',
        'cn' => '从 8.0 版本（Windows）升级的说明',
        'jp' => 'バージョン 8.0 (Windows) からのアップグレード手順',
        'kr' => '버전 8.0에서 업그레이드 지침 (Windows)'
    ],
    'doc_network_install_win' => [
        'th' => 'คำแนะนำในการติดตั้งเครือข่าย (Windows)',
        'en' => 'Instructions for Network Installation (Windows)',
        'cn' => '网络安装说明 (Windows)',
        'jp' => 'ネットワークインストール手順 (Windows)',
        'kr' => '네트워크 설치 지침 (Windows)'
    ],
    'previous_versions_title' => [
        'th' => 'เวอร์ชันก่อนหน้า',
        'en' => 'Previous versions',
        'cn' => '旧版本',
        'jp' => '以前のバージョン',
        'kr' => '이전 버전'
    ],
    'prev_win_update' => [
        'th' => 'อัปเดต INSUL 9.0.23 (Windows, เฉพาะการอัปเดต, ไฟล์ zip)',
        'en' => 'INSUL update 9.0.23 (Windows, update only, zip file)',
        'cn' => 'INSUL 9.0.23 更新 (Windows, 仅更新, zip 文件)',
        'jp' => 'INSULアップデート 9.0.23 (Windows, アップデートのみ, zipファイル)',
        'kr' => 'INSUL 업데이트 9.0.23 (Windows, 업데이트 전용, zip 파일)'
    ],
    'prev_mac_hfs' => [
        'th' => 'INSUL 9.0.22 (HFS) (สำหรับ MacOS 10.12 Sierra และเวอร์ชันก่อนหน้า)',
        'en' => 'INSUL 9.0.22 (HFS) (For MacOS 10.12 Sierra and earlier)',
        'cn' => 'INSUL 9.0.22 (HFS) (适用于 MacOS 10.12 Sierra 及更早版本)',
        'jp' => 'INSUL 9.0.22 (HFS) (MacOS 10.12 Sierra以前用)',
        'kr' => 'INSUL 9.0.22 (HFS) (MacOS 10.12 Sierra 및 이전 버전용)'
    ],
];

function getTextByLang($key)
{
    global $contentTranslations, $lang;
    return $contentTranslations[$key][$lang] ?? $contentTranslations[$key]['th'];
}

?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">

<head>
    <?php include 'template/header.php' ?>
    <?php include 'inc_head.php' ?>
    <link href="app/css/index_.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>

<body>
    <ul id="flag-dropdown-list" class="flag-dropdown" style="left: 74%;">
    </ul>
    <?php include 'template/navbar_slide.php' ?>

    <div class="container my-5" style="color: #555; max-width: 90%;">
        <h4><i class="bi bi-pc-display-horizontal text-danger"></i> <span
                class="text-danger"><?= getTextByLang('windows_title') ?></span></h4>
        <p><?= getTextByLang('windows_trial_text') ?></p>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th style="color: #555;"><?= getTextByLang('table_header_file') ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <span>insul10.0.6</span>
                        <br>

                        <a href="https://www.perfume.com/uploads/insul10.0.6.msi"
                            class="text-primary"><?= getTextByLang('download_link') ?></a>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span>setup9.0.24</span><br>

                        <a href="https://www.perfume.com/uploads/setup9.0.24.exe"
                            class="text-primary"><?= getTextByLang('download_link') ?></a>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span>setup8.0.12</span><br>

                        <a href="https://www.perfume.com/uploads/setup8.0.12.exe"
                            class="text-primary"><?= getTextByLang('download_link') ?></a>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span>setup7.0.13</span><br>

                        <a href="https://www.perfume.com/uploads/setup7.0.13.exe"
                            class="text-primary"><?= getTextByLang('download_link') ?></a>
                    </td>
                </tr>
            </tbody>
        </table>
        <p><?= getTextByLang('windows_info_text') ?></p>

        <hr>

        <h4><i class="bi bi-apple text-danger"></i> <span class="text-danger"><?= getTextByLang('mac_title') ?></span>
        </h4>
        <p><?= getTextByLang('mac_trial_text') ?></p>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th style="color: #555;"><?= getTextByLang('table_header_file') ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <?= getTextByLang('mac_download_text1') ?><br>
                        <a href="https://www.insul.co.nz/media/45384/Insul1006.dmg"
                            class="text-primary"><?= getTextByLang('download_link') ?></a>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= getTextByLang('mac_download_text2') ?><br>
                        <a href="https://www.insul.co.nz/media/45387/Insul1006HFS.dmg"
                            class="text-primary"><?= getTextByLang('download_link') ?></a>
                    </td>
                </tr>
            </tbody>
        </table>


        <h4><i class="bi bi-apple text-danger"></i> <span
                class="text-danger"><?= getTextByLang('hasp_drivers_title') ?></span></h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th style="color: #555;"><?= getTextByLang('table_header_file') ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <?= getTextByLang('driver_win') ?><br>
                        <a href="https://www.perfume.com/wp-content/uploads/2021/05/HASPUserSetup.zip"
                            class="text-primary"><?= getTextByLang('download_link') ?></a>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= getTextByLang('driver_mac') ?><br>
                        <a href="https://www.perfume.com/wp-content/uploads/2021/05/Sentinel_Runtime.zip"
                            class="text-primary"><?= getTextByLang('download_link') ?></a>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= getTextByLang('update_instructions') ?><br>
                        <a href="https://www.perfume.com/wp-content/uploads/2021/05/Instructions-for-Upgrading-the-key-RemoteUtility.pdf"
                            class="text-primary"><?= getTextByLang('download_link') ?></a>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= getTextByLang('remote_utility') ?><br>
                        <a href="https://www.perfume.com/wp-content/uploads/2021/05/MDARemoteUpDateUtilityV6-3.zip"
                            class="text-primary"><?= getTextByLang('download_link') ?></a>
                    </td>
                </tr>
            </tbody>
        </table>

        <h4><i class="bi bi-file-earmark-text text-danger"></i> <span
                class="text-danger"><?= getTextByLang('documents_title') ?></span></h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th style="color: #555;"><?= getTextByLang('table_header_file') ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <?= getTextByLang('doc_release_notes') ?><br>
                        <a href="https://www.perfume.com/wp-content/uploads/2021/05/Insul_notes.pdf"
                            class="text-primary"><?= getTextByLang('download_link') ?></a>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= getTextByLang('doc_manual') ?><br>
                        <a href="https://www.perfume.com/wp-content/uploads/2021/05/Insul-Manual-2017-word-version.pdf"
                            class="text-primary"><?= getTextByLang('download_link') ?></a>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= getTextByLang('doc_trial_install') ?><br>
                        <a href="https://www.perfume.com/wp-content/uploads/2021/05/Install-Instructions-for-INSUL-TrialVersion.pdf"
                            class="text-primary"><?= getTextByLang('download_link') ?></a>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= getTextByLang('doc_single_user_install_win') ?><br>
                        <a href="https://www.perfume.com/wp-content/uploads/2021/05/Install-Instructions-for-v90-From-Web.pdf"
                            class="text-primary"><?= getTextByLang('download_link') ?></a>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= getTextByLang('doc_single_user_install_mac') ?><br>
                        <a href="https://www.perfume.com/wp-content/uploads/2021/05/Install-Instructions-for-v90-From-Web-OSX-.pdf"
                            class="text-primary"><?= getTextByLang('download_link') ?></a>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= getTextByLang('doc_upgrade_8_to_9') ?><br>
                        <a href="https://www.perfume.com/wp-content/uploads/2021/05/Instructions-for-Upgrading-from-Version-8-to-9.pdf"
                            class="text-primary"><?= getTextByLang('download_link') ?></a>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= getTextByLang('doc_network_install_win') ?><br>
                        <a href="https://www.perfume.com/wp-content/uploads/2021/05/InstallInstructionsForNetworkVersion9-0.pdf"
                            class="text-primary"><?= getTextByLang('download_link') ?></a>
                    </td>
                </tr>
            </tbody>
        </table>

        <h4><i class="bi bi-apple text-danger"></i> <span
                class="text-danger"><?= getTextByLang('previous_versions_title') ?></span></h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th style="color: #555;"><?= getTextByLang('table_header_file') ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <?= getTextByLang('prev_win_update') ?><br>
                        <a href="https://www.perfume.com/wp-content/uploads/2021/05/Update90r23-1.zip"
                            class="text-primary"><?= getTextByLang('download_link') ?></a>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= getTextByLang('prev_mac_hfs') ?><br>
                        <a href="https://www.perfume.com/wp-content/uploads/2021/05/Insul9022HFS.zip"
                            class="text-primary"><?= getTextByLang('download_link') ?></a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <?php include 'template/footer.php' ?>

    <script src="index_.js?v=<?php echo time(); ?>"></script>
</body>

</html>