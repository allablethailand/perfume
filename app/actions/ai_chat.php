<?php
/**
 * AI Chat API (Guest Mode Supported)
 * 
 * POST: /app/actions/ai_chat.php
 * 
 * ✅ รองรับ 2 โหมด:
 *    1. Login Mode: ใช้ JWT (user_id)
 *    2. Guest Mode: ใช้ user_companion_id หรือ ai_code
 * ✅ บังคับให้ AI ตอบเฉพาะภาษาจาก preferred_language
 */

require_once('../../lib/connect.php');
require_once('../../lib/jwt_helper.php');
require_once('../../lib/aimodelmanager.php');

global $conn;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// รับข้อมูลจาก request
$input = json_decode(file_get_contents('php://input'), true);
$conversation_id = isset($input['conversation_id']) ? intval($input['conversation_id']) : 0;
$user_message = isset($input['message']) ? trim($input['message']) : '';
$dump_prompt = isset($input['dump_prompt']) ? (bool)$input['dump_prompt'] : false;

// ✅ รับ user_companion_id หรือ ai_code (สำหรับ guest mode)
$user_companion_id_input = isset($input['user_companion_id']) ? intval($input['user_companion_id']) : 0;
$ai_code_input = isset($input['ai_code']) ? strtoupper(trim($input['ai_code'])) : '';

// ✅ รับภาษาจาก request (ถ้ามี) จะใช้แทน preferred_language ในฐานข้อมูล
$language_from_request = isset($input['preferred_language']) ? strtolower(trim($input['preferred_language'])) : null;

// Validate message
if (empty($user_message)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Message cannot be empty'
    ]);
    exit;
}

// ========== ระบุตัวตน: JWT หรือ companion_id/ai_code ==========
$user_id = null;
$user_companion_id = null;
$ai_id = null;
$is_guest_mode = false;
$language = 'th'; // default

// ลอง JWT ก่อน
$headers = getallheaders();
if (isset($headers['Authorization'])) {
    $jwt = str_replace('Bearer ', '', $headers['Authorization']);
    try {
        $decoded = verifyJWT($jwt);
        if ($decoded) {
            $user_id = requireAuth();
        }
    } catch (Exception $e) {
        // JWT ไม่ valid, ลอง guest mode
    }
}

// ถ้าไม่มี user_id จาก JWT -> ลอง guest mode
if (!$user_id) {
    $is_guest_mode = true;
    
    // ✅ วิธีที่ 1: ใช้ user_companion_id โดยตรง
    if ($user_companion_id_input > 0) {
        $stmt = $conn->prepare("
            SELECT user_companion_id, user_id, ai_id, preferred_language
            FROM user_ai_companions
            WHERE user_companion_id = ? AND status = 1 AND del = 0
        ");
        $stmt->bind_param('i', $user_companion_id_input);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $companion = $result->fetch_assoc();
            $user_companion_id = $companion['user_companion_id'];
            $user_id = $companion['user_id'];
            $ai_id = $companion['ai_id'];
            // ✅ ดึงภาษาจาก database เป็นหลัก
            $language = !empty($companion['preferred_language']) ? $companion['preferred_language'] : 'th';
        }
        $stmt->close();
    }
    
    // ✅ วิธีที่ 2: ใช้ ai_code (ถ้ายังไม่ได้ companion)
    if (!$user_companion_id && !empty($ai_code_input)) {
        // หา AI จาก code
        $stmt = $conn->prepare("
            SELECT ai_id 
            FROM ai_companions 
            WHERE ai_code = ? AND status = 1 AND del = 0
        ");
        $stmt->bind_param('s', $ai_code_input);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $ai_data = $result->fetch_assoc();
            $ai_id = $ai_data['ai_id'];
            
            // ลอง session ก่อน
            if (isset($_SESSION['user_companion_id'])) {
                $user_companion_id = $_SESSION['user_companion_id'];
                
                // ✅ ดึง user_id และ preferred_language จาก companion
                $stmt2 = $conn->prepare("
                    SELECT user_id, preferred_language 
                    FROM user_ai_companions 
                    WHERE user_companion_id = ?
                ");
                $stmt2->bind_param('i', $user_companion_id);
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                
                if ($result2->num_rows > 0) {
                    $comp_data = $result2->fetch_assoc();
                    $user_id = $comp_data['user_id'];
                    // ✅ ดึงภาษาจาก database เป็นหลัก
                    $language = !empty($comp_data['preferred_language']) ? $comp_data['preferred_language'] : 'th';
                }
                $stmt2->close();
            }
        }
        $stmt->close();
    }
    
    // ถ้ายังไม่ได้ทั้ง user_id และ companion_id
    if (!$user_id && !$user_companion_id) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Please provide user_companion_id or ai_code, or login first',
            'require_login' => false,
            'guest_mode_available' => true
        ]);
        exit;
    }
}

