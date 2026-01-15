<?php
require_once('../../lib/connect.php');
global $conn;

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set JSON header
header('Content-Type: application/json');

// Get JWT token from Authorization header
$headers = getallheaders();
$jwt = null;
$user_id = null;

if (isset($headers['Authorization'])) {
    $jwt = str_replace('Bearer ', '', $headers['Authorization']);
    
    // Verify JWT and get user_id
    require_once('../../lib/jwt_helper.php');
    $decoded = verifyJWT($jwt);
    if ($decoded) {
        $user_id = requireAuth();
    }
}

// Get session_id for guest users
if (!isset($_SESSION['guest_session_id'])) {
    $_SESSION['guest_session_id'] = session_id();
}
$session_id = $_SESSION['guest_session_id'];

// Get language
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'th';
if (!in_array($lang, ['th', 'en', 'cn', 'jp', 'kr'])) {
    $lang = 'th';
}

// Column names based on language
$name_col = "name_" . $lang;

try {
    // ✅ แก้ไข: ดึงเฉพาะรายการที่ status=1 (ยังไม่ถูกลบ) และเพิ่ม stock_quantity
    if ($user_id) {
        $query = "
            SELECT 
                c.cart_id,
                c.product_id,
                c.quantity,
                c.price,
                c.vat_percentage,
                ROUND(c.price * (1 + c.vat_percentage / 100), 2) as price_with_vat,
                p.{$name_col} as product_name,
                p.status,
                p.stock_quantity,
                (SELECT pi.api_path 
                 FROM product_images pi 
                 WHERE pi.product_id = p.product_id 
                 AND pi.del = 0 
                 ORDER BY pi.is_primary DESC, pi.display_order ASC 
                 LIMIT 1) as product_image
            FROM cart c
            INNER JOIN products p ON c.product_id = p.product_id
            WHERE c.user_id = ? 
            AND c.status = 1
            AND p.del = 0
            ORDER BY c.date_created DESC
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $user_id);
    } else {
        $query = "
            SELECT 
                c.cart_id,
                c.product_id,
                c.quantity,
                c.price,
                c.vat_percentage,
                ROUND(c.price * (1 + c.vat_percentage / 100), 2) as price_with_vat,
                p.{$name_col} as product_name,
                p.status,
                p.stock_quantity,
                (SELECT pi.api_path 
                 FROM product_images pi 
                 WHERE pi.product_id = p.product_id 
                 AND pi.del = 0 
                 ORDER BY pi.is_primary DESC, pi.display_order ASC 
                 LIMIT 1) as product_image
            FROM cart c
            INNER JOIN products p ON c.product_id = p.product_id
            WHERE c.session_id = ? 
            AND (c.user_id IS NULL OR c.user_id = 0)
            AND c.status = 1
            AND p.del = 0
            ORDER BY c.date_created DESC
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $session_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    $subtotal = 0;
    $total_vat = 0;
    $total_items = 0;
    $has_stock_issue = false;
    
    while ($row = $result->fetch_assoc()) {
        $item_price_with_vat = floatval($row['price_with_vat']);
        $item_quantity = intval($row['quantity']);
        $stock_quantity = intval($row['stock_quantity']);
        $item_total = $item_price_with_vat * $item_quantity;
        
        // คำนวณ VAT
        $item_price_before_vat = floatval($row['price']);
        $item_vat = ($item_price_before_vat * $item_quantity * floatval($row['vat_percentage'])) / 100;
        
        // ✅ เช็คว่า stock พอหรือไม่
        $is_out_of_stock = ($stock_quantity <= 0);
        $is_exceeds_stock = ($item_quantity > $stock_quantity);
        
        if ($is_out_of_stock || $is_exceeds_stock) {
            $has_stock_issue = true;
        }
        
        $items[] = [
            'cart_id' => $row['cart_id'],
            'product_id' => $row['product_id'],
            'product_name' => $row['product_name'],
            'product_image' => $row['product_image'] ?: 'public/img/no-image.png',
            'price' => $item_price_before_vat,
            'price_with_vat' => $item_price_with_vat,
            'vat_percentage' => floatval($row['vat_percentage']),
            'quantity' => $item_quantity,
            'stock_quantity' => $stock_quantity,
            'is_out_of_stock' => $is_out_of_stock,
            'is_exceeds_stock' => $is_exceeds_stock,
            'item_total' => $item_total,
            'status' => $row['status']
        ];
        
        $subtotal += ($item_price_before_vat * $item_quantity);
        $total_vat += $item_vat;
        $total_items += $item_quantity;
    }
    
    $stmt->close();
    
    $total = $subtotal + $total_vat;
    
    // คำนวณ average VAT percentage
    $avg_vat_percentage = $subtotal > 0 ? ($total_vat / $subtotal) * 100 : 0;
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'items' => $items,
            'summary' => [
                'subtotal' => round($subtotal, 2),
                'vat_amount' => round($total_vat, 2),
                'vat_percentage' => round($avg_vat_percentage, 2),
                'total' => round($total, 2),
                'total_items' => $total_items,
                'has_stock_issue' => $has_stock_issue
            ]
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>