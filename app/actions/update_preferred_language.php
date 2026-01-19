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
$user_companion_id = $_POST['user_companion_id'] ?? null;
$preferred_language = $_POST['preferred_language'] ?? null;

if (!$user_companion_id || !$preferred_language) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required parameters'
    ]);
    exit;
}

// Validate language
$valid_languages = ['th', 'en', 'cn', 'jp', 'kr'];
if (!in_array($preferred_language, $valid_languages)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid language'
    ]);
    exit;
}

try {
    // ตรวจสอบว่า user_companion นี้เป็นของ user ที่ login อยู่หรือไม่
    $stmt = $conn->prepare("
        SELECT user_companion_id 
        FROM user_ai_companions 
        WHERE user_companion_id = ? 
        AND user_id = ? 
        AND status = '1' 
        AND del = 0
    ");
    $stmt->bind_param("ii", $user_companion_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Unauthorized access'
        ]);
        exit;
    }
    $stmt->close();

    // อัพเดทภาษา
    $stmt = $conn->prepare("
        UPDATE user_ai_companions 
        SET preferred_language = ?
        WHERE user_companion_id = ?
    ");
    $stmt->bind_param("si", $preferred_language, $user_companion_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Language preference updated successfully',
            'preferred_language' => $preferred_language
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update language preference'
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