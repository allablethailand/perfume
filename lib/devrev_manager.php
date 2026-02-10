<?php
/**
 * DevRev Manager
 * 
 * ✅ ไม่บังคับภาษา - AI ตอบตามภาษาที่ user ใช้
 * ✅ Prompt ทั้งหมดเป็นภาษาอังกฤษ
 */

class DevRevManager {
    private $conn;
    private $api_token;
    private $api_base_url = 'https://api.devrev.ai';
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->loadApiToken();
    }
    
    private function loadApiToken() {
        $this->api_token = getenv('DEVREV_API_TOKEN') ?: $_ENV['DEVREV_API_TOKEN'] ?? null;
        
        if (!$this->api_token) {
            $stmt = $this->conn->prepare("
                SELECT api_key 
                FROM ai_models 
                WHERE provider = 'devrev' AND is_active = 1 
                LIMIT 1
            ");
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $this->api_token = $this->decryptApiKey($row['api_key']);
            }
            $stmt->close();
        }
        
        if (!$this->api_token) {
            throw new Exception('DEVREV_API_TOKEN not found in .env or database');
        }
        
        error_log("✅ [DevRev] API Token loaded successfully (length: " . strlen($this->api_token) . " chars)");
    }
    
    private function decryptApiKey($encryptedKey) {
        if (empty($encryptedKey)) return null;
        
        try {
            $secret_key = getenv('JWT_SECRET_KEY') ?: $_ENV['JWT_SECRET_KEY'] ?? '';
            $key = hash('sha256', $secret_key, true);
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
    
    private function callDevRev($endpoint, $method = 'POST', $payload = null) {
        $url = $this->api_base_url . $endpoint;
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . trim($this->api_token)
        ];
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        
        if ($method === 'POST' && $payload !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        } elseif ($method === 'GET') {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $curl_error = curl_error($ch);
            error_log("❌ [DevRev] cURL Error: {$curl_error}");
            curl_close($ch);
            return [
                'success' => false,
                'error' => "cURL Error: {$curl_error}",
                'http_code' => 0
            ];
        }
        
        curl_close($ch);
        
        $decoded = json_decode($response, true);
        
        if ($http_code >= 200 && $http_code < 300) {
            return [
                'success' => true,
                'data' => $decoded,
                'http_code' => $http_code
            ];
        }
        
        error_log("❌ [DevRev] Error response: {$response}");
        
        return [
            'success' => false,
            'error' => $decoded['error']['message'] ?? 'Unknown error',
            'http_code' => $http_code
        ];
    }
    
    public function ensureDevUser($user_id, $email, $display_name) {
        $stmt = $this->conn->prepare("
            SELECT devrev_dev_user_id, devrev_display_id 
            FROM mb_user 
            WHERE user_id = ?
        ");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        $stmt->close();
        
        if (!empty($user_data['devrev_dev_user_id']) && !empty($user_data['devrev_display_id'])) {
            error_log("✅ Dev User already exists: {$user_data['devrev_display_id']}");
            return [
                'dev_user_id' => $user_data['devrev_dev_user_id'],
                'display_id' => $user_data['devrev_display_id']
            ];
        }
        
        $response = $this->callDevRev('/dev-users.create', 'POST', [
            'email' => $email,
            'full_name' => $display_name,
            'state' => 'shadow'
        ]);
        
        if (!$response['success']) {
            throw new Exception('Failed to create dev user: ' . $response['error']);
        }
        
        $dev_user_id = $response['data']['dev_user']['id'];
        $display_id = $response['data']['dev_user']['display_id'];
        
        $update_stmt = $this->conn->prepare("
            UPDATE mb_user 
            SET devrev_dev_user_id = ?, devrev_display_id = ? 
            WHERE user_id = ?
        ");
        $update_stmt->bind_param('ssi', $dev_user_id, $display_id, $user_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        error_log("✅ Created new dev user: {$display_id}");
        
        return [
            'dev_user_id' => $dev_user_id,
            'display_id' => $display_id
        ];
    }
    
    private function createArtifact($file_name, $content) {
        error_log("🔵 [DevRev] Starting createArtifact: {$file_name}");
        
        $prepare_response = $this->callDevRev('/artifacts.prepare', 'POST', [
            'file_name' => $file_name
        ]);
        
        if (!$prepare_response['success']) {
            throw new Exception('Failed to prepare artifact: ' . ($prepare_response['error'] ?? 'Unknown error'));
        }
        
        $artifact_id = $prepare_response['data']['id'] ?? null;
        $upload_url = $prepare_response['data']['url'] ?? null;
        $form_data = $prepare_response['data']['form_data'] ?? null;
        
        if (!$artifact_id || !$upload_url || !$form_data) {
            throw new Exception('Incomplete artifact prepare response');
        }
        
        error_log("✅ [DevRev] Artifact prepared: {$artifact_id}");
        
        // Upload content
        $boundary = '----B' . uniqid();
        $post_data = '';
        
        foreach ($form_data as $field) {
            $post_data .= "--{$boundary}\r\n";
            $post_data .= "Content-Disposition: form-data; name=\"{$field['key']}\"\r\n\r\n";
            $post_data .= "{$field['value']}\r\n";
        }
        
        $post_data .= "--{$boundary}\r\n";
        $post_data .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$file_name}\"\r\n";
        $post_data .= "Content-Type: text/plain; charset=utf-8\r\n\r\n";
        $post_data .= $content . "\r\n";
        $post_data .= "--{$boundary}--\r\n";
        
        $ch = curl_init($upload_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Content-Type: multipart/form-data; boundary={$boundary}"
            ],
            CURLOPT_POSTFIELDS => $post_data
        ]);
        
        $upload_response = curl_exec($ch);
        $upload_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($upload_http_code < 200 || $upload_http_code >= 300) {
            throw new Exception("Failed to upload artifact content: HTTP {$upload_http_code}");
        }
        
        error_log("✅ [DevRev] Content uploaded to artifact: {$artifact_id}");
        
        return $artifact_id;
    }
    
    private function getDefaultPart() {
        static $default_part_id = null;
        
        if ($default_part_id !== null) {
            return $default_part_id;
        }
        
        $response = $this->callDevRev('/parts.list', 'POST', [
            'type' => ['product']
        ]);
        
        if ($response['success'] && !empty($response['data']['parts'])) {
            $default_part_id = $response['data']['parts'][0]['id'];
            error_log("✅ [DevRev] Using existing part: {$default_part_id}");
            return $default_part_id;
        }
        
        $create_response = $this->callDevRev('/parts.create', 'POST', [
            'type' => 'product',
            'name' => 'AI Chat System',
            'description' => 'Default product for AI chat conversations and system prompts'
        ]);
        
        if ($create_response['success']) {
            $default_part_id = $create_response['data']['part']['id'];
            error_log("✅ [DevRev] Created default part: {$default_part_id}");
            return $default_part_id;
        }
        
        return null;
    }
    
    private function getGlobalArticleIds($user_id) {
        $stmt = $this->conn->prepare("
            SELECT devrev_chat_article_id, devrev_prompt_article_id 
            FROM mb_user 
            WHERE user_id = ?
        ");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return [
            'chat_article_id' => $row['devrev_chat_article_id'] ?? null,
            'prompt_article_id' => $row['devrev_prompt_article_id'] ?? null
        ];
    }
    
    private function saveGlobalArticleIds($user_id, $chat_article_id = null, $prompt_article_id = null) {
        if ($chat_article_id) {
            $stmt = $this->conn->prepare("UPDATE mb_user SET devrev_chat_article_id = ? WHERE user_id = ?");
            $stmt->bind_param('si', $chat_article_id, $user_id);
            $stmt->execute();
            $stmt->close();
            error_log("✅ [DevRev] Saved global chat_article_id: {$chat_article_id}");
        }
        
        if ($prompt_article_id) {
            $stmt = $this->conn->prepare("UPDATE mb_user SET devrev_prompt_article_id = ? WHERE user_id = ?");
            $stmt->bind_param('si', $prompt_article_id, $user_id);
            $stmt->execute();
            $stmt->close();
            error_log("✅ [DevRev] Saved global prompt_article_id: {$prompt_article_id}");
        }
        
        $this->conn->commit();
    }
    
    private function createArticle($title, $artifact_id, $dev_user_id, $display_id) {
        $part_id = $this->getDefaultPart();
        if (!$part_id) {
            throw new Exception('No part ID available');
        }
        
        $owner_dev_user_id = 'don:identity:dvrv-us-1:devo/1y6tLzaW99:devu/2';
        
        $payload = [
            'title' => $title,
            'owned_by' => [$owner_dev_user_id],
            'authored_by' => [$display_id],
            'applies_to_parts' => [$part_id],
            'resource' => new stdClass(),
            'status' => 'published',
            'access_level' => 'internal'
        ];
        
        $response = $this->callDevRev('/articles.create', 'POST', $payload);
        
        if (!$response['success']) {
            throw new Exception('Failed to create article: ' . ($response['error'] ?? 'Unknown'));
        }
        
        $article_id = $response['data']['article']['id'] ?? null;
        if (!$article_id) {
            throw new Exception('No article ID returned');
        }
        
        error_log("✅ [DevRev] Article created: {$article_id}");
        
        // Add artifact
        $update_payload = [
            'id' => $article_id,
            'artifacts' => [
                'set' => [$artifact_id]
            ]
        ];
        
        $update_response = $this->callDevRev('/articles.update', 'POST', $update_payload);
        
        if (!$update_response['success']) {
            throw new Exception('Failed to add artifact to article: ' . ($update_response['error'] ?? 'Unknown'));
        }
        
        error_log("✅ [DevRev] Artifact added successfully");
        
        return $article_id;
    }
    
    private function updateArticle($article_id, $artifact_id) {
        error_log("🔵 [DevRev] UPDATE article {$article_id} ← artifact {$artifact_id}");
        
        $payload = [
            'id' => $article_id,
            'artifacts' => [
                'set' => [$artifact_id]
            ]
        ];
        
        $response = $this->callDevRev('/articles.update', 'POST', $payload);
        
        if (!$response['success']) {
            error_log("⚠️ [DevRev] Failed to update article: " . ($response['error'] ?? 'Unknown'));
            return false;
        }
        
        error_log("✅ [DevRev] Article updated successfully");
        return true;
    }
    
    /**
     * ✅ Process Chat - ไม่บังคับภาษา
     */
    public function processChat($conversation_id, $user_id, $user_companion_id, $email, $display_name, $ai_companion, $user_personality = []) {
        error_log("🔵 [DevRev] ========== Starting processChat ==========");
        
        $result = [
            'chat_article_id' => null,
            'prompt_article_id' => null,
            'errors' => [],
            'debug' => ['conversation_id' => $conversation_id]
        ];
        
        try {
            $dev_user = $this->ensureDevUser($user_id, $email, $display_name);
            $dev_user_id = $dev_user['dev_user_id'];
            $display_id = $dev_user['display_id'];
        } catch (Exception $e) {
            error_log("❌ [DevRev] Failed to ensure dev user: " . $e->getMessage());
            $result['errors'][] = 'Dev User: ' . $e->getMessage();
            return $result;
        }
        
        $global = $this->getGlobalArticleIds($user_id);
        
        // ============================================
        // PART 1: Chat History
        // ============================================
        try {
            error_log("🔵 [DevRev] --- Chat History ---");
            
            $history_stmt = $this->conn->prepare("
                SELECT h.conversation_id, h.role, h.message_text, h.created_at 
                FROM ai_chat_history h
                INNER JOIN ai_chat_conversations c ON h.conversation_id = c.conversation_id
                WHERE c.user_companion_id = ?
                ORDER BY h.created_at ASC
            ");
            $history_stmt->bind_param('i', $user_companion_id);
            $history_stmt->execute();
            $history_result = $history_stmt->get_result();
            
            $chat_content = "=== Chat History - User #{$user_id} (All Conversations) ===\n";
            $chat_content .= "DevRev Display ID: {$display_id}\n";
            $chat_content .= "Last updated: " . date('Y-m-d H:i:s') . "\n\n";
            
            $message_count = 0;
            $current_conv = null;
            
            while ($row = $history_result->fetch_assoc()) {
                if ($current_conv !== $row['conversation_id']) {
                    $current_conv = $row['conversation_id'];
                    $chat_content .= "\n--- Conversation #{$current_conv} ---\n";
                }
                
                $chat_content .= "[{$row['created_at']}] " . ucfirst($row['role']) . ":\n{$row['message_text']}\n\n";
                $message_count++;
            }
            
            $history_stmt->close();
            
            if ($message_count > 0) {
                $chat_artifact_id = $this->createArtifact(
                    "chat_history_user_{$user_id}.md",
                    $chat_content
                );
                
                if (!empty($global['chat_article_id'])) {
                    $update_success = $this->updateArticle($global['chat_article_id'], $chat_artifact_id);
                    if ($update_success) {
                        $result['chat_article_id'] = $global['chat_article_id'];
                        $result['debug']['chat_mode'] = 'update';
                    }
                } else {
                    $chat_article_id = $this->createArticle(
                        "Chat History - User #{$user_id}",
                        $chat_artifact_id,
                        $dev_user_id,
                        $display_id
                    );
                    $this->saveGlobalArticleIds($user_id, $chat_article_id, null);
                    $result['chat_article_id'] = $chat_article_id;
                    $result['debug']['chat_mode'] = 'create';
                }
                
                $stmt = $this->conn->prepare("
                    UPDATE ai_chat_conversations 
                    SET devrev_chat_article_id = ?, devrev_last_synced_at = NOW() 
                    WHERE conversation_id = ?
                ");
                $stmt->bind_param('si', $result['chat_article_id'], $conversation_id);
                $stmt->execute();
                $stmt->close();
            }
        } catch (Exception $e) {
            error_log("❌ [DevRev] Chat error: " . $e->getMessage());
            $result['errors'][] = "Chat: " . $e->getMessage();
        }
        
        // ============================================
        // PART 2: System Prompt (OVERWRITE MODE)
        // ============================================
        try {
            error_log("🔵 [DevRev] --- System Prompt (OVERWRITE MODE) ---");
            
            require_once __DIR__ . '/aimodelmanager.php';
            $ai_manager = new AIModelManager($this->conn);
            
            $prompt_data = $ai_manager->buildSystemPrompt($ai_companion, $user_personality);
            $ai_name = $ai_companion['ai_name'] ?? 'AI Assistant';
            
            $raw = $prompt_data['details']['raw_sections'] ?? [];
            
            $prompt_content = "=== System Prompt - {$ai_name} ===\n";
            $prompt_content .= "User ID: {$user_id}\n";
            $prompt_content .= "DevRev Display ID: {$display_id}\n";
            $prompt_content .= "Last updated: " . date('Y-m-d H:i:s') . "\n";
            $prompt_content .= "Current Conversation: #{$conversation_id}\n";
            $prompt_content .= "\n" . str_repeat("=", 80) . "\n\n";
            
            if (!empty($raw['core_personality'])) {
                $prompt_content .= "🎭 Core Personality\n";
                $prompt_content .= str_repeat("-", 80) . "\n";
                $prompt_content .= $raw['core_personality'] . "\n\n";
            }
            
            if (!empty($raw['perfume_knowledge'])) {
                $prompt_content .= "💧 Perfume Knowledge\n";
                $prompt_content .= str_repeat("-", 80) . "\n";
                $prompt_content .= $raw['perfume_knowledge'] . "\n\n";
            }
            
            if (!empty($raw['style_suggestions'])) {
                $prompt_content .= "✨ Style Suggestions\n";
                $prompt_content .= str_repeat("-", 80) . "\n";
                $prompt_content .= $raw['style_suggestions'] . "\n\n";
            }
            
            if (!empty($raw['user_context'])) {
                $prompt_content .= "👤 User Context\n";
                $prompt_content .= str_repeat("-", 80) . "\n";
                $prompt_content .= $raw['user_context'] . "\n\n";
            }
            
            if (!empty($raw['language_rules'])) {
                $prompt_content .= "🌐 Language Rules\n";
                $prompt_content .= str_repeat("-", 80) . "\n";
                $prompt_content .= $raw['language_rules'] . "\n\n";
            }
            
            if (!empty($raw['response_rules'])) {
                $prompt_content .= "📋 Response Rules\n";
                $prompt_content .= str_repeat("-", 80) . "\n";
                $prompt_content .= $raw['response_rules'] . "\n\n";
            }
            
            $prompt_artifact_id = $this->createArtifact(
                "system_prompt_user_{$user_id}.md",
                $prompt_content
            );
            
            if (!empty($global['prompt_article_id'])) {
                $update_success = $this->updateArticle($global['prompt_article_id'], $prompt_artifact_id);
                if ($update_success) {
                    $result['prompt_article_id'] = $global['prompt_article_id'];
                    $result['debug']['prompt_mode'] = 'overwrite';
                }
            } else {
                $prompt_article_id = $this->createArticle(
                    "System Prompt - {$ai_name} - User #{$user_id}",
                    $prompt_artifact_id,
                    $dev_user_id,
                    $display_id
                );
                $this->saveGlobalArticleIds($user_id, null, $prompt_article_id);
                $result['prompt_article_id'] = $prompt_article_id;
                $result['debug']['prompt_mode'] = 'create';
            }
            
            $stmt = $this->conn->prepare("
                UPDATE ai_chat_conversations 
                SET devrev_prompt_article_id = ?, devrev_last_synced_at = NOW() 
                WHERE conversation_id = ?
            ");
            $stmt->bind_param('si', $result['prompt_article_id'], $conversation_id);
            $stmt->execute();
            $stmt->close();
            
        } catch (Exception $e) {
            error_log("❌ [DevRev] Prompt error: " . $e->getMessage());
            $result['errors'][] = "Prompt: " . $e->getMessage();
        }
        
        $this->conn->commit();
        
        error_log("🔵 [DevRev] ========== processChat Completed ==========");
        
        return $result;
    }
}
?>