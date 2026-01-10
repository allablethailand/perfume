<?php
// actions/process_logo.php

// สำหรับ Debugging เท่านั้น: แสดง Error บนหน้าจอ
// ควรปิดเมื่อนำไปใช้จริงบน Production Server เพื่อความปลอดภัย
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');
require_once(__DIR__ . '/../../../../lib/base_directory.php');
require_once(__DIR__ . '/../../../../lib/connect.php');
require_once(__DIR__ . '/../../../../inc/getFunctions.php');

// ประกาศ global variable เพื่อให้เข้าถึงได้ (ถ้า connect.php และ base_directory.php กำหนดตัวแปรใน global scope)
global $conn;
global $base_path; // $base_path ควรเป็น URL root เช่น https://www.perfume.com

$response = ['status' => 'error', 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'edit_all_settings') {
        try {
            // Start transaction
            $conn->begin_transaction();

            // --- 1. จัดการการอัปเดตโลโก้หลักและโลโก้ใน Modal (จาก logo_settings) ---
            $logo_id = $_POST['logo_id'] ?? 0;
            $old_image_path_db = $_POST['old_image_path'] ?? ''; // Path เก่าของโลโก้หลัก (Full URL)
            $old_image_modal_path_db = $_POST['old_image_modal_path'] ?? ''; // Path เก่าของโลโก้ Modal (Full URL)

            if ($logo_id != 1) {
                throw new Exception('Invalid Logo ID. Logo must be ID 1.');
            }

            $upload_dir_physical = $_SERVER['DOCUMENT_ROOT'] . '/public/img/';

            if (!is_dir($upload_dir_physical)) {
                if (!mkdir($upload_dir_physical, 0775, true)) {
                    throw new Exception('Failed to create upload directory.');
                }
            }

            $image_path_for_db = $old_image_path_db;
            $image_modal_path_for_db = $old_image_modal_path_db;

            // จัดการอัปโหลดรูปภาพโลโก้หลัก
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file_tmp_name = $_FILES['image']['tmp_name'];
                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $file_name_new = uniqid('logo_') . '.' . $file_extension;
                $destination_physical = $upload_dir_physical . $file_name_new;

                // ลบรูปภาพเก่าออก (ถ้าไม่ใช่ default และมีอยู่จริง)
                $old_local_file_path = str_replace($base_path, $_SERVER['DOCUMENT_ROOT'], $old_image_path_db);
                if ($old_image_path_db && strpos($old_image_path_db, 'LOGOTRAND.png') === false) {
                    if (file_exists($old_local_file_path) && is_file($old_local_file_path)) {
                        unlink($old_local_file_path);
                    }
                }

                if (move_uploaded_file($file_tmp_name, $destination_physical)) {
                    $image_path_for_db =  '/public/img/' . $file_name_new;
                } else {
                    throw new Exception('Failed to upload new main logo image.');
                }
            }

            // จัดการอัปโหลดรูปภาพโลโก้ใน Modal
            if (isset($_FILES['image_modal']) && $_FILES['image_modal']['error'] === UPLOAD_ERR_OK) {
                $file_tmp_name_modal = $_FILES['image_modal']['tmp_name'];
                $file_extension_modal = pathinfo($_FILES['image_modal']['name'], PATHINFO_EXTENSION);
                $file_name_new_modal = uniqid('modal_logo_') . '.' . $file_extension_modal;
                $destination_physical_modal = $upload_dir_physical . $file_name_new_modal;

                // ลบรูปภาพเก่าออก (ถ้าไม่ใช่ default และมีอยู่จริง)
                $old_local_file_modal_path = str_replace($base_path, $_SERVER['DOCUMENT_ROOT'], $old_image_modal_path_db);
                if ($old_image_modal_path_db && strpos($old_image_modal_path_db, 'perfume.jpg') === false) {
                    if (file_exists($old_local_file_modal_path) && is_file($old_local_file_modal_path)) {
                        unlink($old_local_file_modal_path);
                    }
                }

                if (move_uploaded_file($file_tmp_name_modal, $destination_physical_modal)) {
                    $image_modal_path_for_db = $base_path . '/public/img/' . $file_name_new_modal;
                } else {
                    throw new Exception('Failed to upload new modal logo image.');
                }
            }

            // อัปเดต logo_settings
            $stmt = $conn->prepare("UPDATE logo_settings SET image_path = ?, image_modal_path = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssi", $image_path_for_db, $image_modal_path_for_db, $logo_id);
            if (!$stmt->execute()) {
                throw new Exception('Failed to update logo settings in database: ' . $conn->error);
            }
            $stmt->close();

            // --- 2. จัดการการอัปเดตข้อมูลติดต่อและ Social Media (จาก contact_settings) ---
            $contact_settings_id = $_POST['contact_settings_id'] ?? 0;
            $perfume_store_link = $_POST['perfume_store_link'] ?? '';
            $perfume_store_text = $_POST['perfume_store_text'] ?? '';
            $facebook_link = $_POST['facebook_link'] ?? '';
            $youtube_link = $_POST['youtube_link'] ?? '';
            $instagram_link = $_POST['instagram_link'] ?? '';
            $line_link = $_POST['line_link'] ?? '';
            $tiktok_link = $_POST['tiktok_link'] ?? '';

            // ตรวจสอบว่ามีการตั้งค่าเริ่มต้นในตาราง contact_settings หรือไม่
            $stmt_check_contact = $conn->prepare("SELECT id FROM contact_settings WHERE id = ?");
            $stmt_check_contact->bind_param("i", $contact_settings_id);
            $stmt_check_contact->execute();
            $result_check_contact = $stmt_check_contact->get_result();

            if ($result_check_contact->num_rows > 0) {
                // ถ้ามีอยู่แล้ว ให้อัปเดต
                $stmt_contact = $conn->prepare("UPDATE contact_settings SET 
                                                perfume_store_link = ?, 
                                                perfume_store_text = ?, 
                                                facebook_link = ?, 
                                                youtube_link = ?, 
                                                instagram_link = ?, 
                                                line_link = ?, 
                                                tiktok_link = ?, 
                                                updated_at = NOW() 
                                                WHERE id = ?");
                $stmt_contact->bind_param("sssssssi", 
                                          $perfume_store_link, 
                                          $perfume_store_text, 
                                          $facebook_link, 
                                          $youtube_link, 
                                          $instagram_link, 
                                          $line_link, 
                                          $tiktok_link, 
                                          $contact_settings_id);
            } else {
                // ถ้ายังไม่มี ให้ insert
                $stmt_contact = $conn->prepare("INSERT INTO contact_settings (id, perfume_store_link, perfume_store_text, facebook_link, youtube_link, instagram_link, line_link, tiktok_link) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt_contact->bind_param("isssssss",
                                          $contact_settings_id,
                                          $perfume_store_link,
                                          $perfume_store_text,
                                          $facebook_link,
                                          $youtube_link,
                                          $instagram_link,
                                          $line_link,
                                          $tiktok_link);
            }
            $stmt_check_contact->close();


            if (!$stmt_contact->execute()) {
                throw new Exception('Failed to update contact settings in database: ' . $conn->error);
            }
            $stmt_contact->close();


            // Commit transaction
            $conn->commit();

            $response['status'] = 'success';
            $response['message'] = 'All settings updated successfully.';

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $response['message'] = 'Operation failed: ' . $e->getMessage();
            error_log("Error in process_logo.php: " . $e->getMessage()); // Log error for debugging
        }
    }
}

echo json_encode($response);
exit;
?>