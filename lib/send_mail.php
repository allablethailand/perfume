<?php
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require  __DIR__ . '/../vendor/phpmailer/PHPMailer/src/Exception.php';
require  __DIR__ . '/../vendor/phpmailer/PHPMailer/src/PHPMailer.php';
require  __DIR__ . '/../vendor/phpmailer/PHPMailer/src/SMTP.php';

require_once(__DIR__ . '/../lib/base_directory.php');

/**
 * ส่งอีเมล OTP หรือข้อความอื่นๆ
 * 
 * @param string $to อีเมลผู้รับ
 * @param string $type_mes ประเภทข้อความ (register, forgot, new_password)
 * @param int $id ID ของผู้ใช้
 * @param string $otp รหัส OTP
 * @return bool สำเร็จ = true, ล้มเหลว = false
 */
function sendEmail($to, $type_mes, $id, $otp)
{
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->SMTPAuth   = true;
        $mail->SMTPDebug  = 0; // 0 = ปิด, 1 = errors only, 2 = full debug
        
        // ตั้งค่า SMTP
        $mail->Host       = 'smtp.gmail.com';
        $mail->Username   = 'apisit@origami.life';
        $mail->Password   = 'lswx qgcg iicc ykiv'; // App Password จาก Google
        
        // ลอง SSL (port 465) ก่อน
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        
        // เพิ่ม timeout และ options
        $mail->Timeout    = 30; // 30 seconds
        $mail->SMTPKeepAlive = true;
        
        // ถ้า SSL ไม่ผ่าน ให้ลอง TLS (uncomment บรรทัดด้านล่าง)
        // $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        // $mail->Port       = 587;
        
        // สำหรับ production ที่มีปัญหา SSL verification
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Recipients
        $mail->setFrom('std.nk36116@gmail.com', 'PERFUME');
        $mail->addAddress($to);
        $mail->addReplyTo('std.nk36116@gmail.com', 'PERFUME Support');

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = messageSubject($type_mes);
        $mail->Body    = messageBody($type_mes, $id, $otp);
        $mail->AltBody = strip_tags(messageBody($type_mes, $id, $otp)); // Plain text version

        // ส่งอีเมล
        $mail->send();
        
        error_log("✅ Email sent successfully to: " . $to . " (Type: " . $type_mes . ")");
        return true;
        
    } catch (Exception $e) {
        $errorMsg = "❌ Mail Error to {$to}: {$mail->ErrorInfo}";
        error_log($errorMsg);
        error_log("Exception Message: " . $e->getMessage());
        error_log("Exception Code: " . $e->getCode());
        error_log("Exception File: " . $e->getFile() . " (Line: " . $e->getLine() . ")");
        error_log("Stack Trace: " . $e->getTraceAsString());
        
        return false;
    }
}

/**
 * ส่ง SMS OTP
 * 
 * @param string $phone หมายเลขโทรศัพท์ (รวม country code)
 * @param string $otp รหัส OTP
 * @return bool สำเร็จ = true, ล้มเหลว = false
 */
function sendSMS($phone, $otp)
{
    // ========================================
    // Option 1: ใช้ Twilio (ต้อง install twilio/sdk ก่อน)
    // ========================================
    /*
    try {
        require_once __DIR__ . '/../vendor/autoload.php';
        
        $sid = "your_twilio_account_sid";
        $token = "your_twilio_auth_token";
        $twilioPhone = "your_twilio_phone_number";
        
        $client = new \Twilio\Rest\Client($sid, $token);
        
        $message = $client->messages->create(
            $phone,
            [
                "from" => $twilioPhone,
                "body" => "Your PERFUME verification code is: " . $otp . ". Valid for 10 minutes."
            ]
        );
        
        error_log("✅ SMS sent successfully to: " . $phone . " (SID: " . $message->sid . ")");
        return true;
        
    } catch (Exception $e) {
        error_log("❌ SMS Error to {$phone}: " . $e->getMessage());
        return false;
    }
    */
    
    // ========================================
    // Option 2: ใช้ Thai SMS Gateway (เช่น ThaiBulkSMS)
    // ========================================
    /*
    try {
        $apiKey = "your_api_key";
        $sender = "PERFUME";
        $message = "Your PERFUME verification code is: " . $otp . ". Valid for 10 minutes.";
        
        $url = "https://api.thaibulksms.com/sms";
        $data = [
            'apikey' => $apiKey,
            'sender' => $sender,
            'msisdn' => $phone,
            'message' => $message
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            error_log("✅ SMS sent successfully to: " . $phone);
            return true;
        } else {
            error_log("❌ SMS Error to {$phone}: HTTP {$httpCode} - {$response}");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("❌ SMS Exception to {$phone}: " . $e->getMessage());
        return false;
    }
    */
    
    // ========================================
    // Option 3: ใช้ AWS SNS
    // ========================================
    /*
    try {
        require_once __DIR__ . '/../vendor/autoload.php';
        
        $sns = new \Aws\Sns\SnsClient([
            'version' => 'latest',
            'region'  => 'ap-southeast-1',
            'credentials' => [
                'key'    => 'your_aws_access_key',
                'secret' => 'your_aws_secret_key',
            ]
        ]);
        
        $message = "Your PERFUME verification code is: " . $otp . ". Valid for 10 minutes.";
        
        $result = $sns->publish([
            'Message' => $message,
            'PhoneNumber' => $phone,
        ]);
        
        error_log("✅ SMS sent successfully to: " . $phone . " (MessageId: " . $result['MessageId'] . ")");
        return true;
        
    } catch (Exception $e) {
        error_log("❌ SMS Error to {$phone}: " . $e->getMessage());
        return false;
    }
    */
    
    // ========================================
    // Temporary: Log เท่านั้น (สำหรับทดสอบ)
    // ========================================
    error_log("📱 SMS Mock: Send to {$phone}, OTP: {$otp}");
    error_log("⚠️ SMS feature is not configured. Please set up SMS gateway in send_mail.php");
    
    // Return true เพื่อไม่ให้บล็อกการลงทะเบียน
    // เปลี่ยนเป็น false ถ้าต้องการให้ระบบแจ้งเตือนเมื่อส่ง SMS ไม่สำเร็จ
    return true;
}

