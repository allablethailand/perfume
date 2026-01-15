<?php
/**
 * Groq API Helper Class
 * 
 * ใช้สำหรับเชื่อมต่อกับ Groq API (ฟรี, เร็วมาก)
 * https://console.groq.com/
 */

class GroqAPI {
    private $api_key;
    private $api_url = 'https://api.groq.com/openai/v1/chat/completions';
    private $default_model = 'llama-3.3-70b-versatile'; // โมเดลฟรีที่แนะนำ
    
    public function __construct($api_key = null) {
        // ถ้าไม่ส่ง API key มา จะไปเอาจาก config
        $this->api_key = $api_key ?: $this->getApiKeyFromConfig();
    }
    
    /**
     * ดึง API Key จาก config หรือ environment
     */
    private function getApiKeyFromConfig() {
        // ตัวเลือก 1: จาก environment variable (แนะนำ)
        // if (getenv('GROQ_API_KEY')) {
        //     return getenv('GROQ_API_KEY');
        // }
        
        // ตัวเลือก 2: จากไฟล์ config
        if (file_exists(__DIR__ . '/../config/groq_config.php')) {
            $config = require __DIR__ . '/../config/groq_config.php';
            return $config['api_key'] ?? null;
        }
        
        throw new Exception('Groq API key not found. Please set GROQ_API_KEY environment variable or create config file.');
    }
    
    /**
     * ส่งข้อความไปหา Groq AI และรับคำตอบกลับ
     * 
     * @param array $messages - รูปแบบ OpenAI chat format
     * @param array $options - ตัวเลือกเพิ่มเติม
     * @return array
     */
    public function chat($messages, $options = []) {
        $default_options = [
            'model' => $this->default_model,
            'temperature' => 0.7,
            'max_tokens' => 1024,
            'top_p' => 1,
            'stream' => false
        ];
        
        $params = array_merge($default_options, $options, [
            'messages' => $messages
        ]);
        
        $start_time = microtime(true);
        
        try {
            $response = $this->sendRequest($params);
            $end_time = microtime(true);
            
            return [
                'success' => true,
                'message' => $response['choices'][0]['message']['content'] ?? '',
                'model' => $response['model'] ?? $params['model'],
                'tokens_used' => $response['usage']['total_tokens'] ?? 0,
                'response_time_ms' => round(($end_time - $start_time) * 1000),
                'raw_response' => $response
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => '',
                'tokens_used' => 0,
                'response_time_ms' => 0
            ];
        }
    }
    
    /**
     * ส่ง HTTP Request ไปยัง Groq API
     */
    private function sendRequest($params) {
        $ch = curl_init($this->api_url);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->api_key
            ],
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('cURL Error: ' . $error);
        }
        
        if ($http_code !== 200) {
            $error_data = json_decode($response, true);
            $error_message = $error_data['error']['message'] ?? 'HTTP Error ' . $http_code;
            throw new Exception($error_message);
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response');
        }
        
        return $data;
    }
    
    /**
     * สร้าง System Prompt สำหรับ AI Companion
     */
    public function buildSystemPrompt($ai_companion, $user_personality, $language = 'th') {
        $lang_suffix = '_' . $language;
        
        $system_prompt = $ai_companion['system_prompt' . $lang_suffix] ?? '';
        $perfume_knowledge = $ai_companion['perfume_knowledge' . $lang_suffix] ?? '';
        $style_suggestions = $ai_companion['style_suggestions' . $lang_suffix] ?? '';
        
        // รวม personality ของ user เข้าไปด้วย
        $personality_text = '';
        if (!empty($user_personality)) {
            $personality_text = "\n\n=== User Personality ===\n";
            foreach ($user_personality as $answer) {
                $personality_text .= $answer['question'] . ": " . $answer['answer'] . "\n";
            }
        }
        
        $full_prompt = trim($system_prompt . "\n\n" . $perfume_knowledge . "\n\n" . $style_suggestions . $personality_text);
        
        return $full_prompt;
    }
    
    /**
     * Format conversation history สำหรับส่งไปยัง API
     */
    public function formatConversationHistory($chat_history, $limit = 10) {
        $messages = [];
        
        // เอาแค่ N ข้อความล่าสุด (เพื่อไม่ให้เกิน token limit)
        $recent_history = array_slice($chat_history, -$limit);
        
        foreach ($recent_history as $chat) {
            $messages[] = [
                'role' => $chat['role'], // 'user' or 'assistant'
                'content' => $chat['message_text']
            ];
        }
        
        return $messages;
    }
    
    /**
     * ตรวจสอบว่า API Key ใช้งานได้หรือไม่
     */
    public function testConnection() {
        try {
            $response = $this->chat([
                ['role' => 'user', 'content' => 'Hello, this is a test message.']
            ], ['max_tokens' => 50]);
            
            return $response['success'];
        } catch (Exception $e) {
            return false;
        }
    }
}
?>