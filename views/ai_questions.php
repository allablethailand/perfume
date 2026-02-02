<?php
require_once('lib/connect.php');
global $conn;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// รับ parameters
$ai_code = $_GET['ai_code'] ?? $_SESSION['pending_ai_code'] ?? '';
$lang = $_GET['lang'] ?? $_SESSION['pending_ai_lang'] ?? 'en';

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
            height: 100vh;
            overflow: hidden;
            color: #fff;
        }

        /* ========== Main Container ========== */
        .setup-container {
            display: flex;
            height: 100vh;
            overflow: hidden;
            position: relative;
            background: #0a0a1e;
        }

        /* ========== Audio Wave Background ========== */
        .audio-wave-bg {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1;
            overflow: hidden;
            background: radial-gradient(ellipse at center, rgb(0 0 0) 0%, rgb(0 0 0) 70%);
        }

        .wave-container {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }

        .wave-svg {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 40%;
        }

        .wave-path {
            fill: none;
            stroke-width: 3;
            transition: stroke 0.5s ease;
            filter: blur(2px);
        }

        .wave-path-1 { stroke: #00d4ff; opacity: 0.3; }
        .wave-path-2 { stroke: #667eea; opacity: 0.25; }
        .wave-path-3 { stroke: #764ba2; opacity: 0.2; }

        .particles {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 2;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: #00d4ff;
            border-radius: 50%;
            box-shadow: 0 0 6px rgba(0, 212, 255, 0.6);
            animation: float-particle 12s linear infinite;
        }

        @keyframes float-particle {
            0% {
                transform: translateY(100vh) translateX(0);
                opacity: 0;
            }
            10% { opacity: 0.4; }
            90% { opacity: 0.4; }
            100% {
                transform: translateY(-100px) translateX(100px);
                opacity: 0;
            }
        }

        /* ========== Main Setup Area ========== */
        .setup-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #0a0a1e;
            height: 100%;
            overflow: hidden;
            position: relative;
        }

        /* ========== Avatar Container ========== */
        .avatar-container {
            flex: 1;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            z-index: 10;
            transition: all 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .avatar-container.with-choices {
            transform: translateX(-25%);
        }

        #avatarVideo {
            max-width: 80%;
            max-height: 80%;
            object-fit: contain;
            border-radius: 20px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.8);
            transition: all 0.4s ease;
        }

        /* ========== Chat Display (NEW) ========== */
        .chat-display {
            position: absolute;
            top: 80px;
            left: 30px;
            right: 30px;
            bottom: 120px;
            background: rgba(10, 10, 30, 0.6);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 25px;
            overflow-y: auto;
            z-index: 5;
            display: none;
        }

        .chat-display.show {
            display: block;
        }

        .chat-message {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            animation: slideInMessage 0.3s ease;
        }

        @keyframes slideInMessage {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .chat-message-ai {
            align-items: flex-start;
        }

        .chat-message-user {
            align-items: flex-end;
            flex-direction: row-reverse;
        }

        .chat-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7877c6 0%, #a8a7e5 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 18px;
            color: white;
            box-shadow: 0 4px 12px rgba(120, 119, 198, 0.4);
        }

        .chat-message-user .chat-avatar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .chat-bubble {
            max-width: 70%;
            padding: 14px 18px;
            border-radius: 18px;
            font-size: 15px;
            line-height: 1.6;
            word-wrap: break-word;
        }

        .chat-message-ai .chat-bubble {
            background: rgba(120, 119, 198, 0.2);
            border: 1px solid rgba(120, 119, 198, 0.3);
            color: rgba(255, 255, 255, 0.95);
        }

        .chat-message-user .chat-bubble {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.3) 0%, rgba(118, 75, 162, 0.3) 100%);
            border: 1px solid rgba(102, 126, 234, 0.4);
            color: rgba(255, 255, 255, 0.95);
            text-align: right;
        }

        /* ========== Choices Sidebar ========== */
        .choices-sidebar {
            position: absolute;
            top: 0;
            right: 0;
            width: 450px;
            height: 100%;
            background: rgba(10, 10, 30, 0.95);
            backdrop-filter: blur(20px);
            border-left: 1px solid rgba(255, 255, 255, 0.1);
            padding: 40px 30px;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            z-index: 50;
            overflow-y: auto;
        }

        .choices-sidebar.show {
            opacity: 1;
            transform: translateX(0);
        }

        .choices-header {
            margin-bottom: 30px;
        }

        .choices-title {
            font-size: 18px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 10px;
        }

        .choices-subtitle {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
        }

        /* ========== Language Flags Grid ========== */
        .language-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 30px;
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
            transform: translateY(-2px);
        }

        .language-option.selected {
            border-color: #7877c6;
            background: rgba(120, 119, 198, 0.2);
            box-shadow: 0 8px 24px rgba(120, 119, 198, 0.3);
        }

        .language-flag {
            width: 60px;
            height: 45px;
            margin: 0 auto 12px;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .language-flag img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .language-name {
            font-size: 14px;
            color: #fff;
            font-weight: 500;
        }

        /* ========== Registration Summary ========== */
        .registration-summary {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-label {
            color: rgba(255, 255, 255, 0.6);
            font-size: 13px;
            font-weight: 500;
        }

        .summary-value {
            color: #fff;
            font-size: 14px;
            font-weight: 500;
        }

        /* ========== Choice Options ========== */
        .choice-item {
            padding: 18px 24px;
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 12px;
            font-size: 15px;
        }

        .choice-item:hover {
            border-color: #7877c6;
            background: rgba(120, 119, 198, 0.1);
            transform: translateX(5px);
        }

        .choice-item.selected {
            border-color: #7877c6;
            background: rgba(120, 119, 198, 0.2);
            color: #fff;
        }

        /* ========== Scale Options ========== */
        .scale-container {
            padding: 20px 0;
        }

        .scale-labels {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6);
        }

        .scale-options {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
        }

        .scale-option {
            aspect-ratio: 1;
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
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

        /* ========== Buttons ========== */
        .sidebar-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        /* แก้ไข summary-item ให้มี flexbox */
        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        /* ปุ่มแก้ไข (Edit Button) */
        .edit-field-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(120, 119, 198, 0.2);
            border: 1px solid rgba(120, 119, 198, 0.4);
            color: rgba(255, 255, 255, 0.8);
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-left: 10px;
        }

        .edit-field-btn:hover {
            background: rgba(120, 119, 198, 0.4);
            border-color: #7877c6;
            color: #fff;
            transform: scale(1.05);
        }

        .edit-field-btn i {
            font-size: 14px;
        }

        /* ปุ่มบันทึก (Save Button) */
        .save-field-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
            border: 1px solid rgba(76, 175, 80, 0.6);
            color: white;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-left: 10px;
        }

        .save-field-btn:hover {
            background: linear-gradient(135deg, #66bb6a 0%, #4caf50 100%);
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4);
        }

        .save-field-btn i {
            font-size: 14px;
        }

        /* Input สำหรับแก้ไข */
        .edit-field-input {
            width: 100%;
            padding: 10px 14px;
            background: rgba(255, 255, 255, 0.08);
            border: 2px solid rgba(120, 119, 198, 0.4);
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }

        .edit-field-input:focus {
            outline: none;
            border-color: #7877c6;
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 0 0 3px rgba(120, 119, 198, 0.1);
        }

        .edit-field-input::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        .confirm-btn, .back-btn {
            flex: 1;
            padding: 14px 24px;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .confirm-btn {
            background: linear-gradient(135deg, #7877c6 0%, #a8a7e5 100%);
            color: white;
        }

        .confirm-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(120, 119, 198, 0.5);
        }

        .confirm-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .back-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: rgba(255, 255, 255, 0.8);
            display: none;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        /* ========== AI Speech Bubble ========== */
        .ai-speech-bubble {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -200px);
            max-width: 500px;
            padding: 20px 28px;
            background: linear-gradient(135deg, rgba(120, 119, 198, 0.35) 0%, rgba(168, 167, 229, 0.3) 100%);
            border: 2px solid rgba(120, 119, 198, 0.5);
            border-radius: 20px;
            color: white;
            z-index: 25;
            opacity: 0;
            pointer-events: none;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            box-shadow: 0 12px 40px rgba(120, 119, 198, 0.3);
        }

        .ai-speech-bubble.show {
            opacity: 1;
            transform: translate(-50%, -220px) scale(1);
        }

        .ai-speech-bubble::before {
            content: '';
            position: absolute;
            bottom: -16px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 16px solid transparent;
            border-right: 16px solid transparent;
            border-top: 18px solid rgba(120, 119, 198, 0.5);
        }

        .ai-speech-bubble::after {
            content: '';
            position: absolute;
            bottom: -12px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 14px solid transparent;
            border-right: 14px solid transparent;
            border-top: 15px solid rgba(120, 119, 198, 0.35);
        }

        .ai-speech-text {
            font-size: 15px;
            line-height: 1.7;
            text-align: center;
            margin: 0;
            position: relative;
            z-index: 1;
            font-weight: 500;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        /* ========== Typing Indicator ========== */
        .typing-indicator {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -150px);
            display: none;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: rgba(120, 119, 198, 0.2);
            border: 1px solid rgba(120, 119, 198, 0.4);
            border-radius: 20px;
            z-index: 24;
        }

        .typing-indicator.show {
            display: flex;
            animation: fadeInUp 0.3s ease;
        }

        .typing-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.8);
            animation: typingDot 1.4s infinite;
        }

        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }

        @keyframes typingDot {
            0%, 60%, 100% { transform: translateY(0); opacity: 0.7; }
            30% { transform: translateY(-10px); opacity: 1; }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translate(-50%, -140px);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -150px);
            }
        }

        /* ========== Thinking Icon ========== */
        .ai-thinking-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -150px);
            width: 40px;
            height: 40px;
            background: rgba(120, 119, 198, 0.3);
            border: 2px solid rgba(120, 119, 198, 0.6);
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 24;
        }

        .ai-thinking-icon.show {
            display: flex;
            animation: thinkingPulse 1.5s infinite;
        }

        @keyframes thinkingPulse {
            0%, 100% {
                transform: translate(-50%, -150px) scale(1);
                opacity: 0.8;
            }
            50% {
                transform: translate(-50%, -150px) scale(1.1);
                opacity: 1;
            }
        }

        .ai-thinking-icon i {
            color: rgba(255, 255, 255, 0.9);
            font-size: 18px;
        }

        /* ========== Chat Input ========== */
        .chat-input-area {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            padding: 20px 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            flex-shrink: 0;
            z-index: 20;
        }

        .input-wrapper {
            display: flex;
            gap: 12px;
            max-width: 900px;
            margin: 0 auto;
            align-items: flex-end;
        }

        .message-input {
            flex: 1;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            padding: 12px 20px;
            font-size: 14px;
            height: 44px;
            font-family: inherit;
            line-height: 1.5;
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .message-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .message-input:focus {
            outline: none;
            border-color: #667eea;
            background: rgba(255, 255, 255, 0.15);
        }

        .send-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7877c6 0%, #a8a7e5 100%);
            color: #fff;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.2s;
        }

        .send-btn:hover:not(:disabled) {
            transform: scale(1.05);
            box-shadow: 0 4px 16px rgba(120, 119, 198, 0.5);
        }

        .send-btn:disabled {
            background: rgba(255, 255, 255, 0.2);
            cursor: not-allowed;
        }

        /* ========== Form Fields ========== */
        .form-field {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #fff;
            font-size: 15px;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
            margin-bottom: 15px;
        }

        .form-field:focus {
            outline: none;
            border-color: #7877c6;
            background: rgba(255, 255, 255, 0.08);
        }

        .form-field::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        /* ========== OTP Inputs ========== */
        .otp-container {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 20px 0;
        }

        .otp-input {
            width: 45px;
            height: 50px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            padding: 0;
            margin-bottom: 0;
        }

        /* ========== Loading Overlay ========== */
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

        /* ========== Progress Bar ========== */
        .progress-bar-container {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            padding: 12px 20px;
            border-radius: 12px;
            z-index: 30;
            min-width: 200px;
        }

        .progress-text {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 8px;
            text-align: center;
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

        /* ========== Scrollbar ========== */
        .choices-sidebar::-webkit-scrollbar,
        .chat-display::-webkit-scrollbar {
            width: 6px;
        }

        .choices-sidebar::-webkit-scrollbar-track,
        .chat-display::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
        }

        .choices-sidebar::-webkit-scrollbar-thumb,
        .chat-display::-webkit-scrollbar-thumb {
            background: rgba(120, 119, 198, 0.5);
            border-radius: 3px;
        }

        /* ========== Responsive ========== */
        @media (max-width: 992px) {
            .choices-sidebar {
                width: 100%;
            }

            .avatar-container.with-choices {
                transform: scale(0.6);
            }

            .ai-speech-bubble {
                max-width: calc(100vw - 60px);
            }

            .chat-display {
                left: 15px;
                right: 15px;
                top: 60px;
                bottom: 100px;
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

    <div class="setup-container">
        <div class="setup-main">
            <!-- Audio Wave Background -->
            <div class="audio-wave-bg">
                <div class="wave-container">
                    <svg class="wave-svg" viewBox="0 0 1200 300" preserveAspectRatio="none">
                        <path class="wave-path wave-path-1" d=""></path>
                        <path class="wave-path wave-path-2" d=""></path>
                        <path class="wave-path wave-path-3" d=""></path>
                    </svg>
                </div>
                <div class="particles" id="particlesContainer"></div>
            </div>

            <!-- Avatar Container -->
            <div class="avatar-container" id="avatarContainer">
                <video id="avatarVideo" muted playsinline loop></video>
                
                <!-- AI Speech Bubble (above avatar) -->
                <div class="ai-speech-bubble" id="aiSpeechBubble">
                    <p class="ai-speech-text" id="aiSpeechText"></p>
                </div>
                
                <!-- Typing Indicator -->
                <div class="typing-indicator" id="typingIndicator">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
                
                <!-- Thinking Icon -->
                <div class="ai-thinking-icon" id="thinkingIcon">
                    <i class="fas fa-comment-dots"></i>
                </div>
            </div>

            <!-- Chat Display (NEW) -->
            <div class="chat-display" id="chatDisplay"></div>

            <!-- Progress Bar -->
            <div class="progress-bar-container" id="progressBar" style="display: none;">
                <div class="progress-text">
                    <span id="currentQ">1</span> / <span id="totalQ">10</span> Questions
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill" style="width: 10%;"></div>
                </div>
            </div>

            <!-- Choices Sidebar -->
            <div class="choices-sidebar" id="choicesSidebar">
                <div class="choices-header">
                    <div class="choices-title" id="choicesTitle">Choose an option</div>
                    <div class="choices-subtitle" id="choicesSubtitle">Select your answer</div>
                </div>
                <div id="choicesContent"></div>
                <div class="sidebar-buttons">
                    <button class="back-btn" id="backBtn">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                    <button class="confirm-btn" id="confirmBtn" disabled>
                        <i class="fas fa-check"></i> Confirm
                    </button>
                </div>
            </div>

            <!-- Chat Input Area -->
            <div class="chat-input-area">
                <div class="input-wrapper">
                    <input 
                        type="text"
                        class="message-input" 
                        id="messageInput" 
                        placeholder="Type your message..."
                        autocomplete="off"
                    />
                    <button class="send-btn" id="sendBtn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="app/js/ai_setup_avatar.js?v=<?php echo time(); ?>"></script>

</body>
</html>