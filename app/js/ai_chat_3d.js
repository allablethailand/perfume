/**
 * AI Chat 3D - Enhanced with 2 Video Files (Idle + Speaking) + Welcome Message
 * รองรับวิดีโอ 2 ไฟล์แยก: ไม่พูด กับ พูด
 * เพิ่ม: ทักทายอัตโนมัติเมื่อเข้าหน้าหรือรีเฟรช (ดึงภาษาจาก preferred_language)
 * 
 * ✅ ใช้วิดีโอ 2 ไฟล์
 * ✅ Smooth transition ไม่มี AbortError
 * ✅ Preload วิดีโอล่วงหน้า
 * ✅ รองรับ WebM with Alpha Channel (transparent background)
 * ✅ Welcome message โดยดึงภาษาจาก database (preferred_language)
 * ✅ แสดงผลเร็ว ไม่มี air time
 */

let currentConversationId = 0;
const jwt = sessionStorage.getItem("jwt");

let scene, camera, renderer, avatar, mouth, leftEye, rightEye, leftEyePupil, rightEyePupil;
let isSpeaking = false;
let waveIntensity = 0;

// Video Avatar Settings
let videoAvatar = null;
let useVideoAvatar = true;
const VIDEO_AVATAR_PATH = 'public/ai_videos/';

// ⭐ ชื่อไฟล์วิดีโอ 2 ไฟล์
const IDLE_VIDEO = 'video_696f62ca1fc32_1768907466.webm';      // วิดีโอตอนไม่พูด
const SPEAKING_VIDEO = 'video_696f62b67a40a_1768907446.webm';  // วิดีโอตอนพูด (เปลี่ยนเป็น .webm)
let currentVideoState = 'idle';
let isTransitioning = false;
let preloadedSpeakingVideo = null;

// ทำให้ isSpeaking เป็น global variable
window.isSpeaking = false;
window.waveIntensity = 0;

// 🎉 Welcome Messages (5 ภาษา)
const WELCOME_MESSAGES = {
    th: "ยินดีต้อนรับกลับมานะเพื่อน",
    en: "Welcome back, my friend",
    zh: "欢迎回来，我的朋友",
    ja: "おかえりなさい、友よ",
    ko: "다시 오신 것을 환영합니다, 친구"
};

// 🌍 เก็บภาษาที่ได้จาก database
let userPreferredLanguage = 'th'; // Default
let isWelcomeMessagePlayed = false; // ป้องกันเล่นซ้ำ

$(document).ready(function() {
    if (!jwt) {
        window.location.href = '?login';
        return;
    }
    
    if (useVideoAvatar) {
        initVideoAvatar();
    } else {
        init3DAvatar();
    }
    
    loadConversations();
    
    // 🎤 ✅ เริ่มดึงภาษาทันทีและเล่นทันที (ไม่รอ)
    fetchUserLanguageAndWelcome();
    
    $('#messageInput').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
});

/**
 * 🌍 Fetch User's Preferred Language from Database
 * ✅ เปลี่ยนแปลง: เล่นทันทีด้วยภาษา default แล้วค่อยอัพเดทภาษาที่ถูกต้องทีหลัง
 */
function fetchUserLanguageAndWelcome() {
    // ✅ เล่น Welcome Message ทันทีด้วยภาษา default (ไม่รอ AJAX)
    setTimeout(() => {
        playWelcomeMessage();
    }, 800); // ลด delay จาก 1500ms เหลือ 800ms
    
    // ✅ ดึงภาษาจาก database (ไว้ใช้ในครั้งถัดไป)
    $.ajax({
        url: 'app/actions/get_user_language.php',
        type: 'GET',
        headers: { 'Authorization': 'Bearer ' + jwt },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success' && response.preferred_language) {
                userPreferredLanguage = response.preferred_language;
                console.log('✅ User preferred language updated:', userPreferredLanguage);
            } else {
                console.warn('⚠️ Cannot fetch language, using default: th');
                userPreferredLanguage = 'th';
            }
        },
        error: function() {
            console.error('❌ Failed to fetch user language, using default: th');
            userPreferredLanguage = 'th';
        }
    });
}

