<?php
require_once('lib/connect.php');
global $conn;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// รับ companion_id และ lang จาก URL
$companion_id = $_GET['companion_id'] ?? null;
$lang = $_GET['lang'] ?? 'th';

// ถ้าไม่มี companion_id redirect กลับหน้าแรก
if (!$companion_id) {
    header("Location: ?lang=$lang");
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activate Your AI Companion</title>
    
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
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
            min-height: 100vh;
            color: #fff;
            overflow-x: hidden;
        }

        .fullscreen-container {
            width: 100vw;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .gradient-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 50%, rgba(120, 119, 198, 0.1), transparent 50%),
                        radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.05), transparent 50%);
            pointer-events: none;
        }

        .card {
            max-width: 600px;
            width: 90%;
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 60px 50px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.6);
            text-align: center;
            position: relative;
            z-index: 1;
            animation: fadeInUp 0.8s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo {
            width: 100px;
            height: 100px;
            margin: 0 auto 30px;
            background: linear-gradient(135deg, #7877c6 0%, #a8a7e5 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            box-shadow: 0 10px 40px rgba(120, 119, 198, 0.4);
        }

        h1 {
            font-size: 36px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }

        .subtitle {
            color: rgba(255, 255, 255, 0.6);
            font-size: 16px;
            margin-bottom: 40px;
        }

        /* Step 2: Language Selection */
        .step-language {
            display: none;
        }

        .step-language.active {
            display: block;
        }

        .language-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }

        .language-option {
            padding: 24px 16px;
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }

        .language-option:hover {
            border-color: #7877c6;
            background: rgba(120, 119, 198, 0.1);
            transform: translateY(-4px);
        }

        .language-option.selected {
            border-color: #7877c6;
            background: rgba(120, 119, 198, 0.2);
            box-shadow: 0 8px 24px rgba(120, 119, 198, 0.3);
        }

        .language-flag {
            width: 48px;
            height: 36px;
            object-fit: cover;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .language-name {
            font-size: 14px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
        }

        .btn {
            width: 100%;
            padding: 18px;
            border: none;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #7877c6 0%, #a8a7e5 100%);
            color: white;
            box-shadow: 0 8px 24px rgba(120, 119, 198, 0.3);
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(120, 119, 198, 0.5);
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* Step 3: AI Preview - FULLSCREEN */
        .step-preview {
            display: none;
        }

        .step-preview.active {
            display: block;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: #000;
            z-index: 9999;
            overflow-y: auto;
        }

        .preview-content {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* AI Avatar Section - FULLSCREEN */
        .ai-avatar-section {
            width: 100vw;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
        }

        .ai-avatar-fullscreen {
            width: 100%;
            height: 100%;
            object-fit: contain;
            animation: zoomIn 1.5s ease;
        }

        @keyframes zoomIn {
            from {
                opacity: 0;
                transform: scale(1.1);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .avatar-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, transparent 100%);
            padding: 80px 40px 40px;
        }

        .ai-name-large {
            font-size: 56px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 16px;
            text-align: center;
            letter-spacing: -1px;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        }

        .ai-greeting {
            font-size: 24px;
            color: rgba(255, 255, 255, 0.8);
            text-align: center;
            font-weight: 300;
            letter-spacing: 0.5px;
        }

        /* Video Section - FULLSCREEN */
        .ai-video-section {
            width: 100vw;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #000;
            position: relative;
        }

        .ai-video-fullscreen {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .video-skip {
            position: absolute;
            bottom: 40px;
            right: 40px;
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: #fff;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .video-skip:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Description Section */
        .ai-description-section {
            width: 100vw;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
            padding: 80px 40px;
        }

        .description-content {
            max-width: 800px;
            text-align: center;
        }

        .ai-description-large {
            font-size: 22px;
            line-height: 1.8;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 60px;
            font-weight: 300;
        }

        .btn-start-large {
            padding: 24px 60px;
            font-size: 18px;
            background: linear-gradient(135deg, #7877c6 0%, #a8a7e5 100%);
            color: white;
            border: none;
            border-radius: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 2px;
            box-shadow: 0 12px 40px rgba(120, 119, 198, 0.4);
        }

        .btn-start-large:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 48px rgba(120, 119, 198, 0.6);
        }

        /* Loading */
        .loading {
            display: none;
            text-align: center;
            padding: 40px;
        }

        .loading.active {
            display: block;
        }

        .loading i {
            font-size: 48px;
            color: #7877c6;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error-message, .success-message {
            padding: 16px 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            display: none;
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .success-message {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #86efac;
        }

        .error-message.active, .success-message.active {
            display: block;
        }

        /* Speaking Indicator */
        .speaking-indicator {
            position: fixed;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            padding: 16px 32px;
            background: rgba(120, 119, 198, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(120, 119, 198, 0.4);
            border-radius: 12px;
            color: #fff;
            font-size: 14px;
            display: none;
            align-items: center;
            gap: 12px;
            z-index: 10000;
        }

        .speaking-indicator.active {
            display: flex;
        }

        .speaking-dot {
            width: 8px;
            height: 8px;
            background: #7877c6;
            border-radius: 50%;
            animation: pulse 1.5s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
        }

        @media (max-width: 768px) {
            .card {
                padding: 40px 30px;
            }

            h1 {
                font-size: 28px;
            }

            .ai-name-large {
                font-size: 36px;
            }

            .ai-greeting {
                font-size: 18px;
            }

            .ai-description-large {
                font-size: 18px;
            }

            .btn-start-large {
                padding: 20px 40px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="gradient-overlay"></div>

    <!-- Step 1: Loading (Auto-load AI data) -->
    <div class="fullscreen-container" id="step1Container">
        <div class="card">
            <div class="logo">
                <i class="fas fa-robot"></i>
            </div>

            <h1>Loading AI Companion</h1>
            <p class="subtitle">Please wait...</p>

            <div class="error-message" id="errorMessage"></div>
            <div class="success-message" id="successMessage"></div>

            <div class="loading active" id="loading">
                <i class="fas fa-spinner"></i>
                <p style="margin-top: 20px; color: rgba(255,255,255,0.6);">Loading...</p>
            </div>
        </div>
    </div>

    <!-- Step 2: Language Selection -->
    <div class="fullscreen-container step-language" id="stepLanguage">
        <div class="card">
            <h1>Choose Your Language</h1>
            <p class="subtitle">Select your preferred language</p>

            <div class="language-grid">
                <div class="language-option" data-lang="th" data-greeting="สวัสดี ฉันยินดีที่ได้รู้จักคุณ">
                    <img src="https://flagcdn.com/w320/th.png" class="language-flag" alt="Thai">
                    <span class="language-name">ไทย</span>
                </div>
                <div class="language-option" data-lang="en" data-greeting="Hello! Nice to meet you">
                    <img src="https://flagcdn.com/w320/gb.png" class="language-flag" alt="English">
                    <span class="language-name">English</span>
                </div>
                <div class="language-option" data-lang="cn" data-greeting="你好！很高兴认识你">
                    <img src="https://flagcdn.com/w320/cn.png" class="language-flag" alt="Chinese">
                    <span class="language-name">中文</span>
                </div>
                <div class="language-option" data-lang="jp" data-greeting="こんにちは！お会いできて嬉しいです">
                    <img src="https://flagcdn.com/w320/jp.png" class="language-flag" alt="Japanese">
                    <span class="language-name">日本語</span>
                </div>
                <div class="language-option" data-lang="kr" data-greeting="안녕하세요! 만나서 반갑습니다">
                    <img src="https://flagcdn.com/w320/kr.png" class="language-flag" alt="Korean">
                    <span class="language-name">한국어</span>
                </div>
            </div>
            <button class="btn btn-primary" id="btnConfirmLanguage" disabled>
                <i class="fas fa-arrow-right"></i> Continue
            </button>
        </div>
    </div>

    <!-- Step 3: AI Preview (FULLSCREEN) -->
    <div class="step-preview" id="stepPreview">
        <div class="preview-content">
            <!-- Avatar Fullscreen -->
            <div class="ai-avatar-section" id="avatarSection">
                <img src="" alt="AI Avatar" class="ai-avatar-fullscreen" id="aiAvatarImg">
                <div class="avatar-overlay">
                    <h2 class="ai-name-large" id="aiName">Loading...</h2>
                    <p class="ai-greeting" id="aiGreeting">สวัสดี</p>
                </div>
            </div>

            <!-- Video Fullscreen -->
            <div class="ai-video-section" id="videoSection" style="display: none;">
                <video class="ai-video-fullscreen" id="aiVideo" autoplay>
                    <source src="" type="video/mp4">
                </video>
                <button class="video-skip" onclick="skipVideo()">
                    <i class="fas fa-forward"></i> Skip
                </button>
            </div>

            <!-- Description Section -->
            <div class="ai-description-section" id="descriptionSection" style="display: none;">
                <div class="description-content">
                    <p class="ai-description-large" id="aiDescription">Your personal AI companion</p>
                    <button class="btn-start-large" id="btnStartQuestions">
                        <i class="fas fa-comments"></i> Start Personality Quiz
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Speaking Indicator -->
    <div class="speaking-indicator" id="speakingIndicator">
        <div class="speaking-dot"></div>
        <span id="speakingText">Speaking...</span>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let currentAI = null;
        let selectedLanguage = '<?= $lang ?>';
        let selectedGreeting = 'สวัสดี ฉันยินดีที่ได้รู้จักคุณ';
        let userId = null;
        let currentAudio = null;
        let isSpeaking = false;
        let companionId = <?= intval($companion_id) ?>; // รับจาก PHP

        // 🗣️ Video Greeting Messages
        const VIDEO_GREETINGS = {
            th: "สวัสดี ฉันยินดีที่ได้รู้จักคุณ เรามาทำความรู้จักกันหน่อยดีกว่า",
            en: "Hello! Nice to meet you. Let's get to know each other",
            cn: "你好！很高兴认识你。让我们互相了解一下吧",
            jp: "こんにちは！お会いできて嬉しいです。お互いを知り合いましょう",
            kr: "안녕하세요! 만나서 반갑습니다. 서로 알아가 봅시다"
        };

        const LANG_CODE_MAP = {
            'th': 'th',
            'en': 'en',
            'cn': 'zh',
            'jp': 'ja',
            'kr': 'ko'
        };

        const LANG_DISPLAY_MAP = {
            'th': 'Thai',
            'en': 'English',
            'cn': 'Chinese',
            'jp': 'Japanese',
            'kr': 'Korean'
        };

        $(document).ready(function() {
            const jwt = sessionStorage.getItem('jwt');
            
            if (!jwt) {
                showError('กรุณาเข้าสู่ระบบก่อนเปิดใช้งาน AI Companion');
                setTimeout(() => window.location.href = '?', 2000);
                return;
            }

            // โหลดข้อมูล AI จาก companion_id
            loadCompanionData();
        });

        function loadCompanionData() {
            const jwt = sessionStorage.getItem('jwt');
            
            $.ajax({
                url: 'app/actions/get_companion_data.php',
                type: 'GET',
                headers: { 'Authorization': 'Bearer ' + jwt },
                data: { companion_id: companionId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        userId = response.user_id;
                        currentAI = response.ai_data;
                        selectedLanguage = response.preferred_language || '<?= $lang ?>';
                        
                        hideLoading();
                        
                        // ไปหน้าเลือกภาษา
                        $('#step1Container').fadeOut(400, function() {
                            $('#stepLanguage').addClass('active');
                            
                            // Pre-select language if already set
                            if (selectedLanguage) {
                                $('.language-option[data-lang="' + selectedLanguage + '"]').addClass('selected');
                                $('#btnConfirmLanguage').prop('disabled', false);
                            }
                        });
                    } else {
                        showError(response.message || 'ไม่พบข้อมูล AI Companion');
                        setTimeout(() => window.location.href = '?', 2000);
                    }
                },
                error: function() {
                    showError('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
                    setTimeout(() => window.location.href = '?', 2000);
                }
            });
        }

        $('.language-option').on('click', function() {
            $('.language-option').removeClass('selected');
            $(this).addClass('selected');
            selectedLanguage = $(this).data('lang');
            selectedGreeting = $(this).data('greeting');
            $('#btnConfirmLanguage').prop('disabled', false);
        });

        $('#btnConfirmLanguage').on('click', function() {
            if (!selectedLanguage || !currentAI) {
                showError('กรุณาเลือกภาษา');
                return;
            }

            const langCol = 'ai_name_' + selectedLanguage;
            const descCol = 'perfume_knowledge_' + selectedLanguage;
            
            $('#aiName').text(currentAI[langCol] || currentAI.ai_name_th);
            $('#aiGreeting').text(selectedGreeting);
            $('#aiDescription').text(currentAI[descCol] || currentAI.perfume_knowledge_th || 'Your personal AI companion');
            
            if (currentAI.ai_avatar_url) {
                $('#aiAvatarImg').attr('src', currentAI.ai_avatar_url);
            }

            $('#stepLanguage').fadeOut(400, function() {
                $('#stepPreview').addClass('active');
                
                setTimeout(() => {
                    if (currentAI.ai_video_url) {
                        $('#aiVideo source').attr('src', currentAI.ai_video_url);
                        $('#aiVideo')[0].load();
                        
                        $('#avatarSection').fadeOut(600, function() {
                            $('#videoSection').fadeIn(600);
                            
                            setTimeout(() => {
                                const videoGreeting = VIDEO_GREETINGS[selectedLanguage] || VIDEO_GREETINGS.th;
                                speakText(videoGreeting, LANG_CODE_MAP[selectedLanguage]);
                            }, 800);
                            
                            $('#aiVideo')[0].onended = function() {
                                showDescriptionSection();
                            };
                        });
                    } else {
                        setTimeout(() => {
                            showDescriptionSection();
                        }, 2000);
                    }
                }, 3000);
            });
        });

        function skipVideo() {
            stopSpeaking();
            showDescriptionSection();
        }

        function showDescriptionSection() {
            $('#videoSection').fadeOut(600, function() {
                $('#descriptionSection').fadeIn(600);
            });
        }

        $('#btnStartQuestions').on('click', function() {
            stopSpeaking();
            
            if (!userId || !companionId || !selectedLanguage) {
                showError('ข้อมูลไม่ครบถ้วน กรุณาลองใหม่');
                return;
            }

            const jwt = sessionStorage.getItem('jwt');

            // อัพเดทภาษาที่เลือก
            $.ajax({
                url: 'app/actions/update_companion_language.php',
                type: 'POST',
                headers: { 'Authorization': 'Bearer ' + jwt },
                data: {
                    companion_id: companionId,
                    preferred_language: selectedLanguage
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        window.location.href = '?ai_questions&companion_id=' + companionId + '&lang=' + selectedLanguage;
                    } else {
                        showError(response.message || 'เกิดข้อผิดพลาด');
                    }
                },
                error: function() {
                    showError('ไม่สามารถบันทึกภาษาได้');
                }
            });
        });

        function speakText(text, langCode) {
            if (!text || isSpeaking) return;
            
            stopSpeaking();
            
            isSpeaking = true;
            updateSpeakingIndicator(true, LANG_DISPLAY_MAP[langCode] || 'Speaking');
            
            let ttsUrl;
            const encodedText = encodeURIComponent(text);
            
            if (langCode === 'th') {
                ttsUrl = `https://code.responsivevoice.org/getvoice.php?t=${encodedText}&tl=th&sv=&vn=&pitch=0.5&rate=0.5&vol=1`;
            } else {
                ttsUrl = `https://translate.google.com/translate_tts?ie=UTF-8&tl=${langCode}&client=tw-ob&q=${encodedText}`;
            }
            
            currentAudio = new Audio();
            
            currentAudio.oncanplaythrough = function() {
                this.play().catch(err => {
                    console.error('TTS play error:', err);
                    fallbackToWebSpeech(text, langCode);
                });
            };
            
            currentAudio.onplay = function() {
                isSpeaking = true;
                updateSpeakingIndicator(true, LANG_DISPLAY_MAP[langCode] || 'Speaking');
            };
            
            currentAudio.onended = function() {
                isSpeaking = false;
                updateSpeakingIndicator(false);
                currentAudio = null;
            };
            
            currentAudio.onerror = function(e) {
                console.error('TTS error:', e);
                fallbackToWebSpeech(text, langCode);
            };
            
            currentAudio.src = ttsUrl;
            currentAudio.load();
        }

        function stopSpeaking() {
            if (currentAudio) {
                currentAudio.pause();
                currentAudio = null;
            }
            
            if (window.speechSynthesis) {
                window.speechSynthesis.cancel();
            }
            
            isSpeaking = false;
            updateSpeakingIndicator(false);
        }

        function fallbackToWebSpeech(text, langCode) {
            if (!window.speechSynthesis) {
                isSpeaking = false;
                updateSpeakingIndicator(false);
                console.warn('Speech synthesis not available');
                return;
            }
            
            window.speechSynthesis.cancel();
            
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = langCode === 'th' ? 'th-TH' : 
                             langCode === 'zh' ? 'zh-CN' :
                             langCode === 'ja' ? 'ja-JP' :
                             langCode === 'ko' ? 'ko-KR' : 'en-US';
            utterance.rate = 0.9;
            utterance.pitch = 1.0;
            utterance.volume = 1.0;
            
            utterance.onstart = function() {
                isSpeaking = true;
            };
            
            utterance.onend = function() {
                isSpeaking = false;
                updateSpeakingIndicator(false);
            };
            
            utterance.onerror = function(event) {
                isSpeaking = false;
                updateSpeakingIndicator(false);
                console.error('Speech synthesis error:', event);
            };
            
            window.speechSynthesis.speak(utterance);
        }

        function updateSpeakingIndicator(speaking, language = 'Speaking') {
            if (speaking) {
                $('#speakingText').text(`Speaking in ${language}...`);
                $('#speakingIndicator').addClass('active');
            } else {
                $('#speakingIndicator').removeClass('active');
            }
        }

        function showLoading() {
            $('#loading').addClass('active');
        }

        function hideLoading() {
            $('#loading').removeClass('active');
        }

        function showError(message) {
            $('#errorMessage').text(message).addClass('active');
            setTimeout(() => $('#errorMessage').removeClass('active'), 5000);
        }

        function showSuccess(message) {
            $('#successMessage').text(message).addClass('active');
            setTimeout(() => $('#successMessage').removeClass('active'), 3000);
        }
    </script>
</body>
</html>