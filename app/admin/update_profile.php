<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/perfume/lib/connect.php';

$user_id = $_SESSION['user_id'];
$first = $_POST['first_name'] ?? '';
$last = $_POST['last_name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

// ตรวจสอบว่ามีการอัปโหลดรูปไหม
$profile_img = $_FILES['profile_img']['name'] ?? '';
$tmp_img = $_FILES['profile_img']['tmp_name'] ?? '';

// ดึงรูปเก่า (กรณีไม่อัปโหลด)
$sql = "SELECT profile_img FROM mb_user WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$old_result = $stmt->get_result();
$row = $old_result->fetch_assoc();
$old_img = $row['profile_img'];

// ถ้ามีการอัปโหลด
if (!empty($profile_img) && is_uploaded_file($tmp_img)) {
    move_uploaded_file($tmp_img, $_SERVER['DOCUMENT_ROOT'] . "/perfume/public/img/" . $profile_img);
} else {
    $profile_img = $old_img;
}

// ตรวจสอบรหัสผ่านใหม่
if (!empty($password)) {
    if ($password !== $password_confirm) {
        echo "<script>alert('รหัสผ่านใหม่ไม่ตรงกัน'); window.history.back();</script>";
        exit();
    }
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $sql = "UPDATE mb_user SET first_name=?, last_name=?, email=?, password=?, profile_img=? WHERE user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $first, $last, $email, $password_hash, $profile_img, $user_id);
} else {
    $sql = "UPDATE mb_user SET first_name=?, last_name=?, email=?, profile_img=? WHERE user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $first, $last, $email, $profile_img, $user_id);
}

$stmt->execute();

// ✅ รีหน้า profile พร้อม query param
header("Location: profile.php?updated=1");
exit();
