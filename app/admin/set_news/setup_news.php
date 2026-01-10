<?php
include '../check_permission.php';

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
    'page_title' => [
        'th' => 'ตั้งค่าข่าว',
        'en' => 'Setup News',
        'cn' => '新闻设置',
        'jp' => 'ニュース設定',
        'kr' => '뉴스 설정'
    ],
    'write_news' => [
        'th' => 'เขียนข่าว',
        'en' => 'Write news',
        'cn' => '撰写新闻',
        'jp' => 'ニュースを書く',
        'kr' => '뉴스 작성'
    ],
    'cover_photo' => [
        'th' => 'รูปภาพหน้าปก',
        'en' => 'Cover photo',
        'cn' => '封面图片',
        'jp' => '表紙の写真',
        'kr' => '표지 사진'
    ],
    'cover_photo_size' => [
        'th' => 'ขนาดรูปภาพที่เหมาะสม width: 350px และ height: 250px',
        'en' => 'Appropriate image size: width: 350px and height: 250px',
        'cn' => '合适的图片尺寸：宽: 350px 高: 250px',
        'jp' => '適切な画像サイズ：幅: 350px 高さ: 250px',
        'kr' => '적절한 이미지 크기: 너비: 350px 높이: 250px'
    ],
    'subject' => [
        'th' => 'หัวข้อ',
        'en' => 'Subject',
        'cn' => '主题',
        'jp' => '件名',
        'kr' => '제목'
    ],
    'description' => [
        'th' => 'รายละเอียด',
        'en' => 'Description',
        'cn' => '描述',
        'jp' => '説明',
        'kr' => '설명'
    ],
    'add_news_button' => [
        'th' => 'เพิ่มข่าว',
        'en' => 'Add news',
        'cn' => '添加新闻',
        'jp' => 'ニュースを追加',
        'kr' => '뉴스 추가'
    ],
    'back_button' => [
        'th' => 'กลับ',
        'en' => 'Back',
        'cn' => '返回',
        'jp' => '戻る',
        'kr' => '뒤로'
    ],
    'content' => [
        'th' => 'เนื้อหา',
        'en' => 'Content',
        'cn' => '内容',
        'jp' => '内容',
        'kr' => '내용'
    ],
];

// ฟังก์ชันสำหรับเรียกใช้ข้อความตามภาษาที่เลือก
function getTextByLang($key) {
    global $texts, $lang;
    return $texts[$key][$lang] ?? $texts[$key]['th'];
}
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

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>

    <link href='../css/index_.css?v=<?php echo time();?>' rel='stylesheet'>

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
        .note-toolbar{
            position: sticky !important;
            top: 70px !important;
            z-index: 1 !important;
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
                        <i class="fa-solid fa-pen-clip"></i>
                        <?= getTextByLang('write_news') ?>
                    </h4>
                    
                    <form id="formnews" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-4">
                                <div style="margin: 10px;">
                                    <label for="">
                                        <span><?= getTextByLang('cover_photo') ?></span>: 
                                        <div><span><?= getTextByLang('cover_photo_size') ?></span></div>
<div>photo size < 500 kB</div>
                                    </label>
                                    <div class="previewContainer">
                                        <img id="previewImage" src="" alt="Image Preview" style="max-width: 100%; display: none;">
                                    </div>
                                </div>
                                <div style="margin: 10px;">
                                    <input type="file" class="form-control" id="fileInput" name="fileInput[]">
                                </div>
                                <div style="margin: 10px;">
                                    <label for="">
                                        <span><?= getTextByLang('subject') ?></span>:
                                    </label>
                                    <input type="text" class="form-control" id="news_subject" name="news_subject">
                                </div>
                                <div style="margin: 10px;">
                                    <label for="">
                                        <span><?= getTextByLang('description') ?></span>:
                                    </label>
                                    <div>
                                        <textarea class="form-control" id="news_description" name="news_description"></textarea>
                                    </div>
                                </div>
                                <div style="margin: 10px; text-align: end;">
                                    <button 
                                        type="button" 
                                        id="submitAddnews"
                                        class="btn btn-primary">
                                        <i class="fas fa-plus"></i>
                                        <?= getTextByLang('add_news_button') ?>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div style='margin: 10px; text-align: end;'>
                                    <button type='button' id='backToShopList' class='btn btn-secondary'> 
                                        <i class='fas fa-arrow-left'></i> <?= getTextByLang('back_button') ?>
                                    </button>
                                </div>
                                <div style="margin: 10px;">
                                    <label for="">
                                        <span><?= getTextByLang('content') ?></span>:
                                    </label>
                                    <div>
                                        <textarea class="form-control summernote" id="summernote" name="news_content"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<script src='../js/index_.js?v=<?php echo time();?>'></script>
<script src='js/news_.js?v=<?php echo time();?>'></script>

</body>
</html>