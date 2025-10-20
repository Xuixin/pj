<?php
session_start();
require_once './../conn.php'; // เชื่อมฐานข้อมูล

// รับ rent_detail_id จาก request และ action
$rent_detail_id = intval($_GET['rent_detail_id'] ?? 0);
$action = $_GET['action'] ?? '';

if (!$rent_detail_id || $action !== 'mark_damage') {
    die('ข้อมูลไม่ถูกต้อง');
}

// ดึง rent_detail เดิม พร้อม device_id, rent_id
$sql = "SELECT rd.*, d.model_id FROM rent_detail rd
        JOIN device d ON rd.device_id = d.device_id
        WHERE rd.rent_detail_id = $rent_detail_id";
        
$res = mysqli_query($conn, $sql);
if (mysqli_num_rows($res) === 0) {
    die('ไม่พบข้อมูล');
}
$rent_detail = mysqli_fetch_assoc($res);

$model_id = $rent_detail['model_id'];
$rent_id = $rent_detail['rent_id'];
$old_device_id = $rent_detail['device_id'];

// 1. อัปเดต rent_detail ตัวเดิม ให้สถานะเป็น 'เสีย'
$upd1 = "UPDATE rent_detail SET machine_status = 'เสีย' WHERE rent_detail_id = $rent_detail_id";
mysqli_query($conn, $upd1);

// 2. หาเครื่องใหม่ model เดิม ที่ status = 'ว่าง'
$sql_new_device = "SELECT device_id FROM device 
                   WHERE model_id = $model_id AND status = 'ว่าง' 
                   LIMIT 1";
$res_new = mysqli_query($conn, $sql_new_device);

if (mysqli_num_rows($res_new) > 0) {
    // มีเครื่องใหม่
    $new_device = mysqli_fetch_assoc($res_new);
    $new_device_id = $new_device['device_id'];

    // 3. อัปเดต device ใหม่เป็น เช่าแล้ว
    $upd2 = "UPDATE device SET status = 'เช่าแล้ว' WHERE device_id = $new_device_id";
    mysqli_query($conn, $upd2);

    // 4. สร้าง rent_detail ใหม่ สำหรับเครื่องใหม่
    $ins = "INSERT INTO rent_detail (device_id, rent_id, machine_status, create_At) VALUES
            ($new_device_id, $rent_id, 'ปกติ', NOW())";
    mysqli_query($conn, $ins);

    $msg = "เปลี่ยนอุปกรณ์ใหม่เรียบร้อย";
} else {
    // ไม่มีเครื่องใหม่ ให้สถานะ rent_detail เป็น 'ส่งเคลม'
    $upd3 = "UPDATE rent_detail SET machine_status = 'ส่งเคลม' WHERE rent_detail_id = $rent_detail_id";
    mysqli_query($conn, $upd3);

    $msg = "ไม่มีอุปกรณ์พร้อมใช้งาน เครื่องนี้ถูกตั้งสถานะส่งเคลม";
}

// redirect กลับหน้ารายการ หรือแสดงผล
$_SESSION['success'] = $msg;
header("Location: contract_viewer.php?rent_id=$rent_id");
exit;

?>
