<?php
// edit_contact.php
include '../check_permission.php';

// Define the content in 5 languages
$translations = [
    'th' => [
        'page_title' => 'แก้ไขข้อมูลติดต่อ',
        'heading_title' => 'แก้ไขข้อมูลติดต่อ',
        'alert_not_found' => 'ไม่พบข้อมูลการตั้งค่า. โปรดตรวจสอบว่ามีข้อมูลเริ่มต้นในฐานข้อมูล.',
        'loading' => 'กำลังโหลด...',
        'form_company_name' => 'ชื่อบริษัท:',
        'form_address' => 'ที่อยู่:',
        'form_phone' => 'เบอร์โทรศัพท์:',
        'form_email' => 'อีเมล:',
        'form_hours_weekday' => 'เวลาทำการ (จันทร์-ศุกร์):',
        'form_hours_saturday' => 'เวลาทำการ (เสาร์):',
        'form_link_image' => 'รูปภาพลิงก์ (ถ้ามี):',
        'form_link_image_current' => 'รูปภาพปัจจุบัน',
        'form_link_image_upload_hint' => 'อัปโหลดรูปภาพใหม่เพื่อเปลี่ยนรูปภาพปัจจุบัน',
        'form_link_image_url' => 'URL ของรูปภาพลิงก์:',
        'form_link_image_url_hint' => 'URL ที่รูปภาพจะลิงก์ไปเมื่อคลิก',
        'form_map_iframe_url' => 'URL สำหรับ Google Map iframe:',
        'form_map_iframe_url_hint' => 'ใส่ URL ของแผนที่ Google Map ที่ได้จากการ Embed (Embed a map -> copy iframe URL). <br>ตัวอย่าง: `https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3875.7396441274373!2d100.62457107508995!3d13.734206386655812!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x311d61b2c53ba90d%3A0x4442a96acffee7e9!2z4LmB4LiX4Lij4LiZ4LiU4Liy4Lij4LmMIOC4reC4tOC4meC5gOC4leC4reC4o-C5jOC5gOC4meC4iuC4seC5iOC4meC5geC4meC4pSAoVHJhbmRhciBJbnRlcm5hdGlvbmFsIGNvLiBsdGQp!5e0!3m2!1sen!2sth!4v1730371693406!5m2!1sen!2sth`',
        'form_map_preview' => 'ตัวอย่างแผนที่ปัจจุบัน:',
        'button_save' => 'บันทึกการเปลี่ยนแปลง',
        'confirm_title' => 'ยืนยันการแก้ไข?',
        'confirm_text' => 'คุณต้องการอัปเดตข้อมูลติดต่อนี้ใช่หรือไม่!',
        'confirm_button_text' => 'อัปเดต',
        'success_title' => 'สำเร็จ!',
        'success_text' => 'แก้ไขข้อมูลติดต่อเรียบร้อยแล้ว.',
        'error_title' => 'เกิดข้อผิดพลาด!',
        'error_ajax_fail' => 'ไม่สามารถแก้ไขข้อมูลติดต่อได้: ',
        'no_data_found' => 'ไม่พบข้อมูลการตั้งค่า. โปรดตรวจสอบว่ามีข้อมูลเริ่มต้นในฐานข้อมูล.'
    ],
    'en' => [
        'page_title' => 'Edit Contact Information',
        'heading_title' => 'Edit Contact Information',
        'alert_not_found' => 'Contact settings not found. Please ensure initial data is in the database.',
        'loading' => 'Loading...',
        'form_company_name' => 'Company Name:',
        'form_address' => 'Address:',
        'form_phone' => 'Phone Number:',
        'form_email' => 'Email:',
        'form_hours_weekday' => 'Business Hours (Mon-Fri):',
        'form_hours_saturday' => 'Business Hours (Sat):',
        'form_link_image' => 'Link Image (optional):',
        'form_link_image_current' => 'Current Image',
        'form_link_image_upload_hint' => 'Upload a new image to replace the current one',
        'form_link_image_url' => 'Link Image URL:',
        'form_link_image_url_hint' => 'The URL the image will link to when clicked',
        'form_map_iframe_url' => 'Google Map iframe URL:',
        'form_map_iframe_url_hint' => 'Paste the Google Map URL obtained from the embed option (Embed a map -> copy iframe URL). <br>Example: `https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3875.7396441274373!2d100.62457107508995!3d13.734206386655812!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x311d61b2c53ba90d%3A0x4442a96acffee7e9!2z4LmB4LiX4Lij4LiZ4LiU4Liy4Lij4LmMIOC4reC4tOC4meC5gOC4leC4reC4o-C5jOC5gOC4meC4iuC4seC5iOC4meC5geC4meC4pSAoVHJhbmRhciBJbnRlcm5hdGlvbmFsIGNvLiBsdGQp!5e0!3m2!1sen!2sth!4v1730371693406!5m2!1sen!2sth`',
        'form_map_preview' => 'Current Map Preview:',
        'button_save' => 'Save Changes',
        'confirm_title' => 'Confirm Changes?',
        'confirm_text' => 'Are you sure you want to update this contact information!',
        'confirm_button_text' => 'Update',
        'success_title' => 'Success!',
        'success_text' => 'Contact information has been successfully updated.',
        'error_title' => 'An error occurred!',
        'error_ajax_fail' => 'Could not update contact information: ',
        'no_data_found' => 'Contact settings not found. Please ensure initial data is in the database.'
    ],
    'cn' => [
        'page_title' => '编辑联系信息',
        'heading_title' => '编辑联系信息',
        'alert_not_found' => '未找到联系设置。请确保数据库中有初始数据。',
        'loading' => '加载中...',
        'form_company_name' => '公司名称：',
        'form_address' => '地址：',
        'form_phone' => '电话号码：',
        'form_email' => '电子邮件：',
        'form_hours_weekday' => '营业时间（周一至周五）：',
        'form_hours_saturday' => '营业时间（周六）：',
        'form_link_image' => '链接图片（可选）：',
        'form_link_image_current' => '当前图片',
        'form_link_image_upload_hint' => '上传新图片以替换当前图片',
        'form_link_image_url' => '链接图片网址：',
        'form_link_image_url_hint' => '点击图片后将链接到的网址',
        'form_map_iframe_url' => '谷歌地图内嵌网址：',
        'form_map_iframe_url_hint' => '粘贴从谷歌地图嵌入选项中获取的网址 (嵌入地图 -> 复制内嵌网址)。<br>示例: `https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3875.7396441274373!2d100.62457107508995!3d13.734206386655812!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x311d61b2c53ba90d%3A0x4442a96acffee7e9!2z4LmB4LiX4Lij4LiZ4LiU4Liy4Lij4LmMIOC4reC4tOC4meC5gOC4leC4reC4o-C5jOC5gOC4meC4iuC4seC5iOC4meC5geC4meC4pSAoVHJhbmRhciBJbnRlcm5hdGlvbmFsIGNvLiBsdGQp!5e0!3m2!1sen!2sth!4v1730371693406!5m2!1sen!2sth`',
        'form_map_preview' => '当前地图预览：',
        'button_save' => '保存更改',
        'confirm_title' => '确认更改？',
        'confirm_text' => '您确定要更新此联系信息吗？',
        'confirm_button_text' => '更新',
        'success_title' => '成功！',
        'success_text' => '联系信息已成功更新。',
        'error_title' => '发生错误！',
        'error_ajax_fail' => '无法更新联系信息：',
        'no_data_found' => '未找到联系设置。请确保数据库中有初始数据。'
    ],
    'jp' => [
        'page_title' => '連絡先情報の編集',
        'heading_title' => '連絡先情報の編集',
        'alert_not_found' => '連絡先設定が見つかりません。データベースに初期データがあることを確認してください。',
        'loading' => '読み込み中...',
        'form_company_name' => '会社名：',
        'form_address' => '住所：',
        'form_phone' => '電話番号：',
        'form_email' => 'メールアドレス：',
        'form_hours_weekday' => '営業時間（月～金）：',
        'form_hours_saturday' => '営業時間（土）：',
        'form_link_image' => 'リンク画像（任意）：',
        'form_link_image_current' => '現在の画像',
        'form_link_image_upload_hint' => '現在の画像を置き換えるには、新しい画像をアップロードしてください',
        'form_link_image_url' => 'リンク画像URL：',
        'form_link_image_url_hint' => '画像がクリックされたときにリンクするURL',
        'form_map_iframe_url' => 'Googleマップiframe URL：',
        'form_map_iframe_url_hint' => '埋め込みオプションから取得したGoogleマップのURLを貼り付けます（地図を埋め込む -> iframe URLをコピー）。<br>例: `https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3875.7396441274373!2d100.62457107508995!3d13.734206386655812!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x311d61b2c53ba90d%3A0x4442a96acffee7e9!2z4LmB4LiX4Lij4LiZ4LiU4Liy4Lij4LmMIOC4reC4tOC4meC5gOC4leC4reC4o-C5jOC5gOC4meC4iuC4seC5iOC4meC5geC4meC4pSAoVHJhbmRhciBJbnRlcm5hdGlvbmFsIGNvLiBsdGQp!5e0!3m2!1sen!2sth!4v1730371693406!5m2!1sen!2sth`',
        'form_map_preview' => '現在の地図プレビュー：',
        'button_save' => '変更を保存',
        'confirm_title' => '変更を確認しますか？',
        'confirm_text' => 'この連絡先情報を更新しますか？',
        'confirm_button_text' => '更新',
        'success_title' => '成功！',
        'success_text' => '連絡先情報が正常に更新されました。',
        'error_title' => 'エラーが発生しました！',
        'error_ajax_fail' => '連絡先情報を更新できませんでした：',
        'no_data_found' => '連絡先設定が見つかりません。データベースに初期データがあることを確認してください。'
    ],
    'kr' => [
        'page_title' => '연락처 정보 편집',
        'heading_title' => '연락처 정보 편집',
        'alert_not_found' => '연락처 설정을 찾을 수 없습니다. 데이터베이스에 초기 데이터가 있는지 확인하세요.',
        'loading' => '로딩 중...',
        'form_company_name' => '회사 이름:',
        'form_address' => '주소:',
        'form_phone' => '전화번호:',
        'form_email' => '이메일:',
        'form_hours_weekday' => '영업 시간 (월-금):',
        'form_hours_saturday' => '영업 시간 (토):',
        'form_link_image' => '링크 이미지 (선택 사항):',
        'form_link_image_current' => '현재 이미지',
        'form_link_image_upload_hint' => '현재 이미지를 바꾸려면 새 이미지를 업로드하세요',
        'form_link_image_url' => '링크 이미지 URL:',
        'form_link_image_url_hint' => '클릭 시 이미지가 연결될 URL',
        'form_map_iframe_url' => '구글 지도 iframe URL:',
        'form_map_iframe_url_hint' => '임베드 옵션에서 얻은 구글 지도 URL을 붙여넣으세요 (지도 임베드 -> iframe URL 복사).<br>예시: `https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3875.7396441274373!2d100.62457107508995!3d13.734206386655812!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x311d61b2c53ba90d%3A0x4442a96acffee7e9!2z4LmB4LiX4Lij4LiZ4LiU4Liy4Lij4LmMIOC4reC4tOC4meC5gOC4leC4reC4o-C5jOC5gOC4meC4iuC4seC5iOC4meC5geC4meC4pSAoVHJhbmRhciBJbnRlcm5hdGlvbmFsIGNvLiBsdGQp!5e0!3m2!1sen!2sth!4v1730371693406!5m2!1sen!2sth`',
        'form_map_preview' => '현재 지도 미리보기:',
        'button_save' => '변경 사항 저장',
        'confirm_title' => '변경을 확인하시겠습니까?',
        'confirm_text' => '이 연락처 정보를 업데이트하시겠습니까!',
        'confirm_button_text' => '업데이트',
        'success_title' => '성공!',
        'success_text' => '연락처 정보가 성공적으로 업데이트되었습니다.',
        'error_title' => '오류가 발생했습니다!',
        'error_ajax_fail' => '연락처 정보를 업데이트할 수 없습니다:',
        'no_data_found' => '연락처 설정을 찾을 수 없습니다. 데이터베이스에 초기 데이터가 있는지 확인하세요.'
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

// require_once(__DIR__ . '/../../../lib/connect.php');
// require_once(__DIR__ . '/../../../lib/base_directory.php'); // ตรวจสอบ Path ให้ถูกต้อง

$contact_id = 1; // ID ของ Contact Settings (เราใช้แค่ 1 ชุด)

$stmt = $conn->prepare("SELECT * FROM contact_settings WHERE id = ?");
$stmt->bind_param("i", $contact_id);
$stmt->execute();
$result = $stmt->get_result();
$contact_data = $result->fetch_assoc();
$stmt->close();

if (!$contact_data) {
    echo "<script>alert('" . $text['alert_not_found'] . "'); window.location.href='../dashboard.php';</script>";
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
        .line-ref {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 15px;
            border-left: 5px solid #f57c00;
            padding-left: 10px;
            color: #333;
        }
        .form-section {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .form-section label {
            font-weight: bold;
            margin-bottom: 8px;
            display: block;
        }
        .image-preview {
            max-width: 150px;
            height: auto;
            border: 1px solid #ddd;
            padding: 5px;
            margin-top: 10px;
            display: block;
        }
        #loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
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
    <div class="box-content p-4 bg-light rounded shadow-sm">
        <h4 class="line-ref">
            <i class="fa-solid fa-edit"></i> <?= $text['heading_title'] ?>
        </h4>

        <form id="editContactForm" enctype="multipart/form-data">
            <input type="hidden" name="contact_id" value="<?= htmlspecialchars($contact_data['id']) ?>">
            <input type="hidden" name="action" value="edit_contact">
            <input type="hidden" name="current_link_image_path" value="<?= htmlspecialchars($contact_data['link_image_path']) ?>">

            <div class="form-section">
                <label for="company_name"><?= $text['form_company_name'] ?></label>
                <input type="text" id="company_name" name="company_name" class="form-control" value="<?= htmlspecialchars($contact_data['company_name']) ?>">
            </div>

            <div class="form-section">
                <label for="address"><?= $text['form_address'] ?></label>
                <input type="text" id="address" name="address" class="form-control" value="<?= htmlspecialchars($contact_data['address']) ?>">
            </div>

            <div class="form-section">
                <label for="phone"><?= $text['form_phone'] ?></label>
                <input type="text" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($contact_data['phone']) ?>">
            </div>

            <div class="form-section">
                <label for="email"><?= $text['form_email'] ?></label>
                <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($contact_data['email']) ?>">
            </div>

            <div class="form-section">
                <label for="hours_weekday"><?= $text['form_hours_weekday'] ?></label>
                <input type="text" id="hours_weekday" name="hours_weekday" class="form-control" value="<?= htmlspecialchars($contact_data['hours_weekday']) ?>">
            </div>

            <div class="form-section">
                <label for="hours_saturday"><?= $text['form_hours_saturday'] ?></label>
                <input type="text" id="hours_saturday" name="hours_saturday" class="form-control" value="<?= htmlspecialchars($contact_data['hours_saturday']) ?>">
            </div>

            <div class="form-section">
                <label for="link_image"><?= $text['form_link_image'] ?></label>
                <?php if ($contact_data['link_image_path']): ?>
                    <img src="<?= htmlspecialchars($contact_data['link_image_path']) ?>" alt="Link Image" class="image-preview" id="linkImagePreview">
                    <p class="text-muted mt-2"><?= $text['form_link_image_current'] ?></p>
                <?php else: ?>
                    <img src="" alt="Link Image" class="image-preview" id="linkImagePreview" style="display:none;">
                <?php endif; ?>
                <input type="file" id="link_image" name="link_image" class="form-control mt-2" accept="image/*">
                <small class="form-text text-muted"><?= $text['form_link_image_upload_hint'] ?></small>
            </div>

            <div class="form-section">
                <label for="link_image_url"><?= $text['form_link_image_url'] ?></label>
                <input type="url" id="link_image_url" name="link_image_url" class="form-control" value="<?= htmlspecialchars($contact_data['link_image_url']) ?>">
                <small class="form-text text-muted"><?= $text['form_link_image_url_hint'] ?></small>
            </div>

            <div class="form-section">
                <label for="map_iframe_url"><?= $text['form_map_iframe_url'] ?></label>
                <textarea id="map_iframe_url" name="map_iframe_url" class="form-control" rows="4"><?= htmlspecialchars($contact_data['map_iframe_url']) ?></textarea>
                <small class="form-text text-muted"><?= $text['form_map_iframe_url_hint'] ?></small>
                <?php if ($contact_data['map_iframe_url']): ?>
                    <div class="mt-3">
                        <p><?= $text['form_map_preview'] ?></p>
                        <iframe src="<?= htmlspecialchars($contact_data['map_iframe_url']) ?>" width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                <?php endif; ?>
            </div>

            <div class="text-end mt-4">
                <button type="submit" id="submitEditContact" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?= $text['button_save'] ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script src='../js/index_.js?v=<?php echo time(); ?>'></script>
<script>
    // Preview image before upload
    document.getElementById('link_image').addEventListener('change', function(event) {
        const [file] = event.target.files;
        if (file) {
            const preview = document.getElementById('linkImagePreview');
            preview.src = URL.createObjectURL(file);
            preview.style.display = 'block';
        }
    });

    // Form Submission Handler
    $('#submitEditContact').on('click', function(e) {
        e.preventDefault(); // Prevent default form submission

        var formData = new FormData($('#editContactForm')[0]);

        Swal.fire({
            title: "<?= $text['confirm_title'] ?>",
            text: "<?= $text['confirm_text'] ?>",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#FFC107",
            cancelButtonColor: "#d33",
            confirmButtonText: "<?= $text['confirm_button_text'] ?>"
        }).then((result) => {
            if (result.isConfirmed) {
                $('#loading-overlay').fadeIn(); // Show loading overlay

                $.ajax({
                    url: "actions/process_contact.php", // Target the new process_contact.php
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        $('#loading-overlay').fadeOut(); // Hide loading overlay
                        if (response.status === 'success') {
                            Swal.fire(
                                '<?= $text['success_title'] ?>',
                                '<?= $text['success_text'] ?>',
                                'success'
                            ).then(() => {
                                // Reload page to reflect changes
                                location.reload();
                            });
                        } else {
                            Swal.fire(
                                '<?= $text['error_title'] ?>',
                                response.message,
                                'error'
                            );
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#loading-overlay').fadeOut(); // Hide loading overlay
                        console.error("AJAX Error:", status, error, xhr.responseText);
                        Swal.fire(
                            '<?= $text['error_title'] ?>',
                            '<?= $text['error_ajax_fail'] ?>' + error,
                            'error'
                        );
                    }
                });
            }
        });
    });
</script>

</body>
</html>