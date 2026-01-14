<?php 
include '../check_permission.php'; 
require_once('../../../lib/connect.php');

// Define the content in 5 languages
$translations = [
    'th' => [
        'page_title' => 'จัดการ Meta Tags',
        'manage_title' => 'จัดการ Meta Tags',
        'page_name' => 'ชื่อหน้า',
        'meta_title' => 'Meta Title',
        'meta_description' => 'Meta Description',
        'meta_keywords' => 'Meta Keywords',
        'og_title' => 'OG Title',
        'og_description' => 'OG Description',
        'og_image' => 'OG Image',
        'save_btn' => 'บันทึก',
        'list_title' => 'รายการ Meta Tags',
        'table_page' => 'หน้า',
        'table_title' => 'หัวข้อ',
        'table_description' => 'รายละเอียด',
        'table_action' => 'การดำเนินการ',
        'edit_btn' => 'แก้ไข',
        'alert_meta_title' => 'กรุณากรอก Meta Title',
    ],
    'en' => [
        'page_title' => 'Manage Meta Tags',
        'manage_title' => 'Manage Meta Tags',
        'page_name' => 'Page Name',
        'meta_title' => 'Meta Title',
        'meta_description' => 'Meta Description',
        'meta_keywords' => 'Meta Keywords',
        'og_title' => 'OG Title',
        'og_description' => 'OG Description',
        'og_image' => 'OG Image',
        'save_btn' => 'Save',
        'list_title' => 'List of Meta Tags',
        'table_page' => 'Page',
        'table_title' => 'Title',
        'table_description' => 'Description',
        'table_action' => 'Action',
        'edit_btn' => 'Edit',
        'alert_meta_title' => 'Please enter a Meta Title',
    ],
    'cn' => [
        'page_title' => '管理元标签',
        'manage_title' => '管理元标签',
        'page_name' => '页面名称',
        'meta_title' => '元标题',
        'meta_description' => '元描述',
        'meta_keywords' => '元关键词',
        'og_title' => 'OG 标题',
        'og_description' => 'OG 描述',
        'og_image' => 'OG 图片',
        'save_btn' => '保存',
        'list_title' => '元标签列表',
        'table_page' => '页面',
        'table_title' => '标题',
        'table_description' => '描述',
        'table_action' => '操作',
        'edit_btn' => '编辑',
        'alert_meta_title' => '请输入元标题',
    ],
    'jp' => [
        'page_title' => 'メタタグの管理',
        'manage_title' => 'メタタグの管理',
        'page_name' => 'ページ名',
        'meta_title' => 'メタタイトル',
        'meta_description' => 'メタディスクリプション',
        'meta_keywords' => 'メタキーワード',
        'og_title' => 'OGタイトル',
        'og_description' => 'OGディスクリプション',
        'og_image' => 'OG画像',
        'save_btn' => '保存',
        'list_title' => 'メタタグリスト',
        'table_page' => 'ページ',
        'table_title' => 'タイトル',
        'table_description' => '説明',
        'table_action' => '操作',
        'edit_btn' => '編集',
        'alert_meta_title' => 'メタタイトルを入力してください',
    ],
    'kr' => [
        'page_title' => '메타 태그 관리',
        'manage_title' => '메타 태그 관리',
        'page_name' => '페이지 이름',
        'meta_title' => '메타 제목',
        'meta_description' => '메타 설명',
        'meta_keywords' => '메타 키워드',
        'og_title' => 'OG 제목',
        'og_description' => 'OG 설명',
        'og_image' => 'OG 이미지',
        'save_btn' => '저장',
        'list_title' => '메타 태그 목록',
        'table_page' => '페이지',
        'table_title' => '제목',
        'table_description' => '설명',
        'table_action' => '작업',
        'edit_btn' => '수정',
        'alert_meta_title' => '메타 제목을 입력하십시오',
    ],
];

// โค้ดสำหรับจัดการภาษาจาก URL parameter หรือ Session
$lang = 'th'; // กำหนดภาษาเริ่มต้นเป็น 'th'
$supportedLangs = ['th', 'en', 'cn', 'jp', 'kr'];

