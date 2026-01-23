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

    // ค้นหา AI Companion จากรหัส (โครงสร้างใหม่)
    $stmt = $conn->prepare("
        SELECT 
            ai.*,
            pg.name_th AS product_name_th,
            pg.name_en AS product_name_en
        FROM ai_companions ai
        INNER JOIN product_items pi ON ai.item_id = pi.item_id
        INNER JOIN product_groups pg ON pi.group_id = pg.group_id
        WHERE ai.ai_code = ?
        AND ai.del = 0
        AND ai.status = 1
        AND pi.del = 0
        AND pg.del = 0
    ");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

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
