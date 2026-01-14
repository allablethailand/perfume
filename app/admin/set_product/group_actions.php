<?php
// group_actions.php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');

// --- เริ่มต้นการกำหนดค่า URL และ Path ภายใน group_actions.php ---

// ตรวจ protocol แบบปลอดภัย
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$port = isset($_SERVER['SERVER_PORT']) && (($scheme === 'http' && $_SERVER['SERVER_PORT'] != 80) || ($scheme === 'https' && $_SERVER['SERVER_PORT'] != 443)) ? ':' . $_SERVER['SERVER_PORT'] : '';

// คำนวณ ROOT_URL (URL หลักของเว็บไซต์)
$script_name = $_SERVER['SCRIPT_NAME'];
$base_uri = str_replace(basename($script_name), '', $script_name);

$root_app_uri = '/';
if (strpos($base_uri, '/origami_website/perfume/') !== false) {
    $root_app_uri = '/origami_website/perfume/';
}

$root_url = $scheme . '://' . $host . $port . $root_app_uri;

// กำหนด ROOT_DIR (Path ของ root project บน Server)
// ถ้า group_actions.php อยู่ที่ /var/www/html/origami_website/perfume/admin/set_product/group_actions.php
// เราต้องการ ROOT_DIR เป็น /var/www/html/origami_website/perfume/
define('ROOT_DIR', __DIR__ . '/../../..'); // Path จริงของ 'perfume' folder บน Server
define('PUBLIC_BASE_URL', $root_url . 'public/'); // ทำให้เป็น Constant เพื่อใช้ในฟังก์ชันด้านล่าง

// --- สิ้นสุดการกำหนดค่า URL และ Path ภายใน group_actions.php ---

require_once(ROOT_DIR . '/lib/connect.php'); // ใช้ ROOT_DIR เพื่อความชัดเจน
require_once(ROOT_DIR . '/inc/getFunctions.php');

$response = ['status' => 'error', 'message' => 'Invalid action.', 'message_en' => 'Invalid action.', 'message_cn' => '无效的操作.', 'message_jp' => '無効な操作.', 'message_kr' => '잘못된 작업입니다.'];

