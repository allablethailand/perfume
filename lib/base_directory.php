<?php
// ตรวจ protocol
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

// 🔥 ส่วนที่แก้ไข: ตรวจจับ base path จริงจาก SCRIPT_NAME
$scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';

// ดึง path จริงจาก script ที่กำลังรัน
// ตัวอย่าง: /perfume/app/admin/dashboard.php → /perfume/
// หรือ /app/admin/dashboard.php → /
preg_match('#^(.*?)/app/admin/#', $scriptName, $matches);
$detectedBasePath = isset($matches[1]) ? $matches[1] : '';

// ถ้าไม่เจอ ให้ลองดูว่าอยู่ที่ root หรือไม่
if (empty($detectedBasePath) && strpos($scriptName, '/app/admin/') === 0) {
    $detectedBasePath = '';
}

// กำหนด path
$newPath = $detectedBasePath . '/';
$fixedPath = $detectedBasePath . '/app/admin/';

// Port (ถ้ามี)
$port = '';
if (isset($_SERVER['SERVER_PORT'])) {
    $serverPort = $_SERVER['SERVER_PORT'];
    if (($scheme === 'http' && $serverPort != 80) || ($scheme === 'https' && $serverPort != 443)) {
        $port = ':' . $serverPort;
    }
}

// สร้าง URL เต็ม
$base_Path = $scheme . '://' . $host . $port . $newPath;
$base_PathAdmin = $scheme . '://' . $host . $port . $fixedPath;

// ตั้งค่า global variables
$GLOBALS['new_path'] = $newPath;
$GLOBALS['base_path'] = $base_Path;
$GLOBALS['base_path_admin'] = $base_PathAdmin;
$GLOBALS['path_admin'] = $fixedPath;
$GLOBALS['isFile'] = '.php';
?>