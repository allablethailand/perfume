/**
 * AI Chat JavaScript
 * ✅ emotion popup ลอยบนข้อความ + canvas chroma key + safe zone
 * ✅ AI อยู่นิ่ง ไม่เดิน + ขนาดใหญ่ขึ้น + ลบพื้นหลังขาว/ดำ
 */

let currentConversationId = 0;
let userCompanionId       = null;
const jwt                 = sessionStorage.getItem('jwt');

const urlParams     = new URLSearchParams(window.location.search);
const aiCodeFromURL = urlParams.get('ai_code') || '';

let emotionVideosMap   = {};
let emotionTimer       = null;
let emotionChromaFrame = null;

let isGuestMode = !jwt && aiCodeFromURL;

console.log('🚀 AI Chat Initialized:', { isGuestMode, hasJWT: !!jwt, aiCode: aiCodeFromURL });

// =============================================
// INJECT CSS สำหรับ popup animation
// =============================================
(function injectStyles() {
    const style = document.createElement('style');
    style.textContent = `
        @keyframes popupOut {
            from { opacity: 1; transform: scale(1); }
            to   { opacity: 0; transform: scale(0.7); }
        }
        @keyframes popupIn {
            from { opacity: 0; transform: scale(0.7) translateY(8px); }
            to   { opacity: 1; transform: scale(1)   translateY(0);   }
        }
        .emotion-popup {
            animation: popupIn 0.25s ease-out forwards;
        }
    `;
    document.head.appendChild(style);
})();

// =============================================
// INIT
// =============================================
$(document).ready(function() {
    if (!jwt && !aiCodeFromURL) {
        window.location.href = '?';
        return;
    }
    loadCompanionInfo();
    $('#messageInput').on('input', function() {
        this.style.height = 'auto';
        this.style.height = this.scrollHeight + 'px';
    });
    setupButtonHandlers();
});

// =============================================
// โหลดข้อมูล companion + emotion videos
// =============================================
function loadCompanionInfo() {
    let url   = '';
    const hdr = {};

    if (isGuestMode && aiCodeFromURL) {
        url = 'app/actions/get_ai_data.php?ai_code=' + aiCodeFromURL;
    } else if (jwt) {
        url = 'app/actions/check_ai_companion_status.php';
        hdr['Authorization'] = 'Bearer ' + jwt;
    } else {
        window.location.href = '?';
        return;
    }

    $.ajax({
        url, type: 'GET', headers: hdr, dataType: 'json',
        success: function(response) {
            if (response.status !== 'success') {
                Swal.fire({ title: 'Error!', text: response.message, icon: 'error', background: '#1a1a1a', color: '#fff' })
                    .then(() => { window.location.href = '?'; });
                return;
            }

            const data = response.ai_data || response.companion || response.data;
            if (!data) { window.location.href = '?'; return; }

            if (isGuestMode && response.companion_id) {
                userCompanionId = response.companion_id;
                sessionStorage.setItem('user_companion_id', userCompanionId);
            } else if (response.has_companion && data.user_companion_id) {
                userCompanionId = data.user_companion_id;
                sessionStorage.setItem('user_companion_id', userCompanionId);
            }

            if (data.emotion_videos_array && typeof data.emotion_videos_array === 'object') {
                emotionVideosMap = data.emotion_videos_array;
                console.log('✅ Emotion videos loaded:', Object.keys(emotionVideosMap));
            } else {
                console.warn('⚠️ emotion_videos_array not found');
            }

            const _lang     = typeof currentLang !== 'undefined' ? currentLang : 'th';
            const aiName    = data['ai_name_' + _lang] || data.ai_name_th || data.ai_name || 'AI Companion';
            const avatarUrl = data.ai_avatar_url || data.avatar_url || data.idle_video_url || '';

            sessionStorage.setItem('ai_name', aiName);
            if (avatarUrl) sessionStorage.setItem('ai_avatar_url', avatarUrl);

            $('#aiName').text(aiName);
            if (avatarUrl) {
                $('#aiAvatar').attr('src', avatarUrl).on('error', function() {
                    $(this).attr('src', 'https://via.placeholder.com/40x40/000/fff?text=AI');
                });
            }

            loadConversations();
        },
        error: function() {
            Swal.fire({ title: 'Error!', text: 'Failed to load companion info', icon: 'error', background: '#1a1a1a', color: '#fff' })
                .then(() => { window.location.href = '?'; });
        }
    });
}

