<?php
ob_start();
header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');
require_once(__DIR__ . '/../../../../lib/base_directory.php');
require_once(__DIR__ . '/../../../../lib/connect.php');
require_once(__DIR__ . '/../../../../inc/getFunctions.php');

global $conn;

$response = array('status' => 'error', 'message' => '');

// ============================================
// ฟังก์ชันเข้ารหัส/ถอดรหัส API Key (Simple Encryption)
// ============================================
function encryptApiKey($apiKey) {
    if (empty($apiKey)) return null;
    
    // ใช้ OpenSSL encryption (ควรเก็บ SECRET_KEY ใน config หรือ environment variable)
    $secret_key = 'YOUR_SECRET_ENCRYPTION_KEY_HERE'; // ⚠️ เปลี่ยนเป็น key ของคุณ
    $encryption_method = "AES-256-CBC";
    $secret_iv = 'YOUR_SECRET_IV_HERE'; // ⚠️ เปลี่ยนเป็น IV ของคุณ
    
    $key = hash('sha256', $secret_key);
    $iv = substr(hash('sha256', $secret_iv), 0, 16);
    
    return base64_encode(openssl_encrypt($apiKey, $encryption_method, $key, 0, $iv));
}

function decryptApiKey($encryptedKey) {
    if (empty($encryptedKey)) return null;
    
    $secret_key = 'YOUR_SECRET_ENCRYPTION_KEY_HERE';
    $encryption_method = "AES-256-CBC";
    $secret_iv = 'YOUR_SECRET_IV_HERE';
    
    $key = hash('sha256', $secret_key);
    $iv = substr(hash('sha256', $secret_iv), 0, 16);
    
    return openssl_decrypt(base64_decode($encryptedKey), $encryption_method, $key, 0, $iv);
}

