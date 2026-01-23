<?php
header('Content-Type: application/json');
require_once('../../lib/connect.php');
require_once('../../lib/jwt_helper.php');

global $conn;

// ตรวจสอบ Authorization header
$headers = getallheaders();
$jwt = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;

if (!$jwt) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// ตรวจสอบ JWT
$decoded = verifyJWT($jwt);
if (!$decoded) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid token']);
    exit;
}

$user_id = requireAuth();

// รับข้อมูล
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

if ($order_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid order ID']);
    exit;
}

try {
    // ตรวจสอบว่าออเดอร์เป็นของ user นี้หรือไม่
    $check_sql = "SELECT order_id FROM orders WHERE order_id = ? AND user_id = ? AND del = 0";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $order_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found']);
        exit;
    }
    
    // ✅ แก้ไข: ดึงรายการสินค้าจากออเดอร์ (รองรับทั้ง product_groups และ products)
    $items_sql = "SELECT 
                    oi.product_id, 
                    oi.quantity,
                    pi.group_id
                  FROM order_items oi
                  LEFT JOIN product_items pi ON oi.product_id = pi.item_id
                  WHERE oi.order_id = ?";
    $items_stmt = $conn->prepare($items_sql);
    $items_stmt->bind_param("i", $order_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    $added_count = 0;
    $session_id = session_id();
    
    while ($item = $items_result->fetch_assoc()) {
        $product_id = $item['product_id'];
        $quantity = $item['quantity'];
        $group_id = $item['group_id'];
        
        // ✅ ดึงข้อมูลสินค้าปัจจุบัน (ลองหาจาก product_groups ก่อน แล้วค่อย fallback ไป products)
        if ($group_id) {
            // กรณีเป็นสินค้าจาก product_groups
            $product_sql = "SELECT 
                              pg.price, 
                              pg.vat_percentage, 
                              pg.status, 
                              pg.del,
                              pi.item_id,
                              pi.status as item_status
                           FROM product_groups pg
                           INNER JOIN product_items pi ON pg.group_id = pi.group_id
                           WHERE pi.item_id = ? 
                           AND pg.status = 1 
                           AND pg.del = 0
                           AND pi.status = 'available'";
        } else {
            // กรณีเป็นสินค้าเก่าจาก products
            $product_sql = "SELECT 
                              price, 
                              vat_percentage, 
                              status, 
                              del,
                              product_id as item_id,
                              status as item_status
                           FROM products 
                           WHERE product_id = ? 
                           AND status = 1 
                           AND del = 0";
        }
        
        $product_stmt = $conn->prepare($product_sql);
        $product_stmt->bind_param("i", $product_id);
        $product_stmt->execute();
        $product_result = $product_stmt->get_result();
        $product = $product_result->fetch_assoc();
        
        if (!$product) {
            continue; // ข้ามสินค้าที่ไม่พร้อมจำหน่าย
        }
        
        // ✅ ตรวจสอบว่ามีสินค้าในตะกร้าแล้วหรือไม่
        $cart_check_sql = "SELECT cart_id, quantity 
                          FROM cart 
                          WHERE user_id = ? AND product_id = ? AND status = 1";
        $cart_check_stmt = $conn->prepare($cart_check_sql);
        $cart_check_stmt->bind_param("ii", $user_id, $product_id);
        $cart_check_stmt->execute();
        $cart_check_result = $cart_check_stmt->get_result();
        
        // ✅ เช็คว่าตาราง cart มี date_updated หรือไม่
        $check_column = $conn->query("SHOW COLUMNS FROM cart LIKE 'date_updated'");
        $has_date_updated = $check_column->num_rows > 0;
        
        if ($cart_check_result->num_rows > 0) {
            // อัพเดทจำนวน
            $cart_item = $cart_check_result->fetch_assoc();
            $new_quantity = $cart_item['quantity'] + $quantity;
            
            if ($has_date_updated) {
                $update_sql = "UPDATE cart 
                              SET quantity = ?, 
                                  price = ?,
                                  vat_percentage = ?,
                                  date_updated = NOW()
                              WHERE cart_id = ?";
            } else {
                $update_sql = "UPDATE cart 
                              SET quantity = ?, 
                                  price = ?,
                                  vat_percentage = ?
                              WHERE cart_id = ?";
            }
            
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("iddi", 
                $new_quantity, 
                $product['price'],
                $product['vat_percentage'],
                $cart_item['cart_id']
            );
            $update_stmt->execute();
        } else {
            // เพิ่มสินค้าใหม่
            if ($has_date_updated) {
                $insert_sql = "INSERT INTO cart 
                              (session_id, user_id, product_id, quantity, price, vat_percentage, status, date_created, date_updated)
                              VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())";
            } else {
                $insert_sql = "INSERT INTO cart 
                              (session_id, user_id, product_id, quantity, price, vat_percentage, status, date_created)
                              VALUES (?, ?, ?, ?, ?, ?, 1, NOW())";
            }
            
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("siiidi", 
                $session_id,
                $user_id,
                $product_id,
                $quantity,
                $product['price'],
                $product['vat_percentage']
            );
            $insert_stmt->execute();
        }
        
        $added_count++;
    }
    
    if ($added_count > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => "Added {$added_count} items to cart"
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No items available for reorder'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to reorder: ' . $e->getMessage()
    ]);
}
?>