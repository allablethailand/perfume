<?php 
include 'check_permission.php'; 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ต้องแน่ใจว่าไฟล์เหล่านี้ถูกเรียกก่อนการใช้งานตัวแปรเช่น $new_path
include $_SERVER['DOCUMENT_ROOT'] . '/perfume/lib/connect.php';
include $_SERVER['DOCUMENT_ROOT'] . '/perfume/lib/base_directory.php'; // กำหนด $new_path และอื่นๆ

// กำหนดภาษาเริ่มต้นเป็น 'th' หากไม่มีการกำหนดใน Session
$lang = $_SESSION['lang'] ?? 'th';

// ตรวจสอบภาษาจาก URL
if (isset($_GET['lang'])) {
    $supportedLangs = ['th', 'en', 'cn', 'jp', 'kr'];
    $newLang = $_GET['lang'];
    if (in_array($newLang, $supportedLangs)) {
        // ถ้าเป็นภาษาที่รองรับ ให้บันทึกใน Session
        $_SESSION['lang'] = $newLang;
        $lang = $newLang;
    } else {
        // ถ้าเป็นค่าที่ไม่ถูกต้อง ให้ล้างค่าใน Session เพื่อใช้ค่าเริ่มต้น
        unset($_SESSION['lang']);
        $lang = 'th';
    }
}

// กำหนดข้อความทั้งหมดใน 5 ภาษา
$translations = [
    'th' => [
        'title' => 'โปรไฟล์',
        'profile_title' => 'ข้อมูลส่วนตัว',
        'save_success_message' => 'บันทึกข้อมูลเรียบร้อยแล้ว',
        'change_photo_label' => 'เปลี่ยนรูปภาพ',
        'first_name_label' => 'ชื่อ',
        'last_name_label' => 'นามสกุล',
        'email_label' => 'อีเมล',
        'new_password_label' => 'รหัสผ่านใหม่',
        'confirm_password_label' => 'ยืนยันรหัสผ่านใหม่',
        'save_changes_button' => 'บันทึกการเปลี่ยนแปลง',
        'password_placeholder' => '********'
    ],
    'en' => [
        'title' => 'Profile',
        'profile_title' => 'Personal Information',
        'save_success_message' => 'Information saved successfully',
        'change_photo_label' => 'Change Photo',
        'first_name_label' => 'First Name',
        'last_name_label' => 'Last Name',
        'email_label' => 'Email',
        'new_password_label' => 'New Password',
        'confirm_password_label' => 'Confirm New Password',
        'save_changes_button' => 'Save Changes',
        'password_placeholder' => '********'
    ],
    'cn' => [
        'title' => '个人资料',
        'profile_title' => '个人信息',
        'save_success_message' => '信息已成功保存',
        'change_photo_label' => '更换照片',
        'first_name_label' => '名字',
        'last_name_label' => '姓氏',
        'email_label' => '电子邮件',
        'new_password_label' => '新密码',
        'confirm_password_label' => '确认新密码',
        'save_changes_button' => '保存更改',
        'password_placeholder' => '********'
    ],
    'jp' => [
        'title' => 'プロフィール',
        'profile_title' => '個人情報',
        'save_success_message' => '情報が正常に保存されました',
        'change_photo_label' => '写真を変更',
        'first_name_label' => '名',
        'last_name_label' => '姓',
        'email_label' => 'メールアドレス',
        'new_password_label' => '新しいパスワード',
        'confirm_password_label' => '新しいパスワードを確認',
        'save_changes_button' => '変更を保存',
        'password_placeholder' => '********'
    ],
    'kr' => [
        'title' => '프로필',
        'profile_title' => '개인 정보',
        'save_success_message' => '정보가 성공적으로 저장되었습니다',
        'change_photo_label' => '사진 변경',
        'first_name_label' => '이름',
        'last_name_label' => '성',
        'email_label' => '이메일',
        'new_password_label' => '새 비밀번호',
        'confirm_password_label' => '새 비밀번호 확인',
        'save_changes_button' => '변경 사항 저장',
        'password_placeholder' => '********'
    ]
];

// ใช้ภาษาที่เลือก (ค่าเริ่มต้นคือ 'th')
$currentLang = $translations[$lang];

