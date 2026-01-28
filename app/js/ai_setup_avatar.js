/**
 * AI Setup Avatar - Improved Voice & Animation System
 * ✅ Video Avatar (No Circle Border - Minimal Design)
 * ✅ Centered Intro → Move to Sidebar
 * ✅ Speaking/Idle Video Switching
 * ✅ Multi-language Voice (th, en, cn, jp, kr)
 */

// ========== Global Avatar Variables ==========
let introVideoAvatar = null;
let sidebarVideoAvatar = null;
let isSetupSpeaking = false;
let setupIdleVideoUrl = '';
let setupSpeakingVideoUrl = '';
let currentIntroVideoState = 'idle';
let currentSidebarVideoState = 'idle';
let isIntroTransitioning = false;
let isSidebarTransitioning = false;
let currentSetupAudio = null;

// ========== Voice Messages (5 Languages) ==========
const setupVoiceMessages = {
    // Welcome & Intro
    welcome: {
        th: "ยินดีที่ได้รู้จัก ก่อนอื่นผมอยากรู้จักคุณมากขึ้น โปรดตอบคำถามให้ครบก่อนนะ",
        en: "Nice to meet you. First, I'd like to know you better. Please answer all questions",
        cn: "很高兴认识你。首先，我想更好地了解你。请回答所有问题",
        jp: "お会いできて嬉しいです。まず、あなたのことをもっと知りたいです。すべての質問に答えてください",
        kr: "만나서 반가워요. 먼저 당신을 더 잘 알고 싶어요. 모든 질문에 답해주세요"
    },
    
    choose_language: {
        th: "กรุณาเลือกภาษาที่คุณต้องการใช้งาน",
        en: "Please choose your preferred language",
        cn: "请选择您喜欢的语言",
        jp: "お好みの言語を選択してください",
        kr: "원하는 언어를 선택하세요"
    },
    
    // Register Screen
    please_register: {
        th: "ตอนนี้มาลงทะเบียนเพื่อเริ่มต้นกันเลย",
        en: "Now let's register to get started",
        cn: "现在让我们注册开始吧",
        jp: "それでは登録して始めましょう",
        kr: "이제 등록하여 시작해요"
    },
    
    // Login Screen
    please_login: {
        th: "กรุณาเข้าสู่ระบบด้วยอีเมลหรือเบอร์โทรศัพท์ของคุณ",
        en: "Please login with your email or phone number",
        cn: "请使用您的电子邮件或电话号码登录",
        jp: "メールアドレスまたは電話番号でログインしてください",
        kr: "이메일 또는 전화번호로 로그인하세요"
    },
    
    // OTP Screen
    otp_sent: {
        th: "ฉันได้ส่งรหัส OTP ไปที่อีเมลของคุณแล้ว กรุณากรอกรหัส 6 หลัก",
        en: "I've sent an OTP code to your email. Please enter the 6-digit code",
        cn: "我已将OTP代码发送到您的电子邮件。请输入6位数字代码",
        jp: "メールにOTPコードを送信しました。6桁のコードを入力してください",
        kr: "이메일로 OTP 코드를 보냈습니다. 6자리 코드를 입력하세요"
    },
    
    // Questions Screen
    answer_questions: {
        th: "ตอนนี้ฉันอยากรู้จักคุณมากขึ้น กรุณาตอบคำถามต่อไปนี้",
        en: "Now I'd like to get to know you better. Please answer the following questions",
        cn: "现在我想更好地了解您。请回答以下问题",
        jp: "今、あなたのことをもっと知りたいです。次の質問に答えてください",
        kr: "이제 당신을 더 잘 알고 싶습니다. 다음 질문에 답해주세요"
    },
    
    // Success Messages
    registration_success: {
        th: "สมัครสมาชิกสำเร็จแล้ว กรุณายืนยัน OTP",
        en: "Registration successful! Please verify OTP",
        cn: "注册成功！请验证OTP",
        jp: "登録成功！OTPを確認してください",
        kr: "등록 성공! OTP를 확인하세요"
    },
    
    login_success: {
        th: "เข้าสู่ระบบสำเร็จ กำลังโหลดข้อมูล",
        en: "Login successful! Loading data",
        cn: "登录成功！正在加载数据",
        jp: "ログイン成功！データを読み込んでいます",
        kr: "로그인 성공! 데이터 로딩 중"
    },
    
    otp_verified: {
        th: "ยืนยัน OTP สำเร็จ กรุณาเข้าสู่ระบบ",
        en: "OTP verified successfully! Please login",
        cn: "OTP验证成功！请登录",
        jp: "OTP認証成功！ログインしてください",
        kr: "OTP 인증 성공! 로그인하세요"
    },
    
    setup_complete: {
        th: "เยี่ยมเลย เราพร้อมคุยกันแล้ว มาเริ่มการสนทนากันเลย",
        en: "Great! We're ready to chat. Let's start our conversation",
        cn: "太好了！我们准备好聊天了。让我们开始对话",
        jp: "素晴らしい！チャットの準備ができました。会話を始めましょう",
        kr: "훌륭해요! 채팅 준비가 완료되었습니다. 대화를 시작해요"
    },
    
    // Progress Messages
    processing: {
        th: "กำลังดำเนินการ กรุณารอสักครู่",
        en: "Processing... Please wait",
        cn: "处理中...请稍候",
        jp: "処理中...お待ちください",
        kr: "처리 중... 잠시 기다려주세요"
    }
};

