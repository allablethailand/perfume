/**
 * AI Chat 3D
 * ✅ โหลดเสียงทุก chunk พร้อมกันรอบเดียว (parallel)
 * ✅ Subtitle แสดงทีละประโยคสั้นตาม chunk เสียงที่กำลังเล่น (typewriter)
 * ✅ FIX: thinking video เล่นเฉพาะตอน user ส่งข้อความ → รอ AI → รอโหลดเสียง
 * ✅ FIX: stopThinkingAnimation() หยุด thinking ได้จริง
 * ✅ ค้าง subtitle สุดท้ายจนเสียงจบแล้ว fade out
 * ✅ 3D Emotion Videos
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
let EMOTION_VIDEOS_3D = {};
let currentEmotion = 'calm';

// ✅ video states: 'idle' | 'thinking' | 'speaking'
let currentVideoState = 'idle';
let isTransitioning = false;
// ✅ flag ควบคุม thinking loop
let isThinking = false;

let lastPlayedIdleVideo = null;
let lastPlayedSpeakingVideo = null;
let playEmotionIdleOnce = false;

window.isSpeaking = false;
window.waveIntensity = 0;

let typewriterTimer = null;
let subtitleSequenceTimers = [];

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
    console.log('🚀 Initializing AI Chat 3D...');

    if (!aiCodeFromURL && !jwt) {
        Swal.fire({ title: 'Access Denied', text: 'Please provide AI code or login', icon: 'error' })
            .then(() => { window.location.href = '?'; });
        return;
    }

    const storedCompanionId = sessionStorage.getItem('user_companion_id');
    if (storedCompanionId) companionId = parseInt(storedCompanionId);

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

// ========== ✅ Thinking Video — เล่นเฉพาะตอน user ส่งข้อความ ==========
function startThinkingAnimation() {
    if (!useVideoAvatar || !videoAvatar) return;
    isThinking = true;

    // ดึงวิดีโอ thinking จาก EMOTION_VIDEOS_3D['thinking'] ถ้ามี
    const thinkingUrl = getThinkingVideo();
    if (!thinkingUrl) {
        console.log('⚠️ No thinking video found, staying idle');
        return;
    }
    console.log('🤔 Starting thinking animation:', thinkingUrl);
    switchToVideo(thinkingUrl, 'thinking');
}

function stopThinkingAnimation() {
    if (!isThinking) return;
    isThinking = false;
    console.log('✅ Stopping thinking animation');

    if (!useVideoAvatar || !videoAvatar) return;

    // ถ้าอยู่ใน thinking state → switch กลับ idle ทันที
    if (currentVideoState === 'thinking') {
        const idleUrl = getEmotionVideo3D(currentEmotion, 'idle') || getRandomIdleVideo();
        if (idleUrl) switchToVideo(idleUrl, 'idle');
    }
}

function getThinkingVideo() {
    // ดึงจาก EMOTION_VIDEOS_3D['thinking']['idle'] ถ้ามี
    const thinkingData = EMOTION_VIDEOS_3D['thinking'];
    if (thinkingData) {
        // รองรับทั้ง { idle: [...] } และ array ตรงๆ
        const urls = thinkingData['idle'] || thinkingData['talking'] || (Array.isArray(thinkingData) ? thinkingData : null);
        if (urls && urls.length > 0) {
            return urls[Math.floor(Math.random() * urls.length)];
        }
    }
    return null; // ไม่มี thinking video
}

// ========== Text Splitting ==========
function splitIntoAudioChunks(text, maxLength = 200) {
    let cleaned = text
        .replace(/\*\*([^*]+)\*\*/g, '$1')
        .replace(/\*/g, '')
        .trim();

    if (cleaned.length <= maxLength) return [cleaned];

    const sentences = cleaned.match(/[^.!?。！？\n]+[.!?。！？\n]*/g) || [cleaned];
    const chunks = [];
    let current = '';

    for (let s of sentences) {
        if ((current + s).length <= maxLength) {
            current += s;
        } else {
            if (current) chunks.push(current.trim());
            current = s;
        }
    }
    if (current) chunks.push(current.trim());
    return chunks.length > 0 ? chunks : [cleaned];
}

