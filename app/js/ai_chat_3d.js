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

// video states: 'idle' | 'thinking' | 'speaking'
let currentVideoState = 'idle';
let isTransitioning = false;
let isThinking = false;

// ✅ pending queue สำหรับ speaking animation ที่ถูก block โดย isTransitioning
let pendingSpeakingVideo = null;

let lastPlayedIdleVideo = null;
let lastPlayedSpeakingVideo = null;
let playEmotionIdleOnce = false;

window.isSpeaking = false;
window.waveIntensity = 0;

let typewriterTimer = null;
let subtitleSequenceTimers = [];

// ✅ startup_actions — โหลดจาก admin config
let startupActions = {
    weather:           false,
    news_world:        false,
    news_country:      false,
    news_country_code: 'th',
};

// ✅ Startup Queue — โหลดข้อมูลรอไว้ก่อน พูดเมื่อว่าง
let startupQueue = [];
let startupQueueRunning = false;
let startupDataReady = false;

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
        runStartupSequence();
    });

    $('#messageInput').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });

    // ✅ พอ user คลิกออกจาก input ลอง drain queue
    $('#messageInput').on('blur', function() {
        if (startupDataReady && startupQueue.length > 0 && !startupQueueRunning) {
            setTimeout(tryDrainStartupQueue, 1000);
        }
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

// ========== Startup Sequence ==========
async function runStartupSequence() {
    if (isWelcomeMessagePlayed) return;
    isWelcomeMessagePlayed = true;

    if (useVideoAvatar && videoAvatar && videoAvatar.paused) {
        videoAvatar.play().catch(e => {});
    }

    // 1. Welcome — พูดทันที
    const welcomeText = WELCOME_MESSAGES[userPreferredLanguage] || WELCOME_MESSAGES.th;
    await speakTextWithCache(welcomeText, userPreferredLanguage, 'welcome');

    await delay(400);

    // 2. โหลด weather/news ไปรอใน queue ก่อน (background) แล้วค่อยพูดเมื่อว่าง
    buildStartupQueue().then(() => {
        startupDataReady = true;
        tryDrainStartupQueue();
    });
}

// ─── สร้าง queue โดยดึงข้อมูลล่วงหน้า ──────────────────────────
async function buildStartupQueue() {
    startupQueue = [];

    if (startupActions.weather) {
        const text = await fetchWeatherText();
        if (text) startupQueue.push({ text, lang: userPreferredLanguage, type: 'weather' });
        await delay(100);
    }

    if (startupActions.news_world) {
        const text = await fetchNewsText('world', null, userPreferredLanguage);
        if (text) startupQueue.push({ text, lang: userPreferredLanguage, type: 'news_world' });
        await delay(100);
    }

    if (startupActions.news_country) {
        const cc = startupActions.news_country_code || 'th';
        const text = await fetchNewsText('country', cc, userPreferredLanguage);
        if (text) startupQueue.push({ text, lang: userPreferredLanguage, type: 'news_country' });
    }

    console.log(`✅ Startup queue built: ${startupQueue.length} items`);
}

// ─── Drain queue — พูดทีละชิ้นเมื่อว่าง ─────────────────────────
async function tryDrainStartupQueue() {
    if (startupQueueRunning || startupQueue.length === 0) return;
    startupQueueRunning = true;

    while (startupQueue.length > 0) {
        await waitForIdleWindow();
        const item = startupQueue.shift();
        if (!item) break;
        console.log(`🔊 Startup queue speaking: ${item.type}`);
        await speakTextWithCache(item.text, item.lang, item.type);
        await delay(600);
    }

    startupQueueRunning = false;
    console.log('✅ Startup queue drained');
}

// ─── รอช่องว่าง: user ไม่พิม + ไม่ focus + AI ไม่พูด/คิด ────────
function waitForIdleWindow() {
    return new Promise(resolve => {
        const check = () => {
            const typing  = $('#messageInput').val().trim().length > 0;
            const focused = document.activeElement === document.getElementById('messageInput');
            if (!typing && !focused && !isSpeaking && !isThinking) {
                resolve();
            } else {
                setTimeout(check, 800);
            }
        };
        check();
    });
}

function delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// ─── Fetch Weather (คืน text เท่านั้น ไม่พูดเอง) ─────────────────
function fetchWeatherText() {
    return new Promise((resolve) => {
        if (!('geolocation' in navigator)) { resolve(null); return; }

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const { latitude, longitude } = pos.coords;
                const headers = {};
                if (jwt) headers['Authorization'] = 'Bearer ' + jwt;
                $.ajax({
                    url: `app/actions/get_weather.php?lang=${userPreferredLanguage}&lat=${latitude}&lon=${longitude}`,
                    type: 'GET', headers, dataType: 'json',
                    success: (res) => resolve(res.status === 'success' ? res.data?.message : null),
                    error:   ()    => resolve(null),
                });
            },
            () => resolve(null)
        );
    });
}

