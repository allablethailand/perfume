<?php
header('Content-Type: application/json');
require '../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

require_once(__DIR__ . '/../../lib/connect.php');

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

global $conn;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    exit();
}

// ตรวจสอบ JWT
$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (empty($authHeader)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No authorization token provided'
    ]);
    exit();
}

$jwt = str_replace('Bearer ', '', $authHeader);

try {
    $secret_key = $_ENV['JWT_SECRET_KEY'];
    $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
    $user_id = $decoded->data->user_id;

    // รับข้อมูลจากฟอร์ม
    $address_id = $_POST['address_id'] ?? null;
    $address_label = $_POST['address_label'] ?? '';
    $recipient_name = $_POST['recipient_name'] ?? '';
    $recipient_phone = $_POST['recipient_phone'] ?? '';
    $address_line1 = $_POST['address_line1'] ?? '';
    $address_line2 = $_POST['address_line2'] ?? '';
    $subdistrict = $_POST['subdistrict'] ?? '';
    $district = $_POST['district'] ?? '';
    $province = $_POST['province'] ?? '';
    $country = $_POST['country'] ?? 'Thailand';
    $postal_code = $_POST['postal_code'] ?? '';
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    $conn->begin_transaction();

    try {
        // ถ้าเป็น default ให้ unset default อื่นๆ
        if ($is_default == 1) {
            $sql_unset = "UPDATE user_addresses SET is_default = 0 WHERE user_id = ?";
            $stmt_unset = $conn->prepare($sql_unset);
            $stmt_unset->bind_param("i", $user_id);
            $stmt_unset->execute();
            $stmt_unset->close();
        }

        if ($address_id) {
            // Update existing address
            $sql = "UPDATE user_addresses SET 
                    address_label = ?,
                    recipient_name = ?,
                    recipient_phone = ?,
                    address_line1 = ?,
                    address_line2 = ?,
                    subdistrict = ?,
                    district = ?,
                    province = ?,
                    country = ?,
                    postal_code = ?,
                    is_default = ?,
                    date_updated = NOW()
                    WHERE address_id = ? AND user_id = ?";
            
            $stmt = $conn->prepare($sql);
            // แก้ไข: เพิ่ม i อีก 1 ตัวสำหรับ user_id (iiii = 4 integers)
            $stmt->bind_param("sssssssssiiii", 
                $address_label,      // s
                $recipient_name,     // s
                $recipient_phone,    // s
                $address_line1,      // s
                $address_line2,      // s
                $subdistrict,        // s
                $district,           // s
                $province,           // s
                $country,            // s
                $postal_code,        // s
                $is_default,         // i
                $address_id,         // i
                $user_id             // i
            );
        } else {
            // Insert new address
            $sql = "INSERT INTO user_addresses (
                    user_id,
                    address_label,
                    recipient_name,
                    recipient_phone,
                    address_line1,
                    address_line2,
                    subdistrict,
                    district,
                    province,
                    country,
                    postal_code,
                    is_default,
                    date_created
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issssssssssi",
                $user_id,            // i
                $address_label,      // s
                $recipient_name,     // s
                $recipient_phone,    // s
                $address_line1,      // s
                $address_line2,      // s
                $subdistrict,        // s
                $district,           // s
                $province,           // s
                $country,            // s
                $postal_code,        // s
                $is_default          // i
            );
        }

        if ($stmt->execute()) {
            $conn->commit();
            echo json_encode([
                'status' => 'success',
                'message' => 'Address saved successfully'
            ]);
        } else {
            throw new Exception($stmt->error);
        }

        $stmt->close();

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to save address: ' . $e->getMessage()
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid token: ' . $e->getMessage()
    ]);
}

$conn->close();
?>