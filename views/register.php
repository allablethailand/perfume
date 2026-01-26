<?php
// ไฟล์นี้ใช้สำหรับ debug ปัญหา 500 error
// อัพโหลดไปที่ root directory แล้วเข้า: https://www.trandar.com/perfume/debug_register.php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug_error.log');

echo "<h1>🔍 Register Debug Report</h1>";
echo "<pre style='background: #f0f0f0; padding: 20px; border-radius: 8px;'>";

// 1. ตรวจสอบ PHP Version
echo "=== 1. PHP VERSION ===\n";
echo "PHP Version: " . phpversion() . "\n\n";

// 2. ตรวจสอบ Extensions ที่จำเป็น
echo "=== 2. PHP EXTENSIONS ===\n";
$required_extensions = ['mysqli', 'pdo', 'pdo_mysql', 'mbstring', 'openssl', 'curl', 'json'];
foreach ($required_extensions as $ext) {
    $status = extension_loaded($ext) ? '✅ Loaded' : '❌ Missing';
    echo "$ext: $status\n";
}
echo "\n";

// 3. ตรวจสอบไฟล์ที่จำเป็น
echo "=== 3. FILE EXISTENCE ===\n";
$required_files = [
    'lib/connect.php' => __DIR__ . '/lib/connect.php',
    'lib/send_mail.php' => __DIR__ . '/lib/send_mail.php',
    'lib/base_directory.php' => __DIR__ . '/lib/base_directory.php',
    'vendor/autoload.php' => __DIR__ . '/vendor/autoload.php',
    'vendor/phpmailer/PHPMailer/src/PHPMailer.php' => __DIR__ . '/vendor/phpmailer/PHPMailer/src/PHPMailer.php'
];

foreach ($required_files as $name => $path) {
    $status = file_exists($path) ? '✅ Exists' : '❌ Missing';
    echo "$name: $status\n";
    if (file_exists($path)) {
        echo "  → Path: $path\n";
        echo "  → Readable: " . (is_readable($path) ? 'Yes' : 'No') . "\n";
    }
}
echo "\n";

// 4. ทดสอบ Database Connection
echo "=== 4. DATABASE CONNECTION ===\n";
try {
    if (file_exists(__DIR__ . '/lib/connect.php')) {
        require_once(__DIR__ . '/lib/connect.php');
        
        if (isset($conn) && $conn instanceof mysqli) {
            if ($conn->connect_error) {
                echo "❌ Connection failed: " . $conn->connect_error . "\n";
            } else {
                echo "✅ Database connected successfully\n";
                echo "  → Host: " . $conn->host_info . "\n";
                echo "  → Character set: " . $conn->character_set_name() . "\n";
                
                // ทดสอบ query
                $test_query = "SELECT COUNT(*) as count FROM mb_user LIMIT 1";
                $result = $conn->query($test_query);
                if ($result) {
                    echo "  → Test query: ✅ Success\n";
                } else {
                    echo "  → Test query: ❌ Failed - " . $conn->error . "\n";
                }
            }
        } else {
            echo "❌ \$conn is not a mysqli object\n";
        }
    } else {
        echo "❌ lib/connect.php not found\n";
    }
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
}
echo "\n";

// 5. ทดสอบ Session
echo "=== 5. SESSION ===\n";
try {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    echo "✅ Session started successfully\n";
    echo "  → Session ID: " . session_id() . "\n";
    echo "  → Session save path: " . session_save_path() . "\n";
} catch (Exception $e) {
    echo "❌ Session Error: " . $e->getMessage() . "\n";
}
echo "\n";

// 6. ทดสอบ PHPMailer
echo "=== 6. PHPMAILER ===\n";
try {
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require __DIR__ . '/vendor/autoload.php';
        echo "✅ Composer autoload loaded\n";
        
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            echo "✅ PHPMailer class exists\n";
            
            $testMail = new PHPMailer\PHPMailer\PHPMailer(true);
            echo "✅ PHPMailer object created\n";
        } else {
            echo "❌ PHPMailer class not found\n";
        }
    } else {
        echo "❌ vendor/autoload.php not found\n";
    }
} catch (Exception $e) {
    echo "❌ PHPMailer Error: " . $e->getMessage() . "\n";
}
echo "\n";

