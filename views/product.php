<?php
require_once('lib/connect.php');
global $conn;

// ตรวจสอบภาษา
$lang = isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'cn', 'jp', 'kr']) ? $_GET['lang'] : 'th';
$name_col = "name_" . $lang;
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <?php include 'template/header.php'; ?>
    
    <style>
        /* ดึง Style มาจากหน้าหลักเพื่อความต่อเนื่อง */
        .products-container {
            padding: 60px 5%;
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .page-title {
            font-size: 36px;
            font-weight: 700;
            color: #1a1a1a;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
        }

        /* Product Card Style */
        .product-card {
            position: relative;
            text-decoration: none;
            color: inherit;
            display: block;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .product-card:hover {
            transform: translateY(-8px);
        }

        .product-image-wrapper {
            position: relative;
            overflow: hidden;
            aspect-ratio: 3/4;
            background: #f5f5f5;
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .product-card:hover .product-image {
            transform: scale(1.08);
        }

        .product-price-overlay {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 10px 18px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 15px;
            color: #000;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .product-card:hover .product-price-overlay {
            background: #000;
            color: #fff;
        }

        .product-name {
            margin-top: 15px;
            font-size: 16px;
            font-weight: 500;
            text-align: center;
            color: #1a1a1a;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            grid-column: 1/-1;
            padding: 60px 20px;
        }

        .empty-state h3 {
            font-size: 24px;
            color: #666;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #999;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            .page-title { font-size: 28px; }
        }
    </style>
</head>

<body>
    <?php include 'template/banner_slide.php'; ?>

    <main class="products-container">
        <header class="page-header">
            <h1 class="page-title" data-translate="product">Our Collection</h1>
            <div style="width: 50px; height: 2px; background: #000; margin: 20px auto;"></div>
        </header>

        <div class="products-grid">
            <?php
            // ดึงสินค้าทั้งหมด - แสดงราคาที่ยังไม่รวม VAT
            $products_query = "
                SELECT 
                    p.product_id,
                    p.{$name_col} as product_name,
                    p.price,
                    p.vat_percentage,
                    pi.api_path as image_path
                FROM products p
                LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1 AND pi.del = 0
                WHERE p.status = 1 
                    AND p.del = 0 
                    AND p.stock_quantity > 0
                ORDER BY p.created_at DESC
            ";
            
            $products_result = $conn->query($products_query);
            
            if ($products_result && $products_result->num_rows > 0) {
                while ($product = $products_result->fetch_assoc()) {
                    $product_id_encoded = urlencode(base64_encode($product['product_id']));
                    $product_link = "?product_detail&id=" . $product_id_encoded . "&lang=" . $lang;
                    $image = $product['image_path'] ?? 'path/to/default-image.jpg';
                    ?>
                    
                    <a href="<?= htmlspecialchars($product_link) ?>" class="product-card">
                        <div class="product-image-wrapper">
                            <img src="<?= htmlspecialchars($image) ?>" 
                                 alt="<?= htmlspecialchars($product['product_name']) ?>" 
                                 class="product-image"
                                 loading="lazy">
                            <div class="product-price-overlay">
                                ฿<?= number_format($product['price'], 2) ?>
                            </div>
                        </div>
                        <h3 class="product-name"><?= htmlspecialchars($product['product_name']) ?></h3>
                    </a>

                    <?php
                }
            } else {
                ?>
                <div class="empty-state">
                    <h3>ไม่พบสินค้า</h3>
                    <p>ขณะนี้ยังไม่มีสินค้าที่พร้อมขาย</p>
                </div>
                <?php
            }
            ?>
        </div>
    </main>

    <?php include 'template/footer.php'; ?>
</body>
</html>