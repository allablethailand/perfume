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
    
    // ดึงข้อมูล AI จาก database
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
            ai_avatar_path,
            ai_avatar_url,
            ai_video_path,
            ai_video_url,
            idle_video_path,
            idle_video_url,
            talking_video_path,
            talking_video_url,
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
            voice_id_th,
            voice_id_en,
            voice_id_cn,
            voice_id_jp,
            voice_id_kr,
            voice_gender_th,
            voice_gender_en,
            voice_gender_cn,
            voice_gender_jp,
            voice_gender_kr,
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
    
    error_log("✅ AI Data found: ai_id=" . $ai_data['ai_id'] . ", name=" . $ai_data['ai_name_th']);
    
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