<?php
/**
 * AI Chat API
 * 
 * Endpoint สำหรับส่งข้อความและรับคำตอบจาก AI
 * POST: /app/actions/ai_chat.php
 * 
 * ✅ เพิ่มฟีเจอร์ dump_prompt เพื่อดูข้อมูลที่ส่งไปยัง AI
 */

require_once('../../lib/connect.php');
require_once('../../lib/jwt_helper.php');
require_once('../../lib/aimodelmanager.php');

global $conn;

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set JSON header
header('Content-Type: application/json');

// ตรวจสอบ JWT
$headers = getallheaders();
$jwt = null;
$user_id = null;

if (isset($headers['Authorization'])) {
    $jwt = str_replace('Bearer ', '', $headers['Authorization']);
    $decoded = verifyJWT($jwt);
    if ($decoded) {
        $user_id = requireAuth();
    }
}

if (!$user_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please login first',
        'require_login' => true
    ]);
    exit;
}

// รับข้อมูลจาก request
$input = json_decode(file_get_contents('php://input'), true);
$conversation_id = isset($input['conversation_id']) ? intval($input['conversation_id']) : 0;
$user_message = isset($input['message']) ? trim($input['message']) : '';
$language = isset($input['language']) ? $input['language'] : 'th';

// ✅ เพิ่ม parameter สำหรับ dump prompt (สำหรับ debug)
$dump_prompt = isset($input['dump_prompt']) ? (bool)$input['dump_prompt'] : false;

// Validate
if (empty($user_message)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Message cannot be empty'
    ]);
    exit;
}

