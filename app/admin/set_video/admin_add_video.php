<?php
include '../check_permission.php';

// Define the content in 5 languages
$translations = [
    'th' => [
        'page_title' => 'เพิ่มวิดีโอ',
        'heading' => 'เพิ่มวิดีโอ',
        'label_title' => 'ชื่อวิดีโอ',
        'label_description' => 'คำอธิบาย',
        'label_full_link' => 'ลิงก์ YouTube แบบเต็ม',
        'placeholder_full_link' => 'เช่น https://www.youtube.com/watch?v=mOznkEkxdNU',
        'label_show_on_homepage' => 'แสดงบนหน้าแรก',
        'btn_add_video' => 'เพิ่มวิดีโอ',
        'btn_back' => 'ย้อนกลับ',
        'alert_limit_exceeded' => 'แสดงได้สูงสุด 4 วิดีโอบนหน้าหลักเท่านั้น',
        'alert_limit_exceeded_title' => 'ไม่สามารถบันทึกได้',
        'alert_limit_exceeded_text' => 'คุณสามารถแสดงวิดีโอบนหน้าแรกได้สูงสุด 4 รายการเท่านั้น',
    ],
    'en' => [
        'page_title' => 'Add Video',
        'heading' => 'Add Video',
        'label_title' => 'Video Title',
        'label_description' => 'Description',
        'label_full_link' => 'Full YouTube Link',
        'placeholder_full_link' => 'e.g., https://www.youtube.com/watch?v=mOznkEkxdNU',
        'label_show_on_homepage' => 'Show on Homepage',
        'btn_add_video' => 'Add Video',
        'btn_back' => 'Back',
        'alert_limit_exceeded' => 'Only a maximum of 4 videos can be displayed on the homepage.',
        'alert_limit_exceeded_title' => 'Cannot Save',
        'alert_limit_exceeded_text' => 'You can only show a maximum of 4 videos on the homepage.',
    ],
    'cn' => [
        'page_title' => '添加视频',
        'heading' => '添加视频',
        'label_title' => '视频名称',
        'label_description' => '描述',
        'label_full_link' => '完整的YouTube链接',
        'placeholder_full_link' => '例如：https://www.youtube.com/watch?v=mOznkEkxdNU',
        'label_show_on_homepage' => '显示在主页',
        'btn_add_video' => '添加视频',
        'btn_back' => '返回',
        'alert_limit_exceeded' => '主页最多只能显示4个视频',
        'alert_limit_exceeded_title' => '无法保存',
        'alert_limit_exceeded_text' => '您最多只能在主页显示4个视频。',
    ],
    'jp' => [
        'page_title' => '動画を追加',
        'heading' => '動画を追加',
        'label_title' => '動画のタイトル',
        'label_description' => '説明',
        'label_full_link' => 'YouTubeのフルリンク',
        'placeholder_full_link' => '例：https://www.youtube.com/watch?v=mOznkEkxdNU',
        'label_show_on_homepage' => 'ホームページに表示',
        'btn_add_video' => '動画を追加',
        'btn_back' => '戻る',
        'alert_limit_exceeded' => 'ホームページに表示できる動画は最大4件です',
        'alert_limit_exceeded_title' => '保存できません',
        'alert_limit_exceeded_text' => 'ホームページに表示できる動画は最大4件です。',
    ],
    'kr' => [
        'page_title' => '동영상 추가',
        'heading' => '동영상 추가',
        'label_title' => '동영상 제목',
        'label_description' => '설명',
        'label_full_link' => '전체 YouTube 링크',
        'placeholder_full_link' => '예: https://www.youtube.com/watch?v=mOznkEkxdNU',
        'label_show_on_homepage' => '홈페이지에 표시',
        'btn_add_video' => '동영상 추가',
        'btn_back' => '뒤로',
        'alert_limit_exceeded' => '홈페이지에는 최대 4개의 동영상만 표시할 수 있습니다.',
        'alert_limit_exceeded_title' => '저장할 수 없습니다',
        'alert_limit_exceeded_text' => '홈페이지에는 최대 4개의 동영상만 표시할 수 있습니다.',
    ],
];

// Start session and set language
// session_start();
$lang = $_SESSION['lang'] ?? 'th';
if (isset($_GET['lang']) && in_array($_GET['lang'], ['th', 'en', 'cn', 'jp', 'kr'])) {
    $lang = $_GET['lang'];
    $_SESSION['lang'] = $lang;
}
$text = $translations[$lang];

