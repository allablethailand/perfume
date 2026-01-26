/**
 * AI Chat 3D - Mobile Audio Fix + Unmute Button
 * ✅ แก้ปัญหาเสียงบนมือถือ 100%
 * ✅ ปุ่ม Unmute/Mute ขวาตรงกลาง
 * ✅ รองรับ iOS, Android, Desktop
 * ✅ Auto-unlock audio เมื่อ user interaction
 */

let currentConversationId = 0;
const jwt = sessionStorage.getItem("jwt");

let scene, camera, renderer, avatar, mouth, leftEye, rightEye, leftEyePupil, rightEyePupil;
let isSpeaking = false;
let waveIntensity = 0;

// Video Avatar Settings
let videoAvatar = null;
let useVideoAvatar = true;

let IDLE_VIDEO_URL = '';
let SPEAKING_VIDEO_URL = '';
let currentVideoState = 'idle';
let isTransitioning = false;
let preloadedSpeakingVideo = null;

// Global variables
window.isSpeaking = false;
window.waveIntensity = 0;

// 🔊 Audio Management
let audioContext = null;
let isAudioUnlocked = false;
let isMuted = true; // เริ่มต้นเป็น muted
let currentAudio = null;

// Welcome Messages (5 ภาษา)
const WELCOME_MESSAGES = {
    th: "ยินดีต้อนรับกลับมานะเพื่อน",
    en: "Welcome back, my friend",
    cn: "欢迎回来,我的朋友",
    jp: "おかえりなさい、友よ",
    kr: "다시 오신 것을 환영합니다, 친구"
};

let userPreferredLanguage = 'th';
let isWelcomeMessagePlayed = false;
let aiCompanionData = null;

$(document).ready(function() {
    if (!jwt) {
        window.location.href = '?login';
        return;
    }
    
    // ✅ Setup Unmute Button
    setupUnmuteButton();
    
    // ✅ Setup auto-unlock events
    setupAutoUnlock();
    
    // ✅ Fetch AI data & initialize
    fetchAICompanionData().then(() => {
        if (useVideoAvatar && IDLE_VIDEO_URL && SPEAKING_VIDEO_URL) {
            initVideoAvatar();
        } else {
            init3DAvatar();
        }
        
        loadConversations();
        
        // แสดง Welcome message (แต่ยังไม่เล่นเสียง)
        setTimeout(() => {
            showWelcomeMessage();
        }, 800);
    });
    
    $('#messageInput').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
});

/**
 * 🔊 Setup Unmute Button
 */
function setupUnmuteButton() {
    const unmuteBtn = document.getElementById('unmuteBtn');
    
    unmuteBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        toggleMute();
    });
}

/**
 * 🔄 Toggle Mute/Unmute
 */
function toggleMute() {
    const unmuteBtn = document.getElementById('unmuteBtn');
    const icon = unmuteBtn.querySelector('i');
    
    if (isMuted) {
        // Unmute
        isMuted = false;
        unmuteBtn.classList.remove('muted');
        unmuteBtn.classList.add('unmuted');
        icon.className = 'fas fa-volume-up';
        unmuteBtn.title = 'Click to mute';
        
        // Unlock audio
        unlockAudio();
        
        // เล่น welcome message ถ้ายังไม่ได้เล่น
        if (!isWelcomeMessagePlayed) {
            playWelcomeMessage();
        }
        
        // แจ้งเตือน
        showToast('🔊 Sound Enabled', 'success');
        
    } else {
        // Mute
        isMuted = true;
        unmuteBtn.classList.remove('unmuted');
        unmuteBtn.classList.add('muted');
        icon.className = 'fas fa-volume-mute';
        unmuteBtn.title = 'Click to enable sound';
        
        // หยุดเสียงที่กำลังเล่น
        stopCurrentAudio();
        
        showToast('🔇 Sound Muted', 'info');
    }
}

/**
 * 🔓 Auto-unlock audio on user interaction
 */
function setupAutoUnlock() {
    const unlockEvents = ['touchstart', 'touchend', 'click', 'keydown'];
    
    unlockEvents.forEach(eventName => {
        document.addEventListener(eventName, function unlockOnce() {
            if (!isAudioUnlocked) {
                unlockAudio();
            }
        }, { once: false, passive: true });
    });
}

