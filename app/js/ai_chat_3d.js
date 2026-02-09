/**
 * AI Chat 3D - FIXED: Preload Audio Before Display
 * ✅ รอให้เสียงโหลดเสร็จก่อนแสดงข้อความและท่าพูด
 * ✅ แสดง typing indicator "..." ระหว่างรอ
 * ✅ Sync การแสดงผลกับเสียงให้ตรงกัน
 */

// ========== Global Variables ==========
const urlParams = new URLSearchParams(window.location.search);
const aiCodeFromURL = urlParams.get('ai_code') || '';
const langFromURL = urlParams.get('lang') || 'th';
const companionIdFromURL = urlParams.get('user_companion_id');

let currentConversationId = 0;
let companionId = null;
let jwt = sessionStorage.getItem("jwt");
let isGuestMode = !jwt;

let scene, camera, renderer, avatar, mouth, leftEye, rightEye, leftEyePupil, rightEyePupil;
let isSpeaking = false;
let waveIntensity = 0;

// Video Avatar Settings
let videoAvatar = null;
let useVideoAvatar = true;

// Video URLs from database
let IDLE_VIDEO_URL = '';
let SPEAKING_VIDEO_URL = '';
let currentVideoState = 'idle';
let isTransitioning = false;
let preloadedSpeakingVideo = null;

// Global variables for wave animation
window.isSpeaking = false;
window.waveIntensity = 0;

// Welcome Messages (5 languages)
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

// ✅ NEW: Audio Preload Queue
let audioPreloadQueue = [];
let isProcessingAudio = false;

// ========== Initialize ==========
$(document).ready(function() {
    console.log('🚀 Initializing AI Chat 3D...');
    console.log('AI Code:', aiCodeFromURL);
    console.log('Guest Mode:', isGuestMode);
    
    if (!aiCodeFromURL && !jwt) {
        Swal.fire({
            title: 'Access Denied',
            text: 'Please provide AI code or login',
            icon: 'error'
        }).then(() => {
            window.location.href = '?';
        });
        return;
    }
    
    const storedCompanionId = sessionStorage.getItem('user_companion_id');
    if (storedCompanionId) {
        companionId = parseInt(storedCompanionId);
        console.log('✅ Found stored companionId:', companionId);
    }
    
    // Fetch AI companion data
    fetchAICompanionData().then(() => {
        console.log('📊 After fetch:', {
            idle: IDLE_VIDEO_URL ? '✅' : '❌',
            speaking: SPEAKING_VIDEO_URL ? '✅' : '❌',
            useVideo: useVideoAvatar,
            language: userPreferredLanguage
        });
        
        if (useVideoAvatar && IDLE_VIDEO_URL && SPEAKING_VIDEO_URL) {
            initVideoAvatar();
        } else {
            console.warn('⚠️ No video URLs, using 3D avatar');
            init3DAvatar();
        }
        
        loadConversations();
        
        // Play Welcome Message
        setTimeout(() => {
            playWelcomeMessage();
        }, 800);
    });
    
    $('#messageInput').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    
    $('#menuToggle').on('click', function(e) {
        e.stopPropagation();
        $('#dropdownMenu').toggleClass('show');
        $(this).toggleClass('active');
    });
    
    $(document).on('click', function(event) {
        if (!$(event.target).closest('#menuToggle, #dropdownMenu').length) {
            $('#dropdownMenu').removeClass('show');
            $('#menuToggle').removeClass('active');
        }
    });
    
    $('#dropdownMenu').on('click', function(e) {
        e.stopPropagation();
    });
});

/**
 * ✅ Fetch AI Companion Data (เหมือนเดิม)
 */
