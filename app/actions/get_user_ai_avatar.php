<?php

require_once('../../lib/connect.php');
header('Content-Type: application/json');

$headers = getallheaders();
$jwt = null;

if (isset($headers['Authorization'])) {
    $jwt = str_replace('Bearer ', '', $headers['Authorization']);
} elseif (isset($headers['X-Auth-Token'])) {
    $jwt = $headers['X-Auth-Token'];
}

if (!$jwt) {
    echo json_encode(['status' => 'error', 'message' => 'No token provided']);
    exit;
}

require_once('../../vendor/autoload.php');
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

try {
    $secret_key = $_ENV['JWT_SECRET_KEY'];
    $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
    
    if (time() > $decoded->exp) {
        echo json_encode(['status' => 'error', 'message' => 'Token has expired']);
        exit;
    }
    
    $user_id = isset($decoded->data->user_id) ? intval($decoded->data->user_id) : null;
    $role_id = isset($decoded->data->role_id) ? intval($decoded->data->role_id) : null;

    if (!$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid token data']);
        exit;
    }

    if (!$role_id) {
        echo json_encode(['status' => 'error', 'message' => 'Role ID not found in token']);
        exit;
    }
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid token: ' . $e->getMessage()]);
    exit;
}

if ($role_id != 5) {
    echo json_encode(['status' => 'error', 'message' => 'Not a regular user']);
    exit;
}

// ✅ เช็คจากตาราง user_ai_companions ว่า user คนนี้มี AI companion หรือไม่
$sql = "SELECT uac.user_companion_id, uac.ai_id, uac.preferred_language, 
               ac.ai_avatar_url, ac.ai_name_th, ac.ai_name_en, ac.ai_code,
               uac.setup_completed_at
        FROM user_ai_companions uac
        INNER JOIN ai_companions ac ON uac.ai_id = ac.ai_id
        WHERE uac.user_id = ?
        AND uac.status = 1
        AND uac.del = 0
        AND ac.status = 1
        AND ac.del = 0
        ORDER BY uac.setup_completed_at DESC
        LIMIT 1";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database error'
    ]);
    exit;
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $ai_data = $result->fetch_assoc();
    echo json_encode([
        'status' => 'success',
        'has_ai' => true,
        'ai_avatar_url' => $ai_data['ai_avatar_url'],
        'ai_name_th' => $ai_data['ai_name_th'],
        'ai_name_en' => $ai_data['ai_name_en'],
        'ai_code' => $ai_data['ai_code'],
        'preferred_language' => $ai_data['preferred_language']
    ]);
} else {
    echo json_encode([
        'status' => 'success',
        'has_ai' => false
    ]);
}

$stmt->close();
$conn->close();
?>