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
    <link href="app/css/index_.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            padding-top: 100px;
        }

        .checkout-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .checkout-header {
            background: white;
            border-radius: 12px;
            padding: 30px 40px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .checkout-header h1 {
            font-size: 32px;
            font-weight: 600;
            margin: 0 0 15px 0;
            color: #000;
        }

        .checkout-steps {
            display: flex;
            gap: 20px;
            align-items: center;
            margin-top: 20px;
        }

        .step {
            flex: 1;
            text-align: center;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 8px;
            position: relative;
        }

        .step.active {
            background: #000;
            color: white;
        }

        .step-number {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .step-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .checkout-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }

        .checkout-main {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .section-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #000;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .address-card {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 15px;
            position: relative;
        }

        .address-card:hover {
            border-color: #000;
        }

        .address-card.selected {
            border-color: #000;
            background: #f9f9f9;
        }

        .address-card.selected::after {
            content: "✓";
            position: absolute;
            top: 15px;
            right: 15px;
            width: 30px;
            height: 30px;
            background: #000;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: bold;
        }

        .address-label {
            font-size: 14px;
            font-weight: 600;
            color: #000;
            margin-bottom: 8px;
        }

        .address-details {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
        }

        .add-address-btn {
            width: 100%;
            padding: 15px;
            border: 2px dashed #ddd;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            color: #666;
            font-size: 14px;
            transition: all 0.3s;
        }

        .add-address-btn:hover {
            border-color: #000;
            color: #000;
        }

        .order-item {
            display: grid;
            grid-template-columns: 60px 1fr auto;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            background: #f5f5f5;
        }

        .item-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .item-name {
            font-size: 14px;
            font-weight: 500;
            color: #000;
            margin-bottom: 5px;
        }

        .item-quantity {
            font-size: 13px;
            color: #666;
        }

        .item-price {
            font-size: 16px;
            font-weight: 600;
            color: #000;
            text-align: right;
        }

        .checkout-summary {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            height: fit-content;
            position: sticky;
            top: 120px;
        }

        .summary-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #000;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            font-size: 14px;
            color: #666;
            border-bottom: 1px solid #f0f0f0;
        }

        .summary-row.total {
            font-size: 20px;
            font-weight: 600;
            color: #000;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
            border-bottom: none;
        }

        .place-order-btn {
            width: 100%;
            padding: 16px;
            background: #000;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            cursor: pointer;
            transition: opacity 0.3s;
            margin-top: 20px;
        }

        .place-order-btn:hover:not(:disabled) {
            opacity: 0.8;
        }

        .place-order-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .payment-methods {
            display: grid;
            gap: 15px;
        }

        .payment-method {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .payment-method:hover {
            border-color: #000;
        }

        .payment-method.selected {
            border-color: #000;
            background: #f9f9f9;
        }

        .payment-method input[type="radio"] {
            width: 20px;
            height: 20px;
        }

        .payment-icon {
            font-size: 24px;
            width: 40px;
            text-align: center;
        }

        .payment-info {
            flex: 1;
        }

        .payment-name {
            font-size: 16px;
            font-weight: 600;
            color: #000;
            margin-bottom: 3px;
        }

        .payment-desc {
            font-size: 13px;
            color: #666;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
        }

        @media (max-width: 968px) {
            .checkout-content {
                grid-template-columns: 1fr;
            }

            .checkout-summary {
                position: static;
            }
        }
    </style>
     <?php include 'template/header.php' ?>
</head>

<body>
   

    <div class="checkout-container">
        <div class="checkout-header">
            <h1><i class="fas fa-shopping-bag"></i> Checkout</h1>
            <div class="checkout-steps">
                <div class="step active">
                    <div class="step-number">1</div>
                    <div class="step-label">Cart</div>
                </div>
                <div class="step active">
                    <div class="step-number">2</div>
                    <div class="step-label">Checkout</div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-label">Payment</div>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-label">Complete</div>
                </div>
            </div>
        </div>

        <div id="checkoutContent">
            <!-- Content will be loaded here -->
        </div>
    </div>

    <?php include 'template/footermini.php' ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const jwt = sessionStorage.getItem("jwt");

        // ตรวจสอบว่าล็อกอินหรือไม่
        if (!jwt) {
            Swal.fire({
                title: 'Please Login',
                text: 'You need to login to proceed to checkout',
                icon: 'warning',
                confirmButtonColor: '#000'
            }).then(() => {
                window.location.href = '?cart';
            });
        }

        let cartData = null;
        let addresses = [];
        let selectedAddressId = null;
        let selectedPaymentMethod = 'bank_transfer';

        // โหลดข้อมูล
        function loadCheckoutData() {
            // โหลด Cart
            $.ajax({
                url: 'app/actions/get_cart.php',
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + jwt
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.data.items.length > 0) {
                        cartData = response.data;
                        loadAddresses();
                    } else {
                        showEmptyCart();
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to load cart', 'error');
                }
            });
        }

        // โหลดที่อยู่
        function loadAddresses() {
            $.ajax({
                url: 'app/actions/get_user_address.php',
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + jwt
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        addresses = response.data;
                        // เลือกที่อยู่ default อัตโนมัติ
                        const defaultAddr = addresses.find(addr => addr.is_default == 1);
                        if (defaultAddr) {
                            selectedAddressId = defaultAddr.address_id;
                        } else if (addresses.length > 0) {
                            selectedAddressId = addresses[0].address_id;
                        }
                        displayCheckout();
                    } else {
                        addresses = [];
                        displayCheckout();
                    }
                }
            });
        }

        // แสดงหน้า Checkout
        function displayCheckout() {
            let addressesHtml = '';
            
            if (addresses.length === 0) {
                addressesHtml = `
                    <div class="empty-state">
                        <i class="fas fa-map-marker-alt"></i>
                        <p>No delivery address found</p>
                        <button class="btn btn-primary" onclick="window.location.href='?profile'">
                            Add Address
                        </button>
                    </div>
                `;
            } else {
                addresses.forEach(function(addr) {
                    addressesHtml += `
                        <div class="address-card ${addr.address_id == selectedAddressId ? 'selected' : ''}" 
                             onclick="selectAddress(${addr.address_id})">
                            <div class="address-label">${addr.address_label || 'Address'}</div>
                            <div class="address-details">
                                <strong>${addr.recipient_name}</strong> - ${addr.recipient_phone}<br>
                                ${addr.address_line1}${addr.address_line2 ? ', ' + addr.address_line2 : ''}<br>
                                ${addr.subdistrict}, ${addr.district}, ${addr.province} ${addr.postal_code}
                            </div>
                        </div>
                    `;
                });
                
                addressesHtml += `
                    <button class="add-address-btn" onclick="window.location.href='?profile'">
                        <i class="fas fa-plus"></i> Add New Address
                    </button>
                `;
            }

            // สร้าง HTML สำหรับรายการสินค้า
            let itemsHtml = '';
            cartData.items.forEach(function(item) {
                const itemTotal = item.price_with_vat * item.quantity;
                itemsHtml += `
                    <div class="order-item">
                        <img src="${item.product_image || 'public/img/no-image.png'}" 
                             alt="${item.product_name}" 
                             class="item-image">
                        <div class="item-info">
                            <div class="item-name">${item.product_name}</div>
                            <div class="item-quantity">Qty: ${item.quantity} × ฿${parseFloat(item.price_with_vat).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                        </div>
                        <div class="item-price">฿${itemTotal.toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                    </div>
                `;
            });

            const html = `
                <div class="checkout-content">
                    <div class="checkout-main">
                        <!-- Shipping Address -->
                        <div class="section-card">
                            <div class="section-title">
                                <i class="fas fa-map-marker-alt"></i> Shipping Address
                            </div>
                            ${addressesHtml}
                        </div>

                        <!-- Payment Method -->
                        <div class="section-card">
                            <div class="section-title">
                                <i class="fas fa-credit-card"></i> Payment Method
                            </div>
                            <div class="payment-methods">
                                <label class="payment-method selected">
                                    <input type="radio" name="payment_method" value="bank_transfer" checked onchange="selectPayment('bank_transfer')">
                                    <div class="payment-icon">
                                        <i class="fas fa-university"></i>
                                    </div>
                                    <div class="payment-info">
                                        <div class="payment-name">Bank Transfer</div>
                                        <div class="payment-desc">Transfer to our bank account</div>
                                    </div>
                                </label>
                                <label class="payment-method">
                                    <input type="radio" name="payment_method" value="qr_code" onchange="selectPayment('qr_code')">
                                    <div class="payment-icon">
                                        <i class="fas fa-qrcode"></i>
                                    </div>
                                    <div class="payment-info">
                                        <div class="payment-name">QR Code</div>
                                        <div class="payment-desc">Scan QR code to pay</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div class="section-card">
                            <div class="section-title">
                                <i class="fas fa-box"></i> Order Items (${cartData.summary.total_items})
                            </div>
                            ${itemsHtml}
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="checkout-summary">
                        <div class="summary-title">Order Summary</div>
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span>฿${parseFloat(cartData.summary.subtotal).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                        </div>
                        <div class="summary-row">
                            <span>VAT (${cartData.summary.vat_percentage}%)</span>
                            <span>฿${parseFloat(cartData.summary.vat_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping Fee</span>
                            <span>฿0.00</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total</span>
                            <span>฿${parseFloat(cartData.summary.total).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                        </div>
                        <button class="place-order-btn" onclick="placeOrder()" ${!selectedAddressId ? 'disabled' : ''}>
                            <i class="fas fa-check"></i> Place Order
                        </button>
                    </div>
                </div>
            `;

            $('#checkoutContent').html(html);
        }

        // เลือกที่อยู่
        function selectAddress(addressId) {
            selectedAddressId = addressId;
            $('.address-card').removeClass('selected');
            $(`.address-card[onclick*="${addressId}"]`).addClass('selected');
            $('.place-order-btn').prop('disabled', false);
        }

        // เลือก Payment Method
        function selectPayment(method) {
            selectedPaymentMethod = method;
            $('.payment-method').removeClass('selected');
            $(`input[value="${method}"]`).closest('.payment-method').addClass('selected');
        }

        // สั่งซื้อ
        function placeOrder() {
            if (!selectedAddressId) {
                Swal.fire('Error!', 'Please select a delivery address', 'error');
                return;
            }

            Swal.fire({
                title: 'Confirm Order?',
                text: 'Please review your order before confirming',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#000',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Confirm Order'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'app/actions/create_order.php',
                        type: 'POST',
                        headers: {
                            'Authorization': 'Bearer ' + jwt
                        },
                        data: {
                            address_id: selectedAddressId,
                            payment_method: selectedPaymentMethod
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    title: 'Order Created!',
                                    text: 'Your order has been created successfully',
                                    icon: 'success',
                                    confirmButtonColor: '#000'
                                }).then(() => {
                                    window.location.href = '?payment&order_id=' + response.order_id;
                                });
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error!', 'Failed to create order', 'error');
                        }
                    });
                }
            });
        }

        // แสดงตะกร้าว่าง
        function showEmptyCart() {
            $('#checkoutContent').html(`
                <div class="section-card">
                    <div class="empty-state">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>Your cart is empty</h3>
                        <p>Add some products to proceed to checkout</p>
                        <button class="btn btn-primary" onclick="window.location.href='?product'">
                            Continue Shopping
                        </button>
                    </div>
                </div>
            `);
        }

        // เริ่มต้น
        $(document).ready(function() {
            loadCheckoutData();
        });
    </script>
</body>

</html>