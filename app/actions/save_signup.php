<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');
require_once(__DIR__ . '/../../lib/connect.php');
require_once(__DIR__ . '/../../lib/send_mail.php');

$response = array('status' => '', 'message' => '');

function generateOTP($length = 6) {
    $digits = '0123456789';
    $otp = '';
    for ($i = 0; $i < $length; $i++) {
        $otp .= $digits[rand(0, strlen($digits) - 1)];
    }
    return $otp;
}

try {
    if (isset($_POST['action']) && $_POST['action'] == 'save_signup') {

        $register_data = array(
            'first_name' => $_POST['signUp_name'],
            'last_name' => $_POST['signUp_surname'],
            'email' => $_POST['signUp_email'],
            'country_code' => $_POST['country_code'] ?? '+66',
            'phone' => $_POST['signUp_phone'],
            'password' => $_POST['signUp_password'],
            'confirm_password' => $_POST['signUp_confirm_password'],
            'consent' => $_POST['signUp_agree'],
            'login_method' => $_POST['login_method'] ?? 'email'
        );

        // Check if email already exists
        $stmt = $conn->prepare("SELECT COUNT(user_id) as total FROM mb_user WHERE email = ? AND del = 0");
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }

        $stmt->bind_param("s", $register_data['email']);
        
        if (!$stmt->execute()) {
            throw new Exception("Count query failed: " . $stmt->error);
        }

        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (intval($row['total']) > 0) {
            $response['status'] = 'error';
            $response['message'] = 'Email already exists';
            echo json_encode($response);
            exit;
        }

        // Combine country code with phone number
        $full_phone = $register_data['country_code'] . $register_data['phone'];

        // Check if phone already exists
        $stmt = $conn->prepare("SELECT COUNT(user_id) as total FROM mb_user WHERE phone_number = ? AND del = 0");
        $stmt->bind_param("s", $full_phone);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (intval($row['total']) > 0) {
            $response['status'] = 'error';
            $response['message'] = 'Phone number already exists';
            echo json_encode($response);
            exit;
        }

        // Start transaction
        $conn->begin_transaction();

        try {
            $otp = generateOTP();

            // Insert user
            $stmt = $conn->prepare(
                "INSERT INTO mb_user (
                    first_name, 
                    last_name, 
                    password, 
                    email, 
                    country_code,
                    phone_number, 
                    login_method,
                    consent, 
                    verify, 
                    generate_otp, 
                    email_verified,
                    phone_verified,
                    date_create
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, ?)"
            );
            
            if (!$stmt) {
                throw new Exception("Prepare statement failed: " . $conn->error);
            }

            $hashed_password = password_hash($register_data['password'], PASSWORD_BCRYPT);
            $current_date = date('Y-m-d H:i:s');
            $verify = 1;

            $stmt->bind_param(
                "sssssssisis", 
                $register_data['first_name'], 
                $register_data['last_name'], 
                $hashed_password, 
                $register_data['email'],
                $register_data['country_code'],
                $full_phone,
                $register_data['login_method'],
                $register_data['consent'], 
                $verify, 
                $otp,
                $current_date
            );

            if (!$stmt->execute()) {
                throw new Exception("Execute statement failed: " . $stmt->error);
            }

            $last_insert_id = $conn->insert_id;
            $stmt->close();

            // Assign Customer Role (role_id = 5)
            $role_id = 5;
            $stmt = $conn->prepare("INSERT INTO acc_user_roles (user_id, role_id) VALUES (?, ?)");
            if (!$stmt) {
                throw new Exception("Failed to prepare role assignment: " . $conn->error);
            }
            
            $stmt->bind_param('ii', $last_insert_id, $role_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to assign customer role: " . $stmt->error);
            }
            $stmt->close();

            // Commit transaction
            $conn->commit();

            // Send OTP based on login method
            if ($register_data['login_method'] == 'email') {
                $emailSent = sendEmail($register_data['email'], 'register', $last_insert_id, $otp);
                if (!$emailSent) {
                    error_log("Failed to send email to: " . $register_data['email']);
                }
            } else {
                $smsSent = sendSMS($full_phone, $otp);
                if (!$smsSent) {
                    error_log("Failed to send SMS to: " . $full_phone);
                }
            }

            $response['status'] = 'succeed';
            $response['message'] = 'Registration successful. Please verify your account.';
            $response['user_id'] = $last_insert_id;
            $response['login_method'] = $register_data['login_method'];

        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
}

if (isset($stmt)) {
    $stmt->close();
}
$conn->close();

echo json_encode($response);
?>