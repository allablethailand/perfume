<?php
header('Content-Type: application/json');
require_once('../../lib/connect.php');
require_once('../../lib/jwt_helper.php');

global $conn;

// ✅ แก้ไข: ใช้ requireAuth() อย่างเดียว
$user_id = requireAuth();

// รับข้อมูล
$address_id = isset($_POST['address_id']) ? intval($_POST['address_id']) : 0;
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'bank_transfer';
$selected_cart_ids = isset($_POST['selected_cart_ids']) ? $_POST['selected_cart_ids'] : ''; // ⭐ เพิ่ม: รับรายการที่เลือก

if ($address_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Please select delivery address']);
    exit;
}

try {
    // เริ่ม transaction
    $conn->begin_transaction();
    
    // ⭐ ดึงข้อมูล Cart (ถ้ามีการเลือกเฉพาะบางรายการ)
    if (!empty($selected_cart_ids)) {
        // กรณีเลือกเฉพาะบางรายการ
        $cart_ids_array = explode(',', $selected_cart_ids);
        $placeholders = str_repeat('?,', count($cart_ids_array) - 1) . '?';
        
        $cart_sql = "SELECT c.cart_id, c.product_id, c.quantity, c.price, c.vat_percentage,
                            p.name_th, p.name_en
                     FROM cart c
                     LEFT JOIN products p ON c.product_id = p.product_id
                     WHERE c.user_id = ? AND c.cart_id IN ($placeholders)";
        
        $cart_stmt = $conn->prepare($cart_sql);
        
        // Bind parameters
        $types = str_repeat('i', count($cart_ids_array) + 1);
        $params = array_merge([$user_id], $cart_ids_array);
        $cart_stmt->bind_param($types, ...$params);
    } else {
        // กรณีซื้อทั้งหมดในตะกร้า
        $cart_sql = "SELECT c.cart_id, c.product_id, c.quantity, c.price, c.vat_percentage,
                            p.name_th, p.name_en
                     FROM cart c
                     LEFT JOIN products p ON c.product_id = p.product_id
                     WHERE c.user_id = ?";
        
        $cart_stmt = $conn->prepare($cart_sql);
        $cart_stmt->bind_param("i", $user_id);
    }
    
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    
    if ($cart_result->num_rows === 0) {
        throw new Exception('Cart is empty');
    }
    
    $cart_items = [];
    $cart_ids_to_delete = []; // ⭐ เก็บ cart_id ที่จะลบ
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
        
        // ⭐ เก็บ cart_id เพื่อลบภายหลัง
        $cart_ids_to_delete[] = $item['cart_id'];
        
        $cart_items[] = [
            'product_id' => $item['product_id'],
            'product_name' => $item['name_th'] ?: $item['name_en'],
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
    
    // ✅ แก้ไข: เปลี่ยน payment_status จาก 'unpaid' เป็น 'pending'
    $order_sql = "INSERT INTO orders (
                    order_number, user_id, address_id, subtotal, vat_amount, 
                    total_amount, shipping_fee, discount_amount, 
                    order_status, payment_status, payment_method, 
                    date_created, date_updated
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, NOW(), NOW())";
    
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
        $item_stmt->bind_param("iisididddd",
            $order_id,
            $item['product_id'],
            $item['product_name'],
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
    
    // ⭐ ลบสินค้าออกจาก Cart (เฉพาะที่สั่งซื้อ)
    if (!empty($cart_ids_to_delete)) {
        $placeholders = str_repeat('?,', count($cart_ids_to_delete) - 1) . '?';
        $delete_cart_sql = "DELETE FROM cart WHERE cart_id IN ($placeholders)";
        $delete_cart_stmt = $conn->prepare($delete_cart_sql);
        
        $types = str_repeat('i', count($cart_ids_to_delete));
        $delete_cart_stmt->bind_param($types, ...$cart_ids_to_delete);
        $delete_cart_stmt->execute();
        $delete_cart_stmt->close();
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Order created successfully',
        'order_id' => $order_id,
        'order_number' => $order_number,
        'items_count' => count($cart_items),
        'total_amount' => $total_amount
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