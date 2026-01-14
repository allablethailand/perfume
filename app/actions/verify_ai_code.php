<?php
header('Content-Type: application/json');
require_once('../../lib/connect.php');
global $conn;

$response = ['status' => 'error', 'message' => ''];

try {
    if (!isset($_POST['ai_code'])) {
        throw new Exception("AI Code is required");
    }

    $ai_code = strtoupper(trim($_POST['ai_code']));

    // ค้นหา AI Companion จากรหัส
    $stmt = $conn->prepare("
        SELECT 
            ai.*,
            p.name_th as product_name_th,
            p.name_en as product_name_en
        FROM ai_companions ai
        LEFT JOIN products p ON ai.product_id = p.product_id
        WHERE ai.ai_code = ? 
        AND ai.del = 0 
        AND ai.status = 1
    ");
    
    $stmt->bind_param("s", $ai_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("AI Code not found or inactive");
    }

    $ai_data = $result->fetch_assoc();
    $stmt->close();

    $response = [
        'status' => 'success',
        'message' => 'AI Companion found',
        'data' => $ai_data
    ];

} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
    error_log("Error in verify_ai_code.php: " . $e->getMessage());
}

$conn->close();
echo json_encode($response);
?>