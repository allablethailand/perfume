<?php
header('Content-Type: application/json');
require_once('../../lib/connect.php');
require_once('../../lib/jwt_helper.php');
global $conn;

$response = ['status' => 'error', 'message' => ''];

try {
    error_log("=== SAVE PERSONALITY ANSWERS DEBUG ===");
    error_log("POST data: " . json_encode($_POST));
    error_log("Headers: " . json_encode(getallheaders()));
    
    // รับข้อมูล
    if (!isset($_POST['answers'])) {
        throw new Exception("Missing answers field");
    }

    $answers = json_decode($_POST['answers'], true);
    
    if (!is_array($answers) || count($answers) === 0) {
        error_log("ERROR: Invalid answers format");
        throw new Exception("Invalid answers format");
    }

    $user_companion_id = null;
    
    // ⚠️ FIX: รองรับ 2 กรณี
    // 1. Frontend ส่ง user_companion_id มาตรงๆ (กรณีปกติ)
    if (isset($_POST['user_companion_id']) && !empty($_POST['user_companion_id'])) {
        $user_companion_id = intval($_POST['user_companion_id']);
        error_log("Case 1: Received user_companion_id: " . $user_companion_id);
    }
    // 2. Frontend ไม่ส่ง user_companion_id แต่มี JWT หรือ user_id (fallback)
    else {
        error_log("Case 2: No user_companion_id, trying to find from JWT or session");
        
        $user_id = null;
        
        // พยายามดึง user_id จาก JWT
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $jwt = str_replace('Bearer ', '', $headers['Authorization']);
            try {
                $payload = validateJWT($jwt);
                if ($payload && isset($payload->user_id)) {
                    $user_id = $payload->user_id;
                    error_log("Got user_id from JWT: " . $user_id);
                }
            } catch (Exception $e) {
                error_log("JWT validation failed: " . $e->getMessage());
            }
        }
        
        // ถ้ายังไม่ได้ user_id ลองดูจาก session
        if (!$user_id) {
            session_start();
            if (isset($_SESSION['user_id'])) {
                $user_id = $_SESSION['user_id'];
                error_log("Got user_id from session: " . $user_id);
            }
        }
        
        // ถ้ายังไม่ได้ ลองดูจาก POST
        if (!$user_id && isset($_POST['user_id'])) {
            $user_id = intval($_POST['user_id']);
            error_log("Got user_id from POST: " . $user_id);
        }
        
        if (!$user_id) {
            throw new Exception("Cannot determine user identity");
        }
        
        // หา companion ที่ยัง setup ไม่เสร็จของ user คนนี้
        $stmt = $conn->prepare("
            SELECT user_companion_id 
            FROM user_ai_companions 
            WHERE user_id = ? AND setup_completed = 0 AND del = 0
            ORDER BY first_scan_at DESC
            LIMIT 1
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $companion = $result->fetch_assoc();
        $stmt->close();
        
        if (!$companion) {
            error_log("ERROR: No incomplete setup companion found for user_id: " . $user_id);
            throw new Exception("No companion setup in progress");
        }
        
        $user_companion_id = $companion['user_companion_id'];
        error_log("Found user_companion_id from user_id: " . $user_companion_id);
    }
    
    // ตรวจสอบว่า companion มีอยู่จริง
    $stmt = $conn->prepare("
        SELECT user_id 
        FROM user_ai_companions 
        WHERE user_companion_id = ? AND del = 0
    ");
    $stmt->bind_param("i", $user_companion_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $companion = $result->fetch_assoc();
    $stmt->close();
    
    if (!$companion) {
        error_log("ERROR: Companion not found for ID: " . $user_companion_id);
        throw new Exception("Companion not found");
    }
    
    $user_id = $companion['user_id'];
    error_log("Confirmed user_id: " . $user_id);

    $conn->begin_transaction();

    try {
        // ลบคำตอบเดิม (ถ้ามี)
        error_log("Deleting old answers for companion: " . $user_companion_id);
        $delete_stmt = $conn->prepare("DELETE FROM user_personality_answers WHERE user_companion_id = ?");
        $delete_stmt->bind_param("i", $user_companion_id);
        $delete_stmt->execute();
        $deleted_count = $delete_stmt->affected_rows;
        $delete_stmt->close();
        error_log("Deleted " . $deleted_count . " old answers");

        // บันทึกคำตอบใหม่
        error_log("Inserting " . count($answers) . " new answers");
        $insert_count = 0;
        
        foreach ($answers as $answer) {
            $question_id = $answer['question_id'];
            $choice_id = isset($answer['choice_id']) ? $answer['choice_id'] : null;
            $text_answer = isset($answer['text_answer']) ? $answer['text_answer'] : null;
            $scale_value = isset($answer['scale_value']) ? $answer['scale_value'] : null;

            error_log("Inserting answer for question " . $question_id);

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
            $insert_count++;
        }
        
        error_log("Successfully inserted " . $insert_count . " answers");

        // อัปเดตสถานะว่าตั้งค่าเสร็จแล้ว
        error_log("Updating setup_completed for companion: " . $user_companion_id);
        $update_stmt = $conn->prepare("
            UPDATE user_ai_companions 
            SET setup_completed = 1, setup_completed_at = NOW() 
            WHERE user_companion_id = ?
        ");
        $update_stmt->bind_param("i", $user_companion_id);
        $update_stmt->execute();
        $update_stmt->close();
        error_log("Setup completed updated");

        $conn->commit();
        error_log("Transaction committed successfully");

        $response = [
            'status' => 'success',
            'message' => 'Answers saved successfully',
            'inserted_count' => $insert_count,
            'user_companion_id' => $user_companion_id
        ];

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Transaction rollback: " . $e->getMessage());
        throw $e;
    }

} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
    error_log("ERROR in save_personality_answers.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
}

$conn->close();
error_log("Response: " . json_encode($response));
echo json_encode($response);
?>