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
        'th' => 'จัดการคำสั่งซื้อ',
        'en' => 'Order Management',
        'cn' => '订单管理',
        'jp' => '注文管理',
        'kr' => '주문 관리'
    ],
    'stock_logs' => [
        'th' => 'ประวัติสต็อก',
        'en' => 'Stock Logs',
        'cn' => '库存日志',
        'jp' => '在庫ログ',
        'kr' => '재고 로그'
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
        body {
            background: #f5f7fa;
        }

        .box-content {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 25px;
            margin-top: 20px;
        }

        .page-header {
            background: #2c3e50;
            padding: 25px;
            border-radius: 12px;
            color: white;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .page-header h4 {
            margin: 0;
            font-weight: 600;
            font-size: 24px;
        }

        .page-header .btn {
            background: white;
            color: #2c3e50;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .page-header .btn:hover {
            background: #ecf0f1;
            transform: translateY(-2px);
        }

        /* Status Filter Buttons */
        .status-filter-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 1px solid #dee2e6;
        }

        .filter-title {
            font-weight: 600;
            color: #495057;
            margin-right: 20px;
            font-size: 15px;
        }

        .status-btn {
            padding: 12px 24px;
            margin: 5px;
            border: 2px solid transparent;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .status-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .status-btn.active {
            border-color: #007bff;
            box-shadow: 0 2px 10px rgba(0,123,255,0.3);
        }

        .status-btn .badge {
            margin-left: 8px;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            background: rgba(255,255,255,0.3);
        }

        .status-btn-all { background: #6c757d; color: white; }
        .status-btn-pending { background: #ffc107; color: #333; }
        .status-btn-processing { background: #17a2b8; color: white; }
        .status-btn-paid { background: #20c997; color: white; }
        .status-btn-shipped { background: #007bff; color: white; }
        .status-btn-completed { background: #28a745; color: white; }
        .status-btn-cancelled { background: #dc3545; color: white; }

        /* Table Styles */
        #td_list_orders {
            border-radius: 8px;
            overflow: hidden;
        }

        #td_list_orders thead {
            background: #2c3e50;
            color: white;
        }

        #td_list_orders thead th {
            font-weight: 600;
            font-size: 13px;
            padding: 15px 12px;
            border: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        #td_list_orders tbody tr {
            transition: all 0.2s;
            border-bottom: 1px solid #e9ecef;
        }

        #td_list_orders tbody tr:hover {
            background: #f8f9fa;
        }

        #td_list_orders tbody td {
            padding: 12px;
            vertical-align: middle;
            font-size: 14px;
        }

        /* Customer Info */
        .customer-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #dee2e6;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .customer-details {
            flex: 1;
        }

        .customer-name {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 2px;
        }

        .customer-email {
            font-size: 12px;
            color: #6c757d;
        }

        /* Badges */
        .badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge-warning { background: #ffc107; color: #333; }
        .badge-info { background: #17a2b8; color: white; }
        .badge-paid { background: #20c997; color: white; }
        .badge-success { background: #28a745; color: white; }
        .badge-primary { background: #007bff; color: white; }
        .badge-danger { background: #dc3545; color: white; }

        /* Status Dropdown */
        .status-dropdown {
            border: 2px solid #dee2e6;
            border-radius: 6px;
            padding: 8px 12px;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s;
            background: white;
        }

        .status-dropdown:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
            outline: none;
        }

        /* Buttons */
        .btn-modern {
            border-radius: 6px;
            padding: 8px 16px;
            font-weight: 600;
            font-size: 13px;
            border: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-view-slip {
            background: #17a2b8;
            color: white;
        }

        .btn-view-slip:hover {
            background: #138496;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(23,162,184,0.3);
        }

        .btn-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-view {
            background: #007bff;
            color: white;
        }

        .btn-view:hover {
            background: #0056b3;
            transform: scale(1.1);
        }

        /* Loading Overlay */
        #loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .loading-spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #007bff;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* DataTables Pagination */
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 6px !important;
            margin: 0 3px;
            padding: 8px 12px !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #007bff !important;
            color: white !important;
            border: none !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #0056b3 !important;
            color: white !important;
        }
    </style>
</head>

<?php include '../template/header.php' ?>

<body>
    <div class="content-sticky">
        <div class="container-fluid">
            <div class="box-content">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4>
                            <i class="fas fa-shopping-cart"></i>
                            <?= getTextByLang('page_title') ?>
                        </h4>
                        <button type="button" onclick="window.location.href='stock_logs.php'" class="btn">
                            <i class="fas fa-history"></i>
                            <?= getTextByLang('stock_logs') ?>
                        </button>
                    </div>
                </div>

                <!-- Status Filter - เพิ่มปุ่ม Paid -->
                <div class="status-filter-container">
                    <div class="d-flex flex-wrap align-items-center">
                        <span class="filter-title">
                            <i class="fas fa-filter"></i> กรองสถานะ:
                        </span>
                        <button type="button" class="status-btn status-btn-all active" data-status="">
                            ทั้งหมด <span class="badge badge-light" id="count-all">0</span>
                        </button>
                        <button type="button" class="status-btn status-btn-pending" data-status="pending">
                            Pending <span class="badge badge-light" id="count-pending">0</span>
                        </button>
                        <button type="button" class="status-btn status-btn-processing" data-status="processing">
                            Processing <span class="badge badge-light" id="count-processing">0</span>
                        </button>
                        <button type="button" class="status-btn status-btn-paid" data-status="paid">
                            Paid <span class="badge badge-light" id="count-paid">0</span>
                        </button>
                        <button type="button" class="status-btn status-btn-shipped" data-status="shipped">
                            Shipped <span class="badge badge-light" id="count-shipped">0</span>
                        </button>
                        <button type="button" class="status-btn status-btn-completed" data-status="completed">
                            Completed <span class="badge badge-light" id="count-completed">0</span>
                        </button>
                        <button type="button" class="status-btn status-btn-cancelled" data-status="cancelled">
                            Cancelled <span class="badge badge-light" id="count-cancelled">0</span>
                        </button>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="table-responsive">
                    <table id="td_list_orders" class="table table-hover" style="width:100%;">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th style="width: 120px;">เลขคำสั่งซื้อ</th>
                                <th style="width: 200px;">ลูกค้า</th>
                                <th style="width: 100px;">รายการ</th>
                                <th style="width: 110px;">ยอดรวม</th>
                                <th style="width: 150px;">สถานะคำสั่งซื้อ</th>
                                <th style="width: 120px;">การชำระเงิน</th>
                                <th style="width: 100px;">สลิป</th>
                                <th style="width: 130px;">วันที่</th>
                                <th style="width: 80px;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div id="loading-overlay">
        <div class="loading-spinner"></div>
    </div>
    
    <script src='../js/index_.js?v=<?php echo time(); ?>'></script>
    <script src='js/orders.js?v=<?php echo time(); ?>'></script>
</body>
</html>