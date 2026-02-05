/**
 * AI Setup Chat - Conversational Setup Flow (IMPROVED)
 * ✅ Chat-style interface like ai_chat_3d
 * ✅ AI asks questions one by one
 * ✅ Avatar moves left when choices appear
 * ✅ Support all question types (choice, scale, text)
 * ✅ Multi-language voice support
 * ✅ Added Last Name field
 * ✅ Voice feedback when selecting language
 * ✅ Voice feedback when selecting choices
 * ✅ Back button to edit previous answers
 * ✅ Better login UI with chat display
 * ✅ Edit registration fields before confirm (NEW)
 */

// ========== Global Variables ==========
const urlParams = new URLSearchParams(window.location.search);
const aiCode = urlParams.get('ai_code') || '';
let selectedLanguage = 'en';
let jwt = sessionStorage.getItem('jwt');
let companionId = null;
let userId = null;
let aiCompanionData = null;
let questions = [];
let currentQuestionIndex = 0;
let answers = {};
let currentStep = 'intro'; // intro, language, register, otp, login, questions
let chatHistory = [];
let stepHistory = []; // Track step history for back button

// Avatar video URLs
let idleVideoUrl = '';
let speakingVideoUrl = '';
let isSpeaking = false;
let currentAudio = null;

// Global for wave animation
window.isSpeaking = false;
window.waveIntensity = 0;

// ========== Conversation Messages ==========
const conversationMessages = {
    intro: {
        th: "สวัสดี! ยินดีที่ได้รู้จัก ฉันชื่อ {ai_name} ก่อนที่เราจะเริ่มคุยกัน ฉันอยากรู้จักคุณมากขึ้นหน่อย",
        en: "Hello! Nice to meet you. I'm {ai_name}. Before we start chatting, I'd like to get to know you better",
        cn: "你好！很高兴认识你。我是{ai_name}。在我们开始聊天之前，我想更好地了解你",
        jp: "こんにちは！お会いできて嬉しいです。私は{ai_name}です。チャットを始める前に、あなたのことをもっと知りたいです",
        kr: "안녕하세요! 만나서 반가워요. 저는 {ai_name}입니다. 대화를 시작하기 전에 당신에 대해 더 알고 싶어요"
    },
    choose_language: {
        th: "ก่อนอื่นเลย คุณต้องการใช้ภาษาอะไรในการสนทนากับฉัน?",
        en: "First of all, which language would you like to use to chat with me?",
        cn: "首先，您想用什么语言和我聊天？",
        jp: "まず、私とどの言語でチャットしますか？",
        kr: "먼저, 저와 어떤 언어로 대화하시겠습니까?"
    },
    language_selected: {
        th: "เยี่ยมเลย! เราจะใช้ภาษาไทยในการสนทนากัน",
        en: "Great! We'll chat in English",
        cn: "太好了！我们将用中文聊天",
        jp: "素晴らしい！日本語でチャットします",
        kr: "좋아요! 한국어로 대화하겠습니다"
    },
    need_register: {
        th: "ตอนนี้มาลงทะเบียนเพื่อเริ่มต้นกันเลย เริ่มจากชื่อของคุณก่อนนะ ชื่ออะไรครับ?",
        en: "Now let's register to get started. Let's start with your name. What's your first name?",
        cn: "现在让我们注册开始吧。让我们从您的名字开始。您的名字是什么？",
        jp: "それでは登録して始めましょう。お名前から始めましょう。お名前は？",
        kr: "이제 등록하여 시작해요. 이름부터 시작하죠. 이름이 뭐예요?"
    },
    ask_lastname: {
        th: "ขอบคุณ {name}! และนามสกุลของคุณคืออะไรครับ?",
        en: "Thank you {name}! And what's your last name?",
        cn: "谢谢{name}！您的姓氏是什么？",
        jp: "{name}さん、ありがとうございます！苗字は何ですか？",
        kr: "{name}님, 감사합니다! 성은 무엇입니까?"
    },
    ask_email: {
        th: "เยี่ยมเลย {name} {lastname}! ถัดไป ช่วยบอกอีเมลของคุณหน่อยได้ไหม?",
        en: "Great {name} {lastname}! Next, could you tell me your email address?",
        cn: "太好了{name} {lastname}！接下来，您能告诉我您的电子邮件地址吗？",
        jp: "素晴らしい{name} {lastname}さん！次に、メールアドレスを教えていただけますか？",
        kr: "좋아요 {name} {lastname}님! 다음으로 이메일 주소를 알려주시겠어요?"
    },
    ask_phone: {
        th: "ได้เลย และเบอร์โทรศัพท์ของคุณล่ะ?",
        en: "Got it! And what's your phone number?",
        cn: "知道了！您的电话号码是多少？",
        jp: "わかりました！電話番号は何ですか？",
        kr: "알겠습니다! 전화번호는 무엇입니까?"
    },
    ask_password: {
        th: "เกือบเสร็จแล้ว! ตั้งรหัสผ่านสักหน่อยนะ (อย่างน้อย 6 ตัวอักษร)",
        en: "Almost done! Please set a password (at least 6 characters)",
        cn: "快完成了！请设置密码（至少6个字符）",
        jp: "もうすぐ完了です！パスワードを設定してください（6文字以上）",
        kr: "거의 다 됐어요! 비밀번호를 설정하세요（최소 6자）"
    },
    confirm_registration: {
        th: "ยืนยันข้อมูลการลงทะเบียนของคุณ",
        en: "Confirm your registration details",
        cn: "确认您的注册详细信息",
        jp: "登録情報を確認してください",
        kr: "등록 세부 정보를 확인하세요"
    },
    otp_sent: {
        th: "ฉันได้ส่งรหัส OTP 6 หลักไปที่ {email} แล้ว กรุณากรอกรหัสที่ได้รับ",
        en: "I've sent a 6-digit OTP code to {email}. Please enter the code you received",
        cn: "我已将6位数字OTP代码发送到{email}。请输入您收到的代码",
        jp: "{email}に6桁のOTPコードを送信しました。受け取ったコードを入力してください",
        kr: "{email}로 6자리 OTP 코드를 보냈습니다. 받은 코드를 입력하세요"
    },
    otp_success: {
        th: "ยืนยันสำเร็จ! ตอนนี้คุณสามารถเข้าสู่ระบบได้แล้ว",
        en: "Verified successfully! You can now login",
        cn: "验证成功！您现在可以登录了",
        jp: "確認成功！今すぐログインできます",
        kr: "확인 성공! 이제 로그인할 수 있습니다"
    },
    ask_login: {
        th: "กรุณาเข้าสู่ระบบด้วยอีเมลหรือเบอร์โทรของคุณ",
        en: "Please login with your email or phone number",
        cn: "请使用您的电子邮件或电话登录",
        jp: "メールまたは電話番号でログインしてください",
        kr: "이메일 또는 전화번호로 로그인하세요"
    },
    login_success: {
        th: "ยินดีต้อนรับกลับมา! มาเริ่มทำความรู้จักกันต่อเลยดีกว่า",
        en: "Welcome back! Let's continue getting to know each other",
        cn: "欢迎回来！让我们继续了解彼此",
        jp: "お帰りなさい！お互いを知り続けましょう",
        kr: "돌아오신 것을 환영합니다! 계속 서로 알아가요"
    },
    start_questions: {
        th: "ตอนนี้ฉันจะถามคำถามไม่กี่ข้อเพื่อให้เข้าใจคุณดีขึ้น พร้อมแล้วใช่ไหม?",
        en: "Now I'll ask you a few questions to understand you better. Ready?",
        cn: "现在我会问你几个问题以更好地了解你。准备好了吗？",
        jp: "今、あなたをよりよく理解するためにいくつか質問します。準備はいいですか？",
        kr: "이제 당신을 더 잘 이해하기 위해 몇 가지 질문을 하겠습니다. 준비됐나요?"
    },
    all_done: {
        th: "เยี่ยมมาก! เราทำความรู้จักกันเสร็จแล้ว ตอนนี้เราพร้อมคุยกันได้เลย",
        en: "Excellent! We're all set. Now we can start chatting!",
        cn: "太好了！我们都准备好了。现在我们可以开始聊天了！",
        jp: "素晴らしい！準備完了です。さあチャットを始めましょう！",
        kr: "훌륭해요! 모두 준비됐습니다. 이제 채팅을 시작할 수 있어요!"
    }
};

