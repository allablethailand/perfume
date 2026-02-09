<?php
/**
 * ElevenLabs Text-to-Speech API v3
 * ใช้ voice_id จากตาราง ai_companions + บันทึก usage log
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// โหลด .env
require_once __DIR__ . '/../../vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to load environment variables'
    ]);
    exit;
}

$ELEVENLABS_API_KEY = $_ENV['ELEVENLABS_API_KEY'] ?? '';

if (empty($ELEVENLABS_API_KEY)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'ElevenLabs API key not configured'
    ]);
    exit;
}

// รับข้อมูลจาก Frontend
$input = json_decode(file_get_contents('php://input'), true);
$text = $input['text'] ?? '';
$language = $input['language'] ?? 'en';
$user_companion_id = $input['user_companion_id'] ?? 0;
$ai_id = $input['ai_id'] ?? 0;

if (empty($text)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No text provided'
    ]);
    exit;
}

// ========================================
// ✅ ดึง voice_id จากตาราง ai_companions
// ========================================
require_once __DIR__ . '/../../lib/connect.php';
global $conn;

$voiceId = null;
$voiceName = null;
$user_id = null;

// ลอง ai_id ก่อน (ถ้ามี)
if ($ai_id > 0) {
    $stmt = $conn->prepare("
        SELECT voice_id, voice_name 
        FROM ai_companions 
        WHERE ai_id = ? AND status = 1 AND del = 0
        LIMIT 1
    ");
    $stmt->bind_param('i', $ai_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $voiceId = $row['voice_id'];
        $voiceName = $row['voice_name'];
        error_log("✅ Found voice_id from ai_id: $voiceId ($voiceName)");
    }
    $stmt->close();
}

// ถ้ายังไม่ได้ voice_id ลองหาจาก user_companion_id
if (!$voiceId && $user_companion_id > 0) {
    $stmt = $conn->prepare("
        SELECT ac.voice_id, ac.voice_name, uc.user_id, uc.ai_id
        FROM user_ai_companions uc
        INNER JOIN ai_companions ac ON uc.ai_id = ac.ai_id
        WHERE uc.user_companion_id = ? AND uc.status = 1 AND uc.del = 0
        LIMIT 1
    ");
    $stmt->bind_param('i', $user_companion_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $voiceId = $row['voice_id'];
        $voiceName = $row['voice_name'];
        $user_id = $row['user_id'];
        $ai_id = $row['ai_id'];
        error_log("✅ Found voice_id from user_companion_id: $voiceId ($voiceName)");
    }
    $stmt->close();
}

// ถ้ายังไม่ได้ voice_id ใช้ค่า default จาก .env
if (!$voiceId) {
    $voiceId = $_ENV['ELEVENLABS_VOICE_ID'] ?? 'UdFuclGJ1KL5tAeoBeE0';
    $voiceName = 'Default Voice';
    error_log("⚠️ Using default voice_id from .env: $voiceId");
}

// Map language code สำหรับ ElevenLabs
$langMap = [
    'th' => 'th',
    'en' => 'en',
    'cn' => 'zh',
    'jp' => 'ja',
    'kr' => 'ko'
];
$elevenLabsLang = $langMap[$language] ?? 'en';

// นับจำนวนตัวอักษร
$characterCount = mb_strlen($text, 'UTF-8');
$textLength = strlen($text);

// 🎙️ เรียก ElevenLabs API
$url = "https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}";

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
$curlError = curl_error($ch);
curl_close($ch);

// ดึง IP และ User Agent
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
} elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
}
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

if ($httpCode === 200) {
    $tempDir = $_SERVER['DOCUMENT_ROOT'] . '/public/';
    
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0777, true);
    }
    
    $filename = 'elevenlabs_' . uniqid() . '.mp3';
    $filepath = $tempDir . $filename;
    
    file_put_contents($filepath, $response);
    
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $audioUrl = $protocol . '://' . $host . '/public/' . $filename;
    
    $fileSize = strlen($response);
    
    // ========================================
    // ✅ บันทึก log สำเร็จ
    // ========================================
    
    // แปลง NULL เป็น 0 สำหรับ integer fields
    $user_id_log = $user_id ?? 0;
    $user_companion_id_log = $user_companion_id > 0 ? $user_companion_id : 0;
    $ai_id_log = $ai_id > 0 ? $ai_id : 0;
    
    $modelUsed = 'eleven_v3';
    $isSuccess = 1;
    $errorMessage = '';
    
    $logStmt = $conn->prepare("
        INSERT INTO elevenlabs_usage_logs (
            user_id,
            user_companion_id,
            ai_id,
            voice_id,
            voice_name,
            language_code,
            text_content,
            text_length,
            character_count,
            model_used,
            audio_file_url,
            audio_file_size,
            http_status_code,
            is_success,
            error_message,
            ip_address,
            user_agent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    // ✅ 17 columns = 17 parameters ต้องตรงกัน
    // นับให้ชัดเจน: i=1, i=2, i=3, s=4, s=5, s=6, s=7, i=8, i=9, s=10, s=11, i=12, i=13, i=14, s=15, s=16, s=17
    // Update both occurrences of $logStmt->bind_param inside the script:

$logStmt->bind_param(
    'iiissssiissiiisss',     // Exactly 17 characters
    $user_id_log,           // 1. i
    $user_companion_id_log, // 2. i
    $ai_id_log,             // 3. i
    $voiceId,               // 4. s
    $voiceName,             // 5. s
    $elevenLabsLang,        // 6. s
    $text,                  // 7. s
    $textLength,            // 8. i
    $characterCount,        // 9. i
    $modelUsed,             // 10. s
    $audioUrl,              // 11. s
    $fileSize,              // 12. i
    $httpCode,              // 13. i
    $isSuccess,             // 14. i
    $errorMessage,          // 15. s
    $ipAddress,             // 16. s
    $userAgent              // 17. s
);
    
    $logStmt->execute();
    $logId = $conn->insert_id;
    $logStmt->close();
    
    error_log("✅ TTS Log saved: log_id=$logId, characters=$characterCount");
    
    $conn->close();
    
    echo json_encode([
        'status' => 'success',
        'audio_url' => $audioUrl,
        'language_used' => $elevenLabsLang,
        'voice_id' => $voiceId,
        'voice_name' => $voiceName,
        'model' => $modelUsed,
        'file_size' => $fileSize,
        'character_count' => $characterCount,
        'log_id' => $logId
    ]);
} else {
    // ========================================
    // ❌ บันทึก log ล้มเหลว
    // ========================================
    
    // แปลง NULL เป็น 0 สำหรับ integer fields
    $user_id_log = $user_id ?? 0;
    $user_companion_id_log = $user_companion_id > 0 ? $user_companion_id : 0;
    $ai_id_log = $ai_id > 0 ? $ai_id : 0;
    
    $modelUsed = 'eleven_v3';
    $isSuccess = 0;
    $errorMessage = $curlError ?: 'ElevenLabs API error';
    $audioUrl = '';
    $fileSize = 0;
    
    $logStmt = $conn->prepare("
        INSERT INTO elevenlabs_usage_logs (
            user_id,
            user_companion_id,
            ai_id,
            voice_id,
            voice_name,
            language_code,
            text_content,
            text_length,
            character_count,
            model_used,
            audio_file_url,
            audio_file_size,
            http_status_code,
            is_success,
            error_message,
            ip_address,
            user_agent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    // ✅ 17 parameters
    $logStmt->bind_param(
        'iiissssiissiiss',  // ✅ 17 ตัว
        $user_id_log,           // 1. i
        $user_companion_id_log, // 2. i
        $ai_id_log,             // 3. i
        $voiceId,               // 4. s
        $voiceName,             // 5. s
        $elevenLabsLang,        // 6. s
        $text,                  // 7. s
        $textLength,            // 8. i
        $characterCount,        // 9. i
        $modelUsed,             // 10. s
        $audioUrl,              // 11. s
        $fileSize,              // 12. i
        $httpCode,              // 13. i
        $isSuccess,             // 14. i
        $errorMessage,          // 15. s
        $ipAddress,             // 16. s
        $userAgent              // 17. s
    );
    
    $logStmt->execute();
    $logId = $conn->insert_id;
    $logStmt->close();
    
    error_log("❌ TTS Error logged: log_id=$logId, HTTP $httpCode - $curlError");
    
    $conn->close();
    
    echo json_encode([
        'status' => 'error',
        'message' => 'ElevenLabs API error',
        'http_code' => $httpCode,
        'error' => $curlError,
        'log_id' => $logId
    ]);
}
?>