<?php
include '../check_permission.php';

// Define the content in 5 languages
$translations = [
    'th' => [
        'page_title' => 'จัดการความคิดเห็น',
        'heading_comments' => 'ความคิดเห็นทั้งหมด',
        'col_no' => 'ลำดับ',
        'col_user_id' => 'User ID',
        'col_full_name' => 'ชื่อผู้แสดงความคิดเห็น',
        'col_email' => 'อีเมล',
        'col_comment' => 'ความคิดเห็น',
        'col_date' => 'วันที่',
        'col_url' => 'URL',
        'no_comments' => 'ไม่มีความคิดเห็น',
        'error_message' => 'เกิดข้อผิดพลาด:',
        'search' => 'ค้นหา:',
        'length_menu' => 'แสดง _MENU_ รายการ',
        'info' => 'แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ',
        'next' => 'ถัดไป',
        'previous' => 'ก่อนหน้า',
    ],
    'en' => [
        'page_title' => 'Comment Management',
        'heading_comments' => 'All Comments',
        'col_no' => 'No.',
        'col_user_id' => 'User ID',
        'col_full_name' => 'Commenter Name',
        'col_email' => 'Email',
        'col_comment' => 'Comment',
        'col_date' => 'Date',
        'col_url' => 'URL',
        'no_comments' => 'No comments found',
        'error_message' => 'An error occurred:',
        'search' => 'Search:',
        'length_menu' => 'Show _MENU_ entries',
        'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
        'next' => 'Next',
        'previous' => 'Previous',
    ],
    'cn' => [
        'page_title' => '评论管理',
        'heading_comments' => '所有评论',
        'col_no' => '序号',
        'col_user_id' => '用户ID',
        'col_full_name' => '评论者姓名',
        'col_email' => '电子邮件',
        'col_comment' => '评论',
        'col_date' => '日期',
        'col_url' => 'URL',
        'no_comments' => '没有评论',
        'error_message' => '发生错误：',
        'search' => '搜索:',
        'length_menu' => '显示 _MENU_ 项',
        'info' => '显示第 _START_ 到 _END_ 项，共 _TOTAL_ 项',
        'next' => '下一页',
        'previous' => '上一页',
    ],
    'jp' => [
        'page_title' => 'コメント管理',
        'heading_comments' => 'すべてのコメント',
        'col_no' => '番号',
        'col_user_id' => 'ユーザーID',
        'col_full_name' => 'コメント者名',
        'col_email' => 'メールアドレス',
        'col_comment' => 'コメント',
        'col_date' => '日付',
        'col_url' => 'URL',
        'no_comments' => 'コメントはありません',
        'error_message' => 'エラーが発生しました：',
        'search' => '検索:',
        'length_menu' => '_MENU_ 件表示',
        'info' => '_TOTAL_ 件中 _START_ から _END_ まで表示',
        'next' => '次へ',
        'previous' => '前へ',
    ],
    'kr' => [
        'page_title' => '댓글 관리',
        'heading_comments' => '모든 댓글',
        'col_no' => '번호',
        'col_user_id' => '사용자 ID',
        'col_full_name' => '작성자 이름',
        'col_email' => '이메일',
        'col_comment' => '댓글',
        'col_date' => '날짜',
        'col_url' => 'URL',
        'no_comments' => '댓글이 없습니다',
        'error_message' => '오류가 발생했습니다:',
        'search' => '검색:',
        'length_menu' => '_MENU_개씩 보기',
        'info' => '총 _TOTAL_개 중 _START_에서 _END_번째',
        'next' => '다음',
        'previous' => '이전',
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
    <link href='../css/index_.css?v=<?php echo time(); ?>' rel='stylesheet'>
    <?php include '../template/header.php' ?>
</head>
<body>
<div class="container mt-1">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fas fa-comments"></i> <?= $text['heading_comments'] ?></h4>
        <!-- <div class="btn-group">
            <a href="?lang=th" class="btn lang-switch-btn <?= ($lang == 'th') ? 'btn-primary' : 'btn-outline-primary' ?>">TH</a>
            <a href="?lang=en" class="btn lang-switch-btn <?= ($lang == 'en') ? 'btn-primary' : 'btn-outline-primary' ?>">EN</a>
            <a href="?lang=cn" class="btn lang-switch-btn <?= ($lang == 'cn') ? 'btn-primary' : 'btn-outline-primary' ?>">CN</a>
            <a href="?lang=jp" class="btn lang-switch-btn <?= ($lang == 'jp') ? 'btn-primary' : 'btn-outline-primary' ?>">JP</a>
            <a href="?lang=kr" class="btn lang-switch-btn <?= ($lang == 'kr') ? 'btn-primary' : 'btn-outline-primary' ?>">KR</a>
        </div> -->
    </div>
    <div class="table-responsive mt-4">
        <table id="commentsTable" class="table table-bordered table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th class="text-white"><?= $text['col_no'] ?></th>
                    <th class="text-white"><?= $text['col_user_id'] ?></th>
                    <th class="text-white"><?= $text['col_full_name'] ?></th>
                    <th class="text-white"><?= $text['col_email'] ?></th>
                    <th class="text-white"><?= $text['col_comment'] ?></th>
                    <th class="text-white"><?= $text['col_date'] ?></th>
                    <th class="text-white"><?= $text['col_url'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                // DEBUG: แสดง error ถ้ามี
                mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

                try {
                    require_once(__DIR__ . '/../../../lib/connect.php');

                    $stmt = $conn->prepare("
                        SELECT comment_id, user_id, full_name, email, comment, page_url, date_create 
                        FROM mb_comments 
                        ORDER BY date_create DESC
                    ");
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $counter = 1;
                        while ($row = $result->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . $counter++ . '</td>';
                            echo '<td>' . htmlspecialchars($row['user_id']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['full_name']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                            echo '<td>' . nl2br(htmlspecialchars($row['comment'])) . '</td>';
                            echo '<td>' . date('d/m/Y H:i', strtotime($row['date_create'])) . '</td>';
                            echo '<td>' . htmlspecialchars($row['page_url']) . '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="7" class="text-center">' . $text['no_comments'] . '</td></tr>';
                    }

                    $stmt->close();
                    $conn->close();
                } catch (Exception $e) {
                    echo '<tr><td colspan="7" class="text-danger">' . $text['error_message'] . ' ' . $e->getMessage() . '</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script src='../js/index_.js?v=<?php echo time(); ?>'></script>

<script>
$(document).ready(function() {
    $('#commentsTable').DataTable({
        "language": {
            "search": "<?= $text['search'] ?>",
            "lengthMenu": "<?= $text['length_menu'] ?>",
            "info": "<?= $text['info'] ?>",
            "paginate": {
                "next": "<?= $text['next'] ?>",
                "previous": "<?= $text['previous'] ?>"
            }
        },
        "columnDefs": [
            // กำหนดให้คอลัมน์แรก (ลำดับ) ไม่สามารถเรียงและค้นหาได้
            { "orderable": false, "searchable": false, "targets": 0 }
        ]
    });
});
</script>

</body>
</html>