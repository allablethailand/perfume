<?php include '../check_permission.php'?>
<?php
// กำหนดภาษาที่รองรับ
$supportedLangs = ['th', 'en', 'cn', 'jp', 'kr'];

// กำหนดภาษาเริ่มต้น
$lang = 'th';

// ตรวจสอบและตั้งค่าภาษาจาก URL หรือ Session
if (isset($_GET['lang'])) {
    $newLang = $_GET['lang'];
    if (in_array($newLang, $supportedLangs)) {
        $_SESSION['lang'] = $newLang;
        $lang = $newLang;
    }
} elseif (isset($_SESSION['lang'])) {
    $lang = $_SESSION['lang'];
}

// ข้อมูลข้อความในแต่ละภาษา (ไม่ต้องยุ่งกับ Database)
$text = [
    'th' => [
        'page_title' => 'รายการโปรเจกต์',
        'list_project' => 'รายการโปรเจกต์',
        'write_project' => 'เขียนโปรเจกต์',
        'table_no' => 'ลำดับ',
        'table_subject' => 'หัวข้อ',
        'table_date_created' => 'วันที่สร้าง',
        'table_action' => 'การจัดการ',
        'parent_group' => 'กลุ่มแม่',
        'sub_group' => 'กลุ่มย่อย',
        'manage_category' => 'จัดการหมวดหมู่',
    ],
    'en' => [
        'page_title' => 'List project',
        'list_project' => 'List project',
        'write_project' => 'Write project',
        'table_no' => 'No.',
        'table_subject' => 'Subject',
        'table_date_created' => 'Date created',
        'table_action' => 'Action',
        'parent_group' => 'Parent Group',
        'sub_group' => 'Sub Group',
        'manage_category' => 'Manage Category',
    ],
    'cn' => [
        'page_title' => '项目列表',
        'list_project' => '项目列表',
        'write_project' => '撰写项目',
        'table_no' => '序号',
        'table_subject' => '主题',
        'table_date_created' => '创建日期',
        'table_action' => '操作',
        'parent_group' => '主分类',
        'sub_group' => '子分类',
        'manage_category' => '管理分类',
    ],
    'jp' => [
        'page_title' => 'プロジェクト一覧',
        'list_project' => 'プロジェクト一覧',
        'write_project' => 'プロジェクトを書く',
        'table_no' => '番号',
        'table_subject' => '件名',
        'table_date_created' => '作成日',
        'table_action' => 'アクション',
        'parent_group' => '親グループ',
        'sub_group' => 'サブグループ',
        'manage_category' => 'カテゴリ管理',
    ],
    'kr' => [
        'page_title' => '프로젝트 목록',
        'list_project' => '프로젝트 목록',
        'write_project' => '프로젝트 작성',
        'table_no' => '번호',
        'table_subject' => '제목',
        'table_date_created' => '생성일',
        'table_action' => '행동',
        'parent_group' => '상위 그룹',
        'sub_group' => '하위 그룹',
        'manage_category' => '카테고리 관리',
    ]
];

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
    <link href='../css/index_.css?v=<?php echo time(); ?>' rel='stylesheet'>
    <style>
        .button-class {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
        }

        .responsive-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 10px;
        }

        /* Media query for smaller screens */
        @media (max-width: 768px) {
            .responsive-grid {
                grid-template-columns: 1fr;
            }
        }

        .btn-circle {
            border: none;
            width: 30px;
            height: 28px;
            border-radius: 50%;
            font-size: 14px;
        }

        .btn-edit {
            background-color: #FFC107;
            color: #ffffff;
        }

        .btn-del {
            background-color: #ff4537;
            color: #ffffff;
        }
    </style>
</head>

<?php include '../template/header.php' ?>

<body>
    <div class="content-sticky" id="">
        <div class="container-fluid">
            <div class="box-content">
                <div class="row">
                    <div>
                        <div class="responsive-grid">
                            <div style="margin: 10px;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
    <h4 class="line-ref mb-3">
        <i class="far fa-newspaper"></i>
        <?php echo $text[$lang]['list_project']; ?>
    </h4>

    <div style="display: flex; gap: 10px;">
        <a type="button" class="btn btn-info" 
           href="<?php echo $base_path_admin.'set_project/group_management.php'?>">
            <i class="fas fa-layer-group"></i> 
            <?php echo $text[$lang]['manage_category']; ?>
        </a>

        <a type="button" class="btn btn-primary"
           href="<?php echo $base_path_admin.'set_project/setup_project.php'?>">
            <i class="fa-solid fa-plus"></i>
            <?php echo $text[$lang]['write_project']; ?>
        </a>
    </div>
</div>

                                <table id="td_list_project" class="table table-hover" style="width:100%;">
                                    <thead>
                                        <tr>
                                            <th><?php echo $text[$lang]['table_no']; ?></th>
                                            <th><?php echo $text[$lang]['table_subject']; ?></th>
                                            <th><?php echo $text[$lang]['table_date_created']; ?></th>
                                            <th><?php echo $text[$lang]['table_action']; ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src='../js/index_.js?v=<?php echo time(); ?>'></script>
    <script src='js/project_.js?v=<?php echo time(); ?>'></script>
</body>
</html>