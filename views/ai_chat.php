<?php
require_once('lib/connect.php');
global $conn;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <?php include 'inc_head.php' ?>
    <link href="app/css/index_.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            height: 100vh;
            overflow: hidden;
        }
        
        .chat-container {
            display: grid;
            grid-template-columns: 320px 1fr;
            height: calc(100vh - 70px); 
            margin-top: 70px;
            overflow: hidden;
        }
        
        /* ========== Sidebar ========== */
        .chat-sidebar {
            background: #fff;
            border-right: 1px solid #e0e0e0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            height: 100%;
            position: relative;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            flex-shrink: 0;
        }
        
        .conversations-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
            min-height: 0;
            padding-bottom: 150px; /* เว้นที่สำหรับปุ่มด้านล่าง */
        }

        /* ========== Sidebar Footer (Fixed Bottom) ========== */
        .sidebar-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: #fff;
            border-top: 1px solid #e0e0e0;
            padding: 15px 20px;
            z-index: 10;
        }

        .mode-3d-btn {
            width: 100%;
            padding: 12px 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            letter-spacing: 0.3px;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
            margin-bottom: 10px;
        }

        .mode-3d-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.5);
        }

        .edit-prompts-btn {
            width: 100%;
            padding: 12px 16px;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            border: 1px solid #3a3a3a;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            letter-spacing: 0.3px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
        }

        .edit-prompts-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s;
        }

        .edit-prompts-btn:hover::before {
            left: 100%;
        }

        .edit-prompts-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
            border-color: #4a4a4a;
            background: linear-gradient(135deg, #2d2d2d 0%, #3a3a3a 100%);
        }

        .edit-prompts-btn i {
            font-size: 14px;
        }

        /* ========== Chat Area ========== */
        .chat-main {
            display: flex;
            flex-direction: column;
            background: #fafafa;
            height: 100%;
            overflow: hidden;
            position: relative;
        }
        
        .chat-header {
            background: #fff;
            padding: 15px 30px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-shrink: 0;
            height: 70px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .ai-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .ai-info h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .ai-info p {
            font-size: 12px;
            color: #666;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto !important;
            overflow-x: hidden;
            padding: 30px;
            min-height: 0;
            max-height: 100%;
            scroll-behavior: smooth;
            position: relative;
        }
        
        .chat-messages::-webkit-scrollbar {
            width: 8px;
        }
        .chat-messages::-webkit-scrollbar-track {
            background: transparent;
        }
        .chat-messages::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 10px;
        }
        .chat-messages::-webkit-scrollbar-thumb:hover {
            background: #999;
        }

        /* Empty State */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #999;
            text-align: center;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        /* Messages */
        .message {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            width: 100%;
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .message.user {
            justify-content: flex-end;
        }
        
        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #000;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            flex-shrink: 0;
        }
        
        .message.user .message-avatar {
            background: #007bff;
            order: 2;
        }
        
        .message-content {
            background: #fff;
            padding: 12px 16px;
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            max-width: 70%;
        }
        
        .message.user .message-content {
            background: #000;
            color: #fff;
        }
        
        .message-text {
            line-height: 1.5;
            word-wrap: break-word;
        }
        
        .message-time {
            font-size: 11px;
            opacity: 0.5;
            margin-top: 4px;
        }

        /* Typing Indicator */
        .typing-indicator {
            display: none;
            padding: 0 30px 20px;
        }
        
        .typing-indicator.active {
            display: flex;
            gap: 12px;
            animation: fadeIn 0.3s ease-in;
        }
        
        .typing-dots {
            display: flex;
            gap: 4px;
            padding: 12px;
        }
        
        .typing-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #999;
            animation: typing 1.4s infinite;
        }
        
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }
        
        @keyframes typing {
            0%, 60%, 100% { opacity: 0.3; transform: translateY(0); }
            30% { opacity: 1; transform: translateY(-10px); }
        }

        /* ========== Input Area ========== */
        .chat-input {
            background: #fff;
            padding: 20px 30px;
            border-top: 1px solid #e0e0e0;
            flex-shrink: 0;
            min-height: 80px;
        }
        
        .input-wrapper {
            display: flex;
            gap: 12px;
            max-width: 900px;
            margin: 0 auto;
            align-items: flex-end;
        }
        
        .message-input {
            flex: 1;
            border: 1px solid #e0e0e0;
            border-radius: 24px;
            padding: 12px 20px;
            font-size: 14px;
            resize: none;
            max-height: 120px;
            min-height: 44px;
            font-family: inherit;
            line-height: 1.5;
        }
        
        .message-input:focus {
            outline: none;
            border-color: #000;
        }
        
        .send-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #000;
            color: #fff;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.2s;
        }
        
        .send-btn:hover {
            background: #333;
            transform: scale(1.05);
        }
        
        .send-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        /* Sidebar Styles */
        .new-chat-btn {
            width: 100%;
            padding: 12px;
            background: #000;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .new-chat-btn:hover {
            background: #333;
        }
        
        .conversation-item {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            border: 1px solid transparent;
        }
        
        .conversation-item:hover {
            background: #f5f5f5;
        }
        
        .conversation-item.active {
            background: #000;
            color: #fff;
            border-color: #000;
        }
        
        .conversation-title {
            font-weight: 500;
            margin-bottom: 4px;
            font-size: 14px;
        }
        
        .conversation-preview {
            font-size: 12px;
            opacity: 0.7;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .conversation-time {
            font-size: 11px;
            opacity: 0.5;
            margin-top: 4px;
        }
        
        .delete-conv-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(255,255,255,0.9);
            border: none;
            border-radius: 4px;
            padding: 4px 8px;
            cursor: pointer;
            opacity: 0;
            transition: all 0.2s;
            color: #dc3545;
        }
        
        .conversation-item:hover .delete-conv-btn {
            opacity: 1;
        }
        
        .conversation-item.active .delete-conv-btn {
            background: rgba(255,255,255,0.2);
            color: #fff;
        }

        @media (max-width: 968px) {
            .chat-container { grid-template-columns: 1fr; }
            .chat-sidebar { display: none; }
        }
    </style>
    <?php include 'template/header.php' ?>
