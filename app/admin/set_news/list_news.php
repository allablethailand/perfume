<?php
include '../check_permission.php';

// ส่วนที่เพิ่ม: ตรวจสอบและกำหนดภาษาจาก URL หรือ Session
// session_start(); // หากต้องการใช้ session
$lang = 'th'; // กำหนดภาษาเริ่มต้น
if (isset($_GET['lang'])) {
    $supportedLangs = ['th', 'en', 'cn', 'jp', 'kr'];
    $newLang = $_GET['lang'];
    if (in_array($newLang, $supportedLangs)) {
        // $_SESSION['lang'] = $newLang; // หากต้องการใช้ session
        $lang = $newLang;
    }
} 
// else if (isset($_SESSION['lang'])) {
//     $lang = $_SESSION['lang'];
// }

// ส่วนที่เพิ่ม: กำหนดข้อความตามแต่ละภาษา
$texts = [
    'list_news' => [
        'th' => 'รายการข่าว',
        'en' => 'List of news',
        'cn' => '新闻列表',
        'jp' => 'ニュース一覧',
        'kr' => '뉴스 목록'
    ],
    'write_news' => [
        'th' => 'เขียนข่าว',
        'en' => 'Write news',
        'cn' => '撰写新闻',
        'jp' => 'ニュースを書く',
        'kr' => '뉴스 작성'
    ],
    'table_no' => [
        'th' => 'ลำดับ',
        'en' => 'No.',
        'cn' => '序号',
        'jp' => '番号',
        'kr' => '번호'
    ],
    'table_subject' => [
        'th' => 'หัวข้อ',
        'en' => 'Subject',
        'cn' => '主题',
        'jp' => '件名',
        'kr' => '제목'
    ],
    'table_date_created' => [
        'th' => 'วันที่สร้าง',
        'en' => 'Date created',
        'cn' => '创建日期',
        'jp' => '作成日',
        'kr' => '생성일'
    ],
    'table_action' => [
        'th' => 'การจัดการ',
        'en' => 'Action',
        'cn' => '操作',
        'jp' => 'アクション',
        'kr' => '작업'
    ]
];

// ส่วนที่เพิ่ม: ฟังก์ชันสำหรับเรียกใช้ข้อความตามภาษาที่เลือก
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
    <title><?= getTextByLang('list_news') ?></title>

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
                                <div style="display: flex; justify-content: space-between;">
                                    <h4 class="line-ref mb-3"> 
                                        <i class="far fa-newspaper"></i>   
                                        <?= getTextByLang('list_news') ?>
                                    </h4>
                                    <a type="button" class="btn btn-primary" href="<?php echo $base_path_admin.'set_news/setup_news.php'?>">
                                        <i class="fa-solid fa-plus"></i>
                                        <?= getTextByLang('write_news') ?>
                                    </a>
                                </div>
                                <table id="td_list_news" class="table table-hover" style="width:100%;">
                                    <thead>
                                        <tr>
                                            <th><?= getTextByLang('table_no') ?></th>
                                            <th><?= getTextByLang('table_subject') ?></th>
                                            <th><?= getTextByLang('table_date_created') ?></th>
                                            <th><?= getTextByLang('table_action') ?></th>
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
    <script src='js/news_.js?v=<?php echo time(); ?>'></script>
</body>
</html>