// ========== Choice Feedback Templates ==========
const choiceFeedbackTemplates = {
    th: [
        "เข้าใจแล้ว คุณเลือก {choice}",
        "ได้เลย {choice} สินะ",
        "โอเค คุณชอบ {choice}",
        "เข้าใจแล้ว คุณตอบว่า {choice}",
        "ดีเลย คุณเลือก {choice}"
    ],
    en: [
        "I see, you chose {choice}",
        "Okay, {choice}",
        "Got it, you prefer {choice}",
        "Understood, you answered {choice}",
        "Great, you selected {choice}"
    ],
    cn: [
        "我明白了，你选择了 {choice}",
        "好的，{choice}",
        "知道了，你更喜欢 {choice}",
        "明白了，你回答 {choice}",
        "很好，你选择了 {choice}"
    ],
    jp: [
        "わかりました、{choice} を選びましたね",
        "はい、{choice} ですね",
        "了解しました、{choice} がお好きなんですね",
        "理解しました、{choice} と答えましたね",
        "いいですね、{choice} を選択しました"
    ],
    kr: [
        "알겠습니다, {choice}를 선택하셨군요",
        "네, {choice}",
        "알았어요, {choice}를 선호하시는군요",
        "이해했습니다, {choice}라고 답하셨네요",
        "좋아요, {choice}를 선택하셨어요"
    ]
};

// ========== Scale Feedback Templates ==========
const scaleFeedbackTemplates = {
    th: [
        "โอเค คุณให้คะแนน {value} คะแนน",
        "เข้าใจแล้ว {value} คะแนนสินะ",
        "ได้เลย คุณให้ {value}",
        "เข้าใจแล้ว ระดับ {value}"
    ],
    en: [
        "Okay, you rated {value}",
        "Got it, {value} points",
        "I see, you gave {value}",
        "Understood, level {value}"
    ],
    cn: [
        "好的，你评分 {value}",
        "明白了，{value} 分",
        "我明白了，你给了 {value}",
        "理解了，等级 {value}"
    ],
    jp: [
        "わかりました、{value} と評価しましたね",
        "了解しました、{value} ポイント",
        "理解しました、{value} をつけました",
        "わかりました、レベル {value}"
    ],
    kr: [
        "알겠습니다, {value}로 평가하셨군요",
        "알았어요, {value} 점",
        "이해했습니다, {value}를 주셨네요",
        "알겠습니다, 레벨 {value}"
    ]
};

// ========== Get Random Feedback ==========
function getChoiceFeedback(choiceText) {
    const templates = choiceFeedbackTemplates[selectedLanguage] || choiceFeedbackTemplates['en'];
    const randomTemplate = templates[Math.floor(Math.random() * templates.length)];
    return randomTemplate.replace('{choice}', choiceText);
}

function getScaleFeedback(scaleValue) {
    const templates = scaleFeedbackTemplates[selectedLanguage] || scaleFeedbackTemplates['en'];
    const randomTemplate = templates[Math.floor(Math.random() * templates.length)];
    return randomTemplate.replace('{value}', scaleValue);
}

// ========== Initialize ==========
$(document).ready(function() {
    console.log('🚀 Starting AI Setup Chat...');
    console.log('AI Code:', aiCode);
    
    loadAIData().then(() => {
        initAvatar();
        createWaterWave();
        createParticles();
        
        // ✅ Unlock audio on first user interaction
        unlockAudioContext();
        
        startConversation();
    });
    
    // Send message on Enter
    $('#messageInput').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleUserInput();
        }
    });
    
    $('#sendBtn').on('click', handleUserInput);
    $('#confirmBtn').on('click', handleChoiceConfirm);
    $('#backBtn').on('click', handleBackButton);
});

// ========== Unlock Audio Context ==========
function unlockAudioContext() {
    const unlockAudio = () => {
        const silentAudio = new Audio('data:audio/wav;base64,UklGRigAAABXQVZFZm10IBIAAAABAAEARKwAAIhYAQACABAAAABkYXRhAgAAAAEA');
        silentAudio.play().then(() => {
            console.log('✅ Audio context unlocked');
            document.removeEventListener('click', unlockAudio);
            document.removeEventListener('touchstart', unlockAudio);
        }).catch(e => console.log('Audio unlock failed:', e));
    };
    
    document.addEventListener('click', unlockAudio, { once: true });
    document.addEventListener('touchstart', unlockAudio, { once: true });
}

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
            idleVideoUrl = aiCompanionData.idle_video_url || '';
            speakingVideoUrl = aiCompanionData.talking_video_url || '';
            
            console.log('✅ AI Data loaded:', aiCompanionData.ai_name_en);
        }
    } catch (error) {
        console.error('❌ Failed to load AI data:', error);
    }
}

