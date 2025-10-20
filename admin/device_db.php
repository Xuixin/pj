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

    // รับข้อมูล spec
    $spec_names = isset($_POST['spec_name']) ? $_POST['spec_name'] : [];
    $spec_values = isset($_POST['spec_value']) ? $_POST['spec_value'] : [];

    // สร้าง JSON สำหรับ spec
    $spec_data = null;
    if (!empty($spec_names) && !empty($spec_values)) {
        $specs = [];
        for ($i = 0; $i < count($spec_names); $i++) {
            $name = trim($spec_names[$i]);
            $value = trim($spec_values[$i]);

            // เก็บเฉพาะคู่ที่มีทั้ง name และ value
            if (!empty($name) && !empty($value)) {
                $specs[] = [
                    'name' => $name,
                    'value' => $value
                ];
            }
        }

        // ถ้ามี spec ให้แปลงเป็น JSON
        if (!empty($specs)) {
            $spec_data = json_encode($specs, JSON_UNESCAPED_UNICODE);
        }
    }

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
        // Escape spec_data สำหรับ SQL
        $spec_escaped = $spec_data ? "'" . mysqli_real_escape_string($conn, $spec_data) . "'" : "NULL";
        $insertModelSql = "INSERT INTO model (model_name, brand_id, type_id, price_per_month, spec) VALUES ('$model_name', $brand_id, $type_id, $price_per_month, $spec_escaped)";
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
