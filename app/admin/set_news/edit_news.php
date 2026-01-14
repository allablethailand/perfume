<?php
include '../../../lib/connect.php';
include '../../../lib/base_directory.php';
include '../check_permission.php';

// ส่วนที่เพิ่ม: ตรวจสอบและกำหนดภาษาจาก URL หรือ Session
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

// แปลข้อความต่างๆ ที่ใช้ในหน้าเว็บ
$texts = [
    'th' => [
        'page_title' => 'แก้ไขข่าวสาร',
        'not_found' => 'ไม่พบข้อมูลข่าวที่ต้องการแก้ไข',
        'back_button' => 'กลับ',
        'cover_photo' => 'รูปภาพหน้าปก',
        'cover_photo_size' => 'ขนาดรูปภาพที่เหมาะสม width: 350px และ height: 250px',
        'subject' => 'หัวข้อ',
        'description' => 'รายละเอียด',
        'content' => 'เนื้อหา',
        'save_button' => 'บันทึกข่าว',
        'no_data' => 'ไม่มีข้อมูลข่าว',
    ],
    'en' => [
        'page_title' => 'Edit News',
        'not_found' => 'News data to be edited not found',
        'back_button' => 'Back',
        'cover_photo' => 'Cover photo',
        'cover_photo_size' => 'Appropriate image size: width: 350px and height: 250px',
        'subject' => 'Subject',
        'description' => 'Description',
        'content' => 'Content',
        'save_button' => 'Save News',
        'no_data' => 'No news data',
    ],
    'cn' => [
        'page_title' => '编辑新闻',
        'not_found' => '找不到要编辑的新闻数据',
        'back_button' => '返回',
        'cover_photo' => '封面图片',
        'cover_photo_size' => '合适的图片尺寸：宽: 350px 高: 250px',
        'subject' => '主题',
        'description' => '描述',
        'content' => '内容',
        'save_button' => '保存新闻',
        'no_data' => '没有新闻数据',
    ],
    'jp' => [
        'page_title' => 'ニュースを編集',
        'not_found' => '編集するニュースデータが見つかりません',
        'back_button' => '戻る',
        'cover_photo' => '表紙の写真',
        'cover_photo_size' => '適切な画像サイズ：幅: 350px 高さ: 250px',
        'subject' => '件名',
        'description' => '説明',
        'content' => '内容',
        'save_button' => 'ニュースを保存',
        'no_data' => 'ニュースデータがありません',
    ],
    'kr' => [
        'page_title' => '뉴스 수정',
        'not_found' => '수정할 뉴스 데이터를 찾을 수 없습니다',
        'back_button' => '뒤로',
        'cover_photo' => '표지 사진',
        'cover_photo_size' => '적절한 이미지 크기: 너비: 350px 높이: 250px',
        'subject' => '제목',
        'description' => '설명',
        'content' => '내용',
        'save_button' => '뉴스 저장',
        'no_data' => '뉴스 데이터가 없습니다',
    ]
];

// ฟังก์ชันสำหรับเรียกใช้ข้อความตามภาษาที่เลือก
function getTextByLang($key) {
    global $texts, $lang;
    return $texts[$lang][$key] ?? $texts['th'][$key];
}

if (!isset($_POST['news_id'])) {
    echo "<div class='alert alert-danger'>" . getTextByLang('not_found') . "</div>";
    exit;
}