/**
 * 🎉 Play Welcome Message (ใช้ภาษาจาก database)
 * ✅ เปลี่ยนแปลง: ป้องกันเล่นซ้ำ + force play video
 */
function playWelcomeMessage() {
    // ✅ ป้องกันเล่นซ้ำ
    if (isWelcomeMessagePlayed) {
        console.log('⏭️ Welcome message already played');
        return;
    }
    
    isWelcomeMessagePlayed = true;
    
    const welcomeText = WELCOME_MESSAGES[userPreferredLanguage] || WELCOME_MESSAGES.th;
    
    console.log(`🎉 Playing welcome message in ${userPreferredLanguage}: ${welcomeText}`);
    
    // ✅ Force play video ถ้ายังไม่ได้เล่น (แก้บัคตอนรีเฟรช)
    if (useVideoAvatar && videoAvatar && videoAvatar.paused) {
        videoAvatar.play().catch(e => {
            console.warn('⚠️ Autoplay blocked, will play on user interaction');
        });
    }
    
    // แสดงข้อความ
    showMessage(welcomeText);
    
    // เล่นเสียง
    speakText(welcomeText, userPreferredLanguage);
}

/**
 * 🎬 Initialize Video Avatar
 */
function initVideoAvatar() {
    const container = document.querySelector('.avatar-container');
    
    // สร้าง video element
    videoAvatar = document.createElement('video');
    videoAvatar.id = 'videoAvatar';
    
    // ✅ CSS สำหรับ transparent background
    videoAvatar.style.cssText = `
        position: absolute;
        max-width: 80%;
        max-height: 80%;
        object-fit: contain;
        z-index: 5;
        opacity: 1;
        transition: opacity 0.3s ease;
        background: transparent !important;
        mix-blend-mode: normal;
    `;
    
    // ตั้งค่าวิดีโอ
    videoAvatar.muted = true;
    videoAvatar.playsInline = true;
    videoAvatar.loop = true;
    videoAvatar.preload = 'auto';
    
    // ✅ เริ่มต้นด้วย idle video
    videoAvatar.src = VIDEO_AVATAR_PATH + IDLE_VIDEO;
    currentVideoState = 'idle';
    
    container.appendChild(videoAvatar);
    
    // Timeout fallback
    const loadTimeout = setTimeout(() => {
        if (videoAvatar.readyState < 2) {
            console.warn('Video loading timeout. Switching to 3D avatar...');
            
            Swal.fire({
                icon: 'info',
                title: 'Loading 3D Avatar',
                text: 'Video taking too long. Using 3D model instead.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
            
            useVideoAvatar = false;
            container.removeChild(videoAvatar);
            init3DAvatar();
        }
    }, 5000);
    
    // Event Listeners
    videoAvatar.addEventListener('loadeddata', function() {
        clearTimeout(loadTimeout);
        console.log('✅ Idle video loaded');
        console.log('Video dimensions:', videoAvatar.videoWidth, 'x', videoAvatar.videoHeight);
        
        // ✅ พยายาม autoplay
        videoAvatar.play().catch(e => {
            console.log('⏸️ Autoplay prevented, waiting for user interaction');
        });
    });
    
    videoAvatar.addEventListener('error', function(e) {
        clearTimeout(loadTimeout);
        console.error('❌ Video error:', videoAvatar.error);
        console.error('Error code:', videoAvatar.error ? videoAvatar.error.code : 'unknown');
        
        useVideoAvatar = false;
        container.removeChild(videoAvatar);
        init3DAvatar();
    });
    
    videoAvatar.load();
    
    // ✅ Preload speaking video ล่วงหน้า
    setTimeout(() => preloadSpeakingVideo(), 1000);
}

/**
 * 📥 Preload speaking video
 */
function preloadSpeakingVideo() {
    if (preloadedSpeakingVideo) return;
    
    preloadedSpeakingVideo = document.createElement('video');
    preloadedSpeakingVideo.muted = true;
    preloadedSpeakingVideo.playsInline = true;
    preloadedSpeakingVideo.loop = true;
    preloadedSpeakingVideo.preload = 'auto';
    preloadedSpeakingVideo.src = VIDEO_AVATAR_PATH + SPEAKING_VIDEO;
    
    preloadedSpeakingVideo.addEventListener('loadeddata', function() {
        console.log('✅ Speaking video preloaded');
    });
    
    preloadedSpeakingVideo.load();
}

/**
 * 🎭 Play idle animation
 */
function playIdleAnimation() {
    if (!videoAvatar || isTransitioning) return;
    if (currentVideoState === 'idle') return;
    
    switchToVideo(IDLE_VIDEO, 'idle');
}

/**
 * 🗣️ Play speaking animation
 */
function playSpeakingAnimation() {
    if (!videoAvatar || isTransitioning) return;
    if (currentVideoState === 'speaking') return;
    
    switchToVideo(SPEAKING_VIDEO, 'speaking');
}

/**
 * 🔄 Switch video smoothly
 */
function switchToVideo(videoFile, newState) {
    if (isTransitioning) return;
    
    isTransitioning = true;
    const container = videoAvatar.parentElement;
    
    // ✅ สร้าง video ใหม่
    const newVideo = document.createElement('video');
    newVideo.id = 'videoAvatar';
    newVideo.style.cssText = videoAvatar.style.cssText;
    newVideo.style.opacity = '0';
    newVideo.muted = true;
    newVideo.playsInline = true;
    newVideo.loop = true;
    newVideo.src = VIDEO_AVATAR_PATH + videoFile;
    
    container.appendChild(newVideo);
    
    // เมื่อ video ใหม่พร้อม
    newVideo.addEventListener('canplay', function playNew() {
        newVideo.removeEventListener('canplay', playNew);
        
        // เล่นวิดีโอใหม่
        newVideo.play().then(() => {
            // Fade out เก่า, fade in ใหม่
            videoAvatar.style.opacity = '0';
            newVideo.style.opacity = '1';
            
            setTimeout(() => {
                // ลบวิดีโอเก่า
                container.removeChild(videoAvatar);
                videoAvatar = newVideo;
                currentVideoState = newState;
                isTransitioning = false;
                
                console.log(`✅ Switched to ${newState} video`);
            }, 300);
        }).catch(e => {
            console.error('Play error:', e);
            container.removeChild(newVideo);
            isTransitioning = false;
        });
    });
    
    newVideo.load();
}

/**
 * 🤐 Stop speaking animation
 */
function stopSpeakingAnimation() {
    playIdleAnimation();
}

/**
 * 🎨 Original 3D Avatar initialization (fallback)
 */
function init3DAvatar() {
    const canvas = document.getElementById('avatarCanvas');
    
    scene = new THREE.Scene();
    scene.background = null; 
    
    camera = new THREE.PerspectiveCamera(75, canvas.clientWidth / canvas.clientHeight, 0.1, 1000);
    camera.position.z = 7;
    
    renderer = new THREE.WebGLRenderer({
        canvas: canvas,
        antialias: true,
        alpha: true
    });

    renderer.setClearColor(0x000000, 0);
    renderer.setSize(canvas.clientWidth, canvas.clientHeight);
    renderer.shadowMap.enabled = true;
    
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
    scene.add(ambientLight);
    
    const directionalLight = new THREE.DirectionalLight(0xffffff, 0.6);
    directionalLight.position.set(5, 10, 7);
    directionalLight.castShadow = true;
    scene.add(directionalLight);
    
    const pinkLight = new THREE.PointLight(0xff69b4, 1.5, 100);
    pinkLight.position.set(-5, 3, 5);
    scene.add(pinkLight);
    
    const cyanLight = new THREE.PointLight(0x00ffff, 1.5, 100);
    cyanLight.position.set(5, 3, 5);
    scene.add(cyanLight);
    
    const purpleLight = new THREE.PointLight(0x9d4edd, 1, 100);
    purpleLight.position.set(0, -2, 5);
    scene.add(purpleLight);
    
    createPastelSheepCharacter();
    animate();
    
    window.addEventListener('resize', onWindowResize);
}

function createPastelSheepCharacter() {
    const character = new THREE.Group();
    
    const headGeometry = new THREE.SphereGeometry(1.4, 32, 32);
    const headMaterial = new THREE.MeshPhongMaterial({ 
        color: 0x87CEEB,
        shininess: 60,
        emissive: 0x5dade2,
        emissiveIntensity: 0.15
    });
    const head = new THREE.Mesh(headGeometry, headMaterial);
    head.castShadow = true;
    character.add(head);
    
    avatar = character;
    scene.add(avatar);
}

function animate() {
    requestAnimationFrame(animate);
    
    if (!useVideoAvatar && avatar) {
        if (!isSpeaking) {
            avatar.rotation.y = Math.sin(Date.now() * 0.0008) * 0.08;
            avatar.position.y = Math.sin(Date.now() * 0.0015) * 0.12;
            
            if (Math.random() > 0.995) {
                blinkEyes();
            }
        }
        
        if (isSpeaking && mouth) {
            const mouthScale = 1 + Math.sin(Date.now() * 0.025) * 0.5;
            mouth.scale.y = mouthScale;
            avatar.rotation.x = Math.sin(Date.now() * 0.004) * 0.04;
        }
        
        renderer.render(scene, camera);
    }
}

function blinkEyes() {
    if (leftEye && rightEye) {
        leftEye.scale.y = 0.1;
        rightEye.scale.y = 0.1;
        
        setTimeout(() => {
            leftEye.scale.y = 1;
            rightEye.scale.y = 1;
        }, 150);
    }
}

function onWindowResize() {
    if (!useVideoAvatar) {
        const canvas = document.getElementById('avatarCanvas');
        camera.aspect = canvas.clientWidth / canvas.clientHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(canvas.clientWidth, canvas.clientHeight);
    }
}

function loadConversations() {
    $.ajax({
        url: 'app/actions/get_chat_data.php?action=list_conversations',
        type: 'GET',
        headers: { 'Authorization': 'Bearer ' + jwt },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                displayConversations(response.conversations);
            } else if (response.require_login) {
                window.location.href = '?login';
            }
        }
    });
}