require_once(__DIR__ . '/../../../lib/connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $youtube_id = $_POST['youtube_id'];
    $show = isset($_POST['show_on_homepage']) ? 1 : 0;

    if ($show) {
        $check = $conn->query("SELECT COUNT(*) AS total FROM videos WHERE show_on_homepage = 1");
        $row = $check->fetch_assoc();
        if ($row['total'] >= 4) {
            echo "<script>
                    Swal.fire({
                        icon: 'warning',
                        title: '{$text['alert_limit_exceeded_title']}',
                        text: '{$text['alert_limit_exceeded_text']}',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.history.back();
                    });
                </script>";
            exit;
        }
    }

    $stmt = $conn->prepare("INSERT INTO videos (title, description, youtube_id, show_on_homepage) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $title, $desc, $youtube_id, $show);
    $stmt->execute();

    header("Location: admin_video_list.php");
    exit;
}
?>


<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $text['page_title'] ?></title>

    <link rel="icon" type="image/x-icon" href="../../../public/img/q-removebg-preview1.png">

    <link href="../../../inc/jquery/css/jquery-ui.css" rel="stylesheet">
    <script src="../../../inc/jquery/js/jquery-3.6.0.min.js"></script>
    <script src="../../../inc/jquery/js/jquery-ui.min.js"></script>
    <link href="../../../inc/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="../../../inc/bootstrap/js/bootstrap.min.js"></script>
    <script src="../../../inc/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/fontawesome5-fullcss@1.1.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.0/css/all.min.css" integrity="sha512-9xKTRVabjVeZmc+GUW8GgSmcREDunMM+Dt/GrzchfN8tkwHizc5RP4Ok/MXFFy5rIjJjzhndFScTceq5e6GvVQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
    <link href="../../../inc/sweetalert2/css/sweetalert2.min.css" rel="stylesheet">
    <script src="../../../inc/sweetalert2/js/sweetalert2.all.min.js"></script>
    <link href="../../../inc/select2/css/select2.min.css" rel="stylesheet">
    <script src="../../../inc/select2/js/select2.min.js"></script>
    <link href="https://cdn.datatables.net/v/dt/dt-2.1.4/datatables.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/v/dt/dt-2.1.4/datatables.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-iconpicker/1.10.0/css/bootstrap-iconpicker.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-iconpicker/1.10.0/js/bootstrap-iconpicker.bundle.min.js"></script>
    <link href='../css/index_.css?v=<?php echo time(); ?>' rel='stylesheet'>
    <style>
        .button-class {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
        }
        .responsive-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 10px;
        }
        /* Media query for smaller screens */
        @media (max-width: 768px) {
            .responsive-grid {
                grid-template-columns: 1fr;
            }
        }
        .btn-circle {
            border: none;
            width: 30px;
            height: 28px;
            border-radius: 50%;
            font-size: 14px;
        }
        .btn-edit {
            background-color: #FFC107;
            color: #ffffff;
        }
        .btn-del {
            background-color: #ff4537;
            color: #ffffff;
        }
    </style>
</head>
<body>
    <?php include '../template/header.php'; ?>

    <div class="container mt-5">
        <h3><i class="fas fa-plus-circle"></i> <?= $text['heading'] ?></h3>
        <form method="post" class="mt-4">
            <div class="mb-3">
                <label class="form-label"><?= $text['label_title'] ?></label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label"><?= $text['label_description'] ?></label>
                <textarea name="description" class="form-control" rows="4"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label"><?= $text['label_full_link'] ?></label>
                <input type="text" id="youtube_full_link" class="form-control" placeholder="<?= $text['placeholder_full_link'] ?>">
                <input type="hidden" id="youtube_id" name="youtube_id">
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="show_on_homepage">
                <label class="form-check-label"><?= $text['label_show_on_homepage'] ?></label>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $text['btn_add_video'] ?></button>
            <a href="admin_video_list.php" class="btn btn-secondary"><?= $text['btn_back'] ?></a>
        </form>
    </div>

    <script>
        function extractYouTubeID(url) {
            const regex = /(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&]+)/;
            const match = url.match(regex);
            return match ? match[1] : '';
        }

        document.addEventListener("DOMContentLoaded", function () {
            const fullLink = document.getElementById("youtube_full_link");
            const youtubeIdInput = document.getElementById("youtube_id");

            fullLink.addEventListener("input", function () {
                const id = extractYouTubeID(fullLink.value);
                youtubeIdInput.value = id;
            });
        });
    </script>
    <script src='../js/index_.js?v=<?php echo time(); ?>'></script>
    <script src='js/video_.js?v=<?php echo time(); ?>'></script>
</body>
</html>