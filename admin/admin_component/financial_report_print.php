<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('./../../conn.php');

$currentMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$currentYear = isset($_GET['year']) ? $_GET['year'] : date('Y');
$viewType = isset($_GET['view_type']) ? $_GET['view_type'] : 'month';

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

function h($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// ===== Fetch data (reuse logic from financial_report.php plus extras) =====
if ($viewType === 'month') {
    $monthStart = "$currentYear-$currentMonth-01";
    $monthEnd = date('Y-m-t', strtotime($monthStart));

    // Revenue collected this month
    $stmt = $conn->prepare("SELECT COALESCE(SUM(p.amount), 0) as total_revenue FROM payment p WHERE p.paid_at IS NOT NULL AND DATE(p.paid_at) BETWEEN ? AND ? AND p.status = 'ชำระแล้ว'");
    $stmt->bind_param("ss", $monthStart, $monthEnd);
    $stmt->execute();
    $revenue = $stmt->get_result()->fetch_assoc()['total_revenue'];

    // New contract value in month
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM rent WHERE start_date IS NOT NULL AND DATE(start_date) BETWEEN ? AND ?");
    $stmt->bind_param("ss", $monthStart, $monthEnd);
    $stmt->execute();
    $newContractValue = $stmt->get_result()->fetch_assoc()['total'];

    // Pending (all unpaid)
    $stmt = $conn->prepare("SELECT COALESCE(SUM(p.amount), 0) as total FROM payment p WHERE p.status = 'ยังไม่ชำระ'");
    $stmt->execute();
    $pendingPayment = $stmt->get_result()->fetch_assoc()['total'];

    // Overdue
    $stmt = $conn->prepare("SELECT COALESCE(SUM(p.amount), 0) as total FROM payment p WHERE p.status = 'ยังไม่ชำระ' AND DATE(p.due_date) < CURDATE()");
    $stmt->execute();
    $overdueAmount = $stmt->get_result()->fetch_assoc()['total'];

    // Overdue customers count
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT r.user_id) as cnt FROM payment p JOIN rent r ON p.rent_id = r.rent_id WHERE p.status='ยังไม่ชำระ' AND DATE(p.due_date) < CURDATE()");
    $stmt->execute();
    $overdueCustomersCount = $stmt->get_result()->fetch_assoc()['cnt'];

    // Overdue list
    $stmt = $conn->prepare("SELECT u.user_name, u.phone, r.rent_id, COUNT(p.payment_id) as overdue_count, SUM(p.amount) as overdue_amount, MIN(p.due_date) as earliest_due, MAX(p.due_date) as latest_due FROM payment p JOIN rent r ON p.rent_id = r.rent_id JOIN user u ON r.user_id = u.user_id WHERE p.status='ยังไม่ชำระ' AND DATE(p.due_date) < CURDATE() GROUP BY r.rent_id ORDER BY earliest_due ASC");
    $stmt->execute();
    $overdueList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Payment history in month
    $stmt = $conn->prepare("SELECT p.payment_id, p.due_date, p.paid_at, p.amount, p.status, p.type, r.rent_id, u.user_name FROM payment p JOIN rent r ON p.rent_id = r.rent_id JOIN user u ON r.user_id = u.user_id WHERE (p.paid_at IS NOT NULL AND DATE(p.paid_at) BETWEEN ? AND ?) OR (p.status = 'ยังไม่ชำระ' AND p.due_date IS NOT NULL AND DATE(p.due_date) BETWEEN ? AND ?) ORDER BY p.due_date DESC");
    $stmt->bind_param("ssss", $monthStart, $monthEnd, $monthStart, $monthEnd);
    $stmt->execute();
    $paymentHistory = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Revenue by type (month)
    $stmt = $conn->prepare("SELECT p.type, SUM(p.amount) as total, COUNT(p.payment_id) as count FROM payment p WHERE p.paid_at IS NOT NULL AND DATE(p.paid_at) BETWEEN ? AND ? AND p.status='ชำระแล้ว' GROUP BY p.type");
    $stmt->bind_param("ss", $monthStart, $monthEnd);
    $stmt->execute();
    $revenueByType = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Aging buckets (unpaid only)
    $agingSql = "SELECT 
        COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), DATE(due_date)) BETWEEN 1 AND 7 THEN amount ELSE 0 END),0) AS d1_7,
        COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), DATE(due_date)) BETWEEN 8 AND 30 THEN amount ELSE 0 END),0) AS d8_30,
        COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), DATE(due_date)) BETWEEN 31 AND 60 THEN amount ELSE 0 END),0) AS d31_60,
        COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), DATE(due_date)) > 60 THEN amount ELSE 0 END),0) AS d60p,
        COUNT(CASE WHEN DATEDIFF(CURDATE(), DATE(due_date)) >= 1 THEN 1 END) AS overdue_count
        FROM payment WHERE status='ยังไม่ชำระ' AND due_date IS NOT NULL";
    $aging = $conn->query($agingSql)->fetch_assoc();

    // Collection rate (collected / due this month)
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) AS due_in_month FROM payment WHERE due_date IS NOT NULL AND DATE(due_date) BETWEEN ? AND ?");
    $stmt->bind_param("ss", $monthStart, $monthEnd);
    $stmt->execute();
    $dueInMonth = $stmt->get_result()->fetch_assoc()['due_in_month'];
    $collectionRate = ($dueInMonth > 0) ? ($revenue / $dueInMonth * 100.0) : 0.0;

    // Cash forecast next 30/60/90 days for unpaid
    $forecast = [];
    foreach ([30, 60, 90] as $days) {
        $stmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM payment WHERE status='ยังไม่ชำระ' AND due_date IS NOT NULL AND DATE(due_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)");
        $stmt->bind_param("i", $days);
        $stmt->execute();
        $forecast[$days] = $stmt->get_result()->fetch_assoc()['total'];
    }
} else {
    // Year view
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM payment WHERE paid_at IS NOT NULL AND YEAR(paid_at) = ? AND status='ชำระแล้ว'");
    $stmt->bind_param("i", $currentYear);
    $stmt->execute();
    $yearRevenue = $stmt->get_result()->fetch_assoc()['total'];

    $stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount),0) AS total FROM rent WHERE start_date IS NOT NULL AND YEAR(start_date) = ?");
    $stmt->bind_param("i", $currentYear);
    $stmt->execute();
    $yearContractValue = $stmt->get_result()->fetch_assoc()['total'];

    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM payment WHERE status='ยังไม่ชำระ'");
    $stmt->execute();
    $pendingPayment = $stmt->get_result()->fetch_assoc()['total'];

    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM payment WHERE status='ยังไม่ชำระ' AND DATE(due_date) < CURDATE()");
    $stmt->execute();
    $overdueAmount = $stmt->get_result()->fetch_assoc()['total'];

    // Revenue by type in year
    $stmt = $conn->prepare("SELECT p.type, SUM(p.amount) as total, COUNT(p.payment_id) as count FROM payment p WHERE p.paid_at IS NOT NULL AND YEAR(p.paid_at) = ? AND p.status='ชำระแล้ว' GROUP BY p.type");
    $stmt->bind_param("i", $currentYear);
    $stmt->execute();
    $revenueByTypeYear = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Monthly revenue (count) in year
    $stmt = $conn->prepare("SELECT MONTH(paid_at) as month, SUM(amount) as revenue, COUNT(payment_id) as payment_count FROM payment WHERE paid_at IS NOT NULL AND YEAR(paid_at) = ? AND status='ชำระแล้ว' GROUP BY MONTH(paid_at) ORDER BY month");
    $stmt->bind_param("i", $currentYear);
    $stmt->execute();
    $monthlyRevenue = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Monthly contract values
    $stmt = $conn->prepare("SELECT MONTH(start_date) as month, SUM(total_amount) as contract_value, COUNT(rent_id) as contract_count FROM rent WHERE start_date IS NOT NULL AND YEAR(start_date) = ? GROUP BY MONTH(start_date) ORDER BY month");
    $stmt->bind_param("i", $currentYear);
    $stmt->execute();
    $monthlyContractValue = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // DSO approximation
    $periodDays = ($currentYear == date('Y')) ? (int)date('z') + 1 : 365;
    $dailyAvgRevenue = $periodDays > 0 ? ($yearRevenue / $periodDays) : 0;
    $dsoDays = ($dailyAvgRevenue > 0) ? ($pendingPayment / $dailyAvgRevenue) : 0;
}

