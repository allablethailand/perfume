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
$address_id = $_POST['address_id'] ?? null;

if (!$address_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Address ID is required'
    ]);
    exit();
}

try {
    $secret_key = $_ENV['JWT_SECRET_KEY'];
    $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
    $user_id = $decoded->data->user_id;

    // Soft delete - set del = 1
    $sql = "UPDATE user_addresses SET del = 1, date_updated = NOW() 
            WHERE address_id = ? AND user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $address_id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Address deleted successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to delete address'
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