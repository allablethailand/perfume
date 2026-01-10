<?php

@session_start();
require __DIR__ . '/../vendor/autoload.php';
use Dotenv\Dotenv;

// โหลด .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// ตรวจ protocol แบบปลอดภัย
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
$protocol = $isHttps ? 'https' : 'http';

// เชื่อมต่อ DB ตาม protocol
if ($protocol === 'http') {
    $host = $_ENV['DB_HOST_HTTP'];
    $username = $_ENV['DB_USER_HTTP'];
    $password = $_ENV['DB_PASSWORD_HTTP'];
    $database = $_ENV['DB_NAME_HTTP'];
} else {
    $host = $_ENV['DB_HOST_HTTPS'];
    $username = $_ENV['DB_USER_HTTPS'];
    $password = $_ENV['DB_PASSWORD_HTTPS'];
    $database = $_ENV['DB_NAME_HTTPS'];
}

$conn = new mysqli($host, $username, $password, $database);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($isHttps) {
    // ลบ path /perfume และลิงก์ localhost ทิ้ง
    ob_start(function ($buffer) {
        $buffer = str_replace('http://localhost/perfume/', '', $buffer);
        // $buffer = str_replace('/perfume', '', $buffer);()
        return $buffer;
    });
}
?>
