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
    
    // ดึงรายการสินค้าจากออเดอร์
    $items_sql = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
    $items_stmt = $conn->prepare($items_sql);
    $items_stmt->bind_param("i", $order_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    $added_count = 0;
    $session_id = session_id();
    
    while ($item = $items_result->fetch_assoc()) {
        $product_id = $item['product_id'];
        $quantity = $item['quantity'];
        
        // ดึงข้อมูลสินค้าปัจจุบัน
        $product_sql = "SELECT price, vat_percentage, status, del 
                       FROM products 
                       WHERE product_id = ? AND status = 1 AND del = 0";
        $product_stmt = $conn->prepare($product_sql);
        $product_stmt->bind_param("i", $product_id);
        $product_stmt->execute();
        $product_result = $product_stmt->get_result();
        $product = $product_result->fetch_assoc();
        
        if (!$product) {
            continue; // ข้ามสินค้าที่ไม่พร้อมจำหน่าย
        }
        
        // ตรวจสอบว่ามีสินค้าในตะกร้าแล้วหรือไม่
        $cart_check_sql = "SELECT cart_id, quantity 
                          FROM cart 
                          WHERE user_id = ? AND product_id = ?";
        $cart_check_stmt = $conn->prepare($cart_check_sql);
        $cart_check_stmt->bind_param("ii", $user_id, $product_id);
        $cart_check_stmt->execute();
        $cart_check_result = $cart_check_stmt->get_result();
        
        if ($cart_check_result->num_rows > 0) {
            // อัพเดทจำนวน
            $cart_item = $cart_check_result->fetch_assoc();
            $new_quantity = $cart_item['quantity'] + $quantity;
            
            $update_sql = "UPDATE cart 
                          SET quantity = ?, 
                              price = ?,
                              vat_percentage = ?,
                              date_updated = NOW()
                          WHERE cart_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("idii", 
                $new_quantity, 
                $product['price'],
                $product['vat_percentage'],
                $cart_item['cart_id']
            );
            $update_stmt->execute();
        } else {
            // เพิ่มสินค้าใหม่
            $insert_sql = "INSERT INTO cart 
                          (session_id, user_id, product_id, quantity, price, vat_percentage, date_created, date_updated)
                          VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
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