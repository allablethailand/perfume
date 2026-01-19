/**
 * AI Chat 3D JavaScript
 * 
 * จัดการ 3D Avatar, Text-to-Speech และการสื่อสารกับ API
 */

let currentConversationId = 0;
const jwt = sessionStorage.getItem("jwt");

// Three.js variables
let scene, camera, renderer, avatar, mouth;
let isSpeaking = false;

// ✅ เริ่มต้น
$(document).ready(function() {
    if (!jwt) {
        window.location.href = '?login';
        return;
    }
    
    init3DAvatar();
    loadConversations();
    
    // Auto-resize textarea
    $('#messageInput').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
});

// ✅ สร้าง 3D Avatar
function init3DAvatar() {
    const canvas = document.getElementById('avatarCanvas');
    
    // Scene
    scene = new THREE.Scene();
    scene.background = new THREE.Color(0x1a1a2e);
    
    // Camera
    camera = new THREE.PerspectiveCamera(75, canvas.clientWidth / canvas.clientHeight, 0.1, 1000);
    camera.position.z = 5;
    
    // Renderer
    renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true });
    renderer.setSize(canvas.clientWidth, canvas.clientHeight);
    renderer.shadowMap.enabled = true;
    
    // Lights
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
    scene.add(ambientLight);
    
    const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
    directionalLight.position.set(5, 10, 7);
    directionalLight.castShadow = true;
    scene.add(directionalLight);
    
    const pointLight1 = new THREE.PointLight(0x667eea, 1, 100);
    pointLight1.position.set(-5, 3, 5);
    scene.add(pointLight1);
    
    const pointLight2 = new THREE.PointLight(0x764ba2, 1, 100);
    pointLight2.position.set(5, 3, 5);
    scene.add(pointLight2);
    
    // Create Avatar
    createAvatar();
    
    // Animation loop
    animate();
    
    // Handle resize
    window.addEventListener('resize', onWindowResize);
}

// ✅ สร้างตัวละคร Avatar
function createAvatar() {
    const avatarGroup = new THREE.Group();
    
    // Head
    const headGeometry = new THREE.SphereGeometry(1, 32, 32);
    const headMaterial = new THREE.MeshPhongMaterial({ 
        color: 0xffdbac,
        shininess: 30
    });
    const head = new THREE.Mesh(headGeometry, headMaterial);
    head.castShadow = true;
    avatarGroup.add(head);
    
    // Eyes
    const eyeGeometry = new THREE.SphereGeometry(0.15, 16, 16);
    const eyeMaterial = new THREE.MeshPhongMaterial({ color: 0x000000 });
    
    const leftEye = new THREE.Mesh(eyeGeometry, eyeMaterial);
    leftEye.position.set(-0.3, 0.3, 0.8);
    avatarGroup.add(leftEye);
    
    const rightEye = new THREE.Mesh(eyeGeometry, eyeMaterial);
    rightEye.position.set(0.3, 0.3, 0.8);
    avatarGroup.add(rightEye);
    
    // Mouth (ที่จะขยับได้)
    const mouthGeometry = new THREE.TorusGeometry(0.3, 0.1, 16, 100, Math.PI);
    const mouthMaterial = new THREE.MeshPhongMaterial({ color: 0xff6b6b });
    mouth = new THREE.Mesh(mouthGeometry, mouthMaterial);
    mouth.position.set(0, -0.3, 0.8);
    mouth.rotation.x = Math.PI;
    avatarGroup.add(mouth);
    
    // Body
    const bodyGeometry = new THREE.CylinderGeometry(0.6, 0.8, 2, 32);
    const bodyMaterial = new THREE.MeshPhongMaterial({ 
        color: 0x667eea,
        shininess: 50
    });
    const body = new THREE.Mesh(bodyGeometry, bodyMaterial);
    body.position.y = -2;
    body.castShadow = true;
    avatarGroup.add(body);
    
    // Arms
    const armGeometry = new THREE.CylinderGeometry(0.15, 0.15, 1.5, 16);
    const armMaterial = new THREE.MeshPhongMaterial({ color: 0xffdbac });
    
    const leftArm = new THREE.Mesh(armGeometry, armMaterial);
    leftArm.position.set(-0.8, -1.8, 0);
    leftArm.rotation.z = 0.3;
    avatarGroup.add(leftArm);
    
    const rightArm = new THREE.Mesh(armGeometry, armMaterial);
    rightArm.position.set(0.8, -1.8, 0);
    rightArm.rotation.z = -0.3;
    avatarGroup.add(rightArm);
    
    avatar = avatarGroup;
    scene.add(avatar);
}