// ========== Initialize Avatar ==========
function initAvatar() {
    const video = $('#avatarVideo')[0];
    
    if (idleVideoUrl) {
        video.src = idleVideoUrl;
        video.load();
        video.addEventListener('loadeddata', () => {
            video.play().catch(e => console.log('Autoplay prevented'));
        });
    }
}

// ========== Start Conversation ==========
function startConversation() {
    // Check if user is logged in
    if (jwt) {
        checkSetupStatus();
    } else {
        // Start with intro
        setTimeout(() => {
            const aiName = aiCompanionData?.ai_name_en || 'AI';
            const message = conversationMessages.intro.en.replace('{ai_name}', aiName);
            addChatMessage('ai', message);
            speakText(message);
            
            // After intro, ask for language
            setTimeout(() => {
                askLanguage();
            }, 4000);
        }, 1000);
    }
}

// ========== Chat Display Functions ==========
function addChatMessage(sender, text) {
    const chatDisplay = $('#chatDisplay');
    
    if (!chatDisplay.length) {
        // Create chat display if not exists
        $('<div id="chatDisplay" class="chat-display"></div>').insertBefore('#messageInput').parent();
    }
    
    const messageClass = sender === 'ai' ? 'chat-message-ai' : 'chat-message-user';
    const avatar = sender === 'ai' ? '<i class="fas fa-robot"></i>' : '<i class="fas fa-user"></i>';
    
    const messageHtml = `
        <div class="chat-message ${messageClass}">
            <div class="chat-avatar">${avatar}</div>
            <div class="chat-bubble">${escapeHtml(text)}</div>
        </div>
    `;
    
    $('#chatDisplay').append(messageHtml);
    
    // Scroll to bottom
    setTimeout(() => {
        $('#chatDisplay').scrollTop($('#chatDisplay')[0].scrollHeight);
    }, 100);
    
    // Also show in speech bubble for AI messages
    if (sender === 'ai') {
        showAIMessage(text, 0);
    }
}

// ========== Check Setup Status ==========
async function checkSetupStatus() {
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
                
                const message = conversationMessages.login_success[selectedLanguage];
                addChatMessage('ai', message);
                speakText(message);
                
                setTimeout(() => {
                    startQuestions();
                }, 2500);
            } else if (response.step === 'ready_to_chat') {
                window.location.href = '?ai_chat_3d&ai_code=' + aiCode + '&lang=' + selectedLanguage;
            }
        }
    } catch (error) {
        hideLoading();
        console.error('Check setup error:', error);
        
        // Start fresh
        const aiName = aiCompanionData?.ai_name_en || 'AI';
        const message = conversationMessages.intro.en.replace('{ai_name}', aiName);
        addChatMessage('ai', message);
        speakText(message);
        
        setTimeout(() => {
            askLanguage();
        }, 4000);
    }
}

// ========== Ask Language ==========
function askLanguage() {
    currentStep = 'language';
    stepHistory.push({step: 'language'});
    
    const message = conversationMessages.choose_language.en;
    addChatMessage('ai', message);
    speakText(message);
    
    // Show language choices
    setTimeout(() => {
        showLanguageChoices();
    }, 2000);
}

function showLanguageChoices() {
    const languages = [
        { code: 'th', name: 'ไทย', flag: 'https://flagcdn.com/th.svg' },
        { code: 'en', name: 'English', flag: 'https://flagcdn.com/gb.svg' },
        { code: 'cn', name: '中文', flag: 'https://flagcdn.com/cn.svg' },
        { code: 'jp', name: '日本語', flag: 'https://flagcdn.com/jp.svg' },
        { code: 'kr', name: '한국어', flag: 'https://flagcdn.com/kr.svg' }
    ];
    
    let html = '<div class="language-grid">';
    languages.forEach(lang => {
        html += `
            <div class="language-option" data-value="${lang.code}" data-name="${lang.name}">
                <div class="language-flag">
                    <img src="${lang.flag}" alt="${lang.name}">
                </div>
                <div class="language-name">${lang.name}</div>
            </div>
        `;
    });
    html += '</div>';
    
    showChoices('Choose Your Language', 'Select the language you prefer', html, false);
    
    $('.language-option').on('click', function() {
        $('.language-option').removeClass('selected');
        $(this).addClass('selected');
        selectedLanguage = $(this).data('value');
        const langName = $(this).data('name');
        
        // ✅ Play voice feedback when language is selected
        const confirmMessage = conversationMessages.language_selected[selectedLanguage];
        speakText(confirmMessage);
        
        // Add to chat display
        addChatMessage('user', langName);
        
        $('#confirmBtn').prop('disabled', false);
    });
}

// ========== Start Registration ==========
function startRegistration() {
    currentStep = 'register_name';
    stepHistory.push({step: 'register_name'});
    
    const message = conversationMessages.need_register[selectedLanguage];
    addChatMessage('ai', message);
    speakText(message);
    
    enableInput();
}

let registrationData = {};

