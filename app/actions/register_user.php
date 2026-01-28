<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../../lib/connect.php');
require_once(__DIR__ . '/../../lib/send_mail.php');

global $conn;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$response = ['status' => 'error', 'message' => ''];

try {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $ai_code = strtoupper(trim($_POST['ai_code'] ?? ''));
    $language = trim($_POST['language'] ?? $_SESSION['selected_language'] ?? 'th');
    
    error_log("=== REGISTER USER DEBUG ===");
    error_log("AI Code: " . $ai_code);
    error_log("Language: " . $language);
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        throw new Exception("All fields are required");
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }
    
    if (strlen($password) < 6) {
        throw new Exception("Password must be at least 6 characters");
    }
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT user_id FROM mb_user WHERE email = ? AND del = 0");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception("This email is already registered");
    }
    $stmt->close();
    
    // Check if phone already exists
    $stmt = $conn->prepare("SELECT user_id FROM mb_user WHERE phone_number = ? AND del = 0");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception("This phone number is already registered");
    }
    $stmt->close();
    
    $conn->begin_transaction();
    
    try {
        // Generate OTP
        $otp = sprintf("%06d", rand(0, 999999));
        error_log("Generated OTP: " . $otp);
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        // แยกชื่อออกเป็น first_name และ last_name
        $nameParts = explode(' ', $name, 2);
        $first_name = $nameParts[0];
        $last_name = isset($nameParts[1]) ? $nameParts[1] : '';
        
        // Insert user ตามโครงสร้างตารางจริง
        $stmt = $conn->prepare("
            INSERT INTO mb_user 
            (first_name, last_name, email, phone_number, password, generate_otp, login_method, 
             verify, phone_verified, email_verified, confirm_email, del, date_create, date_update)
            VALUES (?, ?, ?, ?, ?, ?, 'email', 0, 0, 0, 0, 0, NOW(), NOW())
        ");
        $stmt->bind_param("ssssss", $first_name, $last_name, $email, $phone, $hashedPassword, $otp);
        $stmt->execute();
        $user_id = $conn->insert_id;
        $stmt->close();
        
        error_log("Created user_id: " . $user_id);
        
        // Insert user role (role_id = 5 สำหรับ customer)
        $role_id = 5;
        $stmt = $conn->prepare("
            INSERT INTO acc_user_roles (user_id, role_id)
            VALUES (?, ?)
        ");
        $stmt->bind_param("ii", $user_id, $role_id);
        $stmt->execute();
        $stmt->close();
        
        $companion_id = null;
        
        // ถ้ามี ai_code -> สร้าง user_ai_companion ทันที
        if (!empty($ai_code) && preg_match('/^AI-[A-Z0-9]{8,}$/', $ai_code)) {
            error_log("Checking AI code: " . $ai_code);
            
            // ตรวจสอบว่า AI มีอยู่จริง
            $stmt = $conn->prepare("
                SELECT ai_id 
                FROM ai_companions 
                WHERE ai_code = ? AND status = 1 AND del = 0
                LIMIT 1
            ");
            $stmt->bind_param("s", $ai_code);
            $stmt->execute();
            $result = $stmt->get_result();
            $ai = $result->fetch_assoc();
            $stmt->close();
            
            if ($ai) {
                $ai_id = $ai['ai_id'];
                error_log("Found ai_id: " . $ai_id);
                
                // สร้าง companion
                $stmt = $conn->prepare("
                    INSERT INTO user_ai_companions 
                    (ai_id, user_id, preferred_language, setup_completed, first_scan_at, last_active_at, status, del)
                    VALUES (?, ?, ?, 0, NOW(), NOW(), 1, 0)
                ");
                $stmt->bind_param("iis", $ai_id, $user_id, $language);
                $stmt->execute();
                $companion_id = $conn->insert_id;
                $stmt->close();
                
                error_log("Created companion_id: " . $companion_id);
                
                $_SESSION['companion_id'] = $companion_id;
            } else {
                error_log("AI code not found: " . $ai_code);
            }
        } else {
            error_log("No valid AI code provided");
        }
        
        // Send OTP via email
        sendEmail($email, 'register', $user_id, $otp);
        error_log("OTP email sent to: " . $email);
        
        $conn->commit();
        error_log("Transaction committed successfully");
        
        $response = [
            'status' => 'success',
            'message' => 'Registration successful. Please check your email for OTP code.',
            'user_id' => $user_id,
            'method' => 'email'
        ];
        
        // ⚠️ FIX: ส่ง companion_id กลับไปด้วย (ถ้ามี)
        if ($companion_id) {
            $response['companion_id'] = $companion_id;
        }
        
        // ⚠️ DEVELOPMENT ONLY - Remove in production
        if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE === true) {
            $response['dev_otp'] = $otp;
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Transaction rollback: " . $e->getMessage());
        throw $e;
    }
    
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
    error_log("ERROR in register_user.php: " . $e->getMessage());
}

error_log("Response: " . json_encode($response));
echo json_encode($response);
?>