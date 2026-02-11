<?php
ob_start();
header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');

try {
    require_once(__DIR__ . '/../../lib/connect.php');
    global $conn;

    ob_clean();

    if (!$conn || $conn->connect_error) {
        throw new Exception("Database connection failed: " . ($conn ? $conn->connect_error : "No connection"));
    }

    $response = array('status' => '', 'message' => '');

    if (isset($_POST['action']) && $_POST['action'] == 'resetPassword') {
        
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
        
        if (empty($email)) {
            throw new Exception("Email is required");
        }
        
        if (empty($new_password)) {
            throw new Exception("New password is required");
        }
        
        if (strlen($new_password) < 8) {
            throw new Exception("Password must be at least 8 characters");
        }
        
        // ตรวจสอบว่ามี user นี้อยู่จริง
        $sql = "SELECT user_id FROM mb_user WHERE email = ? AND del = 0 LIMIT 1";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("s", $email);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        $stmt = null; // 🔥 เพิ่มบรรทัดนี้
        
        if (!$user) {
            throw new Exception("Email not found");
        }
        
        // Hash password ใหม่
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        
        // Update password ในฐานข้อมูล
        $sql = "UPDATE mb_user 
                SET password = ?, 
                    date_update = NOW() 
                WHERE email = ? AND del = 0";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("ss", $hashed_password, $email);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $stmt->close();
        $stmt = null; // 🔥 เพิ่มบรรทัดนี้
        
        $response['status'] = 'success';
        $response['message'] = 'Password reset successfully';
        
    } else {
        throw new Exception("Invalid action");
    }

} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
}

// 🔥 ลบส่วนนี้ออก เพราะเรา close ไปแล้วด้านบน
// if (isset($stmt) && $stmt instanceof mysqli_stmt) {
//     $stmt->close();
// }

// ปิด connection
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

ob_end_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>