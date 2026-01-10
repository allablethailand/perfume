<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');
require_once(__DIR__ . '/../../../../lib/base_directory.php');
require_once(__DIR__ . '/../../../../lib/connect.php');
require_once(__DIR__ . '/../../../../inc/getFunctions.php'); // ถ้ามี getFunctions.php ที่จำเป็น

global $base_path; // ต้องมั่นใจว่า $base_path ถูกกำหนดใน base_directory.php
global $base_path_admin; // อาจจะไม่จำเป็นสำหรับกรณีนี้
global $conn;

// Helper function to insert into database
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

// Helper function to update in database
function updateInDatabase($conn, $table, $columns, $values, $whereClause, $whereValues)
{
    $setPart = implode(', ', array_map(function ($col) {
        return "$col = ?";
    }, $columns));
    $query = "UPDATE $table SET $setPart WHERE $whereClause";
    $stmt = $conn->prepare($query);
    // Bind parameters
    $types = str_repeat('s', count($values)) . str_repeat('s', count($whereValues));
    $stmt->bind_param($types, ...array_merge($values, $whereValues));
    if ($stmt->execute()) {
        return 1;
    } else {
        return 0;
    }
}

// Helper function to handle file uploads
function handleFileUpload($files, $base_path) // ส่ง $base_path เข้ามาด้วย
{
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $maxFileSize = 5 * 1024 * 1024; // 5 MB

    $uploadResults = [];

    // Check if it's a single file upload or multiple
    if (isset($files['name']) && !is_array($files['name'])) { // Single file
        $files = [
            'name' => [$files['name']],
            'type' => [$files['type']],
            'tmp_name' => [$files['tmp_name']],
            'error' => [$files['error']],
            'size' => [$files['size']]
        ];
    }

    if (isset($files['name']) && is_array($files['name'])) {
        foreach ($files['name'] as $key => $fileName) {
            if ($files['error'][$key] === UPLOAD_ERR_OK) {
                $fileTmpPath = $files['tmp_name'][$key];
                $fileSize = $files['size'][$key];
                $fileType = $files['type'][$key];
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));

                if (in_array($fileExtension, $allowedExtensions) && $fileSize <= $maxFileSize) {
                    $uploadFileDir = '../../../../public/img/'; // ใช้ img folder สำหรับ banner
                    $newFileName = uniqid() . '.' . $fileExtension; // สร้างชื่อไฟล์ที่ไม่ซ้ำกัน
                    $destFilePath = $uploadFileDir . $newFileName;

                    if (!is_dir($uploadFileDir)) {
                        mkdir($uploadFileDir, 0755, true);
                    }

                    if (move_uploaded_file($fileTmpPath, $destFilePath)) {
                        $uploadResults[] = [
                            'success' => true,
                            'fileName' => $newFileName, // ชื่อไฟล์ใหม่
                            'fileSize' => $fileSize,
                            'fileType' => $fileType,
                            'filePath' => $destFilePath, // Physical path on server
                            'dbPath' => $base_path . '/public/img/' . $newFileName // URL path for database
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
                    'fileName' => $fileName, // May be empty if no file uploaded
                    'error' => 'No file uploaded or there was an upload error: ' . $files['error'][$key]
                ];
            }
        }
    } else {
        $uploadResults[] = [
            'success' => false,
            'error' => 'No files were uploaded.'
        ];
    }

    return $uploadResults;
}

$response = array('status' => 'error', 'message' => '');