// ─── Fetch News (คืน text เท่านั้น ไม่พูดเอง) ────────────────────
function fetchNewsText(type, countryCode, lang) {
    return new Promise((resolve) => {
        let url = `app/actions/get_news.php?type=${type}&lang=${lang}`;
        if (type === 'country' && countryCode) url += `&country=${countryCode}`;
        const headers = {};
        if (jwt) headers['Authorization'] = 'Bearer ' + jwt;
        $.ajax({
            url, type: 'GET', headers, dataType: 'json',
            success: (res) => resolve(res.status === 'success' ? res.message : null),
            error:   ()    => resolve(null),
        });
    });
}

// ========== Thinking Video ==========
function startThinkingAnimation() {
    if (!useVideoAvatar || !videoAvatar) return;
    isThinking = true;
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
    if (currentVideoState === 'thinking') {
        const idleUrl = getEmotionVideo3D(currentEmotion, 'idle') || getRandomIdleVideo();
        if (idleUrl) switchToVideo(idleUrl, 'idle');
    }
}

function getThinkingVideo() {
    const thinkingData = EMOTION_VIDEOS_3D['thinking'];
    if (thinkingData) {
        const urls = thinkingData['idle'] || thinkingData['talking'] || (Array.isArray(thinkingData) ? thinkingData : null);
        if (urls && urls.length > 0) {
            return urls[Math.floor(Math.random() * urls.length)];
        }
    }
    return null;
}

// ========== Text Splitting ==========
function splitIntoAudioChunks(text, maxLength = 200) {
    let cleaned = text.replace(/\*\*([^*]+)\*\*/g, '$1').replace(/\*/g, '').trim();
    if (cleaned.length <= maxLength) return [cleaned];
    const sentences = cleaned.match(/[^.!?。！？\n]+[.!?。！？\n]*/g) || [cleaned];
    const chunks = []; let current = '';
    for (let s of sentences) {
        if ((current + s).length <= maxLength) current += s;
        else { if (current) chunks.push(current.trim()); current = s; }
    }
    if (current) chunks.push(current.trim());
    return chunks.length > 0 ? chunks : [cleaned];
}

function splitIntoSubtitleSentences(text) {
    if (!text) return [''];
    let cleaned = text.replace(/\*\*([^*]+)\*\*/g, '$1').replace(/\*/g, '').trim();
    const raw = cleaned.match(/[^.!?。！？\n]+[.!?。！？\n]*/g) || [cleaned];
    const MAX = 80; const chunks = [];
    for (let s of raw) {
        s = s.trim(); if (!s) continue;
        if (s.length <= MAX) { chunks.push(s); }
        else { const parts = s.match(/.{1,80}(?:[,\s]|$)/g) || [s]; for (let p of parts) { p = p.trim(); if (p) chunks.push(p); } }
    }
    return chunks.length > 0 ? chunks : [text];
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

                    if (aiCompanionData.startup_actions) {
                        const sa = (typeof aiCompanionData.startup_actions === 'string')
                            ? JSON.parse(aiCompanionData.startup_actions)
                            : aiCompanionData.startup_actions;
                        startupActions = Object.assign(startupActions, sa);
                        console.log('✅ Startup actions loaded:', startupActions);
                    } else {
                        console.log('ℹ️ No startup_actions — welcome only');
                    }

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
                    jwt = response.jwt; isGuestMode = false;
                }
                resolve();
            },
            error: function() { resolve(); }
        });
    });
}

// ========== Speak with CACHE ==========
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

