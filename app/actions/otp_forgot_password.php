<?php
// ==========================================
// FIX 1: Output Buffer Control
// ==========================================
ob_start();
header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');

try {
    // ==========================================
    // FIX 2: Use __DIR__ for Absolute Path
    // ==========================================
    require_once(__DIR__ . '/../../lib/connect.php');
    require_once(__DIR__ . '/../../lib/send_mail.php');
    
    global $conn;

    // ==========================================
    // FIX 3: Clean Output Buffer BEFORE Processing
    // ==========================================
    ob_clean();

    // ==========================================
    // FIX 4: Check Database Connection
    // ==========================================
    if (!$conn || $conn->connect_error) {
        throw new Exception("Database connection failed: " . ($conn ? $conn->connect_error : "No connection"));
    }

    // ==========================================
    // FIX 5: Initialize Response Variable
    // ==========================================
    $response = array('status' => '', 'message' => '');

    // ==========================================
    // Helper Functions
    // ==========================================
    function generateOTPnew($length = 6)
    {
        $digits = '0123456789';
        $otp = '';
        for ($i = 0; $i < $length; $i++) {
            $otp .= $digits[rand(0, strlen($digits) - 1)];
        }
        return $otp;
    }

    function generatePassword($length = 8) {
        if ($length < 8) {
            $length = 8;
        }

        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $digits = '0123456789';
        $specialChars = '!@#$%^&*()-_=+[]{}<>?';
        $allChars = $lowercase . $uppercase . $digits . $specialChars;

        $password = '';
        $password .= $lowercase[rand(0, strlen($lowercase) - 1)];
        $password .= $uppercase[rand(0, strlen($uppercase) - 1)];
        $password .= $digits[rand(0, strlen($digits) - 1)];
        $password .= $specialChars[rand(0, strlen($specialChars) - 1)];

        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[rand(0, strlen($allChars) - 1)];
        }

        return str_shuffle($password);
    }

    // ==========================================
    // Main Logic
    // ==========================================
    if (isset($_POST['action']) && $_POST['action'] == 'forgotPassword') {
        
        // ==========================================
        // FIX 6: Validate Input
        // ==========================================
        if (!isset($_POST['forgot_email']) || empty(trim($_POST['forgot_email']))) {
            throw new Exception("Email is required");
        }

        $forgot_data = array(
            'user_email' => trim($_POST['forgot_email'])
        );

        // ==========================================
        // Query: Check Email Exists
        // ==========================================
        $sql = "SELECT user_id, generate_otp 
                FROM mb_user 
                WHERE email = ? AND del = 0
                LIMIT 1";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }

        $stmt->bind_param("s", $forgot_data['user_email']);

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $stmt = null;

        if ($row) {
            // Send OTP Email
            sendEmail($forgot_data['user_email'], 'forgot', $row['user_id'], $row['generate_otp']);

            $response['status'] = 'succeed';
            $response['message'] = 'Go to your email to receive OTP code.';
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Email not found.';
        }

    } else if (isset($_POST['action']) && $_POST['action'] == 'sendReset') {
        
        // ==========================================
        // FIX 7: Validate Input
        // ==========================================
        if (!isset($_POST['userId']) || !isset($_POST['otpCode'])) {
            throw new Exception("User ID and OTP code are required");
        }

        $otp_data = array(
            'user_id' => intval($_POST['userId']),
            'otp_code' => intval($_POST['otpCode'])
        );

        if ($otp_data['user_id'] <= 0 || $otp_data['otp_code'] <= 0) {
            throw new Exception("Invalid user ID or OTP code");
        }

        // ==========================================
        // Query: Verify OTP
        // ==========================================
        $sql = "SELECT user_id 
                FROM mb_user 
                WHERE user_id = ? AND generate_otp = ? AND del = 0
                LIMIT 1";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }

        $stmt->bind_param("ii", $otp_data['user_id'], $otp_data['otp_code']);

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $stmt = null;

        if ($row) {
            $response['status'] = 'succeed';
            $response['user_id'] = $otp_data['user_id'];
            $response['message'] = 'OTP code verified successfully.';
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Please enter OTP correctly.';
        }

    } else if (isset($_POST['action']) && $_POST['action'] == 'generatePassword') {

        // ==========================================
        // FIX 8: Validate Input
        // ==========================================
        if (!isset($_POST['userId'])) {
            throw new Exception("User ID is required");
        }

        $re_data = array(
            'user_id' => intval($_POST['userId'])
        );

        if ($re_data['user_id'] <= 0) {
            throw new Exception("Invalid user ID");
        }

        // ==========================================
        // Query: Get User Email
        // ==========================================
        $sql = "SELECT user_id, email 
                FROM mb_user 
                WHERE user_id = ? AND del = 0
                LIMIT 1";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }

        $stmt->bind_param("i", $re_data['user_id']);

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $stmt = null;

        if ($row) {
            // Generate New Password & OTP
            $generate_password = generatePassword(12);
            $generate_otp = generateOTPnew();
            $hashed_password = password_hash($generate_password, PASSWORD_BCRYPT);

            // ==========================================
            // Query: Update Password
            // ==========================================
            $sql = "UPDATE mb_user 
                    SET password = ?, 
                        generate_otp = ?,
                        date_update = NOW()
                    WHERE user_id = ?";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare statement failed: " . $conn->error);
            }

            $stmt->bind_param("sii", $hashed_password, $generate_otp, $re_data['user_id']);

            if (!$stmt->execute()) {
                throw new Exception("Execute statement failed: " . $stmt->error);
            }

            $stmt->close();
            $stmt = null;

            // Send New Password Email
            sendEmail($row['email'], 'new_password', $row['user_id'], $generate_password);

            $response['status'] = 'succeed';
            $response['message'] = 'Go to your email to receive new Password.';
        } else {
            $response['status'] = 'error';
            $response['message'] = 'User not found.';
        }

    } else {
        throw new Exception("Invalid action: " . (isset($_POST['action']) ? $_POST['action'] : 'none'));
    }

} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
    
    // ==========================================
    // FIX 9: Log Error for Debugging
    // ==========================================
    error_log("❌ Error in otp_forgot_password.php: " . $e->getMessage());
    error_log("File: " . $e->getFile() . " (Line: " . $e->getLine() . ")");
}

// ==========================================
// FIX 10: Clean Up Resources
// ==========================================
if (isset($stmt) && $stmt instanceof mysqli_stmt) {
    $stmt->close();
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

// ==========================================
// FIX 11: Clean Buffer & Return JSON
// ==========================================
ob_end_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>