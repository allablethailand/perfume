/**
 * AI Chat 3D - WITH TTS CACHE SYSTEM + RANDOM VIDEO FIX + NO REPEAT + WEATHER EVERY TIME
 * ✅ Cache: welcome, conversation messages (ไม่ cache AI responses)
 * ✅ รอให้เสียงโหลดเสร็จก่อนแสดงข้อความ
 * ✅ Sync การแสดงผลกับเสียงให้ตรงกัน
 * ✅ FIX: ใช้วิดีโอที่ PHP สุ่มมาให้แล้ว (ไม่สุ่มซ้ำใน JS)
 * ✅ NEW: สุ่มวิดีโอถัดไปไม่ซ้ำกับอันที่เพิ่งเล่น
 * ✅ NEW: พูดสภาพอากาศทุกครั้งที่เข้ามา
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

let IDLE_VIDEO_URLS = []; // เก็บ array ของวิดีโอ idle
let SPEAKING_VIDEO_URLS = []; // เก็บ array ของวิดีโอ speaking
let currentVideoState = 'idle';
let isTransitioning = false;
let preloadedSpeakingVideo = null;

// ✅ NEW: เก็บวิดีโอที่เพิ่งเล่น เพื่อป้องกันซ้ำ
let lastPlayedIdleVideo = null;
let lastPlayedSpeakingVideo = null;

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

// ✅ Weather tracking
let weatherReportPlayed = false;
let weatherData = null;

// ========== Initialize ==========
$(document).ready(function() {
    console.log('🚀 Initializing AI Chat 3D with TTS Cache...');
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
    
    // ✅ ดึงข้อมูล AI ก่อน (จะได้ preferred_language ที่ถูกต้อง)
    fetchAICompanionData().then(() => {
        console.log('📊 After fetch:', {
            idle_videos: IDLE_VIDEO_URLS.length,
            useVideo: useVideoAvatar,
            language: userPreferredLanguage // ✅ ตอนนี้จะเป็นภาษาที่ถูกต้องแล้ว
        });
        
        if (useVideoAvatar && IDLE_VIDEO_URLS.length > 0) {
            initVideoAvatar();
        } else {
            console.warn('⚠️ No idle video URLs, using 3D avatar');
            init3DAvatar();
        }
        
        loadConversations();
        
        // ✅ ดึงข้อมูลสภาพอากาศ (ใช้ภาษาที่ถูกต้อง)
        fetchWeatherData().then(() => {
            setTimeout(() => {
                playWelcomeWithWeather();
            }, 800);
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
    
    $('#dropdownMenu').on('click', function(e) {
        e.stopPropagation();
    });
});

// ========== ✅ Fetch Weather Data (พูดทุกครั้ง) ==========
function fetchWeatherData() {
    return new Promise((resolve) => {
        console.log('🌤️ Fetching weather data...');
        
        const headers = {};
        if (jwt) {
            headers['Authorization'] = 'Bearer ' + jwt;
        }
        
        $.ajax({
            url: 'app/actions/get_weather.php?lang=' + userPreferredLanguage,
            type: 'GET',
            headers: headers,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    weatherData = response.data;
                    weatherReportPlayed = false; // ✅ รีเซ็ตให้พูดทุกครั้ง
                    console.log('✅ Weather data loaded:', weatherData);
                } else {
                    console.warn('⚠️ Weather fetch failed');
                }
                resolve();
            },
            error: function(xhr, status, error) {
                console.error('❌ Weather fetch error:', error);
                resolve(); // ไม่ให้ error ขัดขวางการทำงาน
            }
        });
    });
}

// ========== Play Welcome + Weather (ทุกครั้ง) ==========
function playWelcomeWithWeather() {
    if (isWelcomeMessagePlayed) {
        console.log('⏭️ Welcome already played');
        return;
    }
    
    isWelcomeMessagePlayed = true;
    
    const welcomeText = WELCOME_MESSAGES[userPreferredLanguage] || WELCOME_MESSAGES.th;
    
    console.log(`🎉 Playing welcome + weather in ${userPreferredLanguage}`);
    
    if (useVideoAvatar && videoAvatar && videoAvatar.paused) {
        videoAvatar.play().catch(e => {
            console.warn('⚠️ Autoplay blocked');
        });
    }
    
    // เล่น welcome message
    speakTextWithCache(welcomeText, userPreferredLanguage, 'welcome').then(() => {
        // หลังจาก welcome เสร็จ ถ้ามีข้อมูลสภาพอากาศ
        if (weatherData && !weatherReportPlayed) {
            setTimeout(() => {
                playWeatherReport();
            }, 1000);
        }
    });
}

// ========== Play Weather Report (ทุกครั้ง) ==========
function playWeatherReport() {
    if (weatherReportPlayed || !weatherData) {
        return;
    }
    
    weatherReportPlayed = true;
    
    console.log('🌤️ Playing weather report');
    
    const weatherText = weatherData.message;
    
    // ใช้ cache สำหรับข้อความสภาพอากาศ
    speakTextWithCache(weatherText, userPreferredLanguage, 'weather');
}

// ========== ✅ FIX: Fetch AI Data (ดึง preferred_language ทุกครั้ง) ==========
function fetchAICompanionData() {
    return new Promise((resolve, reject) => {
        let url = '';
        const headers = {};
        
        if (isGuestMode && aiCodeFromURL) {
            url = 'app/actions/get_ai_data.php?ai_code=' + aiCodeFromURL;
            
            // ✅ เพิ่ม user_companion_id ถ้ามี
            const storedCompanionId = sessionStorage.getItem('user_companion_id');
            if (storedCompanionId) {
                url += '&user_companion_id=' + storedCompanionId;
            }
            
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
                    
                    IDLE_VIDEO_URLS = aiCompanionData.idle_video_urls_array || [];
                    
                    // ✅ ดึง preferred_language จาก response (ไม่ใช้ default!)
                    if (response.preferred_language) {
                        userPreferredLanguage = response.preferred_language;
                        console.log('✅ Language from API:', userPreferredLanguage);
                    } else if (aiCompanionData.preferred_language) {
                        userPreferredLanguage = aiCompanionData.preferred_language;
                        console.log('✅ Language from ai_data:', userPreferredLanguage);
                    } else {
                        // ถ้าไม่มีเลย ใช้ URL parameter หรือ default
                        userPreferredLanguage = langFromURL || 'th';
                        console.log('⚠️ Using fallback language:', userPreferredLanguage);
                    }
                    
                    // ✅ ลบการเก็บ language ใน sessionStorage ออก (ไม่ cache)
                    // sessionStorage.removeItem('preferred_language');
                    
                    console.log('🎲 Data loaded:', {
                        idle_count: IDLE_VIDEO_URLS.length,
                        language: userPreferredLanguage
                    });
                    
                    // เก็บ companion_id
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

// ========== Speak with CACHE (สำหรับ welcome, conversation) ==========
function speakTextWithCache(text, forceLangCode = null, cacheType = 'welcome') {
    console.log('🎬 Speaking with CACHE:', text.substring(0, 50), '| Type:', cacheType);
    
    return new Promise((resolve, reject) => {
        updateStatus('Preparing voice...', false);
        $('#messageText').html('<span class="typing-indicator">Thinking...</span>');
        $('#currentMessage').fadeIn();
        
        let langCode = forceLangCode || detectLanguage(text);
        const chunks = splitTextIntoChunks(text);
        
        preloadAllAudioChunksWithCache(chunks, langCode, cacheType).then((audioUrls) => {
            console.log('✅ All audio preloaded with cache');
            showMessage(text);
            playPreloadedAudio(audioUrls, langCode, text, resolve); // ส่ง resolve ไปด้วย
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
        
        console.log(`📥 Preloading ${chunks.length} chunks with cache | Type: ${cacheType}`);
        
        chunks.forEach((chunk, index) => {
            const requestData = {
                text: chunk,
                language: langCode,
                cache_type: cacheType
            };
            
            if (companionId) {
                requestData.user_companion_id = companionId;
            }
            
            if (aiCompanionData && aiCompanionData.ai_id) {
                requestData.ai_id = aiCompanionData.ai_id;
            }
            
            if (aiCompanionData && aiCompanionData.voice_id) {
                requestData.voice_id = aiCompanionData.voice_id;
            }
            
            $.ajax({
                url: 'app/actions/get_or_create_tts_cache.php',
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
                        
                        const hitStatus = response.cache_hit ? '✅ Cache HIT' : '🆕 Cache MISS';
                        console.log(`${hitStatus} - Chunk ${index + 1}/${chunks.length}`);
                        
                        if (loadedCount === chunks.length && !hasError) {
                            resolve(audioUrls);
                        }
                    } else {
                        if (!hasError) {
                            hasError = true;
                            reject(new Error('No audio URL'));
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
        
        setTimeout(() => {
            if (loadedCount < chunks.length && !hasError) {
                hasError = true;
                reject(new Error('Audio preload timeout'));
            }
        }, 30000);
    });
}

// ========== Speak AI Response (ไม่ใช้ CACHE) ==========
function speakAIResponseDirectly(text, forceLangCode = null) {
    console.log('🎬 Speaking AI Response (NO CACHE):', text.substring(0, 50));
    
    updateStatus('Preparing voice...', false);
    $('#messageText').html('<span class="typing-indicator">Thinking...</span>');
    $('#currentMessage').fadeIn();
    
    let langCode = forceLangCode || detectLanguage(text);
    const chunks = splitTextIntoChunks(text);
    
    preloadAIResponseAudio(chunks, langCode).then((audioUrls) => {
        console.log('✅ AI Response audio ready (NO CACHE)');
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
        
        console.log(`📥 Preloading ${chunks.length} AI response chunks (NO CACHE)`);
        
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
                        
                        console.log(`✅ AI Chunk ${index + 1}/${chunks.length} loaded (NO CACHE)`);
                        
                        if (loadedCount === chunks.length && !hasError) {
                            resolve(audioUrls);
                        }
                    } else {
                        if (!hasError) {
                            hasError = true;
                            reject(new Error('No audio URL'));
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
        
        setTimeout(() => {
            if (loadedCount < chunks.length && !hasError) {
                hasError = true;
                reject(new Error('Audio preload timeout'));
            }
        }, 30000);
    });
}

// ========== Play Preloaded Audio ==========
function playPreloadedAudio(audioUrls, langCode, fullText, onComplete) {
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
        isSpeaking = false;
        window.isSpeaking = false;
        updateStatus('Ready to chat', false);
        $('#currentMessage').fadeOut();
        
        if (mouth) mouth.scale.y = 1;
        if (useVideoAvatar) stopSpeakingAnimation();
        
        console.log('✅ All audio playback completed');
        
        // เรียก callback
        if (onComplete) onComplete();
    });
}

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
            playAudioUrlsSequentially(audioUrls, index + 1, onComplete);
        });
    };
    
    audio.onended = function() {
        console.log(`⏹️ Chunk ${index + 1} ended`);
        setTimeout(() => {
            playAudioUrlsSequentially(audioUrls, index + 1, onComplete);
        }, 300);
    };
    
    audio.onerror = function(e) {
        console.error(`❌ Audio ${index + 1} error:`, e);
        playAudioUrlsSequentially(audioUrls, index + 1, onComplete);
    };
    
    audio.load();
}

// ========== ✅ Random Video Selection Functions (ป้องกันซ้ำ) ==========
function loadSpeakingVideosIfNeeded() {
    // ✅ โหลด speaking videos เฉพาะตอนที่ต้องใช้จริงๆ
    if (SPEAKING_VIDEO_URLS.length === 0 && aiCompanionData) {
        SPEAKING_VIDEO_URLS = aiCompanionData.talking_video_urls_array || [];
        console.log('📥 Loaded speaking videos on demand:', SPEAKING_VIDEO_URLS.length);
    }
}

function getRandomIdleVideo() {
    if (IDLE_VIDEO_URLS.length === 0) return null;
    
    // ถ้ามีวิดีโอเดียว ก็ return เลย
    if (IDLE_VIDEO_URLS.length === 1) {
        return IDLE_VIDEO_URLS[0];
    }
    
    // สร้าง array ที่ไม่รวมวิดีโอที่เพิ่งเล่น
    const availableVideos = IDLE_VIDEO_URLS.filter(video => video !== lastPlayedIdleVideo);
    
    // ถ้าไม่มีวิดีโอให้เลือก (ไม่น่าเกิด) ให้ใช้ทั้งหมด
    const videoPool = availableVideos.length > 0 ? availableVideos : IDLE_VIDEO_URLS;
    
    const randomIndex = Math.floor(Math.random() * videoPool.length);
    const selectedVideo = videoPool[randomIndex];
    
    // บันทึกวิดีโอที่เลือก
    lastPlayedIdleVideo = selectedVideo;
    
    console.log(`🎲 Random IDLE video (no repeat): ${selectedVideo.split('/').pop()}`);
    console.log(`   Available: ${videoPool.length}/${IDLE_VIDEO_URLS.length} videos`);
    
    return selectedVideo;
}

function getRandomSpeakingVideo() {
    loadSpeakingVideosIfNeeded(); // ✅ โหลด speaking videos ก่อนใช้งาน
    
    if (SPEAKING_VIDEO_URLS.length === 0) return null;
    
    // ถ้ามีวิดีโอเดียว ก็ return เลย
    if (SPEAKING_VIDEO_URLS.length === 1) {
        return SPEAKING_VIDEO_URLS[0];
    }
    
    // สร้าง array ที่ไม่รวมวิดีโอที่เพิ่งเล่น
    const availableVideos = SPEAKING_VIDEO_URLS.filter(video => video !== lastPlayedSpeakingVideo);
    
    // ถ้าไม่มีวิดีโอให้เลือก (ไม่น่าเกิด) ให้ใช้ทั้งหมด
    const videoPool = availableVideos.length > 0 ? availableVideos : SPEAKING_VIDEO_URLS;
    
    const randomIndex = Math.floor(Math.random() * videoPool.length);
    const selectedVideo = videoPool[randomIndex];
    
    // บันทึกวิดีโอที่เลือก
    lastPlayedSpeakingVideo = selectedVideo;
    
    console.log(`🎲 Random SPEAKING video (no repeat): ${selectedVideo.split('/').pop()}`);
    console.log(`   Available: ${videoPool.length}/${SPEAKING_VIDEO_URLS.length} videos`);
    
    return selectedVideo;
}

// ========== Video Avatar Functions ==========
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
    
    // 🔊 เปิดเสียง idle, ปิดเสียงตอนพูด
    videoAvatar.muted = false; // ✅ เปิดเสียงตอน idle
    videoAvatar.volume = 0.7; // ✅ ปรับระดับเสียง 70%
    videoAvatar.playsInline = true;
    videoAvatar.loop = false;
    videoAvatar.preload = 'auto';
    
    // ✅ เมื่อวิดีโอจบให้สุ่มคลิปใหม่ (ไม่ซ้ำ)
    videoAvatar.addEventListener('ended', function() {
        console.log('🔄 Video ended, loading new random video (no repeat)...');
        
        if (currentVideoState === 'idle') {
            const newIdleVideo = getRandomIdleVideo();
            if (newIdleVideo) {
                this.muted = false; // ✅ เปิดเสียง idle
                this.volume = 0.7;
                this.src = newIdleVideo;
                this.load();
                this.play().catch(e => console.error('Play error:', e));
            }
        } else if (currentVideoState === 'speaking') {
            const newSpeakingVideo = getRandomSpeakingVideo();
            if (newSpeakingVideo) {
                this.muted = true; // ✅ ปิดเสียงตอนพูด
                this.src = newSpeakingVideo;
                this.load();
                this.play().catch(e => console.error('Play error:', e));
            }
        }
    });
    
    // ✅ สุ่มเลือกวิดีโอ idle แบบสุ่ม
    const initialIdleVideo = getRandomIdleVideo();
    videoAvatar.src = initialIdleVideo;
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
        console.log('✅ Initial idle video loaded with sound');
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
}

function playIdleAnimation() {
    if (!videoAvatar || isTransitioning) return;
    if (currentVideoState === 'idle') return;
    
    console.log('🔇 Returning to idle (with sound)');
    
    // ✅ สุ่มเลือกวิดีโอ idle ใหม่ทุกครั้ง (ไม่ซ้ำ)
    const randomIdleVideo = getRandomIdleVideo();
    if (randomIdleVideo) {
        switchToVideo(randomIdleVideo, 'idle');
    }
}

function playSpeakingAnimation() {
    if (!videoAvatar || isTransitioning) return;
    if (currentVideoState === 'speaking') return;
    
    console.log('🔊 Switching to speaking (muted)');
    
    // ✅ โหลด speaking videos ครั้งแรกที่จะใช้งาน
    loadSpeakingVideosIfNeeded();
    
    // ✅ สุ่มเลือกวิดีโอ speaking ใหม่ทุกครั้ง (ไม่ซ้ำ)
    const randomSpeakingVideo = getRandomSpeakingVideo();
    if (randomSpeakingVideo) {
        switchToVideo(randomSpeakingVideo, 'speaking');
    } else {
        console.warn('⚠️ No speaking videos available');
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
    
    // 🔊 ตั้งค่าเสียงตาม state
    if (newState === 'idle') {
        newVideo.muted = false; // ✅ เปิดเสียง idle
        newVideo.volume = 0.7;
        console.log('🔊 Idle video: Sound ON');
    } else if (newState === 'speaking') {
        newVideo.muted = true; // ✅ ปิดเสียงตอนพูด
        console.log('🔇 Speaking video: Sound OFF (TTS playing)');
    }
    
    newVideo.playsInline = true;
    newVideo.loop = false;
    newVideo.src = videoUrl;
    
    // ✅ เมื่อวิดีโอจบให้สุ่มคลิปใหม่ (ไม่ซ้ำ)
    newVideo.addEventListener('ended', function() {
        console.log('🔄 Video ended, loading new random video (no repeat)...');
        
        if (currentVideoState === 'idle') {
            const newIdleVideo = getRandomIdleVideo();
            if (newIdleVideo) {
                this.muted = false; // ✅ เปิดเสียง idle
                this.volume = 0.7;
                this.src = newIdleVideo;
                this.load();
                this.play().catch(e => console.error('Play error:', e));
            }
        } else if (currentVideoState === 'speaking') {
            const newSpeakingVideo = getRandomSpeakingVideo();
            if (newSpeakingVideo) {
                this.muted = true; // ✅ ปิดเสียงตอนพูด
                this.src = newSpeakingVideo;
                this.load();
                this.play().catch(e => console.error('Play error:', e));
            }
        }
    });
    
    container.appendChild(newVideo);
    
    newVideo.addEventListener('canplay', function playNew() {
        newVideo.removeEventListener('canplay', playNew);
        
        newVideo.play().then(() => {
            // 🔊 Fade out เสียงวิดีโอเก่า (ถ้ามี)
            if (!videoAvatar.muted) {
                let fadeOutInterval = setInterval(() => {
                    if (videoAvatar.volume > 0.1) {
                        videoAvatar.volume -= 0.1;
                    } else {
                        videoAvatar.volume = 0;
                        clearInterval(fadeOutInterval);
                    }
                }, 50);
            }
            
            videoAvatar.style.opacity = '0';
            newVideo.style.opacity = '1';
            
            setTimeout(() => {
                container.removeChild(videoAvatar);
                videoAvatar = newVideo;
                currentVideoState = newState;
                isTransitioning = false;
                console.log(`✅ Switched to ${newState}: ${videoUrl}`);
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
    console.log('🔊 Stopping speaking, returning to idle with sound');
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
                    showMessage(lastMessage.message);
                }
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
                
                speakAIResponseDirectly(
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

// ========== Utility Functions ==========

function detectLanguage(text) {
    if (/[\u0E00-\u0E7F]/.test(text)) return 'th';
    if (/[\u4E00-\u9FFF]/.test(text)) return 'cn';
    if (/[\u3040-\u309F\u30A0-\u30FF]/.test(text)) return 'jp';
    if (/[\uAC00-\uD7AF]/.test(text)) return 'kr';
    return 'en';
}

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

function fallbackToGoogleTTS(text, langCode, onComplete) {
    const encodedText = encodeURIComponent(text);
    
    let googleLangCode = langCode;
    if (langCode === 'cn') googleLangCode = 'zh-CN';
    if (langCode === 'jp') googleLangCode = 'ja';
    if (langCode === 'kr') googleLangCode = 'ko';
    
    const ttsUrl = `https://translate.google.com/translate_tts?ie=UTF-8&tl=${googleLangCode}&client=tw-ob&q=${encodedText}`;
    
    console.log(`🔊 Using Google TTS fallback`);
    
    if (currentAudio) {
        currentAudio.pause();
        currentAudio = null;
    }
    
    currentAudio = new Audio(ttsUrl);
    
    currentAudio.oncanplaythrough = function() {
        this.play().catch(err => {
            console.error('Google TTS error:', err);
            if (onComplete) onComplete();
        });
    };
    
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

console.log('✅ AI Chat 3D with TTS Cache System + Random Video Fix + No Repeat + Weather Every Time loaded');