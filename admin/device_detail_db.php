<?php 

session_start();
include('./../conn.php');
include('./../lib/generateSerialKey.php');



ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $count_of_device = intval($_POST['quantity']);
    $model_id = intval($_POST['model_id']);


    $result = mysqli_query($conn, "
        SELECT b.brand_name FROM model AS m  
        LEFT JOIN brand AS b ON m.brand_id = b.brand_id
        WHERE m.model_id = $model_id
    ");

    $row = mysqli_fetch_assoc($result);
    $brand_name = $row['brand_name'] ?? 'UNKNOWN';


    $serials = generateSerialKeys($conn, $count_of_device, $model_id, $brand_name);


    foreach ($serials as $serial) {
        $escaped_serial = mysqli_real_escape_string($conn, $serial);
        mysqli_query($conn, "
            INSERT INTO device (serial_number, model_id, status)
            VALUES ('$escaped_serial', $model_id, 'ว่าง')
        ");
    }

   
    header("Location: device_detail.php?model_id=$model_id&success=added");
    exit;
}