function fetchAICompanionData() {
    return new Promise((resolve, reject) => {
        let url = '';
        const headers = {};
        
        if (isGuestMode && aiCodeFromURL) {
            url = 'app/actions/get_ai_data.php?ai_code=' + aiCodeFromURL;
            console.log('🔓 Guest Mode: Using ai_code');
        } else if (jwt) {
            url = 'app/actions/get_ai_companion_info.php';
            headers['Authorization'] = 'Bearer ' + jwt;
            console.log('🔐 Login Mode: Using JWT');
        } else {
            reject(new Error('No authentication method available'));
            return;
        }
        
        $.ajax({
            url: url,
            type: 'GET',
            headers: headers,
            dataType: 'json',
            success: function(response) {
                console.log('📡 API Response:', response);
                
                if (response.status === 'success') {
                    aiCompanionData = response.ai_data || response.companion;
                    
                    if (!aiCompanionData) {
                        console.error('❌ No AI data found');
                        useVideoAvatar = false;
                        resolve();
                        return;
                    }
                    
                    IDLE_VIDEO_URL = aiCompanionData.idle_video_url || '';
                    SPEAKING_VIDEO_URL = aiCompanionData.talking_video_url || '';
                    userPreferredLanguage = aiCompanionData.preferred_language || 'th';
                    
                    if (response.companion_id) {
                        companionId = response.companion_id;
                    } else if (aiCompanionData.user_companion_id) {
                        companionId = aiCompanionData.user_companion_id;
                    }
                    
                    if (response.user_id) {
                        aiCompanionData.user_id = response.user_id;
                    }
                    
                    if (companionId) {
                        sessionStorage.setItem('user_companion_id', companionId);
                    }
                    
                    if (aiCompanionData.ai_code) {
                        sessionStorage.setItem('ai_code', aiCompanionData.ai_code);
                    }
                    
                    console.log('✅ AI Companion loaded:', {
                        ai_id: aiCompanionData.ai_id,
                        companion_id: companionId,
                        user_id: aiCompanionData.user_id,
                        language: userPreferredLanguage
                    });
                                
                    resolve();
                } else {
                    console.error('❌ API Error:', response.message);
                    useVideoAvatar = false;
                    resolve();
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ AJAX Error:', error);
                useVideoAvatar = false;
                resolve();
            }
        });
    });
}

/**
 * 🎉 Play Welcome Message
 */
function playWelcomeMessage() {
    if (isWelcomeMessagePlayed) {
        console.log('⏭️ Welcome already played');
        return;
    }
    
    isWelcomeMessagePlayed = true;
    
    const welcomeText = WELCOME_MESSAGES[userPreferredLanguage] || WELCOME_MESSAGES.th;
    
    console.log(`🎉 Playing welcome in ${userPreferredLanguage}: "${welcomeText}"`);
    
    if (useVideoAvatar && videoAvatar && videoAvatar.paused) {
        videoAvatar.play().catch(e => {
            console.warn('⚠️ Autoplay blocked');
        });
    }
    
    // ✅ ใช้ระบบใหม่: preload audio ก่อนแสดง
    speakTextWithPreload(welcomeText, userPreferredLanguage);
}

// ========== Video Avatar Functions (เหมือนเดิม) ==========
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
            console.warn('Video timeout, switching to 3D');
            useVideoAvatar = false;
            container.removeChild(videoAvatar);
            init3DAvatar();
        }
    }, 5000);
    
    videoAvatar.addEventListener('loadeddata', function() {
        clearTimeout(loadTimeout);
        console.log('✅ Idle video loaded');
        videoAvatar.play().catch(e => console.log('Autoplay prevented'));
    });
    
    videoAvatar.addEventListener('error', function(e) {
        clearTimeout(loadTimeout);
        console.error('❌ Video error');
        useVideoAvatar = false;
        container.removeChild(videoAvatar);
        init3DAvatar();
    });
    
    videoAvatar.load();
    setTimeout(() => preloadSpeakingVideo(), 1000);
}

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

function playIdleAnimation() {
    if (!videoAvatar || isTransitioning || !IDLE_VIDEO_URL) return;
    if (currentVideoState === 'idle') return;
    switchToVideo(IDLE_VIDEO_URL, 'idle');
}

function playSpeakingAnimation() {
    if (!videoAvatar || isTransitioning || !SPEAKING_VIDEO_URL) return;
    if (currentVideoState === 'speaking') return;
    switchToVideo(SPEAKING_VIDEO_URL, 'speaking');
}

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
                console.log(`✅ Switched to ${newState}`);
            }, 300);
        }).catch(e => {
            console.error('Play error:', e);
            container.removeChild(newVideo);
            isTransitioning = false;
        });
    });
    
    newVideo.load();
}

function stopSpeakingAnimation() {
    playIdleAnimation();
}

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

// ========== Chat Functions ==========

