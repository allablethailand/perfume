<?php
/**
 * AI Model Manager
 * 
 * จัดการ AI Models หลายตัว พร้อม Fallback System
 * รองรับ Groq, OpenAI, Anthropic, และ providers อื่นๆ
 * ✅ ปรับโครงสร้าง Prompt: Admin = นิสัยหลัก, User = นิสัยรอง
 * ✅ บังคับใช้ภาษาที่ user เลือก
 * ✅ ห้ามใช้ ครับ/ค่ะ ต้องเลือกอย่างใดอย่างหนึ่ง
 */

class AIModelManager {
    private $conn;
    private $models = [];
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->loadModels();
    }
    
    // ============================================
    // ฟังก์ชันเข้ารหัส/ถอดรหัส API Key
    // ============================================
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
    
    /**
     * ดึง AI Models ทั้งหมดที่ active เรียงตาม priority
     */
    private function loadModels() {
        $stmt = $this->conn->prepare("
            SELECT 
                model_id,
                model_code,
                model_name,
                provider,
                api_key,
                api_endpoint,
                is_free,
                max_tokens,
                cost_per_1k_tokens,
                priority
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
            throw new Exception('No active AI models found. Please activate at least one AI model.');
        }
    }
    
    /**
     * ส่งข้อความไปหา AI พร้อม Fallback System
     */
    public function chat($messages, $options = []) {
        $default_options = [
            'temperature' => 0.7,
            'max_tokens' => 1024,
            'top_p' => 1
        ];
        
        $params = array_merge($default_options, $options);
        
        $attempts = 0;
        $errors = [];
        
        foreach ($this->models as $model) {
            $attempts++;
            
            try {
                $start_time = microtime(true);
                
                $response = $this->sendToProvider($model, $messages, $params);
                
                $end_time = microtime(true);
                $response_time = round(($end_time - $start_time) * 1000);
                
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
    
    /**
     * ส่ง request ไปยัง Provider ที่ถูกต้อง
     */
    private function sendToProvider($model, $messages, $params) {
        if (empty($model['api_key'])) {
            return [
                'success' => false,
                'error' => 'API Key not configured or failed to decrypt'
            ];
        }
        
        switch (strtolower($model['provider'])) {
            case 'groq':
                return $this->sendToGroq($model, $messages, $params);
            
            case 'openai':
                return $this->sendToOpenAI($model, $messages, $params);
            
            case 'anthropic':
                return $this->sendToAnthropic($model, $messages, $params);
            
            default:
                return [
                    'success' => false,
                    'error' => 'Unsupported provider: ' . $model['provider']
                ];
        }
    }
    
    /**
     * ส่งไปยัง Groq API
     */
    private function sendToGroq($model, $messages, $params) {
        $api_url = $model['api_endpoint'] ?: 'https://api.groq.com/openai/v1/chat/completions';
        
        $request_params = [
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
            CURLOPT_POSTFIELDS => json_encode($request_params),
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            $error_data = json_decode($response, true);
            return [
                'success' => false,
                'error' => $error_data['error']['message'] ?? 'HTTP Error ' . $http_code
            ];
        }
        
        $data = json_decode($response, true);
        
        return [
            'success' => true,
            'message' => $data['choices'][0]['message']['content'] ?? '',
            'tokens_used' => $data['usage']['total_tokens'] ?? 0
        ];
    }
    
    /**
     * ส่งไปยัง OpenAI API
     */
    private function sendToOpenAI($model, $messages, $params) {
        $api_url = $model['api_endpoint'] ?: 'https://api.openai.com/v1/chat/completions';
        
        $request_params = [
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
            CURLOPT_POSTFIELDS => json_encode($request_params),
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            $error_data = json_decode($response, true);
            return [
                'success' => false,
                'error' => $error_data['error']['message'] ?? 'HTTP Error ' . $http_code
            ];
        }
        
        $data = json_decode($response, true);
        
        return [
            'success' => true,
            'message' => $data['choices'][0]['message']['content'] ?? '',
            'tokens_used' => $data['usage']['total_tokens'] ?? 0
        ];
    }
    
    /**
     * ส่งไปยัง Anthropic API (Claude)
     */
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
        
        $request_params = [
            'model' => $model['model_code'],
            'max_tokens' => min($params['max_tokens'], $model['max_tokens']),
            'messages' => $anthropic_messages
        ];
        
        if ($system) {
            $request_params['system'] = $system;
        }
        
        $ch = curl_init($api_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $model['api_key'],
                'anthropic-version: 2023-06-01'
            ],
            CURLOPT_POSTFIELDS => json_encode($request_params),
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            $error_data = json_decode($response, true);
            return [
                'success' => false,
                'error' => $error_data['error']['message'] ?? 'HTTP Error ' . $http_code
            ];
        }
        
        $data = json_decode($response, true);
        
        return [
            'success' => true,
            'message' => $data['content'][0]['text'] ?? '',
            'tokens_used' => $data['usage']['input_tokens'] + $data['usage']['output_tokens']
        ];
    }
    
    /**
     * ✅ สร้าง System Prompt แบบใหม่
     * โครงสร้าง:
     * 1. Admin Prompt (นิสัยหลัก) - มาจาก system_prompt, perfume_knowledge, style_suggestions
     * 2. User Personality (นิสัยรอง) - มาจาก user_personality_answers
     * 3. Language Enforcement - บังคับใช้ภาษาที่ user เลือก
     * 4. Response Format Rules - ห้ามใช้ ครับ/ค่ะ
     */
    public function buildSystemPrompt($ai_companion, $user_personality, $language = 'th') {
        $ai_name = $ai_companion['ai_name'] ?? 'AI Assistant';
        
        // ============================================
        // SECTION 1: นิสัยหลัก (จาก Admin)
        // ============================================
        $core_personality = trim($ai_companion['system_prompt'] ?? '');
        
        // ============================================
        // SECTION 2: ความรู้เฉพาะทาง
        // ============================================
        $perfume_knowledge = trim($ai_companion['perfume_knowledge'] ?? '');
        $style_suggestions = trim($ai_companion['style_suggestions'] ?? '');
        
        $expertise = '';
        if (!empty($perfume_knowledge)) {
            $expertise .= "\n\n=== ความรู้เกี่ยวกับน้ำหอม ===\n" . $perfume_knowledge;
        }
        if (!empty($style_suggestions)) {
            $expertise .= "\n\n=== คำแนะนำด้านสไตล์ ===\n" . $style_suggestions;
        }
        
        // ============================================
        // SECTION 3: นิสัยรอง (จาก User Personality)
        // ============================================
        $user_context = '';
        if (!empty($user_personality)) {
            $user_context = "\n\n=== ข้อมูลเพิ่มเติมเกี่ยวกับผู้ใช้ (นิสัยรองที่ต้องคำนึงถึง) ===\n";
            foreach ($user_personality as $answer) {
                $user_context .= "• {$answer['question']}: ";
                
                if (!empty($answer['choice_text'])) {
                    $user_context .= $answer['choice_text'];
                } elseif (!empty($answer['text_answer'])) {
                    $user_context .= $answer['text_answer'];
                } elseif ($answer['scale_value'] !== null) {
                    $user_context .= "คะแนน {$answer['scale_value']}/10";
                }
                $user_context .= "\n";
            }
            
            $user_context .= "\n💡 ใช้ข้อมูลข้างต้นเป็นบริบทเสริมในการตอบ แต่ยังคงรักษานิสัยหลักของคุณไว้";
        }
        
        // ============================================
        // SECTION 4: กฎการใช้ภาษา
        // ============================================
        $language_rules = $this->getLanguageRules($language);
        
        // ============================================
        // SECTION 5: กฎการตอบกลับ
        // ============================================
        $response_rules = $this->getResponseRules($language);
        
        // ============================================
        // รวม Prompt ทั้งหมด
        // ============================================
        $full_prompt = trim(
            $core_personality . 
            $expertise . 
            $user_context . 
            "\n\n" . $language_rules . 
            "\n\n" . $response_rules
        );
        
        // ============================================
        // สร้าง Details เพื่อ Debug
        // ============================================
        $details = [
            'ai_name' => $ai_name,
            'ai_code' => $ai_companion['ai_code'] ?? 'unknown',
            'language' => $language,
            'prompt_sections' => [
                'core_personality' => [
                    'label' => '🎭 นิสัยหลัก (Admin Prompt)',
                    'content' => $core_personality,
                    'length' => mb_strlen($core_personality)
                ],
                'perfume_knowledge' => [
                    'label' => '💧 Perfume Knowledge',
                    'content' => $perfume_knowledge,
                    'length' => mb_strlen($perfume_knowledge)
                ],
                'style_suggestions' => [
                    'label' => '✨ Style Suggestions',
                    'content' => $style_suggestions,
                    'length' => mb_strlen($style_suggestions)
                ],
                'user_personality' => [
                    'label' => '👤 นิสัยรอง (User Personality)',
                    'content' => $user_context,
                    'length' => mb_strlen($user_context),
                    'answers_count' => count($user_personality)
                ],
                'language_rules' => [
                    'label' => '🌐 Language Rules',
                    'content' => $language_rules,
                    'length' => mb_strlen($language_rules)
                ],
                'response_rules' => [
                    'label' => '📋 Response Format Rules',
                    'content' => $response_rules,
                    'length' => mb_strlen($response_rules)
                ]
            ],
            'total_prompt_length' => mb_strlen($full_prompt)
        ];
        
        return [
            'prompt' => $full_prompt,
            'details' => $details
        ];
    }
    
    /**
     * ✅ กฎการใช้ภาษา (บังคับใช้ภาษาที่ user เลือก)
     */
    private function getLanguageRules($language) {
        $language_names = [
            'th' => 'ภาษาไทย',
            'en' => 'English',
            'ja' => '日本語 (Japanese)',
            'ko' => '한국어 (Korean)',
            'zh' => '中文 (Chinese)'
        ];
        
        $lang_name = $language_names[$language] ?? $language_names['th'];
        
        return "=== กฎการใช้ภาษา (LANGUAGE ENFORCEMENT) ===
🌐 คุณ**ต้อง**ตอบเป็น{$lang_name}เท่านั้น ไม่ว่าผู้ใช้จะถามเป็นภาษาอะไร
🌐 ห้ามเปลี่ยนภาษาเว้นแต่ผู้ใช้จะขอเปลี่ยนอย่างชัดเจน เช่น \"เปลี่ยนเป็นภาษาอังกฤษ\" หรือ \"switch to English\"
🌐 ถ้าผู้ใช้ถามเป็นภาษาอื่น ให้ตอบเป็น{$lang_name}ตามปกติ";
    }
    
    /**
     * ✅ กฎการตอบกลับ (ห้ามใช้ ครับ/ค่ะ)
     */
    private function getResponseRules($language) {
        if ($language === 'th') {
            return "=== กฎการตอบกลับ (RESPONSE FORMAT RULES) ===
⛔ **ห้ามใช้ \"ครับ/ค่ะ\" เด็ดขาด** - ต้องเลือกใช้อย่างใดอย่างหนึ่งเท่านั้น
✅ ใช้ \"ครับ\" หรือ \"ค่ะ\" อย่างใดอย่างหนึ่งตลอดทั้งการสนทนา
✅ ถ้านิสัยของคุณเป็นผู้ชาย ให้ใช้ \"ครับ\" เท่านั้น
✅ ถ้านิสัยของคุณเป็นผู้หญิง ให้ใช้ \"ค่ะ\" เท่านั้น
✅ ถ้าไม่ระบุเพศ ให้เลือกตามบุคลิกที่เหมาะสม แล้วใช้แบบนั้นตลอด
📌 ตัวอย่างที่ถูก: \"สวัสดีครับ\" หรือ \"สวัสดีค่ะ\"
⛔ ตัวอย่างที่ผิด: \"สวัสดีครับ/ค่ะ\" (ห้ามมีเครื่องหมาย / )";
        } else {
            return "=== RESPONSE FORMAT RULES ===
✅ Be natural and conversational
✅ Maintain consistent personality throughout the conversation
✅ Adapt your tone based on user's personality profile";
        }
    }
    
    /**
     * Format conversation history สำหรับส่งไปยัง API
     */
    public function formatConversationHistory($chat_history, $limit = 10) {
        $messages = [];
        $recent_history = array_slice($chat_history, -$limit);
        
        foreach ($recent_history as $chat) {
            $messages[] = [
                'role' => $chat['role'],
                'content' => $chat['message_text']
            ];
        }
        
        return $messages;
    }
    
    /**
     * ดึงรายการ AI Models ทั้งหมด (ไม่แสดง API Key)
     */
    public function getModels() {
        $safe_models = [];
        foreach ($this->models as $model) {
            $safe_model = $model;
            $safe_model['api_key'] = !empty($model['api_key']) ? '***ENCRYPTED***' : null;
            $safe_models[] = $safe_model;
        }
        return $safe_models;
    }
}
?>