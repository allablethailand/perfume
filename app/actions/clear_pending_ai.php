<?php
// ไฟล์สำหรับลบ pending_ai_code ออกจาก PHP session
// ใช้เมื่อ OTP verify สำเร็จแล้ว

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ลบ pending AI data
if (isset($_SESSION['pending_ai_code'])) {
    unset($_SESSION['pending_ai_code']);
}

if (isset($_SESSION['pending_ai_lang'])) {
    unset($_SESSION['pending_ai_lang']);
}

echo json_encode([
    'status' => 'success',
    'message' => 'Pending AI data cleared'
]);
?>