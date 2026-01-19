<?php
/**
 * Text-to-Speech API
 * ใช้ Google Translate TTS API (ฟรี ไม่ต้อง API Key)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// รับข้อมูล
$input = json_decode(file_get_contents('php://input'), true);
$text = $input['text'] ?? '';
$lang = $input['lang'] ?? 'th';

if (empty($text)) {
    echo json_encode(['status' => 'error', 'message' => 'Text is required']);
    exit;
}

// จำกัดความยาว (Google Translate TTS รองรับสูงสุด ~200 ตัวอักษร)
$maxLength = 200;
$chunks = [];

// แบ่งข้อความเป็นส่วนๆ ถ้ายาวเกินไป
if (mb_strlen($text) > $maxLength) {
    // แบ่งตามประโยค
    $sentences = preg_split('/([.!?。！？]+)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    $currentChunk = '';
    
    for ($i = 0; $i < count($sentences); $i++) {
        $sentence = $sentences[$i];
        
        if (mb_strlen($currentChunk . $sentence) <= $maxLength) {
            $currentChunk .= $sentence;
        } else {
            if (!empty($currentChunk)) {
                $chunks[] = trim($currentChunk);
            }
            $currentChunk = $sentence;
        }
    }
    
    if (!empty($currentChunk)) {
        $chunks[] = trim($currentChunk);
    }
} else {
    $chunks[] = $text;
}

// สร้าง audio URLs สำหรับแต่ละ chunk
$audioUrls = [];
foreach ($chunks as $chunk) {
    if (!empty(trim($chunk))) {
        // ใช้ Google Translate TTS API (ฟรี)
        $encodedText = urlencode($chunk);
        $audioUrls[] = "https://translate.google.com/translate_tts?ie=UTF-8&tl={$lang}&client=tw-ob&q={$encodedText}";
    }
}

echo json_encode([
    'status' => 'success',
    'audio_urls' => $audioUrls,
    'chunks' => $chunks,
    'language' => $lang
]);