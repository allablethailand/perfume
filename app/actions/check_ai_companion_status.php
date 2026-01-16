<?php
header('Content-Type: application/json');
require_once('../../lib/connect.php');
require_once('../../lib/jwt_helper.php');

global $conn;

// Get JWT token
$headers = getallheaders();
$jwt = null;

if (isset($headers['Authorization'])) {
    $jwt = str_replace('Bearer ', '', $headers['Authorization']);
} elseif (isset($headers['X-Auth-Token'])) {
    $jwt = $headers['X-Auth-Token'];
}

if (!$jwt) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No token provided'
    ]);
    exit;
}

// Verify JWT
$decoded = verifyJWT($jwt);
if (!$decoded) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid token'
    ]);
    exit;
}

   $user_id = isset($decoded->data->user_id) ? intval($decoded->data->user_id) : null;

try {
    // ตรวจสอบว่า user มี AI companion ที่ setup เสร็จแล้วหรือยัง
    $stmt = $conn->prepare("
        SELECT 
            uc.user_companion_id,
            uc.ai_id,
            uc.preferred_language,
            uc.setup_completed,
            uc.setup_completed_at,
            ai.ai_name_th,
            ai.ai_name_en,
            ai.ai_avatar_url
        FROM user_ai_companions uc
        LEFT JOIN ai_companions ai ON uc.ai_id = ai.ai_id
        WHERE uc.user_id = ? 
        AND uc.status = '1'
        AND uc.del = 0
        ORDER BY uc.user_companion_id DESC
        LIMIT 1
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $companion = $result->fetch_assoc();
        
        // ถ้า setup_completed = 1 แสดงว่าเคยทำแบบสอบถามแล้ว
        if ($companion['setup_completed'] == 1) {
            echo json_encode([
                'status' => 'success',
                'has_companion' => true,
                'setup_completed' => true,
                'data' => $companion
            ]);
        } else {
            // มี companion แต่ยังไม่ได้ทำแบบสอบถาม
            echo json_encode([
                'status' => 'success',
                'has_companion' => true,
                'setup_completed' => false,
                'data' => $companion
            ]);
        }
    } else {
        // ยังไม่มี AI companion เลย
        echo json_encode([
            'status' => 'success',
            'has_companion' => false,
            'setup_completed' => false
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