function displayConversations(conversations) {
    const $list = $('#conversationsList');
    $list.empty();
    
    if (conversations.length === 0) {
        $list.html('<p style="text-align: center; color: #666; padding: 20px; font-size: 13px;">No conversations yet</p>');
        return;
    }
    
    conversations.forEach(function(conv) {
        const isActive = conv.conversation_id === currentConversationId ? 'active' : '';
        const timeAgo = formatTimeAgo(conv.updated_at);
        
        const $item = $(`
            <div class="conversation-item ${isActive}" data-id="${conv.conversation_id}">
                <div class="conversation-title">${escapeHtml(conv.title)}</div>
                <div class="conversation-preview">${escapeHtml(conv.last_message)}</div>
                <div class="conversation-time">${timeAgo}</div>
                <button class="delete-conv-btn" onclick="deleteConversation(${conv.conversation_id}, event)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `);
        
        $item.on('click', function() {
            loadConversation(conv.conversation_id);
        });
        
        $list.append($item);
    });
}

function loadConversation(conversationId) {
    currentConversationId = conversationId;
    
    $('.conversation-item').removeClass('active');
    $(`.conversation-item[data-id="${conversationId}"]`).addClass('active');
    
    $.ajax({
        url: 'app/actions/get_chat_data.php?action=get_history&conversation_id=' + conversationId,
        type: 'GET',
        headers: { 'Authorization': 'Bearer ' + jwt },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success' && response.messages.length > 0) {
                const lastMessage = response.messages[response.messages.length - 1];
                if (lastMessage.role === 'assistant') {
                    showMessage(lastMessage.message);
                }
            }
        }
    });
}

