<?php 
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// *** ตรวจสอบ PATH นี้ให้ถูกต้องที่สุด ***
// สมมติว่า group_management.php อยู่ที่ /admin/set_project/
// check_permission.php อยู่ที่ /admin/check_permission.php
include '../../../lib/connect.php';
include '../../../lib/base_directory.php';
include '../check_permission.php';

// ส่วนที่เพิ่ม: ตรวจสอบและกำหนดภาษาจาก URL หรือ Session
// session_start();
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

// ส่วนที่เพิ่ม: กำหนดข้อความตามแต่ละภาษา
$texts = [
    'page_title' => [
        'th' => 'จัดการหมวดหมู่โครงการ',
        'en' => 'project Category Management',
        'cn' => '产品分类管理',
        'jp' => '商品カテゴリー管理',
        'kr' => '제품 카테고리 관리'
    ],
    'manage_categories' => [
        'th' => 'จัดการหมวดหมู่โครงการ',
        'en' => 'Manage project Categories',
        'cn' => '管理产品分类',
        'jp' => '商品カテゴリーを管理',
        'kr' => '제품 카테고리 관리'
    ],
    'add_category' => [
        'th' => 'เพิ่มหมวดหมู่',
        'en' => 'Add Category',
        'cn' => '添加分类',
        'jp' => 'カテゴリーを追加',
        'kr' => '카테고리 추가'
    ],
    'table_id' => [
        'th' => 'ID',
        'en' => 'ID',
        'cn' => 'ID',
        'jp' => 'ID',
        'kr' => 'ID'
    ],
    'table_image' => [
        'th' => 'รูปภาพ',
        'en' => 'Image',
        'cn' => '图片',
        'jp' => '画像',
        'kr' => '이미지'
    ],
    'table_category_name' => [
        'th' => 'ชื่อหมวดหมู่',
        'en' => 'Category Name',
        'cn' => '分类名称',
        'jp' => 'カテゴリー名',
        'kr' => '카테고리명'
    ],
    'table_parent_category' => [
        'th' => 'หมวดหมู่หลัก',
        'en' => 'Parent Category',
        'cn' => '主分类',
        'jp' => '親カテゴリー',
        'kr' => '상위 카테고리'
    ],
    'table_actions' => [
        'th' => 'การจัดการ',
        'en' => 'Actions',
        'cn' => '操作',
        'jp' => 'アクション',
        'kr' => '작업'
    ],
    'main_category_label' => [
        'th' => '- (หมวดหมู่หลัก)',
        'en' => '- (Main Category)',
        'cn' => '- (主分类)',
        'jp' => '- (親カテゴリー)',
        'kr' => '- (상위 카테고리)'
    ],
    'not_found' => [
        'th' => 'ไม่พบ',
        'en' => 'Not found',
        'cn' => '未找到',
        'jp' => '見つかりません',
        'kr' => '찾을 수 없음'
    ],
    'edit_button' => [
        'th' => 'แก้ไข',
        'en' => 'Edit',
        'cn' => '编辑',
        'jp' => '編集',
        'kr' => '수정'
    ],
    'delete_button' => [
        'th' => 'ลบ',
        'en' => 'Delete',
        'cn' => '删除',
        'jp' => '削除',
        'kr' => '삭제'
    ],
    'add_modal_title' => [
        'th' => 'เพิ่มหมวดหมู่ใหม่',
        'en' => 'Add New Category',
        'cn' => '添加新分类',
        'jp' => '新しいカテゴリーを追加',
        'kr' => '새 카테고리 추가'
    ],
    'edit_modal_title' => [
        'th' => 'แก้ไขหมวดหมู่',
        'en' => 'Edit Category',
        'cn' => '编辑分类',
        'jp' => 'カテゴリーを編集',
        'kr' => '카테고리 수정'
    ],
    'name_label' => [
        'th' => 'ชื่อหมวดหมู่',
        'en' => 'Category Name',
        'cn' => '分类名称',
        'jp' => 'カテゴリー名',
        'kr' => '카테고리명'
    ],
    'description_label' => [
        'th' => 'คำอธิบาย',
        'en' => 'Description',
        'cn' => '描述',
        'jp' => '説明',
        'kr' => '설명'
    ],
    'parent_group_label' => [
        'th' => 'หมวดหมู่หลัก (ถ้ามี)',
        'en' => 'Parent Category (optional)',
        'cn' => '主分类 (可选)',
        'jp' => '親カテゴリー (オプション)',
        'kr' => '상위 카테고리 (선택 사항)'
    ],
    'select_parent_group' => [
        'th' => '- เลือกหมวดหมู่หลัก -',
        'en' => '- Select Parent Category -',
        'cn' => '- 选择主分类 -',
        'jp' => '- 親カテゴリーを選択 -',
        'kr' => '- 상위 카테고리 선택 -'
    ],
    'image_label' => [
        'th' => 'รูปภาพหมวดหมู่ (สำหรับกลุ่มหลักเท่านั้น)',
        'en' => 'Category Image (for main groups only)',
        'cn' => '分类图片 (仅限主分类)',
        'jp' => 'カテゴリー画像 (親グループのみ)',
        'kr' => '카테고리 이미지 (상위 그룹만)'
    ],
    'file_size_info' => [
        'th' => 'ขนาดไฟล์ไม่เกิน 500kB (JPG, JPEG, PNG, GIF)',
        'en' => 'File size up to 500kB (JPG, JPEG, PNG, GIF)',
        'cn' => '文件大小不超过500kB (JPG, JPEG, PNG, GIF)',
        'jp' => 'ファイルサイズは500kBまで (JPG, JPEG, PNG, GIF)',
        'kr' => '파일 크기 500kB 이하 (JPG, JPEG, PNG, GIF)'
    ],
    'image_placeholder' => [
        'th' => 'ปล่อยว่างหากไม่ต้องการเปลี่ยนรูปภาพ. ขนาดไฟล์ไม่เกิน 500kB (JPG, JPEG, PNG, GIF)',
        'en' => 'Leave blank to keep the current image. File size up to 500kB (JPG, JPEG, PNG, GIF)',
        'cn' => '留空以保留当前图片。文件大小不超过500kB (JPG, JPEG, PNG, GIF)',
        'jp' => '画像を保持する場合は空白のままにしてください。ファイルサイズは500kBまで (JPG, JPEG, PNG, GIF)',
        'kr' => '현재 이미지를 유지하려면 비워두십시오. 파일 크기 500kB 이하 (JPG, JPEG, PNG, GIF)'
    ],
    'cancel_button' => [
        'th' => 'ยกเลิก',
        'en' => 'Cancel',
        'cn' => '取消',
        'jp' => 'キャンセル',
        'kr' => '취소'
    ],
    'save_button' => [
        'th' => 'บันทึก',
        'en' => 'Save',
        'cn' => '保存',
        'jp' => '保存',
        'kr' => '저장'
    ],
    'save_edit_button' => [
        'th' => 'บันทึกการแก้ไข',
        'en' => 'Save Changes',
        'cn' => '保存修改',
        'jp' => '変更を保存',
        'kr' => '수정 사항 저장'
    ]
];

