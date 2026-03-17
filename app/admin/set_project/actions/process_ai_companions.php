<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');
require_once(__DIR__ . '/../../../../lib/base_directory.php');
require_once(__DIR__ . '/../../../../lib/connect.php');

global $base_path;
global $conn;

$response = ['status' => 'error', 'message' => ''];

function logDebug($message, $data = null) {
    $log_file = __DIR__ . '/../../../../logs/upload_debug.log';
    $log_dir  = dirname($log_file);
    if (!is_dir($log_dir)) { @mkdir($log_dir, 0755, true); }
    $timestamp   = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message";
    if ($data !== null) { $log_message .= "\n" . print_r($data, true); }
    $log_message .= "\n" . str_repeat('-', 80) . "\n";
    @file_put_contents($log_file, $log_message, FILE_APPEND);
}

function handleFileUpload($file_input_name, $upload_type = 'avatar') {
    global $base_path;
    if (!isset($_FILES[$file_input_name]) || $_FILES[$file_input_name]['error'] !== UPLOAD_ERR_OK) {
        $error_msg = isset($_FILES[$file_input_name]) ? 'Error code: ' . $_FILES[$file_input_name]['error'] : 'No file uploaded';
        return ['success' => false, 'error' => $error_msg];
    }
    if (!is_uploaded_file($_FILES[$file_input_name]['tmp_name'])) {
        return ['success' => false, 'error' => 'Invalid uploaded file'];
    }
    $upload_dir = __DIR__ . '/../../../../public/ai_' . $upload_type . 's/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            return ['success' => false, 'error' => 'Cannot create upload directory'];
        }
    }
    $file_extension  = strtolower(pathinfo($_FILES[$file_input_name]['name'], PATHINFO_EXTENSION));
    $unique_filename = $upload_type . '_' . uniqid() . '_' . time() . '.' . $file_extension;
    $file_path       = $upload_dir . $unique_filename;
    $api_path        = $base_path . '/public/ai_' . $upload_type . 's/' . $unique_filename;
    if (!move_uploaded_file($_FILES[$file_input_name]['tmp_name'], $file_path)) {
        return ['success' => false, 'error' => 'Failed to move uploaded file'];
    }
    return ['success' => true, 'file_path' => $file_path, 'api_path' => $api_path];
}

function handleMultipleFileUpload($file_input_name, $upload_type = 'video') {
    global $base_path;
    if (!isset($_FILES[$file_input_name])) { return ['success' => true, 'urls' => []]; }
    $files = $_FILES[$file_input_name];
    if (!is_array($files['name']))         { return ['success' => true, 'urls' => []]; }
    $file_count  = count($files['name']);
    $upload_dir  = __DIR__ . '/../../../../public/ai_' . $upload_type . 's/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) { return ['success' => false, 'error' => 'Cannot create upload directory']; }
    }
    $uploaded_urls = [];
    for ($i = 0; $i < $file_count; $i++) {
        if (!isset($files['error'][$i]) || $files['error'][$i] === UPLOAD_ERR_NO_FILE) { continue; }
        if ($files['error'][$i] !== UPLOAD_ERR_OK)           { continue; }
        if (!is_uploaded_file($files['tmp_name'][$i]))        { continue; }
        $file_extension  = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
        $unique_filename = $upload_type . '_' . uniqid() . '_' . time() . '_' . $i . '.' . $file_extension;
        $file_path       = $upload_dir . $unique_filename;
        $api_path        = $base_path . '/public/ai_' . $upload_type . 's/' . $unique_filename;
        if (move_uploaded_file($files['tmp_name'][$i], $file_path)) { $uploaded_urls[] = $api_path; }
    }
    return ['success' => true, 'urls' => $uploaded_urls];
}