// ========== Initialize Setup Avatar ==========
function initSetupAvatar() {
    console.log('🎬 Initializing Setup Avatar...');
    
    // Load AI data to get video URLs
    loadSetupAIData().then(() => {
        if (setupIdleVideoUrl && setupSpeakingVideoUrl) {
            createIntroVideoAvatar();
            createSidebarVideoAvatar();
            
            // Play welcome message after 1 second
            setTimeout(() => {
                playSetupVoiceMessage('welcome');
            }, 1000);
        } else {
            console.warn('⚠️ No video URLs found for setup avatar');
        }
    });
}

// ========== Load AI Data ==========
async function loadSetupAIData() {
    try {
        const response = await $.ajax({
            url: 'app/actions/get_ai_data.php',
            type: 'GET',
            data: { ai_code: aiCode },
            dataType: 'json'
        });

        if (response.status === 'success' && response.ai_data) {
            const aiData = response.ai_data;
            setupIdleVideoUrl = aiData.idle_video_url || '';
            setupSpeakingVideoUrl = aiData.talking_video_url || '';
            
            console.log('✅ Setup Avatar URLs loaded:', {
                idle: setupIdleVideoUrl ? '✅' : '❌',
                speaking: setupSpeakingVideoUrl ? '✅' : '❌'
            });
        }
    } catch (error) {
        console.error('❌ Failed to load setup AI data:', error);
    }
}

// ========== Create Intro Video Avatar (Center Screen) ==========
function createIntroVideoAvatar() {
    const container = $('#introVideoAvatar')[0];
    if (!container) return;
    
    introVideoAvatar = container;
    introVideoAvatar.muted = true;
    introVideoAvatar.playsInline = true;
    introVideoAvatar.loop = true;
    introVideoAvatar.preload = 'auto';
    introVideoAvatar.src = setupIdleVideoUrl;
    currentIntroVideoState = 'idle';
    
    introVideoAvatar.addEventListener('loadeddata', function() {
        console.log('✅ Intro idle video loaded');
        introVideoAvatar.play().catch(e => console.log('Autoplay prevented'));
    });
    
    introVideoAvatar.addEventListener('error', function(e) {
        console.error('❌ Intro video error:', e);
    });
    
    introVideoAvatar.load();
}

