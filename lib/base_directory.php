<?php
// ตรวจ protocol แบบปลอดภัย
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$scriptDir = isset($_SERVER['SCRIPT_NAME']) ? dirname($_SERVER['SCRIPT_NAME']) : '/';

// สร้าง base path
$basePath = sprintf('%s://%s%s', $scheme, $host, $scriptDir);
$urlParts = parse_url($basePath);

$path = isset($urlParts['path']) ? $urlParts['path'] : '/';
$port = isset($urlParts['port']) ? ':' . $urlParts['port'] : '';

// ตั้งค่าตาม scheme
if ($scheme === 'http') {
    $fixedPath = '/perfume/app/admin/';
    $newPath = '/perfume/';
} else {
    $fixedPath = '/app/admin/';
    $newPath = '/';
}

// กำหนด path สำหรับใช้งาน
$base_Path = $scheme . '://' . $host . $port . $newPath;
$base_PathAdmin = $scheme . '://' . $host . $port . $fixedPath;

// ตั้งค่าให้เป็น global
$GLOBALS['new_path'] = $newPath;
$GLOBALS['base_path'] = $base_Path;
$GLOBALS['base_path_admin'] = $base_PathAdmin;
$GLOBALS['path_admin'] = $fixedPath;
$GLOBALS['isFile'] = '.php';
?>