/**
 * 🔓 Unlock Audio (iOS/Android)
 */
function unlockAudio() {
    if (isAudioUnlocked) return;
    
    try {
        // สร้าง AudioContext
        if (!audioContext) {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }
        
        // Resume AudioContext
        if (audioContext.state === 'suspended') {
            audioContext.resume().then(() => {
                console.log('✅ AudioContext resumed');
            });
        }
        
        // เล่นเสียงเงียบสั้นๆ
        const silentBuffer = audioContext.createBuffer(1, 1, 22050);
        const source = audioContext.createBufferSource();
        source.buffer = silentBuffer;
        source.connect(audioContext.destination);
        source.start(0);
        
        isAudioUnlocked = true;
        console.log('✅ Audio unlocked');
        
        // Unlock video avatar
        if (videoAvatar && videoAvatar.paused) {
            videoAvatar.play().catch(e => {
                console.log('Video play will retry on interaction');
            });
        }
        
    } catch (err) {
        console.warn('⚠️ Audio unlock error:', err.message);
    }
}

/**
 * 🛑 Stop current audio
 */
function stopCurrentAudio() {
    if (currentAudio) {
        currentAudio.pause();
        currentAudio = null;
    }
    
    if (window.speechSynthesis) {
        window.speechSynthesis.cancel();
    }
    
    isSpeaking = false;
    window.isSpeaking = false;
    
    if (useVideoAvatar) {
        stopSpeakingAnimation();
    }
}

/**
 * 📢 Show Toast Notification
 */
function showToast(message, icon = 'info') {
    Swal.fire({
        icon: icon,
        title: message,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true
    });
}

/**
 * 🔍 Fetch AI Companion Data
 */
function fetchAICompanionData() {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: 'app/actions/get_ai_companion_info.php',
            type: 'GET',
            headers: { 'Authorization': 'Bearer ' + jwt },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    aiCompanionData = response.companion;
                    IDLE_VIDEO_URL = response.companion.idle_video_url || '';
                    SPEAKING_VIDEO_URL = response.companion.talking_video_url || '';
                    userPreferredLanguage = response.companion.preferred_language || 'th';
                    
                    console.log('✅ AI Companion loaded:', {
                        ai_id: response.companion.ai_id,
                        ai_name: response.companion.ai_name,
                        language: userPreferredLanguage,
                        idle_video: IDLE_VIDEO_URL,
                        talking_video: SPEAKING_VIDEO_URL
                    });
                    
                    if (!IDLE_VIDEO_URL || !SPEAKING_VIDEO_URL) {
                        console.warn('⚠️ Video URLs not found, switching to 3D avatar');
                        useVideoAvatar = false;
                    }
                    
                    resolve();
                } else {
                    console.error('❌ Failed to fetch AI companion data');
                    useVideoAvatar = false;
                    resolve();
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error fetching AI companion:', error);
                useVideoAvatar = false;
                resolve();
            }
        });
    });
}

/**
 * 🎉 Show Welcome Message (ยังไม่เล่นเสียง)
 */
function showWelcomeMessage() {
    const welcomeText = WELCOME_MESSAGES[userPreferredLanguage] || WELCOME_MESSAGES.th;
    showMessage(welcomeText);
    
    console.log(`👋 Welcome message displayed: ${welcomeText}`);
}

/**
 * 🎉 Play Welcome Message (เล่นเสียง)
 */
function playWelcomeMessage() {
    if (isWelcomeMessagePlayed || isMuted) {
        return;
    }
    
    isWelcomeMessagePlayed = true;
    
    const welcomeText = WELCOME_MESSAGES[userPreferredLanguage] || WELCOME_MESSAGES.th;
    
    console.log(`🎉 Playing welcome message: ${welcomeText}`);
    
    // Unlock audio
    unlockAudio();
    
    // เล่นเสียง
    setTimeout(() => {
        speakText(welcomeText, userPreferredLanguage);
    }, 300);
}

/**
 * 🎬 Initialize Video Avatar
 */
