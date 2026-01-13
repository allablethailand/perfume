<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');
require_once(__DIR__ . '/../../../../lib/base_directory.php');
require_once(__DIR__ . '/../../../../lib/connect.php');

global $base_path;
global $conn;

$response = ['status' => 'error', 'message' => ''];

try {
    if (!isset($_POST['action'])) {
        throw new Exception("No action specified.");
    }

    $action = $_POST['action'];

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
                $upload_dir = __DIR__ . '/../../../../public/ai_avatars/';
                
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['ai_avatar']['name'], PATHINFO_EXTENSION));
                $unique_filename = 'avatar_' . uniqid() . '_' . time() . '.' . $file_extension;
                $file_path = $upload_dir . $unique_filename;
                $api_path = $base_path . '/public/ai_avatars/' . $unique_filename;
                
                if (move_uploaded_file($_FILES['ai_avatar']['tmp_name'], $file_path)) {
                    $ai_avatar_path = $file_path;
                    $ai_avatar_url = $api_path;
                }
            }
            
            // Handle Video Upload
            if (isset($_FILES['ai_video']) && $_FILES['ai_video']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../../../../public/ai_videos/';
                
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['ai_video']['name'], PATHINFO_EXTENSION));
                $unique_filename = 'video_' . uniqid() . '_' . time() . '.' . $file_extension;
                $file_path = $upload_dir . $unique_filename;
                $api_path = $base_path . '/public/ai_videos/' . $unique_filename;
                
                if (move_uploaded_file($_FILES['ai_video']['tmp_name'], $file_path)) {
                    $ai_video_path = $file_path;
                    $ai_video_url = $api_path;
                }
            }
            
            $stmt = $conn->prepare("INSERT INTO ai_companions 
                (product_id, ai_code, 
                 ai_name_th, ai_name_en, ai_name_cn, ai_name_jp, ai_name_kr,
                 ai_avatar_path, ai_avatar_url, ai_video_path, ai_video_url,
                 system_prompt_th, system_prompt_en, system_prompt_cn, system_prompt_jp, system_prompt_kr,
                 perfume_knowledge_th, perfume_knowledge_en, perfume_knowledge_cn, perfume_knowledge_jp, perfume_knowledge_kr,
                 style_suggestions_th, style_suggestions_en, style_suggestions_cn, style_suggestions_jp, style_suggestions_kr,
                 status, del) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
            
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
            $stmt->close();
            
            $conn->commit();
            
            $response = [
                'status' => 'success', 
                'message' => 'AI Companion added successfully!',
                'ai_id' => $ai_id
            ];
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    // ========================================
    // EDIT AI COMPANION
    // ========================================
    } elseif ($action == 'editAICompanion') {
        
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
            $current_query = "SELECT ai_avatar_path, ai_video_path FROM ai_companions WHERE ai_id = $ai_id";
            $current_result = $conn->query($current_query);
            $current = $current_result->fetch_assoc();
            
            $ai_avatar_path = $current['ai_avatar_path'];
            $ai_avatar_url = null;
            $ai_video_path = $current['ai_video_path'];
            $ai_video_url = null;
            
            // Handle Avatar Upload (if new file uploaded)
            if (isset($_FILES['ai_avatar']) && $_FILES['ai_avatar']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../../../../public/ai_avatars/';
                
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['ai_avatar']['name'], PATHINFO_EXTENSION));
                $unique_filename = 'avatar_' . uniqid() . '_' . time() . '.' . $file_extension;
                $file_path = $upload_dir . $unique_filename;
                $api_path = $base_path . '/public/ai_avatars/' . $unique_filename;
                
                if (move_uploaded_file($_FILES['ai_avatar']['tmp_name'], $file_path)) {
                    // Delete old file if exists
                    if ($ai_avatar_path && file_exists($ai_avatar_path)) {
                        unlink($ai_avatar_path);
                    }
                    
                    $ai_avatar_path = $file_path;
                    $ai_avatar_url = $api_path;
                }
            }
            
            // Handle Video Upload (if new file uploaded)
            if (isset($_FILES['ai_video']) && $_FILES['ai_video']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../../../../public/ai_videos/';
                
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['ai_video']['name'], PATHINFO_EXTENSION));
                $unique_filename = 'video_' . uniqid() . '_' . time() . '.' . $file_extension;
                $file_path = $upload_dir . $unique_filename;
                $api_path = $base_path . '/public/ai_videos/' . $unique_filename;
                
                if (move_uploaded_file($_FILES['ai_video']['tmp_name'], $file_path)) {
                    // Delete old file if exists
                    if ($ai_video_path && file_exists($ai_video_path)) {
                        unlink($ai_video_path);
                    }
                    
                    $ai_video_path = $file_path;
                    $ai_video_url = $api_path;
                }
            }
            
            // Update database
            $update_query = "UPDATE ai_companions SET 
                product_id = ?, ai_code = ?,
                ai_name_th = ?, ai_name_en = ?, ai_name_cn = ?, ai_name_jp = ?, ai_name_kr = ?,";
            
            if ($ai_avatar_url) {
                $update_query .= " ai_avatar_path = ?, ai_avatar_url = ?,";
            }
            
            if ($ai_video_url) {
                $update_query .= " ai_video_path = ?, ai_video_url = ?,";
            }
            
            $update_query .= "
                system_prompt_th = ?, system_prompt_en = ?, system_prompt_cn = ?, system_prompt_jp = ?, system_prompt_kr = ?,
                perfume_knowledge_th = ?, perfume_knowledge_en = ?, perfume_knowledge_cn = ?, perfume_knowledge_jp = ?, perfume_knowledge_kr = ?,
                style_suggestions_th = ?, style_suggestions_en = ?, style_suggestions_cn = ?, style_suggestions_jp = ?, style_suggestions_kr = ?,
                status = ?
                WHERE ai_id = ?";
            
            $stmt = $conn->prepare($update_query);
            
            if ($ai_avatar_url && $ai_video_url) {
                $stmt->bind_param("isssssssssssssssssssssssssssii",
                    $product_id, $ai_code,
                    $ai_name_th, $ai_name_en, $ai_name_cn, $ai_name_jp, $ai_name_kr,
                    $ai_avatar_path, $ai_avatar_url,
                    $ai_video_path, $ai_video_url,
                    $system_prompt_th, $system_prompt_en, $system_prompt_cn, $system_prompt_jp, $system_prompt_kr,
                    $perfume_knowledge_th, $perfume_knowledge_en, $perfume_knowledge_cn, $perfume_knowledge_jp, $perfume_knowledge_kr,
                    $style_suggestions_th, $style_suggestions_en, $style_suggestions_cn, $style_suggestions_jp, $style_suggestions_kr,
                    $status, $ai_id);
            } elseif ($ai_avatar_url) {
                $stmt->bind_param("isssssssssssssssssssssssssi",
                    $product_id, $ai_code,
                    $ai_name_th, $ai_name_en, $ai_name_cn, $ai_name_jp, $ai_name_kr,
                    $ai_avatar_path, $ai_avatar_url,
                    $system_prompt_th, $system_prompt_en, $system_prompt_cn, $system_prompt_jp, $system_prompt_kr,
                    $perfume_knowledge_th, $perfume_knowledge_en, $perfume_knowledge_cn, $perfume_knowledge_jp, $perfume_knowledge_kr,
                    $style_suggestions_th, $style_suggestions_en, $style_suggestions_cn, $style_suggestions_jp, $style_suggestions_kr,
                    $status, $ai_id);
            } elseif ($ai_video_url) {
                $stmt->bind_param("isssssssssssssssssssssssssi",
                    $product_id, $ai_code,
                    $ai_name_th, $ai_name_en, $ai_name_cn, $ai_name_jp, $ai_name_kr,
                    $ai_video_path, $ai_video_url,
                    $system_prompt_th, $system_prompt_en, $system_prompt_cn, $system_prompt_jp, $system_prompt_kr,
                    $perfume_knowledge_th, $perfume_knowledge_en, $perfume_knowledge_cn, $perfume_knowledge_jp, $perfume_knowledge_kr,
                    $style_suggestions_th, $style_suggestions_en, $style_suggestions_cn, $style_suggestions_jp, $style_suggestions_kr,
                    $status, $ai_id);
            } else {
                $stmt->bind_param("issssssssssssssssssssssssi",
                    $product_id, $ai_code,
                    $ai_name_th, $ai_name_en, $ai_name_cn, $ai_name_jp, $ai_name_kr,
                    $system_prompt_th, $system_prompt_en, $system_prompt_cn, $system_prompt_jp, $system_prompt_kr,
                    $perfume_knowledge_th, $perfume_knowledge_en, $perfume_knowledge_cn, $perfume_knowledge_jp, $perfume_knowledge_kr,
                    $style_suggestions_th, $style_suggestions_en, $style_suggestions_cn, $style_suggestions_jp, $style_suggestions_kr,
                    $status, $ai_id);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update AI Companion: " . $stmt->error);
            }
            
            $stmt->close();
            
            $conn->commit();
            
            $response = [
                'status' => 'success', 
                'message' => 'AI Companion updated successfully!'
            ];
            
        } catch (Exception $e) {
            $conn->rollback();
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
    error_log("Error in process_ai_companions.php: " . $e->getMessage());
}

$conn->close();
echo json_encode($response);
?>