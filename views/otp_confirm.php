<?php
require_once('lib/connect.php');
global $conn;

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// รับ pending_ai parameters จาก URL (ถ้ามี)
$pending_ai = $_GET['pending_ai'] ?? '';
$pending_lang = $_GET['pending_lang'] ?? 'th';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification</title>
    <?php include 'template/header.php' ?>
    <?php include 'inc_head.php' ?>
    <link href="app/css/index_.css?v=<?php echo time(); ?>" rel="stylesheet">

    <style>
        :root {
            --luxury-black: #000000;
            --luxury-white: #ffffff;
            --luxury-gray: #666666;
            --luxury-light-gray: #f5f5f5;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            min-height: 100vh;
        }

        .height-100 {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .card {
            width: 100%;
            max-width: 450px;
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            z-index: 1;
            justify-content: center;
            align-items: center;
            border-radius: 12px;
            padding: 40px;
            background: var(--luxury-white);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--luxury-black) 0%, #333 100%);
        }

        .card h6 {
            color: var(--luxury-black);
            font-size: 22px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .verification-type {
            background: var(--luxury-black);
            padding: 12px 24px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 14px;
            color: var(--luxury-white);
            text-align: center;
            letter-spacing: 0.5px;
        }

        .verification-type i {
            margin-right: 10px;
            color: var(--luxury-white);
        }

        .card > div:nth-child(3) {
            color: var(--luxury-gray);
            margin: 20px 0;
        }

        .inputs {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
        }

        .inputs input {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            transition: all 0.3s ease;
            background: var(--luxury-white);
            color: var(--luxury-black);
        }

        .inputs input:focus {
            border-color: var(--luxury-black);
            outline: none;
            box-shadow: 0 0 0 4px rgba(0, 0, 0, 0.1);
            transform: scale(1.05);
        }

        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            margin: 0;
        }

        .validate {
            border-radius: 25px;
            height: 48px;
            background-color: var(--luxury-black);
            border: 2px solid var(--luxury-black);
            width: 160px;
            color: var(--luxury-white);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-size: 14px;
        }

        .validate:hover {
            background-color: #333;
            border-color: #333;
            color: var(--luxury-white);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .validate:active {
            transform: translateY(0);
        }

        #maskedNumber {
            font-size: 16px;
            color: var(--luxury-black);
            font-weight: 600;
            letter-spacing: 1px;
        }

        .resend-link {
            margin-top: 20px;
            font-size: 14px;
            color: var(--luxury-gray);
        }

        .resend-link a {
            color: var(--luxury-black);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border-bottom: 1px solid transparent;
        }

        .resend-link a:hover {
            border-bottom: 1px solid var(--luxury-black);
        }

        #loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(5px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        #loading-overlay.active {
            display: flex !important;
        }

        .spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.2);
            border-top: 4px solid var(--luxury-white);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Mobile responsive */
        @media (max-width: 480px) {
            .card {
                padding: 30px 20px;
            }

            .card h6 {
                font-size: 18px;
            }

            .inputs input {
                width: 45px;
                height: 45px;
                font-size: 20px;
            }

            .validate {
                width: 140px;
                height: 44px;
                font-size: 13px;
            }
        }
    </style>

</head>

