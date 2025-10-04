<?php 

function generateSerialKeys($conn, $count, $model_id, $brand_name)
{
    $serials = [];

    // ดึง serial_id ล่าสุดของ model นั้น ๆ
    $sql = "SELECT device_id FROM device WHERE model_id = ? ORDER BY device_id DESC LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $model_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $last_id);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    $last_id = $last_id ?? 0; // ถ้ายังไม่มีให้เริ่มที่ 0

    // วนตามจำนวนที่ต้องการสร้าง
    for ($i = 1; $i <= $count; $i++) {
        $new_id = $last_id + $i;
        $serial_number = sprintf("%s-%03d-%06d", strtoupper($brand_name), $model_id, $new_id);
        $serials[] = $serial_number;
    }

    return $serials;
}
