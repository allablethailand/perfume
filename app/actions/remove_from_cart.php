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

// Validate input
if ($cart_id <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid cart ID'
    ]);
    exit;
}

try {
    // Verify cart ownership and delete
    if ($user_id) {
        $delete_stmt = $conn->prepare("
            DELETE FROM cart 
            WHERE cart_id = ? AND user_id = ?
        ");
        $delete_stmt->bind_param('ii', $cart_id, $user_id);
    } else {
        $delete_stmt = $conn->prepare("
            DELETE FROM cart 
            WHERE cart_id = ? AND session_id = ? AND (user_id IS NULL OR user_id = 0)
        ");
        $delete_stmt->bind_param('is', $cart_id, $session_id);
    }
    
    $delete_stmt->execute();
    
    if ($delete_stmt->affected_rows === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Cart item not found or already removed'
        ]);
        exit;
    }
    
    $delete_stmt->close();
    
    // Get updated cart count
    if ($user_id) {
        $count_stmt = $conn->prepare("
            SELECT SUM(quantity) as count 
            FROM cart 
            WHERE user_id = ?
        ");
        $count_stmt->bind_param('i', $user_id);
    } else {
        $count_stmt = $conn->prepare("
            SELECT SUM(quantity) as count 
            FROM cart 
            WHERE session_id = ? AND (user_id IS NULL OR user_id = 0)
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
        'message' => 'Item removed from cart successfully',
        'cart_count' => $cart_count
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>