try {
    // Action for setup_banner.php (add single image banner)
    if (isset($_POST['action']) && $_POST['action'] == 'addbanner_single') {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $fileInfos = handleFileUpload($_FILES['image'], $base_path); // ส่ง $base_path

            if (!empty($fileInfos) && $fileInfos[0]['success']) {
                $fileInfo = $fileInfos[0];
                $dbPath = $fileInfo['dbPath']; // ใช้ dbPath ที่ได้จาก handleFileUpload

                $stmt = $conn->prepare("INSERT INTO banner (image_path, created_at) VALUES (?, NOW())");
                $stmt->bind_param("s", $dbPath);

                if ($stmt->execute()) {
                    $response = array('status' => 'success', 'message' => 'บันทึกแบนเนอร์เรียบร้อยแล้ว');
                } else {
                    throw new Exception("บันทึกลงฐานข้อมูลล้มเหลว: " . $stmt->error);
                }
            } else {
                throw new Exception("อัปโหลดไฟล์ล้มเหลว: " . ($fileInfos[0]['error'] ?? 'Unknown error'));
            }
        } else {
            throw new Exception("ไม่พบไฟล์รูปภาพที่อัปโหลด หรือเกิดข้อผิดพลาดในการอัปโหลด.");
        }
    }
    // Action for editing a single image banner
    elseif (isset($_POST['action']) && $_POST['action'] == 'editbanner_single') {
        $bannerId = $_POST['banner_id'] ?? null;
        $oldImagePath = $_POST['old_image_path'] ?? null;

        if (!$bannerId || !is_numeric($bannerId)) {
            throw new Exception("Invalid Banner ID.");
        }

        $newImagePath = $oldImagePath; // Default to old path if no new file uploaded

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $fileInfos = handleFileUpload($_FILES['image'], $base_path);

            if (!empty($fileInfos) && $fileInfos[0]['success']) {
                $fileInfo = $fileInfos[0];
                $newImagePath = $fileInfo['dbPath']; // URL path for database

                // Delete old file from server
                if ($oldImagePath) {
                    // Convert web path to server path
                    $oldLocalFilePath = str_replace($base_path, $_SERVER['DOCUMENT_ROOT'], $oldImagePath);
                    if (file_exists($oldLocalFilePath) && is_file($oldLocalFilePath)) {
                        if (!unlink($oldLocalFilePath)) {
                            error_log("Failed to delete old file: " . $oldLocalFilePath);
                            // Optionally throw an exception if deleting old file is critical
                            // throw new Exception("Failed to delete old banner image.");
                        }
                    } else {
                         error_log("Old file not found or is not a file: " . $oldLocalFilePath);
                    }
                }
            } else {
                throw new Exception("อัปโหลดไฟล์ใหม่ล้มเหลว: " . ($fileInfos[0]['error'] ?? 'Unknown error'));
            }
        }

        // Update database with new (or old) image path
        $stmt = $conn->prepare("UPDATE banner SET image_path = ? WHERE id = ?");
        $stmt->bind_param("si", $newImagePath, $bannerId);

        if ($stmt->execute()) {
            $response = array('status' => 'success', 'message' => 'อัปเดตแบนเนอร์เรียบร้อยแล้ว');
        } else {
            throw new Exception("อัปเดตฐานข้อมูลล้มเหลว: " . $stmt->error);
        }
    }
    // ... (rest of your existing actions like addbanner, editbanner, delbanner, getData_banner) ...
    // Original 'addbanner' action (if still needed, might be for a different form)
    // This action seems to be for banners with subject, description, content, and multiple files
    elseif (isset($_POST['action']) && $_POST['action'] == 'addbanner') {
        // This action uses columns that are NOT in your current 'banner' table according to phpMyAdmin screenshots.
        // If you intend to use subject_banner, description_banner, content_banner,
        // you will need to add these columns to your 'banner' table, or create a separate table.
        // For this response, I'm assuming you primarily want to manage simple image banners.
        // If you need this functionality, please confirm the table structure.
        $response = array('status' => 'error', 'message' => 'Action "addbanner" not fully supported with current banner table structure. Please add subject_banner, description_banner, content_banner columns if needed.');

        // Example of how it would work if columns existed:
        /*
        $news_array = [
            'banner_subject' => $_POST['banner_subject'] ?? '',
            'banner_description' => $_POST['banner_description'] ?? '',
            'banner_content'  => $_POST['banner_content'] ?? '',
        ];

        if (isset($news_array)) {
            $stmt = $conn->prepare("INSERT INTO banner
                (subject_banner, description_banner, content_banner, created_at)
                VALUES (?, ?, ?, ?)");

            $news_subject = $news_array['banner_subject'];
            $news_description = $news_array['banner_description'];
            $news_content = mb_convert_encoding($news_array['banner_content'], 'UTF-8', 'auto');
            $current_date = date('Y-m-d H:i:s');

            $stmt->bind_param(
                "ssss",
                $news_subject,
                $news_description,
                $news_content,
                $current_date
            );

            if (!$stmt->execute()) {
                throw new Exception("Execute statement failed: " . $stmt->error);
            }

            $last_inserted_id = $conn->insert_id;

            // Handle fileInput (e.g., cover photo)
            if (isset($_FILES['fileInput']) && $_FILES['fileInput']['error'][0] != 4) {
                $fileInfos = handleFileUpload($_FILES['fileInput']);
                foreach ($fileInfos as $fileInfo) {
                    if ($fileInfo['success']) {
                        $picPath = $base_path . '/public/img/' . $fileInfo['fileName'];
                        // banner_doc table would also need to be created if not exists
                        // and have columns like banner_id, file_name, file_path, etc.
                        $fileColumns = ['banner_id', 'file_name', 'file_size', 'file_type', 'file_path', 'api_path', 'status'];
                        $fileValues = [$last_inserted_id, $fileInfo['fileName'], $fileInfo['fileSize'], $fileInfo['fileType'], $fileInfo['filePath'], $picPath, 1];
                        insertIntoDatabase($conn, 'banner_doc', $fileColumns, $fileValues);
                    } else {
                        throw new Exception('Error uploading file: ' . ($fileInfo['fileName'] ?? 'unknown') . ' - ' . ($fileInfo['error'] ?? 'unknown error'));
                    }
                }
            }
            // Handle image_files (e.g., images inside content)
            if (isset($_FILES['image_files']) && $_FILES['image_files']['error'][0] != 4) {
                $fileInfos = handleFileUpload($_FILES['image_files']);
                foreach ($fileInfos as $fileInfo) {
                    if ($fileInfo['success']) {
                        $picPath = $base_path . '/public/img/' . $fileInfo['fileName'];
                        $fileColumns = ['banner_id', 'file_name', 'file_size', 'file_type', 'file_path', 'api_path'];
                        $fileValues = [$last_inserted_id, $fileInfo['fileName'], $fileInfo['fileSize'], $fileInfo['fileType'], $fileInfo['filePath'], $picPath];
                        insertIntoDatabase($conn, 'banner_doc', $fileColumns, $fileValues);
                    } else {
                        throw new Exception('Error uploading file: ' . ($fileInfo['fileName'] ?? 'unknown') . ' - ' . ($fileInfo['error'] ?? 'unknown error'));
                    }
                }
            }

            $response = array('status' => 'success', 'message' => 'save');
        }
        */
    } elseif (isset($_POST['action']) && $_POST['action'] == 'editbanner') {
        // This action also assumes subject_banner, description_banner, content_banner exists.
        // As per the phpMyAdmin screenshots, these columns are not present in your 'banner' table.
        // If you need to edit simple image banners, you'd only update 'image_path'.
        $response = array('status' => 'error', 'message' => 'Action "editbanner" not fully supported with current banner table structure. Please adjust or add columns if needed.');

        /*
        $news_array = [
            'id' => $_POST['id'] ?? '', // Use 'id' instead of 'banner_id'
            'banner_subject' => $_POST['banner_subject'] ?? '',
            'banner_description' => $_POST['banner_description'] ?? '',
            'banner_content'  => $_POST['banner_content'] ?? '',
        ];

        if (!empty($news_array['id'])) {
            $stmt = $conn->prepare("UPDATE banner
            SET subject_banner = ?,
            description_banner = ?,
            content_banner = ?,
            created_at = ?
            WHERE id = ?"); // Use 'id'

            $news_subject = $news_array['banner_subject'];
            $news_description = $news_array['banner_description'];
            $news_content = mb_convert_encoding($news_array['banner_content'], 'UTF-8', 'auto');
            $current_date = date('Y-m-d H:i:s');
            $news_id = $news_array['id'];

            $stmt->bind_param(
                "ssssi",
                $news_subject,
                $news_description,
                $news_content,
                $current_date,
                $news_id
            );

            if (!$stmt->execute()) {
                throw new Exception("Execute statement failed: " . $stmt->error);
            }

            // Handle fileInput
            if (isset($_FILES['fileInput']) && $_FILES['fileInput']['error'][0] != 4) {
                $fileInfos = handleFileUpload($_FILES['fileInput']);
                foreach ($fileInfos as $fileInfo) {
                    if ($fileInfo['success']) {
                        $picPath = $base_path . '/public/img/' . $fileInfo['fileName'];
                        $fileColumns = ['file_name', 'file_size', 'file_type', 'file_path', 'api_path', 'status'];
                        $fileValues = [$fileInfo['fileName'], $fileInfo['fileSize'], $fileInfo['fileType'], $fileInfo['filePath'], $picPath, 1];
                        $fileWhereClause = 'banner_id = ?'; // This still uses banner_id, should be 'id' if banner_doc linked by 'id'
                        $fileWhereValues = [$news_id];
                        updateInDatabase($conn, 'banner_doc', $fileColumns, $fileValues, $fileWhereClause, $fileWhereValues);
                    } else {
                        throw new Exception('Error uploading file: ' . ($fileInfo['fileName'] ?? 'unknown') . ' - ' . ($fileInfo['error'] ?? 'unknown error'));
                    }
                }
            }
            // Handle image_files
            if (isset($_FILES['image_files']) && $_FILES['image_files']['error'][0] != 4) {
                $fileInfos = handleFileUpload($_FILES['image_files']);
                foreach ($fileInfos as $fileInfo) {
                    if ($fileInfo['success']) {
                        $picPath = $base_path . '/public/img/' . $fileInfo['fileName'];
                        $fileColumns = ['file_name', 'file_size', 'file_type', 'file_path', 'api_path'];
                        $fileValues = [$fileInfo['fileName'], $fileInfo['fileSize'], $fileInfo['fileType'], $fileInfo['filePath'], $picPath];
                        $fileWhereClause = 'banner_id = ?'; // This still uses banner_id, should be 'id' if banner_doc linked by 'id'
                        $fileWhereValues = [$news_id];
                        updateInDatabase($conn, 'banner_doc', $fileColumns, $fileValues, $fileWhereClause, $fileWhereValues);
                    } else {
                        throw new Exception('Error uploading file: ' . ($fileInfo['fileName'] ?? 'unknown') . ' - ' . ($fileInfo['error'] ?? 'unknown error'));
                    }
                }
            }

            $response = array('status' => 'success', 'message' => 'edit save');
        }
        */
    } elseif (isset($_POST['action']) && $_POST['action'] == 'delbanner') {
        $banner_id = $_POST['id'] ?? ''; // ใช้ 'id' ที่ส่งมาจาก AJAX (จาก list_banner.php)

        // ดึง image_path มาก่อน เพื่อลบไฟล์ออกจาก server
        $stmt_select = $conn->prepare("SELECT image_path FROM banner WHERE id = ?");
        $stmt_select->bind_param("i", $banner_id);
        $stmt_select->execute();
        $stmt_select->bind_result($image_path_to_delete);
        $stmt_select->fetch();
        $stmt_select->close();

        if ($image_path_to_delete) {
            // แปลง image_path เป็น local file system path
            $local_file_path = str_replace($base_path, $_SERVER['DOCUMENT_ROOT'], $image_path_to_delete);

            // ตรวจสอบและลบไฟล์จริง
            if (file_exists($local_file_path) && is_file($local_file_path)) {
                if (!unlink($local_file_path)) {
                    error_log("Failed to delete file: " . $local_file_path);
                }
            } else {
                error_log("File not found on server: " . $local_file_path);
            }
        }

        // ลบ record ออกจากตาราง banner โดยตรง (Hard Delete)
        $stmt = $conn->prepare("DELETE FROM banner WHERE id = ?");
        $stmt->bind_param(
            "i",
            $banner_id
        );

        if (!$stmt->execute()) {
            throw new Exception("Execute statement failed on banner table: " . $stmt->error);
        }

        $response = array('status' => 'success', 'message' => 'Delete');
    } elseif (isset($_POST['action']) && $_POST['action'] == 'getData_banner') {
        $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $searchValue = isset($_POST['search']['value']) ? $conn->real_escape_string($_POST['search']['value']) : '';

        $orderColumnIndex = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
        $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';

        // Map column index to actual column name for ordering
        $columns = ['id', 'image_path', 'created_at'];

        $orderBy = $columns[$orderColumnIndex] . " " . $orderDir;

        $whereClause = "1";

        if (!empty($searchValue)) {
            $whereClause .= " AND image_path LIKE '%$searchValue%'";
        }

        // Get total records
        $totalRecordsQuery = "SELECT COUNT(id) FROM banner";
        $totalRecordsResult = $conn->query($totalRecordsQuery);
        $totalRecords = $totalRecordsResult->fetch_row()[0];

        // Get filtered records count
        $totalFilteredQuery = "SELECT COUNT(id) FROM banner WHERE $whereClause";
        $totalFilteredResult = $conn->query($totalFilteredQuery);
        $totalFiltered = $totalFilteredResult->fetch_row()[0];

        // Fetch data
        $dataQuery = "SELECT id, image_path, created_at FROM banner
                      WHERE $whereClause
                      ORDER BY $orderBy
                      LIMIT $start, $length";

        $dataResult = $conn->query($dataQuery);
        $data = [];
        while ($row = $dataResult->fetch_assoc()) {
            $data[] = $row;
        }

        $response = [
            "draw" => intval($draw),
            "recordsTotal" => intval($totalRecords),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data
        ];
    }
    // If no action is specified or recognized
    else {
        $response['message'] = 'No valid action specified.';
    }

} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
}
echo json_encode($response);