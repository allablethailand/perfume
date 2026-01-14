<?php
header('Content-Type: application/json');
require_once('../../lib/connect.php');
require_once('../../lib/jwt_helper.php');

global $conn;

// ✅ ใช้ requireAuth()
$user_id = requireAuth();

try {
    // ดึงที่อยู่ทั้งหมดของ user
    $sql = "SELECT * FROM user_addresses 
            WHERE user_id = ? AND del = 0 
            ORDER BY is_default DESC, date_created DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $addresses = [];
    while ($row = $result->fetch_assoc()) {
        $addresses[] = $row;
    }

    $stmt->close();
    $conn->close();

    echo json_encode([
        'status' => 'success',
        'data' => $addresses
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch addresses: ' . $e->getMessage()
    ]);
}
?>