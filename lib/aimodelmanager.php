<?php
/**
 * AI Model Manager - FIXED DevRev Integration
 * 
 * 🔧 แก้ไข:
 * 1. ✅ ใช้วิธีส่ง prompt แบบเดียวกับโค้ดตัวอย่างที่ทำงานได้
 * 2. ✅ ส่ง prompt ทั้งหมดในครั้งเดียว (ไม่แยก article reference)
 * 3. ✅ ใช้ Content-Type ที่ DevRev API ยอมรับ
 * 4. ✅ เพิ่ม raw_sections ใน buildSystemPrompt เพื่อใช้ใน DevRev Article
 */

class AIModelManager {
    private $conn;
    private $models = [];
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->loadModels();
    }
    
    private function getEncryptionKey() {
        $secret_key = getenv('JWT_SECRET_KEY');
        return hash('sha256', $secret_key, true);
    }
    
    private function decryptApiKey($encryptedKey) {
        if (empty($encryptedKey)) return null;
        
        try {
            $key = $this->getEncryptionKey();
            $cipher = 'AES-256-CBC';
            
            $data = base64_decode($encryptedKey);
            $iv = substr($data, 0, 16);
            $encrypted = substr($data, 16);
            
            $decrypted = openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
            
            return $decrypted !== false ? $decrypted : null;
        } catch (Exception $e) {
            error_log("Decryption error: " . $e->getMessage());
            return null;
        }
    }
    
    private function loadModels() {
        $stmt = $this->conn->prepare("
            SELECT 
                model_id, model_code, model_name, provider,
                api_key, api_endpoint, is_free, max_tokens,
                cost_per_1k_tokens, priority
            FROM ai_models
            WHERE is_active = 1
            ORDER BY priority ASC, is_free DESC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $row['api_key'] = $this->decryptApiKey($row['api_key']);
            $this->models[] = $row;
        }
        $stmt->close();
        
        if (empty($this->models)) {
            throw new Exception('No active AI models found.');
        }
    }
    
    public function chat($messages, $options = []) {
        $params = array_merge([
            'temperature' => 0.7,
            'max_tokens' => 1024,
            'top_p' => 1,
            'devrev_user_id' => null
        ], $options);
        
        // ✅ Validate messages
        $last_user_message = '';
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if ($messages[$i]['role'] === 'user') {
                $last_user_message = trim($messages[$i]['content']);
                break;
            }
        }
        
        if (strlen($last_user_message) < 2) {
            error_log("⚠️ [AI] User message too short: '{$last_user_message}' - rejecting");
            return [
                'success' => false,
                'error' => 'Message too short (minimum 2 characters required)',
                'message' => '',
                'tokens_used' => 0,
                'response_time_ms' => 0,
                'attempts' => 0
            ];
        }
        
        $attempts = 0;
        $errors = [];
        
        foreach ($this->models as $model) {
            $attempts++;
            try {
                $start_time = microtime(true);
                $response = $this->sendToProvider($model, $messages, $params);
                $response_time = round((microtime(true) - $start_time) * 1000);
                
                if ($response['success']) {
                    return [
                        'success' => true,
                        'message' => $response['message'],
                        'model_used' => $model['model_code'],
                        'model_name' => $model['model_name'],
                        'provider' => $model['provider'],
                        'tokens_used' => $response['tokens_used'],
                        'response_time_ms' => $response_time,
                        'attempts' => $attempts,
                        'is_free' => (bool)$model['is_free']
                    ];
                }
                $errors[] = "{$model['model_name']}: {$response['error']}";
            } catch (Exception $e) {
                $errors[] = "{$model['model_name']}: {$e->getMessage()}";
            }
        }
        
        return [
            'success' => false,
            'error' => implode(' | ', $errors),
            'message' => '',
            'tokens_used' => 0,
            'response_time_ms' => 0,
            'attempts' => $attempts
        ];
    }
    
    private function sendToProvider($model, $messages, $params) {
        if (strtolower($model['provider']) !== 'devrev' && empty($model['api_key'])) {
            return ['success' => false, 'error' => 'API Key not configured'];
        }
        
        switch (strtolower($model['provider'])) {
            case 'groq':      return $this->sendToGroq($model, $messages, $params);
            case 'openai':    return $this->sendToOpenAI($model, $messages, $params);
            case 'anthropic': return $this->sendToAnthropic($model, $messages, $params);
            case 'devrev':    return $this->sendToDevRev($model, $messages, $params);
            case 'gemini':    return $this->sendToGemini($model, $messages, $params);
            default:
                return ['success' => false, 'error' => 'Unsupported provider: ' . $model['provider']];
        }
    }
    
    private function sendToGroq($model, $messages, $params) {
        $api_url = $model['api_endpoint'] ?: 'https://api.groq.com/openai/v1/chat/completions';
        
        $payload = [
            'model' => $model['model_code'],
            'messages' => $messages,
            'temperature' => $params['temperature'],
            'max_tokens' => min($params['max_tokens'], $model['max_tokens']),
            'top_p' => $params['top_p']
        ];
        
        $ch = curl_init($api_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $model['api_key']
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            $err = json_decode($response, true);
            return ['success' => false, 'error' => $err['error']['message'] ?? 'HTTP ' . $http_code];
        }
        
        $data = json_decode($response, true);
        return [
            'success' => true,
            'message' => $data['choices'][0]['message']['content'] ?? '',
            'tokens_used' => $data['usage']['total_tokens'] ?? 0
        ];
    }
    private function sendToGemini($model, $messages, $params) {
    $api_key = $model['api_key'];
    
    if (empty($api_key)) {
        return ['success' => false, 'error' => 'API Key not configured'];
    }
    
    error_log("🔵 [Gemini] Starting request with TWO markdown files (System Prompt + Chat History)");
    
    // ========================================
    // STEP 1: แยก system prompt และ chat history
    // ========================================
    $system_instruction = '';
    $chat_history_messages = [];
    
    foreach ($messages as $msg) {
        if ($msg['role'] === 'system') {
            $system_instruction = $msg['content'];
        } else {
            $chat_history_messages[] = $msg;
        }
    }
    
    error_log("📊 [Gemini] Messages breakdown - System: " . (empty($system_instruction) ? 'No' : 'Yes') . ", Chat history: " . count($chat_history_messages));
    
    // ========================================
    // STEP 2: สร้าง System Prompt Markdown (Content)
    // ========================================
    $system_prompt_md = "# System Prompt / AI Personality\n\n";
    $system_prompt_md .= "**Document Type:** AI System Configuration\n\n";
    $system_prompt_md .= "**Generated:** " . date('Y-m-d H:i:s') . "\n\n";
    $system_prompt_md .= "**Purpose:** This document defines the AI's personality, knowledge base, and behavioral rules.\n\n";
    $system_prompt_md .= "---\n\n";
    
    if (!empty($system_instruction)) {
        $system_prompt_md .= $system_instruction;
    } else {
        $system_prompt_md .= "_No system prompt provided._";
    }
    
    $system_prompt_md .= "\n\n---\n\n";
    $system_prompt_md .= "_End of System Prompt_";
    
    error_log("📝 [Gemini] System Prompt Markdown - Length: " . strlen($system_prompt_md) . " chars");
    error_log("📝 [Gemini] System Prompt Preview:\n" . substr($system_prompt_md, 0, 300) . "...");
    
    // ========================================
    // STEP 3: สร้าง Chat History Markdown (Body)
    // ========================================
    $chat_history_md = "# Chat History / Conversation Context\n\n";
    $chat_history_md .= "**Document Type:** Conversation Log\n\n";
    $chat_history_md .= "**Generated:** " . date('Y-m-d H:i:s') . "\n\n";
    $chat_history_md .= "**Total Messages:** " . count($chat_history_messages) . "\n\n";
    $chat_history_md .= "---\n\n";
    
    if (empty($chat_history_messages)) {
        $chat_history_md .= "_No previous conversation history._\n\n";
    } else {
        $message_number = 1;
        foreach ($chat_history_messages as $msg) {
            $role_label = $msg['role'] === 'assistant' ? '🤖 Assistant' : '👤 User';
            $timestamp = date('H:i:s');
            
            $chat_history_md .= "## Message #{$message_number} - {$role_label}\n\n";
            $chat_history_md .= "**Time:** {$timestamp}\n\n";
            $chat_history_md .= "**Content:**\n\n";
            $chat_history_md .= $msg['content'] . "\n\n";
            $chat_history_md .= "---\n\n";
            
            $message_number++;
        }
    }
    
    $chat_history_md .= "_End of Chat History_";
    
    error_log("📝 [Gemini] Chat History Markdown - Length: " . strlen($chat_history_md) . " chars");
    error_log("📝 [Gemini] Chat History Preview:\n" . substr($chat_history_md, 0, 300) . "...");
    
    // ========================================
    // STEP 4: แปลง Markdown เป็น Base64
    // ========================================
    $system_base64 = base64_encode($system_prompt_md);
    $chat_base64 = base64_encode($chat_history_md);
    
    error_log("🔐 [Gemini] Base64 encoded - System: " . strlen($system_base64) . " chars, Chat: " . strlen($chat_base64) . " chars");
    
    // ========================================
    // STEP 5: ดึง User Message ล่าสุด
    // ========================================
    $last_user_msg = '';
    for ($i = count($messages) - 1; $i >= 0; $i--) {
        if ($messages[$i]['role'] === 'user') {
            $last_user_msg = $messages[$i]['content'];
            break;
        }
    }
    
    if (empty($last_user_msg)) {
        error_log("⚠️ [Gemini] No user message found in conversation");
        return ['success' => false, 'error' => 'No user message found'];
    }
    
    error_log("💬 [Gemini] Last user message (length: " . strlen($last_user_msg) . "): " . substr($last_user_msg, 0, 100) . "...");
    
    // ========================================
    // STEP 6: สร้าง Parts Array (2 Markdown Files + User Message)
    // ========================================
    $parts = [
        // Part 1: System Prompt as Markdown File (Content)
        [
            'inline_data' => [
                'mime_type' => 'text/markdown',
                'data' => $system_base64
            ]
        ],
        // Part 2: Chat History as Markdown File (Body)
        [
            'inline_data' => [
                'mime_type' => 'text/markdown',
                'data' => $chat_base64
            ]
        ],
        // Part 3: Current User Message with Context
        [
            'text' => "📎 **Context Files Provided:**\n\n" .
                     "1️⃣ **System Prompt** (Markdown) - Your personality, knowledge, and rules\n" .
                     "2️⃣ **Chat History** (Markdown) - Previous conversation context\n\n" .
                     "---\n\n" .
                     "**Instructions:**\n" .
                     "- Read both markdown files carefully\n" .
                     "- Follow the personality and rules from the System Prompt\n" .
                     "- Use the Chat History for context continuity\n" .
                     "- Respond naturally based on all provided information\n\n" .
                     "---\n\n" .
                     "**Current User Message:**\n\n" .
                     $last_user_msg
        ]
    ];
    
    error_log("📦 [Gemini] Created " . count($parts) . " parts for request");
    
    // ========================================
    // STEP 7: สร้าง Payload
    // ========================================
    $payload = [
        'contents' => [
            [
                'role' => 'user',
                'parts' => $parts
            ]
        ],
        'generationConfig' => [
            'temperature' => $params['temperature'],
            'maxOutputTokens' => min($params['max_tokens'], $model['max_tokens']),
            'topP' => $params['top_p']
        ]
    ];
    
    // ⚠️ หมายเหตุ: ไม่ใช้ systemInstruction เพราะเราส่งเป็น markdown file แล้ว
    // แต่ถ้าต้องการใช้ทั้งสองอย่าง (ซ้ำซ้อน) ก็เพิ่มได้:
    /*
    if (!empty($system_instruction)) {
        $payload['systemInstruction'] = [
            'parts' => [['text' => $system_instruction]]
        ];
    }
    */
    
    error_log("📦 [Gemini] Payload structure created:");
    error_log("   - Temperature: {$params['temperature']}");
    error_log("   - Max tokens: " . min($params['max_tokens'], $model['max_tokens']));
    error_log("   - Top P: {$params['top_p']}");
    
    // ========================================
    // STEP 8: สร้าง API URL
    // ========================================
    $api_url = $model['api_endpoint'];
    
    // เพิ่ม API key ใน URL
    if (strpos($api_url, '?') === false) {
        $api_url .= '?key=' . $api_key;
    } else {
        $api_url .= '&key=' . $api_key;
    }
    
    error_log("🔵 [Gemini] Request URL: " . preg_replace('/key=[^&]+/', 'key=***HIDDEN***', $api_url));
    
    // ========================================
    // STEP 9: ส่ง Request ไป Gemini API
    // ========================================
    $json_payload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    if ($json_payload === false) {
        error_log("❌ [Gemini] Failed to encode JSON: " . json_last_error_msg());
        return ['success' => false, 'error' => 'Failed to encode JSON payload'];
    }
    
    error_log("📤 [Gemini] Sending request - Payload size: " . strlen($json_payload) . " bytes");
    
    $ch = curl_init($api_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => $json_payload,
        CURLOPT_TIMEOUT => 60, // เพิ่ม timeout เพราะมีไฟล์
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);
    
    $start_time = microtime(true);
    $response = curl_exec($ch);
    $elapsed_time = round((microtime(true) - $start_time) * 1000); // milliseconds
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // ========================================
    // STEP 10: ตรวจสอบ cURL Errors
    // ========================================
    if (curl_errno($ch)) {
        $curl_error = curl_error($ch);
        error_log("❌ [Gemini] cURL Error: {$curl_error}");
        curl_close($ch);
        return ['success' => false, 'error' => "cURL Error: {$curl_error}"];
    }
    
    curl_close($ch);
    
    error_log("📊 [Gemini] HTTP Code: {$http_code} | Response time: {$elapsed_time}ms");
    
    // ========================================
    // STEP 11: ตรวจสอบ HTTP Error
    // ========================================
    if ($http_code !== 200) {
        error_log("❌ [Gemini] HTTP Error {$http_code}");
        error_log("📄 [Gemini] Error Response: " . substr($response, 0, 500));
        
        $err = json_decode($response, true);
        $error_msg = $err['error']['message'] ?? "HTTP {$http_code}";
        
        // แสดง error details ถ้ามี
        if (isset($err['error']['details'])) {
            error_log("🔍 [Gemini] Error details: " . json_encode($err['error']['details']));
        }
        
        return ['success' => false, 'error' => $error_msg];
    }
    
    // ========================================
    // STEP 12: Parse JSON Response
    // ========================================
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("❌ [Gemini] JSON decode error: " . json_last_error_msg());
        error_log("📄 [Gemini] Raw response: " . substr($response, 0, 500));
        return ['success' => false, 'error' => 'Invalid JSON response'];
    }
    
    error_log("📄 [Gemini] Response structure: " . json_encode(array_keys($data), JSON_PRETTY_PRINT));
    
    // ========================================
    // STEP 13: ดึงข้อความจาก Response
    // ========================================
    $text = '';
    
    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        $text = $data['candidates'][0]['content']['parts'][0]['text'];
    } else {
        error_log("❌ [Gemini] No text found in response");
        error_log("🔍 [Gemini] Available paths:");
        
        if (isset($data['candidates'])) {
            error_log("   - candidates: YES");
            if (isset($data['candidates'][0])) {
                error_log("   - candidates[0]: YES");
                error_log("   - candidates[0] keys: " . implode(', ', array_keys($data['candidates'][0])));
                
                if (isset($data['candidates'][0]['content'])) {
                    error_log("   - candidates[0]['content']: YES");
                    error_log("   - content keys: " . implode(', ', array_keys($data['candidates'][0]['content'])));
                }
            }
        }
        
        error_log("📄 [Gemini] Full response: " . json_encode($data, JSON_PRETTY_PRINT));
        
        return ['success' => false, 'error' => 'No text in response'];
    }
    
    // ========================================
    // STEP 14: นับ Tokens
    // ========================================
    $input_tokens = $data['usageMetadata']['promptTokenCount'] ?? 0;
    $output_tokens = $data['usageMetadata']['candidatesTokenCount'] ?? 0;
    $total_tokens = $input_tokens + $output_tokens;
    
    error_log("✅ [Gemini] SUCCESS!");
    error_log("   - Input tokens: {$input_tokens}");
    error_log("   - Output tokens: {$output_tokens}");
    error_log("   - Total tokens: {$total_tokens}");
    error_log("   - Response time: {$elapsed_time}ms");
    error_log("   - Response length: " . strlen($text) . " chars");
    error_log("📝 [Gemini] Response preview: " . substr($text, 0, 200) . "...");
    
    // ========================================
    // STEP 15: ตรวจสอบ Safety Ratings (ถ้ามี)
    // ========================================
    if (isset($data['candidates'][0]['safetyRatings'])) {
        error_log("🛡️ [Gemini] Safety Ratings:");
        foreach ($data['candidates'][0]['safetyRatings'] as $rating) {
            $category = $rating['category'] ?? 'unknown';
            $probability = $rating['probability'] ?? 'unknown';
            error_log("   - {$category}: {$probability}");
        }
    }
    
    // ========================================
    // STEP 16: ตรวจสอบ Finish Reason
    // ========================================
    if (isset($data['candidates'][0]['finishReason'])) {
        $finish_reason = $data['candidates'][0]['finishReason'];
        error_log("🏁 [Gemini] Finish Reason: {$finish_reason}");
        
        // เตือนถ้ามีปัญหา
        if ($finish_reason !== 'STOP') {
            error_log("⚠️ [Gemini] Unexpected finish reason: {$finish_reason}");
            
            if ($finish_reason === 'MAX_TOKENS') {
                error_log("   → Response may be truncated (hit max_tokens limit)");
            } elseif ($finish_reason === 'SAFETY') {
                error_log("   → Response blocked by safety filters");
            }
        }
    }
    
    // ========================================
    // STEP 17: Return Success Response
    // ========================================
    return [
        'success' => true,
        'message' => $text,
        'tokens_used' => $total_tokens,
        'metadata' => [
            'input_tokens' => $input_tokens,
            'output_tokens' => $output_tokens,
            'response_time_ms' => $elapsed_time,
            'finish_reason' => $data['candidates'][0]['finishReason'] ?? 'unknown',
            'files_sent' => [
                'system_prompt_md' => strlen($system_prompt_md) . ' chars',
                'chat_history_md' => strlen($chat_history_md) . ' chars'
            ]
        ]
    ];
}
    private function sendToOpenAI($model, $messages, $params) {
        $api_url = $model['api_endpoint'] ?: 'https://api.openai.com/v1/chat/completions';
        
        $payload = [
            'model' => $model['model_code'],
            'messages' => $messages,
            'temperature' => $params['temperature'],
            'max_tokens' => min($params['max_tokens'], $model['max_tokens'])
        ];
        
        $ch = curl_init($api_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $model['api_key']
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            $err = json_decode($response, true);
            return ['success' => false, 'error' => $err['error']['message'] ?? 'HTTP ' . $http_code];
        }
        
        $data = json_decode($response, true);
        return [
            'success' => true,
            'message' => $data['choices'][0]['message']['content'] ?? '',
            'tokens_used' => $data['usage']['total_tokens'] ?? 0
        ];
    }
    
    private function sendToAnthropic($model, $messages, $params) {
        $api_url = $model['api_endpoint'] ?: 'https://api.anthropic.com/v1/messages';
        
        $system = '';
        $anthropic_messages = [];
        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $system = $msg['content'];
            } else {
                $anthropic_messages[] = $msg;
            }
        }
        
        $payload = [
            'model' => $model['model_code'],
            'max_tokens' => min($params['max_tokens'], $model['max_tokens']),
            'messages' => $anthropic_messages
        ];
        if ($system) $payload['system'] = $system;
        
        $ch = curl_init($api_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $model['api_key'],
                'anthropic-version: 2023-06-01'
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            $err = json_decode($response, true);
            return ['success' => false, 'error' => $err['error']['message'] ?? 'HTTP ' . $http_code];
        }
        
        $data = json_decode($response, true);
        return [
            'success' => true,
            'message' => $data['content'][0]['text'] ?? '',
            'tokens_used' => ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0)
        ];
    }
    
    /**
     * ✅ FIXED: DevRev - ใช้วิธีเดียวกับโค้ดตัวอย่างที่ทำงาน
     * ส่ง prompt ทั้งหมดในครั้งเดียว (ไม่ใช้ article reference)
     */
    private function sendToDevRev($model, $messages, $params) {
    $api_key = $model['api_key'];
    
    if (empty($api_key)) {
        $api_key = getenv('DEVREV_API_TOKEN') ?: ($_ENV['DEVREV_API_TOKEN'] ?? null);
        error_log("⚠️ [DevRev AI] No API key in model, using from .env");
    }
    
    if (empty($api_key)) {
        error_log("❌ [DevRev AI] API Key not found");
        return ['success' => false, 'error' => 'API Key not configured'];
    }
    
    error_log("✅ [DevRev AI] Using API key (length: " . strlen($api_key) . ")");
    
    // ✅ ดึง display_id
    $user_display_id = null;
    if (!empty($params['devrev_user_id'])) {
        $stmt = $this->conn->prepare("
            SELECT devrev_display_id 
            FROM mb_user 
            WHERE user_id = ?
        ");
        $stmt->bind_param('i', $params['devrev_user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        $stmt->close();
        
        if ($user_data && !empty($user_data['devrev_display_id'])) {
            $user_display_id = $user_data['devrev_display_id'];
            error_log("✅ [DevRev AI] User display_id: {$user_display_id}");
        }
    }
    
    // ✅ ดึง article content (เหมือนเดิม)
    $article_content = '';
    if (!empty($params['devrev_user_id'])) {
        $article_content = $this->buildArticleContext($api_key, $params['devrev_user_id']);
    }
    
    // ✅ สร้าง prompt (แบบเดียวกับโค้ดทดสอบ STEP 6B)
    $prompt = $this->buildCompletePrompt($messages, $article_content);
    
    error_log("🔵 [DevRev AI] Prompt length: " . strlen($prompt));
    error_log("📝 [DevRev AI] Prompt preview: " . substr($prompt, 0, 300) . "...");
    
    return $this->sendDevRevRequest($api_key, $prompt, $user_display_id);
}
    
    /**
     * ✅ สร้าง prompt ที่ DevRev ต้องการ
     * FIX: ทดลองพบว่า DevRev ต้องการ prompt ที่เรียบง่าย
     */
    private function buildCompletePrompt($messages, $article_content = '') {
    // 1. ดึง user message ล่าสุด
    $last_user_msg = '';
    for ($i = count($messages) - 1; $i >= 0; $i--) {
        if ($messages[$i]['role'] === 'user') {
            $last_user_msg = trim($messages[$i]['content']);
            break;
        }
    }
    
    if (empty($last_user_msg)) {
        return "Hello";
    }
    
    // 2. ถ้ามี article content ให้ส่งแบบเดียวกับโค้ดทดสอบ STEP 6B
    if (!empty($article_content)) {
        // Clean up
        $clean_content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $article_content);
        $clean_content = trim($clean_content);
        
        // Format เหมือนโค้ดทดสอบ
        $context = $clean_content . "\n\n---\n\n";
        $prompt = $context . "User Question: " . $last_user_msg;
        
        error_log("🔵 [DevRev] Prompt with context length: " . strlen($prompt) . " chars");
        
        return $prompt;
    }
    
    // 3. ไม่มี context ก็ส่งแค่ message
    return $last_user_msg;
}
    
    /**
     * ✅ ส่ง request ไป DevRev API ตาม official docs
     * API expects: POST with JSON body {"query": "text"}
     * Max length: 10000 chars
     */
    private function sendDevRevRequest($api_key, $prompt, $user_display_id = null) {
    $api_url = 'https://api.devrev.ai/recommendations.get-reply';
    
    if (strlen($prompt) < 1) {
        return ['success' => false, 'error' => 'Prompt too short'];
    }
    
    if (strlen($prompt) > 10000) {
        error_log("⚠️ [DevRev] Truncating prompt from " . strlen($prompt) . " to 10000 chars");
        $prompt = mb_substr($prompt, 0, 10000, 'UTF-8');
    }
    
    error_log("🔵 [DevRev AI] Sending prompt (" . strlen($prompt) . " chars)");
    
    $payload = ['query' => $prompt];
    $post_data = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    if ($post_data === false) {
        error_log("❌ [DevRev] JSON encode failed: " . json_last_error_msg());
        return ['success' => false, 'error' => 'Failed to encode JSON'];
    }
    
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . trim($api_key)
    ];
    
    error_log("📦 [DevRev] Payload: " . $post_data);
    
    $ch = curl_init($api_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post_data,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    
    $start = microtime(true);
    $response = curl_exec($ch);
    $elapsed = round((microtime(true) - $start) * 1000);
    
    if (curl_errno($ch)) {
        $curl_error = curl_error($ch);
        error_log("❌ [DevRev] cURL Error: {$curl_error}");
        curl_close($ch);
        return ['success' => false, 'error' => "cURL Error: {$curl_error}"];
    }
    
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    error_log("📊 [DevRev] HTTP: {$http_code} | Time: {$elapsed}ms");
    error_log("📄 [DevRev] FULL Response: " . $response);
    
    if (empty($response)) {
        error_log("❌ [DevRev] Empty response from server");
        return ['success' => false, 'error' => 'Empty response from DevRev'];
    }
    
    if ($http_code === 400) {
        $err = json_decode($response, true);
        $error_type = $err['type'] ?? 'unknown';
        $error_msg = $err['message'] ?? 'Bad Request';
        $field_name = $err['field_name'] ?? '';
        
        error_log("❌ [DevRev] Bad Request - Type: {$error_type}, Msg: {$error_msg}, Field: {$field_name}");
        return ['success' => false, 'error' => "Bad Request: {$error_msg}"];
    }
    
    if ($http_code === 500) {
        $err = json_decode($response, true);
        $ref_id = $err['reference_id'] ?? 'unknown';
        return ['success' => false, 'error' => "DevRev Internal Error (ref: {$ref_id})"];
    }
    
    if ($http_code !== 200) {
        $err = json_decode($response, true);
        $msg = $err['message'] ?? "HTTP {$http_code}";
        return ['success' => false, 'error' => $msg];
    }
    
    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("❌ [DevRev] JSON decode error: " . json_last_error_msg());
        return ['success' => false, 'error' => 'Invalid JSON response'];
    }
    
    error_log("🔍 [DevRev] Response keys: " . implode(', ', array_keys($decoded)));
    
    $reply = $decoded['reply'] ?? '';
    
    if (empty($reply)) {
        error_log("❌ [DevRev] Empty reply field");
        
        if (isset($decoded['response'])) {
            $reply = $decoded['response'];
        } elseif (isset($decoded['text'])) {
            $reply = $decoded['text'];
        }
        
        if (empty($reply)) {
            error_log("❌ [DevRev] Full decoded response: " . json_encode($decoded));
            return ['success' => false, 'error' => 'Empty reply from DevRev'];
        }
    }
    
    if (isset($decoded['sources']) && !empty($decoded['sources'])) {
        $source_count = count($decoded['sources']);
        error_log("📚 [DevRev] Sources used: {$source_count}");
        
        foreach ($decoded['sources'] as $idx => $source) {
            $title = $source['title'] ?? 'untitled';
            $type = $source['type'] ?? 'unknown';
            $display_id = $source['display_id'] ?? 'no-id';
            error_log("   [{$idx}] {$type}: {$title} ({$display_id})");
        }
    } else {
        error_log("⚠️ [DevRev] No sources used");
    }
    
    error_log("✅ [DevRev] Success! Reply length: " . strlen($reply) . " chars");
    
    return [
        'success' => true,
        'message' => $reply,
        'tokens_used' => 0
    ];
}

    
    /**
     * Parse DevRev response
     */
    private function parseDevRevResponse($response, $http_code) {
        if ($http_code !== 200) {
            error_log("❌ HTTP {$http_code}: " . substr($response, 0, 200));
            return ['success' => false, 'error' => "HTTP {$http_code}"];
        }
        
        if (empty($response) || $response === '{}') {
            error_log("❌ Empty response");
            return ['success' => false, 'error' => 'Empty response'];
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("❌ JSON error: " . json_last_error_msg());
            return ['success' => false, 'error' => 'Invalid JSON'];
        }
        
        $text = $decoded['reply'] ?? $decoded['response'] ?? $decoded['text'] ?? '';
        
        if (empty($text)) {
            error_log("❌ No reply field. Keys: " . implode(', ', array_keys($decoded)));
            return ['success' => false, 'error' => 'No reply'];
        }
        
        error_log("✅ SUCCESS! Response: " . substr($text, 0, 100) . "...");
        
        return [
            'success' => true,
            'message' => $text,
            'tokens_used' => 0
        ];
    }
    
    /**
     * ✅ ดึง context จาก Articles
     */
    private function buildArticleContext($api_key, $user_id) {
        error_log("🔵 [DevRev AI] Building article context for user: {$user_id}");
        
        $stmt = $this->conn->prepare("
            SELECT devrev_chat_article_id, devrev_prompt_article_id
            FROM mb_user WHERE user_id = ?
        ");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$row) {
            error_log("⚠️ [DevRev AI] User not found");
            return '';
        }
        
        $parts = [];
        
        // 1. System Prompt
        if (!empty($row['devrev_prompt_article_id'])) {
            $content = $this->fetchArticleContent($api_key, $row['devrev_prompt_article_id']);
            if ($content) {
                // ดึงแค่ส่วนสำคัญ
                $lines = explode("\n", $content);
                $important = [];
                
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (preg_match('/^[•\-\*]\s*(.+)/', $line, $m)) {
                        $important[] = $m[1];
                        if (count($important) >= 5) break;
                    }
                }
                
                if (!empty($important)) {
                    $parts[] = "=== Character Traits ===\n" . implode(". ", $important) . ".";
                    error_log("✅ [DevRev AI] Loaded system prompt traits");
                }
            }
        }
        
        // 2. Chat History (5 messages ล่าสุด)
        if (!empty($row['devrev_chat_article_id'])) {
            $content = $this->fetchArticleContent($api_key, $row['devrev_chat_article_id']);
            if ($content) {
                $lines = explode("\n", $content);
                $recent = [];
                
                for ($i = count($lines) - 1; $i >= 0 && count($recent) < 10; $i--) {
                    $line = trim($lines[$i]);
                    if (preg_match('/^\[[\d\-\s:]+\]\s+(User|Assistant):\s*(.*)/', $line, $m)) {
                        array_unshift($recent, $m[1] . ": " . $m[2]);
                    }
                }
                
                if (!empty($recent)) {
                    $parts[] = "=== Recent Conversation ===\n" . implode("\n", $recent);
                    error_log("✅ [DevRev AI] Loaded " . count($recent) . " messages");
                }
            }
        }
        
        $result = implode("\n\n", $parts);
        error_log("📚 [DevRev AI] Total context: " . strlen($result) . " chars");
        
        return $result;
    }
    
    /**
     * ✅ ดึงเนื้อหาจาก Article
     */
    private function fetchArticleContent($api_key, $article_id) {
        error_log("🔵 [DevRev AI] Fetching article: {$article_id}");
        
        // Step 1: Get Article
        $ch = curl_init('https://api.devrev.ai/articles.get');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $api_key
            ],
            CURLOPT_POSTFIELDS => json_encode(['id' => $article_id]),
            CURLOPT_TIMEOUT => 15
        ]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            error_log("❌ [DevRev AI] Failed to get article: HTTP {$http_code}");
            return '';
        }
        
        $data = json_decode($response, true);
        $artifacts = $data['article']['resource']['artifacts'] ?? [];
        
        if (empty($artifacts)) {
            error_log("⚠️ [DevRev AI] No artifacts");
            return '';
        }
        
        $artifact_id = end($artifacts)['id'] ?? null;
        if (!$artifact_id) return '';
        
        // Step 2: Locate artifact
        $ch = curl_init('https://api.devrev.ai/artifacts.locate');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $api_key
            ],
            CURLOPT_POSTFIELDS => json_encode(['id' => $artifact_id]),
            CURLOPT_TIMEOUT => 15
        ]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) return '';
        
        $download_url = json_decode($response, true)['url'] ?? null;
        if (!$download_url) return '';
        
        // Step 3: Download
        $ch = curl_init($download_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true
        ]);
        $content = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200 || empty($content)) return '';
        
        error_log("✅ [DevRev AI] Downloaded " . strlen($content) . " chars");
        
        // จำกัดความยาว
        if (strlen($content) > 5000) {
            $content = substr($content, 0, 5000) . "\n[...truncated...]";
        }
        
        return $content;
    }
    
    /**
     * ✅ FIXED: buildSystemPrompt - เพิ่ม raw_sections เพื่อใช้ใน DevRev Article
     */
    public function buildSystemPrompt($ai_companion, $user_personality, $language = 'th') {
        $ai_name = $ai_companion['ai_name'] ?? 'AI Assistant';
        
        $core_personality = trim($ai_companion['system_prompt'] ?? '');
        $perfume_knowledge = trim($ai_companion['perfume_knowledge'] ?? '');
        $style_suggestions = trim($ai_companion['style_suggestions'] ?? '');
        
        $expertise = '';
        if (!empty($perfume_knowledge))
            $expertise .= "\n\n=== ความรู้เกี่ยวกับน้ำหอม ===\n" . $perfume_knowledge;
        if (!empty($style_suggestions))
            $expertise .= "\n\n=== คำแนะนำด้านสไตล์ ===\n" . $style_suggestions;
        
        $user_context = '';
        if (!empty($user_personality)) {
            $user_context = "\n\n=== ข้อมูลเพิ่มเติมเกี่ยวกับผู้ใช้ (นิสัยรอง) ===\n";
            foreach ($user_personality as $answer) {
                $user_context .= "• {$answer['question']}: ";
                if (!empty($answer['choice_text']))       $user_context .= $answer['choice_text'];
                elseif (!empty($answer['text_answer']))   $user_context .= $answer['text_answer'];
                elseif ($answer['scale_value'] !== null)  $user_context .= "คะแนน {$answer['scale_value']}/5";
                $user_context .= "\n";
            }
            $user_context .= "\n💡 ใช้เป็นบริบทเสริม แต่รักษานิสัยหลักไว้";
        }
        
        $language_rules = $this->getLanguageRules($language);
        $response_rules = $this->getResponseRules($language);
        
        $full_prompt = trim(
            $core_personality . $expertise . $user_context .
            "\n\n" . $language_rules .
            "\n\n" . $response_rules
        );
        
        $details = [
            'ai_name' => $ai_name,
            'ai_code' => $ai_companion['ai_code'] ?? 'unknown',
            'language' => $language,
            'language_source' => 'preferred_language from user_ai_companions',
            'prompt_sections' => [
                'core_personality' => ['label' => '🎭 นิสัยหลัก', 'content' => $core_personality, 'length' => mb_strlen($core_personality)],
                'perfume_knowledge' => ['label' => '💧 Perfume Knowledge', 'content' => $perfume_knowledge, 'length' => mb_strlen($perfume_knowledge)],
                'style_suggestions' => ['label' => '✨ Style Suggestions', 'content' => $style_suggestions, 'length' => mb_strlen($style_suggestions)],
                'user_personality' => ['label' => '👤 นิสัยรอง', 'content' => $user_context, 'length' => mb_strlen($user_context), 'answers_count' => count($user_personality)],
                'language_rules' => ['label' => '🌐 Language Rules', 'content' => $language_rules, 'length' => mb_strlen($language_rules)],
                'response_rules' => ['label' => '📋 Response Rules', 'content' => $response_rules, 'length' => mb_strlen($response_rules)]
            ],
            'total_prompt_length' => mb_strlen($full_prompt),
            // ✅ เพิ่ม raw_sections เพื่อใช้ใน DevRev Article
            'raw_sections' => [
                'core_personality' => $core_personality,
                'perfume_knowledge' => $perfume_knowledge,
                'style_suggestions' => $style_suggestions,
                'user_context' => $user_context,
                'language_rules' => $language_rules,
                'response_rules' => $response_rules
            ]
        ];
        
        return ['prompt' => $full_prompt, 'details' => $details];
    }
    
    private function getLanguageRules($language) {
        $names = ['th'=>'ภาษาไทย (Thai)','en'=>'English','jp'=>'日本語 (Japanese)','kr'=>'한국어 (Korean)','cn'=>'中文 (Chinese)'];
        $n = $names[$language] ?? $names['th'];
        
        $rules = [
            'th' => "=== กฎการใช้ภาษา ===\n🌐 คุณ**ต้อง**ตอบเป็น{$n}เท่านั้น\n🌐 ห้ามเปลี่ยนภาษาเว้นแต่ผู้ใช้จะขอเปลี่ยนอย่างชัดเจน",
            'en' => "=== LANGUAGE ENFORCEMENT ===\n🌐 Respond in {$n} only\n🌐 Do NOT change unless explicitly requested",
            'ja' => "=== 言語ルール ===\n🌐 {$n}でのみ回答\n🌐 明示的に要求されない限り変更しない",
            'ko' => "=== 언어 규칙 ===\n🌐 {$n}로만 응답\n🌐 명시적 요청 없이 변경 안 함",
            'zh' => "=== 语言规则 ===\n🌐 仅{$n}回复\n🌐 无明确要求不更改"
        ];
        return $rules[$language] ?? $rules['th'];
    }
    
    private function getResponseRules($language) {
        if ($language === 'th') {
            return "=== กฎการตอบกลับ ===\n⛔ ห้ามใช้ \"ครับ/ค่ะ\" เด็ดขาด\n✅ ผู้ชาย → \"ครับ\" | ผู้หญิง → \"ค่ะ\"";
        }
        return "=== RESPONSE RULES ===\n✅ Be natural and conversational\n✅ Maintain consistent personality";
    }
    
    public function formatConversationHistory($chat_history, $limit = 10) {
        $messages = [];
        foreach (array_slice($chat_history, -$limit) as $chat) {
            $messages[] = ['role' => $chat['role'], 'content' => $chat['message_text']];
        }
        return $messages;
    }
    
    public function getModels() {
        $safe = [];
        foreach ($this->models as $m) {
            $s = $m;
            $s['api_key'] = !empty($m['api_key']) ? '***ENCRYPTED***' : null;
            $safe[] = $s;
        }
        return $safe;
    }
}
?>