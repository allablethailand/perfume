<?php
// edit_logo.php
include '../check_permission.php'; // ตรวจสอบสิทธิ์การเข้าถึง

// require_once(__DIR__ . '/../../../../lib/connect.php');
// require_once(__DIR__ . '/../../../../lib/base_directory.php'); // ถ้ามี $base_path

// Define the content in 5 languages
$translations = [
    'th' => [
        'page_title' => 'แก้ไขโลโก้และข้อมูลติดต่อ',
        'logo_size_main' => 'ขนาดรูปภาพที่แนะนำสำหรับโลโก้หลัก: กว้าง 100px; สูง 55px;',
        // 'logo_size_modal' => 'ขนาดรูปภาพที่แนะนำสำหรับโลโก้ใน Modal: กว้าง 70% ของ Modal (ประมาณ 245px); สูงอัตโนมัติ;',
        'heading_logo' => 'แก้ไขโลโก้เว็บไซต์',
        'current_logo_main' => 'ภาพโลโก้หลักปัจจุบัน:',
        'new_logo_main' => 'เลือกรูปภาพโลโก้หลักใหม่:',
        'note_main_logo' => 'เลือกไฟล์ใหม่เพื่อเปลี่ยนรูปภาพโลโก้หลัก หากไม่เลือก รูปภาพเดิมจะถูกใช้',
        'current_logo_modal' => 'ภาพโลโก้ใน Modal ปัจจุบัน:',
        'new_logo_modal' => 'เลือกรูปภาพโลโก้ใน Modal ใหม่:',
        'note_modal_logo' => 'เลือกไฟล์ใหม่เพื่อเปลี่ยนรูปภาพโลโก้ใน Modal หากไม่เลือก รูปภาพเดิมจะถูกใช้',
        'heading_contact' => 'แก้ไขข้อมูลติดต่อและ Social Media',
        'link_perfume_store' => 'ลิงก์ perfume Store:',
        'text_perfume_store' => 'ข้อความ perfume Store:',
        'link_facebook' => 'ลิงก์ Facebook:',
        'link_youtube' => 'ลิงก์ YouTube:',
        'link_instagram' => 'ลิงก์ Instagram:',
        'link_line' => 'ลิงก์ Line:',
        'link_tiktok' => 'ลิงก์ TikTok:',
        'update_btn' => 'อัปเดตข้อมูล',
        'loading' => 'กำลังโหลด...',
        'confirm_title' => 'ยืนยันการแก้ไข?',
        'confirm_text' => 'คุณต้องการอัปเดตข้อมูลทั้งหมดใช่หรือไม่!',
        'confirm_button' => 'อัปเดต',
        'success_title' => 'สำเร็จ!',
        'success_message' => 'แก้ไขข้อมูลเรียบร้อยแล้ว.',
        'error_title' => 'เกิดข้อผิดพลาด!',
        'error_ajax' => 'ไม่สามารถแก้ไขข้อมูลได้: ',
        'no_data' => 'ไม่พบข้อมูล'
    ],
    'en' => [
        'page_title' => 'Edit Logo and Contact Info',
        'logo_size_main' => 'Recommended image size for main logo: 100px wide, 55px high.',
        // 'logo_size_modal' => 'Recommended image size for modal logo: 70% of modal width (approx. 245px), auto height.',
        'heading_logo' => 'Edit Website Logo',
        'current_logo_main' => 'Current Main Logo:',
        'new_logo_main' => 'Select New Main Logo:',
        'note_main_logo' => 'Choose a new file to change the main logo. If not, the current image will be used.',
        'current_logo_modal' => 'Current Modal Logo:',
        'new_logo_modal' => 'Select New Modal Logo:',
        'note_modal_logo' => 'Choose a new file to change the modal logo. If not, the current image will be used.',
        'heading_contact' => 'Edit Contact Info and Social Media',
        'link_perfume_store' => 'perfume Store Link:',
        'text_perfume_store' => 'perfume Store Text:',
        'link_facebook' => 'Facebook Link:',
        'link_youtube' => 'YouTube Link:',
        'link_instagram' => 'Instagram Link:',
        'link_line' => 'Line Link:',
        'link_tiktok' => 'TikTok Link:',
        'update_btn' => 'Update Data',
        'loading' => 'Loading...',
        'confirm_title' => 'Confirm Update?',
        'confirm_text' => 'Are you sure you want to update all data?',
        'confirm_button' => 'Update',
        'success_title' => 'Success!',
        'success_message' => 'Data updated successfully.',
        'error_title' => 'Error!',
        'error_ajax' => 'Failed to update data: ',
        'no_data' => 'No data found'
    ],
    'cn' => [
        'page_title' => '编辑徽标和联系方式',
        'logo_size_main' => '主徽标推荐图片尺寸：宽100px；高55px；',
        // 'logo_size_modal' => '模态框徽标推荐图片尺寸：模态框宽度的70%（约245px）；高度自动；',
        'heading_logo' => '编辑网站徽标',
        'current_logo_main' => '当前主徽标：',
        'new_logo_main' => '选择新的主徽标：',
        'note_main_logo' => '选择一个新文件以更改主徽标。如果未选择，将使用原始图片。',
        'current_logo_modal' => '当前模态框徽标：',
        'new_logo_modal' => '选择新的模态框徽标：',
        'note_modal_logo' => '选择一个新文件以更改模态框徽标。如果未选择，将使用原始图片。',
        'heading_contact' => '编辑联系方式和社交媒体',
        'link_perfume_store' => 'perfume 商店链接：',
        'text_perfume_store' => 'perfume 商店文本：',
        'link_facebook' => 'Facebook 链接：',
        'link_youtube' => 'YouTube 链接：',
        'link_instagram' => 'Instagram 链接：',
        'link_line' => 'Line 链接：',
        'link_tiktok' => 'TikTok 链接：',
        'update_btn' => '更新数据',
        'loading' => '加载中...',
        'confirm_title' => '确认更新？',
        'confirm_text' => '您确定要更新所有数据吗？',
        'confirm_button' => '更新',
        'success_title' => '成功！',
        'success_message' => '数据已成功更新。',
        'error_title' => '发生错误！',
        'error_ajax' => '无法更新数据：',
        'no_data' => '未找到数据'
    ],
    'jp' => [
        'page_title' => 'ロゴと連絡先情報の編集',
        'logo_size_main' => 'メインロゴの推奨画像サイズ：幅100px；高さ55px；',
        // 'logo_size_modal' => 'モーダルロゴの推奨画像サイズ：モーダルの幅の70%（約245px）；高さは自動；',
        'heading_logo' => 'ウェブサイトのロゴを編集',
        'current_logo_main' => '現在のメインロゴ：',
        'new_logo_main' => '新しいメインロゴを選択：',
        'note_main_logo' => 'メインロゴを変更するには新しいファイルを選択してください。選択しない場合、元の画像が使用されます。',
        'current_logo_modal' => '現在のモーダルロゴ：',
        'new_logo_modal' => '新しいモーダルロゴを選択：',
        'note_modal_logo' => 'モーダルロゴを変更するには新しいファイルを選択してください。選択しない場合、元の画像が使用されます。',
        'heading_contact' => '連絡先とソーシャルメディアを編集',
        'link_perfume_store' => 'perfumeストアリンク：',
        'text_perfume_store' => 'perfumeストアテキスト：',
        'link_facebook' => 'Facebookリンク：',
        'link_youtube' => 'YouTubeリンク：',
        'link_instagram' => 'Instagramリンク：',
        'link_line' => 'Lineリンク：',
        'link_tiktok' => 'TikTokリンク：',
        'update_btn' => 'データを更新',
        'loading' => '読み込み中...',
        'confirm_title' => '更新を確認しますか？',
        'confirm_text' => 'すべてのデータを更新してもよろしいですか？',
        'confirm_button' => '更新',
        'success_title' => '成功！',
        'success_message' => 'データは正常に更新されました。',
        'error_title' => 'エラーが発生しました！',
        'error_ajax' => 'データを更新できませんでした：',
        'no_data' => 'データが見つかりません'
    ],
    'kr' => [
        'page_title' => '로고 및 연락처 정보 편집',
        'logo_size_main' => '주요 로고 권장 이미지 크기: 가로 100px; 세로 55px;',
        // 'logo_size_modal' => '모달 로고 권장 이미지 크기: 모달 너비의 70%(약 245px); 높이 자동;',
        'heading_logo' => '웹사이트 로고 편집',
        'current_logo_main' => '현재 메인 로고:',
        'new_logo_main' => '새 메인 로고 선택:',
        'note_main_logo' => '주요 로고를 변경하려면 새 파일을 선택하십시오. 선택하지 않으면 기존 이미지가 사용됩니다.',
        'current_logo_modal' => '현재 모달 로고:',
        'new_logo_modal' => '새 모달 로고 선택:',
        'note_modal_logo' => '모달 로고를 변경하려면 새 파일을 선택하십시오. 선택하지 않으면 기존 이미지가 사용됩니다.',
        'heading_contact' => '연락처 및 소셜 미디어 편집',
        'link_perfume_store' => 'perfume 스토어 링크:',
        'text_perfume_store' => 'perfume 스토어 텍스트:',
        'link_facebook' => 'Facebook 링크:',
        'link_youtube' => 'YouTube 링크:',
        'link_instagram' => 'Instagram 링크:',
        'link_line' => 'Line 링크:',
        'link_tiktok' => 'TikTok 링크:',
        'update_btn' => '데이터 업데이트',
        'loading' => '불러오는 중...',
        'confirm_title' => '업데이트 확인?',
        'confirm_text' => '모든 데이터를 업데이트하시겠습니까?!',
        'confirm_button' => '업데이트',
        'success_title' => '성공!',
        'success_message' => '데이터가 성공적으로 수정되었습니다.',
        'error_title' => '오류 발생!',
        'error_ajax' => '데이터를 수정할 수 없습니다:',
        'no_data' => '데이터 없음'
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

global $conn;

// ในกรณีของโลโก้ เราจะดึงข้อมูลจาก ID เดียวคือ 1 เสมอ
$logo_id = 1;

$stmt = $conn->prepare("SELECT id, image_path, image_modal_path FROM logo_settings WHERE id = ?");
$stmt->bind_param("i", $logo_id);
$stmt->execute();
$result = $stmt->get_result();
$logo = $result->fetch_assoc();
$stmt->close();

// หากไม่พบข้อมูลโลโก้ (ซึ่งไม่ควรเกิดขึ้นหากมีการแทรกข้อมูลเริ่มต้นแล้ว)
if (!$logo) {
    $logo = [
        'id' => 1,
        'image_path' => '../public/img/LOGOTRAND.png', // Path โลโก้ default หากไม่พบใน DB
        'image_modal_path' => '../public/img/perfume.jpg' // Path รูปภาพ Modal default หากไม่พบใน DB
    ];
}

// ดึงข้อมูล contact settings
$contact_settings_id = 1; // สมมติว่ามี ID 1 สำหรับการตั้งค่าหลัก
$contact_settings = [
    'perfume_store_link' => '',
    'perfume_store_text' => '',
    'facebook_link' => '',
    'youtube_link' => '',
    'instagram_link' => '',
    'line_link' => '',
    'tiktok_link' => ''
];

$stmt_contact = $conn->prepare("SELECT perfume_store_link, perfume_store_text, facebook_link, youtube_link, instagram_link, line_link, tiktok_link FROM contact_settings WHERE id = ?");
$stmt_contact->bind_param("i", $contact_settings_id);
$stmt_contact->execute();
$result_contact = $stmt_contact->get_result();
if ($data_contact = $result_contact->fetch_assoc()) {
    $contact_settings = $data_contact;
}
$stmt_contact->close();
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
        .banner-img { height: 60px; object-fit: contain; border: 1px solid #ccc; }
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
        <span class="visually-hidden"><?= $text['loading'] ?></span>
    </div>
</div>

<div class="container mt-4">
    <div style="gap :20px">
        <h5>
            <div style="padding-bottom :5px"><?= $text['logo_size_main'] ?></div>
            <!-- <div style="padding-bottom :5px"><?= $text['logo_size_modal'] ?></div> -->
        </h5>
    </div>
    <div class="box-content p-4 bg-light rounded shadow-sm">
        <h4 class="line-ref">
            <i class="fa-solid fa-image"></i> <?= $text['heading_logo'] ?>
        </h4>

        <form id="editLogoForm" enctype="multipart/form-data">
            <input type="hidden" name="logo_id" value="<?= htmlspecialchars($logo['id']) ?>">
            <input type="hidden" name="old_image_path" value="<?= htmlspecialchars($logo['image_path']) ?>">
            <input type="hidden" name="old_image_modal_path" value="<?= htmlspecialchars($logo['image_modal_path']) ?>">

            <div class="row mb-4">
                <div class="">
                    <div class="form-section">
                        <label><?= $text['current_logo_main'] ?></label>
                        <div class="previewContainer mb-2">
                            <img id="currentImage" src="<?= htmlspecialchars($logo['image_path']) ?>" alt="Current Logo" class="img-thumbnail">
                            <img id="previewNewImage" src="#" alt="New Logo Preview" style="display:none; margin-top: 10px;">
                        </div>
                        <label for="image"><?= $text['new_logo_main'] ?></label>
                        <input type="file" class="form-control" name="image" id="image" onchange="previewFile('image', 'currentImage', 'previewNewImage')">
                        <small class="form-text text-muted"><?= $text['note_main_logo'] ?></small>
                    </div>
                </div>

                <!-- <div class="col-md-6">
                    <div class="form-section">
                        <label><?= $text['current_logo_modal'] ?></label>
                        <div class="previewContainer mb-2">
                            <img id="currentImageModal" src="<?= htmlspecialchars($logo['image_modal_path']) ?>" alt="Current Modal Logo" class="img-thumbnail">
                            <img id="previewNewImageModal" src="#" alt="New Modal Logo Preview" style="display:none; margin-top: 10px;">
                        </div>
                        <label for="image_modal"><?= $text['new_logo_modal'] ?></label>
                        <input type="file" class="form-control" name="image_modal" id="image_modal" onchange="previewFile('image_modal', 'currentImageModal', 'previewNewImageModal')">
                        <small class="form-text text-muted"><?= $text['note_modal_logo'] ?></small>
                    </div>
                </div> -->
            </div>

            <h4 class="line-ref mt-5">
                <i class="fa-solid fa-share-nodes"></i> <?= $text['heading_contact'] ?>
            </h4>
            <input type="hidden" name="contact_settings_id" value="<?= htmlspecialchars($contact_settings_id) ?>">

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="perfume_store_link" class="form-label"><?= $text['link_perfume_store'] ?></label>
                    <input type="url" class="form-control" id="perfume_store_link" name="perfume_store_link" value="<?= htmlspecialchars($contact_settings['perfume_store_link']) ?>">
                </div>
                <div class="col-md-6">
                    <label for="perfume_store_text" class="form-label"><?= $text['text_perfume_store'] ?></label>
                    <input type="text" class="form-control" id="perfume_store_text" name="perfume_store_text" value="<?= htmlspecialchars($contact_settings['perfume_store_text']) ?>">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="facebook_link" class="form-label"><?= $text['link_facebook'] ?></label>
                    <input type="url" class="form-control" id="facebook_link" name="facebook_link" value="<?= htmlspecialchars($contact_settings['facebook_link']) ?>">
                </div>
                <div class="col-md-6">
                    <label for="youtube_link" class="form-label"><?= $text['link_youtube'] ?></label>
                    <input type="url" class="form-control" id="youtube_link" name="youtube_link" value="<?= htmlspecialchars($contact_settings['youtube_link']) ?>">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="instagram_link" class="form-label"><?= $text['link_instagram'] ?></label>
                    <input type="url" class="form-control" id="instagram_link" name="instagram_link" value="<?= htmlspecialchars($contact_settings['instagram_link']) ?>">
                </div>
                <div class="col-md-6">
                    <label for="line_link" class="form-label"><?= $text['link_line'] ?></label>
                    <input type="url" class="form-control" id="line_link" name="line_link" value="<?= htmlspecialchars($contact_settings['line_link']) ?>">
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="tiktok_link" class="form-label"><?= $text['link_tiktok'] ?></label>
                    <input type="url" class="form-control" id="tiktok_link" name="tiktok_link" value="<?= htmlspecialchars($contact_settings['tiktok_link']) ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-12 text-end">
                    <button type="submit" id="submitEditLogo" class="btn btn-primary">
                        <i class="fas fa-edit"></i> <?= $text['update_btn'] ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src='../js/index_.js?v=<?php echo time(); ?>'></script>
<script>
    function previewFile(inputId, currentImgId, previewImgId) {
        const previewCurrent = document.getElementById(currentImgId);
        const previewNew = document.getElementById(previewImgId);
        const file = document.getElementById(inputId).files[0];
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
        $('#submitEditLogo').on('click', function(e) {
            e.preventDefault();

            var formData = new FormData($('#editLogoForm')[0]);
            formData.append('action', 'edit_all_settings');

            Swal.fire({
                title: "<?= $text['confirm_title'] ?>",
                text: "<?= $text['confirm_text'] ?>",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#FFC107",
                cancelButtonColor: "#d33",
                confirmButtonText: "<?= $text['confirm_button'] ?>"
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#loading-overlay').fadeIn();

                    $.ajax({
                        url: "actions/process_logo.php",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: 'json',
                        success: function(response) {
                            $('#loading-overlay').fadeOut();
                            if (response.status === 'success') {
                                Swal.fire(
                                    "<?= $text['success_title'] ?>",
                                    "<?= $text['success_message'] ?>",
                                    'success'
                                ).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire(
                                    "<?= $text['error_title'] ?>",
                                    response.message,
                                    'error'
                                );
                            }
                        },
                        error: function(xhr, status, error) {
                            $('#loading-overlay').fadeOut();
                            console.error("AJAX Error:", status, error, xhr.responseText);
                            Swal.fire(
                                "<?= $text['error_title'] ?>",
                                "<?= $text['error_ajax'] ?>" + error,
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