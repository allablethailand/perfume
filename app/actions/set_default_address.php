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

    $conn->begin_transaction();

    try {
        // Unset all default addresses for this user
        $sql_unset = "UPDATE user_addresses SET is_default = 0 WHERE user_id = ?";
        $stmt_unset = $conn->prepare($sql_unset);
        $stmt_unset->bind_param("i", $user_id);
        $stmt_unset->execute();
        $stmt_unset->close();

        // Set the selected address as default
        $sql_set = "UPDATE user_addresses SET is_default = 1 
                    WHERE address_id = ? AND user_id = ?";
        $stmt_set = $conn->prepare($sql_set);
        $stmt_set->bind_param("ii", $address_id, $user_id);
        
        if ($stmt_set->execute()) {
            $conn->commit();
            echo json_encode([
                'status' => 'success',
                'message' => 'Default address updated'
            ]);
        } else {
            throw new Exception($stmt_set->error);
        }

        $stmt_set->close();

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update default address: ' . $e->getMessage()
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid token: ' . $e->getMessage()
    ]);
}

$conn->close();
?>