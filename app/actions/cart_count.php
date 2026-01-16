<?php
require_once('../../lib/connect.php');
global $conn;

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set JSON header
header('Content-Type: application/json');

// ✅ Get JWT token from multiple sources
$headers = getallheaders();
$jwt = null;
$user_id = null;

// 1. Check Authorization header (from AJAX)
if (isset($headers['Authorization'])) {
    $jwt = str_replace('Bearer ', '', $headers['Authorization']);
}

// 2. Check sessionStorage via custom header
if (!$jwt && isset($headers['X-Auth-Token'])) {
    $jwt = $headers['X-Auth-Token'];
}

// 3. Check cookie as fallback
if (!$jwt && isset($_COOKIE['jwt'])) {
    $jwt = $_COOKIE['jwt'];
}

// Verify JWT if exists
if ($jwt) {
    require_once('../../lib/jwt_helper.php');
    $decoded = verifyJWT($jwt);
    if ($decoded) {
        try {
            $user_id = requireAuth();
        } catch (Exception $e) {
            // JWT invalid or expired
            $jwt = null;
            $user_id = null;
        }
    }
}

// Get session_id for guest users
if (!isset($_SESSION['guest_session_id'])) {
    $_SESSION['guest_session_id'] = session_id();
}
$session_id = $_SESSION['guest_session_id'];

try {
    // ✅ แก้ไข: นับเฉพาะรายการที่ status=1
    if ($user_id) {
        $count_stmt = $conn->prepare("
            SELECT SUM(c.quantity) as count 
            FROM cart c
            INNER JOIN products p ON c.product_id = p.product_id
            WHERE c.user_id = ? AND c.status = 1 AND p.del = 0
        ");
        $count_stmt->bind_param('i', $user_id);
    } else {
        $count_stmt = $conn->prepare("
            SELECT SUM(c.quantity) as count 
            FROM cart c
            INNER JOIN products p ON c.product_id = p.product_id
            WHERE c.session_id = ? 
            AND (c.user_id IS NULL OR c.user_id = 0)
            AND c.status = 1
            AND p.del = 0
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
        'count' => $cart_count,
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage(),
        'count' => 0
    ]);
}
?>