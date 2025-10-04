<?php
session_start();
include('./../conn.php');
include('./../lib/upload.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $model_name = mysqli_real_escape_string($conn, $_POST['model_name']);
    $brand_id = intval($_POST['brand_id']);
    $type_id = intval($_POST['type_id']);
    $image_upload = $_FILES['model_images'];
    $price_per_month = intval($_POST['price_per_month']);

    echo "Model Name: $model_name<br>";
    echo "Brand ID: $brand_id<br>";

    // ใช้ฟังก์ชัน uploadfile ที่ include มา
    $uploadResult = uploadfile($image_upload);

    if ($uploadResult['error']) {

        echo $uploadResult['error'];
        // $_SESSION['error'] = "เกิดข้อผิดพลาดในการอับโหลดรูปภาพ";
        // header('Location: device.php');
        // exit;
    } else {
        $insertModelSql = "INSERT INTO model (model_name, brand_id, type_id,price_per_month) VALUES ('$model_name', $brand_id, $type_id, $price_per_month)";
        if (mysqli_query($conn, $insertModelSql)) {
            $model_id = mysqli_insert_id($conn);

            foreach ($uploadResult['path'] as $imgPath) {
                $imgPathEscaped = mysqli_real_escape_string($conn, $imgPath);
                $sqlImg = "INSERT INTO model_img (model_id, img_path) VALUES ($model_id, '$imgPathEscaped')";
                mysqli_query($conn, $sqlImg);
            }


            $_SESSION['success'] = 'เพิ่มโมเดลใหม่สำเร็จ';
            header('Location: device.php');
            exit;
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาดภายในระบบ กรุณาลองใหม่อีกครั้งภายหลัง";
            header('Location: device.php');
            exit;
        }
    }
}
