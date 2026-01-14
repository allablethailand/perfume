<?php 
include '../check_permission.php';

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

// ข้อความสำหรับแต่ละภาษา (ไม่ต้องยุ่งกับฐานข้อมูล)
$text = [
    'th' => [
        'page_title' => 'แก้ไขผู้ใช้',
        'all_users_data' => 'ข้อมูลผู้ใช้ทั้งหมด',
        'table_no' => 'ลำดับ',
        'table_user_id' => 'User ID',
        'table_first_name' => 'ชื่อ',
        'table_last_name' => 'นามสกุล',
        'table_email' => 'อีเมล',
        'table_phone' => 'หมายเลขโทรศัพท์',
        'table_date_create' => 'วันสร้าง',
        'table_date_update' => 'วันอัปเดต',
        'table_action' => 'การจัดการ',
        'no_data' => 'ไม่มีข้อมูลผู้ใช้',
        'delete_button' => 'ลบ',
        'error_message' => 'เกิดข้อผิดพลาด: ',

        // ข้อความสำหรับ SweetAlert และ DataTables
        'dt_search' => 'ค้นหา:',
        'dt_lengthMenu' => 'แสดง _MENU_ รายการ',
        'dt_info' => 'แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ',
        'dt_next' => 'ถัดไป',
        'dt_previous' => 'ก่อนหน้า',
        'dt_infoEmpty' => 'แสดง 0 ถึง 0 จาก 0 รายการ',
        'dt_zeroRecords' => 'ไม่พบข้อมูลที่ตรงกัน',
        'swal_confirm_title' => 'คุณแน่ใจหรือไม่?',
        'swal_confirm_text' => 'ข้อมูลผู้ใช้จะถูกลบอย่างถาวร!',
        'swal_confirm_button' => 'ใช่, ลบเลย!',
        'swal_cancel_button' => 'ยกเลิก',
        'swal_success_title' => 'ลบสำเร็จ!',
        'swal_success_text' => 'ข้อมูลผู้ใช้ถูกลบเรียบร้อยแล้ว.',
        'swal_error_title' => 'เกิดข้อผิดพลาด!',
        'swal_delete_error' => 'ไม่สามารถลบข้อมูลผู้ใช้ได้: ',
        'swal_server_error' => 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้.',
    ],
    'en' => [
        'page_title' => 'Edit Users',
        'all_users_data' => 'All Users Data',
        'table_no' => 'No.',
        'table_user_id' => 'User ID',
        'table_first_name' => 'First Name',
        'table_last_name' => 'Last Name',
        'table_email' => 'Email',
        'table_phone' => 'Phone Number',
        'table_date_create' => 'Date Created',
        'table_date_update' => 'Date Updated',
        'table_action' => 'Action',
        'no_data' => 'No user data found',
        'delete_button' => 'Delete',
        'error_message' => 'An error occurred: ',

        // ข้อความสำหรับ SweetAlert และ DataTables
        'dt_search' => 'Search:',
        'dt_lengthMenu' => 'Show _MENU_ entries',
        'dt_info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
        'dt_next' => 'Next',
        'dt_previous' => 'Previous',
        'dt_infoEmpty' => 'Showing 0 to 0 of 0 entries',
        'dt_zeroRecords' => 'No matching records found',
        'swal_confirm_title' => 'Are you sure?',
        'swal_confirm_text' => 'The user data will be permanently deleted!',
        'swal_confirm_button' => 'Yes, delete it!',
        'swal_cancel_button' => 'Cancel',
        'swal_success_title' => 'Deleted!',
        'swal_success_text' => 'The user data has been successfully deleted.',
        'swal_error_title' => 'Error!',
        'swal_delete_error' => 'Could not delete user data: ',
        'swal_server_error' => 'Could not connect to the server.',
    ],
    'cn' => [
        'page_title' => '编辑用户',
        'all_users_data' => '所有用户数据',
        'table_no' => '序号',
        'table_user_id' => '用户ID',
        'table_first_name' => '名字',
        'table_last_name' => '姓氏',
        'table_email' => '电子邮件',
        'table_phone' => '电话号码',
        'table_date_create' => '创建日期',
        'table_date_update' => '更新日期',
        'table_action' => '操作',
        'no_data' => '未找到用户数据',
        'delete_button' => '删除',
        'error_message' => '发生错误：',

        // ข้อความสำหรับ SweetAlert และ DataTables
        'dt_search' => '搜索:',
        'dt_lengthMenu' => '显示 _MENU_ 项',
        'dt_info' => '显示第 _START_ 至 _END_ 项，共 _TOTAL_ 项',
        'dt_next' => '下一页',
        'dt_previous' => '上一页',
        'dt_infoEmpty' => '显示第 0 至 0 项，共 0 项',
        'dt_zeroRecords' => '没有找到匹配的记录',
        'swal_confirm_title' => '你确定吗？',
        'swal_confirm_text' => '用户数据将被永久删除！',
        'swal_confirm_button' => '是的, 删除！',
        'swal_cancel_button' => '取消',
        'swal_success_title' => '已删除！',
        'swal_success_text' => '用户数据已成功删除。',
        'swal_error_title' => '错误！',
        'swal_delete_error' => '无法删除用户数据：',
        'swal_server_error' => '无法连接到服务器。',
    ],
    'jp' => [
        'page_title' => 'ユーザー編集',
        'all_users_data' => '全ユーザーデータ',
        'table_no' => '番号',
        'table_user_id' => 'ユーザーID',
        'table_first_name' => '名',
        'table_last_name' => '姓',
        'table_email' => 'Eメール',
        'table_phone' => '電話番号',
        'table_date_create' => '作成日',
        'table_date_update' => '更新日',
        'table_action' => 'アクション',
        'no_data' => 'ユーザーデータが見つかりません',
        'delete_button' => '削除',
        'error_message' => 'エラーが発生しました：',

        // ข้อความสำหรับ SweetAlert และ DataTables
        'dt_search' => '検索:',
        'dt_lengthMenu' => '_MENU_ 件表示',
        'dt_info' => '_TOTAL_ 件中 _START_ から _END_ まで表示',
        'dt_next' => '次',
        'dt_previous' => '前',
        'dt_infoEmpty' => '0 件中 0 から 0 まで表示',
        'dt_zeroRecords' => '一致するレコードが見つかりません',
        'swal_confirm_title' => 'よろしいですか？',
        'swal_confirm_text' => 'ユーザーデータは完全に削除されます！',
        'swal_confirm_button' => 'はい、削除します！',
        'swal_cancel_button' => 'キャンセル',
        'swal_success_title' => '削除完了！',
        'swal_success_text' => 'ユーザーデータは正常に削除されました。',
        'swal_error_title' => 'エラー！',
        'swal_delete_error' => 'ユーザーデータを削除できませんでした：',
        'swal_server_error' => 'サーバーに接続できませんでした。',
    ],
    'kr' => [
        'page_title' => '사용자 수정',
        'all_users_data' => '모든 사용자 데이터',
        'table_no' => '번호',
        'table_user_id' => '사용자 ID',
        'table_first_name' => '이름',
        'table_last_name' => '성',
        'table_email' => '이메일',
        'table_phone' => '전화번호',
        'table_date_create' => '생성일',
        'table_date_update' => '업데이트일',
        'table_action' => '관리',
        'no_data' => '사용자 데이터가 없습니다',
        'delete_button' => '삭제',
        'error_message' => '오류가 발생했습니다: ',

        // ข้อความสำหรับ SweetAlert และ DataTables
        'dt_search' => '검색:',
        'dt_lengthMenu' => '_MENU_개씩 보기',
        'dt_info' => '총 _TOTAL_개 항목 중 _START_에서 _END_까지 표시',
        'dt_next' => '다음',
        'dt_previous' => '이전',
        'dt_infoEmpty' => '총 0개 항목 중 0에서 0까지 표시',
        'dt_zeroRecords' => '일치하는 기록을 찾을 수 없습니다',
        'swal_confirm_title' => '확실합니까?',
        'swal_confirm_text' => '사용자 데이터가 영구적으로 삭제됩니다!',
        'swal_confirm_button' => '예, 삭제합니다!',
        'swal_cancel_button' => '취소',
        'swal_success_title' => '삭제되었습니다!',
        'swal_success_text' => '사용자 데이터가 성공적으로 삭제되었습니다.',
        'swal_error_title' => '오류!',
        'swal_delete_error' => '사용자 데이터를 삭제할 수 없습니다: ',
        'swal_server_error' => '서버에 연결할 수 없습니다.',
    ],
];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
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
    <link href='../css/index_.css?v=<?php echo time(); ?>' rel='stylesheet'>
    <?php include '../template/header.php' ?>
    <script>
        // สร้างตัวแปร JavaScript เพื่อเก็บข้อความแปล
        var currentLang = '<?php echo $lang; ?>';
        var translations = <?php echo json_encode($text); ?>;
    </script>