try {
    $conn->begin_transaction();
    
    // ⚠️ ยังไม่กำหนด $language ที่นี่ - ให้ดึงจาก database ก่อน
    
    // ✅ ถ้ายังไม่มี user_companion_id ให้หาและดึง preferred_language
    if (!$user_companion_id && $user_id) {
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
            throw new Exception('No AI companion found');
        }
        
        $companion_data = $companion_result->fetch_assoc();
        $user_companion_id = $companion_data['user_companion_id'];
        $ai_id = $companion_data['ai_id'];
        // ✅ ดึงภาษาจาก database เป็นหลัก
        $language = !empty($companion_data['preferred_language']) ? $companion_data['preferred_language'] : 'th';
        $companion_stmt->close();
    }
    
    // สร้าง conversation ใหม่ (ถ้า conversation_id = 0)
    if ($conversation_id === 0) {
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
        // ✅ ดึงข้อมูล conversation และ preferred_language อีกครั้ง (เผื่อมีการอัพเดท)
        $conv_stmt = $conn->prepare("
            SELECT 
                c.user_companion_id, 
                c.language_used,
                uc.ai_id,
                uc.preferred_language
            FROM ai_chat_conversations c
            INNER JOIN user_ai_companions uc ON c.user_companion_id = uc.user_companion_id
            WHERE c.conversation_id = ? AND c.is_active = 1
        ");
        $conv_stmt->bind_param('i', $conversation_id);
        $conv_stmt->execute();
        $conv_result = $conv_stmt->get_result();
        
        if ($conv_result->num_rows === 0) {
            throw new Exception('Conversation not found');
        }
        
        $conv_data = $conv_result->fetch_assoc();
        $user_companion_id = $conv_data['user_companion_id'];
        $ai_id = $conv_data['ai_id'];
        // ✅ ดึงภาษาจาก database เป็นหลัก
        $language = !empty($conv_data['preferred_language']) ? $conv_data['preferred_language'] : 'th';
        $conv_stmt->close();
    }
    
    // ✅ Override ด้วย request ถ้ามี (สำหรับกรณีพิเศษ)
    if (!empty($language_from_request)) {
        $language = $language_from_request;
        error_log("⚠️ Language overridden by request: {$language}");
    }
    
    // Debug log
    error_log("✅ Final language used: {$language} (from_request: " . ($language_from_request ?: 'null') . ")");
    
    // บันทึกข้อความ user
    $user_chat_stmt = $conn->prepare("
        INSERT INTO ai_chat_history 
        (conversation_id, user_companion_id, user_id, ai_id, role, message_text, language_used) 
        VALUES (?, ?, ?, ?, 'user', ?, ?)
    ");
    $user_chat_stmt->bind_param('iiiiss', $conversation_id, $user_companion_id, $user_id, $ai_id, $user_message, $language);
    $user_chat_stmt->execute();
    $user_chat_stmt->close();
    
    // ดึงข้อมูล AI companion ตามภาษาที่กำหนด
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
    
    // ดึง personality
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
    
    // ดึงประวัติการแชท
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
    
    // สร้าง AI Model Manager
    $aiManager = new AIModelManager($conn);
    
    // สร้าง system prompt
    $system_prompt_result = $aiManager->buildSystemPrompt($ai_companion, $user_personality, $language);
    $system_prompt = $system_prompt_result['prompt'];
    $prompt_details = $system_prompt_result['details'];
    
    // ✅ กำหนดชื่อภาษาเต็มสำหรับคำสั่ง
    $language_names = [
        'th' => 'Thai (ไทย)',
        'en' => 'English',
        'zh' => 'Chinese (中文)',
        'ja' => 'Japanese (日本語)',
        'ko' => 'Korean (한국어)'
    ];
    $language_full_name = isset($language_names[$language]) ? $language_names[$language] : 'Thai';
    
    // ✅ เพิ่มคำสั่งบังคับภาษา
    $ai_name = $ai_companion['ai_name'];
    $identity_instruction = "\n\n=== YOUR IDENTITY ===\n";
    $identity_instruction .= "Your name is: {$ai_name}\n";
    $identity_instruction .= "IMPORTANT RULES:\n";
    $identity_instruction .= "- You must ALWAYS introduce yourself as '{$ai_name}'\n";
    $identity_instruction .= "- You CANNOT change your name under any circumstances\n";
    $identity_instruction .= "- If someone asks you to change your name, politely decline\n";
    
    // ✅ บังคับให้ตอบเฉพาะภาษาที่กำหนด
    $language_instruction = "\n\n=== LANGUAGE REQUIREMENT (CRITICAL) ===\n";
    $language_instruction .= "🔒 MANDATORY LANGUAGE: {$language_full_name}\n\n";
    $language_instruction .= "STRICT RULES:\n";
    $language_instruction .= "1. You MUST respond ONLY in {$language_full_name}\n";
    $language_instruction .= "2. Even if the user writes in a different language, you MUST still reply in {$language_full_name}\n";
    $language_instruction .= "3. Do NOT switch languages under any circumstances\n";
    $language_instruction .= "4. If the user asks you to speak another language, politely explain (in {$language_full_name}) that you are configured to communicate only in {$language_full_name}\n";
    $language_instruction .= "5. This language setting cannot be changed or overridden\n\n";
    $language_instruction .= "Example:\n";
    if ($language === 'th') {
        $language_instruction .= "User (in English): 'Hello, how are you?'\n";
        $language_instruction .= "You: 'สวัสดีค่ะ ดิฉันสบายดีค่ะ คุณเป็นอย่างไรบ้างคะ' (Answer in Thai)\n";
    } elseif ($language === 'en') {
        $language_instruction .= "User (in Thai): 'สวัสดีครับ สบายดีไหม'\n";
        $language_instruction .= "You: 'Hello! I'm doing well, thank you. How are you?' (Answer in English)\n";
    }
    $language_instruction .= "\n🔒 Remember: ALWAYS respond in {$language_full_name}, no exceptions!\n";
    
    $system_prompt = $system_prompt . $identity_instruction . $language_instruction;
    
    $messages = [
        ['role' => 'system', 'content' => $system_prompt]
    ];
    
    $formatted_history = $aiManager->formatConversationHistory($chat_history, 10);
    $messages = array_merge($messages, $formatted_history);
    
    // Dump prompt (ถ้าต้องการ)
    if ($dump_prompt) {
        $conn->rollback();
        
        $language_source = !empty($language_from_request) 
            ? 'from request parameter' 
            : 'from user_ai_companions.preferred_language';
        
        echo json_encode([
            'status' => 'success',
            'dump_mode' => true,
            'guest_mode' => $is_guest_mode,
            'conversation_id' => $conversation_id,
            'user_companion_id' => $user_companion_id,
            'language_info' => [
                'language_code' => $language,
                'language_name' => $language_full_name,
                'source' => $language_source,
                'from_request' => $language_from_request,
                'final_used' => $language
            ],
            'messages_to_send' => $messages
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // ส่งไปยัง AI
    $ai_response = $aiManager->chat($messages, [
        'max_tokens' => 1024,
        'temperature' => 0.7
    ]);
    
    if (!$ai_response['success']) {
        throw new Exception('AI failed: ' . $ai_response['error']);
    }
    
    $ai_message = $ai_response['message'];
    $ai_model = $ai_response['model_used'];
    $tokens_used = $ai_response['tokens_used'];
    $response_time = $ai_response['response_time_ms'];
    $provider_used = $ai_response['provider'];
    
    // บันทึกคำตอบ AI
    $ai_chat_stmt = $conn->prepare("
        INSERT INTO ai_chat_history 
        (conversation_id, user_companion_id, user_id, ai_id, role, message_text, ai_model_used, tokens_used, response_time_ms, language_used) 
        VALUES (?, ?, ?, ?, 'assistant', ?, ?, ?, ?, ?)
    ");
    $ai_chat_stmt->bind_param('iiiissiii', $conversation_id, $user_companion_id, $user_id, $ai_id, $ai_message, $ai_model, $tokens_used, $response_time, $language);
    $ai_chat_stmt->execute();
    $ai_chat_stmt->close();
    
    // อัพเดท conversation
    $update_conv_stmt = $conn->prepare("
        UPDATE ai_chat_conversations 
        SET message_count = message_count + 2, 
            updated_at = NOW() 
        WHERE conversation_id = ?
    ");
    $update_conv_stmt->bind_param('i', $conversation_id);
    $update_conv_stmt->execute();
    $update_conv_stmt->close();
    
    // อัพเดท last_active
    $update_companion_stmt = $conn->prepare("
        UPDATE user_ai_companions 
        SET last_active_at = NOW() 
        WHERE user_companion_id = ?
    ");
    $update_companion_stmt->bind_param('i', $user_companion_id);
    $update_companion_stmt->execute();
    $update_companion_stmt->close();
    
    $conn->commit();
    
    echo json_encode([
        'status' => 'success',
        'guest_mode' => $is_guest_mode,
        'conversation_id' => $conversation_id,
        'user_companion_id' => $user_companion_id,
        'language_used' => $language,
        'ai_message' => $ai_message,
        'ai_name' => $ai_name,
        'tokens_used' => $tokens_used,
        'response_time_ms' => $response_time,
        'model_used' => $ai_model,
        'provider' => $provider_used
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