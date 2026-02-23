<?php

@session_start();
require __DIR__ . '/../vendor/autoload.php';
use Dotenv\Dotenv;

// โหลด .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// ตรวจว่ารันผ่าน CLI (cron) หรือ web
$isCLI = (php_sapi_name() === 'cli');

if ($isCLI) {
    // รันผ่าน cron/terminal → ใช้ HTTPS credentials (production)
    $isHttps = true;
} else {
    // รันผ่าน web → ตรวจ protocol ตามปกติ
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
               || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
}

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

if ($isHttps && !$isCLI) {
    ob_start(function ($buffer) {
        $buffer = str_replace('http://localhost/perfume/', '', $buffer);
        return $buffer;
    });
}
?>