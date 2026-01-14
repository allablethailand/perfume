<?php
// require_once('connect.php');
include '../check_permission.php';
$id = $_GET['id'];
$stmt = $conn->prepare("DELETE FROM videos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
header("Location: admin_video_list.php");
exit;