function splitIntoSubtitleSentences(text) {
    if (!text) return [''];
    let cleaned = text
        .replace(/\*\*([^*]+)\*\*/g, '$1')
        .replace(/\*/g, '')
        .trim();

    const raw = cleaned.match(/[^.!?。！？\n]+[.!?。！？\n]*/g) || [cleaned];
    const MAX = 80;
    const chunks = [];

    for (let s of raw) {
        s = s.trim();
        if (!s) continue;
        if (s.length <= MAX) {
            chunks.push(s);
        } else {
            const parts = s.match(/.{1,80}(?:[,\s]|$)/g) || [s];
            for (let p of parts) {
                p = p.trim();
                if (p) chunks.push(p);
            }
        }
    }
    return chunks.length > 0 ? chunks : [text];
}

// ========== Fetch Weather Data ==========
function fetchWeatherData() {
    return new Promise((resolve) => {
        if ('geolocation' in navigator) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    fetchWeatherByCoordinates(position.coords.latitude, position.coords.longitude).then(resolve);
                },
                (error) => {
                    weatherData = null; weatherReportPlayed = true; resolve();
                }
            );
        } else {
            weatherData = null; weatherReportPlayed = true; resolve();
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
                if (response.status === 'success') { weatherData = response.data; weatherReportPlayed = false; }
                else { weatherData = null; weatherReportPlayed = true; }
                resolve();
            },
            error: function() { weatherData = null; weatherReportPlayed = true; resolve(); }
        });
    });
}

// ========== Play Welcome + Weather ==========
function playWelcomeWithWeather() {
    if (isWelcomeMessagePlayed) return;
    isWelcomeMessagePlayed = true;
    const welcomeText = WELCOME_MESSAGES[userPreferredLanguage] || WELCOME_MESSAGES.th;
    if (useVideoAvatar && videoAvatar && videoAvatar.paused) {
        videoAvatar.play().catch(e => {});
    }
    // ✅ welcome/weather ไม่เล่น thinking — ใช้ speakTextWithCache ตรงๆ
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
                console.log('📦 Full API Response:', JSON.stringify(response));

                if (response.status === 'success') {
                    aiCompanionData = response.ai_data || response.companion;
                    if (!aiCompanionData) { useVideoAvatar = false; resolve(); return; }

                    IDLE_VIDEO_URLS     = aiCompanionData.idle_video_urls_array    || [];
                    SPEAKING_VIDEO_URLS = aiCompanionData.talking_video_urls_array || [];
                    EMOTION_VIDEOS_3D   = aiCompanionData.emotion_videos_3d_array  || {};
                    console.log('✅ 3D Emotion videos loaded:', Object.keys(EMOTION_VIDEOS_3D));
                    console.log('🤔 Thinking videos:', EMOTION_VIDEOS_3D['thinking'] || 'none');

                    if (response.preferred_language) userPreferredLanguage = response.preferred_language;
                    else if (aiCompanionData.preferred_language) userPreferredLanguage = aiCompanionData.preferred_language;
                    else userPreferredLanguage = langFromURL || 'th';

                    if (response.companion_id) companionId = response.companion_id;
                    else if (aiCompanionData.user_companion_id) companionId = aiCompanionData.user_companion_id;

                    if (response.user_id) aiCompanionData.user_id = response.user_id;
                    if (companionId) sessionStorage.setItem('user_companion_id', companionId);
                    if (aiCompanionData.ai_code) sessionStorage.setItem('ai_code', aiCompanionData.ai_code);

                    autoLoginAsCompanionUser().then(resolve).catch(resolve);
                } else {
                    useVideoAvatar = false; resolve();
                }
            },
            error: function() { useVideoAvatar = false; resolve(); }
        });
    });
}

function autoLoginAsCompanionUser() {
    return new Promise((resolve) => {
        const existingJwt = sessionStorage.getItem('jwt');
        if (existingJwt) { resolve(); return; }
        if (!companionId && !aiCodeFromURL) { resolve(); return; }

        const params = new URLSearchParams();
        if (companionId) params.set('user_companion_id', companionId);
        if (aiCodeFromURL) params.set('ai_code', aiCodeFromURL);

        $.ajax({
            url: 'app/actions/get_companion_jwt.php?' + params.toString(),
            type: 'GET', dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.jwt) {
                    sessionStorage.setItem('jwt', response.jwt);
                    jwt = response.jwt;
                    isGuestMode = false;
                }
                resolve();
            },
            error: function() { resolve(); }
        });
    });
}

