<?php
require_once('../../lib/connect.php');
global $conn;

header('Content-Type: application/json');

// Auto-create table if not exists
$conn->query("
    CREATE TABLE IF NOT EXISTS ai_chat_feedback (
        feedback_id  INT AUTO_INCREMENT PRIMARY KEY,
        chat_id      INT NOT NULL,
        feedback     TINYINT(1) NOT NULL COMMENT '1=like, 0=dislike',
        created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_chat (chat_id)
    )
");

$input = json_decode(file_get_contents('php://input'), true);
$chat_id  = isset($input['chat_id'])  ? (int)$input['chat_id']  : 0;
$feedback = isset($input['feedback']) ? (int)$input['feedback'] : -1;

if ($chat_id <= 0 || !in_array($feedback, [0, 1])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

// Verify chat_id exists
$check = $conn->prepare("SELECT chat_id FROM ai_chat_history WHERE chat_id = ? AND role = 'assistant' LIMIT 1");
$check->bind_param('i', $chat_id);
$check->execute();
if ($check->get_result()->num_rows === 0) {
    $check->close();
    echo json_encode(['status' => 'error', 'message' => 'Message not found']);
    exit;
}
$check->close();

$stmt = $conn->prepare("
    INSERT INTO ai_chat_feedback (chat_id, feedback)
    VALUES (?, ?)
    ON DUPLICATE KEY UPDATE feedback = VALUES(feedback)
");
$stmt->bind_param('ii', $chat_id, $feedback);
$stmt->execute();
$stmt->close();

echo json_encode(['status' => 'success', 'chat_id' => $chat_id, 'feedback' => $feedback]);