<body>
    <div id="loading-overlay">
        <div class="spinner"></div>
    </div>

    <?php
    if (isset($_GET['register']) || isset($_GET['forgot'])) {
        $user_id = isset($_GET['otpID']) ? $_GET['otpID'] : '';
        $method = isset($_GET['method']) ? $_GET['method'] : 'email';

        $sql = "SELECT mb_user.email, mb_user.phone_number, mb_user.login_method 
        FROM mb_user 
        WHERE mb_user.user_id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            exit();
        }

        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $login_method = $row['login_method'];
        
        if ($login_method == 'email') {
            $contact = $row['email'];
            $maskedContact = substr($contact, 0, 3) . str_repeat('*', strpos($contact, '@') - 3) . substr($contact, strpos($contact, '@'));
        } else {
            $contact = $row['phone_number'];
            $maskedContact = substr($contact, 0, 5) . str_repeat('*', 3) . ' ' . str_repeat('*', 3) . ' ' . substr($contact, -3);
        }
    }
    ?>

    <?php if (isset($_GET['register'])) { ?>
        <div class="container height-100">
            <div class="position-relative">
                <div class="card p-2 text-center">
                    <h6>Please enter the OTP code<br>to verify your account</h6>
                    
                    <div class="verification-type">
                        <?php if ($login_method == 'email') { ?>
                            <i class="fas fa-envelope"></i>
                            <span>Verification via Email</span>
                        <?php } else { ?>
                            <i class="fas fa-mobile-alt"></i>
                            <span>Verification via Phone</span>
                        <?php } ?>
                    </div>
                    
                    <div>
                        <span>A code has been sent to</span> <br>
                        <small id="maskedNumber"><?php echo $maskedContact; ?></small>
                    </div>
                    
                    <input type="hidden" id="user_id" name="user_id" value="<?php echo $user_id; ?>">
                    <input type="hidden" id="login_method" name="login_method" value="<?php echo $login_method; ?>">
                    <input type="hidden" id="pending_ai" name="pending_ai" value="<?php echo htmlspecialchars($pending_ai); ?>">
                    <input type="hidden" id="pending_lang" name="pending_lang" value="<?php echo htmlspecialchars($pending_lang); ?>">
                    
                    <div id="otp" class="inputs d-flex flex-row justify-content-center mt-4">
                        <input class="text-center form-control rounded" type="text" id="first" maxlength="1" autocomplete="off" />
                        <input class="text-center form-control rounded" type="text" id="second" maxlength="1" autocomplete="off" />
                        <input class="text-center form-control rounded" type="text" id="third" maxlength="1" autocomplete="off" />
                        <input class="text-center form-control rounded" type="text" id="fourth" maxlength="1" autocomplete="off" />
                        <input class="text-center form-control rounded" type="text" id="fifth" maxlength="1" autocomplete="off" />
                        <input class="text-center form-control rounded" type="text" id="sixth" maxlength="1" autocomplete="off" />
                    </div>
                    
                    <div class="mt-4" style="padding-top: 1em; display: flex; justify-content: center;">
                        <button id="confirm_emailBtn" class="px-4 validate">Confirm</button>
                    </div>
                    
                    <div class="resend-link">
                        Didn't receive code? <a href="#" id="resendOTP">Resend</a>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

    <?php if (isset($_GET['forgot'])) { ?>
        <div class="container height-100">
            <div class="position-relative">
                <div class="card p-2 text-center">
                    <h6>Please enter the OTP code<br>to reset your password</h6>
                    
                    <div class="verification-type">
                        <?php if ($login_method == 'email') { ?>
                            <i class="fas fa-envelope"></i>
                            <span>Verification via Email</span>
                        <?php } else { ?>
                            <i class="fas fa-mobile-alt"></i>
                            <span>Verification via Phone</span>
                        <?php } ?>
                    </div>
                    
                    <div>
                        <span>A code has been sent to</span> <br>
                        <small id="maskedNumber"><?php echo $maskedContact; ?></small>
                    </div>
                    
                    <input type="hidden" id="user_id" name="user_id" value="<?php echo $user_id; ?>">
                    <input type="hidden" id="login_method" name="login_method" value="<?php echo $login_method; ?>">
                    
                    <div id="otp" class="inputs d-flex flex-row justify-content-center mt-4">
                        <input class="text-center form-control rounded" type="text" id="first" maxlength="1" autocomplete="off" />
                        <input class="text-center form-control rounded" type="text" id="second" maxlength="1" autocomplete="off" />
                        <input class="text-center form-control rounded" type="text" id="third" maxlength="1" autocomplete="off" />
                        <input class="text-center form-control rounded" type="text" id="fourth" maxlength="1" autocomplete="off" />
                        <input class="text-center form-control rounded" type="text" id="fifth" maxlength="1" autocomplete="off" />
                        <input class="text-center form-control rounded" type="text" id="sixth" maxlength="1" autocomplete="off" />
                    </div>
                    
                    <div class="mt-4" style="padding-top: 1em;">
                        <button id="confirm_resetBtn" class="px-4 validate" style="padding-top: 1em;">Confirm</button>
                    </div>
                    
                    <div class="resend-link">
                        Didn't receive code? <a href="#" id="resendOTP">Resend</a>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

    <script>
        // currentLang ถูกประกาศไว้แล้วใน header.php
        
        function OTPInput() {
            const $inputs = $('#otp > input');

            // ✅ Handle Paste Event - วางได้ที่ช่องไหนก็ได้
            $inputs.on('paste', function(e) {
                e.preventDefault();
                
                // Get pasted data
                const pastedData = (e.originalEvent || e).clipboardData.getData('text/plain');
                
                // Extract only numbers
                const otpDigits = pastedData.replace(/\D/g, '').substring(0, 6);
                
                if (otpDigits.length > 0) {
                    // Fill in all OTP inputs
                    for (let i = 0; i < otpDigits.length && i < 6; i++) {
                        $inputs.eq(i).val(otpDigits[i]);
                    }
                    
                    // Focus on last filled input or next empty one
                    if (otpDigits.length < 6) {
                        $inputs.eq(otpDigits.length).focus();
                    } else {
                        $inputs.eq(5).focus();
                    }
                }
            });

            // Handle typing
            $inputs.each(function(index) {
                $(this).on('input', function() {
                    // Only allow numbers
                    this.value = this.value.replace(/\D/g, '');
                    
                    if (this.value.length > 1) {
                        this.value = this.value[0];
                    }
                    if (this.value !== '' && index < $inputs.length - 1) {
                        $inputs.eq(index + 1).focus();
                    }
                });

                $(this).on('keydown', function(event) {
                    if (event.key === 'Backspace') {
                        this.value = '';
                        if (index > 0) {
                            $inputs.eq(index - 1).focus();
                        }
                    }
                });
            });
        }

        $(document).ready(function() {
            OTPInput();
            $('#first').focus();

            $('#confirm_emailBtn').on('click', function() {
                let otp = '';
                $('#otp > input').each(function() {
                    otp += $(this).val();
                });
                
                if (otp.length !== 6) {
                    alert('Please enter all 6 digits of OTP');
                    return;
                }
                
                let user_id = $('#user_id').val();
                let method = $('#login_method').val();
                
                console.log('Confirming OTP:', {user_id, otp, method});
                confirmOTP(user_id, otp, method);
            });

            $('#confirm_resetBtn').on('click', function() {
                let otp = '';
                $('#otp > input').each(function() {
                    otp += $(this).val();
                });
                
                if (otp.length !== 6) {
                    alert('Please enter all 6 digits of OTP');
                    return;
                }
                
                let user_id = $('#user_id').val();
                let method = $('#login_method').val();
                
                console.log('Confirming Reset:', {user_id, otp, method});
                confirmReset(user_id, otp, method);
            });

            $('#resendOTP').on('click', function(e) {
                e.preventDefault();
                alert('Resend OTP functionality will be implemented');
            });
        });

        function confirmReset(user_id, otp, method) {
            $('#loading-overlay').addClass('active');

            $.ajax({
                url: 'app/actions/otp_forgot_password.php',
                type: 'POST',
                data: {
                    action: 'sendReset',
                    userId: user_id,
                    otpCode: otp,
                    method: method
                },
                dataType: 'JSON',
                success: function(response) {
                    if (response.status == 'succeed') {
                        $.ajax({
                            url: 'app/actions/otp_forgot_password.php',
                            type: 'POST',
                            data: {
                                action: 'generatePassword',
                                userId: response.user_id,
                                method: method
                            },
                            dataType: 'JSON',
                            success: function(response) {
                                if (response.status == 'succeed') {
                                    $('#loading-overlay').removeClass('active');
                                    const Toast = Swal.mixin({
                                        toast: true,
                                        position: "top-end",
                                        showConfirmButton: false,
                                        timer: 3000,
                                        timerProgressBar: true,
                                        didOpen: (toast) => {
                                            toast.onmouseenter = Swal.stopTimer;
                                            toast.onmouseleave = Swal.resumeTimer;
                                        }
                                    });

                                    Toast.fire({
                                        icon: "success",
                                        title: response.message
                                    }).then(() => {
                                        window.location.href = '?';
                                    });
                                } else {
                                    $('#loading-overlay').removeClass('active');
                                    const Toast = Swal.mixin({
                                        toast: true,
                                        position: "top-end",
                                        showConfirmButton: false,
                                        timer: 3000,
                                        timerProgressBar: true,
                                        didOpen: (toast) => {
                                            toast.onmouseenter = Swal.stopTimer;
                                            toast.onmouseleave = Swal.resumeTimer;
                                        }
                                    });

                                    Toast.fire({
                                        icon: "error",
                                        title: response.message
                                    });
                                }
                            },
                            error: function(error) {
                                console.log('Error:', error);
                                $('#loading-overlay').removeClass('active');
                            }
                        });
                    } else {
                        $('#loading-overlay').removeClass('active');
                        const Toast = Swal.mixin({
                            toast: true,
                            position: "top-end",
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.onmouseenter = Swal.stopTimer;
                                toast.onmouseleave = Swal.resumeTimer;
                            }
                        });

                        Toast.fire({
                            icon: "error",
                            title: response.message
                        });
                    }
                },
                error: function(error) {
                    console.log('Error:', error);
                    $('#loading-overlay').removeClass('active');
                }
            });
        }

        function confirmOTP(user_id, otp, method) {
            console.log('Starting OTP confirmation...', {user_id, otp, method});
            $('#loading-overlay').addClass('active');

            $.ajax({
                url: 'app/actions/otp_confirm_email.php',
                type: 'POST',
                data: {
                    action: 'sendOTP',
                    userId: user_id,
                    otpCode: otp,
                    method: method
                },
                dataType: 'JSON',
                success: function(response) {
                    console.log('OTP Response:', response);

                    if (response.status == 'succeed') {
                        $('#loading-overlay').removeClass('active');
                        
                        // 🔥 เช็คว่ามี pending_ai หรือไม่
                        const pendingAi = $('#pending_ai').val();
                        const pendingLang = $('#pending_lang').val() || currentLang;
                        
                        if (pendingAi && pendingAi.trim() !== '') {
                            // มี pending AI -> เก็บใน sessionStorage และ redirect ไปหน้าหลักเพื่อ login
                            Swal.fire({
                                icon: 'success',
                                title: 'Registration Complete!',
                                text: 'Please login to continue activating your AI companion',
                                showConfirmButton: true,
                                confirmButtonColor: '#000000'
                            }).then(() => {
                                // เก็บ pending_ai ใน sessionStorage
                                sessionStorage.setItem('pending_ai_code', pendingAi);
                                sessionStorage.setItem('pending_ai_lang', pendingLang);
                                
                                // ลบ pending_ai_code ออกจาก PHP session
                                $.ajax({
                                    url: 'app/actions/clear_pending_ai.php',
                                    type: 'POST',
                                    complete: function() {
                                        // Redirect ไปหน้าหลัก (modal จะเปิดเองจาก header.php)
                                        window.location.href = '?lang=' + pendingLang;
                                    }
                                });
                            });
                        } else {
                            // ไม่มี pending -> redirect ไปหน้าหลัก
                            Swal.fire({
                                icon: 'success',
                                title: 'Registration Complete!',
                                text: 'Please login to continue',
                                showConfirmButton: true,
                                confirmButtonColor: '#000000'
                            }).then(() => {
                                window.location.href = '?lang=' + currentLang;
                            });
                        }
                    } else {
                        $('#loading-overlay').removeClass('active');
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message || 'Invalid OTP code',
                            showConfirmButton: true,
                            confirmButtonColor: '#000000'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', {xhr, status, error});
                    console.log('Response Text:', xhr.responseText);
                    
                    $('#loading-overlay').removeClass('active');
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Error',
                        text: 'Unable to verify OTP. Please try again.',
                        showConfirmButton: true,
                        confirmButtonColor: '#000000'
                    });
                }
            });
        }
    </script>

</body>

</html>