function handleEmotionVideosUpload() {
    global $base_path;
    $VALID_EMOTIONS = ['happy', 'sad', 'excited', 'calm', 'thinking', 'surprised', 'empathetic'];
    $result = [];
    if (!isset($_FILES['emotion_videos'])) { return $result; }
    $emotion_files   = $_FILES['emotion_videos'];
    $upload_dir_video = __DIR__ . '/../../../../public/ai_videos/';
    $upload_dir_gif   = __DIR__ . '/../../../../public/ai_avatars/';
    if (!is_dir($upload_dir_video)) { @mkdir($upload_dir_video, 0755, true); }
    if (!is_dir($upload_dir_gif))   { @mkdir($upload_dir_gif,   0755, true); }

    foreach ($VALID_EMOTIONS as $emotion) {
        if (!isset($emotion_files['name'][$emotion])) { continue; }
        $files_for_emotion = [
            'name'     => $emotion_files['name'][$emotion],
            'type'     => $emotion_files['type'][$emotion],
            'tmp_name' => $emotion_files['tmp_name'][$emotion],
            'error'    => $emotion_files['error'][$emotion],
            'size'     => $emotion_files['size'][$emotion],
        ];
        $urls       = [];
        $file_count = count($files_for_emotion['name']);
        for ($i = 0; $i < $file_count; $i++) {
            if ($files_for_emotion['error'][$i] === UPLOAD_ERR_NO_FILE) { continue; }
            if ($files_for_emotion['error'][$i] !== UPLOAD_ERR_OK)      { continue; }
            if (!is_uploaded_file($files_for_emotion['tmp_name'][$i]))  { continue; }
            $ext        = strtolower(pathinfo($files_for_emotion['name'][$i], PATHINFO_EXTENSION));
            $is_gif     = ($ext === 'gif');
            $upload_dir  = $is_gif ? $upload_dir_gif   : $upload_dir_video;
            $type_prefix = $is_gif ? 'avatars'         : 'videos';
            $unique_filename = 'emotion_' . $emotion . '_' . uniqid() . '_' . time() . '_' . $i . '.' . $ext;
            $file_path  = $upload_dir . $unique_filename;
            $api_path   = $base_path . '/public/ai_' . $type_prefix . '/' . $unique_filename;
            if (move_uploaded_file($files_for_emotion['tmp_name'][$i], $file_path)) { $urls[] = $api_path; }
        }
        if (!empty($urls)) { $result[$emotion] = $urls; }
    }
    return $result;
}

function handleEmotionVideos3DUpload() {
    global $base_path;
    $VALID_EMOTIONS = ['happy', 'sad', 'excited', 'calm', 'thinking', 'surprised', 'empathetic'];
    $VALID_STATES   = ['idle', 'talking'];
    $result = [];
    if (!isset($_FILES['emotion_videos_3d'])) { return $result; }
    $emotion_files    = $_FILES['emotion_videos_3d'];
    $upload_dir_video = __DIR__ . '/../../../../public/ai_videos/';
    $upload_dir_gif   = __DIR__ . '/../../../../public/ai_avatars/';
    if (!is_dir($upload_dir_video)) { @mkdir($upload_dir_video, 0755, true); }
    if (!is_dir($upload_dir_gif))   { @mkdir($upload_dir_gif,   0755, true); }

    foreach ($VALID_EMOTIONS as $emotion) {
        if (!isset($emotion_files['name'][$emotion])) { continue; }
        foreach ($VALID_STATES as $state) {
            if (!isset($emotion_files['name'][$emotion][$state])) { continue; }
            $files_for_slot = [
                'name'     => $emotion_files['name'][$emotion][$state],
                'type'     => $emotion_files['type'][$emotion][$state],
                'tmp_name' => $emotion_files['tmp_name'][$emotion][$state],
                'error'    => $emotion_files['error'][$emotion][$state],
                'size'     => $emotion_files['size'][$emotion][$state],
            ];
            $urls       = [];
            $file_count = count($files_for_slot['name']);
            for ($i = 0; $i < $file_count; $i++) {
                if ($files_for_slot['error'][$i] === UPLOAD_ERR_NO_FILE) { continue; }
                if ($files_for_slot['error'][$i] !== UPLOAD_ERR_OK)      { continue; }
                if (!is_uploaded_file($files_for_slot['tmp_name'][$i]))  { continue; }
                $ext        = strtolower(pathinfo($files_for_slot['name'][$i], PATHINFO_EXTENSION));
                $is_gif     = ($ext === 'gif');
                $upload_dir  = $is_gif ? $upload_dir_gif   : $upload_dir_video;
                $type_prefix = $is_gif ? 'avatars'         : 'videos';
                $unique_filename = 'em3d_' . $emotion . '_' . $state . '_' . uniqid() . '_' . time() . '_' . $i . '.' . $ext;
                $file_path  = $upload_dir . $unique_filename;
                $api_path   = $base_path . '/public/ai_' . $type_prefix . '/' . $unique_filename;
                if (move_uploaded_file($files_for_slot['tmp_name'][$i], $file_path)) { $urls[] = $api_path; }
            }
            if (!empty($urls)) {
                if (!isset($result[$emotion])) { $result[$emotion] = ['idle' => [], 'talking' => []]; }
                $result[$emotion][$state] = $urls;
            }
        }
    }
    return $result;
}

