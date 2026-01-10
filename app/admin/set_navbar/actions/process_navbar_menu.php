<?php
// actions/process_navbar_menu.php

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_menu_order_status') {
    
    $menu_data_json = $_POST['menu_data'] ?? '[]';
    $menu_updates = json_decode($menu_data_json, true);

    if (empty($menu_updates) || !is_array($menu_updates)) {
        $response['message'] = 'ไม่พบข้อมูลเมนูที่ต้องการอัปเดต.';
        echo json_encode($response);
        exit;
    }

    $conn->begin_transaction();
    $update_success = true;
    $count = 0;

    try {
        $stmt = $conn->prepare("UPDATE dn_navbar_menu SET display_order = ?, is_active = ? WHERE menu_id = ?");

        foreach ($menu_updates as $item) {
            $menu_id = $item['id'] ?? null;
            $order = $item['order'] ?? 99;
            $is_active = $item['is_active'] ?? 0;
            
            if ($menu_id === null) continue;

            // Bind Parameters: integer (order), integer (is_active), integer (menu_id)
            $stmt->bind_param("iii", $order, $is_active, $menu_id);

            if (!$stmt->execute()) {
                $update_success = false;
                $response['message'] = 'Failed to update menu ID: ' . $menu_id . ' Error: ' . $stmt->error;
                break;
            }
            $count++;
        }
        
        $stmt->close();

        if ($update_success) {
            $conn->commit();
            $response['status'] = 'success';
            $response['message'] = 'อัปเดตเมนู ' . $count . ' รายการเรียบร้อยแล้ว! 🔄';
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