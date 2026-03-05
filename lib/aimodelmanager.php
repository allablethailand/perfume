<?php
/**
 * AI Model Manager
 * 
 * FIXED:
 * ✅ buildSystemPrompt: ลบ language rules ออก (ai_chat.php จัดการแล้ว ไม่ duplicate)
 * ✅ buildSystemPrompt: ห้าม emoji อย่างเด็ดขาดในทุก section (เพิ่มรายละเอียดชัดเจน)
 * ✅ buildSystemPrompt: ไม่สั่งให้ AI แนะนำตัวเองในส่วนนี้ (ai_chat.php จัดการ)
 * ✅ sendToGemini: ใช้ systemInstruction + contents แบบ native
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
            $key  = $this->getEncryptionKey();
            $data = base64_decode($encryptedKey);
            $iv   = substr($data, 0, 16);
            $enc  = substr($data, 16);
            $dec  = openssl_decrypt($enc, 'AES-256-CBC', $key, 0, $iv);
            return $dec !== false ? $dec : null;
        } catch (Exception $e) {
            error_log("Decryption error: " . $e->getMessage());
            return null;
        }
    }
    
    private function loadModels() {
        $stmt = $this->conn->prepare("
            SELECT model_id, model_code, model_name, provider,
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
            'temperature'    => 0.7,
            'max_tokens'     => 1024,
            'top_p'          => 1,
            'devrev_user_id' => null
        ], $options);
        
        $last_user_message = '';
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if ($messages[$i]['role'] === 'user') {
                $last_user_message = trim($messages[$i]['content']);
                break;
            }
        }
        
        if (strlen($last_user_message) < 2) {
            return [
                'success'          => false,
                'error'            => 'Message too short (minimum 2 characters required)',
                'message'          => '',
                'tokens_used'      => 0,
                'response_time_ms' => 0,
                'attempts'         => 0
            ];
        }
        
        $attempts = 0;
        $errors   = [];
        
        foreach ($this->models as $model) {
            $attempts++;
            try {
                $start_time    = microtime(true);
                $response      = $this->sendToProvider($model, $messages, $params);
                $response_time = round((microtime(true) - $start_time) * 1000);
                
                if ($response['success']) {
                    return [
                        'success'          => true,
                        'message'          => $response['message'],
                        'model_used'       => $model['model_code'],
                        'model_name'       => $model['model_name'],
                        'provider'         => $model['provider'],
                        'tokens_used'      => $response['tokens_used'],
                        'response_time_ms' => $response_time,
                        'attempts'         => $attempts,
                        'is_free'          => (bool)$model['is_free']
                    ];
                }
                $errors[] = "{$model['model_name']}: {$response['error']}";
            } catch (Exception $e) {
                $errors[] = "{$model['model_name']}: {$e->getMessage()}";
            }
        }
        
        return [
            'success'          => false,
            'error'            => implode(' | ', $errors),
            'message'          => '',
            'tokens_used'      => 0,
            'response_time_ms' => 0,
            'attempts'         => $attempts
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
            'model'       => $model['model_code'],
            'messages'    => $messages,
            'temperature' => $params['temperature'],
            'max_tokens'  => min($params['max_tokens'], $model['max_tokens']),
            'top_p'       => $params['top_p']
        ];
        $ch = curl_init($api_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $model['api_key']
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 30
        ]);
        $response  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http_code !== 200) {
            $err = json_decode($response, true);
            return ['success' => false, 'error' => $err['error']['message'] ?? 'HTTP ' . $http_code];
        }
        $data = json_decode($response, true);
        return [
            'success'      => true,
            'message'      => $data['choices'][0]['message']['content'] ?? '',
            'tokens_used'  => $data['usage']['total_tokens'] ?? 0
        ];
    }

    private function sendToGemini($model, $messages, $params) {
        $api_key = $model['api_key'];
        if (empty($api_key)) {
            return ['success' => false, 'error' => 'API Key not configured'];
        }

        error_log("🔵 [Gemini] Starting request (native systemInstruction mode)");

        $system_instruction = '';
        $chat_messages      = [];

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $system_instruction = $msg['content'];
            } else {
                $chat_messages[] = $msg;
            }
        }

        $contents = [];
        foreach ($chat_messages as $msg) {
            $role = ($msg['role'] === 'assistant') ? 'model' : 'user';
            $contents[] = [
                'role'  => $role,
                'parts' => [['text' => $msg['content']]]
            ];
        }

        if (empty($contents)) {
            return ['success' => false, 'error' => 'No user message found'];
        }

        $last = end($contents);
        if ($last['role'] !== 'user') {
            error_log("⚠️ [Gemini] Last message is not user role, this may cause issues");
        }

        $payload = [
            'contents'         => $contents,
            'generationConfig' => [
                'temperature'     => $params['temperature'],
                'maxOutputTokens' => min($params['max_tokens'], $model['max_tokens']),
                'topP'            => $params['top_p']
            ]
        ];

        if (!empty($system_instruction)) {
            $payload['systemInstruction'] = [
                'parts' => [['text' => $system_instruction]]
            ];
        }

        $api_url = $model['api_endpoint'];
        if (strpos($api_url, '?') === false) {
            $api_url .= '?key=' . $api_key;
        } else {
            $api_url .= '&key=' . $api_key;
        }

        $json_payload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json_payload === false) {
            return ['success' => false, 'error' => 'Failed to encode JSON payload'];
        }

        error_log("📤 [Gemini] Sending - system: " . strlen($system_instruction) . " chars, messages: " . count($contents));

        $ch = curl_init($api_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => $json_payload,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);

        $start_time   = microtime(true);
        $response     = curl_exec($ch);
        $elapsed_time = round((microtime(true) - $start_time) * 1000);
        $http_code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $curl_error = curl_error($ch);
            curl_close($ch);
            error_log("❌ [Gemini] cURL Error: {$curl_error}");
            return ['success' => false, 'error' => "cURL Error: {$curl_error}"];
        }
        curl_close($ch);

        error_log("📊 [Gemini] HTTP: {$http_code} | Time: {$elapsed_time}ms");

        if ($http_code !== 200) {
            $err       = json_decode($response, true);
            $error_msg = $err['error']['message'] ?? "HTTP {$http_code}";
            error_log("❌ [Gemini] Error: {$error_msg}");
            return ['success' => false, 'error' => $error_msg];
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'error' => 'Invalid JSON response'];
        }

        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if (empty($text)) {
            error_log("❌ [Gemini] No text in response: " . json_encode($data));
            return ['success' => false, 'error' => 'No text in response'];
        }

        $input_tokens  = $data['usageMetadata']['promptTokenCount']     ?? 0;
        $output_tokens = $data['usageMetadata']['candidatesTokenCount']  ?? 0;
        $total_tokens  = $input_tokens + $output_tokens;

        error_log("✅ [Gemini] Success — tokens: {$total_tokens}, response: " . strlen($text) . " chars");

        return [
            'success'     => true,
            'message'     => $text,
            'tokens_used' => $total_tokens
        ];
    }

    private function sendToOpenAI($model, $messages, $params) {
        $api_url = $model['api_endpoint'] ?: 'https://api.openai.com/v1/chat/completions';
        $payload = [
            'model'       => $model['model_code'],
            'messages'    => $messages,
            'temperature' => $params['temperature'],
            'max_tokens'  => min($params['max_tokens'], $model['max_tokens'])
        ];
        $ch = curl_init($api_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $model['api_key']
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 30
        ]);
        $response  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http_code !== 200) {
            $err = json_decode($response, true);
            return ['success' => false, 'error' => $err['error']['message'] ?? 'HTTP ' . $http_code];
        }
        $data = json_decode($response, true);
        return [
            'success'     => true,
            'message'     => $data['choices'][0]['message']['content'] ?? '',
            'tokens_used' => $data['usage']['total_tokens'] ?? 0
        ];
    }
    
    private function sendToAnthropic($model, $messages, $params) {
        $api_url            = $model['api_endpoint'] ?: 'https://api.anthropic.com/v1/messages';
        $system             = '';
        $anthropic_messages = [];
        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $system = $msg['content'];
            } else {
                $anthropic_messages[] = $msg;
            }
        }
        $payload = [
            'model'      => $model['model_code'],
            'max_tokens' => min($params['max_tokens'], $model['max_tokens']),
            'messages'   => $anthropic_messages
        ];
        if ($system) $payload['system'] = $system;
        $ch = curl_init($api_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $model['api_key'],
                'anthropic-version: 2023-06-01'
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 30
        ]);
        $response  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http_code !== 200) {
            $err = json_decode($response, true);
            return ['success' => false, 'error' => $err['error']['message'] ?? 'HTTP ' . $http_code];
        }
        $data = json_decode($response, true);
        return [
            'success'     => true,
            'message'     => $data['content'][0]['text'] ?? '',
            'tokens_used' => ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0)
        ];
    }
    
    private function sendToDevRev($model, $messages, $params) {
        $api_key = $model['api_key'];
        if (empty($api_key)) {
            $api_key = getenv('DEVREV_API_TOKEN') ?: ($_ENV['DEVREV_API_TOKEN'] ?? null);
        }
        if (empty($api_key)) {
            return ['success' => false, 'error' => 'API Key not configured'];
        }

        $user_display_id = null;
        if (!empty($params['devrev_user_id'])) {
            $stmt = $this->conn->prepare("SELECT devrev_display_id FROM mb_user WHERE user_id = ?");
            $stmt->bind_param('i', $params['devrev_user_id']);
            $stmt->execute();
            $ud = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($ud && !empty($ud['devrev_display_id'])) {
                $user_display_id = $ud['devrev_display_id'];
            }
        }

        $article_content = '';
        if (!empty($params['devrev_user_id'])) {
            $article_content = $this->buildArticleContext($api_key, $params['devrev_user_id']);
        }

        $prompt = $this->buildCompletePrompt($messages, $article_content);
        return $this->sendDevRevRequest($api_key, $prompt, $user_display_id);
    }
    
    private function buildCompletePrompt($messages, $article_content = '') {
        $last_user_msg = '';
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if ($messages[$i]['role'] === 'user') {
                $last_user_msg = trim($messages[$i]['content']);
                break;
            }
        }
        if (empty($last_user_msg)) return "Hello";
        if (!empty($article_content)) {
            $clean_content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $article_content);
            return trim($clean_content) . "\n\n---\n\nUser Question: " . $last_user_msg;
        }
        return $last_user_msg;
    }
    
    private function sendDevRevRequest($api_key, $prompt, $user_display_id = null) {
        $api_url = 'https://api.devrev.ai/recommendations.get-reply';
        if (strlen($prompt) < 1)    return ['success' => false, 'error' => 'Prompt too short'];
        if (strlen($prompt) > 10000) $prompt = mb_substr($prompt, 0, 10000, 'UTF-8');

        $post_data = json_encode(['query' => $prompt], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($post_data === false) return ['success' => false, 'error' => 'Failed to encode JSON'];

        $ch = curl_init($api_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $post_data,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . trim($api_key)
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);

        $response  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (curl_errno($ch)) {
            $e = curl_error($ch); curl_close($ch);
            return ['success' => false, 'error' => "cURL Error: {$e}"];
        }
        curl_close($ch);

        if (empty($response))   return ['success' => false, 'error' => 'Empty response from DevRev'];
        if ($http_code !== 200) {
            $err = json_decode($response, true);
            return ['success' => false, 'error' => $err['message'] ?? "HTTP {$http_code}"];
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) return ['success' => false, 'error' => 'Invalid JSON response'];

        $reply = $decoded['reply'] ?? $decoded['response'] ?? $decoded['text'] ?? '';
        if (empty($reply)) return ['success' => false, 'error' => 'Empty reply from DevRev'];

        return ['success' => true, 'message' => $reply, 'tokens_used' => 0];
    }
    
    private function buildArticleContext($api_key, $user_id) {
        $stmt = $this->conn->prepare("SELECT devrev_chat_article_id, devrev_prompt_article_id FROM mb_user WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) return '';

        $parts = [];
        if (!empty($row['devrev_prompt_article_id'])) {
            $content = $this->fetchArticleContent($api_key, $row['devrev_prompt_article_id']);
            if ($content) {
                $lines     = explode("\n", $content);
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
                }
            }
        }
        if (!empty($row['devrev_chat_article_id'])) {
            $content = $this->fetchArticleContent($api_key, $row['devrev_chat_article_id']);
            if ($content) {
                $lines  = explode("\n", $content);
                $recent = [];
                for ($i = count($lines) - 1; $i >= 0 && count($recent) < 10; $i--) {
                    $line = trim($lines[$i]);
                    if (preg_match('/^\[[\d\-\s:]+\]\s+(User|Assistant):\s*(.*)/', $line, $m)) {
                        array_unshift($recent, $m[1] . ": " . $m[2]);
                    }
                }
                if (!empty($recent)) {
                    $parts[] = "=== Recent Conversation ===\n" . implode("\n", $recent);
                }
            }
        }
        return implode("\n\n", $parts);
    }
    
    private function fetchArticleContent($api_key, $article_id) {
        $ch = curl_init('https://api.devrev.ai/articles.get');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $api_key
            ],
            CURLOPT_POSTFIELDS     => json_encode(['id' => $article_id]),
            CURLOPT_TIMEOUT        => 15
        ]);
        $response  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http_code !== 200) return '';

        $data      = json_decode($response, true);
        $artifacts = $data['article']['resource']['artifacts'] ?? [];
        if (empty($artifacts)) return '';

        $artifact_id = end($artifacts)['id'] ?? null;
        if (!$artifact_id) return '';

        $ch = curl_init('https://api.devrev.ai/artifacts.locate');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $api_key
            ],
            CURLOPT_POSTFIELDS     => json_encode(['id' => $artifact_id]),
            CURLOPT_TIMEOUT        => 15
        ]);
        $response  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http_code !== 200) return '';

        $download_url = json_decode($response, true)['url'] ?? null;
        if (!$download_url) return '';

        $ch = curl_init($download_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_FOLLOWLOCATION => true
        ]);
        $content   = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http_code !== 200 || empty($content)) return '';

        if (strlen($content) > 5000) {
            $content = substr($content, 0, 5000) . "\n[...truncated...]";
        }
        return $content;
    }
    
    /**
     * buildSystemPrompt
     * - ห้าม emoji อย่างเด็ดขาด ระบุให้ชัดเจนที่สุด
     * - ไม่ duplicate language/identity (ai_chat.php จัดการ)
     */
    public function buildSystemPrompt($ai_companion, $user_personality = []) {
        $sections     = [];
        $raw_sections = [];
        
        // 1. Core Personality
        if (!empty($ai_companion['system_prompt'])) {
            $sections[]                        = "=== YOUR CORE PERSONALITY ===\n" . trim($ai_companion['system_prompt']);
            $raw_sections['core_personality']  = trim($ai_companion['system_prompt']);
        }
        
        // 2. Perfume Knowledge
        if (!empty($ai_companion['perfume_knowledge'])) {
            $sections[]                         = "=== PERFUME KNOWLEDGE ===\n" . trim($ai_companion['perfume_knowledge']);
            $raw_sections['perfume_knowledge']  = trim($ai_companion['perfume_knowledge']);
        }
        
        // 3. Style Suggestions
        if (!empty($ai_companion['style_suggestions'])) {
            $sections[]                          = "=== STYLE & FASHION GUIDANCE ===\n" . trim($ai_companion['style_suggestions']);
            $raw_sections['style_suggestions']   = trim($ai_companion['style_suggestions']);
        }
        
        // 4. User Personality Context
        if (!empty($user_personality)) {
            $user_context  = "=== USER PERSONALITY & PREFERENCES ===\n";
            $user_context .= "Take the following user traits into account:\n\n";
            foreach ($user_personality as $idx => $answer) {
                $num           = $idx + 1;
                $q             = $answer['question'] ?? 'Question';
                $user_context .= "{$num}. {$q}\n";
                if (!empty($answer['choice_text'])) {
                    $user_context .= "   -> {$answer['choice_text']}\n";
                } elseif (!empty($answer['text_answer'])) {
                    $user_context .= "   -> {$answer['text_answer']}\n";
                } elseif (isset($answer['scale_value'])) {
                    $user_context .= "   -> Scale: {$answer['scale_value']}/10\n";
                }
                $user_context .= "\n";
            }
            $sections[]                    = $user_context;
            $raw_sections['user_context']  = $user_context;
        }
        
        // 5. Response Guidelines - ห้าม emoji อย่างเด็ดขาด
        $response_rules  = "=== RESPONSE GUIDELINES ===\n";
        $response_rules .= "- Be warm and natural, like a knowledgeable friend. Not a robot, not a customer service rep.\n";
        $response_rules .= "- ABSOLUTELY NO emojis. This is a hard rule. Do not use any emoji, emoticon, kaomoji, or Unicode pictograph.\n";
        $response_rules .= "- This includes: smileys, hearts, stars, flowers, animals, food, hands, flags, symbols — anything visual.\n";
        $response_rules .= "- If you include even one emoji, the entire response is considered invalid and broken.\n";
        $response_rules .= "- Do NOT greet the user or say your name at the start of every message.\n";
        $response_rules .= "- Keep responses concise and on-topic. Do not pad with filler phrases.\n";
        $response_rules .= "- Use perfume knowledge naturally when relevant, not forced.\n";
        $response_rules .= "- Stay attentive to the user's mood and what they actually asked.\n";
        $response_rules .= "- Remember context from previous messages in this conversation.\n";

        $sections[]                     = $response_rules;
        $raw_sections['response_rules'] = $response_rules;
        
        $full_prompt = implode("\n\n", $sections);
        
        return [
            'prompt'  => $full_prompt,
            'details' => [
                'sections_count'        => count($sections),
                'has_personality'       => !empty($user_personality),
                'has_perfume_knowledge' => !empty($ai_companion['perfume_knowledge']),
                'has_style_suggestions' => !empty($ai_companion['style_suggestions']),
                'raw_sections'          => $raw_sections
            ]
        ];
    }

    public function formatConversationHistory($chat_history, $limit = 10) {
        $formatted = [];
        $recent    = array_slice($chat_history, -$limit);
        foreach ($recent as $msg) {
            $formatted[] = [
                'role'    => $msg['role'],
                'content' => $msg['message_text']
            ];
        }
        return $formatted;
    }
    
    public function getModels() {
        $safe = [];
        foreach ($this->models as $m) {
            $s            = $m;
            $s['api_key'] = !empty($m['api_key']) ? '***ENCRYPTED***' : null;
            $safe[]       = $s;
        }
        return $safe;
    }
}
?>