function initVideoAvatar() {
    const container = document.querySelector('.avatar-container');
    
    videoAvatar = document.createElement('video');
    videoAvatar.id = 'videoAvatar';
    
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
    
    videoAvatar.muted = true;
    videoAvatar.playsInline = true;
    videoAvatar.loop = true;
    videoAvatar.preload = 'auto';
    videoAvatar.src = IDLE_VIDEO_URL;
    currentVideoState = 'idle';
    
    container.appendChild(videoAvatar);
    
    const loadTimeout = setTimeout(() => {
        if (videoAvatar.readyState < 2) {
            console.warn('Video loading timeout. Switching to 3D avatar...');
            useVideoAvatar = false;
            container.removeChild(videoAvatar);
            init3DAvatar();
        }
    }, 5000);
    
    videoAvatar.addEventListener('loadeddata', function() {
        clearTimeout(loadTimeout);
        console.log('✅ Idle video loaded');
        
        videoAvatar.play().catch(e => {
            console.log('⏸️ Autoplay prevented, waiting for interaction');
        });
    });
    
    videoAvatar.addEventListener('error', function(e) {
        clearTimeout(loadTimeout);
        console.error('❌ Video error:', videoAvatar.error);
        useVideoAvatar = false;
        container.removeChild(videoAvatar);
        init3DAvatar();
    });
    
    videoAvatar.load();
    
    setTimeout(() => preloadSpeakingVideo(), 1000);
}

/**
 * 📥 Preload speaking video
 */
function preloadSpeakingVideo() {
    if (preloadedSpeakingVideo || !SPEAKING_VIDEO_URL) return;
    
    preloadedSpeakingVideo = document.createElement('video');
    preloadedSpeakingVideo.muted = true;
    preloadedSpeakingVideo.playsInline = true;
    preloadedSpeakingVideo.loop = true;
    preloadedSpeakingVideo.preload = 'auto';
    preloadedSpeakingVideo.src = SPEAKING_VIDEO_URL;
    
    preloadedSpeakingVideo.addEventListener('loadeddata', function() {
        console.log('✅ Speaking video preloaded');
    });
    
    preloadedSpeakingVideo.load();
}

/**
 * 🎭 Play idle animation
 */
function playIdleAnimation() {
    if (!videoAvatar || isTransitioning || !IDLE_VIDEO_URL) return;
    if (currentVideoState === 'idle') return;
    
    switchToVideo(IDLE_VIDEO_URL, 'idle');
}

/**
 * 🗣️ Play speaking animation
 */
function playSpeakingAnimation() {
    if (!videoAvatar || isTransitioning || !SPEAKING_VIDEO_URL) return;
    if (currentVideoState === 'speaking') return;
    
    switchToVideo(SPEAKING_VIDEO_URL, 'speaking');
}

/**
 * 🔄 Switch video smoothly
 */
