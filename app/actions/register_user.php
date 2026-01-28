<?php
// ⚠️ FIX 3: เพิ่ม error reporting เพื่อดู error
error_reporting(E_ALL);
ini_set('display_errors', 0); // ไม่แสดง error บนหน้าจอ
ini_set('log_errors', 1); // บันทึก error ใน log

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
    error_log("Name: " . $name);
    error_log("Email: " . $email);
    error_log("Phone: " . $phone);
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
    if (!$stmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception("This email is already registered");
    }
    $stmt->close();
    
    // Check if phone already exists
    $stmt = $conn->prepare("SELECT user_id FROM mb_user WHERE phone_number = ? AND del = 0");
    if (!$stmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }
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
        
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        
        $stmt->bind_param("ssssss", $first_name, $last_name, $email, $phone, $hashedPassword, $otp);
        
        if (!$stmt->execute()) {
            throw new Exception("Database execute error: " . $stmt->error);
        }
        
        $user_id = $conn->insert_id;
        $stmt->close();
        
        error_log("Created user_id: " . $user_id);
        
        // Insert user role (role_id = 5 สำหรับ customer)
        $role_id = 5;
        $stmt = $conn->prepare("
            INSERT INTO acc_user_roles (user_id, role_id)
            VALUES (?, ?)
        ");
        
        if (!$stmt) {
            throw new Exception("Database prepare error (roles): " . $conn->error);
        }
        
        $stmt->bind_param("ii", $user_id, $role_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Database execute error (roles): " . $stmt->error);
        }
        
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
            
            if (!$stmt) {
                error_log("Warning: Cannot prepare AI check query: " . $conn->error);
            } else {
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
                    
                    if (!$stmt) {
                        error_log("Warning: Cannot prepare companion insert: " . $conn->error);
                    } else {
                        $stmt->bind_param("iis", $ai_id, $user_id, $language);
                        
                        if ($stmt->execute()) {
                            $companion_id = $conn->insert_id;
                            error_log("Created companion_id: " . $companion_id);
                            $_SESSION['companion_id'] = $companion_id;
                        } else {
                            error_log("Warning: Cannot insert companion: " . $stmt->error);
                        }
                        
                        $stmt->close();
                    }
                } else {
                    error_log("AI code not found: " . $ai_code);
                }
            }
        } else {
            error_log("No valid AI code provided");
        }
        
        // ⚠️ FIX 4: ส่ง OTP via email พร้อม try-catch แยก
        error_log("Attempting to send OTP email to: " . $email);
        $emailSent = false;
        $emailError = null;
        
        try {
            // ⚠️ สำคัญ: ใช้ @ เพื่อป้องกัน fatal error จากการส่งอีเมล
            $emailSent = @sendEmail($email, 'register', $user_id, $otp);
            
            if ($emailSent) {
                error_log("✅ OTP email sent successfully to: " . $email);
            } else {
                error_log("⚠️ OTP email send returned false to: " . $email);
                $emailError = "Email send failed but registration completed";
            }
            
        } catch (Exception $emailException) {
            error_log("❌ Email send exception: " . $emailException->getMessage());
            error_log("Email exception file: " . $emailException->getFile() . " (Line: " . $emailException->getLine() . ")");
            $emailError = $emailException->getMessage();
        } catch (Error $emailError) {
            // จับ Fatal Error ด้วย (PHP 7+)
            error_log("❌ Email fatal error: " . $emailError->getMessage());
            $emailError = "Fatal error during email send: " . $emailError->getMessage();
        }
        
        // Commit transaction (ไม่ว่าอีเมลจะส่งสำเร็จหรือไม่)
        $conn->commit();
        error_log("Transaction committed successfully");
        
        // สร้าง response
        if ($emailSent) {
            $response = [
                'status' => 'success',
                'message' => 'Registration successful. Please check your email for OTP code.',
                'user_id' => $user_id,
                'method' => 'email'
            ];
        } else {
            $response = [
                'status' => 'success',
                'message' => 'Registration successful, but email send failed. Please contact support for OTP.',
                'user_id' => $user_id,
                'method' => 'email',
                'email_sent' => false,
                'email_error' => $emailError
            ];
        }
        
        // ส่ง companion_id กลับไปด้วย (ถ้ามี)
        if ($companion_id) {
            $response['companion_id'] = $companion_id;
        }
        
        // ⚠️ DEVELOPMENT ONLY - แสดง OTP ใน response
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
    error_log("Error file: " . $e->getFile() . " (Line: " . $e->getLine() . ")");
} catch (Error $e) {
    // จับ Fatal Error ด้วย
    $response = [
        'status' => 'error',
        'message' => 'Fatal error: ' . $e->getMessage()
    ];
    error_log("FATAL ERROR in register_user.php: " . $e->getMessage());
    error_log("Error file: " . $e->getFile() . " (Line: " . $e->getLine() . ")");
}

error_log("Response: " . json_encode($response));
echo json_encode($response);
?>