$decodedId = $_POST['news_id'];
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
                        <i class="far fa-newspaper"></i> <?= getTextByLang('page_title'); ?>
                    </h4>

                    <?php
                    $stmt = $conn->prepare("
                        SELECT
                            n.news_id,
                            n.subject_news,
                            n.description_news,
                            n.content_news,
                            n.subject_news_en,
                            n.description_news_en,
                            n.content_news_en,
                            n.subject_news_cn,
                            n.description_news_cn,
                            n.content_news_cn,
                            n.subject_news_jp,
                            n.description_news_jp,
                            n.content_news_jp,
                            n.subject_news_kr,
                            n.description_news_kr,
                            n.content_news_kr,
                            n.date_create,
                            GROUP_CONCAT(DISTINCT d.file_name, ':::', d.api_path, ':::', d.status ORDER BY d.status DESC SEPARATOR '|||') AS files
                        FROM dn_news n
                        LEFT JOIN dn_news_doc d ON n.news_id = d.news_id AND d.del = 0
                        WHERE n.news_id = ?
                        GROUP BY n.news_id
                    ");

                    if ($stmt === false) {
                        die('❌ SQL Prepare failed: ' . $conn->error);
                    }

                    $stmt->bind_param('i', $decodedId);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $content_th = $row['content_news'];
                        $content_en = $row['content_news_en'];
                        $content_cn = $row['content_news_cn'];
                        $content_jp = $row['content_news_jp'];
                        $content_kr = $row['content_news_kr'];
                        
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
                        <form id='formnews_edit' enctype='multipart/form-data'>
                            <input type='hidden' class='form-control' id='news_id' name='news_id' value='" . htmlspecialchars($row['news_id']) . "'>
                            <div class='row'>
                                <div>
                                    <div style='margin: 10px;'>
                                        <div style='margin: 10px; text-align: end;'>
                                            <button type='button' id='backToNewsList' class='btn btn-secondary'> 
                                                <i class='fas fa-arrow-left'></i> " . $texts[$lang]['back_button'] . " 
                                            </button>
                                        </div>
                                        <label><span>" . $texts[$lang]['cover_photo'] . "</span>:</label>
                                        <div><span>" . $texts[$lang]['cover_photo_size'] . "</span></div>
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
                                                        <img src='https://flagcdn.com/w320/th.png' alt='Thai Flag' class='flag-icon' style=' width: 36px; margin-right: 8px;'>Thai
                                                    </button>
                                                </li>
                                                <li class='nav-item' role='presentation'>
                                                    <button class='nav-link' id='en-tab' data-bs-toggle='tab' data-bs-target='#en' type='button' role='tab' aria-controls='en' aria-selected='false'>
                                                        <img src='https://flagcdn.com/w320/gb.png' alt='English Flag' class='flag-icon' style=' width: 36px; margin-right: 8px;'>English
                                                    </button>
                                                </li>
                                                <li class='nav-item' role='presentation'>
                                                    <button class='nav-link' id='cn-tab' data-bs-toggle='tab' data-bs-target='#cn' type='button' role='tab' aria-controls='cn' aria-selected='false'>
                                                        <img src='https://flagcdn.com/w320/cn.png' alt='Chinese Flag' class='flag-icon' style=' width: 36px; margin-right: 8px;'>Chinese
                                                    </button>
                                                </li>
                                                <li class='nav-item' role='presentation'>
                                                    <button class='nav-link' id='jp-tab' data-bs-toggle='tab' data-bs-target='#jp' type='button' role='tab' aria-controls='jp' aria-selected='false'>
                                                        <img src='https://flagcdn.com/w320/jp.png' alt='Japanese Flag' class='flag-icon' style=' width: 36px; margin-right: 8px;'>Japanese
                                                    </button>
                                                </li>
                                                <li class='nav-item' role='presentation'>
                                                    <button class='nav-link' id='kr-tab' data-bs-toggle='tab' data-bs-target='#kr' type='button' role='tab' aria-controls='kr' aria-selected='false'>
                                                        <img src='https://flagcdn.com/w320/kr.png' alt='Korean Flag' class='flag-icon' style=' width: 36px; margin-right: 8px;'>Korean
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class='card-body'>
                                            <div class='tab-content' id='languageTabsContent'>
                                                <div class='tab-pane fade show active' id='th' role='tabpanel' aria-labelledby='th-tab'>
                                                    <div style='margin: 10px;'>
                                                        <label><span>" . $texts['th']['subject'] . " (TH)</span>:</label>
                                                        <input type='text' class='form-control' id='news_subject' name='news_subject' value='" . htmlspecialchars($row['subject_news']) . "'>
                                                    </div>
                                                    <div style='margin: 10px;'>
                                                        <label><span>" . $texts['th']['description'] . " (TH)</span>:</label>
                                                        <textarea class='form-control' id='news_description' name='news_description'>" . htmlspecialchars($row['description_news']) . "</textarea>
                                                    </div>
                                                    <div style='margin: 10px;'>
                                                        <label><span>" . $texts['th']['content'] . " (TH)</span>:</label>
                                                        <textarea class='form-control summernote' id='summernote_update' name='news_content'>" . $content_th_with_correct_paths . "</textarea>
                                                    </div>
                                                </div>
                                                <div class='tab-pane fade' id='en' role='tabpanel' aria-labelledby='en-tab'>
                                                    <button type='button' id='copyFromThai' class='btn btn-info btn-sm float-end mb-2'>Origami Ai Translate</button>
                                                    <div id='loadingIndicator' class='loading-overlay' style='display: none;'>
                                                        <div class='loading-spinner'></div>
                                                    </div>
                                                    <div style='margin: 10px;'>
                                                        <label><span>" . $texts['en']['subject'] . " (EN)</span>:</label>
                                                        <input type='text' class='form-control' id='news_subject_en' name='news_subject_en' value='" . htmlspecialchars($row['subject_news_en']) . "'>
                                                    </div>
                                                    <div style='margin: 10px;'>
                                                        <label><span>" . $texts['en']['description'] . " (EN)</span>:</label>
                                                        <textarea class='form-control' id='news_description_en' name='news_description_en'>" . htmlspecialchars($row['description_news_en']) . "</textarea>
                                                    </div>
                                                    <div style='margin: 10px;'>
                                                        <label><span>" . $texts['en']['content'] . " (EN)</span>:</label>
                                                        <textarea class='form-control summernote' id='summernote_update_en' name='news_content_en'>" . $content_en_with_correct_paths . "</textarea>
                                                    </div>
                                                </div>
                                                <div class='tab-pane fade' id='cn' role='tabpanel' aria-labelledby='cn-tab'>
                                                    <button type='button' id='copyFromThaiCN' class='btn btn-info btn-sm float-end mb-2'>Origami Ai Translate</button>
                                                    <div id='loadingIndicatorCN' class='loading-overlay' style='display: none;'>
                                                        <div class='loading-spinner'></div>
                                                    </div>
                                                    <div style='margin: 10px;'>
                                                        <label><span>" . $texts['cn']['subject'] . " (CN)</span>:</label>
                                                        <input type='text' class='form-control' id='news_subject_cn' name='news_subject_cn' value='" . htmlspecialchars($row['subject_news_cn']) . "'>
                                                    </div>
                                                    <div style='margin: 10px;'>
                                                        <label><span>" . $texts['cn']['description'] . " (CN)</span>:</label>
                                                        <textarea class='form-control' id='news_description_cn' name='news_description_cn'>" . htmlspecialchars($row['description_news_cn']) . "</textarea>
                                                    </div>
                                                    <div style='margin: 10px;'>
                                                        <label><span>" . $texts['cn']['content'] . " (CN)</span>:</label>
                                                        <textarea class='form-control summernote' id='summernote_update_cn' name='news_content_cn'>" . $content_cn_with_correct_paths . "</textarea>
                                                    </div>
                                                </div>
                                                <div class='tab-pane fade' id='jp' role='tabpanel' aria-labelledby='jp-tab'>
                                                    <button type='button' id='copyFromThaiJP' class='btn btn-info btn-sm float-end mb-2'>Origami Ai Translate</button>
                                                    <div id='loadingIndicatorJP' class='loading-overlay' style='display: none;'>
                                                        <div class='loading-spinner'></div>
                                                    </div>
                                                    <div style='margin: 10px;'>
                                                        <label><span>" . $texts['jp']['subject'] . " (JP)</span>:</label>
                                                        <input type='text' class='form-control' id='news_subject_jp' name='news_subject_jp' value='" . htmlspecialchars($row['subject_news_jp']) . "'>
                                                    </div>
                                                    <div style='margin: 10px;'>
                                                        <label><span>" . $texts['jp']['description'] . " (JP)</span>:</label>
                                                        <textarea class='form-control' id='news_description_jp' name='news_description_jp'>" . htmlspecialchars($row['description_news_jp']) . "</textarea>
                                                    </div>
                                                    <div style='margin: 10px;'>
                                                        <label><span>" . $texts['jp']['content'] . " (JP)</span>:</label>
                                                        <textarea class='form-control summernote' id='summernote_update_jp' name='news_content_jp'>" . $content_jp_with_correct_paths . "</textarea>
                                                    </div>
                                                </div>
                                                <div class='tab-pane fade' id='kr' role='tabpanel' aria-labelledby='kr-tab'>
                                                    <button type='button' id='copyFromThaiKR' class='btn btn-info btn-sm float-end mb-2'>Origami Ai Translate</button>
                                                    <div id='loadingIndicatorKR' class='loading-overlay' style='display: none;'>
                                                        <div class='loading-spinner'></div>
                                                    </div>
                                                    <div style='margin: 10px;'>
                                                        <label><span>" . $texts['kr']['subject'] . " (KR)</span>:</label>
                                                        <input type='text' class='form-control' id='news_subject_kr' name='news_subject_kr' value='" . htmlspecialchars($row['subject_news_kr']) . "'>
                                                    </div>
                                                    <div style='margin: 10px;'>
                                                        <label><span>" . $texts['kr']['description'] . " (KR)</span>:</label>
                                                        <textarea class='form-control' id='news_description_kr' name='news_description_kr'>" . htmlspecialchars($row['description_news_kr']) . "</textarea>
                                                    </div>
                                                    <div style='margin: 10px;'>
                                                        <label><span>" . $texts['kr']['content'] . " (KR)</span>:</label>
                                                        <textarea class='form-control summernote' id='summernote_update_kr' name='news_content_kr'>" . $content_kr_with_correct_paths . "</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div style='margin: 10px; text-align: end;'>
                                        <button type='button' id='submitEditnews' class='btn btn-success'>
                                            <i class='fas fa-save'></i> " . $texts[$lang]['save_button'] . "
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        ";
                    } else {
                        echo "<div class='alert alert-warning'>" . $texts[$lang]['no_data'] . "</div>";
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
              // --- ฟังก์ชันสำหรับจัดการการอัปโหลดรูปภาพใน Summernote (ปรับปรุงแล้ว) ---
        function summernoteImageUpload(file, editor) { // เปลี่ยนรับเป็น 'file' ตัวเดียว
            var formData = new FormData();
            formData.append("file", file); // ใช้ file ตัวเดียว
            formData.append("action", "upload_image_content"); 

            $.ajax({
                url: "actions/process_news.php",
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
                }
                if (target === '#cn') {
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
            }
            if (target === '#jp') {
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
            }
            // เพิ่มส่วนของ KR
            if (target === '#kr') {
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
        $('#copyFromThai').on('click', function() {
            // 1. แสดง Loading Indicator
            $('#loadingIndicator').show(); // ให้โชว์ loading animation

            // ดึงค่าจากฟอร์มภาษาไทย
            var thaiSubject = $('#news_subject').val();
            var thaiDescription = $('#news_description').val();
            var thaiContent = $('#summernote_update').summernote('code');

            // สร้าง Object สำหรับข้อมูลที่จะส่งไป
            const dataToSend = {
                language: "th",
                translate: "en",z
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
                    $('#news_subject_en').val(response.subject);
                    $('#news_description_en').val(response.description);
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
        $('#copyFromThaiCN').on('click', function() {
            // 1. แสดง Loading Indicator
            $('#loadingIndicatorCN').show(); // ให้โชว์ loading animation

            // ดึงค่าจากฟอร์มภาษาไทย
            var thaiSubject = $('#news_subject').val();
            var thaiDescription = $('#news_description').val();
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
                    $('#news_subject_cn').val(response.subject);
                    $('#news_description_cn').val(response.description);
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
                $('#loadingIndicatorCN').hide();
            });
        });
        
        // New Copy from Thai to Japanese button functionality
        $('#copyFromThaiJP').on('click', function() {
            // 1. แสดง Loading Indicator
            $('#loadingIndicatorJP').show(); // ให้โชว์ loading animation

            // ดึงค่าจากฟอร์มภาษาไทย
            var thaiSubject = $('#news_subject').val();
            var thaiDescription = $('#news_description').val();
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
                    $('#news_subject_jp').val(response.subject);
                    $('#news_description_jp').val(response.description);
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
                $('#loadingIndicatorJP').hide();
            });
        });

        // เพิ่ม New Copy from Thai to Korean button functionality
        $('#copyFromThaiKR').on('click', function() {
            // 1. แสดง Loading Indicator
            $('#loadingIndicatorKR').show(); // ให้โชว์ loading animation

            // ดึงค่าจากฟอร์มภาษาไทย
            var thaiSubject = $('#news_subject').val();
            var thaiDescription = $('#news_description').val();
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
                    $('#news_subject_kr').val(response.subject);
                    $('#news_description_kr').val(response.description);
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
                $('#loadingIndicatorKR').hide();
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
<script src='js/news_.js?v=<?php echo time(); ?>'></script>
<script src='../js/index_.js?v=<?php echo time(); ?>'></script>
</body>
</html>