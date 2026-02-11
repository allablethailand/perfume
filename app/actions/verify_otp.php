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

    function generateOTPnew($length = 6) {
        $digits = '0123456789';
        $otp = '';
        for ($i = 0; $i < $length; $i++) {
            $otp .= $digits[rand(0, strlen($digits) - 1)];
        }
        return $otp;
    }

    $response = array('status' => '', 'message' => '');

    // ========================================
    // กรณี: ยืนยัน OTP จากการสมัครสมาชิก
    // ========================================
    if (isset($_POST['action']) && $_POST['action'] == 'sendOTP') {
        
        $otp_data = array(
            'user_id' => isset($_POST['userId']) ? intval($_POST['userId']) : 0,
            'otp_code' => isset($_POST['otpCode']) ? strval($_POST['otpCode']) : '', // เปลี่ยนเป็น string
            'method' => isset($_POST['method']) ? $_POST['method'] : 'email'
        );

        if ($otp_data['user_id'] <= 0) {
            throw new Exception("Invalid user ID: " . $otp_data['user_id']);
        }
        
        if (empty($otp_data['otp_code'])) {
            throw new Exception("Invalid OTP code");
        }

        // Query 1: ตรวจสอบ OTP (ใช้ string comparison)
        $sql = "SELECT login_method 
                FROM mb_user 
                WHERE user_id = ? AND generate_otp = ? AND del = 0
                LIMIT 1";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed (Query 1): " . $conn->error);
        }

        $stmt->bind_param("is", $otp_data['user_id'], $otp_data['otp_code']); // i = integer, s = string

        if (!$stmt->execute()) {
            throw new Exception("Execute failed (Query 1): " . $stmt->error);
        }

        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $stmt = null;

        if ($row) {
            // OTP ถูกต้อง
            $login_method = $row['login_method'];
            
            // Query 2: อัพเดทสถานะ
            if ($login_method == 'email') {
                $sql = "UPDATE mb_user 
                        SET generate_otp = ?, 
                            email_verified = 1,
                            confirm_email = 1,
                            date_update = NOW()
                        WHERE user_id = ?";
            } else {
                $sql = "UPDATE mb_user 
                        SET generate_otp = ?, 
                            phone_verified = 1,
                            date_update = NOW()
                        WHERE user_id = ?";
            }

            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Prepare failed (Query 2): " . $conn->error);
            }

            $generate_otp = generateOTPnew();

            $stmt->bind_param("si", $generate_otp, $otp_data['user_id']);

            if (!$stmt->execute()) {
                throw new Exception("Execute failed (Query 2): " . $stmt->error);
            }

            $stmt->close();
            $stmt = null;

            $response['status'] = 'succeed';
            if ($login_method == 'email') {
                $response['message'] = 'Email verified successfully. You can now login.';
            } else {
                $response['message'] = 'Phone number verified successfully. You can now login.';
            }
            
        } else {
            // OTP ไม่ถูกต้อง
            $response['status'] = 'error';
            $response['message'] = 'Invalid OTP code. Please try again.';
        }
        
    } 
    // ========================================
    // กรณี: ยืนยัน OTP จากการลืมรหัสผ่าน
    // ========================================
    elseif (isset($_POST['action']) && $_POST['action'] == 'verifyOTP') {
        
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $otp = isset($_POST['otp']) ? trim($_POST['otp']) : '';
        
        if (empty($email)) {
            throw new Exception("Email is required");
        }
        
        if (empty($otp) || strlen($otp) != 6) {
            throw new Exception("Invalid OTP format");
        }
        
        // ค้นหา user จาก email
        $sql = "SELECT user_id, email, generate_otp 
                FROM mb_user 
                WHERE email = ? AND del = 0
                LIMIT 1";
        
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
        $stmt = null;
        
        if (!$user) {
            $response['status'] = 'error';
            $response['message'] = 'Email not found';
        } else {
            // 🔥 DEBUG: แสดงค่าที่เปรียบเทียบ
            // $db_otp = strval($user['generate_otp']); // แปลงเป็น string
            // $input_otp = strval($otp); // แปลงเป็น string
            
            // // เพิ่ม debug info ใน response (ลบออกตอน production)
            // if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1') {
            //     $response['debug'] = array(
            //         'db_otp' => $db_otp,
            //         'db_otp_length' => strlen($db_otp),
            //         'input_otp' => $input_otp,
            //         'input_otp_length' => strlen($input_otp),
            //         'comparison' => ($db_otp === $input_otp) ? 'MATCH' : 'NO MATCH'
            //     );
            // }
            
            // ตรวจสอบ OTP (ใช้ strict comparison)
            if ($db_otp !== $input_otp) {
                $response['status'] = 'error';
                $response['message'] = 'Invalid OTP code. Please try again.';
            } else {
                // OTP ถูกต้อง
                $response['status'] = 'success';
                $response['message'] = 'OTP verified successfully';
                $response['data'] = array(
                    'email' => $email,
                    'user_id' => $user['user_id']
                );
            }
        }
        
    } 
    else {
        throw new Exception("Invalid action: " . (isset($_POST['action']) ? $_POST['action'] : 'none'));
    }

} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
}

// ปิด statement ถ้ายังเปิดอยู่
if (isset($stmt) && $stmt instanceof mysqli_stmt) {
    $stmt->close();
}

// ปิด connection
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

ob_end_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>