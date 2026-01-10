<?php
include '../check_permission.php'; 
require_once('action/process_about.php'); // แก้ path ให้ถูกต้อง

// ✅ ถ้ามีการเพิ่มใหม่
if (isset($_POST['add_new'])) {
    $type = $_POST['type'] ?? '';
    $content = $_POST['content'] ?? '';
    $author = $_POST['author'] ?? '';
    $position = $_POST['position'] ?? '';
    
    $image_url = null;

    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == UPLOAD_ERR_OK) {
        $fileInfo = handleSingleFileUpload($_FILES['image_file']);
        if ($fileInfo['success']) {
            $base_url = 'https://www.perfume.com';
            $image_url = $base_url . '/public/news_img/' . $fileInfo['fileName'];
        }
    }

    if (!empty($content)) {
        $stmt = $conn->prepare("INSERT INTO about_content (type, content, image_url, author, position) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $type, $content, $image_url, $author, $position);
        $stmt->execute();
        $stmt->close();
        echo "<script>alert('เพิ่มเนื้อหาใหม่เรียบร้อย'); window.location.href='edit_about.php';</script>";
        exit;
    } else {
        echo "<script>alert('กรุณากรอกเนื้อหา'); window.location.href='edit_about.php';</script>";
        exit;
    }
}

// ✅ ถ้ามีการอัปเดตหลายรายการ
$ids = $_POST['ids'] ?? [];
$types = $_POST['types'] ?? [];
$contents = $_POST['contents'] ?? [];
$authors = $_POST['authors'] ?? [];
$positions = $_POST['positions'] ?? [];
$images_old = $_POST['images_old'] ?? [];

$uploaded_files = $_FILES['image_files'] ?? null;
$image_urls_new = [];
if ($uploaded_files && is_array($uploaded_files['name'])) {
    foreach ($uploaded_files['name'] as $key => $name) {
        if ($uploaded_files['error'][$key] == UPLOAD_ERR_OK) {
            $fileInfo = handleSingleFileUpload([
                'name' => $uploaded_files['name'][$key],
                'type' => $uploaded_files['type'][$key],
                'tmp_name' => $uploaded_files['tmp_name'][$key],
                'error' => $uploaded_files['error'][$key],
                'size' => $uploaded_files['size'][$key]
            ]);
            if ($fileInfo['success']) {
                $base_url = 'https://www.perfume.com';
                $image_urls_new[$key] = $base_url . '/public/news_img/' . $fileInfo['fileName'];
            }
        } else {
            $image_urls_new[$key] = $images_old[$key];
        }
    }
}

for ($i = 0; $i < count($ids); $i++) {
    $current_image_url = $image_urls_new[$i] ?? $images_old[$i];
    
    $stmt = $conn->prepare("UPDATE about_content SET type=?, content=?, image_url=?, author=?, position=? WHERE id=?");
    $stmt->bind_param("sssssi",
        $types[$i],
        $contents[$i],
        $current_image_url,
        $authors[$i],
        $positions[$i],
        $ids[$i]
    );
    $stmt->execute();
    $stmt->close();
}

echo "<script>alert('บันทึกการแก้ไขเรียบร้อย'); window.location.href='edit_about.php';</script>";
?>