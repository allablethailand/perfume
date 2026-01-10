<?php
ob_start(); // <<< เพิ่มบรรทัดนี้
header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');
require_once(__DIR__ . '/../../../../lib/base_directory.php');
require_once(__DIR__ . '/../../../../lib/connect.php');
require_once(__DIR__ . '/../../../../inc/getFunctions.php');

global $base_path;
global $base_path_admin;
global $conn;

// --- ฟังก์ชันที่เพิ่มเข้ามาสำหรับแก้ไข Content ---
function updateImageSources($content, $base_path)
{
    // Path สำหรับรูปภาพที่อัปโหลด
    $image_base_url = $base_path . '/public/news_img/';

    // รูปแบบที่ต้องการค้นหา: <img src="" data-filename="some_file.png" ...>
    // ใช้ Regular Expression เพื่อค้นหาและจับกลุ่ม data-filename
    $pattern = '/<img\s+[^>]*src\s*=\s*""\s+data-filename\s*=\s*"([^"]+)"/i';

    // ฟังก์ชันที่จะใช้ในการแทนที่
    $replacement_callback = function ($matches) use ($image_base_url) {
        $filename = $matches[1]; // ชื่อไฟล์ที่ได้จาก data-filename
        $new_src = $image_base_url . $filename;
        
        // แทนที่ src="" ด้วย src="URL_จริง" และลบ data-filename ออกไป
        $updated_img_tag = str_replace(
            $matches[0], // แท็กที่ถูกจับโดย pattern
            '<img src="' . htmlspecialchars($new_src) . '"', // แทนที่ src="" ด้วย src ที่ถูกต้อง
            $matches[0]
        );
        // ลบ data-filename="[...]" ออกเพื่อความเรียบร้อย
        $updated_img_tag = preg_replace('/\s*data-filename\s*=\s*"[^"]+"/i', '', $updated_img_tag);
        
        return $updated_img_tag;
    };

    // ใช้ preg_replace_callback ในการค้นหาและแทนที่
    $updated_content = preg_replace_callback($pattern, $replacement_callback, $content);

    return $updated_content;
}
// --------------------------------------------------

function insertIntoDatabase($conn, $table, $columns, $values)
{
    $placeholders = implode(', ', array_fill(0, count($values), '?'));
    $query = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES ($placeholders)";
    $stmt = $conn->prepare($query);

    $types = str_repeat('s', count($values));
    $stmt->bind_param($types, ...$values);

    if ($stmt->execute()) {
        return 1;
    } else {
        return 0;
    }
}

function updateInDatabase($conn, $table, $columns, $values, $whereClause, $whereValues)
{
    $setPart = implode(', ', array_map(function ($col) {
        return "$col = ?";
    }, $columns));

    $query = "UPDATE $table SET $setPart WHERE $whereClause";

    $stmt = $conn->prepare($query);

    $types = str_repeat('s', count($values)) . str_repeat('s', count($whereValues));
    $stmt->bind_param($types, ...array_merge($values, $whereValues));

    if ($stmt->execute()) {
        return 1;
    } else {
        return 0;
    }
}