// ========== ✅ Speak with CACHE — ไม่เล่น thinking (ใช้สำหรับ welcome/weather) ==========
function speakTextWithCache(text, forceLangCode = null, cacheType = 'welcome') {
    return new Promise((resolve) => {
        updateStatus('Preparing voice...', false);
        let langCode = forceLangCode || detectLanguage(text);
        const audioChunks = splitIntoAudioChunks(text);

        preloadAllAudio(audioChunks, langCode, 'cache', cacheType).then((audioUrls) => {
            playAudioWithSubtitles(audioUrls, audioChunks, langCode, resolve);
        }).catch((error) => {
            console.error('❌ Audio preload failed:', error);
            showMessage(text);
            fallbackToGoogleTTS(text, langCode, resolve);
        });
    });
}

// ========== ✅ Speak AI Response — เล่น thinking ขณะโหลดเสียง ==========
function speakAIResponseDirectly(text, forceLangCode = null, emotion = 'calm') {
    currentEmotion = emotion || 'calm';
    console.log('🎭 emotion:', currentEmotion);

    updateStatus('Preparing voice...', false);
    // ✅ thinking ยังวนอยู่จาก sendMessage() — ไม่ต้องเรียกซ้ำ

    let langCode = forceLangCode || detectLanguage(text);
    const audioChunks = splitIntoAudioChunks(text);

    preloadAllAudio(audioChunks, langCode, 'ai', null).then((audioUrls) => {
        // ✅ โหลดเสียงเสร็จ — หยุด thinking แล้วเริ่มพูด
        stopThinkingAnimation();
        playAudioWithSubtitles(audioUrls, audioChunks, langCode, null);
    }).catch((error) => {
        console.error('❌ AI audio preload failed:', error);
        stopThinkingAnimation();
        showMessage(text);
        fallbackToGoogleTTS(text, langCode);
    });
}

// ========== Preload ALL audio URLs พร้อมกัน (parallel) ==========
function preloadAllAudio(audioChunks, langCode, mode, cacheType) {
    return new Promise((resolve, reject) => {
        const audioUrls = new Array(audioChunks.length).fill(null);
        let loadedCount = 0;
        let hasError = false;

        const timeoutMs = Math.min(Math.max(audioChunks.length * 15000, 30000), 120000);

        const timer = setTimeout(() => {
            if (loadedCount < audioChunks.length && !hasError) {
                hasError = true;
                reject(new Error(`Timeout after ${timeoutMs/1000}s (${loadedCount}/${audioChunks.length})`));
            }
        }, timeoutMs);

        audioChunks.forEach((chunk, index) => {
            let requestData, url;

            if (mode === 'cache') {
                url = 'app/actions/get_or_create_tts_cache.php';
                requestData = { text: chunk, language: langCode, cache_type: cacheType };
                if (companionId) requestData.user_companion_id = companionId;
                if (aiCompanionData && aiCompanionData.ai_id) requestData.ai_id = aiCompanionData.ai_id;
                if (aiCompanionData && aiCompanionData.voice_id) requestData.voice_id = aiCompanionData.voice_id;
            } else {
                url = 'app/actions/elevenlabs_tts.php';
                requestData = { text: chunk, language: langCode };
                if (companionId) requestData.user_companion_id = companionId;
                if (aiCompanionData && aiCompanionData.ai_id) requestData.ai_id = aiCompanionData.ai_id;
                if (aiCompanionData && aiCompanionData.user_id) requestData.user_id = aiCompanionData.user_id;
            }

            $.ajax({
                url, type: 'POST',
                data: JSON.stringify(requestData),
                contentType: 'application/json',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.audio_url) {
                        audioUrls[index] = response.audio_url;
                        loadedCount++;
                        updateStatus(`Loading voice... ${Math.round((loadedCount / audioChunks.length) * 100)}%`, false);
                        if (loadedCount === audioChunks.length) {
                            clearTimeout(timer);
                            resolve(audioUrls);
                        }
                    } else if (!hasError) {
                        hasError = true; clearTimeout(timer);
                        reject(new Error('No audio URL for: ' + chunk));
                    }
                },
                error: function(xhr, status, error) {
                    if (!hasError) {
                        hasError = true; clearTimeout(timer);
                        reject(new Error('TTS error: ' + error));
                    }
                }
            });
        });
    });
}

