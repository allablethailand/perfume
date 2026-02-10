<?php
/**
 * Get AI Companion Info API
 * ดึงข้อมูล AI companion ของ user (รวม random video URLs และ preferred_language)
 */

require_once('../../lib/connect.php');
require_once('../../lib/jwt_helper.php');

global $conn;

header('Content-Type: application/json');

// ตรวจสอบ JWT
$headers = getallheaders();
$jwt = null;
$user_id = null;

if (isset($headers['Authorization'])) {
    $jwt = str_replace('Bearer ', '', $headers['Authorization']);
    $decoded = verifyJWT($jwt);
    if ($decoded) {
        $user_id = requireAuth();
    }
}

if (!$user_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized',
        'require_login' => true
    ]);
    exit;
}

try {
    // ✅ ดึงข้อมูล AI companion ของ user พร้อม video URLs (arrays)
    $stmt = $conn->prepare("
        SELECT 
            uc.user_companion_id,
            uc.ai_id,
            uc.preferred_language,
            uc.last_active_at,
            ai.ai_code,
            ai.ai_name_th,
            ai.ai_name_en,
            ai.ai_name_cn,
            ai.ai_name_jp,
            ai.ai_name_kr,
            ai.ai_avatar_url,
            ai.idle_video_urls,
            ai.talking_video_urls,
            ai.voice_id,
            ai.voice_name
        FROM user_ai_companions uc
        INNER JOIN ai_companions ai ON uc.ai_id = ai.ai_id
        WHERE uc.user_id = ? 
        AND uc.status = 1 
        AND uc.del = 0
        AND ai.status = 1 
        AND ai.del = 0
        ORDER BY uc.last_active_at DESC
        LIMIT 1
    ");
    
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No AI companion found. Please setup AI companion first.'
        ]);
        exit;
    }
    
    $companion = $result->fetch_assoc();
    $stmt->close();
    
    // ✅ เลือกชื่อตามภาษาที่ user เลือก (fallback เป็น th ถ้าไม่มี)
    $lang = strtolower(trim($companion['preferred_language'] ?? 'th'));
    
    // รองรับเฉพาะภาษาที่มีในระบบ
    $supported_langs = ['th', 'en', 'cn', 'jp', 'kr'];
    if (!in_array($lang, $supported_langs)) {
        $lang = 'th';
    }
    
    $ai_name_key = 'ai_name_' . $lang;
    
    // ถ้าชื่อภาษานั้นไม่มี ให้ fallback เป็น th
    $ai_name = !empty($companion[$ai_name_key]) ? $companion[$ai_name_key] : $companion['ai_name_th'];
    
    // ถ้า th ก็ยังไม่มี ให้ใช้ ai_code แทน
    if (empty($ai_name)) {
        $ai_name = $companion['ai_code'] ?? 'AI Companion';
    }
    
    // ✅ สุ่มเลือกวิดีโอจาก arrays
    $idle_videos = json_decode($companion['idle_video_urls'] ?? '[]', true);
    $talking_videos = json_decode($companion['talking_video_urls'] ?? '[]', true);
    
    // สุ่ม idle video
    $idle_video_url = null;
    if (!empty($idle_videos) && is_array($idle_videos)) {
        $random_index = array_rand($idle_videos);
        $idle_video_url = $idle_videos[$random_index];
        error_log("✅ Random idle video selected for user_id=$user_id: " . $idle_video_url);
    }
    
    // สุ่ม talking video
    $talking_video_url = null;
    if (!empty($talking_videos) && is_array($talking_videos)) {
        $random_index = array_rand($talking_videos);
        $talking_video_url = $talking_videos[$random_index];
        error_log("✅ Random talking video selected for user_id=$user_id: " . $talking_video_url);
    }
    
    echo json_encode([
        'status' => 'success',
        'companion' => [
            'user_companion_id' => $companion['user_companion_id'],
            'ai_id' => $companion['ai_id'],
            'ai_code' => $companion['ai_code'],
            'ai_name' => $ai_name,
            'preferred_language' => $companion['preferred_language'],
            'ai_avatar_url' => $companion['ai_avatar_url'],
            'idle_video_url' => $idle_video_url,
            'talking_video_url' => $talking_video_url,
            'voice_id' => $companion['voice_id'],
            'voice_name' => $companion['voice_name'],
            'last_active_at' => $companion['last_active_at'],
            // เก็บ arrays ไว้ในกรณีต้องการใช้งานเพิ่มเติม
            'idle_video_urls_array' => $idle_videos,
            'talking_video_urls_array' => $talking_videos
        ],
        'user_id' => $user_id,
        'companion_id' => $companion['user_companion_id']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>