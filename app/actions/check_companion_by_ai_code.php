<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../../lib/connect.php');

global $conn;

$ai_code = strtoupper(trim($_GET['ai_code'] ?? ''));

// Log the incoming request
error_log("=== CHECK COMPANION BY AI CODE ===");
error_log("Received AI Code: " . $ai_code);
error_log("Raw GET parameter: " . ($_GET['ai_code'] ?? 'NOT SET'));

if (empty($ai_code) || !preg_match('/^AI-[A-Z0-9]{8,}$/', $ai_code)) {
    error_log("❌ Invalid AI code format: " . $ai_code);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid AI code format',
        'debug' => [
            'received' => $ai_code,
            'empty' => empty($ai_code),
            'format_valid' => preg_match('/^AI-[A-Z0-9]{8,}$/', $ai_code)
        ]
    ]);
    exit();
}

try {
    // Step 1: ตรวจสอบว่า AI code มีอยู่จริงหรือไม่
    $stmt = $conn->prepare("
        SELECT ai_id, ai_code, ai_name_th, ai_name_en, ai_avatar_url
        FROM ai_companions 
        WHERE ai_code = ? AND status = 1 AND del = 0
        LIMIT 1
    ");
    $stmt->bind_param("s", $ai_code);
    $stmt->execute();
    $result = $stmt->get_result();
    $ai = $result->fetch_assoc();
    $stmt->close();
    
    if (!$ai) {
        // AI code ไม่มีในระบบ
        error_log("❌ AI code not found: " . $ai_code);
        echo json_encode([
            'status' => 'error',
            'message' => 'AI Companion not found'
        ]);
        exit();
    }
    
    error_log("✅ Found AI: " . $ai['ai_name_th'] . " (ID: " . $ai['ai_id'] . ")");
    
    $ai_id = (int)$ai['ai_id'];
    
    // Step 2: เช็คว่ามี user_companion_id หรือยัง (ไม่สนใจว่า user คนไหน)
    $stmt = $conn->prepare("
        SELECT 
            user_companion_id,
            user_id,
            preferred_language,
            setup_completed
        FROM user_ai_companions
        WHERE ai_id = ? AND del = 0
        LIMIT 1
    ");
    $stmt->bind_param("i", $ai_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $companion = $result->fetch_assoc();
    $stmt->close();
    
    if ($companion) {
        // ✅ มี companion แล้ว
        error_log("✅ Found companion_id: " . $companion['user_companion_id']);
        
        echo json_encode([
            'status' => 'success',
            'has_companion' => true,
            'companion_id' => (int)$companion['user_companion_id'],
            'setup_completed' => (int)$companion['setup_completed'],
            'preferred_language' => $companion['preferred_language'] ?: 'th',
            'ai_data' => [
                'ai_id' => $ai_id,
                'ai_name' => $ai['ai_name_th'],
                'ai_avatar_url' => $ai['ai_avatar_url']
            ]
        ]);
    } else {
        // ❌ ยังไม่มี companion
        error_log("❌ No companion found for AI ID: " . $ai_id);
        
        echo json_encode([
            'status' => 'success',
            'has_companion' => false,
            'ai_id' => $ai_id,
            'ai_data' => [
                'ai_id' => $ai_id,
                'ai_code' => $ai['ai_code'],
                'ai_name' => $ai['ai_name_th'],
                'ai_avatar_url' => $ai['ai_avatar_url']
            ]
        ]);
    }
    
} catch (Exception $e) {
    error_log('check_companion_by_ai_code error: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error'
    ]);
}
?>