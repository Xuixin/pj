<?php
// contract_viewer.php
session_start();
require_once('./../conn.php');

$rent_id = isset($_GET['rent_id']) ? intval($_GET['rent_id']) : 0;

if ($rent_id <= 0) {
    header('Location: rent.php');
    exit;
}

// Get contract details
$sql = "
    SELECT r.*, u.user_name
    FROM rent r
    LEFT JOIN user u ON u.user_id = r.user_id
    WHERE r.rent_id = ?
";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    die("SQL Error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $rent_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}

if (mysqli_num_rows($result) === 0) {
    header('Location: my_contracts.php');
    exit;
}

$contract_data = mysqli_fetch_assoc($result);

// Get rent details (devices)
$sql_device = "SELECT rd.rent_detail_id, rd.device_id, rd.machine_status, d.serial_number, m.model_id, m.model_name, t.type_name 
                FROM rent_detail rd 
                LEFT JOIN device d ON d.device_id = rd.device_id 
                LEFT JOIN model m ON m.model_id = d.model_id 
                LEFT JOIN type t ON t.type_id = m.type_id
                WHERE rent_id = $rent_id";

$device_result = mysqli_query($conn, $sql_device);
$rent_details = [];
if ($device_result) {
    while ($row = mysqli_fetch_assoc($device_result)) {
        $rent_details[] = $row;
    }
}

$sql_pm = "SELECT * FROM pm WHERE rent_id = $rent_id ORDER BY pm_date DESC LIMIT 1";
$res_pm = mysqli_query($conn, $sql_pm);
$pm_data = mysqli_fetch_assoc($res_pm);

// ถ้าไม่มี pm ให้ fallback เป็นวันที่ start_date
$last_pm_date = $pm_data ? $pm_data['pm_date'] : $contract_data['start_date'];
$last_pm_note = $pm_data['note'] ?? 'ยังไม่เคยตรวจสอบ';
$next_pm_date = (new DateTime($last_pm_date))->modify('+3 months');

// Fetch payments
$stmt = $conn->prepare("SELECT payment_id, due_date, amount, status, paid_at, type, slip_file FROM payment WHERE rent_id = ? ORDER BY due_date ASC, payment_id ASC");
$stmt->bind_param('i', $rent_id);
$stmt->execute();
$paymentsRes = $stmt->get_result();
$payments = [];
$totalAmount = 0;
$totalPaid = 0;
while ($row = $paymentsRes->fetch_assoc()) {
    $payments[] = $row;
    $totalAmount += (float) $row['amount'];
    if ($row['status'] === 'ชำระแล้ว') {
        $totalPaid += (float) $row['amount'];
    }
}
$totalRemaining = $totalAmount - $totalPaid;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Contract Viewer - Rent ID: <?= $rent_id ?></title>
</head>