function sendMessage() {
    const message = $('#messageInput').val().trim();
    
    if (!message) return;
    
    // ✅ เล่นวิดีโอถ้ายังไม่ได้เล่น (autoplay policy)
    if (useVideoAvatar && videoAvatar && videoAvatar.paused) {
        videoAvatar.play().catch(e => console.log('Play on interaction'));
    }
    
    $('#messageInput').prop('disabled', true);
    $('#sendBtn').prop('disabled', true);
    $('#messageInput').val('').css('height', 'auto');
    
    updateStatus('Thinking...', false);
    
    $.ajax({
        url: 'app/actions/ai_chat.php',
        type: 'POST',
        headers: {
            'Authorization': 'Bearer ' + jwt,
            'Content-Type': 'application/json'
        },
        data: JSON.stringify({
            conversation_id: currentConversationId,
            message: message
            // ไม่ต้องส่ง language เพราะ PHP จะดึงจาก preferred_language เอง
        }),
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                if (currentConversationId === 0) {
                    currentConversationId = response.conversation_id;
                    loadConversations();
                }
                
                showMessage(response.ai_message);
                speakText(response.ai_message);
            } else {
                Swal.fire('Error', response.message, 'error');
                updateStatus('Ready to chat', false);
            }
            
            $('#messageInput').prop('disabled', false).focus();
            $('#sendBtn').prop('disabled', false);
        },
        error: function() {
            Swal.fire('Error', 'Failed to send message', 'error');
            updateStatus('Ready to chat', false);
            $('#messageInput').prop('disabled', false).focus();
            $('#sendBtn').prop('disabled', false);
        }
    });
}

