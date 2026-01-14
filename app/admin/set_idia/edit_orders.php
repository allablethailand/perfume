<?php
// ส่วนที่เพิ่ม: ตรวจสอบและกำหนดภาษาจาก URL
// session_start();
$lang = 'th'; // กำหนดภาษาเริ่มต้นเป็น 'th'
if (isset($_GET['lang'])) {
    $supportedLangs = ['th', 'en', 'cn', 'jp', 'kr'];
    $newLang = $_GET['lang'];
    if (in_array($newLang, $supportedLangs)) {
        $_SESSION['lang'] = $newLang;
        $lang = $newLang;
    } else {
        unset($_SESSION['lang']);
    }
} else {
    // ถ้าไม่มี lang ใน URL ให้ใช้ค่าจาก Session หรือค่าเริ่มต้น 'th'
    if (isset($_SESSION['lang'])) {
        $lang = $_SESSION['lang'];
    }
}

// ส่วนที่เพิ่ม: กำหนดข้อความตามแต่ละภาษา
$texts = [
    'error_not_found' => [
        'th' => 'ไม่พบข้อมูลไอเดียที่ต้องการแก้ไข',
        'en' => 'Idea data to be edited not found',
        'cn' => '找不到要编辑的想法数据',
        'jp' => '編集するアイデアデータが見つかりません',
        'kr' => '수정할 아이디어 데이터를 찾을 수 없습니다'
    ],
    'page_title' => [
        'th' => 'แก้ไขไอเดีย',
        'en' => 'Edit Idea',
        'cn' => '编辑想法',
        'jp' => 'アイデアを編集',
        'kr' => '아이디어 수정'
    ],
    'edit_idia_header' => [
        'th' => 'แก้ไขไอเดีย',
        'en' => 'Edit Idea',
        'cn' => '编辑想法',
        'jp' => 'アイデアを編集',
        'kr' => '아이디어 수정'
    ],
    'btn_back' => [
        'th' => 'กลับ',
        'en' => 'Back',
        'cn' => '返回',
        'jp' => '戻る',
        'kr' => '뒤로가기'
    ],
    'label_cover_image' => [
        'th' => 'ภาพหน้าปก',
        'en' => 'Cover Image',
        'cn' => '封面图片',
        'jp' => 'カバー画像',
        'kr' => '표지 이미지'
    ],
    'note_image_size' => [
        'th' => 'ขนาดรูปภาพที่เหมาะสม width: 350px และ height: 250px',
        'en' => 'Appropriate image size: 350px width and 250px height',
        'cn' => '图片尺寸建议：宽度350px，高度250px',
        'jp' => '推奨画像サイズ：幅350px、高さ250px',
        'kr' => '권장 이미지 크기: 너비 350px, 높이 250px'
    ],
    'lang_th' => [
        'th' => 'ภาษาไทย',
        'en' => 'Thai',
        'cn' => '泰语',
        'jp' => 'タイ語',
        'kr' => '태국어'
    ],
    'lang_en' => [
        'th' => 'ภาษาอังกฤษ',
        'en' => 'English',
        'cn' => '英语',
        'jp' => '英語',
        'kr' => '영어'
    ],
    'lang_cn' => [
        'th' => 'ภาษาจีน',
        'en' => 'Chinese',
        'cn' => '中文',
        'jp' => '中国語',
        'kr' => '중국어'
    ],
    'lang_jp' => [
        'th' => 'ภาษาญี่ปุ่น',
        'en' => 'Japanese',
        'cn' => '日语',
        'jp' => '日本語',
        'kr' => '일본어'
    ],
    'lang_kr' => [
        'th' => 'ภาษาเกาหลี',
        'en' => 'Korean',
        'cn' => '韩语',
        'jp' => '韓国語',
        'kr' => '한국어'
    ],
    'label_subject' => [
        'th' => 'หัวข้อ',
        'en' => 'Subject',
        'cn' => '标题',
        'jp' => '件名',
        'kr' => '제목'
    ],
    'label_description' => [
        'th' => 'คำอธิบาย',
        'en' => 'Description',
        'cn' => '描述',
        'jp' => '説明',
        'kr' => '설명'
    ],
    'label_content' => [
        'th' => 'เนื้อหา',
        'en' => 'Content',
        'cn' => '内容',
        'jp' => 'コンテンツ',
        'kr' => '내용'
    ],
    'btn_save' => [
        'th' => 'บันทึกไอเดีย',
        'en' => 'Save Idea',
        'cn' => '保存想法',
        'jp' => 'アイデアを保存',
        'kr' => '아이디어 저장'
    ],
    'alert_no_data' => [
        'th' => 'ไม่มีข้อมูลข่าว',
        'en' => 'No news data',
        'cn' => '没有新闻数据',
        'jp' => 'ニュースデータがありません',
        'kr' => '뉴스 데이터가 없습니다'
    ],
    'alert_no_idea_data' => [
        'th' => 'ไม่พบข้อมูลไอเดียที่ต้องการแก้ไข',
        'en' => 'Idea data to be edited not found',
        'cn' => '找不到要编辑的想法数据',
        'jp' => '編集するアイデアデータが見つかりません',
        'kr' => '수정할 아이디어 데이터를 찾을 수 없습니다'
    ],
    'alert_sql_error' => [
        'th' => 'ERROR: ข้อผิดพลาดในการเรียกข้อมูลจากฐานข้อมูล',
        'en' => 'ERROR: Error fetching data from the database',
        'cn' => '错误: 从数据库获取数据出错',
        'jp' => 'エラー: データベースからのデータ取得エラー',
        'kr' => '오류: 데이터베이스에서 데이터를 가져오는 중 오류가 발생했습니다'
    ]
];

// ฟังก์ชันสำหรับเรียกใช้ข้อความตามภาษาที่เลือก
function getTextByLang($key) {
    global $texts, $lang;
    return $texts[$key][$lang] ?? $texts[$key]['th'];
}

include '../../../lib/connect.php';
include '../../../lib/base_directory.php';
include '../check_permission.php';

