<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');
require_once(__DIR__ . '/../../../../lib/base_directory.php');
require_once(__DIR__ . '/../../../../lib/connect.php');

global $base_path;
global $conn;

$response = ['status' => 'error', 'message' => ''];

// ========================================
// FUNCTION: LOG STOCK CHANGES
// ========================================
function logStockChange($conn, $product_id, $log_type, $quantity_before, $quantity_change, $quantity_after, $reference_type = null, $reference_id = null, $order_id = null, $notes = null, $created_by = null) {
    $stmt = $conn->prepare("INSERT INTO stock_logs 
        (product_id, order_id, log_type, quantity_before, quantity_change, quantity_after, reference_type, reference_id, notes, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("iisiiiissi", 
        $product_id, $order_id, $log_type, $quantity_before, $quantity_change, 
        $quantity_after, $reference_type, $reference_id, $notes, $created_by);
    
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

try {
    // Disable MySQL strict mode for GROUP BY compatibility
    $conn->query("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
    
    if (!isset($_POST['action'])) {
        throw new Exception("No action specified.");
    }

    $action = $_POST['action'];

    // ========================================
    // GET ORDERS LIST (DataTables)
    // ========================================
    if ($action == 'getData_orders') {
        $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $searchValue = isset($_POST['search']['value']) ? $conn->real_escape_string($_POST['search']['value']) : '';
        $filterStatus = isset($_POST['filter_status']) ? $_POST['filter_status'] : '';
        
        $whereClause = "o.del = 0";
        
        if (!empty($searchValue)) {
            $whereClause .= " AND (o.order_number LIKE '%$searchValue%' 
                            OR u.first_name LIKE '%$searchValue%'
                            OR u.last_name LIKE '%$searchValue%'
                            OR u.email LIKE '%$searchValue%')";
        }
        
        if (!empty($filterStatus)) {
            $whereClause .= " AND o.order_status = '" . $conn->real_escape_string($filterStatus) . "'";
        }
        
        $totalRecordsQuery = "SELECT COUNT(*) as total FROM orders WHERE del = 0";
        $totalResult = $conn->query($totalRecordsQuery);
        $totalRecords = $totalResult ? $totalResult->fetch_assoc()['total'] : 0;
        
        $totalFilteredQuery = "SELECT COUNT(o.order_id) as total
                              FROM orders o 
                              LEFT JOIN mb_user u ON o.user_id = u.user_id 
                              WHERE $whereClause";
        $filteredResult = $conn->query($totalFilteredQuery);
        $totalFiltered = $filteredResult ? $filteredResult->fetch_assoc()['total'] : 0;
        
        $dataQuery = "SELECT 
                        o.order_id,
                        o.order_number,
                        o.user_id,
                        o.total_amount,
                        o.order_status,
                        o.payment_status,
                        o.date_created,
                        u.first_name,
                        u.last_name,
                        u.email,
                        u.phone_number,
                        u.profile_img,
                        ps.file_path as slip_image,
                        ps.slip_id,
                        (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
                      FROM orders o
                      LEFT JOIN mb_user u ON o.user_id = u.user_id
                      LEFT JOIN payment_slips ps ON o.order_id = ps.order_id
                      WHERE $whereClause
                      ORDER BY o.date_created DESC
                      LIMIT $start, $length";
        
        $dataResult = $conn->query($dataQuery);
        $data = [];
        
        if ($dataResult) {
            while ($row = $dataResult->fetch_assoc()) {
                $row['customer_name'] = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                $data[] = $row;
            }
        }
        
        $response = [
            "draw" => intval($draw),
            "recordsTotal" => intval($totalRecords),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data
        ];
        
    // ========================================
    // GET ORDER DETAILS WITH IMAGES & SLIP
    // ========================================
    } elseif ($action == 'getOrderDetails') {
        
        $order_id = $_POST['order_id'] ?? 0;
    
    if (empty($order_id)) {
        throw new Exception("Order ID is missing.");
    }
    
    $stmt_order = $conn->prepare("SELECT o.*, 
                                  u.first_name, u.last_name, u.email, u.phone_number, u.profile_img,
                                  ps.slip_id, ps.file_path as slip_image, ps.transfer_amount, 
                                  ps.transfer_date, ps.notes as slip_notes
                                  FROM orders o 
                                  LEFT JOIN mb_user u ON o.user_id = u.user_id 
                                  LEFT JOIN payment_slips ps ON o.order_id = ps.order_id
                                  WHERE o.order_id = ? AND o.del = 0");
    $stmt_order->bind_param("i", $order_id);
    $stmt_order->execute();
    $order_result = $stmt_order->get_result();
    
    if ($order_result->num_rows === 0) {
        throw new Exception("Order not found.");
    }
    
    $order = $order_result->fetch_assoc();
    $order['customer_name'] = trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? ''));
    $stmt_order->close();
    
    $stmt_items = $conn->prepare("SELECT 
                              oi.*, 
                              COALESCE(pg.name_th, p.name_th) as name_th,
                              COALESCE(pg.name_en, p.name_en) as name_en,
                              pi_item.serial_number,
                              CASE 
                                  WHEN pi_item.group_id IS NOT NULL THEN 'item'
                                  ELSE 'product'
                              END as product_type,
                              COALESCE(
                                  (SELECT pgi.api_path 
                                   FROM product_group_images pgi
                                   WHERE pgi.group_id = pi_item.group_id 
                                   AND pgi.is_primary = 1 AND pgi.del = 0
                                   LIMIT 1),
                                  (SELECT pi_img.api_path 
                                   FROM product_images pi_img
                                   WHERE pi_img.product_id = oi.product_id 
                                   AND pi_img.is_primary = 1 AND pi_img.del = 0
                                   LIMIT 1)
                              ) as product_image
                              FROM order_items oi
                              LEFT JOIN product_items pi_item ON oi.product_id = pi_item.item_id
                              LEFT JOIN product_groups pg ON pi_item.group_id = pg.group_id AND pg.del = 0
                              LEFT JOIN products p ON oi.product_id = p.product_id AND p.del = 0
                              WHERE oi.order_id = ?
                              ORDER BY oi.order_item_id");
    $stmt_items->bind_param("i", $order_id);
    $stmt_items->execute();
    $items_result = $stmt_items->get_result();
    $items = $items_result->fetch_all(MYSQLI_ASSOC);
    $stmt_items->close();
    
    $response = [
        'status' => 'success',
        'order' => $order,
        'items' => $items
    ];
        
    // ========================================
    // UPDATE ORDER STATUS (WITH AUTO STOCK DEDUCTION)
    // ========================================
   } elseif ($action == 'updateOrderStatus') {

    $order_id   = $_POST['order_id'] ?? 0;
    $new_status = $_POST['order_status'] ?? '';

    if (empty($order_id) || empty($new_status)) {
        throw new Exception("Order ID and status are required.");
    }

    $conn->begin_transaction();

    try {

        // ===============================
        // 1. CHECK CURRENT ORDER
        // ===============================
        $check_stmt = $conn->prepare("
            SELECT order_status, payment_status, user_id 
            FROM orders 
            WHERE order_id = ? AND del = 0
        ");
        $check_stmt->bind_param("i", $order_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows === 0) {
            throw new Exception("Order not found.");
        }

        $current_order = $check_result->fetch_assoc();
        $old_status    = $current_order['order_status'];
        $payment_status = $current_order['payment_status'];
        $user_id       = $current_order['user_id'];
        $check_stmt->close();

        // ===============================
        // 2. UPDATE ORDER STATUS
        // ===============================
        $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
        $stmt->bind_param("si", $new_status, $order_id);

        if (!$stmt->execute()) {
            throw new Exception("Failed to update order status");
        }
        $stmt->close();

        $processed_items = [];
        $created_by = $_SESSION['user_id'] ?? null;

        // ==========================================================
        // 3. WHEN ORDER COMPLETED / PAID (FIRST TIME ONLY)
        // ==========================================================
        if (
            ($new_status === 'completed' || $new_status === 'paid') &&
            ($old_status !== 'completed' && $old_status !== 'paid')
        ) {

            // ===============================
            // 3.1 UPDATE STOCK / ITEMS
            // ===============================
            $stmt_items = $conn->prepare("
                SELECT 
                    oi.product_id,
                    oi.product_name,
                    oi.quantity,
                    pi.group_id,
                    pi.serial_number,
                    pi.status AS item_status
                FROM order_items oi
                LEFT JOIN product_items pi ON oi.product_id = pi.item_id
                WHERE oi.order_id = ?
            ");
            $stmt_items->bind_param("i", $order_id);
            $stmt_items->execute();
            $items_result = $stmt_items->get_result();

            while ($item = $items_result->fetch_assoc()) {

                $product_id   = $item['product_id'];
                $quantity     = $item['quantity'];
                $group_id     = $item['group_id'];
                $serial_number = $item['serial_number'];

                // ===== product_items (serial)
                if ($group_id) {

                    if ($item['item_status'] === 'reserved' || $item['item_status'] === 'available') {

                        $update_stmt = $conn->prepare("
                            UPDATE product_items
                            SET status = 'sold', sold_at = NOW()
                            WHERE item_id = ?
                        ");
                        $update_stmt->bind_param("i", $product_id);
                        $update_stmt->execute();
                        $update_stmt->close();

                        logStockChange(
                            $conn,
                            $product_id,
                            'sold',
                            1,
                            1,
                            0,
                            'order_status_change',
                            $order_id,
                            $order_id,
                            "Item sold (S/N: {$serial_number})",
                            $created_by
                        );
                    }

                } else {
                    // ===== products (old system)
                    $stock = $conn->query("
                        SELECT stock_quantity 
                        FROM products 
                        WHERE product_id = {$product_id} 
                        AND del = 0 
                        FOR UPDATE
                    ")->fetch_assoc();

                    if ($stock['stock_quantity'] < $quantity) {
                        throw new Exception("Insufficient stock for product ID {$product_id}");
                    }

                    $new_stock = $stock['stock_quantity'] - $quantity;

                    $update_stmt = $conn->prepare("
                        UPDATE products SET stock_quantity = ? WHERE product_id = ?
                    ");
                    $update_stmt->bind_param("ii", $new_stock, $product_id);
                    $update_stmt->execute();
                    $update_stmt->close();

                    logStockChange(
                        $conn,
                        $product_id,
                        'deduct',
                        $stock['stock_quantity'],
                        $quantity,
                        $new_stock,
                        'order_status_change',
                        $order_id,
                        $order_id,
                        "Stock deducted",
                        $created_by
                    );
                }
            }
            $stmt_items->close();

            // ===============================
            // 3.2 CREATE USER AI COMPANIONS
            // ===============================
            $ai_stmt = $conn->prepare("
                SELECT DISTINCT ac.ai_id
                FROM order_items oi
                INNER JOIN ai_companions ac ON oi.product_id = ac.item_id
                WHERE oi.order_id = ?
                  AND ac.del = 0
                  AND ac.status = 1
            ");
            $ai_stmt->bind_param("i", $order_id);
            $ai_stmt->execute();
            $ai_result = $ai_stmt->get_result();

            while ($ai = $ai_result->fetch_assoc()) {

                $ai_id = $ai['ai_id'];

                // กันซ้ำ
                $check_ai = $conn->prepare("
                    SELECT 1 FROM user_ai_companions
                    WHERE user_id = ? AND ai_id = ? AND del = 0
                    LIMIT 1
                ");
                $check_ai->bind_param("ii", $user_id, $ai_id);
                $check_ai->execute();
                $exists = $check_ai->get_result()->num_rows > 0;
                $check_ai->close();

                if (!$exists) {
                    $insert_ai = $conn->prepare("
                        INSERT INTO user_ai_companions
                        (ai_id, user_id, status, del)
                        VALUES (?, ?, 1, 0)
                    ");
                    $insert_ai->bind_param("ii", $ai_id, $user_id);
                    $insert_ai->execute();
                    $insert_ai->close();
                }
            }
            $ai_stmt->close();
        }

        // ===============================
        // 4. COMMIT
        // ===============================
        $conn->commit();

        $response = [
            'status'     => 'success',
            'message'    => 'Order status updated successfully',
            'old_status' => $old_status,
            'new_status' => $new_status
        ];

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }



        
    // ========================================
    // UPDATE PAYMENT STATUS & DEDUCT STOCK
    // ========================================
    } elseif ($action == 'updatePaymentStatus') {
        
        $order_id = $_POST['order_id'] ?? 0;
        $payment_status = $_POST['payment_status'] ?? '';
        $order_status = $_POST['order_status'] ?? '';
        
        if (empty($order_id) || empty($payment_status)) {
            throw new Exception("Order ID and payment status are required.");
        }
        
        $conn->begin_transaction();
        
        try {
            $check_stmt = $conn->prepare("SELECT payment_status, order_status FROM orders WHERE order_id = ? AND del = 0");
            $check_stmt->bind_param("i", $order_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                throw new Exception("Order not found.");
            }
            
            $current_order = $check_result->fetch_assoc();
            $old_payment_status = $current_order['payment_status'];
            $check_stmt->close();
            
            $stmt = $conn->prepare("UPDATE orders SET payment_status = ?, order_status = ? WHERE order_id = ?");
            $stmt->bind_param("ssi", $payment_status, $order_status, $order_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update payment status: " . $stmt->error);
            }
            $stmt->close();
            
            $conn->commit();
            
            $response = [
                'status' => 'success',
                'message' => 'Payment status updated successfully!'
            ];
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    // ========================================
    // CANCEL ORDER & RESTORE STOCK
    // ========================================
    } elseif ($action == 'cancelOrder') {
    
    $order_id = $_POST['order_id'] ?? 0;
    $cancel_reason = $_POST['cancel_reason'] ?? 'Cancelled by admin';
    
    if (empty($order_id)) {
        throw new Exception("Order ID is missing.");
    }
    
    $conn->begin_transaction();
    
    try {
        $check_stmt = $conn->prepare("SELECT order_status FROM orders WHERE order_id = ? AND del = 0");
        $check_stmt->bind_param("i", $order_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            throw new Exception("Order not found.");
        }
        
        $order_data = $check_result->fetch_assoc();
        $was_completed = ($order_data['order_status'] === 'completed' || $order_data['order_status'] === 'paid');
        $check_stmt->close();
        
        $stmt = $conn->prepare("UPDATE orders SET order_status = 'cancelled', notes = CONCAT(COALESCE(notes, ''), '\n[CANCELLED] ', ?) WHERE order_id = ?");
        $stmt->bind_param("si", $cancel_reason, $order_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to cancel order: " . $stmt->error);
        }
        $stmt->close();
        
        $restored_items = [];
        $created_by = $_SESSION['user_id'] ?? null;
        
        // ✅ If order was completed/paid, restore stock or unreserve items
        if ($was_completed) {
            
            $stmt_items = $conn->prepare("SELECT 
                                          oi.product_id, 
                                          oi.product_name,
                                          oi.quantity,
                                          pi.group_id,
                                          pi.serial_number,
                                          pi.status as item_status
                                          FROM order_items oi
                                          LEFT JOIN product_items pi ON oi.product_id = pi.item_id
                                          WHERE oi.order_id = ?");
            $stmt_items->bind_param("i", $order_id);
            $stmt_items->execute();
            $items_result = $stmt_items->get_result();
            
            while ($item = $items_result->fetch_assoc()) {
                $product_id = $item['product_id'];
                $quantity = $item['quantity'];
                $group_id = $item['group_id'];
                $serial_number = $item['serial_number'];
                $product_name = $item['product_name'];
                
                if ($group_id) {
                    // ✅ กรณีสินค้ามาจาก product_groups
                    if ($item['item_status'] === 'sold' || $item['item_status'] === 'reserved') {
                        // เปลี่ยนสถานะกลับเป็น available
                        $update_stmt = $conn->prepare("UPDATE product_items 
                                                      SET status = 'available', 
                                                          reserved_at = NULL,
                                                          sold_at = NULL,
                                                          order_id = NULL
                                                      WHERE item_id = ?");
                        $update_stmt->bind_param("i", $product_id);
                        
                        if ($update_stmt->execute()) {
                            // ✅ Log การคืนสต็อก
                            $log_note = "Item restored to available - Order #{$order_id} cancelled: {$cancel_reason}";
                            if ($serial_number) {
                                $log_note .= " (S/N: {$serial_number})";
                            }
                            
                            logStockChange(
                                $conn, 
                                $product_id, 
                                'restore', 
                                0, 
                                1, 
                                1, 
                                'order_cancelled', 
                                $order_id, 
                                $order_id, 
                                $log_note,
                                $created_by
                            );
                            
                            $restored_items[] = [
                                'product_id' => $product_id,
                                'product_name' => $product_name,
                                'serial_number' => $serial_number,
                                'quantity' => $quantity,
                                'status_changed' => $item['item_status'] . ' → available'
                            ];
                        }
                        
                        $update_stmt->close();
                    }
                } else {
                    // ✅ กรณีสินค้าจากระบบเก่า (products table)
                    $stock_query = "SELECT stock_quantity, name_th FROM products WHERE product_id = $product_id AND del = 0 FOR UPDATE";
                    $stock_result = $conn->query($stock_query);
                    
                    if ($stock_result && $stock_result->num_rows > 0) {
                        $product_data = $stock_result->fetch_assoc();
                        $current_stock = $product_data['stock_quantity'];
                        $product_name_db = $product_data['name_th'];
                        
                        $new_stock = $current_stock + $quantity;
                        $update_stmt = $conn->prepare("UPDATE products SET stock_quantity = ? WHERE product_id = ?");
                        $update_stmt->bind_param("ii", $new_stock, $product_id);
                        
                        if ($update_stmt->execute()) {
                            logStockChange(
                                $conn, 
                                $product_id, 
                                'add', 
                                $current_stock, 
                                $quantity, 
                                $new_stock, 
                                'order_cancelled', 
                                $order_id, 
                                $order_id, 
                                "Stock restored - Order #{$order_id} cancelled: {$cancel_reason}",
                                $created_by
                            );
                            
                            $restored_items[] = [
                                'product_id' => $product_id,
                                'product_name' => $product_name_db,
                                'quantity' => $quantity,
                                'old_stock' => $current_stock,
                                'new_stock' => $new_stock
                            ];
                        }
                        
                        $update_stmt->close();
                    }
                }
            }
            
            $stmt_items->close();
        }
        
        $conn->commit();
        
        $message = 'Order cancelled successfully!';
        if (count($restored_items) > 0) {
            $message .= ' ' . count($restored_items) . ' item(s) restored.';
        }
        
        $response = [
            'status' => 'success',
            'message' => $message,
            'restored_items' => $restored_items
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
        
    // ========================================
    // GET STOCK LOGS WITH DATE FILTER
    // ========================================
    } elseif ($action == 'getStockLogs') {
    
    $group_id = $_POST['group_id'] ?? null;
    $order_id = $_POST['order_id'] ?? null;
    $date_from = $_POST['date_from'] ?? null;
    $date_to = $_POST['date_to'] ?? null;
    $log_type = $_POST['log_type'] ?? null;
    $limit = $_POST['limit'] ?? 100;
    
    $whereClause = "1=1";
    
    // ✅ กรองตาม group_id (เฉพาะ product_groups)
    if ($group_id) {
        $whereClause .= " AND pi.group_id = " . intval($group_id);
    }
    
    if ($order_id) {
        $whereClause .= " AND sl.order_id = " . intval($order_id);
    }
    
    if ($log_type) {
        $whereClause .= " AND sl.log_type = '" . $conn->real_escape_string($log_type) . "'";
    }
    
    if ($date_from) {
        $whereClause .= " AND DATE(sl.created_at) >= '" . $conn->real_escape_string($date_from) . "'";
    }
    
    if ($date_to) {
        $whereClause .= " AND DATE(sl.created_at) <= '" . $conn->real_escape_string($date_to) . "'";
    }
    
    // ✅ Query สำหรับ product_groups + product_items เท่านั้น
    $query = "SELECT 
                sl.*,
                pg.name_th as product_name,
                pg.name_en as product_name_en,
                pi.serial_number,
                pi.group_id,
                o.order_number,
                u.first_name,
                u.last_name,
                CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as created_by_name
              FROM stock_logs sl
              LEFT JOIN product_items pi ON sl.product_id = pi.item_id
              LEFT JOIN product_groups pg ON pi.group_id = pg.group_id AND pg.del = 0
              LEFT JOIN orders o ON sl.order_id = o.order_id
              LEFT JOIN mb_user u ON sl.created_by = u.user_id
              WHERE $whereClause
              ORDER BY sl.created_at DESC
              LIMIT $limit";
    
    $result = $conn->query($query);
    $logs = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // ✅ ถ้าไม่มีชื่อสินค้า ให้ใช้ "Item ID: xxx"
            if (!$row['product_name']) {
                $row['product_name'] = 'Item ID: ' . $row['product_id'];
            }
            
            // ✅ Format created_by_name
            $row['created_by_name'] = trim($row['created_by_name']) ?: 'System';
            
            $logs[] = $row;
        }
    }
    
    $response = [
        'status' => 'success',
        'logs' => $logs
    ];
        
    // ========================================
    // GET ORDER STATUS COUNTS
    // ========================================
    } elseif ($action == 'getStatusCounts') {
        
        $query = "SELECT 
                    order_status,
                    COUNT(*) as count
                  FROM orders
                  WHERE del = 0
                  GROUP BY order_status";
        
        $result = $conn->query($query);
        $counts = [
            'all' => 0,
            'pending' => 0,
            'processing' => 0,
            'paid' => 0,
            'shipped' => 0,
            'completed' => 0,
            'cancelled' => 0
        ];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $status = strtolower($row['order_status']);
                $counts[$status] = intval($row['count']);
                $counts['all'] += intval($row['count']);
            }
        }
        
        $response = [
            'status' => 'success',
            'counts' => $counts
        ];
        
    } else {
        throw new Exception("Invalid action: $action");
    }
    
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
    error_log("Error in process_orders.php: " . $e->getMessage());
}

if (isset($conn)) {
    $conn->close();
}

echo json_encode($response);
?>