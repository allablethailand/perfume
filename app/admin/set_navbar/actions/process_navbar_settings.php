<?php
// actions/process_navbar_settings.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');

// ** ต้องเปลี่ยนพาธให้ถูกต้องตามโครงสร้างไฟล์ของคุณ **
require_once(__DIR__ . '/../../../../lib/base_directory.php');
require_once(__DIR__ . '/../../../../lib/connect.php'); 
global $conn;

$response = ['status' => 'error', 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_navbar_settings') {
    
    // ดึงค่าที่ส่งมาจากฟอร์ม
    $settings_map = [
        'navbar_bg_color'       => $_POST['navbar_bg_color'] ?? '#ff9900',
        'navbar_text_color'     => $_POST['navbar_text_color'] ?? '#ffffff',
        'news_ticker_display'   => $_POST['news_ticker_display'] ?? '0',
        'news_ticker_bg_color'  => $_POST['news_ticker_bg_color'] ?? '#ffffffff',
        'news_ticker_text_color'=> $_POST['news_ticker_text_color'] ?? '#555',
        'news_ticker_title_color'=> $_POST['news_ticker_title_color'] ?? '#ff9900',
    ];

    $conn->begin_transaction();
    $update_success = true;

    try {
        $stmt = $conn->prepare("UPDATE dn_settings SET setting_value = ? WHERE setting_key = ?");

        foreach ($settings_map as $key => $value) {
            // Bind Parameters: string, string
            $stmt->bind_param("ss", $value, $key);

            if (!$stmt->execute()) {
                $update_success = false;
                $response['message'] = 'Failed to update setting: ' . $key . ' Error: ' . $stmt->error;
                break;
            }
        }
        
        $stmt->close();

        if ($update_success) {
            $conn->commit();
            $response['status'] = 'success';
            $response['message'] = 'บันทึกการตั้งค่า Navbar และแถบข่าวเรียบร้อยแล้ว ✅';
        } else {
            $conn->rollback();
        }

    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
}

echo json_encode($response);
exit;
?>