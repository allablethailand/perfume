/**
 * AI Chat JavaScript
 * 
 * จัดการ UI และการสื่อสารกับ API
 * รองรับทั้ง Login Mode และ Guest Mode
 */

let currentConversationId = 0;
let userCompanionId = null; // ✅ เพิ่มตัวแปรนี้
const jwt = sessionStorage.getItem("jwt");

// ✅ รับ URL Parameters
const urlParams = new URLSearchParams(window.location.search);
const aiCodeFromURL = urlParams.get('ai_code') || '';


// ✅ ตรวจสอบ Guest Mode
let isGuestMode = !jwt && aiCodeFromURL;

console.log('🚀 AI Chat Initialized:', {
    isGuestMode: isGuestMode,
    hasJWT: !!jwt,
    aiCode: aiCodeFromURL,
    lang: currentLang
});

// ✅ โหลด conversations เมื่อเปิดหน้า
$(document).ready(function() {
    // ✅ เช็คแบบผ่อนปรน - ต้องมี JWT หรือ ai_code อย่างใดอย่างหนึ่ง
    if (!jwt && !aiCodeFromURL) {
        console.warn('⚠️ No authentication found, redirecting...');
        window.location.href = '?';
        return;
    }
    
    // ✅ ลอง user_companion_id จาก sessionStorage ก่อน
    const storedCompanionId = sessionStorage.getItem('user_companion_id');
    if (storedCompanionId) {
        userCompanionId = parseInt(storedCompanionId);
        console.log('✅ Found stored user_companion_id:', userCompanionId);
        loadConversations();
    } else {
        // ✅ ถ้าไม่มี ต้องโหลดข้อมูล AI ก่อน
        console.log('⚠️ No user_companion_id in storage, loading AI info first...');
        loadCompanionInfo();
    }
    
    // Auto-resize textarea
    $('#messageInput').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    
    // ✅ Setup button click handlers
    setupButtonHandlers();
});

