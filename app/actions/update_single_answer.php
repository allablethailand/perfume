<?php
header('Content-Type: application/json');
require_once('../../lib/connect.php');
require_once('../../lib/jwt_helper.php');

global $conn;

// Get JWT token
$headers = getallheaders();
$jwt = null;

if (isset($headers['Authorization'])) {
    $jwt = str_replace('Bearer ', '', $headers['Authorization']);
} elseif (isset($headers['X-Auth-Token'])) {
    $jwt = $headers['X-Auth-Token'];
}

if (!$jwt) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No token provided'
    ]);
    exit;
}

// Verify JWT
$decoded = verifyJWT($jwt);
if (!$decoded) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid token'
    ]);
    exit;
}

// แก้ไข: ใช้ $decoded->data->user_id
$user_id = isset($decoded->data->user_id) ? intval($decoded->data->user_id) : null;
$user_companion_id = $_POST['user_companion_id'] ?? null;
$question_id = $_POST['question_id'] ?? null;

if (!$user_companion_id || !$question_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required parameters'
    ]);
    exit;
}

try {
    // ตรวจสอบว่า user_companion นี้เป็นของ user ที่ login อยู่หรือไม่
    $stmt = $conn->prepare("
        SELECT user_companion_id 
        FROM user_ai_companions 
        WHERE user_companion_id = ? 
        AND user_id = ? 
        AND status = '1' 
        AND del = 0
    ");
    $stmt->bind_param("ii", $user_companion_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Unauthorized access'
        ]);
        exit;
    }
    $stmt->close();

    // ตรวจสอบว่ามีคำตอบเดิมอยู่หรือไม่
    $stmt = $conn->prepare("
        SELECT answer_id 
        FROM user_personality_answers 
        WHERE user_companion_id = ? 
        AND question_id = ?
    ");
    $stmt->bind_param("ii", $user_companion_id, $question_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $is_update = $result->num_rows > 0;
    
    if ($is_update) {
        $answer_row = $result->fetch_assoc();
        $answer_id = $answer_row['answer_id']; // เปลี่ยนจาก user_answer_id
    }
    $stmt->close();

    // เตรียมข้อมูลสำหรับ update/insert
    $choice_id = isset($_POST['choice_id']) ? $_POST['choice_id'] : null;
    $text_answer = isset($_POST['text_answer']) ? $_POST['text_answer'] : null;
    $scale_value = isset($_POST['scale_value']) ? $_POST['scale_value'] : null;
    
    if ($is_update) {
        // UPDATE existing answer
        $stmt = $conn->prepare("
            UPDATE user_personality_answers 
            SET choice_id = ?, 
                text_answer = ?, 
                scale_value = ?
            WHERE answer_id = ?
        ");
        $stmt->bind_param("isii", $choice_id, $text_answer, $scale_value, $answer_id);
    } else {
        // INSERT new answer
        $stmt = $conn->prepare("
            INSERT INTO user_personality_answers 
            (user_companion_id, question_id, choice_id, text_answer, scale_value, answered_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("iiisi", $user_companion_id, $question_id, $choice_id, $text_answer, $scale_value);
    }
    
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Answer updated successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update answer'
        ]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>