<?php
require_once('lib/connect.php');
global $conn;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Generate session_id for guest users
if (!isset($_SESSION['guest_session_id'])) {
    $_SESSION['guest_session_id'] = session_id();
}

// Get order_id from URL
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    header('Location: ?profile');
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <?php include 'template/header.php' ?>
    <?php include 'inc_head.php' ?>
    <link href="app/css/index_.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            padding-top: 100px;
        }

        .order-detail-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #666;
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 20px;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: #000;
        }

        .order-header {
            background: white;
            border-radius: 12px;
            padding: 30px 40px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .order-header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #000;
        }

        .order-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
        }

        .meta-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 5px;
        }

        .meta-value {
            font-size: 16px;
            font-weight: 500;
            color: #000;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-badge.pending { background: #fff3cd; color: #856404; }
        .status-badge.processing { background: #cfe2ff; color: #084298; }
        .status-badge.shipped { background: #d1e7dd; color: #0f5132; }
        .status-badge.delivered { background: #d1e7dd; color: #0a3622; }
        .status-badge.cancelled { background: #f8d7da; color: #842029; }
        .status-badge.paid { background: #d1e7dd; color: #0f5132; }
        .status-badge.unpaid { background: #f8d7da; color: #842029; }

        .section-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #000;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Product Items */
        .product-items {
            display: grid;
            gap: 15px;
        }

        .product-item {
            display: grid;
            grid-template-columns: 100px 1fr auto;
            gap: 20px;
            padding: 20px;
            border: 1px solid #f0f0f0;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .product-item:hover {
            border-color: #ddd;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .products-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            background: #f5f5f5;
        }

        .product-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .product-name {
            font-size: 16px;
            font-weight: 500;
            color: #000;
            margin-bottom: 8px;
        }

        .product-quantity {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        .product-unit-price {
            font-size: 13px;
            color: #999;
        }

        .product-price {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-end;
            text-align: right;
        }

        .product-total {
            font-size: 20px;
            font-weight: 600;
            color: #000;
        }

        /* Shipping Address */
        .address-box {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            line-height: 1.8;
        }

        .address-box strong {
            display: block;
            font-size: 16px;
            margin-bottom: 8px;
            color: #000;
        }

        /* Order Summary */
        .order-summary {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 15px;
            color: #666;
        }

        .summary-row.total {
            font-size: 20px;
            font-weight: 600;
            color: #000;
            padding-top: 15px;
            border-top: 2px solid #ddd;
            margin-top: 10px;
        }

        /* Actions */
        .order-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: opacity 0.3s;
            text-decoration: none;
            display: inline-block;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .btn-primary {
            background: #000;
            color: white;
        }

        .btn-secondary {
            background: white;
            color: #000;
            border: 1px solid #000;
        }

        .btn:hover {
            opacity: 0.8;
        }

        .loading {
            text-align: center;
            padding: 100px 20px;
        }

        .loading i {
            font-size: 50px;
            color: #ccc;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .product-item {
                grid-template-columns: 80px 1fr;
            }

            .product-price {
                grid-column: 2;
                align-items: flex-start;
                margin-top: 10px;
            }

            .order-meta {
                grid-template-columns: 1fr;
            }
        }
        .slip-row{
            margin-bottom: 10px;
        }
        /* เพิ่มใน <style> */
        .meta-value {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .meta-value .btn {
            padding: 8px 16px;
            font-size: 12px;
            white-space: nowrap;
        }
    </style>
</head>
<body>

    <div class="order-detail-container">
        <a href="?profile" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Profile
        </a>

        <div id="orderContent">
            <div class="loading">
                <i class="fas fa-spinner"></i>
                <p style="margin-top: 20px; color: #999;">Loading order details...</p>
            </div>
        </div>
    </div>

    <?php include 'template/footermini.php' ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const jwt = sessionStorage.getItem("jwt");
        const orderId = <?= $order_id ?>;

        // ตรวจสอบว่าล็อกอินหรือไม่
        if (!jwt) {
            window.location.href = '?';
        }

        // โหลดรายละเอียด Order
        function loadOrderDetail() {
            $.ajax({
                url: 'app/actions/get_order_detail.php',
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + jwt
                },
                data: {
                    order_id: orderId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        displayOrderDetail(response.data);
                    } else {
                        displayError(response.message);
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 401) {
                        sessionStorage.removeItem('jwt');
                        window.location.href = '?';
                    } else {
                        displayError('Failed to load order details');
                    }
                }
            });
        }

        function displayOrderDetail(order) {
            const orderDate = new Date(order.date_created).toLocaleDateString('en-GB', {
                day: '2-digit',
                month: 'long',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            // สร้าง HTML สำหรับรายการสินค้า
            let itemsHtml = '';
            if (order.items && order.items.length > 0) {
                order.items.forEach(function(item) {
                    itemsHtml += `
                        <div class="product-item">
                            <img src="${item.product_image || 'public/img/no-image.png'}" 
                                 alt="${item.product_name}" 
                                 class="products-image"
                                 onerror="this.src='public/img/no-image.png'">
                            <div class="product-info">
                                <div class="product-name">${item.product_name}</div>
                                <div class="product-quantity">Quantity: ${item.quantity}</div>
                                <div class="product-unit-price">฿${parseFloat(item.unit_price_with_vat).toLocaleString('en-US', {minimumFractionDigits: 2})} per unit</div>
                            </div>
                            <div class="product-price">
                                <div class="product-total">฿${parseFloat(item.total).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                            </div>
                        </div>
                    `;
                });
            }

            // สร้าง HTML สำหรับที่อยู่จัดส่ง
            let shippingHtml = '';
            if (order.shipping_address) {
                const addr = order.shipping_address;
                shippingHtml = `
                    <div class="address-box">
                        <strong>${addr.recipient_name}</strong>
                        ${addr.recipient_phone}<br>
                        ${addr.address_line1}${addr.address_line2 ? ', ' + addr.address_line2 : ''}<br>
                        ${addr.subdistrict}, ${addr.district}<br>
                        ${addr.province} ${addr.postal_code}
                    </div>
                `;
            }

            // ✅ สร้าง HTML สำหรับหลักฐานการโอนเงิน
            let paymentSlipHtml = '';
            if (order.payment_slip) {
                const slip = order.payment_slip;
                const uploadDate = new Date(slip.date_uploaded).toLocaleDateString('en-GB', {
                    day: '2-digit',
                    month: 'long',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                const transferDate = slip.transfer_date ? new Date(slip.transfer_date).toLocaleDateString('en-GB', {
                    day: '2-digit',
                    month: 'long',
                    year: 'numeric'
                }) : '-';

                const statusClass = slip.status === 'verified' ? 'paid' : 
                                slip.status === 'rejected' ? 'cancelled' : 'pending';
                const statusLabel = slip.status === 'verified' ? 'Verified' : 
                                slip.status === 'rejected' ? 'Rejected' : 'Pending Verification';

                paymentSlipHtml = `
                    <div class="section-card">
                        <div class="section-title">
                            <i class="fas fa-receipt"></i> Payment Slip
                        </div>
                        <div class="payment-slip-container">
                            <div class="slip-info">
                                <div class="slip-row">
                                    <span class="slip-label">Status:</span>
                                    <span class="status-badge ${statusClass}">${statusLabel}</span>
                                </div>
                                <div class="slip-row">
                                    <span class="slip-label">Transfer Amount:</span>
                                    <span class="slip-value">฿${parseFloat(slip.transfer_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                                </div>
                                <div class="slip-row">
                                    <span class="slip-label">Transfer Date:</span>
                                    <span class="slip-value">${transferDate}</span>
                                </div>
                                <div class="slip-row">
                                    <span class="slip-label">Uploaded:</span>
                                    <span class="slip-value">${uploadDate}</span>
                                </div>
                                ${slip.notes ? `
                                <div class="slip-row">
                                    <span class="slip-label">Notes:</span>
                                    <span class="slip-value">${slip.notes}</span>
                                </div>
                                ` : ''}
                            </div>
                            <div class="slip-actions">
                                <button class="btn btn-primary" onclick="viewPaymentSlip('${slip.file_path}')">
                                    <i class="fas fa-eye"></i> View Payment Slip
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }

            const html = `
                <!-- Order Header -->
                <div class="order-header">
                    <h1><i class="fas fa-receipt"></i> Order #${order.order_number}</h1>
                    <div class="order-meta">
                        <div class="meta-item">
                            <div class="meta-label">Order Date</div>
                            <div class="meta-value">${orderDate}</div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Order Status</div>
                            <div class="meta-value">
                                <span class="status-badge ${order.order_status}">${order.order_status_label || order.order_status}</span>
                            </div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Payment Status</div>
                            <div class="meta-value">
                                <span class="status-badge ${order.payment_status}">${order.payment_status_label || order.payment_status}</span>
                                ${order.payment_status === 'pending' ? `
                                    <button class="btn btn-primary" onclick="payOrder(${order.order_id})">
                                        <i class="fas fa-credit-card"></i> Pay Now
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Payment Method</div>
                            <div class="meta-value">${getPaymentMethodLabel(order.payment_method)}</div>
                        </div>
                    </div>
                </div>

                <!-- Product Items -->
                <div class="section-card">
                    <div class="section-title">
                        <i class="fas fa-box"></i> Order Items
                    </div>
                    <div class="product-items">
                        ${itemsHtml}
                    </div>
                </div>

                <!-- Shipping Address -->
                ${order.shipping_address ? `
                <div class="section-card">
                    <div class="section-title">
                        <i class="fas fa-truck"></i> Shipping Address
                    </div>
                    ${shippingHtml}
                </div>
                ` : ''}

                ${paymentSlipHtml}

                <!-- Order Summary -->
                <div class="section-card">
                    <div class="section-title">
                        <i class="fas fa-file-invoice-dollar"></i> Order Summary
                    </div>
                    <div class="order-summary">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span>฿${parseFloat(order.subtotal).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                        </div>
                        <div class="summary-row">
                            <span>VAT (${parseFloat(order.vat_amount / order.subtotal * 100).toFixed(0)}%)</span>
                            <span>฿${parseFloat(order.vat_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                        </div>
                        ${order.shipping_fee > 0 ? `
                            <div class="summary-row">
                                <span>Shipping Fee</span>
                                <span>฿${parseFloat(order.shipping_fee).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                            </div>
                        ` : ''}
                        ${order.discount_amount > 0 ? `
                            <div class="summary-row">
                                <span>Discount ${order.coupon_code ? '(' + order.coupon_code + ')' : ''}</span>
                                <span style="color: #28a745;">-฿${parseFloat(order.discount_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                            </div>
                        ` : ''}
                        <div class="summary-row total">
                            <span>Total Amount</span>
                            <span>฿${parseFloat(order.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="order-actions">
                        ${(order.order_status === 'pending' || order.order_status === 'processing') && order.payment_status === 'unpaid' ? `
                            <button class="btn btn-primary" onclick="payOrder(${order.order_id})">
                                <i class="fas fa-credit-card"></i> Pay Now
                            </button>
                        ` : ''}
                        ${order.order_status === 'delivered' ? `
                            <button class="btn btn-primary" onclick="reorder(${order.order_id})">
                                <i class="fas fa-redo"></i> Reorder
                            </button>
                        ` : ''}
                        <button class="btn btn-secondary" onclick="window.print()">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>
            `;

            $('#orderContent').html(html);
        }

        // ✅ ฟังก์ชันดูหลักฐานการโอนเงิน
        function viewPaymentSlip(filePath) {
            Swal.fire({
                title: 'Payment Slip',
                imageUrl: filePath,
                imageWidth: '100%',
                imageAlt: 'Payment Slip',
                showCloseButton: true,
                showConfirmButton: false,
                width: '40%',
                customClass: {
                    image: 'slip-modal-image'
                }
            });
        }

        function displayError(message) {
            $('#orderContent').html(`
                <div style="text-align: center; padding: 100px 20px;">
                    <i class="fas fa-exclamation-circle" style="font-size: 64px; color: #dc3545; margin-bottom: 20px;"></i>
                    <h3 style="font-size: 24px; color: #666; margin-bottom: 15px;">Error</h3>
                    <p style="font-size: 16px; color: #999; margin-bottom: 30px;">${message}</p>
                    <a href="?profile" class="btn btn-primary">Back to Profile</a>
                </div>
            `);
        }

        function getPaymentMethodLabel(method) {
            const methods = {
                'bank_transfer': 'Bank Transfer',
                'credit_card': 'Credit Card',
                'cash_on_delivery': 'Cash on Delivery'
            };
            return methods[method] || method;
        }

        function payOrder(orderId) {
            Swal.fire({
                title: 'Proceed to Payment?',
                text: 'You will be redirected to the payment page',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#000',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, pay now!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '?payment&order_id=' + orderId;
                }
            });
        }

        function reorder(orderId) {
            Swal.fire({
                title: 'Reorder this?',
                text: 'Items from this order will be added to your cart',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#000',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, add to cart!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'app/actions/reorder.php',
                        type: 'POST',
                        headers: {
                            'Authorization': 'Bearer ' + jwt
                        },
                        data: {
                            order_id: orderId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire('Success!', 'Items added to cart', 'success').then(() => {
                                    window.location.href = '?cart';
                                });
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error!', 'Failed to add items to cart', 'error');
                        }
                    });
                }
            });
        }

        // Initialize
        $(document).ready(function() {
            loadOrderDetail();
        });
    </script>
</body>
</html>