// ฟังก์ชันสำหรับเรียกใช้ข้อความตามภาษาที่เลือก
function getTextByLang($key) {
    global $texts, $lang;
    return $texts[$key][$lang] ?? $texts[$key]['th'];
}
// require_once '../../../inc/connect_db.php'; // เชื่อมต่อฐานข้อมูล


// ตรวจสอบว่าเชื่อมต่อฐานข้อมูลได้หรือไม่ (ถ้า connect_db.php ไม่ได้ die() เมื่อ error)
if (!isset($conn) || !$conn) {
    die("Connection failed: Database connection not established."); // แสดงข้อผิดพลาดร้ายแรงถ้าเชื่อมต่อไม่ได้
}

// กำหนด base URL ของเว็บของคุณ (สำคัญมากสำหรับการแสดงรูปภาพ)
$base_url = 'http://localhost/origami_website/perfume/';
$lang = 'th'; // สมมติว่ามีการกำหนด $lang มาจากส่วนอื่น

// ดึงข้อมูลกลุ่มทั้งหมด
$groups = [];
$sql_groups = "SELECT group_id, group_name, group_name_en, group_name_cn, group_name_jp, group_name_kr, description, description_en, description_cn, description_jp, description_kr, image_path, sort_order FROM dn_project_groups WHERE del = '0' ORDER BY sort_order ASC";
$result_groups = $conn->query($sql_groups);

