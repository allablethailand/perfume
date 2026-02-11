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

// ฟังก์ชันสำหรับอัพโหลดไฟล์เดี่ยว
function handleFileUpload($file_input_name, $upload_type = 'avatar') {
    global $base_path;
    
    logDebug("Starting file upload for: $upload_type", $_FILES[$file_input_name] ?? 'No file');
    
    if (!isset($_FILES[$file_input_name]) || $_FILES[$file_input_name]['error'] !== UPLOAD_ERR_OK) {
        $error_msg = isset($_FILES[$file_input_name]) ? 'Error code: ' . $_FILES[$file_input_name]['error'] : 'No file uploaded';
        logDebug("File upload failed for $upload_type: $error_msg");
        return ['success' => false, 'error' => $error_msg];
    }
    
    if (!is_uploaded_file($_FILES[$file_input_name]['tmp_name'])) {
        logDebug("Not a valid uploaded file for $upload_type");
        return ['success' => false, 'error' => 'Invalid uploaded file'];
    }
    
    $upload_dir = __DIR__ . '/../../../../public/ai_' . $upload_type . 's/';
    
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            logDebug("Failed to create directory: $upload_dir");
            return ['success' => false, 'error' => 'Cannot create upload directory'];
        }
    }
    
    if (!is_writable($upload_dir)) {
        logDebug("Directory not writable: $upload_dir");
        return ['success' => false, 'error' => 'Upload directory is not writable'];
    }
    
    $file_extension = strtolower(pathinfo($_FILES[$file_input_name]['name'], PATHINFO_EXTENSION));
    $unique_filename = $upload_type . '_' . uniqid() . '_' . time() . '.' . $file_extension;
    $file_path = $upload_dir . $unique_filename;
    $api_path = $base_path . '/public/ai_' . $upload_type . 's/' . $unique_filename;
    
    if (!move_uploaded_file($_FILES[$file_input_name]['tmp_name'], $file_path)) {
        $error = error_get_last();
        logDebug("Failed to move uploaded file", $error);
        return ['success' => false, 'error' => 'Failed to move uploaded file: ' . ($error['message'] ?? 'Unknown error')];
    }
    
    if (!file_exists($file_path)) {
        logDebug("File does not exist after move: $file_path");
        return ['success' => false, 'error' => 'File was not created'];
    }
    
    logDebug("File uploaded successfully", [
        'file_path' => $file_path,
        'api_path' => $api_path
    ]);
    
    return [
        'success' => true,
        'file_path' => $file_path,
        'api_path' => $api_path
    ];
}

