<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../../lib/connect.php');
global $conn;

$response = ['status' => 'error', 'message' => ''];

try {
    $ai_code = strtoupper(trim($_GET['ai_code'] ?? ''));

    error_log("=== GET AI DATA DEBUG ===");
    error_log("AI Code: " . $ai_code);

    if (empty($ai_code)) {
        throw new Exception("AI code is required");
    }

    // ดึงข้อมูล AI (เพิ่ม emotion_videos)
    $stmt = $conn->prepare("
        SELECT 
            ai_id, item_id, ai_code,
            ai_name_th, ai_name_en, ai_name_cn, ai_name_jp, ai_name_kr,
            ai_avatar_url, ai_video_url,
            idle_video_url, idle_video_urls,
            talking_video_url, talking_video_urls,
            emotion_videos,
            system_prompt_th, system_prompt_en, system_prompt_cn, system_prompt_jp, system_prompt_kr,
            perfume_knowledge_th, perfume_knowledge_en, perfume_knowledge_cn, perfume_knowledge_jp, perfume_knowledge_kr,
            style_suggestions_th, style_suggestions_en, style_suggestions_cn, style_suggestions_jp, style_suggestions_kr,
            voice_id, voice_name, voice_preview_url,
            status, created_at, updated_at
        FROM ai_companions
        WHERE ai_code = ? AND status = 1 AND del = 0
        LIMIT 1
    ");
    $stmt->bind_param("s", $ai_code);
    $stmt->execute();
    $result  = $stmt->get_result();
    $ai_data = $result->fetch_assoc();
    $stmt->close();

    if (!$ai_data) {
        throw new Exception("AI companion not found");
    }

    // ดึง preferred_language + user_companion_id
    $preferred_language = 'th'; // default
    $user_companion_id  = null;

    // 1. ลองจาก GET parameter ก่อน
    if (isset($_GET['user_companion_id']) && intval($_GET['user_companion_id']) > 0) {
        $user_companion_id = intval($_GET['user_companion_id']);
        error_log("✅ Found user_companion_id from GET: " . $user_companion_id);
    }

    // 2. ลองจาก session
    if (!$user_companion_id) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['user_companion_id'])) {
            $user_companion_id = intval($_SESSION['user_companion_id']);
            error_log("✅ Found user_companion_id from session: " . $user_companion_id);
        }
    }

    // 3. ลองหาจาก ai_id
    if (!$user_companion_id && $ai_data['ai_id']) {
        $find_companion_stmt = $conn->prepare("
            SELECT user_companion_id, preferred_language 
            FROM user_ai_companions 
            WHERE ai_id = ? AND status = 1 AND del = 0
            ORDER BY last_active_at DESC
            LIMIT 1
        ");
        $find_companion_stmt->bind_param('i', $ai_data['ai_id']);
        $find_companion_stmt->execute();
        $find_result = $find_companion_stmt->get_result();
        if ($find_row = $find_result->fetch_assoc()) {
            $user_companion_id  = $find_row['user_companion_id'];
            $preferred_language = $find_row['preferred_language'] ?: 'th';
            error_log("✅ Found companion by ai_id: user_companion_id=" . $user_companion_id . ", lang=" . $preferred_language);
        }
        $find_companion_stmt->close();
    }

    // 4. ถ้ามี companion_id ให้ดึง preferred_language อีกครั้ง
    if ($user_companion_id) {
        $lang_stmt = $conn->prepare("
            SELECT preferred_language 
            FROM user_ai_companions 
            WHERE user_companion_id = ? AND status = 1 AND del = 0
        ");
        $lang_stmt->bind_param('i', $user_companion_id);
        $lang_stmt->execute();
        $lang_result = $lang_stmt->get_result();
        if ($lang_row = $lang_result->fetch_assoc()) {
            $preferred_language = $lang_row['preferred_language'] ?: 'th';
            error_log("✅ Updated preferred_language from DB: " . $preferred_language);
        }
        $lang_stmt->close();
    }

    error_log("=== FINAL VALUES ===");
    error_log("user_companion_id: " . ($user_companion_id ?: 'NOT FOUND'));
    error_log("preferred_language: " . $preferred_language);
    error_log("✅ AI Data found: ai_id=" . $ai_data['ai_id']);

    // สุ่มวิดีโอ idle / talking
    $idle_videos    = json_decode($ai_data['idle_video_urls']    ?? '[]', true);
    $talking_videos = json_decode($ai_data['talking_video_urls'] ?? '[]', true);

    $idle_video_url = null;
    if (!empty($idle_videos) && is_array($idle_videos)) {
        $idle_video_url = $idle_videos[array_rand($idle_videos)];
    }

    $talking_video_url = null;
    if (!empty($talking_videos) && is_array($talking_videos)) {
        $talking_video_url = $talking_videos[array_rand($talking_videos)];
    }

    $ai_data['idle_video_url']           = $idle_video_url;
    $ai_data['talking_video_url']        = $talking_video_url;
    $ai_data['idle_video_urls_array']    = $idle_videos;
    $ai_data['talking_video_urls_array'] = $talking_videos;

    // ✅ NEW: Parse emotion_videos JSON → array
    $emotion_videos_map              = json_decode($ai_data['emotion_videos'] ?? '{}', true) ?: [];
    $ai_data['emotion_videos_array'] = $emotion_videos_map;

    error_log("✅ Emotion videos loaded: " . count($emotion_videos_map) . " emotion(s)");

    $ai_data['preferred_language'] = $preferred_language;
    $ai_data['user_companion_id']  = $user_companion_id;

    $response = [
        'status'             => 'success',
        'ai_data'            => $ai_data,
        'preferred_language' => $preferred_language,
        'companion_id'       => $user_companion_id,
        'message'            => 'AI data loaded successfully'
    ];

} catch (Exception $e) {
    $response = ['status' => 'error', 'message' => $e->getMessage()];
    error_log("ERROR in get_ai_data.php: " . $e->getMessage());
}

$conn->close();
echo json_encode($response);
?>