<?php
// get_models.php
header('Content-Type: application/json');
require_once('./../conn.php');

if ($_POST['brand_id']) {
    $brand_id = mysqli_real_escape_string($conn, $_POST['brand_id']);
    
    // Query to get models with available quantity
    // Assuming you have a model table and equipment table
    $query = "
        SELECT 
            m.model_id,
            m.model_name,
            m.price_per_month,
            COALESCE(
                (SELECT COUNT(*) FROM device d
                 WHERE d.model_id = m.model_id 
                 AND d.status = 'ว่าง'), 0
            ) as available_quantity
        FROM model m 
        WHERE m.brand_id = '$brand_id' 
        ORDER BY m.model_name
    ";
    
    $result = mysqli_query($conn, $query);
    $models = [];


    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $models[] = $row;
        }
    }
    
    echo json_encode($models);
} else {
    echo json_encode([]);
}
?>