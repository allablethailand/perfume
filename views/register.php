<?php
require_once('lib/connect.php');
global $conn;

// Start session for language handling
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Language handling
$lang = 'th';
if (isset($_GET['lang'])) {
    $supportedLangs = ['th', 'en', 'cn', 'jp', 'kr'];
    if (in_array($_GET['lang'], $supportedLangs)) {
        $_SESSION['lang'] = $_GET['lang'];
        $lang = $_GET['lang'];
    }
} elseif (isset($_SESSION['lang'])) {
    $lang = $_SESSION['lang'];
}

// Translation arrays for register page
$translations = [
    'register_title' => [
        'th' => 'สมัครสมาชิก',
        'en' => 'Register',
        'cn' => '注册',
        'jp' => '登録',
        'kr' => '회원가입'
    ],
    'register_subtitle' => [
        'th' => 'กรุณากรอกข้อมูลให้ครบถ้วน',
        'en' => 'Please fill in the information',
        'cn' => '请填写完整信息',
        'jp' => '情報を入力してください',
        'kr' => '정보를 입력해주세요'
    ],
    'first_name' => [
        'th' => 'ชื่อ',
        'en' => 'First name',
        'cn' => '名字',
        'jp' => '名前',
        'kr' => '이름'
    ],
    'last_name' => [
        'th' => 'นามสกุล',
        'en' => 'Last name',
        'cn' => '姓氏',
        'jp' => '苗字',
        'kr' => '성'
    ],
    'email' => [
        'th' => 'อีเมล',
        'en' => 'Email',
        'cn' => '电子邮件',
        'jp' => 'メール',
        'kr' => '이메일'
    ],
    'phone' => [
        'th' => 'เบอร์โทรศัพท์ (9 หลัก)',
        'en' => 'Phone (9 digits)',
        'cn' => '电话号码 (9位)',
        'jp' => '電話番号 (9桁)',
        'kr' => '전화번호 (9자리)'
    ],
    'login_method' => [
        'th' => 'เลือกวิธีเข้าสู่ระบบ',
        'en' => 'Select Login Method',
        'cn' => '选择登录方式',
        'jp' => 'ログイン方法を選択',
        'kr' => '로그인 방법 선택'
    ],
    'login_with_email' => [
        'th' => 'เข้าสู่ระบบด้วยอีเมล',
        'en' => 'Login with Email',
        'cn' => '使用电子邮件登录',
        'jp' => 'メールでログイン',
        'kr' => '이메일로 로그인'
    ],
    'login_with_phone' => [
        'th' => 'เข้าสู่ระบบด้วยเบอร์โทร',
        'en' => 'Login with Phone',
        'cn' => '使用电话登录',
        'jp' => '電話でログイン',
        'kr' => '전화번호로 로그인'
    ],
    'password' => [
        'th' => 'รหัสผ่าน',
        'en' => 'Password',
        'cn' => '密码',
        'jp' => 'パスワード',
        'kr' => '비밀번호'
    ],
    'confirm_password' => [
        'th' => 'ยืนยันรหัสผ่าน',
        'en' => 'Confirm password',
        'cn' => '确认密码',
        'jp' => 'パスワード確認',
        'kr' => '비밀번호 확인'
    ],
    'password_requirements' => [
        'th' => 'ข้อกำหนดรหัสผ่าน:',
        'en' => 'Password requirements:',
        'cn' => '密码要求：',
        'jp' => 'パスワード要件：',
        'kr' => '비밀번호 요구사항:'
    ],
    'min_length' => [
        'th' => 'ความยาวขั้นต่ำ: 8 ตัวอักษร',
        'en' => 'Minimum length: 8 characters',
        'cn' => '最小长度：8 个字符',
        'jp' => '最小長：8 文字',
        'kr' => '최소 길이: 8자'
    ],
    'uppercase' => [
        'th' => 'ต้องมีตัวพิมพ์ใหญ่อย่างน้อย 1 ตัว (A-Z)',
        'en' => 'At least one uppercase letter (A-Z)',
        'cn' => '至少一个大写字母 (A-Z)',
        'jp' => '大文字が少なくとも 1 つ (A-Z)',
        'kr' => '최소 1개의 대문자 (A-Z)'
    ],
    'lowercase' => [
        'th' => 'ต้องมีตัวพิมพ์เล็กอย่างน้อย 1 ตัว (a-z)',
        'en' => 'At least one lowercase letter (a-z)',
        'cn' => '至少一个小写字母 (a-z)',
        'jp' => '小文字が少なくとも 1 つ (a-z)',
        'kr' => '최소 1개의 소문자 (a-z)'
    ],
    'digit' => [
        'th' => 'ต้องมีตัวเลขอย่างน้อย 1 ตัว (0-9)',
        'en' => 'At least one digit (0-9)',
        'cn' => '至少一个数字 (0-9)',
        'jp' => '数字が少なくとも 1 つ (0-9)',
        'kr' => '최소 1개의 숫자 (0-9)'
    ],
    'special_char' => [
        'th' => 'ต้องมีอักขระพิเศษอย่างน้อย 1 ตัว (!@#_)',
        'en' => 'At least one special character (!@#_)',
        'cn' => '至少一个特殊字符 (!@#_)',
        'jp' => '特殊文字が少なくとも 1 つ (!@#_)',
        'kr' => '최소 1개의 특수문자 (!@#_)'
    ],
    'privacy_title' => [
        'th' => 'ฉันได้อ่านและรับทราบนโยบายความเป็นส่วนตัวสำหรับการสมัครสมาชิกเพื่อซื้อสินค้า',
        'en' => 'I have read and acknowledged the privacy policy for membership registration',
        'cn' => '我已阅读并确认会员注册的隐私政策',
        'jp' => '会員登録のプライバシーポリシーを読み、確認しました',
        'kr' => '회원 가입을 위한 개인정보 보호정책을 읽고 확인했습니다'
    ],
    'privacy_text1' => [
        'th' => 'ฉันยินยอมให้ใช้หรือเปิดเผยข้อมูลส่วนบุคคลของฉันแก่บริษัทเพื่อวัตถุประสงค์ในการดำเนินการซื้อสินค้า การจัดส่งหรือให้บริการที่เกี่ยวข้อง รวมถึงการแจ้งข่าวสาร โปรโมชั่น หรือข้อมูลทางการตลาดจากบริษัท',
        'en' => 'I consent to the use or disclosure of my personal information for purchase processing, shipping, and related services, including notifications, promotions, and marketing information.',
        'cn' => '我同意使用或披露我的个人信息用于购买处理、运输和相关服务，包括通知、促销和营销信息。',
        'jp' => '購入処理、配送、関連サービス、通知、プロモーション、マーケティング情報のために個人情報の使用または開示に同意します。',
        'kr' => '구매 처리, 배송 및 관련 서비스, 알림, 프로모션 및 마케팅 정보를 위해 개인정보 사용 또는 공개에 동의합니다.'
    ],
    'privacy_text2' => [
        'th' => 'ในการนี้ ฉันทราบว่าฉันสามารถเพิกถอนความยินยอมนี้ได้ตลอดเวลา',
        'en' => 'I understand that I can revoke this consent at any time.',
        'cn' => '我知道我可以随时撤销此同意。',
        'jp' => 'この同意はいつでも取り消すことができることを理解しています。',
        'kr' => '언제든지 이 동의를 철회할 수 있음을 이해합니다.'
    ],
    'privacy_text3' => [
        'th' => 'ฉันเข้าใจข้อกำหนดและเงื่อนไข รวมถึงเข้าใจเกี่ยวกับการเก็บรวบรวม ใช้ และเปิดเผยข้อมูลส่วนบุคคลที่เกี่ยวข้องกับการสมัครสมาชิกและการซื้อสินค้า',
        'en' => 'I understand the terms and conditions regarding the collection, use, and disclosure of personal information related to membership and purchases.',
        'cn' => '我理解有关会员资格和购买相关个人信息收集、使用和披露的条款和条件。',
        'jp' => '会員資格と購入に関連する個人情報の収集、使用、開示に関する利用規約を理解しています。',
        'kr' => '회원 가입 및 구매와 관련된 개인정보 수집, 사용 및 공개에 관한 이용약관을 이해합니다.'
    ],
    'agree' => [
        'th' => 'ยอมรับเงื่อนไขและนโยบายความเป็นส่วนตัว',
        'en' => 'I agree to terms and privacy policy',
        'cn' => '我同意条款和隐私政策',
        'jp' => '利用規約とプライバシーポリシーに同意します',
        'kr' => '이용약관 및 개인정보 보호정책에 동의합니다'
    ],
    'confirm_btn' => [
        'th' => 'ยืนยัน',
        'en' => 'Confirm',
        'cn' => '确认',
        'jp' => '確認',
        'kr' => '확인'
    ],
    'have_account' => [
        'th' => 'มีบัญชีอยู่แล้ว?',
        'en' => 'Already have an account?',
        'cn' => '已有账户？',
        'jp' => 'すでにアカウントをお持ちですか？',
        'kr' => '이미 계정이 있으신가요?'
    ],
    'login' => [
        'th' => 'เข้าสู่ระบบ',
        'en' => 'Login',
        'cn' => '登录',
        'jp' => 'ログイン',
        'kr' => '로그인'
    ],
    'phone_hint' => [
        'th' => 'ตัวอย่าง: 812345678',
        'en' => 'Example: 812345678',
        'cn' => '示例：812345678',
        'jp' => '例：812345678',
        'kr' => '예: 812345678'
    ],
    'email_exists' => [
        'th' => 'อีเมลนี้ถูกใช้งานแล้ว',
        'en' => 'This email is already registered',
        'cn' => '此电子邮件已被注册',
        'jp' => 'このメールアドレスは既に登録されています',
        'kr' => '이 이메일은 이미 등록되어 있습니다'
    ],
    'phone_exists' => [
        'th' => 'เบอร์โทรศัพท์นี้ถูกใช้งานแล้ว',
        'en' => 'This phone number is already registered',
        'cn' => '此电话号码已被注册',
        'jp' => 'この電話番号は既に登録されています',
        'kr' => '이 전화번호는 이미 등록되어 있습니다'
    ]
];