// ========== เล่น audio + subtitle ทีละประโยค ==========
function playAudioWithSubtitles(audioUrls, audioChunks, langCode, onComplete) {
    const langNames = { 'th': 'Thai', 'en': 'English', 'cn': 'Chinese', 'jp': 'Japanese', 'kr': 'Korean' };
    const subtitleMap = audioChunks.map(chunk => splitIntoSubtitleSentences(chunk));

    isSpeaking = true;
    window.isSpeaking = true;
    updateStatus('Speaking in ' + (langNames[langCode] || 'English') + '...', true);
    if (useVideoAvatar) playSpeakingAnimation();

    playAudioSequence(audioUrls, subtitleMap, 0, () => {
        isSpeaking = false;
        window.isSpeaking = false;
        updateStatus('Ready to chat', false);
        if (mouth) mouth.scale.y = 1;

        setTimeout(() => { $('#currentMessage').fadeOut(600); }, 1200);

        if (useVideoAvatar) stopSpeakingAnimation();
        if (onComplete) onComplete();
    });
}

function playAudioSequence(audioUrls, subtitleMap, index, onComplete) {
    if (index >= audioUrls.length) {
        if (onComplete) onComplete();
        return;
    }

    const audioUrl = audioUrls[index];
    if (!audioUrl) {
        playAudioSequence(audioUrls, subtitleMap, index + 1, onComplete);
        return;
    }

    const sentences    = subtitleMap[index] || [''];
    const isFirstChunk = (index === 0);
    const isLastChunk  = (index >= audioUrls.length - 1);

    const audio = new Audio(audioUrl);

    audio.oncanplaythrough = function() {
        this.play().catch(() => {
            playAudioSequence(audioUrls, subtitleMap, index + 1, onComplete);
        });
    };

    audio.onplay = function() {
        const dur = this.duration || 5;
        playSubtitleSentences(sentences, dur, isFirstChunk);
    };

    audio.onended = function() {
        setTimeout(() => {
            playAudioSequence(audioUrls, subtitleMap, index + 1, onComplete);
        }, isLastChunk ? 0 : 200);
    };

    audio.onerror = function() {
        playAudioSequence(audioUrls, subtitleMap, index + 1, onComplete);
    };

    audio.load();
}

// ========== Subtitle Typewriter ==========
function clearSubtitleTimers() {
    subtitleSequenceTimers.forEach(t => clearTimeout(t));
    subtitleSequenceTimers = [];
    if (typewriterTimer) { clearInterval(typewriterTimer); typewriterTimer = null; }
}

function playSubtitleSentences(sentences, audioDuration, isFirstChunk) {
    clearSubtitleTimers();
    if (!sentences || sentences.length === 0) return;

    const perMs = (audioDuration * 1000) / sentences.length;

    sentences.forEach((sentence, i) => {
        const t = setTimeout(() => {
            showSubtitleTypewriter(sentence, isFirstChunk && i === 0);
        }, i * perMs);
        subtitleSequenceTimers.push(t);
    });
}

function showSubtitleTypewriter(text, isFirst) {
    if (typewriterTimer) { clearInterval(typewriterTimer); typewriterTimer = null; }

    const $msg  = $('#currentMessage');
    const $text = $('#messageText');

    function startTyping() {
        $text.text('');
        $msg.stop(true).fadeIn(200);
        let i = 0;
        const isAsian = /[\u0E00-\u0E7F\u4E00-\u9FFF\u3040-\u30FF\uAC00-\uD7AF]/.test(text);
        const speed = isAsian ? 35 : 25;

        typewriterTimer = setInterval(() => {
            if (i < text.length) {
                $text.text(text.substring(0, i + 1));
                i++;
            } else {
                clearInterval(typewriterTimer);
                typewriterTimer = null;
            }
        }, speed);
    }

    if (isFirst) {
        startTyping();
    } else {
        $msg.stop(true).fadeOut(120, function() { startTyping(); });
    }
}

function showMessage(text) {
    clearSubtitleTimers();
    const firstSentence = splitIntoSubtitleSentences(text)[0] || text;
    $('#messageText').text(firstSentence);
    $('#currentMessage').stop(true).fadeIn(300);
}

// ========== Get emotion video ==========
function getEmotionVideo3D(emotion, state) {
    const emotionData = EMOTION_VIDEOS_3D[emotion];
    if (emotionData && emotionData[state] && emotionData[state].length > 0) {
        const urls = emotionData[state];
        const lastPlayed = state === 'idle' ? lastPlayedIdleVideo : lastPlayedSpeakingVideo;
        const available = urls.length > 1 ? urls.filter(v => v !== lastPlayed) : urls;
        const selected = available[Math.floor(Math.random() * available.length)];
        if (state === 'idle') lastPlayedIdleVideo = selected;
        else lastPlayedSpeakingVideo = selected;
        return selected;
    }
    if (state === 'talking') return getRandomSpeakingVideo();
    return getRandomIdleVideo();
}

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

