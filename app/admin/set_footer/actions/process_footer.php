<?php
// actions/process_footer.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');
require_once(__DIR__ . '/../../../../lib/base_directory.php');
require_once(__DIR__ . '/../../../../lib/connect.php');
require_once(__DIR__ . '/../../../../inc/getFunctions.php');
global $conn;

$response = ['status' => 'error', 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_footer') {
    $footer_id = $_POST['footer_id'] ?? 0;

    if ($footer_id != 1) {
        $response['message'] = 'Invalid Footer ID.';
        echo json_encode($response);
        exit;
    }

    // ดึงข้อมูลจาก POST (ภาษาไทย)
    $bg_color = $_POST['bg_color'] ?? '#393939';
    $footer_top_title = $_POST['footer_top_title'] ?? '';
    $footer_top_subtitle = $_POST['footer_top_subtitle'] ?? '';
    $about_heading = $_POST['about_heading'] ?? '';
    $about_text = $_POST['about_text'] ?? '';
    $contact_heading = $_POST['contact_heading'] ?? '';
    $contact_address = $_POST['contact_address'] ?? '';
    $contact_phone = $_POST['contact_phone'] ?? '';
    $contact_email = $_POST['contact_email'] ?? '';
    $contact_hours_wk = $_POST['contact_hours_wk'] ?? '';
    $contact_hours_sat = $_POST['contact_hours_sat'] ?? '';
    $social_heading = $_POST['social_heading'] ?? '';
    $copyright_text = $_POST['copyright_text'] ?? '';

    // ดึงข้อมูลจาก POST (ภาษาอังกฤษ)
    $footer_top_title_en = $_POST['footer_top_title_en'] ?? '';
    $footer_top_subtitle_en = $_POST['footer_top_subtitle_en'] ?? '';
    $about_heading_en = $_POST['about_heading_en'] ?? '';
    $about_text_en = $_POST['about_text_en'] ?? '';
    $contact_heading_en = $_POST['contact_heading_en'] ?? '';
    $contact_address_en = $_POST['contact_address_en'] ?? '';
    $contact_hours_wk_en = $_POST['contact_hours_wk_en'] ?? '';
    $contact_hours_sat_en = $_POST['contact_hours_sat_en'] ?? '';
    $social_heading_en = $_POST['social_heading_en'] ?? '';

    // ดึงข้อมูลจาก POST (ภาษาจีน)
    $footer_top_title_cn = $_POST['footer_top_title_cn'] ?? '';
    $footer_top_subtitle_cn = $_POST['footer_top_subtitle_cn'] ?? '';
    $about_heading_cn = $_POST['about_heading_cn'] ?? '';
    $about_text_cn = $_POST['about_text_cn'] ?? '';
    $contact_heading_cn = $_POST['contact_heading_cn'] ?? '';
    $contact_address_cn = $_POST['contact_address_cn'] ?? '';
    $contact_hours_wk_cn = $_POST['contact_hours_wk_cn'] ?? '';
    $contact_hours_sat_cn = $_POST['contact_hours_sat_cn'] ?? '';
    $social_heading_cn = $_POST['social_heading_cn'] ?? '';
    
    // ดึงข้อมูลจาก POST (ภาษาญี่ปุ่น)
    $footer_top_title_jp = $_POST['footer_top_title_jp'] ?? '';
    $footer_top_subtitle_jp = $_POST['footer_top_subtitle_jp'] ?? '';
    $about_heading_jp = $_POST['about_heading_jp'] ?? '';
    $about_text_jp = $_POST['about_text_jp'] ?? '';
    $contact_heading_jp = $_POST['contact_heading_jp'] ?? '';
    $contact_address_jp = $_POST['contact_address_jp'] ?? '';
    $contact_hours_wk_jp = $_POST['contact_hours_wk_jp'] ?? '';
    $contact_hours_sat_jp = $_POST['contact_hours_sat_jp'] ?? '';
    $social_heading_jp = $_POST['social_heading_jp'] ?? '';

    // เพิ่ม kr
    $footer_top_title_kr = $_POST['footer_top_title_kr'] ?? '';
    $footer_top_subtitle_kr = $_POST['footer_top_subtitle_kr'] ?? '';
    $about_heading_kr = $_POST['about_heading_kr'] ?? '';
    $about_text_kr = $_POST['about_text_kr'] ?? '';
    $contact_heading_kr = $_POST['contact_heading_kr'] ?? '';
    $contact_address_kr = $_POST['contact_address_kr'] ?? '';
    $contact_hours_wk_kr = $_POST['contact_hours_wk_kr'] ?? '';
    $contact_hours_sat_kr = $_POST['contact_hours_sat_kr'] ?? '';
    $social_heading_kr = $_POST['social_heading_kr'] ?? '';
    
    // Social links มาในรูปแบบ JSON string
    $social_links_json_string = $_POST['social_links_json'] ?? '[]';

    $social_links_array = json_decode($social_links_json_string, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $response['message'] = 'Invalid Social Links JSON data: ' . json_last_error_msg();
        echo json_encode($response);
        exit;
    }
    $social_links_json_for_db = json_encode($social_links_array);

    try {
        $stmt = $conn->prepare("UPDATE footer_settings SET
            bg_color = ?,
            footer_top_title = ?,
            footer_top_subtitle = ?,
            about_heading = ?,
            about_text = ?,
            contact_heading = ?,
            contact_address = ?,
            contact_phone = ?,
            contact_email = ?,
            contact_hours_wk = ?,
            contact_hours_sat = ?,
            social_heading = ?,
            social_links_json = ?,
            copyright_text = ?,

            footer_top_title_en = ?,
            footer_top_subtitle_en = ?,
            about_heading_en = ?,
            about_text_en = ?,
            contact_heading_en = ?,
            contact_address_en = ?,
            contact_hours_wk_en = ?,
            contact_hours_sat_en = ?,
            social_heading_en = ?,

            footer_top_title_cn = ?,
            footer_top_subtitle_cn = ?,
            about_heading_cn = ?,
            about_text_cn = ?,
            contact_heading_cn = ?,
            contact_address_cn = ?,
            contact_hours_wk_cn = ?,
            contact_hours_sat_cn = ?,
            social_heading_cn = ?,

            footer_top_title_jp = ?,
            footer_top_subtitle_jp = ?,
            about_heading_jp = ?,
            about_text_jp = ?,
            contact_heading_jp = ?,
            contact_address_jp = ?,
            contact_hours_wk_jp = ?,
            contact_hours_sat_jp = ?,
            social_heading_jp = ?,
            
            -- เพิ่ม kr ที่นี่
            footer_top_title_kr = ?,
            footer_top_subtitle_kr = ?,
            about_heading_kr = ?,
            about_text_kr = ?,
            contact_heading_kr = ?,
            contact_address_kr = ?,
            contact_hours_wk_kr = ?,
            contact_hours_sat_kr = ?,
            social_heading_kr = ?,

            updated_at = NOW()
            WHERE id = ?");

        // จำนวนคอลัมน์ที่ถูกอัปเดต:
        // คอลัมน์ภาษาไทย: 14 ตัว
        // คอลัมน์ภาษาอังกฤษ: 9 ตัว
        // คอลัมน์ภาษาจีน: 9 ตัว
        // คอลัมน์ภาษาญี่ปุ่น: 9 ตัว
        // เพิ่มคอลัมน์ภาษาเกาหลี: 9 ตัว
        // รวมทั้งหมด 14 + 9 + 9 + 9 + 9 = 50 ตัวที่เป็น string
        // + id 1 ตัว (integer)
        // รวมทั้งหมด 50 's' และ 1 'i'
        $bind_types = "ssssssssssssssssssssssssssssssssssssssssssssssssssi";

        $stmt->bind_param($bind_types,
            $bg_color,
            $footer_top_title,
            $footer_top_subtitle,
            $about_heading,
            $about_text,
            $contact_heading,
            $contact_address,
            $contact_phone,
            $contact_email,
            $contact_hours_wk,
            $contact_hours_sat,
            $social_heading,
            $social_links_json_for_db,
            $copyright_text,

            $footer_top_title_en,
            $footer_top_subtitle_en,
            $about_heading_en,
            $about_text_en,
            $contact_heading_en,
            $contact_address_en,
            $contact_hours_wk_en,
            $contact_hours_sat_en,
            $social_heading_en,

            $footer_top_title_cn,
            $footer_top_subtitle_cn,
            $about_heading_cn,
            $about_text_cn,
            $contact_heading_cn,
            $contact_address_cn,
            $contact_hours_wk_cn,
            $contact_hours_sat_cn,
            $social_heading_cn,
            
            $footer_top_title_jp,
            $footer_top_subtitle_jp,
            $about_heading_jp,
            $about_text_jp,
            $contact_heading_jp,
            $contact_address_jp,
            $contact_hours_wk_jp,
            $contact_hours_sat_jp,
            $social_heading_jp,

            // เพิ่ม kr ที่นี่
            $footer_top_title_kr,
            $footer_top_subtitle_kr,
            $about_heading_kr,
            $about_text_kr,
            $contact_heading_kr,
            $contact_address_kr,
            $contact_hours_wk_kr,
            $contact_hours_sat_kr,
            $social_heading_kr,

            $footer_id
        );

        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Footer updated successfully.';
        } else {
            throw new Exception('Failed to update footer in database: ' . $stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
}

echo json_encode($response);
exit;
?>