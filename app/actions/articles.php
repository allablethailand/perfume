<?php
/**
 * DevRev Full Flow Test
 * 1. สร้าง Dev User
 * 2. สร้าง Part (optional)
 * 3. สร้าง Artifact (แบบถูกต้อง - prepare → upload → verify)
 * 4. สร้าง Article + เชื่อม Artifact
 * 5. ทดสอบ recommendations.get-reply
 */

require_once('../../lib/connect.php');
global $conn;

// ========================================
// Load Environment & Token
// ========================================
$env_path = __DIR__ . '/../../.env';
if (file_exists($env_path)) {
    foreach (file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, "\"'");
        }
    }
}

$api_token = $_ENV['DEVREV_API_TOKEN'] ?? null;
if (!$api_token) {
    die("❌ DEVREV_API_TOKEN not found in .env\n");
}

// ========================================
// Helper Functions
// ========================================
function callDevRev($endpoint, $method, $payload, $token) {
    $url = "https://api.devrev.ai{$endpoint}";
    $ch = curl_init($url);

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . trim($token),
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($payload !== null) {
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        }
    }

    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($response, true);

    return [
        'success' => ($code >= 200 && $code < 300),
        'code' => $code,
        'data' => $decoded
    ];
}

function createArtifact($file_name, $content, $token) {
    echo "  🔧 Creating artifact: {$file_name}\n";
    
    // STEP 1: Prepare
    $prepare = callDevRev('/artifacts.prepare', 'POST', [
        'file_name' => $file_name
    ], $token);
    
    if (!$prepare['success']) {
        throw new Exception('Prepare failed: ' . json_encode($prepare['data']));
    }
    
    $artifact_id = $prepare['data']['id'];
    $upload_url = $prepare['data']['url'];
    $form_data = $prepare['data']['form_data'];
    
    echo "  ✅ Prepared: {$artifact_id}\n";
    
    // STEP 2: Upload to S3
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
        CURLOPT_HTTPHEADER => ["Content-Type: multipart/form-data; boundary={$boundary}"],
        CURLOPT_POSTFIELDS => $post_data
    ]);
    
    $upload_response = curl_exec($ch);
    $upload_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($upload_code < 200 || $upload_code >= 300) {
        throw new Exception("Upload failed: HTTP {$upload_code}");
    }
    
    echo "  ✅ Uploaded to S3\n";
    
    return $artifact_id;
}

// ========================================
// Main Flow
// ========================================
echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║         DevRev Full Flow Test (Create → Query)            ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$timestamp = time();
$test_data = [];

