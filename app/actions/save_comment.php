<?php
require '../../vendor/autoload.php';
require_once('../../lib/connect.php');

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header("Content-Type: application/json");

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
$jwt = str_replace('Bearer ', '', $authHeader);

if (!$jwt) {
    echo json_encode(['status' => 'error', 'message' => 'Missing token']);
    exit;
}

try {
    $secret_key = $_ENV['JWT_SECRET_KEY'];
    $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
    $user_id = $decoded->data->user_id;
    $data = json_decode(file_get_contents("php://input"), true);
    $comment = $data['comment'] ?? '';
    $page_url = $data['page_url'] ?? '';

    if (!$comment || !$page_url) {
        echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบ']);
        exit;
    }

    $sqlUser = "SELECT first_name, last_name, email FROM mb_user WHERE user_id = ?";
    $stmtUser = $conn->prepare($sqlUser);
    $stmtUser->bind_param("i", $user_id);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();
    $userData = $resultUser->fetch_assoc();

    $full_name = $userData['first_name'] . ' ' . $userData['last_name'];
    $email = $userData['email'];

    $sqlInsert = "INSERT INTO mb_comments (user_id, full_name, email, comment, page_url) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sqlInsert);
    $stmt->bind_param("issss", $user_id, $full_name, $email, $comment, $page_url);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถบันทึกได้']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Token ไม่ถูกต้อง']);
}
