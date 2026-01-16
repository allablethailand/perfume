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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            overflow: hidden;
        }
        
        .chat-container-3d {
            display: grid;
            grid-template-columns: 320px 1fr;
            height: calc(100vh - 70px);
            margin-top: 70px;
            overflow: hidden;
        }
        
        /* ========== Sidebar ========== */
        .chat-sidebar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.3);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            height: 100%;
            position: relative;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
        }
        
        .conversations-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
            min-height: 0;
            padding-bottom: 150px;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            padding: 15px 20px;
            z-index: 10;
        }

        .mode-toggle-btn {
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
            margin-bottom: 10px;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        .mode-toggle-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.4);
        }

        .edit-prompts-btn {
            width: 100%;
            padding: 12px 16px;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
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
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .edit-prompts-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
        }

        /* ========== 3D Canvas Area ========== */
        .chat-main-3d {
            display: flex;
            flex-direction: column;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            height: 100%;
            overflow: hidden;
            position: relative;
        }
        
        .avatar-container {
            flex: 1;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        #avatarCanvas {
            width: 100%;
            height: 100%;
            display: block;
        }

        .avatar-overlay {
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            pointer-events: none;
            z-index: 10;
        }

        .avatar-status {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            border-radius: 12px;
            color: white;
            pointer-events: auto;
        }

        .status-indicator {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #00ff00;
            animation: pulse 2s infinite;
        }

        .status-dot.speaking {
            background: #ff6b6b;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .current-message {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            border-radius: 12px;
            color: white;
            max-width: 600px;
            pointer-events: auto;
        }

        .current-message h4 {
            font-size: 12px;
            opacity: 0.7;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .current-message p {
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
        }

        /* ========== Chat Input ========== */
        .chat-input-3d {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            padding: 20px 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            flex-shrink: 0;
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
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            padding: 12px 20px;
            font-size: 14px;
            resize: none;
            max-height: 120px;
            min-height: 44px;
            font-family: inherit;
            line-height: 1.5;
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .message-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .message-input:focus {
            outline: none;
            border-color: #667eea;
            background: rgba(255, 255, 255, 0.15);
        }
        
        .send-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            transform: scale(1.05);
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.5);
        }
        
        .send-btn:disabled {
            background: rgba(255, 255, 255, 0.2);
            cursor: not-allowed;
        }

        /* Sidebar conversation styles */
        .new-chat-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #000000 0%, #764ba2 100%);
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
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .conversation-item {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            border: 1px solid transparent;
            background: rgba(255, 255, 255, 0.5);
        }
        
        .conversation-item:hover {
            background: rgba(255, 255, 255, 0.8);
        }
        
        .conversation-item.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
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
            background: rgba(255, 255, 255, 0.9);
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
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        @media (max-width: 968px) {
            .chat-container-3d { grid-template-columns: 1fr; }
            .chat-sidebar { display: none; }
        }
    </style>
    <?php include 'template/header.php' ?>
</head>
<body>
    <div class="chat-container-3d">
        <div class="chat-sidebar">
            <div class="sidebar-header">
                <button class="new-chat-btn" onclick="createNewChat()">
                    <i class="fas fa-plus"></i> New Chat
                </button>
            </div>
            <div class="conversations-list" id="conversationsList"></div>
            
            <div class="sidebar-footer">
                <button class="mode-toggle-btn" onclick="window.location.href='?ai_chat&lang=<?php echo $_GET['lang'] ?? 'th'; ?>'">
                    <i class="fas fa-comments"></i> Switch to Chat Mode
                </button>
                <button class="edit-prompts-btn" onclick="window.location.href='?ai_edit_prompts&lang=<?php echo $_GET['lang'] ?? 'th'; ?>'">
                    <i class="fas fa-cog"></i> Edit Preferences
                </button>
            </div>
        </div>
        
        <div class="chat-main-3d">
            <div class="avatar-container">
                <canvas id="avatarCanvas"></canvas>
                
                <div class="avatar-overlay">
                    <div class="avatar-status">
                        <div class="status-indicator">
                            <div class="status-dot" id="statusDot"></div>
                            <span id="statusText">Ready to chat</span>
                        </div>
                    </div>
                    
                    <div class="current-message" id="currentMessage" style="display: none;">
                        <h4>AI is saying:</h4>
                        <p id="messageText"></p>
                    </div>
                </div>
            </div>
            
            <div class="chat-input-3d">
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="app/js/ai_chat_3d.js?v=<?php echo time(); ?>"></script>
</body>
</html>