function formatDateThai($dateStr)
{
    if (!$dateStr) return '-';
    return date('d/m/Y', strtotime($dateStr));
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานการเงิน</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: A4 portrait;
            margin: 15mm 12mm;
        }

        body {
            font-family: 'Sarabun', Arial, sans-serif;
            color: #1e293b;
            background: #f8fafc;
            line-height: 1.5;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        /* Header Styles */
        .report-header {
            background: linear-gradient(135deg, #059669 0%, #14b8a6 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .report-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .report-header::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
        }

        .report-header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }

        .report-header .subtitle {
            font-size: 16px;
            opacity: 0.95;
            font-weight: 400;
            position: relative;
            z-index: 1;
        }

        .report-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 1;
        }

        .report-date {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Action Buttons */
        .actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        /* KPI Cards Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .kpi-card {
            background: linear-gradient(135deg, #f6f8fb 0%, #ffffff 100%);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            border: 1px solid #e2e8f0;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #059669, #14b8a6);
        }

        .kpi-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -10px rgba(5, 150, 105, 0.3);
        }

        .kpi-card.warning::before {
            background: linear-gradient(90deg, #f59e0b, #ef4444);
        }

        .kpi-card.success::before {
            background: linear-gradient(90deg, #10b981, #059669);
        }

        .kpi-label {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .kpi-value {
            font-size: 32px;
            font-weight: 700;
            color: #1e293b;
        }

        .kpi-value.success {
            background: linear-gradient(135deg, #10b981, #059669);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .kpi-value.warning {
            background: linear-gradient(135deg, #f59e0b, #ef4444);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .kpi-value.info {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Section Cards */
        .section-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
        }

        .card h3 {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .full-width-card {
            grid-column: 1 / -1;
        }

        /* Table Styles */
        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        thead {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }

        thead th {
            padding: 14px 12px;
            text-align: left;
            font-weight: 600;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }

        tbody td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }

        tbody tr {
            transition: background-color 0.2s ease;
        }

        tbody tr:hover {
            background-color: #f8fafc;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr.overdue {
            background-color: #fef2f2;
        }

        tbody tr.overdue:hover {
            background-color: #fee2e2;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-muted {
            color: #94a3b8;
            font-style: italic;
        }

        .text-number {
            font-weight: 600;
            color: #0f172a;
        }

        .text-currency {
            color: #059669;
            font-weight: 600;
        }

        .text-currency.negative {
            color: #dc2626;
        }

        /* Badge Styles */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }

        /* Alert Box */
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            color: #92400e;
        }

        .alert-info {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            color: #1e40af;
        }

        /* Progress Bar */
        .progress-bar {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 8px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #059669);
            transition: width 0.3s ease;
        }

        .progress-fill.warning {
            background: linear-gradient(90deg, #f59e0b, #ef4444);
        }

        /* Stat Item */
        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .stat-item:last-child {
            border-bottom: none;
        }

        .stat-label {
            color: #64748b;
            font-size: 14px;
        }

        .stat-value {
            font-weight: 600;
            color: #1e293b;
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }

            .container {
                box-shadow: none;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .report-header {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                color-adjust: exact;
                page-break-after: avoid;
            }

            .kpi-card,
            .card {
                page-break-inside: avoid;
                box-shadow: none;
            }

            .kpi-card::before {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            thead {
                display: table-header-group;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .kpi-value.success,
            .kpi-value.warning,
            .kpi-value.info {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .badge {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            tbody tr.overdue {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .report-header h1 {
                font-size: 24px;
            }

            .kpi-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .section-grid {
                grid-template-columns: 1fr;
            }

            .kpi-value {
                font-size: 24px;
            }

            table {
                font-size: 12px;
            }

            thead th,
            tbody td {
                padding: 8px 6px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="report-header">
            <h1>💰 รายงานการเงินและการชำระเงิน</h1>
            <p class="subtitle">ภาพรวมรายได้ ค้างชำระ และประวัติการเงิน</p>
            <div class="report-meta">
                <div class="report-date">
                    🗓️ ประจำ<?= $viewType === 'month' ? 'เดือน: <strong>' . $thaiMonths[$currentMonth] . ' พ.ศ. ' . ($currentYear + 543) . '</strong>' : 'ปี พ.ศ. <strong>' . ($currentYear + 543) . '</strong>' ?>
                </div>
                <div class="actions no-print">
                    <button class="btn" onclick="window.print()">🖨️ พิมพ์รายงาน</button>
                </div>
            </div>
        </div>

        <?php if ($viewType === 'month'): ?>
            <!-- Alert for Overdue -->
            <?php if ($overdueAmount > 0): ?>
                <div class="alert alert-warning no-print">
                    <span style="font-size: 24px;">⚠️</span>
                    <div>
                        <strong>แจ้งเตือน:</strong> มีลูกค้า <strong><?= number_format($overdueCustomersCount) ?> ราย</strong> ที่ค้างชำระเกินกำหนด รวมเป็นเงิน <strong>฿<?= number_format($overdueAmount) ?></strong>
                    </div>
                </div>
            <?php endif; ?>

            <!-- KPI Cards -->
            <div class="kpi-grid">
                <div class="kpi-card success">
                    <div class="kpi-label">รายได้เก็บได้เดือนนี้</div>
                    <div class="kpi-value success">฿<?= number_format($revenue) ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">มูลค่าสัญญาใหม่</div>
                    <div class="kpi-value info">฿<?= number_format($newContractValue) ?></div>
                </div>
                <div class="kpi-card warning">
                    <div class="kpi-label">ค้างรับทั้งหมด</div>
                    <div class="kpi-value warning">฿<?= number_format($pendingPayment) ?></div>
                </div>
                <div class="kpi-card warning">
                    <div class="kpi-label">ค้างชำระเกินกำหนด</div>
                    <div class="kpi-value warning">฿<?= number_format($overdueAmount) ?></div>
                </div>
            </div>

            <!-- Secondary KPIs -->
            <div class="kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
                <div class="kpi-card">
                    <div class="kpi-label">ลูกค้าที่ค้างชำระ</div>
                    <div class="kpi-value"><?= number_format($overdueCustomersCount) ?> ราย</div>
                </div>
                <div class="kpi-card success">
                    <div class="kpi-label">อัตราเก็บหนี้เดือนนี้</div>
                    <div class="kpi-value success"><?= number_format($collectionRate, 1) ?>%</div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= min($collectionRate, 100) ?>%"></div>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">ครบกำหนดเดือนนี้</div>
                    <div class="kpi-value info">฿<?= number_format($dueInMonth) ?></div>
                </div>
            </div>

            <!-- Revenue & Aging Section -->
            <div class="section-grid">
                <div class="card">
                    <h3>💵 รายได้แยกตามประเภท</h3>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ประเภทการชำระ</th>
                                    <th style="width: 30%;" class="text-center">จำนวนครั้ง</th>
                                    <th style="width: 35%;" class="text-right">ยอดรวม</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($revenueByType)): ?>
                                    <tr>
                                        <td colspan="3" class="text-muted" style="text-align:center">ไม่มีข้อมูล</td>
                                    </tr>
                                    <?php else:
                                    $totalCount = 0;
                                    $totalAmount = 0;
                                    foreach ($revenueByType as $t):
                                        $totalCount += $t['count'];
                                        $totalAmount += $t['total'];
                                    ?>
                                        <tr>
                                            <td><?= h($t['type']) ?></td>
                                            <td class="text-center text-number"><?= number_format($t['count']) ?></td>
                                            <td class="text-right text-currency">฿<?= number_format($t['total'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr style="background: #f8fafc; font-weight: 600;">
                                        <td><strong>รวมทั้งหมด</strong></td>
                                        <td class="text-center"><strong><?= number_format($totalCount) ?></strong></td>
                                        <td class="text-right" style="color: #059669;"><strong>฿<?= number_format($totalAmount, 2) ?></strong></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <h3>📊 Aging ค้างชำระ</h3>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ช่วงเวลา</th>
                                    <th style="width: 50%;" class="text-right">ยอดค้าง</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1–7 วัน</td>
                                    <td class="text-right text-currency">฿<?= number_format($aging['d1_7']) ?></td>
                                </tr>
                                <tr>
                                    <td>8–30 วัน</td>
                                    <td class="text-right" style="color: #f59e0b; font-weight: 600;">฿<?= number_format($aging['d8_30']) ?></td>
                                </tr>
                                <tr>
                                    <td>31–60 วัน</td>
                                    <td class="text-right" style="color: #ef4444; font-weight: 600;">฿<?= number_format($aging['d31_60']) ?></td>
                                </tr>
                                <tr>
                                    <td>> 60 วัน</td>
                                    <td class="text-right" style="color: #dc2626; font-weight: 700;">฿<?= number_format($aging['d60p']) ?></td>
                                </tr>
                                <tr style="background: #fef2f2; font-weight: 600;">
                                    <td><strong>รวมค้างชำระทั้งหมด</strong></td>
                                    <td class="text-right" style="color: #dc2626;"><strong>฿<?= number_format($aging['d1_7'] + $aging['d8_30'] + $aging['d31_60'] + $aging['d60p']) ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="text-muted" style="margin-top: 12px; padding: 8px; background: #f8fafc; border-radius: 6px;">
                            📌 จำนวนสัญญาค้างชำระ: <strong style="color: #1e293b;"><?= number_format($aging['overdue_count']) ?> สัญญา</strong>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Cash Forecast -->
            <div class="kpi-grid" style="grid-template-columns: repeat(3, 1fr);">
                <div class="card" style="padding: 20px; text-align: center;">
                    <div style="color: #64748b; font-size: 14px; margin-bottom: 8px;">คาดว่าจะเข้า 30 วัน</div>
                    <div style="font-size: 28px; font-weight: 700; color: #3b82f6;">฿<?= number_format($forecast[30]) ?></div>
                </div>
                <div class="card" style="padding: 20px; text-align: center;">
                    <div style="color: #64748b; font-size: 14px; margin-bottom: 8px;">คาดว่าจะเข้า 60 วัน</div>
                    <div style="font-size: 28px; font-weight: 700; color: #8b5cf6;">฿<?= number_format($forecast[60]) ?></div>
                </div>
                <div class="card" style="padding: 20px; text-align: center;">
                    <div style="color: #64748b; font-size: 14px; margin-bottom: 8px;">คาดว่าจะเข้า 90 วัน</div>
                    <div style="font-size: 28px; font-weight: 700; color: #10b981;">฿<?= number_format($forecast[90]) ?></div>
                </div>
            </div>

            <!-- Overdue List -->
            <div class="card full-width-card">
                <h3>⚠️ รายการค้างชำระเกินกำหนด</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 12%;">สัญญา</th>
                                <th>ชื่อลูกค้า</th>
                                <th style="width: 16%;" class="text-right">ยอดค้าง</th>
                                <th style="width: 22%;" class="text-center">วันครบกำหนด (แรก-ล่าสุด)</th>
                                <th style="width: 12%;" class="text-center">งวดค้าง</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($overdueList)): ?>
                                <tr>
                                    <td colspan="5" class="text-muted" style="text-align:center">ไม่มีข้อมูล</td>
                                </tr>
                                <?php else: foreach ($overdueList as $o): ?>
                                    <tr class="overdue">
                                        <td><strong>#<?= $o['rent_id'] ?></strong></td>
                                        <td><?= h($o['user_name']) ?> (<?= h($o['phone']) ?>)</td>
                                        <td class="text-right text-currency negative">฿<?= number_format($o['overdue_amount'], 2) ?></td>
                                        <td class="text-center"><?= formatDateThai($o['earliest_due']) ?> - <?= formatDateThai($o['latest_due']) ?></td>
                                        <td class="text-center"><span class="badge badge-danger"><?= number_format($o['overdue_count']) ?></span></td>
                                    </tr>
                            <?php endforeach;
                            endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card full-width-card">
                <h3>📋 ประวัติการรับชำระ (ในเดือน)</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:10%">รหัส</th>
                                <th style="width:12%">สัญญา</th>
                                <th>ลูกค้า</th>
                                <th style="width:16%">กำหนดชำระ</th>
                                <th style="width:16%">วันที่ชำระ</th>
                                <th style="width:16%">จำนวน</th>
                                <th style="width:12%">ประเภท</th>
                                <th style="width:12%">สถานะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($paymentHistory)): ?>
                                <tr>
                                    <td colspan="8" class="text-muted" style="text-align:center">ไม่มีข้อมูล</td>
                                </tr>
                                <?php else: foreach ($paymentHistory as $p): ?>
                                    <tr>
                                        <td>#<?= $p['payment_id'] ?></td>
                                        <td>#<?= $p['rent_id'] ?></td>
                                        <td><?= h($p['user_name']) ?></td>
                                        <td><?= formatDateThai($p['due_date']) ?></td>
                                        <td><?= $p['paid_at'] ? formatDateThai($p['paid_at']) : '-' ?></td>
                                        <td class="text-currency">฿<?= number_format($p['amount'], 2) ?></td>
                                        <td><?= h($p['type']) ?></td>
                                        <td>
                                            <span class="badge <?= $p['status'] === 'ชำระแล้ว' ? 'badge-success' : 'badge-warning' ?>">
                                                <?= h($p['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                            <?php endforeach;
                            endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php else: ?>
            <!-- Year View KPIs -->
            <div class="kpi-grid">
                <div class="kpi-card success">
                    <div class="kpi-label">รายได้ทั้งปี</div>
                    <div class="kpi-value success">฿<?= number_format($yearRevenue) ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">มูลค่าสัญญาทั้งปี</div>
                    <div class="kpi-value info">฿<?= number_format($yearContractValue) ?></div>
                </div>
                <div class="kpi-card warning">
                    <div class="kpi-label">ยอดค้างรับ</div>
                    <div class="kpi-value warning">฿<?= number_format($pendingPayment) ?></div>
                </div>
                <div class="kpi-card warning">
                    <div class="kpi-label">ค้างชำระเกินกำหนด</div>
                    <div class="kpi-value warning">฿<?= number_format($overdueAmount) ?></div>
                </div>
            </div>

            <div class="kpi-grid" style="grid-template-columns: 1fr;">
                <div class="card" style="padding: 20px; text-align: center;">
                    <div style="color: #64748b; font-size: 14px; margin-bottom: 8px;">DSO (Days Sales Outstanding)</div>
                    <div style="font-size: 32px; font-weight: 700; color: #3b82f6;"><?= number_format($dsoDays, 1) ?> วัน</div>
                    <p class="text-muted" style="margin-top: 8px;">ระยะเวลาเฉลี่ยในการเก็บเงินค้างรับ</p>
                </div>
            </div>

            <div class="section-grid">
                <div class="card">
                    <h3>💵 รายได้แยกตามประเภท (ทั้งปี)</h3>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ประเภทการชำระ</th>
                                    <th style="width: 30%;" class="text-center">จำนวนครั้ง</th>
                                    <th style="width: 35%;" class="text-right">ยอดรวม</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($revenueByTypeYear)): ?>
                                    <tr>
                                        <td colspan="3" class="text-muted" style="text-align:center">ไม่มีข้อมูล</td>
                                    </tr>
                                    <?php else:
                                    $totalCount = 0;
                                    $totalAmount = 0;
                                    foreach ($revenueByTypeYear as $t):
                                        $totalCount += $t['count'];
                                        $totalAmount += $t['total'];
                                    ?>
                                        <tr>
                                            <td><?= h($t['type']) ?></td>
                                            <td class="text-center text-number"><?= number_format($t['count']) ?></td>
                                            <td class="text-right text-currency">฿<?= number_format($t['total'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr style="background: #f8fafc; font-weight: 600;">
                                        <td><strong>รวมทั้งหมด</strong></td>
                                        <td class="text-center"><strong><?= number_format($totalCount) ?></strong></td>
                                        <td class="text-right" style="color: #059669;"><strong>฿<?= number_format($totalAmount, 2) ?></strong></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <h3>📈 สรุปรายเดือน</h3>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>เดือน</th>
                                    <th style="width: 28%;" class="text-right">รายได้</th>
                                    <th style="width: 18%;" class="text-center">จำนวน</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $revMap = [];
                                foreach (($monthlyRevenue ?? []) as $r) {
                                    $revMap[$r['month']] = $r;
                                }
                                for ($m = 1; $m <= 12; $m++):
                                    $rv = isset($revMap[$m]) ? $revMap[$m] : ['revenue' => 0, 'payment_count' => 0];
                                ?>
                                    <tr>
                                        <td><?= $thaiMonths[sprintf('%02d', $m)] ?></td>
                                        <td class="text-right text-currency">฿<?= number_format($rv['revenue'], 2) ?></td>
                                        <td class="text-center"><?= number_format($rv['payment_count']) ?></td>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card full-width-card">
                <h3>📊 รายได้และมูลค่าสัญญาแยกรายเดือน</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>เดือน</th>
                                <th style="width: 18%;" class="text-right">รายได้</th>
                                <th style="width: 14%;" class="text-center">จำนวนจ่าย</th>
                                <th style="width: 18%;" class="text-right">มูลค่าสัญญา</th>
                                <th style="width: 14%;" class="text-center">จำนวนสัญญา</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $revMap = [];
                            foreach (($monthlyRevenue ?? []) as $r) {
                                $revMap[$r['month']] = $r;
                            }
                            $conMap = [];
                            foreach (($monthlyContractValue ?? []) as $c) {
                                $conMap[$c['month']] = $c;
                            }
                            for ($m = 1; $m <= 12; $m++):
                                $rv = isset($revMap[$m]) ? $revMap[$m] : ['revenue' => 0, 'payment_count' => 0];
                                $cv = isset($conMap[$m]) ? $conMap[$m] : ['contract_value' => 0, 'contract_count' => 0];
                            ?>
                                <tr>
                                    <td><?= $thaiMonths[sprintf('%02d', $m)] ?></td>
                                    <td class="text-right text-currency">฿<?= number_format($rv['revenue'], 2) ?></td>
                                    <td class="text-center"><?= number_format($rv['payment_count']) ?></td>
                                    <td class="text-right" style="color: #3b82f6; font-weight: 600;">฿<?= number_format($cv['contract_value'], 2) ?></td>
                                    <td class="text-center"><?= number_format($cv['contract_count']) ?></td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadPdf() {
            const element = document.body.cloneNode(true);
            element.querySelectorAll('.no-print').forEach(n => n.remove());
            const opt = {
                margin: [12, 10, 12, 10],
                filename: `financial_report_<?= $viewType === 'month' ? $currentYear . $currentMonth : $currentYear ?>.pdf`,
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
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>

</html>