<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../../lib/connect.php');

global $conn;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$response = ['status' => 'error', 'message' => ''];

try {
    $ai_code = strtoupper(trim($_POST['ai_code'] ?? ''));
    $language = trim($_POST['language'] ?? 'th');
    
    if (empty($ai_code) || !preg_match('/^AI-[A-Z0-9]{8,}$/', $ai_code)) {
        throw new Exception("Invalid AI code format");
    }
    
    // Validate language
    $validLangs = ['th', 'en', 'cn', 'jp', 'kr'];
    if (!in_array($language, $validLangs)) {
        throw new Exception("Invalid language");
    }
    
    // เก็บภาษาใน session ไว้สำหรับใช้ตอน register
    $_SESSION['selected_language'] = $language;
    $_SESSION['pending_ai_code'] = $ai_code;
    
    // ตรวจสอบว่า AI มีอยู่จริงหรือไม่
    $stmt = $conn->prepare("
        SELECT ai_id 
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
        throw new Exception("AI Companion not found");
    }
    
    $response = [
        'status' => 'success',
        'message' => 'Language preference saved',
        'companion_id' => null, // ยังไม่ได้สร้าง companion
        'language' => $language
    ];
    
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);
?>