<?php
require_once('lib/connect.php');
global $conn;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// รับ parameters
$ai_code = $_GET['ai_code'] ?? $_SESSION['pending_ai_code'] ?? '';
$lang = $_GET['lang'] ?? $_SESSION['pending_ai_lang'] ?? 'en'; // Default to English

// Validate AI code
if (empty($ai_code) || !preg_match('/^AI-[A-Z0-9]{8,}$/i', $ai_code)) {
    header("Location: ?lang=$lang");
    exit;
}

$ai_code = strtoupper($ai_code);
$_SESSION['pending_ai_code'] = $ai_code;
$_SESSION['pending_ai_lang'] = $lang;
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Companion Setup</title>
    
    <link rel="icon" type="image/x-icon" href="public/product_images/696089dc2eba5_1767934428.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #000;
            min-height: 100vh;
            color: #fff;
            display: flex;
            overflow-x: hidden;
        }
        
        /* Pure black background for intro */
        body.intro-mode {
            background: #000;
        }

        /* AI Avatar Sidebar */
        .ai-sidebar {
            width: 400px;
            background: #000;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            z-index: 100;
            opacity: 0;
            transform: translateX(-100%);
            transition: all 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .ai-sidebar.show {
            opacity: 1;
            transform: translateX(0);
        }

        /* Minimal Video Avatar - No Circle Border */
        .ai-avatar-container {
            width: 100%;
            max-width: 280px;
            margin-bottom: 30px;
            position: relative;
        }

        .ai-avatar-container video {
            width: 100%;
            height: auto;
            object-fit: cover;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .ai-name-sidebar {
            font-size: 24px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 12px;
            text-align: center;
        }

        .ai-status {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.5);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        /* AI Speech Bubble */
        .ai-speech-bubble {
            position: relative;
            margin-top: 30px;
            padding: 20px 24px;
            background: linear-gradient(135deg, rgba(120, 119, 198, 0.25) 0%, rgba(168, 167, 229, 0.2) 100%);
            border: 2px solid rgba(120, 119, 198, 0.4);
            border-radius: 20px;
            max-width: 320px;
            opacity: 0;
            transform: translateY(-10px) scale(0.95);
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            box-shadow: 0 8px 24px rgba(120, 119, 198, 0.2);
        }
        
        .ai-speech-bubble.show {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        
        .ai-speech-bubble::before {
            content: '';
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 12px solid transparent;
            border-right: 12px solid transparent;
            border-bottom: 12px solid rgba(120, 119, 198, 0.4);
        }
        
        .ai-speech-bubble::after {
            content: '';
            position: absolute;
            top: -7px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 10px solid transparent;
            border-right: 10px solid transparent;
            border-bottom: 10px solid rgba(120, 119, 198, 0.25);
        }
        
        .ai-speech-text {
            color: #fff;
            font-size: 14px;
            line-height: 1.6;
            text-align: center;
            margin: 0;
            position: relative;
            z-index: 1;
        }

        /* Main Content */
        .main-content {
            margin-left: 0;
            flex: 1;
            padding: 60px 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #000;
            transition: all 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .main-content.with-sidebar {
            margin-left: 400px;
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
        }

        /* Intro Screen - Pure Black Background */
        .intro-screen {
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            width: 100%;
            max-width: 600px;
            animation: fadeIn 0.8s ease;
        }

        .intro-screen.active {
            display: flex;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .intro-video-container {
            width: 350px;
            height: 350px;
            margin-bottom: 40px;
            position: relative;
        }

        .intro-video-container video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 20px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.8);
        }

        .intro-message {
            font-size: 28px;
            font-weight: 600;
            line-height: 1.6;
            margin-bottom: 50px;
            color: rgba(255, 255, 255, 0.9);
            text-shadow: 0 2px 20px rgba(0, 0, 0, 0.5);
        }

        .intro-loading {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .intro-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            animation: dotBounce 1.4s infinite ease-in-out both;
        }

        .intro-dot:nth-child(1) { animation-delay: -0.32s; }
        .intro-dot:nth-child(2) { animation-delay: -0.16s; }

        @keyframes dotBounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }

        /* Screens */
        .screen {
            display: none;
            width: 100%;
            max-width: 700px;
            animation: fadeInUp 0.6s ease;
        }

        .screen.active {
            display: block;
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

        .card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 50px;
        }
        
        /* No card styling for intro screen */
        .intro-screen .card {
            background: transparent;
            backdrop-filter: none;
            border: none;
            padding: 0;
        }

        .screen-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 16px;
            text-align: center;
        }

        .screen-subtitle {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 40px;
            text-align: center;
            line-height: 1.6;
        }

        /* Language Selection */
        .language-options {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
            margin: 30px 0;
        }

        .language-option {
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }

        .language-option:hover {
            border-color: #7877c6;
            background: rgba(120, 119, 198, 0.1);
        }

        .language-option.selected {
            border-color: #7877c6;
            background: rgba(120, 119, 198, 0.2);
            box-shadow: 0 8px 24px rgba(120, 119, 198, 0.3);
        }

        .language-flag {
            width: 48px;
            height: 36px;
            margin: 0 auto 8px;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        
        .language-flag img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .language-name {
            font-size: 13px;
            color: #fff;
            font-weight: 500;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #fff;
            font-size: 15px;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }

        .form-control:focus {
            outline: none;
            border-color: #7877c6;
            background: rgba(255, 255, 255, 0.08);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        /* OTP Inputs */
        .otp-inputs {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 30px 0;
        }

        .otp-input {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            padding: 0;
        }

        /* Questions */
        .question-number {
            font-size: 13px;
            color: #7877c6;
            font-weight: 700;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .question-text {
            font-size: 24px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 30px;
            line-height: 1.4;
        }

        .choices-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .choice-option {
            padding: 18px 24px;
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            color: rgba(255, 255, 255, 0.8);
            text-align: left;
        }

        .choice-option:hover {
            border-color: #7877c6;
            background: rgba(120, 119, 198, 0.1);
        }

        .choice-option.selected {
            border-color: #7877c6;
            background: rgba(120, 119, 198, 0.2);
            color: #fff;
        }

        /* Scale Input */
        .scale-container {
            padding: 24px 0;
        }

        .scale-labels {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
            font-weight: 500;
        }

        .scale-options {
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        .scale-option {
            flex: 1;
            aspect-ratio: 1;
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            transition: all 0.3s;
        }

        .scale-option:hover {
            border-color: #7877c6;
            background: rgba(120, 119, 198, 0.1);
            transform: scale(1.05);
        }

        .scale-option.selected {
            border-color: #7877c6;
            background: rgba(120, 119, 198, 0.3);
            color: #fff;
            box-shadow: 0 8px 24px rgba(120, 119, 198, 0.4);
        }

        /* Progress Bar */
        .progress-container {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 30px;
        }

        .progress-text {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }

        .progress-bar {
            width: 100%;
            height: 6px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #7877c6 0%, #a8a7e5 100%);
            transition: width 0.4s ease;
            border-radius: 10px;
        }

        /* Buttons */
        .btn {
            padding: 14px 32px;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #7877c6 0%, #a8a7e5 100%);
            color: white;
            width: 100%;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(120, 119, 198, 0.5);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.2);
            flex: 1;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .text-link {
            color: #7877c6;
            text-decoration: none;
            font-size: 14px;
            margin-top: 15px;
            display: inline-block;
            text-align: center;
            width: 100%;
        }

        .text-link:hover {
            text-decoration: underline;
        }

        /* Loading */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 99999;
        }

        .loading-overlay.active {
            display: flex;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(255, 255, 255, 0.1);
            border-top-color: #7877c6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            color: #fff;
            margin-top: 20px;
            font-size: 16px;
        }

        /* Text Input */
        #textAnswerInput {
            width: 100%;
            padding: 16px 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: #fff;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
            margin-bottom: 20px;
        }

        #textAnswerInput:focus {
            outline: none;
            border-color: #7877c6;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 4px rgba(120, 119, 198, 0.1);
        }

        #textAnswerInput::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        /* Responsive */
        @media (max-width: 992px) {
            body {
                flex-direction: column;
            }

            .ai-sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 30px;
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                transform: translateX(0);
            }

            .main-content,
            .main-content.with-sidebar {
                margin-left: 0;
                padding: 40px 30px;
            }

            .language-options {
                grid-template-columns: repeat(3, 1fr);
            }

            .intro-video-container {
                width: 280px;
                height: 280px;
            }
        }

        @media (max-width: 600px) {
            .card {
                padding: 30px 24px;
            }

            .language-options {
                grid-template-columns: repeat(2, 1fr);
            }

            .intro-video-container {
                width: 250px;
                height: 250px;
            }

            .intro-message {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div style="text-align: center;">
            <div class="spinner"></div>
            <div class="loading-text" id="loadingText">Processing...</div>
        </div>
    </div>

    <!-- AI Avatar Sidebar (Hidden initially) -->
    <div class="ai-sidebar" id="aiSidebar">
        <div class="ai-avatar-container">
            <video id="sidebarVideoAvatar" muted playsinline loop></video>
        </div>
        <h2 class="ai-name-sidebar" id="aiName">AI Companion</h2>
        <div class="ai-status">
            <span class="status-dot"></span>
            <span id="statusText">Setting Up</span>
        </div>
        
        <!-- AI Speech Bubble -->
        <div class="ai-speech-bubble" id="aiSpeechBubble">
            <p class="ai-speech-text" id="aiSpeechText">Welcome!</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">

        <!-- Intro Screen - Centered Video -->
        <div class="intro-screen active" id="introScreen">
            <div class="intro-video-container">
                <video id="introVideoAvatar" muted playsinline loop></video>
            </div>
            <div class="intro-message" id="introMessage">
                Nice to meet you<br>
                First, I'd like to know you better<br>
                Please answer all the questions
            </div>
            <div class="intro-loading">
                <div class="intro-dot"></div>
                <div class="intro-dot"></div>
                <div class="intro-dot"></div>
            </div>
        </div>

        <!-- 1. Language Selection Screen -->
        <div class="screen" id="languageScreen">
            <div class="card">
                <h2 class="screen-title">Choose Your Language</h2>
                <p class="screen-subtitle">เลือกภาษา / 选择语言 / 言語を選択 / 언어 선택</p>
                
                <div class="language-options">
                    <div class="language-option" data-lang="th">
                        <div class="language-flag">
                            <img src="https://flagcdn.com/th.svg" alt="Thai">
                        </div>
                        <div class="language-name">ไทย</div>
                    </div>
                    <div class="language-option" data-lang="en">
                        <div class="language-flag">
                            <img src="https://flagcdn.com/gb.svg" alt="English">
                        </div>
                        <div class="language-name">English</div>
                    </div>
                    <div class="language-option" data-lang="cn">
                        <div class="language-flag">
                            <img src="https://flagcdn.com/cn.svg" alt="Chinese">
                        </div>
                        <div class="language-name">中文</div>
                    </div>
                    <div class="language-option" data-lang="jp">
                        <div class="language-flag">
                            <img src="https://flagcdn.com/jp.svg" alt="Japanese">
                        </div>
                        <div class="language-name">日本語</div>
                    </div>
                    <div class="language-option" data-lang="kr">
                        <div class="language-flag">
                            <img src="https://flagcdn.com/kr.svg" alt="Korean">
                        </div>
                        <div class="language-name">한국어</div>
                    </div>
                </div>

                <button class="btn btn-primary" id="btnConfirmLanguage" disabled>
                    <i class="fas fa-check"></i> Confirm Language
                </button>
            </div>
        </div>

        <!-- 2. Register Screen -->
        <div class="screen" id="registerScreen">
            <div class="card">
                <h2 class="screen-title">Create Account</h2>
                <p class="screen-subtitle">Please register to continue with your AI companion</p>
                
                <form id="registerForm">
                    <div class="form-group">
                        <label for="regName">Full Name</label>
                        <input type="text" id="regName" class="form-control" placeholder="John Doe" required>
                    </div>
                    <div class="form-group">
                        <label for="regEmail">Email</label>
                        <input type="email" id="regEmail" class="form-control" placeholder="john@example.com" required>
                    </div>
                    <div class="form-group">
                        <label for="regPhone">Phone Number</label>
                        <input type="tel" id="regPhone" class="form-control" placeholder="+66812345678" required>
                    </div>
                    <div class="form-group">
                        <label for="regPassword">Password (min. 6 characters)</label>
                        <input type="password" id="regPassword" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Register
                    </button>
                </form>
                
                <a href="#" class="text-link" id="linkToLogin">Already have an account? Login</a>
            </div>
        </div>

        <!-- 3. OTP Screen -->
        <div class="screen" id="otpScreen">
            <div class="card">
                <h2 class="screen-title">Enter OTP Code</h2>
                <p class="screen-subtitle">We sent a 6-digit code to<br><strong id="otpContact"></strong></p>
                
                <div class="otp-inputs">
                    <input type="text" class="form-control otp-input" maxlength="1" id="otp1">
                    <input type="text" class="form-control otp-input" maxlength="1" id="otp2">
                    <input type="text" class="form-control otp-input" maxlength="1" id="otp3">
                    <input type="text" class="form-control otp-input" maxlength="1" id="otp4">
                    <input type="text" class="form-control otp-input" maxlength="1" id="otp5">
                    <input type="text" class="form-control otp-input" maxlength="1" id="otp6">
                </div>

                <button class="btn btn-primary" id="btnVerifyOTP">
                    <i class="fas fa-check-circle"></i> Verify OTP
                </button>
                <a href="#" class="text-link" id="linkResendOTP">Resend OTP</a>
            </div>
        </div>

        <!-- 4. Login Screen -->
        <div class="screen" id="loginScreen">
            <div class="card">
                <h2 class="screen-title">Welcome Back</h2>
                <p class="screen-subtitle">Please login to continue</p>
                
                <form id="loginForm">
                    <div class="form-group">
                        <label for="loginUsername">Email or Phone</label>
                        <input type="text" id="loginUsername" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="loginPassword">Password</label>
                        <input type="password" id="loginPassword" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </form>
                
                <a href="#" class="text-link" id="linkToRegister">Don't have an account? Register</a>
            </div>
        </div>

        <!-- 5. Questions Screen -->
        <div class="screen" id="questionsScreen">
            <div class="progress-container">
                <div class="progress-text">
                    <span>Progress</span>
                    <span><span id="currentQ">1</span> / <span id="totalQ">10</span></span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill" style="width: 10%;"></div>
                </div>
            </div>

            <div class="card">
                <div class="question-number" id="questionNumber">Question 1</div>
                <div class="question-text" id="questionText">Loading...</div>
                <div class="choices-container" id="choicesContainer"></div>
                
                <!-- Scale Container (for scale type questions) -->
                <div class="scale-container" id="scaleContainer" style="display: none;">
                    <div class="scale-labels">
                        <span>Strongly Disagree</span>
                        <span>Strongly Agree</span>
                    </div>
                    <div class="scale-options" id="scaleOptions"></div>
                </div>

                <div class="btn-group">
                    <button class="btn btn-secondary" id="btnPrevQuestion" disabled>
                        <i class="fas fa-arrow-left"></i> Previous
                    </button>
                    <button class="btn btn-primary" id="btnNextQuestion" disabled>
                        Next <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>

    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="app/js/ai_setup_avatar.js?v=<?php echo time(); ?>"></script>
    <script>
        // ========== Global Variables ==========
        const aiCode = '<?= $ai_code ?>';
        let selectedLanguage = 'en'; // Force English as default
        let jwt = sessionStorage.getItem('jwt');
        let companionId = null;
        let userId = null;
        let aiCompanionData = null;
        let questions = [];
        let currentQuestionIndex = 0;
        let answers = {};

        // ========== Intro Messages (5 Languages) ==========
        const introMessages = {
            th: "ยินดีที่ได้รู้จัก<br>ก่อนอื่นผมอยากรู้จักคุณมากขึ้น<br>โปรดตอบคำถามให้ครบก่อนนะ",
            en: "Nice to meet you<br>First, I'd like to know you better<br>Please answer all the questions",
            cn: "很高兴认识你<br>首先，我想更好地了解你<br>请回答所有问题",
            jp: "お会いできて嬉しいです<br>まず、あなたのことをもっと知りたいです<br>すべての質問に答えてください",
            kr: "만나서 반가워요<br>먼저 당신을 더 잘 알고 싶어요<br>모든 질문에 답해주세요"
        };
        
        // ========== Set default language to English ==========
        if (!selectedLanguage || selectedLanguage === 'th') {
            selectedLanguage = 'en';
        }

        // ========== Initialize ==========
        $(document).ready(function() {
            console.log('🚀 Starting AI Setup Flow...');
            console.log('AI Code:', aiCode);
            
            // Force English as default language
            if (!selectedLanguage || selectedLanguage === 'th') {
                selectedLanguage = 'en';
            }
            
            console.log('Language:', selectedLanguage);
            console.log('JWT:', jwt ? 'Present' : 'Missing');

            loadAIData();
            
            // ✅ Initialize Avatar System
            if (typeof initSetupAvatar === 'function') {
                initSetupAvatar();
            }
            
            startSetupFlow();
        });

        // ========== Load AI Data ==========
        async function loadAIData() {
            try {
                const response = await $.ajax({
                    url: 'app/actions/get_ai_data.php',
                    type: 'GET',
                    data: { ai_code: aiCode },
                    dataType: 'json'
                });

                if (response.status === 'success') {
                    aiCompanionData = response.ai_data;
                    
                    const langCol = 'ai_name_' + selectedLanguage;
                    $('#aiName').text(aiCompanionData[langCol] || aiCompanionData.ai_name_th);
                }
            } catch (error) {
                console.error('Failed to load AI data:', error);
            }
        }

        // ========== Start Setup Flow ==========
        function startSetupFlow() {
            if (!jwt) {
                // Show intro first, then language selection
                setTimeout(() => {
                    showLanguageScreen();
                }, 4000); // Show intro for 4 seconds
            } else {
                checkCompanionExists();
            }
        }

        async function checkCompanionExists() {
            try {
                showLoading('Checking setup status...');

                const response = await $.ajax({
                    url: 'app/actions/check_setup_status.php',
                    type: 'GET',
                    headers: { 'Authorization': 'Bearer ' + jwt },
                    data: { ai_code: aiCode },
                    dataType: 'json'
                });

                hideLoading();

                if (response.status === 'success') {
                    if (response.step === 'need_setup') {
                        companionId = response.companion_id;
                        sessionStorage.setItem('user_companion_id', companionId);
                        await loadQuestions();
                        showQuestionsScreen();
                    } else if (response.step === 'ready_to_chat') {
                        window.location.href = '?ai_chat_3d&ai_code=' + aiCode + '&lang=' + selectedLanguage;
                    } else {
                        showLanguageScreen();
                    }
                }
            } catch (error) {
                hideLoading();
                console.error('Check companion error:', error);
                showLanguageScreen();
            }
        }

        // ========== 1. Language Selection ==========
        function showLanguageScreen() {
            // Hide intro, show language screen with sidebar
            $('#introScreen').removeClass('active');
            $('#mainContent').addClass('with-sidebar');
            $('#aiSidebar').addClass('show');
            
            setTimeout(() => {
                $('.screen').removeClass('active');
                $('#languageScreen').addClass('active');
                
                // ✅ Speak language selection prompt
                if (typeof playSetupVoiceMessage === 'function') {
                    playSetupVoiceMessage('choose_language');
                }
            }, 600);
        }

        $('.language-option').on('click', function() {
            $('.language-option').removeClass('selected');
            $(this).addClass('selected');
            selectedLanguage = $(this).data('lang');
            
            // Update intro message
            $('#introMessage').html(introMessages[selectedLanguage]);
            
            $('#btnConfirmLanguage').prop('disabled', false);
            
            // ✅ Speak in selected language
            if (typeof playSetupVoiceMessage === 'function') {
                playSetupVoiceMessage('choose_language');
            }
        });

        $('#btnConfirmLanguage').on('click', function() {
            $(this).prop('disabled', true);
            
            // ✅ Show intro again with message in selected language
            showIntroToRegister();
        });

        // ========== Show Intro Before Register ==========
        function showIntroToRegister() {
            // Hide sidebar and language screen
            $('#aiSidebar').removeClass('show');
            $('#mainContent').removeClass('with-sidebar');
            $('.screen').removeClass('active');
            
            // Show intro screen with selected language message
            $('#introMessage').html(introMessages[selectedLanguage]);
            $('#introScreen').addClass('active');
            
            // ✅ Speak the intro message
            if (typeof playSetupVoiceMessage === 'function') {
                playSetupVoiceMessage('please_register');
            }
            
            // After 3 seconds, show register screen
            setTimeout(() => {
                showRegisterScreen();
            }, 3000);
        }

        // ========== 2. Register ==========
        function showRegisterScreen() {
            // Show sidebar and register screen
            $('#introScreen').removeClass('active');
            $('#mainContent').addClass('with-sidebar');
            $('#aiSidebar').addClass('show');
            
            setTimeout(() => {
                $('.screen').removeClass('active');
                $('#registerScreen').addClass('active');
            }, 600);
        }

        $('#registerForm').on('submit', function(e) {
            e.preventDefault();

            const name = $('#regName').val().trim();
            const email = $('#regEmail').val().trim();
            const phone = $('#regPhone').val().trim();
            const password = $('#regPassword').val();

            if (password.length < 6) {
                Swal.fire('Error', 'Password must be at least 6 characters', 'error');
                return;
            }

            showLoading('Creating your account...');

            $.ajax({
                url: 'app/actions/register_user.php',
                type: 'POST',
                data: {
                    name: name,
                    email: email,
                    phone: phone,
                    password: password,
                    ai_code: aiCode,
                    language: selectedLanguage
                },
                dataType: 'json',
                success: function(response) {
                    hideLoading();

                    if (response.status === 'success') {
                        userId = response.user_id;
                        
                        if (response.companion_id) {
                            companionId = response.companion_id;
                            sessionStorage.setItem('user_companion_id', companionId);
                        }

                        // ✅ Speak success message
                        if (typeof playSetupVoiceMessage === 'function') {
                            playSetupVoiceMessage('registration_success');
                        }

                        $('#otpContact').text(email);
                        showOTPScreen();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    hideLoading();
                    Swal.fire('Error', 'Registration failed', 'error');
                }
            });
        });

        $('#linkToLogin').on('click', function(e) {
            e.preventDefault();
            showLoginScreen();
        });

        // ========== 3. OTP ==========
        function showOTPScreen() {
            $('.screen').removeClass('active');
            $('#otpScreen').addClass('active');
            $('.otp-input').val('');
            $('#otp1').focus();
            
            // ✅ Speak OTP prompt
            if (typeof playSetupVoiceMessage === 'function') {
                playSetupVoiceMessage('otp_sent');
            }
        }

        $('.otp-input').on('input', function() {
            if (this.value.length === 1) {
                $(this).next('.otp-input').focus();
            }
        });

        $('.otp-input').on('keydown', function(e) {
            if (e.key === 'Backspace' && !this.value) {
                $(this).prev('.otp-input').focus();
            }
        });

        $('#btnVerifyOTP').on('click', function() {
            const otp = $('#otp1').val() + $('#otp2').val() + $('#otp3').val() + 
                        $('#otp4').val() + $('#otp5').val() + $('#otp6').val();

            if (otp.length !== 6) {
                Swal.fire('Error', 'Please enter all 6 digits', 'error');
                return;
            }

            showLoading('Verifying OTP...');

            $.ajax({
                url: 'app/actions/otp_confirm_email.php',
                type: 'POST',
                data: {
                    action: 'sendOTP',
                    userId: userId,
                    otpCode: otp,
                    method: 'email'
                },
                dataType: 'json',
                success: function(response) {
                    hideLoading();

                    if (response.status === 'succeed') {
                        // ✅ Speak verification success
                        if (typeof playSetupVoiceMessage === 'function') {
                            playSetupVoiceMessage('otp_verified');
                        }
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Verified!',
                            text: 'Account verified. Please login.',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            showLoginScreen();
                        });
                    } else {
                        Swal.fire('Error', 'Invalid OTP', 'error');
                    }
                },
                error: function() {
                    hideLoading();
                    Swal.fire('Error', 'Verification failed', 'error');
                }
            });
        });

        // ========== 4. Login ==========
        function showLoginScreen() {
            $('.screen').removeClass('active');
            $('#loginScreen').addClass('active');
            
            // ✅ Speak login prompt
            if (typeof playSetupVoiceMessage === 'function') {
                playSetupVoiceMessage('please_login');
            }
        }

        $('#loginForm').on('submit', function(e) {
            e.preventDefault();

            const username = $('#loginUsername').val().trim();
            const password = $('#loginPassword').val();

            showLoading('Logging in...');

            $.ajax({
                url: 'app/actions/check_login.php',
                type: 'POST',
                data: { username: username, password: password },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        jwt = response.jwt;
                        sessionStorage.setItem('jwt', jwt);

                        // ✅ Speak login success
                        if (typeof playSetupVoiceMessage === 'function') {
                            playSetupVoiceMessage('login_success');
                        }

                        checkCompanionExists();
                    } else {
                        hideLoading();
                        Swal.fire('Error', response.message || 'Login failed', 'error');
                    }
                },
                error: function() {
                    hideLoading();
                    Swal.fire('Error', 'Login failed', 'error');
                }
            });
        });

        $('#linkToRegister').on('click', function(e) {
            e.preventDefault();
            showRegisterScreen();
        });

        // ========== 5. Questions ==========
        async function loadQuestions() {
            try {
                const response = await $.ajax({
                    url: 'app/actions/get_personality_questions.php',
                    type: 'GET',
                    data: { lang: selectedLanguage },
                    dataType: 'json'
                });

                if (response.status === 'success') {
                    questions = response.data;
                    $('#totalQ').text(questions.length);
                    console.log('✅ Loaded', questions.length, 'questions');
                }
            } catch (error) {
                console.error('Failed to load questions:', error);
            }
        }

        function showQuestionsScreen() {
            $('.screen').removeClass('active');
            $('#questionsScreen').addClass('active');
            
            // ✅ Speak questions intro
            if (typeof playSetupVoiceMessage === 'function') {
                playSetupVoiceMessage('answer_questions');
            }

            if (questions.length > 0) {
                displayQuestion(0);
            }
        }

        function displayQuestion(index) {
            if (index < 0 || index >= questions.length) return;

            currentQuestionIndex = index;
            const question = questions[index];

            const progress = ((index + 1) / questions.length) * 100;
            $('#progressFill').css('width', progress + '%');
            $('#currentQ').text(index + 1);
            $('#questionNumber').text(`Question ${index + 1}`);

            const langCol = 'question_text_' + selectedLanguage;
            const questionText = question[langCol] || question.question_text_th;
            $('#questionText').text(questionText);

            // ✅ Speak the question
            if (typeof speakQuestionText === 'function') {
                speakQuestionText(questionText);
            }

            // Hide all input types first
            $('#choicesContainer').empty().hide();
            $('#scaleContainer').hide();

            // Display based on question type
            if (question.question_type === 'choice') {
                displayChoices(question);
            } else if (question.question_type === 'scale') {
                displayScale(question);
            } else if (question.question_type === 'text') {
                displayTextInput(question);
            }

            // Load previous answer if exists
            if (answers[question.question_id]) {
                loadPreviousAnswer(question);
                $('#btnNextQuestion').prop('disabled', false);
            } else {
                $('#btnNextQuestion').prop('disabled', true);
            }

            $('#btnPrevQuestion').prop('disabled', index === 0);

            if (index === questions.length - 1) {
                $('#btnNextQuestion').html('Complete <i class="fas fa-check"></i>');
            } else {
                $('#btnNextQuestion').html('Next <i class="fas fa-arrow-right"></i>');
            }
        }

        function displayChoices(question) {
            $('#choicesContainer').show();

            if (question.choices) {
                const choiceLangCol = 'choice_text_' + selectedLanguage;

                question.choices.forEach(choice => {
                    const choiceText = choice[choiceLangCol] || choice.choice_text_th;
                    const $choice = $(`
                        <div class="choice-option" data-choice-id="${choice.choice_id}">
                            ${choiceText}
                        </div>
                    `);
                    $('#choicesContainer').append($choice);
                });

                $('.choice-option').on('click', function() {
                    $('.choice-option').removeClass('selected');
                    $(this).addClass('selected');

                    const choiceId = $(this).data('choice-id');
                    answers[question.question_id] = {
                        question_id: question.question_id,
                        choice_id: choiceId
                    };

                    $('#btnNextQuestion').prop('disabled', false);
                });
            }
        }

        function displayScale(question) {
            $('#scaleContainer').show();
            $('#scaleOptions').empty();

            for (let i = 1; i <= 5; i++) {
                const $scale = $(`<div class="scale-option" data-value="${i}">${i}</div>`);
                $('#scaleOptions').append($scale);
            }

            $('.scale-option').on('click', function() {
                $('.scale-option').removeClass('selected');
                $(this).addClass('selected');

                const value = $(this).data('value');
                answers[question.question_id] = {
                    question_id: question.question_id,
                    scale_value: value
                };

                $('#btnNextQuestion').prop('disabled', false);
            });
        }

        function displayTextInput(question) {
            $('#choicesContainer').hide();
            $('#scaleContainer').hide();
            
            if ($('#textAnswerInput').length === 0) {
                const textInputHtml = `
                    <textarea 
                        id="textAnswerInput" 
                        class="form-control" 
                        rows="4" 
                        placeholder="Type your answer here..."
                        style="min-height: 120px; resize: vertical;"
                    ></textarea>
                `;
                $('#scaleContainer').after(textInputHtml);
            }
            
            $('#textAnswerInput').show().val('');

            $('#textAnswerInput').on('input', function() {
                const text = $(this).val().trim();
                
                if (text.length > 0) {
                    answers[question.question_id] = {
                        question_id: question.question_id,
                        text_answer: text
                    };
                    $('#btnNextQuestion').prop('disabled', false);
                } else {
                    delete answers[question.question_id];
                    $('#btnNextQuestion').prop('disabled', true);
                }
            });
        }

        function loadPreviousAnswer(question) {
            const answer = answers[question.question_id];

            if (question.question_type === 'choice' && answer.choice_id) {
                $(`.choice-option[data-choice-id="${answer.choice_id}"]`).addClass('selected');
            } else if (question.question_type === 'scale' && answer.scale_value) {
                $(`.scale-option[data-value="${answer.scale_value}"]`).addClass('selected');
            } else if (question.question_type === 'text' && answer.text_answer) {
                $('#textAnswerInput').val(answer.text_answer);
            }
        }

        $('#btnPrevQuestion').on('click', function() {
            displayQuestion(currentQuestionIndex - 1);
        });

        $('#btnNextQuestion').on('click', function() {
            if (currentQuestionIndex === questions.length - 1) {
                submitAnswers();
            } else {
                displayQuestion(currentQuestionIndex + 1);
            }
        });

        function submitAnswers() {
            showLoading('Saving your answers...');

            const answersArray = Object.values(answers);

            $.ajax({
                url: 'app/actions/save_personality_answers.php',
                type: 'POST',
                headers: { 'Authorization': 'Bearer ' + jwt },
                data: {
                    user_companion_id: companionId,
                    answers: JSON.stringify(answersArray)
                },
                dataType: 'json',
                success: function(response) {
                    hideLoading();

                    if (response.status === 'success') {
                        // ✅ Speak completion message
                        if (typeof playSetupVoiceMessage === 'function') {
                            playSetupVoiceMessage('setup_complete');
                        }
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Complete!',
                            text: 'Setup completed! Ready to chat.',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = '?ai_chat_3d&ai_code=' + aiCode + '&lang=' + selectedLanguage;
                        });
                    } else {
                        Swal.fire('Error', 'Failed to save answers', 'error');
                    }
                },
                error: function() {
                    hideLoading();
                    Swal.fire('Error', 'Failed to save answers', 'error');
                }
            });
        }

        // ========== Utils ==========
        function showLoading(text = 'Loading...') {
            $('#loadingText').text(text);
            $('#loadingOverlay').addClass('active');
            
            // ✅ Speak processing message
            if (typeof playSetupVoiceMessage === 'function') {
                playSetupVoiceMessage('processing');
            }
        }

        function hideLoading() {
            $('#loadingOverlay').removeClass('active');
        }

    </script>
</body>
</html>