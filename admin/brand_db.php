<?php 

session_start();
include('./../conn.php');



if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['brand_name'])) {
    $brand_name = mysqli_real_escape_string($conn, $_POST['brand_name']);
    $sql = "INSERT INTO brand (brand_name)VALUES('$brand_name')";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        $_SESSION['success'] = 'เพิ่มแบรนด์ใหม่สำเร็จ';
    } else {
        $_SESSION['error'] = 'เกิดข้อผิดพลาดในการเพิ่มแบรนด์ใหม่';
        error_log("MySQL Error: " . mysqli_error($conn));
    }

    header("Location: device.php");
    exit;
}




?>
