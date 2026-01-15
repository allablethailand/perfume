/**
 * AI Chat JavaScript
 * 
 * จัดการ UI และการสื่อสารกับ API
 */

let currentConversationId = 0;
const jwt = sessionStorage.getItem("jwt");

// ✅ โหลด conversations เมื่อเปิดหน้า
$(document).ready(function() {
    if (!jwt) {
        window.location.href = '?login';
        return;
    }
    
    loadConversations();
    
    // Auto-resize textarea
    $('#messageInput').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
});

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
        },
        error: function() {
            console.error('Failed to load conversations');
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
function loadConversation(conversationId) {
    currentConversationId = conversationId;
    
    // Update UI
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
                displayMessages(response.messages);
                $('#chatHeader').show();
                scrollToBottom();
            }
        },
        error: function() {
            Swal.fire('Error', 'Failed to load conversation', 'error');
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
            $('#typingIndicator').removeClass('active');
            
            if (response.status === 'success') {
                // แสดงคำตอบของ AI
                appendMessage('assistant', response.ai_message, null);
                
                // Update conversation ID (ถ้าเป็นการแชทใหม่)
                if (currentConversationId === 0) {
                    currentConversationId = response.conversation_id;
                    loadConversations(); // Reload sidebar
                }
                
                scrollToBottom();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
            
            // Enable input
            $('#messageInput').prop('disabled', false).focus();
            $('#sendBtn').prop('disabled', false);
        },
        error: function(xhr) {
            $('#typingIndicator').removeClass('active');
            Swal.fire('Error', 'Failed to send message', 'error');
            
            // Enable input
            $('#messageInput').prop('disabled', false).focus();
            $('#sendBtn').prop('disabled', false);
        }
    });
}

// ✅ สร้าง conversation ใหม่
function createNewChat() {
    currentConversationId = 0;
    
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
                        
                        // ถ้าลบ conversation ที่กำลังเปิดอยู่
                        if (conversationId === currentConversationId) {
                            createNewChat();
                        }
                        
                        loadConversations();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to delete conversation', 'error');
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