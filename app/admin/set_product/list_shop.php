<?php
include '../check_permission.php';

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
        'th' => 'รายการสินค้า',
        'en' => 'Product List',
        'cn' => '产品列表',
        'jp' => '商品リスト',
        'kr' => '상품 목록'
    ],
    'add_product' => [
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
    'price' => [
        'th' => 'ราคา (ไม่รวม VAT)',
        'en' => 'Price (Excl. VAT)',
        'cn' => '价格（不含税）',
        'jp' => '価格（税抜）',
        'kr' => '가격 (부가세 제외)'
    ],
    'price_with_vat' => [
        'th' => 'ราคา (รวม VAT)',
        'en' => 'Price (Incl. VAT)',
        'cn' => '价格（含税）',
        'jp' => '価格（税込）',
        'kr' => '가격 (부가세 포함)'
    ],
    'stock' => [
        'th' => 'สต็อก',
        'en' => 'Stock',
        'cn' => '库存',
        'jp' => '在庫',
        'kr' => '재고'
    ],
    'status' => [
        'th' => 'สถานะ',
        'en' => 'Status',
        'cn' => '状态',
        'jp' => 'ステータス',
        'kr' => '상태'
    ],
    'created_date' => [
        'th' => 'วันที่สร้าง',
        'en' => 'Created Date',
        'cn' => '创建日期',
        'jp' => '作成日',
        'kr' => '생성일'
    ],
    'management' => [
        'th' => 'จัดการ',
        'en' => 'Management',
        'cn' => '管理',
        'jp' => '管理',
        'kr' => '관리'
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
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
        .btn-edit {
            background-color: #FFC107;
            color: #ffffff;
        }
        .btn-del {
            background-color: #ff4537;
            color: #ffffff;
        }
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        .badge-active {
            background-color: #28a745;
        }
        .badge-inactive {
            background-color: #dc3545;
        }
        .badge-stock {
            font-size: 13px;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: 600;
        }
        .badge-stock-high {
            background-color: #28a745;
            color: white;
        }
        .badge-stock-medium {
            background-color: #ffc107;
            color: #212529;
        }
        .badge-stock-low {
            background-color: #fd7e14;
            color: white;
        }
        .badge-stock-out {
            background-color: #dc3545;
            color: white;
        }
        .box-content img {
            width: 4em;
            height: auto;
            border-radius: 8px;
        }
    </style>
</head>

<?php include '../template/header.php' ?>

<body>
    <div class="content-sticky">
        <div class="container-fluid">
            <div class="box-content">
                <div class="row">
                    <div>
                        <div style="margin: 10px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <h4 class="line-ref mb-3"> 
                                    <i class="fas fa-box"></i>   
                                    <?= getTextByLang('page_title') ?>
                                </h4>
                                <div>
                                    <a type="button" class="btn btn-primary" href="<?php echo $base_path_admin.'set_product/add_product.php'?>">
                                        <i class="fa-solid fa-plus"></i>
                                        <?= getTextByLang('add_product') ?>
                                    </a>
                                </div>
                            </div>
                            <table id="td_list_products" class="table table-hover" style="width:100%;">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Image</th>
                                        <th><?= getTextByLang('product_name') ?></th>
                                        <th><?= getTextByLang('price') ?></th>
                                        <th><?= getTextByLang('price_with_vat') ?></th>
                                        <th><?= getTextByLang('stock') ?></th>
                                        <th><?= getTextByLang('status') ?></th>
                                        <th><?= getTextByLang('created_date') ?></th>
                                        <th><?= getTextByLang('management') ?></th>
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
    
    <script src='../js/index_.js?v=<?php echo time(); ?>'></script>
    <script src='js/products.js?v=<?php echo time(); ?>'></script>
</body>
</html>