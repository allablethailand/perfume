<?php
/**
 * Get or Create TTS Cache
 * ตรวจสอบว่ามี cache แล้วหรือยัง ถ้ายังก็สร้างใหม่
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../lib/connect.php';
require_once __DIR__ . '/../../vendor/autoload.php';

global $conn;

$input = json_decode(file_get_contents('php://input'), true);

$text = $input['text'] ?? '';
$language = $input['language'] ?? 'en';
$ai_id = $input['ai_id'] ?? 0;
$voice_id = $input['voice_id'] ?? null;
$cache_type = $input['cache_type'] ?? 'general'; // question, choice, welcome, feedback, general
$question_id = $input['question_id'] ?? null;
$choice_id = $input['choice_id'] ?? null;

if (empty($text)) {
    echo json_encode(['status' => 'error', 'message' => 'No text provided']);
    exit;
}

// ดึง voice_id ถ้ายังไม่มี
if (!$voice_id && $ai_id > 0) {
    $stmt = $conn->prepare("SELECT voice_id FROM ai_companions WHERE ai_id = ? AND status = 1 AND del = 0 LIMIT 1");
    $stmt->bind_param('i', $ai_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $voice_id = $row['voice_id'];
    }
    $stmt->close();
}

if (!$voice_id) {
    // ใช้ default voice
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();
    $voice_id = $_ENV['ELEVENLABS_VOICE_ID'] ?? 'UdFuclGJ1KL5tAeoBeE0';
}

// สร้าง hash สำหรับ lookup (ใช้ text + voice_id + language)
$text_hash = hash('sha256', $text . '|' . $voice_id . '|' . $language);

// ✅ ตรวจสอบว่ามี cache อยู่แล้วหรือไม่
$stmt = $conn->prepare("
    SELECT cache_id, audio_file_url, audio_file_size, character_count 
    FROM ai_tts_cache 
    WHERE text_hash = ? AND voice_id = ? AND language_code = ? AND status = 1 AND del = 0
    LIMIT 1
");
$stmt->bind_param('sss', $text_hash, $voice_id, $language);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // ✅ มี cache แล้ว - ใช้ซ้ำ
    $cache_id = $row['cache_id'];
    
    // อัปเดต hit_count และ last_used_at
    $updateStmt = $conn->prepare("
        UPDATE ai_tts_cache 
        SET hit_count = hit_count + 1, last_used_at = NOW() 
        WHERE cache_id = ?
    ");
    $updateStmt->bind_param('i', $cache_id);
    $updateStmt->execute();
    $updateStmt->close();
    
    error_log("✅ TTS Cache HIT: cache_id=$cache_id");
    
    echo json_encode([
        'status' => 'success',
        'cache_hit' => true,
        'cache_id' => $cache_id,
        'audio_url' => $row['audio_file_url'],
        'character_count' => $row['character_count'],
        'audio_file_size' => $row['audio_file_size']
    ]);
    exit;
}

$stmt->close();

// ❌ ไม่มี cache - สร้างใหม่ผ่าน ElevenLabs
error_log("❌ TTS Cache MISS - Creating new cache for: " . substr($text, 0, 50));

// เรียก ElevenLabs API
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();
$ELEVENLABS_API_KEY = $_ENV['ELEVENLABS_API_KEY'] ?? '';

$langMap = ['th' => 'th', 'en' => 'en', 'cn' => 'zh', 'jp' => 'ja', 'kr' => 'ko'];
$elevenLabsLang = $langMap[$language] ?? 'en';

$url = "https://api.elevenlabs.io/v1/text-to-speech/{$voice_id}";
$data = [
    'text' => $text,
    'model_id' => 'eleven_v3',
    'voice_settings' => [
        'language_code' => $elevenLabsLang,
        'stability' => 0.5,
        'similarity_boost' => 0.75,
        'style' => 0.0,
        'use_speaker_boost' => true
    ],
    'output_format' => 'mp3_44100_128'
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'xi-api-key: ' . $ELEVENLABS_API_KEY
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    // บันทึกไฟล์
    $tempDir = $_SERVER['DOCUMENT_ROOT'] . '/public/tts_cache/';
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0777, true);
    }
    
    $filename = 'tts_' . uniqid() . '.mp3';
    $filepath = $tempDir . $filename;
    file_put_contents($filepath, $response);
    
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $audioUrl = $protocol . '://' . $host . '/public/tts_cache/' . $filename;
    
    $fileSize = strlen($response);
    $characterCount = mb_strlen($text, 'UTF-8');
    
    // บันทึกลง database
    $insertStmt = $conn->prepare("
        INSERT INTO ai_tts_cache (
            ai_id, voice_id, language_code, text_content, text_hash, 
            audio_file_url, audio_file_path, audio_file_size, character_count, 
            model_used, cache_type, question_id, choice_id, hit_count, last_used_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
    ");
    
    $model_used = 'eleven_v3';
    $insertStmt->bind_param(
        'issssssiiisii',
        $ai_id, $voice_id, $language, $text, $text_hash,
        $audioUrl, $filepath, $fileSize, $characterCount,
        $model_used, $cache_type, $question_id, $choice_id
    );
    
    $insertStmt->execute();
    $cache_id = $conn->insert_id;
    $insertStmt->close();
    
    error_log("✅ TTS Cache CREATED: cache_id=$cache_id");
    
    echo json_encode([
        'status' => 'success',
        'cache_hit' => false,
        'cache_id' => $cache_id,
        'audio_url' => $audioUrl,
        'character_count' => $characterCount,
        'audio_file_size' => $fileSize
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'ElevenLabs API failed',
        'http_code' => $httpCode
    ]);
}

$conn->close();
?>