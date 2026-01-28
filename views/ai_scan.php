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

$ai_code = strtoupper($ai_code);

// เก็บ ai_code ใน session
$_SESSION['pending_ai_code'] = $ai_code;
$_SESSION['pending_ai_lang'] = $lang;
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Companion - Connecting...</title>
    
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

        .spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-top-color: #7877c6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 20px 0;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .loading-text {
            margin-top: 16px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <i class="fas fa-robot"></i>
        </div>
        
        <h1>AI Companion Detected</h1>
        <p>Checking your setup status...</p>
        
        <div class="ai-code-display"><?= htmlspecialchars($ai_code) ?></div>
        
        <div class="spinner"></div>
        <p class="loading-text">Please wait...</p>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const aiCode = '<?= $ai_code ?>';
        const currentLang = '<?= $lang ?>';
        
        $(document).ready(function() {
            // เก็บ ai_code ใน sessionStorage
            sessionStorage.setItem('pending_ai_code', aiCode);
            sessionStorage.setItem('pending_ai_lang', currentLang);
            
            // เช็คสถานะทันที
            checkCompanionStatus();
        });
        
        function checkCompanionStatus() {
            $.ajax({
                url: 'app/actions/check_companion_by_ai_code.php',
                type: 'GET',
                data: { 
                    ai_code: aiCode 
                },
                dataType: 'json',
                success: function(response) {
                    console.log('📊 Check result:', response);
                    
                    if (response.status === 'success') {
                        if (response.has_companion) {
                            // ✅ มี companion แล้ว → ไป chat ทันที
                            console.log('✅ Has companion, going to chat...');
                            window.location.href = '?ai_chat_3d&ai_code=' + aiCode + '&lang=' + currentLang;
                        } else {
                            // ❌ ยังไม่มี companion → ไปทำ setup ที่ questions
                            console.log('❌ No companion, going to questions...');
                            window.location.href = '?ai_questions&ai_code=' + aiCode + '&lang=' + currentLang;
                        }
                    } else {
                        // AI code ไม่ถูกต้อง
                        alert('Invalid AI code');
                        window.location.href = '?lang=' + currentLang;
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ Check error:', error);
                    alert('Failed to check status. Please try again.');
                    window.location.href = '?lang=' + currentLang;
                }
            });
        }
    </script>
</body>
</html>