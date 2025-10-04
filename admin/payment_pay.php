<?php
session_start();
require_once('./../conn.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: rent.php');
    exit;
}

$rent_id = isset($_POST['rent_id']) ? (int)$_POST['rent_id'] : 0;
$payment_id = isset($_POST['payment_id']) ? (int)$_POST['payment_id'] : 0;

if ($rent_id <= 0 || $payment_id <= 0) {
    $_SESSION['error'] = 'ข้อมูลไม่ถูกต้อง';
    header('Location: rent.php');
    exit;
}

// ตรวจสอบไฟล์สลิป
if (!isset($_FILES['slip']) || $_FILES['slip']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error'] = 'กรุณาอัปโหลดไฟล์สลิป';
    header('Location: rent_payments.php?rent_id=' . $rent_id);
    exit;
}

$allowed = ['pdf', 'jpg', 'jpeg', 'png'];
$file = $_FILES['slip'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed)) {
    $_SESSION['error'] = 'ชนิดไฟล์ไม่ถูกต้อง (อนุญาต: pdf, jpg, jpeg, png)';
    header('Location: rent_payments.php?rent_id=' . $rent_id);
    exit;
}

// เตรียมโฟลเดอร์อัปโหลด
$uploadDir = __DIR__ . '/../uploads/contracts';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

$basename = 'slip_' . $payment_id . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$targetPath = $uploadDir . '/' . $basename;
$publicPath = 'uploads/contracts/' . $basename; // relative from project root

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    $_SESSION['error'] = 'อัปโหลดไฟล์ไม่สำเร็จ';
    header('Location: rent_payments.php?rent_id=' . $rent_id);
    exit;
}

// อัปเดตสถานะการชำระ
$now = date('Y-m-d H:i:s');
$stmt = $conn->prepare("UPDATE payment SET status='ชำระแล้ว', paid_at=?, slip_file=? WHERE payment_id=? AND rent_id=?");
$stmt->bind_param('ssii', $now, $publicPath, $payment_id, $rent_id);

if ($stmt->execute()) {
    $_SESSION['success'] = 'บันทึกการชำระเงินเรียบร้อย';
} else {
    $_SESSION['error'] = 'ไม่สามารถบันทึกการชำระเงินได้: ' . $conn->error;
}

header('Location: rent_payments.php?rent_id=' . $rent_id);
exit;
