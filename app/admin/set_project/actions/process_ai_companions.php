<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');
require_once(__DIR__ . '/../../../../lib/base_directory.php');
require_once(__DIR__ . '/../../../../lib/connect.php');

global $base_path;
global $conn;

$response = ['status' => 'error', 'message' => ''];

// ฟังก์ชันสำหรับ log ข้อมูล
function logDebug($message, $data = null) {
    $log_file = __DIR__ . '/../../../../logs/upload_debug.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message";
    
    if ($data !== null) {
        $log_message .= "\n" . print_r($data, true);
    }
    
    $log_message .= "\n" . str_repeat('-', 80) . "\n";
    
    @file_put_contents($log_file, $log_message, FILE_APPEND);
}

// ฟังก์ชันสำหรับอัพโหลดไฟล์
function handleFileUpload($file_input_name, $upload_type = 'avatar') {
    global $base_path;
    
    logDebug("Starting file upload for: $upload_type", $_FILES[$file_input_name] ?? 'No file');
    
    if (!isset($_FILES[$file_input_name]) || $_FILES[$file_input_name]['error'] !== UPLOAD_ERR_OK) {
        $error_msg = isset($_FILES[$file_input_name]) ? 'Error code: ' . $_FILES[$file_input_name]['error'] : 'No file uploaded';
        logDebug("File upload failed for $upload_type: $error_msg");
        return ['success' => false, 'error' => $error_msg];
    }
    
    // เช็คว่าเป็นไฟล์จริงหรือไม่
    if (!is_uploaded_file($_FILES[$file_input_name]['tmp_name'])) {
        logDebug("Not a valid uploaded file for $upload_type");
        return ['success' => false, 'error' => 'Invalid uploaded file'];
    }
    
    // กำหนด directory ตาม type
    $upload_dir = __DIR__ . '/../../../../public/ai_' . $upload_type . 's/';
    
    logDebug("Upload directory: $upload_dir");
    logDebug("Directory exists: " . (is_dir($upload_dir) ? 'Yes' : 'No'));
    logDebug("Directory writable: " . (is_writable(dirname($upload_dir)) ? 'Yes' : 'No'));
    
    // สร้าง directory ถ้ายังไม่มี
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            logDebug("Failed to create directory: $upload_dir");
            return ['success' => false, 'error' => 'Cannot create upload directory'];
        }
        logDebug("Directory created successfully");
    }
    
    // เช็ค permission ของ directory
    if (!is_writable($upload_dir)) {
        logDebug("Directory not writable: $upload_dir");
        return ['success' => false, 'error' => 'Upload directory is not writable'];
    }
    
    // สร้างชื่อไฟล์ใหม่
    $file_extension = strtolower(pathinfo($_FILES[$file_input_name]['name'], PATHINFO_EXTENSION));
    $unique_filename = $upload_type . '_' . uniqid() . '_' . time() . '.' . $file_extension;
    $file_path = $upload_dir . $unique_filename;
    $api_path = $base_path . '/public/ai_' . $upload_type . 's/' . $unique_filename;
    
    logDebug("Target file path: $file_path");
    logDebug("API path: $api_path");
    logDebug("Original filename: " . $_FILES[$file_input_name]['name']);
    logDebug("File size: " . $_FILES[$file_input_name]['size'] . " bytes");
    
    // ย้ายไฟล์
    if (!move_uploaded_file($_FILES[$file_input_name]['tmp_name'], $file_path)) {
        $error = error_get_last();
        logDebug("Failed to move uploaded file", $error);
        return ['success' => false, 'error' => 'Failed to move uploaded file: ' . ($error['message'] ?? 'Unknown error')];
    }
    
    // เช็คว่าไฟล์ถูกสร้างจริงหรือไม่
    if (!file_exists($file_path)) {
        logDebug("File does not exist after move: $file_path");
        return ['success' => false, 'error' => 'File was not created'];
    }
    
    logDebug("File uploaded successfully", [
        'file_path' => $file_path,
        'api_path' => $api_path,
        'file_exists' => file_exists($file_path),
        'file_size' => filesize($file_path)
    ]);
    
    return [
        'success' => true,
        'file_path' => $file_path,
        'api_path' => $api_path
    ];
}

