<?php
/**
 * Get Companion Info by ID (No Auth Required)
 * สำหรับดึงข้อมูล AI companion จาก user_companion_id
 */

header('Content-Type: application/json');
require_once('../../lib/connect.php');

global $conn;

$user_companion_id = isset($_GET['user_companion_id']) ? intval($_GET['user_companion_id']) : 0;

if (!$user_companion_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing user_companion_id'
    ]);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT 
            uc.user_companion_id,
            uc.ai_id,
            uc.user_id,
            uc.preferred_language,
            ai.ai_code,
            ai.ai_name_th,
            ai.ai_name_en,
            ai.ai_name_cn,
            ai.ai_name_jp,
            ai.ai_name_kr,
            ai.ai_avatar_url,
            ai.idle_video_url
        FROM user_ai_companions uc
        INNER JOIN ai_companions ai ON uc.ai_id = ai.ai_id
        WHERE uc.user_companion_id = ?
        AND uc.status = '1'
        AND uc.del = 0
        AND ai.status = '1'
        AND ai.del = 0
    ");
    
    $stmt->bind_param('i', $user_companion_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Companion not found'
        ]);
        exit;
    }
    
    $data = $result->fetch_assoc();
    $stmt->close();
    
    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'user_companion_id' => $data['user_companion_id']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>