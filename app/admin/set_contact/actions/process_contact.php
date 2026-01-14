<?php
// actions/process_contact.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');
require_once(__DIR__ . '/../../../../lib/base_directory.php');
require_once(__DIR__ . '/../../../../lib/connect.php');
require_once(__DIR__ . '/../../../../inc/getFunctions.php');

global $conn;
global $base_path; // $base_path ควรเป็น Full URL เช่น https://www.perfume.com

$response = ['status' => 'error', 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_contact') {
    $contact_id = $_POST['contact_id'] ?? 0;

    if ($contact_id != 1) { // เราจัดการแค่ Contact ชุดเดียว (ID 1)
        $response['message'] = 'Invalid Contact ID.';
        echo json_encode($response);
        exit;
    }

    // ดึงข้อมูลจาก POST
    $company_name = $_POST['company_name'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $hours_weekday = $_POST['hours_weekday'] ?? '';
    $hours_saturday = $_POST['hours_saturday'] ?? '';
    $link_image_url = $_POST['link_image_url'] ?? NULL;
    $map_iframe_url = $_POST['map_iframe_url'] ?? NULL;
    // ดึง path รูปภาพปัจจุบันจาก hidden field ที่ส่งมาจาก Form ซึ่งเป็น Full URL
    $current_link_image_full_url = $_POST['current_link_image_path'] ?? NULL; 

    // กำหนด Path สำหรับเก็บรูปภาพโลโก้บนเซิร์ฟเวอร์ (Physical Path)
    // ควรใช้ $_SERVER['DOCUMENT_ROOT'] เพื่อให้เป็น Absolute Path ที่ถูกต้อง
    $upload_dir_physical = $_SERVER['DOCUMENT_ROOT'] . '/public/img/'; 
    
    // ตรวจสอบและสร้างโฟลเดอร์ถ้ายังไม่มี
    if (!is_dir($upload_dir_physical)) {
        if (!mkdir($upload_dir_physical, 0775, true)) { // ใช้ 0775 หรือตามเหมาะสม
            $response['message'] = 'Failed to create upload directory.';
            echo json_encode($response);
            exit;
        }
    }

    // กำหนดค่าเริ่มต้นของ Path ที่จะเก็บใน DB เป็น Full URL เดิม
    $link_image_path_for_db = $current_link_image_full_url; 

    // จัดการการอัปโหลดรูปภาพ link_image
    if (isset($_FILES['link_image']) && $_FILES['link_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['link_image']['tmp_name'];
        $file_extension = pathinfo($_FILES['link_image']['name'], PATHINFO_EXTENSION);
        $file_name_new = uniqid('contact_link_') . '.' . $file_extension; // สร้างชื่อไฟล์ใหม่ที่ไม่ซ้ำกัน
        $destination_physical = $upload_dir_physical . $file_name_new;

        // ลบรูปภาพเก่าออกก่อน ถ้ามีและไม่ใช่รูปภาพ default
        // แปลง Full URL ของรูปภาพเก่าให้เป็น Physical Path ก่อนลบ
        // ตัวอย่าง: https://www.perfume.com/public/img/old.jpg
        // ให้เป็น: C:/xampp/htdocs/origami_website/perfume/public/img/old.jpg
        $old_local_file_path = str_replace($base_path, $_SERVER['DOCUMENT_ROOT'], $current_link_image_full_url);
        
        // ตรวจสอบว่าไฟล์เก่ามีอยู่จริงและไม่ใช่ไฟล์ default (ถ้ามี) ก่อนลบ
        // ในตัวอย่างของคุณ ../public/img/photo_2025-07-01_10-43-53.jpg
        // ถ้าต้องการไม่ให้ลบไฟล์ default นี้ออกไป ให้ตรวจสอบชื่อไฟล์ด้วย
        // ตัวอย่างการตรวจสอบ: ถ้าชื่อไฟล์มี 'photo_2025-07-01_10-43-53.jpg' ไม่ต้องลบ
        if ($current_link_image_full_url && strpos($current_link_image_full_url, 'photo_2025-07-01_10-43-53.jpg') === false) { // แก้ไขตรงนี้ถ้ามีชื่อไฟล์ default ที่ต้องการรักษา
            if (file_exists($old_local_file_path) && is_file($old_local_file_path)) {
                if (!unlink($old_local_file_path)) {
                    error_log("Failed to delete old contact link image file: " . $old_local_file_path);
                }
            } else {
                 error_log("Old contact link image file not found or is not a file: " . $old_local_file_path);
            }
        }

        if (move_uploaded_file($file_tmp_name, $destination_physical)) {
            // ** บันทึกเป็น Full URL ใน DB ตามที่ต้องการ **
            $link_image_path_for_db = $base_path . '/public/img/' . $file_name_new; 
        } else {
            $response['message'] = 'Failed to upload new link image.';
            echo json_encode($response);
            exit;
        }
    } else if (isset($_FILES['link_image']) && $_FILES['link_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $response['message'] = 'File upload error: ' . $_FILES['link_image']['error'];
        echo json_encode($response);
        exit;
    }

    try {
        $stmt = $conn->prepare("UPDATE contact_settings SET 
            company_name = ?,
            address = ?,
            phone = ?,
            email = ?,
            hours_weekday = ?,
            hours_saturday = ?,
            link_image_path = ?,
            link_image_url = ?,
            map_iframe_url = ?,
            updated_at = NOW()
            WHERE id = ?");
        
        $stmt->bind_param("sssssssssi",
            $company_name,
            $address,
            $phone,
            $email,
            $hours_weekday,
            $hours_saturday,
            $link_image_path_for_db, // ใช้ Full URL ที่เตรียมไว้
            $link_image_url,
            $map_iframe_url,
            $contact_id
        );

        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Contact settings updated successfully.';
            $response['new_link_image_path'] = $link_image_path_for_db; // ส่ง path ใหม่กลับไปเผื่อใช้
        } else {
            throw new Exception('Failed to update contact settings in database: ' . $stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
}

echo json_encode($response);
exit;
?>