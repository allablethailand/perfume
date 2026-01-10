<?php
include '../check_permission.php';

// Define the content in 5 languages
$translations = [
    'th' => [
        'page_title' => 'รายการวิดีโอ',
        'heading' => 'รายการวิดีโอ',
        'add_video' => 'เพิ่มวิดีโอ',
        'col_id' => 'ID',
        'col_title' => 'ชื่อ',
        'col_show_homepage' => 'แสดงหน้าแรก',
        'col_actions' => 'การจัดการ',
        'confirm_delete' => 'ลบใช่ไหม?',
        // Datatables strings
        'search' => 'ค้นหา:',
        'length_menu' => 'แสดง _MENU_ รายการ',
        'info' => 'แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ',
        'paginate_first' => 'หน้าแรก',
        'paginate_last' => 'หน้าสุดท้าย',
        'paginate_next' => 'ถัดไป',
        'paginate_previous' => 'ก่อนหน้า',
        'no_data_available' => 'ไม่มีข้อมูลในตาราง',
        'no_matching_records' => 'ไม่พบรายการที่ตรงกัน',
    ],
    'en' => [
        'page_title' => 'Video List',
        'heading' => 'Video List',
        'add_video' => 'Add Video',
        'col_id' => 'ID',
        'col_title' => 'Title',
        'col_show_homepage' => 'Show on Homepage',
        'col_actions' => 'Actions',
        'confirm_delete' => 'Are you sure you want to delete this video?',
        // Datatables strings
        'search' => 'Search:',
        'length_menu' => 'Show _MENU_ entries',
        'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
        'paginate_first' => 'First',
        'paginate_last' => 'Last',
        'paginate_next' => 'Next',
        'paginate_previous' => 'Previous',
        'no_data_available' => 'No data available in table',
        'no_matching_records' => 'No matching records found',
    ],
    'cn' => [
        'page_title' => '视频列表',
        'heading' => '视频列表',
        'add_video' => '添加视频',
        'col_id' => '编号',
        'col_title' => '标题',
        'col_show_homepage' => '显示在主页',
        'col_actions' => '操作',
        'confirm_delete' => '确定要删除吗？',
        // Datatables strings
        'search' => '搜索:',
        'length_menu' => '显示 _MENU_ 项',
        'info' => '显示第 _START_ 到 _END_ 项，共 _TOTAL_ 项',
        'paginate_first' => '首页',
        'paginate_last' => '末页',
        'paginate_next' => '下一页',
        'paginate_previous' => '上一页',
        'no_data_available' => '表格中无可用数据',
        'no_matching_records' => '没有找到匹配的记录',
    ],
    'jp' => [
        'page_title' => '動画一覧',
        'heading' => '動画一覧',
        'add_video' => '動画を追加',
        'col_id' => 'ID',
        'col_title' => 'タイトル',
        'col_show_homepage' => 'ホームページに表示',
        'col_actions' => 'アクション',
        'confirm_delete' => '削除しますか？',
        // Datatables strings
        'search' => '検索:',
        'length_menu' => '_MENU_ 件表示',
        'info' => '_TOTAL_ 件中 _START_ から _END_ まで表示',
        'paginate_first' => '最初',
        'paginate_last' => '最後',
        'paginate_next' => '次へ',
        'paginate_previous' => '前へ',
        'no_data_available' => 'テーブルにデータがありません',
        'no_matching_records' => '一致するレコードが見つかりません',
    ],
    'kr' => [
        'page_title' => '동영상 목록',
        'heading' => '동영상 목록',
        'add_video' => '동영상 추가',
        'col_id' => 'ID',
        'col_title' => '제목',
        'col_show_homepage' => '홈페이지에 표시',
        'col_actions' => '관리',
        'confirm_delete' => '삭제하시겠습니까?',
        // Datatables strings
        'search' => '검색:',
        'length_menu' => '_MENU_개씩 보기',
        'info' => '총 _TOTAL_개 중 _START_에서 _END_번째',
        'paginate_first' => '처음',
        'paginate_last' => '마지막',
        'paginate_next' => '다음',
        'paginate_previous' => '이전',
        'no_data_available' => '테이블에 사용 가능한 데이터가 없습니다.',
        'no_matching_records' => '일치하는 레코드를 찾을 수 없습니다.',
    ],
];

// session_start();
$lang = $_SESSION['lang'] ?? 'th';
if (isset($_GET['lang']) && in_array($_GET['lang'], ['th', 'en', 'cn', 'jp', 'kr'])) {
    $lang = $_GET['lang'];
    $_SESSION['lang'] = $lang;
}
$text = $translations[$lang];

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

<?php 
include '../template/header.php';
require_once(__DIR__ . '/../../../lib/connect.php');

$result = $conn->query("SELECT * FROM videos ORDER BY created_at DESC");
?>

<body>
<div class="content-sticky">
    <div class="container-fluid">
        <div class="box-content">
            <div class="responsive-grid">
                <div style="margin: 10px;">
                    <div style="display: flex; justify-content: space-between;">
                        <h4 class="line-ref mb-3">
                            <i class="fas fa-video"></i>
                            <?= $text['heading'] ?>
                        </h4>
                        <a href="admin_add_video.php" class="btn btn-primary">
                            <i class="fa fa-plus"></i>
                            <?= $text['add_video'] ?>
                        </a>
                    </div>

                    <table class="table table-hover" id="table_video_list">
                        <thead>
                            <tr>
                                <th><?= $text['col_id'] ?></th>
                                <th><?= $text['col_title'] ?></th>
                                <th><?= $text['col_show_homepage'] ?></th>
                                <th><?= $text['col_actions'] ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['id']) ?></td>
                                    <td><?= htmlspecialchars($row['title']) ?></td>
                                    <td><?= $row['show_on_homepage'] ? '✅' : '' ?></td>
                                    <td>
                                        <a href="admin_edit_video.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="admin_delete_video.php?id=<?= $row['id'] ?>" 
                                           class="btn btn-sm btn-del"
                                           onclick="return confirm('<?= $text['confirm_delete'] ?>')">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('#table_video_list').DataTable({
            "language": {
                "search": "<?= $text['search'] ?>",
                "lengthMenu": "<?= $text['length_menu'] ?>",
                "info": "<?= $text['info'] ?>",
                "paginate": {
                    "first": "<?= $text['paginate_first'] ?>",
                    "last": "<?= $text['paginate_last'] ?>",
                    "next": "<?= $text['paginate_next'] ?>",
                    "previous": "<?= $text['paginate_previous'] ?>"
                },
                "zeroRecords": "<?= $text['no_data_available'] ?>",
                "infoEmpty": "<?= $text['no_matching_records'] ?>",
            }
        });
    });
</script>
<script src='../js/index_.js?v=<?php echo time(); ?>'></script>
</body>
</html>