try {
    $conn->begin_transaction();
    
    // ✅ ถ้าไม่มี conversation_id ให้สร้างใหม่
    if ($conversation_id === 0) {
        // ดึง user_companion_id ของ user นี้ พร้อม preferred_language
        $companion_stmt = $conn->prepare("
            SELECT user_companion_id, ai_id, preferred_language 
            FROM user_ai_companions 
            WHERE user_id = ? AND status = 1 AND del = 0
            ORDER BY last_active_at DESC
            LIMIT 1
        ");
        $companion_stmt->bind_param('i', $user_id);
        $companion_stmt->execute();
        $companion_result = $companion_stmt->get_result();
        
        if ($companion_result->num_rows === 0) {
            throw new Exception('No AI companion found. Please setup AI companion first.');
        }
        
        $companion_data = $companion_result->fetch_assoc();
        $user_companion_id = $companion_data['user_companion_id'];
        $ai_id = $companion_data['ai_id'];
        // ✅ ใช้ภาษาที่ user เลือกไว้ (ถ้ามี) หรือใช้ค่าที่ส่งมา
        $language = !empty($companion_data['preferred_language']) ? $companion_data['preferred_language'] : $language;
        $companion_stmt->close();
        
        // สร้าง conversation ใหม่
        $conv_title = mb_substr($user_message, 0, 50) . (mb_strlen($user_message) > 50 ? '...' : '');
        $conv_stmt = $conn->prepare("
            INSERT INTO ai_chat_conversations 
            (user_companion_id, conversation_title, language_used) 
            VALUES (?, ?, ?)
        ");
        $conv_stmt->bind_param('iss', $user_companion_id, $conv_title, $language);
        $conv_stmt->execute();
        $conversation_id = $conn->insert_id;
        $conv_stmt->close();
    } else {
        // ดึงข้อมูล conversation ที่มีอยู่ พร้อม preferred_language
        $conv_stmt = $conn->prepare("
            SELECT 
                c.user_companion_id, 
                c.language_used,
                uc.ai_id,
                uc.preferred_language
            FROM ai_chat_conversations c
            INNER JOIN user_ai_companions uc ON c.user_companion_id = uc.user_companion_id
            WHERE c.conversation_id = ? AND uc.user_id = ? AND c.is_active = 1
        ");
        $conv_stmt->bind_param('ii', $conversation_id, $user_id);
        $conv_stmt->execute();
        $conv_result = $conv_stmt->get_result();
        
        if ($conv_result->num_rows === 0) {
            throw new Exception('Conversation not found');
        }
        
        $conv_data = $conv_result->fetch_assoc();
        $user_companion_id = $conv_data['user_companion_id'];
        $ai_id = $conv_data['ai_id'];
        // ✅ ใช้ภาษาจาก conversation (ถ้ามี) หรือใช้ preferred_language
        $language = !empty($conv_data['language_used']) ? $conv_data['language_used'] : 
                   (!empty($conv_data['preferred_language']) ? $conv_data['preferred_language'] : $language);
        $conv_stmt->close();
    }
    
    // ✅ บันทึกข้อความของ user
    $user_chat_stmt = $conn->prepare("
        INSERT INTO ai_chat_history 
        (conversation_id, user_companion_id, user_id, ai_id, role, message_text, language_used) 
        VALUES (?, ?, ?, ?, 'user', ?, ?)
    ");
    $user_chat_stmt->bind_param('iiiiss', $conversation_id, $user_companion_id, $user_id, $ai_id, $user_message, $language);
    $user_chat_stmt->execute();
    $user_chat_stmt->close();
    
    // ✅ ดึงข้อมูล AI companion (ครบทุก field)
    $lang_col = $language;
    $ai_stmt = $conn->prepare("
        SELECT 
            ai_id,
            ai_code,
            ai_name_{$lang_col} as ai_name,
            system_prompt_{$lang_col} as system_prompt,
            perfume_knowledge_{$lang_col} as perfume_knowledge,
            style_suggestions_{$lang_col} as style_suggestions
        FROM ai_companions 
        WHERE ai_id = ? AND status = 1 AND del = 0
    ");
    $ai_stmt->bind_param('i', $ai_id);
    $ai_stmt->execute();
    $ai_result = $ai_stmt->get_result();
    
    if ($ai_result->num_rows === 0) {
        throw new Exception('AI companion not found');
    }
    
    $ai_companion = $ai_result->fetch_assoc();
    $ai_stmt->close();
    
    // ✅ ดึง personality ของ user พร้อม choices
    $personality_stmt = $conn->prepare("
        SELECT 
            q.question_text_{$lang_col} as question,
            a.text_answer,
            a.scale_value,
            c.choice_text_{$lang_col} as choice_text,
            q.question_order
        FROM user_personality_answers a
        INNER JOIN ai_personality_questions q ON a.question_id = q.question_id
        LEFT JOIN ai_question_choices c ON a.choice_id = c.choice_id
        WHERE a.user_companion_id = ?
        ORDER BY q.question_order
    ");
    $personality_stmt->bind_param('i', $user_companion_id);
    $personality_stmt->execute();
    $personality_result = $personality_stmt->get_result();
    $user_personality = [];
    while ($row = $personality_result->fetch_assoc()) {
        $user_personality[] = $row;
    }
    $personality_stmt->close();
    
    // ✅ ดึงประวัติการแชท (10 ข้อความล่าสุด)
    $history_stmt = $conn->prepare("
        SELECT role, message_text 
        FROM ai_chat_history 
        WHERE conversation_id = ? 
        ORDER BY created_at ASC 
        LIMIT 10
    ");
    $history_stmt->bind_param('i', $conversation_id);
    $history_stmt->execute();
    $history_result = $history_stmt->get_result();
    $chat_history = [];
    while ($row = $history_result->fetch_assoc()) {
        $chat_history[] = $row;
    }
    $history_stmt->close();
    
    // ✅ สร้าง AI Model Manager
    $aiManager = new AIModelManager($conn);
    
    // สร้าง system prompt
    $system_prompt_result = $aiManager->buildSystemPrompt($ai_companion, $user_personality, $language);
    $system_prompt = $system_prompt_result['prompt'];
    $prompt_details = $system_prompt_result['details'];
    
    $messages = [
        ['role' => 'system', 'content' => $system_prompt]
    ];
    
    // เพิ่มประวัติการแชท
    $formatted_history = $aiManager->formatConversationHistory($chat_history, 10);
    $messages = array_merge($messages, $formatted_history);
    
    // ✅ ถ้าต้องการ dump prompt ให้ return ทันที (ไม่ส่งไปยัง AI)
    if ($dump_prompt) {
        $conn->rollback(); // ยกเลิกการบันทึกข้อความ
        
        echo json_encode([
            'status' => 'success',
            'dump_mode' => true,
            'conversation_id' => $conversation_id,
            'ai_companion' => [
                'ai_id' => $ai_companion['ai_id'],
                'ai_code' => $ai_companion['ai_code'],
                'ai_name' => $ai_companion['ai_name']
            ],
            'user_personality_count' => count($user_personality),
            'chat_history_count' => count($chat_history),
            'prompt_details' => $prompt_details,
            'messages_to_send' => $messages,
            'message_structure' => [
                'total_messages' => count($messages),
                'system_message_length' => mb_strlen($messages[0]['content']),
                'history_messages' => count($formatted_history),
                'estimated_tokens' => intval(mb_strlen(json_encode($messages)) / 4) // ประมาณการ
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // ✅ ส่งไปยัง AI (พร้อม Fallback System)
    $ai_response = $aiManager->chat($messages, [
        'max_tokens' => 1024,
        'temperature' => 0.7
    ]);
    
    if (!$ai_response['success']) {
        throw new Exception('All AI models failed: ' . $ai_response['error']);
    }
    
    $ai_message = $ai_response['message'];
    $ai_model = $ai_response['model_used'];
    $tokens_used = $ai_response['tokens_used'];
    $response_time = $ai_response['response_time_ms'];
    $provider_used = $ai_response['provider'];
    
    // ✅ บันทึกคำตอบของ AI
    $ai_chat_stmt = $conn->prepare("
        INSERT INTO ai_chat_history 
        (conversation_id, user_companion_id, user_id, ai_id, role, message_text, ai_model_used, tokens_used, response_time_ms, language_used) 
        VALUES (?, ?, ?, ?, 'assistant', ?, ?, ?, ?, ?)
    ");
    $ai_chat_stmt->bind_param('iiiissiii', $conversation_id, $user_companion_id, $user_id, $ai_id, $ai_message, $ai_model, $tokens_used, $response_time, $language);
    $ai_chat_stmt->execute();
    $ai_chat_stmt->close();
    
    // ✅ อัพเดท conversation
    $update_conv_stmt = $conn->prepare("
        UPDATE ai_chat_conversations 
        SET message_count = message_count + 2, 
            updated_at = NOW() 
        WHERE conversation_id = ?
    ");
    $update_conv_stmt->bind_param('i', $conversation_id);
    $update_conv_stmt->execute();
    $update_conv_stmt->close();
    
    // ✅ อัพเดท last_active ของ user_companion
    $update_companion_stmt = $conn->prepare("
        UPDATE user_ai_companions 
        SET last_active_at = NOW() 
        WHERE user_companion_id = ?
    ");
    $update_companion_stmt->bind_param('i', $user_companion_id);
    $update_companion_stmt->execute();
    $update_companion_stmt->close();
    
    $conn->commit();
    
    // ส่งคำตอบกลับพร้อม prompt details
    echo json_encode([
        'status' => 'success',
        'conversation_id' => $conversation_id,
        'ai_message' => $ai_message,
        'ai_name' => $ai_companion['ai_name'],
        'tokens_used' => $tokens_used,
        'response_time_ms' => $response_time,
        'model_used' => $ai_model,
        'provider' => $provider_used,
        'fallback_attempts' => $ai_response['attempts'],
        'prompt_details' => $prompt_details
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>