<?php
/**
 * Pre-cache TTS สำหรับคำถามทั้งหมด
 * รันครั้งเดียวเพื่อสร้าง cache ล่วงหน้า
 */

require_once __DIR__ . '/../../lib/connect.php';
global $conn;

$languages = ['th', 'en', 'cn', 'jp', 'kr'];
$ai_id = 1; // ใส่ ai_id ที่ต้องการ

// ดึง voice_id
$stmt = $conn->prepare("SELECT voice_id FROM ai_companions WHERE ai_id = ? LIMIT 1");
$stmt->bind_param('i', $ai_id);
$stmt->execute();
$result = $stmt->get_result();
$voice_id = $result->fetch_assoc()['voice_id'] ?? null;
$stmt->close();

if (!$voice_id) {
    die("Voice ID not found for ai_id: $ai_id\n");
}

echo "Starting pre-cache for ai_id=$ai_id, voice_id=$voice_id\n\n";

// ดึงคำถามทั้งหมด
$stmt = $conn->prepare("SELECT question_id, question_text_th, question_text_en, question_text_cn, question_text_jp, question_text_kr FROM ai_personality_questions WHERE status = 1 AND del = 0");
$stmt->execute();
$questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($questions as $question) {
    foreach ($languages as $lang) {
        $text = $question["question_text_$lang"];
        
        if (empty($text)) continue;
        
        echo "Caching Q{$question['question_id']} ($lang): " . substr($text, 0, 50) . "...\n";
        
        $data = [
            'text' => $text,
            'language' => $lang,
            'ai_id' => $ai_id,
            'voice_id' => $voice_id,
            'cache_type' => 'question',
            'question_id' => $question['question_id']
        ];
        
        $ch = curl_init('http://localhost/app/actions/get_or_create_tts_cache.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($result['status'] === 'success') {
            echo "  ✅ " . ($result['cache_hit'] ? "Cache hit" : "Created new") . "\n";
        } else {
            echo "  ❌ Failed\n";
        }
        
        usleep(500000); // หน่วงเวลา 0.5 วินาที
    }
}

echo "\n✅ Pre-caching completed!\n";
?>