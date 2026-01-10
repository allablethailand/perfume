<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');
require_once(__DIR__ . '/../../../../lib/base_directory.php');
require_once(__DIR__ . '/../../../../lib/connect.php');
require_once(__DIR__ . '/../../../../inc/getFunctions.php');

global $base_path;
global $base_path_admin;
global $conn;

// Helper function to handle file uploads
function handleSingleFileUpload($file, $base_path)
{
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $maxFileSize = 5 * 1024 * 1024; // 5 MB

    if ($file['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileNameCmps = explode(".", $file['name']);
        $fileExtension = strtolower(end($fileNameCmps));
        $newFileName = time() . '-' . uniqid() . '.' . $fileExtension;

        if (in_array($fileExtension, $allowedExtensions) && $fileSize <= $maxFileSize) {
            $uploadFileDir = realpath(__DIR__ . '/../../../../public/news_img/') . '/';
            $destFilePath = $uploadFileDir . $newFileName;
            
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            if (move_uploaded_file($fileTmpPath, $destFilePath)) {
                $image_url = $base_path . '/public/news_img/' . $newFileName;
                return ['success' => true, 'url' => $image_url, 'message' => 'Image uploaded successfully.'];
            } else {
                return ['success' => false, 'error' => 'Error occurred while moving the uploaded file.'];
            }
        } else {
            return ['success' => false, 'error' => 'Invalid file type or file size exceeds limit.'];
        }
    }
    return ['success' => false, 'error' => 'No file uploaded or there was an upload error.'];
}

$response = ['status' => 'error', 'message' => 'Invalid action.'];

try {
    if (!isset($_POST['action'])) {
        throw new Exception("No action specified.");
    }

    $action = $_POST['action'];

    // Action: Upload image from Summernote
    if ($action == 'upload_image') {
        if (isset($_FILES['image_file'])) {
            $uploadResult = handleSingleFileUpload($_FILES['image_file'], $base_path);
            if ($uploadResult['success']) {
                $response = ['status' => 'success', 'url' => $uploadResult['url']];
            } else {
                $response['message'] = $uploadResult['error'];
            }
        } else {
            $response['message'] = 'No file was uploaded.';
        }
    }

    // Action: Add new content block
    elseif ($action == 'add_new_block') {
        $type_th = $_POST['type_th'] ?? '';
        $content_th = $_POST['content_th'] ?? '';
        $author_th = $_POST['author_th'] ?? '';
        $position_th = $_POST['position_th'] ?? '';
        
        $type_en = $_POST['type_en'] ?? ''; // English is optional
        $content_en = $_POST['content_en'] ?? ''; // English is optional
        $type_cn = $_POST['type_cn'] ?? ''; // Chinese is optional
        $content_cn = $_POST['content_cn'] ?? ''; // Chinese is optional
        $type_jp = $_POST['type_jp'] ?? ''; // Japanese is optional
        $content_jp = $_POST['content_jp'] ?? ''; // Japanese is optional
        $type_kr = $_POST['type_kr'] ?? ''; // Korean is optional
        $content_kr = $_POST['content_kr'] ?? ''; // Korean is optional

        $image_url = null;

        if (!empty($content_th) || !empty($content_en) || !empty($content_cn) || !empty($content_jp) || !empty($content_kr)) {
            // Handle image file if uploaded
            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == UPLOAD_ERR_OK) {
                $uploadResult = handleSingleFileUpload($_FILES['image_file'], $base_path);
                if ($uploadResult['success']) {
                    $image_url = $uploadResult['url'];
                } else {
                    $response['message'] = 'Failed to upload image: ' . $uploadResult['error'];
                    echo json_encode($response);
                    exit;
                }
            }
            
            $stmt = $conn->prepare("INSERT INTO about_content (type, content, type_en, content_en, type_cn, content_cn, type_jp, content_jp, type_kr, content_kr, image_url, author, position) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssssss", $type_th, $content_th, $type_en, $content_en, $type_cn, $content_cn, $type_jp, $content_jp, $type_kr, $content_kr, $image_url, $author_th, $position_th);

            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'เพิ่มเนื้อหาใหม่เรียบร้อย'];
            } else {
                throw new Exception("Failed to insert into database: " . $stmt->error);
            }
            $stmt->close();
        } else {
            $response['message'] = 'กรุณากรอกเนื้อหาอย่างน้อยหนึ่งภาษา';
        }
    }

    // Action: Save all content blocks
    elseif ($action == 'save_all_blocks') {
        $ids = $_POST['ids'] ?? [];
        $types_th = $_POST['types_th'] ?? [];
        $contents_th = $_POST['contents_th'] ?? [];
        $authors = $_POST['authors'] ?? [];
        $positions = $_POST['positions'] ?? [];
        $images_old = $_POST['images_old'] ?? [];
        
        $types_en = $_POST['types_en'] ?? [];
        $contents_en = $_POST['contents_en'] ?? [];
        
        $types_cn = $_POST['types_cn'] ?? [];
        $contents_cn = $_POST['contents_cn'] ?? [];
        
        $types_jp = $_POST['types_jp'] ?? [];
        $contents_jp = $_POST['contents_jp'] ?? [];

        $types_kr = $_POST['types_kr'] ?? [];
        $contents_kr = $_POST['contents_kr'] ?? [];
        
        $uploaded_files = $_FILES['image_files'] ?? null;
        
        $hasError = false;
        $errorMessage = '';

        for ($i = 0; $i < count($ids); $i++) {
            $current_image_url = $images_old[$i];
            
            if (isset($uploaded_files['name'][$i]) && $uploaded_files['error'][$i] == UPLOAD_ERR_OK) {
                $file = [
                    'name' => $uploaded_files['name'][$i],
                    'type' => $uploaded_files['type'][$i],
                    'tmp_name' => $uploaded_files['tmp_name'][$i],
                    'error' => $uploaded_files['error'][$i],
                    'size' => $uploaded_files['size'][$i]
                ];
                $uploadResult = handleSingleFileUpload($file, $base_path);
                
                if ($uploadResult['success']) {
                    $current_image_url = $uploadResult['url'];
                } else {
                    $hasError = true;
                    $errorMessage = 'Failed to upload new image for block ID ' . $ids[$i] . ': ' . $uploadResult['error'];
                    break;
                }
            }

            $type_en_val = $types_en[$i] ?? '';
            $content_en_val = $contents_en[$i] ?? '';
            $type_cn_val = $types_cn[$i] ?? '';
            $content_cn_val = $contents_cn[$i] ?? '';
            $type_jp_val = $types_jp[$i] ?? '';
            $content_jp_val = $contents_jp[$i] ?? '';
            $type_kr_val = $types_kr[$i] ?? '';
            $content_kr_val = $contents_kr[$i] ?? '';
            
            $stmt = $conn->prepare("UPDATE about_content SET type=?, content=?, type_en=?, content_en=?, type_cn=?, content_cn=?, type_jp=?, content_jp=?, type_kr=?, content_kr=?, image_url=?, author=?, position=? WHERE id=?");
            $stmt->bind_param("sssssssssssssi",
                $types_th[$i],
                $contents_th[$i],
                $type_en_val,
                $content_en_val,
                $type_cn_val,
                $content_cn_val,
                $type_jp_val,
                $content_jp_val,
                $type_kr_val,
                $content_kr_val,
                $current_image_url,
                $authors[$i],
                $positions[$i],
                $ids[$i]
            );

            if (!$stmt->execute()) {
                $hasError = true;
                $errorMessage = "Failed to update database for ID " . $ids[$i] . ": " . $stmt->error;
                break;
            }
            $stmt->close();
        }

        if ($hasError) {
            throw new Exception($errorMessage);
        } else {
            $response = ['status' => 'success', 'message' => 'บันทึกการแก้ไขทั้งหมดเรียบร้อย'];
        }
    }
    
    // Action: Delete block
    elseif ($action == 'delete_block') {
        $id = $_POST['id'] ?? 0;
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM about_content WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'ลบข้อมูลเรียบร้อยแล้ว'];
            } else {
                throw new Exception("Failed to delete from database: " . $stmt->error);
            }
            $stmt->close();
        } else {
            $response['message'] = 'ID ไม่ถูกต้อง';
        }
    }

} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>