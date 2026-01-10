<?php
// ตั้งค่าหัวข้อเพื่อตอบกลับเป็น JSON

header('Content-Type: application/json');

// ตรวจสอบสิทธิ์ผู้ใช้
require_once('../check_permission.php');

// เชื่อมต่อฐานข้อมูล
require_once('../../../lib/connect.php');

// เตรียมตัวแปรสำหรับผลลัพธ์
$response = [
    'success' => false,
    'message' => ''
];

// ตรวจสอบคำขอและค่าที่ส่งมา
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM about_content WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'ลบเรียบร้อยแล้ว';
        } else {
            $response['message'] = 'เกิดข้อผิดพลาดขณะลบข้อมูล';
        }

        $stmt->close();
    } else {
        $response['message'] = 'ID ไม่ถูกต้อง';
    }

    $conn->close();
} else {
    $response['message'] = 'ไม่มีข้อมูลที่ต้องการลบ';
}

// ส่งผลลัพธ์กลับเป็น JSON
echo json_encode($response);
exit;
