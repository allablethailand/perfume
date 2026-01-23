<?php
header('Content-Type: application/json');
require_once('../../lib/connect.php');
require_once('../../lib/jwt_helper.php');

global $conn;

// ✅ ใช้ requireAuth()
$user_id = requireAuth();

// รับค่า filter
$order_status = isset($_GET['order_status']) ? $_GET['order_status'] : '';
$payment_status = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';

try {
    // สร้าง SQL query
    $sql = "SELECT 
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
            WHERE o.user_id = ? AND o.del = 0";
    
    $params = [$user_id];
    $types = "i";
    
    // เพิ่ม filter
    if (!empty($order_status)) {
        $sql .= " AND o.order_status = ?";
        $params[] = $order_status;
        $types .= "s";
    }
    
    if (!empty($payment_status)) {
        $sql .= " AND o.payment_status = ?";
        $params[] = $payment_status;
        $types .= "s";
    }
    
    $sql .= " ORDER BY o.date_created DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    
    while ($order = $result->fetch_assoc()) {
        // ✅ แก้ไข: ดึงรายการสินค้ารองรับทั้ง product_groups และ products
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
                        COALESCE(pg.name_th, p.name_th) as name_th,
                        COALESCE(pg.name_en, p.name_en) as name_en,
                        COALESCE(pg.name_cn, p.name_cn) as name_cn,
                        COALESCE(pg.name_jp, p.name_jp) as name_jp,
                        COALESCE(pg.name_kr, p.name_kr) as name_kr,
                        pi.serial_number
                      FROM order_items oi
                      LEFT JOIN product_items pi ON oi.product_id = pi.item_id
                      LEFT JOIN product_groups pg ON pi.group_id = pg.group_id AND pg.del = 0
                      LEFT JOIN products p ON oi.product_id = p.product_id AND p.del = 0
                      WHERE oi.order_id = ?
                      ORDER BY oi.order_item_id";
        
        $items_stmt = $conn->prepare($items_sql);
        $items_stmt->bind_param("i", $order['order_id']);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        
        $items = [];
        while ($item = $items_result->fetch_assoc()) {
            // ✅ ดึงรูปภาพ (รองรับทั้ง 2 ระบบ)
            $image_sql = "SELECT COALESCE(
                            (SELECT pgi.api_path 
                             FROM product_items pi2
                             INNER JOIN product_groups pg2 ON pi2.group_id = pg2.group_id
                             INNER JOIN product_group_images pgi ON pg2.group_id = pgi.group_id
                             WHERE pi2.item_id = ? AND pgi.is_primary = 1 AND pgi.del = 0
                             LIMIT 1),
                            (SELECT pi_img.api_path 
                             FROM product_images pi_img
                             WHERE pi_img.product_id = ? AND pi_img.is_primary = 1 AND pi_img.del = 0
                             LIMIT 1)
                          ) as api_path";
            
            $image_stmt = $conn->prepare($image_sql);
            $image_stmt->bind_param("ii", $item['product_id'], $item['product_id']);
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
        
        // แปลง status
        $order['order_status_label'] = getOrderStatusLabel($order['order_status']);
        $order['payment_status_label'] = getPaymentStatusLabel($order['payment_status']);
        
        $orders[] = $order;
    }
    
    $stmt->close();
    
    echo json_encode([
        'status' => 'success',
        'data' => $orders
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch orders: ' . $e->getMessage()
    ]);
}

// ✅ แก้ไข: ฟังก์ชันแปลง status ให้ตรงกับ enum
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
        'pending' => 'Pending Payment',  // ✅ แก้จาก unpaid
        'paid' => 'Paid',
        'failed' => 'Payment Failed',    // ✅ เพิ่ม
        'refunded' => 'Refunded'
    ];
    return isset($labels[$status]) ? $labels[$status] : ucfirst($status);
}
?>