function handleUserInput() {
    const input = $('#messageInput').val().trim();
    
    if (!input) return;
    
    // Add user message to chat
    addChatMessage('user', input);
    
    // Clear input
    $('#messageInput').val('');
    disableInput();
    
    // Show thinking icon briefly
    showThinkingIcon();
    
    // Process based on current step
    setTimeout(() => {
        hideThinkingIcon();
        
        switch (currentStep) {
            case 'register_name':
                registrationData.name = input;
                currentStep = 'register_lastname';
                stepHistory.push({step: 'register_lastname', data: {name: input}});
                
                const askLastnameMsg = conversationMessages.ask_lastname[selectedLanguage].replace('{name}', input);
                addChatMessage('ai', askLastnameMsg);
                speakText(askLastnameMsg);
                
                setTimeout(() => {
                    enableInput();
                }, 500);
                break;
                
            case 'register_lastname':
                registrationData.lastname = input;
                currentStep = 'register_email';
                stepHistory.push({step: 'register_email', data: {lastname: input}});
                
                const askEmailMsg = conversationMessages.ask_email[selectedLanguage]
                    .replace('{name}', registrationData.name)
                    .replace('{lastname}', input);
                addChatMessage('ai', askEmailMsg);
                speakText(askEmailMsg);
                
                setTimeout(() => {
                    enableInput();
                }, 500);
                break;
                
            case 'register_email':
                if (!validateEmail(input)) {
                    const errorMsg = selectedLanguage === 'th' ? 
                        "กรุณากรอกอีเมลให้ถูกต้อง" : 
                        "Please enter a valid email address";
                    addChatMessage('ai', errorMsg);
                    speakText(errorMsg);
                    
                    setTimeout(() => {
                        enableInput();
                    }, 500);
                    return;
                }
                registrationData.email = input;
                currentStep = 'register_phone';
                stepHistory.push({step: 'register_phone', data: {email: input}});
                
                const askPhoneMsg = conversationMessages.ask_phone[selectedLanguage];
                addChatMessage('ai', askPhoneMsg);
                speakText(askPhoneMsg);
                
                setTimeout(() => {
                    enableInput();
                }, 500);
                break;
                
            case 'register_phone':
                registrationData.phone = input;
                currentStep = 'register_password';
                stepHistory.push({step: 'register_password', data: {phone: input}});
                
                const askPasswordMsg = conversationMessages.ask_password[selectedLanguage];
                addChatMessage('ai', askPasswordMsg);
                speakText(askPasswordMsg);
                
                setTimeout(() => {
                    enableInput('password');
                }, 500);
                break;
                
            case 'register_password':
                if (input.length < 6) {
                    const errorMsg = selectedLanguage === 'th' ? 
                        "รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร" : 
                        "Password must be at least 6 characters";
                    addChatMessage('ai', errorMsg);
                    speakText(errorMsg);
                    
                    setTimeout(() => {
                        enableInput('password');
                    }, 500);
                    return;
                }
                registrationData.password = input;
                stepHistory.push({step: 'register_password', data: {password: input}});
                
                // Show confirmation
                showRegistrationConfirmation();
                break;
                
            case 'otp':
                verifyOTP(input);
                break;
                
            case 'login_username':
                registrationData.username = input;
                currentStep = 'login_password';
                stepHistory.push({step: 'login_password', data: {username: input}});
                
                const askPwdMsg = selectedLanguage === 'th' ? "และรหัสผ่านของคุณคืออะไร?" : "And your password?";
                addChatMessage('ai', askPwdMsg);
                speakText(askPwdMsg);
                
                setTimeout(() => {
                    enableInput('password');
                }, 500);
                break;
                
            case 'login_password':
                addChatMessage('user', '••••••');
                submitLogin(registrationData.username, input);
                break;
                
            case 'question_text':
                // Save text answer
                const currentQuestion = questions[currentQuestionIndex];
                answers[currentQuestion.question_id] = {
                    question_id: currentQuestion.question_id,
                    text_answer: input
                };
                
                disableInput();
                nextQuestion();
                break;
        }
    }, 800);
}

// ========== Show Registration Confirmation (WITH EDIT BUTTONS) ==========
function showRegistrationConfirmation() {
    const message = conversationMessages.confirm_registration[selectedLanguage];
    addChatMessage('ai', message);
    speakText(message);
    
    const html = `
        <div class="registration-summary">
            <!-- First Name -->
            <div class="summary-item" id="summaryName">
                <div style="flex: 1;">
                    <span class="summary-label">${selectedLanguage === 'th' ? 'ชื่อ' : 'First Name'}:</span>
                    <span class="summary-value">${escapeHtml(registrationData.name)}</span>
                </div>
                <button class="edit-field-btn" data-field="name">
                    <i class="fas fa-edit"></i>
                </button>
            </div>
            
            <!-- Last Name -->
            <div class="summary-item" id="summaryLastname">
                <div style="flex: 1;">
                    <span class="summary-label">${selectedLanguage === 'th' ? 'นามสกุล' : 'Last Name'}:</span>
                    <span class="summary-value">${escapeHtml(registrationData.lastname)}</span>
                </div>
                <button class="edit-field-btn" data-field="lastname">
                    <i class="fas fa-edit"></i>
                </button>
            </div>
            
            <!-- Email -->
            <div class="summary-item" id="summaryEmail">
                <div style="flex: 1;">
                    <span class="summary-label">${selectedLanguage === 'th' ? 'อีเมล' : 'Email'}:</span>
                    <span class="summary-value">${escapeHtml(registrationData.email)}</span>
                </div>
                <button class="edit-field-btn" data-field="email">
                    <i class="fas fa-edit"></i>
                </button>
            </div>
            
            <!-- Phone -->
            <div class="summary-item" id="summaryPhone">
                <div style="flex: 1;">
                    <span class="summary-label">${selectedLanguage === 'th' ? 'เบอร์โทร' : 'Phone'}:</span>
                    <span class="summary-value">${escapeHtml(registrationData.phone)}</span>
                </div>
                <button class="edit-field-btn" data-field="phone">
                    <i class="fas fa-edit"></i>
                </button>
            </div>
            
            <!-- Password -->
            <div class="summary-item" id="summaryPassword">
                <div style="flex: 1;">
                    <span class="summary-label">${selectedLanguage === 'th' ? 'รหัสผ่าน' : 'Password'}:</span>
                    <span class="summary-value">••••••••</span>
                </div>
                <button class="edit-field-btn" data-field="password">
                    <i class="fas fa-edit"></i>
                </button>
            </div>
        </div>
    `;
    
    showChoices(
        selectedLanguage === 'th' ? 'ยืนยันข้อมูล' : 'Confirm Details',
        selectedLanguage === 'th' ? 'กรุณาตรวจสอบข้อมูลของคุณ' : 'Please review your information',
        html,
        true
    );
    
    $('#confirmBtn').prop('disabled', false).html(`<i class="fas fa-check"></i> ${selectedLanguage === 'th' ? 'ลงทะเบียน' : 'Register'}`);
    
    // Handle edit buttons
    $('.edit-field-btn').on('click', function() {
        const field = $(this).data('field');
        editRegistrationField(field);
    });
}

// ========== Edit Registration Field (NEW) ==========
function editRegistrationField(field) {
    const labels = {
        name: selectedLanguage === 'th' ? 'ชื่อ' : 'First Name',
        lastname: selectedLanguage === 'th' ? 'นามสกุล' : 'Last Name',
        email: selectedLanguage === 'th' ? 'อีเมล' : 'Email',
        phone: selectedLanguage === 'th' ? 'เบอร์โทร' : 'Phone',
        password: selectedLanguage === 'th' ? 'รหัสผ่าน' : 'Password'
    };
    
    const currentValue = field === 'password' ? '' : registrationData[field];
    const inputType = field === 'password' ? 'password' : 'text';
    
    const summaryItem = $(`#summary${field.charAt(0).toUpperCase() + field.slice(1)}`);
    
    summaryItem.html(`
        <div style="flex: 1;">
            <span class="summary-label">${labels[field]}:</span>
            <input 
                type="${inputType}" 
                class="form-field edit-field-input" 
                id="edit${field}" 
                value="${escapeHtml(currentValue)}"
                placeholder="${labels[field]}"
                style="margin-top: 8px; margin-bottom: 0;"
            >
        </div>
        <button class="save-field-btn" data-field="${field}">
            <i class="fas fa-check"></i>
        </button>
    `);
    
    // Focus on input
    $(`#edit${field}`).focus();
    
    // Handle save button
    $('.save-field-btn').on('click', function() {
        const fieldName = $(this).data('field');
        saveEditedField(fieldName);
    });
    
    // Handle Enter key
    $(`#edit${field}`).on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            saveEditedField(field);
        }
    });
}

