<?php
require_once('lib/connect.php');
global $conn;

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Generate session_id for guest users
if (!isset($_SESSION['guest_session_id'])) {
    $_SESSION['guest_session_id'] = session_id();
}

// Get language
$lang = isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'cn', 'jp', 'kr']) ? $_GET['lang'] : 'th';

// Get product ID
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$product_id = base64_decode(urldecode($_GET['id']));

// Column names based on language
$name_col = "name_" . $lang;
$desc_col = "description_" . $lang;

// Fetch product details
$product_query = "
    SELECT 
        p.product_id,
        p.{$name_col} as product_name,
        p.{$desc_col} as description,
        p.price,
        p.vat_percentage,
        ROUND(p.price * (1 + p.vat_percentage / 100), 2) as price_with_vat,
        p.status
    FROM products p
    WHERE p.product_id = ? AND p.del = 0
";

$stmt = $conn->prepare($product_query);
$stmt->bind_param('i', $product_id);
$stmt->execute();
$product_result = $stmt->get_result();

if ($product_result->num_rows === 0) {
    header('Location: index.php');
    exit;
}

$product = $product_result->fetch_assoc();
$stmt->close();

// Fetch product images
$images_query = "
    SELECT 
        image_id,
        api_path,
        is_primary,
        display_order
    FROM product_images
    WHERE product_id = ? AND del = 0
    ORDER BY is_primary DESC, display_order ASC
";

$stmt_images = $conn->prepare($images_query);
$stmt_images->bind_param('i', $product_id);
$stmt_images->execute();
$images_result = $stmt_images->get_result();

$images = [];
while ($img = $images_result->fetch_assoc()) {
    $images[] = $img;
}
$stmt_images->close();

