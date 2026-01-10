<?php
include '../check_permission.php';

// Define the content in 5 languages
$translations = [
    'th' => [
        'page_title' => 'ควบคุมสิทธิ์ผู้ใช้งาน',
        'heading_role_permission' => 'สิทธิ์ผู้ใช้งานและสิทธิ์การเข้าถึง',
        'col_role' => 'สิทธิ์ผู้ใช้งาน',
        'col_view' => 'ดู',
        'col_create' => 'สร้าง',
        'col_update' => 'แก้ไข',
        'col_delete' => 'ลบ',
        'col_comment' => 'แสดงความคิดเห็น',
        'heading_role_menu' => 'สิทธิ์ผู้ใช้งานและเมนู',
        'col_menu' => 'เมนู',
        'btn_save' => 'บันทึก',
        'alert_save_success' => 'บันทึกการตั้งค่าเรียบร้อยแล้ว',
        'alert_save_failed' => 'เกิดข้อผิดพลาดในการบันทึก',
    ],
    'en' => [
        'page_title' => 'Role Control',
        'heading_role_permission' => 'Roles and Permissions',
        'col_role' => 'Role',
        'col_view' => 'View',
        'col_create' => 'Create',
        'col_update' => 'Update',
        'col_delete' => 'Delete',
        'col_comment' => 'Comment',
        'heading_role_menu' => 'Roles and Menus',
        'col_menu' => 'Menu',
        'btn_save' => 'Save',
        'alert_save_success' => 'Settings saved successfully!',
        'alert_save_failed' => 'Error saving settings.',
    ],
    'cn' => [
        'page_title' => '角色权限控制',
        'heading_role_permission' => '角色和权限',
        'col_role' => '角色',
        'col_view' => '查看',
        'col_create' => '创建',
        'col_update' => '更新',
        'col_delete' => '删除',
        'col_comment' => '评论',
        'heading_role_menu' => '角色和菜单',
        'col_menu' => '菜单',
        'btn_save' => '保存',
        'alert_save_success' => '设置保存成功！',
        'alert_save_failed' => '保存设置时出错。',
    ],
    'jp' => [
        'page_title' => 'ロール管理',
        'heading_role_permission' => 'ロールと権限',
        'col_role' => 'ロール',
        'col_view' => '閲覧',
        'col_create' => '作成',
        'col_update' => '更新',
        'col_delete' => '削除',
        'col_comment' => 'コメント',
        'heading_role_menu' => 'ロールとメニュー',
        'col_menu' => 'メニュー',
        'btn_save' => '保存',
        'alert_save_success' => '設定が正常に保存されました！',
        'alert_save_failed' => '設定の保存中にエラーが発生しました。',
    ],
    'kr' => [
        'page_title' => '역할 제어',
        'heading_role_permission' => '역할 및 권한',
        'col_role' => '역할',
        'col_view' => '보기',
        'col_create' => '생성',
        'col_update' => '수정',
        'col_delete' => '삭제',
        'col_comment' => '댓글',
        'heading_role_menu' => '역할 및 메뉴',
        'col_menu' => '메뉴',
        'btn_save' => '저장',
        'alert_save_success' => '설정이 성공적으로 저장되었습니다!',
        'alert_save_failed' => '설정 저장 중 오류가 발생했습니다.',
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

        #iconPickerMenu {
            position: absolute;
            right: 0;
            background-color: #fafafa;
            top: 44px;
        }

        .box-icon-picker {
            position: relative;
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
                        <h4 class="line-ref mb-2"><?= $text['heading_role_permission'] ?></h4>
                        <table id="tb_control_permiss" class="table table-bordered" style="width:100%;">
                            <thead>
                                <tr>
                                    <th style="min-width: 280px;"><?= $text['col_role'] ?></th>
                                    <th><?= $text['col_view'] ?></th>
                                    <th><?= $text['col_create'] ?></th>
                                    <th><?= $text['col_update'] ?></th>
                                    <th><?= $text['col_delete'] ?></th>
                                    <th><?= $text['col_comment'] ?></th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        <h4 class="line-ref mb-2"><?= $text['heading_role_menu'] ?></h4>
                        <table id="tb_control_menu" class="table table-bordered" style="width:100%;">
                            <thead>
                                <tr>
                                    <th style="min-width: 280px;"><?= $text['col_menu'] ?></th>
                                    <th>Admin</th>
                                    <th>Editor</th>
                                    <th>Viewer</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                    <div style="text-align: end;">
                        <button type="button" id="saveRoleControl" class="btn btn-primary"><?= $text['btn_save'] ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const translations = {
            'th': {
                'alert_save_success': '<?= $text['alert_save_success'] ?>',
                'alert_save_failed': '<?= $text['alert_save_failed'] ?>',
            },
            'en': {
                'alert_save_success': '<?= $translations['en']['alert_save_success'] ?>',
                'alert_save_failed': '<?= $translations['en']['alert_save_failed'] ?>',
            },
            'cn': {
                'alert_save_success': '<?= $translations['cn']['alert_save_success'] ?>',
                'alert_save_failed': '<?= $translations['cn']['alert_save_failed'] ?>',
            },
            'jp': {
                'alert_save_success': '<?= $translations['jp']['alert_save_success'] ?>',
                'alert_save_failed': '<?= $translations['jp']['alert_save_failed'] ?>',
            },
            'kr': {
                'alert_save_success': '<?= $translations['kr']['alert_save_success'] ?>',
                'alert_save_failed': '<?= $translations['kr']['alert_save_failed'] ?>',
            },
        };
        const currentLang = '<?= $lang ?>';
    </script>

    <script src='../js/index_.js?v=<?php echo time(); ?>'></script>
    <script src='js/role_control_.js?v=<?php echo time(); ?>'></script>

</body>

</html>