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

$texts = [
    'page_title' => [
        'th' => 'เพิ่มสินค้า',
        'en' => 'Add Product',
        'cn' => '添加产品',
        'jp' => '商品を追加',
        'kr' => '상품 추가'
    ],
    'product_name' => [
        'th' => 'ชื่อสินค้า',
        'en' => 'Product Name',
        'cn' => '产品名称',
        'jp' => '商品名',
        'kr' => '상품명'
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
    'stock' => [
        'th' => 'จำนวนสต็อก',
        'en' => 'Stock Quantity',
        'cn' => '库存数量',
        'jp' => '在庫数',
        'kr' => '재고 수량'
    ],
    'images' => [
        'th' => 'รูปภาพสินค้า',
        'en' => 'Product Images',
        'cn' => '产品图片',
        'jp' => '商品画像',
        'kr' => '상품 이미지'
    ],
    'add_button' => [
        'th' => 'เพิ่มสินค้า',
        'en' => 'Add Product',
        'cn' => '添加产品',
        'jp' => '商品を追加',
        'kr' => '상품 추가'
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
    'upload_text' => [
        'th' => 'คลิกหรือลากรูปมาวางที่นี่',
        'en' => 'Click or drag images here',
        'cn' => '点击或拖动图片到这里',
        'jp' => 'クリックまたはドラッグ',
        'kr' => '클릭하거나 드래그'
    ],
    'upload_hint' => [
        'th' => 'รูปแรกจะเป็นรูปหลัก | เลือกได้หลายรูป | ไฟล์ละไม่เกิน 2MB',
        'en' => 'First image will be primary | Multiple files | Max 2MB each',
        'cn' => '第一张图片为主图 | 支持多选 | 每个最大2MB',
        'jp' => '最初の画像がメイン | 複数選択可 | 最大2MB',
        'kr' => '첫 이미지가 기본 | 다중 선택 | 최대 2MB'
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
                        <i class="fas fa-plus-circle"></i>
                        <?= getTextByLang('page_title') ?>
                    </h4>
                    <button type='button' id='backToProductList' class='btn btn-secondary'>
                        <i class='fas fa-arrow-left'></i>
                        <?= getTextByLang('back_button') ?>
                    </button>
                </div>

                <form id="formProduct" enctype="multipart/form-data">
                    <div class="row">
                        
                        <div class="col-lg-5">
                            <div class="sidebar-section">
                                
                                <div class="form-section">
                                    <label>
                                        <i class="fas fa-images"></i>
                                        <?= getTextByLang('images') ?>
                                    </label>
                                    
                                    <div class="image-upload-zone" onclick="document.getElementById('productImages').click()">
                                        <div class="upload-icon-wrapper">
                                            <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                            <div class="upload-text"><?= getTextByLang('upload_text') ?></div>
                                            <div class="upload-hint"><?= getTextByLang('upload_hint') ?></div>
                                        </div>
                                    </div>
                                    
                                    <input type="file" id="productImages" name="product_images[]" multiple accept="image/*">
                                    
                                    <div id="imagePreviewContainer" class="image-preview-container"></div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-section-compact">
                                            <label>
                                                <i class="fas fa-tag"></i>
                                                <?= getTextByLang('price') ?> (฿)
                                            </label>
                                            <input type="number" class="form-control form-control-compact" id="price" name="price" step="0.01" min="0" value="0.00" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-section-compact">
                                            <label>
                                                <i class="fas fa-percent"></i>
                                                <?= getTextByLang('vat') ?> (%)
                                            </label>
                                            <input type="number" class="form-control form-control-compact" id="vat_percentage" name="vat_percentage" step="0.01" min="0" max="100" value="7.00" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-section-compact">
                                    <label>
                                        <i class="fas fa-boxes"></i>
                                        <?= getTextByLang('stock') ?>
                                    </label>
                                    <input type="number" class="form-control form-control-compact" id="stock_quantity" name="stock_quantity" min="0" value="0" required>
                                    <small class="text-muted">จำนวนสินค้าที่มีในสต็อกขณะนี้</small>
                                </div>

                                <div class="form-section-compact">
                                    <label>
                                        <i class="fas fa-toggle-on"></i>
                                        <?= getTextByLang('status') ?>
                                    </label>
                                    <select class="form-control form-control-compact" id="status" name="status" required>
                                        <option value="1"><?= getTextByLang('active') ?></option>
                                        <option value="0"><?= getTextByLang('inactive') ?></option>
                                    </select>
                                </div>

                                <div class="form-section" style="margin-top: 25px;">
                                    <button type="button" id="submitAddProduct" class="btn btn-primary w-100">
                                        <i class="fas fa-plus"></i>
                                        <?= getTextByLang('add_button') ?>
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
                                                    <label><?= getTextByLang('product_name') ?> (TH) *</label>
                                                    <input type="text" class="form-control" id="name_th" name="name_th" required>
                                                </div>
                                                <div class="form-section-compact">
                                                    <label><?= getTextByLang('description') ?> (TH)</label>
                                                    <textarea class="form-control" id="description_th" name="description_th" rows="4"></textarea>
                                                </div>
                                            </div>

                                            <div class="tab-pane fade" id="en">
                                                <div class="form-section-compact">
                                                    <label><?= getTextByLang('product_name') ?> (EN)</label>
                                                    <input type="text" class="form-control" id="name_en" name="name_en">
                                                </div>
                                                <div class="form-section-compact">
                                                    <label><?= getTextByLang('description') ?> (EN)</label>
                                                    <textarea class="form-control" id="description_en" name="description_en" rows="4"></textarea>
                                                </div>
                                            </div>

                                            <div class="tab-pane fade" id="cn">
                                                <div class="form-section-compact">
                                                    <label><?= getTextByLang('product_name') ?> (CN)</label>
                                                    <input type="text" class="form-control" id="name_cn" name="name_cn">
                                                </div>
                                                <div class="form-section-compact">
                                                    <label><?= getTextByLang('description') ?> (CN)</label>
                                                    <textarea class="form-control" id="description_cn" name="description_cn" rows="4"></textarea>
                                                </div>
                                            </div>

                                            <div class="tab-pane fade" id="jp">
                                                <div class="form-section-compact">
                                                    <label><?= getTextByLang('product_name') ?> (JP)</label>
                                                    <input type="text" class="form-control" id="name_jp" name="name_jp">
                                                </div>
                                                <div class="form-section-compact">
                                                    <label><?= getTextByLang('description') ?> (JP)</label>
                                                    <textarea class="form-control" id="description_jp" name="description_jp" rows="4"></textarea>
                                                </div>
                                            </div>

                                            <div class="tab-pane fade" id="kr">
                                                <div class="form-section-compact">
                                                    <label><?= getTextByLang('product_name') ?> (KR)</label>
                                                    <input type="text" class="form-control" id="name_kr" name="name_kr">
                                                </div>
                                                <div class="form-section-compact">
                                                    <label><?= getTextByLang('description') ?> (KR)</label>
                                                    <textarea class="form-control" id="description_kr" name="description_kr" rows="4"></textarea>
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
    <script src='js/products.js?v=<?php echo time(); ?>'></script>
</body>
</html>