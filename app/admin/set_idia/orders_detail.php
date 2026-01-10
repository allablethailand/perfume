<?php
include '../check_permission.php';
require_once(__DIR__ . '/../../../lib/connect.php');

global $conn;

// รับ order_id จาก POST
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

if ($order_id <= 0) {
    header('Location: list_idia.php');
    exit;
}

// ดึงข้อมูล order พร้อม payment slip
$stmt = $conn->prepare("SELECT o.*, 
                        u.first_name, u.last_name, u.email, u.phone_number, u.profile_img,
                        ps.slip_id, ps.file_path as slip_image, ps.transfer_amount, 
                        ps.transfer_date, ps.notes as slip_notes
                        FROM orders o 
                        LEFT JOIN mb_user u ON o.user_id = u.user_id 
                        LEFT JOIN payment_slips ps ON o.order_id = ps.order_id
                        WHERE o.order_id = ? AND o.del = 0");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result->num_rows === 0) {
    header('Location: list_idia.php');
    exit;
}

$order = $order_result->fetch_assoc();
$stmt->close();

// ดึงรายการสินค้า
$stmt_items = $conn->prepare("SELECT 
                              oi.*, 
                              p.name_th, p.name_en, p.stock_quantity,
                              pi.api_path as product_image
                              FROM order_items oi
                              LEFT JOIN products p ON oi.product_id = p.product_id
                              LEFT JOIN product_images pi ON p.product_id = pi.product_id 
                                  AND pi.is_primary = 1 AND pi.del = 0
                              WHERE oi.order_id = ?
                              ORDER BY oi.order_item_id");
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$items_result = $stmt_items->get_result();
$items = $items_result->fetch_all(MYSQLI_ASSOC);
$stmt_items->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดคำสั่งซื้อ #<?= htmlspecialchars($order['order_number']) ?></title>

    <link rel="icon" type="image/x-icon" href="../../../public/product_images/695e0bf362d49_1767771123.jpg">
    <link href="../../../inc/jquery/css/jquery-ui.css" rel="stylesheet">
    <script src="../../../inc/jquery/js/jquery-3.6.0.min.js"></script>
    <script src="../../../inc/jquery/js/jquery-ui.min.js"></script>
    <link href="../../../inc/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="../../../inc/bootstrap/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="../../../inc/sweetalert2/css/sweetalert2.min.css" rel="stylesheet">
    <script src="../../../inc/sweetalert2/js/sweetalert2.all.min.js"></script>
    <link href='../css/index_.css?v=<?php echo time(); ?>' rel='stylesheet'>
    
    <style>
        body {
            background: #f5f7fa;
            font-family: 'Kanit', sans-serif;
        }

        .detail-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .back-button {
            background: white;
            border: 2px solid #e2e8f0;
            padding: 10px 20px;
            border-radius: 10px;
            color: #667eea;
            font-weight: 600;
            transition: all 0.3s;
            margin-bottom: 20px;
        }

        .back-button:hover {
            background: #667eea;
            color: white;
            transform: translateX(-5px);
        }

        .order-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            border-radius: 15px;
            color: white;
            margin-bottom: 25px;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .order-header h2 {
            margin: 0 0 10px 0;
            font-weight: 600;
        }

        .order-meta {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
            margin-top: 15px;
        }

        .order-meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-modern {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .customer-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .customer-avatar-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #667eea;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .customer-info-detail {
            flex: 1;
        }

        .customer-name-large {
            font-size: 20px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .customer-contact {
            color: #718096;
            margin: 5px 0;
        }

        .status-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .status-card {
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }

        .status-card-title {
            font-size: 13px;
            color: #718096;
            margin-bottom: 8px;
        }

        .status-dropdown {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px;
            font-weight: 600;
            font-size: 14px;
            width: 100%;
        }

        .product-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            border: 1px solid #f0f0f0;
            border-radius: 10px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }

        .product-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }

        .product-image {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid #f0f0f0;
        }

        .product-details {
            flex: 1;
        }

        .product-name {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .product-price {
            color: #718096;
            font-size: 14px;
        }

        .product-quantity {
            background: #f0f0f0;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
        }

        .summary-table {
            width: 100%;
            margin-top: 20px;
        }

        .summary-table tr td {
            padding: 10px;
        }

        .summary-table tr:last-child {
            border-top: 2px solid #667eea;
            font-weight: 600;
            font-size: 18px;
            color: #667eea;
        }

        .slip-section {
            text-align: center;
            padding: 20px;
        }

        .slip-preview {
            max-width: 400px;
            margin: 20px auto;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .slip-preview img {
            width: 100%;
            height: auto;
            display: block;
        }

        .btn-view-slip-large {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            margin-top: 15px;
            transition: all 0.3s;
        }

        .btn-view-slip-large:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
        }

        .no-slip-message {
            text-align: center;
            padding: 40px;
            color: #718096;
        }
    </style>
</head>

<?php include '../template/header.php' ?>

<body>
    <div class="detail-container">
        <!-- Back Button -->
        <button onclick="window.location.href='list_idia.php'" class="back-button">
            <i class="fas fa-arrow-left"></i> กลับไปหน้ารายการคำสั่งซื้อ
        </button>

        <!-- Order Header -->
        <div class="order-header">
            <h2>
                <i class="fas fa-shopping-bag"></i>
                คำสั่งซื้อ #<?= htmlspecialchars($order['order_number']) ?>
            </h2>
            <div class="order-meta">
                <div class="order-meta-item">
                    <i class="fas fa-calendar"></i>
                    <span>สั่งซื้อเมื่อ: <?= htmlspecialchars($order['date_created']) ?></span>
                </div>
                <?php if ($order['date_updated']): ?>
                <div class="order-meta-item">
                    <i class="fas fa-clock"></i>
                    <span>อัปเดตล่าสุด: <?= htmlspecialchars($order['date_updated']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <!-- Customer Info -->
                <div class="card-modern">
                    <div class="card-title">
                        <i class="fas fa-user-circle" style="color: #667eea;"></i>
                        ข้อมูลลูกค้า
                    </div>
                    <div class="customer-section">
                        <?php 
                        $avatar_html = '';
                        if ($order['profile_img']) {
                            $avatar_html = '<img src="' . htmlspecialchars($order['profile_img']) . '" class="customer-avatar-large" alt="Avatar">';
                        } else {
                            $initial = $order['first_name'] ? strtoupper(substr($order['first_name'], 0, 1)) : '?';
                            $avatar_html = '<div class="customer-avatar-large" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 600;">' . $initial . '</div>';
                        }
                        echo $avatar_html;
                        ?>
                        <div class="customer-info-detail">
                            <div class="customer-name-large">
                                <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?>
                            </div>
                            <div class="customer-contact">
                                <i class="fas fa-envelope"></i> <?= htmlspecialchars($order['email']) ?>
                            </div>
                            <?php if ($order['phone_number']): ?>
                            <div class="customer-contact">
                                <i class="fas fa-phone"></i> <?= htmlspecialchars($order['phone_number']) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Order Status -->
                <div class="card-modern">
                    <div class="card-title">
                        <i class="fas fa-tasks" style="color: #667eea;"></i>
                        สถานะคำสั่งซื้อ
                    </div>
                    <div class="status-section">
                        <div class="status-card" style="background: #f8f9ff; border: 2px solid #e0e7ff;">
                            <div class="status-card-title">สถานะคำสั่งซื้อ</div>
                            <select class="status-dropdown change-order-status" data-order-id="<?= $order['order_id'] ?>">
                                <option value="pending" <?= $order['order_status'] === 'pending' ? 'selected' : '' ?>>🟡 Pending</option>
                                <option value="processing" <?= $order['order_status'] === 'processing' ? 'selected' : '' ?>>🔵 Processing</option>
                                <option value="confirmed" <?= $order['order_status'] === 'confirmed' ? 'selected' : '' ?>>🟢 confirmed</option>
                                <option value="shipped" <?= $order['order_status'] === 'shipped' ? 'selected' : '' ?>>🚚 Shipped</option>
                                <option value="cancelled" <?= $order['order_status'] === 'cancelled' ? 'selected' : '' ?>>🔴 Cancelled</option>
                            </select>
                        </div>
                        <div class="status-card" style="background: #f0fdf4; border: 2px solid #d1fae5;">
                            <div class="status-card-title">สถานะการชำระเงิน</div>
                            <?php
                            $payment_badges = [
                                'pending' => '<span class="badge" style="background: #ffd93d; color: #333;">⏳ Pending</span>',
                                'paid' => '<span class="badge" style="background: #6dd5b1; color: white;">✓ Paid</span>',
                                'failed' => '<span class="badge" style="background: #f56565; color: white;">✗ Failed</span>',
                                'refunded' => '<span class="badge" style="background: #6bcfff; color: white;">↩ Refunded</span>'
                            ];
                            echo $payment_badges[$order['payment_status']] ?? '<span class="badge" style="background: #cbd5e0;">? Unknown</span>';
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="card-modern">
                    <div class="card-title">
                        <i class="fas fa-box" style="color: #667eea;"></i>
                        รายการสินค้า (<?= count($items) ?> รายการ)
                    </div>
                    <?php foreach ($items as $item): ?>
                    <div class="product-item">
                        <?php if ($item['product_image']): ?>
                            <img src="<?= htmlspecialchars($item['product_image']) ?>" class="product-image" alt="Product">
                        <?php else: ?>
                            <div class="product-image" style="background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
                                <i class="fas fa-image"></i>
                            </div>
                        <?php endif; ?>
                        <div class="product-details">
                            <div class="product-name"><?= htmlspecialchars($item['name_th']) ?></div>
                            <div class="product-price">
                                ราคา: <?= number_format($item['unit_price'], 2) ?> ฿ × 
                                <span class="product-quantity"><?= $item['quantity'] ?> ชิ้น</span>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <strong style="color: #667eea; font-size: 18px;">
                                <?= number_format($item['total'], 2) ?> ฿
                            </strong>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Payment Slip -->
                <?php if (($order['payment_status'] === 'paid' || $order['order_status'] === 'processing') && $order['slip_image']): ?>
                <div class="card-modern">
                    <div class="card-title">
                        <i class="fas fa-receipt" style="color: #667eea;"></i>
                        สลิปการชำระเงิน
                    </div>
                    <div class="slip-section">
                        <div class="slip-preview">
                            <img src="../../../<?= htmlspecialchars($order['slip_image']) ?>" alt="Payment Slip">
                        </div>
                        <button class="btn-view-slip-large" onclick="viewSlipLarge('../../../<?= htmlspecialchars($order['slip_image']) ?>')">
                            <i class="fas fa-search-plus"></i> ดูขนาดเต็ม
                        </button>
                        <?php if ($order['slip_notes']): ?>
                        <div style="margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 10px; text-align: left;">
                            <strong>หมายเหตุ:</strong><br>
                            <?= nl2br(htmlspecialchars($order['slip_notes'])) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="card-modern">
                    <div class="card-title">
                        <i class="fas fa-receipt" style="color: #667eea;"></i>
                        สลิปการชำระเงิน
                    </div>
                    <div class="no-slip-message">
                        <i class="fas fa-file-invoice" style="font-size: 48px; color: #cbd5e0; margin-bottom: 15px;"></i>
                        <p>ยังไม่มีการแนบสลิปการชำระเงิน</p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Order Summary -->
                <div class="card-modern">
                    <div class="card-title">
                        <i class="fas fa-calculator" style="color: #667eea;"></i>
                        สรุปยอดชำระ
                    </div>
                    <table class="summary-table">
                        <tr>
                            <td>ยอดรวมสินค้า</td>
                            <td style="text-align: right;"><?= number_format($order['subtotal'], 2) ?> ฿</td>
                        </tr>
                        <tr>
                            <td>ภาษี VAT</td>
                            <td style="text-align: right;"><?= number_format($order['vat_amount'], 2) ?> ฿</td>
                        </tr>
                        <?php if ($order['shipping_fee'] > 0): ?>
                        <tr>
                            <td>ค่าจัดส่ง</td>
                            <td style="text-align: right;"><?= number_format($order['shipping_fee'], 2) ?> ฿</td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($order['discount_amount'] > 0): ?>
                        <tr style="color: #f56565;">
                            <td>ส่วนลด</td>
                            <td style="text-align: right;">-<?= number_format($order['discount_amount'], 2) ?> ฿</td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td>ยอดชำระทั้งหมด</td>
                            <td style="text-align: right;"><?= number_format($order['total_amount'], 2) ?> ฿</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function viewSlipLarge(slipPath) {
            Swal.fire({
                title: '<span style="color: #667eea;">💳 สลิปการชำระเงิน</span>',
                imageUrl: slipPath,
                imageAlt: 'Payment Slip',
                width: 800,
                showCloseButton: true,
                showConfirmButton: false,
                background: '#fff',
                backdrop: 'rgba(102, 126, 234, 0.4)'
            });
        }

        // Change Order Status
        $('.change-order-status').on('focus', function() {
            $(this).data('old-value', $(this).val());
        });

        $('.change-order-status').on('change', function() {
            const orderId = $(this).data('order-id');
            const newStatus = $(this).val();
            const oldStatus = $(this).data('old-value');
            const selectElement = $(this);

            const statusText = {
                'pending': 'Pending',
                'processing': 'Processing',
                'confirmed': 'confirmed',
                'shipped': 'Shipped',
                'cancelled': 'Cancelled'
            };

            Swal.fire({
                title: '🔄 ยืนยันการเปลี่ยนสถานะ',
                html: `ต้องการเปลี่ยนสถานะเป็น <strong style="color: #667eea;">"${statusText[newStatus]}"</strong> หรือไม่?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#667eea',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '✓ ยืนยัน',
                cancelButtonText: '✗ ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'actions/process_orders.php',
                        type: 'POST',
                        data: {
                            action: 'updateOrderStatus',
                            order_id: orderId,
                            order_status: newStatus
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: '✓ สำเร็จ!',
                                    text: response.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', response.message, 'error');
                                selectElement.val(oldStatus);
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'เกิดข้อผิดพลาดในการเปลี่ยนสถานะ', 'error');
                            selectElement.val(oldStatus);
                        }
                    });
                } else {
                    selectElement.val(oldStatus);
                }
            });
        });
    </script>
</body>
</html>