<?php

// app/actions/get_user_ai_avatar.php
require_once('../../lib/connect.php');
header('Content-Type: application/json');

// ตรวจสอบ JWT token
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

// Verify JWT and get user_id
require_once('../../vendor/autoload.php');
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

try {
    $secret_key = $_ENV['JWT_SECRET_KEY'];
    $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
    
    // ตรวจสอบว่า token หมดอายุหรือไม่
    if (time() > $decoded->exp) {
        echo json_encode(['status' => 'error', 'message' => 'Token has expired']);
        exit;
    }
    
    // ดึงข้อมูลจาก decoded token ตามโครงสร้างที่ถูกต้อง
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

// ตรวจสอบว่าเป็น role 5 (user) หรือไม่
if ($role_id != 5) {
    echo json_encode(['status' => 'error', 'message' => 'Not a regular user']);
    exit;
}

// Query เพื่อหา AI avatar ของ user
// 1. หา orders ที่ complete ของ user
// 2. หา order_items ของ orders นั้น
// 3. หา AI companion ที่เชื่อมกับ product_id
// 4. เอา AI avatar ตัวแรกที่เจอ (หรือจะเอาตัวล่าสุดก็ได้)

$sql = "SELECT DISTINCT ac.ai_avatar_url, ac.ai_name_th, ac.ai_name_en, ac.ai_code, o.date_created
        FROM orders o
        INNER JOIN order_items oi ON o.order_id = oi.order_id
        INNER JOIN ai_companions ac ON oi.product_id = ac.product_id
        WHERE o.user_id = ?
        AND o.order_status = 'confirmed'
        AND o.del = 0
        AND ac.status = 1
        AND ac.del = 0
        ORDER BY o.date_created DESC
        LIMIT 1";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'SQL Prepare failed: ' . $conn->error // ตัวนี้จะบอกว่า SQL ผิดตรงไหน
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
        'ai_code' => $ai_data['ai_code']
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