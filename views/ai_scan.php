<?php
require_once('lib/connect.php');
global $conn;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// รับ ai_code จาก URL
$ai_code = $_GET['ai_code'] ?? '';
$lang = $_GET['lang'] ?? 'th';

// Validate ai_code format
if (empty($ai_code) || !preg_match('/^AI-[A-Z0-9]{8,}$/i', $ai_code)) {
    header("Location: ?lang=$lang");
    exit;
}

// เก็บ ai_code ใน session สำหรับใช้หลัง login
$_SESSION['pending_ai_code'] = strtoupper($ai_code);
$_SESSION['pending_ai_lang'] = $lang;
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Companion - RFID Scan</title>
    
    <link rel="icon" type="image/x-icon" href="public/product_images/696089dc2eba5_1767934428.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
            min-height: 100vh;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .gradient-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 30% 50%, rgba(120, 119, 198, 0.15), transparent 50%);
            pointer-events: none;
        }

        .container {
            max-width: 500px;
            width: 90%;
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 50px 40px;
            text-align: center;
            position: relative;
            z-index: 1;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
            animation: fadeInUp 0.8s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: linear-gradient(135deg, #7877c6 0%, #a8a7e5 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: white;
            box-shadow: 0 8px 32px rgba(120, 119, 198, 0.4);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }

        p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 15px;
            margin-bottom: 16px;
            line-height: 1.6;
        }

        .ai-code-display {
            display: inline-block;
            padding: 12px 24px;
            background: rgba(120, 119, 198, 0.2);
            border: 1px solid rgba(120, 119, 198, 0.4);
            border-radius: 12px;
            font-family: 'Courier New', monospace;
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 2px;
            margin: 20px 0 30px;
            color: #a8a7e5;
        }

        .loading {
            margin: 30px 0;
        }

        .spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-top-color: #7877c6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .loading-text {
            margin-top: 16px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 16px;
            border-radius: 12px;
            color: #fca5a5;
            margin-top: 20px;
            display: none;
        }

        .error-message.active {
            display: block;
        }

        .btn {
            padding: 14px 32px;
            background: linear-gradient(135deg, #7877c6 0%, #a8a7e5 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(120, 119, 198, 0.4);
        }

        @media (max-width: 600px) {
            .container {
                padding: 40px 30px;
            }

            h1 {
                font-size: 24px;
            }

            .ai-code-display {
                font-size: 16px;
                padding: 10px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="gradient-overlay"></div>
    
    <div class="container">
        <div class="icon">
            <i class="fas fa-robot"></i>
        </div>
        
        <h1 id="pageTitle">AI Companion Detected</h1>
        <p id="pageDesc">Verifying your AI code...</p>
        
        <div class="ai-code-display"><?= htmlspecialchars($ai_code) ?></div>
        
        <div class="loading" id="loadingSpinner">
            <div class="spinner"></div>
            <p class="loading-text">Connecting to your AI companion...</p>
        </div>
        
        <div class="error-message" id="errorMessage"></div>
        
        <a href="?lang=<?= $lang ?>" class="btn" id="homeBtn" style="display: none;">
            <i class="fas fa-home"></i> Back to Home
        </a>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const aiCode = '<?= $ai_code ?>';
        const currentLang = '<?= $lang ?>';
        
        $(document).ready(function() {
            // ตรวจสอบว่า user login หรือยัง
            const jwt = sessionStorage.getItem('jwt');
            
            if (!jwt) {
                // ยังไม่ login -> เก็บ ai_code ใน sessionStorage
                sessionStorage.setItem('pending_ai_code', aiCode);
                sessionStorage.setItem('pending_ai_lang', currentLang);
                
                $('#pageTitle').text('Please Login First');
                $('#pageDesc').text('You need to login to activate your AI companion. Redirecting to homepage...');
                
                setTimeout(() => {
                    // Redirect ไปหน้าหลักพร้อม parameter login=1 เพื่อเปิด modal
                    window.location.href = '?login=1&lang=' + currentLang;
                }, 2000);
                return;
            }
            
            // มี JWT -> ตรวจสอบ token และ verify AI code
            verifyUserAndAI();
        });
        
        function verifyUserAndAI() {
            const jwt = sessionStorage.getItem('jwt');
            
            // Step 1: Verify JWT
            $.ajax({
                url: 'app/actions/protected.php',
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + jwt,
                    'X-Auth-Token': jwt
                },
                success: function(response) {
                    if (response.status === 'success') {
                        // Step 2: Verify AI Code และเช็คสถานะ
                        checkAICompanionStatus(response.data.user_id);
                    } else {
                        showError('Authentication failed. Please login again.');
                        setTimeout(() => {
                            sessionStorage.removeItem('jwt');
                            window.location.href = '?login=1&lang=' + currentLang;
                        }, 2000);
                    }
                },
                error: function() {
                    showError('Authentication error. Please login again.');
                    setTimeout(() => {
                        sessionStorage.removeItem('jwt');
                        window.location.href = '?login=1&lang=' + currentLang;
                    }, 2000);
                }
            });
        }
        
        function checkAICompanionStatus(userId) {
            const jwt = sessionStorage.getItem('jwt');
            
            $.ajax({
                url: 'app/actions/verify_and_route_ai.php',
                type: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + jwt,
                    'X-Auth-Token': jwt
                },
                data: {
                    ai_code: aiCode,
                    user_id: userId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        // Route ตามสถานะที่ backend บอก
                        setTimeout(() => {
                            window.location.href = response.redirect_url;
                        }, 1000);
                    } else {
                        showError(response.message || 'Invalid AI code');
                        $('#homeBtn').show();
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    showError(response?.message || 'Connection error. Please try again.');
                    $('#homeBtn').show();
                }
            });
        }
        
        function showError(message) {
            $('#loadingSpinner').hide();
            $('#errorMessage').text(message).addClass('active');
        }
    </script>
</body>
</html>