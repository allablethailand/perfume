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
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f8f8f8;
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
            box-shadow: 0px 5px 20px 0px rgba(0,0,0,0.1);
            z-index: 1;
            justify-content: center;
            align-items: center;
            border-radius: 10px;
            padding: 40px;
            background: #fff;
        }

        .card h6 {
            color: #ff9800;
            font-size: 20px;
            margin-bottom: 20px;
            text-align: center;
        }

        .verification-type {
            background: #f8f9fa;
            padding: 10px 20px;
            border-radius: 5px;
            margin: 15px 0;
            font-size: 14px;
            color: #666;
            text-align: center;
        }

        .verification-type i {
            margin-right: 8px;
            color: #ff9800;
        }

        .inputs {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px;
        }

        .inputs input {
            width: 45px;
            height: 45px;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
        }

        .inputs input:focus {
            border-color: #ff9800;
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.1);
        }

        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            margin: 0;
        }

        .validate {
            border-radius: 20px;
            height: 40px;
            background-color: #FF9800;
            border: 1px solid #FF9800;
            width: 140px;
            color: #ffffff;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .validate:hover {
            color: #ffffff;
            box-shadow: 1px 2px 8px #FF9800;
            transform: translateY(-2px);
        }

        .validate:active {
            transform: translateY(0);
        }

        #maskedNumber {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }

        .resend-link {
            margin-top: 15px;
            font-size: 13px;
            color: #666;
        }

        .resend-link a {
            color: #ff9800;
            text-decoration: none;
            font-weight: 600;
        }

        .resend-link a:hover {
            text-decoration: underline;
        }

        #loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        #loading-overlay.active {
            display: flex !important;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #ff9800;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
                        <input class="text-center form-control rounded" type="text" id="first" maxlength="1" />
                        <input class="text-center form-control rounded" type="text" id="second" maxlength="1" />
                        <input class="text-center form-control rounded" type="text" id="third" maxlength="1" />
                        <input class="text-center form-control rounded" type="text" id="fourth" maxlength="1" />
                        <input class="text-center form-control rounded" type="text" id="fifth" maxlength="1" />
                        <input class="text-center form-control rounded" type="text" id="sixth" maxlength="1" />
                    </div>
                    
                    <div class="mt-4">
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
                        <input class="text-center form-control rounded" type="text" id="first" maxlength="1" />
                        <input class="text-center form-control rounded" type="text" id="second" maxlength="1" />
                        <input class="text-center form-control rounded" type="text" id="third" maxlength="1" />
                        <input class="text-center form-control rounded" type="text" id="fourth" maxlength="1" />
                        <input class="text-center form-control rounded" type="text" id="fifth" maxlength="1" />
                        <input class="text-center form-control rounded" type="text" id="sixth" maxlength="1" />
                    </div>
                    
                    <div class="mt-4">
                        <button id="confirm_resetBtn" class="px-4 validate">Confirm</button>
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

            $inputs.each(function(index) {
                $(this).on('input', function() {
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
                                confirmButtonColor: '#ff9800'
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
                                confirmButtonColor: '#ff9800'
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
                            confirmButtonColor: '#ff9800'
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
                        confirmButtonColor: '#ff9800'
                    });
                }
            });
        }
    </script>

</body>

</html>