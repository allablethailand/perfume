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

/**
 * ฟังก์ชันใหม่: ใช้สำหรับค้นหาและแทนที่ src="" หรือ src ที่ใช้ data-filename ใน blog_content 
 * ให้เป็น URL รูปภาพที่ถูกต้องโดยใช้ api_path
 * @param string $content เนื้อหา HTML ของบล็อก
 * @param array $fileInfos ข้อมูลไฟล์ที่อัปโหลดสำเร็จในรอบนี้ (จาก handleFileUpload)
 * @param string $base_path Base URL ของเว็บไซต์ (จาก global $base_path)
 * @return string เนื้อหา HTML ที่แก้ไข src ของรูปภาพแล้ว
 */
function replaceImageSrc($content, $fileInfos, $base_path) {
    // สร้าง array map ของ filename -> full_url
    $fileMap = [];
    foreach ($fileInfos as $info) {
        if ($info['success']) {
            // ใช้ $base_path + path สัมพัทธ์บนเว็บ เพื่อให้เป็น URL ที่ถูกต้อง
            // $picPath ที่บันทึกในโค้ดก่อนหน้าคือ $base_path . '/public/news_img/' . $fileInfo['fileName'];
            $fileMap[$info['fileName']] = $base_path . '/public/news_img/' . $info['fileName'];
        }
    }

    if (empty($fileMap)) {
        return $content; // ไม่มีไฟล์ที่อัปโหลด ไม่ต้องทำอะไร
    }

    // ใช้ Regular Expression เพื่อค้นหาแท็ก <img>
    // ค้นหา: <img... src="" data-filename="[ชื่อไฟล์]" ...>
    // หรือ: <img... data-filename="[ชื่อไฟล์]" ...>
    // และแทนที่ src="" ด้วย src="https://dict.longdo.com/search/%E0%B9%80%E0%B8%95%E0%B9%87%E0%B8%A1"

    // Pattern: 
    // 1. ค้นหาแท็ก img
    // 2. จับทุก attribute ก่อน data-filename (กลุ่ม 1)
    // 3. จับ data-filename="[ชื่อไฟล์]" (กลุ่ม 2)
    // 4. จับทุก attribute หลัง data-filename (กลุ่ม 3)
    
    // The (?s) flag allows the dot to match newlines (single-line mode for the whole string)
    // The 'i' flag makes it case-insensitive
    // Regex for finding <img> tags that have data-filename or src="" with data-filename.
    $pattern = '/<img\s[^>]*?(src\s*=\s*(["\']).*?\2\s*|)([^>]*)data-filename\s*=\s*(["\'])(.*?)\4([^>]*?)>/si';

    $content = preg_replace_callback($pattern, function($matches) use ($fileMap) {
        $full_tag = $matches[0];
        $src_attribute = $matches[1]; // อาจจะเป็น src="" หรือไม่มีเลย
        $filename_with_quotes = $matches[4] . $matches[5] . $matches[4]; // ชื่อไฟล์พร้อม quote
        $filename = $matches[5]; // ชื่อไฟล์จริง
        
        // ตรวจสอบว่าชื่อไฟล์นี้อยู่ในไฟล์ที่เพิ่งอัปโหลดหรือไม่
        if (isset($fileMap[$filename])) {
            $new_src = $fileMap[$filename];
            
            // ลบ src="" หรือ src="base64..." ที่อาจมีอยู่แล้วออกไปก่อน
            $updated_tag = preg_replace('/src\s*=\s*("|\').*?\1\s*/i', '', $full_tag);
            
            // ลบ data-filename ออกเพื่อความสะอาด
            $updated_tag = preg_replace('/data-filename\s*=\s*("|\').*?\1\s*/i', '', $updated_tag);

            // ใส่ src ใหม่เข้าไป
            // ต้องหาตำแหน่งที่เหมาะสมเพื่อใส่ src ใหม่
            // เราจะใช้วิธีลบ > สุดท้ายออก แล้วใส่ src และ > กลับเข้าไป
            $updated_tag = rtrim($updated_tag, '>'); // ลบ >
            $updated_tag = trim($updated_tag) . ' src="' . $new_src . '">'; // เพิ่ม src และ >

            return $updated_tag;
        }

        return $full_tag; // ถ้าไม่พบชื่อไฟล์นี้ในไฟล์ที่เพิ่งอัปโหลด ให้คงเดิม
    }, $content);
    
    return $content;
}


