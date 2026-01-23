<?php
include '../check_permission.php';
require_once('../../../lib/connect.php');
global $conn;

$lang = 'th';
if (isset($_GET['lang'])) {
    $supportedLangs = ['th', 'en', 'cn', 'jp', 'kr'];
    $newLang = $_GET['lang'];
    if (in_array($newLang, $supportedLangs)) {
        $_SESSION['lang'] = $newLang;
        $lang = $newLang;
    }
} else {
    if (isset($_SESSION['lang'])) {
        $lang = $_SESSION['lang'];
    }
}

if (!isset($_POST['group_id']) && !isset($_GET['group_id'])) {
    echo "<script>alert('Group ID is missing'); window.location.href='list_shop.php';</script>";
    exit;
}

$group_id = $_POST['group_id'] ?? $_GET['group_id'];

// ดึงข้อมูลกลุ่มสินค้า
$stmt = $conn->prepare("SELECT * FROM product_groups WHERE group_id = ? AND del = 0");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('Product group not found'); window.location.href='list_shop.php';</script>";
    exit;
}

$group = $result->fetch_assoc();
$stmt->close();

// ดึงรูปภาพ
$stmt_images = $conn->prepare("SELECT * FROM product_group_images WHERE group_id = ? AND del = 0 ORDER BY is_primary DESC, display_order ASC");
$stmt_images->bind_param("i", $group_id);
$stmt_images->execute();
$result_images = $stmt_images->get_result();
$images = [];
while ($row = $result_images->fetch_assoc()) {
    $images[] = $row;
}
$stmt_images->close();

// นับจำนวนขวด
$stmt_count = $conn->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
    SUM(CASE WHEN status = 'reserved' THEN 1 ELSE 0 END) as reserved,
    SUM(CASE WHEN status = 'sold' THEN 1 ELSE 0 END) as sold
    FROM product_items 
    WHERE group_id = ? AND del = 0");
$stmt_count->bind_param("i", $group_id);
$stmt_count->execute();
$bottle_stats = $stmt_count->get_result()->fetch_assoc();
$stmt_count->close();

