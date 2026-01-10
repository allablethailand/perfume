<?php 
include '../check_permission.php';

// โค้ดสำหรับจัดการภาษา (ที่มากับโค้ดเดิมของคุณ)
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
    if (isset($_SESSION['lang'])) {
        $lang = $_SESSION['lang'];
    }
}

// ข้อความสำหรับแต่ละภาษา (ไม่ต้องยุ่งกับฐานข้อมูล)
$text = [
    'th' => [
        'page_title' => 'ตั้งค่าโปรเจกต์',
        'write_project' => 'เขียนโปรเจกต์',
        'cover_photo' => 'รูปหน้าปก',
        'image_size' => 'ขนาดรูปภาพที่เหมาะสม width: 350px และ height: 250px',
        'subject' => 'หัวข้อ',
        'description' => 'รายละเอียด',
        'related_shops' => 'สินค้าที่เกี่ยวข้อง (เลือกได้หลายชิ้น)',
        'project_button' => 'โปรเจกต์',
        'back_button' => 'กลับ',
        'content' => 'เนื้อหา',
        'content_placeholder' => 'กรอกเนื้อหาโปรเจกต์',
        'select_related_shop' => 'เลือกสินค้าที่เกี่ยวข้อง'
    ],
    'en' => [
        'page_title' => 'Setup project',
        'write_project' => 'Write project',
        'cover_photo' => 'Cover photo',
        'image_size' => 'Recommended image size: width: 350px and height: 250px',
        'subject' => 'Subject',
        'description' => 'Description',
        'related_shops' => 'Related products (select multiple)',
        'project_button' => 'Project',
        'back_button' => 'Back',
        'content' => 'Content',
        'content_placeholder' => 'Enter project content',
        'select_related_shop' => 'Select related products'
    ],
    'cn' => [
        'page_title' => '项目设置',
        'write_project' => '撰写项目',
        'cover_photo' => '封面照片',
        'image_size' => '推荐图片尺寸：宽: 350px，高: 250px',
        'subject' => '主题',
        'description' => '描述',
        'related_shops' => '相关商品 (可多选)',
        'project_button' => '项目',
        'back_button' => '返回',
        'content' => '内容',
        'content_placeholder' => '输入项目内容',
        'select_related_shop' => '选择相关商品'
    ],
    'jp' => [
        'page_title' => 'プロジェクト設定',
        'write_project' => 'プロジェクトを書く',
        'cover_photo' => 'カバー写真',
        'image_size' => '推奨画像サイズ：幅: 350px、高さ: 250px',
        'subject' => '件名',
        'description' => '説明',
        'related_shops' => '関連商品（複数選択可能）',
        'project_button' => 'プロジェクト',
        'back_button' => '戻る',
        'content' => '内容',
        'content_placeholder' => 'プロジェクト内容を入力',
        'select_related_shop' => '関連商品を選択'
    ],
    'kr' => [
        'page_title' => '프로젝트 설정',
        'write_project' => '프로젝트 작성',
        'cover_photo' => '표지 사진',
        'image_size' => '권장 이미지 크기: 너비: 350px, 높이: 250px',
        'subject' => '제목',
        'description' => '설명',
        'related_shops' => '관련 상품 (다중 선택 가능)',
        'project_button' => '프로젝트',
        'back_button' => '뒤로',
        'content' => '내용',
        'content_placeholder' => '프로젝트 내용을 입력하세요',
        'select_related_shop' => '관련 상품 선택'
    ],
];

// โค้ดสำหรับเชื่อมต่อฐานข้อมูลเดิม
// $conn = new mysqli("localhost", "user", "password", "database");
// if ($conn->connect_error) {
//     die("Connection failed: " . $conn->connect_error);
// }

// เพิ่มส่วนนี้: ดึงข้อมูลสินค้าทั้งหมดจากตาราง dn_shop
$sql_shops = "SELECT shop_id, subject_shop FROM dn_shop WHERE del = 0 ORDER BY subject_shop ASC";
$result_shops = $conn->query($sql_shops);
$shops = [];
if ($result_shops->num_rows > 0) {
    while ($row = $result_shops->fetch_assoc()) {
        $shops[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $text[$lang]['page_title']; ?></title>
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
                        <?php echo $text[$lang]['write_project']; ?>
                    </h4>
                    
                    <form id="formproject" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-4">
                                <div style="margin: 10px;">
                                    <label for="">
                                        <span><?php echo $text[$lang]['cover_photo']; ?></span>:
                                        <div><span><?php echo $text[$lang]['image_size']; ?></span></div>
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
                                        <span><?php echo $text[$lang]['subject']; ?></span>:
                                    </label>
                                    <input type="text" class="form-control" id="project_subject" name="project_subject">
                                </div>
                                <div style="margin: 10px;">
                                    <label for="">
                                        <span><?php echo $text[$lang]['description']; ?></span>:
                                    </label>
                                    <div>
                                        <textarea class="form-control" id="project_description" name="project_description"></textarea>
                                    </div>
                                </div>
                                
                                <div style="margin: 10px;">
                                    <label><?php echo $text[$lang]['related_shops']; ?></label>
                                    <select class="form-control select2" multiple="multiple" name="related_shops[]" style="width: 100%;">
                                        <?php foreach ($shops as $shop): ?>
                                            <option value="<?= htmlspecialchars($shop['shop_id']) ?>">
                                                <?= htmlspecialchars($shop['subject_shop']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div style="margin: 10px; text-align: end;">
                                    <button 
                                        type="button" 
                                        id="submitAddproject"
                                        class="btn btn-primary">
                                        <i class="fas fa-plus"></i>
                                        <?php echo $text[$lang]['project_button']; ?>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="col-md-8">
                                <div style='margin: 10px; text-align: end;'>
                                    <button type='button' id='backToShopList' class='btn btn-secondary'> 
                                        <i class='fas fa-arrow-left'></i> <?php echo $text[$lang]['back_button']; ?>
                                    </button>
                                </div>
                                <div style="margin: 10px;">
                                    <label for="">
                                        <span><?php echo $text[$lang]['content']; ?></span>:
                                    </label>
                                    <div>
                                        <textarea class="form-control summernote" id="summernote" name="project_content"></textarea>
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
    <script src='js/project_.js?v=<?php echo time();?>'></script>
    <script>
        $(document).ready(function() {
            // Init Summernote
            $('#summernote').summernote({
                placeholder: '<?php echo $text[$lang]['content_placeholder']; ?>',
                tabsize: 2,
                height: 300
            });
            // Init Select2
            $('.select2').select2({
                placeholder: "<?php echo $text[$lang]['select_related_shop']; ?>",
                allowClear: true
            });
            // Image Preview Function
            $('#fileInput').change(function() {
                if (this.files && this.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#previewImage').attr('src', e.target.result).show();
                    }
                    reader.readAsDataURL(this.files[0]);
                } else {
                    $('#previewImage').hide();
                }
            });
        });
    </script>
</body>
</html>