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
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <?php include 'inc_head.php' ?>
    <?php include 'template/header.php' ?>
    <link href="app/css/index_.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            padding-top: 100px;
        }

        .orders-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .orders-header {
            background: white;
            border-radius: 12px;
            padding: 30px 40px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .orders-header h1 {
            font-size: 32px;
            font-weight: 600;
            margin: 0 0 10px 0;
            color: #000;
        }

        .orders-header p {
            color: #666;
            margin: 0;
        }

        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 20px 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-section select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            min-width: 180px;
            cursor: pointer;
        }

        .filter-section select:focus {
            outline: none;
            border-color: #000;
        }

        .order-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 20px;
        }

        .order-info {
            flex: 1;
        }

        .order-number {
            font-size: 18px;
            font-weight: 600;
            color: #000;
            margin-bottom: 8px;
        }

        .order-date {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }

        .order-status-badges {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.processing {
            background: #cfe2ff;
            color: #084298;
        }

        .status-badge.shipped {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-badge.delivered {
            background: #d1e7dd;
            color: #0a3622;
        }

        .status-badge.cancelled {
            background: #f8d7da;
            color: #842029;
        }

        .status-badge.paid {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-badge.unpaid {
            background: #f8d7da;
            color: #842029;
        }

        .order-total {
            text-align: right;
        }

        .total-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 5px;
        }

        .total-amount {
            font-size: 24px;
            font-weight: 600;
            color: #000;
        }

        .order-items {
            margin-bottom: 20px;
        }

        .order-item {
            display: grid;
            grid-template-columns: 80px 1fr auto;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            background: #f5f5f5;
        }

        .item-details {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .item-name {
            font-size: 16px;
            font-weight: 500;
            color: #000;
            margin-bottom: 5px;
        }

        .item-quantity {
            font-size: 14px;
            color: #666;
        }

        .item-price-section {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-end;
        }

        .item-unit-price {
            font-size: 14px;
            color: #666;
            margin-bottom: 3px;
        }

        .item-total-price {
            font-size: 18px;
            font-weight: 600;
            color: #000;
        }

        .order-shipping {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .shipping-title {
            font-size: 14px;
            font-weight: 600;
            color: #000;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .shipping-details {
            font-size: 14px;
            color: #555;
            line-height: 1.6;
        }

        .order-summary {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #666;
        }

        .summary-row.total {
            font-size: 18px;
            font-weight: 600;
            color: #000;
            padding-top: 10px;
            border-top: 2px solid #ddd;
        }

        .order-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: opacity 0.3s;
            text-decoration: none;
            display: inline-block;
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

        .empty-state {
            background: white;
            border-radius: 12px;
            padding: 80px 20px;
            text-align: center;
        }

        .empty-state i {
            font-size: 80px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 24px;
            color: #666;
            margin-bottom: 15px;
        }

        .empty-state p {
            font-size: 16px;
            color: #999;
            margin-bottom: 30px;
        }

        .loading-state {
            text-align: center;
            padding: 60px 20px;
        }

        .loading-state i {
            font-size: 40px;
            color: #ccc;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 768px) {
            .order-header {
                flex-direction: column;
                gap: 15px;
            }

            .order-total {
                text-align: left;
            }

            .order-item {
                grid-template-columns: 60px 1fr;
            }

            .item-price-section {
                grid-column: 2;
                align-items: flex-start;
                margin-top: 10px;
            }

            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-section select {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <div class="orders-container">
        <div class="orders-header">
            <h1><i class="fas fa-shopping-bag"></i> My Orders</h1>
            <p>View and track your order history</p>
        </div>

        <div class="filter-section">
            <label style="font-weight: 500; color: #666;">Filter by:</label>
            <select id="orderStatusFilter" onchange="loadOrders()">
                <option value="">All Orders</option>
                <option value="pending">Pending</option>
                <option value="processing">Processing</option>
                <option value="shipped">Shipped</option>
                <option value="delivered">Delivered</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <select id="paymentStatusFilter" onchange="loadOrders()">
                <option value="">All Payments</option>
                <option value="paid">Paid</option>
                <option value="unpaid">Unpaid</option>
            </select>
        </div>

        <div id="ordersContent">
            <div class="loading-state">
                <i class="fas fa-spinner"></i>
                <p>Loading orders...</p>
            </div>
        </div>
    </div>

    <?php include 'template/footermini.php' ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const jwt = sessionStorage.getItem("jwt");

        // ตรวจสอบว่าล็อกอินหรือไม่
        if (!jwt) {
            window.location.href = '?';
        }

        function loadOrders() {
            const orderStatus = $('#orderStatusFilter').val();
            const paymentStatus = $('#paymentStatusFilter').val();

            $.ajax({
                url: 'app/actions/get_user_orders.php',
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + jwt
                },
                data: {
                    order_status: orderStatus,
                    payment_status: paymentStatus
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        if (response.data.length === 0) {
                            displayEmptyState();
                        } else {
                            displayOrders(response.data);
                        }
                    } else {
                        displayEmptyState();
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 401) {
                        sessionStorage.removeItem('jwt');
                        window.location.href = '?';
                    } else {
                        Swal.fire('Error!', 'Failed to load orders', 'error');
                        displayEmptyState();
                    }
                }
            });
        }

        function displayEmptyState() {
            $('#ordersContent').html(`
                <div class="empty-state">
                    <i class="fas fa-shopping-bag"></i>
                    <h3>No orders found</h3>
                    <p>You haven't placed any orders yet</p>
                    <button class="btn btn-primary" onclick="window.location.href='?product'">
                        Start Shopping
                    </button>
                </div>
            `);
        }

        function displayOrders(orders) {
            let html = '';

            orders.forEach(function(order) {
                const orderDate = new Date(order.date_created).toLocaleDateString('en-GB', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });

                // สร้าง HTML สำหรับรายการสินค้า
                let itemsHtml = '';
                if (order.items && order.items.length > 0) {
                    order.items.forEach(function(item) {
                        itemsHtml += `
                            <div class="order-item">
                                <img src="${item.product_image || 'public/img/no-image.png'}" 
                                     alt="${item.product_name}" 
                                     class="item-image"
                                     onerror="this.src='public/img/no-image.png'">
                                <div class="item-details">
                                    <div class="item-name">${item.product_name}</div>
                                    <div class="item-quantity">Quantity: ${item.quantity}</div>
                                </div>
                                <div class="item-price-section">
                                    <div class="item-unit-price">฿${parseFloat(item.unit_price_with_vat).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                                    <div class="item-total-price">฿${parseFloat(item.total).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
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
                        <div class="order-shipping">
                            <div class="shipping-title">
                                <i class="fas fa-truck"></i> Shipping Address
                            </div>
                            <div class="shipping-details">
                                <strong>${addr.recipient_name}</strong><br>
                                ${addr.recipient_phone}<br>
                                ${addr.address_line1}${addr.address_line2 ? ', ' + addr.address_line2 : ''}<br>
                                ${addr.subdistrict}, ${addr.district}<br>
                                ${addr.province} ${addr.postal_code}
                            </div>
                        </div>
                    `;
                }

                html += `
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-info">
                                <div class="order-number">
                                    <i class="fas fa-receipt"></i> Order #${order.order_number}
                                </div>
                                <div class="order-date">
                                    <i class="far fa-calendar"></i> ${orderDate}
                                </div>
                                <div class="order-status-badges">
                                    <span class="status-badge ${order.order_status}">${order.order_status_label || order.order_status}</span>
                                    <span class="status-badge ${order.payment_status}">${order.payment_status_label || order.payment_status}</span>
                                </div>
                            </div>
                            <div class="order-total">
                                <div class="total-label">Total Amount</div>
                                <div class="total-amount">฿${parseFloat(order.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                            </div>
                        </div>

                        <div class="order-items">
                            ${itemsHtml}
                        </div>

                        ${shippingHtml}

                        <div class="order-summary">
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span>฿${parseFloat(order.subtotal).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                            </div>
                            <div class="summary-row">
                                <span>VAT</span>
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
                                    <span>-฿${parseFloat(order.discount_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                                </div>
                            ` : ''}
                            <div class="summary-row total">
                                <span>Total</span>
                                <span>฿${parseFloat(order.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                            </div>
                        </div>

                        ${order.payment_slip ? `
                            <div class="order-shipping">
                                <div class="shipping-title">
                                    <i class="fas fa-receipt"></i> Payment Slip
                                </div>
                                <div style="display: flex; gap: 15px; align-items: center;">
                                    <img src="${order.payment_slip.file_path}" 
                                         alt="Payment Slip" 
                                         style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; cursor: pointer;"
                                         onclick="viewPaymentSlip('${order.payment_slip.file_path}')">
                                    <div>
                                        <div style="font-size: 14px; color: #666; margin-bottom: 5px;">
                                            Uploaded: ${new Date(order.payment_slip.date_uploaded).toLocaleDateString('en-GB')}
                                        </div>
                                        <div style="font-size: 13px; color: #999;">
                                            Status: <span class="status-badge ${order.payment_slip.status}">${order.payment_slip.status}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ` : ''}

                        <div class="order-actions">
                            <button class="btn btn-secondary" onclick="viewOrderDetails(${order.order_id})">
                                <i class="fas fa-eye"></i> View Details
                            </button>
                            ${(order.order_status === 'pending' || order.order_status === 'processing') && order.payment_status === 'unpaid' && !order.payment_slip ? `
                                <button class="btn btn-primary" onclick="payOrder(${order.order_id})">
                                    <i class="fas fa-credit-card"></i> Pay Now
                                </button>
                            ` : ''}
                            ${order.order_status === 'delivered' ? `
                                <button class="btn btn-primary" onclick="reorder(${order.order_id})">
                                    <i class="fas fa-redo"></i> Reorder
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `;
            });

            $('#ordersContent').html(html);
        }

        function viewOrderDetails(orderId) {
            window.location.href = '?order_detail&id=' + orderId;
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
            loadOrders();
        });
    </script>
</body>

</html>