if (!isset($_POST['idia_id'])) {
    echo "<div class='alert alert-danger'>" . getTextByLang('alert_no_idea_data') . "</div>";
    exit;
}

$decodedId = $_POST['idia_id'];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getTextByLang('page_title') ?></title>

    <link rel="icon" type="image/x-icon" href="../../../public/img/q-removebg-preview1.png">
    <link href="../../../inc/jquery/css/jquery-ui.css" rel="stylesheet">
    <script src="../../../inc/jquery/js/jquery-3.6.0.min.js"></script>
    <script src="../../../inc/jquery/js/jquery-ui.min.js"></script>

    <link href="../../../inc/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="../../../inc/bootstrap/js/bootstrap.min.js"></script>
    <script src="../../../inc/bootstrap/js/bootstrap.bundle.min.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/fontawesome5-fullcss@1.1.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.0/css/all.min.css" crossorigin="anonymous" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">

    <link href="../../../inc/sweetalert2/css/sweetalert2.min.css" rel="stylesheet">
    <script src="../../../inc/sweetalert2/js/sweetalert2.all.min.js"></script>

    <link href="../../../inc/select2/css/select2.min.css" rel="stylesheet">
    <script src="../../../inc/select2/js/select2.min.js"></script>

    <link href="https://cdn.datatables.net/v/dt/dt-2.1.4/datatables.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/v/dt/dt-2.1.4/datatables.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-iconpicker/1.10.0/css/bootstrap-iconpicker.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-iconpicker/1.10.0/js/bootstrap-iconpicker.bundle.min.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>

    <link href='../css/index_.css?v=<?php echo time(); ?>' rel='stylesheet'>

    <style>
        .note-editable {
            color: #424242;
            font-size: 16px;
            line-height: 1.5;
        }
        .box-content p {
            color: #424242;
        }
        .responsive-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .responsive-button-container {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 10px;
        }
        @media (max-width: 768px) {
            .responsive-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 480px) {
            .responsive-button-container div {
                text-align: center;
            }
        }
        .note-toolbar {
            position: sticky !important;
            top: 70px !important;
            z-index: 1 !important;
        }
        .nav-link.active {
            font-weight: bold;
            border-bottom: 2px solid #007bff;
        }
        .flag-icon {
            width: 36px;
            margin-right: 8px;
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.7);
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>

<?php include '../template/header.php' ?>

