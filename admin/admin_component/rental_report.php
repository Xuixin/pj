<?php
require_once('./../conn.php');



$currentMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$currentYear = isset($_GET['year']) ? $_GET['year'] : date('Y');
$viewType = isset($_GET['view_type']) ? $_GET['view_type'] : 'month';

if ($viewType === 'month') {
    $monthStart = "$currentYear-$currentMonth-01";
    $monthEnd = date('Y-m-t', strtotime($monthStart));

    // ข้อมูลผู้เช่า
    $totalCustomersQuery = "SELECT COUNT(DISTINCT user_id) as cnt FROM rent 
                            WHERE start_date IS NOT NULL 
                            AND DATE(start_date) BETWEEN ? AND ?";
    $stmt = $conn->prepare($totalCustomersQuery);
    $stmt->bind_param("ss", $monthStart, $monthEnd);
    $stmt->execute();
    $totalCustomers = $stmt->get_result()->fetch_assoc()['cnt'];

    $newCustomersQuery = "SELECT COUNT(DISTINCT r.user_id) as cnt 
                          FROM rent r 
                          WHERE r.start_date IS NOT NULL
                          AND DATE(r.start_date) BETWEEN ? AND ?
                          AND NOT EXISTS (
                              SELECT 1 FROM rent r2 
                              WHERE r2.user_id = r.user_id 
                              AND r2.start_date IS NOT NULL
                              AND DATE(r2.start_date) < ?
                          )";
    $stmt = $conn->prepare($newCustomersQuery);
    $stmt->bind_param("sss", $monthStart, $monthEnd, $monthStart);
    $stmt->execute();
    $newCustomers = $stmt->get_result()->fetch_assoc()['cnt'];

    $customerListQuery = "SELECT DISTINCT u.user_id, u.user_name, u.location, u.phone,
                          COUNT(r.rent_id) as contract_count,
                          SUM(r.total_amount) as total_spent
                          FROM rent r
                          JOIN user u ON r.user_id = u.user_id
                          WHERE r.start_date IS NOT NULL
                          AND DATE(r.start_date) BETWEEN ? AND ?
                          GROUP BY u.user_id
                          ORDER BY contract_count DESC";
    $stmt = $conn->prepare($customerListQuery);
    $stmt->bind_param("ss", $monthStart, $monthEnd);
    $stmt->execute();
    $customerList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // ข้อมูลสัญญา
    $newContractsQuery = "SELECT COUNT(*) as cnt FROM rent 
                          WHERE start_date IS NOT NULL 
                          AND DATE(start_date) BETWEEN ? AND ?";
    $stmt = $conn->prepare($newContractsQuery);
    $stmt->bind_param("ss", $monthStart, $monthEnd);
    $stmt->execute();
    $newContracts = $stmt->get_result()->fetch_assoc()['cnt'];

    $activeContractsQuery = "SELECT COUNT(*) as cnt FROM rent 
                             WHERE start_date IS NOT NULL AND end_date IS NOT NULL
                             AND DATE(start_date) <= ? AND DATE(end_date) >= ?";
    $stmt = $conn->prepare($activeContractsQuery);
    $stmt->bind_param("ss", $monthEnd, $monthStart);
    $stmt->execute();
    $activeContracts = $stmt->get_result()->fetch_assoc()['cnt'];

    $expiredContractsQuery = "SELECT COUNT(*) as cnt FROM rent 
                              WHERE end_date IS NOT NULL 
                              AND DATE(end_date) BETWEEN ? AND ?";
    $stmt = $conn->prepare($expiredContractsQuery);
    $stmt->bind_param("ss", $monthStart, $monthEnd);
    $stmt->execute();
    $expiredContracts = $stmt->get_result()->fetch_assoc()['cnt'];

    // ข้อมูลอุปกรณ์
    $devicesRentedQuery = "SELECT COUNT(DISTINCT rd.device_id) as cnt 
                          FROM rent_detail rd
                          JOIN rent r ON rd.rent_id = r.rent_id
                          WHERE r.start_date IS NOT NULL
                          AND DATE(r.start_date) BETWEEN ? AND ?";
    $stmt = $conn->prepare($devicesRentedQuery);
    $stmt->bind_param("ss", $monthStart, $monthEnd);
    $stmt->execute();
    $devicesRented = $stmt->get_result()->fetch_assoc()['cnt'];

    $deviceStatusQuery = "SELECT 
                          COALESCE(SUM(CASE WHEN status = 'ว่าง' THEN 1 ELSE 0 END), 0) as available,
                          COALESCE(SUM(CASE WHEN status = 'เช่าแล้ว' THEN 1 ELSE 0 END), 0) as rented
                          FROM device";
    $stmt = $conn->prepare($deviceStatusQuery);
    $stmt->execute();
    $deviceStatus = $stmt->get_result()->fetch_assoc();

    $brokenDevicesQuery = "SELECT 
                           COALESCE(SUM(CASE WHEN machine_status = 'เสีย' THEN 1 ELSE 0 END), 0) as broken,
                           COALESCE(SUM(CASE WHEN machine_status = 'ส่งเคลม' THEN 1 ELSE 0 END), 0) as claim
                           FROM rent_detail rd
                           JOIN rent r ON rd.rent_id = r.rent_id
                           WHERE r.end_date IS NOT NULL
                           AND DATE(r.end_date) >= CURDATE()";
    $stmt = $conn->prepare($brokenDevicesQuery);
    $stmt->execute();
    $brokenDevices = $stmt->get_result()->fetch_assoc();

    $topModelsQuery = "SELECT m.model_name, b.brand_name, COUNT(rd.device_id) as rent_count
                       FROM rent_detail rd
                       JOIN device d ON rd.device_id = d.device_id
                       JOIN model m ON d.model_id = m.model_id
                       JOIN brand b ON m.brand_id = b.brand_id
                       JOIN rent r ON rd.rent_id = r.rent_id
                       WHERE r.start_date IS NOT NULL
                       AND DATE(r.start_date) BETWEEN ? AND ?
                       GROUP BY m.model_id
                       ORDER BY rent_count DESC
                       LIMIT 10";
    $stmt = $conn->prepare($topModelsQuery);
    $stmt->bind_param("ss", $monthStart, $monthEnd);
    $stmt->execute();
    $topModels = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // รายละเอียดสัญญา
    $contractDetailsQuery = "SELECT r.rent_id, u.user_name, r.start_date, r.end_date, 
                             r.total_amount, r.rent_status, r.payment_type,
                             COUNT(rd.device_id) as device_count
                             FROM rent r
                             JOIN user u ON r.user_id = u.user_id
                             LEFT JOIN rent_detail rd ON r.rent_id = rd.rent_id
                             WHERE r.start_date IS NOT NULL
                             AND DATE(r.start_date) BETWEEN ? AND ?
                             GROUP BY r.rent_id
                             ORDER BY r.start_date DESC";
    $stmt = $conn->prepare($contractDetailsQuery);
    $stmt->bind_param("ss", $monthStart, $monthEnd);
    $stmt->execute();
    $contractDetails = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    // Yearly View
    $yearStart = "$currentYear-01-01";
    $yearEnd = "$currentYear-12-31";

    // สรุปข้อมูลทั้งปี
    $totalCustomersQuery = "SELECT COUNT(DISTINCT user_id) as cnt FROM rent 
                            WHERE start_date IS NOT NULL 
                            AND YEAR(start_date) = ?";
    $stmt = $conn->prepare($totalCustomersQuery);
    $stmt->bind_param("i", $currentYear);
    $stmt->execute();
    $totalCustomers = $stmt->get_result()->fetch_assoc()['cnt'];

    $newContractsQuery = "SELECT COUNT(*) as cnt FROM rent 
                          WHERE start_date IS NOT NULL 
                          AND YEAR(start_date) = ?";
    $stmt = $conn->prepare($newContractsQuery);
    $stmt->bind_param("i", $currentYear);
    $stmt->execute();
    $newContracts = $stmt->get_result()->fetch_assoc()['cnt'];

    $devicesRentedQuery = "SELECT COUNT(DISTINCT rd.device_id) as cnt 
                          FROM rent_detail rd
                          JOIN rent r ON rd.rent_id = r.rent_id
                          WHERE r.start_date IS NOT NULL
                          AND YEAR(r.start_date) = ?";
    $stmt = $conn->prepare($devicesRentedQuery);
    $stmt->bind_param("i", $currentYear);
    $stmt->execute();
    $devicesRented = $stmt->get_result()->fetch_assoc()['cnt'];

    $deviceStatusQuery = "SELECT 
                          COALESCE(SUM(CASE WHEN status = 'ว่าง' THEN 1 ELSE 0 END), 0) as available,
                          COALESCE(SUM(CASE WHEN status = 'เช่าแล้ว' THEN 1 ELSE 0 END), 0) as rented
                          FROM device";
    $stmt = $conn->prepare($deviceStatusQuery);
    $stmt->execute();
    $deviceStatus = $stmt->get_result()->fetch_assoc();

    $brokenDevicesQuery = "SELECT 
                           COALESCE(SUM(CASE WHEN machine_status = 'เสีย' THEN 1 ELSE 0 END), 0) as broken,
                           COALESCE(SUM(CASE WHEN machine_status = 'ส่งเคลม' THEN 1 ELSE 0 END), 0) as claim
                           FROM rent_detail rd
                           JOIN rent r ON rd.rent_id = r.rent_id
                           WHERE r.end_date IS NOT NULL
                           AND DATE(r.end_date) >= CURDATE()";
    $stmt = $conn->prepare($brokenDevicesQuery);
    $stmt->execute();
    $brokenDevices = $stmt->get_result()->fetch_assoc();

    // Top models ทั้งปี
    $topModelsQuery = "SELECT m.model_name, b.brand_name, COUNT(rd.device_id) as rent_count
                       FROM rent_detail rd
                       JOIN device d ON rd.device_id = d.device_id
                       JOIN model m ON d.model_id = m.model_id
                       JOIN brand b ON m.brand_id = b.brand_id
                       JOIN rent r ON rd.rent_id = r.rent_id
                       WHERE r.start_date IS NOT NULL
                       AND YEAR(r.start_date) = ?
                       GROUP BY m.model_id
                       ORDER BY rent_count DESC
                       LIMIT 10";
    $stmt = $conn->prepare($topModelsQuery);
    $stmt->bind_param("i", $currentYear);
    $stmt->execute();
    $topModels = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // รายชื่อผู้เช่าทั้งปี
    $customerListQuery = "SELECT DISTINCT u.user_id, u.user_name, u.location, u.phone,
                          COUNT(r.rent_id) as contract_count,
                          SUM(r.total_amount) as total_spent
                          FROM rent r
                          JOIN user u ON r.user_id = u.user_id
                          WHERE r.start_date IS NOT NULL
                          AND YEAR(r.start_date) = ?
                          GROUP BY u.user_id
                          ORDER BY contract_count DESC
                          LIMIT 20";
    $stmt = $conn->prepare($customerListQuery);
    $stmt->bind_param("i", $currentYear);
    $stmt->execute();
    $customerList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // สรุปรายเดือน
    $monthlyStatsQuery = "SELECT 
        MONTH(r.start_date) as month,
        COUNT(DISTINCT r.user_id) as customers,
        COUNT(r.rent_id) as contracts,
        COUNT(DISTINCT rd.device_id) as devices
        FROM rent r
        LEFT JOIN rent_detail rd ON r.rent_id = rd.rent_id
        WHERE r.start_date IS NOT NULL
        AND YEAR(r.start_date) = ?
        GROUP BY MONTH(r.start_date)
        ORDER BY month";
    $stmt = $conn->prepare($monthlyStatsQuery);
    $stmt->bind_param("i", $currentYear);
    $stmt->execute();
    $monthlyStats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // รายละเอียดสัญญาทั้งปี
    $contractDetailsQuery = "SELECT r.rent_id, u.user_name, r.start_date, r.end_date, 
                             r.total_amount, r.rent_status, r.payment_type,
                             COUNT(rd.device_id) as device_count
                             FROM rent r
                             JOIN user u ON r.user_id = u.user_id
                             LEFT JOIN rent_detail rd ON r.rent_id = rd.rent_id
                             WHERE r.start_date IS NOT NULL
                             AND YEAR(r.start_date) = ?
                             GROUP BY r.rent_id
                             ORDER BY r.start_date DESC
                             LIMIT 50";
    $stmt = $conn->prepare($contractDetailsQuery);
    $stmt->bind_param("i", $currentYear);
    $stmt->execute();
    $contractDetails = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$thaiMonths = [
    '01' => 'มกราคม',
    '02' => 'กุมภาพันธ์',
    '03' => 'มีนาคม',
    '04' => 'เมษายน',
    '05' => 'พฤษภาคม',
    '06' => 'มิถุนายน',
    '07' => 'กรกฎาคม',
    '08' => 'สิงหาคม',
    '09' => 'กันยายน',
    '10' => 'ตุลาคม',
    '11' => 'พฤศจิกายน',
    '12' => 'ธันวาคม'
];

if (!function_exists('formatDateThai')) {
    function formatDateThai($dateStr)
    {
        if (!$dateStr) return '-';
        return date('d/m/Y', strtotime($dateStr));
    }
}
?>

<div id="rental-report-root" class="space-y-6 text-white">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            thead {
                position: static !important;
            }

            @page {
                size: A4 portrait;
                margin: 12mm;
            }

            table {
                border-collapse: collapse !important;
            }

            th,
            td {
                border: 1px solid #e5e7eb !important;
                padding: 6px !important;
            }
        }
    </style>
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 to-pink-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-2xl font-bold">📋 รายงานการเช่า</h2>
                <p class="text-sm opacity-90 mt-1">
                    <?php if ($viewType === 'month'): ?>
                        เดือน<?= $thaiMonths[$currentMonth] ?> พ.ศ. <?= $currentYear + 543 ?>
                    <?php else: ?>
                        ปี พ.ศ. <?= $currentYear + 543 ?>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <form method="GET" class="flex items-end gap-4 no-print">
            <input type="hidden" name="tab" value="rental">

            <div class="flex-1">
                <label class="block text-sm font-medium mb-2 opacity-90">ประเภทการแสดงผล</label>
                <select name="view_type" class="select select-bordered w-full text-black" onchange="this.form.submit()">
                    <option value="month" <?= $viewType === 'month' ? 'selected' : '' ?>>รายเดือน</option>
                    <option value="year" <?= $viewType === 'year' ? 'selected' : '' ?>>รายปี</option>
                </select>
            </div>

            <?php if ($viewType === 'month'): ?>
                <div class="flex-1">
                    <label class="block text-sm font-medium mb-2 opacity-90">เดือน</label>
                    <select name="month" class="select select-bordered w-full text-black" onchange="this.form.submit()">
                        <?php foreach ($thaiMonths as $num => $name): ?>
                            <option value="<?= $num ?>" <?= $currentMonth == $num ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="flex-1">
                <label class="block text-sm font-medium mb-2 opacity-90">ปี</label>
                <select name="year" class="select select-bordered w-full text-black" onchange="this.form.submit()">
                    <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                        <option value="<?= $y ?>" <?= $currentYear == $y ? 'selected' : '' ?>><?= $y + 543 ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </form>

        <?php
        // สร้างลิงก์สำหรับ Export PDF ให้คงพารามิเตอร์ปัจจุบัน
        $exportParams = [
            'view_type' => $viewType,
            'year' => $currentYear
        ];
        if ($viewType === 'month') {
            $exportParams['month'] = $currentMonth;
        }
        $exportQuery = http_build_query($exportParams);
        ?>

        <div class="mt-4 flex items-center gap-3 no-print">
            <a href="admin_component/rental_report_print.php?<?= $exportQuery ?>" target="_blank"><button type="button" class="btn btn-sm btn-outline">
                    <i class="fas fa-print mr-2"></i>Export PDF
                </button></a>
        </div>
    </div>

    <?php if ($viewType === 'month'): ?>
        <!-- Monthly View -->

        <!-- สถิติภาพรวม -->
        <div class="grid grid-cols-4 gap-4">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
                <div class="text-center">
                    <i class="fas fa-file-contract text-3xl mb-2"></i>
                    <h3 class="text-2xl font-bold"><?= number_format($newContracts) ?></h3>
                    <p class="text-sm opacity-90">สัญญาใหม่</p>
                </div>
            </div>

            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
                <div class="text-center">
                    <i class="fas fa-clipboard-check text-3xl mb-2"></i>
                    <h3 class="text-2xl font-bold"><?= number_format($activeContracts) ?></h3>
                    <p class="text-sm opacity-90">สัญญาที่ใช้งาน</p>
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                <div class="text-center">
                    <i class="fas fa-users text-3xl mb-2"></i>
                    <h3 class="text-2xl font-bold"><?= number_format($totalCustomers) ?></h3>
                    <p class="text-sm opacity-90">ผู้เช่า</p>
                </div>
            </div>

            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-lg p-6 text-white">
                <div class="text-center">
                    <i class="fas fa-laptop text-3xl mb-2"></i>
                    <h3 class="text-2xl font-bold"><?= number_format($devicesRented) ?></h3>
                    <p class="text-sm opacity-90">อุปกรณ์</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <!-- ข้อมูลผู้เช่า -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-users text-blue-500"></i>
                        ข้อมูลผู้เช่า
                    </h3>
                    <span class="badge badge-info">ลูกค้าใหม่: <?= $newCustomers ?> ราย</span>
                </div>

                <div class="overflow-x-auto max-h-96 overflow-y-auto">
                    <table class="table table-zebra w-full table-sm">
                        <thead class="sticky top-0 bg-base-200">
                            <tr>
                                <th>#</th>
                                <th>ชื่อลูกค้า</th>
                                <th>สถานที่</th>
                                <th>สัญญา</th>
                                <th>มูลค่า</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($customerList) === 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-gray-500">ไม่มีข้อมูล</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($customerList as $index => $customer): ?>
                                    <tr>
                                        <td class="!text-black"><?= $index + 1 ?></td>
                                        <td class="font-medium !text-black"><?= htmlspecialchars($customer['user_name']) ?></td>
                                        <td class="!text-black"><?= htmlspecialchars($customer['location']) ?></td>
                                        <td><span class="badge badge-primary badge-sm"><?= $customer['contract_count'] ?></span></td>
                                        <td class="font-semibold text-green-600">฿<?= number_format($customer['total_spent']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ข้อมูลอุปกรณ์ -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2 mb-4">
                    <i class="fas fa-laptop text-orange-500"></i>
                    ข้อมูลอุปกรณ์
                </h3>

                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div class="p-3 bg-green-50 rounded-lg text-center">
                        <p class="text-2xl font-bold text-green-600"><?= number_format($deviceStatus['available']) ?></p>
                        <p class="text-sm text-gray-600">อุปกรณ์ว่าง</p>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-lg text-center">
                        <p class="text-2xl font-bold text-blue-600"><?= number_format($deviceStatus['rented']) ?></p>
                        <p class="text-sm text-gray-600">อุปกรณ์เช่าแล้ว</p>
                    </div>
                    <div class="p-3 bg-red-50 rounded-lg text-center">
                        <p class="text-2xl font-bold text-red-600"><?= number_format($brokenDevices['broken']) ?></p>
                        <p class="text-sm text-gray-600">อุปกรณ์เสีย</p>
                    </div>

                </div>

                <div class="mt-4">
                    <h4 class="font-semibold text-gray-700 mb-3">โมเดลยอดนิยม</h4>
                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        <?php if (count($topModels) === 0): ?>
                            <p class="text-center text-gray-500 py-4">ไม่มีข้อมูล</p>
                        <?php else: ?>
                            <?php foreach ($topModels as $index => $model): ?>
                                <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-gradient-to-r from-blue-500 to-purple-500 flex items-center justify-center  text-xs font-bold">
                                            <?= $index + 1 ?>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium !text-black"><?= htmlspecialchars($model['model_name']) ?></p>
                                            <p class="text-xs text-gray-500"><?= htmlspecialchars($model['brand_name']) ?></p>
                                        </div>
                                    </div>
                                    <span class="badge badge-info badge-sm"><?= $model['rent_count'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- รายละเอียดสัญญาทั้งหมด -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2 mb-4">
                <i class="fas fa-file-contract text-purple-500"></i>
                รายละเอียดสัญญาเช่าทั้งหมด
            </h3>

            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="p-4 bg-blue-50/10 rounded-lg">
                    <p class="text-sm text-gray-600 mb-1">สัญญาใหม่</p>
                    <p class="text-2xl font-bold text-blue-600"><?= number_format($newContracts) ?></p>
                </div>
                <div class="p-4 bg-green-50 rounded-lg">
                    <p class="text-sm text-gray-600 mb-1">สัญญาที่ใช้งานอยู่</p>
                    <p class="text-2xl font-bold text-green-600"><?= number_format($activeContracts) ?></p>
                </div>
                <div class="p-4 bg-orange-50 rounded-lg">
                    <p class="text-sm text-gray-600 mb-1">สัญญาหมดอายุ</p>
                    <p class="text-2xl font-bold text-orange-600"><?= number_format($expiredContracts) ?></p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="table table-zebra w-full table-sm">
                    <thead>
                        <tr>
                            <th>สัญญา</th>
                            <th>ลูกค้า</th>
                            <th>วันเริ่ม</th>
                            <th>วันสิ้นสุด</th>
                            <th>อุปกรณ์</th>
                            <th>มูลค่า</th>
                            <th>การชำระ</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($contractDetails) === 0): ?>
                            <tr>
                                <td colspan="8" class="text-center text-gray-500">ไม่มีข้อมูล</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($contractDetails as $contract): ?>
                                <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location.href='contract_viewer.php?rent_id=<?= $contract['rent_id'] ?>'">
                                    <td class="font-medium text-blue-600">
                                        #<?= $contract['rent_id'] ?>
                                        <i class="fas fa-arrow-right ml-1 text-xs"></i>
                                    </td>
                                    <td class="text-gray-600"><?= htmlspecialchars($contract['user_name']) ?></td>
                                    <td class="text-gray-600"><?= formatDateThai($contract['start_date']) ?></td>
                                    <td class="text-gray-600"><?= formatDateThai($contract['end_date']) ?></td>
                                    <td><span class="badge badge-info badge-sm"><?= $contract['device_count'] ?></span></td>
                                    <td class="font-semibold text-gray-600">฿<?= number_format($contract['total_amount']) ?></td>
                                    <td>
                                        <?php if ($contract['payment_type'] === 'all'): ?>
                                            <span class="badge badge-success badge-sm">เต็มจำนวน</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning badge-sm">ผ่อนชำระ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = 'badge-info';
                                        if ($contract['rent_status'] === 'คืนอุปกรณ์เรียบร้อย') $statusClass = 'badge-success';
                                        if ($contract['rent_status'] === 'เกินระยะเวลาคืน') $statusClass = 'badge-error';
                                        ?>
                                        <span class="badge <?= $statusClass ?> badge-sm"><?= $contract['rent_status'] ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php else: ?>
        <!-- Yearly View -->

        <!-- สถิติภาพรวมทั้งปี -->
        <div class="grid grid-cols-3 gap-4">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
                <div class="text-center">
                    <i class="fas fa-file-contract text-4xl mb-3"></i>
                    <h3 class="text-3xl font-bold"><?= number_format($newContracts) ?></h3>
                    <p class="text-sm opacity-90">สัญญาทั้งปี</p>
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                <div class="text-center">
                    <i class="fas fa-users text-4xl mb-3"></i>
                    <h3 class="text-3xl font-bold"><?= number_format($totalCustomers) ?></h3>
                    <p class="text-sm opacity-90">ลูกค้าทั้งปี</p>
                </div>
            </div>

            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-lg p-6 text-white">
                <div class="text-center">
                    <i class="fas fa-laptop text-4xl mb-3"></i>
                    <h3 class="text-3xl font-bold"><?= number_format($devicesRented) ?></h3>
                    <p class="text-sm opacity-90">อุปกรณ์ที่เช่า</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <!-- ข้อมูลผู้เช่า -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2 mb-4">
                    <i class="fas fa-users text-blue-500"></i>
                    Top 20 ผู้เช่าทั้งปี
                </h3>

                <div class="overflow-x-auto max-h-96 overflow-y-auto">
                    <table class="table table-zebra w-full table-sm">
                        <thead class="sticky top-0 bg-base-200">
                            <tr>
                                <th>#</th>
                                <th>ชื่อลูกค้า</th>
                                <th>สัญญา</th>
                                <th>มูลค่ารวม</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($customerList) === 0): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-gray-500">ไม่มีข้อมูล</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($customerList as $index => $customer): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td class="font-medium text-gray-600"><?= htmlspecialchars($customer['user_name']) ?></td>
                                        <td class="text-gray-600"><span class="badge badge-primary badge-sm"><?= $customer['contract_count'] ?></span></td>
                                        <td class="font-semibold text-green-600">฿<?= number_format($customer['total_spent']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ข้อมูลอุปกรณ์ -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2 mb-4">
                    <i class="fas fa-laptop text-orange-500"></i>
                    ข้อมูลอุปกรณ์
                </h3>

                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div class="p-3 bg-green-50 rounded-lg text-center">
                        <p class="text-2xl font-bold text-green-600"><?= number_format($deviceStatus['available']) ?></p>
                        <p class="text-sm text-gray-600">อุปกรณ์ว่าง</p>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-lg text-center">
                        <p class="text-2xl font-bold text-blue-600"><?= number_format($deviceStatus['rented']) ?></p>
                        <p class="text-sm text-gray-600">อุปกรณ์เช่าแล้ว</p>
                    </div>
                    <div class="p-3 bg-red-50 rounded-lg text-center">
                        <p class="text-2xl font-bold text-red-600"><?= number_format($brokenDevices['broken']) ?></p>
                        <p class="text-sm text-gray-600">อุปกรณ์เสีย</p>
                    </div>
                    <div class="p-3 bg-yellow-50 rounded-lg text-center">
                        <p class="text-2xl font-bold text-yellow-600"><?= number_format($brokenDevices['claim']) ?></p>
                        <p class="text-sm text-gray-600">ส่งเคลม</p>
                    </div>
                </div>

                <div class="mt-4">
                    <h4 class="font-semibold text-gray-700 mb-3">Top 10 โมเดลยอดนิยม</h4>
                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        <?php if (count($topModels) === 0): ?>
                            <p class="text-center text-gray-500 py-4">ไม่มีข้อมูล</p>
                        <?php else: ?>
                            <?php foreach ($topModels as $index => $model): ?>
                                <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-gradient-to-r from-blue-500 to-purple-500 flex items-center justify-center text-white text-xs font-bold">
                                            <?= $index + 1 ?>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium"><?= htmlspecialchars($model['model_name']) ?></p>
                                            <p class="text-xs text-gray-500"><?= htmlspecialchars($model['brand_name']) ?></p>
                                        </div>
                                    </div>
                                    <span class="badge badge-info badge-sm"><?= $model['rent_count'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- สรุปรายเดือน -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2 mb-6">
                <i class="fas fa-chart-bar text-green-500"></i>
                สรุปรายงานการเช่าแยกรายเดือน
            </h3>

            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>เดือน</th>
                            <th>จำนวนลูกค้า</th>
                            <th>จำนวนสัญญา</th>
                            <th>อุปกรณ์ที่เช่า</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($monthlyStats) === 0): ?>
                            <tr>
                                <td colspan="4" class="text-center text-gray-500">ไม่มีข้อมูล</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($monthlyStats as $stat): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="font-medium"><?= $thaiMonths[sprintf('%02d', $stat['month'])] ?></td>
                                    <td><span class="badge badge-purple"><?= number_format($stat['customers']) ?></span></td>
                                    <td><span class="badge badge-info"><?= number_format($stat['contracts']) ?></span></td>
                                    <td><span class="badge badge-warning"><?= number_format($stat['devices']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- รายละเอียดสัญญาทั้งหมด (Top 50) -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2 mb-4">
                <i class="fas fa-file-contract text-purple-500"></i>
                รายละเอียดสัญญาเช่าทั้งปี (Top 50)
            </h3>

            <div class="overflow-x-auto">
                <table class="table table-zebra w-full table-sm">
                    <thead>
                        <tr>
                            <th>สัญญา</th>
                            <th>ลูกค้า</th>
                            <th>วันเริ่ม</th>
                            <th>วันสิ้นสุด</th>
                            <th>อุปกรณ์</th>
                            <th>มูลค่า</th>
                            <th>การชำระ</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($contractDetails) === 0): ?>
                            <tr>
                                <td colspan="8" class="text-center text-gray-500">ไม่มีข้อมูล</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($contractDetails as $contract): ?>
                                <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location.href='contract_viewer.php?rent_id=<?= $contract['rent_id'] ?>'">
                                    <td class="font-medium text-blue-600">
                                        #<?= $contract['rent_id'] ?>
                                        <i class="fas fa-arrow-right ml-1 text-xs"></i>
                                    </td>
                                    <td><?= htmlspecialchars($contract['user_name']) ?></td>
                                    <td><?= formatDateThai($contract['start_date']) ?></td>
                                    <td><?= formatDateThai($contract['end_date']) ?></td>
                                    <td><span class="badge badge-info badge-sm"><?= $contract['device_count'] ?></span></td>
                                    <td class="font-semibold">฿<?= number_format($contract['total_amount']) ?></td>
                                    <td>
                                        <?php if ($contract['payment_type'] === 'all'): ?>
                                            <span class="badge badge-success badge-sm">เต็มจำนวน</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning badge-sm">ผ่อนชำระ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = 'badge-info';
                                        if ($contract['rent_status'] === 'คืนอุปกรณ์เรียบร้อย') $statusClass = 'badge-success';
                                        if ($contract['rent_status'] === 'เกินระยะเวลาคืน') $statusClass = 'badge-error';
                                        ?>
                                        <span class="badge <?= $statusClass ?> badge-sm"><?= $contract['rent_status'] ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    function generateReportPdf() {
        const element = document.getElementById('rental-report-root');
        if (!element) return;
        const opt = {
            margin: [12, 10, 12, 10],
            filename: `rental_report_<?= $viewType === 'month' ? $currentYear . $currentMonth : $currentYear ?>.pdf`,
            image: {
                type: 'jpeg',
                quality: 0.98
            },
            html2canvas: {
                scale: 2,
                useCORS: true
            },
            jsPDF: {
                unit: 'mm',
                format: 'a4',
                orientation: 'portrait'
            },
            pagebreak: {
                mode: ['css', 'legacy']
            }
        };
        // Clone to avoid modifying live DOM
        const clone = element.cloneNode(true);
        // Hide controls
        clone.querySelectorAll('.no-print').forEach(n => n.remove());
        // Force table borders for PDF clarity
        const style = document.createElement('style');
        style.textContent = `table{border-collapse:collapse}th,td{border:1px solid #e5e7eb;padding:6px}`;
        clone.prepend(style);
        html2pdf().set(opt).from(clone).save();
    }
</script>