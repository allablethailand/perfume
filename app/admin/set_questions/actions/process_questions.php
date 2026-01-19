<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');
require_once(__DIR__ . '/../../../../lib/base_directory.php');
require_once(__DIR__ . '/../../../../lib/connect.php');

global $base_path;
global $conn;

$response = ['status' => 'error', 'message' => ''];

try {
    if (!isset($_POST['action'])) {
        throw new Exception("No action specified.");
    }

    $action = $_POST['action'];

    // ========================================
    // GET QUESTIONS LIST (DataTables)
    // ========================================
    if ($action == 'getData_questions') {
        $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $searchValue = isset($_POST['search']['value']) ? $conn->real_escape_string($_POST['search']['value']) : '';
        $filterStatus = isset($_POST['filter_status']) ? $_POST['filter_status'] : '';
        
        $whereClause = "del = 0";
        
        if (!empty($searchValue)) {
            $whereClause .= " AND (question_text_th LIKE '%$searchValue%' 
                            OR question_text_en LIKE '%$searchValue%'
                            OR question_text_cn LIKE '%$searchValue%'
                            OR question_text_jp LIKE '%$searchValue%'
                            OR question_text_kr LIKE '%$searchValue%')";
        }
        
        if ($filterStatus !== '') {
            $whereClause .= " AND status = " . intval($filterStatus);
        }
        
        $totalRecordsQuery = "SELECT COUNT(question_id) FROM ai_personality_questions WHERE del = 0";
        $totalRecords = $conn->query($totalRecordsQuery)->fetch_row()[0];
        
        $totalFilteredQuery = "SELECT COUNT(question_id) FROM ai_personality_questions WHERE $whereClause";
        $totalFiltered = $conn->query($totalFilteredQuery)->fetch_row()[0];
        
        $dataQuery = "SELECT * FROM ai_personality_questions 
                      WHERE $whereClause
                      ORDER BY question_order ASC, question_id ASC
                      LIMIT $start, $length";
        
        $dataResult = $conn->query($dataQuery);
        $data = [];
        
        if ($dataResult) {
            while ($row = $dataResult->fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        $response = [
            "draw" => intval($draw),
            "recordsTotal" => intval($totalRecords),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data
        ];
        
    // ========================================
    // GET STATUS COUNTS
    // ========================================
    } elseif ($action == 'getStatusCounts') {
        
        $query = "SELECT 
                    status,
                    COUNT(*) as count
                  FROM ai_personality_questions
                  WHERE del = 0
                  GROUP BY status";
        
        $result = $conn->query($query);
        $counts = [
            'all' => 0,
            'active' => 0,
            'inactive' => 0
        ];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if ($row['status'] == 1) {
                    $counts['active'] = intval($row['count']);
                } else {
                    $counts['inactive'] = intval($row['count']);
                }
                $counts['all'] += intval($row['count']);
            }
        }
        
        $response = [
            'status' => 'success',
            'counts' => $counts
        ];
        
    // ========================================
    // GET QUESTION DETAILS
    // ========================================
    } elseif ($action == 'getQuestionDetails') {
        
        $question_id = $_POST['question_id'] ?? 0;
        
        if (empty($question_id)) {
            throw new Exception("Question ID is missing.");
        }
        
        $stmt = $conn->prepare("SELECT * FROM ai_personality_questions WHERE question_id = ? AND del = 0");
        $stmt->bind_param("i", $question_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Question not found.");
        }
        
        $question = $result->fetch_assoc();
        $stmt->close();
        
        $response = [
            'status' => 'success',
            'question' => $question
        ];
        
    // ========================================
    // ADD NEW QUESTION
    // ========================================
    } elseif ($action == 'addQuestion') {
        
        $question_order = $_POST['question_order'] ?? 0;
        $question_text_th = $_POST['question_text_th'] ?? '';
        $question_text_en = $_POST['question_text_en'] ?? '';
        $question_text_cn = $_POST['question_text_cn'] ?? '';
        $question_text_jp = $_POST['question_text_jp'] ?? '';
        $question_text_kr = $_POST['question_text_kr'] ?? '';
        $question_type = $_POST['question_type'] ?? '';
        $status = isset($_POST['status']) && $_POST['status'] == 'true' ? 1 : 0;
        
        if (empty($question_text_th) || empty($question_type) || empty($question_order)) {
            throw new Exception("กรุณากรอกข้อมูลให้ครบถ้วน (คำถามภาษาไทย, ประเภท, และลำดับ)");
        }
        
        $conn->begin_transaction();
        
        try {
            $stmt = $conn->prepare("INSERT INTO ai_personality_questions 
                (question_order, question_text_th, question_text_en, question_text_cn, question_text_jp, question_text_kr, question_type, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            $stmt->bind_param("issssssi", 
                $question_order, 
                $question_text_th, 
                $question_text_en, 
                $question_text_cn, 
                $question_text_jp, 
                $question_text_kr, 
                $question_type, 
                $status
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to add question: " . $stmt->error);
            }
            
            $new_question_id = $conn->insert_id;
            $stmt->close();
            
            $conn->commit();
            
            $response = [
                'status' => 'success',
                'message' => 'เพิ่มคำถามสำเร็จ!',
                'question_id' => $new_question_id
            ];
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    // ========================================
    // UPDATE QUESTION
    // ========================================
    } elseif ($action == 'updateQuestion') {
        
        $question_id = $_POST['question_id'] ?? 0;
        $question_order = $_POST['question_order'] ?? 0;
        $question_text_th = $_POST['question_text_th'] ?? '';
        $question_text_en = $_POST['question_text_en'] ?? '';
        $question_text_cn = $_POST['question_text_cn'] ?? '';
        $question_text_jp = $_POST['question_text_jp'] ?? '';
        $question_text_kr = $_POST['question_text_kr'] ?? '';
        $question_type = $_POST['question_type'] ?? '';
        $status = isset($_POST['status']) && $_POST['status'] == 'true' ? 1 : 0;
        
        if (empty($question_id) || empty($question_text_th) || empty($question_type) || empty($question_order)) {
            throw new Exception("กรุณากรอกข้อมูลให้ครบถ้วน");
        }
        
        $conn->begin_transaction();
        
        try {
            $stmt = $conn->prepare("UPDATE ai_personality_questions SET 
                question_order = ?,
                question_text_th = ?,
                question_text_en = ?,
                question_text_cn = ?,
                question_text_jp = ?,
                question_text_kr = ?,
                question_type = ?,
                status = ?,
                updated_at = NOW()
                WHERE question_id = ? AND del = 0");
            
            $stmt->bind_param("issssssi", 
                $question_order,
                $question_text_th,
                $question_text_en,
                $question_text_cn,
                $question_text_jp,
                $question_text_kr,
                $question_type,
                $status,
                $question_id
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update question: " . $stmt->error);
            }
            
            $stmt->close();
            $conn->commit();
            
            $response = [
                'status' => 'success',
                'message' => 'แก้ไขคำถามสำเร็จ!'
            ];
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    // ========================================
    // TOGGLE QUESTION STATUS
    // ========================================
    } elseif ($action == 'toggleStatus') {
        
        $question_id = $_POST['question_id'] ?? 0;
        
        if (empty($question_id)) {
            throw new Exception("Question ID is missing.");
        }
        
        $conn->begin_transaction();
        
        try {
            // Get current status
            $check_stmt = $conn->prepare("SELECT status FROM ai_personality_questions WHERE question_id = ? AND del = 0");
            $check_stmt->bind_param("i", $question_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                throw new Exception("Question not found.");
            }
            
            $current = $check_result->fetch_assoc();
            $new_status = $current['status'] == 1 ? 0 : 1;
            $check_stmt->close();
            
            // Update status
            $stmt = $conn->prepare("UPDATE ai_personality_questions SET status = ?, updated_at = NOW() WHERE question_id = ?");
            $stmt->bind_param("ii", $new_status, $question_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to toggle status: " . $stmt->error);
            }
            
            $stmt->close();
            $conn->commit();
            
            $response = [
                'status' => 'success',
                'message' => 'เปลี่ยนสถานะสำเร็จ!',
                'new_status' => $new_status
            ];
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    // ========================================
    // DELETE QUESTION (Soft Delete)
    // ========================================
    } elseif ($action == 'deleteQuestion') {
        
        $question_id = $_POST['question_id'] ?? 0;
        
        if (empty($question_id)) {
            throw new Exception("Question ID is missing.");
        }
        
        $conn->begin_transaction();
        
        try {
            $stmt = $conn->prepare("UPDATE ai_personality_questions SET del = 1, updated_at = NOW() WHERE question_id = ?");
            $stmt->bind_param("i", $question_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to delete question: " . $stmt->error);
            }
            
            $stmt->close();
            $conn->commit();
            
            $response = [
                'status' => 'success',
                'message' => 'ลบคำถามสำเร็จ!'
            ];
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    // ========================================
    // GET CHOICES FOR A QUESTION
    // ========================================
    } elseif ($action == 'getChoices') {
        
        $question_id = $_POST['question_id'] ?? 0;
        
        if (empty($question_id)) {
            throw new Exception("Question ID is missing.");
        }
        
        $stmt = $conn->prepare("SELECT * FROM ai_question_choices 
                               WHERE question_id = ? AND del = 0 
                               ORDER BY choice_order ASC, choice_id ASC");
        $stmt->bind_param("i", $question_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $choices = [];
        while ($row = $result->fetch_assoc()) {
            $choices[] = $row;
        }
        
        $stmt->close();
        
        $response = [
            'status' => 'success',
            'choices' => $choices
        ];
        
    // ========================================
    // GET ALL CHOICES COUNTS
    // ========================================
    } elseif ($action == 'getAllChoicesCounts') {
        
        $query = "SELECT question_id, COUNT(*) as count 
                  FROM ai_question_choices 
                  WHERE del = 0 
                  GROUP BY question_id";
        
        $result = $conn->query($query);
        $counts = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $counts[] = [
                    'question_id' => $row['question_id'],
                    'count' => intval($row['count'])
                ];
            }
        }
        
        $response = [
            'status' => 'success',
            'counts' => $counts
        ];
        
    // ========================================
    // GET CHOICE DETAILS
    // ========================================
    } elseif ($action == 'getChoiceDetails') {
        
        $choice_id = $_POST['choice_id'] ?? 0;
        
        if (empty($choice_id)) {
            throw new Exception("Choice ID is missing.");
        }
        
        $stmt = $conn->prepare("SELECT * FROM ai_question_choices WHERE choice_id = ? AND del = 0");
        $stmt->bind_param("i", $choice_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Choice not found.");
        }
        
        $choice = $result->fetch_assoc();
        $stmt->close();
        
        $response = [
            'status' => 'success',
            'choice' => $choice
        ];
        
    // ========================================
    // ADD NEW CHOICE
    // ========================================
    } elseif ($action == 'addChoice') {
        
        $choice_question_id = $_POST['choice_question_id'] ?? 0;
        $choice_order = $_POST['choice_order'] ?? 0;
        $choice_text_th = $_POST['choice_text_th'] ?? '';
        $choice_text_en = $_POST['choice_text_en'] ?? '';
        $choice_text_cn = $_POST['choice_text_cn'] ?? '';
        $choice_text_jp = $_POST['choice_text_jp'] ?? '';
        $choice_text_kr = $_POST['choice_text_kr'] ?? '';
        $choice_status = isset($_POST['choice_status']) && $_POST['choice_status'] == 'true' ? 1 : 0;
        
        if (empty($choice_question_id) || empty($choice_text_th) || empty($choice_order)) {
            throw new Exception("กรุณากรอกข้อมูลให้ครบถ้วน (คำถาม, ข้อความภาษาไทย, และลำดับ)");
        }
        
        $conn->begin_transaction();
        
        try {
            $stmt = $conn->prepare("INSERT INTO ai_question_choices 
                (question_id, choice_order, choice_text_th, choice_text_en, choice_text_cn, choice_text_jp, choice_text_kr, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            $stmt->bind_param("iisssssi", 
                $choice_question_id,
                $choice_order,
                $choice_text_th,
                $choice_text_en,
                $choice_text_cn,
                $choice_text_jp,
                $choice_text_kr,
                $choice_status
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to add choice: " . $stmt->error);
            }
            
            $new_choice_id = $conn->insert_id;
            $stmt->close();
            
            $conn->commit();
            
            $response = [
                'status' => 'success',
                'message' => 'เพิ่มตัวเลือกสำเร็จ!',
                'choice_id' => $new_choice_id
            ];
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    // ========================================
    // UPDATE CHOICE
    // ========================================
    } elseif ($action == 'updateChoice') {
        
        $choice_id = $_POST['choice_id'] ?? 0;
        $choice_order = $_POST['choice_order'] ?? 0;
        $choice_text_th = $_POST['choice_text_th'] ?? '';
        $choice_text_en = $_POST['choice_text_en'] ?? '';
        $choice_text_cn = $_POST['choice_text_cn'] ?? '';
        $choice_text_jp = $_POST['choice_text_jp'] ?? '';
        $choice_text_kr = $_POST['choice_text_kr'] ?? '';
        $choice_status = isset($_POST['choice_status']) && $_POST['choice_status'] == 'true' ? 1 : 0;
        
        if (empty($choice_id) || empty($choice_text_th) || empty($choice_order)) {
            throw new Exception("กรุณากรอกข้อมูลให้ครบถ้วน");
        }
        
        $conn->begin_transaction();
        
        try {
            $stmt = $conn->prepare("UPDATE ai_question_choices SET 
                choice_order = ?,
                choice_text_th = ?,
                choice_text_en = ?,
                choice_text_cn = ?,
                choice_text_jp = ?,
                choice_text_kr = ?,
                status = ?
                WHERE choice_id = ? AND del = 0");
            
            $stmt->bind_param("isssssii", 
                $choice_order,
                $choice_text_th,
                $choice_text_en,
                $choice_text_cn,
                $choice_text_jp,
                $choice_text_kr,
                $choice_status,
                $choice_id
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update choice: " . $stmt->error);
            }
            
            $stmt->close();
            $conn->commit();
            
            $response = [
                'status' => 'success',
                'message' => 'แก้ไขตัวเลือกสำเร็จ!'
            ];
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    // ========================================
    // DELETE CHOICE (Soft Delete)
    // ========================================
    } elseif ($action == 'deleteChoice') {
        
        $choice_id = $_POST['choice_id'] ?? 0;
        
        if (empty($choice_id)) {
            throw new Exception("Choice ID is missing.");
        }
        
        $conn->begin_transaction();
        
        try {
            $stmt = $conn->prepare("UPDATE ai_question_choices SET del = 1 WHERE choice_id = ?");
            $stmt->bind_param("i", $choice_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to delete choice: " . $stmt->error);
            }
            
            $stmt->close();
            $conn->commit();
            
            $response = [
                'status' => 'success',
                'message' => 'ลบตัวเลือกสำเร็จ!'
            ];
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    } else {
        throw new Exception("Invalid action: $action");
    }
    
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
    error_log("Error in process_questions.php: " . $e->getMessage());
}

$conn->close();
echo json_encode($response);
?>