try {
    if (!isset($_POST['action'])) {
        throw new Exception("No action specified.");
    }

    $action = $_POST['action'];
    
    logDebug("Action: $action", $_POST);

    // ========================================
    // GET AI COMPANIONS LIST (DataTables)
    // ========================================
    if ($action == 'getData_ai_companions') {
        $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $searchValue = isset($_POST['search']['value']) ? $conn->real_escape_string($_POST['search']['value']) : '';
        $lang = isset($_POST['lang']) ? $_POST['lang'] : 'th';
        
        $name_col = "ai_name_" . $lang;
        
        $whereClause = "ai.del = 0";
        
        if (!empty($searchValue)) {
            $whereClause .= " AND (ai.ai_code LIKE '%$searchValue%' 
                            OR ai.ai_name_th LIKE '%$searchValue%' 
                            OR ai.ai_name_en LIKE '%$searchValue%' 
                            OR p.name_th LIKE '%$searchValue%' 
                            OR p.name_en LIKE '%$searchValue%')";
        }
        
        $totalRecordsQuery = "SELECT COUNT(ai_id) FROM ai_companions WHERE del = 0";
        $totalRecords = $conn->query($totalRecordsQuery)->fetch_row()[0];
        
        $totalFilteredQuery = "SELECT COUNT(ai.ai_id) 
                              FROM ai_companions ai
                              LEFT JOIN products p ON ai.product_id = p.product_id
                              WHERE $whereClause";
        $totalFiltered = $conn->query($totalFilteredQuery)->fetch_row()[0];
        
        $dataQuery = "SELECT 
                        ai.*,
                        p.name_th as product_name_th,
                        p.name_en as product_name_en,
                        (SELECT COUNT(*) FROM user_ai_companions WHERE ai_id = ai.ai_id AND del = 0) as user_count
                      FROM ai_companions ai
                      LEFT JOIN products p ON ai.product_id = p.product_id
                      WHERE $whereClause
                      ORDER BY ai.created_at DESC
                      LIMIT $start, $length";
        
        $dataResult = $conn->query($dataQuery);
        $data = [];
        
        if ($dataResult) {
            while ($row = $dataResult->fetch_assoc()) {
                $row['ai_name_display'] = $row[$name_col];
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
    // ADD AI COMPANION
    // ========================================
    } elseif ($action == 'addAICompanion') {
        
        logDebug("=== ADD AI COMPANION START ===");
        
        $product_id = $_POST['product_id'] ?? 0;
        $ai_code = $_POST['ai_code'] ?? '';
        
        $ai_name_th = $_POST['ai_name_th'] ?? '';
        $ai_name_en = $_POST['ai_name_en'] ?? '';
        $ai_name_cn = $_POST['ai_name_cn'] ?? '';
        $ai_name_jp = $_POST['ai_name_jp'] ?? '';
        $ai_name_kr = $_POST['ai_name_kr'] ?? '';
        
        $system_prompt_th = $_POST['system_prompt_th'] ?? '';
        $system_prompt_en = $_POST['system_prompt_en'] ?? '';
        $system_prompt_cn = $_POST['system_prompt_cn'] ?? '';
        $system_prompt_jp = $_POST['system_prompt_jp'] ?? '';
        $system_prompt_kr = $_POST['system_prompt_kr'] ?? '';
        
        $perfume_knowledge_th = $_POST['perfume_knowledge_th'] ?? '';
        $perfume_knowledge_en = $_POST['perfume_knowledge_en'] ?? '';
        $perfume_knowledge_cn = $_POST['perfume_knowledge_cn'] ?? '';
        $perfume_knowledge_jp = $_POST['perfume_knowledge_jp'] ?? '';
        $perfume_knowledge_kr = $_POST['perfume_knowledge_kr'] ?? '';
        
        $style_suggestions_th = $_POST['style_suggestions_th'] ?? '';
        $style_suggestions_en = $_POST['style_suggestions_en'] ?? '';
        $style_suggestions_cn = $_POST['style_suggestions_cn'] ?? '';
        $style_suggestions_jp = $_POST['style_suggestions_jp'] ?? '';
        $style_suggestions_kr = $_POST['style_suggestions_kr'] ?? '';
        
        $status = $_POST['status'] ?? 1;
        
        if (empty($product_id)) {
            throw new Exception("Product is required.");
        }
        
        if (empty($ai_code)) {
            throw new Exception("AI Code is required.");
        }
        
        if (empty($ai_name_th)) {
            throw new Exception("AI Name (Thai) is required.");
        }
        
        // Check if AI code already exists
        $check_code = $conn->prepare("SELECT ai_id FROM ai_companions WHERE ai_code = ? AND del = 0");
        $check_code->bind_param("s", $ai_code);
        $check_code->execute();
        $check_code->store_result();
        
        if ($check_code->num_rows > 0) {
            throw new Exception("AI Code already exists. Please use a unique code.");
        }
        $check_code->close();
        
        $conn->begin_transaction();
        
        try {
            $ai_avatar_path = null;
            $ai_avatar_url = null;
            $ai_video_path = null;
            $ai_video_url = null;
            
            // Handle Avatar Upload
            if (isset($_FILES['ai_avatar']) && $_FILES['ai_avatar']['error'] === UPLOAD_ERR_OK) {
                logDebug("Processing avatar upload");
                $upload_result = handleFileUpload('ai_avatar', 'avatar');
                
                if ($upload_result['success']) {
                    $ai_avatar_path = $upload_result['file_path'];
                    $ai_avatar_url = $upload_result['api_path'];
                    logDebug("Avatar uploaded successfully", $upload_result);
                } else {
                    throw new Exception("Avatar upload failed: " . $upload_result['error']);
                }
            }
            
            // Handle Video Upload
            if (isset($_FILES['ai_video']) && $_FILES['ai_video']['error'] === UPLOAD_ERR_OK) {
                logDebug("Processing video upload");
                $upload_result = handleFileUpload('ai_video', 'video');
                
                if ($upload_result['success']) {
                    $ai_video_path = $upload_result['file_path'];
                    $ai_video_url = $upload_result['api_path'];
                    logDebug("Video uploaded successfully", $upload_result);
                } else {
                    throw new Exception("Video upload failed: " . $upload_result['error']);
                }
            }
            
            logDebug("Preparing to insert into database", [
                'product_id' => $product_id,
                'ai_code' => $ai_code,
                'ai_avatar_path' => $ai_avatar_path,
                'ai_avatar_url' => $ai_avatar_url,
                'ai_video_path' => $ai_video_path,
                'ai_video_url' => $ai_video_url
            ]);
            
            $stmt = $conn->prepare("INSERT INTO ai_companions 
                (product_id, ai_code, 
                 ai_name_th, ai_name_en, ai_name_cn, ai_name_jp, ai_name_kr,
                 ai_avatar_path, ai_avatar_url, ai_video_path, ai_video_url,
                 system_prompt_th, system_prompt_en, system_prompt_cn, system_prompt_jp, system_prompt_kr,
                 perfume_knowledge_th, perfume_knowledge_en, perfume_knowledge_cn, perfume_knowledge_jp, perfume_knowledge_kr,
                 style_suggestions_th, style_suggestions_en, style_suggestions_cn, style_suggestions_jp, style_suggestions_kr,
                 status, del) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
            
            if (!$stmt) {
                throw new Exception("Prepare statement failed: " . $conn->error);
            }
            
            $stmt->bind_param("isssssssssssssssssssssssssi", 
                $product_id, $ai_code,
                $ai_name_th, $ai_name_en, $ai_name_cn, $ai_name_jp, $ai_name_kr,
                $ai_avatar_path, $ai_avatar_url, $ai_video_path, $ai_video_url,
                $system_prompt_th, $system_prompt_en, $system_prompt_cn, $system_prompt_jp, $system_prompt_kr,
                $perfume_knowledge_th, $perfume_knowledge_en, $perfume_knowledge_cn, $perfume_knowledge_jp, $perfume_knowledge_kr,
                $style_suggestions_th, $style_suggestions_en, $style_suggestions_cn, $style_suggestions_jp, $style_suggestions_kr,
                $status);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to add AI Companion: " . $stmt->error);
            }
            
            $ai_id = $conn->insert_id;
            logDebug("AI Companion inserted successfully", ['ai_id' => $ai_id]);
            
            $stmt->close();
            
            $conn->commit();
            
            $response = [
                'status' => 'success', 
                'message' => 'AI Companion added successfully!',
                'ai_id' => $ai_id,
                'debug' => [
                    'avatar_path' => $ai_avatar_path,
                    'avatar_url' => $ai_avatar_url,
                    'video_path' => $ai_video_path,
                    'video_url' => $ai_video_url
                ]
            ];
            
            logDebug("=== ADD AI COMPANION SUCCESS ===", $response);
            
        } catch (Exception $e) {
            $conn->rollback();
            logDebug("=== ADD AI COMPANION FAILED ===", ['error' => $e->getMessage()]);
            throw $e;
        }
        
    // ========================================
    // EDIT AI COMPANION
    // ========================================
    } elseif ($action == 'editAICompanion') {
    
        logDebug("=== EDIT AI COMPANION START ===");
        
        $ai_id = $_POST['ai_id'] ?? 0;
        
        if (empty($ai_id)) {
            throw new Exception("AI ID is missing.");
        }
        
        $product_id = $_POST['product_id'] ?? 0;
        $ai_code = $_POST['ai_code'] ?? '';
        
        $ai_name_th = $_POST['ai_name_th'] ?? '';
        $ai_name_en = $_POST['ai_name_en'] ?? '';
        $ai_name_cn = $_POST['ai_name_cn'] ?? '';
        $ai_name_jp = $_POST['ai_name_jp'] ?? '';
        $ai_name_kr = $_POST['ai_name_kr'] ?? '';
        
        $system_prompt_th = $_POST['system_prompt_th'] ?? '';
        $system_prompt_en = $_POST['system_prompt_en'] ?? '';
        $system_prompt_cn = $_POST['system_prompt_cn'] ?? '';
        $system_prompt_jp = $_POST['system_prompt_jp'] ?? '';
        $system_prompt_kr = $_POST['system_prompt_kr'] ?? '';
        
        $perfume_knowledge_th = $_POST['perfume_knowledge_th'] ?? '';
        $perfume_knowledge_en = $_POST['perfume_knowledge_en'] ?? '';
        $perfume_knowledge_cn = $_POST['perfume_knowledge_cn'] ?? '';
        $perfume_knowledge_jp = $_POST['perfume_knowledge_jp'] ?? '';
        $perfume_knowledge_kr = $_POST['perfume_knowledge_kr'] ?? '';
        
        $style_suggestions_th = $_POST['style_suggestions_th'] ?? '';
        $style_suggestions_en = $_POST['style_suggestions_en'] ?? '';
        $style_suggestions_cn = $_POST['style_suggestions_cn'] ?? '';
        $style_suggestions_jp = $_POST['style_suggestions_jp'] ?? '';
        $style_suggestions_kr = $_POST['style_suggestions_kr'] ?? '';
        
        $status = $_POST['status'] ?? 1;
        
        $delete_avatar = $_POST['delete_avatar'] ?? '0';
        $delete_video = $_POST['delete_video'] ?? '0';
        
        logDebug("Edit parameters", [
            'ai_id' => $ai_id,
            'delete_avatar' => $delete_avatar,
            'delete_video' => $delete_video,
            'has_avatar_file' => isset($_FILES['ai_avatar']),
            'has_video_file' => isset($_FILES['ai_video'])
        ]);
        
        // Check if AI code is being changed and if it already exists
        $check_code = $conn->prepare("SELECT ai_id FROM ai_companions WHERE ai_code = ? AND ai_id != ? AND del = 0");
        $check_code->bind_param("si", $ai_code, $ai_id);
        $check_code->execute();
        $check_code->store_result();
        
        if ($check_code->num_rows > 0) {
            throw new Exception("AI Code already exists. Please use a unique code.");
        }
        $check_code->close();
        
        $conn->begin_transaction();
        
        try {
            // Get current file paths
            $current_query = "SELECT ai_avatar_path, ai_avatar_url, ai_video_path, ai_video_url FROM ai_companions WHERE ai_id = $ai_id";
            $current_result = $conn->query($current_query);
            $current = $current_result->fetch_assoc();
            
            logDebug("Current file paths", $current);
            
            $ai_avatar_path = $current['ai_avatar_path'];
            $ai_avatar_url = $current['ai_avatar_url'];
            $ai_video_path = $current['ai_video_path'];
            $ai_video_url = $current['ai_video_url'];
            
            // ========================================
            // จัดการ Avatar
            // ========================================
            if ($delete_avatar === '1') {
                logDebug("Deleting avatar");
                if ($ai_avatar_path && file_exists($ai_avatar_path)) {
                    unlink($ai_avatar_path);
                    logDebug("Avatar file deleted: $ai_avatar_path");
                }
                $ai_avatar_path = null;
                $ai_avatar_url = null;
            } elseif (isset($_FILES['ai_avatar']) && $_FILES['ai_avatar']['error'] === UPLOAD_ERR_OK) {
                logDebug("Processing new avatar upload");
                $upload_result = handleFileUpload('ai_avatar', 'avatar');
                
                if ($upload_result['success']) {
                    // ลบไฟล์เก่า
                    if ($ai_avatar_path && file_exists($ai_avatar_path)) {
                        unlink($ai_avatar_path);
                        logDebug("Old avatar file deleted: $ai_avatar_path");
                    }
                    
                    $ai_avatar_path = $upload_result['file_path'];
                    $ai_avatar_url = $upload_result['api_path'];
                    logDebug("New avatar uploaded successfully", $upload_result);
                } else {
                    throw new Exception("Avatar upload failed: " . $upload_result['error']);
                }
            }
            
            // ========================================
            // จัดการ Video
            // ========================================
            if ($delete_video === '1') {
                logDebug("Deleting video");
                if ($ai_video_path && file_exists($ai_video_path)) {
                    unlink($ai_video_path);
                    logDebug("Video file deleted: $ai_video_path");
                }
                $ai_video_path = null;
                $ai_video_url = null;
            } elseif (isset($_FILES['ai_video']) && $_FILES['ai_video']['error'] === UPLOAD_ERR_OK) {
                logDebug("Processing new video upload");
                $upload_result = handleFileUpload('ai_video', 'video');
                
                if ($upload_result['success']) {
                    // ลบไฟล์เก่า
                    if ($ai_video_path && file_exists($ai_video_path)) {
                        unlink($ai_video_path);
                        logDebug("Old video file deleted: $ai_video_path");
                    }
                    
                    $ai_video_path = $upload_result['file_path'];
                    $ai_video_url = $upload_result['api_path'];
                    logDebug("New video uploaded successfully", $upload_result);
                } else {
                    throw new Exception("Video upload failed: " . $upload_result['error']);
                }
            }
            
            logDebug("Preparing to update database", [
                'ai_id' => $ai_id,
                'ai_avatar_path' => $ai_avatar_path,
                'ai_avatar_url' => $ai_avatar_url,
                'ai_video_path' => $ai_video_path,
                'ai_video_url' => $ai_video_url
            ]);
            
            $update_query = "UPDATE ai_companions SET 
                product_id = ?, 
                ai_code = ?,
                ai_name_th = ?, 
                ai_name_en = ?, 
                ai_name_cn = ?, 
                ai_name_jp = ?, 
                ai_name_kr = ?,
                ai_avatar_path = ?, 
                ai_avatar_url = ?,
                ai_video_path = ?, 
                ai_video_url = ?,
                system_prompt_th = ?, 
                system_prompt_en = ?, 
                system_prompt_cn = ?, 
                system_prompt_jp = ?, 
                system_prompt_kr = ?,
                perfume_knowledge_th = ?, 
                perfume_knowledge_en = ?, 
                perfume_knowledge_cn = ?, 
                perfume_knowledge_jp = ?, 
                perfume_knowledge_kr = ?,
                style_suggestions_th = ?, 
                style_suggestions_en = ?, 
                style_suggestions_cn = ?, 
                style_suggestions_jp = ?, 
                style_suggestions_kr = ?,
                status = ?
                WHERE ai_id = ?";
            
            $stmt = $conn->prepare($update_query);
            
            if (!$stmt) {
                throw new Exception("Prepare statement failed: " . $conn->error);
            }
            
            $stmt->bind_param("isssssssssssssssssssssssssii",
                $product_id, 
                $ai_code,
                $ai_name_th, 
                $ai_name_en, 
                $ai_name_cn, 
                $ai_name_jp, 
                $ai_name_kr,
                $ai_avatar_path, 
                $ai_avatar_url,
                $ai_video_path, 
                $ai_video_url,
                $system_prompt_th, 
                $system_prompt_en, 
                $system_prompt_cn, 
                $system_prompt_jp, 
                $system_prompt_kr,
                $perfume_knowledge_th, 
                $perfume_knowledge_en, 
                $perfume_knowledge_cn, 
                $perfume_knowledge_jp, 
                $perfume_knowledge_kr,
                $style_suggestions_th, 
                $style_suggestions_en, 
                $style_suggestions_cn, 
                $style_suggestions_jp, 
                $style_suggestions_kr,
                $status, 
                $ai_id
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update AI Companion: " . $stmt->error);
            }
            
            logDebug("Database updated successfully");
            
            $stmt->close();
            
            $conn->commit();
            
            $response = [
                'status' => 'success', 
                'message' => 'AI Companion updated successfully!',
                'debug' => [
                    'avatar_path' => $ai_avatar_path,
                    'avatar_url' => $ai_avatar_url,
                    'video_path' => $ai_video_path,
                    'video_url' => $ai_video_url
                ]
            ];
            
            logDebug("=== EDIT AI COMPANION SUCCESS ===", $response);
            
        } catch (Exception $e) {
            $conn->rollback();
            logDebug("=== EDIT AI COMPANION FAILED ===", ['error' => $e->getMessage()]);
            throw $e;
        }
        
    // ========================================
    // DELETE AI COMPANION
    // ========================================
    } elseif ($action == 'deleteAICompanion') {
        
        $ai_id = $_POST['ai_id'] ?? 0;
        
        if (empty($ai_id)) {
            throw new Exception("AI ID is missing.");
        }
        
        $stmt = $conn->prepare("UPDATE ai_companions SET del = 1 WHERE ai_id = ?");
        $stmt->bind_param("i", $ai_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete AI Companion: " . $stmt->error);
        }
        $stmt->close();
        
        $response = [
            'status' => 'success', 
            'message' => 'AI Companion deleted successfully!'
        ];
        
    // ========================================
    // GENERATE UNIQUE AI CODE
    // ========================================
    } elseif ($action == 'generateAICode') {
        $prefix = 'AI-';
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        $ai_code = $prefix . $random;
        
        // Check if code exists, regenerate if needed
        $check = $conn->query("SELECT ai_id FROM ai_companions WHERE ai_code = '$ai_code'");
        if ($check->num_rows > 0) {
            $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
            $ai_code = $prefix . $random;
        }
        
        $response = [
            'status' => 'success',
            'ai_code' => $ai_code
        ];
        
    } else {
        throw new Exception("Invalid action: $action");
    }
    
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
    logDebug("ERROR", ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    error_log("Error in process_ai_companions.php: " . $e->getMessage());
}

$conn->close();
echo json_encode($response);
?>