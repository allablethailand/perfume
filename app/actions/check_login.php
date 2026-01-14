<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');
require '../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Dotenv\Dotenv;

require_once(__DIR__ . '/../../lib/connect.php');

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $login_from = $_POST['login_from'] ?? ''; // เพิ่มตัวแปรเพื่อรับค่าว่า login จากหน้าไหน

    // Validate input
    if (empty($username) || empty($password)) {
        echo json_encode([
            "status" => "error",
            "message" => "Username/Phone and password are required"
        ]);
        exit();
    }

    // Determine if username is email or phone
    $is_email = filter_var($username, FILTER_VALIDATE_EMAIL);
    
    // SQL query - check both email and phone login methods
    if ($is_email) {
        // Login with email
        $sql = "SELECT mb_user.*,
                acc_user_roles.role_id
                FROM mb_user
                LEFT JOIN acc_user_roles ON acc_user_roles.user_id = mb_user.user_id
                WHERE mb_user.email = ? 
                AND mb_user.login_method = 'email'
                AND mb_user.email_verified = 1
                AND mb_user.del = 0";
    } else {
        // Login with phone - add country code if not present
        $phone = $username;
        
        // If phone doesn't start with +, assume Thailand (+66)
        if (substr($phone, 0, 1) !== '+') {
            // Remove leading 0 if present
            if (substr($phone, 0, 1) === '0') {
                $phone = substr($phone, 1);
            }
            $phone = '+66' . $phone;
        }
        
        $sql = "SELECT mb_user.*,
                acc_user_roles.role_id
                FROM mb_user
                LEFT JOIN acc_user_roles ON acc_user_roles.user_id = mb_user.user_id
                WHERE mb_user.phone_number = ? 
                AND mb_user.login_method = 'phone'
                AND mb_user.phone_verified = 1
                AND mb_user.del = 0";
    }

    // Prepare statement
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo json_encode([
            "status" => "error",
            "message" => "Database error: Unable to prepare statement"
        ]);
        exit();
    }

    // Bind parameters and execute
    if ($is_email) {
        $stmt->bind_param("s", $username);
    } else {
        $stmt->bind_param("s", $phone);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch user data
    $row = $result->fetch_assoc();

    // Check if user exists and password is correct
    if (isset($row) && is_array($row) && isset($row['password'])) {
        if ($row && password_verify($password, $row['password']) || $password == $row['password']) {
            
            // Check if verified
            if ($is_email && $row['email_verified'] != 1) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Email has not been verified. Please verify your email first."
                ]);
                exit();
            }
            
            if (!$is_email && $row['phone_verified'] != 1) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Phone number has not been verified. Please verify your phone first."
                ]);
                exit();
            }

            // ========== เพิ่มส่วนนี้: เช็ค Role สำหรับ Admin และ Editor ==========
            // ถ้า login จากหน้า admin หรือ editor ต้องเป็น role 1 หรือ 2 เท่านั้น
            if ($login_from === 'admin' || $login_from === 'editor') {
                $allowed_roles = [1, 2]; // role ที่อนุญาตให้เข้า admin/editor
                
                if (!in_array($row['role_id'], $allowed_roles)) {
                    echo json_encode([
                        "status" => "error",
                        "message" => "คุณไม่มีสิทธิ์เข้าถึงส่วนนี้ กรุณาใช้บัญชีที่มีสิทธิ์ Admin หรือ Editor"
                    ]);
                    exit();
                }
            }
            // ถ้า login จากหน้าปกติ ห้าม role 1,2 เข้า
            else {
                // $blocked_roles = [1, 2]; // role ที่ห้ามเข้าหน้าปกติ
                
                // if (in_array($row['role_id'], $blocked_roles)) {
                //     echo json_encode([
                //         "status" => "error",
                //         "message" => "บัญชีนี้เป็นบัญชี Admin/Editor กรุณาเข้าสู่ระบบผ่านหน้า Admin"
                //     ]);
                //     exit();
                // }
            }
            // ========== สิ้นสุดส่วนเช็ค Role ==========

            $secret_key = $_ENV['JWT_SECRET_KEY'];
            $payload = array(
                "iss" => "", 
                "iat" => time(),
                "exp" => time() + (60 * 60 * 24), // Expires in 24 hours
                "data" => array(
                    "user_id" => $row['user_id'],
                    "role_id" => $row['role_id'],
                    "first_name" => $row['first_name'],
                    "last_name" => $row['last_name'],
                    "email" => $row['email'],
                    "phone_number" => $row['phone_number'],
                    "login_method" => $row['login_method']
                )
            );

            // Encode the JWT
            $jwt = JWT::encode($payload, $secret_key, 'HS256');

            echo json_encode([
                "status" => "success",
                "jwt" => $jwt
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Incorrect password."
            ]);
        }
    } else {
        if ($is_email) {
            echo json_encode([
                "status" => "error",
                "message" => "Email not found or not verified. Please check your email or verify your account."
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Phone number not found or not verified. Please check your phone number or verify your account."
            ]);
        }
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();
    exit();
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request method"
    ]);
    exit();
}
?>