$texts = [
    'page_title' => [
        'th' => 'แก้ไขกลิ่น',
        'en' => 'Edit Scent',
        'cn' => '编辑香味',
        'jp' => '香りを編集',
        'kr' => '향 수정'
    ],
    'scent_name' => [
        'th' => 'ชื่อกลิ่น',
        'en' => 'Scent Name',
        'cn' => '香味名称',
        'jp' => '香り名',
        'kr' => '향 이름'
    ],
    'description' => [
        'th' => 'รายละเอียด',
        'en' => 'Description',
        'cn' => '描述',
        'jp' => '説明',
        'kr' => '설명'
    ],
    'price' => [
        'th' => 'ราคา',
        'en' => 'Price',
        'cn' => '价格',
        'jp' => '価格',
        'kr' => '가격'
    ],
    'vat' => [
        'th' => 'VAT',
        'en' => 'VAT',
        'cn' => '增值税',
        'jp' => 'VAT',
        'kr' => '부가세'
    ],
    'images' => [
        'th' => 'รูปภาพสินค้า',
        'en' => 'Product Images',
        'cn' => '产品图片',
        'jp' => '商品画像',
        'kr' => '상품 이미지'
    ],
    'save_button' => [
        'th' => 'บันทึก',
        'en' => 'Save',
        'cn' => '保存',
        'jp' => '保存',
        'kr' => '저장'
    ],
    'back_button' => [
        'th' => 'กลับ',
        'en' => 'Back',
        'cn' => '返回',
        'jp' => '戻る',
        'kr' => '뒤로'
    ],
    'status' => [
        'th' => 'สถานะ',
        'en' => 'Status',
        'cn' => '状态',
        'jp' => 'ステータス',
        'kr' => '상태'
    ],
    'active' => [
        'th' => 'เปิดใช้งาน',
        'en' => 'Active',
        'cn' => '启用',
        'jp' => 'アクティブ',
        'kr' => '활성'
    ],
    'inactive' => [
        'th' => 'ปิดใช้งาน',
        'en' => 'Inactive',
        'cn' => '停用',
        'jp' => '非アクティブ',
        'kr' => '비활성'
    ],
    'language' => [
        'th' => [
            'th' => 'ไทย',
            'en' => 'อังกฤษ',
            'cn' => 'จีน',
            'jp' => 'ญี่ปุ่น',
            'kr' => 'เกาหลี'
        ],
        'en' => [
            'th' => 'Thai',
            'en' => 'English',
            'cn' => 'Chinese',
            'jp' => 'Japanese',
            'kr' => 'Korean'
        ],
        'cn' => [
            'th' => '泰语',
            'en' => '英语',
            'cn' => '中文',
            'jp' => '日语',
            'kr' => '韩语'
        ],
        'jp' => [
            'th' => 'タイ語',
            'en' => '英語',
            'cn' => '中国語',
            'jp' => '日本語',
            'kr' => '韓国語'
        ],
        'kr' => [
            'th' => '태국어',
            'en' => '영어',
            'cn' => '중국어',
            'jp' => '일본어',
            'kr' => '한국어'
        ]
    ]
];

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
    <title><?= getTextByLang('page_title') ?></title>

    <link rel="icon" type="image/x-icon" href="../../../public/product_images/695e0bf362d49_1767771123.jpg">
    <link href="../../../inc/jquery/css/jquery-ui.css" rel="stylesheet">
    <script src="../../../inc/jquery/js/jquery-3.6.0.min.js"></script>
    <script src="../../../inc/jquery/js/jquery-ui.min.js"></script>
    <link href="../../../inc/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="../../../inc/bootstrap/js/bootstrap.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/fontawesome5-fullcss@1.1.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="../../../inc/sweetalert2/css/sweetalert2.min.css" rel="stylesheet">
    <script src="../../../inc/sweetalert2/js/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <link href='../css/index_.css?v=<?php echo time(); ?>' rel='stylesheet'>
    <link href='css/product-form-modern.css?v=<?php echo time(); ?>' rel='stylesheet'>
</head>

<?php include '../template/header.php' ?>

