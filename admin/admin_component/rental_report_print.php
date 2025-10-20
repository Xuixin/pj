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

if ($viewType === 'month') {
    $monthStart = "$currentYear-$currentMonth-01";
    $monthEnd = date('Y-m-t', strtotime($monthStart));

    $stmt = $conn->prepare("SELECT COUNT(DISTINCT user_id) as cnt FROM rent WHERE start_date IS NOT NULL AND DATE(start_date) BETWEEN ? AND ?");
    $stmt->bind_param("ss", $monthStart, $monthEnd);
    $stmt->execute();
    $totalCustomers = $stmt->get_result()->fetch_assoc()['cnt'];

    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM rent WHERE start_date IS NOT NULL AND DATE(start_date) BETWEEN ? AND ?");
    $stmt->bind_param("ss", $monthStart, $monthEnd);
    $stmt->execute();
    $newContracts = $stmt->get_result()->fetch_assoc()['cnt'];

    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM rent WHERE start_date IS NOT NULL AND end_date IS NOT NULL AND DATE(start_date) <= ? AND DATE(end_date) >= ?");
    $stmt->bind_param("ss", $monthEnd, $monthStart);
    $stmt->execute();
    $activeContracts = $stmt->get_result()->fetch_assoc()['cnt'];

    $stmt = $conn->prepare("SELECT COUNT(DISTINCT rd.device_id) as cnt FROM rent_detail rd JOIN rent r ON rd.rent_id = r.rent_id WHERE r.start_date IS NOT NULL AND DATE(r.start_date) BETWEEN ? AND ?");
    $stmt->bind_param("ss", $monthStart, $monthEnd);
    $stmt->execute();
    $devicesRented = $stmt->get_result()->fetch_assoc()['cnt'];

    $stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM rent WHERE start_date IS NOT NULL AND DATE(start_date) BETWEEN ? AND ?");
    $stmt->bind_param("ss", $monthStart, $monthEnd);
    $stmt->execute();
    $totalRevenue = $stmt->get_result()->fetch_assoc()['total'];

    $stmt = $conn->prepare("SELECT 
        COALESCE(SUM(CASE WHEN status = 'ว่าง' THEN 1 ELSE 0 END), 0) as available,
        COALESCE(SUM(CASE WHEN status = 'เช่าแล้ว' THEN 1 ELSE 0 END), 0) as rented
        FROM device");
    $stmt->execute();
    $deviceStatus = $stmt->get_result()->fetch_assoc();

    $stmt = $conn->prepare("SELECT 
        COALESCE(SUM(CASE WHEN machine_status = 'เสีย' THEN 1 ELSE 0 END), 0) as broken,
        COALESCE(SUM(CASE WHEN machine_status = 'ส่งเคลม' THEN 1 ELSE 0 END), 0) as claim
        FROM rent_detail rd
        JOIN rent r ON rd.rent_id = r.rent_id
        WHERE r.end_date IS NOT NULL AND DATE(r.end_date) >= CURDATE()");
    $stmt->execute();
    $brokenDevices = $stmt->get_result()->fetch_assoc();

    $stmt = $conn->prepare("SELECT DISTINCT u.user_id, u.user_name, u.location, u.phone, 
        COUNT(r.rent_id) as contract_count, 
        SUM(r.total_amount) as total_spent 
        FROM rent r 
        JOIN user u ON r.user_id = u.user_id 
        WHERE r.start_date IS NOT NULL AND DATE(r.start_date) BETWEEN ? AND ? 
        GROUP BY u.user_id 
        ORDER BY contract_count DESC 
        LIMIT 20");
    $stmt->bind_param("ss", $monthStart, $monthEnd);
    $stmt->execute();
    $customerList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt = $conn->prepare("SELECT m.model_name, b.brand_name, COUNT(rd.device_id) as rent_count 
        FROM rent_detail rd 
        JOIN device d ON rd.device_id = d.device_id 
        JOIN model m ON d.model_id = m.model_id 
        JOIN brand b ON m.brand_id = b.brand_id 
        JOIN rent r ON rd.rent_id = r.rent_id 
        WHERE r.start_date IS NOT NULL AND DATE(r.start_date) BETWEEN ? AND ? 
        GROUP BY m.model_id 
        ORDER BY rent_count DESC 
        LIMIT 10");
    $stmt->bind_param("ss", $monthStart, $monthEnd);
    $stmt->execute();
    $topModels = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt = $conn->prepare("SELECT r.rent_id, u.user_name, r.start_date, r.end_date, 
        r.total_amount, r.rent_status, r.payment_type,
        COUNT(rd.device_id) as device_count
        FROM rent r
        JOIN user u ON r.user_id = u.user_id
        LEFT JOIN rent_detail rd ON r.rent_id = rd.rent_id
        WHERE r.start_date IS NOT NULL AND DATE(r.start_date) BETWEEN ? AND ?
        GROUP BY r.rent_id
        ORDER BY r.start_date DESC
        LIMIT 50");
    $stmt->bind_param("ss", $monthStart, $monthEnd);
    $stmt->execute();
    $contractDetails = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT user_id) as cnt FROM rent WHERE start_date IS NOT NULL AND YEAR(start_date) = ?");
    $stmt->bind_param("i", $currentYear);
    $stmt->execute();
    $totalCustomers = $stmt->get_result()->fetch_assoc()['cnt'];

    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM rent WHERE start_date IS NOT NULL AND YEAR(start_date) = ?");
    $stmt->bind_param("i", $currentYear);
    $stmt->execute();
    $newContracts = $stmt->get_result()->fetch_assoc()['cnt'];

    $stmt = $conn->prepare("SELECT COUNT(DISTINCT rd.device_id) as cnt FROM rent_detail rd JOIN rent r ON rd.rent_id = r.rent_id WHERE r.start_date IS NOT NULL AND YEAR(r.start_date) = ?");
    $stmt->bind_param("i", $currentYear);
    $stmt->execute();
    $devicesRented = $stmt->get_result()->fetch_assoc()['cnt'];

    $stmt = $conn->prepare("SELECT 
        COALESCE(SUM(CASE WHEN status = 'ว่าง' THEN 1 ELSE 0 END), 0) as available,
        COALESCE(SUM(CASE WHEN status = 'เช่าแล้ว' THEN 1 ELSE 0 END), 0) as rented
        FROM device");
    $stmt->execute();
    $deviceStatus = $stmt->get_result()->fetch_assoc();

    $stmt = $conn->prepare("SELECT 
        COALESCE(SUM(CASE WHEN machine_status = 'เสีย' THEN 1 ELSE 0 END), 0) as broken,
        COALESCE(SUM(CASE WHEN machine_status = 'ส่งเคลม' THEN 1 ELSE 0 END), 0) as claim
        FROM rent_detail rd
        JOIN rent r ON rd.rent_id = r.rent_id
        WHERE r.end_date IS NOT NULL AND DATE(r.end_date) >= CURDATE()");
    $stmt->execute();
    $brokenDevices = $stmt->get_result()->fetch_assoc();

    $stmt = $conn->prepare("SELECT m.model_name, b.brand_name, COUNT(rd.device_id) as rent_count
        FROM rent_detail rd
        JOIN device d ON rd.device_id = d.device_id
        JOIN model m ON d.model_id = m.model_id
        JOIN brand b ON m.brand_id = b.brand_id
        JOIN rent r ON rd.rent_id = r.rent_id
        WHERE r.start_date IS NOT NULL AND YEAR(r.start_date) = ?
        GROUP BY m.model_id
        ORDER BY rent_count DESC
        LIMIT 10");
    $stmt->bind_param("i", $currentYear);
    $stmt->execute();
    $topModels = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt = $conn->prepare("SELECT DISTINCT u.user_id, u.user_name, u.location, u.phone,
        COUNT(r.rent_id) as contract_count,
        SUM(r.total_amount) as total_spent
        FROM rent r
        JOIN user u ON r.user_id = u.user_id
        WHERE r.start_date IS NOT NULL AND YEAR(r.start_date) = ?
        GROUP BY u.user_id
        ORDER BY contract_count DESC
        LIMIT 20");
    $stmt->bind_param("i", $currentYear);
    $stmt->execute();
    $customerList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt = $conn->prepare("SELECT 
        MONTH(r.start_date) as month,
        COUNT(DISTINCT r.user_id) as customers,
        COUNT(r.rent_id) as contracts,
        COUNT(DISTINCT rd.device_id) as devices
        FROM rent r
        LEFT JOIN rent_detail rd ON r.rent_id = rd.rent_id
        WHERE r.start_date IS NOT NULL AND YEAR(r.start_date) = ?
        GROUP BY MONTH(r.start_date)
        ORDER BY month");
    $stmt->bind_param("i", $currentYear);
    $stmt->execute();
    $monthlyStats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt = $conn->prepare("SELECT r.rent_id, u.user_name, r.start_date, r.end_date, 
        r.total_amount, r.rent_status, r.payment_type,
        COUNT(rd.device_id) as device_count
        FROM rent r
        JOIN user u ON r.user_id = u.user_id
        LEFT JOIN rent_detail rd ON r.rent_id = rd.rent_id
        WHERE r.start_date IS NOT NULL AND YEAR(r.start_date) = ?
        GROUP BY r.rent_id
        ORDER BY r.start_date DESC
        LIMIT 50");
    $stmt->bind_param("i", $currentYear);
    $stmt->execute();
    $contractDetails = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
    <title>รายงานการเช่า</title>
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

        .report-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            transition: all .3s ease;
            backdrop-filter: blur(10px);
        }

        .btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
            transition: transform .3s ease, box-shadow .3s ease;
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .kpi-label {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .kpi-value {
            font-size: 36px;
            font-weight: 700;
            color: #1e293b;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .section-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
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
        }

        .full-width-card {
            grid-column: 1 / -1;
        }

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
            transition: background-color .2s ease;
        }

        tbody tr:hover {
            background-color: #f8fafc;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .text-center {
            text-align: center;
        }

        .text-number {
            font-weight: 600;
            color: #0f172a;
        }

        .text-currency {
            color: #059669;
            font-weight: 600;
        }

        .text-muted {
            color: #94a3b8;
            font-style: italic;
        }

        .rank-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            font-weight: 600;
            font-size: 13px;
        }

        .rank-1 {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            box-shadow: 0 2px 8px rgba(251, 191, 36, .4);
        }

        .rank-2 {
            background: linear-gradient(135deg, #94a3b8, #64748b);
            box-shadow: 0 2px 8px rgba(148, 163, 184, .4);
        }

        .rank-3 {
            background: linear-gradient(135deg, #fb923c, #ea580c);
            box-shadow: 0 2px 8px rgba(251, 146, 60, .4);
        }

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

            thead {
                display: table-header-group;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .kpi-value {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                color: #667eea !important;
                -webkit-text-fill-color: #667eea !important;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="report-header">
            <h1>📊 รายงานการเช่าอุปกรณ์</h1>
            <p class="subtitle">สรุปภาพรวมและรายละเอียดการดำเนินงาน</p>
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
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">สัญญาใหม่</div>
                    <div class="kpi-value"><?= number_format($newContracts) ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">สัญญาที่ใช้งาน</div>
                    <div class="kpi-value"><?= number_format($activeContracts) ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">จำนวนผู้เช่า</div>
                    <div class="kpi-value"><?= number_format($totalCustomers) ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">อุปกรณ์ที่เช่า</div>
                    <div class="kpi-value"><?= number_format($devicesRented) ?></div>
                </div>
            </div>
            <div class="section-grid">
                <div class="card">
                    <h3>🏆 ลูกค้าอันดับต้น</h3>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 60px;">อันดับ</th>
                                    <th>ชื่อลูกค้า</th>
                                    <th style="width: 150px;">สถานที่</th>
                                    <th style="width: 80px;" class="text-center">สัญญา</th>
                                    <th style="width: 120px;" class="text-center">มูลค่ารวม</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($customerList)): ?>
                                    <tr>
                                        <td colspan="5" class="text-muted" style="text-align:center">ไม่มีข้อมูล</td>
                                    </tr>
                                    <?php else: foreach ($customerList as $i => $c): ?>
                                        <tr>
                                            <td class="text-center"><span class="rank-number <?= $i === 0 ? 'rank-1' : ($i === 1 ? 'rank-2' : ($i === 2 ? 'rank-3' : '')) ?>"><?= $i + 1 ?></span></td>
                                            <td><?= h($c['user_name']) ?></td>
                                            <td><?= h($c['location']) ?></td>
                                            <td class="text-center text-number"><?= number_format($c['contract_count']) ?></td>
                                            <td class="text-center text-currency">฿<?= number_format($c['total_spent']) ?></td>
                                        </tr>
                                <?php endforeach;
                                endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card">
                    <h3>⭐ โมเดลยอดนิยม</h3>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 60px;">อันดับ</th>
                                    <th>โมเดล</th>
                                    <th style="width: 140px;">แบรนด์</th>
                                    <th style="width: 100px;" class="text-center">จำนวนเช่า</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topModels)): ?>
                                    <tr>
                                        <td colspan="4" class="text-muted" style="text-align:center">ไม่มีข้อมูล</td>
                                    </tr>
                                    <?php else: foreach ($topModels as $i => $m): ?>
                                        <tr>
                                            <td class="text-center"><span class="rank-number <?= $i === 0 ? 'rank-1' : ($i === 1 ? 'rank-2' : ($i === 2 ? 'rank-3' : '')) ?>"><?= $i + 1 ?></span></td>
                                            <td><?= h($m['model_name']) ?></td>
                                            <td><?= h($m['brand_name']) ?></td>
                                            <td class="text-center text-number"><?= number_format($m['rent_count']) ?></td>
                                        </tr>
                                <?php endforeach;
                                endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card full-width-card">
                <h3>📝 รายละเอียดสัญญาเช่า (ล่าสุด)</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 100px;">เลขสัญญา</th>
                                <th>ชื่อลูกค้า</th>
                                <th style="width: 110px;">วันเริ่มสัญญา</th>
                                <th style="width: 110px;">วันสิ้นสุด</th>
                                <th style="width: 80px;" class="text-center">อุปกรณ์</th>
                                <th style="width: 120px;" class="text-center">มูลค่า</th>
                                <th style="width: 110px;" class="text-center">การชำระ</th>
                                <th style="width: 100px;" class="text-center">สถานะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($contractDetails)): ?>
                                <tr>
                                    <td colspan="8" class="text-muted" style="text-align:center">ไม่มีข้อมูล</td>
                                </tr>
                                <?php else: foreach ($contractDetails as $row): ?>
                                    <tr>
                                        <td><strong>#<?= $row['rent_id'] ?></strong></td>
                                        <td><?= h($row['user_name']) ?></td>
                                        <td><?= formatDateThai($row['start_date']) ?></td>
                                        <td><?= formatDateThai($row['end_date']) ?></td>
                                        <td class="text-center text-number"><?= number_format($row['device_count']) ?></td>
                                        <td class="text-center text-currency">฿<?= number_format($row['total_amount']) ?></td>
                                        <td class="text-center"><?= $row['payment_type'] === 'all' ? 'เต็มจำนวน' : 'ผ่อนชำระ' ?></td>
                                        <td class="text-center"><?= h($row['rent_status']) ?></td>
                                    </tr>
                            <?php endforeach;
                            endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">สัญญาทั้งปี</div>
                    <div class="kpi-value"><?= number_format($newContracts) ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">ลูกค้าทั้งปี</div>
                    <div class="kpi-value"><?= number_format($totalCustomers) ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">อุปกรณ์ที่เช่า</div>
                    <div class="kpi-value"><?= number_format($devicesRented) ?></div>
                </div>
            </div>
            <div class="section-grid">
                <div class="card">
                    <h3>🏆 Top ผู้เช่าทั้งปี</h3>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 60px;">อันดับ</th>
                                    <th>ชื่อลูกค้า</th>
                                    <th style="width: 150px;">สัญญา</th>
                                    <th style="width: 120px;" class="text-center">มูลค่ารวม</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($customerList)): ?>
                                    <tr>
                                        <td colspan="4" class="text-muted" style="text-align:center">ไม่มีข้อมูล</td>
                                    </tr>
                                    <?php else: foreach ($customerList as $i => $c): ?>
                                        <tr>
                                            <td class="text-center"><span class="rank-number <?= $i === 0 ? 'rank-1' : ($i === 1 ? 'rank-2' : ($i === 2 ? 'rank-3' : '')) ?>"><?= $i + 1 ?></span></td>
                                            <td><?= h($c['user_name']) ?></td>
                                            <td><?= number_format($c['contract_count']) ?></td>
                                            <td class="text-center text-currency">฿<?= number_format($c['total_spent']) ?></td>
                                        </tr>
                                <?php endforeach;
                                endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card">
                    <h3>⭐ Top 10 โมเดลยอดนิยม</h3>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 60px;">อันดับ</th>
                                    <th>โมเดล</th>
                                    <th style="width: 140px;">แบรนด์</th>
                                    <th style="width: 100px;" class="text-center">จำนวนเช่า</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topModels)): ?>
                                    <tr>
                                        <td colspan="4" class="text-muted" style="text-align:center">ไม่มีข้อมูล</td>
                                    </tr>
                                    <?php else: foreach ($topModels as $i => $m): ?>
                                        <tr>
                                            <td class="text-center"><span class="rank-number <?= $i === 0 ? 'rank-1' : ($i === 1 ? 'rank-2' : ($i === 2 ? 'rank-3' : '')) ?>"><?= $i + 1 ?></span></td>
                                            <td><?= h($m['model_name']) ?></td>
                                            <td><?= h($m['brand_name']) ?></td>
                                            <td class="text-center text-number"><?= number_format($m['rent_count']) ?></td>
                                        </tr>
                                <?php endforeach;
                                endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card full-width-card">
                <h3>📅 สรุปรายเดือน</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:26%">เดือน</th>
                                <th style="width:20%">ลูกค้า</th>
                                <th style="width:22%">สัญญา</th>
                                <th style="width:22%">อุปกรณ์</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($monthlyStats ?? []) as $stat): ?>
                                <tr>
                                    <td><?= $thaiMonths[sprintf('%02d', $stat['month'])] ?></td>
                                    <td><?= number_format($stat['customers']) ?></td>
                                    <td><?= number_format($stat['contracts']) ?></td>
                                    <td><?= number_format($stat['devices']) ?></td>
                                </tr>
                            <?php endforeach;
                            if (empty($monthlyStats)): ?>
                                <tr>
                                    <td colspan="4" style="text-align:center;color:#6b7280">ไม่มีข้อมูล</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card full-width-card">
                <h3>📝 รายละเอียดสัญญาเช่า (Top 50)</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 100px;">เลขสัญญา</th>
                                <th>ชื่อลูกค้า</th>
                                <th style="width: 110px;">วันเริ่มสัญญา</th>
                                <th style="width: 110px;">วันสิ้นสุด</th>
                                <th style="width: 80px;" class="text-center">อุปกรณ์</th>
                                <th style="width: 120px;" class="text-center">มูลค่า</th>
                                <th style="width: 110px;" class="text-center">การชำระ</th>
                                <th style="width: 100px;" class="text-center">สถานะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($contractDetails)): ?>
                                <tr>
                                    <td colspan="8" style="text-align:center;color:#6b7280">ไม่มีข้อมูล</td>
                                </tr>
                                <?php else: foreach ($contractDetails as $row): ?>
                                    <tr>
                                        <td>#<?= $row['rent_id'] ?></td>
                                        <td><?= h($row['user_name']) ?></td>
                                        <td><?= formatDateThai($row['start_date']) ?></td>
                                        <td><?= formatDateThai($row['end_date']) ?></td>
                                        <td><?= number_format($row['device_count']) ?></td>
                                        <td>฿<?= number_format($row['total_amount']) ?></td>
                                        <td><?= $row['payment_type'] === 'all' ? 'เต็มจำนวน' : 'ผ่อนชำระ' ?></td>
                                        <td><?= h($row['rent_status']) ?></td>
                                    </tr>
                            <?php endforeach;
                            endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
        <script>
            function downloadPdf() {
                const element = document.body.cloneNode(true);
                element.querySelectorAll('.no-print').forEach(n => n.remove());
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
                html2pdf().set(opt).from(element).save();
            }
        </script>
</body>

</html>