</head>
<body>
<div class="container mt-1">
    <div class="table-responsive mt-4">
        <h4><i class="fas fa-users"></i> <?php echo $text[$lang]['all_users_data']; ?></h4>
        <table id="usersTable" class="table table-bordered table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th class="text-white"><?php echo $text[$lang]['table_no']; ?></th>
                    <th class="text-white"><?php echo $text[$lang]['table_user_id']; ?></th>
                    <th class="text-white"><?php echo $text[$lang]['table_first_name']; ?></th>
                    <th class="text-white"><?php echo $text[$lang]['table_last_name']; ?></th>
                    <th class="text-white"><?php echo $text[$lang]['table_email']; ?></th>
                    <th class="text-white"><?php echo $text[$lang]['table_phone']; ?></th>
                    <th class="text-white"><?php echo $text[$lang]['table_date_create']; ?></th>
                    <th class="text-white"><?php echo $text[$lang]['table_date_update']; ?></th>
                    <th class="text-white"><?php echo $text[$lang]['table_action']; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
                try {
                    require_once(__DIR__ . '/../../../lib/connect.php');
                    $stmt = $conn->prepare("
                        SELECT user_id, first_name, last_name, email, phone_number, date_create, date_update
                        FROM mb_user 
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
                            echo '<td>' . htmlspecialchars($row['first_name']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['last_name']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['phone_number']) . '</td>';
                            echo '<td>' . date('d/m/Y H:i', strtotime($row['date_create'])) . '</td>';
                            echo '<td>' . ($row['date_update'] ? date('d/m/Y H:i', strtotime($row['date_update'])) : 'N/A') . '</td>';
                            echo '<td>
                                    <button class="btn btn-danger btn-sm btn-delete" data-id="' . htmlspecialchars($row['user_id']) . '"><i class="fas fa-trash-alt"></i> ' . $text[$lang]['delete_button'] . '</button>
                                  </td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="9" class="text-center">' . $text[$lang]['no_data'] . '</td></tr>';
                    }
                    $stmt->close();
                    $conn->close();
                } catch (Exception $e) {
                    echo '<tr><td colspan="9" class="text-danger">' . $text[$lang]['error_message'] . $e->getMessage() . '</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
<script src='js/edit_users.js?v=<?php echo time(); ?>'></script>
<script src='../js/index_.js?v=<?php echo time(); ?>'></script>
</body>
</html>

