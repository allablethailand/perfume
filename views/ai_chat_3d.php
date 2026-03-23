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
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php include 'inc_head.php' ?>
    <link href="app/css/index_.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0a0a1e;
            height: 100vh;
            overflow: hidden;
        }
        
        .chat-container-3d {
            display: flex;
            height: 100vh;
            overflow: hidden;
            position: relative;
        }
        
        /* ========== Floating Menu Button ========== */
        .floating-menu-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
            color: white;
            border: none;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            transition: all 0.3s ease;
        }

        .floating-menu-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 30px rgba(102, 126, 234, 0.7);
        }

        .floating-menu-btn.active {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }

        /* ========== Dropdown Menu ========== */
        .dropdown-menu {
            position: fixed;
            top: 90px;
            left: 20px;
            width: 320px;
            max-height: calc(100vh - 120px);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            z-index: 999;
            opacity: 0;
            transform: translateY(-20px);
            pointer-events: none;
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .dropdown-menu.show {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        .dropdown-header {
            padding: 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
        }

        .dropdown-header h3 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #333;
        }

        .new-chat-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #000000 0%, #2d2d2d 100%);
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
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .conversations-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
            min-height: 0;
        }

        .dropdown-footer {
            padding: 15px;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
            background: rgba(255, 255, 255, 0.5);
        }

        .menu-action-btn {
            width: 100%;
            padding: 12px 16px;
            background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
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
            margin-bottom: 8px;
        }

        .menu-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.4);
        }

        .menu-action-btn.secondary {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        }

        /* ========== 3D Canvas Area ========== */
        .chat-main-3d {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #0a0a1e;
            height: 100%;
            overflow: hidden;
            position: relative;
        }

        .audio-wave-bg {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            z-index: 1;
            overflow: hidden;
            background: radial-gradient(ellipse at center, rgb(0 0 0) 0%, rgb(0 0 0) 70%);
        }

        .wave-container {
            position: absolute;
            bottom: 0; left: 0;
            width: 100%; height: 100%;
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }

        .wave-svg {
            position: absolute;
            bottom: 0; left: 0;
            width: 100%; height: 40%;
        }

        .wave-path {
            fill: none;
            stroke-width: 3;
            transition: stroke 0.5s ease;
            filter: blur(2px);
        }

        .wave-path-1 { stroke: #00d4ff; opacity: 0.3; }
        .wave-path-2 { stroke: #667eea; opacity: 0.25; }
        .wave-path-3 { stroke: #764ba2; opacity: 0.2; }

        .particles {
            position: absolute;
            width: 100%; height: 100%;
            top: 0; left: 0;
            z-index: 2;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            width: 2px; height: 2px;
            background: #00d4ff;
            border-radius: 50%;
            box-shadow: 0 0 6px rgba(0, 212, 255, 0.6);
            animation: float-particle 12s linear infinite;
        }

        @keyframes float-particle {
            0%   { transform: translateY(100vh) translateX(0); opacity: 0; }
            10%  { opacity: 0.4; }
            90%  { opacity: 0.4; }
            100% { transform: translateY(-100px) translateX(100px); opacity: 0; }
        }
        
        .avatar-container {
            flex: 1;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            z-index: 10;
        }

        #avatarCanvas {
            width: 100%;
            height: 100%;
            display: block;
        }

        /* ========== Status (top-right) ========== */
        .avatar-overlay {
            position: absolute;
            top: 20px; right: 20px;
            display: flex;
            gap: 15px;
            align-items: flex-start;
            pointer-events: none;
            z-index: 20;
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
            width: 10px; height: 10px;
            border-radius: 50%;
            background: #00ff00;
            animation: pulse 2s infinite;
        }

        .status-dot.speaking { background: #ff6b6b; }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.5; }
        }

        /* ========== ✅ Subtitle — ล่างกลาง จำกัด 2 บรรทัด ========== */
        .current-message {
            position: absolute;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%);
            width: 85%;
            max-width: 680px;
            background: rgba(0, 0, 0, 0.65);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 12px 20px;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            text-align: center;
            z-index: 20;
            pointer-events: none;

            /* ✅ จำกัดความสูง — ไม่เกิน 2 บรรทัด */
            max-height: 76px;
            overflow: hidden;
        }

        .current-message p {
            font-size: 15px;
            line-height: 1.6;
            margin: 0;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.6);

            /* ✅ ตัดข้อความเกิน 2 บรรทัด */
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* ========== Chat Input ========== */
        .chat-input-3d {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            padding: 20px 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            flex-shrink: 0;
            z-index: 20;
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

        .message-input::placeholder { color: rgba(255, 255, 255, 0.5); }
        
        .message-input:focus {
            outline: none;
            border-color: #667eea;
            background: rgba(255, 255, 255, 0.15);
        }
        
        .send-btn {
            width: 44px; height: 44px;
            border-radius: 50%;
            background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
            color: #fff;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.2s;
        }
        
        .send-btn:hover { transform: scale(1.05); box-shadow: 0 4px 16px rgba(102, 126, 234, 0.5); }
        .send-btn:disabled { background: rgba(255, 255, 255, 0.2); cursor: not-allowed; }

        /* Conversation items */
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
        
        .conversation-item:hover { background: rgba(255, 255, 255, 0.8); }
        
        .conversation-item.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }
        
        .conversation-title { font-weight: 500; margin-bottom: 4px; font-size: 14px; }
        .conversation-preview { font-size: 12px; opacity: 0.7; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .conversation-time { font-size: 11px; opacity: 0.5; margin-top: 4px; }
        
        .delete-conv-btn {
            position: absolute;
            top: 8px; right: 8px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 4px;
            padding: 4px 8px;
            cursor: pointer;
            opacity: 0;
            transition: all 0.2s;
            color: #dc3545;
        }
        
        .conversation-item:hover .delete-conv-btn { opacity: 1; }
        .conversation-item.active .delete-conv-btn { background: rgba(255, 255, 255, 0.2); color: #fff; }

        .conversations-list::-webkit-scrollbar { width: 6px; }
        .conversations-list::-webkit-scrollbar-track { background: rgba(0, 0, 0, 0.1); border-radius: 3px; }
        .conversations-list::-webkit-scrollbar-thumb { background: rgba(102, 126, 234, 0.5); border-radius: 3px; }
        .conversations-list::-webkit-scrollbar-thumb:hover { background: rgba(102, 126, 234, 0.7); }

        /* ========== Mobile ========== */
        @media (max-width: 768px) {
            .floating-menu-btn { width: 56px; height: 56px; font-size: 24px; top: 15px; left: 15px; }

            .dropdown-menu { width: calc(100vw - 30px); max-width: none; left: 15px; top: 85px; }

            /* ✅ Subtitle mobile */
            .current-message {
                bottom: 16px;
                width: 92%;
                padding: 10px 14px;
                max-height: 68px;
            }

            .current-message p {
                font-size: 13px;
                line-height: 1.55;
                -webkit-line-clamp: 2;
            }

            .avatar-status { padding: 12px 16px; font-size: 13px; }

            .chat-input-3d { padding: 15px 20px; }

            .message-input { font-size: 16px; padding: 14px 20px; min-height: 50px; border-radius: 25px; }

            .send-btn { width: 50px; height: 50px; font-size: 18px; }

            .new-chat-btn { padding: 14px; font-size: 15px; }

            .menu-action-btn { padding: 14px 18px; font-size: 15px; }

            .conversation-item { padding: 14px; font-size: 14px; }
            .conversation-title { font-size: 15px; }
            .conversation-preview { font-size: 13px; }

            .avatar-overlay { top: 15px; right: 15px; gap: 10px; flex-direction: column; align-items: flex-end; }
            .status-indicator { font-size: 13px; }
            .status-dot { width: 12px; height: 12px; }
        }

        /* ========== Monster Loading Overlay ========== */
        #monsterOverlay {
            position: fixed;
            inset: 0;
            background: #000;
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.5s ease;
        }

        #monsterOverlay.fade-out {
            opacity: 0;
            pointer-events: none;
        }

        .monster-overlay-inner {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 24px;
            text-align: center;
            padding: 40px;
        }

        .monster-spinner {
            width: 40px;
            height: 40px;
            border: 2px solid rgba(255,255,255,0.15);
            border-top-color: #fff;
            border-radius: 50%;
            animation: monster-spin 0.8s linear infinite;
        }

        @keyframes monster-spin {
            to { transform: rotate(360deg); }
        }

        .monster-loading-label {
            color: rgba(255,255,255,0.45);
            font-size: 13px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        #monsterReadyContent {
            display: none;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .monster-ready-mark {
            width: 48px;
            height: 48px;
            border: 1.5px solid #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 20px;
        }

        .monster-ready-title {
            color: #fff;
            font-size: 18px;
            font-weight: 500;
            letter-spacing: 0.02em;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            margin: 0;
        }

        .monster-ready-sub {
            color: rgba(255,255,255,0.35);
            font-size: 12px;
            letter-spacing: 0.08em;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            margin: -12px 0 0;
        }

        #monsterStartBtn {
            margin-top: 8px;
            padding: 12px 40px;
            background: #fff;
            color: #000;
            border: none;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.08em;
            cursor: pointer;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            transition: opacity 0.2s ease;
        }

        #monsterStartBtn:hover {
            opacity: 0.8;
        }
    </style>