function loadConversations() {
    console.log('📡 Loading conversations...');
    
    let url = 'app/actions/get_chat_data.php?action=list_conversations';
    const headers = {};
    
    if (isGuestMode) {
        if (companionId) {
            url += '&user_companion_id=' + companionId;
        } else if (aiCodeFromURL) {
            url += '&ai_code=' + aiCodeFromURL;
        }
    } else if (jwt) {
        headers['Authorization'] = 'Bearer ' + jwt;
    }
    
    $.ajax({
        url: url,
        type: 'GET',
        headers: headers,
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                if (response.user_companion_id) {
                    companionId = response.user_companion_id;
                    sessionStorage.setItem('user_companion_id', companionId);
                    console.log('✅ Updated companionId from conversations:', companionId);
                }
                displayConversations(response.conversations);
            } else if (response.require_login === false) {
                displayConversations([]);
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error loading conversations:', error);
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
                <div class="conversation-preview">${escapeHtml(conv.last_message || '')}</div>
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
    
    let url = 'app/actions/get_chat_data.php?action=get_history&conversation_id=' + conversationId;
    const headers = {};
    
    if (isGuestMode && companionId) {
        url += '&user_companion_id=' + companionId;
    } else if (jwt) {
        headers['Authorization'] = 'Bearer ' + jwt;
    }
    
    $.ajax({
        url: url,
        type: 'GET',
        headers: headers,
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success' && response.messages.length > 0) {
                const lastMessage = response.messages[response.messages.length - 1];
                if (lastMessage.role === 'assistant') {
                    // ✅ แสดงข้อความโดยไม่เล่นเสียง (เพราะเป็นประวัติ)
                    showMessage(lastMessage.message);
                }
            }
        }
    });
    
    $('#dropdownMenu').removeClass('show');
    $('#menuToggle').removeClass('active');
}

