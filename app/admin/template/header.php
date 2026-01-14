<?php
// เช็คสิทธิ์การเข้าถึง Admin ก่อนทำอะไรทั้งหมด
require_once $_SERVER['DOCUMENT_ROOT'] . '/perfume/app/admin/check_admin_permission.php';

// ต้องแน่ใจว่าได้เปิดใช้งาน Session ก่อนการแสดงผลใดๆ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// กำหนดภาษาเริ่มต้นเป็น 'th' ถ้าไม่มีการระบุใน Session
$lang = $_SESSION['lang'] ?? 'th';

// ... โค้ดที่เหลือตามเดิม

// ตรวจสอบภาษาจาก URL
if (isset($_GET['lang'])) {
    $supportedLangs = ['th', 'en', 'cn', 'jp', 'kr'];
    $newLang = $_GET['lang'];
    if (in_array($newLang, $supportedLangs)) {
        // ถ้าเป็นภาษาที่รองรับ ให้บันทึกใน Session
        $_SESSION['lang'] = $newLang;
        $lang = $newLang;
    } else {
        // ถ้าเป็นค่าที่ไม่ถูกต้อง ให้ใช้ภาษาเริ่มต้น
        $lang = 'th';
    }
}


include $_SERVER['DOCUMENT_ROOT'] . '/origami_website/perfume/lib/connect.php';
include $_SERVER['DOCUMENT_ROOT'] . '/origami_website/perfume/lib/base_directory.php';

// กำหนด URL ของรูปโปรไฟล์เริ่มต้น
$default_profile_img = 'https://as1.ftcdn.net/jpg/01/12/09/12/1000_F_112091233_xghsriqmHzk4sq71lWBL4q0e7n9QJKX6.jpg';
$profile_img_src = $default_profile_img; // กำหนดค่าเริ่มต้นเป็นรูปภาพ default
if(isset($_SESSION['avatar'])) {
            $profile_img_src = $_SESSION['avatar'];
        } else {
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT profile_img FROM mb_user WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // ตรวจสอบว่า profile_img มีค่าหรือไม่ ถ้ามีให้ใช้ path นั้น
            if (!empty($row['profile_img'])) {
                $profile_img_src = $new_path . 'public/img/' . htmlspecialchars($row['profile_img']);
            }
        
    }
}
        }

if (!isset($base_path_admin)) {
    $base_path_admin = '/app/admin/';
}

// กำหนดข้อความสำหรับภาษาต่างๆ
$translations = [
    'profile' => [
        'th' => 'โปรไฟล์',
        'en' => 'Profile',
        'cn' => '个人资料',
        'jp' => 'プロフィール',
        'kr' => '프로필'
    ],
    'logout' => [
        'th' => 'ออกจากระบบ',
        'en' => 'Logout',
        'cn' => '登出',
        'jp' => 'ログアウト',
        'kr' => '로그아웃'
    ]
];

// ฟังก์ชันสำหรับดึงข้อความตามภาษา
function getTranslation($key, $lang, $translations) {
    return $translations[$key][$lang] ?? $translations[$key]['th'];
}
?>

<div class="header-topp">
    <div class="container-fluid">
        <div class="header-top-left">
            <span class="toggle-button" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </span>
            <a href="#"><img class="logo" src="<?php echo $new_path; ?>/public/product_images/696089dc2eba5_1767934428.jpg" alt=""></a>
        </div>

        <div class="header-top-right dropdown-wrapper">
            <div class="header-item">
                <i class="fas fa-bell" style="font-size: 30px;"></i>
            </div>

            <div class="header-item dropdown-parent" onclick="toggleDropdown('origamiDropdownMenu', event)">
                <img src="https://www.origami.life/images/menu/HUB.svg" height="35" style="cursor:pointer;">
                <div id="origamiDropdownMenu" class="dropdown-box hidden">
                    <a href="/app/admin/template/switch.php">
                        <img src="<?php echo $new_path; ?>public/img/2_20180425103337.ico" height="40"><br>
                        <span>Origami</span>
                    </a>
                </div>
            </div>

            <div>
                <select id="language-select" class="language-select"></select>
            </div>
            <div class="profile-container dropdown-parent" onclick="toggleDropdown('globalProfileDropdown', event)">
                <img src="<?php echo $profile_img_src; ?>" alt="Profile Picture" class="profile-pic">
                <div id="globalProfileDropdown" class="dropdown-box hidden">
                    <a href="<?php echo $path_admin; ?>profile.php?lang=<?php echo $lang; ?>">
                        <?php echo getTranslation('profile', $lang, $translations); ?>
                    </a>
                    <a href="<?php echo $new_path; ?>index.php?lang=<?php echo $lang; ?>">
                        <?php echo getTranslation('logout', $lang, $translations); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<input type="hidden" name="new_path" id="new_path" value="<?php echo $new_path; ?>">
