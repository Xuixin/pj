<?php
// index.php - หน้าแรก
session_start();
require_once('./../conn.php');


?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechRent - เช่าอุปกรณ์ไอที คุณภาพสูง</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
<?php  include('./components/navbar.php'); ?>

  

    <!-- Footer -->
    <?php include('components/footer.php'); ?>

 
</body>
</html>