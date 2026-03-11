/**
 * AI Chat 3D - WITH TTS CACHE SYSTEM + RANDOM VIDEO FIX + NO REPEAT + WEATHER EVERY TIME
 * ✅ Cache: welcome, conversation messages (ไม่ cache AI responses)
 * ✅ รอให้เสียงโหลดเสร็จก่อนแสดงข้อความ
 * ✅ Sync การแสดงผลกับเสียงให้ตรงกัน
 * ✅ FIX: ใช้วิดีโอที่ PHP สุ่มมาให้แล้ว (ไม่สุ่มซ้ำใน JS)
 * ✅ NEW: สุ่มวิดีโอถัดไปไม่ซ้ำกับอันที่เพิ่งเล่น
 * ✅ NEW: พูดสภาพอากาศทุกครั้งที่เข้ามา
 * ✅ FIX: จำ conversation ล่าสุด + New Chat เริ่ม context ใหม่
 * ✅ NEW: 3D Emotion Videos — talking วนจนเสียงเสร็จ แล้วกลับ idle ของ emotion นั้น
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

let videoAvatar = null;
let useVideoAvatar = true;

let IDLE_VIDEO_URLS = [];
let SPEAKING_VIDEO_URLS = [];
let EMOTION_VIDEOS_3D = {}; // ✅ NEW: { happy: { idle: ['url1'], talking: ['url2'] }, ... }
let currentEmotion = 'calm'; // ✅ NEW: emotion ปัจจุบัน

let currentVideoState = 'idle';
let isTransitioning = false;
let preloadedSpeakingVideo = null;

let lastPlayedIdleVideo = null;
let lastPlayedSpeakingVideo = null;
let playEmotionIdleOnce = false; // ✅ เล่น emotion idle แค่ 1 ครั้งแล้วกลับ general idle

window.isSpeaking = false;
window.waveIntensity = 0;

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
let currentAudio = null;

let weatherReportPlayed = false;
let weatherData = null;

// ========== Initialize ==========
$(document).ready(function() {
    console.log('🚀 Initializing AI Chat 3D with TTS Cache + 3D Emotion Videos...');
    
    if (!aiCodeFromURL && !jwt) {
        Swal.fire({
            title: 'Access Denied',
            text: 'Please provide AI code or login',
            icon: 'error'
        }).then(() => { window.location.href = '?'; });
        return;
    }
    
    const storedCompanionId = sessionStorage.getItem('user_companion_id');
    if (storedCompanionId) {
        companionId = parseInt(storedCompanionId);
    }
    
    fetchAICompanionData().then(() => {
        if (useVideoAvatar && IDLE_VIDEO_URLS.length > 0) {
            initVideoAvatar();
        } else {
            init3DAvatar();
        }
        
        loadConversations();
        
        fetchWeatherData().then(() => {
            setTimeout(() => { playWelcomeWithWeather(); }, 800);
        });
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
    
    $('#dropdownMenu').on('click', function(e) { e.stopPropagation(); });
});

// ========== Fetch Weather Data ==========
function fetchWeatherData() {
    return new Promise((resolve) => {
        if ('geolocation' in navigator) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    fetchWeatherByCoordinates(position.coords.latitude, position.coords.longitude).then(resolve);
                },
                (error) => {
                    console.warn('⚠️ Geolocation denied:', error.message);
                    weatherData = null;
                    weatherReportPlayed = true;
                    resolve();
                }
            );
        } else {
            weatherData = null;
            weatherReportPlayed = true;
            resolve();
        }
    });
}

function fetchWeatherByCoordinates(lat, lon) {
    return new Promise((resolve) => {
        const headers = {};
        if (jwt) headers['Authorization'] = 'Bearer ' + jwt;
        
        $.ajax({
            url: `app/actions/get_weather.php?lang=${userPreferredLanguage}&lat=${lat}&lon=${lon}`,
            type: 'GET', headers, dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    weatherData = response.data;
                    weatherReportPlayed = false;
                } else {
                    weatherData = null;
                    weatherReportPlayed = true;
                }
                resolve();
            },
            error: function() {
                weatherData = null;
                weatherReportPlayed = true;
                resolve();
            }
        });
    });
}

// ========== Play Welcome + Weather ==========
function playWelcomeWithWeather() {
    if (isWelcomeMessagePlayed) return;
    isWelcomeMessagePlayed = true;
    
    const welcomeText = WELCOME_MESSAGES[userPreferredLanguage] || WELCOME_MESSAGES.th;
    
    if (useVideoAvatar && videoAvatar && videoAvatar.paused) {
        videoAvatar.play().catch(e => console.warn('⚠️ Autoplay blocked'));
    }
    
    speakTextWithCache(welcomeText, userPreferredLanguage, 'welcome').then(() => {
        if (weatherData && !weatherReportPlayed) {
            setTimeout(() => { playWeatherReport(); }, 1000);
        }
    });
}

function playWeatherReport() {
    if (weatherReportPlayed || !weatherData) return;
    weatherReportPlayed = true;
    speakTextWithCache(weatherData.message, userPreferredLanguage, 'weather');
}

// ========== Fetch AI Data ==========
function fetchAICompanionData() {
    return new Promise((resolve, reject) => {
        let url = '';
        const headers = {};
        
        if (isGuestMode && aiCodeFromURL) {
            url = 'app/actions/get_ai_data.php?ai_code=' + aiCodeFromURL;
            const storedCompanionId = sessionStorage.getItem('user_companion_id');
            if (storedCompanionId) url += '&user_companion_id=' + storedCompanionId;
        } else if (jwt) {
            url = 'app/actions/get_ai_companion_info.php';
            headers['Authorization'] = 'Bearer ' + jwt;
        } else {
            reject(new Error('No authentication method available'));
            return;
        }
        
        $.ajax({
            url, type: 'GET', headers, dataType: 'json',
            success: function(response) {
                // ✅ DEBUG — ลบออกหลังแก้เสร็จ
                console.log('📦 Full API Response:', JSON.stringify(response));
                console.log('🎭 emotion_videos_3d_array:', response.ai_data?.emotion_videos_3d_array || response.companion?.emotion_videos_3d_array);

                if (response.status === 'success') {
                    aiCompanionData = response.ai_data || response.companion;
                    if (!aiCompanionData) { useVideoAvatar = false; resolve(); return; }
                    
                    IDLE_VIDEO_URLS    = aiCompanionData.idle_video_urls_array    || [];
                    SPEAKING_VIDEO_URLS = aiCompanionData.talking_video_urls_array || [];

                    // ✅ โหลด 3D Emotion Videos
                    EMOTION_VIDEOS_3D = aiCompanionData.emotion_videos_3d_array || {};
                    console.log('✅ 3D Emotion videos loaded:', Object.keys(EMOTION_VIDEOS_3D));
                    console.log('✅ EMOTION_VIDEOS_3D full data:', JSON.stringify(EMOTION_VIDEOS_3D));
                    
                    if (response.preferred_language) {
                        userPreferredLanguage = response.preferred_language;
                    } else if (aiCompanionData.preferred_language) {
                        userPreferredLanguage = aiCompanionData.preferred_language;
                    } else {
                        userPreferredLanguage = langFromURL || 'th';
                    }
                    
                    if (response.companion_id) companionId = response.companion_id;
                    else if (aiCompanionData.user_companion_id) companionId = aiCompanionData.user_companion_id;
                    
                    if (response.user_id) aiCompanionData.user_id = response.user_id;
                    if (companionId) sessionStorage.setItem('user_companion_id', companionId);
                    if (aiCompanionData.ai_code) sessionStorage.setItem('ai_code', aiCompanionData.ai_code);
                    
                    autoLoginAsCompanionUser().then(resolve).catch(resolve);
                    
                } else {
                    useVideoAvatar = false;
                    resolve();
                }
            },
            error: function() { useVideoAvatar = false; resolve(); }
        });
    });
}

function autoLoginAsCompanionUser() {
    return new Promise((resolve) => {
        const existingJwt = sessionStorage.getItem('jwt');
        if (existingJwt) {
            console.log('✅ Already have JWT, skipping auto-login');
            resolve();
            return;
        }

        if (!companionId && !aiCodeFromURL) {
            console.warn('⚠️ No companionId or aiCode for auto-login');
            resolve();
            return;
        }

        const params = new URLSearchParams();
        if (companionId) params.set('user_companion_id', companionId);
        if (aiCodeFromURL) params.set('ai_code', aiCodeFromURL);

        console.log('🔐 Attempting auto-login for companion user...');

        $.ajax({
            url: 'app/actions/get_companion_jwt.php?' + params.toString(),
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.jwt) {
                    sessionStorage.setItem('jwt', response.jwt);
                    jwt = response.jwt;
                    isGuestMode = false;
                    console.log('✅ Auto-login success! JWT stored.');
                } else {
                    console.warn('⚠️ Auto-login skipped:', response.message);
                }
                resolve();
            },
            error: function() {
                console.warn('⚠️ Auto-login request failed, continuing anyway');
                resolve();
            }
        });
    });
}

// ========== Speak with CACHE ==========
function speakTextWithCache(text, forceLangCode = null, cacheType = 'welcome') {
    return new Promise((resolve, reject) => {
        updateStatus('Preparing voice...', false);
        $('#messageText').html('<span class="typing-indicator">Thinking...</span>');
        $('#currentMessage').fadeIn();
        
        let langCode = forceLangCode || detectLanguage(text);
        const chunks = splitTextIntoChunks(text);
        
        preloadAllAudioChunksWithCache(chunks, langCode, cacheType).then((audioUrls) => {
            showMessage(text);
            playPreloadedAudio(audioUrls, langCode, text, resolve);
        }).catch((error) => {
            console.error('❌ Audio preload failed:', error);
            showMessage(text);
            fallbackToGoogleTTS(text, langCode, resolve);
        });
    });
}

// ========== Preload Audio WITH CACHE ==========
function preloadAllAudioChunksWithCache(chunks, langCode, cacheType = 'welcome') {
    return new Promise((resolve, reject) => {
        const audioUrls = [];
        let loadedCount = 0;
        let hasError = false;
        
        chunks.forEach((chunk, index) => {
            const requestData = { text: chunk, language: langCode, cache_type: cacheType };
            if (companionId) requestData.user_companion_id = companionId;
            if (aiCompanionData && aiCompanionData.ai_id) requestData.ai_id = aiCompanionData.ai_id;
            if (aiCompanionData && aiCompanionData.voice_id) requestData.voice_id = aiCompanionData.voice_id;
            
            $.ajax({
                url: 'app/actions/get_or_create_tts_cache.php',
                type: 'POST', data: JSON.stringify(requestData),
                contentType: 'application/json', dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.audio_url) {
                        audioUrls[index] = response.audio_url;
                        loadedCount++;
                        updateStatus(`Loading voice... ${Math.round((loadedCount / chunks.length) * 100)}%`, false);
                        if (loadedCount === chunks.length && !hasError) resolve(audioUrls);
                    } else if (!hasError) { hasError = true; reject(new Error('No audio URL')); }
                },
                error: function(xhr, status, error) {
                    if (!hasError) { hasError = true; reject(new Error('TTS API error: ' + error)); }
                }
            });
        });
        
        setTimeout(() => {
            if (loadedCount < chunks.length && !hasError) { hasError = true; reject(new Error('Audio preload timeout')); }
        }, 30000);
    });
}

// ========== Speak AI Response (ไม่ใช้ CACHE) — รับ emotion ==========
function speakAIResponseDirectly(text, forceLangCode = null, emotion = 'calm') {
    // ✅ บันทึก emotion ปัจจุบัน
    currentEmotion = emotion || 'calm';
    console.log('🎭 Current emotion set to:', currentEmotion);

    updateStatus('Preparing voice...', false);
    $('#messageText').html('<span class="typing-indicator">Thinking...</span>');
    $('#currentMessage').fadeIn();
    
    let langCode = forceLangCode || detectLanguage(text);
    const chunks = splitTextIntoChunks(text);
    
    preloadAIResponseAudio(chunks, langCode).then((audioUrls) => {
        showMessage(text);
        playPreloadedAudio(audioUrls, langCode, text);
    }).catch((error) => {
        console.error('❌ AI audio preload failed:', error);
        showMessage(text);
        fallbackToGoogleTTS(text, langCode);
    });
}

// ========== Preload AI Response (ไม่ผ่าน CACHE) ==========
function preloadAIResponseAudio(chunks, langCode) {
    return new Promise((resolve, reject) => {
        const audioUrls = [];
        let loadedCount = 0;
        let hasError = false;
        
        chunks.forEach((chunk, index) => {
            const requestData = { text: chunk, language: langCode };
            if (companionId) requestData.user_companion_id = companionId;
            if (aiCompanionData && aiCompanionData.ai_id) requestData.ai_id = aiCompanionData.ai_id;
            if (aiCompanionData && aiCompanionData.user_id) requestData.user_id = aiCompanionData.user_id;
            
            $.ajax({
                url: 'app/actions/elevenlabs_tts.php',
                type: 'POST', data: JSON.stringify(requestData),
                contentType: 'application/json', dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.audio_url) {
                        audioUrls[index] = response.audio_url;
                        loadedCount++;
                        updateStatus(`Loading voice... ${Math.round((loadedCount / chunks.length) * 100)}%`, false);
                        if (loadedCount === chunks.length && !hasError) resolve(audioUrls);
                    } else if (!hasError) { hasError = true; reject(new Error('No audio URL')); }
                },
                error: function(xhr, status, error) {
                    if (!hasError) { hasError = true; reject(new Error('TTS API error: ' + error)); }
                }
            });
        });
        
        setTimeout(() => {
            if (loadedCount < chunks.length && !hasError) { hasError = true; reject(new Error('Audio preload timeout')); }
        }, 30000);
    });
}

// ========== Play Preloaded Audio ==========
function playPreloadedAudio(audioUrls, langCode, fullText, onComplete) {
    const langNames = { 'th': 'Thai', 'en': 'English', 'cn': 'Chinese', 'jp': 'Japanese', 'kr': 'Korean' };
    
    isSpeaking = true;
    window.isSpeaking = true;
    updateStatus('Speaking in ' + (langNames[langCode] || 'English') + '...', true);
    
    // ✅ เริ่ม emotion talking animation
    if (useVideoAvatar) playSpeakingAnimation();
    
    playAudioUrlsSequentially(audioUrls, 0, () => {
        isSpeaking = false;
        window.isSpeaking = false;
        updateStatus('Ready to chat', false);
        $('#currentMessage').fadeOut();
        if (mouth) mouth.scale.y = 1;

        // ✅ เสียงเสร็จแล้ว กลับไป emotion idle
        if (useVideoAvatar) stopSpeakingAnimation();
        if (onComplete) onComplete();
    });
}

function playAudioUrlsSequentially(audioUrls, index, onComplete) {
    if (index >= audioUrls.length) { if (onComplete) onComplete(); return; }
    
    const audioUrl = audioUrls[index];
    if (!audioUrl) { playAudioUrlsSequentially(audioUrls, index + 1, onComplete); return; }
    
    const audio = new Audio(audioUrl);
    audio.oncanplaythrough = function() {
        this.play().catch(err => { playAudioUrlsSequentially(audioUrls, index + 1, onComplete); });
    };
    audio.onended = function() {
        setTimeout(() => { playAudioUrlsSequentially(audioUrls, index + 1, onComplete); }, 300);
    };
    audio.onerror = function() { playAudioUrlsSequentially(audioUrls, index + 1, onComplete); };
    audio.load();
}

// ========== ✅ NEW: Get emotion-specific 3D video URL ==========
function getEmotionVideo3D(emotion, state) {
    const emotionData = EMOTION_VIDEOS_3D[emotion];
    if (emotionData && emotionData[state] && emotionData[state].length > 0) {
        const urls = emotionData[state];
        // สุ่มไม่ซ้ำ
        const lastPlayed = state === 'idle' ? lastPlayedIdleVideo : lastPlayedSpeakingVideo;
        const available = urls.length > 1 ? urls.filter(v => v !== lastPlayed) : urls;
        const selected = available[Math.floor(Math.random() * available.length)];
        if (state === 'idle') lastPlayedIdleVideo = selected;
        else lastPlayedSpeakingVideo = selected;
        console.log(`🎭 3D Emotion video [${emotion}][${state}]:`, selected);
        return selected;
    }
    // fallback: general idle/talking
    console.log(`⚠️ No 3D emotion video for [${emotion}][${state}], falling back to general`);
    return state === 'talking' ? getRandomSpeakingVideo() : getRandomIdleVideo();
}

// ========== Random Video Selection ==========
function loadSpeakingVideosIfNeeded() {
    if (SPEAKING_VIDEO_URLS.length === 0 && aiCompanionData) {
        SPEAKING_VIDEO_URLS = aiCompanionData.talking_video_urls_array || [];
    }
}

function getRandomIdleVideo() {
    if (IDLE_VIDEO_URLS.length === 0) return null;
    if (IDLE_VIDEO_URLS.length === 1) return IDLE_VIDEO_URLS[0];
    const available = IDLE_VIDEO_URLS.filter(v => v !== lastPlayedIdleVideo);
    const pool = available.length > 0 ? available : IDLE_VIDEO_URLS;
    const selected = pool[Math.floor(Math.random() * pool.length)];
    lastPlayedIdleVideo = selected;
    return selected;
}

function getRandomSpeakingVideo() {
    loadSpeakingVideosIfNeeded();
    if (SPEAKING_VIDEO_URLS.length === 0) return null;
    if (SPEAKING_VIDEO_URLS.length === 1) return SPEAKING_VIDEO_URLS[0];
    const available = SPEAKING_VIDEO_URLS.filter(v => v !== lastPlayedSpeakingVideo);
    const pool = available.length > 0 ? available : SPEAKING_VIDEO_URLS;
    const selected = pool[Math.floor(Math.random() * pool.length)];
    lastPlayedSpeakingVideo = selected;
    return selected;
}

// ========== Video Avatar Functions ==========
function initVideoAvatar() {
    const container = document.querySelector('.avatar-container');
    
    videoAvatar = document.createElement('video');
    videoAvatar.id = 'videoAvatar';
    videoAvatar.style.cssText = `position:absolute;width:80%;height:80%;object-fit:contain;z-index:5;opacity:1;transition:opacity 0.3s ease;`;
    videoAvatar.muted = false;
    videoAvatar.volume = 0.7;
    videoAvatar.playsInline = true;
    videoAvatar.loop = false;
    videoAvatar.preload = 'auto';
    
    // ✅ แก้ ended event — ตรวจสอบ isSpeaking ด้วย
    videoAvatar.addEventListener('ended', function() {
        if (currentVideoState === 'idle') {
            if (playEmotionIdleOnce) {
                // เล่น emotion idle ครบ 1 รอบแล้ว → กลับ general idle
                playEmotionIdleOnce = false;
                currentEmotion = 'calm';
                const v = getRandomIdleVideo();
                if (v) { this.muted = false; this.volume = 0.7; this.src = v; this.load(); this.play().catch(e => {}); }
            } else {
                // general idle → วนต่อไปเรื่อยๆ
                const v = getRandomIdleVideo();
                if (v) { this.muted = false; this.volume = 0.7; this.src = v; this.load(); this.play().catch(e => {}); }
            }
        } else if (currentVideoState === 'speaking') {
            if (isSpeaking) {
                // ยังพูดอยู่ → วนเล่น emotion talking ต่อ
                const v = getEmotionVideo3D(currentEmotion, 'talking') || getRandomSpeakingVideo();
                if (v) { this.muted = true; this.src = v; this.load(); this.play().catch(e => {}); }
            } else {
                // เสียงเสร็จแล้ว → เล่น emotion idle 1 ครั้ง
                currentVideoState = 'idle';
                playEmotionIdleOnce = true;
                const v = getEmotionVideo3D(currentEmotion, 'idle') || getRandomIdleVideo();
                if (v) { this.muted = false; this.volume = 0.7; this.src = v; this.load(); this.play().catch(e => {}); }
            }
        }
    });
    
    const initialIdleVideo = getEmotionVideo3D(currentEmotion, 'idle') || getRandomIdleVideo();
    videoAvatar.src = initialIdleVideo;
    currentVideoState = 'idle';
    container.appendChild(videoAvatar);
    
    const loadTimeout = setTimeout(() => {
        if (videoAvatar.readyState < 2) {
            useVideoAvatar = false;
            container.removeChild(videoAvatar);
            init3DAvatar();
        }
    }, 5000);
    
    videoAvatar.addEventListener('loadeddata', function() {
        clearTimeout(loadTimeout);
        videoAvatar.play().catch(e => {});
    });
    
    videoAvatar.addEventListener('error', function() {
        clearTimeout(loadTimeout);
        useVideoAvatar = false;
        container.removeChild(videoAvatar);
        init3DAvatar();
    });
    
    videoAvatar.load();
}

// ✅ playSpeakingAnimation — ใช้ emotion talking video
function playSpeakingAnimation() {
    if (!videoAvatar || isTransitioning || currentVideoState === 'speaking') return;
    const videoUrl = getEmotionVideo3D(currentEmotion, 'talking');
    if (videoUrl) switchToVideo(videoUrl, 'speaking');
}

// ✅ playIdleAnimation — ใช้ emotion idle video
function playIdleAnimation() {
    if (!videoAvatar || isTransitioning || currentVideoState === 'idle') return;
    const videoUrl = getEmotionVideo3D(currentEmotion, 'idle') || getRandomIdleVideo();
    if (videoUrl) switchToVideo(videoUrl, 'idle');
}

// ✅ stopSpeakingAnimation — เล่น emotion idle 1 ครั้ง แล้ว ended event จะกลับ general idle เอง
function stopSpeakingAnimation() {
    if (!videoAvatar || isTransitioning) return;
    const emotionIdleUrl = getEmotionVideo3D(currentEmotion, 'idle');
    if (emotionIdleUrl) {
        playEmotionIdleOnce = true; // ✅ บอกให้เล่นแค่ 1 รอบ
        switchToVideo(emotionIdleUrl, 'idle');
    } else {
        // ไม่มี emotion idle → กลับ general idle เลย
        playEmotionIdleOnce = false;
        currentEmotion = 'calm';
        playIdleAnimation();
    }
}

function switchToVideo(videoUrl, newState) {
    if (isTransitioning || !videoUrl) return;
    isTransitioning = true;
    const container = videoAvatar.parentElement;
    
    const newVideo = document.createElement('video');
    newVideo.id = 'videoAvatar';
    newVideo.style.cssText = videoAvatar.style.cssText;
    newVideo.style.opacity = '0';
    newVideo.muted = (newState === 'speaking');
    if (newState === 'idle') newVideo.volume = 0.7;
    newVideo.playsInline = true;
    newVideo.loop = false;
    newVideo.src = videoUrl;

    // ✅ แก้ ended event บน newVideo — ตรวจสอบ isSpeaking ด้วย
    newVideo.addEventListener('ended', function() {
        if (currentVideoState === 'idle') {
            if (playEmotionIdleOnce) {
                // เล่น emotion idle ครบ 1 รอบ → กลับ general idle
                playEmotionIdleOnce = false;
                currentEmotion = 'calm';
                const v = getRandomIdleVideo();
                if (v) { this.muted = false; this.volume = 0.7; this.src = v; this.load(); this.play().catch(e => {}); }
            } else {
                // general idle → วนต่อ
                const v = getRandomIdleVideo();
                if (v) { this.muted = false; this.volume = 0.7; this.src = v; this.load(); this.play().catch(e => {}); }
            }
        } else if (currentVideoState === 'speaking') {
            if (isSpeaking) {
                // ยังพูดอยู่ → วนเล่น emotion talking ต่อ
                const v = getEmotionVideo3D(currentEmotion, 'talking') || getRandomSpeakingVideo();
                if (v) { this.muted = true; this.src = v; this.load(); this.play().catch(e => {}); }
            } else {
                // เสียงเสร็จแล้ว → เล่น emotion idle 1 ครั้ง
                currentVideoState = 'idle';
                playEmotionIdleOnce = true;
                const v = getEmotionVideo3D(currentEmotion, 'idle') || getRandomIdleVideo();
                if (v) { this.muted = false; this.volume = 0.7; this.src = v; this.load(); this.play().catch(e => {}); }
            }
        }
    });
    
    container.appendChild(newVideo);
    
    newVideo.addEventListener('canplay', function playNew() {
        newVideo.removeEventListener('canplay', playNew);
        newVideo.play().then(() => {
            videoAvatar.style.opacity = '0';
            newVideo.style.opacity = '1';
            setTimeout(() => {
                if (videoAvatar && videoAvatar.parentElement === container) {
                    container.removeChild(videoAvatar);
                }
                videoAvatar = newVideo;
                currentVideoState = newState;
                isTransitioning = false;
            }, 300);
        }).catch(e => {
            if (newVideo.parentElement === container) {
                container.removeChild(newVideo);
            }
            isTransitioning = false;
        });
    });
    
    newVideo.load();
}

function init3DAvatar() {
    const canvas = document.getElementById('avatarCanvas');
    scene = new THREE.Scene();
    scene.background = null;
    camera = new THREE.PerspectiveCamera(75, canvas.clientWidth / canvas.clientHeight, 0.1, 1000);
    camera.position.z = 7;
    renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
    renderer.setClearColor(0x000000, 0);
    renderer.setSize(canvas.clientWidth, canvas.clientHeight);
    
    scene.add(new THREE.AmbientLight(0xffffff, 0.5));
    const dLight = new THREE.DirectionalLight(0xffffff, 0.6);
    dLight.position.set(5, 10, 7);
    scene.add(dLight);
    
    createPastelSheepCharacter();
    animate();
    window.addEventListener('resize', onWindowResize);
}

function createPastelSheepCharacter() {
    const character = new THREE.Group();
    const head = new THREE.Mesh(
        new THREE.SphereGeometry(1.4, 32, 32),
        new THREE.MeshPhongMaterial({ color: 0x87CEEB, shininess: 60, emissive: 0x5dade2, emissiveIntensity: 0.15 })
    );
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
        }
        renderer.render(scene, camera);
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
    let url = 'app/actions/get_chat_data.php?action=list_conversations';
    const headers = {};
    
    if (isGuestMode) {
        if (companionId) url += '&user_companion_id=' + companionId;
        else if (aiCodeFromURL) url += '&ai_code=' + aiCodeFromURL;
    } else if (jwt) {
        headers['Authorization'] = 'Bearer ' + jwt;
    }
    
    $.ajax({
        url, type: 'GET', headers, dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                if (response.user_companion_id) {
                    companionId = response.user_companion_id;
                    sessionStorage.setItem('user_companion_id', companionId);
                }
                displayConversations(response.conversations);

                // ✅ Auto-select: ดึงจาก sessionStorage ก่อน ถ้าไม่มีใช้ conversation แรกใน list
                if (response.conversations && response.conversations.length > 0) {
                    const savedId = parseInt(sessionStorage.getItem('last_conversation_id') || '0');
                    const exists  = savedId && response.conversations.find(c => c.conversation_id === savedId);
                    const targetId = exists ? savedId : response.conversations[0].conversation_id;
                    loadConversation(targetId);
                }
            } else if (response.require_login === false) {
                displayConversations([]);
            }
        },
        error: function() { console.error('❌ Error loading conversations'); }
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
        const $item = $(`
            <div class="conversation-item ${isActive}" data-id="${conv.conversation_id}">
                <div class="conversation-title">${escapeHtml(conv.title)}</div>
                <div class="conversation-preview">${escapeHtml(conv.last_message || '')}</div>
                <div class="conversation-time">${formatTimeAgo(conv.updated_at)}</div>
                <button class="delete-conv-btn" onclick="deleteConversation(${conv.conversation_id}, event)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `);
        $item.on('click', function() { loadConversation(conv.conversation_id); });
        $list.append($item);
    });
}

function loadConversation(conversationId) {
    currentConversationId = conversationId;

    // ✅ บันทึก conversation ล่าสุดไว้ใน sessionStorage
    sessionStorage.setItem('last_conversation_id', conversationId);
    
    $('.conversation-item').removeClass('active');
    $(`.conversation-item[data-id="${conversationId}"]`).addClass('active');
    
    let url = 'app/actions/get_chat_data.php?action=get_history&conversation_id=' + conversationId;
    const headers = {};
    
    if (isGuestMode && companionId) url += '&user_companion_id=' + companionId;
    else if (jwt) headers['Authorization'] = 'Bearer ' + jwt;
    
    $.ajax({
        url, type: 'GET', headers, dataType: 'json',
        success: function(response) {
            if (response.status === 'success' && response.messages.length > 0) {
                const lastMessage = response.messages[response.messages.length - 1];
                if (lastMessage.role === 'assistant') showMessage(lastMessage.message);
            }
        }
    });
    
    $('#dropdownMenu').removeClass('show');
    $('#menuToggle').removeClass('active');
}

// ========== Send Message ==========
function sendMessage() {
    const message = $('#messageInput').val().trim();
    if (!message) return;
    
    if (!companionId) {
        const stored = sessionStorage.getItem('user_companion_id');
        if (stored) companionId = parseInt(stored);
    }
    
    const hasCompanion = companionId && companionId > 0;
    const hasAICode = aiCodeFromURL && aiCodeFromURL.trim() !== '';
    
    if (!hasCompanion && !hasAICode) {
        Swal.fire({ icon: 'error', title: 'Cannot Send Message', text: 'Missing companion or AI code. Please reload the page.' });
        return;
    }
    
    if (useVideoAvatar && videoAvatar && videoAvatar.paused) {
        videoAvatar.play().catch(e => {});
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
        if (hasCompanion) requestData.user_companion_id = companionId;
        if (hasAICode) requestData.ai_code = aiCodeFromURL;
    } else if (jwt) {
        headers['Authorization'] = 'Bearer ' + jwt;
        if (hasCompanion) requestData.user_companion_id = companionId;
    }
    
    $.ajax({
        url: 'app/actions/ai_chat.php',
        type: 'POST', headers, data: JSON.stringify(requestData), dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                if (currentConversationId === 0) {
                    currentConversationId = response.conversation_id;
                    // ✅ บันทึก conversation ใหม่ที่เพิ่งสร้าง
                    sessionStorage.setItem('last_conversation_id', currentConversationId);
                    loadConversations();
                }
                if (response.user_companion_id) {
                    companionId = response.user_companion_id;
                    sessionStorage.setItem('user_companion_id', companionId);
                }
                // ✅ ส่ง emotion เข้าไปด้วย
                speakAIResponseDirectly(
                    response.ai_message,
                    response.language_used || requestData.preferred_language,
                    response.ai_emotion || 'calm'
                );
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

// ========== Utility Functions ==========
function detectLanguage(text) {
    if (/[\u0E00-\u0E7F]/.test(text)) return 'th';
    if (/[\u4E00-\u9FFF]/.test(text)) return 'cn';
    if (/[\u3040-\u309F\u30A0-\u30FF]/.test(text)) return 'jp';
    if (/[\uAC00-\uD7AF]/.test(text)) return 'kr';
    return 'en';
}

function splitTextIntoChunks(text, maxLength = 200) {
    if (text.length <= maxLength) return [text];
    const sentences = text.match(/[^.!?。！？]+[.!?。！？]+/g) || [text];
    const chunks = [];
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

function fallbackToGoogleTTS(text, langCode, onComplete) {
    let googleLangCode = langCode;
    if (langCode === 'cn') googleLangCode = 'zh-CN';
    if (langCode === 'jp') googleLangCode = 'ja';
    if (langCode === 'kr') googleLangCode = 'ko';
    
    const ttsUrl = `https://translate.google.com/translate_tts?ie=UTF-8&tl=${googleLangCode}&client=tw-ob&q=${encodeURIComponent(text)}`;
    
    if (currentAudio) { currentAudio.pause(); currentAudio = null; }
    currentAudio = new Audio(ttsUrl);
    currentAudio.oncanplaythrough = function() { this.play().catch(err => { if (onComplete) onComplete(); }); };
    currentAudio.onplay = function() {
        isSpeaking = true;
        window.isSpeaking = true;
        if (useVideoAvatar) playSpeakingAnimation();
    };
    currentAudio.onended = function() {
        isSpeaking = false;
        window.isSpeaking = false;
        updateStatus('Ready to chat', false);
        $('#currentMessage').fadeOut();
        if (useVideoAvatar) stopSpeakingAnimation();
        if (onComplete) onComplete();
    };
    currentAudio.load();
}

function updateStatus(text, speaking) {
    $('#statusText').text(text);
    speaking ? $('#statusDot').addClass('speaking') : $('#statusDot').removeClass('speaking');
}

// ========== ✅ New Chat — ล้าง sessionStorage เพื่อเริ่ม context ใหม่ ==========
function createNewChat() {
    currentConversationId = 0;

    // ✅ ล้าง last_conversation_id เพื่อให้ครั้งหน้า reload ไม่กลับมา conversation เก่า
    sessionStorage.removeItem('last_conversation_id');

    // ✅ Reset emotion กลับ calm
    currentEmotion = 'calm';

    $('.conversation-item').removeClass('active');
    $('#messageInput').val('').focus();
    $('#currentMessage').fadeOut();
    
    if (window.speechSynthesis) window.speechSynthesis.cancel();
    if (currentAudio) { currentAudio.pause(); currentAudio = null; }
    
    isSpeaking = false;
    window.isSpeaking = false;
    updateStatus('Ready to chat', false);

    // ✅ กลับ idle ของ emotion ปัจจุบัน (calm)
    if (useVideoAvatar) stopSpeakingAnimation();
    
    $('#dropdownMenu').removeClass('show');
    $('#menuToggle').removeClass('active');
}

function deleteConversation(conversationId, event) {
    event.stopPropagation();
    
    Swal.fire({
        title: 'Delete Conversation?', text: 'This action cannot be undone', icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#dc3545',
        confirmButtonText: 'Delete', cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            let url = 'app/actions/get_chat_data.php?action=delete_conversation&conversation_id=' + conversationId;
            const headers = {};
            
            if (isGuestMode && companionId) url += '&user_companion_id=' + companionId;
            else if (jwt) headers['Authorization'] = 'Bearer ' + jwt;
            
            $.ajax({
                url, type: 'GET', headers, dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire('Deleted!', 'Conversation has been deleted', 'success');
                        if (conversationId === currentConversationId) {
                            // ✅ ล้าง sessionStorage ถ้าลบ conversation ที่กำลังดูอยู่
                            sessionStorage.removeItem('last_conversation_id');
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
    if (event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); sendMessage(); }
}

function formatTimeAgo(timestamp) {
    const date = new Date(timestamp);
    const now  = new Date();
    const diff = Math.floor((now - date) / 1000);
    if (diff < 60)     return 'Just now';
    if (diff < 3600)   return Math.floor(diff / 60)    + 'm ago';
    if (diff < 86400)  return Math.floor(diff / 3600)  + 'h ago';
    if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
    return date.toLocaleDateString('th-TH', { month: 'short', day: 'numeric' });
}

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, m => map[m]);
}

function goTo2DMode() {
    const urlParams = new URLSearchParams(window.location.search);
    let url = '?ai_chat&lang=' + (urlParams.get('lang') || 'th');
    if (aiCodeFromURL) url += '&ai_code=' + aiCodeFromURL;
    window.location.href = url;
}

function goToPreferences() {
    const urlParams = new URLSearchParams(window.location.search);
    let url = '?ai_edit_prompts&lang=' + (urlParams.get('lang') || 'th');
    if (aiCodeFromURL) url += '&ai_code=' + aiCodeFromURL;
    window.location.href = url;
}

console.log('✅ AI Chat 3D loaded — with 3D Emotion Videos (idle/talking per emotion)');