function sendMessage() {
    const message = $('#messageInput').val().trim();
    
    if (!message) return;
    
    if (!companionId) {
        const storedCompanionId = sessionStorage.getItem('user_companion_id');
        if (storedCompanionId) {
            companionId = parseInt(storedCompanionId);
        }
    }
    
    const hasCompanion = companionId && companionId > 0;
    const hasAICode = aiCodeFromURL && aiCodeFromURL.trim() !== '';
    
    if (!hasCompanion && !hasAICode) {
        Swal.fire({
            icon: 'error',
            title: 'Cannot Send Message',
            text: 'Missing companion or AI code. Please reload the page.',
        });
        return;
    }
    
    if (useVideoAvatar && videoAvatar && videoAvatar.paused) {
        videoAvatar.play().catch(e => console.log('Play on interaction'));
    }
    
    $('#messageInput').prop('disabled', true);
    $('#sendBtn').prop('disabled', true);
    $('#messageInput').val('').css('height', 'auto');
    
    updateStatus('Thinking...', false);
    
    const headers = { 'Content-Type': 'application/json' };
    
    const requestData = {
        conversation_id: currentConversationId,
        message: message,
        preferred_language: userPreferredLanguage || 'th'
    };
    
    if (isGuestMode) {
        if (hasCompanion) {
            requestData.user_companion_id = companionId;
        }
        if (hasAICode) {
            requestData.ai_code = aiCodeFromURL;
        }
    } else if (jwt) {
        headers['Authorization'] = 'Bearer ' + jwt;
        if (hasCompanion) {
            requestData.user_companion_id = companionId;
        }
    }
    
    $.ajax({
        url: 'app/actions/ai_chat.php',
        type: 'POST',
        headers: headers,
        data: JSON.stringify(requestData),
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                if (currentConversationId === 0) {
                    currentConversationId = response.conversation_id;
                    loadConversations();
                }
                
                if (response.user_companion_id) {
                    companionId = response.user_companion_id;
                    sessionStorage.setItem('user_companion_id', companionId);
                }
                
                console.log('✅ AI Response received');
                
                // ✅ ใช้ระบบใหม่: preload audio ก่อนแสดง
                speakTextWithPreload(
                    response.ai_message, 
                    response.language_used || requestData.preferred_language
                );
            } else {
                Swal.fire('Error', response.message, 'error');
                updateStatus('Ready to chat', false);
            }
            
            $('#messageInput').prop('disabled', false).focus();
            $('#sendBtn').prop('disabled', false);
        },
        error: function(xhr, status, error) {
            console.error('❌ Chat error:', xhr.responseText);
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

// ========== ✅ NEW: Audio Preload System ==========

/**
 * ✅ Speak text WITH preload - รอให้เสียงโหลดเสร็จก่อนแสดง UI
 */
function speakTextWithPreload(text, forceLangCode = null) {
    console.log('🎬 Starting speech with preload:', text.substring(0, 50));
    
    // แสดง typing indicator
    updateStatus('Preparing voice...', false);
    $('#messageText').html('<span class="typing-indicator">Thinking...</span>');
    $('#currentMessage').fadeIn();
    
    // ตรวจจับภาษา
    let langCode = forceLangCode || detectLanguage(text);
    
    // แบ่งข้อความเป็น chunks
    const chunks = splitTextIntoChunks(text);
    
    // Preload audio ทั้งหมดก่อน
    preloadAllAudioChunks(chunks, langCode).then((audioUrls) => {
        console.log('✅ All audio preloaded, ready to display and play');
        
        // ✅ ตอนนี้เสียงพร้อมแล้ว → แสดงข้อความ + เล่นเสียง + ท่าพูด
        showMessage(text);
        playPreloadedAudio(audioUrls, langCode, text);
        
    }).catch((error) => {
        console.error('❌ Audio preload failed:', error);
        // Fallback: แสดงข้อความและใช้ Google TTS
        showMessage(text);
        speakText(text, langCode); // ใช้ระบบเดิม
    });
}

/**
 * ✅ Preload audio chunks ทั้งหมดก่อน
 */
function preloadAllAudioChunks(chunks, langCode) {
    return new Promise((resolve, reject) => {
        const audioUrls = [];
        let loadedCount = 0;
        let hasError = false;
        
        console.log(`📥 Preloading ${chunks.length} audio chunks...`);
        
        chunks.forEach((chunk, index) => {
            const requestData = {
                text: chunk,
                language: langCode
            };
            
            if (companionId) {
                requestData.user_companion_id = companionId;
            }
            
            if (aiCompanionData && aiCompanionData.ai_id) {
                requestData.ai_id = aiCompanionData.ai_id;
            }
            
            if (aiCompanionData && aiCompanionData.user_id) {
                requestData.user_id = aiCompanionData.user_id;
            }
            
            $.ajax({
                url: 'app/actions/elevenlabs_tts.php',
                type: 'POST',
                data: JSON.stringify(requestData),
                contentType: 'application/json',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.audio_url) {
                        audioUrls[index] = response.audio_url;
                        loadedCount++;
                        
                        const progress = Math.round((loadedCount / chunks.length) * 100);
                        updateStatus(`Loading voice... ${progress}%`, false);
                        
                        console.log(`✅ Chunk ${index + 1}/${chunks.length} preloaded`);
                        
                        if (loadedCount === chunks.length && !hasError) {
                            resolve(audioUrls);
                        }
                    } else {
                        if (!hasError) {
                            hasError = true;
                            reject(new Error('No audio URL in response'));
                        }
                    }
                },
                error: function(xhr, status, error) {
                    if (!hasError) {
                        hasError = true;
                        reject(new Error('TTS API error: ' + error));
                    }
                }
            });
        });
        
        // Timeout protection (30 วินาที)
        setTimeout(() => {
            if (loadedCount < chunks.length && !hasError) {
                hasError = true;
                reject(new Error('Audio preload timeout'));
            }
        }, 30000);
    });
}

/**
 * ✅ เล่นเสียงที่ preload เสร็จแล้ว
 */
function playPreloadedAudio(audioUrls, langCode, fullText) {
    const langNames = {
        'th': 'Thai',
        'en': 'English',
        'cn': 'Chinese',
        'jp': 'Japanese',
        'kr': 'Korean'
    };
    const detectedLang = langNames[langCode] || 'English';
    
    console.log(`🔊 Playing preloaded audio in ${detectedLang}`);
    
    isSpeaking = true;
    window.isSpeaking = true;
    updateStatus('Speaking in ' + detectedLang + '...', true);
    
    if (useVideoAvatar) {
        playSpeakingAnimation();
    }
    
    playAudioUrlsSequentially(audioUrls, 0, () => {
        // เสร็จหมดแล้ว
        isSpeaking = false;
        window.isSpeaking = false;
        updateStatus('Ready to chat', false);
        $('#currentMessage').fadeOut();
        
        if (mouth) mouth.scale.y = 1;
        if (useVideoAvatar) stopSpeakingAnimation();
        
        console.log('✅ All audio playback completed');
    });
}

/**
 * ✅ เล่น audio URLs แบบ sequential
 */