// ========== Speak AI Response ==========
// ttsText = text WITH [laughs]/[sighs] tags sent to ElevenLabs (never shown to user)
// text    = clean display text shown in subtitle/chat
function speakAIResponseDirectly(text, ttsText = null, forceLangCode = null, emotion = 'calm') {
    currentEmotion = emotion || 'calm';
    console.log('🎭 emotion:', currentEmotion);
    updateStatus('Preparing voice...', false);

    const displayText = text;
    const voiceText   = ttsText || text; // fallback to clean text if no tts_message

    let langCode = forceLangCode || detectLanguage(displayText);
    const ttsChunks     = splitIntoAudioChunks(voiceText);   // with tags → ElevenLabs
    const displayChunks = splitIntoAudioChunks(displayText); // clean → subtitle

    preloadAllAudio(ttsChunks, langCode, 'ai', null).then((audioUrls) => {
        stopThinkingAnimation();
        playAudioWithSubtitles(audioUrls, displayChunks, langCode, null);
    }).catch((error) => {
        console.error('❌ AI audio preload failed:', error);
        stopThinkingAnimation();
        showMessage(displayText);
        fallbackToGoogleTTS(displayText, langCode);
    });
}

// ========== Preload ALL audio (parallel) ==========
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
                contentType: 'application/json', dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.audio_url) {
                        audioUrls[index] = response.audio_url;
                        loadedCount++;
                        updateStatus(`Loading voice... ${Math.round((loadedCount / audioChunks.length) * 100)}%`, false);
                        if (loadedCount === audioChunks.length) { clearTimeout(timer); resolve(audioUrls); }
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

// ========== Play audio + subtitle ==========
function playAudioWithSubtitles(audioUrls, audioChunks, langCode, onComplete) {
    const langNames = { 'th': 'Thai', 'en': 'English', 'cn': 'Chinese', 'jp': 'Japanese', 'kr': 'Korean' };
    const subtitleMap = audioChunks.map(chunk => splitIntoSubtitleSentences(chunk));

    isSpeaking = true; window.isSpeaking = true;
    updateStatus('Speaking in ' + (langNames[langCode] || 'English') + '...', true);
    if (useVideoAvatar) playSpeakingAnimation();

    playAudioSequence(audioUrls, subtitleMap, 0, () => {
        isSpeaking = false; window.isSpeaking = false;
        updateStatus('Ready to chat', false);
        if (mouth) mouth.scale.y = 1;
        setTimeout(() => { $('#currentMessage').fadeOut(600); }, 1200);
        if (useVideoAvatar) stopSpeakingAnimation();
        if (onComplete) onComplete();
    });
}

function playAudioSequence(audioUrls, subtitleMap, index, onComplete) {
    if (index >= audioUrls.length) { if (onComplete) onComplete(); return; }
    const audioUrl = audioUrls[index];
    if (!audioUrl) { playAudioSequence(audioUrls, subtitleMap, index + 1, onComplete); return; }

    const sentences  = subtitleMap[index] || [''];
    const isFirst    = (index === 0);
    const isLast     = (index >= audioUrls.length - 1);
    const audio      = new Audio(audioUrl);

    audio.oncanplaythrough = function() { this.play().catch(() => { playAudioSequence(audioUrls, subtitleMap, index + 1, onComplete); }); };
    audio.onplay = function() { playSubtitleSentences(sentences, this.duration || 5, isFirst); };
    audio.onended = function() { setTimeout(() => { playAudioSequence(audioUrls, subtitleMap, index + 1, onComplete); }, isLast ? 0 : 200); };
    audio.onerror = function() { playAudioSequence(audioUrls, subtitleMap, index + 1, onComplete); };
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
        const t = setTimeout(() => { showSubtitleTypewriter(sentence, isFirstChunk && i === 0); }, i * perMs);
        subtitleSequenceTimers.push(t);
    });
}

function showSubtitleTypewriter(text, isFirst) {
    if (typewriterTimer) { clearInterval(typewriterTimer); typewriterTimer = null; }
    const $msg = $('#currentMessage'); const $text = $('#messageText');
    function startTyping() {
        $text.text(''); $msg.stop(true).fadeIn(200);
        let i = 0;
        const isAsian = /[\u0E00-\u0E7F\u4E00-\u9FFF\u3040-\u30FF\uAC00-\uD7AF]/.test(text);
        const speed = isAsian ? 35 : 25;
        typewriterTimer = setInterval(() => {
            if (i < text.length) { $text.text(text.substring(0, i + 1)); i++; }
            else { clearInterval(typewriterTimer); typewriterTimer = null; }
        }, speed);
    }
    if (isFirst) { startTyping(); }
    else { $msg.stop(true).fadeOut(120, function() { startTyping(); }); }
}

