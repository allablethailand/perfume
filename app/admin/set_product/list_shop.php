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
        'th' => 'จัดการกลุ่มสินค้า (กลิ่น)',
        'en' => 'Manage Product Groups (Scents)',
        'cn' => '管理产品组（香味）',
        'jp' => '商品グループ管理（香り）',
        'kr' => '상품 그룹 관리 (향)'
    ],
    'add_group' => [
        'th' => 'เพิ่มกลิ่นใหม่',
        'en' => 'Add New Scent',
        'cn' => '添加新香味',
        'jp' => '新しい香りを追加',
        'kr' => '새 향 추가'
    ],
    'scent_name' => [
        'th' => 'ชื่อกลิ่น',
        'en' => 'Scent Name',
        'cn' => '香味名称',
        'jp' => '香り名',
        'kr' => '향 이름'
    ],
    'price' => [
        'th' => 'ราคา/ขวด',
        'en' => 'Price/Bottle',
        'cn' => '价格/瓶',
        'jp' => '価格/本',
        'kr' => '가격/병'
    ],
    'total_bottles' => [
        'th' => 'จำนวนขวดทั้งหมด',
        'en' => 'Total Bottles',
        'cn' => '总瓶数',
        'jp' => '合計本数',
        'kr' => '전체 병 수'
    ],
    'available' => [
        'th' => 'พร้อมขาย',
        'en' => 'Available',
        'cn' => '可售',
        'jp' => '在庫',
        'kr' => '판매 가능'
    ],
    'sold' => [
        'th' => 'ขายแล้ว',
        'en' => 'Sold',
        'cn' => '已售',
        'jp' => '販売済',
        'kr' => '판매됨'
    ],
    'manage_bottles' => [
        'th' => 'จัดการขวด',
        'en' => 'Manage Bottles',
        'cn' => '管理瓶子',
        'jp' => 'ボトル管理',
        'kr' => '병 관리'
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
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="../../../inc/sweetalert2/css/sweetalert2.min.css" rel="stylesheet">
    <script src="../../../inc/sweetalert2/js/sweetalert2.all.min.js"></script>
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
        .btn-edit { background-color: #FFC107; color: #ffffff; }
        .btn-del { background-color: #ff4537; color: #ffffff; }
        .btn-bottles { background-color: #17a2b8; color: #ffffff; }
        
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .stock-info {
            font-size: 12px;
            display: flex;
            gap: 10px;
        }
        
        .stock-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: 600;
        }
        
        .stock-available { background-color: #28a745; color: white; }
        .stock-sold { background-color: #6c757d; color: white; }
        .stock-reserved { background-color: #ffc107; color: #212529; }
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
                                    <i class="fas fa-layer-group"></i>   
                                    <?= getTextByLang('page_title') ?>
                                </h4>
                                <div>
                                    <a type="button" class="btn btn-primary" href="<?php echo $base_path_admin.'set_product/add_product_group.php'?>">
                                        <i class="fa-solid fa-plus"></i>
                                        <?= getTextByLang('add_group') ?>
                                    </a>
                                </div>
                            </div>
                            
                            <table id="td_list_product_groups" class="table table-hover" style="width:100%;">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Image</th>
                                        <th><?= getTextByLang('scent_name') ?></th>
                                        <th><?= getTextByLang('price') ?></th>
                                        <th><?= getTextByLang('total_bottles') ?></th>
                                        <th><?= getTextByLang('available') ?></th>
                                        <th><?= getTextByLang('sold') ?></th>
                                        <th>Status</th>
                                        <th>Actions</th>
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
    <script src='js/product_groups.js?v=<?php echo time(); ?>'></script>
</body>
</html>