/**
 * สร้าง Subject สำหรับอีเมล
 */
function messageSubject($subject)
{
    $HTMLsj = '';

    switch ($subject) {
        case 'register':
            $HTMLsj = 'ยืนยันการสมัครสมาชิก - PERFUME';
            break;
        case 'forgot':
            $HTMLsj = 'รีเซ็ตรหัสผ่าน - PERFUME';
            break;
        case 'new_password':
            $HTMLsj = 'รหัสผ่านใหม่ของคุณ - PERFUME';
            break;
        default:
            $HTMLsj = 'แจ้งเตือนจาก PERFUME';
            break;
    }

    return $HTMLsj;
}

/**
 * สร้าง Body สำหรับอีเมล
 */
function messageBody($body, $id, $otp)
{
    global $base_path;
    
    $random_string = generateUrl(8);
    $type_tmp = '';
    $url = '';

    if ($body == 'register') {
        $type_tmp = 'register';
        $url = $base_path . '?otp_confirm&register&otpID=' . urlencode($id) . '&' . urlencode($random_string);
    } else if ($body == 'forgot') {
        $type_tmp = 'forgot';
        $url = $base_path . '?otp_confirm&forgot&otpID=' . urlencode($id) . '&' . urlencode($random_string);
    } else if ($body == 'new_password') {
        $type_tmp = 'new_password';
    }

    $HTMLbd = templateMail($url, $type_tmp, $otp);
    return $HTMLbd;
}

/**
 * สร้าง random string สำหรับ URL
 */
