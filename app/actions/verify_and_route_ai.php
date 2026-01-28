<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../../lib/connect.php');
require_once(__DIR__ . '/../../lib/jwt_helper.php');

global $conn;

/* =======================
   CORS
======================= */
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

/* =======================
   JWT Verify
======================= */
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

/* =======================
   Session & Input
======================= */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = requireAuth();
$ai_code = strtoupper(trim($_POST['ai_code'] ?? ''));

if (empty($ai_code) || !preg_match('/^AI-[A-Z0-9]{8,}$/', $ai_code)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid AI code format']);
    exit();
}

try {

    /* =======================
       STEP 1: ตรวจสอบ AI
    ======================= */
    $stmt = $conn->prepare("
        SELECT ai_id, ai_code, ai_name_th, ai_name_en
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

    $ai_id = (int)$ai['ai_id'];

    /* =======================
       STEP 2: ตรวจ ownership
       1 AI = 1 user เท่านั้น
    ======================= */
    $stmt = $conn->prepare("
        SELECT 
            user_companion_id,
            user_id,
            preferred_language,
            setup_completed
        FROM user_ai_companions
        WHERE ai_id = ? AND del = 0
        LIMIT 1
    ");
    $stmt->bind_param("i", $ai_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $companion = $result->fetch_assoc();
    $stmt->close();

    /* =======================
       CASE A: มี owner แล้ว
    ======================= */
    if ($companion) {

        // มี owner แต่ไม่ใช่ user คนนี้
        if ((int)$companion['user_id'] !== (int)$user_id) {
            http_response_code(403);
            echo json_encode([
                'status' => 'error',
                'message' => 'This AI Companion already has an owner'
            ]);
            exit();
        }

        // เป็น owner คนเดิม → ใช้ข้อมูลเดิม
        if ((int)$companion['setup_completed'] === 0) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Continue AI setup',
                'redirect_url' => '?ai_activation&companion_id=' .
                    $companion['user_companion_id'] .
                    '&lang=' . $companion['preferred_language'],
                'data' => [
                    'ai_id' => $ai_id,
                    'user_companion_id' => $companion['user_companion_id'],
                    'setup_completed' => 0
                ]
            ]);
            exit();
        }

        // setup เสร็จแล้ว → ไปหน้า chat
        $stmt = $conn->prepare("
            UPDATE user_ai_companions
            SET last_active_at = NOW()
            WHERE user_companion_id = ?
        ");
        $stmt->bind_param("i", $companion['user_companion_id']);
        $stmt->execute();
        $stmt->close();

        echo json_encode([
            'status' => 'success',
            'message' => 'Welcome back!',
            'redirect_url' => '?ai_chat_3d&lang=' . $companion['preferred_language'],
            'data' => [
                'ai_id' => $ai_id,
                'user_companion_id' => $companion['user_companion_id'],
                'setup_completed' => 1
            ]
        ]);
        exit();
    }

    /* =======================
       CASE B: ยังไม่มี owner
       → สร้างใหม่ (กรณีเดียว)
    ======================= */
    $conn->begin_transaction();

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

    echo json_encode([
        'status' => 'success',
        'message' => 'AI Companion activated',
        'redirect_url' => '?ai_activation&companion_id=' .
            $user_companion_id .
            '&lang=' . $default_lang,
        'data' => [
            'ai_id' => $ai_id,
            'user_companion_id' => $user_companion_id,
            'setup_completed' => 0
        ]
    ]);
    exit();

} catch (Exception $e) {

    if ($conn->errno) {
        $conn->rollback();
    }

    error_log('verify_and_route_ai error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error'
    ]);
}