<input type="hidden" name="path_admin" id="path_admin" value="<?php echo $path_admin; ?>">

<div id="showTabSidebar" class="row row-tab"></div>


<script>
function toggleSidebar() {
    document.body.classList.toggle('sidebar-open');
}

function toggleDropdown(id, event) {
    event.stopPropagation();
    document.querySelectorAll('.dropdown-box').forEach(el => {
        if (el.id !== id) el.classList.add('hidden');
    });
    const el = document.getElementById(id);
    el.classList.toggle("hidden");
}

document.addEventListener("click", function () {
    document.querySelectorAll('.dropdown-box').forEach(el => el.classList.add('hidden'));
});
</script>

<script>
function switchLanguage() {
    alert("เปลี่ยนภาษายังไม่กำหนดฟังก์ชัน (ไว้ใส่ redirect หรือ switch locale ได้ภายหลัง)");
}
</script>

<style>
/* CSS ตามเดิม */
.header-topp {
    overflow: hidden;
    background-color: #fafafafa;
    padding: 5px 10px;
    top: 0px;
    width: 100%;
    transition: top 0.3s;
    display: flex;
    justify-content: space-between;
    position: sticky;
    border-bottom: 1px solid #ececec;
    z-index: 10;
    overflow: visible;
}

.header-top-left,
.header-top-right {
    display: flex;
    align-items: center;
}

.toggle-button {
    margin-right: 10px;
    cursor: pointer;
    padding-left: 20px;
    font-size: 25px;
    font-weight: bold;
    color: #333;
}

.logo {
    height: 40px;
    padding-left: 2em;
}

.header-item {
    margin-left: 10px;
    position: relative;
    cursor: pointer;
}

.profile-container {
    margin-left: 0px;
    position: relative;
    cursor: pointer;
}

.profile-pic {
    width: 50px;
    height: 50px;
    border-radius: 50%;
}

.dropdown-wrapper {
    position: relative;
}

.dropdown-box {
    position: absolute;
    top: 100%;
    right: 0;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 0px 10px rgba(0, 0, 0, 0.15);
    z-index: 9999;
    padding: 12px 20px;
    display: flex;
    flex-direction: column;
    width: 150px;
}

#origamiDropdownMenu {
    flex-direction: row;
    min-width: 30px;
    justify-content: space-around;
}

.dropdown-box a {
    text-align: center;
    text-decoration: none;
    color: #333;
    font-size: 14px;
    margin: 5px 10px;
}

.dropdown-box img {
    display: block;
    margin: 0 auto 5px;
}

.dropdown-box a:hover {
    background-color: #f9f9f9;
    border-radius: 5px;
}

.hidden {
    display: none;
}

.lang-switcher img {
    border-radius: 4px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
    cursor: pointer;
    transition: transform 0.2s;
}

.lang-switcher img:hover {
    transform: scale(1.05);
}

.language-select-container {
    position: relative;
    display: inline-block;
}

.flag-icon {
    width: 24px;
    height: auto;
    cursor: pointer;
    border: 1px solid #ddd;
    border-radius: 2px;
}

.flag-dropdown {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    background-color: #f9f9f9;
    min-width: 120px;
    box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
    z-index: 1003;
    border-radius: 4px;
    padding: 5px 0;
}
.flag-dropdown a {
    color: black;
    padding: 8px 16px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 10px;
    white-space: nowrap;
}

.flag-dropdown a:hover {
    background-color: #f1f1f1;
}
</style>