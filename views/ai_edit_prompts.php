<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Your Preferences - AI Companion</title>
    
    <link rel="icon" type="image/x-icon" href="/perfume//public/product_images/696089dc2eba5_1767934428.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
            min-height: 100vh;
            color: #fff;
            display: flex;
        }

        /* AI Avatar Sidebar */
        .ai-sidebar {
            width: 400px;
            background: #000;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
        }

        .ai-avatar-container {
            position: relative;
            margin-bottom: 30px;
        }

        .ai-avatar-circle {
            width: 280px;
            height: 280px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid rgba(120, 119, 198, 0.3);
            box-shadow: 0 20px 60px rgba(120, 119, 198, 0.4);
            position: relative;
        }

        .ai-avatar-circle::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(120, 119, 198, 0.3), transparent);
            animation: rotate 3s linear infinite;
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .ai-avatar-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: relative;
            z-index: 1;
        }

        .ai-info {
            text-align: center;
        }

        .ai-name-sidebar {
            font-size: 28px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }

        .ai-status {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.5);
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Main Content */
        .main-content {
            margin-left: 400px;
            flex: 1;
            padding: 60px 80px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .header-section {
            margin-bottom: 40px;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            margin-bottom: 20px;
        }

        .back-button:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }

        h1 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .subtitle {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.6);
        }

        /* Language Selection */
        .language-section {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 24px;
            transition: all 0.3s;
        }

        .language-section:hover {
            border-color: rgba(120, 119, 198, 0.3);
            background: rgba(255, 255, 255, 0.05);
        }

        .language-section-title {
            font-size: 18px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .language-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 16px;
        }

        .language-option {
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }

        .language-option:hover {
            border-color: #7877c6;
            background: rgba(120, 119, 198, 0.1);
            transform: translateY(-4px);
        }

        .language-option.selected {
            border-color: #7877c6;
            background: rgba(120, 119, 198, 0.2);
            box-shadow: 0 8px 20px rgba(120, 119, 198, 0.3);
        }

        .language-flag {
            width: 48px;
            height: 36px;
            border-radius: 4px;
            object-fit: cover;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .language-name {
            font-size: 14px;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.8);
        }

        .language-option.selected .language-name {
            color: #fff;
        }

        /* Questions List */
        .questions-list {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .question-item {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 30px;
            transition: all 0.3s;
        }

        .question-item:hover {
            border-color: rgba(120, 119, 198, 0.3);
            background: rgba(255, 255, 255, 0.05);
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .question-title {
            font-size: 18px;
            font-weight: 600;
            color: #fff;
            flex: 1;
            line-height: 1.4;
        }

        .edit-button {
            padding: 8px 16px;
            background: linear-gradient(135deg, #7877c6 0%, #a8a7e5 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .edit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(120, 119, 198, 0.4);
        }

        .current-answer {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 12px;
            padding: 16px 20px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 15px;
            line-height: 1.6;
        }

        .answer-label {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        /* Edit Form */
        .edit-form {
            display: none;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .edit-form.active {
            display: block;
        }

        .choices-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 16px;
        }

        .choice-option {
            padding: 16px 20px;
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 15px;
            color: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .choice-option:hover {
            border-color: #7877c6;
            background: rgba(120, 119, 198, 0.1);
        }

        .choice-option.selected {
            border-color: #7877c6;
            background: rgba(120, 119, 198, 0.2);
            color: #fff;
        }

        .choice-radio {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.3);
            position: relative;
            flex-shrink: 0;
            transition: all 0.3s;
        }

        .choice-option.selected .choice-radio {
            border-color: #7877c6;
            background: #7877c6;
        }

        .choice-option.selected .choice-radio::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: white;
        }

        .text-input {
            width: 100%;
            padding: 16px 20px;
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            color: #fff;
            transition: all 0.3s;
            resize: vertical;
            min-height: 120px;
            margin-bottom: 16px;
        }

        .text-input:focus {
            outline: none;
            border-color: #7877c6;
            background: rgba(255, 255, 255, 0.05);
        }

        .text-input::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        .scale-container {
            margin-bottom: 16px;
        }

        .scale-labels {
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6);
        }

        .scale-options {
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        .scale-option {
            flex: 1;
            aspect-ratio: 1;
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            transition: all 0.3s;
        }

        .scale-option:hover {
            border-color: #7877c6;
            background: rgba(120, 119, 198, 0.1);
        }

        .scale-option.selected {
            border-color: #7877c6;
            background: rgba(120, 119, 198, 0.3);
            color: #fff;
        }

        .form-buttons {
            display: flex;
            gap: 12px;
        }

        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-save {
            background: linear-gradient(135deg, #7877c6 0%, #a8a7e5 100%);
            color: white;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(120, 119, 198, 0.4);
        }

        .btn-cancel {
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.8);
            border: 2px solid rgba(255, 255, 255, 0.1);
        }

        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Loading */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            backdrop-filter: blur(10px);
        }

        .loading-overlay.active {
            display: flex;
        }

        .loading-content {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 60px;
            border-radius: 24px;
            text-align: center;
        }

        .loading-content i {
            font-size: 56px;
            color: #7877c6;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            margin-top: 24px;
            font-size: 18px;
            color: #fff;
            font-weight: 500;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .ai-sidebar {
                width: 320px;
            }

            .main-content {
                margin-left: 320px;
                padding: 40px 60px;
            }

            .ai-avatar-circle {
                width: 220px;
                height: 220px;
            }
        }

        @media (max-width: 992px) {
            body {
                flex-direction: column;
            }

            .ai-sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 30px;
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }

            .ai-avatar-circle {
                width: 180px;
                height: 180px;
                margin-bottom: 20px;
            }

            .ai-name-sidebar {
                font-size: 24px;
            }

            .main-content {
                margin-left: 0;
                padding: 40px 30px;
            }

            .language-grid {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            }
        }

        @media (max-width: 600px) {
            h1 {
                font-size: 28px;
            }

            .question-title {
                font-size: 16px;
            }

            .question-item {
                padding: 20px;
            }

            .language-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- AI Avatar Sidebar -->
    <div class="ai-sidebar">
        <div class="ai-avatar-container">
            <div class="ai-avatar-circle">
                <img src="" alt="AI Avatar" id="aiAvatarSidebar">
            </div>
        </div>
        <div class="ai-info">
            <h2 class="ai-name-sidebar" id="aiNameSidebar">AI Companion</h2>
            <div class="ai-status">
                <span class="status-dot"></span>
                <span>Active</span>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header-section">
            <a href="#" class="back-button" id="backButton">
                <i class="fas fa-arrow-left"></i> Back to Chat
            </a>
            <h1>Edit Your Preferences</h1>
            <p class="subtitle">Update your answers to personalize your AI companion</p>
        </div>

        <!-- Language Selection -->
        <div class="language-section">
            <div class="language-section-title">
                <i class="fas fa-globe"></i> Preferred Language
            </div>
            <div class="language-grid">
                <div class="language-option" data-lang="th">
                    <img src="https://flagcdn.com/w320/th.png" class="language-flag" alt="Thai">
                    <span class="language-name">ไทย</span>
                </div>
                <div class="language-option" data-lang="en">
                    <img src="https://flagcdn.com/w320/gb.png" class="language-flag" alt="English">
                    <span class="language-name">English</span>
                </div>
                <div class="language-option" data-lang="cn">
                    <img src="https://flagcdn.com/w320/cn.png" class="language-flag" alt="Chinese">
                    <span class="language-name">中文</span>
                </div>
                <div class="language-option" data-lang="jp">
                    <img src="https://flagcdn.com/w320/jp.png" class="language-flag" alt="Japanese">
                    <span class="language-name">日本語</span>
                </div>
                <div class="language-option" data-lang="kr">
                    <img src="https://flagcdn.com/w320/kr.png" class="language-flag" alt="Korean">
                    <span class="language-name">한국어</span>
                </div>
            </div>
        </div>

        <div class="questions-list" id="questionsList">
            <!-- Questions will be loaded here -->
        </div>
    </div>

    <!-- Loading -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <i class="fas fa-spinner"></i>
            <div class="loading-text">Saving changes...</div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const lang = urlParams.get('lang') || 'th';
        let companionId = null;
        let currentPreferredLang = 'th';

        const jwt = sessionStorage.getItem('jwt');
        if (!jwt) {
            Swal.fire('Error!', 'Please login first', 'error').then(() => {
                window.location.href = '?';
            });
        }

        $(document).ready(function() {
            loadCompanionInfo();
            loadQuestionsAndAnswers();

            $('#backButton').on('click', function(e) {
                e.preventDefault();
                window.location.href = '?ai_chat&lang=' + lang;
            });

            // Language selection
            $('.language-option').on('click', function() {
                const selectedLang = $(this).data('lang');
                updatePreferredLanguage(selectedLang);
            });
        });

        function loadCompanionInfo() {
            $.ajax({
                url: 'app/actions/check_ai_companion_status.php',
                type: 'GET',
                headers: { 'Authorization': 'Bearer ' + jwt },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.has_companion) {
                        companionId = response.data.user_companion_id;
                        currentPreferredLang = response.data.preferred_language || 'th';
                        
                        // Set selected language
                        $('.language-option').removeClass('selected');
                        $(`.language-option[data-lang="${currentPreferredLang}"]`).addClass('selected');
                        
                        const langCol = 'ai_name_' + lang;
                        $('#aiNameSidebar').text(response.data[langCol] || response.data.ai_name_th);
                        
                        if (response.data.ai_avatar_url) {
                            $('#aiAvatarSidebar').attr('src', response.data.ai_avatar_url);
                        }
                    } else {
                        Swal.fire('Error!', 'No AI companion found', 'error').then(() => {
                            window.location.href = '?';
                        });
                    }
                }
            });
        }

        function updatePreferredLanguage(selectedLang) {
            if (selectedLang === currentPreferredLang) {
                return; // No change
            }

            const cId = companionId || urlParams.get('companion_id');

            $('#loadingOverlay .loading-text').text('Updating language...');
            $('#loadingOverlay').addClass('active');

            $.ajax({
                url: 'app/actions/update_preferred_language.php',
                type: 'POST',
                headers: { 'Authorization': 'Bearer ' + jwt },
                data: {
                    user_companion_id: cId,
                    preferred_language: selectedLang
                },
                dataType: 'json',
                success: function(response) {
                    $('#loadingOverlay').removeClass('active');
                    $('#loadingOverlay .loading-text').text('Saving changes...');
                    
                    if (response.status === 'success') {
                        currentPreferredLang = selectedLang;
                        $('.language-option').removeClass('selected');
                        $(`.language-option[data-lang="${selectedLang}"]`).addClass('selected');
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            text: 'Language preference updated successfully',
                            timer: 1500,
                            showConfirmButton: false,
                            background: '#1a1a1a',
                            color: '#fff'
                        });
                    } else {
                        Swal.fire('Error!', response.message || 'Failed to update language', 'error');
                        // Revert selection
                        $('.language-option').removeClass('selected');
                        $(`.language-option[data-lang="${currentPreferredLang}"]`).addClass('selected');
                    }
                },
                error: function() {
                    $('#loadingOverlay').removeClass('active');
                    $('#loadingOverlay .loading-text').text('Saving changes...');
                    Swal.fire('Error!', 'Failed to update language', 'error');
                    // Revert selection
                    $('.language-option').removeClass('selected');
                    $(`.language-option[data-lang="${currentPreferredLang}"]`).addClass('selected');
                }
            });
        }

        function loadQuestionsAndAnswers() {
            if (!companionId && !urlParams.has('companion_id')) {
                // Wait for companionId from loadCompanionInfo
                setTimeout(loadQuestionsAndAnswers, 500);
                return;
            }

            const cId = companionId || urlParams.get('companion_id');

            $.ajax({
                url: 'app/actions/get_user_answers.php',
                type: 'GET',
                headers: { 'Authorization': 'Bearer ' + jwt },
                data: { user_companion_id: cId, lang: lang },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        displayQuestionsAndAnswers(response.data);
                    } else {
                        Swal.fire('Error!', 'Failed to load questions', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to load questions', 'error');
                }
            });
        }

        function displayQuestionsAndAnswers(data) {
            $('#questionsList').empty();

            data.forEach(item => {
                const questionHtml = createQuestionItem(item);
                $('#questionsList').append(questionHtml);
            });

            // Attach event listeners
            $('.edit-button').on('click', function() {
                const questionId = $(this).data('question-id');
                toggleEditForm(questionId);
            });

            $('.choice-option').on('click', function() {
                const questionId = $(this).data('question-id');
                $(`.choice-option[data-question-id="${questionId}"]`).removeClass('selected');
                $(this).addClass('selected');
            });

            $('.scale-option').on('click', function() {
                const questionId = $(this).data('question-id');
                $(`.scale-option[data-question-id="${questionId}"]`).removeClass('selected');
                $(this).addClass('selected');
            });

            $('.btn-cancel').on('click', function() {
                const questionId = $(this).data('question-id');
                toggleEditForm(questionId);
            });

            $('.btn-save').on('click', function() {
                const questionId = $(this).data('question-id');
                saveAnswer(questionId);
            });
        }

        function createQuestionItem(item) {
            const langCol = 'question_text_' + lang;
            const questionText = item[langCol] || item.question_text_th;
            
            let currentAnswerHtml = '';
            if (item.question_type === 'choice' && item.choice_text) {
                const choiceCol = 'choice_text_' + lang;
                currentAnswerHtml = item[choiceCol] || item.choice_text;
            } else if (item.question_type === 'text' && item.text_answer) {
                currentAnswerHtml = item.text_answer;
            } else if (item.question_type === 'scale' && item.scale_value) {
                currentAnswerHtml = `Scale: ${item.scale_value}/5`;
            }

            let inputHtml = '';
            if (item.question_type === 'choice') {
                inputHtml = '<div class="choices-container">';
                if (item.choices && item.choices.length > 0) {
                    item.choices.forEach(choice => {
                        const choiceTextCol = 'choice_text_' + lang;
                        const isSelected = choice.choice_id === item.selected_choice_id ? 'selected' : '';
                        inputHtml += `
                            <div class="choice-option ${isSelected}" data-question-id="${item.question_id}" data-choice-id="${choice.choice_id}">
                                <div class="choice-radio"></div>
                                <span>${choice[choiceTextCol] || choice.choice_text_th}</span>
                            </div>
                        `;
                    });
                }
                inputHtml += '</div>';
            } else if (item.question_type === 'text') {
                inputHtml = `
                    <textarea class="text-input" id="text_${item.question_id}" placeholder="Type your answer...">${item.text_answer || ''}</textarea>
                `;
            } else if (item.question_type === 'scale') {
                inputHtml = `
                    <div class="scale-container">
                        <div class="scale-options">
                `;
                for (let i = 1; i <= 5; i++) {
                    const isSelected = i === parseInt(item.scale_value) ? 'selected' : '';
                    inputHtml += `<div class="scale-option ${isSelected}" data-question-id="${item.question_id}" data-value="${i}">${i}</div>`;
                }
                inputHtml += `
                        </div>
                    </div>
                `;
            }

            return `
                <div class="question-item" id="question_${item.question_id}">
                    <div class="question-header">
                        <div class="question-title">${questionText}</div>
                        <button class="edit-button" data-question-id="${item.question_id}">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </div>
                    <div>
                        <div class="answer-label">Current Answer:</div>
                        <div class="current-answer">${currentAnswerHtml}</div>
                    </div>
                    <div class="edit-form" id="edit_form_${item.question_id}">
                        ${inputHtml}
                        <div class="form-buttons">
                            <button class="btn btn-cancel" data-question-id="${item.question_id}">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button class="btn btn-save" data-question-id="${item.question_id}">
                                <i class="fas fa-check"></i> Save
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }

        function toggleEditForm(questionId) {
            $(`#edit_form_${questionId}`).toggleClass('active');
        }

        function saveAnswer(questionId) {
            const cId = companionId || urlParams.get('companion_id');
            const question = $(`#question_${questionId}`);
            const questionType = question.find('.choice-option').length > 0 ? 'choice' : 
                                 question.find('.text-input').length > 0 ? 'text' : 'scale';

            let answerData = {
                user_companion_id: cId,
                question_id: questionId
            };

            if (questionType === 'choice') {
                const selectedChoice = question.find('.choice-option.selected');
                if (!selectedChoice.length) {
                    Swal.fire('Warning!', 'Please select an answer', 'warning');
                    return;
                }
                answerData.choice_id = selectedChoice.data('choice-id');
            } else if (questionType === 'text') {
                const textValue = question.find('.text-input').val().trim();
                if (!textValue) {
                    Swal.fire('Warning!', 'Please enter an answer', 'warning');
                    return;
                }
                answerData.text_answer = textValue;
            } else if (questionType === 'scale') {
                const selectedScale = question.find('.scale-option.selected');
                if (!selectedScale.length) {
                    Swal.fire('Warning!', 'Please select a scale value', 'warning');
                    return;
                }
                answerData.scale_value = selectedScale.data('value');
            }

            $('#loadingOverlay').addClass('active');

            $.ajax({
                url: 'app/actions/update_single_answer.php',
                type: 'POST',
                headers: { 'Authorization': 'Bearer ' + jwt },
                data: answerData,
                dataType: 'json',
                success: function(response) {
                    $('#loadingOverlay').removeClass('active');
                    
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Saved!',
                            text: 'Your answer has been updated',
                            timer: 1500,
                            showConfirmButton: false,
                            background: '#1a1a1a',
                            color: '#fff'
                        }).then(() => {
                            loadQuestionsAndAnswers(); // Reload to show updated answer
                        });
                    } else {
                        Swal.fire('Error!', response.message || 'Failed to save answer', 'error');
                    }
                },
                error: function() {
                    $('#loadingOverlay').removeClass('active');
                    Swal.fire('Error!', 'Failed to save answer', 'error');
                }
            });
        }
    </script>
</body>
</html>