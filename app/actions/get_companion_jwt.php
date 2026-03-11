<?php
/**
 * get_companion_jwt.php
 * Auto-login: สร้าง JWT จาก user_companion_id หรือ ai_code
 * ใช้สำหรับ guest ที่ผ่านการสร้าง companion แล้ว ให้ถือว่า login แล้ว
 */
header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');

require '../../vendor/autoload.php';
use Firebase\JWT\JWT;
use Dotenv\Dotenv;
require_once(__DIR__ . '/../../lib/connect.php');

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
    exit;
}

$ai_code           = trim($_GET['ai_code'] ?? '');
$user_companion_id = intval($_GET['user_companion_id'] ?? 0);

if (empty($ai_code) && $user_companion_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

// ดึง user_id จาก user_companion ที่ผูกกับ ai_code หรือ companion_id
if ($user_companion_id > 0) {
    $sql = "SELECT uc.user_companion_id, uc.user_id, uc.preferred_language,
                   u.first_name, u.last_name, u.email, u.phone_number,
                   u.login_method, r.role_id
            FROM user_ai_companions uc
            JOIN mb_user u ON u.user_id = uc.user_id
            LEFT JOIN acc_user_roles r ON r.user_id = u.user_id
            WHERE uc.user_companion_id = ? AND uc.del = 0 AND u.del = 0
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_companion_id);
} else {
    // ค้นหาจาก ai_code → ai_companions → user_companions → mb_user
    $sql = "SELECT uc.user_companion_id, uc.user_id, uc.preferred_language,
                   u.first_name, u.last_name, u.email, u.phone_number,
                   u.login_method, r.role_id
            FROM ai_companions ac
            JOIN user_ai_companions uc ON uc.ai_id = ac.ai_id
            JOIN mb_user u ON u.user_id = uc.user_id
            LEFT JOIN acc_user_roles r ON r.user_id = u.user_id
            WHERE ac.ai_code = ? AND uc.del = 0 AND u.del = 0
            ORDER BY uc.user_companion_id DESC
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $ai_code);
}

if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'DB prepare failed']);
    exit;
}

$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['status' => 'error', 'message' => 'Companion not found']);
    exit;
}

// สร้าง JWT เหมือนกับ check_login.php
$secret_key = $_ENV['JWT_SECRET_KEY'];
$payload = [
    'iss'  => '',
    'iat'  => time(),
    'exp'  => time() + (60 * 60 * 24 * 7), // 7 วัน (longer for companion users)
    'data' => [
        'user_id'      => $row['user_id'],
        'role_id'      => $row['role_id'] ?? 3,
        'first_name'   => $row['first_name'],
        'last_name'    => $row['last_name'],
        'email'        => $row['email'],
        'phone_number' => $row['phone_number'],
        'login_method' => $row['login_method'],
    ]
];

$jwt = JWT::encode($payload, $secret_key, 'HS256');

echo json_encode([
    'status'             => 'success',
    'jwt'                => $jwt,
    'user_id'            => $row['user_id'],
    'user_companion_id'  => $row['user_companion_id'],
    'preferred_language' => $row['preferred_language'] ?? 'th',
]);