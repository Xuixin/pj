<?php
session_start();
require_once './../conn.php';

$rent_id = intval($_POST['rent_id'] ?? 0);
$pm_date = $_POST['pm_date'] ?? '';
$pm_note = trim($_POST['pm_note'] ?? '');

if (!$rent_id || !$pm_date || !$pm_note) {
    $_SESSION['error'] = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    header("Location: contract_viewer.php?rent_id=$rent_id");
    exit;
}

$sql = "INSERT INTO pm (rent_id, pm_date, note) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    $_SESSION['error'] = "เตรียมคำสั่งล้มเหลว: " . $conn->error;
    header("Location: contract_viewer.php?rent_id=$rent_id");
    exit;
}

$stmt->bind_param("iss", $rent_id, $pm_date, $pm_note);

if (!$stmt->execute()) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดขณะบันทึก: " . $stmt->error;
} else {
    $_SESSION['success'] = 'บันทึกข้อมูลการตรวจ PM แล้ว';
}

$stmt->close();
header("Location: contract_viewer.php?rent_id=$rent_id");
exit;