// ✅ Animation loop
function animate() {
    requestAnimationFrame(animate);
    
    // Gentle idle animation
    if (!isSpeaking) {
        avatar.rotation.y = Math.sin(Date.now() * 0.001) * 0.1;
        avatar.position.y = Math.sin(Date.now() * 0.002) * 0.1;
    }
    
    // Speaking animation (ปากขยับ)
    if (isSpeaking && mouth) {
        const mouthScale = 1 + Math.sin(Date.now() * 0.02) * 0.3;
        mouth.scale.y = mouthScale;
        
        // เอียงหัวเล็กน้อยตอนพูด
        avatar.rotation.x = Math.sin(Date.now() * 0.005) * 0.05;
    }
    
    renderer.render(scene, camera);
}

// ✅ Handle window resize
function onWindowResize() {
    const canvas = document.getElementById('avatarCanvas');
    camera.aspect = canvas.clientWidth / canvas.clientHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(canvas.clientWidth, canvas.clientHeight);
}

// ✅ โหลดรายการ conversations
function loadConversations() {
    $.ajax({
        url: 'app/actions/get_chat_data.php?action=list_conversations',
        type: 'GET',
        headers: {
            'Authorization': 'Bearer ' + jwt
        },
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

// ✅ แสดงรายการ conversations
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

// ✅ โหลดประวัติแชท
function loadConversation(conversationId) {
    currentConversationId = conversationId;
    
    $('.conversation-item').removeClass('active');
    $(`.conversation-item[data-id="${conversationId}"]`).addClass('active');
    
    $.ajax({
        url: 'app/actions/get_chat_data.php?action=get_history&conversation_id=' + conversationId,
        type: 'GET',
        headers: {
            'Authorization': 'Bearer ' + jwt
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                // แสดงข้อความล่าสุด
                if (response.messages.length > 0) {
                    const lastMessage = response.messages[response.messages.length - 1];
                    if (lastMessage.role === 'assistant') {
                        showMessage(lastMessage.message);
                    }
                }
            }
        }
    });
}

// ✅ ส่งข้อความ
function sendMessage() {
    const message = $('#messageInput').val().trim();
    
    if (!message) {
        return;
    }
    
    // Disable input
    $('#messageInput').prop('disabled', true);
    $('#sendBtn').prop('disabled', true);
    
    // Clear input
    $('#messageInput').val('').css('height', 'auto');
    
    // Update status
    updateStatus('Thinking...', false);
    
    // Send to API
    $.ajax({
        url: 'app/actions/ai_chat.php',
        type: 'POST',
        headers: {
            'Authorization': 'Bearer ' + jwt,
            'Content-Type': 'application/json'
        },
        data: JSON.stringify({
            conversation_id: currentConversationId,
            message: message,
            language: 'th'
        }),
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                // Update conversation ID
                if (currentConversationId === 0) {
                    currentConversationId = response.conversation_id;
                    loadConversations();
                }
                
                // แสดงข้อความและพูด
                showMessage(response.ai_message);
                speakText(response.ai_message);
            } else {
                Swal.fire('Error', response.message, 'error');
                updateStatus('Ready to chat', false);
            }
            
            // Enable input
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

// ✅ แสดงข้อความบนหน้าจอ
function showMessage(text) {
    $('#messageText').text(text);
    $('#currentMessage').fadeIn();
}

// ✅ Text-to-Speech รองรับหลายภาษาด้วย Google Translate TTS
function speakText(text) {
    console.log('🎤 Preparing to speak:', text.substring(0, 100));
    
    // ตรวจจับภาษา
    let langCode = 'th'; // Default Thai
    let detectedLang = 'Thai';
    
    // Thai
    if (/[\u0E00-\u0E7F]/.test(text)) {
        langCode = 'th';
        detectedLang = 'Thai';
    }
    // Chinese (Mandarin)
    else if (/[\u4E00-\u9FFF]/.test(text)) {
        langCode = 'zh-CN';
        detectedLang = 'Chinese';
    }
    // Japanese
    else if (/[\u3040-\u309F\u30A0-\u30FF]/.test(text)) {
        langCode = 'ja';
        detectedLang = 'Japanese';
    }
    // Korean
    else if (/[\uAC00-\uD7AF]/.test(text)) {
        langCode = 'ko';
        detectedLang = 'Korean';
    }
    // English
    else {
        langCode = 'en';
        detectedLang = 'English';
    }
    
    console.log('🗣️ Detected:', detectedLang, '(', langCode, ')');
    
    // Update status
    isSpeaking = true;
    updateStatus('Speaking in ' + detectedLang + '...', true);
    
    // แบ่งข้อความถ้ายาวเกิน 200 ตัวอักษร
    const maxLength = 200;
    const chunks = [];
    
    if (text.length > maxLength) {
        // แบ่งตามประโยค
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
    
    console.log('📝 Split into', chunks.length, 'chunks');
    
    // เล่น audio ทีละชิ้น
    playGoogleTTSChunks(chunks, 0, langCode);
}

// ✅ เล่น Google TTS ทีละชิ้น
let currentAudio = null;

function playGoogleTTSChunks(chunks, index, langCode) {
    if (index >= chunks.length) {
        // เล่นจบแล้ว
        console.log('✅ Finished speaking all chunks');
        isSpeaking = false;
        updateStatus('Ready to chat', false);
        $('#currentMessage').fadeOut();
        
        // Reset mouth
        if (mouth) {
            mouth.scale.y = 1;
        }
        return;
    }
    
    const chunk = chunks[index];
    console.log('🔊 Playing chunk', (index + 1), 'of', chunks.length, ':', chunk.substring(0, 50));
    
    // สร้าง Google TTS URL
    const encodedText = encodeURIComponent(chunk);
    const ttsUrl = `https://translate.google.com/translate_tts?ie=UTF-8&tl=${langCode}&client=tw-ob&q=${encodedText}`;
    
    // สร้าง audio element
    currentAudio = new Audio();
    
    currentAudio.oncanplaythrough = function() {
        console.log('✅ Audio ready, playing chunk', (index + 1));
        this.play().catch(err => {
            console.error('❌ Play error:', err);
            // ลอง fallback
            playGoogleTTSChunks(chunks, index + 1, langCode);
        });
    };
    
    currentAudio.onplay = function() {
        console.log('▶️ Playing audio chunk', (index + 1));
        isSpeaking = true;
    };
    
    currentAudio.onended = function() {
        console.log('✅ Chunk', (index + 1), 'finished');
        // เล่นชิ้นถัดไป
        setTimeout(() => {
            playGoogleTTSChunks(chunks, index + 1, langCode);
        }, 300); // หน่วงเวลาเล็กน้อยระหว่างชิ้น
    };
    
    currentAudio.onerror = function(e) {
        console.error('❌ Audio error on chunk', (index + 1), ':', e);
        console.warn('⚠️ Falling back to Web Speech API');
        // Fallback to Web Speech API
        fallbackToWebSpeech(chunks.join(' '), langCode);
    };
    
    // ตั้ง src และโหลด
    currentAudio.src = ttsUrl;
    currentAudio.load();
}

// ✅ Fallback: ใช้ Web Speech API ถ้า Google TTS ไม่ทำงาน
function fallbackToWebSpeech(text, langCode) {
    console.log('⚠️ Falling back to Web Speech API');
    
    if (!window.speechSynthesis) {
        console.error('❌ Web Speech API not supported');
        isSpeaking = false;
        updateStatus('Ready to chat', false);
        
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
    
    // Stop current speech
    window.speechSynthesis.cancel();
    
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = langCode === 'th' ? 'th-TH' : 
                     langCode === 'zh-CN' ? 'zh-CN' :
                     langCode === 'ja' ? 'ja-JP' :
                     langCode === 'ko' ? 'ko-KR' : 'en-US';
    utterance.rate = 0.85;
    utterance.pitch = 1.0;
    utterance.volume = 1.0;
    
    utterance.onstart = function() {
        console.log('✅ Fallback speech started');
        isSpeaking = true;
    };
    
    utterance.onend = function() {
        console.log('✅ Fallback speech ended');
        isSpeaking = false;
        updateStatus('Ready to chat', false);
        $('#currentMessage').fadeOut();
        
        if (mouth) {
            mouth.scale.y = 1;
        }
    };
    
    utterance.onerror = function(event) {
        console.error('❌ Fallback speech error:', event.error);
        isSpeaking = false;
        updateStatus('Ready to chat', false);
    };
    
    window.speechSynthesis.speak(utterance);
}

// ✅ ฟังก์ชันพูดจริง
function speakWithVoice(text, langCode, detectedLang, preferredVoiceName, voices) {
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = langCode;
    
    console.log('📢 Total available voices:', voices.length);
    console.log('🔍 Looking for language:', langCode);
    
    // แสดง voices ที่มีทั้งหมด (เพื่อ debug)
    if (voices.length > 0) {
        console.log('📋 All voices:', voices.map(v => `${v.name} (${v.lang})`).slice(0, 10).join(', '));
    }
    
    // หา voice ที่เหมาะสมที่สุด
    let selectedVoice = null;
    const langPrefix = langCode.split('-')[0]; // เช่น 'th', 'zh', 'ja', 'ko', 'en'
    
    // 1. หาจากภาษาที่ตรงทั้งหมด (เช่น th-TH)
    selectedVoice = voices.find(voice => voice.lang === langCode);
    
    // 2. ถ้าไม่เจอ ให้หาจากภาษาคร่าวๆ (เช่น th)
    if (!selectedVoice) {
        selectedVoice = voices.find(voice => voice.lang.startsWith(langPrefix));
    }
    
    // 3. ถ้ายังไม่เจอ ให้หาจากชื่อ voice
    if (!selectedVoice && preferredVoiceName) {
        selectedVoice = voices.find(voice => 
            voice.name.toLowerCase().includes(preferredVoiceName.toLowerCase())
        );
    }
    
    // 4. หา Google voices (มักจะดีที่สุด)
    if (!selectedVoice) {
        selectedVoice = voices.find(voice => 
            voice.name.includes('Google') && voice.lang.startsWith(langPrefix)
        );
    }
    
    // 5. หา Microsoft voices
    if (!selectedVoice) {
        selectedVoice = voices.find(voice => 
            voice.name.includes('Microsoft') && voice.lang.startsWith(langPrefix)
        );
    }
    
    // 6. หาจาก local voice ใดๆ ที่ตรงกับภาษา
    if (!selectedVoice) {
        selectedVoice = voices.find(voice => voice.lang.includes(langPrefix));
    }
    
    if (selectedVoice) {
        utterance.voice = selectedVoice;
        console.log('✅ Selected voice:', selectedVoice.name, `(${selectedVoice.lang})`);
    } else {
        console.warn('⚠️ No matching voice found for', langCode);
        console.warn('💡 Using browser default voice');
        
        // แสดง voices ที่มีเพื่อให้ผู้ใช้ทราบ
        const availableLangs = [...new Set(voices.map(v => v.lang))];
        console.log('🌍 Available languages:', availableLangs.join(', '));
        
        // แจ้งเตือนผู้ใช้
        if (detectedLang !== 'English') {
            console.warn(`⚠️ ${detectedLang} voice not found. The speech may sound incorrect.`);
            console.warn('💡 Try using Chrome or Edge for better language support.');
        }
    }
    
    // ตั้งค่าเสียง
    utterance.rate = 0.85;  // ความเร็ว (0.1 - 10)
    utterance.pitch = 1.0;  // ระดับเสียง (0 - 2)
    utterance.volume = 1.0; // ความดัง (0 - 1)
    
    // Events
    utterance.onstart = function() {
        console.log('✅ Started speaking in ' + detectedLang);
        isSpeaking = true;
        updateStatus('Speaking in ' + detectedLang + '...', true);
    };
    
    utterance.onend = function() {
        console.log('✅ Finished speaking');
        isSpeaking = false;
        updateStatus('Ready to chat', false);
        $('#currentMessage').fadeOut();
        
        // Reset mouth
        if (mouth) {
            mouth.scale.y = 1;
        }
    };
    
    utterance.onerror = function(event) {
        console.error('❌ Speech error:', event.error);
        isSpeaking = false;
        updateStatus('Ready to chat', false);
        
        Swal.fire({
            icon: 'error',
            title: 'Speech Error',
            text: 'Failed to speak: ' + event.error,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    };
    
    // พูด!
    console.log('🎤 Speaking now...');
    window.speechSynthesis.speak(utterance);
}

// ✅ Fallback: Web Speech API (สำหรับกรณี ResponsiveVoice ไม่ทำงาน)
function useWebSpeechAPI(text) {
    if (!window.speechSynthesis) {
        console.error('Web Speech API not supported');
        Swal.fire({
            icon: 'warning',
            title: 'TTS Not Available',
            text: 'Your browser does not support text-to-speech',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
        return;
    }
    
    const utterance = new SpeechSynthesisUtterance(text);
    
    // ตรวจจับภาษา
    if (/[\u0E00-\u0E7F]/.test(text)) {
        utterance.lang = 'th-TH';
    } else if (/[\u4E00-\u9FFF]/.test(text)) {
        utterance.lang = 'zh-CN';
    } else if (/[\u3040-\u309F\u30A0-\u30FF]/.test(text)) {
        utterance.lang = 'ja-JP';
    } else if (/[\uAC00-\uD7AF]/.test(text)) {
        utterance.lang = 'ko-KR';
    } else {
        utterance.lang = 'en-US';
    }
    
    utterance.rate = 0.9;
    utterance.pitch = 1.0;
    utterance.volume = 1.0;
    
    isSpeaking = true;
    updateStatus('Speaking (fallback)...', true);
    
    utterance.onend = function() {
        isSpeaking = false;
        updateStatus('Ready to chat', false);
        $('#currentMessage').fadeOut();
        if (mouth) mouth.scale.y = 1;
    };
    
    utterance.onerror = function(e) {
        console.error('Web Speech API error:', e);
        isSpeaking = false;
        updateStatus('Ready to chat', false);
    };
    
    window.speechSynthesis.speak(utterance);
}

// ✅ Update status indicator
function updateStatus(text, speaking) {
    $('#statusText').text(text);
    
    if (speaking) {
        $('#statusDot').addClass('speaking');
    } else {
        $('#statusDot').removeClass('speaking');
    }
}

// ✅ สร้าง conversation ใหม่
function createNewChat() {
    currentConversationId = 0;
    $('.conversation-item').removeClass('active');
    $('#messageInput').val('').focus();
    $('#currentMessage').fadeOut();
    
    // Stop speaking
    if (responsiveVoice.isPlaying()) {
        responsiveVoice.cancel();
    }
    
    updateStatus('Ready to chat', false);
}

// ✅ ลบ conversation
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
                headers: {
                    'Authorization': 'Bearer ' + jwt
                },
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

// ✅ Handle Enter key
function handleKeyPress(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
    }
}

// ✅ Helper functions
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