<body>
    <div class="content-sticky" id="">
        <div class="container-fluid">
            <div class="box-content">
                <div class="row">
                    <h4 class="line-ref mb-3">
                        <i class="far fa-idiapaper"></i> <?= getTextByLang('edit_idia_header') ?>
                    </h4>

                    <?php
                    $stmt = $conn->prepare("
                        SELECT
                            n.idia_id,
                            n.subject_idia,
                            n.description_idia,
                            n.content_idia,
                            n.subject_idia_en,
                            n.description_idia_en,
                            n.content_idia_en,
                            n.date_create,
                            n.subject_idia_cn,
                            n.description_idia_cn,
                            n.content_idia_cn,
                            n.subject_idia_jp,
                            n.description_idia_jp,
                            n.content_idia_jp,
                            n.subject_idia_kr,
                            n.description_idia_kr,
                            n.content_idia_kr,
                            GROUP_CONCAT(DISTINCT d.file_name, ':::', d.api_path, ':::', d.status ORDER BY d.status DESC SEPARATOR '|||') AS files
                        FROM dn_idia n
                        LEFT JOIN dn_idia_doc d ON n.idia_id = d.idia_id AND d.del = 0
                        WHERE n.idia_id = ?
                        GROUP BY n.idia_id
                    ");

                    if ($stmt === false) {
                        die(getTextByLang('alert_sql_error') . ": " . $conn->error);
                    }

                    $stmt->bind_param('i', $decodedId);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $content_th = $row['content_idia'];
                        $content_en = $row['content_idia_en'];
                        $content_cn = $row['content_idia_cn'];
                        $content_jp = $row['content_idia_jp'];
                        $content_kr = $row['content_idia_kr'];
                        
                        $pic_data = [];
                        $previewImageSrc = '';

                        if (!empty($row['files'])) {
                            $files = explode('|||', $row['files']);
                            foreach ($files as $file_str) {
                                list($file_name, $api_path, $status) = explode(':::', $file_str);
                                if ($status == 1) { 
                                    $previewImageSrc = htmlspecialchars($api_path);
                                } else { 
                                    $pic_data[htmlspecialchars($file_name)] = htmlspecialchars($api_path);
                                }
                            }
                        }

                        $dom_th = new DOMDocument();
                        libxml_use_internal_errors(true);
                        $source_th = !empty($content_th) ? mb_convert_encoding($content_th, 'HTML-ENTITIES', 'UTF-8') : '<div></div>';
                        $dom_th->loadHTML($source_th, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                        libxml_clear_errors();
                        $images_th = $dom_th->getElementsByTagName('img');
                        foreach ($images_th as $img) {
                            $data_filename = $img->getAttribute('data-filename');
                            if (!empty($data_filename) && isset($pic_data[$data_filename])) {
                                $img->setAttribute('src', $pic_data[$data_filename]);
                            }
                        }
                        $content_th_with_correct_paths = $dom_th->saveHTML();

                        $dom_en = new DOMDocument();
                        libxml_use_internal_errors(true);
                        $source_en = !empty($content_en) ? mb_convert_encoding($content_en, 'HTML-ENTITIES', 'UTF-8') : '<div></div>';
                        $dom_en->loadHTML($source_en, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                        libxml_clear_errors();
                        $images_en = $dom_en->getElementsByTagName('img');
                        foreach ($images_en as $img) {
                            $data_filename = $img->getAttribute('data-filename');
                            if (!empty($data_filename) && isset($pic_data[$data_filename])) {
                                $img->setAttribute('src', $pic_data[$data_filename]);
                            }
                        }
                        $content_en_with_correct_paths = $dom_en->saveHTML();
                        
                        $dom_cn = new DOMDocument();
                        libxml_use_internal_errors(true);
                        $source_cn = !empty($content_cn) ? mb_convert_encoding($content_cn, 'HTML-ENTITIES', 'UTF-8') : '<div></div>';
                        $dom_cn->loadHTML($source_cn, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                        libxml_clear_errors();
                        $images_cn = $dom_cn->getElementsByTagName('img');
                        foreach ($images_cn as $img) {
                            $data_filename = $img->getAttribute('data-filename');
                            if (!empty($data_filename) && isset($pic_data[$data_filename])) {
                                $img->setAttribute('src', $pic_data[$data_filename]);
                            }
                        }
                        $content_cn_with_correct_paths = $dom_cn->saveHTML();

                        $dom_jp = new DOMDocument();
                        libxml_use_internal_errors(true);
                        $source_jp = !empty($content_jp) ? mb_convert_encoding($content_jp, 'HTML-ENTITIES', 'UTF-8') : '<div></div>';
                        $dom_jp->loadHTML($source_jp, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                        libxml_clear_errors();
                        $images_jp = $dom_jp->getElementsByTagName('img');
                        foreach ($images_jp as $img) {
                            $data_filename = $img->getAttribute('data-filename');
                            if (!empty($data_filename) && isset($pic_data[$data_filename])) {
                                $img->setAttribute('src', $pic_data[$data_filename]);
                            }
                        }
                        $content_jp_with_correct_paths = $dom_jp->saveHTML();
                        
                        $dom_kr = new DOMDocument();
                        libxml_use_internal_errors(true);
                        $source_kr = !empty($content_kr) ? mb_convert_encoding($content_kr, 'HTML-ENTITIES', 'UTF-8') : '<div></div>';
                        $dom_kr->loadHTML($source_kr, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                        libxml_clear_errors();
                        $images_kr = $dom_kr->getElementsByTagName('img');
                        foreach ($images_kr as $img) {
                            $data_filename = $img->getAttribute('data-filename');
                            if (!empty($data_filename) && isset($pic_data[$data_filename])) {
                                $img->setAttribute('src', $pic_data[$data_filename]);
                            }
                        }
                        $content_kr_with_correct_paths = $dom_kr->saveHTML();
                        
                        echo "
                        <form id='formidia_edit' enctype='multipart/form-data'>
                            <input type='hidden' class='form-control' id='idia_id' name='idia_id' value='" . htmlspecialchars($row['idia_id']) . "'>
                            <div class='row'>
                                <div>
                                    <div style='margin: 10px;'>
                                        <div style='margin: 10px; text-align: end;'>
                                            <button type='button' id='backToidiaList' class='btn btn-secondary'> 
                                                <i class='fas fa-arrow-left'></i> " . getTextByLang('btn_back') . "
                                            </button>
                                        </div>
                                        <label><span>" . getTextByLang('label_cover_image') . "</span>:</label>
                                        <div><span>" . getTextByLang('note_image_size') . "</span></div>
                                        <div>photo size < 500 kB</div>
                                        <div id='previewContainer' class='previewContainer'>
                                            <img id='previewImage' src='{$previewImageSrc}' alt='Image Preview' style='max-width: 100%;'>
                                        </div>
                                    </div>
                                    <div style='margin: 10px;'>
                                        <input type='file' class='form-control' id='fileInput' name='fileInput'>
                                    </div>
                                </div>
                                <div>
                                    <div class='card mb-4'>
                                        <div class='card-header p-0'>
                                            <ul class='nav nav-tabs' id='languageTabs' role='tablist'>
                                                <li class='nav-item' role='presentation'>
                                                    <button class='nav-link active' id='th-tab' data-bs-toggle='tab' data-bs-target='#th' type='button' role='tab' aria-controls='th' aria-selected='true'>
                                                        <img src='https://flagcdn.com/w320/th.png' alt='Thai Flag' class='flag-icon' style=' width: 36px; margin-right: 8px;'>" . getTextByLang('lang_th') . "
                                                    </button>
                                                </li>
                                                <li class='nav-item' role='presentation'>
                                                    <button class='nav-link' id='en-tab' data-bs-toggle='tab' data-bs-target='#en' type='button' role='tab' aria-controls='en' aria-selected='false'>
                                                        <img src='https://flagcdn.com/w320/gb.png' alt='English Flag' class='flag-icon' style=' width: 36px; margin-right: 8px;'>" . getTextByLang('lang_en') . "
                                                    </button>
                                                </li>
                                                <li class='nav-item' role='presentation'>
                                                    <button class='nav-link' id='cn-tab' data-bs-toggle='tab' data-bs-target='#cn' type='button' role='tab' aria-controls='cn' aria-selected='false'>
                                                        <img src='https://flagcdn.com/w320/cn.png' alt='Chinese Flag' class='flag-icon' style=' width: 36px; margin-right: 8px;'>" . getTextByLang('lang_cn') . "
                                                    </button>
                                                </li>
                                                <li class='nav-item' role='presentation'>
                                                    <button class='nav-link' id='jp-tab' data-bs-toggle='tab' data-bs-target='#jp' type='button' role='tab' aria-controls='jp' aria-selected='false'>
                                                        <img src='https://flagcdn.com/w320/jp.png' alt='Japanese Flag' class='flag-icon' style=' width: 36px; margin-right: 8px;'>" . getTextByLang('lang_jp') . "
                                                    </button>
                                                </li>
                                                <li class='nav-item' role='presentation'>
                                                    <button class='nav-link' id='kr-tab' data-bs-toggle='tab' data-bs-target='#kr' type='button' role='tab' aria-controls='kr' aria-selected='false'>
                                                        <img src='https://flagcdn.com/w320/kr.png' alt='Korean Flag' class='flag-icon' style=' width: 36px; margin-right: 8px;'>" . getTextByLang('lang_kr') . "
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class='card-body'>
                                            <div class='tab-content' id='languageTabsContent'>
                                                <div class='tab-pane fade show active' id='th' role='tabpanel' aria-labelledby='th-tab'>
                                                    <div style='margin: 10px;'>
                                                        <label><span>" . getTextByLang('label_subject') . " (TH)</span>:</label>
                                                        <input type='text' class='form-control' id='idia_subject' name='idia_subject' value='" . htmlspecialchars($row['subject_idia']) . "'>
                                                    </div>
                                                    <div style='margin: 10px;'>
                                                        <label><span>" . getTextByLang('label_description') . " (TH)</span>:</label>
                                                        <textarea class='form-control' id='idia_description' name='idia_description'>" . htmlspecialchars($row['description_idia']) . "</textarea>
                                                    </div>
                                                    <div style='margin: 10px;'>
                                                        <label><span>" . getTextByLang('label_content') . " (TH)</span>:</label>
                                                        <textarea class='form-control summernote' id='summernote_update' name='idia_content'>" . $content_th_with_correct_paths . "</textarea>
                                                    </div>
                                                </div>
                                                <div class='tab-pane fade' id='en' role='tabpanel' aria-labelledby='en-tab'>
                                                    <div style='margin: 10px;'>
                                                        <button type='button' id='copyFromThai' class='btn btn-info btn-sm float-end mb-2'>Origami Ai Translate</button>
                                                        <div id='loadingIndicator' class='loading-overlay' style='display: none;'>
                                                            <div class='loading-spinner'></div>
                                                        </div>
                                                        <label><span>" . getTextByLang('label_subject') . " (EN)</span>:</label>
                                                        <input type='text' class='form-control' id='idia_subject_en' name='idia_subject_en' value='" . htmlspecialchars($row['subject_idia_en']) . "'>
                                                    </div>
                                                    <div style='margin: 10px;'>
                                                        <label><span>" . getTextByLang('label_description') . " (EN)</span>:</label>
                                                        <textarea class='form-control' id='idia_description_en' name='idia_description_en'>" . htmlspecialchars($row['description_idia_en']) . "</textarea>
                                                    </div>
                                                    <div style='margin: 10px;'>
                                                        <label><span>" . getTextByLang('label_content') . " (EN)</span>:</label>
                                                        <textarea class='form-control summernote' id='summernote_update_en' name='idia_content_en'>" . $content_en_with_correct_paths . "</textarea>
                                                    </div>
                                                </div>
                                                <div class='tab-pane fade' id='cn' role='tabpanel' aria-labelledby='cn-tab'>
                                                    <div style='margin: 10px;'>
                                                        <button type='button' id='copyFromThaiCn' class='btn btn-info btn-sm float-end mb-2'>Origami Ai Translate</button>
                                                        <div id='loadingIndicatorCn' class='loading-overlay' style='display: none;'>
                                                            <div class='loading-spinner'></div>
                                                        </div>
                                                        <label><span>" . getTextByLang('label_subject') . " (CN)</span>:</label>
                                                        <input type='text' class='form-control' id='idia_subject_cn' name='idia_subject_cn' value='" . htmlspecialchars($row['subject_idia_cn']) . "'>
                                                    </div>
                                                    <div style='margin: 10px;'>
                                                        <label><span>" . getTextByLang('label_description') . " (CN)</span>:</label>
                                                        <textarea class='form-control' id='idia_description_cn' name='idia_description_cn'>" . htmlspecialchars($row['description_idia_cn']) . "</textarea>
                                                    </div>
                                                    <div style='margin: 10px;'>
                                                        <label><span>" . getTextByLang('label_content') . " (CN)</span>:</label>
                                                        <textarea class='form-control summernote' id='summernote_update_cn' name='idia_content_cn'>" . $content_cn_with_correct_paths . "</textarea>
                                                    </div>
                                                </div>
                                                <div class='tab-pane fade' id='jp' role='tabpanel' aria-labelledby='jp-tab'>
                                                    <div style='margin: 10px;'>
                                                        <button type='button' id='copyFromThaiJp' class='btn btn-info btn-sm float-end mb-2'>Origami Ai Translate</button>
                                                        <div id='loadingIndicatorJp' class='loading-overlay' style='display: none;'>
                                                            <div class='loading-spinner'></div>
                                                        </div>
                                                        <label><span>" . getTextByLang('label_subject') . " (JP)</span>:</label>
                                                        <input type='text' class='form-control' id='idia_subject_jp' name='idia_subject_jp' value='" . htmlspecialchars($row['subject_idia_jp']) . "'>
                                                    </div>
                                                    <div style='margin: 10px;'>
                                                        <label><span>" . getTextByLang('label_description') . " (JP)</span>:</label>
                                                        <textarea class='form-control' id='idia_description_jp' name='idia_description_jp'>" . htmlspecialchars($row['description_idia_jp']) . "</textarea>
                                                    </div>
                                                    <div style='margin: 10px;'>
                                                        <label><span>" . getTextByLang('label_content') . " (JP)</span>:</label>
                                                        <textarea class='form-control summernote' id='summernote_update_jp' name='idia_content_jp'>" . $content_jp_with_correct_paths . "</textarea>
                                                    </div>
                                                </div>
                                                <div class='tab-pane fade' id='kr' role='tabpanel' aria-labelledby='kr-tab'>
                                                    <div style='margin: 10px;'>
                                                        <button type='button' id='copyFromThaiKr' class='btn btn-info btn-sm float-end mb-2'>Origami Ai Translate</button>
                                                        <div id='loadingIndicatorKr' class='loading-overlay' style='display: none;'>
                                                            <div class='loading-spinner'></div>
                                                        </div>
                                                        <label><span>" . getTextByLang('label_subject') . " (KR)</span>:</label>
                                                        <input type='text' class='form-control' id='idia_subject_kr' name='idia_subject_kr' value='" . htmlspecialchars($row['subject_idia_kr']) . "'>
                                                    </div>
                                                    <div style='margin: 10px;'>
                                                        <label><span>" . getTextByLang('label_description') . " (KR)</span>:</label>
                                                        <textarea class='form-control' id='idia_description_kr' name='idia_description_kr'>" . htmlspecialchars($row['description_idia_kr']) . "</textarea>
                                                    </div>
                                                    <div style='margin: 10px;'>
                                                        <label><span>" . getTextByLang('label_content') . " (KR)</span>:</label>
                                                        <textarea class='form-control summernote' id='summernote_update_kr' name='idia_content_kr'>" . $content_kr_with_correct_paths . "</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div style='margin: 10px; text-align: end;'>
                                        <button type='button' id='submitEditidia' class='btn btn-success'>
                                            <i class='fas fa-save'></i> " . getTextByLang('btn_save') . "
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        ";
                    } else {
                        echo "<div class='alert alert-warning'>" . getTextByLang('alert_no_data') . "</div>";
                    }
                    $stmt->close();
                    $conn->close();
                    ?>
                </div>
            </div>
        </div>
    </div>

    
<script>
    $(document).ready(function() {
        // ลบโค้ดส่วนที่เกี่ยวข้องกับ related_shops ออกทั้งหมด
         function summernoteImageUpload(file, editor) { // เปลี่ยนรับเป็น 'file' ตัวเดียว
            var formData = new FormData();
            formData.append("file", file); // ใช้ file ตัวเดียว
            formData.append("action", "upload_image_content"); 

            $.ajax({
                url: "actions/process_idia.php",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json', // *** เพิ่ม: ให้ jQuery พยายามแปลง JSON อัตโนมัติ ***
                success: function (json) {
                    try {
                        // *** ลบ JSON.parse() ออกไป เนื่องจากตั้งค่า dataType: 'json' แล้ว ***
                        if (json.status === 'success' && json.filePath) { // ใช้ json.filePath ตามรูปแบบ response แรก
                            // ตรวจสอบความถูกต้องของ URL ก่อนแทรก
                            if (typeof json.filePath === 'string' && json.filePath.startsWith('http')) {
                                // แทรกรูปภาพที่อัปโหลดสำเร็จกลับเข้าไปใน editor
                                // ใส่ json.fileName เป็นพารามิเตอร์ที่ 3 เพื่อให้ Summernote เก็บชื่อไฟล์ไว้ใน data-filename attribute (ถ้าเซิร์ฟเวอร์ส่งกลับมา)
                                editor.summernote('insertImage', json.filePath, json.fileName); 
                            } else {
                                console.error("❌ Invalid filePath received from server:", json.filePath);
                                alertError('Image path is invalid (Client error).');
                            }
                        } else {
                            alertError('การอัปโหลดรูปภาพล้มเหลว: ' + (json.message || 'Unknown error. Check server response.'));
                        }
                    } catch (e) {
                        // การจับข้อผิดพลาดใน try/catch นี้จะทำงานหาก json มีปัญหาบางอย่าง (แม้จะกำหนด dataType: 'json' แล้ว)
                        console.error("❌ JSON handling error during upload:", json, e);
                        alertError('Invalid response from server during upload');
                    }
                },
                error: function (xhr, textStatus, errorThrown) {
                    console.error("❌ AJAX error during upload:", textStatus, errorThrown, xhr.responseText);
                    alertError('AJAX request failed during upload: ' + textStatus);
                }
            });
        }
        $('#summernote_update').summernote({
            height: 600,
            minHeight: 600,
            maxHeight: 600,
            toolbar: [
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['font', ['fontname', 'fontsize', 'forecolor']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'picture', 'video', 'table']],
                ['view', ['fullscreen', ['codeview', 'fullscreen']]],
                ['image', ['resizeFull', 'resizeHalf', 'resizeQuarter']]
            ],
            fontNames: ['Kanit', 'Arial', 'Arial Black', 'Comic Sans MS', 'Courier New', 'Georgia', 'Times New Roman', 'Verdana', 'sans-serif'],
            fontNamesIgnoreCheck: ['Kanit'],
            fontsizeUnits: ['px', 'pt'],
            fontsize: ['8', '10', '12', '14', '16', '18', '24', '36'],
            callbacks: {
                onImageUpload: function(files) {
                    summernoteImageUpload(files, $(this));
                }
            }
        });

        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
            var target = $(e.target).attr("data-bs-target");
            if (target === '#en') {
                if ($('#summernote_update_en').data('summernote')) {
                    $('#summernote_update_en').summernote('destroy');
                }
                $('#summernote_update_en').summernote({
                    height: 600,
                    minHeight: 600,
                    maxHeight: 600,
                    toolbar: [
                        ['style', ['bold', 'italic', 'underline', 'clear']],
                        ['font', ['fontname', 'fontsize', 'forecolor']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['insert', ['link', 'picture', 'video', 'table']],
                        ['view', ['fullscreen', ['codeview', 'fullscreen']]],
                        ['image', ['resizeFull', 'resizeHalf', 'resizeQuarter']]
                    ],
                    fontNames: ['Kanit', 'Arial', 'Arial Black', 'Comic Sans MS', 'Courier New', 'Georgia', 'Times New Roman', 'Verdana', 'sans-serif'],
                    fontNamesIgnoreCheck: ['Kanit'],
                    fontsizeUnits: ['px', 'pt'],
                    fontsize: ['8', '10', '12', '14', '16', '18', '24', '36'],
                    callbacks: {
                onImageUpload: function(files) {
                    summernoteImageUpload(files, $(this));
                }
            }
                });
            } else if (target === '#cn') {
                if ($('#summernote_update_cn').data('summernote')) {
                    $('#summernote_update_cn').summernote('destroy');
                }
                $('#summernote_update_cn').summernote({
                    height: 600,
                    minHeight: 600,
                    maxHeight: 600,
                    toolbar: [
                        ['style', ['bold', 'italic', 'underline', 'clear']],
                        ['font', ['fontname', 'fontsize', 'forecolor']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['insert', ['link', 'picture', 'video', 'table']],
                        ['view', ['fullscreen', ['codeview', 'fullscreen']]],
                        ['image', ['resizeFull', 'resizeHalf', 'resizeQuarter']]
                    ],
                    fontNames: ['Kanit', 'Arial', 'Arial Black', 'Comic Sans MS', 'Courier New', 'Georgia', 'Times New Roman', 'Verdana', 'sans-serif'],
                    fontNamesIgnoreCheck: ['Kanit'],
                    fontsizeUnits: ['px', 'pt'],
                    fontsize: ['8', '10', '12', '14', '16', '18', '24', '36'],
                    callbacks: {
                onImageUpload: function(files) {
                    summernoteImageUpload(files, $(this));
                }
            }
                });
            } else if (target === '#jp') {
                if ($('#summernote_update_jp').data('summernote')) {
                    $('#summernote_update_jp').summernote('destroy');
                }
                $('#summernote_update_jp').summernote({
                    height: 600,
                    minHeight: 600,
                    maxHeight: 600,
                    toolbar: [
                        ['style', ['bold', 'italic', 'underline', 'clear']],
                        ['font', ['fontname', 'fontsize', 'forecolor']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['insert', ['link', 'picture', 'video', 'table']],
                        ['view', ['fullscreen', ['codeview', 'fullscreen']]],
                        ['image', ['resizeFull', 'resizeHalf', 'resizeQuarter']]
                    ],
                    fontNames: ['Kanit', 'Arial', 'Arial Black', 'Comic Sans MS', 'Courier New', 'Georgia', 'Times New Roman', 'Verdana', 'sans-serif'],
                    fontNamesIgnoreCheck: ['Kanit'],
                    fontsizeUnits: ['px', 'pt'],
                    fontsize: ['8', '10', '12', '14', '16', '18', '24', '36'],
                    callbacks: {
                onImageUpload: function(files) {
                    summernoteImageUpload(files, $(this));
                }
            }
                });
            } else if (target === '#kr') { // เพิ่มส่วนของ KR
                if ($('#summernote_update_kr').data('summernote')) {
                    $('#summernote_update_kr').summernote('destroy');
                }
                $('#summernote_update_kr').summernote({
                    height: 600,
                    minHeight: 600,
                    maxHeight: 600,
                    toolbar: [
                        ['style', ['bold', 'italic', 'underline', 'clear']],
                        ['font', ['fontname', 'fontsize', 'forecolor']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['insert', ['link', 'picture', 'video', 'table']],
                        ['view', ['fullscreen', ['codeview', 'fullscreen']]],
                        ['image', ['resizeFull', 'resizeHalf', 'resizeQuarter']]
                    ],
                    fontNames: ['Kanit', 'Arial', 'Arial Black', 'Comic Sans MS', 'Courier New', 'Georgia', 'Times New Roman', 'Verdana', 'sans-serif'],
                    fontNamesIgnoreCheck: ['Kanit'],
                    fontsizeUnits: ['px', 'pt'],
                    fontsize: ['8', '10', '12', '14', '16', '18', '24', '36'],
                    callbacks: {
                onImageUpload: function(files) {
                    summernoteImageUpload(files, $(this));
                }
            }
                });
            }
        });

        // New Copy from Thai button functionality
        $('#copyFromThai').on('click', function () {
            // 1. แสดง Loading Indicator
            $('#loadingIndicator').show(); // ให้โชว์ loading animation

            // ดึงค่าจากฟอร์มภาษาไทย
            var thaiSubject = $('#idia_subject').val();
            var thaiDescription = $('#idia_description').val();
            var thaiContent = $('#summernote_update').summernote('code');

            // สร้าง Object สำหรับข้อมูลที่จะส่งไป
            const dataToSend = {
                language: "th",
                translate: "en",
                company: 2,
                content: {
                    subject: thaiSubject,
                    description: thaiDescription,
                    content: thaiContent
                }
            };

            // ส่งข้อมูลแบบ POST ไปยังไฟล์ actions/translate.php
            fetch('actions/translate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer',
                },
                body: JSON.stringify(dataToSend),
            })
            .then(res => res.json())
            .then(response => {
                console.log(response);

                if (response.status === 'success') {
                    $('#idia_subject_en').val(response.subject);
                    $('#idia_description_en').val(response.description);
                    $('#summernote_update_en').summernote('code', response.content);
                    alert('การแปลสำเร็จ!');
                } else {
                    alert('การแปลล้มเหลว: ' + (response.message || response.error));
                }
            })
            .catch(error => {
                console.error("error:", error);
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + error);
            })
            .finally(() => {
                // 2. ซ่อน Loading Indicator เมื่อเสร็จสิ้นกระบวนการทั้งหมด
                $('#loadingIndicator').hide();
            });
        });
        
        // New Copy from Thai to Chinese button functionality
        $('#copyFromThaiCn').on('click', function () {
            // 1. แสดง Loading Indicator สำหรับภาษาจีน
            $('#loadingIndicatorCn').show();

            // ดึงค่าจากฟอร์มภาษาไทย
            var thaiSubject = $('#idia_subject').val();
            var thaiDescription = $('#idia_description').val();
            var thaiContent = $('#summernote_update').summernote('code');

            // สร้าง Object สำหรับข้อมูลที่จะส่งไป
            const dataToSend = {
                language: "th",
                translate: "cn",
                company: 2,
                content: {
                    subject: thaiSubject,
                    description: thaiDescription,
                    content: thaiContent
                }
            };

            // ส่งข้อมูลแบบ POST ไปยังไฟล์ actions/translate.php
            fetch('actions/translate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer',
                },
                body: JSON.stringify(dataToSend),
            })
            .then(res => res.json())
            .then(response => {
                console.log(response);

                if (response.status === 'success') {
                    $('#idia_subject_cn').val(response.subject);
                    $('#idia_description_cn').val(response.description);
                    $('#summernote_update_cn').summernote('code', response.content);
                    alert('การแปลสำเร็จ!');
                } else {
                    alert('การแปลล้มเหลว: ' + (response.message || response.error));
                }
            })
            .catch(error => {
                console.error("error:", error);
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + error);
            })
            .finally(() => {
                // 2. ซ่อน Loading Indicator เมื่อเสร็จสิ้นกระบวนการทั้งหมด
                $('#loadingIndicatorCn').hide();
            });
        });

        // New Copy from Thai to Japanese button functionality
        $('#copyFromThaiJp').on('click', function () {
            // 1. แสดง Loading Indicator สำหรับภาษาญี่ปุ่น
            $('#loadingIndicatorJp').show();

            // ดึงค่าจากฟอร์มภาษาไทย
            var thaiSubject = $('#idia_subject').val();
            var thaiDescription = $('#idia_description').val();
            var thaiContent = $('#summernote_update').summernote('code');

            // สร้าง Object สำหรับข้อมูลที่จะส่งไป
            const dataToSend = {
                language: "th",
                translate: "jp",
                company: 2,
                content: {
                    subject: thaiSubject,
                    description: thaiDescription,
                    content: thaiContent
                }
            };

            // ส่งข้อมูลแบบ POST ไปยังไฟล์ actions/translate.php
            fetch('actions/translate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer',
                },
                body: JSON.stringify(dataToSend),
            })
            .then(res => res.json())
            .then(response => {
                console.log(response);

                if (response.status === 'success') {
                    $('#idia_subject_jp').val(response.subject);
                    $('#idia_description_jp').val(response.description);
                    $('#summernote_update_jp').summernote('code', response.content);
                    alert('การแปลสำเร็จ!');
                } else {
                    alert('การแปลล้มเหลว: ' + (response.message || response.error));
                }
            })
            .catch(error => {
                console.error("error:", error);
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + error);
            })
            .finally(() => {
                // 2. ซ่อน Loading Indicator เมื่อเสร็จสิ้นกระบวนการทั้งหมด
                $('#loadingIndicatorJp').hide();
            });
        });

        // New Copy from Thai to Korean button functionality
        $('#copyFromThaiKr').on('click', function () {
            // 1. แสดง Loading Indicator สำหรับภาษาเกาหลี
            $('#loadingIndicatorKr').show();

            // ดึงค่าจากฟอร์มภาษาไทย
            var thaiSubject = $('#idia_subject').val();
            var thaiDescription = $('#idia_description').val();
            var thaiContent = $('#summernote_update').summernote('code');

            // สร้าง Object สำหรับข้อมูลที่จะส่งไป
            const dataToSend = {
                language: "th",
                translate: "kr",
                company: 2,
                content: {
                    subject: thaiSubject,
                    description: thaiDescription,
                    content: thaiContent
                }
            };

            // ส่งข้อมูลแบบ POST ไปยังไฟล์ actions/translate.php
            fetch('actions/translate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer',
                },
                body: JSON.stringify(dataToSend),
            })
            .then(res => res.json())
            .then(response => {
                console.log(response);

                if (response.status === 'success') {
                    $('#idia_subject_kr').val(response.subject);
                    $('#idia_description_kr').val(response.description);
                    $('#summernote_update_kr').summernote('code', response.content);
                    alert('การแปลสำเร็จ!');
                } else {
                    alert('การแปลล้มเหลว: ' + (response.message || response.error));
                }
            })
            .catch(error => {
                console.error("error:", error);
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + error);
            })
            .finally(() => {
                // 2. ซ่อน Loading Indicator เมื่อเสร็จสิ้นกระบวนการทั้งหมด
                $('#loadingIndicatorKr').hide();
            });
        });

        $('#fileInput').on('change', function() {
            var input = this;
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#previewImage').attr('src', e.target.result);
                };
                reader.readAsDataURL(input.files[0]);
            }
        });

        $('#backToidiaList').on('click', function() {
            window.location.href = "list_idia.php";
        });

        $("#submitEditidia").on("click", function(event) {
            event.preventDefault();
            var formidia = $("#formidia_edit")[0];
            var formData = new FormData(formidia);
            formData.set("action", "editidia");
            formData.set("idia_id", $("#idia_id").val());
            var contentFromEditor_th = $("#summernote_update").summernote('code');
            var contentFromEditor_en = $('#summernote_update_en').summernote('code');
            var contentFromEditor_cn = $('#summernote_update_cn').summernote('code');
            var contentFromEditor_jp = $('#summernote_update_jp').summernote('code');
            var contentFromEditor_kr = $('#summernote_update_kr').summernote('code');
            var checkIsUrl = false;
            
            if (contentFromEditor_th) {
                var tempDiv = document.createElement("div");
                tempDiv.innerHTML = contentFromEditor_th;
                var imgTags = tempDiv.getElementsByTagName("img");
                for (var i = 0; i < imgTags.length; i++) {
                    var imgSrc = imgTags[i].getAttribute("src");
                    var filename = imgTags[i].getAttribute("data-filename");
                    if (!imgSrc) continue;

                    imgSrc = imgSrc.replace(/ /g, "%20");
                    if (!isValidUrl(imgSrc)) {
                        var file = base64ToFile(imgSrc, filename);
                        if (file) {
                            formData.append("image_files_th[]", file);
                        }
                        if (imgSrc.startsWith("data:image")) {
                            imgTags[i].setAttribute("src", "");
                        }
                    } else {
                        checkIsUrl = true;
                    }
                }
                formData.set("idia_content", tempDiv.innerHTML);
            }

            if (contentFromEditor_en) {
                var tempDiv_en = document.createElement("div");
                tempDiv_en.innerHTML = contentFromEditor_en;
                var imgTags_en = tempDiv_en.getElementsByTagName("img");
                for (var i = 0; i < imgTags_en.length; i++) {
                    var imgSrc_en = imgTags_en[i].getAttribute("src");
                    var filename_en = imgTags_en[i].getAttribute("data-filename");
                    if (!imgSrc_en) continue;

                    imgSrc_en = imgSrc_en.replace(/ /g, "%20");
                    if (!isValidUrl(imgSrc_en)) {
                        var file_en = base64ToFile(imgSrc_en, filename_en);
                        if (file_en) {
                            formData.append("image_files_en[]", file_en);
                        }
                        if (imgSrc_en.startsWith("data:image")) {
                            imgTags_en[i].setAttribute("src", "");
                        }
                    } else {
                        checkIsUrl = true;
                    }
                }
                formData.set("idia_content_en", tempDiv_en.innerHTML);
            }
            
            if (contentFromEditor_cn) {
                var tempDiv_cn = document.createElement("div");
                tempDiv_cn.innerHTML = contentFromEditor_cn;
                var imgTags_cn = tempDiv_cn.getElementsByTagName("img");
                for (var i = 0; i < imgTags_cn.length; i++) {
                    var imgSrc_cn = imgTags_cn[i].getAttribute("src");
                    var filename_cn = imgTags_cn[i].getAttribute("data-filename");
                    if (!imgSrc_cn) continue;

                    imgSrc_cn = imgSrc_cn.replace(/ /g, "%20");
                    if (!isValidUrl(imgSrc_cn)) {
                        var file_cn = base64ToFile(imgSrc_cn, filename_cn);
                        if (file_cn) {
                            formData.append("image_files_cn[]", file_cn);
                        }
                        if (imgSrc_cn.startsWith("data:image")) {
                            imgTags_cn[i].setAttribute("src", "");
                        }
                    } else {
                        checkIsUrl = true;
                    }
                }
                formData.set("idia_content_cn", tempDiv_cn.innerHTML);
            }
            
            if (contentFromEditor_jp) {
                var tempDiv_jp = document.createElement("div");
                tempDiv_jp.innerHTML = contentFromEditor_jp;
                var imgTags_jp = tempDiv_jp.getElementsByTagName("img");
                for (var i = 0; i < imgTags_jp.length; i++) {
                    var imgSrc_jp = imgTags_jp[i].getAttribute("src");
                    var filename_jp = imgTags_jp[i].getAttribute("data-filename");
                    if (!imgSrc_jp) continue;

                    imgSrc_jp = imgSrc_jp.replace(/ /g, "%20");
                    if (!isValidUrl(imgSrc_jp)) {
                        var file_jp = base64ToFile(imgSrc_jp, filename_jp);
                        if (file_jp) {
                            formData.append("image_files_jp[]", file_jp);
                        }
                        if (imgSrc_jp.startsWith("data:image")) {
                            imgTags_jp[i].setAttribute("src", "");
                        }
                    } else {
                        checkIsUrl = true;
                    }
                }
                formData.set("idia_content_jp", tempDiv_jp.innerHTML);
            }
            
            if (contentFromEditor_kr) { // เพิ่มส่วนของ KR
                var tempDiv_kr = document.createElement("div");
                tempDiv_kr.innerHTML = contentFromEditor_kr;
                var imgTags_kr = tempDiv_kr.getElementsByTagName("img");
                for (var i = 0; i < imgTags_kr.length; i++) {
                    var imgSrc_kr = imgTags_kr[i].getAttribute("src");
                    var filename_kr = imgTags_kr[i].getAttribute("data-filename");
                    if (!imgSrc_kr) continue;

                    imgSrc_kr = imgSrc_kr.replace(/ /g, "%20");
                    if (!isValidUrl(imgSrc_kr)) {
                        var file_kr = base64ToFile(imgSrc_kr, filename_kr);
                        if (file_kr) {
                            formData.append("image_files_kr[]", file_kr);
                        }
                        if (imgSrc_kr.startsWith("data:image")) {
                            imgTags_kr[i].setAttribute("src", "");
                        }
                    } else {
                        checkIsUrl = true;
                    }
                }
                formData.set("idia_content_kr", tempDiv_kr.innerHTML);
            }

            $(".is-invalid").removeClass("is-invalid");
            if (!$("#idia_subject").val().trim()) {
                $("#idia_subject").addClass("is-invalid");
                return;
            }
            if (!$("#idia_description").val().trim()) {
                $("#idia_description").addClass("is-invalid");
                return;
            }
            if (!contentFromEditor_th.trim() && !contentFromEditor_en.trim() && !contentFromEditor_cn.trim() && !contentFromEditor_jp.trim() && !contentFromEditor_kr.trim()) {
                alertError("กรุณากรอกข้อมูลเนื้อหาอย่างน้อยหนึ่งภาษา");
                return;
            }

            formData.set("idia_subject_en", $("#idia_subject_en").val());
            formData.set("idia_description_en", $("#idia_description_en").val());
            formData.set("idia_subject_cn", $("#idia_subject_cn").val());
            formData.set("idia_description_cn", $("#idia_description_cn").val());
            formData.set("idia_subject_jp", $("#idia_subject_jp").val());
            formData.set("idia_description_jp", $("#idia_description_jp").val());
            formData.set("idia_subject_kr", $("#idia_subject_kr").val()); // เพิ่มส่วนของ KR
            formData.set("idia_description_kr", $("#idia_description_kr").val()); // เพิ่มส่วนของ KR

            Swal.fire({
                title: checkIsUrl ? "ระบบตรวจพบรูปภาพจากเว็บไซต์อื่น ๆ?" : "คุณแน่ใจหรือไม่?",
                text: "คุณต้องการแก้ไขไอเดียใช่หรือไม่?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#4CAF50",
                cancelButtonColor: "#d33",
                confirmButtonText: "ตกลง",
                cancelButtonText: "ยกเลิก"
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#loading-overlay').fadeIn();
                    $.ajax({
                        url: "actions/process_idia.php",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function (response) {
                            try {
                                var json = (typeof response === "string") ? JSON.parse(response) : response;
                                if (json.status === 'success') {
                                    location.reload();
                                } else {
                                    Swal.fire('ข้อผิดพลาด', json.message || 'ข้อผิดพลาดที่ไม่ทราบสาเหตุ', 'error');
                                }
                            } catch (e) {
                                console.error("❌ JSON parse error:", e);
                                Swal.fire('ข้อผิดพลาด', 'การตอบสนองจากเซิร์ฟเวอร์ไม่ถูกต้อง', 'error');
                            }
                        },
                        error: function (xhr) {
                            console.error("❌ AJAX error:", xhr.responseText);
                            Swal.fire('ข้อผิดพลาด', 'การร้องขอ AJAX ล้มเหลว', 'error');
                            $('#loading-overlay').fadeOut();
                        },
                    });
                } else {
                    $('#loading-overlay').fadeOut();
                }
            });
        });
    });

    function base64ToFile(base64, fileName) {
        // ... (Your existing base64ToFile function) ...
    }

    function alertError(textAlert) {
        // ... (Your existing alertError function) ...
    }

    function isValidUrl(str) {
        // ... (Your existing isValidUrl function) ...
    }
</script>
<script src='js/idia_.js?v=<?php echo time(); ?>'></script>
<script src='../js/index_.js?v=<?php echo time(); ?>'></script>
</body>
</html>