<?php
/**
 * Email Debug Tool with Real-time Logging
 * วางไฟล์นี้ที่: /perfume/app/actions/debug_email.php
 * เข้าถึง: https://www.trandar.com/perfume/app/actions/debug_email.php
 */

// ตั้งค่า error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// โหลด vendor autoload ตั้งแต่ต้น
$vendorPath = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($vendorPath)) {
    require_once($vendorPath);
}

// โหลด PHPMailer classes
if (file_exists(__DIR__ . '/../../vendor/phpmailer/PHPMailer/src/Exception.php')) {
    require_once __DIR__ . '/../../vendor/phpmailer/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../../vendor/phpmailer/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../../vendor/phpmailer/PHPMailer/src/SMTP.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ถ้าเป็นการทดสอบส่งอีเมล
if (isset($_POST['action']) && $_POST['action'] === 'test_send') {
    header('Content-Type: application/json');
    
    $logs = [];
    $success = false;
    
    // Custom error handler เพื่อเก็บ log
    set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$logs) {
        $logs[] = [
            'type' => 'error',
            'message' => "[$errno] $errstr in $errfile:$errline"
        ];
    });
    
    try {
        $logs[] = ['type' => 'info', 'message' => '🚀 เริ่มทดสอบการส่งอีเมล...'];
        
        // ตรวจสอบ Vendor Autoload
        if (!file_exists($vendorPath)) {
            throw new Exception("❌ ไม่พบ vendor/autoload.php ที่: $vendorPath");
        }
        $logs[] = ['type' => 'success', 'message' => '✅ พบ vendor/autoload.php'];
        
        // ตรวจสอบ PHPMailer
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            throw new Exception("❌ ไม่พบ PHPMailer class");
        }
        $logs[] = ['type' => 'success', 'message' => '✅ PHPMailer พร้อมใช้งาน'];
        
        $mail = new PHPMailer(true);
        
        // SMTP Configuration
        $logs[] = ['type' => 'info', 'message' => '📧 กำลังตั้งค่า SMTP...'];
        
        $mail->SMTPDebug = 0; // ปิด debug output
        $mail->isSMTP();
        $mail->SMTPAuth = true;
        $mail->Host = 'smtp.gmail.com';
        $mail->Username = 'std.nk36116@gmail.com';
        $mail->Password = 'xkde obhl qmbz wzvp';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->Timeout = 60;
        
        $logs[] = ['type' => 'success', 'message' => '✅ SMTP Config: smtp.gmail.com:587 (STARTTLS)'];
        
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        $logs[] = ['type' => 'success', 'message' => '✅ SSL Options configured'];
        
        // Email Settings
        $testEmail = $_POST['email'] ?? 'std.nk36116@gmail.com';
        $logs[] = ['type' => 'info', 'message' => "📬 ผู้รับ: $testEmail"];
        
        $mail->setFrom('std.nk36116@gmail.com', 'PERFUME Test');
        $mail->addAddress($testEmail);
        
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Test Email - ' . date('Y-m-d H:i:s');
        $mail->Body = '
            <div style="font-family: Arial, sans-serif; padding: 20px; max-width: 600px; margin: 0 auto;">
                <h2 style="color: #ff9800;">🎉 การทดสอบสำเร็จ!</h2>
                <p>อีเมลนี้ส่งจาก PERFUME Email Debug Tool</p>
                <p><strong>เวลาส่ง:</strong> ' . date('Y-m-d H:i:s') . '</p>
                <p><strong>Server:</strong> ' . $_SERVER['SERVER_NAME'] . '</p>
                <hr>
                <p style="color: #666; font-size: 12px;">
                    หากคุณได้รับอีเมลนี้ แสดงว่าระบบส่งอีเมลทำงานได้ปกติ ✅
                </p>
            </div>
        ';
        
        $logs[] = ['type' => 'info', 'message' => '📤 กำลังส่งอีเมล...'];
        
        // ส่งอีเมล
        $mail->send();
        
        $logs[] = ['type' => 'success', 'message' => '✅✅✅ ส่งอีเมลสำเร็จ!'];
        $logs[] = ['type' => 'success', 'message' => '🎉 กรุณาตรวจสอบกล่องจดหมายของคุณ'];
        
        $success = true;
        
    } catch (Exception $e) {
        $logs[] = ['type' => 'error', 'message' => '❌ เกิดข้อผิดพลาด: ' . $e->getMessage()];
        $logs[] = ['type' => 'error', 'message' => '📂 ไฟล์: ' . $e->getFile() . ' (บรรทัด: ' . $e->getLine() . ')'];
        $success = false;
    }
    
    restore_error_handler();
    
    echo json_encode([
        'success' => $success,
        'logs' => $logs
    ]);
    exit;
}