<body class=" h-screen">

    <div class=" h-screen gap-2 gap-y-24 row-h sticky">

        <div class="col-span-5">
            <?php include('./components/navbar.php') ?>
        </div>

        <!-- Main Content -->
        <div class="">

            <div class=" w-full mx-24 col-span-5 rounded row-span-8 mx-auto overflow-auto p-6">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <a href="my_contracts.php" class="btn btn-ghost mr-4">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold">ใบสัญญาการเช่า</h1>
                            <p class="text-gray-600">Rent ID: <?= $rent_id ?></p>
                        </div>
                    </div>
                </div>

                <!-- Tab Navigation -->
                <div  class="tabs tabs-bordered">
                    <input type="radio" name="contract_tabs" role="tab" class="tab" aria-label="ข้อมูลการเช่า" checked="checked">
                    <!-- Tab Content: Rent Details -->
                    <div id="rent-details" role="tabpanel" class="tab-content p-6" checked="checked">
                        <!-- Contract Info Card -->
                        <div class="card bg-white shadow-lg mb-6">
                            <div class="card-body">
                                <div class="flex items-center justify-between mb-6">
                                    <div class="flex">
                                        <div
                                            class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center mr-3">
                                            <i class="fas fa-file-contract text-white text-lg"></i>
                                        </div>
                                        <h2 class="card-title text-xl font-bold text-gray-800">ข้อมูลการเช่า</h2>
                                    </div>
                                </div>

                                <!-- ข้อมูล -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Left Column -->
                                    <div class="space-y-4">
                                        <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-blue-500">
                                            <label
                                                class="text-sm font-semibold text-blue-600 uppercase tracking-wide">ผู้เช่า</label>
                                            <p class="text-lg font-medium text-gray-800 mt-1">
                                                <?= htmlspecialchars($contract_data['user_name']) ?>
                                            </p>
                                        </div>

                                        <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-green-500">
                                            <label
                                                class="text-sm font-semibold text-green-600 uppercase tracking-wide">ผู้อนุมัติ</label>
                                            <p class="text-lg font-medium text-gray-800 mt-1">
                                                ประเภทอุปกรณ์
                                            </p>
                                        </div>

                                        <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-purple-500">
                                            <label
                                                class="text-sm font-semibold text-purple-600 uppercase tracking-wide">สถานะใบสัญญา</label>
                                            <div class="mt-2">
                                                <?php
                                                switch ($contract_data['rent_status']) {
                                                    case 'อยู่ระหว่างการเช่า':
                                                        echo '<span class="badge badge-warning">อยู่ระหว่างการเช่า</span>';
                                                        break;
                                                    case 'คืนอุปกรณ์เรียบร้อย':
                                                        echo '<span class="badge badge-success">คืนอุปกรณ์แล้ว</span>';
                                                        break;
                                                    case 'เกินระยะเวลาคืน':
                                                        echo '<span class="badge badge-error">เลยกำหนดคืน</span>';
                                                        break;
                                                    default:
                                                        echo '<span class="badge badge-neutral">ไม่ทราบสถานะ</span>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right Column -->
                                    <div class="space-y-4">
                                        <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-orange-500">
                                            <label
                                                class="text-sm font-semibold text-orange-600 uppercase tracking-wide">วันที่เริ่มต้น</label>
                                            <div class="flex items-center mt-1">
                                                <i class="fas fa-calendar-alt text-orange-500 mr-2"></i>
                                                <p class="text-lg font-medium text-gray-800">
                                                    <?= date('d/m/Y', strtotime($contract_data['start_date'])) ?>
                                                </p>
                                            </div>
                                        </div>

                                        <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-red-500">
                                            <label
                                                class="text-sm font-semibold text-red-600 uppercase tracking-wide">วันที่สิ้นสุด</label>
                                            <div class="flex items-center mt-1">
                                                <i class="fas fa-calendar-times text-red-500 mr-2"></i>
                                                <p class="text-lg font-medium text-gray-800">
                                                    <?= date('d/m/Y', strtotime($contract_data['end_date'])) ?>
                                                </p>
                                            </div>
                                        </div>

                                        <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-indigo-500">
                                            <label
                                                class="text-sm font-semibold text-indigo-600 uppercase tracking-wide">ระยะเวลาเช่า</label>
                                            <div class="flex items-center mt-1">
                                                <i class="fas fa-hourglass-half text-indigo-500 mr-2"></i>
                                                <?php
                                                $start = new DateTime($contract_data['start_date']);
                                                $end = new DateTime($contract_data['end_date']);
                                                $diff = $start->diff($end);
                                                $days = $diff->days;
                                                ?>
                                                <p class="text-lg font-medium text-gray-800"><?= $days ?> วัน</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-6 pt-6 border-t border-gray-200">
                                    <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-tools text-green-600 text-lg mt-1"></i>
                                            </div>
                                            <div class="ml-3 flex-1 relative">
                                                <label
                                                    class="text-sm font-semibold text-green-700 uppercase tracking-wide">ข้อมูลการตรวจสอบ
                                                    PM</label>
                                                <p class="text-gray-700 mt-2 leading-relaxed">
                                                    <strong>รอบถัดไป:</strong> <?= $next_pm_date->format('d/m/Y') ?>
                                                    (ทุก 3 เดือน)
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contract File Section -->
                        <div class="card bg-white shadow-lg">
                            <div class="card-body">
                                <div class="flex items-center justify-between mb-4">
                                    <h2 class="card-title">ไฟล์ใบสัญญา</h2>
                                </div>

                                <?php if ($contract_data['file_lease']): ?>
                                    <?php
                                    $file_path = './../' . $contract_data['file_lease'];
                                    $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                                    $file_size = file_exists($file_path) ? filesize($file_path) : 0;
                                    ?>

                                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6">
                                        <div class="flex items-center justify-center">
                                            <div class="text-center">
                                                <?php if ($file_extension === 'pdf'): ?>
                                                    <i class="fas fa-file-pdf text-6xl text-red-500 mb-4"></i>
                                                    <div class="mb-4">
                                                        <embed src="../<?= $contract_data['file_lease'] ?>"
                                                            type="application/pdf" width="100%" height="500px"
                                                            class="border rounded-lg">
                                                    </div>
                                                <?php elseif (in_array($file_extension, ['jpg', 'jpeg', 'png'])): ?>
                                                    <i class="fas fa-file-image text-6xl text-green-500 mb-4"></i>
                                                    <div class="mb-4">
                                                        <img src="../<?= $contract_data['file_lease'] ?>" alt="Contract Image"
                                                            class="max-w-full h-auto border rounded-lg mx-auto"
                                                            style="max-height: 500px;">
                                                    </div>
                                                <?php else: ?>
                                                    <i class="fas fa-file-alt text-6xl text-blue-500 mb-4"></i>
                                                <?php endif; ?>

                                                <h3 class="text-lg font-semibold mb-2">
                                                    <?= basename($contract_data['file_lease']) ?>
                                                </h3>
                                                <p class="text-gray-600 mb-2">
                                                    ขนาดไฟล์:
                                                    <?= $file_size > 0 ? number_format($file_size / 1024, 2) . ' KB' : 'Unknown' ?>
                                                </p>

                                                <div class="mt-4">
                                                    <a href="../<?= $contract_data['file_lease'] ?>" target="_blank"
                                                        class="btn btn-primary">
                                                        <i class="fas fa-external-link-alt mr-2"></i>
                                                        เปิดในหน้าต่างใหม่
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <input type="radio" name="contract_tabs" role="tab" class="tab" aria-label="รายการอุปกรณ์">
                    <!-- Tab Content: Device List -->
                    <div id="device-list" role="tabpanel" class="tab-content p-6">
                        <div class="card bg-white shadow-lg">
                            <div class="card-body">
                                <div class="flex items-center justify-between mb-6">
                                    <div class="flex items-center">
                                        <div
                                            class="w-10 h-10 bg-gradient-to-r from-green-500 to-teal-600 rounded-lg flex items-center justify-center mr-3">
                                            <i class="fas fa-laptop text-white text-lg"></i>
                                        </div>
                                        <h2 class="card-title text-xl font-bold text-gray-800">รายการอุปกรณ์ที่เช่า</h2>
                                    </div>
                                    <div class="text-sm text-gray-600 bg-gray-100 px-3 py-1 rounded-full">
                                        <i class="fas fa-boxes mr-2"></i>
                                        <strong>รวม: <span id="totalCount"><?= count($rent_details) ?></span>
                                            รายการ</strong>
                                    </div>
                                </div>

                                <?php if (!empty($rent_details)): ?>
                                    <!-- Filter Section -->
                                    <div class="mb-6 p-4 bg-gray-50 rounded-lg border">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                                    <i class="fas fa-search mr-1"></i>ค้นหาทั่วไป
                                                </label>
                                                <input type="text" id="globalSearch" class="input input-bordered w-full"
                                                    placeholder="ค้นหา Serial Number, รุ่น...">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                                    <i class="fas fa-filter mr-1"></i>กรองตามรุ่น
                                                </label>
                                                <select id="modelFilter" class="select select-bordered w-full">
                                                    <option value="">ทุกรุ่น</option>
                                                    <?php
                                                    $models = array_unique(array_column($rent_details, 'model_name'));
                                                    foreach ($models as $model): ?>
                                                        <option value="<?= htmlspecialchars($model) ?>">
                                                            <?= htmlspecialchars($model) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                                    <i class="fas fa-tags mr-1"></i>กรองตามประเภท
                                                </label>
                                                <select id="typeFilter" class="select select-bordered w-full">
                                                    <option value="">ทุกประเภท</option>
                                                    <?php
                                                    $types = array_unique(array_column($rent_details, 'type_name'));
                                                    foreach ($types as $type): ?>
                                                        <option value="<?= htmlspecialchars($type) ?>">
                                                            <?= htmlspecialchars($type) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                                    <i class="fas fa-heartbeat mr-1"></i>กรองตามสถานะ
                                                </label>
                                                <select id="statusFilter" class="select select-bordered w-full">
                                                    <option value="">ทุกสถานะ</option>
                                                    <option value="ปกติ">ปกติ</option>
                                                    <option value="ชำรุด">ชำรุด</option>
                                                    <option value="ซ่อมแซม">ซ่อมแซม</option>
                                                </select>
                                            </div>
                                            <div class="flex items-end">
                                                <button id="clearFilters" class="btn btn-outline btn-sm">
                                                    <i class="fas fa-times mr-2"></i>ล้างตัวกรอง
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- DataTable -->
                                    <div class="overflow-x-auto">
                                        <table id="rentDetailsTable" class="table table-zebra w-full">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="text-center font-semibold">
                                                        <i class="fas fa-hashtag mr-1"></i>ลำดับ
                                                    </th>
                                                    <th class="font-semibold">
                                                        <i class="fas fa-barcode mr-1"></i>Serial Number
                                                    </th>
                                                    <th class="font-semibold">
                                                        <i class="fas fa-laptop mr-1"></i>ชื่อรุ่น
                                                    </th>
                                                    <th class="font-semibold">
                                                        <i class="fas fa-tag mr-1"></i>ประเภท
                                                    </th>
                                                    <th class="text-center font-semibold">
                                                        <i class="fas fa-heartbeat mr-1"></i>สถานะเครื่อง
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody id="tableBody">
                                                <?php foreach ($rent_details as $index => $detail): ?>
                                                    <tr class="hover:bg-gray-50 transition-colors">
                                                        <td class="text-center font-medium"><?= $index + 1 ?></td>
                                                        <td>
                                                            <div class="flex items-center">
                                                                <div
                                                                    class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                                                    <i class="fas fa-desktop text-blue-600 text-sm"></i>
                                                                </div>
                                                                <div>
                                                                    <div class="font-medium text-gray-900">
                                                                        <?= htmlspecialchars($detail['serial_number']) ?>
                                                                    </div>
                                                                    <div class="text-sm text-gray-500">Device ID:
                                                                        <?= $detail['device_id'] ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="font-medium text-gray-900">
                                                                <?= htmlspecialchars($detail['model_name']) ?>
                                                            </div>
                                                            <div class="text-sm text-gray-500">Model ID:
                                                                <?= $detail['model_id'] ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span
                                                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                                                <i class="fas fa-tag mr-1 text-xs"></i>
                                                                <?= htmlspecialchars($detail['type_name']) ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-center">
                                                            <?php
                                                            $status_config = [
                                                                'ปกติ' => ['class' => 'bg-green-100 text-green-800', 'icon' => 'fas fa-check-circle'],
                                                                'ส่งเคลม' => ['class' => 'bg-red-100 text-red-800', 'icon' => 'fas fa-exclamation-triangle'],
                                                                'เสีย' => ['class' => 'bg-yellow-100 text-yellow-800', 'icon' => 'fas fa-tools'],
                                                            ];
                                                            $config = $status_config[$detail['machine_status']] ?? ['class' => 'bg-gray-100 text-gray-800', 'icon' => 'fas fa-question'];
                                                            ?>
                                                            <span
                                                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $config['class'] ?>">
                                                                <i class="<?= $config['icon'] ?> mr-1 text-xs"></i>
                                                                <?= htmlspecialchars($detail['machine_status']) ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Pagination -->
                                    <div class="flex items-center justify-between mt-6 pt-6 border-t border-gray-200">
                                        <div class="text-sm text-gray-600">
                                            แสดง <span id="showingStart">1</span> ถึง <span
                                                id="showingEnd"><?= min(10, count($rent_details)) ?></span>
                                            จากทั้งหมด <span id="totalItems"><?= count($rent_details) ?></span> รายการ
                                        </div>
                                        <div class="flex space-x-2">
                                            <button id="prevPage" class="btn btn-outline btn-sm" disabled>
                                                <i class="fas fa-chevron-left mr-1"></i>ก่อนหน้า
                                            </button>
                                            <div id="pageNumbers" class="flex space-x-1">
                                                <!-- Page numbers will be inserted here -->
                                            </div>
                                            <button id="nextPage" class="btn btn-outline btn-sm">
                                                ถัดไป<i class="fas fa-chevron-right ml-1"></i>
                                            </button>
                                        </div>
                                    </div>

                                <?php else: ?>
                                    <div class="text-center py-12">
                                        <div
                                            class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <i class="fas fa-inbox text-4xl text-gray-400"></i>
                                        </div>
                                        <h3 class="text-lg font-semibold text-gray-600 mb-2">ไม่พบรายการอุปกรณ์</h3>
                                        <p class="text-gray-500">ไม่มีอุปกรณ์ที่เช่าในสัญญานี้</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>


                    <input type="radio" name="contract_tabs" role="tab" class="tab" aria-label="ข้อมูลการชำระเงิน">
                    <div id="payment-info" role="tabpanel" class="tab-content p-6">
                        <div class="card bg-white shadow-lg">
                            <div class="card-body">
                                <div class="flex items-center justify-between mb-6">
                                    <div class="flex items-center">
                                        <div
                                            class="w-10 h-10 bg-gradient-to-r from-green-400 to-blue-500 rounded-lg flex items-center justify-center mr-3">
                                            <i class="fas fa-receipt text-white text-lg"></i>
                                        </div>
                                        <h2 class="card-title text-xl font-bold text-gray-800">ข้อมูลการชำระเงิน</h2>
                                    </div>
                                </div>

                                <!-- สรุปยอดเงิน -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                    <div class="stats shadow">
                                        <div class="stat">
                                            <div class="stat-figure text-primary">
                                                <i class="fas fa-money-bill-wave text-3xl"></i>
                                            </div>
                                            <div class="stat-title">จำนวนเงินสุทธิ</div>
                                            <div class="stat-value text-primary"><?= number_format($totalAmount, 2) ?>
                                            </div>
                                            <div class="stat-desc">บาท</div>
                                        </div>
                                    </div>
                                    <div class="stats shadow">
                                        <div class="stat">
                                            <div class="stat-figure text-success">
                                                <i class="fas fa-check-circle text-3xl"></i>
                                            </div>
                                            <div class="stat-title">ชำระแล้ว</div>
                                            <div class="stat-value text-success"><?= number_format($totalPaid, 2) ?>
                                            </div>
                                            <div class="stat-desc">บาท</div>
                                        </div>
                                    </div>
                                    <div class="stats shadow">
                                        <div class="stat">
                                            <div class="stat-figure text-warning">
                                                <i class="fas fa-exclamation-circle text-3xl"></i>
                                            </div>
                                            <div class="stat-title">คงเหลือ</div>
                                            <div class="stat-value text-warning">
                                                <?= number_format($totalRemaining, 2) ?>
                                            </div>
                                            <div class="stat-desc">บาท</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Table -->
                                <div class="overflow-x-auto">
                                    <table class="table table-zebra w-full">
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th class="font-semibold">
                                                    <i class="fas fa-hashtag mr-1"></i>#
                                                </th>
                                                <th class="font-semibold">
                                                    <i class="fas fa-calendar-alt mr-1"></i>กำหนดชำระ
                                                </th>
                                                <th class="font-semibold">
                                                    <i class="fas fa-money-bill mr-1"></i>จำนวนเงิน
                                                </th>
                                                <th class="font-semibold">
                                                    <i class="fas fa-tag mr-1"></i>ประเภท
                                                </th>
                                                <th class="font-semibold">
                                                    <i class="fas fa-check-circle mr-1"></i>สถานะ
                                                </th>
                                                <th class="font-semibold">
                                                    <i class="fas fa-file-image mr-1"></i>หลักฐาน
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $i = 1;
                                            foreach ($payments as $p): ?>
                                                <tr class="hover:bg-gray-50 transition-colors">
                                                    <td class="font-medium"><?= $i++ ?></td>
                                                    <td>
                                                        <div class="flex items-center">
                                                            <i class="fas fa-calendar text-blue-500 mr-2"></i>
                                                            <span
                                                                class="font-medium"><?= date('d/m/Y', strtotime($p['due_date'])) ?></span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="flex items-center">
                                                            <i class="fas fa-baht-sign text-green-500 mr-2"></i>
                                                            <span
                                                                class="font-medium"><?= number_format((float) $p['amount'], 2) ?>
                                                                บาท</span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                                                            <i class="fas fa-tag mr-1 text-xs"></i>
                                                            <?= htmlspecialchars($p['type']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($p['status'] === 'ชำระแล้ว'): ?>
                                                            <div class="flex flex-col">
                                                                <span class="badge badge-success mb-1">
                                                                    <i class="fas fa-check mr-1"></i>ชำระแล้ว
                                                                </span>
                                                                <?php if ($p['paid_at']): ?>
                                                                    <small class="text-gray-500 text-xs">
                                                                        <i
                                                                            class="fas fa-clock mr-1"></i><?= date('d/m/Y H:i', strtotime($p['paid_at'])) ?>
                                                                    </small>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="badge badge-warning">
                                                                <i class="fas fa-clock mr-1"></i>ยังไม่ชำระ
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($p['slip_file'])): ?>
                                                            <a class="btn btn-outline btn-sm" target="_blank"
                                                                href="../<?= htmlspecialchars($p['slip_file']) ?>">
                                                                <i class="fas fa-external-link-alt mr-1"></i>ดูไฟล์
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="text-gray-400 italic">
                                                                <i class="fas fa-minus mr-1"></i>ไม่มีไฟล์
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <?php if (empty($payments)): ?>
                                    <div class="text-center py-12">
                                        <div
                                            class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <i class="fas fa-receipt text-4xl text-gray-400"></i>
                                        </div>
                                        <h3 class="text-lg font-semibold text-gray-600 mb-2">ไม่พบข้อมูลการชำระเงิน</h3>
                                        <p class="text-gray-500">ยังไม่มีรายการชำระเงินสำหรับสัญญานี้</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>



            </div>
        </div>
    </div>

    <script>
        // Tab functionality
        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.add('hidden');
            });

            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => {
                tab.classList.remove('tab-active');
            });

            // Show selected tab content
            document.getElementById(tabName).classList.remove('hidden');

            // Add active class to clicked tab
            event.target.classList.add('tab-active');
        }

        document.addEventListener('DOMContentLoaded', function () {
            const table = document.getElementById('rentDetailsTable');
            const tbody = document.getElementById('tableBody');

            if (table && tbody) {
                const originalRows = Array.from(tbody.querySelectorAll('tr'));
                let filteredRows = [...originalRows];
                let currentPage = 1;
                const rowsPerPage = 10;

                // Filter elements
                const globalSearch = document.getElementById('globalSearch');
                const modelFilter = document.getElementById('modelFilter');
                const typeFilter = document.getElementById('typeFilter');
                const statusFilter = document.getElementById('statusFilter');
                const clearFilters = document.getElementById('clearFilters');

                // Pagination elements
                const prevPage = document.getElementById('prevPage');
                const nextPage = document.getElementById('nextPage');
                const pageNumbers = document.getElementById('pageNumbers');
                const showingStart = document.getElementById('showingStart');
                const showingEnd = document.getElementById('showingEnd');
                const totalItems = document.getElementById('totalItems');
                const totalCount = document.getElementById('totalCount');

                // Filter functions
                function applyFilters() {
                    const searchTerm = globalSearch.value.toLowerCase();
                    const modelValue = modelFilter.value.toLowerCase();
                    const typeValue = typeFilter.value.toLowerCase();
                    const statusValue = statusFilter.value;

                    filteredRows = originalRows.filter(row => {
                        const serialText = row.cells[1].innerText.toLowerCase();
                        const modelText = row.cells[2].innerText.toLowerCase();
                        const typeText = row.cells[3].innerText.toLowerCase();
                        const statusText = row.cells[4].innerText;

                        const matchesSearch = serialText.includes(searchTerm) || modelText.includes(searchTerm);
                        const matchesModel = !modelValue || modelText.includes(modelValue);
                        const matchesType = !typeValue || typeText.includes(typeValue);
                        const matchesStatus = !statusValue || statusText.includes(statusValue);

                        return matchesSearch && matchesModel && matchesType && matchesStatus;
                    });

                    currentPage = 1;
                    renderTable();
                }

                // Render filtered table rows based on pagination
                function renderTable() {
                    const startIndex = (currentPage - 1) * rowsPerPage;
                    const endIndex = startIndex + rowsPerPage;
                    tbody.innerHTML = '';
                    const rowsToShow = filteredRows.slice(startIndex, endIndex);
                    rowsToShow.forEach(row => tbody.appendChild(row));

                    showingStart.textContent = filteredRows.length === 0 ? 0 : startIndex + 1;
                    showingEnd.textContent = Math.min(endIndex, filteredRows.length);
                    totalItems.textContent = filteredRows.length;
                    totalCount.textContent = filteredRows.length;

                    prevPage.disabled = currentPage === 1;
                    nextPage.disabled = endIndex >= filteredRows.length;

                    renderPageNumbers();
                }

                // Generate pagination buttons
                function renderPageNumbers() {
                    pageNumbers.innerHTML = '';
                    const totalPages = Math.ceil(filteredRows.length / rowsPerPage);

                    for (let i = 1; i <= totalPages; i++) {
                        const btn = document.createElement('button');
                        btn.className = `btn btn-sm ${i === currentPage ? 'btn-primary' : 'btn-outline'}`;
                        btn.textContent = i;
                        btn.addEventListener('click', () => {
                            currentPage = i;
                            renderTable();
                        });
                        pageNumbers.appendChild(btn);
                    }
                }

                // Event listeners
                if (globalSearch) globalSearch.addEventListener('input', applyFilters);
                if (modelFilter) modelFilter.addEventListener('change', applyFilters);
                if (typeFilter) typeFilter.addEventListener('change', applyFilters);
                if (statusFilter) statusFilter.addEventListener('change', applyFilters);
                if (clearFilters) {
                    clearFilters.addEventListener('click', () => {
                        globalSearch.value = '';
                        modelFilter.value = '';
                        typeFilter.value = '';
                        statusFilter.value = '';
                        applyFilters();
                    });
                }
                if (prevPage) {
                    prevPage.addEventListener('click', () => {
                        if (currentPage > 1) {
                            currentPage--;
                            renderTable();
                        }
                    });
                }
                if (nextPage) {
                    nextPage.addEventListener('click', () => {
                        if ((currentPage * rowsPerPage) < filteredRows.length) {
                            currentPage++;
                            renderTable();
                        }
                    });
                }

                // Initial render
                applyFilters();
            }
        });
    </script>

    <!-- script -->
    <?php include('./../lib/toast.php') ?>
    <script src="./../scripts/main.js"></script>

</body>

</html>