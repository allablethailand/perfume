<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../../lib/connect.php');
require_once(__DIR__ . '/../../lib/jwt_helper.php');

global $conn;

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
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

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$user_id = requireAuth();
$ai_code = strtoupper(trim($_POST['ai_code'] ?? ''));

// Validate AI code format
if (empty($ai_code) || !preg_match('/^AI-[A-Z0-9]{8,}$/', $ai_code)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid AI code format']);
    exit();
}

try {
    // Step 1: ตรวจสอบว่า AI Code มีอยู่จริงในระบบหรือไม่
    $stmt = $conn->prepare("
        SELECT ai_id, ai_code, ai_name_th, ai_name_en, status 
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
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'AI Companion not found or inactive'
        ]);
        exit();
    }
    
    $ai_id = $ai['ai_id'];
    
    // Step 2: ตรวจสอบว่า AI มีเจ้าของแล้วหรือยัง
    $stmt = $conn->prepare("
        SELECT 
            user_companion_id,
            preferred_language,
            setup_completed,
            status,
            user_id
        FROM user_ai_companions
        WHERE ai_id = ? AND del = 0
        LIMIT 1
    ");
    $stmt->bind_param("i", $ai_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing_companion = $result->fetch_assoc();
    $stmt->close();
    
    // Step 3: ถ้ามีคนอื่นเป็นเจ้าของแล้ว -> แสดง error
    if ($existing_companion && $existing_companion['user_id'] != $user_id) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'This AI Companion already has an owner'
        ]);
        exit();
    }
    
    // Step 4: ถ้าไม่มีเจ้าของเลย -> สร้างใหม่
    if (!$existing_companion) {
        $conn->begin_transaction();
        
        try {
            // สร้าง user_ai_companion ใหม่
            $default_lang = $_SESSION['pending_ai_lang'] ?? 'th';
            $stmt = $conn->prepare("
                INSERT INTO user_ai_companions 
                (ai_id, user_id, preferred_language, setup_completed, first_scan_at, last_active_at, status, del)
                VALUES (?, ?, ?, 0, NOW(), NOW(), 1, 0)
            ");
            $stmt->bind_param("iis", $ai_id, $user_id, $default_lang);
            $stmt->execute();
            $user_companion_id = $conn->insert_id;
            $stmt->close();
            
            $conn->commit();
            
            // ส่งไปหน้า activation
            echo json_encode([
                'status' => 'success',
                'message' => 'AI Companion activated',
                'redirect_url' => '?ai_activation&companion_id=' . $user_companion_id . '&lang=' . $default_lang,
                'data' => [
                    'ai_id' => $ai_id,
                    'user_companion_id' => $user_companion_id,
                    'setup_completed' => 0
                ]
            ]);
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }
    
    // Step 5: มี companion แล้ว และเป็นของ user คนนี้เอง -> ตรวจสอบสถานะ setup
    if ($existing_companion['setup_completed'] == 0) {
        // ยัง setup ไม่เสร็จ -> ส่งไปหน้า activation
        echo json_encode([
            'status' => 'success',
            'message' => 'Continue AI setup',
            'redirect_url' => '?ai_activation&companion_id=' . $existing_companion['user_companion_id'] . '&lang=' . $existing_companion['preferred_language'],
            'data' => [
                'ai_id' => $ai_id,
                'user_companion_id' => $existing_companion['user_companion_id'],
                'setup_completed' => 0
            ]
        ]);
    } else {
        // Setup เสร็จแล้ว -> ส่งไปหน้าแชท
        // อัพเดท last_active_at
        $stmt = $conn->prepare("
            UPDATE user_ai_companions 
            SET last_active_at = NOW() 
            WHERE user_companion_id = ?
        ");
        $stmt->bind_param("i", $existing_companion['user_companion_id']);
        $stmt->execute();
        $stmt->close();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Welcome back!',
            'redirect_url' => '?ai_chat_3d&lang=' . $existing_companion['preferred_language'],
            'data' => [
                'ai_id' => $ai_id,
                'user_companion_id' => $existing_companion['user_companion_id'],
                'setup_completed' => 1
            ]
        ]);
    }
    
} catch (Exception $e) {
    error_log("verify_and_route_ai error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>