// 7. ทดสอบ SMTP Connection
echo "=== 7. SMTP CONNECTION TEST ===\n";
try {
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->SMTPDebug = 0;
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'apisit@origami.life';
        $mail->Password = 'lswx qgcg iicc ykiv';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->Timeout = 10;
        
        // ลองเชื่อมต่อ SMTP
        if ($mail->smtpConnect()) {
            echo "✅ SMTP connection successful (Port 465)\n";
            $mail->smtpClose();
        } else {
            echo "❌ SMTP connection failed (Port 465)\n";
            
            // ลอง Port 587
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            if ($mail->smtpConnect()) {
                echo "✅ SMTP connection successful (Port 587)\n";
                $mail->smtpClose();
            } else {
                echo "❌ SMTP connection failed (Port 587)\n";
            }
        }
    }
} catch (Exception $e) {
    echo "❌ SMTP Test Error: " . $e->getMessage() . "\n";
}
echo "\n";

// 8. ทดสอบ File Permissions
echo "=== 8. FILE PERMISSIONS ===\n";
$check_dirs = [
    __DIR__,
    __DIR__ . '/lib',
    __DIR__ . '/vendor',
    session_save_path()
];

foreach ($check_dirs as $dir) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        $writable = is_writable($dir) ? '✅ Writable' : '❌ Not writable';
        echo "$dir\n";
        echo "  → Permissions: $perms\n";
        echo "  → $writable\n";
    }
}
echo "\n";

// 9. ตรวจสอบ Memory & Limits
echo "=== 9. PHP CONFIGURATION ===\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Max Execution Time: " . ini_get('max_execution_time') . " seconds\n";
echo "Post Max Size: " . ini_get('post_max_size') . "\n";
echo "Upload Max Filesize: " . ini_get('upload_max_filesize') . "\n";
echo "Display Errors: " . ini_get('display_errors') . "\n";
echo "Log Errors: " . ini_get('log_errors') . "\n";
echo "Error Log: " . ini_get('error_log') . "\n";
echo "\n";

// 10. ทดสอบ Register Form Processing (Simulation)
echo "=== 10. REGISTER FORM SIMULATION ===\n";
try {
    echo "Testing form validation...\n";
    
    // Simulate POST data
    $_POST = [
        'signUp_name' => 'Test',
        'signUp_surname' => 'User',
        'signUp_email' => 'test@example.com',
        'country_code' => '+66',
        'signUp_phone' => '812345678',
        'signUp_password' => 'Test@1234',
        'signUp_confirm_password' => 'Test@1234',
        'login_method' => 'email',
        'signUp_agree' => '1'
    ];
    
    $first_name = $_POST['signUp_name'] ?? '';
    $last_name = $_POST['signUp_surname'] ?? '';
    $email = $_POST['signUp_email'] ?? '';
    $country_code = $_POST['country_code'] ?? '+66';
    $phone = $_POST['signUp_phone'] ?? '';
    $password = $_POST['signUp_password'] ?? '';
    
    echo "✅ Form data processed:\n";
    echo "  → Name: $first_name $last_name\n";
    echo "  → Email: $email\n";
    echo "  → Phone: $country_code$phone\n";
    echo "  → Password: " . str_repeat('*', strlen($password)) . "\n";
    
    // Test password hashing
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    echo "✅ Password hashing: Success\n";
    
    // Test OTP generation
    $otp = rand(100000, 999999);
    echo "✅ OTP generation: $otp\n";
    
} catch (Exception $e) {
    echo "❌ Form Simulation Error: " . $e->getMessage() . "\n";
}
echo "\n";

echo "=== DEBUG COMPLETE ===\n";
echo "Check the error log at: " . __DIR__ . "/debug_error.log\n";
echo "\n";

// ตรวจสอบ Error Log ล่าสุด
if (file_exists(__DIR__ . '/debug_error.log')) {
    echo "=== RECENT ERRORS FROM LOG ===\n";
    $log_content = file_get_contents(__DIR__ . '/debug_error.log');
    $lines = explode("\n", $log_content);
    $recent_lines = array_slice($lines, -20); // แสดง 20 บรรทัดล่าสุด
    echo implode("\n", $recent_lines);
}

echo "</pre>";

echo "<h2>📋 Recommendations</h2>";
echo "<ul>";
echo "<li>If any files are missing, upload them from your local environment</li>";
echo "<li>If SMTP connection fails, try changing to port 587 with STARTTLS</li>";
echo "<li>Check server error logs at /var/log/apache2/error.log or /var/log/nginx/error.log</li>";
echo "<li>Make sure lib/connect.php has correct database credentials</li>";
echo "<li>Verify PHPMailer is installed: composer require phpmailer/phpmailer</li>";
echo "</ul>";
?>