function handleFileUpload($files)
{
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    $maxFileSize = 5 * 1024 * 1024; // 5 MB
    $uploadResults = [];
    $uploadFileDir = '../../../../public/news_img/';

    // ตรวจสอบและสร้าง Folder ถ้ายังไม่มี
    if (!is_dir($uploadFileDir)) {
        mkdir($uploadFileDir, 0755, true);
    }

    // ฟังก์ชันย่อยสำหรับจัดการการอัปโหลดไฟล์เดียว
    $processFile = function ($fileTmpPath, $fileName, $fileSize, $fileType, $fileError) use ($allowedExtensions, $maxFileSize, $uploadFileDir, &$uploadResults) {
        if ($fileError === UPLOAD_ERR_OK) {
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            if (in_array($fileExtension, $allowedExtensions) && $fileSize <= $maxFileSize) {
                // *** ส่วนที่แก้ไข: สร้างชื่อไฟล์ใหม่ที่ไม่ซ้ำกัน ***
                // สร้างชื่อไฟล์ใหม่: [uniqid]_[timestamp].[ext]
                $newFileName = uniqid() . '_' . time() . '.' . $fileExtension;
                // หากต้องการชื่อที่อ่านง่ายขึ้นอาจใช้ date("YmdHis") แทน time() ก็ได้
                
                $destFilePath = $uploadFileDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destFilePath)) {
                    $uploadResults[] = [
                        'success' => true,
                        // ใช้ชื่อไฟล์ใหม่ที่ถูกสร้างขึ้น
                        'fileName' => $newFileName, 
                        'fileSize' => $fileSize,
                        'fileType' => $fileType,
                        'filePath' => $destFilePath
                    ];
                } else {
                    $uploadResults[] = [
                        'success' => false,
                        'fileName' => $fileName,
                        'error' => 'Error occurred while moving the uploaded file.'
                    ];
                }
            } else {
                $uploadResults[] = [
                    'success' => false,
                    'fileName' => $fileName,
                    'error' => 'Invalid file type or file size exceeds limit.'
                ];
            }
        } else {
            $uploadResults[] = [
                'success' => false,
                'fileName' => $fileName,
                'error' => 'No file uploaded or there was an upload error (Code: ' . $fileError . ').'
            ];
        }
    };

    // ตรวจสอบว่าเป็นไฟล์ array หรือไฟล์เดียว
    if (isset($files['name']) && is_array($files['name'])) {
        // กรณีเป็นไฟล์หลายไฟล์ (เช่น รูปภาพใน Content)
        foreach ($files['name'] as $key => $fileName) {
            $processFile(
                $files['tmp_name'][$key],
                $fileName,
                $files['size'][$key],
                $files['type'][$key],
                $files['error'][$key]
            );
        }
    } else if (isset($files['name'])) { 
        // กรณีเป็นไฟล์เดียว (เช่น Cover Photo)
        $processFile(
            $files['tmp_name'],
            $files['name'],
            $files['size'],
            $files['type'],
            $files['error']
        );
    } else {
        $uploadResults[] = [
            'success' => false,
            'error' => 'No files were uploaded.'
        ];
    }
    return $uploadResults;
}

$response = array('status' => 'error', 'message' => '');

// actions/process_project.php

// 1. ตรวจสอบ Action 'upload_image_content' ก่อน Action อื่นๆ
if (isset($_POST['action']) && $_POST['action'] === 'upload_image_content') {
    // ตรวจสอบว่ามีการส่งไฟล์ 'file' มาจริงหรือไม่
    if (!isset($_FILES['file'])) {
        $response = array('status' => 'error', 'message' => 'No file received.');
    } else {
        // ใช้ handleFileUpload กับไฟล์ที่ถูกส่งมาในชื่อ 'file' (จาก uploadImage)
        $uploadResults = handleFileUpload($_FILES['file']); 
        
       if (!empty($uploadResults) && $uploadResults[0]['success']) {
            
            // 💡 แก้ไข: ใช้ $base_path เพื่อสร้าง URL ที่เบราว์เซอร์เข้าถึงได้
            // สมมติว่า $base_path เก็บ URL รากของเว็บไซต์ เช่น "https://perfume.com"
            $image_url_path = $base_path . 'public/news_img/' . $uploadResults[0]['fileName']; 
            
            // ส่งชื่อไฟล์และพาธกลับไปยัง Summernote
            $response = array(
                'status' => 'success',
                'fileName' => $uploadResults[0]['fileName'],
                // *** ใช้ $image_url_path แทน Relative Path ของ PHP ***
                'filePath' => $image_url_path 
            );
        } else {
            $response = array(
                'status' => 'error',
                'message' => 'Upload failed: ' . ($uploadResults[0]['error'] ?? 'Unknown error')
            );
        }
    }
    
    // สำคัญมาก: ส่ง JSON กลับไปทันทีและหยุดการประมวลผลโค้ดที่เหลือ
    ob_end_clean(); // <<< เพิ่ม: ล้าง output buffer ก่อนส่ง JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit; 
}

