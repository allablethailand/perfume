<?php
header('Content-Type: application/json');
require_once('../../lib/connect.php');
require_once('../../lib/jwt_helper.php');

global $conn;

// ✅ ใช้ requireAuth()
$user_id = requireAuth();

// รับ order_id
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid order ID']);
    exit;
}

try {
    // ตรวจสอบว่า order เป็นของ user นี้หรือไม่
    $check_sql = "SELECT order_id FROM orders WHERE order_id = ? AND user_id = ? AND del = 0";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $order_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found']);
        exit;
    }
    $check_stmt->close();
    
    // ดึงข้อมูล Order
    $order_sql = "SELECT 
                    o.order_id,
                    o.order_number,
                    o.user_id,
                    o.address_id,
                    o.subtotal,
                    o.vat_amount,
                    o.total_amount,
                    o.shipping_fee,
                    o.discount_amount,
                    o.coupon_code,
                    o.order_status,
                    o.payment_status,
                    o.payment_method,
                    o.notes,
                    o.date_created,
                    o.date_updated
                  FROM orders o
                  WHERE o.order_id = ? AND o.del = 0";
    
    $order_stmt = $conn->prepare($order_sql);
    $order_stmt->bind_param("i", $order_id);
    $order_stmt->execute();
    $order_result = $order_stmt->get_result();
    
    if ($order_result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found']);
        exit;
    }
    
    $order = $order_result->fetch_assoc();
    $order_stmt->close();
    
    // ดึงรายการสินค้า
    $items_sql = "SELECT 
                    oi.order_item_id,
                    oi.product_id,
                    oi.product_name,
                    oi.quantity,
                    oi.unit_price,
                    oi.vat_percentage,
                    oi.unit_price_with_vat,
                    oi.subtotal,
                    oi.vat_amount,
                    oi.total,
                    p.name_th,
                    p.name_en
                  FROM order_items oi
                  LEFT JOIN products p ON oi.product_id = p.product_id
                  WHERE oi.order_id = ?
                  ORDER BY oi.order_item_id";
    
    $items_stmt = $conn->prepare($items_sql);
    $items_stmt->bind_param("i", $order['order_id']);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    $items = [];
    while ($item = $items_result->fetch_assoc()) {
        // ดึงรูปภาพสินค้า
        $image_sql = "SELECT api_path 
                     FROM product_images 
                     WHERE product_id = ? AND is_primary = 1 AND del = 0 
                     LIMIT 1";
        $image_stmt = $conn->prepare($image_sql);
        $image_stmt->bind_param("i", $item['product_id']);
        $image_stmt->execute();
        $image_result = $image_stmt->get_result();
        $image = $image_result->fetch_assoc();
        $image_stmt->close();
        
        $item['product_image'] = $image ? $image['api_path'] : null;
        $items[] = $item;
    }
    
    $items_stmt->close();
    $order['items'] = $items;
    
    // ดึงที่อยู่จัดส่ง (เพิ่ม country)
    if ($order['address_id']) {
        $address_sql = "SELECT 
                          address_id,
                          address_label,
                          recipient_name,
                          recipient_phone,
                          address_line1,
                          address_line2,
                          subdistrict,
                          district,
                          province,
                          country,
                          postal_code
                      FROM user_addresses
                      WHERE address_id = ? AND del = 0";
        
        $address_stmt = $conn->prepare($address_sql);
        $address_stmt->bind_param("i", $order['address_id']);
        $address_stmt->execute();
        $address_result = $address_stmt->get_result();
        $order['shipping_address'] = $address_result->fetch_assoc();
        $address_stmt->close();
    } else {
        $order['shipping_address'] = null;
    }
    
    // ✅ ดึงหลักฐานการโอนเงิน (Payment Slip)
    $slip_sql = "SELECT 
                    slip_id,
                    order_id,
                    user_id,
                    file_name,
                    file_path,
                    file_size,
                    transfer_date,
                    transfer_amount,
                    notes,
                    status,
                    date_uploaded,
                    date_verified,
                    verified_by
                 FROM payment_slips
                 WHERE order_id = ? AND user_id = ?
                 ORDER BY date_uploaded DESC
                 LIMIT 1";
    
    $slip_stmt = $conn->prepare($slip_sql);
    $slip_stmt->bind_param("ii", $order['order_id'], $user_id);
    $slip_stmt->execute();
    $slip_result = $slip_stmt->get_result();
    
    if ($slip_result->num_rows > 0) {
        $order['payment_slip'] = $slip_result->fetch_assoc();
    } else {
        $order['payment_slip'] = null;
    }
    $slip_stmt->close();
    
    // แปลง status
    $order['order_status_label'] = getOrderStatusLabel($order['order_status']);
    $order['payment_status_label'] = getPaymentStatusLabel($order['payment_status']);
    
    echo json_encode([
        'status' => 'success',
        'data' => $order
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch order detail: ' . $e->getMessage()
    ]);
}

// ฟังก์ชันแปลง status
function getOrderStatusLabel($status) {
    $labels = [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled'
    ];
    return isset($labels[$status]) ? $labels[$status] : ucfirst($status);
}

function getPaymentStatusLabel($status) {
    $labels = [
        'paid' => 'Paid',
        'unpaid' => 'Unpaid',
        'refunded' => 'Refunded'
    ];
    return isset($labels[$status]) ? $labels[$status] : ucfirst($status);
}
?>