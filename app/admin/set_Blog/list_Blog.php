<?php
include '../check_permission.php';

// Define the content in 5 languages
$translations = [
    'th' => [
        'page_title' => 'รายการบทความ',
        'heading' => 'รายการบทความ',
        'btn_write_blog' => 'เขียนบทความ',
        'col_no' => 'ลำดับ',
        'col_subject' => 'หัวข้อ',
        'col_date_created' => 'วันที่สร้าง',
        'col_action' => 'การจัดการ',
        'alert_delete_confirm' => 'คุณต้องการลบบทความนี้ใช่หรือไม่?',
        'alert_delete_success' => 'ลบบทความสำเร็จแล้ว!',
        'alert_delete_error' => 'เกิดข้อผิดพลาดในการลบบทความ',
    ],
    'en' => [
        'page_title' => 'Blog List',
        'heading' => 'Blog List',
        'btn_write_blog' => 'Write Blog',
        'col_no' => 'No.',
        'col_subject' => 'Subject',
        'col_date_created' => 'Date created',
        'col_action' => 'Action',
        'alert_delete_confirm' => 'Do you want to delete this blog post?',
        'alert_delete_success' => 'Blog deleted successfully!',
        'alert_delete_error' => 'Error deleting blog post',
    ],
    'cn' => [
        'page_title' => '博客列表',
        'heading' => '博客列表',
        'btn_write_blog' => '撰写博客',
        'col_no' => '序号',
        'col_subject' => '主题',
        'col_date_created' => '创建日期',
        'col_action' => '操作',
        'alert_delete_confirm' => '您确定要删除这篇博客文章吗？',
        'alert_delete_success' => '博客删除成功！',
        'alert_delete_error' => '删除博客时出错',
    ],
    'jp' => [
        'page_title' => 'ブログリスト',
        'heading' => 'ブログリスト',
        'btn_write_blog' => 'ブログを書く',
        'col_no' => '番号',
        'col_subject' => '件名',
        'col_date_created' => '作成日',
        'col_action' => 'アクション',
        'alert_delete_confirm' => 'このブログ投稿を削除しますか？',
        'alert_delete_success' => 'ブログが正常に削除されました！',
        'alert_delete_error' => 'ブログの削除中にエラーが発生しました',
    ],
    'kr' => [
        'page_title' => '블로그 목록',
        'heading' => '블로그 목록',
        'btn_write_blog' => '블로그 쓰기',
        'col_no' => '번호',
        'col_subject' => '제목',
        'col_date_created' => '생성일',
        'col_action' => '작업',
        'alert_delete_confirm' => '이 블로그 게시물을 삭제하시겠습니까?',
        'alert_delete_success' => '블로그가 성공적으로 삭제되었습니다!',
        'alert_delete_error' => '블로그 삭제 중 오류가 발생했습니다.',
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
                                        <?= $text['heading'] ?>
                                    </h4>
                                    <a type="button" class="btn btn-primary" href="<?php echo $base_path_admin . 'set_Blog/setup_Blog.php' ?>">
                                        <i class="fa-solid fa-plus"></i>
                                        <?= $text['btn_write_blog'] ?>
                                    </a>
                                </div>

                                <table id="td_list_blog" class="table table-hover" style="width:100%;">
                                    <thead>
                                        <tr>
                                            <th><?= $text['col_no'] ?></th>
                                            <th><?= $text['col_subject'] ?></th>
                                            <th><?= $text['col_date_created'] ?></th>
                                            <th><?= $text['col_action'] ?></th>
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

    <script>
        const translations = {
            'th': {
                'alert_delete_confirm': '<?= $text['alert_delete_confirm'] ?>',
                'alert_delete_success': '<?= $text['alert_delete_success'] ?>',
                'alert_delete_error': '<?= $text['alert_delete_error'] ?>',
            },
            'en': {
                'alert_delete_confirm': '<?= $translations['en']['alert_delete_confirm'] ?>',
                'alert_delete_success': '<?= $translations['en']['alert_delete_success'] ?>',
                'alert_delete_error': '<?= $translations['en']['alert_delete_error'] ?>',
            },
            'cn': {
                'alert_delete_confirm': '<?= $translations['cn']['alert_delete_confirm'] ?>',
                'alert_delete_success': '<?= $translations['cn']['alert_delete_success'] ?>',
                'alert_delete_error': '<?= $translations['cn']['alert_delete_error'] ?>',
            },
            'jp': {
                'alert_delete_confirm': '<?= $translations['jp']['alert_delete_confirm'] ?>',
                'alert_delete_success': '<?= $translations['jp']['alert_delete_success'] ?>',
                'alert_delete_error': '<?= $translations['jp']['alert_delete_error'] ?>',
            },
            'kr': {
                'alert_delete_confirm': '<?= $translations['kr']['alert_delete_confirm'] ?>',
                'alert_delete_success': '<?= $translations['kr']['alert_delete_success'] ?>',
                'alert_delete_error': '<?= $translations['kr']['alert_delete_error'] ?>',
            },
        };
        const currentLang = '<?= $lang ?>';
    </script>
    <script src='../js/index_.js?v=<?php echo time(); ?>'></script>
    <script src='js/Blog_.js?v=<?php echo time(); ?>'></script>
</body>

</html>