function showMessage(text) {
    $('#messageText').text(text);
    $('#currentMessage').fadeIn();
}

/**
 * 🗣️ Speak Text (รองรับการส่ง langCode จาก Welcome Message)
 */
function speakText(text, forceLangCode = null) {
    let langCode = forceLangCode; // ถ้ามีการบังคับภาษา ให้ใช้เลย
    let detectedLang = 'Thai';
    
    // ถ้าไม่มีการบังคับภาษา ให้ detect จากข้อความ
    if (!langCode) {
        if (/[\u0E00-\u0E7F]/.test(text)) {
            langCode = 'th';
            detectedLang = 'Thai';
        } else if (/[\u4E00-\u9FFF]/.test(text)) {
            langCode = 'zh';
            detectedLang = 'Chinese';
        } else if (/[\u3040-\u309F\u30A0-\u30FF]/.test(text)) {
            langCode = 'ja';
            detectedLang = 'Japanese';
        } else if (/[\uAC00-\uD7AF]/.test(text)) {
            langCode = 'ko';
            detectedLang = 'Korean';
        } else {
            langCode = 'en';
            detectedLang = 'English';
        }
    } else {
        // Map langCode to detectedLang
        const langMap = {
            'th': 'Thai',
            'en': 'English',
            'zh': 'Chinese',
            'ja': 'Japanese',
            'ko': 'Korean'
        };
        detectedLang = langMap[langCode] || 'English';
    }
    
    isSpeaking = true;
    window.isSpeaking = true;
    updateStatus('Speaking in ' + detectedLang + '...', true);
    
    // ✅ เริ่ม speaking animation
    if (useVideoAvatar) {
        playSpeakingAnimation();
    }
    
    const maxLength = 200;
    const chunks = [];
    
    if (text.length > maxLength) {
        const sentences = text.match(/[^.!?。！？]+[.!?。！？]+/g) || [text];
        let currentChunk = '';
        
        for (let sentence of sentences) {
            if ((currentChunk + sentence).length <= maxLength) {
                currentChunk += sentence;
            } else {
                if (currentChunk) chunks.push(currentChunk.trim());
                currentChunk = sentence;
            }
        }
        if (currentChunk) chunks.push(currentChunk.trim());
    } else {
        chunks.push(text);
    }
    
    playTTSChunks(chunks, 0, langCode);
}

let currentAudio = null;