// ========== Video Avatar Init ==========
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

    videoAvatar.addEventListener('ended', function() {
        if (currentVideoState === 'thinking') {
            // ✅ ตรวจ flag — ถ้ายัง thinking อยู่ให้วนซ้ำ ถ้าไม่ให้กลับ idle
            if (isThinking) {
                const v = getThinkingVideo() || getRandomIdleVideo();
                if (v) { this.src = v; this.load(); this.play().catch(e => {}); }
            } else {
                currentVideoState = 'idle';
                const v = getRandomIdleVideo();
                if (v) { this.muted = false; this.volume = 0.7; this.src = v; this.load(); this.play().catch(e => {}); }
            }
            return;
        }

        if (currentVideoState === 'idle') {
            if (playEmotionIdleOnce) {
                playEmotionIdleOnce = false; currentEmotion = 'calm';
            }
            const v = getRandomIdleVideo();
            if (v) { this.muted = false; this.volume = 0.7; this.src = v; this.load(); this.play().catch(e => {}); }

        } else if (currentVideoState === 'speaking') {
            if (isSpeaking) {
                const v = getEmotionVideo3D(currentEmotion, 'talking') || getRandomSpeakingVideo();
                if (v) { this.muted = true; this.src = v; this.load(); this.play().catch(e => {}); }
            } else {
                currentVideoState = 'idle'; playEmotionIdleOnce = true;
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
        if (videoAvatar.readyState < 2) { useVideoAvatar = false; container.removeChild(videoAvatar); init3DAvatar(); }
    }, 5000);

    videoAvatar.addEventListener('loadeddata', function() { clearTimeout(loadTimeout); videoAvatar.play().catch(e => {}); });
    videoAvatar.addEventListener('error', function() {
        clearTimeout(loadTimeout); useVideoAvatar = false; container.removeChild(videoAvatar); init3DAvatar();
    });
    videoAvatar.load();
}

function playSpeakingAnimation() {
    if (!videoAvatar || isTransitioning || currentVideoState === 'speaking') return;
    const videoUrl = getEmotionVideo3D(currentEmotion, 'talking');
    if (videoUrl) switchToVideo(videoUrl, 'speaking');
}

function playIdleAnimation() {
    if (!videoAvatar || isTransitioning || currentVideoState === 'idle') return;
    const videoUrl = getEmotionVideo3D(currentEmotion, 'idle') || getRandomIdleVideo();
    if (videoUrl) switchToVideo(videoUrl, 'idle');
}

function stopSpeakingAnimation() {
    if (!videoAvatar || isTransitioning) return;
    const emotionIdleUrl = getEmotionVideo3D(currentEmotion, 'idle');
    if (emotionIdleUrl) {
        playEmotionIdleOnce = true;
        switchToVideo(emotionIdleUrl, 'idle');
    } else {
        playEmotionIdleOnce = false; currentEmotion = 'calm';
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
    if (newState !== 'speaking') newVideo.volume = 0.7;
    newVideo.playsInline = true;
    newVideo.loop = false;
    newVideo.src = videoUrl;

    newVideo.addEventListener('ended', function() {
        if (currentVideoState === 'thinking') {
            // ✅ ตรวจ flag
            if (isThinking) {
                const v = getThinkingVideo() || getRandomIdleVideo();
                if (v) { this.src = v; this.load(); this.play().catch(e => {}); }
            } else {
                currentVideoState = 'idle';
                const v = getRandomIdleVideo();
                if (v) { this.muted = false; this.volume = 0.7; this.src = v; this.load(); this.play().catch(e => {}); }
            }
            return;
        }

        if (currentVideoState === 'idle') {
            if (playEmotionIdleOnce) {
                playEmotionIdleOnce = false; currentEmotion = 'calm';
            }
            const v = getRandomIdleVideo();
            if (v) { this.muted = false; this.volume = 0.7; this.src = v; this.load(); this.play().catch(e => {}); }

        } else if (currentVideoState === 'speaking') {
            if (isSpeaking) {
                const v = getEmotionVideo3D(currentEmotion, 'talking') || getRandomSpeakingVideo();
                if (v) { this.muted = true; this.src = v; this.load(); this.play().catch(e => {}); }
            } else {
                currentVideoState = 'idle'; playEmotionIdleOnce = true;
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
                if (videoAvatar && videoAvatar.parentElement === container) container.removeChild(videoAvatar);
                videoAvatar = newVideo;
                currentVideoState = newState;
                isTransitioning = false;
            }, 300);
        }).catch(e => {
            if (newVideo.parentElement === container) container.removeChild(newVideo);
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
                if (response.user_companion_id) { companionId = response.user_companion_id; sessionStorage.setItem('user_companion_id', companionId); }
                displayConversations(response.conversations);
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
        Swal.fire({ icon: 'error', title: 'Cannot Send Message', text: 'Missing companion or AI code.' });
        return;
    }

    if (useVideoAvatar && videoAvatar && videoAvatar.paused) videoAvatar.play().catch(e => {});

    $('#messageInput').prop('disabled', true);
    $('#sendBtn').prop('disabled', true);
    $('#messageInput').val('').css('height', 'auto');
    updateStatus('Thinking...', false);

    // ✅ เริ่ม thinking ทันทีที่กด send
    startThinkingAnimation();

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
                    sessionStorage.setItem('last_conversation_id', currentConversationId);
                    loadConversations();
                }
                if (response.user_companion_id) { companionId = response.user_companion_id; sessionStorage.setItem('user_companion_id', companionId); }
                // ✅ thinking ยังวนอยู่ — speakAIResponseDirectly จะหยุดเองเมื่อโหลดเสียงเสร็จ
                speakAIResponseDirectly(
                    response.ai_message,
                    response.language_used || requestData.preferred_language,
                    response.ai_emotion || 'calm'
                );
            } else {
                stopThinkingAnimation(); // ✅ หยุด thinking ถ้า error
                Swal.fire('Error', response.message, 'error');
                updateStatus('Ready to chat', false);
            }
            $('#messageInput').prop('disabled', false).focus();
            $('#sendBtn').prop('disabled', false);
        },
        error: function() {
            stopThinkingAnimation(); // ✅ หยุด thinking ถ้า error
            Swal.fire('Error', 'Failed to send message', 'error');
            updateStatus('Ready to chat', false);
            $('#messageInput').prop('disabled', false).focus();
            $('#sendBtn').prop('disabled', false);
        }
    });
}