<body>
    <div class="content-sticky">
        <div class="container-fluid">
            <div class="product-form-container">
                
                <div class="page-header">
                    <h4>
                        <i class="fas fa-edit"></i>
                        <?= getTextByLang('page_title') ?>
                    </h4>
                    <button type='button' id='backToList' class='btn btn-secondary'>
                        <i class='fas fa-arrow-left'></i>
                        <?= getTextByLang('back_button') ?>
                    </button>
                </div>

                <!-- Bottle Statistics -->
                <div class="alert alert-info mb-4">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <h5 class="mb-0"><?= $bottle_stats['total'] ?></h5>
                            <small>ทั้งหมด</small>
                        </div>
                        <div class="col-md-3">
                            <h5 class="mb-0 text-success"><?= $bottle_stats['available'] ?></h5>
                            <small>พร้อมขาย</small>
                        </div>
                        <div class="col-md-3">
                            <h5 class="mb-0 text-warning"><?= $bottle_stats['reserved'] ?></h5>
                            <small>จองแล้ว</small>
                        </div>
                        <div class="col-md-3">
                            <h5 class="mb-0 text-secondary"><?= $bottle_stats['sold'] ?></h5>
                            <small>ขายแล้ว</small>
                        </div>
                    </div>
                </div>

                <form id="formGroupEdit" enctype="multipart/form-data">
                    <input type="hidden" id="group_id" name="group_id" value="<?= htmlspecialchars($group['group_id']) ?>">
                    <input type="hidden" id="existing_images" name="existing_images" value="">
                    
                    <div class="row">
                        
                        <div class="col-lg-5">
                            <div class="sidebar-section">
                                
                                <div class="form-section">
                                    <label>
                                        <i class="fas fa-images"></i>
                                        <?= getTextByLang('images') ?>
                                        <small>ลากเรียงลำดับรูป | รูปแรกเป็นรูปหลัก | เพิ่มรูปใหม่ได้</small>
                                    </label>
                                    
                                    <div id="imagePreviewContainer" class="image-preview-container">
                                        <?php foreach ($images as $index => $img): ?>
                                            <div class="image-preview-item" data-image-id="<?= $img['image_id'] ?>">
                                                <img src="<?= htmlspecialchars($img['api_path']) ?>" alt="Product Image">
                                                <button type="button" class="remove-image" onclick="removeExistingImage(<?= $img['image_id'] ?>)">×</button>
                                                <?php if ($img['is_primary'] == 1): ?>
                                                    <span class="primary-badge">PRIMARY</span>
                                                <?php endif; ?>
                                                <span class="order-number">#<?= $index + 1 ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="image-upload-zone" onclick="document.getElementById('groupImages').click()" style="margin-top: 20px; padding: 25px 20px;">
                                        <div class="upload-icon-wrapper">
                                            <i class="fas fa-plus-circle upload-icon" style="font-size: 40px;"></i>
                                            <div class="upload-text" style="font-size: 15px;">เพิ่มรูปใหม่</div>
                                        </div>
                                    </div>
                                    
                                    <input type="file" id="groupImages" name="group_images[]" multiple accept="image/*">
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-section-compact">
                                            <label>
                                                <i class="fas fa-tag"></i>
                                                <?= getTextByLang('price') ?> (฿)
                                            </label>
                                            <input type="number" class="form-control form-control-compact" id="price" name="price" step="0.01" min="0" value="<?= htmlspecialchars($group['price']) ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-section-compact">
                                            <label>
                                                <i class="fas fa-percent"></i>
                                                <?= getTextByLang('vat') ?> (%)
                                            </label>
                                            <input type="number" class="form-control form-control-compact" id="vat_percentage" name="vat_percentage" step="0.01" min="0" max="100" value="<?= htmlspecialchars($group['vat_percentage']) ?>" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-section-compact">
                                    <label>
                                        <i class="fas fa-toggle-on"></i>
                                        <?= getTextByLang('status') ?>
                                    </label>
                                    <select class="form-control form-control-compact" id="status" name="status" required>
                                        <option value="1" <?= $group['status'] == 1 ? 'selected' : '' ?>><?= getTextByLang('active') ?></option>
                                        <option value="0" <?= $group['status'] == 0 ? 'selected' : '' ?>><?= getTextByLang('inactive') ?></option>
                                    </select>
                                </div>

                                <div class="form-section" style="margin-top: 25px;">
                                    <button type="button" id="submitEditGroup" class="btn btn-success w-100">
                                        <i class="fas fa-save"></i>
                                        <?= getTextByLang('save_button') ?>
                                    </button>
                                </div>
                                
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="content-section">
                                
                                <div class="card">
                                    <div class="card-header p-0">
                                        <ul class="nav nav-tabs" id="languageTabs" role="tablist">
                                            <li class="nav-item">
                                                <button class="nav-link active" id="th-tab" data-bs-toggle="tab" data-bs-target="#th" type="button">
                                                    <img src="https://flagcdn.com/w320/th.png" class="flag-icon">
                                                    <?= $texts['language'][$lang]['th'] ?>
                                                </button>
                                            </li>
                                            <li class="nav-item">
                                                <button class="nav-link" id="en-tab" data-bs-toggle="tab" data-bs-target="#en" type="button">
                                                    <img src="https://flagcdn.com/w320/gb.png" class="flag-icon">
                                                    <?= $texts['language'][$lang]['en'] ?>
                                                </button>
                                            </li>
                                            <li class="nav-item">
                                                <button class="nav-link" id="cn-tab" data-bs-toggle="tab" data-bs-target="#cn" type="button">
                                                    <img src="https://flagcdn.com/w320/cn.png" class="flag-icon">
                                                    <?= $texts['language'][$lang]['cn'] ?>
                                                </button>
                                            </li>
                                            <li class="nav-item">
                                                <button class="nav-link" id="jp-tab" data-bs-toggle="tab" data-bs-target="#jp" type="button">
                                                    <img src="https://flagcdn.com/w320/jp.png" class="flag-icon">
                                                    <?= $texts['language'][$lang]['jp'] ?>
                                                </button>
                                            </li>
                                            <li class="nav-item">
                                                <button class="nav-link" id="kr-tab" data-bs-toggle="tab" data-bs-target="#kr" type="button">
                                                    <img src="https://flagcdn.com/w320/kr.png" class="flag-icon">
                                                    <?= $texts['language'][$lang]['kr'] ?>
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                    
                                    <div class="card-body">
                                        <div class="tab-content">
                                            
                                            <div class="tab-pane fade show active" id="th">
                                                <div class="form-section-compact">
                                                    <label><?= getTextByLang('scent_name') ?> (TH) *</label>
                                                    <input type="text" class="form-control" id="name_th" name="name_th" value="<?= htmlspecialchars($group['name_th']) ?>" required>
                                                </div>
                                                <div class="form-section-compact">
                                                    <label><?= getTextByLang('description') ?> (TH)</label>
                                                    <textarea class="form-control" id="description_th" name="description_th" rows="4"><?= htmlspecialchars($group['description_th']) ?></textarea>
                                                </div>
                                            </div>

                                            <div class="tab-pane fade" id="en">
                                                <div class="form-section-compact">
                                                    <label><?= getTextByLang('scent_name') ?> (EN)</label>
                                                    <input type="text" class="form-control" id="name_en" name="name_en" value="<?= htmlspecialchars($group['name_en']) ?>">
                                                </div>
                                                <div class="form-section-compact">
                                                    <label><?= getTextByLang('description') ?> (EN)</label>
                                                    <textarea class="form-control" id="description_en" name="description_en" rows="4"><?= htmlspecialchars($group['description_en']) ?></textarea>
                                                </div>
                                            </div>

                                            <div class="tab-pane fade" id="cn">
                                                <div class="form-section-compact">
                                                    <label><?= getTextByLang('scent_name') ?> (CN)</label>
                                                    <input type="text" class="form-control" id="name_cn" name="name_cn" value="<?= htmlspecialchars($group['name_cn']) ?>">
                                                </div>
                                                <div class="form-section-compact">
                                                    <label><?= getTextByLang('description') ?> (CN)</label>
                                                    <textarea class="form-control" id="description_cn" name="description_cn" rows="4"><?= htmlspecialchars($group['description_cn']) ?></textarea>
                                                </div>
                                            </div>

                                            <div class="tab-pane fade" id="jp">
                                                <div class="form-section-compact">
                                                    <label><?= getTextByLang('scent_name') ?> (JP)</label>
                                                    <input type="text" class="form-control" id="name_jp" name="name_jp" value="<?= htmlspecialchars($group['name_jp']) ?>">
                                                </div>
                                                <div class="form-section-compact">
                                                    <label><?= getTextByLang('description') ?> (JP)</label>
                                                    <textarea class="form-control" id="description_jp" name="description_jp" rows="4"><?= htmlspecialchars($group['description_jp']) ?></textarea>
                                                </div>
                                            </div>

                                            <div class="tab-pane fade" id="kr">
                                                <div class="form-section-compact">
                                                    <label><?= getTextByLang('scent_name') ?> (KR)</label>
                                                    <input type="text" class="form-control" id="name_kr" name="name_kr" value="<?= htmlspecialchars($group['name_kr']) ?>">
                                                </div>
                                                <div class="form-section-compact">
                                                    <label><?= getTextByLang('description') ?> (KR)</label>
                                                    <textarea class="form-control" id="description_kr" name="description_kr" rows="4"><?= htmlspecialchars($group['description_kr']) ?></textarea>
                                                </div>
                                            </div>
                                            
                                        </div>
                                    </div>
                                </div>
                                
                            </div>
                        </div>
                        
                    </div>
                </form>
                
            </div>
        </div>
    </div>

    <div id="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <script src='../js/index_.js?v=<?php echo time(); ?>'></script>
    <script src='js/product_groups.js?v=<?php echo time(); ?>'></script>
</body>
</html>