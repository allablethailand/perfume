<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../../lib/connect.php');
require_once(__DIR__ . '/../../lib/jwt_helper.php');

global $conn;

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

$user_id = $decoded['user_id'];
$companion_id = intval($_POST['companion_id'] ?? 0);
$preferred_language = $_POST['preferred_language'] ?? '';

// Validate
if (!$companion_id || !$preferred_language) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit();
}

$allowed_langs = ['th', 'en', 'cn', 'jp', 'kr'];
if (!in_array($preferred_language, $allowed_langs)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid language']);
    exit();
}

try {
    // อัพเดทภาษา
    $stmt = $conn->prepare("
        UPDATE user_ai_companions 
        SET preferred_language = ?,
            last_active_at = NOW()
        WHERE user_companion_id = ? 
          AND user_id = ?
          AND del = 0
    ");
    $stmt->bind_param("sii", $preferred_language, $companion_id, $user_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Language updated',
            'companion_id' => $companion_id,
            'preferred_language' => $preferred_language
        ]);
    } else {
        throw new Exception('Failed to update language');
    }
    
} catch (Exception $e) {
    error_log("update_companion_language error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>