?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
<link rel="icon" type="image/x-icon" href="../public/img/q-removebg-preview1.png">    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $currentLang['title'] ?></title>

    <?php include 'inc_head.php'; ?>
    <link href="css/index_.css?v=<?= time(); ?>" rel="stylesheet">
    <style>
        .dashboard-wrapper {
            padding: 30px;
        }
        .dashboard-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            background-color: #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            transition: 0.3s;
        }
        .dashboard-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .dashboard-card h3 {
            margin-bottom: 10px;
            font-size: 1.5rem;
        }
        .dashboard-card p {
            color: #666;
        }
        input[type="text"], input[type="email"], input[type="password"], input[type="file"] {
        width: 100%;
        padding: 8px;
        margin-bottom: 10px;
        }
        button {
        padding: 10px 20px;
        background-color: #007BFF;
        color: white;
        border: none;
        border-radius: 4px;
        }
    </style>
</head>
<body>

    <?php include 'template/header.php'; ?>
    <?php
    // include $_SERVER['DOCUMENT_ROOT'] . '/perfume/lib/connect.php'; // ย้ายไปด้านบนแล้ว
    // include $_SERVER['DOCUMENT_ROOT'] . '/perfume/lib/base_directory.php'; // ย้ายไปด้านบนแล้ว

    $user_id = $_SESSION['user_id']; // ต้องแน่ใจว่า login แล้ว

    $sql = "SELECT * FROM mb_user WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // กำหนด URL ของรูปโปรไฟล์เริ่มต้น
    $default_profile_img = 'https://as1.ftcdn.net/jpg/01/12/09/12/1000_F_112091233_xghsriqmHzk4sq71lWBL4q0e7n9QJKX6.jpg';

    // *** ส่วนที่แก้ไขเพื่อให้เหมือนกับใน header.php โดยใช้ $new_path ***
    $profile_img_src = $default_profile_img; // กำหนดค่าเริ่มต้นเป็น default
    
    // ตรวจสอบว่ามีรูปโปรไฟล์ในฐานข้อมูลหรือไม่
    if (!empty($user['profile_img'])) {
        // ใช้ $new_path ในการสร้าง URL เหมือนกับใน header.php
        $profile_img_src = $new_path . 'public/img/' . htmlspecialchars($user['profile_img']);
    }

    // หมายเหตุ: โค้ดใน header.php มีการดึงจาก $_SESSION['avatar'] ก่อนด้วย
    // ในหน้านี้เราดึงจาก DB โดยตรงซึ่งสมเหตุสมผลกว่าสำหรับหน้า Profile
    // ถ้าต้องการให้เหมือน 100% คือเพิ่มเงื่อนไขจาก $_SESSION['avatar'] ก่อน
    /*
    if(isset($_SESSION['avatar'])) {
        $profile_img_src = $_SESSION['avatar'];
    } else {
        //... logic ดึงจาก DB ตามด้านบน
    }
    */
    // แต่สำหรับหน้านี้ การดึงจาก DB ล่าสุดก็เพียงพอแล้ว
    // -----------------------------------------------------------------
    ?>
    <?php if (isset($_GET['updated'])): ?>
    <div style="text-align: center; color: green; font-weight: bold; margin-bottom: 20px;">
        ✅ <?= $currentLang['save_success_message'] ?>
    </div>
    <?php endif; ?>
    <div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h3 class="mb-4"><?= $currentLang['profile_title'] ?></h3>
                    <form action="update_profile.php" method="POST" enctype="multipart/form-data">
                        <img src="<?= $profile_img_src ?>" alt="รูปโปรไฟล์" class="rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                        <div class="form-group text-start">
                            <label for="profile_img"><?= $currentLang['change_photo_label'] ?>:</label>
                            <input type="file" name="profile_img" class="form-control">
                        </div>

                        <div class="form-group text-start mt-3">
                            <label><?= $currentLang['first_name_label'] ?>:</label>
                            <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>">
                        </div>

                        <div class="form-group text-start mt-3">
                            <label><?= $currentLang['last_name_label'] ?>:</label>
                            <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>">
                        </div>

                        <div class="form-group text-start mt-3">
                            <label><?= $currentLang['email_label'] ?>:</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>">
                        </div>

                        <div class="form-group text-start mt-3">
                            <label><?= $currentLang['new_password_label'] ?>:</label>
                            <input type="password" name="password" class="form-control" placeholder="<?= $currentLang['password_placeholder'] ?>">
                        </div>

                        <div class="form-group text-start mt-3">
                            <label><?= $currentLang['confirm_password_label'] ?>:</label>
                            <input type="password" name="password_confirm" class="form-control" placeholder="<?= $currentLang['password_placeholder'] ?>">
                        </div>

                        <button type="submit" class="btn btn-primary mt-4 w-100"><?= $currentLang['save_changes_button'] ?></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

    <script src="js/index_.js?v=<?= time(); ?>"></script>
</body>
</html>