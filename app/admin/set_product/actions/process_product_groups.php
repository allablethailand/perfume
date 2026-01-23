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
    if (!isset($_POST['action'])) {
        throw new Exception("No action specified.");
    }

    $action = $_POST['action'];

    // ========================================
    // GET PRODUCT GROUPS LIST (DataTables)
    // ========================================
        if ($action === 'getData_groups') {

    $draw   = intval($_POST['draw'] ?? 1);
    $start  = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);
    $lang   = $_POST['lang'] ?? 'th';
    $search = $conn->real_escape_string($_POST['search']['value'] ?? '');

    $name_col = "name_" . $lang;

    $where = "pg.del = 0";
    if ($search !== '') {
        $where .= " AND (
            pg.name_th LIKE '%$search%' OR
            pg.name_en LIKE '%$search%' OR
            pg.name_cn LIKE '%$search%' OR
            pg.name_jp LIKE '%$search%' OR
            pg.name_kr LIKE '%$search%'
        )";
    }

    // total
    $total = $conn->query("SELECT COUNT(*) FROM product_groups WHERE del = 0")->fetch_row()[0];

    // filtered
    $filtered = $conn->query("
        SELECT COUNT(DISTINCT pg.group_id)
        FROM product_groups pg
        WHERE $where
    ")->fetch_row()[0];

    // ✅ แก้ไข SQL: เพิ่ม GROUP BY ให้ครบถ้วนตามมาตรฐาน only_full_group_by
    $sql = "
        SELECT
            pg.group_id,
            pg.name_th,
            pg.name_en,
            pg.name_cn,
            pg.name_jp,
            pg.name_kr,
            pg.price,
            pg.status,
            pg.created_at,

            MAX(pgi.api_path) AS primary_image,

            COUNT(DISTINCT pi.item_id) AS total_bottles,
            SUM(CASE WHEN pi.status = 'available' AND pi.del = 0 THEN 1 ELSE 0 END) AS available_bottles,
            SUM(CASE WHEN pi.status = 'sold' AND pi.del = 0 THEN 1 ELSE 0 END) AS sold_bottles,
            SUM(CASE WHEN pi.status = 'reserved' AND pi.del = 0 THEN 1 ELSE 0 END) AS reserved_bottles

        FROM product_groups pg
        LEFT JOIN product_group_images pgi
            ON pg.group_id = pgi.group_id
           AND pgi.is_primary = 1
           AND pgi.del = 0
        LEFT JOIN product_items pi
            ON pg.group_id = pi.group_id
           AND pi.del = 0
        WHERE $where
        GROUP BY 
            pg.group_id, 
            pg.name_th, 
            pg.name_en, 
            pg.name_cn, 
            pg.name_jp, 
            pg.name_kr, 
            pg.price, 
            pg.status, 
            pg.created_at
        ORDER BY pg.created_at DESC
        LIMIT $start, $length
    ";

    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception("SQL ERROR: " . $conn->error);
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $row['name_display'] = $row[$name_col] ?? '-';
        $data[] = $row;
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => intval($total),
        'recordsFiltered' => intval($filtered),
        'data' => $data
    ]);
    exit;

    
        
    // ========================================
    // ADD PRODUCT GROUP (WITH AUTO BOTTLE CREATION)
    // ========================================
    } elseif ($action == 'addGroup') {
        
        $name_th = $_POST['name_th'] ?? '';
        $name_en = $_POST['name_en'] ?? '';
        $name_cn = $_POST['name_cn'] ?? '';
        $name_jp = $_POST['name_jp'] ?? '';
        $name_kr = $_POST['name_kr'] ?? '';
        
        $description_th = $_POST['description_th'] ?? '';
        $description_en = $_POST['description_en'] ?? '';
        $description_cn = $_POST['description_cn'] ?? '';
        $description_jp = $_POST['description_jp'] ?? '';
        $description_kr = $_POST['description_kr'] ?? '';
        
        $price = $_POST['price'] ?? 0.00;
        $vat_percentage = $_POST['vat_percentage'] ?? 7.00;
        $bottle_quantity = isset($_POST['bottle_quantity']) ? intval($_POST['bottle_quantity']) : 0;
        $serial_prefix = strtoupper($_POST['serial_prefix'] ?? '');
        
        if (empty($name_th)) {
            throw new Exception("Product name (Thai) is required.");
        }
        
        if (empty($serial_prefix)) {
            throw new Exception("Serial prefix is required.");
        }
        
        if ($bottle_quantity < 1) {
            throw new Exception("Bottle quantity must be at least 1.");
        }
        
        $conn->begin_transaction();
        
        try {
            // 1. สร้างกลุ่มสินค้า
            $stmt = $conn->prepare("INSERT INTO product_groups 
                (name_th, name_en, name_cn, name_jp, name_kr, 
                 description_th, description_en, description_cn, description_jp, description_kr,
                 price, vat_percentage, status, del) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0)");
            
            $stmt->bind_param("ssssssssssdd", 
                $name_th, $name_en, $name_cn, $name_jp, $name_kr,
                $description_th, $description_en, $description_cn, $description_jp, $description_kr,
                $price, $vat_percentage);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to add product group: " . $stmt->error);
            }
            
            $group_id = $conn->insert_id;
            $stmt->close();
            
            // 2. สร้างขวดอัตโนมัติ + บันทึก Stock Logs
            $stmt_bottle = $conn->prepare("INSERT INTO product_items 
                (group_id, serial_number, status, del) 
                VALUES (?, ?, 'available', 0)");
            
            // ✅ Prepare stock log statement
            $stmt_log = $conn->prepare("INSERT INTO stock_logs 
                (product_id, order_id, log_type, quantity_before, quantity_change, quantity_after, 
                 reference_type, reference_id, notes, created_by, created_at) 
                VALUES (?, NULL, 'add', 0, 1, 1, 'group_created', ?, ?, ?, NOW())");
            
            $bottles_created = 0;
            $serial_start = '';
            $serial_end = '';
            $created_by = $_SESSION['user_id'] ?? null;
            
            for ($i = 1; $i <= $bottle_quantity; $i++) {
                $serial_number = $serial_prefix . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);
                
                if ($i === 1) {
                    $serial_start = $serial_number;
                }
                if ($i === $bottle_quantity) {
                    $serial_end = $serial_number;
                }
                
                $stmt_bottle->bind_param("is", $group_id, $serial_number);
                
                if ($stmt_bottle->execute()) {
                    $item_id = $conn->insert_id;
                    $bottles_created++;
                    
                    // ✅ บันทึก stock log
                    $log_note = "Initial bottle created - Group: {$name_th} (S/N: {$serial_number})";
                    $stmt_log->bind_param("iisi", $item_id, $group_id, $log_note, $created_by);
                    $stmt_log->execute();
                } else {
                    error_log("Failed to create bottle: " . $serial_number);
                }
            }
            
            $stmt_bottle->close();
            $stmt_log->close();
            
            // 3. อัปโหลดรูปภาพ
            $images_uploaded = 0;
            
            if (isset($_FILES['group_images']) && 
                is_array($_FILES['group_images']['name']) && 
                count($_FILES['group_images']['name']) > 0 &&
                $_FILES['group_images']['error'][0] !== UPLOAD_ERR_NO_FILE) {
                
                $upload_dir = __DIR__ . '/../../../../public/product_images/';
                
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                foreach ($_FILES['group_images']['name'] as $key => $filename) {
                    if ($_FILES['group_images']['error'][$key] === UPLOAD_ERR_OK) {
                        $tmp_name = $_FILES['group_images']['tmp_name'][$key];
                        $file_size = $_FILES['group_images']['size'][$key];
                        $file_type = $_FILES['group_images']['type'][$key];
                        
                        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                        if (!in_array($file_type, $allowed_types) || $file_size > 2 * 1024 * 1024) {
                            continue;
                        }
                        
                        $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        $unique_filename = uniqid() . '_' . time() . '.' . $file_extension;
                        $file_path = $upload_dir . $unique_filename;
                        $api_path = $base_path . '/public/product_images/' . $unique_filename;
                        
                        if (move_uploaded_file($tmp_name, $file_path)) {
                            $is_primary = ($key === 0) ? 1 : 0;
                            $display_order = $key;
                            
                            $stmt_img = $conn->prepare("INSERT INTO product_group_images 
                                (group_id, file_name, file_path, api_path, file_size, file_type, display_order, is_primary, del) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)");
                            
                            $stmt_img->bind_param("isssissi", 
                                $group_id, $unique_filename, $file_path, $api_path, 
                                $file_size, $file_type, $display_order, $is_primary);
                            
                            if ($stmt_img->execute()) {
                                $images_uploaded++;
                            }
                            
                            $stmt_img->close();
                        }
                    }
                }
            }
            
            $conn->commit();
            
            $response = [
                'status' => 'success', 
                'message' => "สร้างกลิ่นสำเร็จ! ($images_uploaded รูป, $bottles_created ขวด)",
                'group_id' => $group_id,
                'bottles_created' => $bottles_created,
                'serial_start' => $serial_start,
                'serial_end' => $serial_end
            ];
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    // ========================================
    // EDIT PRODUCT GROUP
    // ========================================
    } elseif ($action == 'editGroup') {
        
        $group_id = $_POST['group_id'] ?? 0;
        
        if (empty($group_id)) {
            throw new Exception("Group ID is missing.");
        }
        
        $name_th = $_POST['name_th'] ?? '';
        $name_en = $_POST['name_en'] ?? '';
        $name_cn = $_POST['name_cn'] ?? '';
        $name_jp = $_POST['name_jp'] ?? '';
        $name_kr = $_POST['name_kr'] ?? '';
        
        $description_th = $_POST['description_th'] ?? '';
        $description_en = $_POST['description_en'] ?? '';
        $description_cn = $_POST['description_cn'] ?? '';
        $description_jp = $_POST['description_jp'] ?? '';
        $description_kr = $_POST['description_kr'] ?? '';
        
        $price = $_POST['price'] ?? 0.00;
        $vat_percentage = $_POST['vat_percentage'] ?? 7.00;
        $status = $_POST['status'] ?? 1;
        
        if (empty($name_th)) {
            throw new Exception("Product name (Thai) is required.");
        }
        
        $conn->begin_transaction();
        
        try {
            // อัปเดตข้อมูลกลุ่ม
            $stmt = $conn->prepare("UPDATE product_groups SET 
                name_th = ?, name_en = ?, name_cn = ?, name_jp = ?, name_kr = ?,
                description_th = ?, description_en = ?, description_cn = ?, description_jp = ?, description_kr = ?,
                price = ?, vat_percentage = ?, status = ?
                WHERE group_id = ?");
            
            $stmt->bind_param("ssssssssssddii", 
                $name_th, $name_en, $name_cn, $name_jp, $name_kr,
                $description_th, $description_en, $description_cn, $description_jp, $description_kr,
                $price, $vat_percentage, $status, $group_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update group: " . $stmt->error);
            }
            $stmt->close();
            
            $has_primary = false;
            
            // อัปเดตลำดับรูปภาพที่มีอยู่แล้ว
            if (isset($_POST['existing_images']) && !empty($_POST['existing_images'])) {
                $existing_images = json_decode($_POST['existing_images'], true);
                
                if ($existing_images && is_array($existing_images) && count($existing_images) > 0) {
                    $conn->query("UPDATE product_group_images SET is_primary = 0 WHERE group_id = $group_id AND del = 0");
                    
                    foreach ($existing_images as $index => $image_id) {
                        $is_primary = ($index === 0) ? 1 : 0;
                        
                        $stmt_update = $conn->prepare("UPDATE product_group_images 
                            SET display_order = ?, is_primary = ? 
                            WHERE image_id = ? AND group_id = ?");
                        $stmt_update->bind_param("iiii", $index, $is_primary, $image_id, $group_id);
                        $stmt_update->execute();
                        $stmt_update->close();
                        
                        if ($is_primary) {
                            $has_primary = true;
                        }
                    }
                }
            }
            
            // อัปโหลดรูปใหม่
            $images_uploaded = 0;
            
            if (isset($_FILES['group_images']) && 
                is_array($_FILES['group_images']['name']) && 
                count($_FILES['group_images']['name']) > 0 &&
                $_FILES['group_images']['error'][0] !== UPLOAD_ERR_NO_FILE) {
                
                $upload_dir = __DIR__ . '/../../../../public/product_images/';
                
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $max_order_query = "SELECT MAX(display_order) as max_order FROM product_group_images WHERE group_id = $group_id AND del = 0";
                $max_order_result = $conn->query($max_order_query);
                $max_order = $max_order_result->fetch_assoc()['max_order'] ?? -1;
                
                foreach ($_FILES['group_images']['name'] as $key => $filename) {
                    if ($_FILES['group_images']['error'][$key] === UPLOAD_ERR_OK) {
                        $tmp_name = $_FILES['group_images']['tmp_name'][$key];
                        $file_size = $_FILES['group_images']['size'][$key];
                        $file_type = $_FILES['group_images']['type'][$key];
                        
                        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                        if (!in_array($file_type, $allowed_types) || $file_size > 2 * 1024 * 1024) {
                            continue;
                        }
                        
                        $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        $unique_filename = uniqid() . '_' . time() . '.' . $file_extension;
                        $file_path = $upload_dir . $unique_filename;
                        $api_path = $base_path . '/public/product_images/' . $unique_filename;
                        
                        if (move_uploaded_file($tmp_name, $file_path)) {
                            $display_order = ++$max_order;
                            $is_primary = (!$has_primary && $key === 0) ? 1 : 0;
                            
                            if ($is_primary) {
                                $has_primary = true;
                            }
                            
                            $stmt_img = $conn->prepare("INSERT INTO product_group_images 
                                (group_id, file_name, file_path, api_path, file_size, file_type, display_order, is_primary, del) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)");
                            
                            $stmt_img->bind_param("isssissi", 
                                $group_id, $unique_filename, $file_path, $api_path, 
                                $file_size, $file_type, $display_order, $is_primary);
                            
                            if ($stmt_img->execute()) {
                                $images_uploaded++;
                            }
                            
                            $stmt_img->close();
                        }
                    }
                }
            }
            
            // ถ้าไม่มีรูปหลัก ให้เซ็ตรูปแรกเป็นหลัก
            if (!$has_primary) {
                $first_image_query = "SELECT image_id FROM product_group_images 
                                     WHERE group_id = $group_id AND del = 0 
                                     ORDER BY display_order ASC 
                                     LIMIT 1";
                $first_image_result = $conn->query($first_image_query);
                
                if ($first_image_result && $first_image_result->num_rows > 0) {
                    $first_image = $first_image_result->fetch_assoc();
                    $first_image_id = $first_image['image_id'];
                    
                    $conn->query("UPDATE product_group_images 
                                 SET is_primary = 1 
                                 WHERE image_id = $first_image_id");
                }
            }
            
            $conn->commit();
            
            $message = 'อัปเดตกลิ่นสำเร็จ!';
            if ($images_uploaded > 0) {
                $message .= " (เพิ่มรูปใหม่ $images_uploaded รูป)";
            }
            
            $response = [
                'status' => 'success', 
                'message' => $message
            ];
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    // ========================================
    // DELETE IMAGE
    // ========================================
    } elseif ($action == 'deleteImage') {
        
        $image_id = $_POST['image_id'] ?? 0;
        
        if (empty($image_id)) {
            throw new Exception("Image ID is missing.");
        }
        
        $stmt = $conn->prepare("UPDATE product_group_images SET del = 1 WHERE image_id = ?");
        $stmt->bind_param("i", $image_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete image: " . $stmt->error);
        }
        $stmt->close();
        
        $response = [
            'status' => 'success', 
            'message' => 'ลบรูปภาพสำเร็จ!'
        ];
        
    // ========================================
    // DELETE PRODUCT GROUP
    // ========================================
    } elseif ($action == 'deleteGroup') {
        
        $group_id = $_POST['group_id'] ?? 0;
        
        if (empty($group_id)) {
            throw new Exception("Group ID is missing.");
        }
        
        $conn->begin_transaction();
        
        try {
            // Soft delete group
            $stmt = $conn->prepare("UPDATE product_groups SET del = 1 WHERE group_id = ?");
            $stmt->bind_param("i", $group_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to delete group: " . $stmt->error);
            }
            $stmt->close();
            
            // Soft delete bottles
            $stmt_items = $conn->prepare("UPDATE product_items SET del = 1 WHERE group_id = ?");
            $stmt_items->bind_param("i", $group_id);
            $stmt_items->execute();
            $stmt_items->close();
            
            // Soft delete images
            $stmt_img = $conn->prepare("UPDATE product_group_images SET del = 1 WHERE group_id = ?");
            $stmt_img->bind_param("i", $group_id);
            $stmt_img->execute();
            $stmt_img->close();
            
            $conn->commit();
            
            $response = [
                'status' => 'success', 
                'message' => 'ลบกลิ่นสำเร็จ!'
            ];
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    // ========================================
    // GET BOTTLES LIST (DataTables)
    // ========================================
    } elseif ($action == 'getData_bottles') {
        $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
        
        if (empty($group_id)) {
            throw new Exception("Group ID is required.");
        }
        
        $whereClause = "group_id = $group_id AND del = 0";
        
        $totalRecordsQuery = "SELECT COUNT(item_id) FROM product_items WHERE $whereClause";
        $totalRecords = $conn->query($totalRecordsQuery)->fetch_row()[0];
        
        $dataQuery = "SELECT * FROM product_items 
                     WHERE $whereClause
                     ORDER BY serial_number ASC
                     LIMIT $start, $length";
        
        $dataResult = $conn->query($dataQuery);
        $data = [];
        
        if ($dataResult) {
            while ($row = $dataResult->fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        $response = [
            "draw" => intval($draw),
            "recordsTotal" => intval($totalRecords),
            "recordsFiltered" => intval($totalRecords),
            "data" => $data
        ];
        
    // ========================================
    // GET BOTTLE STATISTICS
    // ========================================
    } elseif ($action == 'getBottleStats') {
        $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
        
        if (empty($group_id)) {
            throw new Exception("Group ID is required.");
        }
        
        $stmt = $conn->prepare("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
            SUM(CASE WHEN status = 'reserved' THEN 1 ELSE 0 END) as reserved,
            SUM(CASE WHEN status = 'sold' THEN 1 ELSE 0 END) as sold
            FROM product_items 
            WHERE group_id = ? AND del = 0");
        
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        $stmt->close();
        
        $response = [
            'status' => 'success',
            'stats' => $stats
        ];
        
    // ========================================
    // ADD BOTTLES
    // ========================================
    } elseif ($action == 'addBottles') {
        $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
        $prefix = strtoupper($_POST['prefix'] ?? '');
        $start_number = isset($_POST['start_number']) ? intval($_POST['start_number']) : 1;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
        
        if (empty($group_id) || empty($prefix)) {
            throw new Exception("Group ID and prefix are required.");
        }
        
        if ($quantity < 1 || $quantity > 1000) {
            throw new Exception("Quantity must be between 1 and 1000.");
        }
        
        $conn->begin_transaction();
        
        try {
            // ✅ ดึงชื่อกลุ่มสินค้า
            $stmt_group = $conn->prepare("SELECT name_th FROM product_groups WHERE group_id = ? AND del = 0");
            $stmt_group->bind_param("i", $group_id);
            $stmt_group->execute();
            $group_result = $stmt_group->get_result();
            
            if ($group_result->num_rows === 0) {
                throw new Exception("Product group not found.");
            }
            
            $group_name = $group_result->fetch_assoc()['name_th'];
            $stmt_group->close();
            
            // ✅ Prepare statements
            $stmt = $conn->prepare("INSERT INTO product_items (group_id, serial_number, status, del) VALUES (?, ?, 'available', 0)");
            $stmt_log = $conn->prepare("INSERT INTO stock_logs 
                (product_id, order_id, log_type, quantity_before, quantity_change, quantity_after, 
                 reference_type, reference_id, notes, created_by, created_at) 
                VALUES (?, NULL, 'add', 0, 1, 1, 'bottles_added', ?, ?, ?, NOW())");
            
            $created = 0;
            $serial_start = '';
            $serial_end = '';
            $created_by = $_SESSION['user_id'] ?? null;
            
            for ($i = 0; $i < $quantity; $i++) {
                $number = $start_number + $i;
                $serial = $prefix . '-' . str_pad($number, 3, '0', STR_PAD_LEFT);
                
                if ($i === 0) $serial_start = $serial;
                if ($i === $quantity - 1) $serial_end = $serial;
                
                $stmt->bind_param("is", $group_id, $serial);
                
                if ($stmt->execute()) {
                    $item_id = $conn->insert_id;
                    $created++;
                    
                    // ✅ บันทึก stock log
                    $log_note = "Bottle added to group: {$group_name} (S/N: {$serial})";
                    $stmt_log->bind_param("iisi", $item_id, $group_id, $log_note, $created_by);
                    $stmt_log->execute();
                }
            }
            
            $stmt->close();
            $stmt_log->close();
            $conn->commit();
            
            $response = [
                'status' => 'success',
                'message' => "เพิ่มขวดสำเร็จ $created ขวด",
                'created' => $created,
                'serial_start' => $serial_start,
                'serial_end' => $serial_end
            ];
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    // ========================================
    // DELETE BOTTLE
    // ========================================
    } elseif ($action == 'deleteBottle') {
        $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
        
        if (empty($item_id)) {
            throw new Exception("Item ID is required.");
        }
        
        // ตรวจสอบสถานะก่อนลบ
        $check = $conn->query("SELECT status FROM product_items WHERE item_id = $item_id");
        if ($check->num_rows === 0) {
            throw new Exception("Bottle not found.");
        }
        
        $status = $check->fetch_assoc()['status'];
        
        if ($status === 'sold') {
            throw new Exception("ไม่สามารถลบขวดที่ขายแล้ว");
        }
        
        // Soft delete
        $stmt = $conn->prepare("UPDATE product_items SET del = 1 WHERE item_id = ?");
        $stmt->bind_param("i", $item_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete bottle.");
        }
        $stmt->close();
        
        $response = [
            'status' => 'success',
            'message' => 'ลบขวดสำเร็จ'
        ];
        
    } else {
        throw new Exception("Invalid action: $action");
    }
    
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
    error_log("Error in process_product_groups.php: " . $e->getMessage());
}

$conn->close();
echo json_encode($response);
?>