// =============================================
// Canvas Chroma Key — ลบทั้งขาว + ดำ
// =============================================
function startEmotionChromaKey(video, canvas) {
    const ctx = canvas.getContext('2d');
    const W   = canvas.width;
    const H   = canvas.height;

    const WHITE_THRESHOLD = 210;
    const NEUTRAL_DIFF    = 20;

    // Safe zone — โซนกลางที่ไม่ลบพื้นหลังเด็ดขาด
    const SAFE_LEFT   = W * 1;
    const SAFE_RIGHT  = W * 1;
    const SAFE_TOP    = H * 1;
    const SAFE_BOTTOM = H * 1;

    function processFrame() {
        if (video.paused || video.ended) return;
        try {
            ctx.drawImage(video, 0, 0, W, H);
            const frame = ctx.getImageData(0, 0, W, H);
            const data  = frame.data;

            for (let i = 0; i < data.length; i += 4) {
                const px = i / 4;
                const x  = px % W;
                const y  = Math.floor(px / W);

                if (x >= SAFE_LEFT && x <= SAFE_RIGHT &&
                    y >= SAFE_TOP  && y <= SAFE_BOTTOM) {
                    continue;
                }

                const r    = data[i];
                const g    = data[i + 1];
                const b    = data[i + 2];
                const maxC = Math.max(r, g, b);
                const minC = Math.min(r, g, b);
                const diff = maxC - minC;

                // ลบพื้นขาว/เทาอ่อน
                if (maxC >= WHITE_THRESHOLD && diff < NEUTRAL_DIFF) {
                    data[i + 3] = 0;
                    continue;
                }
                // ลบพื้นเขียว (green screen)
                if (g > 80 && g > r * 1.4 && g > b * 1.4) {
                    data[i + 3] = 0;
                }
            }
            ctx.putImageData(frame, 0, 0);
        } catch(e) {
            console.warn('⚠️ Chroma key error:', e.message);
            stopEmotionChromaKey();
            return;
        }
        emotionChromaFrame = requestAnimationFrame(processFrame);
    }
    processFrame();
}

function stopEmotionChromaKey() {
    if (emotionChromaFrame) {
        cancelAnimationFrame(emotionChromaFrame);
        emotionChromaFrame = null;
    }
}

// =============================================
// ✅ Emotion Popup — ลอยบน message-content
//    อยู่นิ่งซ้ายมือ + ขนาดใหญ่ขึ้น + ลบพื้นขาว/ดำ
// =============================================
function playEmotionInBubble(emotion) {
    const $lastMsg = $('.message.assistant').last();
    if ($lastMsg.length === 0) return;

    const $content = $lastMsg.find('.message-content');
    if ($content.length === 0) return;

    const urls = emotionVideosMap[emotion] || emotionVideosMap['calm'];
    if (!urls || urls.length === 0) {
        console.log('⚠️ No emotion video for:', emotion);
        return;
    }

    // เคลียร์ของเก่า
    if (emotionTimer) { clearTimeout(emotionTimer); emotionTimer = null; }
    stopEmotionChromaKey();
    $('.emotion-popup').remove();

    const fileUrl = urls[Math.floor(Math.random() * urls.length)];
    const ext     = fileUrl.split('.').pop().split('?')[0].toLowerCase();
    const isGif   = (ext === 'gif');

    // ✅ ขนาด 1:1 square
    const POPUP_W = 100;
    const POPUP_H = 100;

    console.log('🎭 Emotion popup:', emotion, '|', fileUrl);

    $content.css('position', 'relative');

    const $popup = $('<div class="emotion-popup"></div>').css({
        position:      'absolute',
        top:           (-POPUP_H - 6) + 'px',
        left:          '0px',          // ✅ อยู่ซ้ายมือตายตัว ไม่ขยับ
        width:         POPUP_W + 'px',
        height:        POPUP_H + 'px',
        borderRadius:  '8px',
        overflow:      'hidden',
        zIndex:        100,
        pointerEvents: 'none',
        background:    'transparent',
        boxShadow:     'none',
    });

    if (isGif) {
        const $img = $('<img>').attr('src', fileUrl).css({
            width: '100%', height: '100%', objectFit: 'contain',
            display: 'block', background: 'transparent',
            mixBlendMode: 'multiply'
        });
        $popup.append($img);
        $content.append($popup);

    } else {
        // Video + canvas chroma key
        const $canvas = $('<canvas>').attr({ width: POPUP_W, height: POPUP_H }).css({
            width: '100%', height: '100%', display: 'block'
        });
        const $vid = $('<video>').attr({ muted: true, playsinline: true, crossorigin: 'anonymous' })
            .css({ display: 'none', position: 'absolute', top: '-9999px' });

        $popup.append($canvas).append($vid);
        $content.append($popup);

        const vid = $vid[0];
        vid.src = fileUrl;
        vid.oncanplay = function() {
            this.play().catch(() => {});
            startEmotionChromaKey(this, $canvas[0]);
        };
        vid.load();
    }

    // ซ่อนหลัง 5 วินาที
    function hidePopup() {
        stopEmotionChromaKey();
        $popup.css({ animation: 'popupOut 0.25s ease-in forwards' });
        setTimeout(function() { $popup.remove(); }, 300);
    }
    emotionTimer = setTimeout(hidePopup, 5000);
}

