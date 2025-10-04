<?php
require_once('./../conn.php');



$currentMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$currentYear = isset($_GET['year']) ? $_GET['year'] : date('Y');
$viewType = isset($_GET['view_type']) ? $_GET['view_type'] : 'month';

if ($viewType === 'month') {
    $monthStart = "$currentYear-$currentMonth-01";
    $monthEnd = date('Y-m-t', strtotime($monthStart));

    // รายได้
    $revenueQuery = "SELECT COALESCE(SUM(p.amount), 0) as total_revenue 
                     FROM payment p 
                     WHERE p.paid_at IS NOT NULL 
                     AND DATE(p.paid_at) BETWEEN ? AND ? AND p.status = 'ชำระแล้ว'";
    $stmt = $conn->prepare($revenueQuery);
    $stmt->bind_param("ss", $monthStart, $monthEnd);
    $stmt->execute();
    $revenue = $stmt->get_result()->fetch_assoc()['total_revenue'];

    // มูลค่าสัญญาใหม่
    $newContractValueQuery = "SELECT COALESCE(SUM(total_amount), 0) as total 
                              FROM rent 
                              WHERE start_date IS NOT NULL 
                              AND DATE(start_date) BETWEEN ? AND ?";
    $stmt = $conn->prepare($newContractValueQuery);
    $stmt->bind_param("ss", $monthStart, $monthEnd);
    $stmt->execute();
    $newContractValue = $stmt->get_result()->fetch_assoc()['total'];

    // ยอดค้างรับทั้งหมด (ยังไม่ชำระ)
    $pendingPaymentQuery = "SELECT COALESCE(SUM(p.amount), 0) as total
                            FROM payment p
                            WHERE p.status = 'ยังไม่ชำระ'";
    $stmt = $conn->prepare($pendingPaymentQuery);
    $stmt->execute();
    $pendingPayment = $stmt->get_result()->fetch_assoc()['total'];

    // ยอดค้างชำระ (เกินกำหนด)
    $overdueAmountQuery = "SELECT COALESCE(SUM(p.amount), 0) as total
                           FROM payment p
                           WHERE p.status = 'ยังไม่ชำระ' 
                           AND DATE(p.due_date) < CURDATE()";
    $stmt = $conn->prepare($overdueAmountQuery);
    $stmt->execute();
    $overdueAmount = $stmt->get_result()->fetch_assoc()['total'];

    // จำนวนลูกค้าค้างชำระ
    $overdueCustomersQuery = "SELECT COUNT(DISTINCT r.user_id) as cnt
                              FROM payment p
                              JOIN rent r ON p.rent_id = r.rent_id
                              WHERE p.status = 'ยังไม่ชำระ' 
                              AND DATE(p.due_date) < CURDATE()";
    $stmt = $conn->prepare($overdueCustomersQuery);
    $stmt->execute();
    $overdueCustomersCount = $stmt->get_result()->fetch_assoc()['cnt'];

    // รายละเอียดผู้ค้างชำระ
    $overdueListQuery = "SELECT u.user_name, u.phone, r.rent_id, 
                         COUNT(p.payment_id) as overdue_count,
                         SUM(p.amount) as overdue_amount,
                         MIN(p.due_date) as earliest_due,
                         MAX(p.due_date) as latest_due
                         FROM payment p
                         JOIN rent r ON p.rent_id = r.rent_id
                         JOIN user u ON r.user_id = u.user_id
                         WHERE p.status = 'ยังไม่ชำระ' 
                         AND DATE(p.due_date) < CURDATE()
                         GROUP BY r.rent_id
                         ORDER BY earliest_due ASC";
    $stmt = $conn->prepare($overdueListQuery);
    $stmt->execute();
    $overdueList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // รายละเอียดการชำระเงินในเดือนนี้
    $paymentHistoryQuery = "SELECT p.payment_id, p.due_date, p.paid_at, p.amount, 
                            p.status, p.type, r.rent_id, u.user_name
                            FROM payment p
                            JOIN rent r ON p.rent_id = r.rent_id
                            JOIN user u ON r.user_id = u.user_id
                            WHERE (p.paid_at IS NOT NULL AND DATE(p.paid_at) BETWEEN ? AND ?)
                            OR (p.status = 'ยังไม่ชำระ' AND p.due_date IS NOT NULL AND DATE(p.due_date) BETWEEN ? AND ?)
                            ORDER BY p.due_date DESC";
    $stmt = $conn->prepare($paymentHistoryQuery);
    $stmt->bind_param("ssss", $monthStart, $monthEnd, $monthStart, $monthEnd);
    $stmt->execute();
    $paymentHistory = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // รายได้แยกตามประเภท
    $revenueByTypeQuery = "SELECT p.type, SUM(p.amount) as total, COUNT(p.payment_id) as count
                           FROM payment p
                           WHERE p.paid_at IS NOT NULL 
                           AND DATE(p.paid_at) BETWEEN ? AND ? AND p.status = 'ชำระแล้ว'
                           GROUP BY p.type";
    $stmt = $conn->prepare($revenueByTypeQuery);
    $stmt->bind_param("ss", $monthStart, $monthEnd);
    $stmt->execute();
    $revenueByType = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    // Yearly View
    $yearStart = "$currentYear-01-01";
    $yearEnd = "$currentYear-12-31";

    // รายได้ทั้งปี
    $yearRevenueQuery = "SELECT COALESCE(SUM(amount), 0) as total 
                         FROM payment 
                         WHERE paid_at IS NOT NULL 
                         AND YEAR(paid_at) = ? AND status = 'ชำระแล้ว'";
    $stmt = $conn->prepare($yearRevenueQuery);
    $stmt->bind_param("i", $currentYear);
    $stmt->execute();
    $yearRevenue = $stmt->get_result()->fetch_assoc()['total'];

    // มูลค่าสัญญาทั้งปี
    $yearContractValueQuery = "SELECT COALESCE(SUM(total_amount), 0) as total 
                               FROM rent 
                               WHERE start_date IS NOT NULL 
                               AND YEAR(start_date) = ?";
    $stmt = $conn->prepare($yearContractValueQuery);
    $stmt->bind_param("i", $currentYear);
    $stmt->execute();
    $yearContractValue = $stmt->get_result()->fetch_assoc()['total'];

    // ยอดค้างรับทั้งหมด
    $pendingPaymentQuery = "SELECT COALESCE(SUM(p.amount), 0) as total
                            FROM payment p
                            WHERE p.status = 'ยังไม่ชำระ'";
    $stmt = $conn->prepare($pendingPaymentQuery);
    $stmt->execute();
    $pendingPayment = $stmt->get_result()->fetch_assoc()['total'];

    // ยอดค้างชำระ (เกินกำหนด)
    $overdueAmountQuery = "SELECT COALESCE(SUM(p.amount), 0) as total
                           FROM payment p
                           WHERE p.status = 'ยังไม่ชำระ' 
                           AND DATE(p.due_date) < CURDATE()";
    $stmt = $conn->prepare($overdueAmountQuery);
    $stmt->execute();
    $overdueAmount = $stmt->get_result()->fetch_assoc()['total'];

    // จำนวนลูกค้าค้างชำระ
    $overdueCustomersQuery = "SELECT COUNT(DISTINCT r.user_id) as cnt
                              FROM payment p
                              JOIN rent r ON p.rent_id = r.rent_id
                              WHERE p.status = 'ยังไม่ชำระ' 
                              AND DATE(p.due_date) < CURDATE()";
    $stmt = $conn->prepare($overdueCustomersQuery);
    $stmt->execute();
    $overdueCustomersCount = $stmt->get_result()->fetch_assoc()['cnt'];

    // รายละเอียดผู้ค้างชำระทั้งปี
    $overdueListQuery = "SELECT u.user_name, u.phone, r.rent_id, 
                         COUNT(p.payment_id) as overdue_count,
                         SUM(p.amount) as overdue_amount,
                         MIN(p.due_date) as earliest_due,
                         MAX(p.due_date) as latest_due
                         FROM payment p
                         JOIN rent r ON p.rent_id = r.rent_id
                         JOIN user u ON r.user_id = u.user_id
                         WHERE p.status = 'ยังไม่ชำระ' 
                         AND DATE(p.due_date) < CURDATE()
                         GROUP BY r.rent_id
                         ORDER BY earliest_due ASC
                         LIMIT 20";
    $stmt = $conn->prepare($overdueListQuery);
    $stmt->execute();
    $overdueList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // รายได้แยกตามประเภททั้งปี
    $revenueByTypeQuery = "SELECT p.type, SUM(p.amount) as total, COUNT(p.payment_id) as count
                           FROM payment p
                           WHERE p.paid_at IS NOT NULL 
                           AND YEAR(p.paid_at) = ? AND p.status = 'ชำระแล้ว'
                           GROUP BY p.type";
    $stmt = $conn->prepare($revenueByTypeQuery);
    $stmt->bind_param("i", $currentYear);
    $stmt->execute();
    $revenueByType = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // รายได้แยกตามเดือน
    $monthlyRevenueQuery = "SELECT 
        MONTH(paid_at) as month,
        SUM(amount) as revenue,
        COUNT(payment_id) as payment_count
        FROM payment
        WHERE paid_at IS NOT NULL 
        AND YEAR(paid_at) = ? AND status = 'ชำระแล้ว'
        GROUP BY MONTH(paid_at)
        ORDER BY month";
    $stmt = $conn->prepare($monthlyRevenueQuery);
    $stmt->bind_param("i", $currentYear);
    $stmt->execute();
    $monthlyRevenue = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // มูลค่าสัญญาแยกตามเดือน
    $monthlyContractValueQuery = "SELECT 
        MONTH(start_date) as month,
        SUM(total_amount) as contract_value,
        COUNT(rent_id) as contract_count
        FROM rent
        WHERE start_date IS NOT NULL 
        AND YEAR(start_date) = ?
        GROUP BY MONTH(start_date)
        ORDER BY month";
    $stmt = $conn->prepare($monthlyContractValueQuery);
    $stmt->bind_param("i", $currentYear);
    $stmt->execute();
    $monthlyContractValue = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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

if (!function_exists('formatDateTime')) {
    function formatDateTime($dateStr)
    {
        if (!$dateStr) return '-';
        return date('d/m/Y H:i', strtotime($dateStr));
    }
}
?>

<div class="space-y-6 text-black">
    <!-- Header -->
    <div class="bg-gradient-to-r from-green-600 to-teal-600 rounded-xl shadow-lg p-6 text-black">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-2xl font-bold">💰 รายงานการเงิน</h2>
                <p class="text-sm opacity-90 mt-1">
                    <?php if ($viewType === 'month'): ?>
                        เดือน<?= $thaiMonths[$currentMonth] ?> พ.ศ. <?= $currentYear + 543 ?>
                    <?php else: ?>
                        ปี พ.ศ. <?= $currentYear + 543 ?>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <form method="GET" class="flex items-end gap-4">
            <input type="hidden" name="tab" value="financial">

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
    </div>

    <?php if ($viewType === 'month'): ?>
        <!-- Monthly View -->

        <!-- สรุปการเงิน -->
        <div class="grid grid-cols-4 gap-4">
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-black">
                <div class="text-center">
                    <i class="fas fa-hand-holding-usd text-3xl mb-2"></i>
                    <h3 class="text-2xl font-bold">฿<?= number_format($revenue) ?></h3>
                    <p class="text-sm opacity-90">รายได้เก็บได้</p>
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-black">
                <div class="text-center">
                    <i class="fas fa-file-invoice-dollar text-3xl mb-2"></i>
                    <h3 class="text-2xl font-bold">฿<?= number_format($newContractValue) ?></h3>
                    <p class="text-sm opacity-90">มูลค่าสัญญาใหม่</p>
                </div>
            </div>

            <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg shadow-lg p-6 text-black">
                <div class="text-center">
                    <i class="fas fa-hourglass-half text-3xl mb-2"></i>
                    <h3 class="text-2xl font-bold">฿<?= number_format($pendingPayment) ?></h3>
                    <p class="text-sm opacity-90">ยอดค้างรับ</p>
                </div>
            </div>

            <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-lg shadow-lg p-6 text-black">
                <div class="text-center">
                    <i class="fas fa-exclamation-triangle text-3xl mb-2"></i>
                    <h3 class="text-2xl font-bold">฿<?= number_format($overdueAmount) ?></h3>
                    <p class="text-sm opacity-90">ค้างชำระ</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-6">
            <!-- รายได้แยกตามประเภท -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2 mb-4">
                    <i class="fas fa-chart-pie text-blue-500"></i>
                    รายได้แยกตามประเภท
                </h3>

                <div class="space-y-3">
                    <?php if (count($revenueByType) === 0): ?>
                        <p class="text-center text-gray-500 py-4">ไม่มีข้อมูล</p>
                    <?php else: ?>
                        <?php foreach ($revenueByType as $type): ?>
                            <div class="p-4 bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-medium text-gray-700">
                                        <?= $type['type'] === 'เต็มจำนวน' ? '💵 ชำระเต็มจำนวน' : '📅 ชำระแบบงวด' ?>
                                    </span>
                                    <span class="badge badge-info"><?= $type['count'] ?> รายการ</span>
                                </div>
                                <p class="text-2xl font-bold text-blue-600">฿<?= number_format($type['total'], 2) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- รายการค้างชำระ -->
            <div class="bg-white rounded-xl shadow-lg p-6 col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-exclamation-circle text-red-500"></i>
                        รายการค้างชำระ
                    </h3>
                    <span class="badge badge-error"><?= $overdueCustomersCount ?> ราย</span>
                </div>

                <div class="space-y-2 max-h-80 overflow-y-auto">
                    <?php if (count($overdueList) === 0): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-check-circle text-5xl text-green-300 mb-3"></i>
                            <p class="text-gray-500">ไม่มีรายการค้างชำระ 🎉</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($overdueList as $overdue): ?>
                            <a href="rent_payments.php?rent_id=<?= $overdue['rent_id'] ?>"
                                class="block border border-red-200 rounded-lg p-4 bg-red-50 hover:bg-red-100 hover:shadow-md transition cursor-pointer">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <p class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($overdue['user_name']) ?></p>
                                        <p class="text-sm text-gray-600 mt-1">
                                            <i class="fas fa-file-contract mr-1"></i>สัญญา #<?= $overdue['rent_id'] ?>
                                            <i class="fas fa-arrow-right ml-1 text-red-500"></i>
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            <i class="fas fa-phone mr-1"></i><?= htmlspecialchars($overdue['phone']) ?>
                                        </p>
                                        <p class="text-xs text-red-600 mt-2">
                                            <i class="fas fa-clock mr-1"></i>
                                            ครบกำหนด: <?= formatDateThai($overdue['earliest_due']) ?>
                                            <?php if ($overdue['overdue_count'] > 1): ?>
                                                - <?= formatDateThai($overdue['latest_due']) ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-bold text-red-600">฿<?= number_format($overdue['overdue_amount'], 2) ?></p>
                                        <p class="text-sm text-gray-600 mt-1"><?= $overdue['overdue_count'] ?> งวด</p>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ประวัติการชำระเงิน -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2 mb-4">
                <i class="fas fa-history text-purple-500"></i>
                ประวัติการชำระเงิน
            </h3>

            <div class="overflow-x-auto">
                <table class="table table-zebra w-full table-sm">
                    <thead>
                        <tr>
                            <th>รหัส</th>
                            <th>สัญญา</th>
                            <th>ลูกค้า</th>
                            <th>กำหนดชำระ</th>
                            <th>วันที่ชำระ</th>
                            <th>จำนวน</th>
                            <th>ประเภท</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($paymentHistory) === 0): ?>
                            <tr>
                                <td colspan="8" class="text-center text-gray-500">ไม่มีข้อมูล</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($paymentHistory as $payment): ?>
                                <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location.href='rent_payments.php?rent_id=<?= $payment['rent_id'] ?>'">
                                    <td class="font-medium text-blue-600">#<?= $payment['payment_id'] ?></td>
                                    <td class="font-medium text-blue-600">
                                        #<?= $payment['rent_id'] ?>
                                        <i class="fas fa-arrow-right ml-1 text-xs"></i>
                                    </td>
                                    <td><?= htmlspecialchars($payment['user_name']) ?></td>
                                    <td><?= formatDateThai($payment['due_date']) ?></td>
                                    <td><?= formatDateTime($payment['paid_at']) ?></td>
                                    <td class="font-bold text-green-600">฿<?= number_format($payment['amount'], 2) ?></td>
                                    <td>
                                        <?php if ($payment['type'] === 'เต็มจำนวน'): ?>
                                            <span class="badge badge-success badge-sm">เต็มจำนวน</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning badge-sm">งวด</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($payment['status'] === 'ชำระแล้ว'): ?>
                                            <span class="badge badge-success badge-sm">✓ ชำระแล้ว</span>
                                        <?php else: ?>
                                            <?php
                                            $isOverdue = strtotime($payment['due_date']) < time();
                                            $badgeClass = $isOverdue ? 'badge-error' : 'badge-warning';
                                            ?>
                                            <span class="badge <?= $badgeClass ?> badge-sm">
                                                <?= $isOverdue ? '⚠ เกินกำหนด' : '⏳ รอชำระ' ?>
                                            </span>
                                        <?php endif; ?>
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

        <!-- สรุปการเงินทั้งปี -->
        <div class="grid grid-cols-4 gap-4">
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-black">
                <div class="text-center">
                    <i class="fas fa-hand-holding-usd text-4xl mb-3"></i>
                    <p class="text-sm opacity-90 mb-2">รายได้ทั้งปี</p>
                    <p class="text-3xl font-bold">฿<?= number_format($yearRevenue) ?></p>
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-black">
                <div class="text-center">
                    <i class="fas fa-file-invoice-dollar text-4xl mb-3"></i>
                    <p class="text-sm opacity-90 mb-2">มูลค่าสัญญา</p>
                    <p class="text-3xl font-bold">฿<?= number_format($yearContractValue) ?></p>
                </div>
            </div>

            <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-6 text-black">
                <div class="text-center">
                    <i class="fas fa-hourglass-half text-4xl mb-3"></i>
                    <p class="text-sm opacity-90 mb-2">ยอดค้างรับ</p>
                    <p class="text-3xl font-bold">฿<?= number_format($pendingPayment) ?></p>
                </div>
            </div>

            <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-lg p-6 text-black">
                <div class="text-center">
                    <i class="fas fa-exclamation-triangle text-4xl mb-3"></i>
                    <p class="text-sm opacity-90 mb-2">ค้างชำระ</p>
                    <p class="text-3xl font-bold">฿<?= number_format($overdueAmount) ?></p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-6">
            <!-- รายได้แยกตามประเภท -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2 mb-4">
                    <i class="fas fa-chart-pie text-blue-500"></i>
                    รายได้แยกตามประเภท
                </h3>

                <div class="space-y-3">
                    <?php if (count($revenueByType) === 0): ?>
                        <p class="text-center text-gray-500 py-4">ไม่มีข้อมูล</p>
                    <?php else: ?>
                        <?php foreach ($revenueByType as $type): ?>
                            <div class="p-4 bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-medium text-gray-700">
                                        <?= $type['type'] === 'เต็มจำนวน' ? '💵 ชำระเต็มจำนวน' : '📅 ชำระแบบงวด' ?>
                                    </span>
                                    <span class="badge badge-info"><?= $type['count'] ?> รายการ</span>
                                </div>
                                <p class="text-2xl font-bold text-blue-600">฿<?= number_format($type['total'], 2) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- รายการค้างชำระ -->
            <div class="bg-white rounded-xl shadow-lg p-6 col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-exclamation-circle text-red-500"></i>
                        รายการค้างชำระ (Top 20)
                    </h3>
                    <span class="badge badge-error"><?= $overdueCustomersCount ?> ราย</span>
                </div>

                <div class="space-y-2 max-h-80 overflow-y-auto">
                    <?php if (count($overdueList) === 0): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-check-circle text-5xl text-green-300 mb-3"></i>
                            <p class="text-gray-500">ไม่มีรายการค้างชำระ 🎉</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($overdueList as $overdue): ?>
                            <a href="rent_payments.php?rent_id=<?= $overdue['rent_id'] ?>"
                                class="block border border-red-200 rounded-lg p-3 bg-red-50 hover:bg-red-100 transition cursor-pointer">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <p class="font-bold text-gray-800"><?= htmlspecialchars($overdue['user_name']) ?></p>
                                        <p class="text-xs text-gray-600 mt-1">
                                            <i class="fas fa-file-contract mr-1"></i>สัญญา #<?= $overdue['rent_id'] ?>
                                            <i class="fas fa-arrow-right ml-1 text-red-500"></i>
                                            <span class="ml-2"><i class="fas fa-phone mr-1"></i><?= htmlspecialchars($overdue['phone']) ?></span>
                                        </p>
                                        <p class="text-xs text-red-600 mt-1">
                                            <i class="fas fa-clock mr-1"></i>
                                            ครบกำหนด: <?= formatDateThai($overdue['earliest_due']) ?>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-bold text-red-600">฿<?= number_format($overdue['overdue_amount'], 2) ?></p>
                                        <p class="text-xs text-gray-600"><?= $overdue['overdue_count'] ?> งวด</p>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- สรุปรายเดือน -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2 mb-6">
                <i class="fas fa-chart-line text-green-500"></i>
                สรุปรายได้และมูลค่าสัญญาแยกรายเดือน
            </h3>

            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>เดือน</th>
                            <th>รายได้เก็บได้</th>
                            <th>จำนวนการชำระ</th>
                            <th>มูลค่าสัญญาใหม่</th>
                            <th>จำนวนสัญญา</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $revenueMap = [];
                        foreach ($monthlyRevenue as $rev) {
                            $revenueMap[$rev['month']] = $rev;
                        }
                        $contractMap = [];
                        foreach ($monthlyContractValue as $con) {
                            $contractMap[$con['month']] = $con;
                        }

                        for ($m = 1; $m <= 12; $m++):
                            $rev = isset($revenueMap[$m]) ? $revenueMap[$m] : ['revenue' => 0, 'payment_count' => 0];
                            $con = isset($contractMap[$m]) ? $contractMap[$m] : ['contract_value' => 0, 'contract_count' => 0];
                        ?>
                            <tr class="hover:bg-gray-50">
                                <td class="font-medium"><?= $thaiMonths[sprintf('%02d', $m)] ?></td>
                                <td class="font-bold text-green-600">฿<?= number_format($rev['revenue'], 2) ?></td>
                                <td><span class="badge badge-success"><?= number_format($rev['payment_count']) ?></span></td>
                                <td class="font-bold text-purple-600">฿<?= number_format($con['contract_value'], 2) ?></td>
                                <td><span class="badge badge-info"><?= number_format($con['contract_count']) ?></span></td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>