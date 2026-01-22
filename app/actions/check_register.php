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
            'phone' => $_POST['signUp_phone'],
            'password' => $_POST['signUp_password'],
            'confirm_password' => $_POST['signUp_confirm_password'],
            'consent' => $_POST['signUp_agree'],
            'verify' => $_POST['signUp_send_mail']
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

        // Check if phone already exists
        $stmt = $conn->prepare("SELECT COUNT(user_id) as total FROM mb_user WHERE phone_number = ? AND del = 0");
        $stmt->bind_param("s", $register_data['phone']);
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
            // Insert user
            $stmt = $conn->prepare(
                "INSERT INTO mb_user (first_name, last_name, password, email, phone_number, consent, verify, generate_otp, confirm_email, date_create) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            if (!$stmt) {
                throw new Exception("Prepare statement failed: " . $conn->error);
            }

            $hashed_password = password_hash($register_data['password'], PASSWORD_BCRYPT);
            $current_date = date('Y-m-d H:i:s');
            $confirm_email = 0; // เริ่มต้นเป็น 0 (ยังไม่ได้ยืนยัน)

            $stmt->bind_param(
                "sssssiisis", 
                $register_data['first_name'], 
                $register_data['last_name'], 
                $hashed_password, 
                $register_data['email'], 
                $register_data['phone'], 
                $register_data['consent'], 
                $register_data['verify'], 
                $otp,
                $confirm_email,  // เพิ่มค่านี้
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

            // Send verification email
            sendEmail($register_data['email'], 'register', $last_insert_id, $otp);

            $response['status'] = 'succeed';
            $response['message'] = 'Signup completed successfully. Please check your email for verification.';

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