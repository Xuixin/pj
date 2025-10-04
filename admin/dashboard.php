<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('./../conn.php');

// ดึงข้อมูลนับจำนวนพนักงาน
$userCount = $conn->query("SELECT COUNT(*) as cnt FROM user")->fetch_assoc()['cnt'];

// ดึงจำนวนอุปกรณ์ทั้งหมดและสถานะต่าง ๆ
$statusCounts = $conn->query("
    SELECT machine_status, COUNT(*) as cnt 
    FROM rent_detail 
    GROUP BY machine_status
")->fetch_all(MYSQLI_ASSOC);

$statusSummary = ['ปกติ' => 0, 'ส่งเคลม' => 0, 'เสีย' => 0];
foreach ($statusCounts as $row) {
    $statusSummary[$row['machine_status']] = $row['cnt'];
}

$avalable_device = $conn->query("SELECT COUNT(device_id) FROM device WHERE device.status = 'ว่าง'")->fetch_assoc();
$unavalable_device = $conn->query("SELECT COUNT(device_id) FROM device WHERE device.status = 'เช่าแล้ว'")->fetch_assoc();

// ดึงรายการสัญญาที่จะหมดอายุใน 14 วัน
$expiringContracts = $conn->query("
    SELECT r.rent_id, r.user_id, r.end_date , u.user_name
    FROM rent r
    LEFT JOIN user u ON u.user_id = r.user_id
    WHERE end_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)
    ORDER BY end_date ASC
")->fetch_all(MYSQLI_ASSOC);

// ดึงรายการอุปกรณ์สถานะส่งเคลมหรือเสีย (5 รายการล่าสุด)
$brokenDevices = $conn->query("
    SELECT rd.rent_detail_id, d.serial_number, m.model_name, rd.machine_status, r.end_date
    FROM rent_detail rd
    JOIN device d ON rd.device_id = d.device_id
    JOIN model m ON d.model_id = m.model_id
    JOIN rent r ON rd.rent_id = r.rent_id
    WHERE rd.machine_status IN ('ส่งเคลม','เสีย')
    ORDER BY rd.create_At DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// ดึงรายการ PM ที่จะถึงกำหนดใน 30 วันข้างหน้า
$upcomingPMs = $conn->query("
    SELECT pm.pm_id, pm.pm_date, r.user_id, r.rent_id, u.user_name, DATE_ADD( COALESCE(pm.pm_date, r.start_date), INTERVAL 1 MONTH ) AS next_pm_date FROM rent r LEFT JOIN pm ON pm.rent_id = r.rent_id JOIN user u ON u.user_id = r.user_id WHERE DATE_ADD( COALESCE(pm.pm_date, r.start_date), INTERVAL 1 MONTH ) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY) ORDER BY next_pm_date ASC
")->fetch_all(MYSQLI_ASSOC);

// ฟังก์ชันช่วยแปลงวันที่ให้อ่านง่าย
function formatDateThai($dateStr)
{
    return date('d/m/Y', strtotime($dateStr));
}

// รับค่า tab จาก URL parameter
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <title>Dashboard - ระบบจัดการอุปกรณ์</title>
    <style>
        * {
            font-family: 'Kanit', sans-serif;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .stat-card.blue {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stat-card.green {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        .stat-card.red {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-normal {
            background: #dcfce7;
            color: #166534;
        }

        .status-claim {
            background: #fef3c7;
            color: #92400e;
        }

        .status-broken {
            background: #fee2e2;
            color: #991b1b;
        }

        .scrollbar-thin {
            scrollbar-width: thin;
            scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
        }

        .scrollbar-thin::-webkit-scrollbar {
            width: 6px;
        }

        .scrollbar-thin::-webkit-scrollbar-track {
            background: transparent;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.5);
            border-radius: 3px;
        }
    </style>
</head>

<body class="bg-gray-100">

    <div class="grid grid-cols-5 grid-rows-1 h-screen gap-2 min-h-screen">
        <!-- Sidebar -->
        <div class="">
            <div class="rounded-2xl inline-block h-full">
                <?php
                include('admin_component/sidebar.php')
                ?>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-span-4 z-20 row-span-1 col-start-2 row-start-1 py-3 pr-3 grid grid-cols-5 grid-rows-8 gap-6">
            <!-- Header -->
            <div class="col-span-5 row-span-1 bg-white rounded-2xl shadow-lg flex items-center justify-between px-8">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-gradient-to-r from-green-400 to-blue-500 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-white text-lg"></i>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-lg font-semibold text-gray-800">ยินดีต้อนรับ, <?php echo $_SESSION['admin_name'] ?></h1>
                        <p class="text-sm text-gray-500">ภาพรวมข้อมูล</p>
                    </div>
                </div>

            </div>

            <!-- name of each tab group should be unique -->
            <div class="tabs tabs-border col-span-5 row-span-7">
                <input type="radio" name="my_tabs_2" class="tab mr-4" aria-label="ภาพรวม"
                    <?= $activeTab === 'overview' ? 'checked="checked"' : '' ?>
                    onclick="window.location.href='?tab=overview'" />
                <div class="tab-content border-base-300 bg-base-100 p-10"><?php include('admin_component/general_content.php'); ?></div>

                <input type="radio" name="my_tabs_2" class="tab mr-4" aria-label="รายงานการเช่า"
                    <?= $activeTab === 'rental' ? 'checked="checked"' : '' ?>
                    onclick="window.location.href='?tab=rental'" />
                <div class="tab-content border-base-300 bg-base-100 p-10"><?php include('admin_component/rental_report.php'); ?></div>

                <input type="radio" name="my_tabs_2" class="tab mr-4" aria-label="รายงานการเงิน"
                    <?= $activeTab === 'financial' ? 'checked="checked"' : '' ?>
                    onclick="window.location.href='?tab=financial'" />
                <div class="tab-content border-base-300 bg-base-100 p-10"><?php include('admin_component/financial_report.php'); ?></div>
            </div>


        </div>
    </div>

    <?php include('./../lib/toast.php'); ?>

</body>

</html>