function playAudioUrlsSequentially(audioUrls, index, onComplete) {
    if (index >= audioUrls.length) {
        if (onComplete) onComplete();
        return;
    }
    
    const audioUrl = audioUrls[index];
    
    if (!audioUrl) {
        console.warn(`⚠️ Skipping chunk ${index + 1} (no URL)`);
        playAudioUrlsSequentially(audioUrls, index + 1, onComplete);
        return;
    }
    
    console.log(`▶️ Playing chunk ${index + 1}/${audioUrls.length}`);
    
    const audio = new Audio(audioUrl);
    
    audio.oncanplaythrough = function() {
        this.play().catch(err => {
            console.error('Audio play error:', err);
            // ข้ามไปชิ้นถัดไป
            playAudioUrlsSequentially(audioUrls, index + 1, onComplete);
        });
    };
    
    audio.onended = function() {
        console.log(`⏹️ Chunk ${index + 1} ended`);
        // เล่นชิ้นถัดไป (หน่วงเวลา 300ms)
        setTimeout(() => {
            playAudioUrlsSequentially(audioUrls, index + 1, onComplete);
        }, 300);
    };
    
    audio.onerror = function(e) {
        console.error(`❌ Audio ${index + 1} error:`, e);
        // ข้ามไปชิ้นถัดไป
        playAudioUrlsSequentially(audioUrls, index + 1, onComplete);
    };
    
    audio.load();
}

/**
 * ✅ ตรวจจับภาษา
 */
function detectLanguage(text) {
    if (/[\u0E00-\u0E7F]/.test(text)) return 'th';
    if (/[\u4E00-\u9FFF]/.test(text)) return 'cn';
    if (/[\u3040-\u309F\u30A0-\u30FF]/.test(text)) return 'jp';
    if (/[\uAC00-\uD7AF]/.test(text)) return 'kr';
    return 'en';
}

/**
 * ✅ แบ่งข้อความเป็น chunks (200 ตัวอักษร)
 */
function splitTextIntoChunks(text, maxLength = 200) {
    const chunks = [];
    
    if (text.length <= maxLength) {
        return [text];
    }
    
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
    
    return chunks;
}

// ========== ✅ FALLBACK: ระบบเดิม (สำหรับ welcome message หรือ error) ==========

let currentAudio = null;

function speakText(text, forceLangCode = null) {
    let langCode = forceLangCode || detectLanguage(text);
    
    const langNames = {
        'th': 'Thai',
        'en': 'English',
        'cn': 'Chinese',
        'jp': 'Japanese',
        'kr': 'Korean'
    };
    const detectedLang = langNames[langCode] || 'English';
    
    console.log(`🔊 Speaking in ${detectedLang}: "${text.substring(0, 50)}..."`);
    
    isSpeaking = true;
    window.isSpeaking = true;
    updateStatus('Speaking in ' + detectedLang + '...', true);
    
    if (useVideoAvatar) {
        playSpeakingAnimation();
    }
    
    const chunks = splitTextIntoChunks(text);
    playTTSChunks(chunks, 0, langCode);
}

