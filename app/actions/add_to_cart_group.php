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

// ✅ รับ group_id แทน product_id
$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

// Validate input
if ($group_id <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid group ID'
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
    // ✅ ดึงข้อมูล product group
    $stmt_group = $conn->prepare("
        SELECT group_id, price, vat_percentage, status 
        FROM product_groups 
        WHERE group_id = ? AND del = 0
        FOR UPDATE
    ");
    $stmt_group->bind_param('i', $group_id);
    $stmt_group->execute();
    $result_group = $stmt_group->get_result();
    
    if ($result_group->num_rows === 0) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => 'Product group not found'
        ]);
        exit;
    }
    
    $group = $result_group->fetch_assoc();
    $stmt_group->close();
    
    // Check if product is available
    if ($group['status'] != 1) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => 'Product is not available'
        ]);
        exit;
    }
    
    // ✅ สุ่มเลือกขวดที่ available จำนวนที่ต้องการ
    $stmt_bottles = $conn->prepare("
        SELECT item_id, serial_number 
        FROM product_items 
        WHERE group_id = ? AND status = 'available' AND del = 0
        ORDER BY RAND()
        LIMIT ?
        FOR UPDATE
    ");
    $stmt_bottles->bind_param('ii', $group_id, $quantity);
    $stmt_bottles->execute();
    $result_bottles = $stmt_bottles->get_result();
    
    if ($result_bottles->num_rows < $quantity) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => 'Not enough stock available'
        ]);
        exit;
    }
    
    $selected_bottles = [];
    while ($bottle = $result_bottles->fetch_assoc()) {
        $selected_bottles[] = $bottle;
    }
    $stmt_bottles->close();
    
    // ✅ เพิ่มแต่ละขวดลงตะกร้า
    $items_added = 0;
    
    foreach ($selected_bottles as $bottle) {
        $item_id = $bottle['item_id'];
        
        // ✅ เช็คว่ามีขวดนี้ในตะกร้าแล้วหรือยัง (status=1)
        $check_stmt = $conn->prepare("
            SELECT cart_id, quantity 
            FROM cart 
            WHERE user_id = ? AND product_id = ? AND status = 1
        ");
        $check_stmt->bind_param('ii', $user_id, $item_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // ✅ มีอยู่แล้ว แต่เพิ่มจำนวนไม่ได้ เพราะขวดมีอันเดียว
            // ดังนั้นข้ามไปขวดอื่น
            $check_stmt->close();
            continue;
        }
        $check_stmt->close();
        
        // ✅ เพิ่มขวดใหม่ลงตะกร้า
        $insert_stmt = $conn->prepare("
            INSERT INTO cart (user_id, session_id, product_id, quantity, price, vat_percentage, status, date_created, date_updated) 
            VALUES (?, ?, ?, 1, ?, ?, 1, NOW(), NOW())
        ");
        $insert_stmt->bind_param('isidd', $user_id, $session_id, $item_id, $group['price'], $group['vat_percentage']);
        
        if ($insert_stmt->execute()) {
            $items_added++;
        }
        $insert_stmt->close();
    }
    
    // ✅ Commit transaction
    $conn->commit();
    
    if ($items_added > 0) {
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
            'message' => "เพิ่มสินค้าลงตะกร้าแล้ว ($items_added ขวด)",
            'cart_count' => $cart_count,
            'group_id' => $group_id,
            'items_added' => $items_added,
            'bottles' => array_map(function($b) { return $b['serial_number']; }, $selected_bottles)
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No items were added (already in cart or unavailable)'
        ]);
    }
    
} catch (Exception $e) {
    // ✅ Rollback on error
    $conn->rollback();
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>