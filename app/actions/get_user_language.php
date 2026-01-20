<?php
/**
 * Get User Preferred Language
 * 
 * Endpoint สำหรับดึงภาษาที่ user เลือกจาก preferred_language
 * GET: /app/actions/get_user_language.php
 */

require_once('../../lib/connect.php');
require_once('../../lib/jwt_helper.php');

global $conn;

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set JSON header
header('Content-Type: application/json');

// ตรวจสอบ JWT
$headers = getallheaders();
$jwt = null;
$user_id = null;

if (isset($headers['Authorization'])) {
    $jwt = str_replace('Bearer ', '', $headers['Authorization']);
    $decoded = verifyJWT($jwt);
    if ($decoded) {
        $user_id = requireAuth();
    }
}

if (!$user_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please login first',
        'require_login' => true
    ]);
    exit;
}

try {
    // ดึง preferred_language จาก user_ai_companions
    $stmt = $conn->prepare("
        SELECT preferred_language 
        FROM user_ai_companions 
        WHERE user_id = ? 
        AND status = 1 
        AND del = 0 
        ORDER BY last_active_at DESC 
        LIMIT 1
    ");
    
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $preferred_language = !empty($row['preferred_language']) ? $row['preferred_language'] : 'th';
        
        echo json_encode([
            'status' => 'success',
            'preferred_language' => $preferred_language
        ]);
    } else {
        // ถ้าไม่มี companion ให้ใช้ default
        echo json_encode([
            'status' => 'success',
            'preferred_language' => 'th'
        ]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>