function playTTSChunks(chunks, index, langCode) {
    if (index >= chunks.length) {
        isSpeaking = false;
        window.isSpeaking = false;
        updateStatus('Ready to chat', false);
        $('#currentMessage').fadeOut();
        
        if (mouth) mouth.scale.y = 1;
        if (useVideoAvatar) stopSpeakingAnimation();
        
        console.log('✅ TTS completed');
        return;
    }
    
    const chunk = chunks[index];
    
    const requestData = {
        text: chunk,
        language: langCode
    };
    
    if (companionId) {
        requestData.user_companion_id = companionId;
    }
    
    if (aiCompanionData && aiCompanionData.ai_id) {
        requestData.ai_id = aiCompanionData.ai_id;
    }
    
    if (aiCompanionData && aiCompanionData.user_id) {
        requestData.user_id = aiCompanionData.user_id;
    }
    
    $.ajax({
        url: 'app/actions/elevenlabs_tts.php',
        type: 'POST',
        data: JSON.stringify(requestData),
        contentType: 'application/json',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success' && response.audio_url) {
                playAudioFromURL(response.audio_url, () => {
                    setTimeout(() => {
                        playTTSChunks(chunks, index + 1, langCode);
                    }, 300);
                }, () => {
                    playGoogleTTSFallback(chunk, langCode, () => {
                        playTTSChunks(chunks, index + 1, langCode);
                    });
                });
            } else {
                playGoogleTTSFallback(chunk, langCode, () => {
                    playTTSChunks(chunks, index + 1, langCode);
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ ElevenLabs API error:', error);
            playGoogleTTSFallback(chunk, langCode, () => {
                playTTSChunks(chunks, index + 1, langCode);
            });
        }
    });
}

function playAudioFromURL(audioUrl, onEndCallback, onErrorCallback) {
    if (currentAudio) {
        currentAudio.pause();
        currentAudio = null;
    }
    
    currentAudio = new Audio(audioUrl);
    
    currentAudio.oncanplaythrough = function() {
        this.play().catch(err => {
            console.error('Audio play error:', err);
            if (onErrorCallback) onErrorCallback();
        });
    };
    
    currentAudio.onplay = function() {
        console.log('▶️ ElevenLabs audio playing');
        isSpeaking = true;
        window.isSpeaking = true;
    };
    
    currentAudio.onended = function() {
        console.log('⏹️ ElevenLabs audio ended');
        if (onEndCallback) onEndCallback();
    };
    
    currentAudio.onerror = function(e) {
        console.error('❌ Audio playback error:', e);
        if (onErrorCallback) onErrorCallback();
    };
    
    currentAudio.load();
}

function playGoogleTTSFallback(text, langCode, callback) {
    const encodedText = encodeURIComponent(text);
    
    let googleLangCode = langCode;
    if (langCode === 'cn') googleLangCode = 'zh-CN';
    if (langCode === 'jp') googleLangCode = 'ja';
    if (langCode === 'kr') googleLangCode = 'ko';
    if (langCode === 'th') googleLangCode = 'th';
    
    const ttsUrl = `https://translate.google.com/translate_tts?ie=UTF-8&tl=${googleLangCode}&client=tw-ob&q=${encodedText}`;
    
    console.log(`🔊 Using Google TTS fallback for: ${langCode}`);
    
    if (currentAudio) {
        currentAudio.pause();
        currentAudio = null;
    }
    
    currentAudio = new Audio();
    
    currentAudio.oncanplaythrough = function() {
        this.play().catch(err => {
            console.error('Google TTS play error:', err);
            fallbackToWebSpeech(text, langCode);
            if (callback) callback();
        });
    };
    
    currentAudio.onplay = function() {
        console.log('▶️ Google TTS playing');
        isSpeaking = true;
        window.isSpeaking = true;
    };
    
    currentAudio.onended = function() {
        console.log('⏹️ Google TTS ended');
        if (callback) callback();
    };
    
    currentAudio.onerror = function(e) {
        console.error('❌ Google TTS error, using Web Speech API');
        fallbackToWebSpeech(text, langCode);
        if (callback) callback();
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
    
    console.log('🔄 Using Web Speech API fallback');
    
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
        console.error('Web Speech error:', event);
        isSpeaking = false;
        window.isSpeaking = false;
        updateStatus('Ready to chat', false);
        
        if (useVideoAvatar) {
            stopSpeakingAnimation();
        }
    };
    
    window.speechSynthesis.speak(utterance);
}

// ========== Utility Functions ==========

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
    
    $('#dropdownMenu').removeClass('show');
    $('#menuToggle').removeClass('active');
    
    console.log('➕ New chat created');
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
            let url = 'app/actions/get_chat_data.php?action=delete_conversation&conversation_id=' + conversationId;
            const headers = {};
            
            if (isGuestMode && companionId) {
                url += '&user_companion_id=' + companionId;
            } else if (jwt) {
                headers['Authorization'] = 'Bearer ' + jwt;
            }
            
            $.ajax({
                url: url,
                type: 'GET',
                headers: headers,
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
    if (!text) return '';
    
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

function goTo2DMode() {
    const urlParams = new URLSearchParams(window.location.search);
    const lang = urlParams.get('lang') || 'th';
    const aiCode = urlParams.get('ai_code') || '';
    
    let url = '?ai_chat&lang=' + lang;
    if (aiCode) {
        url += '&ai_code=' + aiCode;
    }
    
    console.log('🔄 Switching to 2D Mode:', url);
    window.location.href = url;
}

function goToPreferences() {
    const urlParams = new URLSearchParams(window.location.search);
    const lang = urlParams.get('lang') || 'th';
    const aiCode = urlParams.get('ai_code') || '';
    
    let url = '?ai_edit_prompts&lang=' + lang;
    if (aiCode) {
        url += '&ai_code=' + aiCode;
    }
    
    console.log('⚙️ Opening Preferences:', url);
    window.location.href = url;
}

console.log('✅ AI Chat 3D with Audio Preload loaded');