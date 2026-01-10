<?php
include '../check_permission.php';

// Define the content in 5 languages
$translations = [
    'th' => [
        'page_title' => 'ตั้งค่าเมนู',
        'heading' => 'ตั้งค่าเมนู',
        'col_no' => 'ลำดับ',
        'col_icon' => 'Image Path', // <-- แก้ไขตรงนี้
        'col_menu_name' => 'ชื่อเมนู',
        'col_main_menu' => 'เมนูหลัก',
        'col_path' => 'เส้นทาง',
        'col_order' => 'ลำดับ',
        'col_actions' => 'จัดการ',
        'btn_add' => 'เพิ่ม',
        'btn_save' => 'บันทึก',
        'btn_edit' => 'แก้ไข',
        'btn_delete' => 'ลบ',
        'confirm_delete' => 'ยืนยันการลบเมนูนี้ใช่หรือไม่?',
        'alert_fill_all_fields' => 'กรุณากรอกข้อมูลให้ครบถ้วน',
        'alert_delete_success' => 'ลบเมนูเรียบร้อยแล้ว',
        'alert_delete_failed' => 'เกิดข้อผิดพลาดในการลบเมนู',
        'alert_add_success' => 'เพิ่มเมนูเรียบร้อยแล้ว',
        'alert_add_failed' => 'เกิดข้อผิดพลาดในการเพิ่มเมนู',
        'alert_update_success' => 'อัปเดตเมนูเรียบร้อยแล้ว',
        'alert_update_failed' => 'เกิดข้อผิดพลาดในการอัปเดตเมนู',
    ],
    'en' => [
        'page_title' => 'Menu Settings',
        'heading' => 'Menu Settings',
        'col_no' => 'No.',
        'col_icon' => 'Image Path', // <-- แก้ไขตรงนี้
        'col_menu_name' => 'Menu Name',
        'col_main_menu' => 'Main Menu',
        'col_path' => 'Path',
        'col_order' => 'Order',
        'col_actions' => 'Actions',
        'btn_add' => 'Add',
        'btn_save' => 'Save',
        'btn_edit' => 'Edit',
        'btn_delete' => 'Delete',
        'confirm_delete' => 'Are you sure you want to delete this menu?',
        'alert_fill_all_fields' => 'Please fill in all fields.',
        'alert_delete_success' => 'Menu deleted successfully.',
        'alert_delete_failed' => 'Error deleting menu.',
        'alert_add_success' => 'Menu added successfully.',
        'alert_add_failed' => 'Error adding menu.',
        'alert_update_success' => 'Menu updated successfully.',
        'alert_update_failed' => 'Error updating menu.',
    ],
    'cn' => [
        'page_title' => '菜单设置',
        'heading' => '菜单设置',
        'col_no' => '序号',
        'col_icon' => '图片路径', // <-- แก้ไขตรงนี้
        'col_menu_name' => '菜单名称',
        'col_main_menu' => '主菜单',
        'col_path' => '路径',
        'col_order' => '顺序',
        'col_actions' => '操作',
        'btn_add' => '添加',
        'btn_save' => '保存',
        'btn_edit' => '编辑',
        'btn_delete' => '删除',
        'confirm_delete' => '您确定要删除此菜单吗？',
        'alert_fill_all_fields' => '请填写所有字段。',
        'alert_delete_success' => '菜单删除成功。',
        'alert_delete_failed' => '删除菜单时出错。',
        'alert_add_success' => '菜单添加成功。',
        'alert_add_failed' => '添加菜单时出错。',
        'alert_update_success' => '菜单更新成功。',
        'alert_update_failed' => '更新菜单时出错。',
    ],
    'jp' => [
        'page_title' => 'メニュー設定',
        'heading' => 'メニュー設定',
        'col_no' => '番号',
        'col_icon' => '画像パス', // <-- แก้ไขตรงนี้
        'col_menu_name' => 'メニュー名',
        'col_main_menu' => 'メインメニュー',
        'col_path' => 'パス',
        'col_order' => '順序',
        'col_actions' => '操作',
        'btn_add' => '追加',
        'btn_save' => '保存',
        'btn_edit' => '編集',
        'btn_delete' => '削除',
        'confirm_delete' => 'このメニューを削除してもよろしいですか？',
        'alert_fill_all_fields' => 'すべての項目を記入してください。',
        'alert_delete_success' => 'メニューが正常に削除されました。',
        'alert_delete_failed' => 'メニューの削除中にエラーが発生しました。',
        'alert_add_success' => 'メニューが正常に追加されました。',
        'alert_add_failed' => 'メニューの追加中にエラーが発生しました。',
        'alert_update_success' => 'メニューが正常に更新されました。',
        'alert_update_failed' => 'メニューの更新中にエラーが発生しました。',
    ],
    'kr' => [
        'page_title' => '메뉴 설정',
        'heading' => '메뉴 설정',
        'col_no' => '번호',
        'col_icon' => '이미지 경로', // <-- แก้ไขตรงนี้
        'col_menu_name' => '메뉴 이름',
        'col_main_menu' => '메인 메뉴',
        'col_path' => '경로',
        'col_order' => '순서',
        'col_actions' => '관리',
        'btn_add' => '추가',
        'btn_save' => '저장',
        'btn_edit' => '수정',
        'btn_delete' => '삭제',
        'confirm_delete' => '이 메뉴를 삭제하시겠습니까?',
        'alert_fill_all_fields' => '모든 필드를 채워주세요.',
        'alert_delete_success' => '메뉴가 성공적으로 삭제되었습니다.',
        'alert_delete_failed' => '메뉴 삭제 중 오류가 발생했습니다.',
        'alert_add_success' => '메뉴가 성공적으로 추가되었습니다.',
        'alert_add_failed' => '메뉴 추가 중 오류가 발생했습니다.',
        'alert_update_success' => '메뉴가 성공적으로 업데이트되었습니다.',
        'alert_update_failed' => '메뉴 업데이트 중 오류가 발생했습니다.',
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
    <link href='../css/index_.css?v=<?php echo time(); ?>' rel='stylesheet'>
    <style>
        .btn-circle {
            border: none;
            width: 30px;
            height: 28px;
            border-radius: 50%;
            font-size: 14px;
        }

        .btn-save {
            background-color: #4CAF50;
            color: #ffffff;
        }

        .btn-edit {
            background-color: #FFC107;
            color: #ffffff;
        }

        .btn-del {
            background-color: #ff4537;
            color: #ffffff;
        }
        
        /* ลบ CSS ที่เกี่ยวข้องกับ Icon Picker */
        /* #iconPickerMenu {
            position: absolute;
            right: 0;
            background-color: #fafafa;
            top: 44px;
            z-index: 99;
        }

        .box-icon-picker {
            position: relative;
        }

        .iconMenu {
            position: absolute;
            top: 30px;
            right: 0;
            background-color: #fafafa;
        } */
        .menu-image-preview {
            max-width: 30px; /* จำกัดขนาดรูปภาพให้พอดีกับตาราง */
            height: auto;
        }
    </style>
</head>

<?php include '../template/header.php' ?>

<body>
    <div class="content-sticky" id="">
        <div class="container-fluid">
            <div class="box-content">
                <div class="row">
                    <div class="">
                        <h4 class="line-ref mb-3"><?= $text['heading'] ?></h4>
                        <table id="tb_list_menu" class="table table-bordered" style="width:100%;">
                            <thead>
                                <tr>
                                    <th><?= $text['col_no'] ?></th>
                                    <th><?= $text['col_icon'] ?></th>
                                    <th><?= $text['col_menu_name'] ?></th>
                                    <th><?= $text['col_main_menu'] ?></th>
                                    <th><?= $text['col_path'] ?></th>
                                    <th><?= $text['col_order'] ?></th>
                                    <th><?= $text['col_actions'] ?></th>
                                </tr>
                                <tr id="add_row">
                                    <th></th>
                                    <th style="max-width: 150px;">
                                        <input type="text" id="set_icon" name="set_icon" class="form-control" value="" placeholder="Image URL">
                                    </th>
                                    <th><input type="text" id="set_menu_name" name="set_menu_name" class="form-control" value=""></th>
                                    <th><select id="set_menu_main" name="set_menu_main" class="form-select"></select></th>
                                    <th><input type="text" id="set_menu_path" name="set_menu_path" class="form-control" value=""></th>
                                    <th></th>
                                    <th>
                                        <div style="display: flex; justify-content: space-between;">
                                            <div>
                                                <button type="button" id="submitAddMenu" class="btn btn-primary">
                                                    <i class="fas fa-plus"></i> <?= $text['btn_add'] ?>
                                                </button>
                                            </div>
                                            </div>
                                    </th>
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

    <script>
        const translations = {
            'th': {
                'btn_save': '<?= $text['btn_save'] ?>',
                'btn_edit': '<?= $text['btn_edit'] ?>',
                'btn_delete': '<?= $text['btn_delete'] ?>',
                'confirm_delete': '<?= $text['confirm_delete'] ?>',
                'alert_fill_all_fields': '<?= $text['alert_fill_all_fields'] ?>',
                'alert_delete_success': '<?= $text['alert_delete_success'] ?>',
                'alert_delete_failed': '<?= $text['alert_delete_failed'] ?>',
                'alert_add_success': '<?= $text['alert_add_success'] ?>',
                'alert_add_failed': '<?= $text['alert_add_failed'] ?>',
                'alert_update_success': '<?= $text['alert_update_success'] ?>',
                'alert_update_failed': '<?= $text['alert_update_failed'] ?>',
            },
            'en': {
                'btn_save': '<?= $translations['en']['btn_save'] ?>',
                'btn_edit': '<?= $translations['en']['btn_edit'] ?>',
                'btn_delete': '<?= $translations['en']['btn_delete'] ?>',
                'confirm_delete': '<?= $translations['en']['confirm_delete'] ?>',
                'alert_fill_all_fields': '<?= $translations['en']['alert_fill_all_fields'] ?>',
                'alert_delete_success': '<?= $translations['en']['alert_delete_success'] ?>',
                'alert_delete_failed': '<?= $translations['en']['alert_delete_failed'] ?>',
                'alert_add_success': '<?= $translations['en']['alert_add_success'] ?>',
                'alert_add_failed': '<?= $translations['en']['alert_add_failed'] ?>',
                'alert_update_success': '<?= $translations['en']['alert_update_success'] ?>',
                'alert_update_failed': '<?= $translations['en']['alert_update_failed'] ?>',
            },
            'cn': {
                'btn_save': '<?= $translations['cn']['btn_save'] ?>',
                'btn_edit': '<?= $translations['cn']['btn_edit'] ?>',
                'btn_delete': '<?= $translations['cn']['btn_delete'] ?>',
                'confirm_delete': '<?= $translations['cn']['confirm_delete'] ?>',
                'alert_fill_all_fields': '<?= $translations['cn']['alert_fill_all_fields'] ?>',
                'alert_delete_success': '<?= $translations['cn']['alert_delete_success'] ?>',
                'alert_delete_failed': '<?= $translations['cn']['alert_delete_failed'] ?>',
                'alert_add_success': '<?= $translations['cn']['alert_add_success'] ?>',
                'alert_add_failed': '<?= $translations['cn']['alert_add_failed'] ?>',
                'alert_update_success': '<?= $translations['cn']['alert_update_success'] ?>',
                'alert_update_failed': '<?= $translations['cn']['alert_update_failed'] ?>',
            },
            'jp': {
                'btn_save': '<?= $translations['jp']['btn_save'] ?>',
                'btn_edit': '<?= $translations['jp']['btn_edit'] ?>',
                'btn_delete': '<?= $translations['jp']['btn_delete'] ?>',
                'confirm_delete': '<?= $translations['jp']['confirm_delete'] ?>',
                'alert_fill_all_fields': '<?= $translations['jp']['alert_fill_all_fields'] ?>',
                'alert_delete_success': '<?= $translations['jp']['alert_delete_success'] ?>',
                'alert_delete_failed': '<?= $translations['jp']['alert_delete_failed'] ?>',
                'alert_add_success': '<?= $translations['jp']['alert_add_success'] ?>',
                'alert_add_failed': '<?= $translations['jp']['alert_add_failed'] ?>',
                'alert_update_success': '<?= $translations['jp']['alert_update_success'] ?>',
                'alert_update_failed': '<?= $translations['jp']['alert_update_failed'] ?>',
            },
            'kr': {
                'btn_save': '<?= $translations['kr']['btn_save'] ?>',
                'btn_edit': '<?= $translations['kr']['btn_edit'] ?>',
                'btn_delete': '<?= $translations['kr']['btn_delete'] ?>',
                'confirm_delete': '<?= $translations['kr']['confirm_delete'] ?>',
                'alert_fill_all_fields': '<?= $translations['kr']['alert_fill_all_fields'] ?>',
                'alert_delete_success': '<?= $translations['kr']['alert_delete_success'] ?>',
                'alert_delete_failed': '<?= $translations['kr']['alert_delete_failed'] ?>',
                'alert_add_success': '<?= $translations['kr']['alert_add_success'] ?>',
                'alert_add_failed': '<?= $translations['kr']['alert_add_failed'] ?>',
                'alert_update_success': '<?= $translations['kr']['alert_update_success'] ?>',
                'alert_update_failed': '<?= $translations['kr']['alert_update_failed'] ?>',
            },
        };
        const currentLang = '<?= $lang ?>';
    </script>

    <script src='../js/index_.js?v=<?php echo time(); ?>'></script>
    <script src='js/list_menu_.js?v=<?php echo time(); ?>'></script>

</body>

</html>