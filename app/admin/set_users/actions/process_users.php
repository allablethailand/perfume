<?php
header('Content-Type: application/json');

// ตั้งค่าการรายงานข้อผิดพลาด
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ตรวจสอบว่ามีการส่ง action มาหรือไม่
if (!isset($_POST['action'])) {
    echo json_encode(['status' => 'error', 'message' => 'Action not specified.']);
    exit();
}

try {
    // รวมไฟล์เชื่อมต่อฐานข้อมูล
    require_once(__DIR__ . '/../../../../lib/connect.php');

    $action = $_POST['action'];

    switch ($action) {
        case 'delete_user':
            // ตรวจสอบว่ามีการส่ง user_id มาหรือไม่
            if (!isset($_POST['user_id'])) {
                echo json_encode(['status' => 'error', 'message' => 'User ID not specified.']);
                exit();
            }

            $user_id = $_POST['user_id'];

            // เตรียมคำสั่ง SQL เพื่อลบผู้ใช้
            $stmt = $conn->prepare("DELETE FROM mb_user WHERE user_id = ?");

            // ผูกค่าพารามิเตอร์และทำการ execute
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            // ตรวจสอบว่ามีแถวที่ถูกลบไปหรือไม่
            if ($stmt->affected_rows > 0) {
                echo json_encode(['status' => 'success', 'message' => 'User deleted successfully.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'User not found or not deleted.']);
            }

            $stmt->close();
            break;

        // คุณสามารถเพิ่ม case อื่นๆ ที่นี่ เช่น 'edit_user' หรือ 'add_user'
        // case 'edit_user':
        //     // โค้ดสำหรับแก้ไขผู้ใช้
        //     break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
            break;
    }

    $conn->close();

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>