<?php
require_once('./../conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rent_id = $_POST['rent_id'] ?? null;
    $new_status = $_POST['new_status'] ?? null;

    if ($rent_id && $new_status) {
        $stmt = $conn->prepare("UPDATE rent SET rent_status = ? WHERE rent_id = ?");
        $stmt->bind_param("si", $new_status, $rent_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['success'] = "อัปเดตสถานะเรียบร้อยแล้ว";
    } else {
        $_SESSION['error'] = "ข้อมูลไม่ครบถ้วน";
    }

    header("Location: rent.php"); // หรือหน้าเดิมที่มีตาราง
    exit;
}
