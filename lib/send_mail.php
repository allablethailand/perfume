<?php
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require  __DIR__ . '/../vendor/phpmailer/PHPMailer/src/Exception.php';
require  __DIR__ . '/../vendor/phpmailer/PHPMailer/src/PHPMailer.php';
require  __DIR__ . '/../vendor/phpmailer/PHPMailer/src/SMTP.php';

require_once(__DIR__ . '/../lib/base_directory.php');

function sendEmail($to, $type_mes, $id, $otp)
{
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();
        $mail->SMTPAuth   = true;

        $mail->Host       = 'smtp.gmail.com';
        $mail->Username   = 'apisit@origami.life'; // ใส่อีเมลของคุณ
        $mail->Password   = 'lswx qgcg iicc ykiv';     // ใส่ App Password

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        //Recipients
        $mail->setFrom('apisit@origami.life', 'PERFUME');
        $mail->addAddress($to);

        //Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = messageSubject($type_mes);
        $mail->Body    = messageBody($type_mes, $id, $otp);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail Error: {$mail->ErrorInfo}");
        return false;
    }
}

function sendSMS($phone, $otp)
{
    // สำหรับส่ง SMS ใช้ API ของผู้ให้บริการ SMS Gateway
    // ตัวอย่าง: Twilio, AWS SNS, หรือผู้ให้บริการในไทยอย่าง ThaiBulkSMS
    
    // ตัวอย่างการส่งด้วย Twilio (ต้อง install twilio/sdk ก่อน)
    /*
    try {
        $sid = "your_twilio_sid";
        $token = "your_twilio_token";
        $twilio = new \Twilio\Rest\Client($sid, $token);
        
        $message = $twilio->messages->create(
            $phone,
            [
                "from" => "your_twilio_phone",
                "body" => "Your PERFUME verification code is: " . $otp
            ]
        );
        
        return true;
    } catch (Exception $e) {
        error_log("SMS Error: " . $e->getMessage());
        return false;
    }
    */
    
    // สำหรับตอนนี้ให้ log ไว้ก่อน
    error_log("SMS to {$phone}: OTP = {$otp}");
    return true; // สมมติว่าส่งสำเร็จ
}

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
            break;
    }

    return $HTMLsj;
}

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

function templateMail($url, $type_tmp, $otp)
{
    switch ($type_tmp) {
        case 'register':
            $mesMail = '<!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            background-color: #f9f9f9;
                            color: #333;
                            padding: 20px;
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
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            background-color: #f9f9f9;
                            color: #333;
                            padding: 20px;
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
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            background-color: #f9f9f9;
                            color: #333;
                            padding: 20px;
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
            $mesMail = '';
            break;
    }
}
?>