// ========== Utility Functions ==========
function detectLanguage(text) {
    if (/[\u0E00-\u0E7F]/.test(text)) return 'th';
    if (/[\u4E00-\u9FFF]/.test(text)) return 'cn';
    if (/[\u3040-\u309F\u30A0-\u30FF]/.test(text)) return 'jp';
    if (/[\uAC00-\uD7AF]/.test(text)) return 'kr';
    return 'en';
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
        isSpeaking = true; window.isSpeaking = true;
        showSubtitleTypewriter(text.substring(0, 80), true);
        if (useVideoAvatar) playSpeakingAnimation();
    };
    currentAudio.onended = function() {
        isSpeaking = false; window.isSpeaking = false;
        updateStatus('Ready to chat', false);
        setTimeout(() => { $('#currentMessage').fadeOut(600); }, 1200);
        if (useVideoAvatar) stopSpeakingAnimation();
        if (onComplete) onComplete();
    };
    currentAudio.load();
}

function updateStatus(text, speaking) {
    $('#statusText').text(text);
    speaking ? $('#statusDot').addClass('speaking') : $('#statusDot').removeClass('speaking');
}

function createNewChat() {
    currentConversationId = 0;
    sessionStorage.removeItem('last_conversation_id');
    currentEmotion = 'calm';
    isThinking = false; // ✅ reset flag
    clearSubtitleTimers();
    $('.conversation-item').removeClass('active');
    $('#messageInput').val('').focus();
    $('#currentMessage').stop(true).fadeOut(400);
    if (window.speechSynthesis) window.speechSynthesis.cancel();
    if (currentAudio) { currentAudio.pause(); currentAudio = null; }
    isSpeaking = false; window.isSpeaking = false;
    updateStatus('Ready to chat', false);
    if (useVideoAvatar && currentVideoState !== 'idle') {
        const v = getRandomIdleVideo();
        if (v) switchToVideo(v, 'idle');
    }
    $('#dropdownMenu').removeClass('show');
    $('#menuToggle').removeClass('active');
}

function deleteConversation(conversationId, event) {
    event.stopPropagation();
    Swal.fire({
        title: 'Delete Conversation?', text: 'This action cannot be undone', icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Delete', cancelButtonText: 'Cancel'
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
                        if (conversationId === currentConversationId) { sessionStorage.removeItem('last_conversation_id'); createNewChat(); }
                        loadConversations();
                    } else { Swal.fire('Error', response.message, 'error'); }
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

console.log('✅ AI Chat 3D — Thinking flag-based + Parallel preload + Typewriter subtitle');