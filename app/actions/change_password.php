<?php
ob_start();
header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');

try {
    require_once(__DIR__ . '/../../lib/connect.php');
    require_once(__DIR__ . '/../../lib/send_mail.php');
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

    // Step 1: ส่ง OTP ไปยังอีเมล
    if (isset($_POST['action']) && $_POST['action'] == 'sendOTPChangePassword') {
        
        $user_email = isset($_POST['email']) ? trim($_POST['email']) : '';

        if (empty($user_email)) {
            throw new Exception("Email is required");
        }

        // ตรวจสอบว่าอีเมลมีอยู่ในระบบหรือไม่
        $stmt = $conn->prepare("SELECT user_id, email, generate_otp FROM mb_user WHERE email = ? AND del = 0 LIMIT 1");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("s", $user_email);

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user) {
            // ส่ง OTP ไปยังอีเมล
            sendEmail($user['email'], 'change_password', $user['user_id'], $user['generate_otp']);

            $response['status'] = 'succeed';
            $response['message'] = 'OTP has been sent to your email.';
            $response['user_id'] = $user['user_id'];
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Email not found in our system.';
        }
        
    } 
    // Step 2: ยืนยัน OTP
    elseif (isset($_POST['action']) && $_POST['action'] == 'verifyOTPChangePassword') {
        
        $user_id = isset($_POST['userId']) ? intval($_POST['userId']) : 0;
        $otp_code = isset($_POST['otpCode']) ? intval($_POST['otpCode']) : 0;

        if ($user_id <= 0) {
            throw new Exception("Invalid user ID");
        }
        
        if ($otp_code <= 0) {
            throw new Exception("Invalid OTP code");
        }

        // ตรวจสอบ OTP
        $stmt = $conn->prepare("SELECT user_id FROM mb_user WHERE user_id = ? AND generate_otp = ? AND del = 0 LIMIT 1");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ii", $user_id, $otp_code);

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            // OTP ถูกต้อง
            $response['status'] = 'succeed';
            $response['message'] = 'OTP verified successfully.';
            $response['user_id'] = $user_id;
        } else {
            // OTP ไม่ถูกต้อง
            $response['status'] = 'error';
            $response['message'] = 'Invalid OTP code. Please try again.';
        }
        
    }
    // Step 3: เปลี่ยนรหัสผ่าน
    elseif (isset($_POST['action']) && $_POST['action'] == 'updatePassword') {
        
        $user_id = isset($_POST['userId']) ? intval($_POST['userId']) : 0;
        $new_password = isset($_POST['newPassword']) ? trim($_POST['newPassword']) : '';
        $confirm_password = isset($_POST['confirmPassword']) ? trim($_POST['confirmPassword']) : '';

        if ($user_id <= 0) {
            throw new Exception("Invalid user ID");
        }

        if (empty($new_password) || empty($confirm_password)) {
            throw new Exception("Password fields cannot be empty");
        }

        if ($new_password !== $confirm_password) {
            throw new Exception("Passwords do not match");
        }

        // ตรวจสอบความแข็งแรงของรหัสผ่าน
        if (strlen($new_password) < 8) {
            throw new Exception("Password must be at least 8 characters long");
        }

        if (!preg_match('/[A-Z]/', $new_password)) {
            throw new Exception("Password must contain at least one uppercase letter");
        }

        if (!preg_match('/[a-z]/', $new_password)) {
            throw new Exception("Password must contain at least one lowercase letter");
        }

        if (!preg_match('/[0-9]/', $new_password)) {
            throw new Exception("Password must contain at least one number");
        }

        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_password)) {
            throw new Exception("Password must contain at least one special character");
        }

        // อัพเดทรหัสผ่านและ generate OTP ใหม่
        $stmt = $conn->prepare(
            "UPDATE mb_user 
            SET password = ?, 
                generate_otp = ?,
                date_update = NOW()
            WHERE user_id = ? AND del = 0"
        );

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        $new_otp = generateOTPnew();

        $stmt->bind_param("sii", $hashed_password, $new_otp, $user_id);

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $stmt->close();

        $response['status'] = 'succeed';
        $response['message'] = 'Password has been changed successfully.';
        
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