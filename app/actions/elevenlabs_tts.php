<?php
/**
 * ElevenLabs Text-to-Speech API v3
 * รองรับ 5 ภาษา: th, en, cn, jp, kr
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

if (empty($text)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No text provided'
    ]);
    exit;
}

// 🎤 Voice IDs สำหรับแต่ละภาษา
$voiceMap = [
    'th' => 'UdFuclGJ1KL5tAeoBeE0',
    'en' => 'UdFuclGJ1KL5tAeoBeE0', // Rachel (Multilingual)
    'cn' => 'UdFuclGJ1KL5tAeoBeE0', // Bella (Multilingual)
    'jp' => 'UdFuclGJ1KL5tAeoBeE0', // Domi (Multilingual)
    'kr' => 'UdFuclGJ1KL5tAeoBeE0'  // Elli (Multilingual)
];

$voiceId = $_ENV['ELEVENLABS_VOICE_ID'] ?? $voiceMap[$language] ?? $voiceMap['en'];

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
        'language_code' => 'th',
        'stability' => 0.5,           // เพิ่มจาก 0.5 → ทำให้เสียงมั่นคงไม่แปลกๆ
        'similarity_boost' => 0.75,    // เพิ่มจาก 0.75 → เสียงใกล้เคียงเสียงจริงมากขึ้น
        'style' => 0.0,                // เพิ่มจาก 0.0 → ให้มีอารมณ์ธรรมชาติ
        'use_speaker_boost' => true
    ],
    'output_format' => 'mp3_44100_128' // คุณภาพเสียงดีขึ้น
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
    // ✅ กำหนด path ให้ถูกต้อง - บันทึกใน /perfume/temp/
    $tempDir = $_SERVER['DOCUMENT_ROOT'] . '/perfume/temp/';
    
    // สร้างโฟลเดอร์ temp ถ้ายังไม่มี
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0777, true);
    }
    
    // บันทึกไฟล์เสียง
    $filename = 'elevenlabs_' . uniqid() . '.mp3';
    $filepath = $tempDir . $filename;
    
    file_put_contents($filepath, $response);
    
    // ✅ สร้าง URL แบบ absolute
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $audioUrl = $protocol . '://' . $host . '/perfume/temp/' . $filename;
    
    echo json_encode([
        'status' => 'success',
        'audio_url' => $audioUrl,
        'language_used' => $elevenLabsLang,
        'voice_id' => $voiceId,
        'model' => 'eleven_multilingual_v2',
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