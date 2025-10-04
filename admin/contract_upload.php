<?php
session_start();
require_once('./../conn.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'วิธีการร้องขอไม่ถูกต้อง';
    header('Location: rent.php');
    exit;
}

$rent_id = isset($_POST['rent_id']) ? intval($_POST['rent_id']) : 0;

if ($rent_id <= 0) {
    $_SESSION['error'] = 'ไม่พบ rent_id';
    header('Location: rent.php');
    exit;
}

if (!isset($_FILES['contract_file']) || $_FILES['contract_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error'] = 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์';
    header('Location: rent.php');
    exit;
}

$file = $_FILES['contract_file'];
$max_size = 10 * 1024 * 1024;

if ($file['size'] > $max_size) {
    $_SESSION['error'] = 'ไฟล์มีขนาดใหญ่เกิน 10MB';
    header('Location: rent.php');
    exit;
}

$allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
$file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($file_extension, $allowed_extensions)) {
    $_SESSION['error'] = 'อนุญาตเฉพาะไฟล์ PDF, DOC, DOCX, JPG, JPEG, PNG';
    header('Location: rent.php');
    exit;
}

$upload_dir = './../uploads/contracts/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$new_filename = 'contract_' . $rent_id . '_' . date('YmdHis') . '_' . uniqid() . '.' . $file_extension;
$file_path = $upload_dir . $new_filename;
$db_file_path = 'uploads/contracts/' . $new_filename;

if (!move_uploaded_file($file['tmp_name'], $file_path)) {
    $_SESSION['error'] = 'ไม่สามารถบันทึกไฟล์ได้';
    header('Location: rent.php');
    exit;
}

$sql = "UPDATE rent SET file_lease = ? WHERE rent_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "si", $db_file_path, $rent_id);

if (!mysqli_stmt_execute($stmt)) {
    unlink($file_path);
    $_SESSION['error'] = 'อัปเดตฐานข้อมูลล้มเหลว: ' . mysqli_error($conn);
    header('Location: rentt.php');
    exit;
}

mysqli_stmt_close($stmt);

// ✅ สำเร็จ
$_SESSION['success'] = 'อัปโหลดใบสัญญาสำเร็จ';
header('Location: rent.php');
exit;
?>
