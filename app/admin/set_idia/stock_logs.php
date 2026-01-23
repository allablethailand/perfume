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

// ✅ แก้ไข: ใช้เฉพาะ product_groups (ไม่มี products อีกต่อไป)
$products_query = "
    SELECT 
        pg.group_id as id,
        pg.name_th,
        pg.name_en
    FROM product_groups pg
    WHERE pg.del = 0
    ORDER BY pg.name_th
";
$products_result = $conn->query($products_query);
$products = [];
while ($row = $products_result->fetch_assoc()) {
    $products[] = $row;
}

$texts = [
    'page_title' => [
        'th' => 'ประวัติการเปลี่ยนแปลงสต็อก',
        'en' => 'Stock Change History',
        'cn' => '库存变更历史',
        'jp' => '在庫変更履歴',
        'kr' => '재고 변경 기록'
    ],
    'back' => [
        'th' => 'กลับ',
        'en' => 'Back',
        'cn' => '返回',
        'jp' => '戻る',
        'kr' => '뒤로'
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
    <link href="../../../inc/select2/css/select2.min.css" rel="stylesheet">
    <script src="../../../inc/select2/js/select2.min.js"></script>
    <link href='../css/index_.css?v=<?php echo time(); ?>' rel='stylesheet'>
    
    <style>
        .log-container {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .filter-section {
            margin-bottom: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .log-item {
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #ddd;
            background: #fafafa;
            border-radius: 4px;
            transition: all 0.2s;
        }
        .log-item:hover {
            background: #f0f0f0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .log-item.log-add, .log-item.log-restore {
            border-left-color: #28a745;
        }
        .log-item.log-deduct, .log-item.log-sold {
            border-left-color: #dc3545;
        }
        .log-item.log-adjust {
            border-left-color: #ffc107;
        }
        .log-type-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .log-type-add, .log-type-restore {
            background: #d4edda;
            color: #155724;
        }
        .log-type-deduct, .log-type-sold {
            background: #f8d7da;
            color: #721c24;
        }
        .log-type-adjust {
            background: #fff3cd;
            color: #856404;
        }
        .quantity-change {
            font-size: 18px;
            font-weight: 700;
        }
        .quantity-change.positive {
            color: #28a745;
        }
        .quantity-change.negative {
            color: #dc3545;
        }
        .log-meta {
            font-size: 12px;
            color: #6c757d;
            margin-top: 8px;
        }
        .no-logs {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        .quick-filter-btn {
            padding: 6px 12px;
            font-size: 13px;
            margin: 2px;
            border-radius: 15px;
        }
        .serial-number {
            background: #e3f2fd;
            color: #1565c0;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 8px;
        }
        .product-type-badge {
            background: #f3e5f5;
            color: #6a1b9a;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 5px;
        }
    </style>
</head>

<?php include '../template/header.php' ?>

<body>
    <div class="content-sticky">
        <div class="container-fluid">
            <div class="log-container">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0">
                        <i class="fas fa-history"></i>   
                        <?= getTextByLang('page_title') ?>
                    </h4>
                    <button type="button" onclick="window.location.href='list_idia.php'" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        <?= getTextByLang('back') ?>
                    </button>
                </div>
                
                <div class="filter-section">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label><i class="fas fa-box"></i> กลุ่มสินค้า</label>
                            <select id="filterProduct" class="form-control">
                                <option value="">ทั้งหมด</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?= htmlspecialchars($product['id']) ?>">
                                        <?= htmlspecialchars($product['name_th']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label><i class="fas fa-tag"></i> ประเภท</label>
                            <select id="filterLogType" class="form-control">
                                <option value="">ทั้งหมด</option>
                                <option value="add">เพิ่มสต็อก</option>
                                <option value="deduct">ตัดสต็อก</option>
                                <option value="sold">ขายแล้ว</option>
                                <option value="restore">คืนสต็อก</option>
                                <option value="adjust">ปรับปรุง</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label><i class="fas fa-calendar-alt"></i> วันที่เริ่มต้น</label>
                            <input type="date" id="filterDateFrom" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label><i class="fas fa-calendar-check"></i> วันที่สิ้นสุด</label>
                            <input type="date" id="filterDateTo" class="form-control">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="button" id="btnLoadLogs" class="btn btn-primary me-2">
                                <i class="fas fa-search"></i> ค้นหา
                            </button>
                            <button type="button" id="btnResetFilter" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> รีเซ็ต
                            </button>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <small class="text-muted"><i class="fas fa-clock"></i> ช่วงเวลาด่วน:</small><br>
                            <button type="button" class="btn btn-sm btn-outline-primary quick-filter-btn" data-days="0">วันนี้</button>
                            <button type="button" class="btn btn-sm btn-outline-primary quick-filter-btn" data-days="7">7 วันที่แล้ว</button>
                            <button type="button" class="btn btn-sm btn-outline-primary quick-filter-btn" data-days="30">30 วันที่แล้ว</button>
                            <button type="button" class="btn btn-sm btn-outline-primary quick-filter-btn" data-days="90">90 วันที่แล้ว</button>
                        </div>
                    </div>
                </div>
                
                <div id="logsContainer">
                    <div class="no-logs">
                        <i class="fas fa-box-open" style="font-size: 48px; color: #ddd;"></i>
                        <p>กรุณาเลือกกรองข้อมูลและกดค้นหา</p>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <script>
        $(document).ready(function() {
            
            $('#filterProduct').select2({
                placeholder: 'ทั้งหมด',
                allowClear: true
            });
            
            // Set default date to today
            let today = new Date().toISOString().split('T')[0];
            $('#filterDateTo').val(today);
            
            // Quick filter buttons
            $('.quick-filter-btn').on('click', function() {
                let days = parseInt($(this).data('days'));
                let dateTo = new Date();
                let dateFrom = new Date();
                
                if (days > 0) {
                    dateFrom.setDate(dateFrom.getDate() - days);
                }
                
                $('#filterDateFrom').val(dateFrom.toISOString().split('T')[0]);
                $('#filterDateTo').val(dateTo.toISOString().split('T')[0]);
                
                loadStockLogs();
            });
            
            $('#btnLoadLogs').on('click', function() {
                loadStockLogs();
            });
            
            $('#btnResetFilter').on('click', function() {
                $('#filterProduct').val('').trigger('change');
                $('#filterLogType').val('');
                $('#filterDateFrom').val('');
                $('#filterDateTo').val(today);
                loadStockLogs();
            });
            
            function loadStockLogs() {
                let groupId = $('#filterProduct').val();
                let logType = $('#filterLogType').val();
                let dateFrom = $('#filterDateFrom').val();
                let dateTo = $('#filterDateTo').val();
                
                $.ajax({
                    url: 'actions/process_orders.php',
                    type: 'POST',
                    data: {
                        action: 'getStockLogs',
                        group_id: groupId,
                        log_type: logType,
                        date_from: dateFrom,
                        date_to: dateTo,
                        limit: 200
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            displayLogs(response.logs);
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        alert('Failed to load stock logs');
                    }
                });
            }
            
            function displayLogs(logs) {
                let container = $('#logsContainer');
                container.empty();
                
                if (logs.length === 0) {
                    container.html(`
                        <div class="no-logs">
                            <i class="fas fa-inbox" style="font-size: 48px; color: #ddd;"></i>
                            <p>ไม่พบประวัติการเปลี่ยนแปลงสต็อกในช่วงเวลาที่เลือก</p>
                        </div>
                    `);
                    return;
                }
                
                // ✅ Add summary
                let totalAdd = logs.filter(l => l.log_type === 'add' || l.log_type === 'restore').reduce((sum, l) => sum + parseInt(l.quantity_change), 0);
                let totalDeduct = logs.filter(l => l.log_type === 'deduct' || l.log_type === 'sold').reduce((sum, l) => sum + parseInt(l.quantity_change), 0);
                
                let summaryHtml = `
                    <div class="alert alert-info mb-3">
                        <strong><i class="fas fa-chart-bar"></i> สรุป:</strong>
                        พบ ${logs.length} รายการ | 
                        <span class="text-success">เพิ่ม/คืน: +${totalAdd}</span> | 
                        <span class="text-danger">ตัด/ขาย: -${totalDeduct}</span> | 
                        <span class="text-primary">สุทธิ: ${totalAdd - totalDeduct}</span>
                    </div>
                `;
                container.append(summaryHtml);
                
                logs.forEach(function(log) {
                    let logTypeClass = 'log-' + log.log_type;
                    let badgeClass = 'log-type-' + log.log_type;
                    let logTypeText = '';
                    
                    switch(log.log_type) {
                        case 'add':
                            logTypeText = 'เพิ่มสต็อก';
                            break;
                        case 'deduct':
                            logTypeText = 'ตัดสต็อก';
                            break;
                        case 'sold':
                            logTypeText = 'ขายแล้ว';
                            break;
                        case 'restore':
                            logTypeText = 'คืนสต็อก';
                            break;
                        case 'adjust':
                            logTypeText = 'ปรับปรุง';
                            break;
                    }
                    
                    let changeIcon = '';
                    let changeClass = '';
                    let changeText = '';
                    
                    if (log.log_type === 'add' || log.log_type === 'restore') {
                        changeIcon = '<i class="fas fa-plus-circle"></i>';
                        changeClass = 'positive';
                        changeText = '+' + log.quantity_change;
                    } else if (log.log_type === 'deduct' || log.log_type === 'sold') {
                        changeIcon = '<i class="fas fa-minus-circle"></i>';
                        changeClass = 'negative';
                        changeText = '-' + log.quantity_change;
                    } else {
                        changeIcon = '<i class="fas fa-edit"></i>';
                        changeClass = log.quantity_change > 0 ? 'positive' : 'negative';
                        changeText = (log.quantity_change > 0 ? '+' : '') + log.quantity_change;
                    }
                    
                    // ✅ แสดง Serial Number ถ้ามี
                    let serialInfo = '';
                    if (log.serial_number) {
                        serialInfo = `<span class="serial-number">S/N: ${log.serial_number}</span>`;
                    }
                    
                    let orderInfo = '';
                    if (log.order_number) {
                        orderInfo = `<br><strong>Order:</strong> #${log.order_number}`;
                    }
                    
                    let referenceInfo = '';
                    if (log.reference_type) {
                        referenceInfo = `<br><strong>Reference:</strong> ${log.reference_type}`;
                    }
                    
                    let notesInfo = '';
                    if (log.notes) {
                        notesInfo = `<br><small>${log.notes}</small>`;
                    }
                    
                    let createdBy = log.created_by_name || 'System';
                    
                    let html = `
                        <div class="log-item ${logTypeClass}">
                            <div class="row">
                                <div class="col-md-8">
                                    <span class="log-type-badge ${badgeClass}">${logTypeText}</span>
                                    <strong style="margin-left: 10px;">${log.product_name || 'Unknown Product'}</strong>
                                    ${serialInfo}
                                    <div class="log-meta">
                                        <i class="fas fa-clock"></i> ${log.created_at}
                                        <i class="fas fa-user" style="margin-left: 15px;"></i> ${createdBy}
                                        ${orderInfo}
                                        ${referenceInfo}
                                        ${notesInfo}
                                    </div>
                                </div>
                                <div class="col-md-4 text-end">
                                    <div class="quantity-change ${changeClass}">
                                        ${changeIcon} ${changeText}
                                    </div>
                                    <div style="font-size: 14px; color: #6c757d; margin-top: 5px;">
                                        ${log.quantity_before} → ${log.quantity_after}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    container.append(html);
                });
            }
            
            // Load logs on page load (today's logs)
            $('.quick-filter-btn[data-days="0"]').click();
        });
    </script>
    
    <script src='../js/index_.js?v=<?php echo time(); ?>'></script>
</body>
</html>