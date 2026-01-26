<?php
header('Content-Type: application/json');
require_once('../../lib/connect.php');

global $conn;

// รับพารามิเตอร์
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$lang = isset($_GET['lang']) && in_array($_GET['lang'], ['th', 'en', 'cn', 'jp', 'kr']) ? $_GET['lang'] : 'th';

if (strlen($query) < 2) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Query too short'
    ]);
    exit;
}

$searchTerm = $conn->real_escape_string($query);

// กำหนดคอลัมน์ตามภาษา
$subject_col = 'subject_news' . ($lang !== 'th' ? '_' . $lang : '');
$description_col = 'description_news' . ($lang !== 'th' ? '_' . $lang : '');

// ค้นหาข่าว - แก้ไข query ให้ใช้ subquery
$news_query = "
    SELECT 
        n.news_id,
        n.{$subject_col} as title,
        n.{$description_col} as description,
        (
            SELECT dnc.api_path 
            FROM dn_news_doc dnc 
            WHERE dnc.news_id = n.news_id 
                AND dnc.del = '0' 
                AND dnc.status = '1' 
            ORDER BY dnc.id ASC 
            LIMIT 1
        ) AS image
    FROM dn_news n
    WHERE n.del = '0'
        AND n.status = '0'
        AND (
            n.subject_news LIKE '%{$searchTerm}%' OR 
            n.subject_news_en LIKE '%{$searchTerm}%' OR 
            n.subject_news_cn LIKE '%{$searchTerm}%' OR 
            n.subject_news_jp LIKE '%{$searchTerm}%' OR 
            n.subject_news_kr LIKE '%{$searchTerm}%' OR
            n.description_news LIKE '%{$searchTerm}%' OR 
            n.description_news_en LIKE '%{$searchTerm}%' OR 
            n.description_news_cn LIKE '%{$searchTerm}%' OR 
            n.description_news_jp LIKE '%{$searchTerm}%' OR 
            n.description_news_kr LIKE '%{$searchTerm}%' OR
            n.content_news LIKE '%{$searchTerm}%' OR 
            n.content_news_en LIKE '%{$searchTerm}%' OR 
            n.content_news_cn LIKE '%{$searchTerm}%' OR 
            n.content_news_jp LIKE '%{$searchTerm}%' OR 
            n.content_news_kr LIKE '%{$searchTerm}%'
        )
    ORDER BY n.date_create DESC
    LIMIT 5
";

$news_result = $conn->query($news_query);
$news = [];

if ($news_result && $news_result->num_rows > 0) {
    while ($row = $news_result->fetch_assoc()) {
        $news[] = [
            'news_id' => $row['news_id'],
            'title' => $row['title'] ?: '',
            'description' => $row['description'] ?: '',
            'image' => $row['image'] ?: null
        ];
    }
}

// ค้นหาสินค้า (เหมือนเดิม)
$product_name_col = 'name_' . $lang;
$product_desc_col = 'description_' . $lang;

$product_query = "
    SELECT 
        p.product_id,
        p.{$product_name_col} as name,
        p.{$product_desc_col} as description,
        p.price,
        pi.api_path as image
    FROM products p
    LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1 AND pi.del = 0
    WHERE p.del = 0 
        AND p.status = 1
        AND p.stock_quantity > 0
        AND (
            p.name_th LIKE '%{$searchTerm}%' OR
            p.name_en LIKE '%{$searchTerm}%' OR
            p.name_cn LIKE '%{$searchTerm}%' OR
            p.name_jp LIKE '%{$searchTerm}%' OR
            p.name_kr LIKE '%{$searchTerm}%' OR
            p.description_th LIKE '%{$searchTerm}%' OR
            p.description_en LIKE '%{$searchTerm}%' OR
            p.description_cn LIKE '%{$searchTerm}%' OR
            p.description_jp LIKE '%{$searchTerm}%' OR
            p.description_kr LIKE '%{$searchTerm}%'
        )
    ORDER BY p.created_at DESC
    LIMIT 5
";

$product_result = $conn->query($product_query);
$products = [];

if ($product_result && $product_result->num_rows > 0) {
    while ($row = $product_result->fetch_assoc()) {
        $products[] = [
            'product_id' => $row['product_id'],
            'name' => $row['name'] ?: '',
            'description' => $row['description'] ?: '',
            'price' => $row['price'],
            'image' => $row['image']
        ];
    }
}

echo json_encode([
    'status' => 'success',
    'news' => $news,
    'products' => $products
]);