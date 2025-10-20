<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once('./../conn.php');


$method = $_POST['_method'];


if ($method === 'POST') {
    $user_name = mysqli_real_escape_string($conn,$_POST['user_name']);
    $localtion = mysqli_real_escape_string($conn,$_POST['location']);
    $phone = mysqli_real_escape_string($conn,$_POST['phone']);


    $sql = "INSERT INTO `user` (`user_name`, `location`, `phone`) 
            VALUES ('$user_name', '$location', '$phone')";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        $_SESSION['success'] = 'เพิ่มข้อมูลผู้เช่าใหม่สำเร็จ';
    } else {
        $_SESSION['error'] = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล';
        error_log("MySQL Error: " . mysqli_error($conn));
    }

    header('Location: user.php');
    exit;
} elseif ($method === 'PUT') {
    $user_id = intval($_POST['user_id']);
    $user_name = mysqli_real_escape_string($conn,$_POST['user_name']);
    $localtion = mysqli_real_escape_string($conn,$_POST['location']);
    $phone = mysqli_real_escape_string($conn,$_POST['phone']);

    $sql = "UPDATE `user` 
            SET `user_name`='$user_name',`localtion`='$localtion',`phone`='phone' 
            WHERE user_id = $user_id";


    $result = mysqli_query($conn, $sql);

    if ($result) {
        $_SESSION['success'] = 'แก้ไขข้อมูลของ ' . $admin_name . ' สำเร็จ';
    } else {
        $_SESSION['error'] = 'เกิดข้อผิดพลาดในการแก้ไขข้อมูล';
        error_log("MySQL Error: " . mysqli_error($conn));
    }

    header('Location: user.php');
    exit;
} elseif ($method === "delete") {
    $user_id = intval($_POST['user_id']);

    $sql = "DELETE from `user` WHERE user_id = $user_id";

    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_affected_rows($conn) > 0) {
        $_SESSION['success'] = 'ลบข้อมูลผู้เช่าสำเร็จ';
    } else {
        $_SESSION['error'] = 'เกิดข้อผิดพลาดในการลบข้อมูลผู้เช่า';
        error_log("MySQL Error: " . mysqli_error($conn));
    }

    header('Location: user.php');
    exit;
}