// ถ้าเป็นการเรียกดู system info
if (isset($_GET['info'])) {
    header('Content-Type: application/json');
    
    $info = [
        'php_version' => PHP_VERSION,
        'openssl_loaded' => extension_loaded('openssl'),
        'openssl_version' => defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : 'N/A',
        'vendor_exists' => file_exists(__DIR__ . '/../../vendor/autoload.php'),
        'phpmailer_exists' => false,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
        'script_path' => __FILE__
    ];
    
    // ตรวจสอบ PHPMailer
    if ($info['vendor_exists']) {
        require_once(__DIR__ . '/../../vendor/autoload.php');
        $info['phpmailer_exists'] = class_exists('PHPMailer\PHPMailer\PHPMailer');
    }
    
    echo json_encode($info);
    exit;
}

// ถ้าเป็นการทดสอบ SMTP connection
if (isset($_GET['test_smtp'])) {
    header('Content-Type: application/json');
    
    $host = $_GET['host'] ?? 'smtp.gmail.com';
    $port = intval($_GET['port'] ?? 587);
    
    $timeout = 10;
    $errno = 0;
    $errstr = '';
    
    $startTime = microtime(true);
    $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
    $endTime = microtime(true);
    
    $result = [
        'success' => (bool)$socket,
        'host' => $host,
        'port' => $port,
        'time' => round(($endTime - $startTime) * 1000, 2) . ' ms',
        'error' => $socket ? null : "$errno - $errstr"
    ];
    
    if ($socket) {
        fclose($socket);
    }
    
    echo json_encode($result);
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📧 Email Debug Tool - PERFUME</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .content {
            padding: 30px;
        }
        
        .section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .info-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        
        .info-item strong {
            color: #667eea;
            display: block;
            margin-bottom: 5px;
        }
        
        .info-item span {
            color: #666;
            font-size: 14px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-success {
            background: #d4edda;
            color: #155724;
        }
        
        .status-error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-loading {
            background: #fff3cd;
            color: #856404;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .log-container {
            background: #1e1e1e;
            color: #fff;
            padding: 20px;
            border-radius: 8px;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
            display: none;
        }
        
        .log-container.active {
            display: block;
        }
        
        .log-entry {
            margin-bottom: 8px;
            padding: 8px;
            border-radius: 4px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .log-info {
            color: #61dafb;
        }
        
        .log-success {
            color: #98c379;
            background: rgba(152, 195, 121, 0.1);
        }
        
        .log-error {
            color: #e06c75;
            background: rgba(224, 108, 117, 0.1);
        }
        
        .smtp-test {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        
        .smtp-test-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            text-align: center;
        }
        
        .smtp-test-item.testing {
            border-color: #ffc107;
        }
        
        .smtp-test-item.success {
            border-color: #28a745;
            background: #d4edda;
        }
        
        .smtp-test-item.failed {
            border-color: #dc3545;
            background: #f8d7da;
        }
        
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Email Debug Tool</h1>
            <p>PERFUME - ระบบทดสอบและแก้ไขปัญหาการส่งอีเมล</p>
        </div>
        
        <div class="content">
            <!-- System Information -->
            <div class="section">
                <h2>💻 System Information</h2>
                <div class="info-grid" id="systemInfo">
                    <div class="info-item">
                        <strong>Loading...</strong>
                    </div>
                </div>
            </div>
            
            <!-- SMTP Connection Test -->
            <div class="section">
                <h2>🔌 SMTP Connection Test</h2>
                <div class="smtp-test" id="smtpTest">
                    <div class="smtp-test-item">
                        <h3>Port 587 (TLS)</h3>
                        <p id="smtp587">Testing...</p>
                    </div>
                    <div class="smtp-test-item">
                        <h3>Port 465 (SSL)</h3>
                        <p id="smtp465">Testing...</p>
                    </div>
                </div>
            </div>
            
            <!-- Email Send Test -->
            <div class="section">
                <h2>📤 Email Send Test</h2>
                <form id="testForm">
                    <div class="form-group">
                        <label for="email">Email ผู้รับ:</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="std.nk36116@gmail.com"
                            placeholder="your-email@example.com"
                            required
                        >
                    </div>
                    <button type="submit" class="btn" id="sendBtn">
                        <span>🚀 ส่งอีเมลทดสอบ</span>
                    </button>
                </form>
                
                <div class="log-container" id="logContainer"></div>
            </div>
            
            <div class="warning">
                <strong>⚠️ คำเตือน:</strong> ไฟล์นี้ใช้สำหรับทดสอบเท่านั้น กรุณาลบออกหลังจากแก้ไขปัญหาเสร็จแล้ว
            </div>
        </div>
    </div>
    
    <script>
        // Load System Info
        async function loadSystemInfo() {
            try {
                const response = await fetch('?info=1');
                const info = await response.json();
                
                const container = document.getElementById('systemInfo');
                container.innerHTML = `
                    <div class="info-item">
                        <strong>PHP Version</strong>
                        <span>${info.php_version}</span>
                    </div>
                    <div class="info-item">
                        <strong>OpenSSL</strong>
                        <span class="status-badge ${info.openssl_loaded ? 'status-success' : 'status-error'}">
                            ${info.openssl_loaded ? '✅ Loaded' : '❌ Not Loaded'}
                        </span>
                        ${info.openssl_loaded ? `<br><small>${info.openssl_version}</small>` : ''}
                    </div>
                    <div class="info-item">
                        <strong>Vendor Autoload</strong>
                        <span class="status-badge ${info.vendor_exists ? 'status-success' : 'status-error'}">
                            ${info.vendor_exists ? '✅ Found' : '❌ Not Found'}
                        </span>
                    </div>
                    <div class="info-item">
                        <strong>PHPMailer</strong>
                        <span class="status-badge ${info.phpmailer_exists ? 'status-success' : 'status-error'}">
                            ${info.phpmailer_exists ? '✅ Available' : '❌ Not Available'}
                        </span>
                    </div>
                `;
            } catch (error) {
                console.error('Error loading system info:', error);
            }
        }
        
        // Test SMTP Connection
        async function testSMTP(port) {
            const element = document.getElementById(`smtp${port}`);
            const container = element.closest('.smtp-test-item');
            
            container.className = 'smtp-test-item testing';
            element.innerHTML = '<div class="spinner"></div> Testing...';
            
            try {
                const response = await fetch(`?test_smtp=1&port=${port}`);
                const result = await response.json();
                
                if (result.success) {
                    container.className = 'smtp-test-item success';
                    element.innerHTML = `✅ Connected<br><small>${result.time}</small>`;
                } else {
                    container.className = 'smtp-test-item failed';
                    element.innerHTML = `❌ Failed<br><small>${result.error}</small>`;
                }
            } catch (error) {
                container.className = 'smtp-test-item failed';
                element.innerHTML = `❌ Error<br><small>${error.message}</small>`;
            }
        }
        
        // Add log entry
        function addLog(type, message) {
            const logContainer = document.getElementById('logContainer');
            logContainer.classList.add('active');
            
            const entry = document.createElement('div');
            entry.className = `log-entry log-${type}`;
            entry.textContent = `${new Date().toLocaleTimeString()} | ${message}`;
            
            logContainer.appendChild(entry);
            logContainer.scrollTop = logContainer.scrollHeight;
        }
        
        // Clear logs
        function clearLogs() {
            const logContainer = document.getElementById('logContainer');
            logContainer.innerHTML = '';
        }
        
        // Handle form submission
        document.getElementById('testForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const btn = document.getElementById('sendBtn');
            const email = document.getElementById('email').value;
            
            btn.disabled = true;
            btn.innerHTML = '<div class="spinner"></div> <span>กำลังส่ง...</span>';
            
            clearLogs();
            
            try {
                const formData = new FormData();
                formData.append('action', 'test_send');
                formData.append('email', email);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                // Display logs
                result.logs.forEach(log => {
                    addLog(log.type, log.message);
                });
                
                if (result.success) {
                    btn.innerHTML = '✅ <span>ส่งสำเร็จ!</span>';
                    setTimeout(() => {
                        btn.innerHTML = '🚀 <span>ส่งอีเมลทดสอบ</span>';
                        btn.disabled = false;
                    }, 3000);
                } else {
                    btn.innerHTML = '❌ <span>ส่งไม่สำเร็จ</span>';
                    setTimeout(() => {
                        btn.innerHTML = '🚀 <span>ส่งอีเมลทดสอบ</span>';
                        btn.disabled = false;
                    }, 3000);
                }
                
            } catch (error) {
                addLog('error', `เกิดข้อผิดพลาด: ${error.message}`);
                btn.innerHTML = '❌ <span>เกิดข้อผิดพลาด</span>';
                setTimeout(() => {
                    btn.innerHTML = '🚀 <span>ส่งอีเมลทดสอบ</span>';
                    btn.disabled = false;
                }, 3000);
            }
        });
        
        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            loadSystemInfo();
            testSMTP(587);
            testSMTP(465);
        });
    </script>
</body>
</html>