// ========== Create Sidebar Video Avatar ==========
function createSidebarVideoAvatar() {
    const container = $('#sidebarVideoAvatar')[0];
    if (!container) return;
    
    sidebarVideoAvatar = container;
    sidebarVideoAvatar.muted = true;
    sidebarVideoAvatar.playsInline = true;
    sidebarVideoAvatar.loop = true;
    sidebarVideoAvatar.preload = 'auto';
    sidebarVideoAvatar.src = setupIdleVideoUrl;
    currentSidebarVideoState = 'idle';
    
    sidebarVideoAvatar.addEventListener('loadeddata', function() {
        console.log('✅ Sidebar idle video loaded');
        sidebarVideoAvatar.play().catch(e => console.log('Autoplay prevented'));
    });
    
    sidebarVideoAvatar.addEventListener('error', function(e) {
        console.error('❌ Sidebar video error:', e);
    });
    
    sidebarVideoAvatar.load();
}

// ========== Play Speaking Animation ==========
function playSetupSpeakingAnimation() {
    // Switch intro video to speaking if visible
    if ($('#introScreen').hasClass('active') && introVideoAvatar) {
        if (currentIntroVideoState !== 'speaking') {
            switchVideoSource(introVideoAvatar, setupSpeakingVideoUrl, 'intro', 'speaking');
        }
    }
    
    // Switch sidebar video to speaking if visible
    if ($('#aiSidebar').hasClass('show') && sidebarVideoAvatar) {
        if (currentSidebarVideoState !== 'speaking') {
            switchVideoSource(sidebarVideoAvatar, setupSpeakingVideoUrl, 'sidebar', 'speaking');
        }
    }
}

// ========== Play Idle Animation ==========
function playSetupIdleAnimation() {
    // Switch intro video to idle if visible
    if ($('#introScreen').hasClass('active') && introVideoAvatar) {
        if (currentIntroVideoState !== 'idle') {
            switchVideoSource(introVideoAvatar, setupIdleVideoUrl, 'intro', 'idle');
        }
    }
    
    // Switch sidebar video to idle if visible
    if ($('#aiSidebar').hasClass('show') && sidebarVideoAvatar) {
        if (currentSidebarVideoState !== 'idle') {
            switchVideoSource(sidebarVideoAvatar, setupIdleVideoUrl, 'sidebar', 'idle');
        }
    }
}

// ========== Switch Video Source ==========
function switchVideoSource(videoElement, newSrc, videoType, newState) {
    if (!videoElement || !newSrc) return;
    
    // Smooth transition
    videoElement.style.opacity = '0.3';
    
    setTimeout(() => {
        videoElement.src = newSrc;
        videoElement.load();
        
        videoElement.addEventListener('canplay', function onCanPlay() {
            videoElement.removeEventListener('canplay', onCanPlay);
            
            videoElement.play().then(() => {
                videoElement.style.opacity = '1';
                
                if (videoType === 'intro') {
                    currentIntroVideoState = newState;
                } else if (videoType === 'sidebar') {
                    currentSidebarVideoState = newState;
                }
                
                console.log(`✅ ${videoType} switched to ${newState}`);
            }).catch(e => {
                console.error('Play error:', e);
                videoElement.style.opacity = '1';
            });
        });
    }, 300);
}

// ========== Play Voice Message ==========
function playSetupVoiceMessage(messageKey) {
    const message = setupVoiceMessages[messageKey];
    if (!message || !message[selectedLanguage]) {
        console.warn('⚠️ Voice message not found:', messageKey, selectedLanguage);
        return;
    }
    
    const text = message[selectedLanguage];
    console.log(`🔊 Setup Speaking (${messageKey}):`, text);
    
    // ✅ Show speech bubble
    showAISpeechBubble(text, 6000);
    
    speakSetupText(text);
}

