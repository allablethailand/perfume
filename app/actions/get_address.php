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
$address_id = $_GET['address_id'] ?? null;

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

    // ดึงข้อมูลที่อยู่เดียว
    $sql = "SELECT * FROM user_addresses 
            WHERE address_id = ? AND user_id = ? AND del = 0";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $address_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'status' => 'success',
            'data' => $row
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Address not found'
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