<?php
header('Content-Type: application/json');
require_once('../../lib/connect.php');
global $conn;

$response = ['status' => 'error', 'message' => ''];

try {
    $lang = isset($_GET['lang']) ? $_GET['lang'] : 'th';
    
    // ดึงคำถามทั้งหมด
    $stmt = $conn->prepare("
        SELECT * FROM ai_personality_questions 
        WHERE del = 0 AND status = 1 
        ORDER BY question_order ASC
    ");
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $questions = [];
    
    while ($row = $result->fetch_assoc()) {
        $question_id = $row['question_id'];
        
        // ดึงตัวเลือก (ถ้าเป็นแบบ choice)
        if ($row['question_type'] === 'choice') {
            $stmt_choices = $conn->prepare("
                SELECT * FROM ai_question_choices 
                WHERE question_id = ? AND del = 0 AND status = 1 
                ORDER BY choice_order ASC
            ");
            $stmt_choices->bind_param("i", $question_id);
            $stmt_choices->execute();
            $choices_result = $stmt_choices->get_result();
            
            $choices = [];
            while ($choice = $choices_result->fetch_assoc()) {
                $choices[] = $choice;
            }
            $stmt_choices->close();
            
            $row['choices'] = $choices;
        }
        
        $questions[] = $row;
    }
    
    $stmt->close();

    if (count($questions) === 0) {
        throw new Exception("No questions found");
    }

    $response = [
        'status' => 'success',
        'data' => $questions
    ];

} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
    error_log("Error in get_personality_questions.php: " . $e->getMessage());
}

$conn->close();
echo json_encode($response);
?>