// เช็คจาก URL ก่อน
if (isset($_GET['lang']) && in_array($_GET['lang'], $supportedLangs)) {
    $_SESSION['lang'] = $_GET['lang'];
    $lang = $_GET['lang'];
} else {
    // ถ้าไม่มีใน URL ให้เช็คจาก Session
    if (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $supportedLangs)) {
        $lang = $_SESSION['lang'];
    }
}

$text = $translations[$lang] ?? $translations['th']; // Fallback หากภาษาไม่ถูกต้อง

// ฟังก์ชันสำหรับเพิ่มพารามิเตอร์ภาษาลงใน URL
function addLangToUrl($url, $lang) {
    if (strpos($url, '?') === false) {
        return $url . '?lang=' . $lang;
    } else {
        return $url . '&lang=' . $lang;
    }
}

$meta = [];
if (isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM metatags WHERE id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $meta = $result->fetch_assoc();
}
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
</head>
<?php include '../template/header.php'; ?>
<body>
<div class="content-sticky">
    <div class="container-fluid">
        <div class="box-content">
            <div style="margin: 10px;">
                <h4 class="line-ref mb-3">
                    <i class="fa-solid fa-code"></i>
                    <?= $text['manage_title'] ?>
                </h4>

                <form method="post" action="setup_metatags.php" enctype="multipart/form-data">
                    <?php if (!empty($meta['id'])): ?>
                        <input type="hidden" name="id" value="<?= $meta['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label><?= $text['page_name'] ?></label>
                        <input type="text" name="page_name" class="form-control" value="<?= htmlspecialchars($meta['page_name'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label><?= $text['meta_title'] ?></label>
                        <input type="text" name="meta_title" class="form-control" value="<?= htmlspecialchars($meta['meta_title'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label><?= $text['meta_description'] ?></label>
                        <textarea name="meta_description" class="form-control"><?= htmlspecialchars($meta['meta_description'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label><?= $text['meta_keywords'] ?></label>
                        <textarea name="meta_keywords" class="form-control"><?= htmlspecialchars($meta['meta_keywords'] ?? '') ?></textarea>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label><?= $text['og_title'] ?></label>
                        <input type="text" name="og_title" class="form-control" value="<?= htmlspecialchars($meta['og_title'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label><?= $text['og_description'] ?></label>
                        <textarea name="og_description" class="form-control"><?= htmlspecialchars($meta['og_description'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label><?= $text['og_image'] ?></label>
                        <input type="file" name="og_image" class="form-control">
                        <?php
                        $defaultImage = '../../public/img/q-removebg-preview1.png';
                        $ogImagePath = !empty($meta['og_image']) ? htmlspecialchars($meta['og_image']) : $defaultImage;
                        ?>
                        <img src="<?= $ogImagePath ?>" style="max-height: 100px; max-width: 150px; object-fit: contain; border:1px solid #ccc; padding: 4px;" class="mt-2">
                    </div>

                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-save"></i> <?= $text['save_btn'] ?>
                    </button>
                </form>

                <hr>
                <h5 class="mt-4"><i class="fa fa-list"></i> <?= $text['list_title'] ?></h5>
                <table class="table table-bordered mt-2">
                    <thead>
                        <tr>
                            <th><?= $text['table_page'] ?></th>
                            <th><?= $text['table_title'] ?></th>
                            <th><?= $text['table_description'] ?></th>
                            <th><?= $text['table_action'] ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $conn->query("SELECT * FROM metatags");
                        while ($row = $result->fetch_assoc()):
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($row['page_name']) ?></td>
                                <td><?= htmlspecialchars($row['meta_title']) ?></td>
                                <td><?= htmlspecialchars($row['meta_description']) ?></td>
                                <td>
                                    <a href="<?= addLangToUrl('?id=' . $row['id'], $lang) ?>" class="btn btn-sm btn-warning">
                                        <i class="fa fa-edit"></i> <?= $text['edit_btn'] ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelector('form').addEventListener('submit', function(e) {
    if (document.querySelector('[name=meta_title]').value.trim() === '') {
        alert('<?= $text['alert_meta_title'] ?>');
        e.preventDefault();
    }
});
</script>
<script src='../js/index_.js?v=<?php echo time(); ?>'></script>
<script src='js/banner_.js?v=<?php echo time(); ?>'></script>
</body>
</html>