try {
    // ============================================
    // Action: Get AI Models Data (DataTable)
    // ============================================
    if (isset($_POST['action']) && $_POST['action'] == 'getData_ai_models') {
        $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $searchValue = isset($_POST['search']['value']) ? $conn->real_escape_string($_POST['search']['value']) : '';

        $whereClause = "1=1"; // ไม่มี soft delete ในตาราง ai_models

        if (!empty($searchValue)) {
            $whereClause .= " AND (model_name LIKE '%$searchValue%' OR model_code LIKE '%$searchValue%' OR provider LIKE '%$searchValue%')";
        }

        $orderBy = "priority DESC, created_at DESC";

        // Query ข้อมูล + เช็คว่ามี API Key หรือไม่
        $dataQuery = "SELECT 
                        model_id, 
                        model_code, 
                        model_name, 
                        provider, 
                        is_free, 
                        is_active, 
                        priority,
                        CASE WHEN api_key IS NOT NULL AND api_key != '' THEN '1' ELSE '0' END as has_api_key
                    FROM ai_models 
                    WHERE $whereClause
                    ORDER BY $orderBy
                    LIMIT $start, $length";

        $dataResult = $conn->query($dataQuery);
        $data = [];
        while ($row = $dataResult->fetch_assoc()) {
            $data[] = $row;
        }

        $Index = 'model_id';
        $totalRecords = getTotalRecords($conn, 'ai_models', $Index);
        $totalFiltered = getFilteredRecordsCount($conn, 'ai_models', $whereClause, $Index);

        $response = [
            "draw" => intval($draw),
            "recordsTotal" => intval($totalRecords),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data
        ];
    }
    
    // ============================================
    // Action: Add AI Model
    // ============================================
    elseif (isset($_POST['action']) && $_POST['action'] == 'addAiModel') {
        $model_code = $_POST['model_code'] ?? '';
        $model_name = $_POST['model_name'] ?? '';
        $provider = $_POST['provider'] ?? '';
        $api_key = $_POST['api_key'] ?? '';
        $api_endpoint = $_POST['api_endpoint'] ?? '';
        $is_free = isset($_POST['is_free']) ? intval($_POST['is_free']) : 0;
        $max_tokens = isset($_POST['max_tokens']) && $_POST['max_tokens'] !== '' ? intval($_POST['max_tokens']) : null;
        $cost_per_1k_tokens = isset($_POST['cost_per_1k_tokens']) && $_POST['cost_per_1k_tokens'] !== '' ? floatval($_POST['cost_per_1k_tokens']) : null;
        $priority = isset($_POST['priority']) ? intval($_POST['priority']) : 0;
        $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;

        // Validate
        if (empty($model_code) || empty($model_name) || empty($provider)) {
            throw new Exception("กรุณากรอกข้อมูลให้ครบถ้วน");
        }

        // เช็คว่า model_code ซ้ำหรือไม่
        $checkStmt = $conn->prepare("SELECT model_id FROM ai_models WHERE model_code = ?");
        $checkStmt->bind_param("s", $model_code);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult->num_rows > 0) {
            throw new Exception("Model Code นี้มีในระบบแล้ว");
        }
        $checkStmt->close();

        // เข้ารหัส API Key ถ้ามี
        $encrypted_api_key = !empty($api_key) ? encryptApiKey($api_key) : null;

        // Insert
        $stmt = $conn->prepare("INSERT INTO ai_models 
            (model_code, model_name, provider, api_key, api_endpoint, is_free, max_tokens, cost_per_1k_tokens, priority, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param(
            "sssssiddii",
            $model_code,
            $model_name,
            $provider,
            $encrypted_api_key,
            $api_endpoint,
            $is_free,
            $max_tokens,
            $cost_per_1k_tokens,
            $priority,
            $is_active
        );

        if (!$stmt->execute()) {
            throw new Exception("เกิดข้อผิดพลาดในการบันทึก: " . $stmt->error);
        }

        $response = array('status' => 'success', 'message' => 'บันทึก AI Model สำเร็จ');
    }
    
    // ============================================
    // Action: Edit AI Model
    // ============================================
    elseif (isset($_POST['action']) && $_POST['action'] == 'editAiModel') {
        $model_id = $_POST['model_id'] ?? '';
        $model_code = $_POST['model_code'] ?? '';
        $model_name = $_POST['model_name'] ?? '';
        $provider = $_POST['provider'] ?? '';
        $api_key = $_POST['api_key'] ?? '';
        $api_endpoint = $_POST['api_endpoint'] ?? '';
        $is_free = isset($_POST['is_free']) ? intval($_POST['is_free']) : 0;
        $max_tokens = isset($_POST['max_tokens']) && $_POST['max_tokens'] !== '' ? intval($_POST['max_tokens']) : null;
        $cost_per_1k_tokens = isset($_POST['cost_per_1k_tokens']) && $_POST['cost_per_1k_tokens'] !== '' ? floatval($_POST['cost_per_1k_tokens']) : null;
        $priority = isset($_POST['priority']) ? intval($_POST['priority']) : 0;
        $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;

        if (empty($model_id)) {
            throw new Exception("ไม่พบ Model ID");
        }

        // เช็คว่า model_code ซ้ำหรือไม่ (ยกเว้นตัวเอง)
        $checkStmt = $conn->prepare("SELECT model_id FROM ai_models WHERE model_code = ? AND model_id != ?");
        $checkStmt->bind_param("si", $model_code, $model_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult->num_rows > 0) {
            throw new Exception("Model Code นี้มีในระบบแล้ว");
        }
        $checkStmt->close();

        // ถ้ามี API Key ใหม่ ให้เข้ารหัส ถ้าไม่มีให้ใช้ค่าเดิม
        if (!empty($api_key)) {
            $encrypted_api_key = encryptApiKey($api_key);
            $stmt = $conn->prepare("UPDATE ai_models 
                SET model_code = ?, 
                    model_name = ?, 
                    provider = ?, 
                    api_key = ?,
                    api_endpoint = ?,
                    is_free = ?, 
                    max_tokens = ?, 
                    cost_per_1k_tokens = ?, 
                    priority = ?, 
                    is_active = ?
                WHERE model_id = ?");
            
            $stmt->bind_param(
                "sssssiddiii",
                $model_code,
                $model_name,
                $provider,
                $encrypted_api_key,
                $api_endpoint,
                $is_free,
                $max_tokens,
                $cost_per_1k_tokens,
                $priority,
                $is_active,
                $model_id
            );
        } else {
            // ไม่อัปเดต API Key
            $stmt = $conn->prepare("UPDATE ai_models 
                SET model_code = ?, 
                    model_name = ?, 
                    provider = ?, 
                    api_endpoint = ?,
                    is_free = ?, 
                    max_tokens = ?, 
                    cost_per_1k_tokens = ?, 
                    priority = ?, 
                    is_active = ?
                WHERE model_id = ?");
            
            $stmt->bind_param(
                "ssssiddiii",
                $model_code,
                $model_name,
                $provider,
                $api_endpoint,
                $is_free,
                $max_tokens,
                $cost_per_1k_tokens,
                $priority,
                $is_active,
                $model_id
            );
        }

        if (!$stmt->execute()) {
            throw new Exception("เกิดข้อผิดพลาดในการแก้ไข: " . $stmt->error);
        }

        $response = array('status' => 'success', 'message' => 'แก้ไข AI Model สำเร็จ');
    }
    
    // ============================================
    // Action: Toggle Active Status
    // ============================================
    elseif (isset($_POST['action']) && $_POST['action'] == 'toggleStatus') {
        $model_id = $_POST['model_id'] ?? '';
        $new_status = $_POST['new_status'] ?? '';

        if (empty($model_id)) {
            throw new Exception("ไม่พบ Model ID");
        }

        $stmt = $conn->prepare("UPDATE ai_models SET is_active = ? WHERE model_id = ?");
        $stmt->bind_param("ii", $new_status, $model_id);

        if (!$stmt->execute()) {
            throw new Exception("เกิดข้อผิดพลาดในการอัปเดตสถานะ: " . $stmt->error);
        }

        $response = array('status' => 'success', 'message' => 'อัปเดตสถานะสำเร็จ');
    }
    
    // ============================================
    // Action: Update API Key
    // ============================================
    elseif (isset($_POST['action']) && $_POST['action'] == 'updateApiKey') {
        $model_id = $_POST['model_id'] ?? '';
        $api_key = $_POST['api_key'] ?? '';

        if (empty($model_id)) {
            throw new Exception("ไม่พบ Model ID");
        }

        if (empty($api_key)) {
            throw new Exception("กรุณากรอก API Key");
        }

        $encrypted_api_key = encryptApiKey($api_key);

        $stmt = $conn->prepare("UPDATE ai_models SET api_key = ? WHERE model_id = ?");
        $stmt->bind_param("si", $encrypted_api_key, $model_id);

        if (!$stmt->execute()) {
            throw new Exception("เกิดข้อผิดพลาดในการอัปเดต API Key: " . $stmt->error);
        }

        $response = array('status' => 'success', 'message' => 'อัปเดต API Key สำเร็จ');
    }
    
    // ============================================
    // Action: Get Active Model (สำหรับใช้ตอบ AI Chat)
    // ============================================
    elseif (isset($_POST['action']) && $_POST['action'] == 'getActiveModel') {
        // ดึง Model ที่ Active และมี Priority สูงสุด
        $stmt = $conn->prepare("SELECT 
                                    model_id, 
                                    model_code, 
                                    model_name, 
                                    provider, 
                                    api_key,
                                    api_endpoint,
                                    max_tokens
                                FROM ai_models 
                                WHERE is_active = 1 
                                ORDER BY priority DESC, created_at DESC 
                                LIMIT 1");
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $model = $result->fetch_assoc();
            
            // ถอดรหัส API Key
            $model['api_key'] = decryptApiKey($model['api_key']);
            
            $response = array(
                'status' => 'success', 
                'model' => $model
            );
        } else {
            throw new Exception("ไม่พบ AI Model ที่ Active");
        }
    }

} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
}

if (isset($stmt)) {
    $stmt->close();
}
$conn->close();

ob_end_clean();
echo json_encode($response);
?>