<?php
include '../check_permission.php';
// ตรวจสอบให้แน่ใจว่าได้ include lib/connect.php และ lib/base_directory.php เพื่อเข้าถึง $conn และ $base_path
// require_once(__DIR__ . '/../../../../lib/connect.php');
// require_once(__DIR__ . '/../../../../lib/base_directory.php'); // ต้องแน่ใจว่าไฟล์นี้มี $base_path

// Define the content in 5 languages
$translations = [
    'th' => [
        'page_title' => 'ตั้งค่าแบนเนอร์',
        'image_height' => 'ความสูงรูปภาพ: 360px;',
        'image_width' => 'ความกว้างรูปภาพ: 1521px;',
        'heading_add_banner' => 'เพิ่มแบนเนอร์',
        'label_image' => 'รูปภาพ:',
        'alt_image_preview' => 'ภาพตัวอย่างแบนเนอร์',
        'btn_save' => 'บันทึก',
        'loading_text' => 'กำลังโหลด...',
        'alert_select_image' => 'กรุณาเลือกรูปภาพแบนเนอร์',
        'confirm_title' => 'ยืนยันการบันทึก?',
        'confirm_text' => 'คุณต้องการเพิ่มแบนเนอร์นี้ใช่หรือไม่!',
        'confirm_save_btn' => 'บันทึก',
        'update_success_title' => 'สำเร็จ!',
        'update_success_text' => 'บันทึกแบนเนอร์เรียบร้อยแล้ว.',
        'error_title' => 'เกิดข้อผิดพลาด!',
        'update_error_text' => 'ไม่สามารถบันทึกแบนเนอร์ได้:',
        'cancel_text' => 'ยกเลิก'
    ],
    'en' => [
        'page_title' => 'Setup Banner',
        'image_height' => 'Image Height: 360px;',
        'image_width' => 'Image Width: 1521px;',
        'heading_add_banner' => 'Add Banner',
        'label_image' => 'Image:',
        'alt_image_preview' => 'Banner preview image',
        'btn_save' => 'Save',
        'loading_text' => 'Loading...',
        'alert_select_image' => 'Please select a banner image.',
        'confirm_title' => 'Confirm Save?',
        'confirm_text' => 'Are you sure you want to add this banner!',
        'confirm_save_btn' => 'Save',
        'update_success_title' => 'Success!',
        'update_success_text' => 'Banner saved successfully.',
        'error_title' => 'An error occurred!',
        'update_error_text' => 'Could not save the banner:',
        'cancel_text' => 'Cancel'
    ],
    'cn' => [
        'page_title' => '设置横幅',
        'image_height' => '图片高度：360px；',
        'image_width' => '图片宽度：1521px；',
        'heading_add_banner' => '添加横幅',
        'label_image' => '图片：',
        'alt_image_preview' => '横幅预览图片',
        'btn_save' => '保存',
        'loading_text' => '加载中...',
        'alert_select_image' => '请选择横幅图片。',
        'confirm_title' => '确认保存？',
        'confirm_text' => '您确定要添加此横幅吗？',
        'confirm_save_btn' => '保存',
        'update_success_title' => '成功！',
        'update_success_text' => '横幅已成功保存。',
        'error_title' => '发生错误！',
        'update_error_text' => '无法保存横幅：',
        'cancel_text' => '取消'
    ],
    'jp' => [
        'page_title' => 'バナー設定',
        'image_height' => '画像高さ：360px；',
        'image_width' => '画像幅：1521px；',
        'heading_add_banner' => 'バナーを追加',
        'label_image' => '画像：',
        'alt_image_preview' => 'バナープレビュー画像',
        'btn_save' => '保存',
        'loading_text' => '読み込み中...',
        'alert_select_image' => 'バナー画像を選択してください。',
        'confirm_title' => '保存を確認しますか？',
        'confirm_text' => 'このバナーを追加しますか？',
        'confirm_save_btn' => '保存',
        'update_success_title' => '成功！',
        'update_success_text' => 'バナーは正常に保存されました。',
        'error_title' => 'エラーが発生しました！',
        'update_error_text' => 'バナーを保存できませんでした：',
        'cancel_text' => 'キャンセル'
    ],
    'kr' => [
        'page_title' => '배너 설정',
        'image_height' => '이미지 높이: 360px;',
        'image_width' => '이미지 너비: 1521px;',
        'heading_add_banner' => '배너 추가',
        'label_image' => '이미지:',
        'alt_image_preview' => '배너 미리보기 이미지',
        'btn_save' => '저장',
        'loading_text' => '로딩 중...',
        'alert_select_image' => '배너 이미지를 선택하십시오.',
        'confirm_title' => '저장을 확인하시겠습니까?',
        'confirm_text' => '이 배너를 추가하시겠습니까!',
        'confirm_save_btn' => '저장',
        'update_success_title' => '성공!',
        'update_success_text' => '배너가 성공적으로 저장되었습니다.',
        'error_title' => '오류가 발생했습니다!',
        'update_error_text' => '배너를 저장할 수 없습니다:',
        'cancel_text' => '취소'
    ],
];

