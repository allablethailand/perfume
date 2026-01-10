<?php

header('Content-Type: application/json');

require_once(__DIR__ . '/../../../lib/connect.php');
require_once(__DIR__ . '/../../../lib/base_directory.php');
require_once(__DIR__ . '/../../../lib/permissions.php');

global $base_path;
global $base_path_admin;
global $isFile; // ดึงตัวแปร isFile ที่ประกาศจาก base_directory.php

// ----------------------------------------------------------------------
// *** ส่วนที่ถูกแก้ไข: ลบเงื่อนไขที่ต้องมี 'email' และลบโค้ดที่ดึง user_id จาก email ออก ***
// ----------------------------------------------------------------------
// ถ้าต้องการให้ระบบยังคงดึง user_id จาก email *ถ้ามี* user_id แล้ว ให้เก็บโค้ดนี้ไว้
// แต่เปลี่ยนเงื่อนไข
/*
if (isset($_SESSION['oid']) && !isset($_SESSION['user_id']) && isset($_SESSION['email'])) { // เพิ่ม isset($_SESSION['email']) กลับมาถ้ายังต้องการใช้ email ดึง user_id
    function getUserFromEmail($conn, $email) {
        // ... (โค้ดดั้งเดิม)
    }
    
    $userId = getUserFromEmail($conn, $_SESSION['email']);
    
    if ($userId) {
        $_SESSION['user_id'] = $userId;
    }
}
*/
// แต่ถ้าต้องการให้มันข้ามการตรวจสอบ user_id ด้วย email ไปเลย ก็ให้ข้ามส่วนนี้ไป

// ----------------------------------------------------------------------
// *** ส่วนที่ 2: การตรวจสอบสิทธิ์การเข้าถึงเมนู (ไม่แก้ไข) ***
// *** checkPermissions จะถูกเรียกและจะตัดสินใจเองว่าควรให้สิทธิ์อะไร ***
// ----------------------------------------------------------------------
$arrPermiss = checkPermissions($_SESSION);

// ถ้าไม่มี email/user_id และ checkPermissions ไม่ได้ส่งค่ากลับมา
// ค่า $allowedMenus จะเป็น array ว่าง ทำให้ไม่เห็นเมนู
// ถ้าต้องการให้เห็นเมนูทั้งหมดในกรณีที่ไม่มีสิทธิ์ (ไม่มี user_id หรือ email)
// คุณอาจจะต้องแก้ไขฟังก์ชัน checkPermissions ให้ return สิทธิ์ทั้งหมด
// หรือกำหนดค่าเริ่มต้นให้เป็นสิทธิ์ทั้งหมดเมื่อ checkPermissions ล้มเหลว

$allowedMenus = (isset($arrPermiss) && is_array($arrPermiss) && isset($arrPermiss['menus_id'])) 
    ? explode(',', $arrPermiss['menus_id']) 
    : []; // ถ้า checkPermissions ส่งค่าว่าง/ไม่สมบูรณ์ จะเป็น []

$sidebarItems = [];

// ----------------------------------------------------------------------
// *** ส่วนที่ 3: การสร้างรายการเมนูหลัก (ไม่แก้ไข) ***
// ----------------------------------------------------------------------
$sql_parent = "SELECT ml_menus.* FROM ml_menus WHERE ml_menus.del = ? AND parent_id = 0 ORDER BY ml_menus.menu_order ASC"; // เพิ่ม ORDER BY เพื่อให้ลำดับถูกต้อง
$stmt_parent = $conn->prepare($sql_parent);
if ($stmt_parent === false) {
    echo json_encode(["status" => "error", "message" => "Database error: Unable to prepare statement"]);
    exit();
}

$del = 0;
$stmt_parent->bind_param("i", $del);
$stmt_parent->execute();
$result = $stmt_parent->get_result();
$arrayMainMenu = $result->fetch_all(MYSQLI_ASSOC);

foreach ($arrayMainMenu as $row) {
    // โค้ดนี้จะอนุญาตให้แสดงเฉพาะเมนูที่มีสิทธิ์อยู่ใน $allowedMenus เท่านั้น
    if (empty($allowedMenus) || in_array($row['menu_id'], $allowedMenus)) { // *** แก้ไขตรงนี้ให้เห็นเมนูทั้งหมดถ้าไม่มีการกำหนดสิทธิ์ ($allowedMenus ว่าง) ***
        if ($row['parent_id'] == 0) {
            $sidebarItems[] = [
                'id' => $row['menu_id'],
                'icon' => $row['menu_icon'],
                'label' => $row['menu_label'],
                'link' => ($row['menu_link']) ? $base_path_admin . str_replace('.php', '', $row['menu_link']) . $isFile : '#',
                'order' => $row['menu_order'],
                'subItems' => [],
            ];
        }
    }
}

// ----------------------------------------------------------------------
// *** ส่วนที่ 4: เมนูย่อย (ไม่แก้ไข) ***
// ----------------------------------------------------------------------
// Childen menus
$sql_childen = "SELECT ml_menus.* FROM ml_menus WHERE ml_menus.del = ? AND parent_id > 0 ORDER BY ml_menus.menu_order ASC"; // เพิ่ม ORDER BY เพื่อให้ลำดับถูกต้อง
$stmt_childen = $conn->prepare($sql_childen);
if ($stmt_childen === false) {
    echo json_encode(["status" => "error", "message" => "Database error: Unable to prepare statement"]);
    exit();
}

$stmt_childen->bind_param("i", $del);
$stmt_childen->execute();
$result = $stmt_childen->get_result();
$arraySubMenu = $result->fetch_all(MYSQLI_ASSOC);

foreach ($arraySubMenu as $childen) {
    // ต้องตรวจสอบสิทธิ์สำหรับเมนูย่อยด้วย
    if (empty($allowedMenus) || in_array($childen['menu_id'], $allowedMenus)) { // *** เพิ่มการตรวจสอบสิทธิ์สำหรับเมนูย่อย ***
        foreach ($sidebarItems as &$parentItem) {
            if ($parentItem['id'] == $childen['parent_id']) {
                $parentItem['subItems'][] = [
                    'id' => $childen['menu_id'],
                    'icon' => $childen['menu_icon'],
                    'label' => $childen['menu_label'],
                    'link' => ($childen['menu_link']) ? $base_path_admin . str_replace('.php', '', $childen['menu_link']) . $isFile : '#',
                    'order' => $childen['menu_order'],
                    'parentId' => $childen['parent_id'],
                ];
                break;
            }
        }
    }
}
unset($parentItem); // ยกเลิกการอ้างอิงเพื่อป้องกันผลข้างเคียง

echo json_encode([
    'sidebarItems' => $sidebarItems
]);
?>