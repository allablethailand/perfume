<?php
/**
 * Get User Answers (Guest Mode Supported)
 * ✅ รองรับทั้ง JWT และ Guest Mode
 */

header('Content-Type: application/json');
require_once('../../lib/connect.php');
require_once('../../lib/jwt_helper.php');

global $conn;

// ========== รับพารามิเตอร์ ==========
$user_companion_id = $_GET['user_companion_id'] ?? null;
$ai_code = isset($_GET['ai_code']) ? strtoupper(trim($_GET['ai_code'])) : '';
$lang = $_GET['lang'] ?? 'th';

// ========== ระบุตัวตน: JWT หรือ Guest Mode ==========
$user_id = null;
$is_guest_mode = false;

// ลอง JWT ก่อน
$headers = getallheaders();
$jwt = null;

if (isset($headers['Authorization'])) {
    $jwt = str_replace('Bearer ', '', $headers['Authorization']);
} elseif (isset($headers['X-Auth-Token'])) {
    $jwt = $headers['X-Auth-Token'];
}

// ถ้ามี JWT ให้ verify
if ($jwt) {
    try {
        $decoded = verifyJWT($jwt);
        if ($decoded && isset($decoded->data->user_id)) {
            $user_id = intval($decoded->data->user_id);
        }
    } catch (Exception $e) {
        // JWT ไม่ valid, ลอง guest mode
    }
}

// ถ้าไม่มี JWT = Guest Mode
if (!$user_id) {
    $is_guest_mode = true;
    
    // ✅ ต้องมี user_companion_id หรือ ai_code
    if (!$user_companion_id && !$ai_code) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Please provide user_companion_id or ai_code',
            'require_login' => false
        ]);
        exit;
    }
    
    // ถ้ามี ai_code ให้หา companion
    if ($ai_code && !$user_companion_id) {
        // ลอง session ก่อน
        session_start();
        if (isset($_SESSION['user_companion_id'])) {
            $user_companion_id = $_SESSION['user_companion_id'];
        }
    }
}

// ตรวจสอบว่ามี user_companion_id แล้ว
if (!$user_companion_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'user_companion_id is required',
        'require_login' => false
    ]);
    exit;
}

try {
    // ✅ ถ้าเป็น Login Mode ให้เช็คสิทธิ์
    if (!$is_guest_mode && $user_id) {
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
    }
    // ✅ Guest Mode ไม่ต้องเช็คสิทธิ์ แต่ต้องเช็คว่า companion มีอยู่จริง
    else {
        $stmt = $conn->prepare("
            SELECT user_companion_id 
            FROM user_ai_companions 
            WHERE user_companion_id = ? 
            AND status = '1' 
            AND del = 0
        ");
        $stmt->bind_param("i", $user_companion_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Companion not found'
            ]);
            exit;
        }
        $stmt->close();
    }

    // ดึงข้อมูลคำถามและคำตอบ
    $stmt = $conn->prepare("
        SELECT 
            q.question_id,
            q.question_text_th,
            q.question_text_en,
            q.question_text_cn,
            q.question_text_jp,
            q.question_text_kr,
            q.question_type,
            a.answer_id,
            a.choice_id AS selected_choice_id,
            a.text_answer,
            a.scale_value,
            c.choice_text_th,
            c.choice_text_en,
            c.choice_text_cn,
            c.choice_text_jp,
            c.choice_text_kr
        FROM ai_personality_questions q
        LEFT JOIN user_personality_answers a 
            ON q.question_id = a.question_id 
            AND a.user_companion_id = ?
        LEFT JOIN ai_question_choices c 
            ON a.choice_id = c.choice_id
        WHERE q.del = 0 AND q.status = '1'
        ORDER BY q.question_order
    ");
    
    $stmt->bind_param("i", $user_companion_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $questions = [];
    
    while ($row = $result->fetch_assoc()) {
        $question_id = $row['question_id'];
        
        // ถ้ายังไม่มีคำถามนี้ใน array
        if (!isset($questions[$question_id])) {
            $questions[$question_id] = [
                'question_id' => $row['question_id'],
                'question_text_th' => $row['question_text_th'],
                'question_text_en' => $row['question_text_en'],
                'question_text_cn' => $row['question_text_cn'],
                'question_text_jp' => $row['question_text_jp'],
                'question_text_kr' => $row['question_text_kr'],
                'question_type' => $row['question_type'],
                'answer_id' => $row['answer_id'],
                'selected_choice_id' => $row['selected_choice_id'],
                'text_answer' => $row['text_answer'],
                'scale_value' => $row['scale_value'],
                'choice_text_th' => $row['choice_text_th'],
                'choice_text_en' => $row['choice_text_en'],
                'choice_text_cn' => $row['choice_text_cn'],
                'choice_text_jp' => $row['choice_text_jp'],
                'choice_text_kr' => $row['choice_text_kr'],
                'choices' => []
            ];
        }
    }
    
    // ดึง choices สำหรับแต่ละคำถาม
    foreach ($questions as $question_id => &$question) {
        if ($question['question_type'] === 'choice') {
            $stmt_choices = $conn->prepare("
                SELECT 
                    choice_id,
                    choice_text_th,
                    choice_text_en,
                    choice_text_cn,
                    choice_text_jp,
                    choice_text_kr,
                    choice_order
                FROM ai_question_choices
                WHERE question_id = ?
                AND del = 0
                AND status = '1'
                ORDER BY choice_order
            ");
            
            $stmt_choices->bind_param("i", $question_id);
            $stmt_choices->execute();
            $choices_result = $stmt_choices->get_result();
            
            while ($choice = $choices_result->fetch_assoc()) {
                $question['choices'][] = $choice;
            }
            
            $stmt_choices->close();
        }
    }
    
    $stmt->close();
    
    // Convert to indexed array
    $questions = array_values($questions);
    
    echo json_encode([
        'status' => 'success',
        'guest_mode' => $is_guest_mode,
        'data' => $questions
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>