if ($result_groups) {
    while ($row = $result_groups->fetch_assoc()) {
        $row['full_image_url_display'] = !empty($row['image_path']) ? htmlspecialchars($row['image_path']) : $base_url . 'public/img/group_placeholder.jpg';
        $row['image_path_for_js'] = !empty($row['image_path']) ? htmlspecialchars($row['image_path'], ENT_QUOTES) : '';
        
        $groups[] = $row;
    }
} else {
    echo "Error fetching groups: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getTextByLang('page_title') ?></title>
    <link rel="icon" type="image/x-icon" href="../../../public/img/q-removebg-preview1.png">
    <link href="../../../inc/jquery/css/jquery-ui.css" rel="stylesheet">
    <script src="../../../inc/jquery/js/jquery-3.6.0.min.js"></script>
    <script src="../../../inc/jquery/js/jquery-ui.min.js"></script>
    <link href="../../../inc/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="../../../inc/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/fontawesome5-fullcss@1.10.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.0/css/all.min.css" integrity="sha512-9xKTRVabjVeZmc+GUW8GgSmcREDunMM+Dt/GrzchfN8tkwHizc5RP4Ok/MXFFy5rIjJjzhndFScTceq5e6GvVQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
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
        .box-content img {
    width: 40px;
    height: auto;
    border-radius: 8px;
}
        .group-image-preview {
            width: 20px;
            height: 20px;
            object-fit: cover;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.5em 1em;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background-color: #007bff;
            color: white !important;
            border-color: #007bff;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background-color: #e9ecef;
            border-color: #e9ecef;
        }
        .language-switcher {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 20px;
        }
        .language-switcher img {
            width: 30px;
            height: auto;
            cursor: pointer;
            border: 2px solid transparent;
            transition: border-color 0.2s ease-in-out;
        }
        .language-switcher img.active {
            border-color: #007bff;
        }
        .lang-thai-fields, .lang-en-fields, .lang-cn-fields, .lang-jp-fields, .lang-kr-fields {
            display: none;
        }
        /* Style สำหรับ Select2 ใน Modal จัดการโปรเจกต์ */
        #manageProjectsForm .select2-container {
            width: 100% !important;
        }
        .project-select-container {
            margin-top: 15px;
        }
    </style>
