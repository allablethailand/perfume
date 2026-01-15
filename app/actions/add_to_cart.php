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

// ✅ บังคับให้ล็อกอินก่อน
if (!$user_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please login first',
        'require_login' => true
    ]);
    exit;
}

// Get session_id
if (!isset($_SESSION['guest_session_id'])) {
    $_SESSION['guest_session_id'] = session_id();
}
$session_id = $_SESSION['guest_session_id'];

// Get POST data
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

// Validate input
if ($product_id <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid product ID'
    ]);
    exit;
}

if ($quantity <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid quantity'
    ]);
    exit;
}

// ✅ Start transaction to prevent race condition
$conn->begin_transaction();

try {
    // Get product details with lock
    $stmt = $conn->prepare("
        SELECT product_id, price, vat_percentage, status, stock_quantity 
        FROM products 
        WHERE product_id = ? AND del = 0
        FOR UPDATE
    ");
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => 'Product not found'
        ]);
        exit;
    }
    
    $product = $result->fetch_assoc();
    $stmt->close();
    
    // Check if product is available
    if ($product['status'] != 1) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => 'Product is not available'
        ]);
        exit;
    }
    
    // Check stock
    if ($product['stock_quantity'] <= 0) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => 'Product is out of stock'
        ]);
        exit;
    }
    
    // ✅ แก้ไข: ตรวจสอบเฉพาะรายการที่ active (status=1) เท่านั้น
    // ไม่สนใจรายการที่ status=0 (ถูกลบแล้ว)
    $check_stmt = $conn->prepare("
        SELECT cart_id, quantity 
        FROM cart 
        WHERE user_id = ? AND product_id = ? AND status = 1
        FOR UPDATE
    ");
    $check_stmt->bind_param('ii', $user_id, $product_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // ✅ มีรายการที่ active อยู่แล้ว ให้เพิ่มจำนวน
        $existing = $check_result->fetch_assoc();
        $new_quantity = $existing['quantity'] + $quantity;
        
        // เช็ค stock
        if ($new_quantity > $product['stock_quantity']) {
            $conn->rollback();
            echo json_encode([
                'status' => 'error',
                'message' => 'Stock not available. Please reduce quantity.'
            ]);
            exit;
        }
        
        $update_stmt = $conn->prepare("
            UPDATE cart 
            SET quantity = quantity + ?, 
                price = ?,
                vat_percentage = ?,
                date_updated = NOW() 
            WHERE cart_id = ?
        ");
        $update_stmt->bind_param('iddi', $quantity, $product['price'], $product['vat_percentage'], $existing['cart_id']);
        
        if (!$update_stmt->execute()) {
            throw new Exception('Failed to update cart: ' . $update_stmt->error);
        }
        $update_stmt->close();
        
        $message = 'Cart updated successfully';
    } else {
        // ✅ ไม่มีรายการที่ active ให้สร้างใหม่เลย
        // (ไม่สนใจว่าจะมีรายการ status=0 อยู่หรือไม่)
        
        // เช็ค stock
        if ($quantity > $product['stock_quantity']) {
            $conn->rollback();
            echo json_encode([
                'status' => 'error',
                'message' => 'Stock not available. Please reduce quantity.'
            ]);
            exit;
        }
        
        $insert_stmt = $conn->prepare("
            INSERT INTO cart (user_id, session_id, product_id, quantity, price, vat_percentage, status, date_created, date_updated) 
            VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
        ");
        $insert_stmt->bind_param('isiids', $user_id, $session_id, $product_id, $quantity, $product['price'], $product['vat_percentage']);
        
        if (!$insert_stmt->execute()) {
            throw new Exception('Failed to insert cart item: ' . $insert_stmt->error);
        }
        $insert_stmt->close();
        
        $message = 'Product added to cart successfully';
    }
    
    $check_stmt->close();
    
    // ✅ Commit transaction
    $conn->commit();
    
    // ✅ นับจำนวนสินค้าในตะกร้า (เฉพาะที่ status=1)
    $count_stmt = $conn->prepare("
        SELECT SUM(quantity) as count 
        FROM cart 
        WHERE user_id = ? AND status = 1
    ");
    $count_stmt->bind_param('i', $user_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_data = $count_result->fetch_assoc();
    $count_stmt->close();
    
    $cart_count = $count_data['count'] ? intval($count_data['count']) : 0;
    
    echo json_encode([
        'status' => 'success',
        'message' => $message,
        'cart_count' => $cart_count,
        'product_id' => $product_id,
        'quantity' => $quantity
    ]);
    
} catch (Exception $e) {
    // ✅ Rollback on error
    $conn->rollback();
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>