<?php
// เริ่ม session
session_start();

include '../check_permission.php';

// กำหนดภาษาเริ่มต้นและตรวจสอบค่าใน URL
$lang = 'th';
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

// สร้าง array สำหรับเก็บข้อความในแต่ละภาษา
$texts = [
    'th' => [
        'title' => 'ตั้งค่าบล็อก',
        'header' => 'เขียนบล็อก',
        'cover_photo' => 'รูปหน้าปก',
        'photo_size' => 'ขนาดรูปภาพที่เหมาะสม width: 350px และ height: 250px',
        'subject' => 'หัวข้อ',
        'description' => 'คำอธิบาย',
        'content' => 'เนื้อหา',
        'related_projects' => 'โปรเจกต์ที่เกี่ยวข้อง (เลือกได้หลายโปรเจกต์)',
        'back_button' => 'ย้อนกลับ',
        'save_button' => 'บันทึกบล็อก',
        'summernote_placeholder' => 'กรอกเนื้อหาบล็อก',
        'select2_placeholder' => 'เลือกโปรเจกต์ที่เกี่ยวข้อง',
    ],
    'en' => [
        'title' => 'Setup Blog',
        'header' => 'Write Blog',
        'cover_photo' => 'Cover photo',
        'photo_size' => 'Recommended image size: width: 350px and height: 250px',
        'subject' => 'Subject',
        'description' => 'Description',
        'content' => 'Content',
        'related_projects' => 'Related Projects (multiple selections allowed)',
        'back_button' => 'Back',
        'save_button' => 'Save Blog',
        'summernote_placeholder' => 'Enter blog content',
        'select2_placeholder' => 'Select related projects',
    ],
    'cn' => [
        'title' => '设置博客',
        'header' => '撰写博客',
        'cover_photo' => '封面照片',
        'photo_size' => '推荐图片尺寸：宽度 350px，高度 250px',
        'subject' => '主题',
        'description' => '描述',
        'content' => '内容',
        'related_projects' => '相关项目（可多选）',
        'back_button' => '返回',
        'save_button' => '保存博客',
        'summernote_placeholder' => '输入博客内容',
        'select2_placeholder' => '选择相关项目',
    ],
    'jp' => [
        'title' => 'ブログ設定',
        'header' => 'ブログを書く',
        'cover_photo' => 'カバー写真',
        'photo_size' => '推奨画像サイズ：幅350px、高さ250px',
        'subject' => '件名',
        'description' => '説明',
        'content' => '内容',
        'related_projects' => '関連プロジェクト（複数選択可能）',
        'back_button' => '戻る',
        'save_button' => 'ブログを保存',
        'summernote_placeholder' => 'ブログの内容を入力してください',
        'select2_placeholder' => '関連プロジェクトを選択',
    ],
    'kr' => [
        'title' => '블로그 설정',
        'header' => '블로그 쓰기',
        'cover_photo' => '표지 사진',
        'photo_size' => '권장 이미지 크기: 너비 350px, 높이 250px',
        'subject' => '제목',
        'description' => '설명',
        'content' => '내용',
        'related_projects' => '관련 프로젝트 (다중 선택 가능)',
        'back_button' => '뒤로',
        'save_button' => '블로그 저장',
        'summernote_placeholder' => '블로그 내용 입력',
        'select2_placeholder' => '관련 프로젝트 선택',
    ],
];

$current_texts = $texts[$lang];

// ในความเป็นจริงต้องมีการเชื่อมต่อฐานข้อมูลตรงนี้
// $conn = new mysqli("localhost", "user", "password", "database");
// if ($conn->connect_error) {
//     die("Connection failed: " . $conn->connect_error);
// }

// เพิ่มส่วนนี้: ดึงข้อมูลสินค้าทั้งหมดจากตาราง dn_project
$sql_projects = "SELECT project_id, subject_project FROM dn_project WHERE del = 0 ORDER BY subject_project ASC";
$result_projects = $conn->query($sql_projects);
$projects = [];
if ($result_projects->num_rows > 0) {
    while ($row = $result_projects->fetch_assoc()) {
        $projects[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_texts['title']; ?></title>

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
                        <?php echo $current_texts['header']; ?>
                    </h4>
                    
                    <form id="formblog" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-4">
                                <div style="margin: 10px;">
                                    <label for="">
                                        <span><?php echo $current_texts['cover_photo']; ?></span>:
                                        <div><span><?php echo $current_texts['photo_size']; ?></span></div>
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
                                        <span><?php echo $current_texts['subject']; ?></span>:
                                    </label>
                                    <input type="text" class="form-control" id="blog_subject" name="blog_subject">
                                </div>
                                <div style="margin: 10px;">
                                    <label for="">
                                        <span><?php echo $current_texts['description']; ?></span>:
                                    </label>
                                    <div>
                                        <textarea class="form-control" id="blog_description" name="blog_description"></textarea>
                                    </div>
                                </div>
                                
                                <div style="margin: 10px;">
                                    <label><?php echo $current_texts['related_projects']; ?></label>
                                    <select class="form-control select2" multiple="multiple" name="related_projects[]" style="width: 100%;">
                                        <?php foreach ($projects as $project): ?>
                                            <option value="<?= htmlspecialchars($project['project_id']) ?>">
                                                <?= htmlspecialchars($project['subject_project']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div style="margin: 10px; text-align: end;">
                                    <button 
                                        type="button" 
                                        id="submitAddblog"
                                        class="btn btn-primary">
                                        <i class="fas fa-plus"></i>
                                        <?php echo $current_texts['save_button']; ?>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="col-md-8">
                                <div style='margin: 10px; text-align: end;'>
                                    <button type='button' id='backToprojectList' class='btn btn-secondary'> 
                                        <i class='fas fa-arrow-left'></i> <?php echo $current_texts['back_button']; ?>
                                    </button>
                                </div>
                                <div style="margin: 10px;">
                                    <label for="">
                                        <span><?php echo $current_texts['content']; ?></span>:
                                    </label>
                                    <div>
                                        <textarea class="form-control summernote" id="summernote" name="blog_content"></textarea>
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
<script src='js/Blog_.js?v=<?php echo time();?>'></script>

<script>
    $(document).ready(function() {
        // Init Summernote
        $('#summernote').summernote({
            placeholder: '<?php echo $current_texts['summernote_placeholder']; ?>',
            tabsize: 2,
            height: 300
        });

        // Init Select2
        $('.select2').select2({
            placeholder: "<?php echo $current_texts['select2_placeholder']; ?>",
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