// ========== Speak Text with TTS ==========
function speakSetupText(text) {
    // Stop any current audio
    if (currentSetupAudio) {
        currentSetupAudio.pause();
        currentSetupAudio = null;
    }
    
    isSetupSpeaking = true;
    playSetupSpeakingAnimation();
    
    // Update status
    $('#statusText').text('Speaking...');
    
    const encodedText = encodeURIComponent(text);
    let ttsUrl;
    
    // Choose TTS service based on language
    if (selectedLanguage === 'th') {
        ttsUrl = `https://code.responsivevoice.org/getvoice.php?t=${encodedText}&tl=th&sv=&vn=&pitch=0.5&rate=0.5&vol=1`;
    } else {
        let googleLang = selectedLanguage;
        if (selectedLanguage === 'cn') googleLang = 'zh-CN';
        if (selectedLanguage === 'jp') googleLang = 'ja';
        if (selectedLanguage === 'kr') googleLang = 'ko';
        
        ttsUrl = `https://translate.google.com/translate_tts?ie=UTF-8&tl=${googleLang}&client=tw-ob&q=${encodedText}`;
    }
    
    currentSetupAudio = new Audio();
    
    currentSetupAudio.oncanplaythrough = function() {
        this.play().catch(err => {
            console.error('TTS play error:', err);
            stopSetupSpeaking();
        });
    };
    
    currentSetupAudio.onended = function() {
        stopSetupSpeaking();
    };
    
    currentSetupAudio.onerror = function(e) {
        console.error('❌ TTS error, trying Web Speech API');
        fallbackSetupWebSpeech(text);
    };
    
    currentSetupAudio.src = ttsUrl;
    currentSetupAudio.load();
}

// ========== Fallback Web Speech API ==========
function fallbackSetupWebSpeech(text) {
    if (!window.speechSynthesis) {
        stopSetupSpeaking();
        return;
    }
    
    window.speechSynthesis.cancel();
    
    const utterance = new SpeechSynthesisUtterance(text);
    
    if (selectedLanguage === 'th') {
        utterance.lang = 'th-TH';
    } else if (selectedLanguage === 'cn') {
        utterance.lang = 'zh-CN';
    } else if (selectedLanguage === 'jp') {
        utterance.lang = 'ja-JP';
    } else if (selectedLanguage === 'kr') {
        utterance.lang = 'ko-KR';
    } else {
        utterance.lang = 'en-US';
    }
    
    utterance.rate = 0.85;
    utterance.pitch = 1.0;
    utterance.volume = 1.0;
    
    utterance.onend = function() {
        stopSetupSpeaking();
    };
    
    utterance.onerror = function(event) {
        console.error('Web Speech error:', event);
        stopSetupSpeaking();
    };
    
    window.speechSynthesis.speak(utterance);
}

// ========== Stop Speaking ==========
function stopSetupSpeaking() {
    isSetupSpeaking = false;
    playSetupIdleAnimation();
    $('#statusText').text('Setting Up');
}

// ========== Show AI Speech Bubble ==========
function showAISpeechBubble(text, duration = 5000) {
    const $bubble = $('#aiSpeechBubble');
    const $text = $('#aiSpeechText');
    
    if (!$bubble.length) {
        console.warn('⚠️ Speech bubble element not found');
        return;
    }
    
    $text.text(text);
    $bubble.addClass('show');
    
    // Auto hide after duration
    setTimeout(() => {
        $bubble.removeClass('show');
    }, duration);
}

// ========== Hide AI Speech Bubble ==========
function hideAISpeechBubble() {
    $('#aiSpeechBubble').removeClass('show');
}

// ========== Speak Question Text ==========
function speakQuestionText(questionText) {
    console.log('🔊 Speaking Question:', questionText);
    
    // ✅ Show speech bubble with question
    showAISpeechBubble(questionText, 8000);
    
    speakSetupText(questionText);
}

// ========== Export Functions ==========
window.initSetupAvatar = initSetupAvatar;
window.playSetupVoiceMessage = playSetupVoiceMessage;
window.speakQuestionText = speakQuestionText;
window.stopSetupSpeaking = stopSetupSpeaking;
window.showAISpeechBubble = showAISpeechBubble;
window.hideAISpeechBubble = hideAISpeechBubble;

console.log('✅ AI Setup Avatar System (Improved) Loaded');