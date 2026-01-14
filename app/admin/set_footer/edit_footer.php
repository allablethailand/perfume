<?php
// edit_footer.php
include '../check_permission.php';

$footer_id = 1; // ID ของ Footer Settings (เราใช้แค่ 1 ชุด)

$stmt = $conn->prepare("SELECT * FROM footer_settings WHERE id = ?");
$stmt->bind_param("i", $footer_id);
$stmt->execute();
$result = $stmt->get_result();
$footer_data = $result->fetch_assoc();
$stmt->close();

if (!$footer_data) {
    echo "<script>alert('Footer settings not found. Please ensure initial data is in the database.'); window.location.href='../dashboard.php';</script>";
    exit;
}

$social_links = json_decode($footer_data['social_links_json'], true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $social_links = [];
    error_log("Error decoding social_links_json: " . json_last_error_msg());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไข Footer</title>

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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/spectrum/1.8.0/spectrum.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/spectrum/1.8.0/spectrum.min.css" />

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
        .social-link-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            background-color: #e9e9e9;
            padding: 8px;
            border-radius: 5px;
        }
        .social-link-item i {
            font-size: 20px;
            margin-right: 10px;
            width: 25px;
            text-align: center;
        }
        .social-link-item input {
            flex-grow: 1;
            margin-right: 10px;
        }
        .social-link-item button {
            margin-left: 5px;
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
        .sp-replacer {
            display: flex;
            align-items: center;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            height: calc(2.25rem + 2px);
            padding: 0.375rem 0.75rem;
            width: 100%;
            cursor: pointer;
        }
        .sp-preview {
            width: 24px;
            height: 24px;
            border: 1px solid #ccc;
            margin-right: 10px;
            border-radius: 3px;
        }
        .language-switcher {
            margin-bottom: 20px;
            text-align: right;
        }
        .lang-button {
            cursor: pointer;
            border: none;
            background: none;
            padding: 5px 10px;
            opacity: 0.5;
        }
        .lang-button.active {
            opacity: 1;
            border-bottom: 2px solid #007bff;
        }
    </style>
</head>
<body>
<?php include '../template/header.php'; ?>

<div id="loading-overlay">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<div class="container mt-4">
    <div class="box-content p-4 bg-light rounded shadow-sm">
        <h4 class="line-ref">
            <i class="fa-solid fa-edit"></i> แก้ไข Footer
        </h4>

        <div class="language-switcher">
            <button type="button" class="lang-button active" data-lang="th">
                <img src="https://flagcdn.com/w320/th.png" alt="Thai Flag" style=' width: 36px;
                                                margin-right: 8px;'>
            </button>
            <button type="button" class="lang-button" data-lang="en">
                <img src="https://flagcdn.com/w320/gb.png" alt="English Flag" style=' width: 36px;
                                                margin-right: 8px;'>
            </button>
            <button type="button" class="lang-button" data-lang="cn">
                <img src="https://flagcdn.com/w320/cn.png" alt="Chinese Flag" style=' width: 36px;
                                                margin-right: 8px;'>
            </button>
            <button type="button" class="lang-button" data-lang="jp">
                <img src="https://flagcdn.com/w320/jp.png" alt="Japanese Flag" style=' width: 36px;
                                                margin-right: 8px;'>
            </button>
            <button type="button" class="lang-button" data-lang="kr">
                <img src="https://flagcdn.com/w320/kr.png" alt="Korean Flag" style=' width: 36px;
                                                margin-right: 8px;'>
            </button>
        </div>

        <form id="editFooterForm">
            <input type="hidden" name="footer_id" value="<?= htmlspecialchars($footer_data['id']) ?>">
            <input type="hidden" name="action" value="edit_footer">

            <div id="form-th" class="language-form">
                <div class="form-section">
                    <label for="bg_color">สีพื้นหลัง Footer:</label>
                    <input type="text" id="bg_color" name="bg_color" class="form-control" value="<?= htmlspecialchars($footer_data['bg_color']) ?>">
                </div>
                <div class="form-section">
                    <label for="footer_top_title">หัวข้อส่วนบน (Register Title):</label>
                    <input type="text" id="footer_top_title" name="footer_top_title" class="form-control" value="<?= htmlspecialchars($footer_data['footer_top_title']) ?>">
                </div>
                <div class="form-section">
                    <label for="footer_top_subtitle">คำอธิบายส่วนบน (Register Subtitle):</label>
                    <textarea id="footer_top_subtitle" name="footer_top_subtitle" class="form-control" rows="2"><?= htmlspecialchars($footer_data['footer_top_subtitle']) ?></textarea>
                </div>
                <div class="form-section">
                    <label for="about_heading">หัวข้อเกี่ยวกับเรา:</label>
                    <input type="text" id="about_heading" name="about_heading" class="form-control" value="<?= htmlspecialchars($footer_data['about_heading']) ?>">
                </div>
                <div class="form-section">
                    <label for="about_text">ข้อความเกี่ยวกับเรา:</label>
                    <textarea id="about_text" name="about_text" class="form-control" rows="5"><?= htmlspecialchars($footer_data['about_text']) ?></textarea>
                </div>
                <div class="form-section">
                    <label for="contact_heading">หัวข้อติดต่อเรา:</label>
                    <input type="text" id="contact_heading" name="contact_heading" class="form-control" value="<?= htmlspecialchars($footer_data['contact_heading']) ?>">
                </div>
                <div class="form-section">
                    <label for="contact_address">ที่อยู่:</label>
                    <input type="text" id="contact_address" name="contact_address" class="form-control" value="<?= htmlspecialchars($footer_data['contact_address']) ?>">
                </div>
                <div class="form-section">
                    <label for="contact_phone">เบอร์โทรศัพท์:</label>
                    <input type="text" id="contact_phone" name="contact_phone" class="form-control" value="<?= htmlspecialchars($footer_data['contact_phone']) ?>">
                </div>
                <div class="form-section">
                    <label for="contact_email">อีเมล:</label>
                    <input type="email" id="contact_email" name="contact_email" class="form-control" value="<?= htmlspecialchars($footer_data['contact_email']) ?>">
                </div>
                <div class="form-section">
                    <label for="contact_hours_wk">เวลาทำการ (จันทร์-ศุกร์):</label>
                    <input type="text" id="contact_hours_wk" name="contact_hours_wk" class="form-control" value="<?= htmlspecialchars($footer_data['contact_hours_wk']) ?>">
                </div>
                <div class="form-section">
                    <label for="contact_hours_sat">เวลาทำการ (เสาร์):</label>
                    <input type="text" id="contact_hours_sat" name="contact_hours_sat" class="form-control" value="<?= htmlspecialchars($footer_data['contact_hours_sat']) ?>">
                </div>
                <div class="form-section">
                    <label for="social_heading">หัวข้อ Social Media:</label>
                    <input type="text" id="social_heading" name="social_heading" class="form-control" value="<?= htmlspecialchars($footer_data['social_heading']) ?>">
                </div>
                <div class="form-section">
                    <label>Social Media Links:</label>
                    <div id="socialLinksContainer">
                    </div>
                    <button type="button" class="btn btn-success mt-3" id="addSocialLink">
                        <i class="fas fa-plus"></i> เพิ่ม Social Link
                    </button>
                </div>
                <div class="form-section">
                    <label for="copyright_text">ข้อความลิขสิทธิ์ (Copyright Text):</label>
                    <input type="text" id="copyright_text" name="copyright_text" class="form-control" value="<?= htmlspecialchars($footer_data['copyright_text']) ?>">
                </div>
            </div>

            <div id="form-en" class="language-form" style="display: none;">
                <div class="form-section">
                    <label for="bg_color_en">Footer Background Color (EN):</label>
                    <input type="text" id="bg_color_en" name="bg_color_en" class="form-control" value="<?= htmlspecialchars($footer_data['bg_color_en'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="footer_top_title_en">Register Title (EN):</label>
                    <input type="text" id="footer_top_title_en" name="footer_top_title_en" class="form-control" value="<?= htmlspecialchars($footer_data['footer_top_title_en'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="footer_top_subtitle_en">Register Subtitle (EN):</label>
                    <textarea id="footer_top_subtitle_en" name="footer_top_subtitle_en" class="form-control" rows="2"><?= htmlspecialchars($footer_data['footer_top_subtitle_en'] ?? '') ?></textarea>
                </div>
                <div class="form-section">
                    <label for="about_heading_en">About Us Heading (EN):</label>
                    <input type="text" id="about_heading_en" name="about_heading_en" class="form-control" value="<?= htmlspecialchars($footer_data['about_heading_en'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="about_text_en">About Us Text (EN):</label>
                    <textarea id="about_text_en" name="about_text_en" class="form-control" rows="5"><?= htmlspecialchars($footer_data['about_text_en'] ?? '') ?></textarea>
                </div>
                <div class="form-section">
                    <label for="contact_heading_en">Contact Us Heading (EN):</label>
                    <input type="text" id="contact_heading_en" name="contact_heading_en" class="form-control" value="<?= htmlspecialchars($footer_data['contact_heading_en'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="contact_address_en">Address (EN):</label>
                    <input type="text" id="contact_address_en" name="contact_address_en" class="form-control" value="<?= htmlspecialchars($footer_data['contact_address_en'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="contact_phone_en">เบอร์โทรศัพท์ (EN):</label>
                    <input type="text" id="contact_phone_en" name="contact_phone_en" class="form-control" value="<?= htmlspecialchars($footer_data['contact_phone_en'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="contact_email_en">อีเมล (EN):</label>
                    <input type="email" id="contact_email_en" name="contact_email_en" class="form-control" value="<?= htmlspecialchars($footer_data['contact_email_en'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="contact_hours_wk_en">Opening Hours (Mon-Fri) (EN):</label>
                    <input type="text" id="contact_hours_wk_en" name="contact_hours_wk_en" class="form-control" value="<?= htmlspecialchars($footer_data['contact_hours_wk_en'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="contact_hours_sat_en">Opening Hours (Sat) (EN):</label>
                    <input type="text" id="contact_hours_sat_en" name="contact_hours_sat_en" class="form-control" value="<?= htmlspecialchars($footer_data['contact_hours_sat_en'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="social_heading_en">Social Media Heading (EN):</label>
                    <input type="text" id="social_heading_en" name="social_heading_en" class="form-control" value="<?= htmlspecialchars($footer_data['social_heading_en'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label>Social Media Links:</label>
                    <p class="text-muted">Social Media Links are shared for all languages.</p>
                </div>
                <div class="form-section">
                    <label for="copyright_text_en">Copyright Text (EN):</label>
                    <input type="text" id="copyright_text_en" name="copyright_text_en" class="form-control" value="<?= htmlspecialchars($footer_data['copyright_text_en'] ?? '') ?>">
                </div>
                <button type="button" id="copy-th-data" class="btn btn-info">
                    <i class="fas fa-copy"></i> คัดลอกข้อมูลจากภาษาไทย
                </button>
            </div>
            
            <div id="form-cn" class="language-form" style="display: none;">
                <div class="form-section">
                    <label for="bg_color_cn">Footer Background Color (CN):</label>
                    <input type="text" id="bg_color_cn" name="bg_color_cn" class="form-control" value="<?= htmlspecialchars($footer_data['bg_color_cn'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="footer_top_title_cn">Register Title (CN):</label>
                    <input type="text" id="footer_top_title_cn" name="footer_top_title_cn" class="form-control" value="<?= htmlspecialchars($footer_data['footer_top_title_cn'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="footer_top_subtitle_cn">Register Subtitle (CN):</label>
                    <textarea id="footer_top_subtitle_cn" name="footer_top_subtitle_cn" class="form-control" rows="2"><?= htmlspecialchars($footer_data['footer_top_subtitle_cn'] ?? '') ?></textarea>
                </div>
                <div class="form-section">
                    <label for="about_heading_cn">About Us Heading (CN):</label>
                    <input type="text" id="about_heading_cn" name="about_heading_cn" class="form-control" value="<?= htmlspecialchars($footer_data['about_heading_cn'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="about_text_cn">About Us Text (CN):</label>
                    <textarea id="about_text_cn" name="about_text_cn" class="form-control" rows="5"><?= htmlspecialchars($footer_data['about_text_cn'] ?? '') ?></textarea>
                </div>
                <div class="form-section">
                    <label for="contact_heading_cn">Contact Us Heading (CN):</label>
                    <input type="text" id="contact_heading_cn" name="contact_heading_cn" class="form-control" value="<?= htmlspecialchars($footer_data['contact_heading_cn'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="contact_address_cn">Address (CN):</label>
                    <input type="text" id="contact_address_cn" name="contact_address_cn" class="form-control" value="<?= htmlspecialchars($footer_data['contact_address_cn'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="contact_phone_cn">เบอร์โทรศัพท์ (CN):</label>
                    <input type="text" id="contact_phone_cn" name="contact_phone_cn" class="form-control" value="<?= htmlspecialchars($footer_data['contact_phone_cn'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="contact_email_cn">อีเมล (CN):</label>
                    <input type="email" id="contact_email_cn" name="contact_email_cn" class="form-control" value="<?= htmlspecialchars($footer_data['contact_email_cn'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="contact_hours_wk_cn">Opening Hours (Mon-Fri) (CN):</label>
                    <input type="text" id="contact_hours_wk_cn" name="contact_hours_wk_cn" class="form-control" value="<?= htmlspecialchars($footer_data['contact_hours_wk_cn'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="contact_hours_sat_cn">Opening Hours (Sat) (CN):</label>
                    <input type="text" id="contact_hours_sat_cn" name="contact_hours_sat_cn" class="form-control" value="<?= htmlspecialchars($footer_data['contact_hours_sat_cn'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="social_heading_cn">Social Media Heading (CN):</label>
                    <input type="text" id="social_heading_cn" name="social_heading_cn" class="form-control" value="<?= htmlspecialchars($footer_data['social_heading_cn'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label>Social Media Links:</label>
                    <p class="text-muted">Social Media Links are shared for all languages.</p>
                </div>
                <div class="form-section">
                    <label for="copyright_text_cn">Copyright Text (CN):</label>
                    <input type="text" id="copyright_text_cn" name="copyright_text_cn" class="form-control" value="<?= htmlspecialchars($footer_data['copyright_text_cn'] ?? '') ?>">
                </div>
                <button type="button" id="copy-en-data-cn" class="btn btn-info">
                    <i class="fas fa-copy"></i> คัดลอกข้อมูลจากภาษาอังกฤษ
                </button>
            </div>
            
            <div id="form-jp" class="language-form" style="display: none;">
                <div class="form-section">
                    <label for="bg_color_jp">Footer Background Color (JP):</label>
                    <input type="text" id="bg_color_jp" name="bg_color_jp" class="form-control" value="<?= htmlspecialchars($footer_data['bg_color_jp'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="footer_top_title_jp">Register Title (JP):</label>
                    <input type="text" id="footer_top_title_jp" name="footer_top_title_jp" class="form-control" value="<?= htmlspecialchars($footer_data['footer_top_title_jp'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="footer_top_subtitle_jp">Register Subtitle (JP):</label>
                    <textarea id="footer_top_subtitle_jp" name="footer_top_subtitle_jp" class="form-control" rows="2"><?= htmlspecialchars($footer_data['footer_top_subtitle_jp'] ?? '') ?></textarea>
                </div>
                <div class="form-section">
                    <label for="about_heading_jp">About Us Heading (JP):</label>
                    <input type="text" id="about_heading_jp" name="about_heading_jp" class="form-control" value="<?= htmlspecialchars($footer_data['about_heading_jp'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="about_text_jp">About Us Text (JP):</label>
                    <textarea id="about_text_jp" name="about_text_jp" class="form-control" rows="5"><?= htmlspecialchars($footer_data['about_text_jp'] ?? '') ?></textarea>
                </div>
                <div class="form-section">
                    <label for="contact_heading_jp">Contact Us Heading (JP):</label>
                    <input type="text" id="contact_heading_jp" name="contact_heading_jp" class="form-control" value="<?= htmlspecialchars($footer_data['contact_heading_jp'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="contact_address_jp">Address (JP):</label>
                    <input type="text" id="contact_address_jp" name="contact_address_jp" class="form-control" value="<?= htmlspecialchars($footer_data['contact_address_jp'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="contact_phone_jp">เบอร์โทรศัพท์ (JP):</label>
                    <input type="text" id="contact_phone_jp" name="contact_phone_jp" class="form-control" value="<?= htmlspecialchars($footer_data['contact_phone_jp'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="contact_email_jp">อีเมล (JP):</label>
                    <input type="email" id="contact_email_jp" name="contact_email_jp" class="form-control" value="<?= htmlspecialchars($footer_data['contact_email_jp'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="contact_hours_wk_jp">Opening Hours (Mon-Fri) (JP):</label>
                    <input type="text" id="contact_hours_wk_jp" name="contact_hours_wk_jp" class="form-control" value="<?= htmlspecialchars($footer_data['contact_hours_wk_jp'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="contact_hours_sat_jp">Opening Hours (Sat) (JP):</label>
                    <input type="text" id="contact_hours_sat_jp" name="contact_hours_sat_jp" class="form-control" value="<?= htmlspecialchars($footer_data['contact_hours_sat_jp'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="social_heading_jp">Social Media Heading (JP):</label>
                    <input type="text" id="social_heading_jp" name="social_heading_jp" class="form-control" value="<?= htmlspecialchars($footer_data['social_heading_jp'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label>Social Media Links:</label>
                    <p class="text-muted">Social Media Links are shared for all languages.</p>
                </div>
                <div class="form-section">
                    <label for="copyright_text_jp">Copyright Text (JP):</label>
                    <input type="text" id="copyright_text_jp" name="copyright_text_jp" class="form-control" value="<?= htmlspecialchars($footer_data['copyright_text_jp'] ?? '') ?>">
                </div>
                <button type="button" id="copy-en-data-jp" class="btn btn-info">
                    <i class="fas fa-copy"></i> คัดลอกข้อมูลจากภาษาอังกฤษ
                </button>
            </div>
            
            <div id="form-kr" class="language-form" style="display: none;">
                <div class="form-section">
                    <label for="bg_color_kr">Footer Background Color (KR):</label>
                    <input type="text" id="bg_color_kr" name="bg_color_kr" class="form-control" value="<?= htmlspecialchars($footer_data['bg_color_kr'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="footer_top_title_kr">Register Title (KR):</label>
                    <input type="text" id="footer_top_title_kr" name="footer_top_title_kr" class="form-control" value="<?= htmlspecialchars($footer_data['footer_top_title_kr'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="footer_top_subtitle_kr">Register Subtitle (KR):</label>
                    <textarea id="footer_top_subtitle_kr" name="footer_top_subtitle_kr" class="form-control" rows="2"><?= htmlspecialchars($footer_data['footer_top_subtitle_kr'] ?? '') ?></textarea>
                </div>
                <div class="form-section">
                    <label for="about_heading_kr">About Us Heading (KR):</label>
                    <input type="text" id="about_heading_kr" name="about_heading_kr" class="form-control" value="<?= htmlspecialchars($footer_data['about_heading_kr'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="about_text_kr">About Us Text (KR):</label>
                    <textarea id="about_text_kr" name="about_text_kr" class="form-control" rows="5"><?= htmlspecialchars($footer_data['about_text_kr'] ?? '') ?></textarea>
                </div>
                <div class="form-section">
                    <label for="contact_heading_kr">Contact Us Heading (KR):</label>
                    <input type="text" id="contact_heading_kr" name="contact_heading_kr" class="form-control" value="<?= htmlspecialchars($footer_data['contact_heading_kr'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="contact_address_kr">Address (KR):</label>
                    <input type="text" id="contact_address_kr" name="contact_address_kr" class="form-control" value="<?= htmlspecialchars($footer_data['contact_address_kr'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="contact_phone_kr">เบอร์โทรศัพท์ (KR):</label>
                    <input type="text" id="contact_phone_kr" name="contact_phone_kr" class="form-control" value="<?= htmlspecialchars($footer_data['contact_phone_kr'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="contact_email_kr">อีเมล (KR):</label>
                    <input type="email" id="contact_email_kr" name="contact_email_kr" class="form-control" value="<?= htmlspecialchars($footer_data['contact_email_kr'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="contact_hours_wk_kr">Opening Hours (Mon-Fri) (KR):</label>
                    <input type="text" id="contact_hours_wk_kr" name="contact_hours_wk_kr" class="form-control" value="<?= htmlspecialchars($footer_data['contact_hours_wk_kr'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="contact_hours_sat_kr">Opening Hours (Sat) (KR):</label>
                    <input type="text" id="contact_hours_sat_kr" name="contact_hours_sat_kr" class="form-control" value="<?= htmlspecialchars($footer_data['contact_hours_sat_kr'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label for="social_heading_kr">Social Media Heading (KR):</label>
                    <input type="text" id="social_heading_kr" name="social_heading_kr" class="form-control" value="<?= htmlspecialchars($footer_data['social_heading_kr'] ?? '') ?>">
                </div>
                <div class="form-section">
                    <label>Social Media Links:</label>
                    <p class="text-muted">Social Media Links are shared for all languages.</p>
                </div>
                <div class="form-section">
                    <label for="copyright_text_kr">Copyright Text (KR):</label>
                    <input type="text" id="copyright_text_kr" name="copyright_text_kr" class="form-control" value="<?= htmlspecialchars($footer_data['copyright_text_kr'] ?? '') ?>">
                </div>
                <button type="button" id="copy-en-data-kr" class="btn btn-info">
                    <i class="fas fa-copy"></i> คัดลอกข้อมูลจากภาษาอังกฤษ
                </button>
            </div>


            <div class="text-end mt-4">
                <button type="submit" id="submitEditFooter" class="btn btn-primary">
                    <i class="fas fa-save"></i> บันทึกการเปลี่ยนแปลง
                </button>
            </div>
        </form>
    </div>
</div>

<script src='../js/index_.js?v=<?php echo time(); ?>'></script>
<script>
    const footerData = <?= json_encode($footer_data); ?>;

    // Initialize Spectrum Color Picker
    $("#bg_color").spectrum({
        color: "<?= htmlspecialchars($footer_data['bg_color']) ?>",
        flat: false, showInput: true, allowEmpty: false,
        showPalette: true, palette: [ ["#393939", "#000000", "#FFFFFF", "#FF5733", "#33FF57", "#3357FF"] ],
        showInitial: true, showSelectionPalette: true, preferredFormat: "hex",
    });

    // Handle Social Links Dynamic Management (unchanged from original code)
    let socialLinks = <?= json_encode($social_links); ?>;
    function renderSocialLinks() {
        const container = $('#socialLinksContainer');
        container.empty();
        if (socialLinks.length === 0) {
            container.append('<p class="text-muted">ยังไม่มี Social Link. กรุณาเพิ่มลิงก์.</p>');
        }
        socialLinks.forEach((link, index) => {
            const itemHtml = `<div class="social-link-item">
                <i class="${link.icon}" style="color: ${link.color};"></i>
                <input type="text" class="form-control form-control-sm" placeholder="Icon Class (e.g., fab fa-facebook-f)" value="${link.icon}" data-index="${index}" data-field="icon">
                <input type="text" class="form-control form-control-sm ms-2" placeholder="URL (e.g., https://facebook.com/)" value="${link.url}" data-index="${index}" data-field="url">
                <input type="text" class="form-control form-control-sm ms-2 social-color-picker" value="${link.color}" data-index="${index}" data-field="color">
                <button type="button" class="btn btn-danger btn-sm delete-social-link" data-index="${index}"><i class="fas fa-trash"></i></button>
            </div>`;
            container.append(itemHtml);
        });
        $('.social-color-picker').each(function() {
            const initialColor = $(this).val();
            $(this).spectrum({
                color: initialColor,
                flat: false, showInput: true, allowEmpty: false,
                showPalette: true, preferredFormat: "hex",
                change: function(color) {
                    const index = $(this).data('index');
                    socialLinks[index].color = color.toHexString();
                    $(this).prevAll('i').first().css('color', color.toHexString());
                }
            });
            const index = $(this).data('index');
            $(this).prevAll('i').first().css('color', socialLinks[index].color);
        });
    }
    $('#addSocialLink').on('click', function() {
        socialLinks.push({ icon: '', url: '', color: '#393939' });
        renderSocialLinks();
    });
    $('#socialLinksContainer').on('input', 'input', function() {
        const index = $(this).data('index');
        const field = $(this).data('field');
        socialLinks[index][field] = $(this).val();
        if (field === 'icon') {
            $(this).prevAll('i').first().attr('class', $(this).val());
        }
    });
    $('#socialLinksContainer').on('click', '.delete-social-link', function() {
        const index = $(this).data('index');
        socialLinks.splice(index, 1);
        renderSocialLinks();
    });
    renderSocialLinks();

    // Language switcher logic
    $('.lang-button').on('click', function() {
        const lang = $(this).data('lang');
        $('.lang-button').removeClass('active');
        $(this).addClass('active');
        $('.language-form').hide();
        $('#form-' + lang).show();
    });

    // Copy data from Thai to English
    $('#copy-th-data').on('click', function() {
        // Mapping of Thai fields to English fields
        const mapping = {
            'footer_top_title': 'footer_top_title_en',
            'footer_top_subtitle': 'footer_top_subtitle_en',
            'about_heading': 'about_heading_en',
            'about_text': 'about_text_en',
            'contact_heading': 'contact_heading_en',
            'contact_address': 'contact_address_en',
            'social_heading': 'social_heading_en',
            'contact_hours_wk': 'contact_hours_wk_en',
            'contact_hours_sat': 'contact_hours_sat_en'
        };

        for (const th_field in mapping) {
            const en_field = mapping[th_field];
            const th_value = $(`#${th_field}`).val();
            $(`#${en_field}`).val(th_value);
        }

        // คอลัมน์ที่ไม่มี _en ให้ใช้ค่าเดียวกัน
        $('#contact_phone_en').val($('#contact_phone').val());
        $('#contact_email_en').val($('#contact_email').val());
        $('#copyright_text_en').val($('#copyright_text').val());

        Swal.fire('คัดลอกข้อมูลสำเร็จ!', 'ข้อมูลจากภาษาไทยถูกคัดลอกไปยังฟอร์มภาษาอังกฤษแล้ว', 'success');
    });

    // Copy data from English to Chinese
    $('#copy-en-data-cn').on('click', function() {
        const mapping = {
            'bg_color_en': 'bg_color_cn',
            'footer_top_title_en': 'footer_top_title_cn',
            'footer_top_subtitle_en': 'footer_top_subtitle_cn',
            'about_heading_en': 'about_heading_cn',
            'about_text_en': 'about_text_cn',
            'contact_heading_en': 'contact_heading_cn',
            'contact_address_en': 'contact_address_cn',
            'contact_phone_en': 'contact_phone_cn',
            'contact_email_en': 'contact_email_cn',
            'contact_hours_wk_en': 'contact_hours_wk_cn',
            'contact_hours_sat_en': 'contact_hours_sat_cn',
            'social_heading_en': 'social_heading_cn',
            'copyright_text_en': 'copyright_text_cn'
        };

        for (const en_field in mapping) {
            const cn_field = mapping[en_field];
            const en_value = $(`#${en_field}`).val();
            $(`#${cn_field}`).val(en_value);
        }

        Swal.fire('คัดลอกข้อมูลสำเร็จ!', 'ข้อมูลจากภาษาอังกฤษถูกคัดลอกไปยังฟอร์มภาษาจีนแล้ว', 'success');
    });

    // Copy data from English to Japanese (jp) - New function
    $('#copy-en-data-jp').on('click', function() {
        const mapping = {
            'bg_color_en': 'bg_color_jp',
            'footer_top_title_en': 'footer_top_title_jp',
            'footer_top_subtitle_en': 'footer_top_subtitle_jp',
            'about_heading_en': 'about_heading_jp',
            'about_text_en': 'about_text_jp',
            'contact_heading_en': 'contact_heading_jp',
            'contact_address_en': 'contact_address_jp',
            'contact_phone_en': 'contact_phone_jp',
            'contact_email_en': 'contact_email_jp',
            'contact_hours_wk_en': 'contact_hours_wk_jp',
            'contact_hours_sat_en': 'contact_hours_sat_jp',
            'social_heading_en': 'social_heading_jp',
            'copyright_text_en': 'copyright_text_jp'
        };

        for (const en_field in mapping) {
            const jp_field = mapping[en_field];
            const en_value = $(`#${en_field}`).val();
            $(`#${jp_field}`).val(en_value);
        }

        Swal.fire('คัดลอกข้อมูลสำเร็จ!', 'ข้อมูลจากภาษาอังกฤษถูกคัดลอกไปยังฟอร์มภาษาญี่ปุ่นแล้ว', 'success');
    });
    
    // Copy data from English to Korean (kr) - New function
    $('#copy-en-data-kr').on('click', function() {
        const mapping = {
            'bg_color_en': 'bg_color_kr',
            'footer_top_title_en': 'footer_top_title_kr',
            'footer_top_subtitle_en': 'footer_top_subtitle_kr',
            'about_heading_en': 'about_heading_kr',
            'about_text_en': 'about_text_kr',
            'contact_heading_en': 'contact_heading_kr',
            'contact_address_en': 'contact_address_kr',
            'contact_phone_en': 'contact_phone_kr',
            'contact_email_en': 'contact_email_kr',
            'contact_hours_wk_en': 'contact_hours_wk_kr',
            'contact_hours_sat_en': 'contact_hours_sat_kr',
            'social_heading_en': 'social_heading_kr',
            'copyright_text_en': 'copyright_text_kr'
        };

        for (const en_field in mapping) {
            const kr_field = mapping[en_field];
            const en_value = $(`#${en_field}`).val();
            $(`#${kr_field}`).val(en_value);
        }

        Swal.fire('คัดลอกข้อมูลสำเร็จ!', 'ข้อมูลจากภาษาอังกฤษถูกคัดลอกไปยังฟอร์มภาษาเกาหลีแล้ว', 'success');
    });

    // Form Submission Handler
    $('#submitEditFooter').on('click', function(e) {
        e.preventDefault();

        var formData = new FormData($('#editFooterForm')[0]);
        formData.append('social_links_json', JSON.stringify(socialLinks));

        Swal.fire({
            title: "ยืนยันการแก้ไข?",
            text: "คุณต้องการอัปเดต Footer นี้ใช่หรือไม่!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#FFC107",
            cancelButtonColor: "#d33",
            confirmButtonText: "อัปเดต"
        }).then((result) => {
            if (result.isConfirmed) {
                $('#loading-overlay').fadeIn();

                $.ajax({
                    url: "actions/process_footer.php",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        $('#loading-overlay').fadeOut();
                        if (response.status === 'success') {
                            Swal.fire(
                                'สำเร็จ!',
                                'แก้ไข Footer เรียบร้อยแล้ว.',
                                'success'
                            ).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire(
                                'เกิดข้อผิดพลาด!',
                                response.message,
                                'error'
                            );
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#loading-overlay').fadeOut();
                        console.error("AJAX Error:", status, error, xhr.responseText);
                        Swal.fire(
                            'เกิดข้อผิดพลาด!',
                            'ไม่สามารถแก้ไข Footer ได้: ' + error,
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