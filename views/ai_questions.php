<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personality Quiz - AI Companion</title>
    
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

        /* Speech Bubble */
        .speech-bubble {
            position: absolute;
            left: 50%;
            bottom: 100%;
            transform: translateX(-50%);
            margin-bottom: 30px;
            background: linear-gradient(135deg, rgba(120, 119, 198, 0.2) 0%, rgba(168, 167, 229, 0.15) 100%);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(120, 119, 198, 0.4);
            border-radius: 20px;
            padding: 20px 24px;
            min-width: 320px;
            max-width: 350px;
            box-shadow: 0 10px 40px rgba(120, 119, 198, 0.3);
            opacity: 0;
            animation: bubbleIn 0.5s ease forwards;
            animation-delay: 0.3s;
            z-index: 10;
        }

        @keyframes bubbleIn {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }

        .speech-bubble::before {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 15px solid transparent;
            border-right: 15px solid transparent;
            border-top: 20px solid rgba(120, 119, 198, 0.4);
        }

        .speech-bubble::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            margin-top: -2px;
            width: 0;
            height: 0;
            border-left: 13px solid transparent;
            border-right: 13px solid transparent;
            border-top: 18px solid rgba(120, 119, 198, 0.15);
        }

        .speech-bubble-text {
            font-size: 15px;
            line-height: 1.6;
            color: #fff;
            font-weight: 500;
        }

        .speech-bubble-icon {
            display: inline-block;
            margin-right: 8px;
            font-size: 16px;
            color: #7877c6;
        }

        /* Question Change Animation */
        .speech-bubble.changing {
            animation: bubbleOut 0.3s ease forwards;
        }

        @keyframes bubbleOut {
            from {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
            to {
                opacity: 0;
                transform: translateX(-50%) translateY(-10px);
            }
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
            justify-content: center;
            min-height: 100vh;
        }

        /* Progress Bar */
        .progress-container {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 24px 32px;
            margin-bottom: 40px;
        }

        .progress-text {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            font-weight: 600;
        }

        .progress-numbers {
            font-size: 18px;
            color: #7877c6;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #7877c6 0%, #a8a7e5 100%);
            transition: width 0.4s ease;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(120, 119, 198, 0.6);
        }

        /* Question Card */
        .question-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 50px 60px;
            animation: fadeInUp 0.6s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .question-number {
            font-size: 13px;
            color: #7877c6;
            font-weight: 700;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .question-text {
            font-size: 28px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 40px;
            line-height: 1.4;
            letter-spacing: -0.5px;
        }

        /* Choices */
        .choices-container {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .choice-option {
            padding: 22px 28px;
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 16px;
            color: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .choice-option:hover {
            border-color: #7877c6;
            background: rgba(120, 119, 198, 0.1);
            transform: translateX(8px);
        }

        .choice-option.selected {
            border-color: #7877c6;
            background: rgba(120, 119, 198, 0.2);
            color: #fff;
            box-shadow: 0 8px 24px rgba(120, 119, 198, 0.3);
        }

        .choice-radio {
            width: 24px;
            height: 24px;
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
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: white;
        }

        /* Text Input */
        .text-input {
            width: 100%;
            padding: 20px 24px;
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            font-size: 16px;
            font-family: 'Inter', sans-serif;
            color: #fff;
            transition: all 0.3s;
            resize: vertical;
            min-height: 140px;
        }

        .text-input:focus {
            outline: none;
            border-color: #7877c6;
            background: rgba(255, 255, 255, 0.05);
            box-shadow: 0 0 0 4px rgba(120, 119, 198, 0.1);
        }

        .text-input::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        /* Scale */
        .scale-container {
            padding: 24px 0;
        }

        .scale-labels {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
            font-weight: 500;
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
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            transition: all 0.3s;
        }

        .scale-option:hover {
            border-color: #7877c6;
            background: rgba(120, 119, 198, 0.1);
            transform: scale(1.05);
        }

        .scale-option.selected {
            border-color: #7877c6;
            background: rgba(120, 119, 198, 0.3);
            color: #fff;
            box-shadow: 0 8px 24px rgba(120, 119, 198, 0.4);
        }

        /* Navigation */
        .nav-buttons {
            display: flex;
            gap: 16px;
            margin-top: 40px;
        }

        .btn {
            flex: 1;
            padding: 18px;
            border: none;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #7877c6 0%, #a8a7e5 100%);
            color: white;
            box-shadow: 0 8px 24px rgba(120, 119, 198, 0.3);
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(120, 119, 198, 0.5);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.8);
            border: 2px solid rgba(255, 255, 255, 0.1);
        }

        .btn-secondary:hover:not(:disabled) {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            transform: none;
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

            .speech-bubble {
                min-width: 280px;
                max-width: 300px;
                margin-bottom: 20px;
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

            .speech-bubble {
                position: static;
                transform: none;
                margin: 20px auto 0;
                animation: bubbleInMobile 0.5s ease forwards;
            }

            @keyframes bubbleInMobile {
                from {
                    opacity: 0;
                    transform: translateY(-10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .speech-bubble::before,
            .speech-bubble::after {
                display: none;
            }

            .main-content {
                margin-left: 0;
                padding: 40px 30px;
            }

            .question-card {
                padding: 40px 30px;
            }

            .question-text {
                font-size: 24px;
            }
        }

        @media (max-width: 600px) {
            .question-card {
                padding: 30px 24px;
            }

            .question-text {
                font-size: 20px;
            }

            .choice-option {
                padding: 18px 20px;
                font-size: 15px;
            }

            .scale-option {
                font-size: 20px;
            }

            .speech-bubble {
                min-width: auto;
                width: 100%;
                padding: 16px 20px;
            }

            .speech-bubble-text {
                font-size: 14px;
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
            <!-- Speech Bubble -->
            <div class="speech-bubble" id="speechBubble">
                <div class="speech-bubble-text">
                    <i class="fas fa-comment-dots speech-bubble-icon"></i>
                    <span id="speechBubbleText">กำลังโหลดคำถาม...</span>
                </div>
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
        <!-- Progress -->
        <div class="progress-container">
            <div class="progress-text">
                <span>Progress</span>
                <span class="progress-numbers">
                    <span id="currentQuestion">1</span> / <span id="totalQuestions">10</span>
                </span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill" style="width: 10%;"></div>
            </div>
        </div>

        <!-- Question Card -->
        <div class="question-card" id="questionCard">
            <div class="question-number" id="questionNumber">Question 1</div>
            <div class="question-text" id="questionText">Loading...</div>
            
            <div class="choices-container" id="choicesContainer"></div>
            <textarea class="text-input" id="textInput" style="display: none;" placeholder="Type your answer here..."></textarea>
            
            <div class="scale-container" id="scaleContainer" style="display: none;">
                <div class="scale-labels">
                    <span>Strongly Disagree</span>
                    <span>Strongly Agree</span>
                </div>
                <div class="scale-options" id="scaleOptions"></div>
            </div>

            <div class="nav-buttons">
                <button class="btn btn-secondary" id="btnPrevious" disabled>
                    <i class="fas fa-arrow-left"></i> Previous
                </button>
                <button class="btn btn-primary" id="btnNext" disabled>
                    Next <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Loading -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <i class="fas fa-spinner"></i>
            <div class="loading-text">Saving your answers...</div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const companionId = urlParams.get('companion_id');
        const lang = urlParams.get('lang') || 'th';

        let questions = [];
        let currentQuestionIndex = 0;
        let answers = {};

        const jwt = sessionStorage.getItem('jwt');
        if (!jwt || !companionId) {
            Swal.fire('Error!', 'Invalid access', 'error').then(() => {
                window.location.href = '?';
            });
        }

        $(document).ready(function() {
            loadAIInfo();
            loadQuestions();
        });

        function loadAIInfo() {
            $.ajax({
                url: 'app/actions/get_companion_info.php',
                type: 'GET',
                headers: { 'Authorization': 'Bearer ' + jwt },
                data: { companion_id: companionId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        const ai = response.data;
                        const langCol = 'ai_name_' + lang;
                        $('#aiNameSidebar').text(ai[langCol] || ai.ai_name_th);
                        
                        if (ai.ai_avatar_url) {
                            $('#aiAvatarSidebar').attr('src', ai.ai_avatar_url);
                        }
                    }
                }
            });
        }

        function loadQuestions() {
            $.ajax({
                url: 'app/actions/get_personality_questions.php',
                type: 'GET',
                data: { lang: lang },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        questions = response.data;
                        $('#totalQuestions').text(questions.length);
                        displayQuestion(0);
                    } else {
                        Swal.fire('Error!', 'Failed to load questions', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to load questions', 'error');
                }
            });
        }

        function updateSpeechBubble(questionText) {
            const bubble = $('#speechBubble');
            const bubbleText = $('#speechBubbleText');
            
            // Add changing class to animate out
            bubble.addClass('changing');
            
            // Wait for animation, then update text and animate in
            setTimeout(function() {
                bubbleText.text(questionText);
                bubble.removeClass('changing');
                
                // Re-trigger animation by removing and re-adding element
                const bubbleClone = bubble.clone(true);
                bubble.replaceWith(bubbleClone);
            }, 300);
        }

        function displayQuestion(index) {
            if (index < 0 || index >= questions.length) return;

            currentQuestionIndex = index;
            const question = questions[index];

            const progress = ((index + 1) / questions.length) * 100;
            $('#progressFill').css('width', progress + '%');
            $('#currentQuestion').text(index + 1);
            $('#questionNumber').text(`Question ${index + 1}`);

            const langCol = 'question_text_' + lang;
            const questionText = question[langCol] || question.question_text_th;
            $('#questionText').text(questionText);

            // Update speech bubble with current question
            updateSpeechBubble(questionText);

            $('#choicesContainer').empty().hide();
            $('#textInput').val('').hide();
            $('#scaleContainer').hide();

            if (question.question_type === 'choice') {
                displayChoices(question);
            } else if (question.question_type === 'text') {
                displayTextInput(question);
            } else if (question.question_type === 'scale') {
                displayScaleInput(question);
            }

            $('#btnPrevious').prop('disabled', index === 0);
            
            if (answers[question.question_id]) {
                $('#btnNext').prop('disabled', false);
                loadPreviousAnswer(question);
            } else {
                $('#btnNext').prop('disabled', true);
            }

            if (index === questions.length - 1) {
                $('#btnNext').html('<i class="fas fa-check"></i> Complete');
            } else {
                $('#btnNext').html('Next <i class="fas fa-arrow-right"></i>');
            }
        }

        function displayChoices(question) {
            $('#choicesContainer').show();
            
            if (!question.choices || question.choices.length === 0) return;

            const langCol = 'choice_text_' + lang;
            
            question.choices.forEach(choice => {
                const choiceHtml = `
                    <div class="choice-option" data-choice-id="${choice.choice_id}">
                        <div class="choice-radio"></div>
                        <span>${choice[langCol] || choice.choice_text_th}</span>
                    </div>
                `;
                $('#choicesContainer').append(choiceHtml);
            });

            $('.choice-option').on('click', function() {
                $('.choice-option').removeClass('selected');
                $(this).addClass('selected');
                
                const choiceId = $(this).data('choice-id');
                answers[question.question_id] = {
                    question_id: question.question_id,
                    choice_id: choiceId
                };
                
                $('#btnNext').prop('disabled', false);
            });
        }

        function displayTextInput(question) {
            $('#textInput').show();

            $('#textInput').on('input', function() {
                const text = $(this).val().trim();
                
                if (text.length > 0) {
                    answers[question.question_id] = {
                        question_id: question.question_id,
                        text_answer: text
                    };
                    $('#btnNext').prop('disabled', false);
                } else {
                    delete answers[question.question_id];
                    $('#btnNext').prop('disabled', true);
                }
            });
        }

        function displayScaleInput(question) {
            $('#scaleContainer').show();
            $('#scaleOptions').empty();

            for (let i = 1; i <= 5; i++) {
                const scaleHtml = `<div class="scale-option" data-value="${i}">${i}</div>`;
                $('#scaleOptions').append(scaleHtml);
            }

            $('.scale-option').on('click', function() {
                $('.scale-option').removeClass('selected');
                $(this).addClass('selected');
                
                const value = $(this).data('value');
                answers[question.question_id] = {
                    question_id: question.question_id,
                    scale_value: value
                };
                
                $('#btnNext').prop('disabled', false);
            });
        }

        function loadPreviousAnswer(question) {
            const answer = answers[question.question_id];
            
            if (question.question_type === 'choice' && answer.choice_id) {
                $(`.choice-option[data-choice-id="${answer.choice_id}"]`).addClass('selected');
            } else if (question.question_type === 'text' && answer.text_answer) {
                $('#textInput').val(answer.text_answer);
            } else if (question.question_type === 'scale' && answer.scale_value) {
                $(`.scale-option[data-value="${answer.scale_value}"]`).addClass('selected');
            }
        }

        $('#btnPrevious').on('click', function() {
            displayQuestion(currentQuestionIndex - 1);
        });

        $('#btnNext').on('click', function() {
            if (currentQuestionIndex === questions.length - 1) {
                submitAnswers();
            } else {
                displayQuestion(currentQuestionIndex + 1);
            }
        });

        function submitAnswers() {
            $('#loadingOverlay').addClass('active');

            const answersArray = Object.values(answers);

            $.ajax({
                url: 'app/actions/save_personality_answers.php',
                type: 'POST',
                headers: { 'Authorization': 'Bearer ' + jwt },
                data: {
                    user_companion_id: companionId,
                    answers: JSON.stringify(answersArray)
                },
                dataType: 'json',
                success: function(response) {
                    $('#loadingOverlay').removeClass('active');
                    
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Complete!',
                            text: 'Thank you for answering. You can now chat with your AI Companion!',
                            confirmButtonText: 'Go to Chat',
                            background: '#1a1a1a',
                            color: '#fff'
                        }).then(() => {
                            window.location.href = '?ai_chat_3d&companion_id=' + companionId + '&lang=' + lang;
                        });
                    } else {
                        Swal.fire('Error!', response.message || 'Failed to save answers', 'error');
                    }
                },
                error: function() {
                    $('#loadingOverlay').removeClass('active');
                    Swal.fire('Error!', 'Failed to save answers', 'error');
                }
            });
        }
    </script>
</body>
</html>