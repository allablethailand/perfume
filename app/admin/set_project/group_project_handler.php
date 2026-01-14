<?php
// group_project_handler.php

// ต้องมี connect_db.php 
// require_once 'path/to/connect_db.php'; 
require_once(__DIR__ . '/../../../lib/base_directory.php');
require_once(__DIR__ . '/../../../lib/connect.php');
require_once(__DIR__ . '/../../../inc/getFunctions.php');
header('Content-Type: application/json');

// ตรวจสอบการเชื่อมต่อ
if (!isset($conn) || !$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    
    // =======================================================
    // 1. ดึงรายการโปรเจกต์ทั้งหมด (ที่ยังไม่ได้ถูกเพิ่มในกลุ่มนี้) สำหรับ Select2
    //    *แก้ไข: ตรวจสอบจากตารางเชื่อมโยง dn_project_group_relations
    // =======================================================
    case 'get_all_projects_to_add':
        $term = $_GET['term'] ?? ''; // คำค้นหา
        $group_id = $_GET['group_id'] ?? 0; // ID กลุ่มปัจจุบัน
        
        // ถ้าไม่มีคำค้นหา ให้ใช้ '%' เพื่อดึงทั้งหมด
        $search_term_like = $term ? "%{$term}%" : "%"; 

        // SQL: ดึงโปรเจกต์ที่ 'del' = '0' และ project_id นั้น 'ไม่อยู่' ในตารางเชื่อมโยงสำหรับ group_id นี้
        $sql = "SELECT p.project_id, p.subject_project 
                FROM dn_project p
                LEFT JOIN dn_project_group_relations r 
                    ON p.project_id = r.project_id AND r.group_id = ?
                WHERE p.del = '0' 
                  AND r.relation_id IS NULL  -- โปรเจกต์ที่ยังไม่มีความสัมพันธ์กับกลุ่มนี้
                  AND p.subject_project LIKE ?
                ORDER BY p.subject_project ASC
                LIMIT 50"; // จำกัดจำนวนผลลัพธ์เพื่อประสิทธิภาพ (สามารถปรับเพิ่มได้)

        $stmt = $conn->prepare($sql);
        // ใช้ "is" สำหรับ group_id และ search_term_like
        $stmt->bind_param("is", $group_id, $search_term_like);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $projects = [];
        while ($row = $result->fetch_assoc()) {
            $projects[] = [
                "id" => $row["project_id"],
                "text" => htmlspecialchars($row["subject_project"]) . " (ID: " . $row["project_id"] . ")"
            ];
        }
        $stmt->close();

        // ส่งผลลัพธ์ในรูปแบบที่ Select2 คาดหวัง
        echo json_encode(["results" => $projects]);
        break;


    // =======================================================
    // 2. ดึงรายการโปรเจกต์ที่อยู่ในกลุ่มนี้ สำหรับตาราง 
    //    *แก้ไข: ดึงข้อมูลจากตารางเชื่อมโยง dn_project_group_relations
    // =======================================================
    case 'get_projects_in_group':
        $group_id = $_GET['group_id'] ?? 0;
        
        if ($group_id <= 0) {
            echo json_encode([]);
            exit();
        }

        // SQL: ดึงข้อมูลโปรเจกต์ทั้งหมดที่มีความสัมพันธ์กับ group_id นี้
        $sql = "SELECT p.project_id, p.subject_project, r.relation_id
                FROM `dn_project_group_relations` r
                JOIN `dn_project` p ON r.project_id = p.project_id
                WHERE p.`del` = '0' AND r.`group_id` = ?
                ORDER BY p.`subject_project` ASC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $projects = [];
        while ($row = $result->fetch_assoc()) {
            // ในโครงสร้างใหม่นี้ ทุกโปรเจกต์ที่ถูกดึงมาคือ 'อยู่ในกลุ่มนี้' เสมอ
            $is_match = !empty($row['relation_id']);
            $projects[] = [
                'project_id' => $row['project_id'],
                'subject_project' => htmlspecialchars($row['subject_project']),
                'group_id_match' => $is_match,
                'status_text' => 'จัดอยู่ในกลุ่มนี้',
            ];
        }
        $stmt->close();

        echo json_encode($projects);
        break;
        
    // =======================================================
    // 3. เพิ่ม Project เข้า Group (Insert ลงตารางเชื่อมโยง) 
    //    *แก้ไข: เปลี่ยนจาก UPDATE เป็น INSERT
    // =======================================================
    case 'add_project_to_group':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
            exit();
        }

        $group_id = $_POST['group_id'] ?? 0;
        $project_id = $_POST['project_id'] ?? 0;
        $date_create = date("Y-m-d H:i:s"); // เพิ่มการบันทึกวันเวลา

        if ($group_id <= 0 || $project_id <= 0) {
            echo json_encode(['status' => 'warning', 'message' => 'ข้อมูลกลุ่มหรือโปรเจกต์ไม่ถูกต้อง.']);
            exit();
        }

        // 1. ตรวจสอบก่อนว่ามีความสัมพันธ์นี้อยู่แล้วหรือไม่
        $check_sql = "SELECT relation_id FROM dn_project_group_relations WHERE group_id = ? AND project_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $group_id, $project_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $check_stmt->close();
            echo json_encode(['status' => 'warning', 'message' => 'โปรเจกต์นี้ถูกเพิ่มในกลุ่มนี้อยู่แล้ว.']);
            exit();
        }
        $check_stmt->close();

        // 2. ถ้ายังไม่มี ให้ทำการ INSERT
        $insert_sql = "INSERT INTO dn_project_group_relations (group_id, project_id, date_create) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);

        if ($stmt) {
            $stmt->bind_param("iis", $group_id, $project_id, $date_create);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'จัดโปรเจกต์เข้ากลุ่มเรียบร้อยแล้ว.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database prepare failed: ' . $conn->error]);
        }
        break;

    // =======================================================
    // 4. นำ Project ออกจาก Group (DELETE จากตารางเชื่อมโยง)
    //    *แก้ไข: เปลี่ยนจาก UPDATE เป็น DELETE
    // =======================================================
    case 'remove_project_from_group':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
            exit();
        }

        $group_id = $_POST['group_id'] ?? 0;
        $project_id = $_POST['project_id'] ?? 0;
        
        if ($group_id <= 0 || $project_id <= 0) {
            echo json_encode(['status' => 'warning', 'message' => 'ข้อมูลกลุ่มหรือโปรเจกต์ไม่ถูกต้อง.']);
            exit();
        }

        // SQL: ลบรายการความสัมพันธ์ออกจากตารางเชื่อมโยง
        $stmt = $conn->prepare("DELETE FROM dn_project_group_relations WHERE project_id = ? AND group_id = ?");

        if ($stmt) {
            $stmt->bind_param("ii", $project_id, $group_id); 
            
            if ($stmt->execute()) { 
                if ($stmt->affected_rows > 0) {
                    echo json_encode(['status' => 'success', 'message' => 'นำโปรเจกต์ออกจากกลุ่มเรียบร้อยแล้ว.']);
                } else {
                    echo json_encode(['status' => 'warning', 'message' => 'ไม่พบความสัมพันธ์ หรือโปรเจกต์นี้ไม่ได้อยู่ในกลุ่มนี้.']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการลบข้อมูล: ' . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database prepare failed: ' . $conn->error]);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
        break;
}

?>