try {
    // ========================================
    // STEP 1: Create Dev User
    // ========================================
    echo "┌─ STEP 1: Create Dev User ────────────────────────────────┐\n";
    
    $user_result = callDevRev('/dev-users.create', 'POST', [
        'email' => "testuser_{$timestamp}@example.com",
        'full_name' => "Test User {$timestamp}",
        'state' => 'shadow'
    ], $api_token);
    
    if (!$user_result['success']) {
        throw new Exception('Failed to create dev user: ' . json_encode($user_result['data']));
    }
    
    $dev_user_id = $user_result['data']['dev_user']['id'];
    $display_id = $user_result['data']['dev_user']['display_id'];
    
    $test_data['dev_user_id'] = $dev_user_id;
    $test_data['display_id'] = $display_id;
    
    echo "  ✅ Dev User: {$display_id}\n";
    echo "  📧 Email: testuser_{$timestamp}@example.com\n";
    echo "└────────────────────────────────────────────────────────────┘\n\n";
    
    // ========================================
    // STEP 2: Get/Create Part
    // ========================================
    echo "┌─ STEP 2: Get Default Part ────────────────────────────────┐\n";
    
    $parts_result = callDevRev('/parts.list', 'POST', [
        'type' => ['product']
    ], $api_token);
    
    if ($parts_result['success'] && !empty($parts_result['data']['parts'])) {
        $part_id = $parts_result['data']['parts'][0]['id'];
        echo "  ✅ Using existing part: {$part_id}\n";
    } else {
        $create_part = callDevRev('/parts.create', 'POST', [
            'type' => 'product',
            'name' => 'Test Product',
            'description' => 'Test product for DevRev testing'
        ], $api_token);
        
        if (!$create_part['success']) {
            throw new Exception('Failed to create part');
        }
        
        $part_id = $create_part['data']['part']['id'];
        echo "  ✅ Created new part: {$part_id}\n";
    }
    
    $test_data['part_id'] = $part_id;
    echo "└────────────────────────────────────────────────────────────┘\n\n";
    
    // ========================================
    // STEP 3: Create Artifacts
    // ========================================
    echo "┌─ STEP 3: Create Artifacts ────────────────────────────────┐\n";
    
    // Artifact 1: System Prompt
    $system_prompt_content = <<<EOT
=== AI Character: Luna ===
User: Test User #{$timestamp}
Display ID: {$display_id}

=== Personality ===
- ชื่อ: Luna
- นิสัย: เป็นกันเอง สนุกสนาน ให้คำแนะนำที่ดี
- ความเชี่ยวชาญ: น้ำหอม, แฟชั่น, ไลฟ์สตายล์

=== Character Traits ===
• พูดจาสุภาพและเป็นมิตร
• ชอบใช้อีโมจิในการสนทนา 🌸
• ให้คำแนะนำที่เข้าใจง่าย
• มีความรู้เรื่องน้ำหอมอย่างลึกซึ้ง
• สามารถอธิบายกลิ่นได้อย่างมีชีวิตชีวา

=== Response Style ===
- ใช้ "ค่ะ" ในการตอบ
- ตอบสั้นกระชับ แต่ครบถ้วน
- ให้ความสำคัญกับความรู้สึกของผู้ใช้
EOT;
    
    $prompt_artifact_id = createArtifact(
        "system_prompt_user_{$timestamp}.txt",
        $system_prompt_content,
        $api_token
    );
    
    $test_data['prompt_artifact_id'] = $prompt_artifact_id;
    echo "  ✅ System Prompt Artifact: {$prompt_artifact_id}\n\n";
    
    // Artifact 2: Chat History
    $chat_history_content = <<<EOT
=== Chat History - User #{$timestamp} ===
DevRev Display ID: {$display_id}
Last updated: {date('Y-m-d H:i:s')}

--- Conversation #1 ---
[2026-02-04 10:00:00] User:
สวัสดีครับ

[2026-02-04 10:00:05] Assistant:
สวัสดีค่ะ! ยินดีที่ได้รู้จักนะคะ 🌸 มีอะไรให้ช่วยไหมคะ

[2026-02-04 10:00:15] User:
อยากหาน้ำหอมหอมๆ สักตัว

[2026-02-04 10:00:20] Assistant:
เยี่ยมเลยค่ะ! คุณชอบกลิ่นแบบไหนคะ หวานๆ สดชื่น หรือหรูหรา? 🌺

[2026-02-04 10:00:30] User:
ชอบกลิ่นหวานนิดๆ แต่ไม่เหมือนขนม

[2026-02-04 10:00:35] Assistant:
เข้าใจค่ะ! คุณน่าจะชอบกลิ่น Floral Sweet หรือ Fruity Sweet ค่ะ 
ลองดู Dior Miss Dior Blooming Bouquet หรือ Marc Jacobs Daisy ดูนะคะ 
กลิ่นหวานละมุน ไม่หวานจัด เหมาะกับทุกโอกาสเลยค่ะ 💕
EOT;
    
    $chat_artifact_id = createArtifact(
        "chat_history_user_{$timestamp}.txt",
        $chat_history_content,
        $api_token
    );
    
    $test_data['chat_artifact_id'] = $chat_artifact_id;
    echo "  ✅ Chat History Artifact: {$chat_artifact_id}\n";
    echo "└────────────────────────────────────────────────────────────┘\n\n";
    
    // ========================================
    // STEP 4: Create Articles
    // ========================================
    echo "┌─ STEP 4: Create Articles ─────────────────────────────────┐\n";
    
    // Article 1: System Prompt
    echo "  📝 Creating System Prompt Article...\n";
    
    $prompt_article = callDevRev('/articles.create', 'POST', [
        'title' => "System Prompt - Luna - User #{$timestamp}",
        'owned_by' => [$dev_user_id],
        'authored_by' => [$display_id],
        'applies_to_parts' => [$part_id],
        'resource' => new stdClass(),
        'access_level' => 'public'
    ], $api_token);
    
    if (!$prompt_article['success']) {
        throw new Exception('Failed to create prompt article: ' . json_encode($prompt_article['data']));
    }
    
    $prompt_article_id = $prompt_article['data']['article']['id'];
    echo "  ✅ Article created: {$prompt_article_id}\n";
    
    // Add artifact
    echo "  🔗 Adding artifact...\n";
    $update_prompt = callDevRev('/articles.update', 'POST', [
        'id' => $prompt_article_id,
        'artifacts' => ['set' => [$prompt_artifact_id]]
    ], $api_token);
    
    if (!$update_prompt['success']) {
        throw new Exception('Failed to add artifact to prompt article');
    }
    
    echo "  ✅ Artifact added to System Prompt Article\n\n";
    $test_data['prompt_article_id'] = $prompt_article_id;
    
    // Article 2: Chat History
    echo "  📝 Creating Chat History Article...\n";
    
    $chat_article = callDevRev('/articles.create', 'POST', [
        'title' => "Chat History - User #{$timestamp}",
        'owned_by' => [$dev_user_id],
        'authored_by' => [$display_id],
        'applies_to_parts' => [$part_id],
        'resource' => new stdClass(),
        'access_level' => 'public'
    ], $api_token);
    
    if (!$chat_article['success']) {
        throw new Exception('Failed to create chat article: ' . json_encode($chat_article['data']));
    }
    
    $chat_article_id = $chat_article['data']['article']['id'];
    echo "  ✅ Article created: {$chat_article_id}\n";
    
    // Add artifact
    echo "  🔗 Adding artifact...\n";
    $update_chat = callDevRev('/articles.update', 'POST', [
        'id' => $chat_article_id,
        'artifacts' => ['set' => [$chat_artifact_id]]
    ], $api_token);
    
    if (!$update_chat['success']) {
        throw new Exception('Failed to add artifact to chat article');
    }
    
    echo "  ✅ Artifact added to Chat History Article\n";
    $test_data['chat_article_id'] = $chat_article_id;
    
    echo "└────────────────────────────────────────────────────────────┘\n\n";
    
    // ========================================
    // STEP 5: Verify Articles
    // ========================================
    echo "┌─ STEP 5: Verify Articles ─────────────────────────────────┐\n";
    
    sleep(2); // รอให้ DevRev sync
    
    $verify_prompt = callDevRev('/articles.get', 'POST', [
        'id' => $prompt_article_id
    ], $api_token);
    
    if ($verify_prompt['success']) {
        $artifacts = $verify_prompt['data']['article']['resource']['artifacts'] ?? [];
        echo "  ✅ System Prompt Article: " . count($artifacts) . " artifact(s)\n";
    }
    
    $verify_chat = callDevRev('/articles.get', 'POST', [
        'id' => $chat_article_id
    ], $api_token);
    
    if ($verify_chat['success']) {
        $artifacts = $verify_chat['data']['article']['resource']['artifacts'] ?? [];
        echo "  ✅ Chat History Article: " . count($artifacts) . " artifact(s)\n";
    }
    
    echo "└────────────────────────────────────────────────────────────┘\n\n";
    
    // ========================================
    // STEP 6: Test Query (แบบที่ 1 - ส่งแค่ query)
    // ========================================
    echo "┌─ STEP 6A: Test Query (Simple) ───────────────────────────┐\n";
    
    $test_query_1 = "แนะนำน้ำหอมกลิ่นหวานๆ ให้หน่อยครับ";
    
    echo "  ❓ Query: {$test_query_1}\n\n";
    
    $query_result_1 = callDevRev('/recommendations.get-reply', 'POST', [
        'query' => $test_query_1
    ], $api_token);
    
    if ($query_result_1['success']) {
        $reply = $query_result_1['data']['reply'] ?? '';
        $sources = $query_result_1['data']['sources'] ?? [];
        
        echo "  💬 Reply:\n";
        echo "  " . str_repeat("-", 58) . "\n";
        echo "  " . wordwrap($reply, 58, "\n  ") . "\n";
        echo "  " . str_repeat("-", 58) . "\n\n";
        
        if (!empty($sources)) {
            echo "  📚 Sources Used: " . count($sources) . "\n";
            foreach ($sources as $idx => $source) {
                $type = $source['type'] ?? 'unknown';
                $title = $source['title'] ?? 'untitled';
                $source_display_id = $source['display_id'] ?? 'no-id';
                echo "     [{$idx}] {$type}: {$title} ({$source_display_id})\n";
            }
        } else {
            echo "  ⚠️  No sources used (DevRev ไม่ได้อ่าน Articles)\n";
        }
    } else {
        echo "  ❌ Query failed: " . json_encode($query_result_1['data']) . "\n";
    }
    
    echo "└────────────────────────────────────────────────────────────┘\n\n";
    
    // ========================================
    // STEP 6B: Test Query (แบบที่ 2 - ส่ง context ไปด้วย)
    // ========================================
    echo "┌─ STEP 6B: Test Query (With Context) ─────────────────────┐\n";
    
    $context = <<<EOT
AI Character: Luna
Personality: เป็นกันเอง สนุกสนาน ให้คำแนะนำดีๆ เชี่ยวชาญเรื่องน้ำหอม

Recent conversation:
User: สวัสดีครับ
Assistant: สวัสดีค่ะ! ยินดีที่ได้รู้จักนะคะ 🌸
User: อยากหาน้ำหอมหอมๆ สักตัว
Assistant: เยี่ยมเลยค่ะ! คุณชอบกลิ่นแบบไหนคะ
User: ชอบกลิ่นหวานนิดๆ แต่ไม่เหมือนขนม
Assistant: คุณน่าจะชอบกลิ่น Floral Sweet หรือ Fruity Sweet ค่ะ

---

EOT;
    
    $test_query_2 = "ราคาประมาณเท่าไหร่ครับ";
    $full_query = $context . "User Question: " . $test_query_2;
    
    echo "  ❓ Query: {$test_query_2}\n";
    echo "  📦 Context: " . strlen($context) . " chars\n\n";
    
    $query_result_2 = callDevRev('/recommendations.get-reply', 'POST', [
        'query' => $full_query
    ], $api_token);
    
    if ($query_result_2['success']) {
        $reply = $query_result_2['data']['reply'] ?? '';
        $sources = $query_result_2['data']['sources'] ?? [];
        
        echo "  💬 Reply:\n";
        echo "  " . str_repeat("-", 58) . "\n";
        echo "  " . wordwrap($reply, 58, "\n  ") . "\n";
        echo "  " . str_repeat("-", 58) . "\n\n";
        
        if (!empty($sources)) {
            echo "  📚 Sources Used: " . count($sources) . "\n";
        } else {
            echo "  ✅ ใช้ context ที่เราส่งไป (ไม่ต้องพึ่ง Articles)\n";
        }
    } else {
        echo "  ❌ Query failed: " . json_encode($query_result_2['data']) . "\n";
    }
    
    echo "└────────────────────────────────────────────────────────────┘\n\n";
    
    // ========================================
    // SUMMARY
    // ========================================
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║                  สรุปผลการทดสอบ                           ║\n";
    echo "╠════════════════════════════════════════════════════════════╣\n";
    echo "║  ✅ Dev User:      {$display_id}\n";
    echo "║  ✅ Part:          " . substr($part_id, -20) . "\n";
    echo "║                                                            ║\n";
    echo "║  📝 Articles Created:                                      ║\n";
    echo "║     - System Prompt: " . substr($prompt_article_id, -20) . "\n";
    echo "║     - Chat History:  " . substr($chat_article_id, -20) . "\n";
    echo "║                                                            ║\n";
    echo "║  💡 ผลการทดสอบ:                                           ║\n";
    
    if (!empty($query_result_1['data']['sources'])) {
        echo "║     ✅ DevRev อ่าน Articles ได้ (แบบที่ 1)               ║\n";
    } else {
        echo "║     ⚠️  DevRev ไม่อ่าน Articles (แบบที่ 1)              ║\n";
        echo "║     💡 แนะนำให้ส่ง context ไปกับ query (แบบที่ 2)       ║\n";
    }
    
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    
    // บันทึกข้อมูลลง file สำหรับ debug
    file_put_contents(
        __DIR__ . "/test_result_{$timestamp}.json",
        json_encode($test_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
    
    echo "📁 Test data saved to: test_result_{$timestamp}.json\n\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

$conn->close();
?>