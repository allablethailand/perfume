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

// Get POST data
$cart_id = isset($_POST['cart_id']) ? intval($_POST['cart_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

// Validate input
if ($cart_id <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid cart ID'
    ]);
    exit;
}

if ($quantity < 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid quantity'
    ]);
    exit;
}

// ✅ Start transaction
$conn->begin_transaction();

try {
    // ✅ ดึงข้อมูล cart และ product พร้อม lock
    if ($user_id) {
        $cart_stmt = $conn->prepare("
            SELECT c.cart_id, c.product_id, c.quantity, p.stock_quantity, p.status
            FROM cart c
            INNER JOIN products p ON c.product_id = p.product_id
            WHERE c.cart_id = ? AND c.user_id = ? AND c.status = 1
            FOR UPDATE
        ");
        $cart_stmt->bind_param('ii', $cart_id, $user_id);
    } else {
        $cart_stmt = $conn->prepare("
            SELECT c.cart_id, c.product_id, c.quantity, p.stock_quantity, p.status
            FROM cart c
            INNER JOIN products p ON c.product_id = p.product_id
            WHERE c.cart_id = ? AND c.session_id = ? AND (c.user_id IS NULL OR c.user_id = 0) AND c.status = 1
            FOR UPDATE
        ");
        $cart_stmt->bind_param('is', $cart_id, $session_id);
    }
    
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    
    if ($cart_result->num_rows === 0) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => 'Cart item not found'
        ]);
        exit;
    }
    
    $cart_data = $cart_result->fetch_assoc();
    $cart_stmt->close();
    
    // ✅ เช็คว่าสินค้ายังพร้อมขายหรือไม่
    if ($cart_data['status'] != 1) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => 'Product is no longer available'
        ]);
        exit;
    }
    
    // ✅ ถ้าจำนวนเป็น 0 ให้ทำ soft delete
    if ($quantity === 0) {
        $update_stmt = $conn->prepare("
            UPDATE cart 
            SET status = 0, date_updated = NOW()
            WHERE cart_id = ?
        ");
        $update_stmt->bind_param('i', $cart_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        $conn->commit();
        
        // นับจำนวนสินค้าในตะกร้า
        $cart_count = getCartCount($conn, $user_id, $session_id);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Item removed from cart',
            'cart_count' => $cart_count,
            'cart_id' => $cart_id,
            'quantity' => 0
        ]);
        exit;
    }
    
    // ✅ เช็ค stock ก่อนอัพเดท
    $stock_quantity = intval($cart_data['stock_quantity']);
    
    if ($quantity > $stock_quantity) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => 'Stock not available. Please reduce quantity.',
            'available_stock' => $stock_quantity,
            'requested_quantity' => $quantity
        ]);
        exit;
    }
    
    if ($stock_quantity <= 0) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => 'Product is out of stock',
            'available_stock' => 0
        ]);
        exit;
    }
    
    // ✅ อัพเดทจำนวนปกติ
    $update_stmt = $conn->prepare("
        UPDATE cart 
        SET quantity = ?, date_updated = NOW()
        WHERE cart_id = ?
    ");
    $update_stmt->bind_param('ii', $quantity, $cart_id);
    $update_stmt->execute();
    
    if ($update_stmt->affected_rows === 0) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => 'Cart item not found or no changes made'
        ]);
        exit;
    }
    
    $update_stmt->close();
    
    // ✅ Commit transaction
    $conn->commit();
    
    // ✅ นับจำนวนสินค้าในตะกร้า
    $cart_count = getCartCount($conn, $user_id, $session_id);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Cart updated successfully',
        'cart_count' => $cart_count,
        'cart_id' => $cart_id,
        'quantity' => $quantity,
        'available_stock' => $stock_quantity
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

// ✅ Helper function to get cart count
function getCartCount($conn, $user_id, $session_id) {
    if ($user_id) {
        $count_stmt = $conn->prepare("
            SELECT SUM(quantity) as count 
            FROM cart 
            WHERE user_id = ? AND status = 1
        ");
        $count_stmt->bind_param('i', $user_id);
    } else {
        $count_stmt = $conn->prepare("
            SELECT SUM(quantity) as count 
            FROM cart 
            WHERE session_id = ? AND (user_id IS NULL OR user_id = 0) AND status = 1
        ");
        $count_stmt->bind_param('s', $session_id);
    }
    
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_data = $count_result->fetch_assoc();
    $count_stmt->close();
    
    return $count_data['count'] ? intval($count_data['count']) : 0;
}
?>