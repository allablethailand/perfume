<?php
header('Content-Type: application/json');
require_once('../../lib/connect.php');
require_once('../../lib/jwt_helper.php');
global $conn;

$response = ['status' => 'error', 'message' => ''];

try {
    // ตรวจสอบ JWT
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        throw new Exception("Unauthorized - No token provided");
    }

    $jwt = $matches[1];
    $decoded = validateJWT($jwt);
    
    if (!$decoded) {
        throw new Exception("Unauthorized - Invalid token");
    }

    // แก้ตรงนี้ - เพิ่ม ->data
    $user_id = $decoded->data->user_id;

    // รับข้อมูล
    if (!isset($_POST['ai_id']) || !isset($_POST['preferred_language'])) {
        throw new Exception("Missing required fields");
    }

    $ai_id = intval($_POST['ai_id']);
    $preferred_language = $_POST['preferred_language'];

    // ตรวจสอบว่ามี AI นี้ในระบบหรือไม่
    $check_ai = $conn->prepare("SELECT ai_id FROM ai_companions WHERE ai_id = ? AND del = 0 AND status = 1");
    $check_ai->bind_param("i", $ai_id);
    $check_ai->execute();
    $check_ai->store_result();

    if ($check_ai->num_rows === 0) {
        throw new Exception("AI Companion not found");
    }
    $check_ai->close();

    // ตรวจสอบว่า user เคยเปิดใช้งาน AI ตัวนี้หรือยัง
    $check_existing = $conn->prepare("
        SELECT user_companion_id, setup_completed 
        FROM user_ai_companions 
        WHERE user_id = ? AND ai_id = ? AND del = 0
    ");
    $check_existing->bind_param("ii", $user_id, $ai_id);
    $check_existing->execute();
    $existing_result = $check_existing->get_result();

    if ($existing_result->num_rows > 0) {
        $existing = $existing_result->fetch_assoc();
        
        if ($existing['setup_completed'] == 1) {
            $response = [
                'status' => 'success',
                'message' => 'AI Companion already activated',
                'user_companion_id' => $existing['user_companion_id'],
                'redirect' => 'chat'
            ];
        } else {
            $response = [
                'status' => 'success',
                'message' => 'Continue setup',
                'user_companion_id' => $existing['user_companion_id']
            ];
        }
        
        $check_existing->close();
        $conn->close();
        echo json_encode($response);
        exit;
    }

    $check_existing->close();

    // สร้างความสัมพันธ์ใหม่
    $stmt = $conn->prepare("
        INSERT INTO user_ai_companions 
        (user_id, ai_id, preferred_language, setup_completed, del) 
        VALUES (?, ?, ?, 0, 0)
    ");
    
    $stmt->bind_param("iis", $user_id, $ai_id, $preferred_language);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to create AI Companion: " . $stmt->error);
    }

    $user_companion_id = $conn->insert_id;
    $stmt->close();

    $response = [
        'status' => 'success',
        'message' => 'AI Companion created successfully',
        'user_companion_id' => $user_companion_id
    ];

} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
    error_log("Error in create_user_companion.php: " . $e->getMessage());
}

$conn->close();
echo json_encode($response);
?>