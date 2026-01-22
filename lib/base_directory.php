<?php
/**
 * Base Directory Configuration
 * รองรับทั้ง Frontend และ Admin Panel
 * 
 * Frontend: https://www.trandar.com/perfume/
 * Admin: https://www.trandar.com/perfume/app/admin/
 */

// ตรวจ protocol
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

// ดึง script path
$scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';

// 🔥 ตรวจจับ base path อัตโนมัติ
$detectedBasePath = '';

// กรณีที่ 1: Admin Panel
if (preg_match('#^(.*?)/app/admin/#', $scriptName, $matches)) {
    $detectedBasePath = isset($matches[1]) ? $matches[1] : '';
}
// กรณีที่ 2: Frontend (views, lib, etc.)
elseif (preg_match('#^(.*?)/(views|lib|app|index\.php)#', $scriptName, $matches)) {
    $detectedBasePath = isset($matches[1]) ? $matches[1] : '';
}
// กรณีที่ 3: Root level
else {
    // ถ้าไม่เจอ pattern ใด ให้ใช้ค่า default
    $detectedBasePath = '/perfume';
}

// ตรวจสอบว่าเป็น localhost หรือ production
if ($host === 'localhost' || strpos($host, '127.0.0.1') !== false) {
    // Local environment
    if (empty($detectedBasePath) || $detectedBasePath === '/') {
        $detectedBasePath = '/origami_website/perfume';
    }
} else {
    // Production environment
    if (empty($detectedBasePath) || $detectedBasePath === '/') {
        $detectedBasePath = '/perfume';
    }
}

// กำหนด paths
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

// เพิ่มตัวแปรเพื่อ Debug (สามารถลบออกได้เมื่อแน่ใจว่าใช้งานได้)
if (isset($_GET['debug_path'])) {
    echo "<pre>";
    echo "Script Name: " . $scriptName . "\n";
    echo "Detected Base Path: " . $detectedBasePath . "\n";
    echo "New Path: " . $newPath . "\n";
    echo "Base Path: " . $base_Path . "\n";
    echo "Base Path Admin: " . $base_PathAdmin . "\n";
    echo "</pre>";
}
?>