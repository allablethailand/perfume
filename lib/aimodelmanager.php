<?php
/**
 * AI Model Manager
 * 
 * จัดการ AI Models หลายตัว พร้อม Fallback System
 * รองรับ Groq, OpenAI, Anthropic, และ providers อื่นๆ
 */

class AIModelManager {
    private $conn;
    private $models = [];
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->loadModels();
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
            $this->models[] = $row;
        }
        $stmt->close();
        
        if (empty($this->models)) {
            throw new Exception('No active AI models found. Please activate at least one AI model.');
        }
    }
    
    /**
     * ส่งข้อความไปหา AI พร้อม Fallback System
     * 
     * @param array $messages - รูปแบบ OpenAI chat format
     * @param array $options - ตัวเลือกเพิ่มเติม
     * @return array
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
        
        // ✅ ลองส่งไปยัง AI แต่ละตัว ตาม priority
        foreach ($this->models as $model) {
            $attempts++;
            
            try {
                $start_time = microtime(true);
                
                // เลือก Provider ที่ถูกต้อง
                $response = $this->sendToProvider($model, $messages, $params);
                
                $end_time = microtime(true);
                $response_time = round(($end_time - $start_time) * 1000);
                
                // ถ้าสำเร็จ return ทันที
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
                
                // ถ้าไม่สำเร็จ เก็บ error ไว้
                $errors[] = "{$model['model_name']}: {$response['error']}";
                
            } catch (Exception $e) {
                $errors[] = "{$model['model_name']}: {$e->getMessage()}";
            }
        }
        
        // ถ้าลองทุกตัวแล้วยังไม่สำเร็จ
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
        
        // แปลง messages format สำหรับ Anthropic
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
     * สร้าง System Prompt สำหรับ AI Companion
     */
    public function buildSystemPrompt($ai_companion, $user_personality, $language = 'th') {
        // ✅ 1. ดึง Prompt หลักจาก Admin (AI Companion)
        $system_prompt = $ai_companion['system_prompt'] ?? '';
        $perfume_knowledge = $ai_companion['perfume_knowledge'] ?? '';
        $style_suggestions = $ai_companion['style_suggestions'] ?? '';
        
        // ✅ 2. สร้าง Prompt รองจาก User Personality
        $personality_text = '';
        if (!empty($user_personality)) {
            $personality_text = "\n\n=== ข้อมูลเพิ่มเติมเกี่ยวกับผู้ใช้ (User Personality) ===\n";
            foreach ($user_personality as $answer) {
                $personality_text .= "• {$answer['question']}: ";
                
                if (!empty($answer['choice_text'])) {
                    $personality_text .= $answer['choice_text'];
                } elseif (!empty($answer['text_answer'])) {
                    $personality_text .= $answer['text_answer'];
                } elseif ($answer['scale_value'] !== null) {
                    $personality_text .= "คะแนน {$answer['scale_value']}/10";
                }
                $personality_text .= "\n";
            }
            
            $personality_text .= "\n📌 **โปรดปรับคำตอบให้เหมาะกับบุคลิกและความชอบของผู้ใช้ด้านบน**";
        }
        
        // ✅ 3. รวม Prompt ทั้งหมด
        $full_prompt = trim(
            $system_prompt . "\n\n" . 
            $perfume_knowledge . "\n\n" . 
            $style_suggestions . 
            $personality_text
        );
        
        // ✅ 4. สร้าง Details เพื่อ Debug
        $details = [
            'ai_name' => $ai_companion['ai_name'] ?? 'Unknown AI',
            'ai_code' => $ai_companion['ai_code'] ?? 'unknown',
            'language' => $language,
            'prompt_sections' => [
                'system_prompt' => [
                    'label' => '🤖 System Prompt (ฝั่ง Admin)',
                    'content' => $system_prompt,
                    'length' => mb_strlen($system_prompt)
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
                    'label' => '👤 User Personality (คำตอบของ User)',
                    'content' => $personality_text,
                    'length' => mb_strlen($personality_text),
                    'answers_count' => count($user_personality)
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
     * ดึงรายการ AI Models ทั้งหมด
     */
    public function getModels() {
        return $this->models;
    }
}
?>