<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../../lib/connect.php');
require_once(__DIR__ . '/../../lib/jwt_helper.php');

global $conn;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verify JWT
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$jwt = $matches[1];
$decoded = verifyJWT($jwt);

if (!$decoded) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid token']);
    exit();
}

$user_id = requireAuth();
$companion_id = intval($_GET['companion_id'] ?? 0);

if (!$companion_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Companion ID required']);
    exit();
}

try {
    // ดึงข้อมูล companion และ AI
    $stmt = $conn->prepare("
        SELECT 
            uac.user_companion_id,
            uac.ai_id,
            uac.user_id,
            uac.preferred_language,
            uac.setup_completed,
            ac.ai_code,
            ac.ai_name_th,
            ac.ai_name_en,
            ac.ai_name_cn,
            ac.ai_name_jp,
            ac.ai_name_kr,
            ac.ai_avatar_url,
            ac.ai_video_url,
            ac.perfume_knowledge_th,
            ac.perfume_knowledge_en,
            ac.perfume_knowledge_cn,
            ac.perfume_knowledge_jp,
            ac.perfume_knowledge_kr
        FROM user_ai_companions uac
        INNER JOIN ai_companions ac ON uac.ai_id = ac.ai_id
        WHERE uac.user_companion_id = ? 
          AND uac.user_id = ? 
          AND uac.del = 0
          AND ac.del = 0
        LIMIT 1
    ");
    $stmt->bind_param("ii", $companion_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    
    if (!$data) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Companion not found']);
        exit();
    }
    
    echo json_encode([
        'status' => 'success',
        'user_id' => $user_id,
        'preferred_language' => $data['preferred_language'],
        'setup_completed' => $data['setup_completed'],
        'ai_data' => [
            'ai_id' => $data['ai_id'],
            'ai_code' => $data['ai_code'],
            'ai_name_th' => $data['ai_name_th'],
            'ai_name_en' => $data['ai_name_en'],
            'ai_name_cn' => $data['ai_name_cn'],
            'ai_name_jp' => $data['ai_name_jp'],
            'ai_name_kr' => $data['ai_name_kr'],
            'ai_avatar_url' => $data['ai_avatar_url'],
            'ai_video_url' => $data['ai_video_url'],
            'perfume_knowledge_th' => $data['perfume_knowledge_th'],
            'perfume_knowledge_en' => $data['perfume_knowledge_en'],
            'perfume_knowledge_cn' => $data['perfume_knowledge_cn'],
            'perfume_knowledge_jp' => $data['perfume_knowledge_jp'],
            'perfume_knowledge_kr' => $data['perfume_knowledge_kr']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("get_companion_data error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>