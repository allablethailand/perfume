<?php
/**
 * Get or Create TTS Cache
 * ตรวจสอบว่ามี cache แล้วหรือยัง ถ้ายังก็สร้างใหม่
 *
 * ✅ FIX: ใช้ __DIR__ แทน DOCUMENT_ROOT เพื่อให้ path ถูกต้องทั้ง local และ production
 *    - local:      http://localhost/public/tts_cache/xxx.mp3
 *    - production: https://www.trandar.com/perfume/public/tts_cache/xxx.mp3
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../lib/connect.php';
require_once __DIR__ . '/../../vendor/autoload.php';

global $conn;

$input = json_decode(file_get_contents('php://input'), true);

$text        = $input['text']        ?? '';
$language    = $input['language']    ?? 'en';
$ai_id       = $input['ai_id']       ?? 0;
$voice_id    = $input['voice_id']    ?? null;
$cache_type  = $input['cache_type']  ?? 'general'; // question, choice, welcome, weather, feedback, general
$question_id = $input['question_id'] ?? null;
$choice_id   = $input['choice_id']   ?? null;

if (empty($text)) {
    echo json_encode(['status' => 'error', 'message' => 'No text provided']);
    exit;
}

// ============================================================
// ✅ คำนวณ physical path และ public URL ที่ถูกต้องทุก environment
// ============================================================
//
// ไฟล์นี้อยู่ที่:  <projectRoot>/app/actions/get_or_create_tts_cache.php
// projectRoot คือ 2 ระดับขึ้นจาก __DIR__
//
//   local:      projectRoot = /var/www/html          → webBase = ''
//   production: projectRoot = /var/www/html/perfume  → webBase = '/perfume'
//
// ดังนั้น audio URL จะออกมาถูกต้องทั้งสองเคส:
//   local:      http://localhost/public/tts_cache/xxx.mp3
//   production: https://www.trandar.com/perfume/public/tts_cache/xxx.mp3

$projectRoot = realpath(__DIR__ . '/../../');       // physical root ของ project
$ttsCacheDir = $projectRoot . '/public/tts_cache/'; // physical dir สำหรับบันทึกไฟล์

// หา web base path โดยตัด DOCUMENT_ROOT ออก
$docRoot = rtrim(realpath($_SERVER['DOCUMENT_ROOT']), '/');
$webBase = str_replace($docRoot, '', $projectRoot); // '' หรือ '/perfume'
$webBase = rtrim($webBase, '/');

$protocol    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host        = $_SERVER['HTTP_HOST'];
$ttsCacheUrl = $protocol . '://' . $host . $webBase . '/public/tts_cache/';

// ============================================================

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
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();
    $voice_id = $_ENV['ELEVENLABS_VOICE_ID'] ?? 'UdFuclGJ1KL5tAeoBeE0';
}

// สร้าง hash สำหรับ lookup (text + voice_id + language)
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
    // ✅ Cache HIT
    $cache_id = $row['cache_id'];

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
        'status'          => 'success',
        'cache_hit'       => true,
        'cache_id'        => $cache_id,
        'audio_url'       => $row['audio_file_url'],
        'character_count' => $row['character_count'],
        'audio_file_size' => $row['audio_file_size'],
    ]);
    exit;
}

$stmt->close();

// ❌ Cache MISS → สร้างใหม่ผ่าน ElevenLabs
error_log("❌ TTS Cache MISS - Creating new: " . substr($text, 0, 50));
error_log("📂 Dir : $ttsCacheDir");
error_log("🌐 URL : $ttsCacheUrl");

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();
$ELEVENLABS_API_KEY = $_ENV['ELEVENLABS_API_KEY'] ?? '';

$langMap        = ['th' => 'th', 'en' => 'en', 'cn' => 'zh', 'jp' => 'ja', 'kr' => 'ko'];
$elevenLabsLang = $langMap[$language] ?? 'en';

$apiUrl = "https://api.elevenlabs.io/v1/text-to-speech/{$voice_id}";
$data   = [
    'text'           => $text,
    'model_id'       => 'eleven_v3',
    'voice_settings' => [
        'language_code'    => $elevenLabsLang,
        'stability'        => 0.5,
        'similarity_boost' => 0.75,
        'style'            => 0.0,
        'use_speaker_boost'=> true,
    ],
    'output_format' => 'mp3_44100_128',
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'xi-api-key: ' . $ELEVENLABS_API_KEY,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {

    // ✅ สร้าง directory ถ้ายังไม่มี
    if (!file_exists($ttsCacheDir)) {
        mkdir($ttsCacheDir, 0777, true);
    }

    $filename = 'tts_' . uniqid() . '.mp3';
    $filepath = $ttsCacheDir . $filename;   // ✅ physical path บน disk
    $audioUrl = $ttsCacheUrl  . $filename;  // ✅ public URL ที่ถูกต้อง

    file_put_contents($filepath, $response);

    $fileSize       = strlen($response);
    $characterCount = mb_strlen($text, 'UTF-8');
    $model_used     = 'eleven_v3';

    // บันทึกลง database
    $insertStmt = $conn->prepare("
        INSERT INTO ai_tts_cache (
            ai_id, voice_id, language_code, text_content, text_hash,
            audio_file_url, audio_file_path, audio_file_size, character_count,
            model_used, cache_type, question_id, choice_id, hit_count, last_used_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
    ");

    $insertStmt->bind_param(
        'issssssiiisii',
        $ai_id, $voice_id, $language, $text, $text_hash,
        $audioUrl, $filepath, $fileSize, $characterCount,
        $model_used, $cache_type, $question_id, $choice_id
    );

    if ($insertStmt->execute()) {
        $cache_id = $conn->insert_id;
        error_log("✅ TTS Cache CREATED: cache_id=$cache_id | $audioUrl");
    } else {
        error_log("❌ TTS INSERT failed: " . $insertStmt->error);
        $cache_id = 0;
    }
    $insertStmt->close();

    echo json_encode([
        'status'          => 'success',
        'cache_hit'       => false,
        'cache_id'        => $cache_id,
        'audio_url'       => $audioUrl,
        'character_count' => $characterCount,
        'audio_file_size' => $fileSize,
    ]);

} else {
    error_log("❌ ElevenLabs failed: HTTP $httpCode");
    echo json_encode([
        'status'    => 'error',
        'message'   => 'ElevenLabs API failed',
        'http_code' => $httpCode,
    ]);
}

$conn->close();
?>