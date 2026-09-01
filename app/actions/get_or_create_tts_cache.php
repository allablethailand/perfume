<?php
/**
 * Get or Create TTS Cache
 * ตรวจสอบว่ามี cache แล้วหรือยัง ถ้ายังก็สร้างใหม่
 *
 * ✅ FIX 1: ใช้ __DIR__ แทน DOCUMENT_ROOT → path ถูกทั้ง local และ production
 * ✅ FIX 2: bind_param NULL integer → เปลี่ยน 'i' เป็น 's' สำหรับ question_id/choice_id
 *           เพราะ PHP mysqli 'i' + NULL → ส่ง 0 ซึ่งชน FK constraint บน production
 * ✅ FIX 3: ตรวจ duplicate key (del=1/status=0) ก่อน INSERT เพื่อกัน unique key error
 * ✅ FIX 4: เพิ่ม detailed error_log ทุก step สำหรับ debug บน production
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../lib/connect.php';
require_once __DIR__ . '/../../vendor/autoload.php';

global $conn;

$input = json_decode(file_get_contents('php://input'), true);

$text        = $input['text']        ?? '';
$language    = $input['language']    ?? 'en';
$ai_id       = (int)($input['ai_id'] ?? 0);
$voice_id    = $input['voice_id']    ?? null;
$cache_type  = $input['cache_type']  ?? 'general';
// ✅ ใช้ null จริงๆ (ไม่แปลงเป็น int เพื่อรักษา NULL)
$question_id = isset($input['question_id']) && $input['question_id'] !== null ? (int)$input['question_id'] : null;
$choice_id   = isset($input['choice_id'])   && $input['choice_id']   !== null ? (int)$input['choice_id']   : null;

if (empty($text)) {
    echo json_encode(['status' => 'error', 'message' => 'No text provided']);
    exit;
}

// ============================================================
// ✅ คำนวณ physical path และ public URL ที่ถูกต้องทุก environment
//
//   ไฟล์นี้: <projectRoot>/app/actions/get_or_create_tts_cache.php
//   local:      projectRoot = /…/htdocs       → webBase = ''
//   production: projectRoot = /…/html/perfume → webBase = '/perfume'
// ============================================================
$projectRoot = realpath(__DIR__ . '/../../');
$ttsCacheDir = $projectRoot . '/public/tts_cache/';

$docRoot     = rtrim(realpath($_SERVER['DOCUMENT_ROOT']), '/');
$webBase     = str_replace($docRoot, '', $projectRoot);
$webBase     = rtrim($webBase, '/');

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
    require_once __DIR__ . '/../../lib/env_boot.php';
    $voice_id = $_ENV['ELEVENLABS_VOICE_ID'] ?? 'UdFuclGJ1KL5tAeoBeE0';
}

// สร้าง hash สำหรับ lookup
$text_hash = hash('sha256', $text . '|' . $voice_id . '|' . $language);

// ============================================================
// ✅ CHECK CACHE — ดึงทุก record (รวม inactive) เพื่อกัน duplicate key
// ============================================================
$stmt = $conn->prepare("
    SELECT cache_id, audio_file_url, audio_file_size, character_count, status, del
    FROM ai_tts_cache
    WHERE text_hash = ? AND voice_id = ? AND language_code = ?
    LIMIT 1
");
$stmt->bind_param('sss', $text_hash, $voice_id, $language);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $cache_id = $row['cache_id'];
    $stmt->close();

    if ((int)$row['status'] === 1 && (int)$row['del'] === 0) {
        // ✅ Cache HIT — active record
        $updateStmt = $conn->prepare("
            UPDATE ai_tts_cache
            SET hit_count = hit_count + 1, last_used_at = NOW()
            WHERE cache_id = ?
        ");
        $updateStmt->bind_param('i', $cache_id);
        $updateStmt->execute();
        $updateStmt->close();

        error_log("✅ TTS Cache HIT: cache_id=$cache_id | type=$cache_type");

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

    // ⚠️ มี record แต่ inactive/deleted → ลบก่อน insert ใหม่ (ป้องกัน unique key conflict)
    $delStmt = $conn->prepare("DELETE FROM ai_tts_cache WHERE cache_id = ?");
    $delStmt->bind_param('i', $cache_id);
    $delStmt->execute();
    $delStmt->close();
    error_log("⚠️ TTS Cache: Removed inactive record cache_id=$cache_id | status={$row['status']} del={$row['del']}");

} else {
    $stmt->close();
}

// ============================================================
// ❌ Cache MISS → เรียก ElevenLabs API
// ============================================================
error_log("❌ TTS Cache MISS | type=$cache_type | ai_id=$ai_id | voice=$voice_id | lang=$language | text=" . mb_substr($text, 0, 60, 'UTF-8'));
error_log("📂 ttsCacheDir : $ttsCacheDir");
error_log("🌐 ttsCacheUrl : $ttsCacheUrl");

require_once __DIR__ . '/../../lib/env_boot.php';
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

$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200) {
    error_log("❌ ElevenLabs API failed: HTTP $httpCode | $curlError");
    echo json_encode([
        'status'    => 'error',
        'message'   => 'ElevenLabs API failed',
        'http_code' => $httpCode,
    ]);
    exit;
}

// ✅ เขียนไฟล์เสียง
if (!file_exists($ttsCacheDir)) {
    if (!mkdir($ttsCacheDir, 0777, true)) {
        error_log("❌ Cannot create directory: $ttsCacheDir");
    }
}

$filename = 'tts_' . uniqid() . '.mp3';
$filepath = $ttsCacheDir . $filename;
$audioUrl = $ttsCacheUrl  . $filename;

$written = file_put_contents($filepath, $response);
if ($written === false) {
    error_log("❌ file_put_contents FAILED: cannot write to $filepath");
    echo json_encode(['status' => 'error', 'message' => 'Cannot write audio file to disk']);
    exit;
}
error_log("✅ Audio file written: $filepath ($written bytes)");

$fileSize       = strlen($response);
$characterCount = mb_strlen($text, 'UTF-8');
$model_used     = 'eleven_v3';

// ============================================================
// ✅ INSERT INTO ai_tts_cache
//
// FIX: เปลี่ยน bind_param type ของ question_id และ choice_id
//      จาก 'i' → 's'
//
// เหตุผล:
//   PHP mysqli bind_param 'i' + null → แปลง null เป็น 0 โดยอัตโนมัติ
//   ถ้า column question_id/choice_id มี FK constraint และ 0 ไม่มีใน parent table
//   → MySQL จะ reject → INSERT fail → insert_id = 0 → ไม่ถูกบันทึก
//
//   การใช้ 's' + null → mysqli ส่ง SQL NULL ที่ถูกต้อง → ไม่มีปัญหา FK
// ============================================================
$insertStmt = $conn->prepare("
    INSERT INTO ai_tts_cache (
        ai_id, voice_id, language_code, text_content, text_hash,
        audio_file_url, audio_file_path, audio_file_size, character_count,
        model_used, cache_type, question_id, choice_id, hit_count, last_used_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
");

//               i  s  s  s  s  s  s  i  i  s  s  s  s
$insertStmt->bind_param(
    'issssssiiisss',
    $ai_id,          // i  → int
    $voice_id,       // s  → string
    $language,       // s  → string
    $text,           // s  → string (text content)
    $text_hash,      // s  → string (sha256)
    $audioUrl,       // s  → string (public URL)
    $filepath,       // s  → string (disk path)
    $fileSize,       // i  → int
    $characterCount, // i  → int
    $model_used,     // s  → string
    $cache_type,     // s  → string (welcome/weather/general/…)
    $question_id,    // s  → NULL-safe (ถ้าเป็น int 'i' จะแปลง null→0 ซึ่งผิด FK)
    $choice_id       // s  → NULL-safe (เหมือนกัน)
);

$execOk   = $insertStmt->execute();
$cache_id = $execOk ? (int)$conn->insert_id : 0;
$dbError  = $execOk ? '' : ('errno=' . $insertStmt->errno . ' | ' . $insertStmt->error);
$insertStmt->close();

if ($execOk && $cache_id > 0) {
    error_log("✅ TTS Cache CREATED: cache_id=$cache_id | type=$cache_type | ai_id=$ai_id | url=$audioUrl");
} else {
    error_log("❌ TTS Cache INSERT FAILED: $dbError | type=$cache_type | ai_id=$ai_id | voice=$voice_id | hash=$text_hash");
}

echo json_encode([
    'status'          => 'success',
    'cache_hit'       => false,
    'cache_id'        => $cache_id,
    'audio_url'       => $audioUrl,
    'character_count' => $characterCount,
    'audio_file_size' => $fileSize,
    // debug field — ดูใน Network tab เพื่อ confirm ว่า save สำเร็จหรือไม่
    'db_saved'        => $cache_id > 0,
]);

$conn->close();
?>
