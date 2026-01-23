<?php
header('Content-Type: application/json');
require_once('../../lib/connect.php');
require_once('../../lib/jwt_helper.php');

global $conn;

// ✅ ใช้ requireAuth() เพื่อตรวจสอบการล็อกอิน
$user_id = requireAuth();

// รับข้อมูล
$address_id = isset($_POST['address_id']) ? intval($_POST['address_id']) : 0;
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'bank_transfer';
$selected_cart_ids = isset($_POST['selected_cart_ids']) ? $_POST['selected_cart_ids'] : ''; // รับรายการที่เลือก

if ($address_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Please select delivery address']);
    exit;
}

try {
    // เริ่ม transaction
    $conn->begin_transaction();
    
    // ✅ แก้ไข: ดึงข้อมูล Cart จาก product_groups แทน products
    if (!empty($selected_cart_ids)) {
        // กรณีเลือกเฉพาะบางรายการ
        $cart_ids_array = explode(',', $selected_cart_ids);
        $placeholders = str_repeat('?,', count($cart_ids_array) - 1) . '?';
        
        $cart_sql = "SELECT 
                        c.cart_id, 
                        c.product_id, 
                        c.quantity, 
                        c.price, 
                        c.vat_percentage,
                        COALESCE(pg.name_th, p.name_th) as name_th,
                        COALESCE(pg.name_en, p.name_en) as name_en,
                        COALESCE(pg.name_cn, p.name_cn) as name_cn,
                        COALESCE(pg.name_jp, p.name_jp) as name_jp,
                        COALESCE(pg.name_kr, p.name_kr) as name_kr,
                        pi.serial_number,
                        pi.group_id
                     FROM cart c
                     LEFT JOIN product_items pi ON c.product_id = pi.item_id
                     LEFT JOIN product_groups pg ON pi.group_id = pg.group_id AND pg.del = 0
                     LEFT JOIN products p ON c.product_id = p.product_id AND p.del = 0
                     WHERE c.user_id = ? AND c.status = 1 AND c.cart_id IN ($placeholders)";
        
        $cart_stmt = $conn->prepare($cart_sql);
        
        // Bind parameters
        $types = str_repeat('i', count($cart_ids_array) + 1);
        $params = array_merge([$user_id], $cart_ids_array);
        $cart_stmt->bind_param($types, ...$params);
    } else {
        // กรณีซื้อทั้งหมดในตะกร้า
        $cart_sql = "SELECT 
                        c.cart_id, 
                        c.product_id, 
                        c.quantity, 
                        c.price, 
                        c.vat_percentage,
                        COALESCE(pg.name_th, p.name_th) as name_th,
                        COALESCE(pg.name_en, p.name_en) as name_en,
                        COALESCE(pg.name_cn, p.name_cn) as name_cn,
                        COALESCE(pg.name_jp, p.name_jp) as name_jp,
                        COALESCE(pg.name_kr, p.name_kr) as name_kr,
                        pi.serial_number,
                        pi.group_id
                     FROM cart c
                     LEFT JOIN product_items pi ON c.product_id = pi.item_id
                     LEFT JOIN product_groups pg ON pi.group_id = pg.group_id AND pg.del = 0
                     LEFT JOIN products p ON c.product_id = p.product_id AND p.del = 0
                     WHERE c.user_id = ? AND c.status = 1";
        
        $cart_stmt = $conn->prepare($cart_sql);
        $cart_stmt->bind_param("i", $user_id);
    }
    
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    
    if ($cart_result->num_rows === 0) {
        throw new Exception('Cart is empty');
    }
    
    $cart_items = [];
    $cart_ids_to_delete = []; // เก็บ cart_id ที่จะลบ
    $item_ids_to_reserve = []; // เก็บ item_id ที่จะเปลี่ยนเป็น reserved
    $subtotal = 0;
    $vat_amount = 0;
    
    while ($item = $cart_result->fetch_assoc()) {
        $unit_price = floatval($item['price']);
        $vat_percentage = floatval($item['vat_percentage']);
        $quantity = intval($item['quantity']);
        
        // คำนวณราคา
        $unit_price_with_vat = $unit_price * (1 + $vat_percentage / 100);
        $item_subtotal = $unit_price * $quantity;
        $item_vat = $item_subtotal * ($vat_percentage / 100);
        $item_total = $item_subtotal + $item_vat;
        
        $subtotal += $item_subtotal;
        $vat_amount += $item_vat;
        
        // เก็บ cart_id เพื่อลบภายหลัง
        $cart_ids_to_delete[] = $item['cart_id'];
        
        // ✅ เก็บ item_id เพื่อเปลี่ยน status เป็น reserved
        if ($item['product_id']) {
            $item_ids_to_reserve[] = $item['product_id'];
        }
        
        // ✅ ใช้ชื่อจาก product_groups หรือ products (ถ้าเป็นระบบเก่า)
        $product_name = $item['name_th'] ?: $item['name_en'];
        
        $cart_items[] = [
            'product_id' => $item['product_id'],
            'product_name' => $product_name,
            'serial_number' => $item['serial_number'],
            'quantity' => $quantity,
            'unit_price' => $unit_price,
            'vat_percentage' => $vat_percentage,
            'unit_price_with_vat' => $unit_price_with_vat,
            'subtotal' => $item_subtotal,
            'vat_amount' => $item_vat,
            'total' => $item_total
        ];
    }
    
    $cart_stmt->close();
    
    $total_amount = $subtotal + $vat_amount;
    $shipping_fee = 0; // ฟรีค่าจัดส่ง
    $discount_amount = 0;
    
    // สร้าง Order Number
    $order_number = 'ORD' . date('Ymd') . sprintf('%06d', rand(1, 999999));
    
    // ✅ แก้ไข: เช็คว่าตาราง orders มี date_updated หรือไม่
    $check_column = $conn->query("SHOW COLUMNS FROM orders LIKE 'date_updated'");
    $has_date_updated = $check_column->num_rows > 0;
    
    // สร้างออเดอร์ใหม่
    // บรรทัดที่สร้าง order
    if ($has_date_updated) {
        $order_sql = "INSERT INTO orders (
                        order_number, user_id, address_id, subtotal, vat_amount, 
                        total_amount, shipping_fee, discount_amount, 
                        order_status, payment_status, payment_method, 
                        date_created, date_updated
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, NOW(), NOW())";
                    // ✅ แก้จาก 'unpaid' เป็น 'pending'
    } else {
        $order_sql = "INSERT INTO orders (
                        order_number, user_id, address_id, subtotal, vat_amount, 
                        total_amount, shipping_fee, discount_amount, 
                        order_status, payment_status, payment_method, 
                        date_created
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, NOW())";
                    // ✅ แก้จาก 'unpaid' เป็น 'pending'
    }
    
    $order_stmt = $conn->prepare($order_sql);
    $order_stmt->bind_param("siiddddds", 
        $order_number,
        $user_id,
        $address_id,
        $subtotal,
        $vat_amount,
        $total_amount,
        $shipping_fee,
        $discount_amount,
        $payment_method
    );
    
    if (!$order_stmt->execute()) {
        throw new Exception('Failed to create order');
    }
    
    $order_id = $conn->insert_id;
    $order_stmt->close();
    
    // บันทึกรายการสินค้า
    $item_sql = "INSERT INTO order_items (
                    order_id, product_id, product_name, quantity, 
                    unit_price, vat_percentage, unit_price_with_vat, 
                    subtotal, vat_amount, total, date_created
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $item_stmt = $conn->prepare($item_sql);
    
    foreach ($cart_items as $item) {
        // ✅ เพิ่ม serial_number ในชื่อสินค้า (ถ้ามี)
        $product_name_with_serial = $item['product_name'];
        if ($item['serial_number']) {
            $product_name_with_serial .= ' (' . $item['serial_number'] . ')';
        }
        
        $item_stmt->bind_param("iisididddd",
            $order_id,
            $item['product_id'],
            $product_name_with_serial, // ใช้ชื่อที่มี serial number
            $item['quantity'],
            $item['unit_price'],
            $item['vat_percentage'],
            $item['unit_price_with_vat'],
            $item['subtotal'],
            $item['vat_amount'],
            $item['total']
        );
        
        if (!$item_stmt->execute()) {
            throw new Exception('Failed to add order items');
        }
    }
    
    $item_stmt->close();
    
    // ✅ เปลี่ยน status ของขวดเป็น 'reserved'
    if (!empty($item_ids_to_reserve)) {
        // ✅ เช็คว่าตาราง product_items มี date_updated หรือไม่
        $check_pi_column = $conn->query("SHOW COLUMNS FROM product_items LIKE 'date_updated'");
        $pi_has_date_updated = $check_pi_column->num_rows > 0;
        
        $placeholders = str_repeat('?,', count($item_ids_to_reserve) - 1) . '?';
        
        if ($pi_has_date_updated) {
            $reserve_sql = "UPDATE product_items 
                           SET status = 'reserved', 
                               reserved_at = NOW(),
                               order_id = ?,
                               date_updated = NOW() 
                           WHERE item_id IN ($placeholders)";
        } else {
            $reserve_sql = "UPDATE product_items 
                           SET status = 'reserved', 
                               reserved_at = NOW(),
                               order_id = ?
                           WHERE item_id IN ($placeholders)";
        }
        
        $reserve_stmt = $conn->prepare($reserve_sql);
        $types = 'i' . str_repeat('i', count($item_ids_to_reserve));
        $params = array_merge([$order_id], $item_ids_to_reserve);
        $reserve_stmt->bind_param($types, ...$params);
        $reserve_stmt->execute();
        $reserve_stmt->close();
    }
    
    // ✅ Soft delete cart items
    if (!empty($cart_ids_to_delete)) {
        // ✅ เช็คว่าตาราง cart มี date_updated หรือไม่
        $check_cart_column = $conn->query("SHOW COLUMNS FROM cart LIKE 'date_updated'");
        $cart_has_date_updated = $check_cart_column->num_rows > 0;
        
        $placeholders = str_repeat('?,', count($cart_ids_to_delete) - 1) . '?';
        
        if ($cart_has_date_updated) {
            $update_cart_sql = "UPDATE cart SET status = 0, date_updated = NOW() WHERE cart_id IN ($placeholders)";
        } else {
            $update_cart_sql = "UPDATE cart SET status = 0 WHERE cart_id IN ($placeholders)";
        }
        
        $update_cart_stmt = $conn->prepare($update_cart_sql);
        $types = str_repeat('i', count($cart_ids_to_delete));
        $update_cart_stmt->bind_param($types, ...$cart_ids_to_delete);
        $update_cart_stmt->execute();
        $update_cart_stmt->close();
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Order created successfully',
        'order_id' => $order_id,
        'order_number' => $order_number,
        'items_count' => count($cart_items),
        'total_amount' => $total_amount,
        'reserved_bottles' => count($item_ids_to_reserve)
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>