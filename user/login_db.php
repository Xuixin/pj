<?php
session_start();
require_once('./../conn.php'); // เชื่อมต่อฐานข้อมูล

$user_name = trim($_POST['user_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');

// ตรวจสอบข้อมูล
if ($user_name === '' || $phone === '') {
    $_SESSION['login_error'] = 'กรุณากรอกข้อมูลให้ครบ';
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

// ตรวจสอบในฐานข้อมูล
$sql = "SELECT * FROM user WHERE user_name = ? AND phone = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $user_name, $phone);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    // สำเร็จ: ตั้งค่า session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_name'] = $user['user_name'];
    header('Location: home.php'); // หรือกลับไปหน้าเดิม
} else {
    $_SESSION['login_error'] = 'ไม่พบผู้ใช้งาน';
    header('Location: ' . $_SERVER['HTTP_REFERER']);
}
exit;
