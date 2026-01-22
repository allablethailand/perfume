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
    // GET PRODUCTS LIST (DataTables)
    // ========================================
    if ($action == 'getData_products') {
        $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $searchValue = isset($_POST['search']['value']) ? $conn->real_escape_string($_POST['search']['value']) : '';
        $lang = isset($_POST['lang']) ? $_POST['lang'] : 'th';
        
        $name_col = "name_" . $lang;
        
        $whereClause = "p.del = 0";
        
        if (!empty($searchValue)) {
            $whereClause .= " AND (p.name_th LIKE '%$searchValue%' 
                            OR p.name_en LIKE '%$searchValue%' 
                            OR p.name_cn LIKE '%$searchValue%' 
                            OR p.name_jp LIKE '%$searchValue%' 
                            OR p.name_kr LIKE '%$searchValue%')";
        }
        
        $totalRecordsQuery = "SELECT COUNT(product_id) FROM products WHERE del = 0";
        $totalRecords = $conn->query($totalRecordsQuery)->fetch_row()[0];
        
        $totalFilteredQuery = "SELECT COUNT(p.product_id) FROM products p WHERE $whereClause";
        $totalFiltered = $conn->query($totalFilteredQuery)->fetch_row()[0];
        
        $dataQuery = "SELECT 
                        p.*,
                        pi.api_path as primary_image,
                        ROUND(p.price * (1 + p.vat_percentage / 100)) as price_with_vat
                      FROM products p
                      LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1 AND pi.del = 0
                      WHERE $whereClause
                      ORDER BY p.created_at DESC
                      LIMIT $start, $length";
        
        $dataResult = $conn->query($dataQuery);
        $data = [];
        
        if ($dataResult) {
            while ($row = $dataResult->fetch_assoc()) {
                $row['name_display'] = $row[$name_col];
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
    // ADD PRODUCT (WITH STOCK LOG)
    // ========================================
    } elseif ($action == 'addProduct') {
        
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
        $stock_quantity = isset($_POST['stock_quantity']) ? intval($_POST['stock_quantity']) : 0;
        $status = $_POST['status'] ?? 1;
        
        if (empty($name_th)) {
            throw new Exception("Product name (Thai) is required.");
        }
        
        if ($stock_quantity < 0) {
            throw new Exception("Stock quantity cannot be negative.");
        }
        
        $conn->begin_transaction();
        
        try {
            $stmt = $conn->prepare("INSERT INTO products 
                (name_th, name_en, name_cn, name_jp, name_kr, 
                 description_th, description_en, description_cn, description_jp, description_kr,
                 price, vat_percentage, stock_quantity, status, del) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
            
            $stmt->bind_param("ssssssssssddii", 
                $name_th, $name_en, $name_cn, $name_jp, $name_kr,
                $description_th, $description_en, $description_cn, $description_jp, $description_kr,
                $price, $vat_percentage, $stock_quantity, $status);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to add product: " . $stmt->error);
            }
            
            $product_id = $conn->insert_id;
            $stmt->close();
            
            // Log initial stock
            if ($stock_quantity > 0) {
                $created_by = $_SESSION['user_id'] ?? null;
                logStockChange($conn, $product_id, 'add', 0, $stock_quantity, $stock_quantity, 
                    'manual', $product_id, null, 'Initial stock when product created', $created_by);
            }
            
            $images_uploaded = 0;
            
            if (isset($_FILES['product_images']) && 
                is_array($_FILES['product_images']['name']) && 
                count($_FILES['product_images']['name']) > 0 &&
                $_FILES['product_images']['error'][0] !== UPLOAD_ERR_NO_FILE) {
                
                $upload_dir = __DIR__ . '/../../../../public/product_images/';
                
                if (!is_dir($upload_dir)) {
                    if (!mkdir($upload_dir, 0755, true)) {
                        throw new Exception('Failed to create upload directory');
                    }
                }
                
                if (!is_writable($upload_dir)) {
                    throw new Exception('Upload directory is not writable');
                }
                
                $image_order = [];
                if (isset($_POST['image_order'])) {
                    $image_order = json_decode($_POST['image_order'], true);
                }
                
                foreach ($_FILES['product_images']['name'] as $key => $filename) {
                    if ($_FILES['product_images']['error'][$key] === UPLOAD_ERR_OK) {
                        $tmp_name = $_FILES['product_images']['tmp_name'][$key];
                        $file_size = $_FILES['product_images']['size'][$key];
                        $file_type = $_FILES['product_images']['type'][$key];
                        
                        if (!file_exists($tmp_name)) {
                            continue;
                        }
                        
                        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                        if (!in_array($file_type, $allowed_types)) {
                            continue;
                        }
                        
                        if ($file_size > 2 * 1024 * 1024) {
                            continue;
                        }
                        
                        $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        $unique_filename = uniqid() . '_' . time() . '.' . $file_extension;
                        $file_path = $upload_dir . $unique_filename;
                        $api_path = $base_path . '/public/product_images/' . $unique_filename;
                        
                        if (move_uploaded_file($tmp_name, $file_path)) {
                            if (!file_exists($file_path)) {
                                continue;
                            }
                            
                            $is_primary = ($key === 0) ? 1 : 0;
                            $display_order = isset($image_order[$key]) ? $image_order[$key] : $key;
                            
                            $stmt_img = $conn->prepare("INSERT INTO product_images 
                                (product_id, file_name, file_path, api_path, file_size, file_type, display_order, is_primary, del) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)");
                            
                            $stmt_img->bind_param("isssissi", 
                                $product_id, $unique_filename, $file_path, $api_path, 
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
                'message' => "Product added successfully! ($images_uploaded images uploaded)",
                'product_id' => $product_id,
                'images_uploaded' => $images_uploaded
            ];
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    // ========================================
    // EDIT PRODUCT (WITH STOCK LOG)
    // ========================================
    } elseif ($action == 'editProduct') {
        
        $product_id = $_POST['product_id'] ?? 0;
        
        if (empty($product_id)) {
            throw new Exception("Product ID is missing.");
        }
        
        // Get current stock before update
        $current_stock_query = "SELECT stock_quantity FROM products WHERE product_id = $product_id AND del = 0";
        $current_stock_result = $conn->query($current_stock_query);
        
        if ($current_stock_result->num_rows === 0) {
            throw new Exception("Product not found.");
        }
        
        $old_stock = $current_stock_result->fetch_assoc()['stock_quantity'];
        
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
        $stock_quantity = isset($_POST['stock_quantity']) ? intval($_POST['stock_quantity']) : 0;
        $status = $_POST['status'] ?? 1;
        
        if ($stock_quantity < 0) {
            throw new Exception("Stock quantity cannot be negative.");
        }
        
        $conn->begin_transaction();
        
        try {
            $stmt = $conn->prepare("UPDATE products SET 
                name_th = ?, name_en = ?, name_cn = ?, name_jp = ?, name_kr = ?,
                description_th = ?, description_en = ?, description_cn = ?, description_jp = ?, description_kr = ?,
                price = ?, vat_percentage = ?, stock_quantity = ?, status = ?
                WHERE product_id = ?");
            
            $stmt->bind_param("ssssssssssddiii", 
                $name_th, $name_en, $name_cn, $name_jp, $name_kr,
                $description_th, $description_en, $description_cn, $description_jp, $description_kr,
                $price, $vat_percentage, $stock_quantity, $status, $product_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update product: " . $stmt->error);
            }
            $stmt->close();
            
            // Log stock change if different
            if ($old_stock != $stock_quantity) {
                $change = $stock_quantity - $old_stock;
                $log_type = ($change > 0) ? 'add' : 'deduct';
                $created_by = $_SESSION['user_id'] ?? null;
                
                logStockChange($conn, $product_id, $log_type, $old_stock, abs($change), $stock_quantity, 
                    'manual', $product_id, null, 'Manual stock adjustment via product edit', $created_by);
            }
            
            $has_primary = false;
            
            if (isset($_POST['existing_images']) && !empty($_POST['existing_images'])) {
                $existing_images = json_decode($_POST['existing_images'], true);
                
                if ($existing_images && is_array($existing_images) && count($existing_images) > 0) {
                    $conn->query("UPDATE product_images SET is_primary = 0 WHERE product_id = $product_id AND del = 0");
                    
                    foreach ($existing_images as $index => $image_id) {
                        $is_primary = ($index === 0) ? 1 : 0;
                        
                        $stmt_update = $conn->prepare("UPDATE product_images 
                            SET display_order = ?, is_primary = ? 
                            WHERE image_id = ? AND product_id = ?");
                        $stmt_update->bind_param("iiii", $index, $is_primary, $image_id, $product_id);
                        $stmt_update->execute();
                        $stmt_update->close();
                        
                        if ($is_primary) {
                            $has_primary = true;
                        }
                    }
                }
            }
            
            $images_uploaded = 0;
            
            if (isset($_FILES['product_images']) && 
                is_array($_FILES['product_images']['name']) && 
                count($_FILES['product_images']['name']) > 0 &&
                $_FILES['product_images']['error'][0] !== UPLOAD_ERR_NO_FILE) {
                
                $upload_dir = __DIR__ . '/../../../../public/product_images/';
                
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $max_order_query = "SELECT MAX(display_order) as max_order FROM product_images WHERE product_id = $product_id AND del = 0";
                $max_order_result = $conn->query($max_order_query);
                $max_order = $max_order_result->fetch_assoc()['max_order'] ?? -1;
                
                foreach ($_FILES['product_images']['name'] as $key => $filename) {
                    if ($_FILES['product_images']['error'][$key] === UPLOAD_ERR_OK) {
                        $tmp_name = $_FILES['product_images']['tmp_name'][$key];
                        $file_size = $_FILES['product_images']['size'][$key];
                        $file_type = $_FILES['product_images']['type'][$key];
                        
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
                            
                            $stmt_img = $conn->prepare("INSERT INTO product_images 
                                (product_id, file_name, file_path, api_path, file_size, file_type, display_order, is_primary, del) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)");
                            
                            $stmt_img->bind_param("isssissi", 
                                $product_id, $unique_filename, $file_path, $api_path, 
                                $file_size, $file_type, $display_order, $is_primary);
                            
                            if ($stmt_img->execute()) {
                                $images_uploaded++;
                            }
                            
                            $stmt_img->close();
                        }
                    }
                }
            }
            
            if (!$has_primary) {
                $first_image_query = "SELECT image_id FROM product_images 
                                     WHERE product_id = $product_id AND del = 0 
                                     ORDER BY display_order ASC 
                                     LIMIT 1";
                $first_image_result = $conn->query($first_image_query);
                
                if ($first_image_result && $first_image_result->num_rows > 0) {
                    $first_image = $first_image_result->fetch_assoc();
                    $first_image_id = $first_image['image_id'];
                    
                    $conn->query("UPDATE product_images 
                                 SET is_primary = 1 
                                 WHERE image_id = $first_image_id");
                    
                    $has_primary = true;
                }
            }
            
            $conn->commit();
            
            $message = 'Product updated successfully!';
            if ($images_uploaded > 0) {
                $message .= " ($images_uploaded new images added)";
            }
            
            $response = [
                'status' => 'success', 
                'message' => $message,
                'has_primary' => $has_primary
            ];
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    // ========================================
    // DELETE PRODUCT (Soft Delete)
    // ========================================
    } elseif ($action == 'deleteProduct') {
        
        $product_id = $_POST['product_id'] ?? 0;
        
        if (empty($product_id)) {
            throw new Exception("Product ID is missing.");
        }
        
        $stmt = $conn->prepare("UPDATE products SET del = 1 WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete product: " . $stmt->error);
        }
        $stmt->close();
        
        $stmt_img = $conn->prepare("UPDATE product_images SET del = 1 WHERE product_id = ?");
        $stmt_img->bind_param("i", $product_id);
        $stmt_img->execute();
        $stmt_img->close();
        
        $response = [
            'status' => 'success', 
            'message' => 'Product deleted successfully!'
        ];
        
    // ========================================
    // DELETE IMAGE
    // ========================================
    } elseif ($action == 'deleteImage') {
        
        $image_id = $_POST['image_id'] ?? 0;
        
        if (empty($image_id)) {
            throw new Exception("Image ID is missing.");
        }
        
        $stmt = $conn->prepare("UPDATE product_images SET del = 1 WHERE image_id = ?");
        $stmt->bind_param("i", $image_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete image: " . $stmt->error);
        }
        $stmt->close();
        
        $response = [
            'status' => 'success', 
            'message' => 'Image deleted successfully!'
        ];
        
    } else {
        throw new Exception("Invalid action: $action");
    }
    
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
    error_log("Error in process_products.php: " . $e->getMessage());
}

$conn->close();
echo json_encode($response);
?>