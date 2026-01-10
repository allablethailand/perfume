<?php
date_default_timezone_set('Asia/Bangkok');
require_once(__DIR__ . '/../../../../lib/base_directory.php');
require_once(__DIR__ . '/../../../../lib/connect.php');
require_once(__DIR__ . '/../../../../inc/getFunctions.php');

if (!isset($_POST['main_group_id'])) {
    echo "<option value=''>-- ไม่มีข้อมูล --</option>";
    exit;
}

$mainGroupId = intval($_POST['main_group_id']);

$stmt = $conn->prepare("SELECT group_id, group_name FROM dn_project_groups WHERE parent_group_id = ?");
$stmt->bind_param("i", $mainGroupId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<option value=''>-- ไม่มีกลุ่มย่อย --</option>";
} else {
    echo "<option value=''>-- เลือกกลุ่มย่อย --</option>";
    while ($row = $result->fetch_assoc()) {
        echo "<option value='{$row['group_id']}'>{$row['group_name']}</option>";
    }
}
?>