// ──────────────────────────────────────────────────────────────────────────────
// ✅ Helper: build startup_actions JSON from POST
// ──────────────────────────────────────────────────────────────────────────────
function buildStartupActions() {
    return json_encode([
        'weather'           => !empty($_POST['sa_weather']),
        'news_world'        => !empty($_POST['sa_news_world']),
        'news_country'      => !empty($_POST['sa_news_country']),
        'news_country_code' => $_POST['sa_country_code'] ?? 'th',
        // ℹ️ ภาษาที่ AI พูดดึงจาก userPreferredLanguage ของ companion อัตโนมัติ ไม่ต้องเซ็ตที่นี่
    ], JSON_UNESCAPED_UNICODE);
}

try {
    if (!isset($_POST['action'])) { throw new Exception("No action specified."); }

    $action = $_POST['action'];
    logDebug("Action: $action", $_POST);

    // ========================================
    // GET AI COMPANIONS LIST (DataTables)
    // ========================================
    if ($action == 'getData_ai_companions') {
        $draw        = isset($_POST['draw'])   ? intval($_POST['draw'])   : 1;
        $start       = isset($_POST['start'])  ? intval($_POST['start'])  : 0;
        $length      = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $searchValue = isset($_POST['search']['value']) ? $conn->real_escape_string($_POST['search']['value']) : '';
        $lang        = isset($_POST['lang']) ? $_POST['lang'] : 'th';
        $name_col    = "ai_name_" . $lang;

        $whereClause = "ai.del = 0";
        if (!empty($searchValue)) {
            $whereClause .= " AND (ai.ai_code LIKE '%$searchValue%' 
                            OR ai.ai_name_th LIKE '%$searchValue%' 
                            OR ai.ai_name_en LIKE '%$searchValue%' 
                            OR pi.serial_number LIKE '%$searchValue%')";
        }

        $totalRecords  = $conn->query("SELECT COUNT(ai_id) FROM ai_companions WHERE del = 0")->fetch_row()[0];
        $totalFiltered = $conn->query("SELECT COUNT(ai.ai_id) FROM ai_companions ai LEFT JOIN product_items pi ON ai.item_id = pi.item_id WHERE $whereClause")->fetch_row()[0];

        $dataResult = $conn->query("SELECT ai.*, pi.serial_number,
                        (SELECT COUNT(*) FROM user_ai_companions WHERE ai_id = ai.ai_id AND del = 0) as user_count
                      FROM ai_companions ai
                      LEFT JOIN product_items pi ON ai.item_id = pi.item_id
                      WHERE $whereClause
                      ORDER BY ai.created_at DESC
                      LIMIT $start, $length");

        $data = [];
        if ($dataResult) {
            while ($row = $dataResult->fetch_assoc()) {
                $row['ai_name_display']          = $row[$name_col];
                $row['idle_video_urls_array']    = json_decode($row['idle_video_urls']    ?? '[]', true);
                $row['talking_video_urls_array'] = json_decode($row['talking_video_urls'] ?? '[]', true);
                $row['emotion_videos_array']     = json_decode($row['emotion_videos']     ?? '{}', true);
                $row['emotion_videos_3d_array']  = json_decode($row['emotion_videos_3d']  ?? '{}', true);
                // ✅ ส่ง startup_actions กลับด้วย (decoded)
                $row['startup_actions']          = json_decode($row['startup_actions']    ?? '{}', true) ?: (object)[];
                $data[] = $row;
            }
        }

        $response = [
            "draw"            => intval($draw),
            "recordsTotal"    => intval($totalRecords),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data,
        ];

    // ========================================
    // ADD AI COMPANION
    // ========================================
    } elseif ($action == 'addAICompanion') {

        logDebug("=== ADD AI COMPANION START ===");

        $item_id = $_POST['item_id'] ?? 0;
        $ai_code = $_POST['ai_code'] ?? '';

        $ai_name_th = $_POST['ai_name_th'] ?? '';
        $ai_name_en = $_POST['ai_name_en'] ?? '';
        $ai_name_cn = $_POST['ai_name_cn'] ?? '';
        $ai_name_jp = $_POST['ai_name_jp'] ?? '';
        $ai_name_kr = $_POST['ai_name_kr'] ?? '';

        $system_prompt_th = $_POST['system_prompt_th'] ?? '';
        $system_prompt_en = $_POST['system_prompt_en'] ?? '';
        $system_prompt_cn = $_POST['system_prompt_cn'] ?? '';
        $system_prompt_jp = $_POST['system_prompt_jp'] ?? '';
        $system_prompt_kr = $_POST['system_prompt_kr'] ?? '';

        $perfume_knowledge_th = $_POST['perfume_knowledge_th'] ?? '';
        $perfume_knowledge_en = $_POST['perfume_knowledge_en'] ?? '';
        $perfume_knowledge_cn = $_POST['perfume_knowledge_cn'] ?? '';
        $perfume_knowledge_jp = $_POST['perfume_knowledge_jp'] ?? '';
        $perfume_knowledge_kr = $_POST['perfume_knowledge_kr'] ?? '';

        $style_suggestions_th = $_POST['style_suggestions_th'] ?? '';
        $style_suggestions_en = $_POST['style_suggestions_en'] ?? '';
        $style_suggestions_cn = $_POST['style_suggestions_cn'] ?? '';
        $style_suggestions_jp = $_POST['style_suggestions_jp'] ?? '';
        $style_suggestions_kr = $_POST['style_suggestions_kr'] ?? '';

        $voice_id          = $_POST['voice_id']          ?? null;
        $voice_name        = $_POST['voice_name']        ?? null;
        $voice_preview_url = $_POST['voice_preview_url'] ?? null;
        $status            = $_POST['status']            ?? 1;

        // ✅ Startup actions
        $startup_actions_json = buildStartupActions();

        if (empty($item_id))    throw new Exception("Bottle item is required.");
        if (empty($ai_code))    throw new Exception("AI Code is required.");
        if (empty($ai_name_th)) throw new Exception("AI Name (Thai) is required.");

        $check_code = $conn->prepare("SELECT ai_id FROM ai_companions WHERE ai_code = ? AND del = 0");
        $check_code->bind_param("s", $ai_code);
        $check_code->execute();
        $check_code->store_result();
        if ($check_code->num_rows > 0) throw new Exception("AI Code already exists.");
        $check_code->close();

        $conn->begin_transaction();
        try {
            $ai_avatar_url      = null;
            $ai_video_url       = null;
            $idle_video_urls    = '[]';
            $talking_video_urls = '[]';
            $emotion_videos     = '{}';
            $emotion_videos_3d  = '{}';

            if (isset($_FILES['ai_avatar']) && $_FILES['ai_avatar']['error'] === UPLOAD_ERR_OK) {
                $r = handleFileUpload('ai_avatar', 'avatar');
                if ($r['success']) { $ai_avatar_url = $r['api_path']; }
                else { throw new Exception("Avatar upload failed: " . $r['error']); }
            }

            if (isset($_FILES['ai_video']) && $_FILES['ai_video']['error'] === UPLOAD_ERR_OK) {
                $r = handleFileUpload('ai_video', 'video');
                if ($r['success']) { $ai_video_url = $r['api_path']; }
                else { throw new Exception("Intro video upload failed: " . $r['error']); }
            }

            $r = handleMultipleFileUpload('idle_videos', 'video');
            if ($r['success'] && !empty($r['urls'])) {
                $idle_video_urls = json_encode($r['urls'], JSON_UNESCAPED_SLASHES);
            }

            $r = handleMultipleFileUpload('talking_videos', 'video');
            if ($r['success'] && !empty($r['urls'])) {
                $talking_video_urls = json_encode($r['urls'], JSON_UNESCAPED_SLASHES);
            }

            $uploaded_emotions = handleEmotionVideosUpload();
            if (!empty($uploaded_emotions)) {
                $emotion_videos = json_encode($uploaded_emotions, JSON_UNESCAPED_SLASHES);
            }

            $uploaded_emotions_3d = handleEmotionVideos3DUpload();
            if (!empty($uploaded_emotions_3d)) {
                $emotion_videos_3d = json_encode($uploaded_emotions_3d, JSON_UNESCAPED_SLASHES);
            }

            // ✅ INSERT with startup_actions
            $stmt = $conn->prepare("INSERT INTO ai_companions 
                (item_id, ai_code, 
                 ai_name_th, ai_name_en, ai_name_cn, ai_name_jp, ai_name_kr,
                 ai_avatar_url, ai_video_url, idle_video_urls, talking_video_urls,
                 emotion_videos, emotion_videos_3d,
                 system_prompt_th, system_prompt_en, system_prompt_cn, system_prompt_jp, system_prompt_kr,
                 perfume_knowledge_th, perfume_knowledge_en, perfume_knowledge_cn, perfume_knowledge_jp, perfume_knowledge_kr,
                 style_suggestions_th, style_suggestions_en, style_suggestions_cn, style_suggestions_jp, style_suggestions_kr,
                 voice_id, voice_name, voice_preview_url,
                 startup_actions,
                 status, del) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");

            $stmt->bind_param(
                "issssss" .   // 1:item_id(i), 2:ai_code, 3-7:names
                "ssssss"  .   // 8-13: avatar, video, idle_urls, talking_urls, emotions2d, emotions3d
                "sssss"   .   // 14-18: system_prompts
                "sssss"   .   // 19-23: perfume_knowledge
                "sssss"   .   // 24-28: style_suggestions
                "ssss"    .   // 29-32: voice_id, voice_name, voice_preview_url, startup_actions
                "i",          // 33: status
                $item_id, $ai_code,
                $ai_name_th, $ai_name_en, $ai_name_cn, $ai_name_jp, $ai_name_kr,
                $ai_avatar_url, $ai_video_url, $idle_video_urls, $talking_video_urls,
                $emotion_videos, $emotion_videos_3d,
                $system_prompt_th, $system_prompt_en, $system_prompt_cn, $system_prompt_jp, $system_prompt_kr,
                $perfume_knowledge_th, $perfume_knowledge_en, $perfume_knowledge_cn, $perfume_knowledge_jp, $perfume_knowledge_kr,
                $style_suggestions_th, $style_suggestions_en, $style_suggestions_cn, $style_suggestions_jp, $style_suggestions_kr,
                $voice_id, $voice_name, $voice_preview_url,
                $startup_actions_json,
                $status
            );

            if (!$stmt->execute()) throw new Exception("Failed to add AI Companion: " . $stmt->error);
            $ai_id = $conn->insert_id;
            $stmt->close();
            $conn->commit();

            $response = [
                'status'  => 'success',
                'message' => 'AI Companion added successfully!',
                'ai_id'   => $ai_id,
            ];

        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }

    // ========================================
    // EDIT AI COMPANION
    // ========================================
    } elseif ($action == 'editAICompanion') {

        logDebug("=== EDIT AI COMPANION START ===");

        $ai_id = $_POST['ai_id'] ?? 0;
        if (empty($ai_id)) throw new Exception("AI ID is missing.");

        $item_id = $_POST['item_id'] ?? 0;
        $ai_code = $_POST['ai_code'] ?? '';

        $ai_name_th = $_POST['ai_name_th'] ?? '';
        $ai_name_en = $_POST['ai_name_en'] ?? '';
        $ai_name_cn = $_POST['ai_name_cn'] ?? '';
        $ai_name_jp = $_POST['ai_name_jp'] ?? '';
        $ai_name_kr = $_POST['ai_name_kr'] ?? '';

        $system_prompt_th = $_POST['system_prompt_th'] ?? '';
        $system_prompt_en = $_POST['system_prompt_en'] ?? '';
        $system_prompt_cn = $_POST['system_prompt_cn'] ?? '';
        $system_prompt_jp = $_POST['system_prompt_jp'] ?? '';
        $system_prompt_kr = $_POST['system_prompt_kr'] ?? '';

        $perfume_knowledge_th = $_POST['perfume_knowledge_th'] ?? '';
        $perfume_knowledge_en = $_POST['perfume_knowledge_en'] ?? '';
        $perfume_knowledge_cn = $_POST['perfume_knowledge_cn'] ?? '';
        $perfume_knowledge_jp = $_POST['perfume_knowledge_jp'] ?? '';
        $perfume_knowledge_kr = $_POST['perfume_knowledge_kr'] ?? '';

        $style_suggestions_th = $_POST['style_suggestions_th'] ?? '';
        $style_suggestions_en = $_POST['style_suggestions_en'] ?? '';
        $style_suggestions_cn = $_POST['style_suggestions_cn'] ?? '';
        $style_suggestions_jp = $_POST['style_suggestions_jp'] ?? '';
        $style_suggestions_kr = $_POST['style_suggestions_kr'] ?? '';

        $voice_id          = $_POST['voice_id']          ?? null;
        $voice_name        = $_POST['voice_name']        ?? null;
        $voice_preview_url = $_POST['voice_preview_url'] ?? null;
        $status            = $_POST['status']            ?? 1;

        $delete_avatar = $_POST['delete_avatar'] ?? '0';
        $delete_video  = $_POST['delete_video']  ?? '0';

        $deleted_idle_videos    = isset($_POST['deleted_idle_videos'])    ? json_decode($_POST['deleted_idle_videos'],    true) : [];
        $deleted_talking_videos = isset($_POST['deleted_talking_videos']) ? json_decode($_POST['deleted_talking_videos'], true) : [];

        // ✅ Startup actions
        $startup_actions_json = buildStartupActions();

        // 2D deleted emotion videos
        $deleted_emotion_by_emotion = [];
        $VALID_EMOTIONS = ['happy', 'sad', 'excited', 'calm', 'thinking', 'surprised', 'empathetic'];
        foreach ($VALID_EMOTIONS as $em) {
            if (isset($_POST['deleted_emotion_videos'][$em])) {
                $raw     = $_POST['deleted_emotion_videos'][$em];
                $decoded = is_array($raw) ? $raw : json_decode($raw, true);
                if (!empty($decoded)) {
                    $deleted_emotion_by_emotion[$em] = array_map(fn($u) => str_replace('\\/', '/', $u), $decoded);
                }
            }
        }

        // 3D deleted emotion videos
        $VALID_STATES     = ['idle', 'talking'];
        $deleted_emotion_3d = [];
        foreach ($VALID_EMOTIONS as $em) {
            foreach ($VALID_STATES as $st) {
                if (isset($_POST['deleted_emotion_videos_3d'][$em][$st])) {
                    $raw     = $_POST['deleted_emotion_videos_3d'][$em][$st];
                    $decoded = is_array($raw) ? $raw : json_decode($raw, true);
                    if (!empty($decoded)) {
                        if (!isset($deleted_emotion_3d[$em])) $deleted_emotion_3d[$em] = [];
                        $deleted_emotion_3d[$em][$st] = array_map(fn($u) => str_replace('\\/', '/', $u), $decoded);
                    }
                }
            }
        }

        $check_code = $conn->prepare("SELECT ai_id FROM ai_companions WHERE ai_code = ? AND ai_id != ? AND del = 0");
        $check_code->bind_param("si", $ai_code, $ai_id);
        $check_code->execute();
        $check_code->store_result();
        if ($check_code->num_rows > 0) throw new Exception("AI Code already exists.");
        $check_code->close();

        $conn->begin_transaction();
        try {
            $current_result = $conn->query("SELECT ai_avatar_url, ai_video_url, idle_video_urls, talking_video_urls, 
                                                   emotion_videos, emotion_videos_3d 
                                            FROM ai_companions WHERE ai_id = $ai_id");
            $current = $current_result->fetch_assoc();

            $ai_avatar_url            = $current['ai_avatar_url'];
            $ai_video_url             = $current['ai_video_url'];
            $idle_video_urls_array    = json_decode($current['idle_video_urls']    ?? '[]', true);
            $talking_video_urls_array = json_decode($current['talking_video_urls'] ?? '[]', true);
            $emotion_videos_map       = json_decode($current['emotion_videos']     ?? '{}', true) ?: [];
            $emotion_videos_3d_map    = json_decode($current['emotion_videos_3d']  ?? '{}', true) ?: [];

            // Avatar
            if ($delete_avatar === '1') {
                $ai_avatar_url = null;
            } elseif (isset($_FILES['ai_avatar']) && $_FILES['ai_avatar']['error'] === UPLOAD_ERR_OK) {
                $r = handleFileUpload('ai_avatar', 'avatar');
                if ($r['success']) { $ai_avatar_url = $r['api_path']; }
            }

            // Intro Video
            if ($delete_video === '1') {
                $ai_video_url = null;
            } elseif (isset($_FILES['ai_video']) && $_FILES['ai_video']['error'] === UPLOAD_ERR_OK) {
                $r = handleFileUpload('ai_video', 'video');
                if ($r['success']) { $ai_video_url = $r['api_path']; }
            }

            // Idle videos
            if (!empty($deleted_idle_videos)) {
                $n_del   = array_map(fn($u) => str_replace('\\/', '/', $u), $deleted_idle_videos);
                $n_exist = array_map(fn($u) => str_replace('\\/', '/', $u), $idle_video_urls_array);
                $idle_video_urls_array = array_values(array_diff($n_exist, $n_del));
            }
            $r = handleMultipleFileUpload('idle_videos', 'video');
            if ($r['success'] && !empty($r['urls'])) {
                $idle_video_urls_array = array_merge($idle_video_urls_array, $r['urls']);
            }

            // Talking videos
            if (!empty($deleted_talking_videos)) {
                $n_del   = array_map(fn($u) => str_replace('\\/', '/', $u), $deleted_talking_videos);
                $n_exist = array_map(fn($u) => str_replace('\\/', '/', $u), $talking_video_urls_array);
                $talking_video_urls_array = array_values(array_diff($n_exist, $n_del));
            }
            $r = handleMultipleFileUpload('talking_videos', 'video');
            if ($r['success'] && !empty($r['urls'])) {
                $talking_video_urls_array = array_merge($talking_video_urls_array, $r['urls']);
            }

            // 2D emotion videos
            foreach ($deleted_emotion_by_emotion as $em => $del_urls) {
                if (!isset($emotion_videos_map[$em])) { continue; }
                $n_exist = array_map(fn($u) => str_replace('\\/', '/', $u), $emotion_videos_map[$em]);
                $emotion_videos_map[$em] = array_values(array_diff($n_exist, $del_urls));
                if (empty($emotion_videos_map[$em])) { unset($emotion_videos_map[$em]); }
            }
            $new_emotions = handleEmotionVideosUpload();
            foreach ($new_emotions as $em => $new_urls) {
                if (!isset($emotion_videos_map[$em])) { $emotion_videos_map[$em] = []; }
                $emotion_videos_map[$em] = array_merge($emotion_videos_map[$em], $new_urls);
            }

            // 3D emotion videos
            foreach ($deleted_emotion_3d as $em => $states) {
                foreach ($states as $st => $del_urls) {
                    if (!isset($emotion_videos_3d_map[$em][$st])) { continue; }
                    $n_exist = array_map(fn($u) => str_replace('\\/', '/', $u), $emotion_videos_3d_map[$em][$st]);
                    $emotion_videos_3d_map[$em][$st] = array_values(array_diff($n_exist, $del_urls));
                }
                $idle_empty    = empty($emotion_videos_3d_map[$em]['idle']    ?? []);
                $talking_empty = empty($emotion_videos_3d_map[$em]['talking'] ?? []);
                if ($idle_empty && $talking_empty) { unset($emotion_videos_3d_map[$em]); }
            }
            $new_emotions_3d = handleEmotionVideos3DUpload();
            foreach ($new_emotions_3d as $em => $states) {
                if (!isset($emotion_videos_3d_map[$em])) { $emotion_videos_3d_map[$em] = ['idle' => [], 'talking' => []]; }
                foreach ($states as $st => $new_urls) {
                    if (!isset($emotion_videos_3d_map[$em][$st])) { $emotion_videos_3d_map[$em][$st] = []; }
                    $emotion_videos_3d_map[$em][$st] = array_merge($emotion_videos_3d_map[$em][$st], $new_urls);
                }
            }

            $idle_video_urls    = json_encode($idle_video_urls_array,    JSON_UNESCAPED_SLASHES);
            $talking_video_urls = json_encode($talking_video_urls_array, JSON_UNESCAPED_SLASHES);
            $emotion_videos     = json_encode($emotion_videos_map,       JSON_UNESCAPED_SLASHES);
            $emotion_videos_3d  = json_encode($emotion_videos_3d_map,    JSON_UNESCAPED_SLASHES);

            // ✅ UPDATE with startup_actions
            $update_query = "UPDATE ai_companions SET 
                item_id = ?, ai_code = ?,
                ai_name_th = ?, ai_name_en = ?, ai_name_cn = ?, ai_name_jp = ?, ai_name_kr = ?,
                ai_avatar_url = ?, ai_video_url = ?, idle_video_urls = ?, talking_video_urls = ?,
                emotion_videos = ?, emotion_videos_3d = ?,
                system_prompt_th = ?, system_prompt_en = ?, system_prompt_cn = ?, system_prompt_jp = ?, system_prompt_kr = ?,
                perfume_knowledge_th = ?, perfume_knowledge_en = ?, perfume_knowledge_cn = ?, perfume_knowledge_jp = ?, perfume_knowledge_kr = ?,
                style_suggestions_th = ?, style_suggestions_en = ?, style_suggestions_cn = ?, style_suggestions_jp = ?, style_suggestions_kr = ?,
                voice_id = ?, voice_name = ?, voice_preview_url = ?,
                startup_actions = ?,
                status = ?
                WHERE ai_id = ?";

            $stmt = $conn->prepare($update_query);
            if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

            $stmt->bind_param(
                "issssss" .   // 1:item_id(i), 2:ai_code, 3-7:names
                "ssssss"  .   // 8-13: avatar,video,idle,talking,em2d,em3d
                "sssss"   .   // 14-18: system_prompts
                "sssss"   .   // 19-23: perfume_knowledge
                "sssss"   .   // 24-28: style_suggestions
                "ssss"    .   // 29-32: voice_id, voice_name, voice_preview, startup_actions
                "ii",         // 33:status, 34:ai_id
                $item_id, $ai_code,
                $ai_name_th, $ai_name_en, $ai_name_cn, $ai_name_jp, $ai_name_kr,
                $ai_avatar_url, $ai_video_url, $idle_video_urls, $talking_video_urls,
                $emotion_videos, $emotion_videos_3d,
                $system_prompt_th, $system_prompt_en, $system_prompt_cn, $system_prompt_jp, $system_prompt_kr,
                $perfume_knowledge_th, $perfume_knowledge_en, $perfume_knowledge_cn, $perfume_knowledge_jp, $perfume_knowledge_kr,
                $style_suggestions_th, $style_suggestions_en, $style_suggestions_cn, $style_suggestions_jp, $style_suggestions_kr,
                $voice_id, $voice_name, $voice_preview_url,
                $startup_actions_json,
                $status,
                $ai_id
            );

            if (!$stmt->execute()) throw new Exception("Failed to update AI Companion: " . $stmt->error);
            $stmt->close();
            $conn->commit();

            $response = ['status' => 'success', 'message' => 'AI Companion updated successfully!'];

        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }

    // ========================================
    // DELETE AI COMPANION
    // ========================================
    } elseif ($action == 'deleteAICompanion') {

        $ai_id = $_POST['ai_id'] ?? 0;
        if (empty($ai_id)) throw new Exception("AI ID is missing.");

        $stmt = $conn->prepare("UPDATE ai_companions SET del = 1 WHERE ai_id = ?");
        $stmt->bind_param("i", $ai_id);
        if (!$stmt->execute()) throw new Exception("Failed to delete: " . $stmt->error);
        $stmt->close();

        $response = ['status' => 'success', 'message' => 'AI Companion deleted successfully!'];

    // ========================================
    // GENERATE UNIQUE AI CODE
    // ========================================
    } elseif ($action == 'generateAICode') {

        $prefix  = 'AI-';
        $ai_code = $prefix . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        $check   = $conn->query("SELECT ai_id FROM ai_companions WHERE ai_code = '$ai_code'");
        if ($check->num_rows > 0) {
            $ai_code = $prefix . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        }
        $response = ['status' => 'success', 'ai_code' => $ai_code];

    } else {
        throw new Exception("Invalid action: $action");
    }

} catch (Exception $e) {
    $response = ['status' => 'error', 'message' => $e->getMessage()];
    logDebug("ERROR", ['message' => $e->getMessage()]);
    error_log("Error in process_ai_companions.php: " . $e->getMessage());
}

$conn->close();
echo json_encode($response);