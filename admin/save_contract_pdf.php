<?php
session_start();
include('./../conn.php');
include('./../lib/format_date.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['rental_preview'])) {
    die("ไม่พบข้อมูลใน session");
}

$rental = $_SESSION['rental_preview'];


// เตรียมข้อมูลจาก session
$user_id = $rental['user_id'];
$start_date = $rental['start_date'];
$end_date = $rental['return_date'];

// ข้อมูลการชำระเงินจาก session
$total_amount = isset($_SESSION['total_amount']) ? (int)$_SESSION['total_amount'] : 0;
$payment_type = $_SESSION['payment_type'] ?? 'full'; // full | installment
$installment_months = isset($_SESSION['installment_months']) ? (int)$_SESSION['installment_months'] : 0;
$payment_schedule = $_SESSION['payment_schedule'] ?? [];

// ✅ ตรวจสอบอุปกรณ์ว่างพอจริงหรือไม่
$all_items = array_merge($rental['items'] ?? [], $rental['backup_items'] ?? []);
foreach ($all_items as $item) {
    $model_id = $item['model_id'];
    $quantity = $item['quantity'];
    $model_name = $item['model_name'];

    $sql_check = "SELECT COUNT(*) AS available_count 
                  FROM device 
                  WHERE model_id = '$model_id' AND status = 'ว่าง'";
    $res_check = mysqli_query($conn, $sql_check);
    $row = mysqli_fetch_assoc($res_check);

    if ($row['available_count'] < $quantity) {
        $_SESSION['error'] = "❌ อุปกรณ์โมเดล <strong>$model_name</strong> มีไม่เพียงพอ (ขอ $quantity, เหลือ {$row['available_count']})";
        header("Location: rent.php");
        exit;
    }
}

// ✅ Insert ข้อมูลหลักการเช่า (ใช้ prepared statement และเพิ่ม total_amount, payment_type)
$stmtRent = $conn->prepare("INSERT INTO `rent` (`user_id`, `start_date`, `end_date`, `file_lease`, `rent_status`, `pm_latest`, `total_amount`, `payment_type`) VALUES (?, ?, ?, '', 'อยู่ระหว่างการเช่า', 0, ?, ?)");
$stmtRent->bind_param('issis', $user_id, $start_date, $end_date, $total_amount, $payment_type);

if ($stmtRent->execute()) {
    $rent_id = mysqli_insert_id($conn);

    // ✅ ดึง serial จริงจากฐานข้อมูล - Regular devices
    foreach ($rental['items'] as $key => $item) {
        $model_id = $item['model_id'];
        $quantity = $item['quantity'];

        $sql_get_serials = "SELECT device_id, serial_number 
                            FROM device 
                            WHERE model_id = '$model_id' AND status = 'ว่าง' 
                            LIMIT $quantity";
        $res_serials = mysqli_query($conn, $sql_get_serials);

        $serials = [];
        while ($row = mysqli_fetch_assoc($res_serials)) {
            $serials[] = $row;
        }

        // บันทึก rent_detail และเปลี่ยนสถานะ
        foreach ($serials as $serial) {
            $device_id = $serial['device_id'];

            $sql_detail = "INSERT INTO `rent_detail` (`device_id`, `rent_id`, `machine_status`, `create_At`, `backup_device`) 
                           VALUES ('$device_id', '$rent_id', 'ปกติ', NOW(), 0)";
            if (!mysqli_query($conn, $sql_detail)) {
                echo "❌ rent_detail ผิดพลาด: " . mysqli_error($conn) . "<br>";
            }

            $sql_update_device = "UPDATE `device` SET `status`='เช่าแล้ว' WHERE `device_id` = '$device_id'";
            mysqli_query($conn, $sql_update_device);
        }

        // ✅ อัปเดต session ให้มี serial จริง
        $rental['items'][$key]['serials'] = $serials;
    }

    // ✅ ดึง serial จริงจากฐานข้อมูล - Backup devices
    foreach ($rental['backup_items'] as $key => $item) {
        $model_id = $item['model_id'];
        $quantity = $item['quantity'];

        $sql_get_serials = "SELECT device_id, serial_number 
                            FROM device 
                            WHERE model_id = '$model_id' AND status = 'ว่าง' 
                            LIMIT $quantity";
        $res_serials = mysqli_query($conn, $sql_get_serials);

        $serials = [];
        while ($row = mysqli_fetch_assoc($res_serials)) {
            $serials[] = $row;
        }

        // บันทึก rent_detail และเปลี่ยนสถานะ
        foreach ($serials as $serial) {
            $device_id = $serial['device_id'];

            $sql_detail = "INSERT INTO `rent_detail` (`device_id`, `rent_id`, `machine_status`, `create_At`, `backup_device`) 
                           VALUES ('$device_id', '$rent_id', 'สำรอง', NOW(), 1)";
            if (!mysqli_query($conn, $sql_detail)) {
                echo "❌ rent_detail ผิดพลาด: " . mysqli_error($conn) . "<br>";
            }

            $sql_update_device = "UPDATE `device` SET `status`='เช่าแล้ว' WHERE `device_id` = '$device_id'";
            mysqli_query($conn, $sql_update_device);
        }

        // ✅ อัปเดต session ให้มี serial จริง
        $rental['backup_items'][$key]['serials'] = $serials;
    }

    // ✅ บันทึกตารางกำหนดชำระเงิน (ถ้ามี)
    if (!empty($payment_schedule)) {
        $stmtPay = $conn->prepare("INSERT INTO `payment` (`due_date`, `amount`, `status`, `paid_at`, `type`, `rent_id`) VALUES (?, ?, 'ยังไม่ชำระ', NULL, ?, ?)");
        foreach ($payment_schedule as $pay) {
            $due = $pay['due_date'];
            $amt = (float)$pay['amount'];
            $ptype = ($payment_type === 'installment') ? 'งวด' : 'เต็มจำนวน';
            $stmtPay->bind_param('sdsi', $due, $amt, $ptype, $rent_id);
            $stmtPay->execute();
        }
    } else {
        // ชำระเต็มจำนวนครั้งเดียว
        $stmtPayOnce = $conn->prepare("INSERT INTO `payment` (`due_date`, `amount`, `status`, `paid_at`, `type`, `rent_id`) VALUES (?, ?, 'ยังไม่ชำระ', NULL, 'เต็มจำนวน', ?)");
        $stmtPayOnce->bind_param('sdi', $start_date, $total_amount, $rent_id);
        $stmtPayOnce->execute();
    }

    // ✅ เก็บ session ใหม่ (พร้อม serial จริง)
    $_SESSION['rental_preview'] = $rental;

    // ✅ แจ้งผลลัพธ์และ redirect
    $_SESSION['success'] = 'ทำรายการสัญญาใหม่สำเร็จ';
    header('Location: rent.php');
    exit;
} else {
    echo "❌ เกิดข้อผิดพลาดในการบันทึกข้อมูลเช่า: " . $conn->error;
}