function showMessage(text) {
    clearSubtitleTimers();
    const firstSentence = splitIntoSubtitleSentences(text)[0] || text;
    $('#messageText').text(firstSentence);
    $('#currentMessage').stop(true).fadeIn(300);
}

// ========== Emotion Video Helpers ==========
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
    if (SPEAKING_VIDEO_URLS.length === 0 && aiCompanionData) {
        SPEAKING_VIDEO_URLS = aiCompanionData.talking_video_urls_array || [];
    }
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
    videoAvatar.muted = false; videoAvatar.volume = 0.7;
    videoAvatar.playsInline = true; videoAvatar.loop = false; videoAvatar.preload = 'auto';

    videoAvatar.addEventListener('ended', function() { handleVideoEnded(this); });

    const initialIdleVideo = getEmotionVideo3D(currentEmotion, 'idle') || getRandomIdleVideo();
    videoAvatar.src = initialIdleVideo; currentVideoState = 'idle';
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

function handleVideoEnded(el) {
    if (currentVideoState === 'thinking') {
        if (isThinking) {
            const v = getThinkingVideo() || getRandomIdleVideo();
            if (v) { el.src = v; el.load(); el.play().catch(e => {}); }
        } else {
            currentVideoState = 'idle';
            const v = getEmotionVideo3D(currentEmotion, 'idle') || getRandomIdleVideo();
            if (v) { el.muted = false; el.volume = 0.7; el.src = v; el.load(); el.play().catch(e => {}); }
        }
        return;
    }

    if (currentVideoState === 'idle') {
        if (playEmotionIdleOnce) { playEmotionIdleOnce = false; currentEmotion = 'calm'; }
        const v = getRandomIdleVideo();
        if (v) { el.muted = false; el.volume = 0.7; el.src = v; el.load(); el.play().catch(e => {}); }

    } else if (currentVideoState === 'speaking') {
        if (isSpeaking) {
            const v = getEmotionVideo3D(currentEmotion, 'talking');
            if (v) { el.muted = true; el.src = v; el.load(); el.play().catch(e => {}); }
        } else {
            currentVideoState = 'idle'; playEmotionIdleOnce = true;
            const v = getEmotionVideo3D(currentEmotion, 'idle') || getRandomIdleVideo();
            if (v) { el.muted = false; el.volume = 0.7; el.src = v; el.load(); el.play().catch(e => {}); }
        }
    }
}

function playSpeakingAnimation() {
    if (!videoAvatar) return;
    const videoUrl = getEmotionVideo3D(currentEmotion, 'talking');
    if (!videoUrl) return;

    if (isTransitioning) {
        pendingSpeakingVideo = { url: videoUrl, state: 'speaking' };
        console.log('⏳ Queued speaking animation');
        return;
    }

    if (currentVideoState === 'speaking') return;

    pendingSpeakingVideo = null;
    switchToVideo(videoUrl, 'speaking');
}

function stopSpeakingAnimation() {
    if (!videoAvatar) return;
    pendingSpeakingVideo = null;
    if (isTransitioning) return;

    const emotionIdleUrl = getEmotionVideo3D(currentEmotion, 'idle');
    if (emotionIdleUrl) {
        playEmotionIdleOnce = true;
        switchToVideo(emotionIdleUrl, 'idle');
    } else {
        playEmotionIdleOnce = false; currentEmotion = 'calm';
        if (currentVideoState !== 'idle') {
            const v = getRandomIdleVideo();
            if (v) switchToVideo(v, 'idle');
        }
    }

    // ✅ หลัง AI พูดเสร็จ ลอง drain startup queue ถ้ายังมีค้างอยู่
    if (startupDataReady && startupQueue.length > 0 && !startupQueueRunning) {
        setTimeout(tryDrainStartupQueue, 1200);
    }
}