// ========== Save Edited Field (NEW) ==========
function saveEditedField(field) {
    const newValue = $(`#edit${field}`).val().trim();
    
    const labels = {
        name: selectedLanguage === 'th' ? 'ชื่อ' : 'First Name',
        lastname: selectedLanguage === 'th' ? 'นามสกุล' : 'Last Name',
        email: selectedLanguage === 'th' ? 'อีเมล' : 'Email',
        phone: selectedLanguage === 'th' ? 'เบอร์โทร' : 'Phone',
        password: selectedLanguage === 'th' ? 'รหัสผ่าน' : 'Password'
    };
    
    // Validate
    if (!newValue) {
        alert(selectedLanguage === 'th' ? 'กรุณากรอกข้อมูล' : 'Please enter a value');
        return;
    }
    
    if (field === 'email' && !validateEmail(newValue)) {
        alert(selectedLanguage === 'th' ? 'กรุณากรอกอีเมลให้ถูกต้อง' : 'Please enter a valid email');
        return;
    }
    
    if (field === 'password' && newValue.length < 6) {
        alert(selectedLanguage === 'th' ? 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร' : 'Password must be at least 6 characters');
        return;
    }
    
    // Save new value
    registrationData[field] = newValue;
    
    // Update display
    const displayValue = field === 'password' ? '••••••••' : escapeHtml(newValue);
    const summaryItem = $(`#summary${field.charAt(0).toUpperCase() + field.slice(1)}`);
    
    summaryItem.html(`
        <div style="flex: 1;">
            <span class="summary-label">${labels[field]}:</span>
            <span class="summary-value">${displayValue}</span>
        </div>
        <button class="edit-field-btn" data-field="${field}">
            <i class="fas fa-edit"></i>
        </button>
    `);
    
    // Re-bind edit button
    $('.edit-field-btn').on('click', function() {
        const fieldToEdit = $(this).data('field');
        editRegistrationField(fieldToEdit);
    });
}

// ========== Submit Registration ==========
function submitRegistration() {
    hideAIMessage();
    hideChoices();
    showLoading(selectedLanguage === 'th' ? 'กำลังสร้างบัญชี...' : 'Creating your account...');
    
    $.ajax({
        url: 'app/actions/register_user.php',
        type: 'POST',
        data: {
            first_name: registrationData.name,      // ✅ เปลี่ยนจาก name เป็น first_name
            last_name: registrationData.lastname,   // ✅ ส่ง last_name ตามปกติ
            email: registrationData.email,
            phone: registrationData.phone,
            password: registrationData.password,
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
                
                currentStep = 'otp';
                stepHistory.push({step: 'otp'});
                
                const otpMsg = conversationMessages.otp_sent[selectedLanguage]
                    .replace('{email}', registrationData.email);
                
                addChatMessage('ai', otpMsg);
                speakText(otpMsg);
                
                // Show OTP input
                setTimeout(() => {
                    showOTPInput();
                }, 2500);
            } else {
                const errorMsg = "Error: " + response.message;
                addChatMessage('ai', errorMsg);
                speakText(selectedLanguage === 'th' ? 'การลงทะเบียนล้มเหลว' : 'Registration failed');
                
                // Allow retry
                setTimeout(() => {
                    showRegistrationConfirmation();
                }, 1500);
            }
        },
        error: function() {
            hideLoading();
            const errorMsg = selectedLanguage === 'th' ? 
                "การลงทะเบียนล้มเหลว กรุณาลองอีกครั้ง" : 
                "Registration failed. Please try again.";
            addChatMessage('ai', errorMsg);
            speakText(errorMsg);
            
            setTimeout(() => {
                showRegistrationConfirmation();
            }, 1500);
        }
    });
}

// ========== Show OTP Input ==========
function showOTPInput() {
    const html = `
        <div class="otp-container">
            <input type="text" class="form-field otp-input" maxlength="1" id="otp1" autocomplete="off">
            <input type="text" class="form-field otp-input" maxlength="1" id="otp2" autocomplete="off">
            <input type="text" class="form-field otp-input" maxlength="1" id="otp3" autocomplete="off">
            <input type="text" class="form-field otp-input" maxlength="1" id="otp4" autocomplete="off">
            <input type="text" class="form-field otp-input" maxlength="1" id="otp5" autocomplete="off">
            <input type="text" class="form-field otp-input" maxlength="1" id="otp6" autocomplete="off">
        </div>
    `;
    
    showChoices(
        selectedLanguage === 'th' ? 'กรอกรหัส OTP' : 'Enter OTP Code',
        selectedLanguage === 'th' ? 'กรอกรหัส 6 หลักที่เราส่งให้' : 'Enter the 6-digit code we sent',
        html,
        false
    );
    
    $('#otp1').focus();
    
    // ✅ Handle Paste Event - วางได้ที่ช่องไหนก็ได้
    $('.otp-input').on('paste', function(e) {
        e.preventDefault();
        
        // Get pasted data
        const pastedData = (e.originalEvent || e).clipboardData.getData('text/plain');
        
        // Extract only numbers
        const otpDigits = pastedData.replace(/\D/g, '').substring(0, 6);
        
        if (otpDigits.length > 0) {
            // Fill in all OTP inputs
            for (let i = 0; i < otpDigits.length && i < 6; i++) {
                $('#otp' + (i + 1)).val(otpDigits[i]);
            }
            
            // Focus on last filled input or next empty one
            if (otpDigits.length < 6) {
                $('#otp' + (otpDigits.length + 1)).focus();
            } else {
                $('#otp6').focus();
                // Enable confirm button if all filled
                $('#confirmBtn').prop('disabled', false);
            }
        }
    });
    
    // Handle typing
    $('.otp-input').on('input', function() {
        // Only allow numbers
        this.value = this.value.replace(/\D/g, '');
        
        if (this.value.length === 1) {
            $(this).next('.otp-input').focus();
        }
        
        // Check if all filled
        const otp = $('#otp1').val() + $('#otp2').val() + $('#otp3').val() + 
                    $('#otp4').val() + $('#otp5').val() + $('#otp6').val();
        
        if (otp.length === 6) {
            $('#confirmBtn').prop('disabled', false);
        } else {
            $('#confirmBtn').prop('disabled', true);
        }
    });
    
    // Handle backspace
    $('.otp-input').on('keydown', function(e) {
        if (e.key === 'Backspace' && !this.value) {
            $(this).prev('.otp-input').focus();
        }
    });
}