if (isset($_POST['action'])) {
    $action = $_POST['action'];

    switch ($action) {
        case 'add_group':
            $group_name = trim($conn->real_escape_string($_POST['group_name']));
            $group_name_en = trim($conn->real_escape_string($_POST['group_name_en']));
            $group_name_cn = trim($conn->real_escape_string($_POST['group_name_cn']));
            $group_name_jp = trim($conn->real_escape_string($_POST['group_name_jp']));
            $group_name_kr = trim($conn->real_escape_string($_POST['group_name_kr'])); // เพิ่ม kr
            // รับค่า parent_group_id ให้เป็น int หรือ NULL
            $parent_group_id = !empty($_POST['parent_group_id']) ? (int)$_POST['parent_group_id'] : null;
            $image_path = null;

            if (empty($group_name)) {
                $response = ['status' => 'error', 'message' => 'กรุณากรอกชื่อหมวดหมู่.', 'message_en' => 'Please enter a group name.', 'message_cn' => '请输入群组名称.', 'message_jp' => 'グループ名を入力してください.', 'message_kr' => '그룹 이름을 입력하십시오.'];
                echo json_encode($response);
                exit();
            }

            // ตรวจสอบว่าชื่อกลุ่มซ้ำหรือไม่ (สำหรับกลุ่มแม่และกลุ่มย่อยภายใต้กลุ่มแม่เดียวกัน)
            $check_sql = "SELECT COUNT(*) AS count FROM dn_shop_groups WHERE group_name = ? AND del = '0'";
            if ($parent_group_id !== null) {
                $check_sql .= " AND parent_group_id = ?";
            } else {
                $check_sql .= " AND parent_group_id IS NULL";
            }
            $stmt_check = $conn->prepare($check_sql);
            if ($stmt_check) {
                if ($parent_group_id !== null) {
                    $stmt_check->bind_param("si", $group_name, $parent_group_id);
                } else {
                    $stmt_check->bind_param("s", $group_name);
                }
                $stmt_check->execute();
                $check_result = $stmt_check->get_result();
                if ($check_result && $check_result->fetch_assoc()['count'] > 0) {
                    $response = ['status' => 'error', 'message' => 'ชื่อหมวดหมู่นี้มีอยู่แล้วในหมวดหมู่เดียวกัน!', 'message_en' => 'This group name already exists in the same category!', 'message_cn' => '此群组名称在同一类别中已存在！', 'message_jp' => 'このグループ名は同じカテゴリにすでに存在します！', 'message_kr' => '이 그룹 이름은 동일한 카테고리에 이미 존재합니다!'];
                    echo json_encode($response);
                    exit();
                }
                $stmt_check->close();
            } else {
                $response = ['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการตรวจสอบชื่อหมวดหมู่: ' . $conn->error, 'message_en' => 'An error occurred while checking the group name: ' . $conn->error, 'message_cn' => '检查群组名称时出错：' . $conn->error, 'message_jp' => 'グループ名の確認中にエラーが発生しました：' . $conn->error, 'message_kr' => '그룹 이름 확인 중 오류가 발생했습니다: ' . $conn->error];
                echo json_encode($response);
                exit();
            }

            // จัดการอัปโหลดรูปภาพสำหรับกลุ่มแม่เท่านั้น
            if ($parent_group_id === null) { // ถ้าเป็นกลุ่มแม่
                if (isset($_FILES['group_image']) && $_FILES['group_image']['error'] == 0) {
                    $upload_dir = ROOT_DIR . '/public/uploads/group_images/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    $file_name = time() . '_' . uniqid() . '.' . strtolower(pathinfo($_FILES['group_image']['name'], PATHINFO_EXTENSION));
                    $target_file = $upload_dir . $file_name;
                    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

                    $check = getimagesize($_FILES['group_image']['tmp_name']);
                    if ($check === false) {
                        $response = ['status' => 'error', 'message' => 'ไฟล์ที่อัปโหลดไม่ใช่รูปภาพ.', 'message_en' => 'The uploaded file is not an image.', 'message_cn' => '上传的文件不是图片.', 'message_jp' => 'アップロードされたファイルは画像ではありません.', 'message_kr' => '업로드된 파일은 이미지가 아닙니다.'];
                        echo json_encode($response);
                        exit();
                    }

                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                    if (!in_array($file_type, $allowed_types)) {
                        $response = ['status' => 'error', 'message' => 'ขออภัย, อนุญาตเฉพาะ JPG, JPEG, PNG & GIF files เท่านั้น.', 'message_en' => 'Sorry, only JPG, JPEG, PNG & GIF files are allowed.', 'message_cn' => '抱歉，只允许JPG, JPEG, PNG和GIF文件.', 'message_jp' => '申し訳ありませんが、JPG、JPEG、PNG、GIFファイルのみが許可されています.', 'message_kr' => '죄송합니다. JPG, JPEG, PNG, GIF 파일만 허용됩니다.'];
                        echo json_encode($response);
                        exit();
                    }

                    if ($_FILES['group_image']['size'] > 5 * 1024 * 1024) {
                        $response = ['status' => 'error', 'message' => 'ขนาดไฟล์รูปภาพต้องไม่เกิน 5MB.', 'message_en' => 'Image file size must not exceed 5MB.', 'message_cn' => '图片文件大小不得超过5MB.', 'message_jp' => '画像ファイルサイズは5MBを超えてはいけません.', 'message_kr' => '이미지 파일 크기는 5MB를 초과할 수 없습니다.'];
                        echo json_encode($response);
                        exit();
                    }

                    if (move_uploaded_file($_FILES['group_image']['tmp_name'], $target_file)) {
                        $image_path = PUBLIC_BASE_URL . 'uploads/group_images/' . $file_name;
                    } else {
                        $response = ['status' => 'error', 'message' => 'ไม่สามารถอัปโหลดรูปภาพได้.', 'message_en' => 'Could not upload the image.', 'message_cn' => '无法上传图片.', 'message_jp' => '画像をアップロードできませんでした.', 'message_kr' => '이미지를 업로드할 수 없습니다.'];
                        echo json_encode($response);
                        exit();
                    }
                }
            } elseif ($parent_group_id !== null && isset($_FILES['group_image']) && $_FILES['group_image']['error'] == 0) {
                // หากพยายามอัปโหลดรูปภาพสำหรับกลุ่มย่อย ให้เกิดข้อผิดพลาด
                $response = ['status' => 'error', 'message' => 'กลุ่มย่อยไม่สามารถมีรูปภาพได้.', 'message_en' => 'Subgroups cannot have images.', 'message_cn' => '子群组不能有图片.', 'message_jp' => 'サブグループには画像を含めることはできません.', 'message_kr' => '하위 그룹에는 이미지를 포함할 수 없습니다.'];
                echo json_encode($response);
                exit();
            }

            // ใช้ NULL สำหรับ parent_group_id เมื่อเป็น NULL
            // **แก้ไขตรงนี้**: เพิ่มคอลัมน์ status และค่า '1' และคอลัมน์ภาษาอื่น
            $sql = "INSERT INTO dn_shop_groups (group_name, group_name_en, group_name_cn, group_name_jp, group_name_kr, parent_group_id, image_path, date_create, del, status) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), '0', '1')";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                // เปลี่ยนการ bind_param ให้รองรับ NULL ได้อย่างถูกต้องโดยใช้ "s" สำหรับ int และ "s" สำหรับ string ที่เป็น NULL
                // MySQL จะแปลง NULL string เป็น NULL ในคอลัมน์ INT หรือ TEXT โดยอัตโนมัติ
                $stmt->bind_param("sssssss", $group_name, $group_name_en, $group_name_cn, $group_name_jp, $group_name_kr, $parent_group_id, $image_path);

                if ($stmt->execute()) {
                    $response = ['status' => 'success', 'message' => 'เพิ่มหมวดหมู่สำเร็จ!', 'message_en' => 'Group added successfully!', 'message_cn' => '群组添加成功！', 'message_jp' => 'グループが正常に追加されました！', 'message_kr' => '그룹이 성공적으로 추가되었습니다!'];
                } else {
                    $response = ['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการเพิ่มหมวดหมู่: ' . $stmt->error, 'message_en' => 'An error occurred while adding the group: ' . $stmt->error, 'message_cn' => '添加群组时出错：' . $stmt->error, 'message_jp' => 'グループの追加中にエラーが発生しました：' . $stmt->error, 'message_kr' => '그룹 추가 중 오류가 발생했습니다: ' . $stmt->error];
                }
                $stmt->close();
            } else {
                $response = ['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: ' . $conn->error, 'message_en' => 'An error occurred while preparing the SQL statement: ' . $conn->error, 'message_cn' => '准备SQL语句时出错：' . $conn->error, 'message_jp' => 'SQLステートメントの準備中にエラーが発生しました：' . $conn->error, 'message_kr' => 'SQL 문 준비 중 오류가 발생했습니다: ' . $conn->error];
            }
            break;

        case 'edit_group':
            $group_id = (int)$_POST['group_id'];
            $group_name = trim($conn->real_escape_string($_POST['group_name']));
            // เพิ่มตัวแปรสำหรับคอลัมน์ที่ต้องการแก้ไข
            $group_name_en = trim($conn->real_escape_string($_POST['group_name_en']));
            $group_name_cn = trim($conn->real_escape_string($_POST['group_name_cn']));
            $group_name_jp = trim($conn->real_escape_string($_POST['group_name_jp']));
            $group_name_kr = trim($conn->real_escape_string($_POST['group_name_kr'])); // เพิ่ม kr
            $description = trim($conn->real_escape_string($_POST['description']));
            $description_en = trim($conn->real_escape_string($_POST['description_en']));
            $description_cn = trim($conn->real_escape_string($_POST['description_cn']));
            $description_jp = trim($conn->real_escape_string($_POST['description_jp']));
            $description_kr = trim($conn->real_escape_string($_POST['description_kr'])); // เพิ่ม kr

            $group_type = $_POST['group_type']; // 'main' or 'sub'
            $parent_group_id = null; // Default for main groups

            if (empty($group_name)) {
                $response = ['status' => 'error', 'message' => 'กรุณากรอกชื่อหมวดหมู่.', 'message_en' => 'Please enter a group name.', 'message_cn' => '请输入群组名称.', 'message_jp' => 'グループ名を入力してください.', 'message_kr' => '그룹 이름을 입력하십시오.'];
                echo json_encode($response);
                exit();
            }

            // ตรวจสอบว่าชื่อกลุ่มซ้ำหรือไม่
            $check_sql = "SELECT COUNT(*) AS count FROM dn_shop_groups WHERE group_name = ? AND group_id != ? AND del = '0'";
            if ($group_type === 'sub') {
                $parent_group_id = !empty($_POST['parent_group_id']) ? (int)$_POST['parent_group_id'] : null;
                if ($parent_group_id !== null) {
                    $check_sql .= " AND parent_group_id = ?";
                } else {
                    $check_sql .= " AND parent_group_id IS NULL";
                }
            } else { // main group
                $check_sql .= " AND parent_group_id IS NULL";
            }
            $stmt_check = $conn->prepare($check_sql);
            if ($stmt_check) {
                if ($group_type === 'sub' && $parent_group_id !== null) {
                    $stmt_check->bind_param("sii", $group_name, $group_id, $parent_group_id);
                } elseif ($group_type === 'sub' && $parent_group_id === null) {
                    $stmt_check->bind_param("si", $group_name, $group_id);
                } else { // main group
                    $stmt_check->bind_param("si", $group_name, $group_id);
                }
                $stmt_check->execute();
                $check_result = $stmt_check->get_result();
                if ($check_result && $check_result->fetch_assoc()['count'] > 0) {
                    $response = ['status' => 'error', 'message' => 'ชื่อหมวดหมู่นี้มีอยู่แล้วในหมวดหมู่เดียวกัน!', 'message_en' => 'This group name already exists in the same category!', 'message_cn' => '此群组名称在同一类别中已存在！', 'message_jp' => 'このグループ名は同じカテゴリにすでに存在します！', 'message_kr' => '이 그룹 이름은 동일한 카테고리에 이미 존재합니다!'];
                    echo json_encode($response);
                    exit();
                }
                $stmt_check->close();
            } else {
                $response = ['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการตรวจสอบชื่อหมวดหมู่: ' . $conn->error, 'message_en' => 'An error occurred while checking the group name: ' . $conn->error, 'message_cn' => '检查群组名称时出错：' . $conn->error, 'message_jp' => 'グループ名の確認中にエラーが発生しました：' . $conn->error, 'message_kr' => '그룹 이름 확인 중 오류가 발생했습니다: ' . $conn->error];
                echo json_encode($response);
                exit();
            }

            // ดึง image_path ปัจจุบัน
            $sql_fetch_current_image = "SELECT image_path FROM dn_shop_groups WHERE group_id = ?";
            $stmt_fetch = $conn->prepare($sql_fetch_current_image);
            $stmt_fetch->bind_param("i", $group_id);
            $stmt_fetch->execute();
            $stmt_fetch->bind_result($current_image_path);
            $stmt_fetch->fetch();
            $stmt_fetch->close();

            $new_image_path = $current_image_path; // เริ่มต้นด้วย path รูปภาพปัจจุบัน

            // จัดการอัปโหลดรูปภาพสำหรับกลุ่มแม่เท่านั้น (ถ้ามีการเลือกไฟล์ใหม่)
            if ($group_type === 'main') {
                if (isset($_FILES['group_image']) && $_FILES['group_image']['error'] == 0) {
                    $upload_dir = ROOT_DIR . '/public/uploads/group_images/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    $file_name = time() . '_' . uniqid() . '.' . strtolower(pathinfo($_FILES['group_image']['name'], PATHINFO_EXTENSION));
                    $target_file = $upload_dir . $file_name;
                    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

                    $check = getimagesize($_FILES['group_image']['tmp_name']);
                    if ($check === false) {
                        $response = ['status' => 'error', 'message' => 'ไฟล์ที่อัปโหลดไม่ใช่รูปภาพ.', 'message_en' => 'The uploaded file is not an image.', 'message_cn' => '上传的文件不是图片.', 'message_jp' => 'アップロードされたファイルは画像ではありません.', 'message_kr' => '업로드된 파일은 이미지가 아닙니다.'];
                        echo json_encode($response);
                        exit();
                    }
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                    if (!in_array($file_type, $allowed_types)) {
                        $response = ['status' => 'error', 'message' => 'ขออภัย, อนุญาตเฉพาะ JPG, JPEG, PNG & GIF files เท่านั้น.', 'message_en' => 'Sorry, only JPG, JPEG, PNG & GIF files are allowed.', 'message_cn' => '抱歉，只允许JPG, JPEG, PNG和GIF文件.', 'message_jp' => '申し訳ありませんが、JPG、JPEG、PNG、GIFファイルのみが許可されています.', 'message_kr' => '죄송합니다. JPG, JPEG, PNG, GIF 파일만 허용됩니다.'];
                        echo json_encode($response);
                        exit();
                    }
                    if ($_FILES['group_image']['size'] > 5 * 1024 * 1024) {
                        $response = ['status' => 'error', 'message' => 'ขนาดไฟล์รูปภาพต้องไม่เกิน 5MB.', 'message_en' => 'Image file size must not exceed 5MB.', 'message_cn' => '图片文件大小不得超过5MB.', 'message_jp' => '画像ファイルサイズは5MBを超えてはいけません.', 'message_kr' => '이미지 파일 크기는 5MB를 초과할 수 없습니다.'];
                        echo json_encode($response);
                        exit();
                    }

                    if (move_uploaded_file($_FILES['group_image']['tmp_name'], $target_file)) {
                        $new_image_path = PUBLIC_BASE_URL . 'uploads/group_images/' . $file_name;
                        deleteOldImage($current_image_path); // เรียกใช้ฟังก์ชันที่กำหนดในไฟล์นี้
                    } else {
                        $response = ['status' => 'error', 'message' => 'ไม่สามารถอัปโหลดรูปภาพได้.', 'message_en' => 'Could not upload the image.', 'message_cn' => '无法上传图片.', 'message_jp' => '画像をアップロードできませんでした.', 'message_kr' => '이미지를 업로드할 수 없습니다.'];
                        echo json_encode($response);
                        exit();
                    }
                }
            } else { // if group_type is 'sub'
                $new_image_path = null; // กลุ่มย่อยต้องไม่มีรูปภาพ
                deleteOldImage($current_image_path); // ลบรูปภาพเก่าถ้ากลุ่มถูกเปลี่ยนจาก main เป็น sub
                $parent_group_id = !empty($_POST['parent_group_id']) ? (int)$_POST['parent_group_id'] : null;
            }

            // คำสั่ง SQL ที่แก้ไขแล้ว: เพิ่มคอลัมน์ group_name_en, group_name_cn, description, description_en, description_cn
            $sql = "UPDATE dn_shop_groups SET group_name = ?, group_name_en = ?, group_name_cn = ?, group_name_jp = ?, group_name_kr = ?, description = ?, description_en = ?, description_cn = ?, description_jp = ?, description_kr = ?, parent_group_id = ?, image_path = ? WHERE group_id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                // แก้ไข bind_param ให้มีจำนวนอาร์กิวเมนต์ที่ถูกต้อง
                $stmt->bind_param("ssssssssssisi", $group_name, $group_name_en, $group_name_cn, $group_name_jp, $group_name_kr, $description, $description_en, $description_cn, $description_jp, $description_kr, $parent_group_id, $new_image_path, $group_id);

                if ($stmt->execute()) {
                    $response = ['status' => 'success', 'message' => 'แก้ไขหมวดหมู่สำเร็จ!', 'message_en' => 'Group updated successfully!', 'message_cn' => '群组更新成功！', 'message_jp' => 'グループが正常に更新されました！', 'message_kr' => '그룹이 성공적으로 업데이트되었습니다!'];
                } else {
                    $response = ['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการแก้ไขหมวดหมู่: ' . $stmt->error, 'message_en' => 'An error occurred while updating the group: ' . $stmt->error, 'message_cn' => '更新群组时出错：' . $stmt->error, 'message_jp' => 'グループの更新中にエラーが発生しました：' . $stmt->error, 'message_kr' => '그룹 업데이트 중 오류가 발생했습니다: ' . $stmt->error];
                }
                $stmt->close();
            } else {
                $response = ['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: ' . $conn->error, 'message_en' => 'An error occurred while preparing the SQL statement: ' . $conn->error, 'message_cn' => '准备SQL语句时出错：' . $conn->error, 'message_jp' => 'SQLステートメントの準備中にエラーが発生しました：' . $conn->error, 'message_kr' => 'SQL 문 준비 중 오류가 발생했습니다: ' . $conn->error];
            }
            break;

        case 'delete_group':
            $group_id = (int)$_POST['group_id'];

            $conn->begin_transaction();
            try {
                // ดึง image_path ของกลุ่มที่กำลังจะลบ
                $sql_get_image = "SELECT image_path FROM dn_shop_groups WHERE group_id = ?";
                $stmt_get_image = $conn->prepare($sql_get_image);
                $stmt_get_image->bind_param("i", $group_id);
                $stmt_get_image->execute();
                $stmt_get_image->bind_result($image_to_delete);
                $stmt_get_image->fetch();
                $stmt_get_image->close();

                // Set parent_group_id to NULL for sub-groups under this main group
                $sql_update_children = "UPDATE dn_shop_groups SET parent_group_id = NULL WHERE parent_group_id = ?";
                $stmt_update_children = $conn->prepare($sql_update_children);
                if ($stmt_update_children) {
                    $stmt_update_children->bind_param("i", $group_id);
                    $stmt_update_children->execute();
                    $stmt_update_children->close();
                } else {
                    throw new Exception("Error preparing update children statement: " . $conn->error);
                }

                // Set group_id to NULL for products under this group
                $sql_update_products = "UPDATE dn_shop SET group_id = NULL WHERE group_id = ?";
                $stmt_update_products = $conn->prepare($sql_update_products);
                if ($stmt_update_products) {
                    $stmt_update_products->bind_param("i", $group_id);
                    $stmt_update_products->execute();
                    $stmt_update_products->close();
                } else {
                    throw new Exception("Error preparing update products statement: " . $conn->error);
                }

                // Soft delete the group
                $sql = "UPDATE dn_shop_groups SET del = '1' WHERE group_id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("i", $group_id);
                    if ($stmt->execute()) {
                        $conn->commit();
                        $response = ['status' => 'success', 'message' => 'ลบหมวดหมู่สำเร็จ!', 'message_en' => 'Group deleted successfully!', 'message_cn' => '群组删除成功！', 'message_jp' => 'グループは正常に削除されました！', 'message_kr' => '그룹이 성공적으로 삭제되었습니다!'];
                        // ลบไฟล์รูปภาพหลังจากลบใน DB สำเร็จ
                        deleteOldImage($image_to_delete); // เรียกใช้ฟังก์ชันที่กำหนดในไฟล์นี้
                    } else {
                        throw new Exception("Error executing delete statement: " . $stmt->error);
                    }
                    $stmt->close();
                } else {
                    throw new Exception("Error preparing delete statement: " . $conn->error);
                }
            } catch (Exception $e) {
                $conn->rollback();
                $response = ['status' => 'error', 'message' => 'ไม่สามารถลบหมวดหมู่ได้: ' . $e->getMessage(), 'message_en' => 'Could not delete the group: ' . $e->getMessage(), 'message_cn' => '无法删除群组：' . $e->getMessage(), 'message_jp' => 'グループを削除できませんでした：' . $e->getMessage(), 'message_kr' => '그룹을 삭제할 수 없습니다: ' . $e->getMessage()];
            }
            break;
    }
}

/**
 * ฟังก์ชันสำหรับลบไฟล์รูปภาพเก่าจาก Server
 * @param string $image_url Full URL ของรูปภาพที่จะลบ
 */
function deleteOldImage($image_url) {
    // กำหนด placeholder image ที่แน่นอน
    $placeholder_image_name = 'group_placeholder.jpg'; // คุณอาจต้องปรับชื่อนี้
    $default_image_from_db = 'https://www.perfume.com/public/shop_img/6878c8c67917f_photo_2025-07-17_16-55-28.jpg'; // URL รูปภาพ default ที่พบในฐานข้อมูล

    if ($image_url && str_starts_with($image_url, PUBLIC_BASE_URL)) {
        $relative_path = substr($image_url, strlen(PUBLIC_BASE_URL));
        $local_file_path = ROOT_DIR . '/public/' . $relative_path;

        // ตรวจสอบว่าเป็น placeholder image หรือ URL ที่ไม่ควรลบ
        $is_placeholder_or_default = (strpos($image_url, $placeholder_image_name) !== false || $image_url === $default_image_from_db);

        if (!$is_placeholder_or_default && file_exists($local_file_path)) {
            unlink($local_file_path);
        }
    }
}

$conn->close();
echo json_encode($response);
?>