// Helper function
function tt($key, $lang) {
    global $translations;
    return $translations[$key][$lang] ?? $translations[$key]['en'];
}

// Error message variable
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['signUp_name'] ?? '';
    $last_name = $_POST['signUp_surname'] ?? '';
    $email = $_POST['signUp_email'] ?? '';
    $country_code = $_POST['country_code'] ?? '+66';
    $phone = $_POST['signUp_phone'] ?? '';
    $password = $_POST['signUp_password'] ?? '';
    $consent = isset($_POST['signUp_agree']) ? 1 : 0;
    $login_method = $_POST['login_method'] ?? 'email';

    // Combine country code with phone number
    $full_phone = $country_code . $phone;

    // Check if email already exists
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM mb_user WHERE email = ? AND del = 0");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row['count'] > 0) {
        $error_message = tt('email_exists', $lang);
    } else {
        // Check if phone already exists
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM mb_user WHERE phone_number = ? AND del = 0");
        $stmt->bind_param("s", $full_phone);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row['count'] > 0) {
            $error_message = tt('phone_exists', $lang);
        } else {
            // No duplicates found, proceed with registration
            $conn->begin_transaction();

            try {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $verify = 1;
                $generate_otp = rand(100000, 999999);
                $confirm_email = 0;
                $del = 0;

                $sql = "INSERT INTO mb_user ( 
                    first_name, 
                    last_name, 
                    password, 
                    email, 
                    country_code, 
                    phone_number, 
                    login_method, 
                    consent, 
                    verify, 
                    generate_otp, 
                    email_verified, 
                    phone_verified,
                    confirm_email,
                    del,
                    date_create 
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, ?, ?, NOW())";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "sssssssiisii",
                    $first_name, 
                    $last_name, 
                    $hashedPassword, 
                    $email, 
                    $country_code, 
                    $full_phone, 
                    $login_method, 
                    $consent, 
                    $verify, 
                    $generate_otp,
                    $confirm_email,
                    $del
                );

                if (!$stmt->execute()) {
                    throw new Exception("Insert user error: " . $stmt->error);
                }

                $user_id = $conn->insert_id;
                $stmt->close();

                $sql_role = "INSERT INTO acc_user_roles (user_id, role_id) VALUES (?, 5)";
                $stmt_role = $conn->prepare($sql_role);
                $stmt_role->bind_param("i", $user_id);

                if (!$stmt_role->execute()) {
                    throw new Exception("Insert role error: " . $stmt_role->error);
                }
                $stmt_role->close();

                $conn->commit();

                // ส่ง OTP ตาม login_method ที่เลือก
                require_once(__DIR__ . '/../lib/send_mail.php');
                
                $otpSent = false;
                $errorDetail = '';

                try {
                    if ($login_method == 'email') {
                        // ส่ง OTP ทางอีเมล
                        error_log("🔵 Attempting to send email OTP to: " . $email);
                        $otpSent = sendEmail($email, 'register', $user_id, $generate_otp);
                        
                        if (!$otpSent) {
                            $errorDetail = "Failed to send email to: " . $email;
                            error_log("❌ " . $errorDetail);
                        } else {
                            error_log("✅ Email sent successfully to: " . $email);
                        }
                    } else {
                        // ส่ง OTP ทาง SMS
                        error_log("🔵 Attempting to send SMS OTP to: " . $full_phone);
                        $otpSent = sendSMS($full_phone, $generate_otp);
                        
                        if (!$otpSent) {
                            $errorDetail = "Failed to send SMS to: " . $full_phone;
                            error_log("❌ " . $errorDetail);
                        } else {
                            error_log("✅ SMS sent successfully to: " . $full_phone);
                        }
                    }
                } catch (Exception $e) {
                    $errorDetail = "OTP sending exception: " . $e->getMessage();
                    error_log("⚠️ " . $errorDetail);
                    error_log("Exception trace: " . $e->getTraceAsString());
                }

                // 🔥 Redirect ไปหน้า OTP แม้ว่าการส่ง OTP จะล้มเหลว
                // เพราะ OTP ถูกบันทึกไว้ในฐานข้อมูลแล้ว ผู้ใช้สามารถกรอกได้
                
                // เช็คว่ามี pending_ai_code จากการสแกน RFID หรือไม่
                if (isset($_SESSION['pending_ai_code']) && !empty($_SESSION['pending_ai_code'])) {
                    $ai_code = $_SESSION['pending_ai_code'];
                    $ai_lang = $_SESSION['pending_ai_lang'] ?? 'th';
                    
                    // ไม่ต้องลบออกจาก session ตอนนี้ จะลบหลังจาก OTP verify สำเร็จ
                    // Redirect ไปหน้า OTP พร้อมส่ง pending_ai parameter
                    header("Location: ?otp_confirm&register&otpID=" . $user_id . "&method=" . $login_method . "&lang=" . $lang . "&pending_ai=" . urlencode($ai_code) . "&pending_lang=" . urlencode($ai_lang));
                    exit;
                }

                // ไม่มี pending AI -> Redirect ไปหน้า OTP ปกติ
                error_log("🔄 Redirecting to OTP page for user ID: " . $user_id);
                header("Location: ?otp_confirm&register&otpID=" . $user_id . "&method=" . $login_method . "&lang=" . $lang);
                exit;

            } catch (Exception $e) {
                $conn->rollback();
                $error_message = "Error: " . $e->getMessage();
                error_log("💥 Registration error: " . $e->getMessage());
                error_log("Error trace: " . $e->getTraceAsString());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f8f8f8;
            color: #1a1a1a;
            line-height: 1.6;
        }

        .register-container {
            max-width: 800px;
            margin: 80px auto 80px;
            padding: 0 20px;
        }

        .register-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .register-header {
            text-align: center;
            padding: 50px 40px 30px;
            background: linear-gradient(135deg, #000 0%, #333 100%);
            color: #fff;
        }

        .register-header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }

        .register-header p {
            font-size: 15px;
            opacity: 0.9;
        }

        .register-body {
            padding: 40px;
        }

        .error-message {
            background: #ffe6e6;
            border: 2px solid #ff3d00;
            border-radius: 6px;
            padding: 15px 20px;
            margin-bottom: 20px;
            color: #c62828;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            animation: shake 0.3s ease-in-out;
        }

        .error-message i {
            margin-right: 10px;
            font-size: 18px;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
            color: #333;
        }

        .form-group label i {
            margin-right: 8px;
            color: #666;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .form-control:focus {
            outline: none;
            border-color: #000;
            background: #fff;
        }

        .form-control.error {
            border-color: #ff3d00;
            background: #ffe6e6;
        }

        .phone-input-group {
            display: flex;
            gap: 10px;
        }

        .country-code-select {
            width: 120px;
            padding: 12px 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 15px;
            background: #fafafa;
            cursor: pointer;
        }

        .phone-input {
            flex: 1;
        }

        .phone-hint {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }

        .login-method-box {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .login-method-box h4 {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 15px;
            color: #1a1a1a;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .radio-option {
            flex: 1;
            min-width: 200px;
        }

        .radio-wrapper {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #fff;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .radio-wrapper:hover {
            border-color: #000;
            background: #fafafa;
        }

        .radio-wrapper input[type="radio"] {
            margin-right: 12px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .radio-wrapper input[type="radio"]:checked + label {
            font-weight: 600;
        }

        .radio-wrapper.selected {
            border-color: #000;
            background: #f0f0f0;
        }

        .radio-wrapper label {
            font-size: 14px;
            color: #333;
            cursor: pointer;
            flex: 1;
            margin: 0;
        }

        .password-requirements {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #e0e0e0;
        }

        .password-requirements h4 {
            font-size: 14px;
            margin-bottom: 12px;
            color: #333;
        }

        .requirement-item {
            display: flex;
            align-items: center;
            padding: 6px 0;
            font-size: 13px;
            color: #666;
        }

        .requirement-item i {
            margin-right: 10px;
            font-size: 12px;
        }

        .requirement-item.valid {
            color: #4CAF50;
        }

        .requirement-item.valid i {
            color: #4CAF50;
        }

        .requirement-item.invalid {
            color: #ff3d00;
        }

        .requirement-item.invalid i {
            color: #ff3d00;
        }

        .consent-box {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 30px;
            margin: 30px 0;
        }

        .consent-box h3 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #1a1a1a;
            line-height: 1.5;
        }

        .consent-box p {
            font-size: 14px;
            color: #555;
            margin-bottom: 15px;
            line-height: 1.8;
        }

        .checkbox-group {
            margin: 20px 0;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            padding: 12px;
            background: #fff;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .checkbox-wrapper:hover {
            background: #f8f9fa;
        }

        .checkbox-wrapper input[type="checkbox"] {
            margin-right: 12px;
            margin-top: 4px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .checkbox-wrapper label {
            font-size: 14px;
            color: #333;
            cursor: pointer;
            font-weight: 500;
        }

        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #e0e0e0;
        }

        .login-link {
            font-size: 14px;
            color: #666;
        }

        .login-link a {
            color: #000;
            font-weight: 600;
            text-decoration: none;
            transition: opacity 0.3s ease;
        }

        .login-link a:hover {
            opacity: 0.7;
        }

        .btn-submit {
            padding: 14px 40px;
            background: #000;
            color: #fff;
            border: none;
            border-radius: 30px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
        }

        .btn-submit:hover {
            background: #333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .btn-submit:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        @media (max-width: 768px) {
            .register-container {
                margin: 40px auto;
            }

            .register-body {
                padding: 30px 20px;
            }

            .register-header {
                padding: 40px 20px 20px;
            }

            .register-header h1 {
                font-size: 24px;
            }

            .form-actions {
                flex-direction: column-reverse;
                gap: 20px;
            }

            .btn-submit {
                width: 100%;
            }

            .radio-group {
                flex-direction: column;
            }
        }

        @import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
    </style>
    <?php include 'template/header.php'; ?>
</head>
<body>

    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h1><?= tt('register_title', $lang) ?></h1>
                <p><?= tt('register_subtitle', $lang) ?></p>
            </div>

            <div class="register-body">
                <?php if (!empty($error_message)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= $error_message ?></span>
                    </div>
                <?php endif; ?>

                <form id="personal_register" method="post">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="signUp_name">
                                <i class="fas fa-user"></i>
                                <?= tt('first_name', $lang) ?>
                            </label>
                            <input id="signUp_name" name="signUp_name" type="text" class="form-control" 
                                   value="<?= isset($_POST['signUp_name']) ? htmlspecialchars($_POST['signUp_name']) : '' ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="signUp_surname">
                                <?= tt('last_name', $lang) ?>
                            </label>
                            <input id="signUp_surname" name="signUp_surname" type="text" class="form-control" 
                                   value="<?= isset($_POST['signUp_surname']) ? htmlspecialchars($_POST['signUp_surname']) : '' ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="signUp_email">
                                <i class="fas fa-envelope"></i>
                                <?= tt('email', $lang) ?>
                            </label>
                            <input id="signUp_email" name="signUp_email" type="email" 
                                   class="form-control <?= (!empty($error_message) && strpos($error_message, tt('email_exists', $lang)) !== false) ? 'error' : '' ?>" 
                                   value="<?= isset($_POST['signUp_email']) ? htmlspecialchars($_POST['signUp_email']) : '' ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="signUp_phone">
                                <i class="fas fa-phone-volume"></i>
                                <?= tt('phone', $lang) ?>
                            </label>
                            <div class="phone-input-group">
                                <select id="country_code" name="country_code" class="country-code-select">
                                    <option value="+66" <?= (isset($_POST['country_code']) && $_POST['country_code'] == '+66') ? 'selected' : 'selected' ?>>🇹🇭 +66</option>
                                    <option value="+1" <?= (isset($_POST['country_code']) && $_POST['country_code'] == '+1') ? 'selected' : '' ?>>🇺🇸 +1</option>
                                    <option value="+44" <?= (isset($_POST['country_code']) && $_POST['country_code'] == '+44') ? 'selected' : '' ?>>🇬🇧 +44</option>
                                    <option value="+81" <?= (isset($_POST['country_code']) && $_POST['country_code'] == '+81') ? 'selected' : '' ?>>🇯🇵 +81</option>
                                    <option value="+82" <?= (isset($_POST['country_code']) && $_POST['country_code'] == '+82') ? 'selected' : '' ?>>🇰🇷 +82</option>
                                    <option value="+86" <?= (isset($_POST['country_code']) && $_POST['country_code'] == '+86') ? 'selected' : '' ?>>🇨🇳 +86</option>
                                    <option value="+65" <?= (isset($_POST['country_code']) && $_POST['country_code'] == '+65') ? 'selected' : '' ?>>🇸🇬 +65</option>
                                    <option value="+60" <?= (isset($_POST['country_code']) && $_POST['country_code'] == '+60') ? 'selected' : '' ?>>🇲🇾 +60</option>
                                    <option value="+84" <?= (isset($_POST['country_code']) && $_POST['country_code'] == '+84') ? 'selected' : '' ?>>🇻🇳 +84</option>
                                    <option value="+62" <?= (isset($_POST['country_code']) && $_POST['country_code'] == '+62') ? 'selected' : '' ?>>🇮🇩 +62</option>
                                    <option value="+63" <?= (isset($_POST['country_code']) && $_POST['country_code'] == '+63') ? 'selected' : '' ?>>🇵🇭 +63</option>
                                </select>
                                <input id="signUp_phone" name="signUp_phone" type="tel" 
                                       class="form-control phone-input <?= (!empty($error_message) && strpos($error_message, tt('phone_exists', $lang)) !== false) ? 'error' : '' ?>" 
                                       pattern="[1-9][0-9]{8}" maxlength="9" placeholder="812345678" 
                                       value="<?= isset($_POST['signUp_phone']) ? htmlspecialchars($_POST['signUp_phone']) : '' ?>" required>
                            </div>
                            <div class="phone-hint"><?= tt('phone_hint', $lang) ?></div>
                        </div>
                    </div>

                    <!-- Login Method Selection -->
                    <div class="login-method-box">
                        <h4>
                            <i class="fas fa-key"></i>
                            <?= tt('login_method', $lang) ?>
                        </h4>
                        <div class="radio-group">
                            <div class="radio-option">
                                <div class="radio-wrapper" data-method="email">
                                    <input type="radio" id="method_email" name="login_method" value="email" 
                                           <?= (!isset($_POST['login_method']) || $_POST['login_method'] == 'email') ? 'checked' : '' ?>>
                                    <label for="method_email">
                                        <i class="fas fa-envelope"></i>
                                        <?= tt('login_with_email', $lang) ?>
                                    </label>
                                </div>
                            </div>
                            <div class="radio-option">
                                <div class="radio-wrapper" data-method="phone">
                                    <input type="radio" id="method_phone" name="login_method" value="phone"
                                           <?= (isset($_POST['login_method']) && $_POST['login_method'] == 'phone') ? 'checked' : '' ?>>
                                    <label for="method_phone">
                                        <i class="fas fa-mobile-alt"></i>
                                        <?= tt('login_with_phone', $lang) ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="signUp_password">
                                <i class="fas fa-lock"></i>
                                <?= tt('password', $lang) ?>
                            </label>
                            <input id="signUp_password" name="signUp_password" type="password" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="signUp_confirm_password">
                                <?= tt('confirm_password', $lang) ?>
                            </label>
                            <input id="signUp_confirm_password" name="signUp_confirm_password" type="password" class="form-control" disabled required>
                        </div>
                    </div>

                    <div class="password-requirements">
                        <h4><?= tt('password_requirements', $lang) ?></h4>
                        <div class="requirement-item invalid" id="password_length">
                            <i class="fas fa-times"></i>
                            <span><?= tt('min_length', $lang) ?></span>
                        </div>
                        <div class="requirement-item invalid" id="password_upper">
                            <i class="fas fa-times"></i>
                            <span><?= tt('uppercase', $lang) ?></span>
                        </div>
                        <div class="requirement-item invalid" id="password_lower">
                            <i class="fas fa-times"></i>
                            <span><?= tt('lowercase', $lang) ?></span>
                        </div>
                        <div class="requirement-item invalid" id="password_number">
                            <i class="fas fa-times"></i>
                            <span><?= tt('digit', $lang) ?></span>
                        </div>
                        <div class="requirement-item invalid" id="password_special">
                            <i class="fas fa-times"></i>
                            <span><?= tt('special_char', $lang) ?></span>
                        </div>
                    </div>

                    <div class="consent-box">
                        <h3><?= tt('privacy_title', $lang) ?></h3>
                        <p><?= tt('privacy_text1', $lang) ?></p>
                        <p><?= tt('privacy_text2', $lang) ?></p>
                        <p><?= tt('privacy_text3', $lang) ?></p>
                    </div>

                    <div class="checkbox-group">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="signUp_agree" name="signUp_agree" value="1" required>
                            <label for="signUp_agree"><?= tt('agree', $lang) ?></label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <div class="login-link">
                            <?= tt('have_account', $lang) ?>
                            <a href="javascript:void(0)" id="openLoginModal">
                                <?= tt('login', $lang) ?>
                            </a>
                        </div>
                        <button type="submit" class="btn-submit"><?= tt('confirm_btn', $lang) ?></button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <?php include 'template/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const loginBtn = document.getElementById('openLoginModal');
            const loginModal = document.getElementById('myModal-sign-in');

            if (loginBtn && loginModal) {
                loginBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    loginModal.style.display = 'block';
                });
            }
        });
        
        // Phone number validation
        const phoneInput = document.getElementById('signUp_phone');
        phoneInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '');
            
            if (this.value.length > 0 && this.value[0] === '0') {
                this.value = this.value.substring(1);
            }
            
            if (this.value.length > 9) {
                this.value = this.value.substring(0, 9);
            }
        });

        // Radio button styling
        const radioWrappers = document.querySelectorAll('.radio-wrapper');
        radioWrappers.forEach(wrapper => {
            const radio = wrapper.querySelector('input[type="radio"]');
            
            wrapper.addEventListener('click', function() {
                radioWrappers.forEach(w => w.classList.remove('selected'));
                this.classList.add('selected');
                radio.checked = true;
            });
            
            if (radio.checked) {
                wrapper.classList.add('selected');
            }
        });

        // Password validation
        const password = document.getElementById('signUp_password');
        const confirmPassword = document.getElementById('signUp_confirm_password');
        
        const requirements = {
            length: document.getElementById('password_length'),
            upper: document.getElementById('password_upper'),
            lower: document.getElementById('password_lower'),
            number: document.getElementById('password_number'),
            special: document.getElementById('password_special')
        };

        password.addEventListener('input', function() {
            const value = this.value;
            
            if (value.length > 0) {
                confirmPassword.disabled = false;
            } else {
                confirmPassword.disabled = true;
                confirmPassword.value = '';
            }

            if (value.length >= 8) {
                requirements.length.classList.remove('invalid');
                requirements.length.classList.add('valid');
                requirements.length.querySelector('i').classList.remove('fa-times');
                requirements.length.querySelector('i').classList.add('fa-check');
            } else {
                requirements.length.classList.remove('valid');
                requirements.length.classList.add('invalid');
                requirements.length.querySelector('i').classList.remove('fa-check');
                requirements.length.querySelector('i').classList.add('fa-times');
            }

            if (/[A-Z]/.test(value)) {
                requirements.upper.classList.remove('invalid');
                requirements.upper.classList.add('valid');
                requirements.upper.querySelector('i').classList.remove('fa-times');
                requirements.upper.querySelector('i').classList.add('fa-check');
            } else {
                requirements.upper.classList.remove('valid');
                requirements.upper.classList.add('invalid');
                requirements.upper.querySelector('i').classList.remove('fa-check');
                requirements.upper.querySelector('i').classList.add('fa-times');
            }

            if (/[a-z]/.test(value)) {
                requirements.lower.classList.remove('invalid');
                requirements.lower.classList.add('valid');
                requirements.lower.querySelector('i').classList.remove('fa-times');
                requirements.lower.querySelector('i').classList.add('fa-check');
            } else {
                requirements.lower.classList.remove('valid');
                requirements.lower.classList.add('invalid');
                requirements.lower.querySelector('i').classList.remove('fa-check');
                requirements.lower.querySelector('i').classList.add('fa-times');
            }

            if (/[0-9]/.test(value)) {
                requirements.number.classList.remove('invalid');
                requirements.number.classList.add('valid');
                requirements.number.querySelector('i').classList.remove('fa-times');
                requirements.number.querySelector('i').classList.add('fa-check');
            } else {
                requirements.number.classList.remove('valid');
                requirements.number.classList.add('invalid');
                requirements.number.querySelector('i').classList.remove('fa-check');
                requirements.number.querySelector('i').classList.add('fa-times');
            }

            if (/[!@#_]/.test(value)) {
                requirements.special.classList.remove('invalid');
                requirements.special.classList.add('valid');
                requirements.special.querySelector('i').classList.remove('fa-times');
                requirements.special.querySelector('i').classList.add('fa-check');
            } else {
                requirements.special.classList.remove('valid');
                requirements.special.classList.add('invalid');
                requirements.special.querySelector('i').classList.remove('fa-check');
                requirements.special.querySelector('i').classList.add('fa-times');
            }
        });

        confirmPassword.addEventListener('input', function() {
            if (this.value === password.value && this.value.length > 0) {
                this.style.borderColor = '#4CAF50';
            } else if (this.value.length > 0) {
                this.style.borderColor = '#ff3d00';
            } else {
                this.style.borderColor = '#e0e0e0';
            }
        });

        document.getElementById('personal_register').addEventListener('submit', function(e) {
            const pwd = password.value;
            const confirmPwd = confirmPassword.value;
            const phone = phoneInput.value;

            if (phone.length !== 9) {
                e.preventDefault();
                alert('Phone number must be exactly 9 digits.');
                phoneInput.focus();
                return false;
            }

            if (phone[0] === '0') {
                e.preventDefault();
                alert('Phone number cannot start with 0.');
                phoneInput.focus();
                return false;
            }

            const isLengthValid = pwd.length >= 8;
            const hasUpper = /[A-Z]/.test(pwd);
            const hasLower = /[a-z]/.test(pwd);
            const hasNumber = /[0-9]/.test(pwd);
            const hasSpecial = /[!@#_]/.test(pwd);

            if (!isLengthValid || !hasUpper || !hasLower || !hasNumber || !hasSpecial) {
                e.preventDefault();
                alert('Please ensure your password meets all requirements.');
                return false;
            }

            if (pwd !== confirmPwd) {
                e.preventDefault();
                alert('Passwords do not match.');
                confirmPassword.focus();
                return false;
            }

            if (!document.getElementById('signUp_agree').checked) {
                e.preventDefault();
                alert('Please accept the terms and conditions.');
                return false;
            }
        });
    </script>
    <script src="/perfume/js/tracker.js" defer></script>
</body>
</html>