/**
 * AI Chat 3D - Pastel Cyberpunk Sheep Character
 * ตัวละครแกะสีพาสเทลสไตล์ cyberpunk เหมือนในรูป
 */

let currentConversationId = 0;
const jwt = sessionStorage.getItem("jwt");

let scene, camera, renderer, avatar, mouth, leftEye, rightEye, leftEyePupil, rightEyePupil;
let isSpeaking = false;

$(document).ready(function() {
    if (!jwt) {
        window.location.href = '?login';
        return;
    }
    
    init3DAvatar();
    loadConversations();
    
    $('#messageInput').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
});

function init3DAvatar() {
    const canvas = document.getElementById('avatarCanvas');
    
    scene = new THREE.Scene();
    scene.background = new THREE.Color(0x1a1a2e);
    
    camera = new THREE.PerspectiveCamera(75, canvas.clientWidth / canvas.clientHeight, 0.1, 1000);
    camera.position.z = 7;
    
    renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true });
    renderer.setSize(canvas.clientWidth, canvas.clientHeight);
    renderer.shadowMap.enabled = true;
    
    // ไฟสไตล์ cyberpunk
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
    scene.add(ambientLight);
    
    const directionalLight = new THREE.DirectionalLight(0xffffff, 0.6);
    directionalLight.position.set(5, 10, 7);
    directionalLight.castShadow = true;
    scene.add(directionalLight);
    
    // Neon lights
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
    
    // 🐑 หัว - ฟ้าพาสเทลเหมือนในรูป
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
    
    // 🌸 ผมหยิกสีทองอ่อนด้านบน (เด่นชัดเหมือนในรูป)
    const curlGeometry = new THREE.SphereGeometry(0.45, 16, 16);
    const curlMaterial = new THREE.MeshPhongMaterial({ 
        color: 0xFFE4B5,
        shininess: 90
    });
    
    // วางผมเป็นทรง pompadour
    for (let i = 0; i < 10; i++) {
        const curl = new THREE.Mesh(curlGeometry, curlMaterial);
        const angle = (i / 10) * Math.PI * 2;
        const radius = 0.85;
        curl.position.set(
            Math.cos(angle) * radius,
            1.0 + Math.sin(i * 2) * 0.25,
            Math.sin(angle) * radius
        );
        curl.scale.set(0.85, 1.3, 0.85);
        character.add(curl);
    }
    
    // เพิ่มผมกลางๆ บน
    for (let i = 0; i < 5; i++) {
        const topCurl = new THREE.Mesh(curlGeometry, curlMaterial);
        const angle = (i / 5) * Math.PI * 2;
        topCurl.position.set(
            Math.cos(angle) * 0.4,
            1.4 + i * 0.1,
            Math.sin(angle) * 0.4
        );
        topCurl.scale.set(0.7, 1.4, 0.7);
        character.add(topCurl);
    }
    
    // 👁️ ตาซ้าย - ขนาดใหญ่ชัดเจน
    const eyeWhiteGeometry = new THREE.SphereGeometry(0.28, 24, 24);
    const eyeWhiteMaterial = new THREE.MeshPhongMaterial({ 
        color: 0xffffff,
        shininess: 100
    });
    
    leftEye = new THREE.Mesh(eyeWhiteGeometry, eyeWhiteMaterial);
    leftEye.position.set(-0.45, 0.35, 1.15);
    character.add(leftEye);
    
    // ม่านตาดำ
    const pupilGeometry = new THREE.SphereGeometry(0.18, 20, 20);
    const pupilMaterial = new THREE.MeshPhongMaterial({ 
        color: 0x000000,
        shininess: 80
    });
    
    leftEyePupil = new THREE.Mesh(pupilGeometry, pupilMaterial);
    leftEyePupil.position.set(-0.45, 0.35, 1.35);
    character.add(leftEyePupil);
    
    // จุดแสงในตา
    const highlightGeometry = new THREE.SphereGeometry(0.08, 12, 12);
    const highlightMaterial = new THREE.MeshBasicMaterial({ 
        color: 0xffffff
    });
    
    const leftHighlight = new THREE.Mesh(highlightGeometry, highlightMaterial);
    leftHighlight.position.set(-0.38, 0.42, 1.42);
    character.add(leftHighlight);
    
    // 👁️ ตาขวา - เหมือนตาซ้าย
    rightEye = new THREE.Mesh(eyeWhiteGeometry, eyeWhiteMaterial);
    rightEye.position.set(0.45, 0.35, 1.15);
    character.add(rightEye);
    
    rightEyePupil = new THREE.Mesh(pupilGeometry, pupilMaterial);
    rightEyePupil.position.set(0.45, 0.35, 1.35);
    character.add(rightEyePupil);
    
    const rightHighlight = new THREE.Mesh(highlightGeometry, highlightMaterial);
    rightHighlight.position.set(0.52, 0.42, 1.42);
    character.add(rightHighlight);
    
    // ขนตา (เส้นบางๆ)
    const lashGeometry = new THREE.BoxGeometry(0.02, 0.15, 0.02);
    const lashMaterial = new THREE.MeshBasicMaterial({ color: 0x000000 });
    
    for (let i = 0; i < 3; i++) {
        const leftLash = new THREE.Mesh(lashGeometry, lashMaterial);
        leftLash.position.set(-0.45 + (i - 1) * 0.12, 0.55, 1.3);
        leftLash.rotation.z = (i - 1) * 0.1;
        character.add(leftLash);
        
        const rightLash = new THREE.Mesh(lashGeometry, lashMaterial);
        rightLash.position.set(0.45 + (i - 1) * 0.12, 0.55, 1.3);
        rightLash.rotation.z = (i - 1) * 0.1;
        character.add(rightLash);
    }
    
    // 👄 ปาก - ชัดเจน ยิ้มน่ารัก
    const smileGeometry = new THREE.TorusGeometry(0.35, 0.12, 16, 100, Math.PI);
    const smileMaterial = new THREE.MeshPhongMaterial({ 
        color: 0xff69b4,
        emissive: 0xff1493,
        emissiveIntensity: 0.3,
        shininess: 80
    });
    
    mouth = new THREE.Mesh(smileGeometry, smileMaterial);
    mouth.position.set(0, -0.15, 1.15);
    mouth.rotation.x = Math.PI;
    character.add(mouth);
    
    // ลิ้น
    const tongueGeometry = new THREE.SphereGeometry(0.15, 16, 16, 0, Math.PI);
    const tongueMaterial = new THREE.MeshPhongMaterial({ 
        color: 0xff6b9d,
        shininess: 40
    });
    const tongue = new THREE.Mesh(tongueGeometry, tongueMaterial);
    tongue.position.set(0, -0.25, 1.1);
    tongue.rotation.x = -Math.PI / 2;
    tongue.scale.z = 0.5;
    character.add(tongue);
    
    // 👂 หูแกะ - ชมพูพาสเทล เหมือนในรูป
    const earGeometry = new THREE.ConeGeometry(0.35, 0.9, 8);
    const earMaterial = new THREE.MeshPhongMaterial({ 
        color: 0xffb3d9,
        shininess: 50,
        emissive: 0xffb3d9,
        emissiveIntensity: 0.1
    });
    
    const leftEar = new THREE.Mesh(earGeometry, earMaterial);
    leftEar.position.set(-1.1, 0.9, 0.2);
    leftEar.rotation.z = -0.5;
    leftEar.castShadow = true;
    character.add(leftEar);
    
    const rightEar = new THREE.Mesh(earGeometry, earMaterial);
    rightEar.position.set(1.1, 0.9, 0.2);
    rightEar.rotation.z = 0.5;
    rightEar.castShadow = true;
    character.add(rightEar);
    
    // ขนนุ่ม ๆ บนหู
    const furGeometry = new THREE.SphereGeometry(0.12, 12, 12);
    const furMaterial = new THREE.MeshPhongMaterial({ 
        color: 0xffc0e3,
        shininess: 70
    });
    
    for (let i = 0; i < 3; i++) {
        const leftFur = new THREE.Mesh(furGeometry, furMaterial);
        leftFur.position.set(-1.05, 1.1 - i * 0.2, 0.15);
        character.add(leftFur);
        
        const rightFur = new THREE.Mesh(furGeometry, furMaterial);
        rightFur.position.set(1.05, 1.1 - i * 0.2, 0.15);
        character.add(rightFur);
    }
    
    // 🦾 แขนเมทัลลิก - เหมือนในรูป
    const armGeometry = new THREE.CylinderGeometry(0.22, 0.18, 1.6, 16);
    const metalMaterial = new THREE.MeshStandardMaterial({ 
        color: 0xc0c0c0,
        metalness: 0.95,
        roughness: 0.05,
        emissive: 0x4dd0e1,
        emissiveIntensity: 0.4
    });
    
    const leftArm = new THREE.Mesh(armGeometry, metalMaterial);
    leftArm.position.set(-1.35, -1.6, 0);
    leftArm.rotation.z = 0.5;
    leftArm.castShadow = true;
    character.add(leftArm);
    
    const rightArm = new THREE.Mesh(armGeometry, metalMaterial);
    rightArm.position.set(1.35, -1.6, 0);
    rightArm.rotation.z = -0.5;
    rightArm.castShadow = true;
    character.add(rightArm);
    
    // มือโลหะ
    const handGeometry = new THREE.SphereGeometry(0.25, 16, 16);
    const leftHand = new THREE.Mesh(handGeometry, metalMaterial);
    leftHand.position.set(-1.7, -2.3, 0);
    character.add(leftHand);
    
    const rightHand = new THREE.Mesh(handGeometry, metalMaterial);
    rightHand.position.set(1.7, -2.3, 0);
    character.add(rightHand);
    
    // 👕 เสื้อยีนส์แจ็คเก็ต - สีฟ้าเข้ม
    const bodyGeometry = new THREE.CylinderGeometry(0.9, 1.1, 2.4, 32);
    const bodyMaterial = new THREE.MeshPhongMaterial({ 
        color: 0x1e3a5f,
        shininess: 25,
        emissive: 0x0d1b2a,
        emissiveIntensity: 0.2
    });
    const body = new THREE.Mesh(bodyGeometry, bodyMaterial);
    body.position.y = -2.4;
    body.castShadow = true;
    character.add(body);
    
    // 🌟 แถบ neon บนเสื้อ
    const stripGeometry = new THREE.BoxGeometry(0.12, 0.35, 0.12);
    const neonMaterial = new THREE.MeshStandardMaterial({ 
        color: 0x00ffff,
        emissive: 0x00ffff,
        emissiveIntensity: 1.2,
        metalness: 0.5,
        roughness: 0.3
    });
    
    for (let i = -2; i <= 2; i++) {
        const strip = new THREE.Mesh(stripGeometry, neonMaterial);
        strip.position.set(i * 0.35, -2.2, 1.0);
        character.add(strip);
    }
    
    // ปก jacket
    const collarGeometry = new THREE.BoxGeometry(0.4, 0.6, 0.1);
    const collarMaterial = new THREE.MeshPhongMaterial({ 
        color: 0x2c5f8d,
        shininess: 30
    });
    
    const leftCollar = new THREE.Mesh(collarGeometry, collarMaterial);
    leftCollar.position.set(-0.4, -1.5, 0.95);
    leftCollar.rotation.z = -0.3;
    character.add(leftCollar);
    
    const rightCollar = new THREE.Mesh(collarGeometry, collarMaterial);
    rightCollar.position.set(0.4, -1.5, 0.95);
    rightCollar.rotation.z = 0.3;
    character.add(rightCollar);
    
    // 🦵 ขา - ฟ้าพาสเทล
    const legGeometry = new THREE.CylinderGeometry(0.28, 0.24, 2.0, 16);
    const legMaterial = new THREE.MeshPhongMaterial({ 
        color: 0xb8e6f5,
        shininess: 35
    });
    
    const leftLeg = new THREE.Mesh(legGeometry, legMaterial);
    leftLeg.position.set(-0.45, -4.3, 0);
    leftLeg.castShadow = true;
    character.add(leftLeg);
    
    const rightLeg = new THREE.Mesh(legGeometry, legMaterial);
    rightLeg.position.set(0.45, -4.3, 0);
    rightLeg.castShadow = true;
    character.add(rightLeg);
    
    // 👟 รองเท้า - ขาวเหมือนในรูป
    const shoeGeometry = new THREE.BoxGeometry(0.5, 0.35, 0.7);
    const shoeMaterial = new THREE.MeshPhongMaterial({ 
        color: 0xf5f5f5,
        shininess: 70
    });
    
    const leftShoe = new THREE.Mesh(shoeGeometry, shoeMaterial);
    leftShoe.position.set(-0.45, -5.4, 0.15);
    leftShoe.castShadow = true;
    character.add(leftShoe);
    
    const rightShoe = new THREE.Mesh(shoeGeometry, shoeMaterial);
    rightShoe.position.set(0.45, -5.4, 0.15);
    rightShoe.castShadow = true;
    character.add(rightShoe);
    
    // เชือกรองเท้า
    const laceGeometry = new THREE.BoxGeometry(0.35, 0.03, 0.03);
    const laceMaterial = new THREE.MeshBasicMaterial({ color: 0x333333 });
    
    for (let i = 0; i < 3; i++) {
        const leftLace = new THREE.Mesh(laceGeometry, laceMaterial);
        leftLace.position.set(-0.45, -5.3 + i * 0.1, 0.4);
        character.add(leftLace);
        
        const rightLace = new THREE.Mesh(laceGeometry, laceMaterial);
        rightLace.position.set(0.45, -5.3 + i * 0.1, 0.4);
        character.add(rightLace);
    }
    
    avatar = character;
    scene.add(avatar);
}