</head>
<?php include '../template/header.php' ?>
<body>
    <div class="content-sticky">
        <div class="container-fluid">
            <div class="box-content">
                <div class="row">
                    <div class="col-12">
                        <div style="margin: 10px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h4 class="line-ref">
            <i class="fas fa-layer-group"></i> <?= getTextByLang('manage_categories') ?>
        </h4>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addGroupModal">
            <i class="fa-solid fa-plus"></i> <?= getTextByLang('add_category') ?>
        </button>
    </div>
    <table id="groupsTable" class="table table-hover" style="width:100%;">
        <thead>
            <tr>
                <th style="width: 50px;"><i class="fas fa-arrows-alt-v"></i></th> <th><?= getTextByLang('table_id') ?></th>
                <th><?= getTextByLang('table_image') ?></th>
                <th><?= getTextByLang('table_category_name') ?> (TH)</th>
                <th><?= getTextByLang('table_category_name') ?> (EN)</th>
                <th><?= getTextByLang('table_category_name') ?> (CN)</th>
                <th><?= getTextByLang('table_category_name') ?> (JP)</th>
                <th><?= getTextByLang('table_category_name') ?> (KR)</th>
                <th style="width: 200px;"><?= getTextByLang('table_actions') ?></th>
            </tr>
        </thead>
        <tbody id="sortableGroups"> <?php
            foreach ($groups as $group) {
                // เพิ่ม data-id เพื่อใช้ในการอ้างอิง group_id ใน JavaScript
                echo '<tr data-id="' . htmlspecialchars($group['group_id']) . '">'; 
                // คอลัมน์สำหรับ Handle การลาก
                echo '<td class="sort-handle" style="cursor: move;"><i class="fas fa-grip-vertical"></i></td>';
                echo '<td>' . htmlspecialchars($group['group_id']) . '</td>';
                echo '<td>';
                echo '<img src="' . $group['full_image_url_display'] . '" class="group-image-preview" alt="Group Image">';
                echo '</td>';
                echo '<td>' . htmlspecialchars($group['group_name']) . '</td>';
                echo '<td>' . htmlspecialchars($group['group_name_en']) . '</td>';
                echo '<td>' . htmlspecialchars($group['group_name_cn']) . '</td>';
                echo '<td>' . htmlspecialchars($group['group_name_jp']) . '</td>';
                echo '<td>' . htmlspecialchars($group['group_name_kr']) . '</td>';
                echo '<td>';
                // ... ปุ่มต่างๆ เหมือนเดิม
                echo '<button class="btn btn-sm btn-info me-2" onclick="myApp_manageProjects(' . $group['group_id'] . ', \'' . htmlspecialchars($group['group_name'], ENT_QUOTES) . '\')"><i class="fas fa-folder-open"></i> ' . 'จัดการโปรเจกต์' . '</button>';
                echo '<button class="btn btn-sm btn-edit me-2" onclick="myApp_editGroup(' . $group['group_id'] . ', \'' . htmlspecialchars($group['group_name'], ENT_QUOTES) . '\', \'' . htmlspecialchars($group['group_name_en'], ENT_QUOTES) . '\', \'' . htmlspecialchars($group['group_name_cn'], ENT_QUOTES) . '\', \'' . htmlspecialchars($group['group_name_jp'], ENT_QUOTES) . '\', \'' . htmlspecialchars($group['group_name_kr'], ENT_QUOTES) . '\', \'' . htmlspecialchars($group['description'], ENT_QUOTES) . '\', \'' . htmlspecialchars($group['description_en'], ENT_QUOTES) . '\', \'' . htmlspecialchars($group['description_cn'], ENT_QUOTES) . '\', \'' . htmlspecialchars($group['description_jp'], ENT_QUOTES) . '\', \'' . htmlspecialchars($group['description_kr'], ENT_QUOTES) . '\', \'' . $group['image_path_for_js'] . '\')"><i class="fas fa-edit"></i> ' . getTextByLang('edit_button') . '</button>';
                echo '<button class="btn btn-sm btn-del" onclick="myApp_deleteGroup(' . $group['group_id'] . ')"><i class="fas fa-trash-alt"></i> ' . getTextByLang('delete_button') . '</button>';
                echo '</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>
</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addGroupModal" tabindex="-1" aria-labelledby="addGroupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addGroupForm" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addGroupModalLabel"><?= getTextByLang('add_modal_title') ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="language-switcher mb-3">
                            <img src="https://flagcdn.com/w320/th.png" alt="Thai" class="lang-flag active" data-lang="th">
                            <img src="https://flagcdn.com/w320/gb.png" alt="English" class="lang-flag" data-lang="en">
                            <img src="https://flagcdn.com/w320/cn.png" alt="Chinese" class="lang-flag" data-lang="cn">
                            <img src="https://flagcdn.com/w320/jp.png" alt="Japanese" class="lang-flag" data-lang="jp">
                            <img src="https://flagcdn.com/w320/kr.png" alt="Korean" class="lang-flag" data-lang="kr">
                        </div>
                        <div class="lang-fields-container">
                            <div class="lang-thai-fields" style="display:block;">
                                <div class="mb-3">
                                    <label for="newGroupName" class="form-label"><?= getTextByLang('name_label') ?> (TH)</label>
                                    <input type="text" class="form-control" id="newGroupName" name="group_name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="newGroupDescription" class="form-label"><?= getTextByLang('description_label') ?> (TH)</label>
                                    <textarea class="form-control" id="newGroupDescription" name="description" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="lang-en-fields">
                                <div class="mb-3">
                                    <label for="newGroupNameEn" class="form-label"><?= getTextByLang('name_label') ?> (EN)</label>
                                    <input type="text" class="form-control" id="newGroupNameEn" name="group_name_en">
                                </div>
                                <div class="mb-3">
                                    <label for="newGroupDescriptionEn" class="form-label"><?= getTextByLang('description_label') ?> (EN)</label>
                                    <textarea class="form-control" id="newGroupDescriptionEn" name="description_en" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="lang-cn-fields">
                                <div class="mb-3">
                                    <label for="newGroupNameCn" class="form-label"><?= getTextByLang('name_label') ?> (CN)</label>
                                    <input type="text" class="form-control" id="newGroupNameCn" name="group_name_cn">
                                </div>
                                <div class="mb-3">
                                    <label for="newGroupDescriptionCn" class="form-label"><?= getTextByLang('description_label') ?> (CN)</label>
                                    <textarea class="form-control" id="newGroupDescriptionCn" name="description_cn" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="lang-jp-fields">
                                <div class="mb-3">
                                    <label for="newGroupNameJp" class="form-label"><?= getTextByLang('name_label') ?> (JP)</label>
                                    <input type="text" class="form-control" id="newGroupNameJp" name="group_name_jp">
                                </div>
                                <div class="mb-3">
                                    <label for="newGroupDescriptionJp" class="form-label"><?= getTextByLang('description_label') ?> (JP)</label>
                                    <textarea class="form-control" id="newGroupDescriptionJp" name="description_jp" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="lang-kr-fields">
                                <div class="mb-3">
                                    <label for="newGroupNameKr" class="form-label"><?= getTextByLang('name_label') ?> (KR)</label>
                                    <input type="text" class="form-control" id="newGroupNameKr" name="group_name_kr">
                                </div>
                                <div class="mb-3">
                                    <label for="newGroupDescriptionKr" class="form-label"><?= getTextByLang('description_label') ?> (KR)</label>
                                    <textarea class="form-control" id="newGroupDescriptionKr" name="description_kr" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="newGroupImage" class="form-label"><?= getTextByLang('image_label') ?></label>
                            <input type="file" class="form-control" id="newGroupImage" name="group_image" accept="image/*">
                            <img id="newGroupImagePreview" src="#" alt="Image Preview" style="display:none; max-width: 150px; margin-top: 10px;">
                            <small class="text-muted"><?= getTextByLang('file_size_info') ?></small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= getTextByLang('cancel_button') ?></button>
                        <button type="submit" class="btn btn-primary"><?= getTextByLang('save_button') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editGroupModal" tabindex="-1" aria-labelledby="editGroupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editGroupForm" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editGroupModalLabel"><?= getTextByLang('edit_modal_title') ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="editGroupId" name="group_id">
                        <div class="language-switcher mb-3">
                            <img src="https://flagcdn.com/w320/th.png" alt="Thai" class="lang-flag active" data-lang="th">
                            <img src="https://flagcdn.com/w320/gb.png" alt="English" class="lang-flag" data-lang="en">
                            <img src="https://flagcdn.com/w320/cn.png" alt="Chinese" class="lang-flag" data-lang="cn">
                            <img src="https://flagcdn.com/w320/jp.png" alt="Japanese" class="lang-flag" data-lang="jp">
                            <img src="https://flagcdn.com/w320/kr.png" alt="Korean" class="lang-flag" data-lang="kr">
                        </div>
                        <div class="lang-fields-container">
                            <div class="lang-thai-fields" style="display:block;">
                                <div class="mb-3">
                                    <label for="editGroupName" class="form-label"><?= getTextByLang('name_label') ?> (TH)</label>
                                    <input type="text" class="form-control" id="editGroupName" name="group_name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="editGroupDescription" class="form-label"><?= getTextByLang('description_label') ?> (TH)</label>
                                    <textarea class="form-control" id="editGroupDescription" name="description" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="lang-en-fields">
                                <div class="mb-3">
                                    <label for="editGroupNameEn" class="form-label"><?= getTextByLang('name_label') ?> (EN)</label>
                                    <input type="text" class="form-control" id="editGroupNameEn" name="group_name_en">
                                </div>
                                <div class="mb-3">
                                    <label for="editGroupDescriptionEn" class="form-label"><?= getTextByLang('description_label') ?> (EN)</label>
                                    <textarea class="form-control" id="editGroupDescriptionEn" name="description_en" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="lang-cn-fields">
                                <div class="mb-3">
                                    <label for="editGroupNameCn" class="form-label"><?= getTextByLang('name_label') ?> (CN)</label>
                                    <input type="text" class="form-control" id="editGroupNameCn" name="group_name_cn">
                                </div>
                                <div class="mb-3">
                                    <label for="editGroupDescriptionCn" class="form-label"><?= getTextByLang('description_label') ?> (CN)</label>
                                    <textarea class="form-control" id="editGroupDescriptionCn" name="description_cn" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="lang-jp-fields">
                                <div class="mb-3">
                                    <label for="editGroupNameJp" class="form-label"><?= getTextByLang('name_label') ?> (JP)</label>
                                    <input type="text" class="form-control" id="editGroupNameJp" name="group_name_jp">
                                </div>
                                <div class="mb-3">
                                    <label for="editGroupDescriptionJp" class="form-label"><?= getTextByLang('description_label') ?> (JP)</label>
                                    <textarea class="form-control" id="editGroupDescriptionJp" name="description_jp" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="lang-kr-fields">
                                <div class="mb-3">
                                    <label for="editGroupNameKr" class="form-label"><?= getTextByLang('name_label') ?> (KR)</label>
                                    <input type="text" class="form-control" id="editGroupNameKr" name="group_name_kr">
                                </div>
                                <div class="mb-3">
                                    <label for="editGroupDescriptionKr" class="form-label"><?= getTextByLang('description_label') ?> (KR)</label>
                                    <textarea class="form-control" id="editGroupDescriptionKr" name="description_kr" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3" id="editImageContainer">
                            <label for="editGroupImage" class="form-label"><?= getTextByLang('image_label') ?></label>
                            <input type="file" class="form-control" id="editGroupImage" name="group_image" accept="image/*">
                            <img id="editGroupImagePreview" src="#" alt="Image Preview" style="max-width: 150px; margin-top: 10px; display: none;">
                            <p class="text-muted mt-2"><?= getTextByLang('image_placeholder') ?></p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= getTextByLang('cancel_button') ?></button>
                        <button type="submit" class="btn btn-primary"><?= getTextByLang('save_edit_button') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="manageProjectsModal" tabindex="-1" aria-labelledby="manageProjectsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="manageProjectsForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="manageProjectsModalLabel"><i class="fas fa-folder-open"></i> จัดการโปรเจกต์ของกลุ่ม: <span id="currentGroupName"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="manageProjectsGroupId" name="group_id">
                        
                        <div class="project-select-container mb-4">
                            <h6>เพิ่มโปรเจกต์ใหม่เข้ากลุ่มนี้</h6>
                            <select class="form-control project-selector" id="addProjectSelector" name="project_id" style="width: 100%;">
                                <option value="" selected disabled>ค้นหาและเลือกโปรเจกต์...</option>
                            </select>
                            <button type="button" class="btn btn-success btn-sm mt-2" id="addProjectToGroupBtn"><i class="fas fa-plus"></i> จัดเข้ากลุ่มนี้</button>
                        </div>

                        <hr>

                        <h6>โปรเจกต์ในกลุ่มนี้</h6>
                        <table class="table table-bordered table-striped" id="projectsInGroupTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ชื่อโปรเจกต์ (TH)</th>
                                    <th>สถานะในกลุ่ม</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                </tbody>
                        </table>
                        
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">สำเร็จ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script src='../js/index_.js?v=<?php echo time(); ?>'></script>
    <script>
    $(document).ready(function() {
        // DataTables Initialization
        // $('#groupsTable').DataTable({
        //     "language": {
        //         // หาก URL นี้ไม่สามารถโหลดได้ ให้ลองเปลี่ยนเป็น https:// หรือโหลดไฟล์มาไว้ในเครื่อง
        //         "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/th.json"
        //     }
        // });
        // *** การใช้งาน jQuery UI Sortable เพื่อเรียงลำดับกลุ่ม ***
    $("#groupsTable tbody").sortable({
        items: "tr",
        cursor: "move",
        handle: ".sort-handle", // กำหนดให้ลากได้เฉพาะตรง icon handle ที่เพิ่มเข้ามา
        opacity: 0.8,
        update: function(event, ui) {
            updateGroupOrder(); // เรียกใช้ฟังก์ชันเมื่อมีการเปลี่ยนลำดับ
        }
    }).disableSelection();

    // 2. ฟังก์ชันส่งค่าลำดับไปยัง Server
    function updateGroupOrder() {
        var groupOrder = [];
        $("#groupsTable tbody tr").each(function(index) {
            groupOrder.push({
                group_id: $(this).data("id"),
                sort_order: index + 1
            });
        });

        $.ajax({
            url: 'group_actions.php',
            type: 'POST',
            data: {
                action: 'update_group_order',
                group_order: groupOrder
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // แจ้งเตือนความสำเร็จแบบ Toast ไม่ต้อง reload หน้า
                    Swal.fire({
                        icon: 'success',
                        title: response.message,
                        showConfirmButton: false,
                        timer: 1000,
                        toast: true,
                        position: 'top-end'
                    });
                } else {
                    Swal.fire('เกิดข้อผิดพลาด!', response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                Swal.fire('เกิดข้อผิดพลาด!', 'ไม่สามารถบันทึกลำดับได้. โปรดตรวจสอบ Network tab.', 'error');
            }
        });
    }

        // Language Switcher Logic (ไม่มีการเปลี่ยนแปลง)
        $('.lang-flag').on('click', function() {
            $('.lang-flag').removeClass('active');
            $(this).addClass('active');
            var lang = $(this).data('lang');
            
            $('.lang-fields-container').find('> div').hide();
            $('.lang-' + lang + '-fields').show();
        });

        // Add Group Form Submission (ไม่มีการเปลี่ยนแปลง)
        $('#addGroupForm').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            formData.append('action', 'add_group');

            $.ajax({
                url: 'group_actions.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    Swal.fire({
                        icon: response.status,
                        title: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        if (response.status === 'success') {
                            $('#addGroupModal').modal('hide');
                            location.reload();
                        }
                    });
                },
                error: function(xhr, status, error) {
                    Swal.fire('เกิดข้อผิดพลาด!', 'ไม่สามารถเพิ่มหมวดหมู่ได้. โปรดตรวจสอบ Console และ Network tab สำหรับรายละเอียด.', 'error');
                }
            });
        });

        // Edit Group Form Submission (ไม่มีการเปลี่ยนแปลง)
        $('#editGroupForm').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            formData.append('action', 'edit_group');

            $.ajax({
                url: 'group_actions.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    Swal.fire({
                        icon: response.status,
                        title: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        if (response.status === 'success') {
                            $('#editGroupModal').modal('hide');
                            location.reload();
                        }
                    });
                },
                error: function(xhr, status, error) {
                    Swal.fire('เกิดข้อผิดพลาด!', 'ไม่สามารถแก้ไขหมวดหมู่ได้. โปรดตรวจสอบ Console และ Network tab สำหรับรายละเอียด.', 'error');
                }
            });
        });

        // Select2 initialization สำหรับ Manage Projects Modal
        $('#manageProjectsModal').on('shown.bs.modal', function () {
            if ($('#addProjectSelector').data('select2')) {
                $('#addProjectSelector').select2('destroy');
            }
            
            $('#addProjectSelector').select2({
                dropdownParent: $('#manageProjectsModal'),
                placeholder: 'เลือกโปรเจกต์ที่ต้องการเพิ่ม',
                allowClear: true,
                ajax: {
                    url: 'group_project_handler.php',
                    dataType: 'json',
                    data: function(params) {
                        return { 
                            action: 'get_all_projects_to_add',
                            group_id: $('#manageProjectsGroupId').val(),
                            term: params.term || '' // เพิ่ม term สำหรับค้นหา
                        };
                    },
                    processResults: function(data) {
                        return { results: data.results };
                    }
                },
                minimumInputLength: 0 // สำคัญ! เพื่อให้โหลดทันทีเมื่อไม่มีการค้นหา
            });

            // ไม่จำเป็นต้องเรียก open() เพราะ minimumInputLength: 0 จะจัดการให้
            // $('#addProjectSelector').select2('open'); 
        });

        // Button click: เพิ่ม Project เข้า Group (แก้ไข: ใช้ตารางเชื่อมโยง)
        $('#addProjectToGroupBtn').on('click', function() {
            const projectId = $('#addProjectSelector').val();
            const groupId = $('#manageProjectsGroupId').val();

            if (projectId && groupId) {
                $.ajax({
                    url: 'group_project_handler.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'add_project_to_group',
                        group_id: groupId,
                        project_id: projectId
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: response.status,
                            title: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            if (response.status === 'success') {
                                loadProjectsInGroup(groupId);
                                $('#addProjectSelector').val(null).trigger('change'); 
                            }
                        });
                    },
                    error: function() {
                        Swal.fire('เกิดข้อผิดพลาด!', 'ไม่สามารถเพิ่มโปรเจกต์เข้ากลุ่มได้.', 'error');
                    }
                });
            } else {
                Swal.fire('แจ้งเตือน', 'กรุณาเลือกโปรเจกต์ก่อน.', 'warning');
            }
        });

        // Event delegation สำหรับปุ่ม "นำออกจากกลุ่ม" (แก้ไข: ใช้ตารางเชื่อมโยง)
        $('#projectsInGroupTable').on('click', '.btn-remove-project', function(e) {
            // **การแก้ไขปัญหา Modal ปิดคือตรงนี้**
            // ป้องกันไม่ให้ Event ดั้งเดิมของปุ่มทำงาน (ซึ่งอาจทำให้ Modal ปิด)
            e.preventDefault(); 
            e.stopPropagation(); // อาจช่วยได้ในบางกรณีที่มี Event ซ้อนทับกัน

            const projectId = $(this).data('project-id');
            const groupId = $('#manageProjectsGroupId').val();

            Swal.fire({
                title: 'ยืนยัน',
                text: "คุณต้องการนำโปรเจกต์นี้ออกจากกลุ่มหรือไม่?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'ใช่, นำออก!',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'group_project_handler.php',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'remove_project_from_group',
                            group_id: groupId,
                            project_id: projectId
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: response.status,
                                title: response.message,
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                if (response.status === 'success') {
                                    // โหลดรายการโปรเจกต์ใหม่ในตาราง
                                    loadProjectsInGroup(groupId);
                                }
                            });
                        },
                        error: function() {
                            Swal.fire('เกิดข้อผิดพลาด!', 'ไม่สามารถนำโปรเจกต์ออกจากกลุ่มได้.', 'error');
                        }
                    });
                }
            });
        });


    });

    // Function to load projects in the group (แก้ไข: ใช้ตารางเชื่อมโยง)
    function loadProjectsInGroup(groupId) {
        $.ajax({
            url: 'group_project_handler.php',
            type: 'GET',
            dataType: 'json',
            data: {
                action: 'get_projects_in_group',
                group_id: groupId
            },
            success: function(data) {
                const tbody = $('#projectsInGroupTable tbody');
                tbody.empty();
                if (data.length > 0) {
                    data.forEach(function(project) {
                        const statusText = 'จัดอยู่ในกลุ่มนี้';
                        const statusColor = 'badge bg-success';

                        const row = `
                            <tr>
                                <td>${project.project_id}</td>
                                <td>${project.subject_project}</td>
                                <td><span class="${statusColor}">${statusText}</span></td>
                                <td>
                                    <button class="btn btn-sm btn-danger btn-remove-project" data-project-id="${project.project_id}" type="button">
                                        <i class="fas fa-minus-circle"></i> นำออกจากกลุ่ม
                                    </button>
                                </td>
                            </tr>
                        `;
                        tbody.append(row);
                    });
                } else {
                    tbody.append('<tr><td colspan="4" class="text-center">ไม่พบโปรเจกต์ในกลุ่มนี้.</td></tr>');
                }
            },
            error: function() {
                $('#projectsInGroupTable tbody').html('<tr><td colspan="4" class="text-center text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูลโปรเจกต์.</td></tr>');
            }
        });
    }
    
    // Function to handle Edit Group button click (ไม่มีการเปลี่ยนแปลง)
    function myApp_editGroup(id, name, name_en, name_cn, name_jp, name_kr, desc, desc_en, desc_cn, desc_jp, desc_kr, image_path) {
        $('#editGroupId').val(id);
        $('#editGroupName').val(name);
        $('#editGroupDescription').val(desc);
        // ... set other language fields ...
        $('#editGroupNameEn').val(name_en);
        $('#editGroupDescriptionEn').val(desc_en);
        $('#editGroupNameCn').val(name_cn);
        $('#editGroupDescriptionCn').val(desc_cn);
        $('#editGroupNameJp').val(name_jp);
        $('#editGroupDescriptionJp').val(desc_jp);
        $('#editGroupNameKr').val(name_kr);
        $('#editGroupDescriptionKr').val(desc_kr);

        var preview = $('#editGroupImagePreview');
        var placeholder = $('#editImageContainer p.text-muted');

        if (image_path) {
            preview.attr('src', image_path).show();
            placeholder.text("รูปภาพปัจจุบัน: " + image_path);
        } else {
            preview.hide().attr('src', '#');
            placeholder.text("ไม่มีรูปภาพ. เลือกไฟล์เพื่ออัปโหลดใหม่.");
        }

        $('#editGroupImage').val('');
        
        $('#editGroupModal').modal('show');
    }

    // Function to handle Manage Projects button click (ไม่มีการเปลี่ยนแปลง)
    function myApp_manageProjects(groupId, groupName) {
        $('#manageProjectsGroupId').val(groupId);
        $('#currentGroupName').text(groupName);

        loadProjectsInGroup(groupId);

        $('#addProjectSelector').val(null).trigger('change'); 

        $('#manageProjectsModal').modal('show');
    }

    // Function to handle Delete Group (ไม่มีการเปลี่ยนแปลง)
    function myApp_deleteGroup(groupId) {
        Swal.fire({
            title: 'ยืนยันการลบ',
            text: "คุณต้องการลบหมวดหมู่นี้หรือไม่? การดำเนินการนี้ไม่สามารถยกเลิกได้!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ใช่, ลบ!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'group_actions.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'delete_group',
                        group_id: groupId
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: response.status,
                            title: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            if (response.status === 'success') {
                                location.reload();
                            }
                        });
                    },
                    error: function() {
                        Swal.fire('เกิดข้อผิดพลาด!', 'ไม่สามารถลบหมวดหมู่ได้.', 'error');
                    }
                });
            }
        });
    }
</script>
</body>
</html>