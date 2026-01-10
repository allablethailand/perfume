<?php
include '../check_permission.php';
// require_once(__DIR__ . '/../../../../lib/connect.php'); // Include your database connection
// require_once(__DIR__ . '/../../../../lib/base_directory.php'); // Include base_directory.php for $base_path

// Define the content in 5 languages
$translations = [
    'th' => [
        'page_title' => 'แก้ไขแบนเนอร์',
        'invalid_banner_id' => 'รหัสแบนเนอร์ไม่ถูกต้อง',
        'banner_not_found' => 'ไม่พบแบนเนอร์',
        'image_height' => 'ความสูงรูปภาพ: 360px;',
        'image_width' => 'ความกว้างรูปภาพ: 1521px;',
        'heading_edit_banner' => 'แก้ไขแบนเนอร์',
        'label_current_image' => 'ภาพปัจจุบัน:',
        'alt_current_image' => 'ภาพแบนเนอร์ปัจจุบัน',
        'alt_new_image' => 'ภาพแบนเนอร์ใหม่',
        'label_select_new_image' => 'เลือกรูปภาพใหม่:',
        'help_text_image' => 'เลือกไฟล์ใหม่เพื่อเปลี่ยนรูปภาพ หากไม่เลือก รูปภาพเดิมจะถูกใช้',
        'btn_update' => 'อัปเดต',
        'loading_text' => 'กำลังโหลด...',
        'confirm_title' => 'ยืนยันการแก้ไข?',
        'confirm_text' => 'คุณต้องการอัปเดตแบนเนอร์นี้ใช่หรือไม่!',
        'confirm_update_btn' => 'อัปเดต',
        'update_success_title' => 'สำเร็จ!',
        'update_success_text' => 'แก้ไขแบนเนอร์เรียบร้อยแล้ว.',
        'error_title' => 'เกิดข้อผิดพลาด!',
        'update_error_text' => 'ไม่สามารถแก้ไขแบนเนอร์ได้:',
    ],
    'en' => [
        'page_title' => 'Edit Banner',
        'invalid_banner_id' => 'Invalid Banner ID',
        'banner_not_found' => 'Banner not found',
        'image_height' => 'Image Height: 360px;',
        'image_width' => 'Image Width: 1521px;',
        'heading_edit_banner' => 'Edit Banner',
        'label_current_image' => 'Current Image:',
        'alt_current_image' => 'Current Banner Image',
        'alt_new_image' => 'New Banner Image',
        'label_select_new_image' => 'Select New Image:',
        'help_text_image' => 'Select a new file to change the image. If not, the old image will be used.',
        'btn_update' => 'Update',
        'loading_text' => 'Loading...',
        'confirm_title' => 'Confirm Update?',
        'confirm_text' => 'Are you sure you want to update this banner!',
        'confirm_update_btn' => 'Update',
        'update_success_title' => 'Success!',
        'update_success_text' => 'The banner has been successfully updated.',
        'error_title' => 'An error occurred!',
        'update_error_text' => 'Could not update the banner:',
    ],
    'cn' => [
        'page_title' => '编辑横幅',
        'invalid_banner_id' => '无效的横幅ID',
        'banner_not_found' => '未找到横幅',
        'image_height' => '图片高度：360px；',
        'image_width' => '图片宽度：1521px；',
        'heading_edit_banner' => '编辑横幅',
        'label_current_image' => '当前图片：',
        'alt_current_image' => '当前横幅图片',
        'alt_new_image' => '新横幅图片',
        'label_select_new_image' => '选择新图片：',
        'help_text_image' => '选择新文件以更改图片。如果不选择，将使用旧图片。',
        'btn_update' => '更新',
        'loading_text' => '加载中...',
        'confirm_title' => '确认更新？',
        'confirm_text' => '您确定要更新此横幅吗？',
        'confirm_update_btn' => '更新',
        'update_success_title' => '成功！',
        'update_success_text' => '横幅已成功更新。',
        'error_title' => '发生错误！',
        'update_error_text' => '无法更新横幅：',
    ],
    'jp' => [
        'page_title' => 'バナー編集',
        'invalid_banner_id' => '無効なバナーID',
        'banner_not_found' => 'バナーが見つかりません',
        'image_height' => '画像高さ：360px；',
        'image_width' => '画像幅：1521px；',
        'heading_edit_banner' => 'バナー編集',
        'label_current_image' => '現在の画像：',
        'alt_current_image' => '現在のバナー画像',
        'alt_new_image' => '新しいバナー画像',
        'label_select_new_image' => '新しい画像を選択：',
        'help_text_image' => '新しいファイルを選択して画像を更新します。選択しない場合は、元の画像が使用されます。',
        'btn_update' => '更新',
        'loading_text' => '読み込み中...',
        'confirm_title' => '更新を確認しますか？',
        'confirm_text' => 'このバナーを更新しますか？',
        'confirm_update_btn' => '更新',
        'update_success_title' => '成功！',
        'update_success_text' => 'バナーは正常に更新されました。',
        'error_title' => 'エラーが発生しました！',
        'update_error_text' => 'バナーを更新できませんでした：',
    ],
    'kr' => [
        'page_title' => '배너 수정',
        'invalid_banner_id' => '잘못된 배너 ID',
        'banner_not_found' => '배너를 찾을 수 없습니다',
        'image_height' => '이미지 높이: 360px;',
        'image_width' => '이미지 너비: 1521px;',
        'heading_edit_banner' => '배너 수정',
        'label_current_image' => '현재 이미지:',
        'alt_current_image' => '현재 배너 이미지',
        'alt_new_image' => '새 배너 이미지',
        'label_select_new_image' => '새 이미지 선택:',
        'help_text_image' => '이미지를 변경하려면 새 파일을 선택하십시오. 선택하지 않으면 기존 이미지가 사용됩니다.',
        'btn_update' => '업데이트',
        'loading_text' => '로딩 중...',
        'confirm_title' => '업데이트를 확인하시겠습니까?',
        'confirm_text' => '이 배너를 업데이트하시겠습니까!',
        'confirm_update_btn' => '업데이트',
        'update_success_title' => '성공!',
        'update_success_text' => '배너가 성공적으로 업데이트되었습니다.',
        'error_title' => '오류가 발생했습니다!',
        'update_error_text' => '배너를 업데이트할 수 없습니다:',
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

$id = $_GET['id'] ?? 0;

// Validate $id to prevent SQL Injection
if (!is_numeric($id) || $id <= 0) {
    echo "<script>alert('" . $text['invalid_banner_id'] . "'); window.location.href='list_banner.php';</script>";
    exit;
}

$stmt = $conn->prepare("SELECT id, image_path FROM banner WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$banner = $result->fetch_assoc();
$stmt->close();

if (!$banner) {
    echo "<script>alert('" . $text['banner_not_found'] . "'); window.location.href='list_banner.php';</script>";
    exit;
}

// Don't need the PHP POST section here as it's handled by AJAX
// if ($_SERVER['REQUEST_METHOD'] === 'POST') { ... }
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
        .btn-edit { background-color: #FFC107; color: white; }
        .btn-del { background-color: #DC3545; color: white; }
        .banner-img { height: 60px; object-fit: cover; border: 1px solid #ccc; }
        .line-ref { font-size: 20px; font-weight: bold; margin-bottom: 15px; border-left: 5px solid #f57c00; padding-left: 10px; color: #333; }
        .previewContainer img { max-width: 100%; height: auto; display: block; border: 1px solid #ccc; padding: 5px; border-radius: 4px; }
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
    <div style="gap :20px"><h5>
        <div style="padding-bottom :5px"><?= $text['image_height'] ?></div>
        <div style="padding-bottom :5px"><?= $text['image_width'] ?></div>
    </h5></div>
    <div class="box-content p-4 bg-light rounded shadow-sm">
        <h4 class="line-ref">
            <i class="fa-solid fa-image"></i> <?= $text['heading_edit_banner'] ?>
        </h4>

        <form id="editBannerForm" enctype="multipart/form-data">
            <input type="hidden" name="banner_id" value="<?= htmlspecialchars($banner['id']) ?>">
            <input type="hidden" name="old_image_path" value="<?= htmlspecialchars($banner['image_path']) ?>">

            <div class="row">
                <div class="col-md-4">
                    <div class="form-section">
                        <label><?= $text['label_current_image'] ?></label>
                        <div class="previewContainer mb-2">
                            <img id="currentImage" src="<?= htmlspecialchars($banner['image_path']) ?>" alt="<?= $text['alt_current_image'] ?>" class="img-thumbnail">
                            <img id="previewNewImage" src="#" alt="<?= $text['alt_new_image'] ?>" style="display:none; margin-top: 10px;">
                        </div>
                        <label for="image"><?= $text['label_select_new_image'] ?></label>
                        <input type="file" class="form-control" name="image" id="image" onchange="previewFile()">
                        <small class="form-text text-muted"><?= $text['help_text_image'] ?></small>
                    </div>
                </div>

                <div class="col-md-8 d-flex align-items-end">
                    <div class="form-section w-100 text-end">
                        <button type="submit" id="submitEditBanner" class="btn btn-primary">
                            <i class="fas fa-edit"></i> <?= $text['btn_update'] ?>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src='../js/index_.js?v=<?php echo time(); ?>'></script>
<script>
    const translations = {
        'th': {
            'confirm_title': '<?= $text['confirm_title'] ?>',
            'confirm_text': '<?= $text['confirm_text'] ?>',
            'confirm_update_btn': '<?= $text['confirm_update_btn'] ?>',
            'update_success_title': '<?= $text['update_success_title'] ?>',
            'update_success_text': '<?= $text['update_success_text'] ?>',
            'error_title': '<?= $text['error_title'] ?>',
            'update_error_text': '<?= $text['update_error_text'] ?>',
            'cancel_text': 'ยกเลิก'
        },
        'en': {
            'confirm_title': '<?= $text['confirm_title'] ?>',
            'confirm_text': '<?= $text['confirm_text'] ?>',
            'confirm_update_btn': '<?= $text['confirm_update_btn'] ?>',
            'update_success_title': '<?= $text['update_success_title'] ?>',
            'update_success_text': '<?= $text['update_success_text'] ?>',
            'error_title': '<?= $text['error_title'] ?>',
            'update_error_text': '<?= $text['update_error_text'] ?>',
            'cancel_text': 'Cancel'
        },
        'cn': {
            'confirm_title': '<?= $text['confirm_title'] ?>',
            'confirm_text': '<?= $text['confirm_text'] ?>',
            'confirm_update_btn': '<?= $text['confirm_update_btn'] ?>',
            'update_success_title': '<?= $text['update_success_title'] ?>',
            'update_success_text': '<?= $text['update_success_text'] ?>',
            'error_title': '<?= $text['error_title'] ?>',
            'update_error_text': '<?= $text['update_error_text'] ?>',
            'cancel_text': '取消'
        },
        'jp': {
            'confirm_title': '<?= $text['confirm_title'] ?>',
            'confirm_text': '<?= $text['confirm_text'] ?>',
            'confirm_update_btn': '<?= $text['confirm_update_btn'] ?>',
            'update_success_title': '<?= $text['update_success_title'] ?>',
            'update_success_text': '<?= $text['update_success_text'] ?>',
            'error_title': '<?= $text['error_title'] ?>',
            'update_error_text': '<?= $text['update_error_text'] ?>',
            'cancel_text': 'キャンセル'
        },
        'kr': {
            'confirm_title': '<?= $text['confirm_title'] ?>',
            'confirm_text': '<?= $text['confirm_text'] ?>',
            'confirm_update_btn': '<?= $text['confirm_update_btn'] ?>',
            'update_success_title': '<?= $text['update_success_title'] ?>',
            'update_success_text': '<?= $text['update_success_text'] ?>',
            'error_title': '<?= $text['error_title'] ?>',
            'update_error_text': '<?= $text['update_error_text'] ?>',
            'cancel_text': '취소'
        }
    };
    const currentLang = '<?= $lang ?>';

    function previewFile() {
        const previewCurrent = document.getElementById('currentImage');
        const previewNew = document.getElementById('previewNewImage');
        const file = document.getElementById('image').files[0];
        const reader = new FileReader();

        reader.onloadend = function () {
            previewNew.src = reader.result;
            previewNew.style.display = 'block';
            previewCurrent.style.display = 'none';
        }

        if (file) {
            reader.readAsDataURL(file);
        } else {
            previewNew.src = "";
            previewNew.style.display = 'none';
            previewCurrent.style.display = 'block';
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
        $('#editBannerForm').on('submit', function(e) {
            e.preventDefault();

            var formData = new FormData(this);
            formData.append('action', 'editbanner_single');

            const text = translations[currentLang];

            Swal.fire({
                title: text['confirm_title'],
                text: text['confirm_text'],
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#FFC107",
                cancelButtonColor: "#d33",
                confirmButtonText: text['confirm_update_btn'],
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

</body>
</html>