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
    // ✅ เพิ่ม startup_actions ใน SELECT
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
            ai.emotion_videos,
            ai.emotion_videos_3d,
            ai.startup_actions,
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
    
    // เลือกชื่อตามภาษาที่ user เลือก
    $lang = strtolower(trim($companion['preferred_language'] ?? 'th'));
    $supported_langs = ['th', 'en', 'cn', 'jp', 'kr'];
    if (!in_array($lang, $supported_langs)) { $lang = 'th'; }
    
    $ai_name_key = 'ai_name_' . $lang;
    $ai_name = !empty($companion[$ai_name_key]) ? $companion[$ai_name_key] : $companion['ai_name_th'];
    if (empty($ai_name)) { $ai_name = $companion['ai_code'] ?? 'AI Companion'; }
    
    // Parse idle/talking arrays
    $idle_videos    = json_decode($companion['idle_video_urls']    ?? '[]', true) ?: [];
    $talking_videos = json_decode($companion['talking_video_urls'] ?? '[]', true) ?: [];
    
    $idle_video_url    = !empty($idle_videos)    ? $idle_videos[array_rand($idle_videos)]       : null;
    $talking_video_url = !empty($talking_videos) ? $talking_videos[array_rand($talking_videos)] : null;

    // ✅ Parse emotion_videos (2D)
    $emotion_videos_array = json_decode($companion['emotion_videos'] ?? '{}', true) ?: [];

    // ✅ Parse emotion_videos_3d
    $emotion_videos_3d_array = json_decode($companion['emotion_videos_3d'] ?? '{}', true) ?: [];

    // ✅ Parse startup_actions
    $startup_actions = json_decode($companion['startup_actions'] ?? '{}', true) ?: (object)[];

    error_log("✅ emotion_videos_3d loaded: " . count($emotion_videos_3d_array) . " emotion(s) for user_id=$user_id");
    error_log("✅ startup_actions: " . json_encode($startup_actions));
    
    echo json_encode([
        'status' => 'success',
        'companion' => [
            'user_companion_id'        => $companion['user_companion_id'],
            'ai_id'                    => $companion['ai_id'],
            'ai_code'                  => $companion['ai_code'],
            'ai_name'                  => $ai_name,
            'preferred_language'       => $companion['preferred_language'],
            'ai_avatar_url'            => $companion['ai_avatar_url'],
            'idle_video_url'           => $idle_video_url,
            'talking_video_url'        => $talking_video_url,
            'voice_id'                 => $companion['voice_id'],
            'voice_name'               => $companion['voice_name'],
            'last_active_at'           => $companion['last_active_at'],
            'idle_video_urls_array'    => $idle_videos,
            'talking_video_urls_array' => $talking_videos,
            'emotion_videos_array'     => $emotion_videos_array,
            'emotion_videos_3d_array'  => $emotion_videos_3d_array,
            'startup_actions'          => $startup_actions,  // ✅ เพิ่ม
        ],
        'user_id'            => $user_id,
        'companion_id'       => $companion['user_companion_id'],
        'preferred_language' => $companion['preferred_language'] ?? 'th',
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();