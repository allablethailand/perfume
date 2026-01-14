<?php
// actions/process_layout.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');
// ** ต้องเปลี่ยนพาธให้ถูกต้องตามโครงสร้างไฟล์ของคุณ **
require_once(__DIR__ . '/../../../../lib/base_directory.php');
require_once(__DIR__ . '/../../../../lib/connect.php'); 
// global $conn; ต้องแน่ใจว่าตัวแปร $conn จาก connect.php ใช้งานได้
global $conn;

$response = ['status' => 'error', 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_layout') {
    $layout_data_json_string = $_POST['layout_data_json'] ?? '[]';

    $layout_data_array = json_decode($layout_data_json_string, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $response['message'] = 'Invalid Layout Data JSON: ' . json_last_error_msg();
        echo json_encode($response);
        exit;
    }
    
    // เริ่ม Transaction เพื่อให้การอัปเดตทั้งหมดสำเร็จหรือล้มเหลวทั้งหมด
    $conn->begin_transaction();
    $update_success = true;

    try {
        $stmt = $conn->prepare("UPDATE homepage_layout SET
            display_order = ?,
            background_color = ?,
            is_full_width = ?,
            is_active = ?,
            last_updated = NOW()
            WHERE block_name = ?");

        foreach ($layout_data_array as $block) {
            // ตรวจสอบความถูกต้องของข้อมูล (อย่างน้อย)
            if (!isset($block['block_name'], $block['display_order'], $block['background_color'], $block['is_full_width'], $block['is_active'])) {
                $update_success = false;
                $response['message'] = 'Missing data fields for one block.';
                break;
            }

            // Bind Parameters: integer, string, integer, integer, string
            // $block['is_full_width'] เป็นค่าที่ fix ไว้แล้ว
            $stmt->bind_param("isiss", 
                $block['display_order'], 
                $block['background_color'], 
                $block['is_full_width'], 
                $block['is_active'],
                $block['block_name']
            );

            if (!$stmt->execute()) {
                $update_success = false;
                $response['message'] = 'Failed to update block: ' . $block['block_name'] . ' Error: ' . $stmt->error;
                break;
            }
        }
        
        $stmt->close();

        if ($update_success) {
            $conn->commit();
            $response['status'] = 'success';
            $response['message'] = 'บันทึกการตั้งค่าเลย์เอาต์หน้าหลักเรียบร้อยแล้ว ✅';
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