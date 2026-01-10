<?php
include '../check_permission.php';
// require_once(__DIR__ . '/../../../../lib/connect.php'); // Include your database connection

// Define the content in 5 languages
$translations = [
    'th' => [
        'page_title' => 'รายการแบนเนอร์',
        'heading_banner_list' => 'รายการแบนเนอร์',
        'add_banner_btn' => 'เพิ่มแบนเนอร์',
        'image_height' => 'ความสูงรูปภาพ: 360px;',
        'image_width' => 'ความกว้างรูปภาพ: 1521px;',
        'col_no' => 'ลำดับ',
        'col_image' => 'รูปภาพ',
        'col_added_date' => 'วันที่เพิ่ม',
        'col_actions' => 'การจัดการ',
        'edit_title' => 'แก้ไข',
        'delete_title' => 'ลบ',
        'confirm_delete_title' => 'คุณแน่ใจหรือไม่?',
        'confirm_delete_text' => 'คุณต้องการลบแบนเนอร์นี้หรือไม่!',
        'confirm_delete_yes' => 'ใช่, ลบเลย!',
        'confirm_delete_cancel' => 'ยกเลิก',
        'delete_success_title' => 'ลบแล้ว!',
        'delete_success_text' => 'แบนเนอร์ถูกลบเรียบร้อยแล้ว.',
        'error_title' => 'เกิดข้อผิดพลาด!',
        'delete_error_text' => 'ไม่สามารถลบแบนเนอร์ได้.',
        'loading' => 'กำลังโหลด...',
    ],
    'en' => [
        'page_title' => 'Banner List',
        'heading_banner_list' => 'Banner List',
        'add_banner_btn' => 'Add Banner',
        'image_height' => 'Image Height: 360px;',
        'image_width' => 'Image Width: 1521px;',
        'col_no' => 'No.',
        'col_image' => 'Image',
        'col_added_date' => 'Added Date',
        'col_actions' => 'Actions',
        'edit_title' => 'Edit',
        'delete_title' => 'Delete',
        'confirm_delete_title' => 'Are you sure?',
        'confirm_delete_text' => 'Do you want to delete this banner!',
        'confirm_delete_yes' => 'Yes, delete it!',
        'confirm_delete_cancel' => 'Cancel',
        'delete_success_title' => 'Deleted!',
        'delete_success_text' => 'The banner has been successfully deleted.',
        'error_title' => 'An error occurred!',
        'delete_error_text' => 'Could not delete the banner.',
        'loading' => 'Loading...',
    ],
    'cn' => [
        'page_title' => '横幅列表',
        'heading_banner_list' => '横幅列表',
        'add_banner_btn' => '添加横幅',
        'image_height' => '图片高度：360px；',
        'image_width' => '图片宽度：1521px；',
        'col_no' => '序号',
        'col_image' => '图片',
        'col_added_date' => '添加日期',
        'col_actions' => '管理',
        'edit_title' => '编辑',
        'delete_title' => '删除',
        'confirm_delete_title' => '您确定吗？',
        'confirm_delete_text' => '您确定要删除此横幅吗？',
        'confirm_delete_yes' => '是的, 删除！',
        'confirm_delete_cancel' => '取消',
        'delete_success_title' => '已删除！',
        'delete_success_text' => '横幅已成功删除。',
        'error_title' => '发生错误！',
        'delete_error_text' => '无法删除横幅。',
        'loading' => '加载中...',
    ],
    'jp' => [
        'page_title' => 'バナーリスト',
        'heading_banner_list' => 'バナーリスト',
        'add_banner_btn' => 'バナーを追加',
        'image_height' => '画像高さ：360px；',
        'image_width' => '画像幅：1521px；',
        'col_no' => '番号',
        'col_image' => '画像',
        'col_added_date' => '追加日',
        'col_actions' => '管理',
        'edit_title' => '編集',
        'delete_title' => '削除',
        'confirm_delete_title' => 'よろしいですか？',
        'confirm_delete_text' => 'このバナーを削除しますか？',
        'confirm_delete_yes' => 'はい、削除します！',
        'confirm_delete_cancel' => 'キャンセル',
        'delete_success_title' => '削除しました！',
        'delete_success_text' => 'バナーは正常に削除されました。',
        'error_title' => 'エラーが発生しました！',
        'delete_error_text' => 'バナーを削除できませんでした。',
        'loading' => '読み込み中...',
    ],
    'kr' => [
        'page_title' => '배너 목록',
        'heading_banner_list' => '배너 목록',
        'add_banner_btn' => '배너 추가',
        'image_height' => '이미지 높이: 360px;',
        'image_width' => '이미지 너비: 1521px;',
        'col_no' => '번호',
        'col_image' => '이미지',
        'col_added_date' => '추가 날짜',
        'col_actions' => '관리',
        'edit_title' => '수정',
        'delete_title' => '삭제',
        'confirm_delete_title' => '확실합니까?',
        'confirm_delete_text' => '이 배너를 삭제하시겠습니까?',
        'confirm_delete_yes' => '네, 삭제합니다!',
        'confirm_delete_cancel' => '취소',
        'delete_success_title' => '삭제됨!',
        'delete_success_text' => '배너가 성공적으로 삭제되었습니다.',
        'error_title' => '오류가 발생했습니다!',
        'delete_error_text' => '배너를 삭제할 수 없습니다.',
        'loading' => '로딩 중...',
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
    <link href="https://cdn.jsdelivr.net/npm/fontawesome5-fullcss@1.1.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.0/css/all.min.css">
    <link href="../../../inc/sweetalert2/css/sweetalert2.min.css" rel="stylesheet">
    <script src="../../../inc/sweetalert2/js/sweetalert2.all.min.js"></script>
    <link href="../../../inc/select2/css/select2.min.css" rel="stylesheet">
    <script src="../../../inc/select2/js/select2.min.js"></script>
    <link href="https://cdn.datatables.net/v/dt/dt-2.1.4/datatables.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/v/dt/dt-2.1.4/datatables.min.js"></script>
    <link href='../css/index_.css?v=<?php echo time(); ?>' rel='stylesheet'>
    <style>
        .btn-circle { border: none; width: 30px; height: 30px; border-radius: 50%; font-size: 14px; display: inline-flex; align-items: center; justify-content: center; }
        .btn-edit { background-color: #FFC107; color: white; }
        .btn-del { background-color: #DC3545; color: white; }
        .banner-img { height: 60px; width: auto; max-width: 150px; object-fit: cover; border: 1px solid #ccc; border-radius: 4px; padding: 2px; }
        .line-ref { font-size: 20px; font-weight: bold; margin-bottom: 15px; border-left: 5px solid #f57c00; padding-left: 10px; color: #333; }
    </style>
</head>
<body>
<?php include '../template/header.php'; ?>

<div class="content-sticky">
    <div class="container-fluid">
        <div class="box-content p-4 bg-light rounded shadow-sm">
            <div class="responsive-grid">
                <div style="margin: 10px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h4 class="line-ref mb-0">
                            <i class="fa-solid fa-image"></i>
                            <?= $text['heading_banner_list'] ?>
                        </h4>
                        <a class="btn btn-primary" href="setup_banner.php">
                            <i class="fa-solid fa-plus"></i> <?= $text['add_banner_btn'] ?>
                        </a>
                    </div>
                    <div style="gap :20px"><h5>
                        <div style="padding-bottom :5px"><?= $text['image_height'] ?></div>
                        <div style="padding-bottom :5px"><?= $text['image_width'] ?></div>
                    </h5></div>
                    <table id="td_list_Banner" class="table table-hover table-striped table-bordered" style="width:100%;">
                        <thead>
                            <tr>
                                <th><?= $text['col_no'] ?></th>
                                <th><?= $text['col_image'] ?></th>
                                <th><?= $text['col_added_date'] ?></th>
                                <th><?= $text['col_actions'] ?></th>
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

<script src='../js/index_.js?v=<?php echo time(); ?>'></script>
<script>
    $(document).ready(function() {
        var bannerTable = $('#td_list_Banner').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "actions/process_banner.php",
                "type": "POST",
                "data": function (d) {
                    d.action = 'getData_banner';
                    d.lang = '<?= $lang ?>'; // Pass the language to the server-side script
                }
            },
            "columns": [
                { "data": null, "orderable": false, "searchable": false, "render": function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }},
                { "data": "image_path", "render": function (data, type, row) {
                    return '<img src="' + data + '" class="banner-img" alt="Banner Image">';
                }},
                { "data": "created_at", "render": function (data, type, row) {
                    if (data) {
                        const date = new Date(data);
                        // Using a simple locale-based date format for multi-language support
                        const formattedDate = date.toLocaleDateString('<?= $lang ?>', { year: 'numeric', month: '2-digit', day: '2-digit' });
                        const formattedTime = date.toLocaleTimeString('<?= $lang ?>', { hour: '2-digit', minute: '2-digit' });
                        return formattedDate + ' ' + formattedTime;
                    }
                    return '';
                }},
                { "data": "id", "orderable": false, "searchable": false, "render": function (data, type, row) {
                    return `
                        <button class="btn btn-circle btn-edit btn-sm me-2" title="<?= $text['edit_title'] ?>" data-id="${data}">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                        <button class="btn btn-circle btn-del btn-sm" title="<?= $text['delete_title'] ?>" data-id="${data}">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                }}
            ],
            "language": {
                // You can add language support for DataTables here
                // For a more robust solution, you can create a separate JSON file for each language
                "lengthMenu": "แสดง _MENU_ รายการต่อหน้า",
                "zeroRecords": "ไม่พบข้อมูล",
                "info": "แสดงหน้า _PAGE_ จาก _PAGES_",
                "infoEmpty": "ไม่มีข้อมูลให้แสดง",
                "infoFiltered": "(กรองจาก _MAX_ รายการทั้งหมด)",
                "search": "ค้นหา:",
                "paginate": {
                    "first": "หน้าแรก",
                    "last": "หน้าสุดท้าย",
                    "next": "ถัดไป",
                    "previous": "ก่อนหน้า"
                },
            }
        });

        // Handle delete button click
        $(document).on('click', '.btn-del', function() {
            var bannerId = $(this).data('id');
            Swal.fire({
                title: '<?= $text['confirm_delete_title'] ?>',
                text: "<?= $text['confirm_delete_text'] ?>",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '<?= $text['confirm_delete_yes'] ?>',
                cancelButtonText: '<?= $text['confirm_delete_cancel'] ?>'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'actions/process_banner.php',
                        type: 'POST',
                        data: {
                            action: 'delbanner',
                            id: bannerId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire(
                                    '<?= $text['delete_success_title'] ?>',
                                    '<?= $text['delete_success_text'] ?>',
                                    'success'
                                ).then(() => {
                                    bannerTable.ajax.reload(null, false);
                                });
                            } else {
                                Swal.fire(
                                    '<?= $text['error_title'] ?>',
                                    response.message,
                                    'error'
                                );
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("Error deleting banner:", error, xhr.responseText);
                            Swal.fire(
                                '<?= $text['error_title'] ?>',
                                '<?= $text['delete_error_text'] ?>',
                                'error'
                            );
                        }
                    });
                }
            });
        });

        // Handle edit button click (redirect to an edit page)
        $(document).on('click', '.btn-edit', function() {
            var bannerId = $(this).data('id');
            window.location.href = 'edit_banner.php?id=' + bannerId;
        });
    });
</script>
</body>
</html>