function generateUrl($length)
{
    $characters = '!@#$%^&*()_+1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * Template HTML สำหรับอีเมล
 */
function templateMail($url, $type_tmp, $otp)
{
    switch ($type_tmp) {
        case 'register':
            $mesMail = '<!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            background-color: #f9f9f9;
                            color: #333;
                            padding: 20px;
                            margin: 0;
                        }
                        .email-container {
                            background-color: #fff;
                            border: 1px solid #ddd;
                            padding: 40px;
                            border-radius: 8px;
                            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                            max-width: 600px;
                            margin: 0 auto;
                        }
                        .logo {
                            text-align: center;
                            font-size: 32px;
                            font-weight: bold;
                            margin-bottom: 30px;
                            color: #000;
                        }
                        .email-container h2 {
                            color: #000;
                            margin-bottom: 20px;
                        }
                        .email-container p {
                            font-size: 16px;
                            line-height: 1.6;
                            margin-bottom: 20px;
                        }
                        .otp-code {
                            background-color: #f5f5f5;
                            border: 2px dashed #ff9800;
                            padding: 20px;
                            text-align: center;
                            border-radius: 8px;
                            margin: 30px 0;
                        }
                        .otp-code h1 {
                            color: #ff9800;
                            font-size: 48px;
                            margin: 0;
                            letter-spacing: 10px;
                        }
                        .btn {
                            display: inline-block;
                            margin-top: 20px;
                            padding: 15px 40px;
                            background-color: #000;
                            color: #fff !important;
                            text-decoration: none;
                            border-radius: 30px;
                            font-weight: bold;
                            transition: all 0.3s ease;
                        }
                        .btn:hover {
                            background-color: #333;
                        }
                        .footer {
                            margin-top: 40px;
                            padding-top: 20px;
                            border-top: 1px solid #ddd;
                            text-align: center;
                            color: #666;
                            font-size: 14px;
                        }
                    </style>
                </head>
                <body>
                    <div class="email-container">
                        <div class="logo">PERFUME</div>
                        <h2>ยืนยันการสมัครสมาชิก</h2>
                        <p>ขอบคุณที่สมัครสมาชิกกับเรา!</p>
                        <p>กรุณาใช้รหัส OTP ด้านล่างเพื่อยืนยันบัญชีของคุณ:</p>
                        
                        <div class="otp-code">
                            <h1>' . $otp . '</h1>
                            <p style="margin: 10px 0 0 0; color: #666;">รหัสนี้จะหมดอายุใน 10 นาที</p>
                        </div>
                        
                        <div style="text-align: center;">
                            <a href="' . $url . '" class="btn">ยืนยันบัญชี</a>
                        </div>
                        
                        <div class="footer">
                            <p>หากคุณไม่ได้สมัครสมาชิก กรุณาเพิกเฉยอีเมลนี้</p>
                            <p><strong>PERFUME</strong> - Your Signature Scent</p>
                        </div>
                    </div>
                </body>
            </html>';
            return $mesMail;
            break;
            
        case 'forgot':
            $mesMail = '<!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            background-color: #f9f9f9;
                            color: #333;
                            padding: 20px;
                            margin: 0;
                        }
                        .email-container {
                            background-color: #fff;
                            border: 1px solid #ddd;
                            padding: 40px;
                            border-radius: 8px;
                            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                            max-width: 600px;
                            margin: 0 auto;
                        }
                        .logo {
                            text-align: center;
                            font-size: 32px;
                            font-weight: bold;
                            margin-bottom: 30px;
                            color: #000;
                        }
                        .otp-code {
                            background-color: #f5f5f5;
                            border: 2px dashed #ff9800;
                            padding: 20px;
                            text-align: center;
                            border-radius: 8px;
                            margin: 30px 0;
                        }
                        .otp-code h1 {
                            color: #ff9800;
                            font-size: 48px;
                            margin: 0;
                            letter-spacing: 10px;
                        }
                        .btn {
                            display: inline-block;
                            margin-top: 20px;
                            padding: 15px 40px;
                            background-color: #000;
                            color: #fff !important;
                            text-decoration: none;
                            border-radius: 30px;
                            font-weight: bold;
                        }
                        .footer {
                            margin-top: 40px;
                            padding-top: 20px;
                            border-top: 1px solid #ddd;
                            text-align: center;
                            color: #666;
                            font-size: 14px;
                        }
                    </style>
                </head>
                <body>
                    <div class="email-container">
                        <div class="logo">PERFUME</div>
                        <h2>รีเซ็ตรหัสผ่าน</h2>
                        <p>คุณได้ขอรีเซ็ตรหัสผ่าน กรุณาใช้รหัส OTP ด้านล่าง:</p>
                        
                        <div class="otp-code">
                            <h1>' . $otp . '</h1>
                            <p style="margin: 10px 0 0 0; color: #666;">รหัสนี้จะหมดอายุใน 10 นาที</p>
                        </div>
                        
                        <div style="text-align: center;">
                            <a href="' . $url . '" class="btn">รีเซ็ตรหัสผ่าน</a>
                        </div>
                        
                        <div class="footer">
                            <p>หากคุณไม่ได้ขอรีเซ็ตรหัสผ่าน กรุณาเพิกเฉยอีเมลนี้</p>
                            <p><strong>PERFUME</strong></p>
                        </div>
                    </div>
                </body>
            </html>';
            return $mesMail;
            break;
            
        case 'new_password':
            $mesMail = '<!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            background-color: #f9f9f9;
                            color: #333;
                            padding: 20px;
                            margin: 0;
                        }
                        .email-container {
                            background-color: #fff;
                            border: 1px solid #ddd;
                            padding: 40px;
                            border-radius: 8px;
                            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                            max-width: 600px;
                            margin: 0 auto;
                        }
                        .logo {
                            text-align: center;
                            font-size: 32px;
                            font-weight: bold;
                            margin-bottom: 30px;
                            color: #000;
                        }
                        .password-box {
                            background-color: #f5f5f5;
                            border: 2px solid #4CAF50;
                            padding: 20px;
                            text-align: center;
                            border-radius: 8px;
                            margin: 30px 0;
                        }
                        .password-box h1 {
                            color: #4CAF50;
                            font-size: 32px;
                            margin: 0;
                            word-break: break-all;
                        }
                        .footer {
                            margin-top: 40px;
                            padding-top: 20px;
                            border-top: 1px solid #ddd;
                            text-align: center;
                            color: #666;
                            font-size: 14px;
                        }
                    </style>
                </head>
                <body>
                    <div class="email-container">
                        <div class="logo">PERFUME</div>
                        <h2>รหัสผ่านใหม่ของคุณ</h2>
                        <p>รหัสผ่านของคุณถูกรีเซ็ตแล้ว กรุณาใช้รหัสผ่านด้านล่างเพื่อเข้าสู่ระบบ:</p>
                        
                        <div class="password-box">
                            <h1>' . $otp . '</h1>
                        </div>
                        
                        <p style="color: #ff5722; font-weight: bold;">⚠️ กรุณาเปลี่ยนรหัสผ่านหลังจากเข้าสู่ระบบ</p>
                        
                        <div class="footer">
                            <p><strong>PERFUME</strong></p>
                        </div>
                    </div>
                </body>
            </html>';
            return $mesMail;
            break;
            
        default:
            $mesMail = '<!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        .container { max-width: 600px; margin: 0 auto; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <h2>PERFUME</h2>
                        <p>ข้อความจากระบบ</p>
                    </div>
                </body>
            </html>';
            break;
    }
    
    return $mesMail;
}
?>