// =============================================
// Setup ปุ่ม
// =============================================
function setupButtonHandlers() {
    const _lang = typeof currentLang !== 'undefined' ? currentLang : 'th';

    $('#switch3DBtn').off('click').on('click', function() {
        let url = '?ai_chat_3d&lang=' + _lang;
        if (aiCodeFromURL) url += '&ai_code=' + aiCodeFromURL;
        window.location.href = url;
    });

    $('#editPromptsBtn').off('click').on('click', function() {
        let url = '?ai_edit_prompts&lang=' + _lang;
        if (aiCodeFromURL) url += '&ai_code=' + aiCodeFromURL;
        window.location.href = url;
    });
}

// =============================================
// Load conversations
// =============================================
function loadConversations() {
    if (!userCompanionId) { setTimeout(loadConversations, 500); return; }

    let url   = 'app/actions/get_chat_data.php?action=list_conversations&user_companion_id=' + userCompanionId;
    const hdr = {};

    if (isGuestMode && aiCodeFromURL) { url += '&ai_code=' + aiCodeFromURL; }
    else if (jwt) { hdr['Authorization'] = 'Bearer ' + jwt; }
    else { window.location.href = '?'; return; }

    $.ajax({
        url, type: 'GET', headers: hdr, dataType: 'json',
        success: function(r) {
            if (r.status === 'success') displayConversations(r.conversations);
            else if (r.require_login && !isGuestMode) window.location.href = '?';
        }
    });
}

