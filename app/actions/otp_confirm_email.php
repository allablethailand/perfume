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

    if (isset($_POST['action']) && $_POST['action'] == 'sendOTP') {
        
        $otp_data = array(
            'user_id' => isset($_POST['userId']) ? intval($_POST['userId']) : 0,
            'otp_code' => isset($_POST['otpCode']) ? intval($_POST['otpCode']) : 0,
            'method' => isset($_POST['method']) ? $_POST['method'] : 'email'
        );

        if ($otp_data['user_id'] <= 0) {
            throw new Exception("Invalid user ID: " . $otp_data['user_id']);
        }
        
        if ($otp_data['otp_code'] <= 0) {
            throw new Exception("Invalid OTP code: " . $otp_data['otp_code']);
        }

        // Query 1: ตรวจสอบ OTP (แก้ไขแล้ว - ไม่ใช้ COUNT กับ column อื่น)
        $sql = "SELECT login_method 
                FROM mb_user 
                WHERE user_id = ? AND generate_otp = ? AND del = 0
                LIMIT 1";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed (Query 1): " . $conn->error);
        }

        $stmt->bind_param("ii", $otp_data['user_id'], $otp_data['otp_code']);

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
        
    } else {
        throw new Exception("Invalid action: " . (isset($_POST['action']) ? $_POST['action'] : 'none'));
    }

} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
}

if (isset($stmt) && $stmt instanceof mysqli_stmt) {
    $stmt->close();
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

ob_end_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>