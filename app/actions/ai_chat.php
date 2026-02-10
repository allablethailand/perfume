<?php
/**
 * AI Chat API (Guest Mode Supported + DevRev Integration)
 * 
 * POST: /app/actions/ai_chat.php
 * 
 * ✅ รองรับ 2 โหมด:
 *    1. Login Mode: ใช้ JWT (user_id)
 *    2. Guest Mode: ใช้ user_companion_id หรือ ai_code
 * ✅ ไม่บังคับภาษา - AI ตอบตามภาษาที่ user ใช้
 * ✅ ดึง prompt จากคอลัมน์ใดก็ได้ที่มีข้อมูล (ไม่สนว่าจะเป็นภาษาอะไร)
 * ✅ Prompt ทั้งหมดเป็นภาษาอังกฤษ
 * ✅ FIX: แยก DevRev sync ออกจาก main flow
 *    - DevRev provider: ต้อง sync ก่อนแล้วค่อยถาม DevRev AI
 *    - AI providers อื่น: ตอบได้เลย แล้วค่อย sync DevRev ทีหลังแบบ async
 */

require_once('../../lib/connect.php');
require_once('../../lib/jwt_helper.php');
require_once('../../lib/aimodelmanager.php');
require_once('../../lib/devrev_manager.php');

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
            SELECT user_companion_id, user_id, ai_id
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
                
                $stmt2 = $conn->prepare("
                    SELECT user_id
                    FROM user_ai_companions 
                    WHERE user_companion_id = ?
                ");
                $stmt2->bind_param('i', $user_companion_id);
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                
                if ($result2->num_rows > 0) {
                    $comp_data = $result2->fetch_assoc();
                    $user_id = $comp_data['user_id'];
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
    
    // ✅ ถ้ายังไม่มี user_companion_id ให้หา
    if (!$user_companion_id && $user_id) {
        $companion_stmt = $conn->prepare("
            SELECT user_companion_id, ai_id
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
        $companion_stmt->close();
    }
    
    // สร้าง conversation ใหม่ (ถ้า conversation_id = 0)
    if ($conversation_id === 0) {
        $conv_title = mb_substr($user_message, 0, 50) . (mb_strlen($user_message) > 50 ? '...' : '');
        $conv_stmt = $conn->prepare("
            INSERT INTO ai_chat_conversations 
            (user_companion_id, conversation_title) 
            VALUES (?, ?)
        ");
        $conv_stmt->bind_param('is', $user_companion_id, $conv_title);
        $conv_stmt->execute();
        $conversation_id = $conn->insert_id;
        $conv_stmt->close();
    } else {
        // ✅ ดึงข้อมูล conversation
        $conv_stmt = $conn->prepare("
            SELECT 
                c.user_companion_id,
                uc.ai_id
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
        $conv_stmt->close();
    }
    
    // บันทึกข้อความ user (ไม่ระบุ language_used)
    $user_chat_stmt = $conn->prepare("
        INSERT INTO ai_chat_history 
        (conversation_id, user_companion_id, user_id, ai_id, role, message_text) 
        VALUES (?, ?, ?, ?, 'user', ?)
    ");
    $user_chat_stmt->bind_param('iiiis', $conversation_id, $user_companion_id, $user_id, $ai_id, $user_message);
    $user_chat_stmt->execute();
    $user_chat_stmt->close();
    
    // ========================================
    // ✅ ดึงข้อมูล AI companion (ไม่สนใจภาษา - หาคอลัมน์แรกที่มีข้อมูล)
    // ========================================
    $ai_stmt = $conn->prepare("
        SELECT 
            ai_id,
            ai_code,
            COALESCE(ai_name_en, ai_name_th, ai_name_cn, ai_name_jp, ai_name_kr) as ai_name,
            COALESCE(system_prompt_en, system_prompt_th, system_prompt_cn, system_prompt_jp, system_prompt_kr) as system_prompt,
            COALESCE(perfume_knowledge_en, perfume_knowledge_th, perfume_knowledge_cn, perfume_knowledge_jp, perfume_knowledge_kr) as perfume_knowledge,
            COALESCE(style_suggestions_en, style_suggestions_th, style_suggestions_cn, style_suggestions_jp, style_suggestions_kr) as style_suggestions
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
    
    // ดึง personality (ใช้คอลัมน์แรกที่เจอ)
    $personality_stmt = $conn->prepare("
        SELECT 
            COALESCE(q.question_text_en, q.question_text_th, q.question_text_cn, q.question_text_jp, q.question_text_kr) as question,
            a.text_answer,
            a.scale_value,
            COALESCE(c.choice_text_en, c.choice_text_th, c.choice_text_cn, c.choice_text_jp, c.choice_text_kr) as choice_text,
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
    
    // ✅ สร้าง system prompt (ไม่ระบุภาษา - ให้ AI ยืดหยุ่น)
    $system_prompt_result = $aiManager->buildSystemPrompt($ai_companion, $user_personality);
    $system_prompt = $system_prompt_result['prompt'];
    
    // ✅ เพิ่มส่วน identity และ language flexibility
    // ตรวจสอบ AI name ก่อนใช้งาน
    $ai_name = !empty($ai_companion['ai_name']) ? $ai_companion['ai_name'] : 'AI Assistant';
    if ($ai_name === 'AI Assistant') {
        error_log("⚠️ [AI Chat] AI name was empty, using default: AI Assistant");
    }
    
    $identity_instruction = "\n\n=== YOUR IDENTITY ===\n";
    $identity_instruction .= "Your name is: {$ai_name}\n";
    $identity_instruction .= "IMPORTANT RULES:\n";
    $identity_instruction .= "- You must ALWAYS introduce yourself as '{$ai_name}'\n";
    $identity_instruction .= "- You CANNOT change your name under any circumstances\n";
    $identity_instruction .= "- If someone asks you to change your name, politely decline\n";
    
    $language_instruction = "\n\n=== LANGUAGE FLEXIBILITY ===\n";
    $language_instruction .= "🌐 MULTILINGUAL SUPPORT:\n\n";
    $language_instruction .= "RULES:\n";
    $language_instruction .= "1. You CAN respond in ANY language the user prefers\n";
    $language_instruction .= "2. Mirror the user's language by default (if they write in Thai, respond in Thai; if English, respond in English)\n";
    $language_instruction .= "3. If the user EXPLICITLY asks you to respond in a specific language (e.g., 'please answer in English'), honor that request\n";
    $language_instruction .= "4. You are fluent in: Thai, English, Chinese, jppanese, and krrean\n";
    $language_instruction .= "5. Be natural and adaptive - the goal is comfortable communication\n\n";
    $language_instruction .= "💡 Example:\n";
    $language_instruction .= "- User writes in Thai → You respond in Thai\n";
    $language_instruction .= "- User writes in Thai but says 'answer in English' → You respond in English\n";
    $language_instruction .= "- User switches languages mid-conversation → You adapt immediately\n";
    
    $system_prompt = $system_prompt . $identity_instruction . $language_instruction;
    
    $messages = [
        ['role' => 'system', 'content' => $system_prompt]
    ];
    
    $formatted_history = $aiManager->formatConversationHistory($chat_history, 10);
    $messages = array_merge($messages, $formatted_history);
    
    // Dump prompt (ถ้าต้องการ)
    if ($dump_prompt) {
        $conn->rollback();
        
        echo json_encode([
            'status' => 'success',
            'dump_mode' => true,
            'guest_mode' => $is_guest_mode,
            'conversation_id' => $conversation_id,
            'user_companion_id' => $user_companion_id,
            'messages_to_send' => $messages
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // ========================================
    // ✅ ตรวจสอบว่าจะใช้ AI provider ไหน
    // ========================================
    $will_use_devrev = false;
    $selected_provider = null;
    
    $models_stmt = $conn->prepare("
        SELECT provider 
        FROM ai_models 
        WHERE is_active = 1 
        ORDER BY priority ASC, is_free DESC
        LIMIT 1
    ");
    $models_stmt->execute();
    $models_result = $models_stmt->get_result();
    if ($models_result->num_rows > 0) {
        $first_model = $models_result->fetch_assoc();
        $selected_provider = strtolower($first_model['provider']);
        $will_use_devrev = ($selected_provider === 'devrev');
    }
    $models_stmt->close();
    
    error_log("🔵 [AI Chat] Selected provider: {$selected_provider}, Will use DevRev: " . ($will_use_devrev ? 'YES' : 'NO'));
    
    // ========================================
    // ✅ ถ้าใช้ DevRev -> ต้อง sync ก่อน
    // ========================================
    $devrev_result = null;
    $devrev_debug = [];
    
    if ($will_use_devrev) {
        error_log("🔵 [AI Chat] Using DevRev provider → Syncing BEFORE AI call");
        
        try {
            $user_stmt = $conn->prepare("
                SELECT user_id, first_name, last_name, email, devrev_dev_user_id, devrev_display_id
                FROM mb_user
                WHERE user_id = ?
            ");
            $user_stmt->bind_param('i', $user_id);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            $user_data = $user_result->fetch_assoc();
            $user_stmt->close();

            if ($user_data) {
                $devrev = new DevRevManager($conn);
                
                $email = $user_data['email'] ?: "user{$user_id}@example.com";
                $display_name = trim($user_data['first_name'] . ' ' . $user_data['last_name']) ?: "User {$user_id}";
                
                // ✅ ตรวจสอบ AI name ก่อน sync
                if (empty($ai_companion['ai_name'])) {
                    $ai_companion['ai_name'] = 'AI Assistant';
                    error_log("⚠️ [AI Chat] Fixed empty AI name before DevRev sync");
                }
                
                $devrev_debug = [
                    'mode' => 'sync_before_ai_call',
                    'conversation_id' => $conversation_id,
                    'user_id' => $user_id,
                    'user_companion_id' => $user_companion_id,
                    'email_used' => $email,
                    'display_name_used' => $display_name,
                    'ai_name' => $ai_companion['ai_name']
                ];

                $devrev_result = $devrev->processChat(
                    $conversation_id, 
                    $user_id, 
                    $user_companion_id, 
                    $email,
                    $display_name,
                    $ai_companion, 
                    $user_personality
                );

                error_log("✅ [AI Chat] DevRev synced BEFORE AI call — Chat: " . ($devrev_result['chat_article_id'] ?? 'NULL') . ", Prompt: " . ($devrev_result['prompt_article_id'] ?? 'NULL'));
                $devrev_debug['processChat_result'] = $devrev_result;

            } else {
                error_log("⚠️ [AI Chat] User data not found for DevRev sync");
                $devrev_debug['error'] = 'User data not found';
            }
        } catch (Exception $devrev_error) {
            error_log("⚠️ [AI Chat] DevRev sync failed (before AI call): " . $devrev_error->getMessage());
            $devrev_debug['exception'] = $devrev_error->getMessage();
        }
    }
    
    // ========================================
    // ✅ ส่งไปยัง AI
    // ========================================
    $ai_response = $aiManager->chat($messages, [
        'max_tokens' => 1024,
        'temperature' => 0.7,
        'devrev_user_id' => $user_id
    ]);
    
    if (!$ai_response['success']) {
        throw new Exception('AI failed: ' . $ai_response['error']);
    }
    
    $ai_message = $ai_response['message'];
    $ai_model = $ai_response['model_used'];
    $tokens_used = $ai_response['tokens_used'];
    $response_time = $ai_response['response_time_ms'];
    $provider_used = $ai_response['provider'];
    
    // บันทึกคำตอบ AI (ไม่ระบุ language_used)
    $ai_chat_stmt = $conn->prepare("
        INSERT INTO ai_chat_history 
        (conversation_id, user_companion_id, user_id, ai_id, role, message_text, ai_model_used, tokens_used, response_time_ms) 
        VALUES (?, ?, ?, ?, 'assistant', ?, ?, ?, ?)
    ");
    $ai_chat_stmt->bind_param('iiiissii', $conversation_id, $user_companion_id, $user_id, $ai_id, $ai_message, $ai_model, $tokens_used, $response_time);
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
    
    // ========================================
    // ✅ ถ้าไม่ใช้ DevRev -> sync ทีหลังแบบ async
    // ========================================
    if (!$will_use_devrev) {
        error_log("🔵 [AI Chat] NOT using DevRev provider → Scheduling async sync AFTER response");
        
        // ✅ ตรวจสอบและแก้ไข ai_companion ก่อน queue
        if (empty($ai_companion['ai_name'])) {
            $ai_companion['ai_name'] = 'AI Assistant';
            error_log("⚠️ [AI Chat] Fixed empty AI name before queue");
        }
        
        // ✅ ไม่เก็บ language อีกต่อไป (ให้ AI ยืดหยุ่น)
        $sync_data = json_encode([
            'conversation_id' => $conversation_id,
            'user_id' => $user_id,
            'user_companion_id' => $user_companion_id,
            'ai_companion' => $ai_companion,
            'user_personality' => $user_personality
        ], JSON_UNESCAPED_UNICODE);
        
        try {
            $queue_stmt = $conn->prepare("
                INSERT INTO devrev_sync_queue 
                (conversation_id, user_id, sync_data, status, created_at)
                VALUES (?, ?, ?, 'pending', NOW())
                ON DUPLICATE KEY UPDATE 
                    sync_data = VALUES(sync_data),
                    status = 'pending',
                    retry_count = 0,
                    updated_at = NOW()
            ");
            $queue_stmt->bind_param('iis', $conversation_id, $user_id, $sync_data);
            $queue_stmt->execute();
            $queue_stmt->close();
            
            error_log("✅ [AI Chat] DevRev sync queued for async processing");
            
            $devrev_debug = [
                'mode' => 'async_queue',
                'queued' => true,
                'message' => 'DevRev sync will be processed in background'
            ];
            
        } catch (Exception $queue_error) {
            error_log("⚠️ [AI Chat] Failed to queue DevRev sync: " . $queue_error->getMessage());
            $devrev_debug = [
                'mode' => 'async_queue',
                'queued' => false,
                'error' => $queue_error->getMessage()
            ];
        }
    }
    
    // ========================================
    // ✅ ส่ง Response กลับทันที
    // ========================================
    echo json_encode([
        'status' => 'success',
        'guest_mode' => $is_guest_mode,
        'conversation_id' => $conversation_id,
        'user_companion_id' => $user_companion_id,
        'ai_message' => $ai_message,
        'ai_name' => $ai_name,
        'tokens_used' => $tokens_used,
        'response_time_ms' => $response_time,
        'model_used' => $ai_model,
        'provider' => $provider_used,
        'devrev_mode' => $will_use_devrev ? 'sync_before' : 'async_after',
        'devrev_synced' => $will_use_devrev ? (!empty($devrev_result['chat_article_id']) || !empty($devrev_result['prompt_article_id'])) : false,
        'devrev_articles' => $devrev_result,
        'devrev_debug' => $devrev_debug
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>