// ฟังก์ชันสำหรับอัพโหลดหลายไฟล์ (แก้ไขใหม่)
function handleMultipleFileUpload($file_input_name, $upload_type = 'video') {
    global $base_path;
    
    $uploaded_urls = [];
    
    logDebug("Starting multiple file upload for: $file_input_name", $_FILES);
    
    if (!isset($_FILES[$file_input_name])) {
        logDebug("No files found for: $file_input_name");
        return ['success' => true, 'urls' => []];
    }
    
    $files = $_FILES[$file_input_name];
    
    // ตรวจสอบว่าเป็น array หรือไม่
    if (!is_array($files['name'])) {
        logDebug("Not an array upload");
        return ['success' => true, 'urls' => []];
    }
    
    $file_count = count($files['name']);
    logDebug("File count: $file_count");
    
    $upload_dir = __DIR__ . '/../../../../public/ai_' . $upload_type . 's/';
    
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            return ['success' => false, 'error' => 'Cannot create upload directory'];
        }
    }
    
    for ($i = 0; $i < $file_count; $i++) {
        // ข้ามถ้าไม่มีไฟล์หรือมี error
        if (!isset($files['error'][$i]) || $files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            logDebug("File $i has error: " . $files['error'][$i]);
            continue;
        }
        
        // ตรวจสอบว่าเป็นไฟล์ที่อัพโหลดจริง
        if (!is_uploaded_file($files['tmp_name'][$i])) {
            logDebug("File $i is not a valid uploaded file");
            continue;
        }
        
        $file_extension = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
        $unique_filename = $upload_type . '_' . uniqid() . '_' . time() . '_' . $i . '.' . $file_extension;
        $file_path = $upload_dir . $unique_filename;
        $api_path = $base_path . '/public/ai_' . $upload_type . 's/' . $unique_filename;
        
        if (move_uploaded_file($files['tmp_name'][$i], $file_path)) {
            $uploaded_urls[] = $api_path;
            logDebug("File $i uploaded successfully: $api_path");
        } else {
            logDebug("Failed to move file $i");
        }
    }
    
    logDebug("Total files uploaded: " . count($uploaded_urls), $uploaded_urls);
    
    return ['success' => true, 'urls' => $uploaded_urls];
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
                            OR pi.serial_number LIKE '%$searchValue%')";
        }
        
        $totalRecordsQuery = "SELECT COUNT(ai_id) FROM ai_companions WHERE del = 0";
        $totalRecords = $conn->query($totalRecordsQuery)->fetch_row()[0];
        
        $totalFilteredQuery = "SELECT COUNT(ai.ai_id) 
                              FROM ai_companions ai
                              LEFT JOIN product_items pi ON ai.item_id = pi.item_id
                              WHERE $whereClause";
        $totalFiltered = $conn->query($totalFilteredQuery)->fetch_row()[0];
        
        $dataQuery = "SELECT 
                        ai.*,
                        pi.serial_number,
                        (SELECT COUNT(*) FROM user_ai_companions WHERE ai_id = ai.ai_id AND del = 0) as user_count
                      FROM ai_companions ai
                      LEFT JOIN product_items pi ON ai.item_id = pi.item_id
                      WHERE $whereClause
                      ORDER BY ai.created_at DESC
                      LIMIT $start, $length";
        
        $dataResult = $conn->query($dataQuery);
        $data = [];
        
        if ($dataResult) {
            while ($row = $dataResult->fetch_assoc()) {
                $row['ai_name_display'] = $row[$name_col];
                
                // Decode JSON arrays for video URLs
                $row['idle_video_urls_array'] = json_decode($row['idle_video_urls'] ?? '[]', true);
                $row['talking_video_urls_array'] = json_decode($row['talking_video_urls'] ?? '[]', true);
                
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
        
        $item_id = $_POST['item_id'] ?? 0;
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
        $voice_id = $_POST['voice_id'] ?? null;
        $voice_name = $_POST['voice_name'] ?? null;
        $voice_preview_url = $_POST['voice_preview_url'] ?? null;
        $status = $_POST['status'] ?? 1;
        
        if (empty($item_id)) {
            throw new Exception("Bottle item is required.");
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
            $ai_avatar_url = null;
            $ai_video_url = null;
            $idle_video_urls = '[]';
            $talking_video_urls = '[]';
            
            // Handle Avatar Upload (single)
            if (isset($_FILES['ai_avatar']) && $_FILES['ai_avatar']['error'] === UPLOAD_ERR_OK) {
                logDebug("Processing avatar upload");
                $upload_result = handleFileUpload('ai_avatar', 'avatar');
                
                if ($upload_result['success']) {
                    $ai_avatar_url = $upload_result['api_path'];
                    logDebug("Avatar uploaded successfully", $upload_result);
                } else {
                    throw new Exception("Avatar upload failed: " . $upload_result['error']);
                }
            }
            
            // Handle Intro Video Upload (single)
            if (isset($_FILES['ai_video']) && $_FILES['ai_video']['error'] === UPLOAD_ERR_OK) {
                logDebug("Processing intro video upload");
                $upload_result = handleFileUpload('ai_video', 'video');
                
                if ($upload_result['success']) {
                    $ai_video_url = $upload_result['api_path'];
                    logDebug("Intro video uploaded successfully", $upload_result);
                } else {
                    throw new Exception("Intro video upload failed: " . $upload_result['error']);
                }
            }
            
            // Handle Multiple Idle Videos
            logDebug("Checking for idle_videos", isset($_FILES['idle_videos']));
            $upload_result = handleMultipleFileUpload('idle_videos', 'video');
            
            if ($upload_result['success'] && !empty($upload_result['urls'])) {
                $idle_video_urls = json_encode($upload_result['urls']);
                logDebug("Idle videos uploaded successfully", $upload_result);
            }
            
            // Handle Multiple Talking Videos
            logDebug("Checking for talking_videos", isset($_FILES['talking_videos']));
            $upload_result = handleMultipleFileUpload('talking_videos', 'video');
            
            if ($upload_result['success'] && !empty($upload_result['urls'])) {
                $talking_video_urls = json_encode($upload_result['urls']);
                logDebug("Talking videos uploaded successfully", $upload_result);
            }
            
            logDebug("Preparing to insert into database", [
                'item_id' => $item_id,
                'ai_code' => $ai_code,
                'ai_avatar_url' => $ai_avatar_url,
                'ai_video_url' => $ai_video_url,
                'idle_video_urls' => $idle_video_urls,
                'talking_video_urls' => $talking_video_urls
            ]);
            
            $stmt = $conn->prepare("INSERT INTO ai_companions 
        (item_id, ai_code, 
         ai_name_th, ai_name_en, ai_name_cn, ai_name_jp, ai_name_kr,
         ai_avatar_url, 
         ai_video_url,
         idle_video_urls,
         talking_video_urls,
         system_prompt_th, system_prompt_en, system_prompt_cn, system_prompt_jp, system_prompt_kr,
         perfume_knowledge_th, perfume_knowledge_en, perfume_knowledge_cn, perfume_knowledge_jp, perfume_knowledge_kr,
         style_suggestions_th, style_suggestions_en, style_suggestions_cn, style_suggestions_jp, style_suggestions_kr,
         voice_id, voice_name, voice_preview_url,
         status, del) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
    
    $stmt->bind_param("issssssssssssssssssssssssssssi", 
        $item_id, 
        $ai_code,
        $ai_name_th, $ai_name_en, $ai_name_cn, $ai_name_jp, $ai_name_kr,
        $ai_avatar_url, 
        $ai_video_url,
        $idle_video_urls,
        $talking_video_urls,
        $system_prompt_th, $system_prompt_en, $system_prompt_cn, $system_prompt_jp, $system_prompt_kr,
        $perfume_knowledge_th, $perfume_knowledge_en, $perfume_knowledge_cn, $perfume_knowledge_jp, $perfume_knowledge_kr,
        $style_suggestions_th, $style_suggestions_en, $style_suggestions_cn, $style_suggestions_jp, $style_suggestions_kr,
        $voice_id, $voice_name, $voice_preview_url,
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
                    'idle_video_urls' => $idle_video_urls,
                    'talking_video_urls' => $talking_video_urls
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
    
    $item_id = $_POST['item_id'] ?? 0;
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
    
    $voice_id = $_POST['voice_id'] ?? null;
    $voice_name = $_POST['voice_name'] ?? null;
    $voice_preview_url = $_POST['voice_preview_url'] ?? null;
    
    $status = $_POST['status'] ?? 1;
    
    $delete_avatar = $_POST['delete_avatar'] ?? '0';
    $delete_video = $_POST['delete_video'] ?? '0';
    $deleted_idle_videos = isset($_POST['deleted_idle_videos']) ? json_decode($_POST['deleted_idle_videos'], true) : [];
    $deleted_talking_videos = isset($_POST['deleted_talking_videos']) ? json_decode($_POST['deleted_talking_videos'], true) : [];
    
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
        // Get current data
        $current_query = "SELECT ai_avatar_url, ai_video_url, idle_video_urls, talking_video_urls 
                          FROM ai_companions WHERE ai_id = $ai_id";
        $current_result = $conn->query($current_query);
        $current = $current_result->fetch_assoc();
        
        $ai_avatar_url = $current['ai_avatar_url'];
        $ai_video_url = $current['ai_video_url'];
        $idle_video_urls_array = json_decode($current['idle_video_urls'] ?? '[]', true);
        $talking_video_urls_array = json_decode($current['talking_video_urls'] ?? '[]', true);
        
        logDebug("Current idle videos", $idle_video_urls_array);
        logDebug("Videos to delete", $deleted_idle_videos);
        
        // Handle Avatar deletion/upload
        if ($delete_avatar === '1') {
            $ai_avatar_url = null;
        } elseif (isset($_FILES['ai_avatar']) && $_FILES['ai_avatar']['error'] === UPLOAD_ERR_OK) {
            $upload_result = handleFileUpload('ai_avatar', 'avatar');
            if ($upload_result['success']) {
                $ai_avatar_url = $upload_result['api_path'];
            }
        }
        
        // Handle Intro Video deletion/upload
        if ($delete_video === '1') {
            $ai_video_url = null;
        } elseif (isset($_FILES['ai_video']) && $_FILES['ai_video']['error'] === UPLOAD_ERR_OK) {
            $upload_result = handleFileUpload('ai_video', 'video');
            if ($upload_result['success']) {
                $ai_video_url = $upload_result['api_path'];
            }
        }
        
        // 🔧 FIX: Normalize URLs before comparison (remove backslash escapes)
        if (!empty($deleted_idle_videos)) {
            $normalized_deleted = array_map(function($url) {
                return str_replace('\\/', '/', $url);
            }, $deleted_idle_videos);
            
            $normalized_existing = array_map(function($url) {
                return str_replace('\\/', '/', $url);
            }, $idle_video_urls_array);
            
            $idle_video_urls_array = array_values(array_diff($normalized_existing, $normalized_deleted));
            
            logDebug("After idle deletion", $idle_video_urls_array);
        }
        
        // Handle new Idle Videos upload
        $upload_result = handleMultipleFileUpload('idle_videos', 'video');
        if ($upload_result['success'] && !empty($upload_result['urls'])) {
            $idle_video_urls_array = array_merge($idle_video_urls_array, $upload_result['urls']);
            logDebug("After adding new idle videos", $idle_video_urls_array);
        }
        
        // 🔧 FIX: Normalize URLs for talking videos too
        if (!empty($deleted_talking_videos)) {
            $normalized_deleted = array_map(function($url) {
                return str_replace('\\/', '/', $url);
            }, $deleted_talking_videos);
            
            $normalized_existing = array_map(function($url) {
                return str_replace('\\/', '/', $url);
            }, $talking_video_urls_array);
            
            $talking_video_urls_array = array_values(array_diff($normalized_existing, $normalized_deleted));
            
            logDebug("After talking deletion", $talking_video_urls_array);
        }
        
        // Handle new Talking Videos upload
        $upload_result = handleMultipleFileUpload('talking_videos', 'video');
        if ($upload_result['success'] && !empty($upload_result['urls'])) {
            $talking_video_urls_array = array_merge($talking_video_urls_array, $upload_result['urls']);
            logDebug("After adding new talking videos", $talking_video_urls_array);
        }
        
        // 🔧 FIX: Use JSON_UNESCAPED_SLASHES to prevent \/
        $idle_video_urls = json_encode($idle_video_urls_array, JSON_UNESCAPED_SLASHES);
        $talking_video_urls = json_encode($talking_video_urls_array, JSON_UNESCAPED_SLASHES);

        logDebug("Preparing UPDATE", [
            'idle_video_urls' => $idle_video_urls,
            'talking_video_urls' => $talking_video_urls,
            'voice_id' => $voice_id,
            'voice_name' => $voice_name
        ]);

        $update_query = "UPDATE ai_companions SET 
            item_id = ?, ai_code = ?,
            ai_name_th = ?, ai_name_en = ?, ai_name_cn = ?, ai_name_jp = ?, ai_name_kr = ?,
            ai_avatar_url = ?, ai_video_url = ?, idle_video_urls = ?, talking_video_urls = ?,
            system_prompt_th = ?, system_prompt_en = ?, system_prompt_cn = ?, system_prompt_jp = ?, system_prompt_kr = ?,
            perfume_knowledge_th = ?, perfume_knowledge_en = ?, perfume_knowledge_cn = ?, perfume_knowledge_jp = ?, perfume_knowledge_kr = ?,
            style_suggestions_th = ?, style_suggestions_en = ?, style_suggestions_cn = ?, style_suggestions_jp = ?, style_suggestions_kr = ?,
            voice_id = ?, voice_name = ?, voice_preview_url = ?,
            status = ?
            WHERE ai_id = ?";

        $stmt = $conn->prepare($update_query);

        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }

        // 🔧 FIX: Corrected bind_param type string (31 parameters)
        $stmt->bind_param("issssssssssssssssssssssssssssii",
            $item_id,              // 1 (i)
            $ai_code,              // 2 (s)
            $ai_name_th, $ai_name_en, $ai_name_cn, $ai_name_jp, $ai_name_kr, // 3-7 (sssss)
            $ai_avatar_url,        // 8 (s)
            $ai_video_url,         // 9 (s)
            $idle_video_urls,      // 10 (s)
            $talking_video_urls,   // 11 (s)
            $system_prompt_th, $system_prompt_en, $system_prompt_cn, $system_prompt_jp, $system_prompt_kr, // 12-16 (sssss)
            $perfume_knowledge_th, $perfume_knowledge_en, $perfume_knowledge_cn, $perfume_knowledge_jp, $perfume_knowledge_kr, // 17-21 (sssss)
            $style_suggestions_th, $style_suggestions_en, $style_suggestions_cn, $style_suggestions_jp, $style_suggestions_kr, // 22-26 (sssss)
            $voice_id,             // 27 (s)
            $voice_name,           // 28 (s)
            $voice_preview_url,    // 29 (s)
            $status,               // 30 (i)
            $ai_id                 // 31 (i)
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update AI Companion: " . $stmt->error);
        }
        
        $stmt->close();
        $conn->commit();
        
        $response = [
            'status' => 'success', 
            'message' => 'AI Companion updated successfully!'
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