$page_title = $product['product_name'];
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - PERFUME</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --luxury-black: #000000;
            --luxury-white: #ffffff;
            --luxury-gray: #666666;
            --luxury-light-gray: #f5f5f5;
            --transition: cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--luxury-white);
            color: var(--luxury-black);
            line-height: 1.6;
        }

        /* ========================================
           CUSTOM NOTIFICATION
           ======================================== */
        
        .notification {
            position: fixed;
            top: 100px;
            right: 40px;
            background: var(--luxury-white);
            border: 1px solid rgba(0, 0, 0, 0.1);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            padding: 20px 25px;
            border-radius: 4px;
            z-index: 10000;
            min-width: 320px;
            max-width: 400px;
            opacity: 0;
            transform: translateX(450px);
            transition: all 0.4s var(--transition);
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }

        .notification.show {
            opacity: 1;
            transform: translateX(0);
        }

        .notification-icon {
            font-size: 20px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .notification.success .notification-icon {
            color: #28a745;
        }

        .notification.error .notification-icon {
            color: #dc3545;
        }

        .notification.info .notification-icon {
            color: var(--luxury-black);
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.02em;
            margin-bottom: 5px;
            color: var(--luxury-black);
        }

        .notification-message {
            font-size: 13px;
            line-height: 1.6;
            color: var(--luxury-gray);
        }

        .notification-close {
            background: none;
            border: none;
            font-size: 18px;
            color: var(--luxury-gray);
            cursor: pointer;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.3s;
            flex-shrink: 0;
        }

        .notification-close:hover {
            color: var(--luxury-black);
        }

        /* ========================================
           MINIMAL HEADER
           ======================================== */
        
        .minimal-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            z-index: 1000;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .header-content {
            max-width: 100%;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            font-weight: 600;
            letter-spacing: 0.15em;
            color: var(--luxury-black);
            text-decoration: none;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--luxury-black);
            text-decoration: none;
            font-size: 13px;
            font-weight: 400;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            transition: opacity 0.3s;
        }

        .back-btn:hover {
            opacity: 0.6;
        }

        /* ========================================
           PRODUCT DETAIL LAYOUT
           ======================================== */
        
        .product-detail-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 0;
            margin-top: 70px;
            min-height: 100vh;
        }

        .product-gallery {
            display: flex;
            flex-direction: column;
            gap: 0;
            padding: 40px;
            background: var(--luxury-light-gray);
        }

        .gallery-image {
            width: 100%;
            margin-bottom: 20px;
        }

        .gallery-image img {
            width: 100%;
            height: auto;
            display: block;
            object-fit: cover;
        }

        .product-info {
            position: sticky;
            top: 70px;
            height: calc(100vh - 70px);
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow-y: auto;
        }

        .product-info-top {
            flex-grow: 1;
        }

        .product-title {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 500;
            margin-bottom: 15px;
            letter-spacing: 0.02em;
            line-height: 1.3;
        }

        .product-price {
            font-size: 20px;
            font-weight: 400;
            margin-bottom: 30px;
            color: var(--luxury-gray);
        }

        .price-breakdown {
            font-size: 13px;
            color: var(--luxury-gray);
            margin-top: 8px;
            font-weight: 300;
        }

        .product-description {
            font-size: 14px;
            line-height: 1.8;
            color: var(--luxury-gray);
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .detail-accordion {
            margin-bottom: 40px;
        }

        .detail-item {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            transition: opacity 0.3s;
        }

        .detail-header:hover {
            opacity: 0.6;
        }

        .detail-header i {
            font-size: 10px;
            transition: transform 0.3s;
        }

        .detail-item.active .detail-header i {
            transform: rotate(180deg);
        }

        .detail-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            font-size: 13px;
            line-height: 1.8;
            color: var(--luxury-gray);
        }

        .detail-item.active .detail-content {
            max-height: 500px;
            padding-bottom: 20px;
        }

        .product-actions {
            margin-top: auto;
        }

        .btn {
            width: 100%;
            padding: 18px;
            font-size: 12px;
            font-weight: 500;
            text-align: center;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            display: block;
            margin-bottom: 12px;
        }

        .btn-primary {
            background: var(--luxury-black);
            color: var(--luxury-white);
        }

        .btn-primary:hover:not(:disabled) {
            background: #333;
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: var(--luxury-white);
            color: var(--luxury-black);
            border: 1px solid var(--luxury-black);
        }

        .btn-secondary:hover {
            background: var(--luxury-black);
            color: var(--luxury-white);
        }

        .share-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .share-title {
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-bottom: 15px;
            color: var(--luxury-gray);
        }

        .share-buttons {
            display: flex;
            gap: 12px;
        }

        .share-btn {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            color: var(--luxury-black);
            text-decoration: none;
            transition: all 0.3s;
            font-size: 14px;
        }

        .share-btn:hover {
            background: var(--luxury-black);
            color: var(--luxury-white);
            border-color: var(--luxury-black);
        }

        @media (max-width: 1024px) {
            .product-detail-container {
                grid-template-columns: 1fr;
            }

            .product-info {
                position: relative;
                top: 0;
                height: auto;
            }

            .product-gallery {
                padding: 20px;
            }

            .notification {
                right: 20px;
                left: 20px;
                min-width: auto;
            }
        }

        @media (max-width: 640px) {
            .header-content {
                padding: 15px 20px;
            }

            .product-gallery {
                padding: 15px;
            }

            .product-info {
                padding: 40px 20px;
            }

            .product-title {
                font-size: 24px;
            }

            .product-price {
                font-size: 18px;
            }

            .notification {
                top: 80px;
                right: 15px;
                left: 15px;
            }
        }

        html {
            scroll-behavior: smooth;
        }

        .product-info::-webkit-scrollbar {
            width: 4px;
        }

        .product-info::-webkit-scrollbar-track {
            background: transparent;
        }

        .product-info::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 2px;
        }

        .product-info::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 0, 0, 0.3);
        }
    </style>
