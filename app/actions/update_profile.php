<?php
header('Content-Type: application/json');
require '../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

require_once(__DIR__ . '/../../lib/connect.php');

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

global $conn;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    exit();
}

// ตรวจสอบ JWT
$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (empty($authHeader)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No authorization token provided'
    ]);
    exit();
}

$jwt = str_replace('Bearer ', '', $authHeader);

try {
    $secret_key = $_ENV['JWT_SECRET_KEY'];
    $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
    $user_id = $decoded->data->user_id;

    // รับข้อมูลจากฟอร์ม
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';

    // Validate input
    if (empty($first_name) || empty($last_name)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'First name and last name are required'
        ]);
        exit();
    }

    // Update user profile
    $sql = "UPDATE mb_user SET 
            first_name = ?,
            last_name = ?,
            phone_number = ?,
            date_update = NOW()
            WHERE user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $first_name, $last_name, $phone_number, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Profile updated successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update profile: ' . $stmt->error
        ]);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid token: ' . $e->getMessage()
    ]);
}

$conn->close();
?>