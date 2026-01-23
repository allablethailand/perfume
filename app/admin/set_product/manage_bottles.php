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

// ดึงข้อมูลกลุ่ม
$stmt = $conn->prepare("SELECT * FROM product_groups WHERE group_id = ? AND del = 0");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('Group not found'); window.location.href='list_shop.php';</script>";
    exit;
}

$group = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการขวด - <?= htmlspecialchars($group['name_th']) ?></title>

    <link rel="icon" type="image/x-icon" href="../../../public/product_images/695e0bf362d49_1767771123.jpg">
    <link href="../../../inc/jquery/css/jquery-ui.css" rel="stylesheet">
    <script src="../../../inc/jquery/js/jquery-3.6.0.min.js"></script>
    <script src="../../../inc/jquery/js/jquery-ui.min.js"></script>
    <link href="../../../inc/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="../../../inc/bootstrap/js/bootstrap.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/fontawesome5-fullcss@1.1.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="../../../inc/sweetalert2/css/sweetalert2.min.css" rel="stylesheet">
    <script src="../../../inc/sweetalert2/js/sweetalert2.all.min.js"></script>
    <link href="https://cdn.datatables.net/v/dt/dt-2.1.4/datatables.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/v/dt/dt-2.1.4/datatables.min.js"></script>
    <link href='../css/index_.css?v=<?php echo time(); ?>' rel='stylesheet'>
    
    <style>
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-available {
            background-color: #28a745;
            color: white;
        }
        .status-reserved {
            background-color: #ffc107;
            color: #212529;
        }
        .status-sold {
            background-color: #6c757d;
            color: white;
        }
        .serial-number {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 14px;
            color: #667eea;
        }
        .btn-circle {
            border: none;
            width: 30px;
            height: 28px;
            border-radius: 50%;
            font-size: 14px;
        }
        .btn-del {
            background-color: #ff4537;
            color: #ffffff;
        }
        .info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
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
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <h4 class="line-ref mb-0"> 
                                    <i class="fas fa-wine-bottle"></i>   
                                    จัดการขวด: <?= htmlspecialchars($group['name_th']) ?>
                                </h4>
                                <div>
                                    <button type='button' class='btn btn-primary' onclick="showAddBottlesModal()">
                                        <i class='fas fa-plus'></i>
                                        เพิ่มขวดใหม่
                                    </button>
                                    <button type='button' class='btn btn-secondary' onclick="window.location.href='list_shop.php'">
                                        <i class='fas fa-arrow-left'></i>
                                        กลับ
                                    </button>
                                </div>
                            </div>

                            <div class="info-card">
                                <div class="row text-center">
                                    <div class="col-md-3">
                                        <h3 class="mb-0" id="stat-total">-</h3>
                                        <small>ทั้งหมด</small>
                                    </div>
                                    <div class="col-md-3">
                                        <h3 class="mb-0" id="stat-available">-</h3>
                                        <small>พร้อมขาย</small>
                                    </div>
                                    <div class="col-md-3">
                                        <h3 class="mb-0" id="stat-reserved">-</h3>
                                        <small>จองแล้ว</small>
                                    </div>
                                    <div class="col-md-3">
                                        <h3 class="mb-0" id="stat-sold">-</h3>
                                        <small>ขายแล้ว</small>
                                    </div>
                                </div>
                            </div>
                            
                            <table id="td_bottles" class="table table-hover" style="width:100%;">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>รหัสขวด</th>
                                        <th>สถานะ</th>
                                        <th>จองเมื่อ</th>
                                        <th>ขายเมื่อ</th>
                                        <th>Order ID</th>
                                        <th>สร้างเมื่อ</th>
                                        <th>จัดการ</th>
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

    <!-- Modal: เพิ่มขวดใหม่ -->
    <div class="modal fade" id="addBottlesModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">เพิ่มขวดใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Prefix รหัสขวด</label>
                        <input type="text" class="form-control" id="new_prefix" placeholder="เช่น ROSE" required>
                        <small class="text-muted">รหัสจะเป็น PREFIX-XXX</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">เริ่มต้นที่เลข</label>
                        <input type="number" class="form-control" id="start_number" value="1" min="1" required>
                        <small class="text-muted">ระบบจะเริ่มนับจากเลขนี้</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">จำนวนขวดที่ต้องการเพิ่ม</label>
                        <input type="number" class="form-control" id="add_quantity" value="10" min="1" max="1000" required>
                    </div>
                    <div class="alert alert-info">
                        <strong>ตัวอย่าง:</strong> Prefix "ROSE", เริ่มต้น 101, จำนวน 10<br>
                        จะสร้าง: ROSE-101, ROSE-102, ..., ROSE-110
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" onclick="addBottles()">เพิ่มขวด</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const GROUP_ID = <?= $group_id ?>;
        
        $(document).ready(function() {
            loadBottles();
            loadStats();
        });

        function loadBottles() {
            if ($.fn.DataTable.isDataTable('#td_bottles')) {
                $('#td_bottles').DataTable().destroy();
            }

            $('#td_bottles').DataTable({
                "autoWidth": false,
                "processing": true,
                "serverSide": true,
                ajax: {
                    url: "actions/process_product_groups.php",
                    method: 'POST',
                    data: function(d) {
                        d.action = 'getData_bottles';
                        d.group_id = GROUP_ID;
                    }
                },
                "ordering": true,
                "order": [[1, 'asc']],
                "pageLength": 50,
                "lengthMenu": [25, 50, 100, 500],
                columnDefs: [
                    {
                        "target": 0,
                        data: null,
                        render: function(data, type, row, meta) {
                            return meta.row + 1;
                        }
                    },
                    {
                        "target": 1,
                        data: "serial_number",
                        render: function(data) {
                            return `<span class="serial-number">${data}</span>`;
                        }
                    },
                    {
                        "target": 2,
                        data: "status",
                        render: function(data) {
                            let className = 'status-available';
                            let text = 'พร้อมขาย';
                            
                            if (data === 'reserved') {
                                className = 'status-reserved';
                                text = 'จองแล้ว';
                            } else if (data === 'sold') {
                                className = 'status-sold';
                                text = 'ขายแล้ว';
                            }
                            
                            return `<span class="status-badge ${className}">${text}</span>`;
                        }
                    },
                    {
                        "target": 3,
                        data: "reserved_at",
                        render: function(data) {
                            return data || '-';
                        }
                    },
                    {
                        "target": 4,
                        data: "sold_at",
                        render: function(data) {
                            return data || '-';
                        }
                    },
                    {
                        "target": 5,
                        data: "order_id",
                        render: function(data) {
                            return data || '-';
                        }
                    },
                    {
                        "target": 6,
                        data: "created_at"
                    },
                    {
                        "target": 7,
                        data: null,
                        render: function(data, type, row) {
                            if (row.status === 'sold') {
                                return '<span class="text-muted">ขายแล้ว</span>';
                            }
                            
                            return `
                                <button type="button" class="btn-circle btn-del" onclick="deleteBottle(${row.item_id}, '${row.serial_number}')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            `;
                        }
                    }
                ]
            });
        }

        function loadStats() {
            $.ajax({
                url: 'actions/process_product_groups.php',
                type: 'POST',
                data: {
                    action: 'getBottleStats',
                    group_id: GROUP_ID
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        $('#stat-total').text(response.stats.total);
                        $('#stat-available').text(response.stats.available);
                        $('#stat-reserved').text(response.stats.reserved);
                        $('#stat-sold').text(response.stats.sold);
                    }
                }
            });
        }

        function showAddBottlesModal() {
            $('#addBottlesModal').modal('show');
        }

        function addBottles() {
            const prefix = $('#new_prefix').val().trim().toUpperCase();
            const startNum = parseInt($('#start_number').val());
            const quantity = parseInt($('#add_quantity').val());

            if (!prefix) {
                Swal.fire('Error', 'กรุณากรอก Prefix', 'error');
                return;
            }

            if (quantity < 1 || quantity > 1000) {
                Swal.fire('Error', 'จำนวนต้องอยู่ระหว่าง 1-1000', 'error');
                return;
            }

            $.ajax({
                url: 'actions/process_product_groups.php',
                type: 'POST',
                data: {
                    action: 'addBottles',
                    group_id: GROUP_ID,
                    prefix: prefix,
                    start_number: startNum,
                    quantity: quantity
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        $('#addBottlesModal').modal('hide');
                        Swal.fire('สำเร็จ!', response.message, 'success');
                        loadBottles();
                        loadStats();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'เพิ่มขวดไม่สำเร็จ', 'error');
                }
            });
        }

        function deleteBottle(itemId, serialNumber) {
            Swal.fire({
                title: 'ลบขวดนี้?',
                html: `รหัส: <strong>${serialNumber}</strong>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'ใช่, ลบเลย!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'actions/process_product_groups.php',
                        type: 'POST',
                        data: {
                            action: 'deleteBottle',
                            item_id: itemId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire('ลบแล้ว!', response.message, 'success');
                                loadBottles();
                                loadStats();
                            } else {
                                Swal.fire('Error', response.message, 'error');
                            }
                        }
                    });
                }
            });
        }
    </script>

    <script src='../js/index_.js?v=<?php echo time(); ?>'></script>
</body>
</html>