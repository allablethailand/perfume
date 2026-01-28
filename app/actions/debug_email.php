<?php
/**
 * Debug Register User
 * วางไฟล์นี้ที่: /perfume/app/actions/debug_register.php
 * เข้าถึง: https://www.trandar.com/perfume/app/actions/debug_register.php
 */

// เปิด error reporting ทั้งหมด
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: application/json');

$response = [
    'status' => 'testing',
    'steps' => [],
    'errors' => []
];

try {
    // Step 1: ตรวจสอบไฟล์ที่ต้องใช้
    $response['steps'][] = '1. ตรวจสอบไฟล์ที่จำเป็น...';
    
    $files = [
        'connect.php' => __DIR__ . '/../../lib/connect.php',
        'send_mail.php' => __DIR__ . '/../../lib/send_mail.php',
        'base_directory.php' => __DIR__ . '/../../lib/base_directory.php'
    ];
    
    foreach ($files as $name => $path) {
        if (file_exists($path)) {
            $response['steps'][] = "✅ พบ $name";
        } else {
            $response['errors'][] = "❌ ไม่พบ $name ที่: $path";
        }
    }
    
    // Step 2: โหลด connect.php
    $response['steps'][] = '2. กำลังโหลด connect.php...';
    require_once(__DIR__ . '/../../lib/connect.php');
    $response['steps'][] = '✅ โหลด connect.php สำเร็จ';
    
    // Step 3: ตรวจสอบ database connection
    $response['steps'][] = '3. ตรวจสอบ database connection...';
    global $conn;
    
    if (!isset($conn)) {
        throw new Exception("❌ ไม่พบตัวแปร \$conn");
    }
    
    if ($conn->connect_error) {
        throw new Exception("❌ Database connection failed: " . $conn->connect_error);
    }
    
    $response['steps'][] = '✅ Database connected';
    $response['db_info'] = [
        'host' => $conn->host_info,
        'server_version' => $conn->server_info,
        'client_version' => $conn->client_info
    ];
    
    // Step 4: โหลด send_mail.php
    $response['steps'][] = '4. กำลังโหลด send_mail.php...';
    require_once(__DIR__ . '/../../lib/send_mail.php');
    $response['steps'][] = '✅ โหลด send_mail.php สำเร็จ';
    
    // Step 5: ตรวจสอบ function
    $response['steps'][] = '5. ตรวจสอบ functions ที่จำเป็น...';
    
    if (function_exists('sendEmail')) {
        $response['steps'][] = '✅ พบ function sendEmail()';
    } else {
        $response['errors'][] = '❌ ไม่พบ function sendEmail()';
    }
    
    // Step 6: ทดสอบ session
    $response['steps'][] = '6. ตรวจสอบ session...';
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $response['steps'][] = '✅ Session started (ID: ' . session_id() . ')';
    
    // Step 7: ทดสอบการ query database
    $response['steps'][] = '7. ทดสอบ query database...';
    
    $testEmail = 'test_' . time() . '@example.com';
    $stmt = $conn->prepare("SELECT user_id FROM mb_user WHERE email = ? AND del = 0 LIMIT 1");
    
    if (!$stmt) {
        throw new Exception("❌ Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("s", $testEmail);
    
    if (!$stmt->execute()) {
        throw new Exception("❌ Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $stmt->close();
    
    $response['steps'][] = '✅ Database query ทำงานได้';
    
    // Step 8: ทดสอบ password_hash
    $response['steps'][] = '8. ทดสอบ password_hash...';
    $testHash = password_hash('test123', PASSWORD_BCRYPT);
    if ($testHash) {
        $response['steps'][] = '✅ password_hash ทำงานได้';
    } else {
        $response['errors'][] = '❌ password_hash ไม่ทำงาน';
    }
    
    // Step 9: ทดสอบ OTP generation
    $response['steps'][] = '9. ทดสอบ OTP generation...';
    $testOTP = sprintf("%06d", rand(0, 999999));
    $response['steps'][] = '✅ OTP สร้างได้: ' . $testOTP;
    
    // Step 10: ตรวจสอบ POST data simulation
    $response['steps'][] = '10. จำลอง POST data...';
    
    $_POST['name'] = 'Test User';
    $_POST['email'] = 'test@example.com';
    $_POST['phone'] = '0123456789';
    $_POST['password'] = 'test123';
    $_POST['ai_code'] = 'AI-TEST1234';
    $_POST['language'] = 'th';
    
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $ai_code = strtoupper(trim($_POST['ai_code'] ?? ''));
    $language = trim($_POST['language'] ?? $_SESSION['selected_language'] ?? 'th');
    
    $response['steps'][] = '✅ POST data ประมวลผลได้';
    $response['test_data'] = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'password_length' => strlen($password),
        'ai_code' => $ai_code,
        'language' => $language
    ];
    
    // Step 11: ทดสอบ validation
    $response['steps'][] = '11. ทดสอบ validation...';
    
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $response['errors'][] = '❌ Validation failed: Missing required fields';
    } else {
        $response['steps'][] = '✅ Required fields validation passed';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['errors'][] = '❌ Validation failed: Invalid email';
    } else {
        $response['steps'][] = '✅ Email validation passed';
    }
    
    if (strlen($password) < 6) {
        $response['errors'][] = '❌ Validation failed: Password too short';
    } else {
        $response['steps'][] = '✅ Password validation passed';
    }
    
    // Step 12: สรุปผล
    $response['steps'][] = '12. สรุปผล...';
    
    if (empty($response['errors'])) {
        $response['status'] = 'success';
        $response['message'] = '✅ ทุกอย่างพร้อมใช้งาน! ระบบควรทำงานได้ปกติ';
        $response['steps'][] = '✅✅✅ ทุก step ผ่านหมด!';
        $response['recommendation'] = 'ถ้ายัง Error 500 อยู่ ให้ตรวจสอบ error_log ของ server';
    } else {
        $response['status'] = 'has_errors';
        $response['message'] = '⚠️ พบปัญหาบางอย่าง ดูใน errors';
    }
    
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['errors'][] = '❌ Exception: ' . $e->getMessage();
    $response['errors'][] = '📂 File: ' . $e->getFile() . ' (Line: ' . $e->getLine() . ')';
    $response['message'] = 'เกิดข้อผิดพลาดระหว่างการทดสอบ';
} catch (Error $e) {
    $response['status'] = 'fatal_error';
    $response['errors'][] = '❌ Fatal Error: ' . $e->getMessage();
    $response['errors'][] = '📂 File: ' . $e->getFile() . ' (Line: ' . $e->getLine() . ')';
    $response['message'] = 'เกิด Fatal Error';
}

// แสดงผลลัพธ์
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>