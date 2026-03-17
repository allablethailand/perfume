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

    // ✅ SELECT รวม emotion_videos_3d และ startup_actions ด้วย
    $stmt = $conn->prepare("
        SELECT 
            ai_id, item_id, ai_code,
            ai_name_th, ai_name_en, ai_name_cn, ai_name_jp, ai_name_kr,
            ai_avatar_url, ai_video_url,
            idle_video_url, idle_video_urls,
            talking_video_url, talking_video_urls,
            emotion_videos, emotion_videos_3d,
            startup_actions,
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
    $preferred_language = 'th';
    $user_companion_id  = null;

    if (isset($_GET['user_companion_id']) && intval($_GET['user_companion_id']) > 0) {
        $user_companion_id = intval($_GET['user_companion_id']);
        error_log("✅ Found user_companion_id from GET: " . $user_companion_id);
    }

    if (!$user_companion_id) {
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        if (isset($_SESSION['user_companion_id'])) {
            $user_companion_id = intval($_SESSION['user_companion_id']);
            error_log("✅ Found user_companion_id from session: " . $user_companion_id);
        }
    }

    if (!$user_companion_id && $ai_data['ai_id']) {
        $find_stmt = $conn->prepare("
            SELECT user_companion_id, preferred_language 
            FROM user_ai_companions 
            WHERE ai_id = ? AND status = 1 AND del = 0
            ORDER BY last_active_at DESC
            LIMIT 1
        ");
        $find_stmt->bind_param('i', $ai_data['ai_id']);
        $find_stmt->execute();
        $find_result = $find_stmt->get_result();
        if ($find_row = $find_result->fetch_assoc()) {
            $user_companion_id  = $find_row['user_companion_id'];
            $preferred_language = $find_row['preferred_language'] ?: 'th';
        }
        $find_stmt->close();
    }

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
        }
        $lang_stmt->close();
    }

    // ─── Parse video arrays ───────────────────────────────
    $idle_videos    = json_decode($ai_data['idle_video_urls']    ?? '[]', true) ?: [];
    $talking_videos = json_decode($ai_data['talking_video_urls'] ?? '[]', true) ?: [];

    $ai_data['idle_video_url']           = !empty($idle_videos)    ? $idle_videos[array_rand($idle_videos)]       : null;
    $ai_data['talking_video_url']        = !empty($talking_videos) ? $talking_videos[array_rand($talking_videos)] : null;
    $ai_data['idle_video_urls_array']    = $idle_videos;
    $ai_data['talking_video_urls_array'] = $talking_videos;

    // ─── ✅ Parse emotion_videos (2D) ─────────────────────
    $ai_data['emotion_videos_array'] = json_decode($ai_data['emotion_videos'] ?? '{}', true) ?: [];

    // ─── ✅ Parse emotion_videos_3d ───────────────────────
    $ai_data['emotion_videos_3d_array'] = json_decode($ai_data['emotion_videos_3d'] ?? '{}', true) ?: [];

    // ─── ✅ Parse startup_actions ─────────────────────────
    $ai_data['startup_actions'] = json_decode($ai_data['startup_actions'] ?? '{}', true) ?: (object)[];

    $ai_data['preferred_language'] = $preferred_language;
    $ai_data['user_companion_id']  = $user_companion_id;

    error_log("✅ startup_actions: " . json_encode($ai_data['startup_actions']));
    error_log("✅ emotion_videos_3d keys: " . implode(',', array_keys($ai_data['emotion_videos_3d_array'])));

    $response = [
        'status'             => 'success',
        'ai_data'            => $ai_data,
        'preferred_language' => $preferred_language,
        'companion_id'       => $user_companion_id,
        'message'            => 'AI data loaded successfully',
    ];

} catch (Exception $e) {
    $response = ['status' => 'error', 'message' => $e->getMessage()];
    error_log("ERROR in get_ai_data.php: " . $e->getMessage());
}

$conn->close();
echo json_encode($response);