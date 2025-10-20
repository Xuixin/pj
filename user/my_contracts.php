<?php
// index.php - หน้าแรก
session_start();
require_once('./../conn.php');
require_once('./../lib/format_date.php');

// สมมติว่า user login แล้ว และมี user_id อยู่ใน session
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT 
        r.*, 
        u.user_name, 
        COUNT(rd.rent_detail_id) AS total 
        FROM rent r 
        JOIN rent_detail rd ON rd.rent_id = r.rent_id 
        JOIN user u ON r.user_id = u.user_id 
        WHERE r.user_id = $user_id
        GROUP BY r.rent_id
        ORDER BY r.start_date DESC";

$result = mysqli_query($conn, $sql);

$contracts = [];

while ($row = mysqli_fetch_assoc($result)) {
    $contracts[] = $row;
}


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
    <!-- AOS CSS -->
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">

</head>

<body class="bg-gray-50">
    <!-- Navigation -->
    <?php include('./components/navbar.php');
    ?>

    <!-- Hero Section -->
    <section class="min-h-screen p-4 sm:p-6 lg:p-8 xl:px-24">
        <div class="w-full mx-auto bg-white rounded-lg shadow-sm py-4 px-4 sm:py-6 sm:px-6 lg:py-8 lg:px-10">
            <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold mb-4 sm:mb-6 text-gray-700">สัญญาเช่าของฉัน</h1>

            <?php if (empty($contracts)): ?>
                <div class="text-center text-gray-500 py-12">
                    <i class="fas fa-file-contract text-5xl text-gray-300 mb-4"></i>
                    <p>ยังไม่มีสัญญาเช่า</p>
                </div>
            <?php else: ?>
                <div class="grid gap-3 sm:gap-4">
                    <?php foreach ($contracts as $contract): ?>
                        <div class="contract-item p-3 sm:p-4 rounded-xl border border-gray-100 bg-white shadow-sm hover:shadow-md transition cursor-pointer"
                            onclick="window.location.href = 'contract_detail.php?rent_id=<?= $contract['rent_id'] ?>' ">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-4">
                                <div class="flex-1 w-full sm:w-auto">
                                    <div class="flex items-center mb-2">
                                        <i class="fas fa-file-contract text-blue-500 mr-2 text-sm sm:text-base"></i>
                                        <span class="font-semibold text-gray-800 text-sm sm:text-base">
                                            รหัสสัญญา: <?= $contract['rent_id'] ?>
                                        </span>
                                    </div>
                                    <div class="flex items-center text-xs sm:text-sm text-gray-600">
                                        <i class="fas fa-user mr-2"></i>
                                        <span>ผู้เช่า: <?= htmlspecialchars($contract['user_name']) ?></span>
                                    </div>
                                    <div class="flex items-center text-xs sm:text-sm text-gray-600 mt-1">
                                        <i class="fas fa-calendar-alt mr-2"></i>
                                        <span>วันที่เริ่ม: <?= formatThaiShortDateTime($contract['start_date']) ?></span>
                                    </div>
                                </div>
                                <div class="w-full sm:w-auto">
                                    <div class="px-3 py-2 bg-red-50 rounded-lg border border-red-200 text-center sm:text-right">
                                        <i class="fas fa-clock text-red-500 mr-1 sm:mr-2"></i>
                                        <span class="text-red-600 font-semibold text-xs sm:text-sm">
                                            สิ้นสุด: <?= formatThaiShortDateTime($contract['end_date']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>



    <!-- Footer -->
    <?php include('components/footer.php'); ?>

    <!-- AOS JS -->
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>

</body>

</html>