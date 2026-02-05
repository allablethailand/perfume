<?php
/**
 * DevRev Manager - FIXED Artifact Updates
 * 
 * ✅ แก้การ update artifacts ให้ถูก format
 * ✅ Article จะมี artifacts เสมอ
 * ✅ ปรับปรุง logging
 * ✅ FIX: สร้าง Article ด้วย empty resource ก่อน แล้วค่อย update artifacts (ตามตัวอย่างที่ใช้งานได้)
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
    
    // ✅ FIX: ตรวจสอบว่า API key มีรูปแบบถูกต้อง
    if (strlen($this->api_token) < 100) {
        error_log("⚠️ [DevRev] API Key seems too short: " . strlen($this->api_token) . " chars");
    }
    
    // ตรวจสอบว่าเป็น JWT format หรือไม่
    $parts = explode('.', $this->api_token);
    if (count($parts) !== 3) {
        error_log("⚠️ [DevRev] API Key doesn't look like JWT format (expected 3 parts, got " . count($parts) . ")");
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
        // ✅ FIX: เพิ่ม SSL verification options
        CURLOPT_SSL_VERIFYPEER => true,  // ตรวจสอบ SSL certificate
        CURLOPT_SSL_VERIFYHOST => 2,      // ตรวจสอบ hostname
        // สำหรับ production ที่มี certificate ปกติ ควรเปิด verification
        // ถ้ายังมีปัญหา ให้ใช้แบบนี้ชั่วคราว:
        // CURLOPT_SSL_VERIFYPEER => false,
        // CURLOPT_SSL_VERIFYHOST => 0,
    ]);
    
    if ($method === 'POST' && $payload !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        error_log("📤 [DevRev] Request to {$endpoint}: " . substr($json, 0, 500));
    } elseif ($method === 'GET') {
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // ✅ FIX: เพิ่ม error handling สำหรับ cURL
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
    
    error_log("📊 [DevRev] Response HTTP {$http_code} from {$endpoint}");
    
    $decoded = json_decode($response, true);
    
    if ($http_code >= 200 && $http_code < 300) {
        return [
            'success' => true, 
            'data' => $decoded,
            'http_code' => $http_code
        ];
    }
    
    error_log("❌ [DevRev] Error response: {$response}");
    
    $error_detail = [
        'http_code' => $http_code,
        'endpoint' => $endpoint,
        'error_message' => $decoded['error']['message'] ?? $decoded['message'] ?? 'Unknown error',
        'error_type' => $decoded['error']['type'] ?? $decoded['type'] ?? 'unknown',
        'raw_response' => $response
    ];
    
    return [
        'success' => false, 
        'error' => json_encode($error_detail),
        'http_code' => $http_code,
        'raw' => $response
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
            error_log("✅ Dev User already exists: {$user_data['devrev_display_id']} ({$user_data['devrev_dev_user_id']})");
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
        
        error_log("✅ Created new dev user: {$display_id} ({$dev_user_id})");
        
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
        
        error_log("🔵 [DevRev] S3 Upload HTTP Code: {$upload_http_code}");
        
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
        
        error_log("⚠️ [DevRev] No parts found, creating default part...");
        
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
            error_log("✅ [DevRev] Saved global chat_article_id: {$chat_article_id} → user: {$user_id}");
        }
        if ($prompt_article_id) {
            $stmt = $this->conn->prepare("UPDATE mb_user SET devrev_prompt_article_id = ? WHERE user_id = ?");
            $stmt->bind_param('si', $prompt_article_id, $user_id);
            $stmt->execute();
            $stmt->close();
            error_log("✅ [DevRev] Saved global prompt_article_id: {$prompt_article_id} → user: {$user_id}");
        }
        $this->conn->commit();
    }
    
    /**
     * ✅ FIX: สร้าง Article ด้วย empty resource ก่อน แล้วค่อย update artifacts ทีหลัง
     * (ตามตัวอย่างโค้ดที่ใช้งานได้จริง)
     */
    private function createArticle($title, $artifact_id, $dev_user_id, $display_id) {
        $part_id = $this->getDefaultPart();
        if (!$part_id) {
            throw new Exception('No part ID available');
        }
        
        // ✅ STEP 1: สร้าง Article ด้วย EMPTY resource (ตามตัวอย่างที่ใช้งานได้)
        $payload = [
            'title' => $title,
            'owned_by' => [$dev_user_id],
            'authored_by' => [$display_id],
            'applies_to_parts' => [$part_id],
            'resource' => new stdClass(),  // ✅ Empty object แทนที่จะส่ง artifacts ตั้งแต่แรก
            'access_level' => 'public'
        ];
        
        error_log("🔵 [DevRev] Creating article with EMPTY resource first...");
        $response = $this->callDevRev('/articles.create', 'POST', $payload);
        
        if (!$response['success']) {
            throw new Exception('Failed to create article: ' . ($response['error'] ?? 'Unknown'));
        }
        
        $article_id = $response['data']['article']['id'] ?? null;
        if (!$article_id) {
            throw new Exception('No article ID returned');
        }
        
        error_log("✅ [DevRev] Article created (empty): {$article_id}");
        
        // ✅ STEP 2: เพิ่ม Artifact เข้า Article ทีหลัง
        error_log("🔵 [DevRev] Adding artifact to article...");
        
        $update_payload = [
            'id' => $article_id,
            'artifacts' => [
                'set' => [$artifact_id]
            ]
        ];
        
        $update_response = $this->callDevRev('/articles.update', 'POST', $update_payload);
        
        if (!$update_response['success']) {
            error_log("❌ [DevRev] Failed to add artifact: " . ($update_response['error'] ?? 'Unknown'));
            throw new Exception('Failed to add artifact to article: ' . ($update_response['error'] ?? 'Unknown'));
        }
        
        error_log("✅ [DevRev] Artifact added successfully");
        
        // ✅ STEP 3: Verify ว่า artifact ถูก link แล้วจริงๆ
        sleep(1); // รอให้ DevRev sync
        $verify = $this->callDevRev('/articles.get', 'POST', ['id' => $article_id]);
        $artifacts_in_article = $verify['data']['article']['resource']['artifacts'] ?? [];
        
        if (empty($artifacts_in_article)) {
            error_log("❌ [DevRev] Verification failed - no artifacts found in article");
            throw new Exception("Failed to verify artifact in article");
        }
        
        error_log("✅ [DevRev] Article verified with " . count($artifacts_in_article) . " artifact(s)");
        return $article_id;
    }
    
    /**
     * ✅ FIX: Update article - ใช้ format ที่ถูกต้อง
     */
    private function updateArticle($article_id, $artifact_id) {
        error_log("🔵 [DevRev] UPDATE article {$article_id} ← artifact {$artifact_id}");
        
        // ✅ ใช้ artifacts.set แทน resource.artifacts
        $payload = [
            'id' => $article_id,
            'artifacts' => [
                'set' => [$artifact_id]
            ]
        ];
        
        error_log("🔵 [DevRev] UPDATE article payload: " . json_encode($payload, JSON_UNESCAPED_UNICODE));
        
        $response = $this->callDevRev('/articles.update', 'POST', $payload);
        
        if (!$response['success']) {
            error_log("⚠️ [DevRev] Failed to update article: " . ($response['error'] ?? 'Unknown'));
            return false;
        }
        
        // ✅ Verify ว่า update สำเร็จ
        sleep(1);
        $verify = $this->callDevRev('/articles.get', 'POST', ['id' => $article_id]);
        $artifacts_in_article = $verify['data']['article']['resource']['artifacts'] ?? [];
        
        if (empty($artifacts_in_article)) {
            error_log("❌ [DevRev] Update verification failed - no artifacts found");
            return false;
        }
        
        error_log("✅ [DevRev] Article updated successfully with " . count($artifacts_in_article) . " artifact(s)");
        return true;
    }
    
    public function processChat($conversation_id, $user_id, $user_companion_id, $email, $display_name, $ai_companion, $user_personality = [], $language = 'th') {
        error_log("🔵 [DevRev] ========== Starting processChat ==========");
        error_log("🔵 [DevRev] Conversation: {$conversation_id}, User: {$user_id}");
        
        $result = [
            'chat_article_id' => null,
            'prompt_article_id' => null,
            'errors' => [],
            'debug' => ['conversation_id' => $conversation_id, 'api_calls' => []]
        ];
        
        try {
            $dev_user = $this->ensureDevUser($user_id, $email, $display_name);
            $dev_user_id = $dev_user['dev_user_id'];
            $display_id = $dev_user['display_id'];
            error_log("🔵 [DevRev] Using dev_user: {$display_id} ({$dev_user_id})");
        } catch (Exception $e) {
            error_log("❌ [DevRev] Failed to ensure dev user: " . $e->getMessage());
            $result['errors'][] = 'Dev User: ' . $e->getMessage();
            return $result;
        }
        
        $global = $this->getGlobalArticleIds($user_id);
        error_log("🔵 [DevRev] Global articles — Chat: " . ($global['chat_article_id'] ?: 'None') . ", Prompt: " . ($global['prompt_article_id'] ?: 'None'));
        
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
            
            error_log("🔵 [DevRev] Total messages: {$message_count}");
            
            if ($message_count > 0) {
                $chat_artifact_id = $this->createArtifact(
                    "chat_history_user_{$user_id}.txt",
                    $chat_content
                );
                
                if (!empty($global['chat_article_id'])) {
                    $update_success = $this->updateArticle($global['chat_article_id'], $chat_artifact_id);
                    if ($update_success) {
                        $result['chat_article_id'] = $global['chat_article_id'];
                        $result['debug']['chat_mode'] = 'update';
                    } else {
                        throw new Exception("Failed to update chat article");
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
        // PART 2: System Prompt
        // ============================================
        try {
            error_log("🔵 [DevRev] --- System Prompt ---");
            
            require_once __DIR__ . '/AIModelManager.php';
            $ai_manager = new AIModelManager($this->conn);
            $prompt_data = $ai_manager->buildSystemPrompt($ai_companion, $user_personality, $language);
            $ai_name = $ai_companion['ai_name'] ?? 'AI Assistant';
            
            $prompt_content = "=== System Prompt - {$ai_name} ===\n";
            $prompt_content .= "User ID: {$user_id}\n";
            $prompt_content .= "DevRev Display ID: {$display_id}\n";
            $prompt_content .= "Last updated: " . date('Y-m-d H:i:s') . "\n";
            $prompt_content .= "Current Conversation: #{$conversation_id}\n";
            $prompt_content .= "Language: {$language}\n";
            $prompt_content .= "\n" . str_repeat("=", 80) . "\n\n";
            
            if (!empty($prompt_data['details']['prompt_sections'])) {
                foreach ($prompt_data['details']['prompt_sections'] as $section_info) {
                    if (!empty($section_info['content'])) {
                        $prompt_content .= $section_info['label'] . "\n";
                        $prompt_content .= str_repeat("-", 80) . "\n";
                        $prompt_content .= $section_info['content'] . "\n\n";
                    }
                }
            }
            
            $prompt_content .= "\n" . str_repeat("=", 80) . "\n";
            $prompt_content .= "=== FULL SYSTEM PROMPT ===\n";
            $prompt_content .= str_repeat("=", 80) . "\n\n";
            $prompt_content .= $prompt_data['prompt'] . "\n";
            
            $prompt_artifact_id = $this->createArtifact(
                "system_prompt_user_{$user_id}.txt",
                $prompt_content
            );
            
            if (!empty($global['prompt_article_id'])) {
                $update_success = $this->updateArticle($global['prompt_article_id'], $prompt_artifact_id);
                if ($update_success) {
                    $result['prompt_article_id'] = $global['prompt_article_id'];
                    $result['debug']['prompt_mode'] = 'update';
                } else {
                    throw new Exception("Failed to update prompt article");
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
        
        error_log("🔵 [DevRev] ===== Summary =====");
        error_log("   Dev User: {$display_id} ({$dev_user_id})");
        error_log("   Chat: " . ($result['chat_article_id'] ?? 'FAILED') . " (" . ($result['debug']['chat_mode'] ?? 'N/A') . ")");
        error_log("   Prompt: " . ($result['prompt_article_id'] ?? 'FAILED') . " (" . ($result['debug']['prompt_mode'] ?? 'N/A') . ")");
        if (!empty($result['errors'])) {
            foreach ($result['errors'] as $err) error_log("   ⚠️ {$err}");
        }
        error_log("🔵 [DevRev] ========== processChat Completed ==========");
        
        return $result;
    }
}
?>