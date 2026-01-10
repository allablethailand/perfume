<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');
require_once('../../lib/connect.php');
require_once('../../lib/send_mail.php');

global $conn;

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


try {

    if (isset($_POST['action']) && $_POST['action'] == 'forgotPassword') {
        $forgot_data = array(
            'user_email' => $_POST['forgot_email']
        );

        $stmt = $conn->prepare("SELECT COUNT(user_id) as total, user_id, generate_otp FROM mb_user WHERE email = ?");
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }

        $stmt->bind_param("s", $forgot_data['user_email']);

        if (!$stmt->execute()) {
            throw new Exception("Count query failed: " . $stmt->error);
        }

        $row = $stmt->get_result()->fetch_assoc();

        if (intval($row['total']) > 0) {
            sendEmail($forgot_data['user_email'], 'forgot', $row['user_id'], $row['generate_otp']);

            $response['status'] = 'succeed';
            $response['message'] = 'Go to your email to receive OTP code.';
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Email not found.';
        }
    } else if (isset($_POST['action']) && $_POST['action'] == 'sendReset') {
        $otp_data = array(
            'user_id' => $_POST['userId'],
            'otp_code' => $_POST['otpCode']
        );

        $stmt = $conn->prepare("SELECT COUNT(user_id) as total FROM mb_user WHERE user_id = ? AND generate_otp = ?");
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }

        $stmt->bind_param("ii", $otp_data['user_id'], $otp_data['otp_code']);

        if (!$stmt->execute()) {
            throw new Exception("Count query failed: " . $stmt->error);
        }

        $row = $stmt->get_result()->fetch_assoc();

        if (intval($row['total']) > 0) {

            $response['status'] = 'succeed';
            $response['user_id'] = $otp_data['user_id'];
            $response['message'] = 'OTP code verified successfully.';
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Please enter OTP correctly.';
        }
    } else if (isset($_POST['action']) && $_POST['action'] == 'generatePassword') {

        $re_data = array(
            'user_id' => $_POST['userId']
        );

        $stmt = $conn->prepare("SELECT COUNT(user_id) as total, user_id, email FROM mb_user WHERE user_id = ?");
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }

        $stmt->bind_param("i", $re_data['user_id']);

        if (!$stmt->execute()) {
            throw new Exception("Count query failed: " . $stmt->error);
        }

        $row = $stmt->get_result()->fetch_assoc();

        if (intval($row['total']) > 0) {

            $stmt = $conn->prepare(
                "UPDATE mb_user 
                SET 
                password = ?,
                generate_otp = ?
                WHERE user_id = ?"
            );

            if (!$stmt) {
                throw new Exception("Prepare statement failed: " . $conn->error);
            }

            $generate_password = generatePassword(12);
            $generate_otp = generateOTPnew();
            $hashed_password = password_hash($generate_password, PASSWORD_BCRYPT);

            $stmt->bind_param(
                "sii",
                $hashed_password,
                $generate_otp,
                $re_data['user_id'],
            );

            if (!$stmt->execute()) {
                throw new Exception("Execute statement failed: " . $stmt->error);
            }

            sendEmail($row['email'], 'new_password', $row['user_id'], $generate_password);

            $response['status'] = 'succeed';
            $response['message'] = 'Go to your email to receive new Passwor.';
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Email not found.';
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
