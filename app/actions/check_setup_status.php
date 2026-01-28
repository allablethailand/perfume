<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../../lib/connect.php');
require_once(__DIR__ . '/../../lib/jwt_helper.php');

global $conn;

$response = ['status' => 'error', 'message' => ''];

try {
    // รับ ai_code จาก URL
    $ai_code = strtoupper(trim($_GET['ai_code'] ?? ''));
    
    if (empty($ai_code) || !preg_match('/^AI-[A-Z0-9]{8,}$/', $ai_code)) {
        throw new Exception("Invalid AI code format");
    }
    
    // ตรวจสอบว่ามี JWT หรือไม่
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    $jwt = null;
    $user_id = null;
    
    if (!empty($authHeader) && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $jwt = $matches[1];
        $decoded = verifyJWT($jwt);
        
        if ($decoded) {
            $user_id = requireAuth();
        }
    }
    
    // Step 1: ตรวจสอบว่า AI Code มีอยู่จริงหรือไม่
    $stmt = $conn->prepare("
        SELECT ai_id, ai_code, ai_name_th, ai_name_en, ai_name_cn, ai_name_jp, ai_name_kr,
               ai_avatar_url, idle_video_url, talking_video_url
        FROM ai_companions 
        WHERE ai_code = ? AND status = 1 AND del = 0
        LIMIT 1
    ");
    $stmt->bind_param("s", $ai_code);
    $stmt->execute();
    $result = $stmt->get_result();
    $ai = $result->fetch_assoc();
    $stmt->close();
    
    if (!$ai) {
        throw new Exception("AI Companion not found");
    }
    
    $ai_id = $ai['ai_id'];
    
    // Step 2: ถ้าไม่มี JWT -> ยังไม่ได้ login
    if (!$user_id) {
        echo json_encode([
            'status' => 'success',
            'step' => 'need_register',
            'message' => 'Please register to continue',
            'ai_data' => $ai
        ]);
        exit();
    }
    
    // Step 3: มี JWT -> เช็คว่ามี companion หรือยัง
    $stmt = $conn->prepare("
        SELECT user_companion_id, setup_completed, preferred_language
        FROM user_ai_companions
        WHERE ai_id = ? AND user_id = ? AND del = 0
        LIMIT 1
    ");
    $stmt->bind_param("ii", $ai_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $companion = $result->fetch_assoc();
    $stmt->close();
    
    // Step 4: ไม่มี companion -> สร้างใหม่
    if (!$companion) {
        $default_lang = $_GET['lang'] ?? 'th';
        
        $stmt = $conn->prepare("
            INSERT INTO user_ai_companions 
            (ai_id, user_id, preferred_language, setup_completed, first_scan_at, last_active_at, status, del)
            VALUES (?, ?, ?, 0, NOW(), NOW(), 1, 0)
        ");
        $stmt->bind_param("iis", $ai_id, $user_id, $default_lang);
        $stmt->execute();
        $companion_id = $conn->insert_id;
        $stmt->close();
        
        echo json_encode([
            'status' => 'success',
            'step' => 'need_setup',
            'message' => 'AI Companion created. Please complete setup.',
            'companion_id' => $companion_id,
            'preferred_language' => $default_lang,
            'ai_data' => $ai
        ]);
        exit();
    }
    
    // Step 5: มี companion แล้ว -> เช็ค setup
    if ($companion['setup_completed'] == 0) {
        echo json_encode([
            'status' => 'success',
            'step' => 'need_setup',
            'message' => 'Please complete AI setup',
            'companion_id' => $companion['user_companion_id'],
            'preferred_language' => $companion['preferred_language'],
            'ai_data' => $ai
        ]);
    } else {
        echo json_encode([
            'status' => 'success',
            'step' => 'ready_to_chat',
            'message' => 'AI Companion ready',
            'companion_id' => $companion['user_companion_id'],
            'preferred_language' => $companion['preferred_language'],
            'ai_data' => $ai
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>