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
let speechQueue = [];

// Speech Synthesis
const synth = window.speechSynthesis;
let currentUtterance = null;

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

// ✅ Text-to-Speech
function speakText(text) {
    // Stop current speech
    if (currentUtterance) {
        synth.cancel();
    }
    
    // Create new utterance
    currentUtterance = new SpeechSynthesisUtterance(text);
    
    // ตั้งค่าเสียง (ใช้เสียงไทยถ้ามี)
    const voices = synth.getVoices();
    const thaiVoice = voices.find(voice => voice.lang === 'th-TH') || 
                      voices.find(voice => voice.lang.startsWith('th')) ||
                      voices[0];
    
    if (thaiVoice) {
        currentUtterance.voice = thaiVoice;
    }
    
    currentUtterance.lang = 'th-TH';
    currentUtterance.rate = 0.9;
    currentUtterance.pitch = 1.1;
    currentUtterance.volume = 1;
    
    // Events
    currentUtterance.onstart = function() {
        isSpeaking = true;
        updateStatus('Speaking...', true);
    };
    
    currentUtterance.onend = function() {
        isSpeaking = false;
        updateStatus('Ready to chat', false);
        $('#currentMessage').fadeOut();
        
        // Reset mouth
        if (mouth) {
            mouth.scale.y = 1;
        }
    };
    
    currentUtterance.onerror = function(event) {
        console.error('Speech error:', event);
        isSpeaking = false;
        updateStatus('Ready to chat', false);
    };
    
    // Speak
    synth.speak(currentUtterance);
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
    if (synth.speaking) {
        synth.cancel();
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

// ✅ Load voices when available
if (synth.onvoiceschanged !== undefined) {
    synth.onvoiceschanged = function() {
        synth.getVoices();
    };
}