$response = array('status' => 'error', 'message' => '');

try {
    if (isset($_POST['action']) && $_POST['action'] == 'addblog') {
        $blog_array = [
            'blog_subject' => $_POST['blog_subject'] ?? '',
            'blog_description' => $_POST['blog_description'] ?? '',
            'blog_content' => $_POST['blog_content'] ?? '',
            'blog_subject_en' => $_POST['blog_subject_en'] ?? '',
            'blog_description_en' => $_POST['blog_description_en'] ?? '',
            'blog_content_en' => $_POST['blog_content_en'] ?? '',
            'blog_subject_cn' => $_POST['blog_subject_cn'] ?? '',
            'blog_description_cn' => $_POST['blog_description_cn'] ?? '',
            'blog_content_cn' => $_POST['blog_content_cn'] ?? '',
            'blog_subject_jp' => $_POST['blog_subject_jp'] ?? '',
            'blog_description_jp' => $_POST['blog_description_jp'] ?? '',
            'blog_content_jp' => $_POST['blog_content_jp'] ?? '',
            'blog_subject_kr' => $_POST['blog_subject_kr'] ?? '',
            'blog_description_kr' => $_POST['blog_description_kr'] ?? '',
            'blog_content_kr' => $_POST['blog_content_kr'] ?? '',
        ];
        
        $related_projects = $_POST['related_projects'] ?? [];
        $contentImageFiles = []; // Array สำหรับเก็บข้อมูลไฟล์ที่อัปโหลดสำหรับ Content ทุกภาษา

        // จัดการรูปภาพใน Content (จาก editor) ก่อน
        if (isset($_FILES['image_files']) && $_FILES['image_files']['error'][0] !== UPLOAD_ERR_NO_FILE) {
            $fileInfos = handleFileUpload($_FILES['image_files']);
            $contentImageFiles = array_merge($contentImageFiles, $fileInfos);
            // บันทึกข้อมูลรูปภาพลง dn_blog_doc ในภายหลังพร้อม blog_id
        }

        if (isset($blog_array)) {
            // ************ ส่วนที่แก้ไข: อัปเดต src ใน content ก่อนบันทึก ************
            $blog_content = mb_convert_encoding($blog_array['blog_content'], 'UTF-8', 'auto');
            $blog_content_en = mb_convert_encoding($blog_array['blog_content_en'], 'UTF-8', 'auto');
            $blog_content_cn = mb_convert_encoding($blog_array['blog_content_cn'], 'UTF-8', 'auto');
            $blog_content_jp = mb_convert_encoding($blog_array['blog_content_jp'], 'UTF-8', 'auto');
            $blog_content_kr = mb_convert_encoding($blog_array['blog_content_kr'], 'UTF-8', 'auto');
            
            // แทนที่ src ใน content
            $blog_content = replaceImageSrc($blog_content, $contentImageFiles, $base_path);
            $blog_content_en = replaceImageSrc($blog_content_en, $contentImageFiles, $base_path);
            $blog_content_cn = replaceImageSrc($blog_content_cn, $contentImageFiles, $base_path);
            $blog_content_jp = replaceImageSrc($blog_content_jp, $contentImageFiles, $base_path);
            $blog_content_kr = replaceImageSrc($blog_content_kr, $contentImageFiles, $base_path);
            // *******************************************************************
            
            $stmt = $conn->prepare("INSERT INTO dn_blog 
                (subject_blog, description_blog, content_blog, subject_blog_en, description_blog_en, content_blog_en, subject_blog_cn, description_blog_cn, content_blog_cn, subject_blog_jp, description_blog_jp, content_blog_jp, subject_blog_kr, description_blog_kr, content_blog_kr, date_create) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $blog_subject = $blog_array['blog_subject'];
            $blog_description = $blog_array['blog_description'];
            $blog_subject_en = $blog_array['blog_subject_en'];
            $blog_description_en = $blog_array['blog_description_en'];
            $blog_subject_cn = $blog_array['blog_subject_cn'];
            $blog_description_cn = $blog_array['blog_description_cn'];
            $blog_subject_jp = $blog_array['blog_subject_jp'];
            $blog_description_jp = $blog_array['blog_description_jp'];
            $blog_subject_kr = $blog_array['blog_subject_kr'];
            $blog_description_kr = $blog_array['blog_description_kr'];
            $current_date = date('Y-m-d H:i:s');

            $stmt->bind_param(
                "ssssssssssssssss",
                $blog_subject,
                $blog_description,
                $blog_content,
                $blog_subject_en,
                $blog_description_en,
                $blog_content_en,
                $blog_subject_cn,
                $blog_description_cn,
                $blog_content_cn,
                $blog_subject_jp,
                $blog_description_jp,
                $blog_content_jp,
                $blog_subject_kr,
                $blog_description_kr,
                $blog_content_kr,
                $current_date
            );

            if (!$stmt->execute()) {
                throw new Exception("Execute statement failed: " . $stmt->error);
            }
            $last_inserted_id = $conn->insert_id;
            
            if (!empty($related_projects)) {
                $stmt_project_insert = $conn->prepare("INSERT INTO dn_blog_project (blog_id, project_id) VALUES (?, ?)");
                foreach ($related_projects as $project_id) {
                    $stmt_project_insert->bind_param("ii", $last_inserted_id, $project_id);
                    $stmt_project_insert->execute();
                }
                $stmt_project_insert->close();
            }

            // จัดการไฟล์ Cover Photo (fileInput)
            if (isset($_FILES['fileInput']) && $_FILES['fileInput']['error'][0] !== UPLOAD_ERR_NO_FILE) {
                $fileInfos = handleFileUpload($_FILES['fileInput']);
                foreach ($fileInfos as $fileInfo) {
                    if ($fileInfo['success']) {
                        $picPath = $base_path . '/public/news_img/' . $fileInfo['fileName'];
                        $fileColumns = ['blog_id', 'file_name', 'file_size', 'file_type', 'file_path', 'api_path', 'status'];
                        $fileValues = [$last_inserted_id, $fileInfo['fileName'], $fileInfo['fileSize'], $fileInfo['fileType'], $fileInfo['filePath'], $picPath, 1];
                        insertIntoDatabase($conn, 'dn_blog_doc', $fileColumns, $fileValues);
                    } else {
                        throw new Exception('Error uploading file: ' . ($fileInfo['fileName'] ?? 'unknown') . ' - ' . $fileInfo['error']);
                    }
                }
            }
            
            // บันทึกไฟล์รูปภาพใน Content (image_files)
            foreach ($contentImageFiles as $fileInfo) {
                if ($fileInfo['success']) {
                    $picPath = $base_path . '/public/news_img/' . $fileInfo['fileName'];
                    $fileColumns = ['blog_id', 'file_name', 'file_size', 'file_type', 'file_path', 'api_path'];
                    $fileValues = [$last_inserted_id, $fileInfo['fileName'], $fileInfo['fileSize'], $fileInfo['fileType'], $fileInfo['filePath'], $picPath];
                    insertIntoDatabase($conn, 'dn_blog_doc', $fileColumns, $fileValues);
                } else {
                    // ข้อผิดพลาดในการอัปโหลดไฟล์ Content จะถูกจัดการใน handleFileUpload แล้ว
                    // แต่ควรเก็บ error นี้ไว้ถ้าต้องการ debug
                }
            }
            
            $response = array('status' => 'success', 'message' => 'save');
        }

    } elseif (isset($_POST['action']) && $_POST['action'] == 'editblog') {
        $blog_array = [
            'blog_id' => $_POST['blog_id'] ?? '',
            'blog_subject' => $_POST['blog_subject'] ?? '',
            'blog_description' => $_POST['blog_description'] ?? '',
            'blog_content' => $_POST['blog_content'] ?? '',
            'blog_subject_en' => $_POST['blog_subject_en'] ?? '',
            'blog_description_en' => $_POST['blog_description_en'] ?? '',
            'blog_content_en' => $_POST['blog_content_en'] ?? '',
            'blog_subject_cn' => $_POST['blog_subject_cn'] ?? '',
            'blog_description_cn' => $_POST['blog_description_cn'] ?? '',
            'blog_content_cn' => $_POST['blog_content_cn'] ?? '',
            'blog_subject_jp' => $_POST['blog_subject_jp'] ?? '',
            'blog_description_jp' => $_POST['blog_description_jp'] ?? '',
            'blog_content_jp' => $_POST['blog_content_jp'] ?? '',
            'blog_subject_kr' => $_POST['blog_subject_kr'] ?? '',
            'blog_description_kr' => $_POST['blog_description_kr'] ?? '',
            'blog_content_kr' => $_POST['blog_content_kr'] ?? '',
        ];

        $related_projects = $_POST['related_projects'] ?? [];
        $blog_id = $blog_array['blog_id'];
        $all_content_image_files = []; // Array สำหรับเก็บข้อมูลไฟล์ที่อัปโหลดสำเร็จสำหรับ Content ทุกภาษา

        if (!empty($blog_id)) {
            
            // จัดการรูปภาพใน Content ทุกภาษา
            // TH
            if (isset($_FILES['image_files_th']) && is_array($_FILES['image_files_th']['name']) && $_FILES['image_files_th']['error'][0] !== UPLOAD_ERR_NO_FILE) {
                $fileInfos = handleFileUpload($_FILES['image_files_th']);
                $all_content_image_files = array_merge($all_content_image_files, $fileInfos);
                foreach ($fileInfos as $fileInfo) {
                    if ($fileInfo['success']) {
                        $picPath = $base_path . '/public/news_img/' . $fileInfo['fileName'];
                        $fileColumns = ['blog_id', 'file_name', 'file_size', 'file_type', 'file_path', 'api_path'];
                        $fileValues = [$blog_id, $fileInfo['fileName'], $fileInfo['fileSize'], $fileInfo['fileType'], $fileInfo['filePath'], $picPath];
                        insertIntoDatabase($conn, 'dn_blog_doc', $fileColumns, $fileValues);
                    } else {
                        throw new Exception('Error uploading content file (TH): ' . ($fileInfo['fileName'] ?? 'unknown') . ' - ' . $fileInfo['error']);
                    }
                }
            }

            // EN
            if (isset($_FILES['image_files_en']) && is_array($_FILES['image_files_en']['name']) && $_FILES['image_files_en']['error'][0] !== UPLOAD_ERR_NO_FILE) {
                $fileInfos = handleFileUpload($_FILES['image_files_en']);
                $all_content_image_files = array_merge($all_content_image_files, $fileInfos);
                foreach ($fileInfos as $fileInfo) {
                    if ($fileInfo['success']) {
                        $picPath = $base_path . '/public/news_img/' . $fileInfo['fileName'];
                        $fileColumns = ['blog_id', 'file_name', 'file_size', 'file_type', 'file_path', 'api_path', 'lang_tag'];
                        $fileValues = [$blog_id, $fileInfo['fileName'], $fileInfo['fileSize'], $fileInfo['fileType'], $fileInfo['filePath'], $picPath, 'en'];
                        insertIntoDatabase($conn, 'dn_blog_doc', $fileColumns, $fileValues);
                    } else {
                        throw new Exception('Error uploading content file (EN): ' . ($fileInfo['fileName'] ?? 'unknown') . ' - ' . $fileInfo['error']);
                    }
                }
            }

            // CN
            if (isset($_FILES['image_files_cn']) && is_array($_FILES['image_files_cn']['name']) && $_FILES['image_files_cn']['error'][0] !== UPLOAD_ERR_NO_FILE) {
                $fileInfos = handleFileUpload($_FILES['image_files_cn']);
                $all_content_image_files = array_merge($all_content_image_files, $fileInfos);
                foreach ($fileInfos as $fileInfo) {
                    if ($fileInfo['success']) {
                        $picPath = $base_path . '/public/news_img/' . $fileInfo['fileName'];
                        $fileColumns = ['blog_id', 'file_name', 'file_size', 'file_type', 'file_path', 'api_path', 'lang_tag'];
                        $fileValues = [$blog_id, $fileInfo['fileName'], $fileInfo['fileSize'], $fileInfo['fileType'], $fileInfo['filePath'], $picPath, 'cn'];
                        insertIntoDatabase($conn, 'dn_blog_doc', $fileColumns, $fileValues);
                    } else {
                        throw new Exception('Error uploading content file (CN): ' . ($fileInfo['fileName'] ?? 'unknown') . ' - ' . $fileInfo['error']);
                    }
                }
            }
            
            // JP
            if (isset($_FILES['image_files_jp']) && is_array($_FILES['image_files_jp']['name']) && $_FILES['image_files_jp']['error'][0] !== UPLOAD_ERR_NO_FILE) {
                $fileInfos = handleFileUpload($_FILES['image_files_jp']);
                $all_content_image_files = array_merge($all_content_image_files, $fileInfos);
                foreach ($fileInfos as $fileInfo) {
                    if ($fileInfo['success']) {
                        $picPath = $base_path . '/public/news_img/' . $fileInfo['fileName'];
                        $fileColumns = ['blog_id', 'file_name', 'file_size', 'file_type', 'file_path', 'api_path', 'lang_tag'];
                        $fileValues = [$blog_id, $fileInfo['fileName'], $fileInfo['fileSize'], $fileInfo['fileType'], $fileInfo['filePath'], $picPath, 'jp'];
                        insertIntoDatabase($conn, 'dn_blog_doc', $fileColumns, $fileValues);
                    } else {
                        throw new Exception('Error uploading content file (JP): ' . ($fileInfo['fileName'] ?? 'unknown') . ' - ' . $fileInfo['error']);
                    }
                }
            }

            // KR
            if (isset($_FILES['image_files_kr']) && is_array($_FILES['image_files_kr']['name']) && $_FILES['image_files_kr']['error'][0] !== UPLOAD_ERR_NO_FILE) {
                $fileInfos = handleFileUpload($_FILES['image_files_kr']);
                $all_content_image_files = array_merge($all_content_image_files, $fileInfos);
                foreach ($fileInfos as $fileInfo) {
                    if ($fileInfo['success']) {
                        $picPath = $base_path . '/public/news_img/' . $fileInfo['fileName'];
                        $fileColumns = ['blog_id', 'file_name', 'file_size', 'file_type', 'file_path', 'api_path', 'lang_tag'];
                        $fileValues = [$blog_id, $fileInfo['fileName'], $fileInfo['fileSize'], $fileInfo['fileType'], $fileInfo['filePath'], $picPath, 'kr'];
                        insertIntoDatabase($conn, 'dn_blog_doc', $fileColumns, $fileValues);
                    } else {
                        throw new Exception('Error uploading content file (KR): ' . ($fileInfo['fileName'] ?? 'unknown') . ' - ' . $fileInfo['error']);
                    }
                }
            }

            // ************ ส่วนที่แก้ไข: อัปเดต src ใน content ก่อนบันทึก ************
            // ดึงข้อมูล Content และแทนที่ src
            $blog_content = mb_convert_encoding($blog_array['blog_content'], 'UTF-8', 'auto');
            $blog_content_en = mb_convert_encoding($blog_array['blog_content_en'] ?? '', 'UTF-8', 'auto');
            $blog_content_cn = mb_convert_encoding($blog_array['blog_content_cn'] ?? '', 'UTF-8', 'auto');
            $blog_content_jp = mb_convert_encoding($blog_array['blog_content_jp'] ?? '', 'UTF-8', 'auto');
            $blog_content_kr = mb_convert_encoding($blog_array['blog_content_kr'] ?? '', 'UTF-8', 'auto');
            
            $blog_content = replaceImageSrc($blog_content, $all_content_image_files, $base_path);
            $blog_content_en = replaceImageSrc($blog_content_en, $all_content_image_files, $base_path);
            $blog_content_cn = replaceImageSrc($blog_content_cn, $all_content_image_files, $base_path);
            $blog_content_jp = replaceImageSrc($blog_content_jp, $all_content_image_files, $base_path);
            $blog_content_kr = replaceImageSrc($blog_content_kr, $all_content_image_files, $base_path);
            // *******************************************************************
            
            $stmt = $conn->prepare("UPDATE dn_blog 
            SET subject_blog = ?, 
            description_blog = ?, 
            content_blog = ?,
            subject_blog_en = ?,
            description_blog_en = ?,
            content_blog_en = ?,
            subject_blog_cn = ?,
            description_blog_cn = ?,
            content_blog_cn = ?,
            subject_blog_jp = ?,
            description_blog_jp = ?,
            content_blog_jp = ?,
            subject_blog_kr = ?,
            description_blog_kr = ?,
            content_blog_kr = ?,
            date_create = ? 
            WHERE blog_id = ?");

            $blog_subject = $blog_array['blog_subject'];
            $blog_description = $blog_array['blog_description'];
            $blog_subject_en = $blog_array['blog_subject_en'] ?? '';
            $blog_description_en = $blog_array['blog_description_en'] ?? '';
            $blog_subject_cn = $blog_array['blog_subject_cn'] ?? '';
            $blog_description_cn = $blog_array['blog_description_cn'] ?? '';
            $blog_subject_jp = $blog_array['blog_subject_jp'] ?? '';
            $blog_description_jp = $blog_array['blog_description_jp'] ?? '';
            $blog_subject_kr = $blog_array['blog_subject_kr'] ?? '';
            $blog_description_kr = $blog_array['blog_description_kr'] ?? '';
            $current_date = date('Y-m-d H:i:s');
            

            $stmt->bind_param(
                "ssssssssssssssssi",
                $blog_subject,
                $blog_description,
                $blog_content, // ใช้ค่าที่ถูก replace แล้ว
                $blog_subject_en,
                $blog_description_en,
                $blog_content_en, // ใช้ค่าที่ถูก replace แล้ว
                $blog_subject_cn,
                $blog_description_cn,
                $blog_content_cn, // ใช้ค่าที่ถูก replace แล้ว
                $blog_subject_jp,
                $blog_description_jp,
                $blog_content_jp, // ใช้ค่าที่ถูก replace แล้ว
                $blog_subject_kr,
                $blog_description_kr,
                $blog_content_kr, // ใช้ค่าที่ถูก replace แล้ว
                $current_date,
                $blog_id
            );

            if (!$stmt->execute()) {
                throw new Exception("Execute statement failed: " . $stmt->error);
            }
            
            $stmt_delete_projects = $conn->prepare("DELETE FROM dn_blog_project WHERE blog_id = ?");
            $stmt_delete_projects->bind_param("i", $blog_id);
            $stmt_delete_projects->execute();
            $stmt_delete_projects->close();

            if (!empty($related_projects)) {
                $stmt_project_insert = $conn->prepare("INSERT INTO dn_blog_project (blog_id, project_id) VALUES (?, ?)");
                foreach ($related_projects as $project_id) {
                    $stmt_project_insert->bind_param("ii", $blog_id, $project_id);
                    $stmt_project_insert->execute();
                }
                $stmt_project_insert->close();
            }

            // จัดการรูป Cover Photo (fileInput)
            if (isset($_FILES['fileInput']) && $_FILES['fileInput']['error'] == UPLOAD_ERR_OK) {
                // 1. ดึง path ของรูปเก่าเพื่อลบ
                $getOldCoverStmt = $conn->prepare("SELECT file_path FROM dn_blog_doc WHERE blog_id = ? AND status = 1 AND del = 0");
                if ($getOldCoverStmt) {
                    $getOldCoverStmt->bind_param("i", $blog_id);
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
                    $checkExistingCoverStmt = $conn->prepare("SELECT COUNT(*) FROM dn_blog_doc WHERE blog_id = ? AND status = 1 AND del = 0");
                    $checkExistingCoverStmt->bind_param("i", $blog_id);
                    $checkExistingCoverStmt->execute();
                    $existingCount = $checkExistingCoverStmt->get_result()->fetch_row()[0];
                    $checkExistingCoverStmt->close();

                    if ($existingCount > 0) {
                        // 5. ถ้ามีอยู่แล้ว ให้อัปเดตข้อมูล
                        $updateCoverStmt = $conn->prepare("UPDATE dn_blog_doc
                            SET file_name = ?, file_size = ?, file_type = ?, file_path = ?, api_path = ?
                            WHERE blog_id = ? AND status = 1 AND del = 0");
                        if ($updateCoverStmt) {
                            $updateCoverStmt->bind_param(
                                "sisssi",
                                $fileInfo['fileName'],
                                $fileInfo['fileSize'],
                                $fileInfo['fileType'],
                                $fileInfo['filePath'],
                                $picPath,
                                $blog_id
                            );
                            $updateCoverStmt->execute();
                            $updateCoverStmt->close();
                        }
                    } else {
                        // 6. ถ้าไม่มี ให้แทรกข้อมูลใหม่
                        $fileColumns = ['blog_id', 'file_name', 'file_size', 'file_type', 'file_path', 'api_path', 'status'];
                        $fileValues = [$blog_id, $fileInfo['fileName'], $fileInfo['fileSize'], $fileInfo['fileType'], $fileInfo['filePath'], $picPath, 1];
                        insertIntoDatabase($conn, 'dn_blog_doc', $fileColumns, $fileValues);
                    }
                    
                } else {
                    throw new Exception('Error uploading cover file: ' . ($fileInfo['fileName'] ?? 'unknown') . ' - ' . $fileInfo['error']);
                }
            }


            $response = array('status' => 'success', 'message' => 'edit save');
        }

    } elseif (isset($_POST['action']) && $_POST['action'] == 'delblog') {
        $blog_id = $_POST['id'] ?? '';
        $del = '1';
        
        $stmt = $conn->prepare("UPDATE dn_blog 
            SET del = ? 
            WHERE blog_id = ?");
        $stmt->bind_param("si", $del, $blog_id);
        if (!$stmt->execute()) {
            throw new Exception("Execute statement failed: " . $stmt->error);
        }
        $stmt->close();
        
        $stmt = $conn->prepare("UPDATE dn_blog_doc 
            SET del = ? 
            WHERE blog_id = ?");
        $stmt->bind_param("si", $del, $blog_id);
        if (!$stmt->execute()) {
            throw new Exception("Execute statement failed: " . $stmt->error);
        }
        $stmt->close();

        $stmt = $conn->prepare("UPDATE dn_blog_project 
            SET del = ? 
            WHERE blog_id = ?");
        $stmt->bind_param("si", $del, $blog_id);
        if (!$stmt->execute()) {
            throw new Exception("Execute statement failed: " . $stmt->error);
        }
        $stmt->close();
        
        $response = array('status' => 'success', 'message' => 'Delete');
        
    } elseif (isset($_POST['action']) && $_POST['action'] == 'getData_blog') {
        $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $searchValue = isset($_POST['search']['value']) ? $conn->real_escape_string($_POST['search']['value']) : '';

        $orderIndex = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
        $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';

        $columns = ['blog_id'];

        $whereClause = "del = 0";

        if (!empty($searchValue)) {
            $whereClause .= " AND (subject_blog LIKE '%$searchValue%' OR subject_blog_en LIKE '%$searchValue%' OR subject_blog_cn LIKE '%$searchValue%' OR subject_blog_jp LIKE '%$searchValue%' OR subject_blog_kr LIKE '%$searchValue%')";
        }

        $orderBy = $columns[$orderIndex] . " " . $orderDir;

        $dataQuery = "SELECT blog_id, subject_blog, date_create FROM dn_blog 
                    WHERE $whereClause
                    ORDER BY $orderBy
                    LIMIT $start, $length";

        $dataResult = $conn->query($dataQuery);
        $data = [];
        while ($row = $dataResult->fetch_assoc()) {
            $data[] = $row;
        }

        $Index = 'blog_id';
        $totalRecords = getTotalRecords($conn, 'dn_blog', $Index);
        $totalFiltered = getFilteredRecordsCount($conn, 'dn_blog', $whereClause, $Index);

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