function displayConversations(conversations) {
    const $list = $('#conversationsList');
    $list.empty();

    if (!conversations.length) {
        $list.html('<p style="text-align:center;color:#999;padding:20px;font-size:13px;">No conversations yet</p>');
        return;
    }

    conversations.forEach(function(conv) {
        const active = conv.conversation_id === currentConversationId ? 'active' : '';
        const $item  = $(`
            <div class="conversation-item ${active}" data-id="${conv.conversation_id}">
                <div class="conversation-title">${escapeHtml(conv.title)}</div>
                <div class="conversation-preview">${escapeHtml(conv.last_message)}</div>
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
    $('.conversation-item').removeClass('active');
    $(`.conversation-item[data-id="${conversationId}"]`).addClass('active');

    let url   = 'app/actions/get_chat_data.php?action=get_history&conversation_id=' + conversationId;
    const hdr = {};

    if (isGuestMode) {
        if (userCompanionId) url += '&user_companion_id=' + userCompanionId;
        if (aiCodeFromURL)   url += '&ai_code=' + aiCodeFromURL;
    } else if (jwt) {
        hdr['Authorization'] = 'Bearer ' + jwt;
    }

    $.ajax({
        url, type: 'GET', headers: hdr, dataType: 'json',
        success: function(r) {
            if (r.status === 'success') {
                displayMessages(r.messages);
                $('#chatHeader').show();
                scrollToBottom();
            }
        },
        error: function() {
            Swal.fire({ title: 'Error', text: 'Failed to load conversation', icon: 'error', background: '#1a1a1a', color: '#fff' });
        }
    });
}

function displayMessages(messages) {
    const $c = $('#chatMessages');
    $c.empty();
    if (!messages.length) {
        $c.html('<div class="empty-state"><h3>No messages yet</h3><p>Start the conversation!</p></div>');
        return;
    }
    messages.forEach(function(msg) { appendMessage(msg.role, msg.message, msg.timestamp); });
}

function appendMessage(role, text, timestamp) {
    const $c     = $('#chatMessages');
    $c.find('.empty-state').remove();
    const isUser = role === 'user';
    const time   = timestamp ? formatTime(timestamp) : 'Now';
    const label  = isUser ? 'U' : 'AI';

    const $msg = $(`
        <div class="message ${isUser ? 'user' : 'assistant'}">
            <div class="message-avatar-wrap">
                <div class="message-avatar">
                    <span class="avatar-label">${label}</span>
                </div>
            </div>
            <div class="message-content">
                <div class="message-text">${escapeHtml(text)}</div>
                <div class="message-time">${time}</div>
            </div>
        </div>
    `);
    $c.append($msg);
}

// =============================================
// Send message
// =============================================
function sendMessage() {
    const message = $('#messageInput').val().trim();
    if (!message) return;

    if (!userCompanionId) {
        Swal.fire({ title: 'Error', text: 'AI companion not loaded yet', icon: 'error', background: '#1a1a1a', color: '#fff' });
        return;
    }

    $('#messageInput').prop('disabled', true);
    $('#sendBtn').prop('disabled', true);

    appendMessage('user', message, null);
    scrollToBottom();
    $('#messageInput').val('').css('height', 'auto');
    $('#typingIndicator').addClass('active');
    scrollToBottom();

    const hdr  = { 'Content-Type': 'application/json' };
    const body = {
        user_companion_id: userCompanionId,
        conversation_id:   currentConversationId,
        message:           message,
        language:          typeof currentLang !== 'undefined' ? currentLang : 'th'
    };

    if (isGuestMode && aiCodeFromURL) { body.ai_code = aiCodeFromURL; }
    else if (jwt) { hdr['Authorization'] = 'Bearer ' + jwt; }

    $.ajax({
        url:      'app/actions/ai_chat.php',
        type:     'POST',
        headers:  hdr,
        data:     JSON.stringify(body),
        dataType: 'json',
        success: function(r) {
            $('#typingIndicator').removeClass('active');

            if (r.status === 'success') {
                appendMessage('assistant', r.ai_message, null);
                scrollToBottom();
                $('#chatHeader').show();

                if (r.ai_emotion) {
                    console.log('🎭 AI Emotion:', r.ai_emotion);
                    playEmotionInBubble(r.ai_emotion);
                }

                if (currentConversationId === 0) {
                    currentConversationId = r.conversation_id;
                    loadConversations();
                }
            } else {
                Swal.fire({ title: 'Error', text: r.message, icon: 'error', background: '#1a1a1a', color: '#fff' });
            }

            $('#messageInput').prop('disabled', false).focus();
            $('#sendBtn').prop('disabled', false);
        },
        error: function() {
            $('#typingIndicator').removeClass('active');
            Swal.fire({ title: 'Error', text: 'Failed to send message', icon: 'error', background: '#1a1a1a', color: '#fff' });
            $('#messageInput').prop('disabled', false).focus();
            $('#sendBtn').prop('disabled', false);
        }
    });
}

// =============================================
// New chat / Delete
// =============================================
function createNewChat() {
    currentConversationId = 0;
    if (emotionTimer) { clearTimeout(emotionTimer); emotionTimer = null; }
    stopEmotionChromaKey();
    $('.emotion-popup').remove();

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

function deleteConversation(conversationId, event) {
    event.stopPropagation();

    Swal.fire({
        title: 'Delete Conversation?', text: 'This action cannot be undone', icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#dc3545',
        confirmButtonText: 'Delete', cancelButtonText: 'Cancel',
        background: '#1a1a1a', color: '#fff'
    }).then((result) => {
        if (!result.isConfirmed) return;

        let url   = 'app/actions/get_chat_data.php?action=delete_conversation&conversation_id=' + conversationId;
        const hdr = {};

        if (isGuestMode && aiCodeFromURL) { url += '&ai_code=' + aiCodeFromURL; }
        else if (jwt) { hdr['Authorization'] = 'Bearer ' + jwt; }

        $.ajax({
            url, type: 'GET', headers: hdr, dataType: 'json',
            success: function(r) {
                if (r.status === 'success') {
                    Swal.fire({ title: 'Deleted!', text: 'Conversation deleted', icon: 'success', background: '#1a1a1a', color: '#fff', timer: 1500, showConfirmButton: false });
                    if (conversationId === currentConversationId) createNewChat();
                    loadConversations();
                } else {
                    Swal.fire({ title: 'Error', text: r.message, icon: 'error', background: '#1a1a1a', color: '#fff' });
                }
            },
            error: function() {
                Swal.fire({ title: 'Error', text: 'Failed to delete', icon: 'error', background: '#1a1a1a', color: '#fff' });
            }
        });
    });
}

// =============================================
// Helpers
// =============================================
function handleKeyPress(event) {
    if (event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); sendMessage(); }
}

function scrollToBottom() {
    const $m = $('#chatMessages');
    $m.scrollTop($m[0].scrollHeight);
}

function formatTime(ts) {
    return new Date(ts).toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
}

function formatTimeAgo(ts) {
    const diff = Math.floor((new Date() - new Date(ts)) / 1000);
    if (diff < 60)     return 'Just now';
    if (diff < 3600)   return Math.floor(diff / 60)    + 'm ago';
    if (diff < 86400)  return Math.floor(diff / 3600)  + 'h ago';
    if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
    return new Date(ts).toLocaleDateString('th-TH', { month: 'short', day: 'numeric' });
}

function escapeHtml(text) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return (text || '').replace(/[&<>"']/g, m => map[m]);
}