function playIdleAnimation() {
    if (!videoAvatar || isTransitioning || currentVideoState === 'idle') return;
    const videoUrl = getEmotionVideo3D(currentEmotion, 'idle') || getRandomIdleVideo();
    if (videoUrl) switchToVideo(videoUrl, 'idle');
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
    newVideo.playsInline = true; newVideo.loop = false; newVideo.src = videoUrl;

    newVideo.addEventListener('ended', function() { handleVideoEnded(this); });

    container.appendChild(newVideo);
    newVideo.addEventListener('canplay', function playNew() {
        newVideo.removeEventListener('canplay', playNew);
        newVideo.play().then(() => {
            videoAvatar.style.opacity = '0'; newVideo.style.opacity = '1';
            setTimeout(() => {
                if (videoAvatar && videoAvatar.parentElement === container) container.removeChild(videoAvatar);
                videoAvatar = newVideo;
                currentVideoState = newState;
                isTransitioning = false;

                if (pendingSpeakingVideo && isSpeaking) {
                    const pending = pendingSpeakingVideo;
                    pendingSpeakingVideo = null;
                    switchToVideo(pending.url, pending.state);
                }
            }, 300);
        }).catch(e => {
            if (newVideo.parentElement === container) container.removeChild(newVideo);
            isTransitioning = false;
            pendingSpeakingVideo = null;
        });
    });
    newVideo.load();
}

// ========== 3D Fallback Avatar ==========
function init3DAvatar() {
    const canvas = document.getElementById('avatarCanvas');
    scene = new THREE.Scene(); scene.background = null;
    camera = new THREE.PerspectiveCamera(75, canvas.clientWidth / canvas.clientHeight, 0.1, 1000);
    camera.position.z = 7;
    renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
    renderer.setClearColor(0x000000, 0);
    renderer.setSize(canvas.clientWidth, canvas.clientHeight);
    scene.add(new THREE.AmbientLight(0xffffff, 0.5));
    const dLight = new THREE.DirectionalLight(0xffffff, 0.6);
    dLight.position.set(5, 10, 7); scene.add(dLight);
    createPastelSheepCharacter(); animate();
    window.addEventListener('resize', onWindowResize);
}

function createPastelSheepCharacter() {
    const character = new THREE.Group();
    const head = new THREE.Mesh(
        new THREE.SphereGeometry(1.4, 32, 32),
        new THREE.MeshPhongMaterial({ color: 0x87CEEB, shininess: 60, emissive: 0x5dade2, emissiveIntensity: 0.15 })
    );
    character.add(head); avatar = character; scene.add(avatar);
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
    } else if (jwt) { headers['Authorization'] = 'Bearer ' + jwt; }
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
    const $list = $('#conversationsList'); $list.empty();
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
    $('#dropdownMenu').removeClass('show'); $('#menuToggle').removeClass('active');
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
                speakAIResponseDirectly(
                    response.ai_message,
                    response.tts_message || response.ai_message,
                    response.language_used || requestData.preferred_language,
                    response.ai_emotion || 'calm'
                );
            } else {
                stopThinkingAnimation();
                Swal.fire('Error', response.message, 'error');
                updateStatus('Ready to chat', false);
            }
            $('#messageInput').prop('disabled', false).focus();
            $('#sendBtn').prop('disabled', false);
        },
        error: function() {
            stopThinkingAnimation();
            Swal.fire('Error', 'Failed to send message', 'error');
            updateStatus('Ready to chat', false);
            $('#messageInput').prop('disabled', false).focus();
            $('#sendBtn').prop('disabled', false);
        }
    });
}

// ========== Utility ==========
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
    currentEmotion = 'calm'; isThinking = false;
    pendingSpeakingVideo = null;
    clearSubtitleTimers();
    $('.conversation-item').removeClass('active');
    $('#messageInput').val('').focus();
    $('#currentMessage').stop(true).fadeOut(400);
    if (window.speechSynthesis) window.speechSynthesis.cancel();
    if (currentAudio) { currentAudio.pause(); currentAudio = null; }
    isSpeaking = false; window.isSpeaking = false;
    updateStatus('Ready to chat', false);
    if (useVideoAvatar && currentVideoState !== 'idle') {
        const v = getRandomIdleVideo(); if (v) switchToVideo(v, 'idle');
    }
    $('#dropdownMenu').removeClass('show'); $('#menuToggle').removeClass('active');
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
    const date = new Date(timestamp); const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    if (diff < 60)     return 'Just now';
    if (diff < 3600)   return Math.floor(diff / 60)   + 'm ago';
    if (diff < 86400)  return Math.floor(diff / 3600) + 'h ago';
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

console.log('✅ AI Chat 3D — startup queue + idle window + news detail');