</head>
<body>

    <!-- Monster Loading Overlay -->
    <div id="monsterOverlay">
        <div class="monster-overlay-inner">
            <!-- Loading state -->
            <div id="monsterLoadingContent">
                <div style="display:flex;flex-direction:column;align-items:center;gap:20px;">
                    <div class="monster-spinner"></div>
                    <span class="monster-loading-label">กำลังเตรียม Monster</span>
                </div>
            </div>
            <!-- Ready state -->
            <div id="monsterReadyContent">
                <div class="monster-ready-mark">✓</div>
                <p class="monster-ready-title">Monster พร้อมแล้ว</p>
                <p class="monster-ready-sub">ระบบโหลดเสร็จสมบูรณ์</p>
                <button id="monsterStartBtn" onclick="dismissMonsterOverlay()">เริ่มคุย</button>
            </div>
        </div>
    </div>

    <script>
        setTimeout(function() {
            document.getElementById('monsterLoadingContent').style.display = 'none';
            var ready = document.getElementById('monsterReadyContent');
            ready.style.display = 'flex';
        }, 1500);

        function dismissMonsterOverlay() {
            var overlay = document.getElementById('monsterOverlay');
            overlay.classList.add('fade-out');
            setTimeout(function() { overlay.style.display = 'none'; }, 500);
        }
    </script>

    <!-- Floating Menu Button -->
    <button class="floating-menu-btn" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Dropdown Menu -->
    <div class="dropdown-menu" id="dropdownMenu">
        <div class="dropdown-header">
            <h3>Conversations</h3>
            <button class="new-chat-btn" onclick="createNewChat()">
                <i class="fas fa-plus"></i> New Chat
            </button>
        </div>
        <div class="conversations-list" id="conversationsList">
            <p style="text-align: center; color: #666; padding: 20px; font-size: 13px;">Loading...</p>
        </div>
        <div class="dropdown-footer">
            <button class="menu-action-btn" onclick="goTo2DMode()">
                <i class="fas fa-comments"></i> 2D Mode
            </button>
            <button class="menu-action-btn secondary" onclick="goToPreferences()">
                <i class="fas fa-cog"></i> Preferences
            </button>
        </div>
    </div>

    <div class="chat-container-3d">
        <div class="chat-main-3d">
            <!-- Audio Wave Background -->
            <div class="audio-wave-bg">
                <div class="wave-container">
                    <svg class="wave-svg" viewBox="0 0 1200 300" preserveAspectRatio="none">
                        <path class="wave-path wave-path-1" d=""></path>
                        <path class="wave-path wave-path-2" d=""></path>
                        <path class="wave-path wave-path-3" d=""></path>
                    </svg>
                </div>
                <div class="particles" id="particlesContainer"></div>
            </div>

            <div class="avatar-container">
                <canvas id="avatarCanvas"></canvas>

                <!-- Status — บนขวา -->
                <div class="avatar-overlay">
                    <div class="avatar-status">
                        <div class="status-indicator">
                            <div class="status-dot" id="statusDot"></div>
                            <span id="statusText">Ready to chat</span>
                        </div>
                    </div>
                </div>

                <!-- ✅ Subtitle — ล่างกลาง จำกัด 2 บรรทัด -->
                <div class="current-message" id="currentMessage" style="display: none;">
                    <p id="messageText"></p>
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
    <script>
        let waveAnimationFrame;
        let waveOffset = 0;

        function createWaterWave() {
            const paths = document.querySelectorAll('.wave-path');
            
            function animateWaves() {
                const speed = window.isSpeaking ? 0.08 : 0.015; 
                waveOffset += speed;
                
                if (!window.waveIntensity) window.waveIntensity = 0;
                if (window.isSpeaking) {
                    window.waveIntensity = Math.min(window.waveIntensity + 0.1, 1);
                } else {
                    window.waveIntensity = Math.max(window.waveIntensity - 0.05, 0);
                }
                
                paths.forEach((path, index) => {
                    const points = [];
                    const baseAmplitude = 10 + (index * 5); 
                    const noise = window.isSpeaking ? (Math.random() * 15 * window.waveIntensity) : 0;
                    const amplitude = baseAmplitude + (50 * window.waveIntensity) + noise;
                    const frequency = 0.006 + (index * 0.002);
                    const offset = waveOffset * (1 + index * 0.3);
                    
                    for (let x = 0; x <= 1200; x += 15) {
                        let wave = Math.sin(x * frequency + offset);
                        if (window.isSpeaking) {
                            wave += Math.sin(x * 0.05 + offset * 2) * 0.2 * window.waveIntensity;
                            wave += (Math.random() - 0.5) * 0.1 * window.waveIntensity;
                        }
                        const edgeSoftener = Math.sin((x / 1200) * Math.PI);
                        const y = 200 + (wave * amplitude * edgeSoftener);
                        points.push(`${x},${y}`);
                    }
                    
                    const pathData = `M 0,300 L ${points.map((p, i) => {
                        if (i === 0) return `0,${p.split(',')[1]}`;
                        return p;
                    }).join(' L ')} L 1200,300 Z`;
                    
                    path.setAttribute('d', pathData);
                });
                
                waveAnimationFrame = requestAnimationFrame(animateWaves);
            }
            
            animateWaves();
        }

        function goTo2DMode() {
            const urlParams = new URLSearchParams(window.location.search);
            const lang = urlParams.get('lang') || 'th';
            const aiCode = urlParams.get('ai_code') || '';
            let url = '?ai_chat&lang=' + lang;
            if (aiCode) url += '&ai_code=' + aiCode;
            window.location.href = url;
        }
        
        function goToPreferences() {
            const urlParams = new URLSearchParams(window.location.search);
            const lang = urlParams.get('lang') || 'th';
            const aiCode = urlParams.get('ai_code') || '';
            let url = '?ai_edit_prompts&lang=' + lang;
            if (aiCode) url += '&ai_code=' + aiCode;
            window.location.href = url;
        }

        function createParticles() {
            const container = document.getElementById('particlesContainer');
            for (let i = 0; i < 25; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 12 + 's';
                particle.style.animationDuration = (10 + Math.random() * 4) + 's';
                const colors = ['#00d4ff', '#4dd0e1', '#667eea', '#80deea'];
                particle.style.background = colors[Math.floor(Math.random() * colors.length)];
                container.appendChild(particle);
            }
            
            setInterval(() => {
                const particles = document.querySelectorAll('.particle');
                particles.forEach(particle => {
                    if (window.isSpeaking) {
                        particle.style.animationDuration = (6 + Math.random() * 2) + 's';
                        particle.style.opacity = '0.7';
                    } else {
                        particle.style.animationDuration = (10 + Math.random() * 4) + 's';
                        particle.style.opacity = '0.4';
                    }
                });
            }, 100);
        }

        createWaterWave();
        createParticles();
    </script>
    <script src="/perfume/js/tracker.js" defer></script>
</body>
</html>