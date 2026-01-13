<?php
header('Content-Type: application/json');
require_once('../../lib/connect.php');
require_once('../../lib/jwt_helper.php');
global $conn;

$response = ['status' => 'error', 'message' => ''];

try {
    // ตรวจสอบ JWT
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        throw new Exception("Unauthorized - No token provided");
    }

    $jwt = $matches[1];
    $decoded = validateJWT($jwt);
    
    if (!$decoded) {
        throw new Exception("Unauthorized - Invalid token");
    }

    $user_id = $decoded->data->user_id;

    // รับ companion_id
    if (!isset($_GET['companion_id'])) {
        throw new Exception("Missing companion_id parameter");
    }

    $companion_id = intval($_GET['companion_id']);

    // ดึงข้อมูล AI Companion พร้อมตรวจสอบว่าเป็นของ user คนนี้
    $stmt = $conn->prepare("
        SELECT 
            uac.user_companion_id,
            uac.ai_id,
            uac.preferred_language,
            uac.setup_completed,
            ai.ai_name_th,
            ai.ai_name_en,
            ai.ai_name_cn,
            ai.ai_name_jp,
            ai.ai_name_kr,
            ai.ai_avatar_path,
            ai.ai_avatar_url,
            ai.ai_video_path,
            ai.ai_video_url,
            ai.perfume_knowledge_th,
            ai.perfume_knowledge_en,
            ai.perfume_knowledge_cn,
            ai.perfume_knowledge_jp,
            ai.perfume_knowledge_kr,
            ai.style_suggestions_th,
            ai.style_suggestions_en,
            ai.style_suggestions_cn,
            ai.style_suggestions_jp,
            ai.style_suggestions_kr
        FROM user_ai_companions uac
        INNER JOIN ai_companions ai ON uac.ai_id = ai.ai_id
        WHERE uac.user_companion_id = ? 
        AND uac.user_id = ? 
        AND uac.del = 0
        AND ai.del = 0
        AND ai.status = 1
    ");
    
    $stmt->bind_param("ii", $companion_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("AI Companion not found or unauthorized access");
    }

    $data = $result->fetch_assoc();
    $stmt->close();

    // ถ้ามี ai_avatar_path ให้ใช้ path แทน url
    if (!empty($data['ai_avatar_path'])) {
        $data['ai_avatar_display'] = $data['ai_avatar_path'];
    } elseif (!empty($data['ai_avatar_url'])) {
        $data['ai_avatar_display'] = $data['ai_avatar_url'];
    } else {
        $data['ai_avatar_display'] = null;
    }

    // ถ้ามี ai_video_path ให้ใช้ path แทน url
    if (!empty($data['ai_video_path'])) {
        $data['ai_video_display'] = $data['ai_video_path'];
    } elseif (!empty($data['ai_video_url'])) {
        $data['ai_video_display'] = $data['ai_video_url'];
    } else {
        $data['ai_video_display'] = null;
    }

    $response = [
        'status' => 'success',
        'data' => $data
    ];

} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
    error_log("Error in get_companion_info.php: " . $e->getMessage());
}

$conn->close();
echo json_encode($response);
?>