<?php include 'template/header.php'; ?>
</head>
<body>


    <!-- Notification Container -->
    <div id="notification" class="notification">
        <div class="notification-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="notification-content">
            <div class="notification-title"></div>
            <div class="notification-message"></div>
        </div>
        <button class="notification-close" onclick="hideNotification()">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Product Detail -->
    <div class="product-detail-container">
        
        <!-- Left Side - Vertical Gallery -->
        <div class="product-gallery">
            <?php if (!empty($images)): ?>
                <?php foreach ($images as $img): ?>
                <div class="gallery-image">
                    <img src="<?= htmlspecialchars($img['api_path']) ?>" 
                         alt="<?= htmlspecialchars($product['product_name']) ?>"
                         loading="lazy">
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="gallery-image">
                    <img src="https://via.placeholder.com/800x1067?text=No+Image" 
                         alt="<?= htmlspecialchars($product['product_name']) ?>">
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Side - Sticky Product Info -->
        <div class="product-info">
            <div class="product-info-top">
                <h1 class="product-title"><?= htmlspecialchars($product['product_name']) ?></h1>
                
                <div class="product-price">
                    ฿<?= number_format($product['price_with_vat'], 2) ?>
                    <div class="price-breakdown">
                        <?= $lang === 'en' ? 'Includes VAT' : ($lang === 'cn' ? '含增值税' : ($lang === 'jp' ? '税込' : ($lang === 'kr' ? '부가세 포함' : 'รวม VAT แล้ว'))) ?> (<?= number_format($product['vat_percentage'], 0) ?>%)
                    </div>
                </div>

                <?php if (!empty($product['description'])): ?>
                <div class="product-description">
                    <?= nl2br(htmlspecialchars($product['description'])) ?>
                </div>
                <?php endif; ?>

                <!-- Product Details Accordion -->
                <div class="detail-accordion">
                    <div class="detail-item">
                        <div class="detail-header" onclick="toggleDetail(this)">
                            <span><?= $lang === 'en' ? 'Product Information' : ($lang === 'cn' ? '产品信息' : ($lang === 'jp' ? '製品情報' : ($lang === 'kr' ? '제품 정보' : 'ข้อมูลสินค้า'))) ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="detail-content">
                            <p><strong><?= $lang === 'en' ? 'Price (Before VAT):' : ($lang === 'cn' ? '价格（税前）：' : ($lang === 'jp' ? '価格（税抜）：' : ($lang === 'kr' ? '가격 (세전):' : 'ราคา (ก่อน VAT):'))) ?></strong> ฿<?= number_format($product['price'], 2) ?></p>
                            <p><strong><?= $lang === 'en' ? 'VAT:' : ($lang === 'cn' ? '增值税：' : ($lang === 'jp' ? 'VAT：' : ($lang === 'kr' ? '부가세:' : 'VAT:'))) ?></strong> <?= number_format($product['vat_percentage'], 0) ?>%</p>
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-header" onclick="toggleDetail(this)">
                            <span><?= $lang === 'en' ? 'Delivery & Returns' : ($lang === 'cn' ? '配送和退货' : ($lang === 'jp' ? '配送と返品' : ($lang === 'kr' ? '배송 및 반품' : 'การจัดส่งและการคืนสินค้า'))) ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="detail-content">
                            <p><?= $lang === 'en' ? 'Free delivery for orders over ฿5,000' : ($lang === 'cn' ? '订单满 ฿5,000 免费配送' : ($lang === 'jp' ? '฿5,000以上のご注文で送料無料' : ($lang === 'kr' ? '฿5,000 이상 주문 시 무료 배송' : 'จัดส่งฟรีสำหรับคำสั่งซื้อมากกว่า ฿5,000'))) ?></p>
                            <p><?= $lang === 'en' ? 'Returns accepted within 7 days' : ($lang === 'cn' ? '7天内接受退货' : ($lang === 'jp' ? '7日以内の返品可能' : ($lang === 'kr' ? '7일 이내 반품 가능' : 'รับคืนสินค้าภายใน 7 วัน'))) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="product-actions">
                <button class="btn btn-primary" id="addToCartBtn" onclick="addToCart(event)">
                    <i class="fas fa-shopping-bag"></i>
                    <?= $lang === 'en' ? 'Add to Cart' : ($lang === 'cn' ? '加入购物车' : ($lang === 'jp' ? 'カートに追加' : ($lang === 'kr' ? '장바구니에 담기' : 'เพิ่มลงตะกร้า'))) ?>
                </button>
                
                <a href="https://lin.ee/yoSCNwF" target="_blank" class="btn btn-secondary">
                    <i class="fab fa-line"></i>
                    <?= $lang === 'en' ? 'Contact on LINE' : ($lang === 'cn' ? '通过 LINE 联系' : ($lang === 'jp' ? 'LINEでお問い合わせ' : ($lang === 'kr' ? 'LINE으로 문의' : 'ติดต่อผ่าน LINE'))) ?>
                </a>

                <!-- Share Section -->
                <div class="share-section">
                    <div class="share-title">
                        <?= $lang === 'en' ? 'Share' : ($lang === 'cn' ? '分享' : ($lang === 'jp' ? 'シェア' : ($lang === 'kr' ? '공유' : 'แชร์'))) ?>
                    </div>
                    <div class="share-buttons">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($_SERVER['REQUEST_URI']) ?>" target="_blank" class="share-btn">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?= urlencode($_SERVER['REQUEST_URI']) ?>&text=<?= urlencode($product['product_name']) ?>" target="_blank" class="share-btn">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://social-plugins.line.me/lineit/share?url=<?= urlencode($_SERVER['REQUEST_URI']) ?>" target="_blank" class="share-btn">
                            <i class="fab fa-line"></i>
                        </a>
                        <a href="javascript:void(0)" onclick="copyLink()" class="share-btn">
                            <i class="fas fa-link"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const lang = "<?= $lang ?>";
        
        // Notification messages by language
        const messages = {
            success: {
                title: {
                    th: 'สำเร็จ!',
                    en: 'Success!',
                    cn: '成功！',
                    jp: '成功！',
                    kr: '성공!'
                },
                added: {
                    th: 'เพิ่มสินค้าลงตะกร้าแล้ว',
                    en: 'Product added to cart',
                    cn: '产品已添加到购物车',
                    jp: 'カートに追加されました',
                    kr: '장바구니에 추가되었습니다'
                },
                copied: {
                    th: 'คัดลอกลิงก์แล้ว',
                    en: 'Link copied to clipboard',
                    cn: '链接已复制',
                    jp: 'リンクをコピーしました',
                    kr: '링크가 복사되었습니다'
                }
            },
            error: {
                title: {
                    th: 'เกิดข้อผิดพลาด',
                    en: 'Error',
                    cn: '错误',
                    jp: 'エラー',
                    kr: '오류'
                },
                failed: {
                    th: 'ไม่สามารถเพิ่มสินค้าได้',
                    en: 'Failed to add product',
                    cn: '添加产品失败',
                    jp: '製品を追加できませんでした',
                    kr: '제품을 추가하지 못했습니다'
                }
            },
            info: {
                title: {
                    th: 'กรุณาเข้าสู่ระบบ',
                    en: 'Please Login',
                    cn: '请登录',
                    jp: 'ログインしてください',
                    kr: '로그인하세요'
                },
                loginRequired: {
                    th: 'กรุณาเข้าสู่ระบบก่อนเพิ่มสินค้าลงตะกร้า',
                    en: 'Please login to add products to cart',
                    cn: '请先登录以将产品添加到购物车',
                    jp: 'カートに追加するにはログインしてください',
                    kr: '장바구니에 추가하려면 로그인하세요'
                }
            }
        };

        // Show notification function
        function showNotification(type, title, message) {
            const notification = document.getElementById('notification');
            const iconElement = notification.querySelector('.notification-icon i');
            const titleElement = notification.querySelector('.notification-title');
            const messageElement = notification.querySelector('.notification-message');
            
            // Remove previous classes
            notification.classList.remove('success', 'error', 'info', 'show');
            
            // Set icon based on type
            iconElement.className = '';
            if (type === 'success') {
                iconElement.className = 'fas fa-check-circle';
            } else if (type === 'error') {
                iconElement.className = 'fas fa-exclamation-circle';
            } else if (type === 'info') {
                iconElement.className = 'fas fa-info-circle';
            }
            
            // Set content
            titleElement.textContent = title;
            messageElement.textContent = message;
            
            // Add type class and show
            notification.classList.add(type);
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
            
            // Auto hide after 4 seconds
            setTimeout(() => {
                hideNotification();
            }, 4000);
        }

        // Hide notification function
        function hideNotification() {
            const notification = document.getElementById('notification');
            notification.classList.remove('show');
        }

        // Toggle accordion
        function toggleDetail(element) {
            const item = element.parentElement;
            const allItems = document.querySelectorAll('.detail-item');
            
            allItems.forEach(i => {
                if (i !== item) {
                    i.classList.remove('active');
                }
            });
            
            item.classList.toggle('active');
        }

        // Copy link
        function copyLink() {
            const url = window.location.href;
            
            navigator.clipboard.writeText(url).then(() => {
                showNotification('success', messages.success.title[lang], messages.success.copied[lang]);
            }).catch(err => {
                showNotification('error', messages.error.title[lang], 'Failed to copy link');
            });
        }

        // Add to cart function
        function addToCart(event) {
            const productId = <?= $product_id ?>;
            
            // Check JWT
            const jwt = sessionStorage.getItem("jwt");
            
            if (!jwt) {
                // Show login required notification
                showNotification('info', messages.info.title[lang], messages.info.loginRequired[lang]);
                
                // Open login modal after a short delay
                setTimeout(() => {
                    document.getElementById('myModal-sign-in').style.display = 'block';
                }, 1500);
                return;
            }
            
            // Prepare headers
            const headers = {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Authorization': 'Bearer ' + jwt
            };
            
            // Get button
            const addButton = document.getElementById('addToCartBtn');
            const originalHTML = addButton.innerHTML;
            
            // Disable button and show loading
            addButton.disabled = true;
            addButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + (lang === 'en' ? 'Adding...' : (lang === 'cn' ? '添加中...' : (lang === 'jp' ? '追加中...' : (lang === 'kr' ? '추가 중...' : 'กำลังเพิ่ม...'))));
            
            // Send request
            fetch('app/actions/add_to_cart.php', {
                method: 'POST',
                headers: headers,
                body: new URLSearchParams({
                    product_id: productId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Response:', data);
                
                if (data.status === 'success') {
                    // Show success notification
                    showNotification('success', messages.success.title[lang], messages.success.added[lang]);
                    
                    // Update button to show success
                    addButton.innerHTML = '<i class="fas fa-check"></i> ' + messages.success.added[lang];
                    addButton.style.background = '#28a745';
                    
                    // Update cart badge
                    if (typeof window.updateCartCount === 'function') {
                        window.updateCartCount();
                    } else if (data.cart_count !== undefined) {
                        const cartBadge = document.getElementById('cartBadge');
                        if (cartBadge) {
                            if (data.cart_count > 0) {
                                cartBadge.textContent = data.cart_count;
                                cartBadge.style.display = 'flex';
                            } else {
                                cartBadge.style.display = 'none';
                            }
                        }
                    }
                    
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        addButton.disabled = false;
                        addButton.innerHTML = originalHTML;
                        addButton.style.background = '';
                    }, 2000);
                    
                } else if (data.require_login) {
                    // Show login required notification
                    showNotification('info', messages.info.title[lang], messages.info.loginRequired[lang]);
                    sessionStorage.removeItem("jwt");
                    
                    setTimeout(() => {
                        document.getElementById('myModal-sign-in').style.display = 'block';
                    }, 1500);
                    
                    addButton.disabled = false;
                    addButton.innerHTML = originalHTML;
                } else {
                    // Show error notification
                    showNotification('error', messages.error.title[lang], data.message || messages.error.failed[lang]);
                    addButton.disabled = false;
                    addButton.innerHTML = originalHTML;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', messages.error.title[lang], messages.error.failed[lang] + ': ' + error.message);
                addButton.disabled = false;
                addButton.innerHTML = originalHTML;
            });
        }
    </script>

    <?php include 'template/footer.php'; ?>
</body>
</html>