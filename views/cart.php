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

        .cart-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .cart-header {
            background: white;
            border-radius: 12px;
            padding: 30px 40px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .cart-header h1 {
            font-size: 32px;
            font-weight: 600;
            margin: 0;
            color: #000;
        }

        .cart-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }

        .cart-items {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .cart-item {
            display: grid;
            grid-template-columns: 120px 1fr auto;
            gap: 20px;
            padding: 20px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            background: #f5f5f5;
        }

        .item-details {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .item-name {
            font-size: 18px;
            font-weight: 600;
            color: #000;
            margin-bottom: 8px;
        }

        .item-price {
            font-size: 16px;
            color: #666;
        }

        .item-actions {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: flex-end;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 5px;
        }

        .qty-btn {
            width: 32px;
            height: 32px;
            border: none;
            background: #f5f5f5;
            cursor: pointer;
            border-radius: 4px;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s;
        }

        .qty-btn:hover {
            background: #e0e0e0;
        }

        .qty-input {
            width: 50px;
            text-align: center;
            border: none;
            font-size: 16px;
            font-weight: 600;
        }

        .remove-btn {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            font-size: 14px;
            padding: 8px;
            transition: opacity 0.3s;
        }

        .remove-btn:hover {
            opacity: 0.7;
        }

        .item-total {
            font-size: 18px;
            font-weight: 600;
            color: #000;
        }

        .cart-summary {
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

        .summary-row:last-child {
            border-bottom: none;
        }

        .summary-row.total {
            font-size: 20px;
            font-weight: 600;
            color: #000;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }

        .checkout-btn {
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

        .checkout-btn:hover {
            opacity: 0.8;
        }

        .checkout-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .empty-cart {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 12px;
        }

        .empty-cart i {
            font-size: 80px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-cart h3 {
            font-size: 24px;
            color: #666;
            margin-bottom: 30px;
        }

        .continue-shopping {
            background: #000;
            color: white;
            border: none;
            padding: 12px 32px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            cursor: pointer;
            transition: opacity 0.3s;
        }

        .continue-shopping:hover {
            opacity: 0.8;
        }

        @media (max-width: 968px) {
            .cart-content {
                grid-template-columns: 1fr;
            }

            .cart-summary {
                position: static;
            }

            .cart-item {
                grid-template-columns: 80px 1fr;
            }

            .item-actions {
                grid-column: 2;
                flex-direction: row;
                justify-content: space-between;
                margin-top: 15px;
            }
        }
    </style>
    <?php include 'template/header.php' ?>
</head>

<body>


    <div class="cart-container">
        <div class="cart-header">
            <h1><i class="fas fa-shopping-cart"></i> Shopping Cart</h1>
        </div>

        <div id="cartContentArea">
            <!-- Cart items will be loaded here -->
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const jwt = sessionStorage.getItem("jwt");

        function loadCart() {
            const headers = jwt ? {
                'Authorization': 'Bearer ' + jwt
            } : {};

            $.ajax({
                url: 'app/actions/get_cart.php',
                type: 'GET',
                headers: headers,
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        if (response.data.items.length === 0) {
                            displayEmptyCart();
                        } else {
                            displayCart(response.data);
                        }
                    } else {
                        displayEmptyCart();
                    }
                },
                error: function() {
                    displayEmptyCart();
                }
            });
        }

        function displayEmptyCart() {
            $('#cartContentArea').html(`
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Your cart is empty</h3>
                    <button class="continue-shopping" onclick="window.location.href='?product'">
                        Continue Shopping
                    </button>
                </div>
            `);
        }

        function displayCart(data) {
            const items = data.items;
            const summary = data.summary;

            let itemsHtml = '';
            items.forEach(function(item) {
                const itemTotal = item.price_with_vat * item.quantity;
                itemsHtml += `
                    <div class="cart-item" data-cart-id="${item.cart_id}">
                        <img src="${item.product_image || 'public/img/no-image.png'}" alt="${item.product_name}" class="item-image">
                        <div class="item-details">
                            <div>
                                <div class="item-name">${item.product_name}</div>
                                <div class="item-price">฿${parseFloat(item.price_with_vat).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                            </div>
                            <div class="quantity-control">
                                <button class="qty-btn" onclick="updateQuantity(${item.cart_id}, ${item.quantity - 1})">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" class="qty-input" value="${item.quantity}" readonly>
                                <button class="qty-btn" onclick="updateQuantity(${item.cart_id}, ${item.quantity + 1})">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="item-actions">
                            <div class="item-total">฿${itemTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                            <button class="remove-btn" onclick="removeItem(${item.cart_id})">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </div>
                    </div>
                `;
            });

            const html = `
                <div class="cart-content">
                    <div class="cart-items">
                        ${itemsHtml}
                    </div>
                    <div class="cart-summary">
                        <div class="summary-title">Order Summary</div>
                        <div class="summary-row">
                            <span>Subtotal (${summary.total_items} items)</span>
                            <span>฿${parseFloat(summary.subtotal).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                        </div>
                        <div class="summary-row">
                            <span>VAT (${summary.vat_percentage}%)</span>
                            <span>฿${parseFloat(summary.vat_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total</span>
                            <span>฿${parseFloat(summary.total).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                        </div>
                        <button class="checkout-btn" onclick="proceedToCheckout()" ${!jwt ? 'disabled' : ''}>
                            ${!jwt ? 'Please Login to Checkout' : 'Proceed to Checkout'}
                        </button>
                        ${!jwt ? '<p style="text-align: center; margin-top: 15px; font-size: 13px; color: #999;">You need to login to complete your purchase</p>' : ''}
                    </div>
                </div>
            `;

            $('#cartContentArea').html(html);
        }

        function updateQuantity(cartId, newQuantity) {
            if (newQuantity < 1) {
                removeItem(cartId);
                return;
            }

            const headers = jwt ? {
                'Authorization': 'Bearer ' + jwt
            } : {};

            $.ajax({
                url: 'app/actions/update_cart.php',
                type: 'POST',
                headers: headers,
                data: {
                    cart_id: cartId,
                    quantity: newQuantity
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        loadCart();
                        if (window.updateCartCount) {
                            window.updateCartCount();
                        }
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                }
            });
        }

        function removeItem(cartId) {
            Swal.fire({
                title: 'Remove item?',
                text: "Are you sure you want to remove this item from cart?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#000',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, remove it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const headers = jwt ? {
                        'Authorization': 'Bearer ' + jwt
                    } : {};

                    $.ajax({
                        url: 'app/actions/remove_from_cart.php',
                        type: 'POST',
                        headers: headers,
                        data: {
                            cart_id: cartId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire('Removed!', 'Item has been removed from cart.', 'success');
                                loadCart();
                                if (window.updateCartCount) {
                                    window.updateCartCount();
                                }
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        }
                    });
                }
            });
        }

        function proceedToCheckout() {
            if (!jwt) {
                Swal.fire('Please Login', 'You need to login to proceed to checkout', 'warning');
                return;
            }
            window.location.href = '?checkout';
        }

        // Initialize
        $(document).ready(function() {
            loadCart();
        });
    </script>
</body>

</html>