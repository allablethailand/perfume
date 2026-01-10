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

try {
    // Get product details
    $stmt = $conn->prepare("
        SELECT product_id, price, vat_percentage, status 
        FROM products 
        WHERE product_id = ? AND del = 0
    ");
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
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
        echo json_encode([
            'status' => 'error',
            'message' => 'Product is not available'
        ]);
        exit;
    }
    
    // Check if item already exists in cart (สำหรับ logged-in user เท่านั้น)
    $check_stmt = $conn->prepare("
        SELECT cart_id, quantity 
        FROM cart 
        WHERE user_id = ? AND product_id = ?
    ");
    $check_stmt->bind_param('ii', $user_id, $product_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update existing cart item
        $existing = $check_result->fetch_assoc();
        $new_quantity = $existing['quantity'] + $quantity;
        
        $update_stmt = $conn->prepare("
            UPDATE cart 
            SET quantity = ?, 
                price = ?,
                vat_percentage = ?,
                date_updated = NOW() 
            WHERE cart_id = ?
        ");
        $update_stmt->bind_param('iddi', $new_quantity, $product['price'], $product['vat_percentage'], $existing['cart_id']);
        $update_stmt->execute();
        $update_stmt->close();
        
        $message = 'Cart updated successfully';
    } else {
        // Insert new cart item
        $insert_stmt = $conn->prepare("
            INSERT INTO cart (user_id, session_id, product_id, quantity, price, vat_percentage, date_created, date_updated) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $insert_stmt->bind_param('isiids', $user_id, $session_id, $product_id, $quantity, $product['price'], $product['vat_percentage']);
        
        if (!$insert_stmt->execute()) {
            throw new Exception('Failed to insert cart item: ' . $insert_stmt->error);
        }
        $insert_stmt->close();
        
        $message = 'Product added to cart successfully';
    }
    
    $check_stmt->close();
    
    // Get updated cart count
    $count_stmt = $conn->prepare("
        SELECT SUM(quantity) as count 
        FROM cart 
        WHERE user_id = ?
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
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>