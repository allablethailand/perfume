<?php
/**
 * AI Chat API (Guest Mode Supported + DevRev Integration + Emotion Detection)
 * 
 * FIXED:
 * ✅ Emotion instruction เข้มข้นขึ้น - บังคับ pure JSON เท่านั้น
 * ✅ ห้าม emoji ในทุก prompt (เพิ่ม Unicode range block ใน post-processing)
 * ✅ ห้าม AI แนะนำตัวซ้ำๆ ทุกข้อความ
 * ✅ บังคับ AI อ่าน + ตอบ user message ล่าสุดก่อนเสมอ
 * ✅ Fallback parse ดีขึ้น - ถ้า AI ส่ง JSON แบบ raw จะ strip ออกก่อน
 * ✅ stripEmojis() - ลบ emoji ออกจาก ai_message หลัง parse เสมอ
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

// ========================================
// Helper: ลบ emoji ออกจาก string
// ========================================
function stripEmojis(string $text): string {
    // ครอบคลุม emoji ranges หลักทั้งหมด
    $patterns = [
        '/[\x{1F600}-\x{1F64F}]/u',   // Emoticons
        '/[\x{1F300}-\x{1F5FF}]/u',   // Misc Symbols & Pictographs
        '/[\x{1F680}-\x{1F6FF}]/u',   // Transport & Map
        '/[\x{1F700}-\x{1F77F}]/u',   // Alchemical Symbols
        '/[\x{1F780}-\x{1F7FF}]/u',   // Geometric Shapes Extended
        '/[\x{1F800}-\x{1F8FF}]/u',   // Supplemental Arrows-C
        '/[\x{1F900}-\x{1F9FF}]/u',   // Supplemental Symbols & Pictographs
        '/[\x{1FA00}-\x{1FA6F}]/u',   // Chess Symbols
        '/[\x{1FA70}-\x{1FAFF}]/u',   // Symbols & Pictographs Extended-A
        '/[\x{2600}-\x{26FF}]/u',     // Misc Symbols (sun, moon, etc.)
        '/[\x{2700}-\x{27BF}]/u',     // Dingbats
        '/[\x{FE00}-\x{FE0F}]/u',     // Variation Selectors
        '/[\x{1F1E0}-\x{1F1FF}]/u',   // Flags (Regional Indicators)
        '/[\x{200D}]/u',              // Zero Width Joiner
        '/[\x{20E3}]/u',              // Combining Enclosing Keycap
    ];
    $cleaned = preg_replace($patterns, '', $text);
    // ลบ whitespace ซ้ำที่เกิดจากการลบ emoji
    $cleaned = preg_replace('/  +/', ' ', $cleaned);
    return trim($cleaned ?? $text);
}

$input                  = json_decode(file_get_contents('php://input'), true);
$conversation_id        = isset($input['conversation_id'])    ? intval($input['conversation_id'])       : 0;
$user_message           = isset($input['message'])            ? trim($input['message'])                  : '';
$dump_prompt            = isset($input['dump_prompt'])        ? (bool)$input['dump_prompt']              : false;
$user_companion_id_input = isset($input['user_companion_id']) ? intval($input['user_companion_id'])      : 0;
$ai_code_input          = isset($input['ai_code'])            ? strtoupper(trim($input['ai_code']))      : '';
$preferred_language     = isset($input['preferred_language'])  ? strtolower(trim($input['preferred_language'])) : 'th';

// Validate preferred_language — fallback to 'th' if invalid
$valid_languages = ['th', 'en', 'cn', 'jp', 'kr'];
if (!in_array($preferred_language, $valid_languages)) {
    $preferred_language = 'th';
}
$language_names = ['th' => 'Thai', 'en' => 'English', 'cn' => 'Chinese', 'jp' => 'Japanese', 'kr' => 'Korean'];
$preferred_language_name = $language_names[$preferred_language];

if (empty($user_message)) {
    echo json_encode(['status' => 'error', 'message' => 'Message cannot be empty']);
    exit;
}

// ========== ระบุตัวตน ==========
$user_id           = null;
$user_companion_id = null;
$ai_id             = null;
$is_guest_mode     = false;

$headers = getallheaders();
if (isset($headers['Authorization'])) {
    $jwt = str_replace('Bearer ', '', $headers['Authorization']);
    try {
        $decoded = verifyJWT($jwt);
        if ($decoded) {
            $user_id = requireAuth();
        }
    } catch (Exception $e) {}
}

if (!$user_id) {
    $is_guest_mode = true;

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
            $companion         = $result->fetch_assoc();
            $user_companion_id = $companion['user_companion_id'];
            $user_id           = $companion['user_id'];
            $ai_id             = $companion['ai_id'];
        }
        $stmt->close();
    }

    if (!$user_companion_id && !empty($ai_code_input)) {
        $stmt = $conn->prepare("
            SELECT ai_id FROM ai_companions WHERE ai_code = ? AND status = 1 AND del = 0
        ");
        $stmt->bind_param('s', $ai_code_input);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $ai_data = $result->fetch_assoc();
            $ai_id   = $ai_data['ai_id'];

            if (isset($_SESSION['user_companion_id'])) {
                $user_companion_id = $_SESSION['user_companion_id'];
                $stmt2 = $conn->prepare("SELECT user_id FROM user_ai_companions WHERE user_companion_id = ?");
                $stmt2->bind_param('i', $user_companion_id);
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                if ($result2->num_rows > 0) {
                    $comp_data = $result2->fetch_assoc();
                    $user_id   = $comp_data['user_id'];
                }
                $stmt2->close();
            }
        }
        $stmt->close();
    }

    if (!$user_id && !$user_companion_id) {
        echo json_encode([
            'status'               => 'error',
            'message'              => 'Please provide user_companion_id or ai_code, or login first',
            'require_login'        => false,
            'guest_mode_available' => true
        ]);
        exit;
    }
}

try {
    $conn->begin_transaction();

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
        $companion_data    = $companion_result->fetch_assoc();
        $user_companion_id = $companion_data['user_companion_id'];
        $ai_id             = $companion_data['ai_id'];
        $companion_stmt->close();
    }

    if ($conversation_id === 0) {
        $conv_title = mb_substr($user_message, 0, 50) . (mb_strlen($user_message) > 50 ? '...' : '');
        $conv_stmt  = $conn->prepare("
            INSERT INTO ai_chat_conversations (user_companion_id, conversation_title) VALUES (?, ?)
        ");
        $conv_stmt->bind_param('is', $user_companion_id, $conv_title);
        $conv_stmt->execute();
        $conversation_id = $conn->insert_id;
        $conv_stmt->close();
    } else {
        $conv_stmt = $conn->prepare("
            SELECT c.user_companion_id, uc.ai_id
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
        $conv_data         = $conv_result->fetch_assoc();
        $user_companion_id = $conv_data['user_companion_id'];
        $ai_id             = $conv_data['ai_id'];
        $conv_stmt->close();
    }

    $user_chat_stmt = $conn->prepare("
        INSERT INTO ai_chat_history 
        (conversation_id, user_companion_id, user_id, ai_id, role, message_text) 
        VALUES (?, ?, ?, ?, 'user', ?)
    ");
    $user_chat_stmt->bind_param('iiiis', $conversation_id, $user_companion_id, $user_id, $ai_id, $user_message);
    $user_chat_stmt->execute();
    $user_chat_stmt->close();

    // ========================================
    // ดึงข้อมูล AI companion
    // ========================================
    $ai_stmt = $conn->prepare("
        SELECT 
            ai_id, ai_code,
            COALESCE(ai_name_en, ai_name_th, ai_name_cn, ai_name_jp, ai_name_kr)                                                       AS ai_name,
            COALESCE(system_prompt_en, system_prompt_th, system_prompt_cn, system_prompt_jp, system_prompt_kr)                          AS system_prompt,
            COALESCE(perfume_knowledge_en, perfume_knowledge_th, perfume_knowledge_cn, perfume_knowledge_jp, perfume_knowledge_kr)      AS perfume_knowledge,
            COALESCE(style_suggestions_en, style_suggestions_th, style_suggestions_cn, style_suggestions_jp, style_suggestions_kr)      AS style_suggestions
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

    $personality_stmt = $conn->prepare("
        SELECT 
            COALESCE(q.question_text_en, q.question_text_th, q.question_text_cn, q.question_text_jp, q.question_text_kr) AS question,
            a.text_answer, a.scale_value,
            COALESCE(c.choice_text_en, c.choice_text_th, c.choice_text_cn, c.choice_text_jp, c.choice_text_kr) AS choice_text,
            q.question_order
        FROM user_personality_answers a
        INNER JOIN ai_personality_questions q ON a.question_id = q.question_id
        LEFT JOIN  ai_question_choices c ON a.choice_id = c.choice_id
        WHERE a.user_companion_id = ?
        ORDER BY q.question_order
    ");
    $personality_stmt->bind_param('i', $user_companion_id);
    $personality_stmt->execute();
    $personality_result = $personality_stmt->get_result();
    $user_personality   = [];
    while ($row = $personality_result->fetch_assoc()) {
        $user_personality[] = $row;
    }
    $personality_stmt->close();

    // ========================================
    // ดึง summary + history สำหรับส่งให้ AI
    // Logic: ถ้ามี summary เก็บใน DB → ส่ง summary + 5 รอบล่าสุด
    //        ถ้าไม่มี (บทสนทนาสั้น) → ส่ง history ดิบ 10 รอบล่าสุด
    //        ถ้าคุยเกิน SUMMARY_TRIGGER_ROUNDS → สร้าง/อัปเดต summary ใหม่
    // ========================================
    define('SUMMARY_TRIGGER_ROUNDS', 10); // trigger เมื่อคุยครบ 10 รอบ
    define('RECENT_ROUNDS_WITH_SUMMARY', 5); // ส่ง 5 รอบล่าสุดเมื่อมี summary
    define('RECENT_ROUNDS_NO_SUMMARY', 10);  // ส่ง 10 รอบเมื่อไม่มี summary

    // ดึง summary ปัจจุบัน + message_count จาก conversation
    $conv_info_stmt = $conn->prepare("
        SELECT message_count, conversation_summary, summary_updated_at
        FROM ai_chat_conversations
        WHERE conversation_id = ?
    ");
    $conv_info_stmt->bind_param('i', $conversation_id);
    $conv_info_stmt->execute();
    $conv_info        = $conv_info_stmt->get_result()->fetch_assoc();
    $conv_info_stmt->close();

    $current_summary  = $conv_info['conversation_summary'] ?? '';
    $message_count    = (int)($conv_info['message_count'] ?? 0);
    // message_count นับทั้ง user+assistant รวมกัน → รอบสนทนา = message_count / 2
    $total_rounds     = (int)floor($message_count / 2);

    // ดึง history ดิบ (เอาแค่ที่จำเป็น)
    // ถ้ามี summary → ดึงแค่ RECENT_ROUNDS_WITH_SUMMARY*2 rows
    // ถ้าไม่มี       → ดึงแค่ RECENT_ROUNDS_NO_SUMMARY*2 rows
    $history_rows_needed = $current_summary
        ? (RECENT_ROUNDS_WITH_SUMMARY * 2 + 2)
        : (RECENT_ROUNDS_NO_SUMMARY * 2 + 2);

    // ดึง N rows ล่าสุด โดยใช้ subquery (ORDER DESC + LIMIT แล้วกลับลำดับ)
    $history_stmt = $conn->prepare("
        SELECT role, message_text FROM (
            SELECT role, message_text, created_at
            FROM ai_chat_history 
            WHERE conversation_id = ? 
            ORDER BY created_at DESC 
            LIMIT " . $history_rows_needed . "
        ) sub ORDER BY sub.created_at ASC
    ");
    $history_stmt->bind_param('i', $conversation_id);
    $history_stmt->execute();
    $history_result = $history_stmt->get_result();
    $chat_history   = [];
    while ($row = $history_result->fetch_assoc()) {
        $chat_history[] = $row;
    }
    $history_stmt->close();

    // ตัด user message ล่าสุดออก (เพิ่ง insert ไปก่อนหน้า)
    // จะ append กลับเองตอนสร้าง $messages
    if (!empty($chat_history) && $chat_history[count($chat_history) - 1]['role'] === 'user') {
        array_pop($chat_history);
    }

    // ตรวจว่าถึงเวลาสรุปไหม
    // trigger เมื่อ total_rounds ทวีคูณของ SUMMARY_TRIGGER_ROUNDS พอดี
    // เช่น รอบที่ 10, 20, 30 ... และยังไม่ได้สรุปในรอบนี้
    $should_summarize = false;
    if ($total_rounds > 0 && $total_rounds % SUMMARY_TRIGGER_ROUNDS === 0) {
        // ป้องกัน summarize ซ้ำ: เช็คว่า summary ล่าสุดถูกทำไปแล้วในรอบนี้หรือยัง
        // โดยนับ message_count ณ ตอนที่ summary ถูก update ครั้งล่าสุด
        // วิธีง่าย: เก็บ summary_message_count ไว้ใน DB แทน
        // ตอนนี้ใช้วิธี: ถ้า summary_updated_at อยู่ภายใน 60 วินาที = เพิ่งทำไปแล้ว ข้ามได้
        $just_summarized = false;
        if (!empty($conv_info['summary_updated_at'])) {
            $last_summary_time = strtotime($conv_info['summary_updated_at']);
            $just_summarized   = (time() - $last_summary_time) < 60;
        }
        if (!$just_summarized) {
            $should_summarize = true;
            error_log("[Summary] Trigger at round {$total_rounds}, will summarize.");
        }
    }

    $aiManager            = new AIModelManager($conn);
    $system_prompt_result = $aiManager->buildSystemPrompt($ai_companion, $user_personality);
    $system_prompt        = $system_prompt_result['prompt'];

    $ai_name = !empty($ai_companion['ai_name']) ? $ai_companion['ai_name'] : 'AI Assistant';

    // ========================================
    // Identity instruction - ห้ามแนะนำตัวซ้ำทุกข้อความ
    // ========================================
    $identity_instruction  = "\n\n=== YOUR IDENTITY ===\n";
    $identity_instruction .= "Your name is: {$ai_name}\n";
    $identity_instruction .= "IMPORTANT RULES:\n";
    $identity_instruction .= "- Only introduce yourself when the user explicitly asks your name, or at the very first message of a conversation.\n";
    $identity_instruction .= "- Do NOT say your name or re-introduce yourself on every reply. This is annoying and unnatural.\n";
    $identity_instruction .= "- You CANNOT change your name under any circumstances.\n";
    $identity_instruction .= "- If someone asks you to change your name, politely decline once and move on.\n";

    // ========================================
    // Language & Tone - ห้าม emoji อย่างเด็ดขาด
    // ========================================
    $language_instruction  = "\n\n=== LANGUAGE & TONE ===\n";
    $language_instruction .= "LANGUAGE RULES — NON-NEGOTIABLE:\n";
    $language_instruction .= "1. You MUST always respond in {$preferred_language_name} ({$preferred_language}) regardless of what language the user writes in.\n";
    $language_instruction .= "2. Even if the user writes in English, Thai, Chinese, Japanese, or Korean — you ALWAYS reply in {$preferred_language_name}.\n";
    $language_instruction .= "3. Do NOT switch language under any circumstances, even if the user asks you to.\n";
    $language_instruction .= "4. You are fluent in Thai, English, Chinese, Japanese, and Korean.\n\n";
    $language_instruction .= "TONE & FORMAT RULES — NON-NEGOTIABLE:\n";
    $language_instruction .= "- ZERO emojis. Do not use any emoji, emoticon, Unicode symbol, or pictograph. Not even one.\n";
    $language_instruction .= "- This includes but is not limited to: smiley faces, hearts, stars, flowers, animals, flags, hands.\n";
    $language_instruction .= "- Plain text only. If you include any emoji, your response is considered broken.\n";
    $language_instruction .= "- Be natural and conversational, like a real friend texting.\n";
    $language_instruction .= "- Do not start every message with a greeting or your name.\n";
    $language_instruction .= "- Keep responses concise unless the user asks for detail.\n";

    // ========================================
    // Emotion + JSON instruction - บังคับ pure JSON, ห้าม emoji ใน message field
    // ========================================
    $emotion_instruction  = "\n\n=== RESPONSE FORMAT — CRITICAL ===\n";
    $emotion_instruction .= "You MUST output ONLY a raw JSON object. No other text before or after it.\n\n";
    $emotion_instruction .= "Required format:\n";
    $emotion_instruction .= '{"message": "your reply here", "emotion": "one_of_the_emotions_below"}';
    $emotion_instruction .= "\n\nAllowed emotion values (lowercase, pick exactly one):\n";
    $emotion_instruction .= "happy | sad | excited | calm | thinking | surprised | empathetic\n\n";
    $emotion_instruction .= "Emotion guide:\n";
    $emotion_instruction .= "- happy: positive, good news, compliments\n";
    $emotion_instruction .= "- sad: consoling, bad news, loss\n";
    $emotion_instruction .= "- excited: enthusiastic topics, new discoveries\n";
    $emotion_instruction .= "- calm: neutral info, factual answers\n";
    $emotion_instruction .= "- thinking: analyzing, complex questions\n";
    $emotion_instruction .= "- surprised: unexpected questions or facts\n";
    $emotion_instruction .= "- empathetic: supporting user feelings, sensitive topics\n\n";
    $emotion_instruction .= "ABSOLUTE RULES — violation breaks the app:\n";
    $emotion_instruction .= "- Output ONLY the JSON object. Nothing before it, nothing after it.\n";
    $emotion_instruction .= "- Do NOT wrap in ```json or ``` or any markdown.\n";
    $emotion_instruction .= "- Do NOT add explanations, preamble, or trailing text.\n";
    $emotion_instruction .= "- The 'message' field MUST be in {$preferred_language_name} ({$preferred_language}). No other language is allowed.\n";
    $emotion_instruction .= "- The 'message' field must contain ZERO emojis. Plain text only.\n";
    $emotion_instruction .= "- The 'emotion' field must be one of the 7 values above, exactly as written.\n";

    // ========================================
    // บังคับให้ตอบ user message ล่าสุดก่อนเสมอ
    // ========================================
    $focus_instruction  = "\n\n=== CONVERSATION FOCUS ===\n";
    $focus_instruction .= "- Always read and respond to the user's LATEST message first.\n";
    $focus_instruction .= "- Do not bring up previous topics unless the user does.\n";
    $focus_instruction .= "- If the user changes subject, follow them immediately.\n";
    $focus_instruction .= "- Never repeat or summarize what was just said.\n";

    $system_prompt = $system_prompt . $identity_instruction . $language_instruction . $emotion_instruction . $focus_instruction;

    // ========================================
    // สร้าง summary ถ้าถึงเวลา (ทำก่อน build messages)
    // ========================================
    if ($should_summarize) {
        // ดึง history ทั้งหมดที่ยังไม่ได้สรุป (ยกเว้น 5 รอบล่าสุด)
        $all_history_stmt = $conn->prepare("
            SELECT role, message_text 
            FROM ai_chat_history 
            WHERE conversation_id = ?
            ORDER BY created_at ASC
            LIMIT " . ($total_rounds * 2));
        $all_history_stmt->bind_param('i', $conversation_id);
        $all_history_stmt->execute();
        $all_history_result = $all_history_stmt->get_result();
        $all_history_rows   = [];
        while ($row = $all_history_result->fetch_assoc()) {
            $all_history_rows[] = $row;
        }
        $all_history_stmt->close();

        // สร้าง text สำหรับส่งให้ AI สรุป (ยกเว้น 5 รอบล่าสุด)
        $rows_to_summarize = array_slice($all_history_rows, 0, max(0, count($all_history_rows) - (RECENT_ROUNDS_WITH_SUMMARY * 2)));

        if (!empty($rows_to_summarize)) {
            $history_text = '';
            foreach ($rows_to_summarize as $msg) {
                $role          = $msg['role'] === 'user' ? 'User' : 'Assistant';
                $history_text .= $role . ": " . $msg['message_text'] . "\n";
            }
            if (!empty($current_summary)) {
                $history_text = "Previous summary:\n" . $current_summary . "\n\nNew conversation:\n" . $history_text;
            }

            $summary_prompt = "Summarize the following conversation in 3-5 bullet points. ";
            $summary_prompt .= "Be concise. Capture: who the user is, their preferences, key topics discussed, and any important facts. ";
            $summary_prompt .= "Write in the same language as the conversation. No emojis. Plain text only.

";
            $summary_prompt .= $history_text;

            $summary_messages = [
                ['role' => 'system', 'content' => 'You are a conversation summarizer. Output only the bullet-point summary. No JSON. No emojis. Plain text.'],
                ['role' => 'user',   'content' => $summary_prompt]
            ];

            $summary_response = $aiManager->chat($summary_messages, ['max_tokens' => 300, 'temperature' => 0.3]);

            if ($summary_response['success'] && !empty($summary_response['message'])) {
                $new_summary = trim($summary_response['message']);
                // บันทึก summary ลง DB
                $save_summary_stmt = $conn->prepare("
                    UPDATE ai_chat_conversations 
                    SET conversation_summary = ?, summary_updated_at = NOW()
                    WHERE conversation_id = ?
                ");
                $save_summary_stmt->bind_param('si', $new_summary, $conversation_id);
                $save_summary_stmt->execute();
                $save_summary_stmt->close();
                $current_summary = $new_summary;
                error_log("[Summary] Saved new summary for conversation {$conversation_id}");
            }
        }
    }

    // ========================================
    // Build messages สำหรับส่งให้ AI
    // ========================================
    $messages = [['role' => 'system', 'content' => $system_prompt]];

    // ถ้ามี summary → ใส่เป็น context ก่อน history
    if (!empty($current_summary)) {
        $summary_context  = "=== CONVERSATION SUMMARY (what was discussed before) ===
";
        $summary_context .= $current_summary;
        $summary_context .= "
=== END SUMMARY ===
";
        $summary_context .= "Use this summary as background context. Focus on the RECENT messages and the user's LATEST message.";
        $messages[] = ['role' => 'user',      'content' => $summary_context];
        $messages[] = ['role' => 'assistant', 'content' => 'Understood. I have the context from our previous conversation.'];
    }

    $recent_limit     = $current_summary ? RECENT_ROUNDS_WITH_SUMMARY : RECENT_ROUNDS_NO_SUMMARY;
    $formatted_history = $aiManager->formatConversationHistory($chat_history, $recent_limit);
    $messages          = array_merge($messages, $formatted_history);

    // เพิ่ม user message ล่าสุดต่อท้ายสุดเสมอ
    $messages[] = ['role' => 'user', 'content' => $user_message];

    if ($dump_prompt) {
        $conn->rollback();
        echo json_encode([
            'status'            => 'success',
            'dump_mode'         => true,
            'guest_mode'        => $is_guest_mode,
            'conversation_id'   => $conversation_id,
            'user_companion_id' => $user_companion_id,
            'messages_to_send'  => $messages
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ========================================
    // ตรวจสอบ provider
    // ========================================
    $will_use_devrev   = false;
    $selected_provider = null;

    $models_stmt = $conn->prepare("
        SELECT provider FROM ai_models WHERE is_active = 1 ORDER BY priority ASC, is_free DESC LIMIT 1
    ");
    $models_stmt->execute();
    $models_result = $models_stmt->get_result();
    if ($models_result->num_rows > 0) {
        $first_model       = $models_result->fetch_assoc();
        $selected_provider = strtolower($first_model['provider']);
        $will_use_devrev   = ($selected_provider === 'devrev');
    }
    $models_stmt->close();

    $devrev_result = null;
    $devrev_debug  = [];

    if ($will_use_devrev) {
        try {
            $user_stmt = $conn->prepare("
                SELECT user_id, first_name, last_name, email, devrev_dev_user_id, devrev_display_id
                FROM mb_user WHERE user_id = ?
            ");
            $user_stmt->bind_param('i', $user_id);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            $user_data   = $user_result->fetch_assoc();
            $user_stmt->close();

            if ($user_data) {
                $devrev       = new DevRevManager($conn);
                $email        = $user_data['email'] ?: "user{$user_id}@example.com";
                $display_name = trim($user_data['first_name'] . ' ' . $user_data['last_name']) ?: "User {$user_id}";

                if (empty($ai_companion['ai_name'])) {
                    $ai_companion['ai_name'] = 'AI Assistant';
                }

                $devrev_result = $devrev->processChat(
                    $conversation_id, $user_id, $user_companion_id,
                    $email, $display_name, $ai_companion, $user_personality
                );
            }
        } catch (Exception $devrev_error) {
            error_log("⚠️ [AI Chat] DevRev sync failed: " . $devrev_error->getMessage());
        }
    }

    // ========================================
    // ส่งไปยัง AI
    // ========================================
    $ai_response = $aiManager->chat($messages, [
        'max_tokens'     => 1024,
        'temperature'    => 0.7,
        'devrev_user_id' => $user_id
    ]);

    if (!$ai_response['success']) {
        throw new Exception('AI failed: ' . $ai_response['error']);
    }

    $ai_message_raw = $ai_response['message'];
    $ai_model       = $ai_response['model_used'];
    $tokens_used    = $ai_response['tokens_used'];
    $response_time  = $ai_response['response_time_ms'];
    $provider_used  = $ai_response['provider'];

    // ========================================
    // Parse JSON - robust fallback
    // ========================================
    $ai_message     = $ai_message_raw;
    $ai_emotion     = 'calm';
    $valid_emotions = ['happy', 'sad', 'excited', 'calm', 'thinking', 'surprised', 'empathetic'];

    // Strip markdown code fences
    $cleaned = trim($ai_message_raw);
    $cleaned = preg_replace('/^```json\s*/i', '', $cleaned);
    $cleaned = preg_replace('/^```\s*/i',     '', $cleaned);
    $cleaned = preg_replace('/\s*```$/i',     '', $cleaned);
    $cleaned = trim($cleaned);

    // หา JSON object แรกในก้อนข้อความ (กรณี AI พิมอะไรก่อน JSON)
    if (preg_match('/\{[\s\S]*"message"[\s\S]*"emotion"[\s\S]*\}/U', $cleaned, $json_match)) {
        $cleaned = $json_match[0];
    }

    $parsed_response = json_decode($cleaned, true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($parsed_response) && isset($parsed_response['message'])) {
        $ai_message  = trim($parsed_response['message']);
        $raw_emotion = strtolower(trim($parsed_response['emotion'] ?? 'calm'));
        $ai_emotion  = in_array($raw_emotion, $valid_emotions) ? $raw_emotion : 'calm';
        error_log("✅ [AI Emotion] Parsed OK — emotion: {$ai_emotion}");
    } else {
        // Fallback: ถ้า parse ไม่ได้เลย ใช้ raw text แต่ strip markdown/json ออก
        error_log("⚠️ [AI Emotion] JSON parse failed, fallback to raw. Error: " . json_last_error_msg());
        $ai_message = preg_replace('/^\s*\{[^}]*"message"\s*:\s*"/i', '', $ai_message_raw);
        $ai_message = preg_replace('/",[^}]*"emotion"[^}]*\}\s*$/i', '', $ai_message);
        $ai_message = trim($ai_message, " \t\n\r\0\x0B\"");
        if (empty($ai_message)) {
            $ai_message = $ai_message_raw;
        }
        $ai_emotion = 'calm';
    }

    // ========================================
    // FINAL SAFETY: ลบ emoji ออกจาก ai_message เสมอ ไม่ว่า AI จะส่งอะไรมา
    // ========================================
    $ai_message = stripEmojis($ai_message);
    if (empty(trim($ai_message))) {
        // กรณี message กลายเป็นว่างหลัง strip (ไม่น่าเกิด แต่ป้องกันไว้)
        $ai_message = stripEmojis($ai_message_raw);
    }

    // บันทึกคำตอบ AI
    $ai_chat_stmt = $conn->prepare("
        INSERT INTO ai_chat_history 
        (conversation_id, user_companion_id, user_id, ai_id, role, message_text, ai_emotion, ai_model_used, tokens_used, response_time_ms) 
        VALUES (?, ?, ?, ?, 'assistant', ?, ?, ?, ?, ?)
    ");
    $ai_chat_stmt->bind_param('iiiisssii',
        $conversation_id, $user_companion_id, $user_id, $ai_id,
        $ai_message, $ai_emotion, $ai_model, $tokens_used, $response_time
    );
    $ai_chat_stmt->execute();
    $ai_chat_id = $conn->insert_id;
    $ai_chat_stmt->close();

    $update_conv_stmt = $conn->prepare("
        UPDATE ai_chat_conversations 
        SET message_count = message_count + 2, updated_at = NOW() 
        WHERE conversation_id = ?
    ");
    $update_conv_stmt->bind_param('i', $conversation_id);
    $update_conv_stmt->execute();
    $update_conv_stmt->close();

    $update_companion_stmt = $conn->prepare("
        UPDATE user_ai_companions SET last_active_at = NOW() WHERE user_companion_id = ?
    ");
    $update_companion_stmt->bind_param('i', $user_companion_id);
    $update_companion_stmt->execute();
    $update_companion_stmt->close();

    $conn->commit();

    // ========================================
    // Async DevRev sync (ถ้าไม่ได้ใช้ DevRev provider)
    // ========================================
    if (!$will_use_devrev) {
        if (empty($ai_companion['ai_name'])) {
            $ai_companion['ai_name'] = 'AI Assistant';
        }

        $sync_data = json_encode([
            'conversation_id'   => $conversation_id,
            'user_id'           => $user_id,
            'user_companion_id' => $user_companion_id,
            'ai_companion'      => $ai_companion,
            'user_personality'  => $user_personality
        ], JSON_UNESCAPED_UNICODE);

        try {
            $queue_stmt = $conn->prepare("
                INSERT INTO devrev_sync_queue 
                (conversation_id, user_id, sync_data, status, created_at)
                VALUES (?, ?, ?, 'pending', NOW())
                ON DUPLICATE KEY UPDATE 
                    sync_data = VALUES(sync_data), status = 'pending', retry_count = 0, updated_at = NOW()
            ");
            $queue_stmt->bind_param('iis', $conversation_id, $user_id, $sync_data);
            $queue_stmt->execute();
            $queue_stmt->close();
        } catch (Exception $queue_error) {
            error_log("⚠️ [AI Chat] Failed to queue DevRev sync: " . $queue_error->getMessage());
        }
    }

    echo json_encode([
        'status'            => 'success',
        'guest_mode'        => $is_guest_mode,
        'conversation_id'   => $conversation_id,
        'user_companion_id' => $user_companion_id,
        'ai_chat_id'        => $ai_chat_id,
        'ai_message'        => $ai_message,
        'ai_emotion'        => $ai_emotion,
        'ai_name'           => $ai_name,
        'language_used'     => $preferred_language,
        'tokens_used'       => $tokens_used,
        'response_time_ms'  => $response_time,
        'model_used'        => $ai_model,
        'provider'          => $provider_used,
        'devrev_mode'       => $will_use_devrev ? 'sync_before' : 'async_after',
        'devrev_synced'     => $will_use_devrev
            ? (!empty($devrev_result['chat_article_id']) || !empty($devrev_result['prompt_article_id']))
            : false,
        'devrev_articles'   => $devrev_result,
        'devrev_debug'      => $devrev_debug
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>