</head>
<body>
    <div class="chat-container">
        <div class="chat-sidebar" id="chatSidebar">
            <div class="sidebar-header">
                <button class="new-chat-btn" onclick="createNewChat()">
                    <i class="fas fa-plus"></i> New Chat
                </button>
            </div>
            <div class="conversations-list" id="conversationsList"></div>
            
            <!-- ✅ ปุ่มสลับโหมดและ Edit Preferences -->
            <div class="sidebar-footer">
                <button class="mode-3d-btn" id="switch3DBtn">
                    <i class="fas fa-cube"></i> Switch to 3D Mode
                </button>
                <button class="edit-prompts-btn" id="editPromptsBtn">
                    <i class="fas fa-cog"></i> Edit Preferences
                </button>
            </div>
        </div>
        
        <div class="chat-main">
            <div class="chat-header" id="chatHeader" style="display: none;">
                <div class="header-left">
                    <img src="" alt="AI" class="ai-avatar" id="aiAvatar">
                    <div class="ai-info">
                        <h3 id="aiName">AI Companion</h3>
                        <p>Your Personal Perfume Assistant</p>
                    </div>
                </div>
            </div>
            
            <div class="chat-messages" id="chatMessages">
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <h3>Start a New Conversation</h3>
                    <p>Select a conversation or create a new one to begin</p>
                </div>
            </div>
            
            <div class="typing-indicator" id="typingIndicator">
                <div class="message-avatar">AI</div>
                <div class="message-content">
                    <div class="typing-dots">
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                    </div>
                </div>
            </div>
            
            <div class="chat-input">
                <div class="input-wrapper">
                    <textarea 
                        class="message-input" 
                        id="messageInput" 
                        placeholder="Type your message..."
                        rows="1"
                        onkeydown="handleKeyPress(event)"
                    ></textarea>
                    <button class="send-btn" id="sendBtn" onclick="sendMessage()">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="app/js/ai_chat.js?v=<?php echo time(); ?>"></script>
    <script>
        // ✅ Setup ปุ่มสลับหน้าพร้อม Guest Mode Support
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const lang = urlParams.get('lang') || 'th';
            const aiCode = urlParams.get('ai_code') || '';
            
            console.log('🎯 Button handlers setup:', {
                lang: lang,
                aiCode: aiCode,
                hasJWT: !!sessionStorage.getItem('jwt')
            });
            
            // ปุ่มสลับไป 3D Mode
            const switch3DBtn = document.getElementById('switch3DBtn');
            if (switch3DBtn) {
                switch3DBtn.addEventListener('click', function() {
                    let url = '?ai_chat_3d&lang=' + lang;
                    if (aiCode) {
                        url += '&ai_code=' + aiCode;
                    }
                    console.log('🔄 Switching to 3D Mode:', url);
                    window.location.href = url;
                });
            }
            
            // ปุ่ม Edit Preferences
            const editPromptsBtn = document.getElementById('editPromptsBtn');
            if (editPromptsBtn) {
                editPromptsBtn.addEventListener('click', function() {
                    let url = '?ai_edit_prompts&lang=' + lang;
                    if (aiCode) {
                        url += '&ai_code=' + aiCode;
                    }
                    console.log('⚙️ Opening Preferences:', url);
                    window.location.href = url;
                });
            }
        });
    </script>
</body>
</html>