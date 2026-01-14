<?php
// perfume/router.php

// 1. นำเข้าไฟล์ที่จำเป็น (เช่น การเชื่อมต่อฐานข้อมูล)
require_once('lib/connect.php');
global $conn; // ตรวจสอบให้แน่ใจว่า $conn ถูกกำหนดค่าใน lib/connect.php

// 2. ดึง URL Path ที่ร้องขอ
$url = $_SERVER['REQUEST_URI'];
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); // ดึง base path ของ script (เช่น /perfume)

// ลบ base path ออกจาก URL และลบ query string
$path = parse_url($url, PHP_URL_PATH);
if (strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}
$path = trim($path, '/'); // ตัวอย่าง: "shop" หรือ "service" หรือ "" สำหรับหน้าหลัก

// 3. การจัดการ Routing
if (empty($path) || $path === 'index') {
    // ถ้า path ว่างเปล่า หรือ เป็น 'index' (สำหรับหน้าหลัก)
    // ให้เรียกใช้ index.php เดิม
    require __DIR__ . '/index.php';
} else {
    // สำหรับหน้าอื่นๆ ที่อยู่ใน app/
    
    // สร้างชื่อไฟล์ที่คาดว่าจะถูกเรียกใช้ (เช่น "shop.php")
    $targetFile = __DIR__ . '/app/' . $path . '.php';

    // ตรวจสอบว่าไฟล์มีอยู่จริงหรือไม่
    if (file_exists($targetFile)) {
        // ถ้ามีไฟล์อยู่ ให้ require ไฟล์นั้น
        require $targetFile;
    } else {
        // ถ้าไม่มีไฟล์อยู่ (404 Not Found)
        http_response_code(404);
        // คุณอาจจะต้องสร้างไฟล์ 404.php หรือแสดงหน้า Error
        echo "404 Not Found";
        // หรือส่งไปยังหน้าหลัก: header('Location: ' . $basePath);
        // require __DIR__ . '/404.php';
    }
}
?>