// Set default language to 'th' if not specified in session or URL
// session_start();
$lang = $_SESSION['lang'] ?? 'th';
if (isset($_GET['lang']) && in_array($_GET['lang'], ['th', 'en', 'cn', 'jp', 'kr'])) {
    $lang = $_GET['lang'];
    $_SESSION['lang'] = $lang;
}
$text = $translations[$lang];
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

    <link href="https://cdn.jsdelivr.net/npm/fontawesome5-fullcss@1.1.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.0/css/all.min.css">

    <link href="../../../inc/sweetalert2/css/sweetalert2.min.css" rel="stylesheet">
    <script src="../../../inc/sweetalert2/js/sweetalert2.all.min.js"></script>

    <link href="../../../inc/select2/css/select2.min.css" rel="stylesheet">
    <script src="../../../inc/select2/js/select2.min.js"></script>

    <link href="https://cdn.datatables.net/v/dt/dt-2.1.4/datatables.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/v/dt/dt-2.1.4/datatables.min.js"></script>

    <link href='../css/index_.css?v=<?php echo time(); ?>' rel='stylesheet'>

    <style>
        .btn-circle { border: none; width: 30px; height: 30px; border-radius: 50%; font-size: 14px; display: inline-flex; align-items: center; justify-content: center; }
        .previewContainer img { max-width: 100%; display: none; border: 1px solid #ccc; padding: 5px; border-radius: 4px; }
        .form-section { margin: 10px; }
        .line-ref { font-size: 20px; font-weight: bold; margin-bottom: 15px; border-left: 5px solid #f57c00; padding-left: 10px; color: #333; }
        .btn-edit { background-color: #FFC107; color: white; }
        .btn-del { background-color: #DC3545; color: white; }
        .banner-img { height: 60px; object-fit: cover; border: 1px solid #ccc; }
        #loading-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.8); display: flex; justify-content: center; align-items: center; z-index: 9999; }
        .spinner-border { width: 3rem; height: 3rem; }
    </style>
</head>

<body>
<?php include '../template/header.php'; ?>

<div id="loading-overlay">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden"><?= $text['loading_text'] ?></span>
    </div>
</div>

<div class="container mt-4">
    <div style="gap: 20px"><h5>
        <div style="padding-bottom: 5px"><?= $text['image_height'] ?></div>
        <div style="padding-bottom: 5px"><?= $text['image_width'] ?></div>
    </h5></div>
    <div class="box-content p-4 bg-light rounded shadow-sm">
        <h4 class="line-ref">
            <i class="fa-solid fa-image"></i> <?= $text['heading_add_banner'] ?>
        </h4>

        <form id="bannerForm" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-section">
                        <label for="image"><?= $text['label_image'] ?></label>
                        <div class="previewContainer">
                            <img id="previewImage" src="#" alt="<?= $text['alt_image_preview'] ?>">
                        </div>
                        <input type="file" class="form-control mt-2" id="image" name="image" required onchange="previewFile()">
                    </div>
                </div>
                <div class="col-md-8 d-flex align-items-end">
                    <div class="form-section w-100 text-end">
                        <button type="submit" id="submitBanner" class="btn btn-primary">
                            <i class="fas fa-upload"></i> <?= $text['btn_save'] ?>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    const translations = {
        'th': {
            'alert_select_image': '<?= $text['alert_select_image'] ?>',
            'confirm_title': '<?= $text['confirm_title'] ?>',
            'confirm_text': '<?= $text['confirm_text'] ?>',
            'confirm_save_btn': '<?= $text['confirm_save_btn'] ?>',
            'update_success_title': '<?= $text['update_success_title'] ?>',
            'update_success_text': '<?= $text['update_success_text'] ?>',
            'error_title': '<?= $text['error_title'] ?>',
            'update_error_text': '<?= $text['update_error_text'] ?>',
            'cancel_text': '<?= $text['cancel_text'] ?>'
        },
        'en': {
            'alert_select_image': '<?= $text['alert_select_image'] ?>',
            'confirm_title': '<?= $text['confirm_title'] ?>',
            'confirm_text': '<?= $text['confirm_text'] ?>',
            'confirm_save_btn': '<?= $text['confirm_save_btn'] ?>',
            'update_success_title': '<?= $text['update_success_title'] ?>',
            'update_success_text': '<?= $text['update_success_text'] ?>',
            'error_title': '<?= $text['error_title'] ?>',
            'update_error_text': '<?= $text['update_error_text'] ?>',
            'cancel_text': '<?= $text['cancel_text'] ?>'
        },
        'cn': {
            'alert_select_image': '<?= $text['alert_select_image'] ?>',
            'confirm_title': '<?= $text['confirm_title'] ?>',
            'confirm_text': '<?= $text['confirm_text'] ?>',
            'confirm_save_btn': '<?= $text['confirm_save_btn'] ?>',
            'update_success_title': '<?= $text['update_success_title'] ?>',
            'update_success_text': '<?= $text['update_success_text'] ?>',
            'error_title': '<?= $text['error_title'] ?>',
            'update_error_text': '<?= $text['update_error_text'] ?>',
            'cancel_text': '<?= $text['cancel_text'] ?>'
        },
        'jp': {
            'alert_select_image': '<?= $text['alert_select_image'] ?>',
            'confirm_title': '<?= $text['confirm_title'] ?>',
            'confirm_text': '<?= $text['confirm_text'] ?>',
            'confirm_save_btn': '<?= $text['confirm_save_btn'] ?>',
            'update_success_title': '<?= $text['update_success_title'] ?>',
            'update_success_text': '<?= $text['update_success_text'] ?>',
            'error_title': '<?= $text['error_title'] ?>',
            'update_error_text': '<?= $text['update_error_text'] ?>',
            'cancel_text': '<?= $text['cancel_text'] ?>'
        },
        'kr': {
            'alert_select_image': '<?= $text['alert_select_image'] ?>',
            'confirm_title': '<?= $text['confirm_title'] ?>',
            'confirm_text': '<?= $text['confirm_text'] ?>',
            'confirm_save_btn': '<?= $text['confirm_save_btn'] ?>',
            'update_success_title': '<?= $text['update_success_title'] ?>',
            'update_success_text': '<?= $text['update_success_text'] ?>',
            'error_title': '<?= $text['error_title'] ?>',
            'update_error_text': '<?= $text['update_error_text'] ?>',
            'cancel_text': '<?= $text['cancel_text'] ?>'
        }
    };
    const currentLang = '<?= $lang ?>';

    function previewFile() {
        const preview = document.getElementById('previewImage');
        const file = document.getElementById('image').files[0];
        const reader = new FileReader();

        reader.onloadend = function () {
            preview.src = reader.result;
            preview.style.display = 'block';
        }

        if (file) {
            reader.readAsDataURL(file);
        } else {
            preview.src = "";
            preview.style.display = 'none';
        }
    }

    function alertError(textAlert) {
        const Toast = Swal.mixin({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
        });
        Toast.fire({
            icon: "error",
            title: textAlert
        });
    }

    $(document).ready(function() {
        $('#bannerForm').on('submit', function(e) {
            e.preventDefault();

            var formData = new FormData(this);
            formData.append('action', 'addbanner_single');

            const text = translations[currentLang];

            if ($('#image').get(0).files.length === 0) {
                alertError(text['alert_select_image']);
                return;
            }

            Swal.fire({
                title: text['confirm_title'],
                text: text['confirm_text'],
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#4CAF50",
                cancelButtonColor: "#d33",
                confirmButtonText: text['confirm_save_btn'],
                cancelButtonText: text['cancel_text']
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#loading-overlay').fadeIn();

                    $.ajax({
                        url: "actions/process_banner.php",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: 'json',
                        success: function(response) {
                            $('#loading-overlay').fadeOut();
                            if (response.status === 'success') {
                                Swal.fire(
                                    text['update_success_title'],
                                    text['update_success_text'],
                                    'success'
                                ).then(() => {
                                    window.location.href = 'list_banner.php';
                                });
                            } else {
                                Swal.fire(
                                    text['error_title'],
                                    response.message,
                                    'error'
                                );
                            }
                        },
                        error: function(xhr, status, error) {
                            $('#loading-overlay').fadeOut();
                            console.error("AJAX Error:", status, error, xhr.responseText);
                            Swal.fire(
                                text['error_title'],
                                text['update_error_text'] + ' ' + error,
                                'error'
                            );
                        }
                    });
                }
            });
        });
    });
</script>

<script src='../js/index_.js?v=<?php echo time(); ?>'></script>
</body>
</html>