function verifyOTP(otp) {
    hideChoices();
    showLoading(selectedLanguage === 'th' ? 'กำลังยืนยัน OTP...' : 'Verifying OTP...');
    
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
                const successMsg = conversationMessages.otp_success[selectedLanguage];
                addChatMessage('ai', successMsg);
                speakText(successMsg);
                
                setTimeout(() => {
                    showLoginPrompt();
                }, 3000);
            } else {
                const errorMsg = selectedLanguage === 'th' ? 
                    "รหัส OTP ไม่ถูกต้อง กรุณาลองอีกครั้ง" : 
                    "Invalid OTP. Please try again.";
                addChatMessage('ai', errorMsg);
                speakText(errorMsg);
                
                setTimeout(() => {
                    showOTPInput();
                }, 2000);
            }
        },
        error: function() {
            hideLoading();
            const errorMsg = selectedLanguage === 'th' ? 
                "การยืนยันล้มเหลว กรุณาลองอีกครั้ง" : 
                "Verification failed. Please try again.";
            addChatMessage('ai', errorMsg);
            speakText(errorMsg);
            
            setTimeout(() => {
                showOTPInput();
            }, 2000);
        }
    });
}

// ========== Show Login Prompt ==========
function showLoginPrompt() {
    currentStep = 'login_username';
    stepHistory.push({step: 'login_username'});
    
    const message = conversationMessages.ask_login[selectedLanguage];
    addChatMessage('ai', message);
    speakText(message);
    
    setTimeout(() => {
        enableInput();
    }, 500);
}

// ========== Submit Login ==========
function submitLogin(username, password) {
    hideAIMessage();
    showLoading(selectedLanguage === 'th' ? 'กำลังเข้าสู่ระบบ...' : 'Logging in...');
    
    $.ajax({
        url: 'app/actions/check_login.php',
        type: 'POST',
        data: { username: username, password: password },
        dataType: 'json',
        success: function(response) {
            hideLoading();
            
            if (response.status === 'success') {
                jwt = response.jwt;
                sessionStorage.setItem('jwt', jwt);
                
                const successMsg = conversationMessages.login_success[selectedLanguage];
                addChatMessage('ai', successMsg);
                speakText(successMsg);
                
                setTimeout(() => {
                    checkSetupStatus();
                }, 2500);
            } else {
                const errorMsg = selectedLanguage === 'th' ? 
                    "เข้าสู่ระบบล้มเหลว: " + response.message : 
                    "Login failed: " + response.message;
                addChatMessage('ai', errorMsg);
                speakText(selectedLanguage === 'th' ? 'เข้าสู่ระบบล้มเหลว' : 'Login failed');
                
                setTimeout(() => {
                    enableInput();
                }, 500);
            }
        },
        error: function() {
            hideLoading();
            const errorMsg = selectedLanguage === 'th' ? 
                "เข้าสู่ระบบล้มเหลว กรุณาลองอีกครั้ง" : 
                "Login failed. Please try again.";
            addChatMessage('ai', errorMsg);
            speakText(errorMsg);
            
            setTimeout(() => {
                enableInput();
            }, 500);
        }
    });
}

// ========== Start Questions ==========
async function startQuestions() {
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
            $('#progressBar').show();
            
            const message = conversationMessages.start_questions[selectedLanguage];
            addChatMessage('ai', message);
            speakText(message);
            
            setTimeout(() => {
                askQuestion(0);
            }, 3500);
        }
    } catch (error) {
        console.error('Failed to load questions:', error);
    }
}

function askQuestion(index) {
    if (index >= questions.length) {
        completeSetup();
        return;
    }
    
    currentQuestionIndex = index;
    const question = questions[index];
    
    // Update progress
    const progress = ((index + 1) / questions.length) * 100;
    $('#progressFill').css('width', progress + '%');
    $('#currentQ').text(index + 1);
    
    // Get question text in selected language
    const langCol = 'question_text_' + selectedLanguage;
    const questionText = question[langCol] || question.question_text_th;
    
    // Show AI message
    addChatMessage('ai', questionText);
    speakText(questionText);
    
    // Show appropriate input
    setTimeout(() => {
        if (question.question_type === 'choice') {
            showQuestionChoices(question);
        } else if (question.question_type === 'scale') {
            showScaleOptions(question);
        } else if (question.question_type === 'text') {
            currentStep = 'question_text';
            enableInput();
        }
    }, 2000);
}

// ========== Show Question Choices (WITH VOICE FEEDBACK) ==========
function showQuestionChoices(question) {
    const choiceLangCol = 'choice_text_' + selectedLanguage;
    
    let html = '';
    question.choices.forEach(choice => {
        const choiceText = choice[choiceLangCol] || choice.choice_text_th;
        html += `
            <div class="choice-item" data-choice-id="${choice.choice_id}" data-choice-text="${escapeHtml(choiceText)}">
                ${choiceText}
            </div>
        `;
    });
    
    showChoices(
        selectedLanguage === 'th' ? 'เลือกคำตอบของคุณ' : 'Choose your answer',
        '',
        html,
        true
    );
    
    $('.choice-item').on('click', function() {
        $('.choice-item').removeClass('selected');
        $(this).addClass('selected');
        
        const choiceId = $(this).data('choice-id');
        const choiceText = $(this).data('choice-text');
        
        answers[question.question_id] = {
            question_id: question.question_id,
            choice_id: choiceId
        };
        
        // ✅ AI พูดตอบกลับทันทีเมื่อเลือก choice
        const feedback = getChoiceFeedback(choiceText);
        speakText(feedback);
        
        $('#confirmBtn').prop('disabled', false);
    });
}

