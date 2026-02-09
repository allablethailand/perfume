<?php
/**
 * ElevenLabs Text-to-Speech API v3
 * ใช้ voice_id จากตาราง ai_companions
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
        error_log("✅ Found voice_id from ai_id: $voiceId ({$row['voice_name']})");
    }
    $stmt->close();
}

// ถ้ายังไม่ได้ voice_id ลองหาจาก user_companion_id
if (!$voiceId && $user_companion_id > 0) {
    $stmt = $conn->prepare("
        SELECT ac.voice_id, ac.voice_name 
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
        error_log("✅ Found voice_id from user_companion_id: $voiceId ({$row['voice_name']})");
    }
    $stmt->close();
}

// ถ้ายังไม่ได้ voice_id ใช้ค่า default จาก .env
if (!$voiceId) {
    $voiceId = $_ENV['ELEVENLABS_VOICE_ID'] ?? 'UdFuclGJ1KL5tAeoBeE0';
    error_log("⚠️ Using default voice_id from .env: $voiceId");
}

$conn->close();

// Map language code สำหรับ ElevenLabs
$langMap = [
    'th' => 'th',
    'en' => 'en',
    'cn' => 'zh',
    'jp' => 'ja',
    'kr' => 'ko'
];
$elevenLabsLang = $langMap[$language] ?? 'en';

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
    
    echo json_encode([
        'status' => 'success',
        'audio_url' => $audioUrl,
        'language_used' => $elevenLabsLang,
        'voice_id' => $voiceId,
        'model' => 'eleven_v3',
        'file_size' => strlen($response)
    ]);
} else {
    error_log("ElevenLabs API Error: HTTP {$httpCode} - {$curlError}");
    
    echo json_encode([
        'status' => 'error',
        'message' => 'ElevenLabs API error',
        'http_code' => $httpCode,
        'error' => $curlError
    ]);
}
?>