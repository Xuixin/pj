<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once('./../conn.php');


$method = $_POST['_method'];


if ($method === 'POST') {
    $admin_name = $_POST['admin_name'];
    $admin_password = $_POST['admin_password'];
    $hash_password = password_hash($admin_password, PASSWORD_BCRYPT);
    $admin_name = mysqli_real_escape_string($conn, $admin_name);
    $hash_password = mysqli_real_escape_string($conn, $hash_password);

    $tel = mysqli_real_escape_string($conn, $_POST['tel']);
    echo '3';
    $sql = "INSERT INTO `admin` (`admin_name`, `admin_password`, `tel`) 
            VALUES ('$admin_name', '$hash_password', '$tel')";

    $result = mysqli_query($conn, $sql);
    echo 'a';


    if ($result) {
        $_SESSION['success'] = 'เพิ่มข้อมูลพนักงานใหม่สำเร็จ';
    } else {
        $_SESSION['error'] = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล';
        error_log("MySQL Error: " . mysqli_error($conn));
    }

    header('Location: admin.php');
    exit;
} elseif ($method === 'PUT') {
    $admin_id = intval($_POST['admin_id']);
    $admin_name = mysqli_real_escape_string($conn, $_POST['admin_name']);
    $tel = mysqli_real_escape_string($conn, $_POST['tel']);
    $admin_password = $_POST['admin_password'];

    if (!empty($admin_password)) {
        $hash_password = mysqli_real_escape_string($conn, password_hash($admin_password, PASSWORD_BCRYPT));
        $sql = "UPDATE `admin` SET admin_name = '$admin_name',tel = '$tel'  , admin_password = '$hash_password' WHERE admin_id = $admin_id";
    } else {
        $sql = "UPDATE `admin` SET admin_name = '$admin_name', tel = '$tel' WHERE admin_id = $admin_id";
    }


    $result = mysqli_query($conn, $sql);

    if ($result) {
        $_SESSION['success'] = 'แก้ไขข้อมูล ' . $admin_name . ' สำเร็จ';
    } else {
        $_SESSION['error'] = 'เกิดข้อผิดพลาดในการแก้ไขข้อมูล';
        error_log("MySQL Error: " . mysqli_error($conn));
    }

    header('Location: admin.php');
    exit;
} elseif ($method === "delete") {
    $admin_id = intval($_POST['admin_id']);

    $sql = "DELETE from `admin` WHERE admin_id = $admin_id";

    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_affected_rows($conn) > 0) {
        $_SESSION['success'] = 'ลบพนักงานสำเร็จ';
    } else {
        $_SESSION['error'] = 'เกิดข้อผิดพลาดในการลบข้อมูลพนักงาน';
        error_log("MySQL Error: " . mysqli_error($conn));
    }

    header('Location: admin.php');
    exit;
}
