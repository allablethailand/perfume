<?php
header('Content-Type: application/json');
require_once('../../lib/connect.php');
require_once('../../lib/jwt_helper.php');

global $conn;

// ตรวจสอบ Authorization header
$headers = getallheaders();
$jwt = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;

if (!$jwt) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// ตรวจสอบ JWT
$decoded = verifyJWT($jwt);
if (!$decoded) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid token']);
    exit;
}

$user_id = requireAuth();

// รับข้อมูล
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$transfer_amount = isset($_POST['transfer_amount']) ? floatval($_POST['transfer_amount']) : 0;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

if ($order_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid order ID']);
    exit;
}

// ตรวจสอบว่า order เป็นของ user นี้หรือไม่
$check_sql = "SELECT order_id, total_amount FROM orders WHERE order_id = ? AND user_id = ? AND del = 0";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $order_id, $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Order not found']);
    exit;
}

$order = $check_result->fetch_assoc();

// ตรวจสอบไฟล์
if (!isset($_FILES['slip_file']) || $_FILES['slip_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'Please select a payment slip image']);
    exit;
}

$file = $_FILES['slip_file'];

// ตรวจสอบขนาดไฟล์ (5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['status' => 'error', 'message' => 'File size must be less than 5MB']);
    exit;
}

// ตรวจสอบประเภทไฟล์
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_types)) {
    echo json_encode(['status' => 'error', 'message' => 'Please upload an image file (JPG, PNG, GIF)']);
    exit;
}

try {
    // เริ่ม transaction
    $conn->begin_transaction();
    
    // สร้างโฟลเดอร์สำหรับเก็บไฟล์
    $upload_dir = '../../public/uploads/payment_slips/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // สร้างชื่อไฟล์ใหม่
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = 'slip_' . $order_id . '_' . time() . '.' . $file_extension;
    $file_path = $upload_dir . $new_filename;

    // อัพโหลดไฟล์
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new Exception('Failed to upload file');
    }

    // บันทึกข้อมูลลงฐานข้อมูล - ใช้ NOW() สำหรับ transfer_date และ date_uploaded
    $insert_sql = "INSERT INTO payment_slips (
                    order_id, user_id, file_name, file_path, file_size,
                    transfer_date, transfer_amount, notes, status, date_uploaded
                   ) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, 'pending', NOW())";
    
    $api_path = 'public/uploads/payment_slips/' . $new_filename;
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("iissids",
        $order_id,
        $user_id,
        $new_filename,
        $api_path,
        $file['size'],
        $transfer_amount,
        $notes
    );

    if (!$insert_stmt->execute()) {
        throw new Exception('Failed to save payment slip information');
    }

    // อัพเดทสถานะ order เป็น paid และ processing พร้อมกัน
    $update_sql = "UPDATE orders 
                   SET payment_status = 'paid', 
                       order_status = 'processing',
                       date_updated = NOW()
                   WHERE order_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $order_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception('Failed to update order status');
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Payment slip uploaded successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    
    // ลบไฟล์ถ้าอัพโหลดสำเร็จแต่บันทึกฐานข้อมูลไม่สำเร็จ
    if (isset($file_path) && file_exists($file_path)) {
        unlink($file_path);
    }

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>