<?php
/**
 * Update Preferred Language (Guest Mode Supported)
 * ✅ รองรับทั้ง JWT และ Guest Mode
 */

header('Content-Type: application/json');
require_once('../../lib/connect.php');
require_once('../../lib/jwt_helper.php');

global $conn;

// ========== รับข้อมูล ==========
$input = json_decode(file_get_contents('php://input'), true);

// Fallback to $_POST if JSON decode fails
if (!$input) {
    $input = $_POST;
}

$user_companion_id = isset($input['user_companion_id']) ? intval($input['user_companion_id']) : null;
$preferred_language = isset($input['preferred_language']) ? trim($input['preferred_language']) : null;
$ai_code = isset($input['ai_code']) ? strtoupper(trim($input['ai_code'])) : '';

// ========== ระบุตัวตน: JWT หรือ Guest Mode ==========
$user_id = null;
$is_guest_mode = false;

// ลอง JWT ก่อน
$headers = getallheaders();
$jwt = null;

if (isset($headers['Authorization'])) {
    $jwt = str_replace('Bearer ', '', $headers['Authorization']);
} elseif (isset($headers['X-Auth-Token'])) {
    $jwt = $headers['X-Auth-Token'];
}

// ถ้ามี JWT ให้ verify
if ($jwt) {
    try {
        $decoded = verifyJWT($jwt);
        if ($decoded && isset($decoded->data->user_id)) {
            $user_id = intval($decoded->data->user_id);
        }
    } catch (Exception $e) {
        // JWT ไม่ valid, ลอง guest mode
    }
}

// ถ้าไม่มี JWT = Guest Mode
if (!$user_id) {
    $is_guest_mode = true;
    
    // ✅ Guest Mode ต้องมี user_companion_id หรือ ai_code
    if (!$user_companion_id && !$ai_code) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Please provide user_companion_id or ai_code',
            'require_login' => false
        ]);
        exit;
    }
}

// ========== Validate Parameters ==========
if (!$user_companion_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing user_companion_id'
    ]);
    exit;
}

if (!$preferred_language) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing preferred_language'
    ]);
    exit;
}

// Validate language
$valid_languages = ['th', 'en', 'cn', 'jp', 'kr'];
if (!in_array($preferred_language, $valid_languages)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid language. Allowed: ' . implode(', ', $valid_languages)
    ]);
    exit;
}

try {
    // ✅ ถ้าเป็น Login Mode ให้เช็คสิทธิ์
    if (!$is_guest_mode && $user_id) {
        $stmt = $conn->prepare("
            SELECT user_companion_id 
            FROM user_ai_companions 
            WHERE user_companion_id = ? 
            AND user_id = ? 
            AND status = '1' 
            AND del = 0
        ");
        $stmt->bind_param("ii", $user_companion_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ]);
            exit;
        }
        $stmt->close();
    }
    // ✅ Guest Mode - เช็คว่า companion มีอยู่จริง
    else {
        $stmt = $conn->prepare("
            SELECT user_companion_id, user_id
            FROM user_ai_companions 
            WHERE user_companion_id = ? 
            AND status = '1' 
            AND del = 0
        ");
        $stmt->bind_param("i", $user_companion_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Companion not found'
            ]);
            exit;
        }
        
        // เก็บ user_id สำหรับ guest mode
        $companion_data = $result->fetch_assoc();
        $user_id = $companion_data['user_id'];
        
        $stmt->close();
    }

    // ========== อัพเดทภาษา ==========
    // ========== อัพเดทภาษา ==========
$stmt = $conn->prepare("
    UPDATE user_ai_companions 
    SET preferred_language = ?,
        last_active_at = NOW()
    WHERE user_companion_id = ?
");
$stmt->bind_param("si", $preferred_language, $user_companion_id);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Language preference updated successfully',
        'guest_mode' => $is_guest_mode,
        'user_companion_id' => $user_companion_id,
        'preferred_language' => $preferred_language
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to update language preference: ' . $stmt->error
    ]);
}

$stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>