// ✅ โหลดข้อมูล Companion เพื่อเอา user_companion_id
function loadCompanionInfo() {
    let url = '';
    const headers = {};
    let canLoadInfo = false;

    if (isGuestMode && aiCodeFromURL) {
        // Guest Mode: ใช้ get_ai_data.php
        url = 'app/actions/get_ai_data.php?ai_code=' + aiCodeFromURL;
        canLoadInfo = true;
        console.log('🔓 Guest Mode: Loading AI info with ai_code');
    } else if (jwt) {
        // Login Mode: ใช้ check_ai_companion_status.php
        url = 'app/actions/check_ai_companion_status.php';
        headers['Authorization'] = 'Bearer ' + jwt;
        canLoadInfo = true;
        console.log('🔐 Login Mode: Loading AI info with JWT');
    }

    if (!canLoadInfo) {
        console.error('❌ No auth method available');
        window.location.href = '?';
        return;
    }

    $.ajax({
        url: url,
        type: 'GET',
        headers: headers,
        dataType: 'json',
        success: function(response) {
            console.log('✅ Companion info loaded:', response);
            
            if (response.status === 'success') {
                const data = response.ai_data || response.companion || response.data;
                
                if (!data) {
                    console.error('❌ No data in response');
                    window.location.href = '?';
                    return;
                }
                
                // ✅ เก็บ user_companion_id
                if (isGuestMode && response.companion_id) {
                    userCompanionId = response.companion_id;
                    sessionStorage.setItem('user_companion_id', userCompanionId);
                    console.log('✅ Stored companion_id from guest mode:', userCompanionId);
                } else if (response.has_companion && data.user_companion_id) {
                    userCompanionId = data.user_companion_id;
                    sessionStorage.setItem('user_companion_id', userCompanionId);
                    console.log('✅ Stored companion_id from login mode:', userCompanionId);
                }

                // ✅ เก็บข้อมูล AI
                const langCol = 'ai_name_' + currentLang;
                const aiName = data[langCol] || data.ai_name_th || data.ai_name || data.name || 'AI Companion';
                const avatarUrl = data.ai_avatar_url || data.avatar_url || data.image_url || data.idle_video_url || '';
                
                sessionStorage.setItem('ai_name', aiName);
                if (avatarUrl) {
                    sessionStorage.setItem('ai_avatar_url', avatarUrl);
                }
                
                // ✅ แสดงข้อมูล AI ใน Header
                $('#aiName').text(aiName);
                if (avatarUrl) {
                    $('#aiAvatar').attr('src', avatarUrl).on('error', function() {
                        $(this).attr('src', 'https://via.placeholder.com/40x40/000/fff?text=AI');
                    });
                }

                console.log('✅ AI info ready:', {
                    companion_id: userCompanionId,
                    name: aiName,
                    avatar: avatarUrl
                });

                // ✅ โหลด conversations ต่อ
                loadConversations();
                
            } else {
                console.error('❌ API returned error:', response.message);
                Swal.fire({
                    title: 'Error!',
                    text: response.message || 'Failed to load AI companion info',
                    icon: 'error',
                    background: '#1a1a1a',
                    color: '#fff'
                }).then(() => {
                    window.location.href = '?';
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error loading companion info:', {
                status: status,
                error: error,
                response: xhr.responseText
            });
            
            Swal.fire({
                title: 'Error!',
                text: 'Failed to load companion info',
                icon: 'error',
                background: '#1a1a1a',
                color: '#fff'
            }).then(() => {
                window.location.href = '?';
            });
        }
    });
}

// ✅ Setup ปุ่มต่างๆ
function setupButtonHandlers() {
    // ปุ่มสลับไป 3D Mode
    $('#switch3DBtn').off('click').on('click', function() {
        let url = '?ai_chat_3d&lang=' + currentLang;
        if (aiCodeFromURL) {
            url += '&ai_code=' + aiCodeFromURL;
        }
        console.log('🔄 Switching to 3D Mode:', url);
        window.location.href = url;
    });
    
    // ปุ่ม Edit Preferences
    $('#editPromptsBtn').off('click').on('click', function() {
        let url = '?ai_edit_prompts&lang=' + currentLang;
        if (aiCodeFromURL) {
            url += '&ai_code=' + aiCodeFromURL;
        }
        console.log('⚙️ Opening Preferences:', url);
        window.location.href = url;
    });
}

// ✅ โหลดรายการ conversations
function loadConversations() {
    // ✅ ต้องมี user_companion_id ก่อน
    if (!userCompanionId) {
        console.warn('⚠️ No user_companion_id yet, waiting...');
        setTimeout(loadConversations, 500);
        return;
    }
    
    let url = 'app/actions/get_chat_data.php?action=list_conversations&user_companion_id=' + userCompanionId;
    const headers = {};
    
    // ✅ Guest Mode Support
    if (isGuestMode && aiCodeFromURL) {
        url += '&ai_code=' + aiCodeFromURL;
        console.log('🔓 Guest Mode: Loading conversations with ai_code');
    } else if (jwt) {
        headers['Authorization'] = 'Bearer ' + jwt;
        console.log('🔐 Login Mode: Loading conversations with JWT');
    } else {
        console.error('❌ No authentication method available');
        window.location.href = '?';
        return;
    }
    
    console.log('📡 Loading conversations from:', url);
    
    $.ajax({
        url: url,
        type: 'GET',
        headers: headers,
        dataType: 'json',
        success: function(response) {
            console.log('✅ Conversations loaded:', response);
            
            if (response.status === 'success') {
                displayConversations(response.conversations);
            } else if (response.require_login && !isGuestMode) {
                console.warn('⚠️ Login required');
                window.location.href = '?';
            } else {
                console.error('❌ Failed to load conversations:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error loading conversations:', {
                status: status,
                error: error,
                response: xhr.responseText
            });
        }
    });
}

// ✅ แสดงรายการ conversations
function displayConversations(conversations) {
    const $list = $('#conversationsList');
    $list.empty();
    
    if (conversations.length === 0) {
        $list.html('<p style="text-align: center; color: #999; padding: 20px; font-size: 13px;">No conversations yet</p>');
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

// ✅ โหลดประวัติแชทของ conversation
// ✅ โหลดประวัติแชทของ conversation
function loadConversation(conversationId) {
    currentConversationId = conversationId;
    
    // Update UI
    $('.conversation-item').removeClass('active');
    $(`.conversation-item[data-id="${conversationId}"]`).addClass('active');
    
    let url = 'app/actions/get_chat_data.php?action=get_history&conversation_id=' + conversationId;
    const headers = {};
    
    // ✅ Guest Mode Support - ต้องส่ง user_companion_id หรือ ai_code
    if (isGuestMode) {
        if (userCompanionId) {
            url += '&user_companion_id=' + userCompanionId;
        }
        if (aiCodeFromURL) {
            url += '&ai_code=' + aiCodeFromURL;
        }
    } else if (jwt) {
        headers['Authorization'] = 'Bearer ' + jwt;
    }
    
    console.log('📖 Loading conversation:', conversationId);
    
    $.ajax({
        url: url,
        type: 'GET',
        headers: headers,
        dataType: 'json',
        success: function(response) {
            console.log('✅ Conversation loaded:', response);
            
            if (response.status === 'success') {
                displayMessages(response.messages);
                $('#chatHeader').show();
                scrollToBottom();
            } else {
                console.error('❌ Failed to load conversation:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error loading conversation:', error);
            Swal.fire({
                title: 'Error',
                text: 'Failed to load conversation',
                icon: 'error',
                background: '#1a1a1a',
                color: '#fff'
            });
        }
    });
}

// ✅ แสดงข้อความในแชท
function displayMessages(messages) {
    const $container = $('#chatMessages');
    $container.empty();
    
    if (messages.length === 0) {
        $container.html('<div class="empty-state"><h3>No messages yet</h3><p>Start the conversation!</p></div>');
        return;
    }
    
    messages.forEach(function(msg) {
        appendMessage(msg.role, msg.message, msg.timestamp);
    });
}

// ✅ เพิ่มข้อความลงในแชท
function appendMessage(role, text, timestamp) {
    const $container = $('#chatMessages');
    $container.find('.empty-state').remove();
    
    const isUser = role === 'user';
    const time = timestamp ? formatTime(timestamp) : 'Now';
    
    const $message = $(`
        <div class="message ${isUser ? 'user' : 'assistant'}">
            <div class="message-avatar">${isUser ? 'U' : 'AI'}</div>
            <div class="message-content">
                <div class="message-text">${escapeHtml(text)}</div>
                <div class="message-time">${time}</div>
            </div>
        </div>
    `);
    
    $container.append($message);
}

// ✅ ส่งข้อความ
function sendMessage() {
    const message = $('#messageInput').val().trim();
    
    if (!message) {
        return;
    }
    
    // ✅ ต้องมี user_companion_id
    if (!userCompanionId) {
        Swal.fire({
            title: 'Error',
            text: 'AI companion not loaded yet',
            icon: 'error',
            background: '#1a1a1a',
            color: '#fff'
        });
        return;
    }
    
    // Disable input
    $('#messageInput').prop('disabled', true);
    $('#sendBtn').prop('disabled', true);
    
    // แสดงข้อความของ user
    appendMessage('user', message, null);
    scrollToBottom();
    
    // Clear input
    $('#messageInput').val('').css('height', 'auto');
    
    // Show typing indicator
    $('#typingIndicator').addClass('active');
    scrollToBottom();
    
    // ✅ เตรียม headers และ data
    const headers = { 'Content-Type': 'application/json' };
    const requestData = {
        user_companion_id: userCompanionId, // ✅ เพิ่มตัวนี้
        conversation_id: currentConversationId,
        message: message,
        language: currentLang
    };
    
    // ✅ Guest Mode Support
    if (isGuestMode && aiCodeFromURL) {
        requestData.ai_code = aiCodeFromURL;
        console.log('🔓 Sending message (Guest Mode)');
    } else if (jwt) {
        headers['Authorization'] = 'Bearer ' + jwt;
        console.log('🔐 Sending message (Login Mode)');
    }
    
    console.log('📤 Sending message:', {
        user_companion_id: userCompanionId,
        conversation_id: currentConversationId,
        message_length: message.length,
        mode: isGuestMode ? 'guest' : 'login'
    });
    
    // Send to API
    $.ajax({
        url: 'app/actions/ai_chat.php',
        type: 'POST',
        headers: headers,
        data: JSON.stringify(requestData),
        dataType: 'json',
        success: function(response) {
            $('#typingIndicator').removeClass('active');
            
            console.log('✅ Message sent successfully:', response);
            
            if (response.status === 'success') {
                // แสดงคำตอบของ AI
                appendMessage('assistant', response.ai_message, null);
                
                // Update conversation ID (ถ้าเป็นการแชทใหม่)
                if (currentConversationId === 0) {
                    currentConversationId = response.conversation_id;
                    console.log('🆕 New conversation created:', currentConversationId);
                    loadConversations(); // Reload sidebar
                }
                
                scrollToBottom();
            } else {
                console.error('❌ API returned error:', response.message);
                Swal.fire({
                    title: 'Error',
                    text: response.message,
                    icon: 'error',
                    background: '#1a1a1a',
                    color: '#fff'
                });
            }
            
            // Enable input
            $('#messageInput').prop('disabled', false).focus();
            $('#sendBtn').prop('disabled', false);
        },
        error: function(xhr, status, error) {
            $('#typingIndicator').removeClass('active');
            
            console.error('❌ Error sending message:', {
                status: status,
                error: error,
                response: xhr.responseText
            });
            
            Swal.fire({
                title: 'Error',
                text: 'Failed to send message',
                icon: 'error',
                background: '#1a1a1a',
                color: '#fff'
            });
            
            // Enable input
            $('#messageInput').prop('disabled', false).focus();
            $('#sendBtn').prop('disabled', false);
        }
    });
}



// ✅ สร้าง conversation ใหม่
function createNewChat() {
    currentConversationId = 0;
    
    console.log('🆕 Creating new chat');
    
    // Clear UI
    $('#chatMessages').html(`
        <div class="empty-state">
            <i class="fas fa-comments"></i>
            <h3>New Conversation</h3>
            <p>Type a message to start chatting with your AI companion</p>
        </div>
    `);
    
    $('.conversation-item').removeClass('active');
    $('#messageInput').val('').focus();
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
        cancelButtonText: 'Cancel',
        background: '#1a1a1a',
        color: '#fff'
    }).then((result) => {
        if (result.isConfirmed) {
            let url = 'app/actions/get_chat_data.php?action=delete_conversation&conversation_id=' + conversationId;
            const headers = {};
            
            // ✅ Guest Mode Support
            if (isGuestMode && aiCodeFromURL) {
                url += '&ai_code=' + aiCodeFromURL;
            } else if (jwt) {
                headers['Authorization'] = 'Bearer ' + jwt;
            }
            
            console.log('🗑️ Deleting conversation:', conversationId);
            
            $.ajax({
                url: url,
                type: 'GET',
                headers: headers,
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        console.log('✅ Conversation deleted');
                        
                        Swal.fire({
                            title: 'Deleted!',
                            text: 'Conversation has been deleted',
                            icon: 'success',
                            background: '#1a1a1a',
                            color: '#fff',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        
                        // ถ้าลบ conversation ที่กำลังเปิดอยู่
                        if (conversationId === currentConversationId) {
                            createNewChat();
                        }
                        
                        loadConversations();
                    } else {
                        console.error('❌ Failed to delete:', response.message);
                        Swal.fire({
                            title: 'Error',
                            text: response.message,
                            icon: 'error',
                            background: '#1a1a1a',
                            color: '#fff'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ Error deleting conversation:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to delete conversation',
                        icon: 'error',
                        background: '#1a1a1a',
                        color: '#fff'
                    });
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

// ✅ Scroll to bottom
function scrollToBottom() {
    const $messages = $('#chatMessages');
    $messages.scrollTop($messages[0].scrollHeight);
}

// ✅ Helper: Format time
function formatTime(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
}

// ✅ Helper: Format time ago
function formatTimeAgo(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000); // seconds
    
    if (diff < 60) return 'Just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
    
    return date.toLocaleDateString('th-TH', { month: 'short', day: 'numeric' });
}

// ✅ Helper: Escape HTML
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