try {
    if (isset($_POST['action']) && $_POST['action'] == 'addproject') {
        $project_array = [
            'project_subject' => $_POST['project_subject'] ?? '',
            'project_description' => $_POST['project_description'] ?? '',
            'project_content' => $_POST['project_content'] ?? '',
            'project_subject_en' => $_POST['project_subject_en'] ?? '',
            'project_description_en' => $_POST['project_description_en'] ?? '',
            'project_content_en' => $_POST['project_content_en'] ?? '',
            'project_subject_cn' => $_POST['project_subject_cn'] ?? '',
            'project_description_cn' => $_POST['project_description_cn'] ?? '',
            'project_content_cn' => $_POST['project_content_cn'] ?? '',
            'project_subject_jp' => $_POST['project_subject_jp'] ?? '',
            'project_description_jp' => $_POST['project_description_jp'] ?? '',
            'project_content_jp' => $_POST['project_content_jp'] ?? '',
            'project_subject_kr' => $_POST['project_subject_kr'] ?? '',
            'project_description_kr' => $_POST['project_description_kr'] ?? '',
            'project_content_kr' => $_POST['project_content_kr'] ?? '',
        ];
         
        $related_shops = $_POST['related_shops'] ?? [];

        if (isset($project_array)) {
            // --- ส่วนที่แก้ไข: เรียกใช้ฟังก์ชัน updateImageSources ก่อนบันทึก Content ---
            $project_content = updateImageSources($_POST['project_content'] ?? '', $base_path);
            $project_content = mb_convert_encoding($project_content, 'UTF-8', 'auto');
            $project_content_en = updateImageSources($_POST['project_content_en'] ?? '', $base_path);
            $project_content_en = mb_convert_encoding($project_content_en, 'UTF-8', 'auto');
            $project_content_cn = updateImageSources($_POST['project_content_cn'] ?? '', $base_path);
            $project_content_cn = mb_convert_encoding($project_content_cn, 'UTF-8', 'auto');
            $project_content_jp = updateImageSources($_POST['project_content_jp'] ?? '', $base_path);
            $project_content_jp = mb_convert_encoding($project_content_jp, 'UTF-8', 'auto');
            $project_content_kr = updateImageSources($_POST['project_content_kr'] ?? '', $base_path);
            $project_content_kr = mb_convert_encoding($project_content_kr, 'UTF-8', 'auto');
            // ---------------------------------------------------------------------

            $stmt = $conn->prepare("INSERT INTO dn_project 
                (subject_project, description_project, content_project, subject_project_en, description_project_en, content_project_en, subject_project_cn, description_project_cn, content_project_cn, subject_project_jp, description_project_jp, content_project_jp, subject_project_kr, description_project_kr, content_project_kr, date_create) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $project_subject = $project_array['project_subject'];
            $project_description = $project_array['project_description'];
            // $project_content ถูกกำหนดค่าใหม่แล้วข้างบน
            $project_subject_en = $project_array['project_subject_en'];
            $project_description_en = $project_array['project_description_en'];
            // $project_content_en ถูกกำหนดค่าใหม่แล้วข้างบน
            $project_subject_cn = $project_array['project_subject_cn'];
            $project_description_cn = $project_array['project_description_cn'];
            // $project_content_cn ถูกกำหนดค่าใหม่แล้วข้างบน
            $project_subject_jp = $project_array['project_subject_jp'];
            $project_description_jp = $project_array['project_description_jp'];
            // $project_content_jp ถูกกำหนดค่าใหม่แล้วข้างบน
            $project_subject_kr = $project_array['project_subject_kr'];
            $project_description_kr = $project_array['project_description_kr'];
            // $project_content_kr ถูกกำหนดค่าใหม่แล้วข้างบน
            $current_date = date('Y-m-d H:i:s');

            $stmt->bind_param(
                "ssssssssssssssss",
                $project_subject,
                $project_description,
                $project_content,
                $project_subject_en,
                $project_description_en,
                $project_content_en,
                $project_subject_cn,
                $project_description_cn,
                $project_content_cn,
                $project_subject_jp,
                $project_description_jp,
                $project_content_jp,
                $project_subject_kr,
                $project_description_kr,
                $project_content_kr,
                $current_date
            );

            if (!$stmt->execute()) {
                throw new Exception("Execute statement failed: " . $stmt->error);
            }
            $last_inserted_id = $conn->insert_id;
             
            if (!empty($related_shops)) {
                $stmt_shop_insert = $conn->prepare("INSERT INTO dn_project_shop (project_id, shop_id) VALUES (?, ?)");
                foreach ($related_shops as $shop_id) {
                    $stmt_shop_insert->bind_param("ii", $last_inserted_id, $shop_id);
                    $stmt_shop_insert->execute();
                }
                $stmt_shop_insert->close();
            }

            // แก้ไขการตรวจสอบไฟล์
            if (isset($_FILES['fileInput']) && $_FILES['fileInput']['error'][0] !== UPLOAD_ERR_NO_FILE) {
                $fileInfos = handleFileUpload($_FILES['fileInput']);
                foreach ($fileInfos as $fileInfo) {
                    if ($fileInfo['success']) {
                        $picPath = $base_path . '/public/news_img/' . $fileInfo['fileName'];
                        $fileColumns = ['project_id', 'file_name', 'file_size', 'file_type', 'file_path', 'api_path', 'status'];
                        $fileValues = [$last_inserted_id, $fileInfo['fileName'], $fileInfo['fileSize'], $fileInfo['fileType'], $fileInfo['filePath'], $picPath, 1];
                        insertIntoDatabase($conn, 'dn_project_doc', $fileColumns, $fileValues);
                    } else {
                        throw new Exception('Error uploading file: ' . ($fileInfo['fileName'] ?? 'unknown') . ' - ' . $fileInfo['error']);
                    }
                }
            }
            if (isset($_FILES['image_files']) && $_FILES['image_files']['error'][0] !== UPLOAD_ERR_NO_FILE) {
                $fileInfos = handleFileUpload($_FILES['image_files']);
                foreach ($fileInfos as $fileInfo) {
                    if ($fileInfo['success']) {
                        $picPath = $base_path . '/public/news_img/' . $fileInfo['fileName'];
                        $fileColumns = ['project_id', 'file_name', 'file_size', 'file_type', 'file_path', 'api_path'];
                        $fileValues = [$last_inserted_id, $fileInfo['fileName'], $fileInfo['fileSize'], $fileInfo['fileType'], $fileInfo['filePath'], $picPath];
                        insertIntoDatabase($conn, 'dn_project_doc', $fileColumns, $fileValues);
                    } else {
                        throw new Exception('Error uploading file: ' . ($fileInfo['fileName'] ?? 'unknown') . ' - ' . $fileInfo['error']);
                    }
                }
            }
             
            $response = array('status' => 'success', 'message' => 'save');
        }

    } elseif (isset($_POST['action']) && $_POST['action'] == 'editproject') {
        $project_array = [
            'project_id' => $_POST['project_id'] ?? '',
            'project_subject' => $_POST['project_subject'] ?? '',
            'project_description' => $_POST['project_description'] ?? '',
            'project_content' => $_POST['project_content'] ?? '',
            'project_subject_en' => $_POST['project_subject_en'] ?? '',
            'project_description_en' => $_POST['project_description_en'] ?? '',
            'project_content_en' => $_POST['project_content_en'] ?? '',
            'project_subject_cn' => $_POST['project_subject_cn'] ?? '',
            'project_description_cn' => $_POST['project_description_cn'] ?? '',
            'project_content_cn' => $_POST['project_content_cn'] ?? '',
            'project_subject_jp' => $_POST['project_subject_jp'] ?? '',
            'project_description_jp' => $_POST['project_description_jp'] ?? '',
            'project_content_jp' => $_POST['project_content_jp'] ?? '',
            'project_subject_kr' => $_POST['project_subject_kr'] ?? '',
            'project_description_kr' => $_POST['project_description_kr'] ?? '',
            'project_content_kr' => $_POST['project_content_kr'] ?? '',
        ];

        $related_shops = $_POST['related_shops'] ?? [];

        if (!empty($project_array['project_id'])) {
            // --- ส่วนที่แก้ไข: เรียกใช้ฟังก์ชัน updateImageSources ก่อนอัปเดต Content ---
            $project_content = updateImageSources($_POST['project_content'] ?? '', $base_path);
            $project_content = mb_convert_encoding($project_content, 'UTF-8', 'auto');
            $project_content_en = updateImageSources($_POST['project_content_en'] ?? '', $base_path);
            $project_content_en = mb_convert_encoding($project_content_en, 'UTF-8', 'auto');
            $project_content_cn = updateImageSources($_POST['project_content_cn'] ?? '', $base_path);
            $project_content_cn = mb_convert_encoding($project_content_cn, 'UTF-8', 'auto');
            $project_content_jp = updateImageSources($_POST['project_content_jp'] ?? '', $base_path);
            $project_content_jp = mb_convert_encoding($project_content_jp, 'UTF-8', 'auto');
            $project_content_kr = updateImageSources($_POST['project_content_kr'] ?? '', $base_path);
            $project_content_kr = mb_convert_encoding($project_content_kr, 'UTF-8', 'auto');
            // ---------------------------------------------------------------------

            $stmt = $conn->prepare("UPDATE dn_project 
            SET subject_project = ?, 
            description_project = ?, 
            content_project = ?,
            subject_project_en = ?,
            description_project_en = ?,
            content_project_en = ?,
            subject_project_cn = ?,
            description_project_cn = ?,
            content_project_cn = ?,
            subject_project_jp = ?,
            description_project_jp = ?,
            content_project_jp = ?,
            subject_project_kr = ?,
            description_project_kr = ?,
            content_project_kr = ?,
            date_create = ? 
            WHERE project_id = ?");

            $project_subject = $project_array['project_subject'];
            $project_description = $project_array['project_description'];
            // $project_content ถูกกำหนดค่าใหม่แล้วข้างบน
            $project_subject_en = $project_array['project_subject_en'] ?? '';
            $project_description_en = $project_array['project_description_en'] ?? '';
            // $project_content_en ถูกกำหนดค่าใหม่แล้วข้างบน
            $project_subject_cn = $project_array['project_subject_cn'] ?? '';
            $project_description_cn = $project_array['project_description_cn'] ?? '';
            // $project_content_cn ถูกกำหนดค่าใหม่แล้วข้างบน
            $project_subject_jp = $project_array['project_subject_jp'] ?? '';
            $project_description_jp = $project_array['project_description_jp'] ?? '';
            // $project_content_jp ถูกกำหนดค่าใหม่แล้วข้างบน
            $project_subject_kr = $project_array['project_subject_kr'] ?? '';
            $project_description_kr = $project_array['project_description_kr'] ?? '';
            // $project_content_kr ถูกกำหนดค่าใหม่แล้วข้างบน
            $current_date = date('Y-m-d H:i:s');
            $project_id = $project_array['project_id'];

            $stmt->bind_param(
                "ssssssssssssssssi",
                $project_subject,
                $project_description,
                $project_content,
                $project_subject_en,
                $project_description_en,
                $project_content_en,
                $project_subject_cn,
                $project_description_cn,
                $project_content_cn,
                $project_subject_jp,
                $project_description_jp,
                $project_content_jp,
                $project_subject_kr,
                $project_description_kr,
                $project_content_kr,
                $current_date,
                $project_id
            );

            if (!$stmt->execute()) {
                throw new Exception("Execute statement failed: " . $stmt->error);
            }
             
            $stmt_delete_shops = $conn->prepare("DELETE FROM dn_project_shop WHERE project_id = ?");
            $stmt_delete_shops->bind_param("i", $project_id);
            $stmt_delete_shops->execute();
            $stmt_delete_shops->close();

            if (!empty($related_shops)) {
                $stmt_shop_insert = $conn->prepare("INSERT INTO dn_project_shop (project_id, shop_id) VALUES (?, ?)");
                foreach ($related_shops as $shop_id) {
                    $stmt_shop_insert->bind_param("ii", $project_id, $shop_id);
                    $stmt_shop_insert->execute();
                }
                $stmt_shop_insert->close();
            }

         
            // --- ส่วนที่แก้ไข: จัดการรูป Cover Photo --- 
            if (isset($_FILES['fileInput']) && $_FILES['fileInput']['error'] == UPLOAD_ERR_OK) {
                // 1. ดึง path ของรูปเก่าเพื่อลบ
                $getOldCoverStmt = $conn->prepare("SELECT file_path FROM dn_project_doc WHERE project_id = ? AND status = 1 AND del = 0");
                if ($getOldCoverStmt) {
                    $getOldCoverStmt->bind_param("i", $project_id);
                    $getOldCoverStmt->execute();
                    $oldCoverResult = $getOldCoverStmt->get_result();
                    if ($oldCoverRow = $oldCoverResult->fetch_assoc()) {
                        $oldCoverPath = $oldCoverRow['file_path'];
                        // 2. ลบไฟล์เก่าถ้ามีอยู่จริง
                        if ($oldCoverPath && file_exists($oldCoverPath)) {
                            unlink($oldCoverPath);
                        }
                    }
                    $getOldCoverStmt->close();
                }

                // 3. อัปโหลดไฟล์รูปภาพใหม่
                $fileInfo = handleFileUpload($_FILES['fileInput'])[0];
                if ($fileInfo['success']) {
                    $picPath = $base_path . '/public/news_img/' . $fileInfo['fileName'];
                     
                    // 4. ตรวจสอบว่ามี Cover Photo ในฐานข้อมูลอยู่แล้วหรือไม่
                    $checkExistingCoverStmt = $conn->prepare("SELECT COUNT(*) FROM dn_project_doc WHERE project_id = ? AND status = 1 AND del = 0");
                    $checkExistingCoverStmt->bind_param("i", $project_id);
                    $checkExistingCoverStmt->execute();
                    $existingCount = $checkExistingCoverStmt->get_result()->fetch_row()[0];
                    $checkExistingCoverStmt->close();

                    if ($existingCount > 0) {
                             // 5. ถ้ามีอยู่แล้ว ให้อัปเดตข้อมูล
                        $updateCoverStmt = $conn->prepare("UPDATE dn_project_doc 
                            SET file_name = ?, file_size = ?, file_type = ?, file_path = ?, api_path = ? 
                            WHERE project_id = ? AND status = 1 AND del = 0");
                        if ($updateCoverStmt) {
                            $updateCoverStmt->bind_param(
                                "sisssi",
                                $fileInfo['fileName'],
                                $fileInfo['fileSize'],
                                $fileInfo['fileType'],
                                $fileInfo['filePath'],
                                $picPath,
                                $project_id
                            );
                            $updateCoverStmt->execute();
                            $updateCoverStmt->close();
                        }
                    } else {
                        // 6. ถ้าไม่มี ให้แทรกข้อมูลใหม่
                        $fileColumns = ['project_id', 'file_name', 'file_size', 'file_type', 'file_path', 'api_path', 'status'];
                        $fileValues = [$project_id, $fileInfo['fileName'], $fileInfo['fileSize'], $fileInfo['fileType'], $fileInfo['filePath'], $picPath, 1];
                        insertIntoDatabase($conn, 'dn_project_doc', $fileColumns, $fileValues);
                    }
                } else {
                    throw new Exception('Error uploading cover file: ' . ($fileInfo['fileName'] ?? 'unknown') . ' - ' . $fileInfo['error']);
                }
            }
            // --- สิ้นสุดส่วนที่แก้ไข ---
             
            // จัดการรูปภาพใน Content (ภาษาไทย)
            if (isset($_FILES['image_files_th']) && is_array($_FILES['image_files_th']['name']) && $_FILES['image_files_th']['error'][0] !== UPLOAD_ERR_NO_FILE) {
                $fileInfos = handleFileUpload($_FILES['image_files_th']);
                foreach ($fileInfos as $fileInfo) {
                    if ($fileInfo['success']) {
                        $picPath = $base_path . '/public/news_img/' . $fileInfo['fileName'];
                        $fileColumns = ['project_id', 'file_name', 'file_size', 'file_type', 'file_path', 'api_path'];
                        $fileValues = [$project_id, $fileInfo['fileName'], $fileInfo['fileSize'], $fileInfo['fileType'], $fileInfo['filePath'], $picPath];
                        insertIntoDatabase($conn, 'dn_project_doc', $fileColumns, $fileValues);
                    } else {
                        throw new Exception('Error uploading content file (TH): ' . ($fileInfo['fileName'] ?? 'unknown') . ' - ' . $fileInfo['error']);
                    }
                }
            }

            // จัดการรูปภาพใน Content (ภาษาอังกฤษ)
            if (isset($_FILES['image_files_en']) && is_array($_FILES['image_files_en']['name']) && $_FILES['image_files_en']['error'][0] !== UPLOAD_ERR_NO_FILE) {
                $fileInfos = handleFileUpload($_FILES['image_files_en']);
                foreach ($fileInfos as $fileInfo) {
                    if ($fileInfo['success']) {
                        $picPath = $base_path . '/public/news_img/' . $fileInfo['fileName'];
                        $fileColumns = ['project_id', 'file_name', 'file_size', 'file_type', 'file_path', 'api_path', 'lang'];
                        $fileValues = [$project_id, $fileInfo['fileName'], $fileInfo['fileSize'], $fileInfo['fileType'], $fileInfo['filePath'], $picPath, 'en'];
                        insertIntoDatabase($conn, 'dn_project_doc', $fileColumns, $fileValues);
                    } else {
                        throw new Exception('Error uploading content file (EN): ' . ($fileInfo['fileName'] ?? 'unknown') . ' - ' . $fileInfo['error']);
                    }
                }
            }

            // จัดการรูปภาพใน Content (ภาษาจีน)
            if (isset($_FILES['image_files_cn']) && is_array($_FILES['image_files_cn']['name']) && $_FILES['image_files_cn']['error'][0] !== UPLOAD_ERR_NO_FILE) {
                $fileInfos = handleFileUpload($_FILES['image_files_cn']);
                foreach ($fileInfos as $fileInfo) {
                    if ($fileInfo['success']) {
                        $picPath = $base_path . '/public/news_img/' . $fileInfo['fileName'];
                        $fileColumns = ['project_id', 'file_name', 'file_size', 'file_type', 'file_path', 'api_path', 'lang'];
                        $fileValues = [$project_id, $fileInfo['fileName'], $fileInfo['fileSize'], $fileInfo['fileType'], $fileInfo['filePath'], $picPath, 'cn'];
                        insertIntoDatabase($conn, 'dn_project_doc', $fileColumns, $fileValues);
                    } else {
                        throw new Exception('Error uploading content file (CN): ' . ($fileInfo['fileName'] ?? 'unknown') . ' - ' . $fileInfo['error']);
                    }
                }
            }
             
            // จัดการรูปภาพใน Content (ภาษาญี่ปุ่น)
            if (isset($_FILES['image_files_jp']) && is_array($_FILES['image_files_jp']['name']) && $_FILES['image_files_jp']['error'][0] !== UPLOAD_ERR_NO_FILE) {
                $fileInfos = handleFileUpload($_FILES['image_files_jp']);
                foreach ($fileInfos as $fileInfo) {
                    if ($fileInfo['success']) {
                        $picPath = $base_path . '/public/news_img/' . $fileInfo['fileName'];
                        $fileColumns = ['project_id', 'file_name', 'file_size', 'file_type', 'file_path', 'api_path', 'lang'];
                        $fileValues = [$project_id, $fileInfo['fileName'], $fileInfo['fileSize'], $fileInfo['fileType'], $fileInfo['filePath'], $picPath, 'jp'];
                        insertIntoDatabase($conn, 'dn_project_doc', $fileColumns, $fileValues);
                    } else {
                        throw new Exception('Error uploading content file (JP): ' . ($fileInfo['fileName'] ?? 'unknown') . ' - ' . $fileInfo['error']);
                    }
                }
            }

            // จัดการรูปภาพใน Content (ภาษาเกาหลี)
            if (isset($_FILES['image_files_kr']) && is_array($_FILES['image_files_kr']['name']) && $_FILES['image_files_kr']['error'][0] !== UPLOAD_ERR_NO_FILE) {
                $fileInfos = handleFileUpload($_FILES['image_files_kr']);
                foreach ($fileInfos as $fileInfo) {
                    if ($fileInfo['success']) {
                        $picPath = $base_path . '/public/news_img/' . $fileInfo['fileName'];
                        $fileColumns = ['project_id', 'file_name', 'file_size', 'file_type', 'file_path', 'api_path', 'lang'];
                        $fileValues = [$project_id, $fileInfo['fileName'], $fileInfo['fileSize'], $fileInfo['fileType'], $fileInfo['filePath'], $picPath, 'kr'];
                        insertIntoDatabase($conn, 'dn_project_doc', $fileColumns, $fileValues);
                    } else {
                        throw new Exception('Error uploading content file (KR): ' . ($fileInfo['fileName'] ?? 'unknown') . ' - ' . $fileInfo['error']);
                    }
                }
            }
             
            $response = array('status' => 'success', 'message' => 'edit save');
        }

    } elseif (isset($_POST['action']) && $_POST['action'] == 'delproject') {
        $project_id = $_POST['id'] ?? '';
        $del = '1';
         
        $stmt = $conn->prepare("UPDATE dn_project 
            SET del = ? 
            WHERE project_id = ?");
        $stmt->bind_param("si", $del, $project_id);
        if (!$stmt->execute()) {
            throw new Exception("Execute statement failed: " . $stmt->error);
        }
        $stmt->close();
         
        $stmt = $conn->prepare("UPDATE dn_project_doc 
            SET del = ? 
            WHERE project_id = ?");
        $stmt->bind_param("si", $del, $project_id);
        if (!$stmt->execute()) {
            throw new Exception("Execute statement failed: " . $stmt->error);
        }
        $stmt->close();

        $stmt = $conn->prepare("UPDATE dn_project_shop 
            SET del = ? 
            WHERE project_id = ?");
        $stmt->bind_param("si", $del, $project_id);
        if (!$stmt->execute()) {
            throw new Exception("Execute statement failed: " . $stmt->error);
        }
        $stmt->close();
         
        $response = array('status' => 'success', 'message' => 'Delete');
         
    } elseif (isset($_POST['action']) && $_POST['action'] == 'getData_project') {
        $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $searchValue = isset($_POST['search']['value']) ? $conn->real_escape_string($_POST['search']['value']) : '';

        $orderIndex = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
        $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';

        $columns = ['project_id'];

        $whereClause = "del = 0";

        if (!empty($searchValue)) {
            $whereClause .= " AND (subject_project LIKE '%$searchValue%' OR subject_project_en LIKE '%$searchValue%' OR subject_project_cn LIKE '%$searchValue%' OR subject_project_jp LIKE '%$searchValue%' OR subject_project_kr LIKE '%$searchValue%')";
        }

        $orderBy = $columns[$orderIndex] . " " . $orderDir;

        $dataQuery = "SELECT project_id, subject_project, subject_project_en, subject_project_cn, subject_project_jp, subject_project_kr, date_create FROM dn_project 
                WHERE $whereClause
                ORDER BY $orderBy
                LIMIT $start, $length";

        $dataResult = $conn->query($dataQuery);
        $data = [];
        while ($row = $dataResult->fetch_assoc()) {
            $data[] = $row;
        }

        $Index = 'project_id';
        $totalRecords = getTotalRecords($conn, 'dn_project', $Index);
        $totalFiltered = getFilteredRecordsCount($conn, 'dn_project', $whereClause, $Index);

        $response = [
            "draw" => intval($draw),
            "recordsTotal" => intval($totalRecords),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data
        ];
    }
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
}

if (isset($stmt)) {
    $stmt->close();
}
$conn->close();

echo json_encode($response);
?>