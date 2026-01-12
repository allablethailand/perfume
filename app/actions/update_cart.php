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

try {
    // ✅ ถ้าจำนวนเป็น 0 ให้ทำ soft delete
    if ($quantity === 0) {
        if ($user_id) {
            $update_stmt = $conn->prepare("
                UPDATE cart 
                SET status = 0, date_updated = NOW()
                WHERE cart_id = ? AND user_id = ? AND status = 1
            ");
            $update_stmt->bind_param('ii', $cart_id, $user_id);
        } else {
            $update_stmt = $conn->prepare("
                UPDATE cart 
                SET status = 0, date_updated = NOW()
                WHERE cart_id = ? AND session_id = ? AND (user_id IS NULL OR user_id = 0) AND status = 1
            ");
            $update_stmt->bind_param('is', $cart_id, $session_id);
        }
        
        $update_stmt->execute();
        
        if ($update_stmt->affected_rows === 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Cart item not found'
            ]);
            exit;
        }
        
        $update_stmt->close();
        $message = 'Item removed from cart';
    } else {
        // ✅ อัพเดทจำนวนปกติ (เฉพาะรายการที่ status=1)
        if ($user_id) {
            $update_stmt = $conn->prepare("
                UPDATE cart 
                SET quantity = ?, date_updated = NOW()
                WHERE cart_id = ? AND user_id = ? AND status = 1
            ");
            $update_stmt->bind_param('iii', $quantity, $cart_id, $user_id);
        } else {
            $update_stmt = $conn->prepare("
                UPDATE cart 
                SET quantity = ?, date_updated = NOW()
                WHERE cart_id = ? AND session_id = ? AND (user_id IS NULL OR user_id = 0) AND status = 1
            ");
            $update_stmt->bind_param('iis', $quantity, $cart_id, $session_id);
        }
        
        $update_stmt->execute();
        
        if ($update_stmt->affected_rows === 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Cart item not found or no changes made'
            ]);
            exit;
        }
        
        $update_stmt->close();
        $message = 'Cart updated successfully';
    }
    
    // ✅ นับจำนวนสินค้าในตะกร้า (เฉพาะที่ status=1)
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
    
    $cart_count = $count_data['count'] ? intval($count_data['count']) : 0;
    
    echo json_encode([
        'status' => 'success',
        'message' => $message,
        'cart_count' => $cart_count,
        'cart_id' => $cart_id,
        'quantity' => $quantity
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>