function playTTSChunks(chunks, index, langCode) {
    if (index >= chunks.length) {
        isSpeaking = false;
        window.isSpeaking = false;
        updateStatus('Ready to chat', false);
        $('#currentMessage').fadeOut();
        
        if (mouth) mouth.scale.y = 1;
        
        // ✅ หยุด speaking animation
        if (useVideoAvatar) {
            stopSpeakingAnimation();
        }
        
        return;
    }
    
    const chunk = chunks[index];
    const encodedText = encodeURIComponent(chunk);
    
    let ttsUrl;
    
    if (langCode === 'th') {
        ttsUrl = `https://code.responsivevoice.org/getvoice.php?t=${encodedText}&tl=th&sv=&vn=&pitch=0.5&rate=0.5&vol=1`;
    } else {
        ttsUrl = `https://translate.google.com/translate_tts?ie=UTF-8&tl=${langCode}&client=tw-ob&q=${encodedText}`;
    }
    
    if (currentAudio) {
        currentAudio.pause();
        currentAudio = null;
    }
    
    currentAudio = new Audio();
    
    currentAudio.oncanplaythrough = function() {
        this.play().catch(err => {
            console.error('TTS play error:', err);
            playTTSChunks(chunks, index + 1, langCode);
        });
    };
    
    currentAudio.onplay = function() {
        isSpeaking = true;
        window.isSpeaking = true;
    };
    
    currentAudio.onended = function() {
        setTimeout(() => {
            playTTSChunks(chunks, index + 1, langCode);
        }, 300);
    };
    
    currentAudio.onerror = function(e) {
        console.error('TTS error:', e);
        fallbackToWebSpeech(chunks.join(' '), langCode);
    };
    
    currentAudio.src = ttsUrl;
    currentAudio.load();
}

function fallbackToWebSpeech(text, langCode) {
    if (!window.speechSynthesis) {
        isSpeaking = false;
        window.isSpeaking = false;
        updateStatus('Ready to chat', false);
        
        if (useVideoAvatar) {
            stopSpeakingAnimation();
        }
        
        Swal.fire({
            icon: 'warning',
            title: 'TTS Not Available',
            text: 'Text-to-speech is not available. Please try using Chrome or Edge browser.',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000
        });
        return;
    }
    
    window.speechSynthesis.cancel();
    
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = langCode === 'th' ? 'th-TH' : 
                     langCode === 'zh' ? 'zh-CN' :
                     langCode === 'ja' ? 'ja-JP' :
                     langCode === 'ko' ? 'ko-KR' : 'en-US';
    utterance.rate = 0.85;
    utterance.pitch = 1.0;
    utterance.volume = 1.0;
    
    utterance.onstart = function() {
        isSpeaking = true;
        window.isSpeaking = true;
    };
    
    utterance.onend = function() {
        isSpeaking = false;
        window.isSpeaking = false;
        updateStatus('Ready to chat', false);
        $('#currentMessage').fadeOut();
        
        if (mouth) mouth.scale.y = 1;
        if (useVideoAvatar) {
            stopSpeakingAnimation();
        }
    };
    
    utterance.onerror = function(event) {
        isSpeaking = false;
        window.isSpeaking = false;
        updateStatus('Ready to chat', false);
        
        if (useVideoAvatar) {
            stopSpeakingAnimation();
        }
    };
    
    window.speechSynthesis.speak(utterance);
}

function updateStatus(text, speaking) {
    $('#statusText').text(text);
    
    if (speaking) {
        $('#statusDot').addClass('speaking');
    } else {
        $('#statusDot').removeClass('speaking');
    }
}

function createNewChat() {
    currentConversationId = 0;
    $('.conversation-item').removeClass('active');
    $('#messageInput').val('').focus();
    $('#currentMessage').fadeOut();
    
    if (window.speechSynthesis) {
        window.speechSynthesis.cancel();
    }
    
    if (currentAudio) {
        currentAudio.pause();
        currentAudio = null;
    }
    
    isSpeaking = false;
    window.isSpeaking = false;
    updateStatus('Ready to chat', false);
    
    if (useVideoAvatar) {
        playIdleAnimation();
    }
}

function deleteConversation(conversationId, event) {
    event.stopPropagation();
    
    Swal.fire({
        title: 'Delete Conversation?',
        text: 'This action cannot be undone',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'app/actions/get_chat_data.php?action=delete_conversation&conversation_id=' + conversationId,
                type: 'GET',
                headers: { 'Authorization': 'Bearer ' + jwt },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire('Deleted!', 'Conversation has been deleted', 'success');
                        
                        if (conversationId === currentConversationId) {
                            createNewChat();
                        }
                        
                        loadConversations();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                }
            });
        }
    });
}

function handleKeyPress(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
    }
}

function formatTimeAgo(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    
    if (diff < 60) return 'Just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
    
    return date.toLocaleDateString('th-TH', { month: 'short', day: 'numeric' });
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