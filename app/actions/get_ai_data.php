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
    
    // ดึงข้อมูล AI จาก database (รวม idle_video_urls และ talking_video_urls)
    $stmt = $conn->prepare("
        SELECT 
            ai_id,
            item_id,
            ai_code,
            ai_name_th,
            ai_name_en,
            ai_name_cn,
            ai_name_jp,
            ai_name_kr,
            ai_avatar_url,
            ai_video_url,
            idle_video_url,
            idle_video_urls,
            talking_video_url,
            talking_video_urls,
            system_prompt_th,
            system_prompt_en,
            system_prompt_cn,
            system_prompt_jp,
            system_prompt_kr,
            perfume_knowledge_th,
            perfume_knowledge_en,
            perfume_knowledge_cn,
            perfume_knowledge_jp,
            perfume_knowledge_kr,
            style_suggestions_th,
            style_suggestions_en,
            style_suggestions_cn,
            style_suggestions_jp,
            style_suggestions_kr,
            voice_id,
            voice_name,
            voice_preview_url,
            status,
            created_at,
            updated_at
        FROM ai_companions
        WHERE ai_code = ? AND status = 1 AND del = 0
        LIMIT 1
    ");
    
    $stmt->bind_param("s", $ai_code);
    $stmt->execute();
    $result = $stmt->get_result();
    $ai_data = $result->fetch_assoc();
    $stmt->close();
    
    if (!$ai_data) {
        error_log("ERROR: AI not found for code: " . $ai_code);
        throw new Exception("AI companion not found with code: " . $ai_code);
    }
    
    error_log("✅ AI Data found: ai_id=" . $ai_data['ai_id'] . ", name=" . $ai_data['ai_name_th'] . ", voice_id=" . $ai_data['voice_id']);
    
    // ✅ สุ่มเลือกวิดีโอจาก arrays
    $idle_videos = json_decode($ai_data['idle_video_urls'] ?? '[]', true);
    $talking_videos = json_decode($ai_data['talking_video_urls'] ?? '[]', true);
    
    // สุ่ม idle video
    $idle_video_url = null;
    if (!empty($idle_videos) && is_array($idle_videos)) {
        $random_index = array_rand($idle_videos);
        $idle_video_url = $idle_videos[$random_index];
        error_log("✅ Random idle video selected: " . $idle_video_url);
    }
    
    // สุ่ม talking video
    $talking_video_url = null;
    if (!empty($talking_videos) && is_array($talking_videos)) {
        $random_index = array_rand($talking_videos);
        $talking_video_url = $talking_videos[$random_index];
        error_log("✅ Random talking video selected: " . $talking_video_url);
    }
    
    // เพิ่ม URLs ที่สุ่มแล้วเข้าไปใน response
    $ai_data['idle_video_url'] = $idle_video_url;
    $ai_data['talking_video_url'] = $talking_video_url;
    
    // เก็บ arrays ไว้ด้วยในกรณีต้องการใช้งาน
    $ai_data['idle_video_urls_array'] = $idle_videos;
    $ai_data['talking_video_urls_array'] = $talking_videos;
    
    $response = [
        'status' => 'success',
        'ai_data' => $ai_data,
        'message' => 'AI data loaded successfully'
    ];
    
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
    error_log("ERROR in get_ai_data.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
}

$conn->close();
error_log("Response: " . json_encode($response));
echo json_encode($response);
?>