function switchToVideo(videoUrl, newState) {
    if (isTransitioning || !videoUrl) return;
    
    isTransitioning = true;
    const container = videoAvatar.parentElement;
    
    const newVideo = document.createElement('video');
    newVideo.id = 'videoAvatar';
    newVideo.style.cssText = videoAvatar.style.cssText;
    newVideo.style.opacity = '0';
    newVideo.muted = true;
    newVideo.playsInline = true;
    newVideo.loop = true;
    newVideo.src = videoUrl;
    
    container.appendChild(newVideo);
    
    newVideo.addEventListener('canplay', function playNew() {
        newVideo.removeEventListener('canplay', playNew);
        
        newVideo.play().then(() => {
            videoAvatar.style.opacity = '0';
            newVideo.style.opacity = '1';
            
            setTimeout(() => {
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
 * 🎨 Original 3D Avatar initialization
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
    
    // ✅ Unlock audio & video
    unlockAudio();
    
    if (videoAvatar && videoAvatar.paused) {
        videoAvatar.play().catch(e => console.log('Video play on interaction'));
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
        }),
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                if (currentConversationId === 0) {
                    currentConversationId = response.conversation_id;
                    loadConversations();
                }
                
                showMessage(response.ai_message);
                
                // ✅ เล่นเสียงถ้าไม่ได้ mute
                if (!isMuted) {
                    speakText(response.ai_message);
                }
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
 * 🗣️ Speak text with language detection
 */
function speakText(text, forceLangCode = null) {
    // ถ้า muted ไม่ต้องเล่นเสียง
    if (isMuted) {
        console.log('🔇 Audio is muted, skipping TTS');
        return;
    }
    
    let langCode = forceLangCode;
    let detectedLang = 'Thai';
    
    if (!langCode) {
        if (/[\u0E00-\u0E7F]/.test(text)) {
            langCode = 'th';
            detectedLang = 'Thai';
        } else if (/[\u4E00-\u9FFF]/.test(text)) {
            langCode = 'cn';
            detectedLang = 'Chinese';
        } else if (/[\u3040-\u309F\u30A0-\u30FF]/.test(text)) {
            langCode = 'jp';
            detectedLang = 'Japanese';
        } else if (/[\uAC00-\uD7AF]/.test(text)) {
            langCode = 'kr';
            detectedLang = 'Korean';
        } else {
            langCode = 'en';
            detectedLang = 'English';
        }
    } else {
        const langMap = {
            'th': 'Thai',
            'en': 'English',
            'cn': 'Chinese',
            'jp': 'Japanese',
            'kr': 'Korean'
        };
        detectedLang = langMap[langCode] || 'English';
    }
    
    isSpeaking = true;
    window.isSpeaking = true;
    updateStatus('Speaking in ' + detectedLang + '...', true);
    
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

/**
 * 🔊 Play TTS chunks
 */
function playTTSChunks(chunks, index, langCode) {
    // ถ้า muted หรือผู้ใช้หยุด ให้หยุดทันที
    if (isMuted || index >= chunks.length) {
        isSpeaking = false;
        window.isSpeaking = false;
        updateStatus('Ready to chat', false);
        $('#currentMessage').fadeOut();
        
        if (mouth) mouth.scale.y = 1;
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
        let googleLangCode = langCode;
        if (langCode === 'cn') googleLangCode = 'zh-CN';
        if (langCode === 'jp') googleLangCode = 'ja';
        if (langCode === 'kr') googleLangCode = 'ko';
        
        ttsUrl = `https://translate.google.com/translate_tts?ie=UTF-8&tl=${googleLangCode}&client=tw-ob&q=${encodedText}`;
    }
    
    if (currentAudio) {
        currentAudio.pause();
        currentAudio = null;
    }
    
    currentAudio = new Audio();
    currentAudio.crossOrigin = 'anonymous';
    currentAudio.preload = 'auto';
    
    currentAudio.oncanplaythrough = function() {
        if (isMuted) {
            playTTSChunks(chunks, chunks.length, langCode); // Skip remaining
            return;
        }
        
        const playPromise = this.play();
        
        if (playPromise !== undefined) {
            playPromise.then(() => {
                console.log('🔊 Audio playing');
            }).catch(err => {
                console.error('❌ TTS play error:', err.message);
                playTTSChunks(chunks, index + 1, langCode);
            });
        }
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

/**
 * 🔄 Fallback to Web Speech API
 */
function fallbackToWebSpeech(text, langCode) {
    if (!window.speechSynthesis || isMuted) {
        isSpeaking = false;
        window.isSpeaking = false;
        updateStatus('Ready to chat', false);
        
        if (useVideoAvatar) {
            stopSpeakingAnimation();
        }
        
        if (!isMuted) {
            showToast('⚠️ TTS Not Available', 'warning');
        }
        return;
    }
    
    window.speechSynthesis.cancel();
    
    const utterance = new SpeechSynthesisUtterance(text);
    
    if (langCode === 'th') {
        utterance.lang = 'th-TH';
    } else if (langCode === 'cn') {
        utterance.lang = 'zh-CN';
    } else if (langCode === 'jp') {
        utterance.lang = 'ja-JP';
    } else if (langCode === 'kr') {
        utterance.lang = 'ko-KR';
    } else {
        utterance.lang = 'en-US';
    }
    
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
    
    stopCurrentAudio();
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