// ========== Show Scale Options (WITH VOICE FEEDBACK) ==========
function showScaleOptions(question) {
    const html = `
        <div class="scale-container">
            <div class="scale-labels">
                <span>${selectedLanguage === 'th' ? 'ไม่เห็นด้วยอย่างยิ่ง' : 'Strongly Disagree'}</span>
                <span>${selectedLanguage === 'th' ? 'เห็นด้วยอย่างยิ่ง' : 'Strongly Agree'}</span>
            </div>
            <div class="scale-options" id="scaleOpts">
                <div class="scale-option" data-value="1">1</div>
                <div class="scale-option" data-value="2">2</div>
                <div class="scale-option" data-value="3">3</div>
                <div class="scale-option" data-value="4">4</div>
                <div class="scale-option" data-value="5">5</div>
            </div>
        </div>
    `;
    
    showChoices(
        selectedLanguage === 'th' ? 'ให้คะแนนคำตอบของคุณ' : 'Rate your answer',
        selectedLanguage === 'th' ? '1 = ไม่เห็นด้วยอย่างยิ่ง, 5 = เห็นด้วยอย่างยิ่ง' : '1 = Strongly Disagree, 5 = Strongly Agree',
        html,
        true
    );
    
    $('.scale-option').on('click', function() {
        $('.scale-option').removeClass('selected');
        $(this).addClass('selected');
        
        const value = $(this).data('value');
        answers[question.question_id] = {
            question_id: question.question_id,
            scale_value: value
        };
        
        // ✅ AI พูดตอบกลับทันทีเมื่อเลือก scale
        const feedback = getScaleFeedback(value);
        speakText(feedback);
        
        $('#confirmBtn').prop('disabled', false);
    });
}

function handleChoiceConfirm() {
    if (currentStep === 'language') {
        hideChoices();
        
        setTimeout(() => {
            startRegistration();
        }, 800);
    } else if (currentStep === 'otp') {
        const otp = $('#otp1').val() + $('#otp2').val() + $('#otp3').val() + 
                    $('#otp4').val() + $('#otp5').val() + $('#otp6').val();
        
        verifyOTP(otp);
    } else if (currentStep === 'register_password') {
        // ✅ ดึงข้อมูลล่าสุดจาก DOM ก่อน submit
        const finalData = {
            name: $('.summary-item:eq(0) .summary-value').text().trim() || registrationData.name,
            lastname: $('.summary-item:eq(1) .summary-value').text().trim() || registrationData.lastname,
            email: $('.summary-item:eq(2) .summary-value').text().trim() || registrationData.email,
            phone: $('.summary-item:eq(3) .summary-value').text().trim() || registrationData.phone,
            password: registrationData.password // password ไม่แสดงใน DOM
        };
        
        // อัพเดท registrationData ก่อน submit
        Object.assign(registrationData, finalData);
        
        submitRegistration();
    } else {
        // Question answered
        hideChoices();
        
        // Show thinking icon while processing
        showThinkingIcon();
        
        setTimeout(() => {
            hideThinkingIcon();
            nextQuestion();
        }, 1000);
    }
}

function nextQuestion() {
    hideAIMessage();
    
    setTimeout(() => {
        askQuestion(currentQuestionIndex + 1);
    }, 500);
}

// ========== Back Button Handler ==========
function handleBackButton() {
    if (stepHistory.length <= 1) {
        console.log('Cannot go back further');
        return;
    }
    
    // Remove current step
    stepHistory.pop();
    
    // Get previous step
    const previousStep = stepHistory[stepHistory.length - 1];
    
    console.log('Going back to:', previousStep);
    
    hideChoices();
    
    // Restore to previous step
    switch (previousStep.step) {
        case 'language':
            askLanguage();
            break;
        case 'register_name':
            startRegistration();
            break;
        case 'register_lastname':
            // Re-ask for lastname
            currentStep = 'register_lastname';
            const askLastnameMsg = conversationMessages.ask_lastname[selectedLanguage]
                .replace('{name}', registrationData.name);
            addChatMessage('ai', askLastnameMsg);
            speakText(askLastnameMsg);
            setTimeout(() => enableInput(), 500);
            break;
        case 'register_email':
            // Re-ask for email
            currentStep = 'register_email';
            const askEmailMsg = conversationMessages.ask_email[selectedLanguage]
                .replace('{name}', registrationData.name)
                .replace('{lastname}', registrationData.lastname);
            addChatMessage('ai', askEmailMsg);
            speakText(askEmailMsg);
            setTimeout(() => enableInput(), 500);
            break;
        case 'register_phone':
            // Re-ask for phone
            currentStep = 'register_phone';
            const askPhoneMsg = conversationMessages.ask_phone[selectedLanguage];
            addChatMessage('ai', askPhoneMsg);
            speakText(askPhoneMsg);
            setTimeout(() => enableInput(), 500);
            break;
        case 'register_password':
            // Re-ask for password
            currentStep = 'register_password';
            const askPasswordMsg = conversationMessages.ask_password[selectedLanguage];
            addChatMessage('ai', askPasswordMsg);
            speakText(askPasswordMsg);
            setTimeout(() => enableInput('password'), 500);
            break;
    }
}

// ========== Complete Setup ==========
function completeSetup() {
    hideChoices();
    showLoading(selectedLanguage === 'th' ? 'กำลังบันทึกคำตอบ...' : 'Saving your answers...');
    
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
                const message = conversationMessages.all_done[selectedLanguage];
                addChatMessage('ai', message);
                speakText(message);
                
                setTimeout(() => {
                    window.location.href = '?ai_chat_3d&ai_code=' + aiCode + '&lang=' + selectedLanguage;
                }, 4000);
            } else {
                const errorMsg = selectedLanguage === 'th' ? 
                    "บันทึกคำตอบล้มเหลว กรุณาลองอีกครั้ง" : 
                    "Failed to save answers. Please try again.";
                addChatMessage('ai', errorMsg);
                speakText(errorMsg);
            }
        },
        error: function() {
            hideLoading();
            const errorMsg = selectedLanguage === 'th' ? 
                "บันทึกคำตอบล้มเหลว กรุณาลองอีกครั้ง" : 
                "Failed to save answers. Please try again.";
            addChatMessage('ai', errorMsg);
            speakText(errorMsg);
        }
    });
}

// ========== UI Helper Functions ==========
function showAIMessage(text, duration = 6000) {
    $('#aiSpeechText').text(text);
    $('#aiSpeechBubble').addClass('show');
    
    // Auto hide after duration
    if (duration > 0) {
        setTimeout(() => {
            hideAIMessage();
        }, duration);
    }
}

function hideAIMessage() {
    $('#aiSpeechBubble').removeClass('show');
}

function showThinkingIcon() {
    $('#thinkingIcon').addClass('show');
}

function hideThinkingIcon() {
    $('#thinkingIcon').removeClass('show');
}

function showChoices(title, subtitle, content, showBackBtn = false) {
    $('#choicesTitle').text(title);
    $('#choicesSubtitle').text(subtitle);
    $('#choicesContent').html(content);
    $('#confirmBtn').prop('disabled', true);
    
    // Show/hide back button
    if (showBackBtn && stepHistory.length > 1) {
        $('#backBtn').show();
    } else {
        $('#backBtn').hide();
    }
    
    // Show choices sidebar
    $('#choicesSidebar').addClass('show');
    $('#avatarContainer').addClass('with-choices');
}