function animate() {
    requestAnimationFrame(animate);
    
    if (!isSpeaking) {
        avatar.rotation.y = Math.sin(Date.now() * 0.0008) * 0.08;
        avatar.position.y = Math.sin(Date.now() * 0.0015) * 0.12;
        
        // กระพริบตา
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
    const canvas = document.getElementById('avatarCanvas');
    camera.aspect = canvas.clientWidth / canvas.clientHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(canvas.clientWidth, canvas.clientHeight);
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
            message: message,
            language: 'th'
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

function speakText(text) {
    let langCode = 'th';
    let detectedLang = 'Thai';
    
    if (/[\u0E00-\u0E7F]/.test(text)) {
        langCode = 'th';
        detectedLang = 'Thai';
    } else if (/[\u4E00-\u9FFF]/.test(text)) {
        langCode = 'zh-CN';
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
    
    isSpeaking = true;
    updateStatus('Speaking in ' + detectedLang + '...', true);
    
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
    
    playGoogleTTSChunks(chunks, 0, langCode);
}

let currentAudio = null;

function playGoogleTTSChunks(chunks, index, langCode) {
    if (index >= chunks.length) {
        isSpeaking = false;
        updateStatus('Ready to chat', false);
        $('#currentMessage').fadeOut();
        if (mouth) mouth.scale.y = 1;
        return;
    }
    
    const chunk = chunks[index];
    const encodedText = encodeURIComponent(chunk);
    const ttsUrl = `https://translate.google.com/translate_tts?ie=UTF-8&tl=${langCode}&client=tw-ob&q=${encodedText}`;
    
    currentAudio = new Audio();
    
    currentAudio.oncanplaythrough = function() {
        this.play().catch(err => {
            playGoogleTTSChunks(chunks, index + 1, langCode);
        });
    };
    
    currentAudio.onplay = function() {
        isSpeaking = true;
    };
    
    currentAudio.onended = function() {
        setTimeout(() => {
            playGoogleTTSChunks(chunks, index + 1, langCode);
        }, 300);
    };
    
    currentAudio.onerror = function(e) {
        fallbackToWebSpeech(chunks.join(' '), langCode);
    };
    
    currentAudio.src = ttsUrl;
    currentAudio.load();
}

function fallbackToWebSpeech(text, langCode) {
    if (!window.speechSynthesis) {
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
        isSpeaking = true;
    };
    
    utterance.onend = function() {
        isSpeaking = false;
        updateStatus('Ready to chat', false);
        $('#currentMessage').fadeOut();
        if (mouth) mouth.scale.y = 1;
    };
    
    utterance.onerror = function(event) {
        isSpeaking = false;
        updateStatus('Ready to chat', false);
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
    
    updateStatus('Ready to chat', false);
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