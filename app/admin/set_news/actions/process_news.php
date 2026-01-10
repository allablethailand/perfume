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

/**
 * ฟังก์ชันสำหรับประมวลผล Content เพื่อแทนที่ src="" ด้วย URL รูปภาพที่ถูกต้อง
 * @param string $content เนื้อหา HTML
 * @param string $base_url ฐาน URL สำหรับรูปภาพ (เช่น https://www.perfume.com/)
 * @return string เนื้อหา HTML ที่ถูกแก้ไข src แล้ว
 */
function processNewsContent($content, $base_url)
{
    // ตรวจสอบให้แน่ใจว่า $base_url มี / ปิดท้าย
    if (substr($base_url, -1) !== '/') {
        $base_url .= '/';
    }
    
    // Path ที่ใช้สำหรับเก็บรูปภาพข่าว (ต้องตรงกับที่ใช้ใน handleFileUpload)
    // ใน handleFileUpload ใช้ '../../../../public/news_img/' 
    // ดังนั้น URL Public Path ควรจะเป็น '/public/news_img/'
    $image_public_path = 'public/news_img/';
    
    // Regular Expression เพื่อค้นหาแท็ก <img> ที่มี data-filename และ src เป็นค่าว่าง/ไม่มี
    // รูปแบบที่ต้องการแก้ไข: <img src="" data-filename="4.png" ...>
    // หรือ: <img data-filename="4.png" ...>
    $pattern = '/<img\s+(?:src=["\']\s*["\']\s*)?(?:[^>]*?)data-filename=["\']([^"\']+\.(?:png|jpg|jpeg|gif))["\'](?:[^>]*?)\s*\/?>/i';

    // ฟังก์ชันสำหรับแทนที่
    $replacement = function($matches) use ($base_url, $image_public_path) {
        $full_image_url = $base_url . $image_public_path . $matches[1];
        // แทนที่ด้วยแท็ก <img> ใหม่ที่มี src ถูกต้อง
        $new_img_tag = str_replace($matches[0], '<img src="' . htmlspecialchars($full_image_url) . '" data-filename="' . htmlspecialchars($matches[1]) . '"', $matches[0]);

        // หากไม่มี src เดิมเลย จะเพิ่ม src เข้าไป
        if (strpos($matches[0], 'src=') === false || preg_match('/src=["\']\s*["\']/i', $matches[0])) {
            return preg_replace('/<img\s+/', '<img src="' . htmlspecialchars($full_image_url) . '" ', $matches[0], 1);
        }
        
        // ถ้ามี src="" อยู่แล้ว ก็แทนที่ค่าว่างใน src
        return preg_replace('/src=["\']\s*["\']/i', 'src="' . htmlspecialchars($full_image_url) . '"', $matches[0]);
    };

    // ใช้ preg_replace_callback เพื่อแทนที่ใน Content
    $processed_content = preg_replace_callback($pattern, $replacement, $content);

    return $processed_content;
}


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
    if (isset($_POST['action']) && $_POST['action'] == 'addnews') {
        $news_array = [
            'news_subject' => $_POST['news_subject'] ?? '',
            'news_description' => $_POST['news_description'] ?? '',
            'news_content' => $_POST['news_content'] ?? '',
            'news_subject_en' => $_POST['news_subject_en'] ?? '',
            'news_description_en' => $_POST['news_description_en'] ?? '',
            'news_content_en' => $_POST['news_content_en'] ?? '',
            'news_subject_cn' => $_POST['news_subject_cn'] ?? '',
            'news_description_cn' => $_POST['news_description_cn'] ?? '',
            'news_content_cn' => $_POST['news_content_cn'] ?? '',
            'news_subject_jp' => $_POST['news_subject_jp'] ?? '',
            'news_description_jp' => $_POST['news_description_jp'] ?? '',
            'news_content_jp' => $_POST['news_content_jp'] ?? '',
            'news_subject_kr' => $_POST['news_subject_kr'] ?? '',
            'news_description_kr' => $_POST['news_description_kr'] ?? '',
            'news_content_kr' => $_POST['news_content_kr'] ?? '',
        ];

        if (isset($news_array)) {
            $stmt = $conn->prepare("INSERT INTO dn_news 
                (subject_news, description_news, content_news, subject_news_en, description_news_en, content_news_en, subject_news_cn, description_news_cn, content_news_cn, subject_news_jp, description_news_jp, content_news_jp, subject_news_kr, description_news_kr, content_news_kr, date_create) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $news_subject = $news_array['news_subject'];
            $news_description = $news_array['news_description'];
            // 👇 แก้ไข: ประมวลผล Content เพื่อแทนที่ src รูปภาพ
            $news_content = processNewsContent($news_array['news_content'], $base_path);
            $news_content = mb_convert_encoding($news_content, 'UTF-8', 'auto');
            
            $news_subject_en = $news_array['news_subject_en'];
            $news_description_en = $news_array['news_description_en'];
            // 👇 แก้ไข: ประมวลผล Content เพื่อแทนที่ src รูปภาพ
            $news_content_en = processNewsContent($news_array['news_content_en'], $base_path);
            $news_content_en = mb_convert_encoding($news_content_en, 'UTF-8', 'auto');
            
            $news_subject_cn = $news_array['news_subject_cn'];
            $news_description_cn = $news_array['news_description_cn'];
            // 👇 แก้ไข: ประมวลผล Content เพื่อแทนที่ src รูปภาพ
            $news_content_cn = processNewsContent($news_array['news_content_cn'], $base_path);
            $news_content_cn = mb_convert_encoding($news_content_cn, 'UTF-8', 'auto');
            
            $news_subject_jp = $news_array['news_subject_jp'];
            $news_description_jp = $news_array['news_description_jp'];
            // 👇 แก้ไข: ประมวลผล Content เพื่อแทนที่ src รูปภาพ
            $news_content_jp = processNewsContent($news_array['news_content_jp'], $base_path);
            $news_content_jp = mb_convert_encoding($news_content_jp, 'UTF-8', 'auto');
            
            $news_subject_kr = $news_array['news_subject_kr'];
            $news_description_kr = $news_array['news_description_kr'];
            // 👇 แก้ไข: ประมวลผล Content เพื่อแทนที่ src รูปภาพ
            $news_content_kr = processNewsContent($news_array['news_content_kr'], $base_path);
            $news_content_kr = mb_convert_encoding($news_content_kr, 'UTF-8', 'auto');
            
            $current_date = date('Y-m-d H:i:s');

            $stmt->bind_param(
                "ssssssssssssssss",
                $news_subject,
                $news_description,
                $news_content,
                $news_subject_en,
                $news_description_en,
                $news_content_en,
                $news_subject_cn,
                $news_description_cn,
                $news_content_cn,
                $news_subject_jp,
                $news_description_jp,
                $news_content_jp,
                $news_subject_kr,
                $news_description_kr,
                $news_content_kr,
                $current_date
            );

            if (!$stmt->execute()) {
                throw new Exception("Execute statement failed: " . $stmt->error);
            }
            $last_inserted_id = $conn->insert_id;
            
            // แก้ไขการตรวจสอบไฟล์
            if (isset($_FILES['fileInput']) && $_FILES['fileInput']['error'][0] !== UPLOAD_ERR_NO_FILE) {
                $fileInfos = handleFileUpload($_FILES['fileInput']);
                foreach ($fileInfos as $fileInfo) {
                    if ($fileInfo['success']) {
                        $picPath = $base_path . '/public/news_img/' . $fileInfo['fileName'];
                        $fileColumns = ['news_id', 'file_name', 'file_size', 'file_type', 'file_path', 'api_path', 'status'];
                        $fileValues = [$last_inserted_id, $fileInfo['fileName'], $fileInfo['fileSize'], $fileInfo['fileType'], $fileInfo['filePath'], $picPath, 1];
                        insertIntoDatabase($conn, 'dn_news_doc', $fileColumns, $fileValues);
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
                        $fileColumns = ['news_id', 'file_name', 'file_size', 'file_type', 'file_path', 'api_path'];
                        $fileValues = [$last_inserted_id, $fileInfo['fileName'], $fileInfo['fileSize'], $fileInfo['fileType'], $fileInfo['filePath'], $picPath];
                        insertIntoDatabase($conn, 'dn_news_doc', $fileColumns, $fileValues);
                    } else {
                        throw new Exception('Error uploading file: ' . ($fileInfo['fileName'] ?? 'unknown') . ' - ' . $fileInfo['error']);
                    }
                }
            }
            
            $response = array('status' => 'success', 'message' => 'save');
        }

    } elseif (isset($_POST['action']) && $_POST['action'] == 'editnews') {
        $news_array = [
            'news_id' => $_POST['news_id'] ?? '',
            'news_subject' => $_POST['news_subject'] ?? '',
            'news_description' => $_POST['news_description'] ?? '',
            'news_content' => $_POST['news_content'] ?? '',
            'news_subject_en' => $_POST['news_subject_en'] ?? '',
            'news_description_en' => $_POST['news_description_en'] ?? '',
            'news_content_en' => $_POST['news_content_en'] ?? '',
            'news_subject_cn' => $_POST['news_subject_cn'] ?? '',
            'news_description_cn' => $_POST['news_description_cn'] ?? '',
            'news_content_cn' => $_POST['news_content_cn'] ?? '',
            'news_subject_jp' => $_POST['news_subject_jp'] ?? '',
            'news_description_jp' => $_POST['news_description_jp'] ?? '',
            'news_content_jp' => $_POST['news_content_jp'] ?? '',
            'news_subject_kr' => $_POST['news_subject_kr'] ?? '',
            'news_description_kr' => $_POST['news_description_kr'] ?? '',
            'news_content_kr' => $_POST['news_content_kr'] ?? '',
        ];

        if (!empty($news_array['news_id'])) {
            $stmt = $conn->prepare("UPDATE dn_news 
            SET subject_news = ?, 
            description_news = ?, 
            content_news = ?,
            subject_news_en = ?,
            description_news_en = ?,
            content_news_en = ?,
            subject_news_cn = ?,
            description_news_cn = ?,
            content_news_cn = ?,
            subject_news_jp = ?,
            description_news_jp = ?,
            content_news_jp = ?,
            subject_news_kr = ?,
            description_news_kr = ?,
            content_news_kr = ?,
            date_create = ? 
            WHERE news_id = ?");

            $news_subject = $news_array['news_subject'];
            $news_description = $news_array['news_description'];
            // 👇 แก้ไข: ประมวลผล Content เพื่อแทนที่ src รูปภาพ
            $news_content = processNewsContent($news_array['news_content'], $base_path);
            $news_content = mb_convert_encoding($news_content, 'UTF-8', 'auto');
            
            $news_subject_en = $news_array['news_subject_en'];
            $news_description_en = $news_array['news_description_en'];
            // 👇 แก้ไข: ประมวลผล Content เพื่อแทนที่ src รูปภาพ
            $news_content_en = processNewsContent($news_array['news_content_en'], $base_path);
            $news_content_en = mb_convert_encoding($news_content_en, 'UTF-8', 'auto');
            
            $news_subject_cn = $news_array['news_subject_cn'];
            $news_description_cn = $news_array['news_description_cn'];
            // 👇 แก้ไข: ประมวลผล Content เพื่อแทนที่ src รูปภาพ
            $news_content_cn = processNewsContent($news_array['news_content_cn'], $base_path);
            $news_content_cn = mb_convert_encoding($news_content_cn, 'UTF-8', 'auto');
            
            $news_subject_jp = $news_array['news_subject_jp'];
            $news_description_jp = $news_array['news_description_jp'];
            // 👇 แก้ไข: ประมวลผล Content เพื่อแทนที่ src รูปภาพ
            $news_content_jp = processNewsContent($news_array['news_content_jp'], $base_path);
            $news_content_jp = mb_convert_encoding($news_content_jp, 'UTF-8', 'auto');
            
            $news_subject_kr = $news_array['news_subject_kr'];
            $news_description_kr = $news_array['news_description_kr'];
            // 👇 แก้ไข: ประมวลผล Content เพื่อแทนที่ src รูปภาพ
            $news_content_kr = processNewsContent($news_array['news_content_kr'], $base_path);
            $news_content_kr = mb_convert_encoding($news_content_kr, 'UTF-8', 'auto');
            
            $current_date = date('Y-m-d H:i:s');
            $news_id = $news_array['news_id'];

            // แก้ไขตรงนี้: เพิ่ม 'i' สำหรับ news_id
            $stmt->bind_param(
                "ssssssssssssssssi",
                $news_subject,
                $news_description,
                $news_content,
                $news_subject_en,
                $news_description_en,
                $news_content_en,
                $news_subject_cn,
                $news_description_cn,
                $news_content_cn,
                $news_subject_jp,
                $news_description_jp,
                $news_content_jp,
                $news_subject_kr,
                $news_description_kr,
                $news_content_kr,
                $current_date,
                $news_id
            );

            if (!$stmt->execute()) {
                throw new Exception("Execute statement failed: " . $stmt->error);
            }
            
            // จัดการรูป Cover Photo
            if (isset($_FILES['fileInput']) && $_FILES['fileInput']['error'] == UPLOAD_ERR_OK) {
                // 1. ดึง path ของรูปเก่าเพื่อลบ
                $getOldCoverStmt = $conn->prepare("SELECT file_path FROM dn_news_doc WHERE news_id = ? AND status = 1 AND del = 0");
                if ($getOldCoverStmt) {
                    $getOldCoverStmt->bind_param("i", $news_id);
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
                    $checkExistingCoverStmt = $conn->prepare("SELECT COUNT(*) FROM dn_news_doc WHERE news_id = ? AND status = 1 AND del = 0");
                    $checkExistingCoverStmt->bind_param("i", $news_id);
                    $checkExistingCoverStmt->execute();
                    $existingCount = $checkExistingCoverStmt->get_result()->fetch_row()[0];
                    $checkExistingCoverStmt->close();

                    if ($existingCount > 0) {
                        // 5. ถ้ามีอยู่แล้ว ให้อัปเดตข้อมูล
                        $updateCoverStmt = $conn->prepare("UPDATE dn_news_doc
                            SET file_name = ?, file_size = ?, file_type = ?, file_path = ?, api_path = ?
                            WHERE news_id = ? AND status = 1 AND del = 0");
                        if ($updateCoverStmt) {
                            $updateCoverStmt->bind_param(
                                "sisssi",
                                $fileInfo['fileName'],
                                $fileInfo['fileSize'],
                                $fileInfo['fileType'],
                                $fileInfo['filePath'],
                                $picPath,
                                $news_id
                            );
                            $updateCoverStmt->execute();
                            $updateCoverStmt->close();
                        }
                    } else {
                        // 6. ถ้าไม่มี ให้แทรกข้อมูลใหม่
                        $fileColumns = ['news_id', 'file_name', 'file_size', 'file_type', 'file_path', 'api_path', 'status'];
                        $fileValues = [$news_id, $fileInfo['fileName'], $fileInfo['fileSize'], $fileInfo['fileType'], $fileInfo['filePath'], $picPath, 1];
                        insertIntoDatabase($conn, 'dn_news_doc', $fileColumns, $fileValues);
                    }
                    
                } else {
                    throw new Exception('Error uploading cover file: ' . ($fileInfo['fileName'] ?? 'unknown') . ' - ' . $fileInfo['error']);
                }
            }

            // จัดการรูปภาพใน Content (ภาษาไทย)
            if (isset($_FILES['image_files_th']) && is_array($_FILES['image_files_th']['name']) && $_FILES['image_files_th']['error'][0] !== UPLOAD_ERR_NO_FILE) {
                $fileInfos = handleFileUpload($_FILES['image_files_th']);
                foreach ($fileInfos as $fileInfo) {
                    if ($fileInfo['success']) {
                        $picPath = $base_path . '/public/news_img/' . $fileInfo['fileName'];
                        $fileColumns = ['news_id', 'file_name', 'file_size', 'file_type', 'file_path', 'api_path'];
                        $fileValues = [$news_id, $fileInfo['fileName'], $fileInfo['fileSize'], $fileInfo['fileType'], $fileInfo['filePath'], $picPath];
                        insertIntoDatabase($conn, 'dn_news_doc', $fileColumns, $fileValues);
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
                        $fileColumns = ['news_id', 'file_name', 'file_size', 'file_type', 'file_path', 'api_path', 'lang'];
                        $fileValues = [$news_id, $fileInfo['fileName'], $fileInfo['fileSize'], $fileInfo['fileType'], $fileInfo['filePath'], $picPath, 'en'];
                        insertIntoDatabase($conn, 'dn_news_doc', $fileColumns, $fileValues);
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
                        $fileColumns = ['news_id', 'file_name', 'file_size', 'file_type', 'file_path', 'api_path', 'lang'];
                        $fileValues = [$news_id, $fileInfo['fileName'], $fileInfo['fileSize'], $fileInfo['fileType'], $fileInfo['filePath'], $picPath, 'cn'];
                        insertIntoDatabase($conn, 'dn_news_doc', $fileColumns, $fileValues);
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
                        $fileColumns = ['news_id', 'file_name', 'file_size', 'file_type', 'file_path', 'api_path', 'lang'];
                        $fileValues = [$news_id, $fileInfo['fileName'], $fileInfo['fileSize'], $fileInfo['fileType'], $fileInfo['filePath'], $picPath, 'jp'];
                        insertIntoDatabase($conn, 'dn_news_doc', $fileColumns, $fileValues);
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
                        $fileColumns = ['news_id', 'file_name', 'file_size', 'file_type', 'file_path', 'api_path', 'lang'];
                        $fileValues = [$news_id, $fileInfo['fileName'], $fileInfo['fileSize'], $fileInfo['fileType'], $fileInfo['filePath'], $picPath, 'kr'];
                        insertIntoDatabase($conn, 'dn_news_doc', $fileColumns, $fileValues);
                    } else {
                        throw new Exception('Error uploading content file (KR): ' . ($fileInfo['fileName'] ?? 'unknown') . ' - ' . $fileInfo['error']);
                    }
                }
            }


            $response = array('status' => 'success', 'message' => 'edit save');
        }

    } elseif (isset($_POST['action']) && $_POST['action'] == 'delnews') {
        $news_id = $_POST['id'] ?? '';
        $del = '1';
        
        $stmt = $conn->prepare("UPDATE dn_news 
            SET del = ? 
            WHERE news_id = ?");
        $stmt->bind_param("si", $del, $news_id);
        if (!$stmt->execute()) {
            throw new Exception("Execute statement failed: " . $stmt->error);
        }
        $stmt->close();
        
        $stmt = $conn->prepare("UPDATE dn_news_doc 
            SET del = ? 
            WHERE news_id = ?");
        $stmt->bind_param("si", $del, $news_id);
        if (!$stmt->execute()) {
            throw new Exception("Execute statement failed: " . $stmt->error);
        }
        $stmt->close();
        
        $response = array('status' => 'success', 'message' => 'Delete');
        
    } elseif (isset($_POST['action']) && $_POST['action'] == 'getData_news') {
        $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $searchValue = isset($_POST['search']['value']) ? $conn->real_escape_string($_POST['search']['value']) : '';

        $orderIndex = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
        $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';

        $columns = ['news_id'];

        $whereClause = "del = 0";

        if (!empty($searchValue)) {
            $whereClause .= " AND (subject_news LIKE '%$searchValue%' OR subject_news_en LIKE '%$searchValue%' OR subject_news_cn LIKE '%$searchValue%' OR subject_news_jp LIKE '%$searchValue%' OR subject_news_kr LIKE '%$searchValue%')";
        }

        $orderBy = $columns[$orderIndex] . " " . $orderDir;

        $dataQuery = "SELECT news_id, subject_news, date_create FROM dn_news 
                        WHERE $whereClause
                        ORDER BY $orderBy
                        LIMIT $start, $length";

        $dataResult = $conn->query($dataQuery);
        $data = [];
        while ($row = $dataResult->fetch_assoc()) {
            $data[] = $row;
        }

        // ต้องมีการเรียก getTotalRecords และ getFilteredRecordsCount แต่ไม่ได้ให้โค้ดฟังก์ชันมา
        // ถ้าฟังก์ชันเหล่านี้มีอยู่แล้วใน inc/getFunctions.php ก็สามารถใช้ต่อได้
        // สมมติว่ามี
        $Index = 'news_id';
        $totalRecords = getTotalRecords($conn, 'dn_news', $Index);
        $totalFiltered = getFilteredRecordsCount($conn, 'dn_news', $whereClause, $Index);

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