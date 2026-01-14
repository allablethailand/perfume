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
    if (!isset($_POST['user_companion_id']) || !isset($_POST['answers'])) {
        throw new Exception("Missing required fields");
    }

    $user_companion_id = intval($_POST['user_companion_id']);
    $answers = json_decode($_POST['answers'], true);

    if (!is_array($answers) || count($answers) === 0) {
        throw new Exception("Invalid answers format");
    }

    // ตรวจสอบว่า user_companion_id นี้เป็นของ user คนนี้จริงหรือไม่
    $check_owner = $conn->prepare("
        SELECT user_companion_id 
        FROM user_ai_companions 
        WHERE user_companion_id = ? AND user_id = ? AND del = 0
    ");
    $check_owner->bind_param("ii", $user_companion_id, $user_id);
    $check_owner->execute();
    $check_owner->store_result();

    if ($check_owner->num_rows === 0) {
        throw new Exception("Unauthorized access to this companion");
    }
    $check_owner->close();

    $conn->begin_transaction();

    try {
        // ลบคำตอบเดิม (ถ้ามี)
        $delete_stmt = $conn->prepare("DELETE FROM user_personality_answers WHERE user_companion_id = ?");
        $delete_stmt->bind_param("i", $user_companion_id);
        $delete_stmt->execute();
        $delete_stmt->close();

        // บันทึกคำตอบใหม่
        foreach ($answers as $answer) {
            $question_id = $answer['question_id'];
            $choice_id = isset($answer['choice_id']) ? $answer['choice_id'] : null;
            $text_answer = isset($answer['text_answer']) ? $answer['text_answer'] : null;
            $scale_value = isset($answer['scale_value']) ? $answer['scale_value'] : null;

            $stmt = $conn->prepare("
                INSERT INTO user_personality_answers 
                (user_companion_id, question_id, choice_id, text_answer, scale_value) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param("iiisi", 
                $user_companion_id, 
                $question_id, 
                $choice_id, 
                $text_answer, 
                $scale_value
            );
            
            $stmt->execute();
            $stmt->close();
        }

        // อัปเดตสถานะว่าตั้งค่าเสร็จแล้ว
        $update_stmt = $conn->prepare("
            UPDATE user_ai_companions 
            SET setup_completed = 1, setup_completed_at = NOW() 
            WHERE user_companion_id = ?
        ");
        $update_stmt->bind_param("i", $user_companion_id);
        $update_stmt->execute();
        $update_stmt->close();

        $conn->commit();

        $response = [
            'status' => 'success',
            'message' => 'Answers saved successfully'
        ];

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
    error_log("Error in save_personality_answers.php: " . $e->getMessage());
}

$conn->close();
echo json_encode($response);
?>