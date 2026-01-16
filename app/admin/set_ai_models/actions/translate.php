<?php
// เริ่ม Session ก่อนเพื่อเข้าถึง $_SESSION['comp_id']
session_start();

header('Content-Type: application/json');

// อ่านข้อมูล JSON ที่ส่งมาจาก JavaScript
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// 1. ตรวจสอบว่าข้อมูลที่รับมาถูกต้องหรือไม่
if (empty($data) || !isset($data['content'])) {
    echo json_encode(['error' => 'Invalid data received']);
    http_response_code(400); // Bad Request
    exit;
}

// 2. ดึง comp_id จาก Session แทนค่าคงที่
$comp_id = $_SESSION['comp_id'] ?? ''; // ดึง comp_id จาก Session, ถ้าไม่มีให้เป็นค่าว่าง

// 3. ตรวจสอบ comp_id: ถ้าไม่มีค่าหรือเป็นค่าว่าง ให้ส่ง error กลับ
if (empty($comp_id)) {
    echo json_encode(['status' => 'error', 'message' => 'The company ID (comp_id) is not available in the session. Please log in again.']);
    http_response_code(401); // Unauthorized
    exit;
}

function testTranslationAPI($data, $company_id) {
    // ข้อมูลทดสอบ
    $language = $data['language'];
    $translate = $data['translate'];
    // $company = $data['company']; // ไม่ต้องใช้ตัวแปรนี้แล้ว ใช้ $company_id แทน
    $code = $data['code'];
    $content = $data['content']; // content จะเป็น array/object ที่มี subject, description, content อยู่ข้างใน
    
    $test_data = [
        // *** แก้ไข: ใช้ $company_id ที่ดึงจาก Session แล้ว ***
        'company' => (string) $company_id, // comp_id ของคุณ (แปลงเป็น string เพื่อส่งใน JSON)
        'code' => $code,
        'language' => $language,
        'translate' => $translate, 
        'content' => $content
    ];
    
    // เข้ารหัสข้อมูล
    $encoded_data = urlencode(json_encode($test_data, JSON_UNESCAPED_UNICODE));
    
    $url = "https://www.origami.life/api/website/translate.php"; // URL API ของคุณ
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "data=" . $encoded_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    
    if($httpCode === 200) {
        $json_result = json_decode($result, true);
        if($json_result && isset($json_result['status']) && $json_result['status'] === 'success') {
            // ตรวจสอบโครงสร้างข้อมูลที่ถูกต้องก่อนส่งกลับ
            if (isset($json_result['data']['translated'])) {
                return [
                    'status' => 'success',
                    'subject' => $json_result['data']['translated']['subject'] ?? null,
                    'description' => $json_result['data']['translated']['description'] ?? null,
                    'content' => $json_result['data']['translated']['content'] ?? null,
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'API returned success but missing "translated" data structure.'
                ];
            }
        } else {
             // ตรวจสอบว่ามี message หรือไม่ หากไม่มีให้ใช้ข้อความทั่วไป
            $message = $json_result['message'] ?? 'API call failed with an unknown error.';
            return [
                'status' => 'error',
                'message' => $message
            ];
        }
    }
    
    // ถ้า HTTP Code ไม่ใช่ 200 หรือ cURL มีปัญหา
    $error_message = ($result === false) ? curl_error($ch) : $result;
    return [
        'status' => 'error',
        'message' => "HTTP Error Code: $httpCode. API response: " . $error_message
    ];
}

// ข้อมูลที่ได้รับจาก JavaScript
// เรียกใช้ฟังก์ชันทดสอบ โดยส่ง $comp_id ที่ดึงมาจาก Session เข้าไป
$result = testTranslationAPI($data, $comp_id);
echo json_encode($result);
?>