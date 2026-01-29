<?php
require_once('lib/connect.php');
global $conn;

// ตรวจสอบภาษา
$lang = isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'cn', 'jp', 'kr']) ? $_GET['lang'] : 'th';
$name_col = "name_" . $lang;
$desc_col = "description_" . $lang;

// รับค่าการค้นหา
$searchQuery = isset($_GET['s']) ? trim($_GET['s']) : '';
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

        .search-info {
            margin-top: 15px;
            color: #666;
            font-size: 16px;
        }

        .search-term {
            color: #000;
            font-weight: 600;
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
            transition: color 0.3s ease;
        }

        .product-card:hover .product-name {
            color: #c9a961;
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
            <h1 class="page-title" data-translate="product">
                <?php 
                echo match($lang) {
                    'en' => 'Our Collection',
                    'cn' => '我们的系列',
                    'jp' => 'コレクション',
                    'kr' => '컬렉션',
                    default => 'คอลเลคชั่นของเรา',
                };
                ?>
            </h1>
            <div style="width: 50px; height: 2px; background: #000; margin: 20px auto;"></div>
            
            <?php if ($searchQuery): ?>
                <div class="search-info">
                    <?php 
                    echo match($lang) {
                        'en' => 'Search results for',
                        'cn' => '搜索结果',
                        'jp' => '検索結果',
                        'kr' => '검색 결과',
                        default => 'ผลการค้นหาสำหรับ',
                    };
                    ?> "<span class="search-term"><?= htmlspecialchars($searchQuery) ?></span>"
                </div>
            <?php endif; ?>
        </header>

        <div class="products-grid">
            <?php
            // สร้าง WHERE clause สำหรับการค้นหา
            $search_where = '';
            if ($searchQuery !== '') {
                $searchTerm = $conn->real_escape_string($searchQuery);
                $search_where = " AND (
                    pg.name_th LIKE '%{$searchTerm}%' OR
                    pg.name_en LIKE '%{$searchTerm}%' OR
                    pg.name_cn LIKE '%{$searchTerm}%' OR
                    pg.name_jp LIKE '%{$searchTerm}%' OR
                    pg.name_kr LIKE '%{$searchTerm}%' OR
                    pg.description_th LIKE '%{$searchTerm}%' OR
                    pg.description_en LIKE '%{$searchTerm}%' OR
                    pg.description_cn LIKE '%{$searchTerm}%' OR
                    pg.description_jp LIKE '%{$searchTerm}%' OR
                    pg.description_kr LIKE '%{$searchTerm}%'
                )";
            }
            
            // ✅ แก้ไข: ดึงจาก product_groups แทน products
            $products_query = "
                SELECT 
                    pg.group_id,
                    pg.{$name_col} as product_name,
                    pg.price,
                    pg.vat_percentage,
                    (SELECT pgi.api_path 
                     FROM product_group_images pgi 
                     WHERE pgi.group_id = pg.group_id 
                     AND pgi.del = 0 
                     ORDER BY pgi.is_primary DESC, pgi.display_order ASC 
                     LIMIT 1) as image_path,
                    (SELECT COUNT(*) 
                     FROM product_items pi 
                     WHERE pi.group_id = pg.group_id 
                     AND pi.status = 'available' 
                     AND pi.del = 0) as available_stock
                FROM product_groups pg
                WHERE pg.status = 1 
                    AND pg.del = 0
                    {$search_where}
                HAVING available_stock > 0
                ORDER BY pg.created_at DESC
            ";
            
            $products_result = $conn->query($products_query);
            
            if ($products_result && $products_result->num_rows > 0) {
                while ($product = $products_result->fetch_assoc()) {
                    // ✅ ใช้ group_id แทน product_id
                    $product_id_encoded = urlencode(base64_encode($product['group_id']));
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
                                ฿<?= number_format($product['price'] * (1 + $product['vat_percentage'] / 100), 0) ?>
                            </div>
                        </div>
                        <h3 class="product-name"><?= htmlspecialchars($product['product_name']) ?></h3>
                    </a>

                    <?php
                }
            } else {
                ?>
                <div class="empty-state">
                    <?php if ($searchQuery): ?>
                        <h3>
                            <?php 
                            echo match($lang) {
                                'en' => 'No products found',
                                'cn' => '未找到产品',
                                'jp' => '製品が見つかりません',
                                'kr' => '제품을 찾을 수 없습니다',
                                default => 'ไม่พบสินค้า',
                            };
                            ?>
                        </h3>
                        <p>
                            <?php 
                            echo match($lang) {
                                'en' => 'Try searching with different keywords',
                                'cn' => '尝试使用不同的关键词搜索',
                                'jp' => '別のキーワードで検索してみてください',
                                'kr' => '다른 키워드로 검색해 보세요',
                                default => 'ลองค้นหาด้วยคำค้นอื่น',
                            };
                            ?>
                        </p>
                    <?php else: ?>
                        <h3>
                            <?php 
                            echo match($lang) {
                                'en' => 'No products available',
                                'cn' => '暂无产品',
                                'jp' => '製品がありません',
                                'kr' => '제품이 없습니다',
                                default => 'ไม่พบสินค้า',
                            };
                            ?>
                        </h3>
                        <p>
                            <?php 
                            echo match($lang) {
                                'en' => 'No products are currently available for sale',
                                'cn' => '目前没有可供销售的产品',
                                'jp' => '現在販売可能な製品はありません',
                                'kr' => '현재 판매 가능한 제품이 없습니다',
                                default => 'ขณะนี้ยังไม่มีสินค้าที่พร้อมขาย',
                            };
                            ?>
                        </p>
                    <?php endif; ?>
                </div>
                <?php
            }
            ?>
        </div>
    </main>

    <?php include 'template/footer.php'; ?>
</body>
</html>