function hideChoices() {
    $('#choicesSidebar').removeClass('show');
    $('#avatarContainer').removeClass('with-choices');
}

function enableInput(type = 'text') {
    $('#messageInput').prop('type', type).prop('disabled', false).focus();
    $('#sendBtn').prop('disabled', false);
}

function disableInput() {
    $('#messageInput').prop('disabled', true);
    $('#sendBtn').prop('disabled', true);
}

// ========== TTS Functions ==========
function speakText(text) {
    console.log('🔊 Speaking:', text.substring(0, 50) + '...');
    
    // Stop any current audio
    if (currentAudio) {
        currentAudio.pause();
        currentAudio = null;
    }
    
    isSpeaking = true;
    window.isSpeaking = true;
    
    // Switch to speaking video
    if (speakingVideoUrl) {
        switchToVideo(speakingVideoUrl);
    }
    
    const encodedText = encodeURIComponent(text);
    let ttsUrl;
    
    if (selectedLanguage === 'th') {
        ttsUrl = `https://code.responsivevoice.org/getvoice.php?t=${encodedText}&tl=th&sv=&vn=&pitch=0.5&rate=0.5&vol=1`;
    } else {
        let googleLang = selectedLanguage;
        if (selectedLanguage === 'cn') googleLang = 'zh-CN';
        if (selectedLanguage === 'jp') googleLang = 'ja';
        if (selectedLanguage === 'kr') googleLang = 'ko';
        
        ttsUrl = `https://translate.google.com/translate_tts?ie=UTF-8&tl=${googleLang}&client=tw-ob&q=${encodedText}`;
    }
    
    currentAudio = new Audio(ttsUrl);
    currentAudio.volume = 1.0;
    
    // Try to play immediately
    const playPromise = currentAudio.play();
    
    if (playPromise !== undefined) {
        playPromise
            .then(() => {
                console.log('✅ Audio playing');
            })
            .catch(error => {
                console.warn('⚠️ Autoplay prevented');
            });
    }
    
    currentAudio.onended = function() {
        console.log('⏹️ Audio ended');
        isSpeaking = false;
        window.isSpeaking = false;
        
        if (idleVideoUrl) {
            switchToVideo(idleVideoUrl);
        }
    };
    
    currentAudio.onerror = function(e) {
        console.error('❌ TTS error:', e);
        isSpeaking = false;
        window.isSpeaking = false;
        
        if (idleVideoUrl) {
            switchToVideo(idleVideoUrl);
        }
        
        // Try Web Speech API as fallback
        tryWebSpeechFallback(text);
    };
}

function tryWebSpeechFallback(text) {
    if (!window.speechSynthesis) {
        console.error('❌ Web Speech API not available');
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
    
    utterance.rate = 0.9;
    utterance.pitch = 1.0;
    utterance.volume = 1.0;
    
    utterance.onstart = function() {
        console.log('🗣️ Web Speech started');
        isSpeaking = true;
        window.isSpeaking = true;
        if (speakingVideoUrl) switchToVideo(speakingVideoUrl);
    };
    
    utterance.onend = function() {
        console.log('✅ Web Speech ended');
        isSpeaking = false;
        window.isSpeaking = false;
        if (idleVideoUrl) switchToVideo(idleVideoUrl);
    };
    
    utterance.onerror = function(event) {
        console.error('❌ Web Speech error:', event);
        isSpeaking = false;
        window.isSpeaking = false;
        if (idleVideoUrl) switchToVideo(idleVideoUrl);
    };
    
    window.speechSynthesis.speak(utterance);
}

function switchToVideo(videoUrl) {
    const video = $('#avatarVideo')[0];
    
    video.style.opacity = '0.3';
    
    setTimeout(() => {
        video.src = videoUrl;
        video.load();
        
        video.addEventListener('canplay', function onCanPlay() {
            video.removeEventListener('canplay', onCanPlay);
            
            video.play().then(() => {
                video.style.opacity = '1';
            }).catch(e => {
                console.error('Play error:', e);
                video.style.opacity = '1';
            });
        });
    }, 300);
}

// ========== Wave Animation ==========
function createWaterWave() {
    const paths = document.querySelectorAll('.wave-path');
    let waveOffset = 0;
    
    function animateWaves() {
        const speed = window.isSpeaking ? 0.08 : 0.015;
        waveOffset += speed;
        
        if (!window.waveIntensity) window.waveIntensity = 0;
        if (window.isSpeaking) {
            window.waveIntensity = Math.min(window.waveIntensity + 0.1, 1);
        } else {
            window.waveIntensity = Math.max(window.waveIntensity - 0.05, 0);
        }
        
        paths.forEach((path, index) => {
            const points = [];
            const baseAmplitude = 10 + (index * 5);
            const noise = window.isSpeaking ? (Math.random() * 15 * window.waveIntensity) : 0;
            const amplitude = baseAmplitude + (50 * window.waveIntensity) + noise;
            const frequency = 0.006 + (index * 0.002);
            const offset = waveOffset * (1 + index * 0.3);
            
            for (let x = 0; x <= 1200; x += 15) {
                let wave = Math.sin(x * frequency + offset);
                
                if (window.isSpeaking) {
                    wave += Math.sin(x * 0.05 + offset * 2) * 0.2 * window.waveIntensity;
                    wave += (Math.random() - 0.5) * 0.1 * window.waveIntensity;
                }
                
                const edgeSoftener = Math.sin((x / 1200) * Math.PI);
                const y = 200 + (wave * amplitude * edgeSoftener);
                points.push(`${x},${y}`);
            }
            
            const pathData = `M 0,300 L ${points.map((p, i) => {
                if (i === 0) return `0,${p.split(',')[1]}`;
                return p;
            }).join(' L ')} L 1200,300 Z`;
            
            path.setAttribute('d', pathData);
        });
        
        requestAnimationFrame(animateWaves);
    }
    
    animateWaves();
}

function createParticles() {
    const container = document.getElementById('particlesContainer');
    for (let i = 0; i < 25; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 12 + 's';
        particle.style.animationDuration = (10 + Math.random() * 4) + 's';
        
        const colors = ['#00d4ff', '#4dd0e1', '#667eea', '#80deea'];
        particle.style.background = colors[Math.floor(Math.random() * colors.length)];
        
        container.appendChild(particle);
    }
}

// ========== Utils ==========
function showLoading(text = 'Loading...') {
    $('#loadingText').text(text);
    $('#loadingOverlay').addClass('active');
}

function hideLoading() {
    $('#loadingOverlay').removeClass('active');
}

function validateEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

console.log('✅ AI Setup Chat System (Improved with Editable Registration) Loaded');