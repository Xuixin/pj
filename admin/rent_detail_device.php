<?php
require_once('./../conn.php');

if (!$_GET['rent_id']) {
    header('location: rent.php');
}

$rent_id = $_GET['rent_id'];


$sql_device = "SELECT rd.rent_detail_id, rd.device_id, rd.machine_status,rd.backup_device, d.serial_number, m.model_id, m.model_name, t.type_name 
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


$result = $conn->query("
    SELECT COUNT(*) AS cnt
    FROM `rent_detail` 
    WHERE backup_device = 1 AND machine_status = 'สำรอง'
");

$row = $result->fetch_assoc();
$haveBackup = intval($row['cnt']) > 0;


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['case']) && $_POST['case'] === 'cancelBackupDeviceId') {
    $backupDeviceId = intval($_POST['backup_device_id']); // id จากปุ่ม
    $failedDeviceId = intval($_POST['failed_device']);    // id ที่เลือกจาก dropdown

    // อัปเดต backup_device ให้สถานะ 'สำรอง'
    $sql1 = "UPDATE rent_detail SET machine_status = 'สำรอง' WHERE device_id = $backupDeviceId";
    // อัปเดต failed_device ให้สถานะ 'ปกติ'
    $sql2 = "UPDATE rent_detail SET machine_status = 'ปกติ' WHERE device_id = $failedDeviceId";

    $success1 = mysqli_query($conn, $sql1);
    $success2 = mysqli_query($conn, $sql2);

    if ($success1 && $success2) {
        $_SESSION['succes'] = "ยกเลิกสำเร็จแล้ว";

        header("Location: rent_detail_device.php?rent_id=$rent_id");
        exit;
    } else {
        $error = mysqli_error($conn);
        echo "<script>alert('เกิดข้อผิดพลาด: $error');</script>";
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['failed_device_id'], $_POST['backup_choice'])) {
    $failedDeviceId = intval($_POST['failed_device_id']);   // เครื่องเสีย
    $backupDeviceId = intval($_POST['backup_choice']);      // เครื่องสำรองที่เลือก

    // 1. อัปเดตเครื่องสำรอง -> ใช้งาน
    mysqli_query($conn, "UPDATE rent_detail SET machine_status = 'กำลังใช้เครื่อง' WHERE device_id = $backupDeviceId");

    // 2. เครื่องเสีย -> เปลี่ยน status ปกติ / ซ่อมเสร็จ (ถ้าต้องการ)
    mysqli_query($conn, "UPDATE rent_detail SET machine_status = 'เสีย' WHERE device_id = $failedDeviceId");

    $_SESSION['success'] = 'เริ่มต้นใช้เครื่องสำรอง';
    header("Location: rent_detail_device.php?rent_id=$rent_id");
    exit;
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Employee Management</title>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card-shadow {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .hover-scale {
            transition: transform 0.2s ease-in-out;
        }

        .hover-scale:hover {
            transform: scale(1.02);
        }
    </style>
</head>

<body class="bg-gradient-to-br from-slate-100 to-slate-300 ">



    <div class="grid grid-cols-5 grid-rows-1 h-screen gap-2 row-h   min-h-screen sticky">
        <!-- Sidebar -->
        <div class="">
            <div class="rounded-2xl inline-block  h-full">
                <?php
                include('admin_component/sidebar.php')
                ?>
            </div>
        </div>


        <!-- Main Content -->
        <div class="col-span-4 z-20 row-span-1 col-start-2 row-start-1 py-3 pr-3 grid grid-cols-5 grid-rows-8 gap-6">
            <div class="col-span-5 row-span-1 bg-white rounded-2xl shadow-lg flex items-center justify-between px-8">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-gradient-to-r from-green-400 to-blue-500 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-white text-lg"></i>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-lg font-semibold text-gray-800">ยินดีต้อนรับ, <?php echo $_SESSION['admin_name'] ?></h1>
                    </div>
                </div>

            </div>

            <div class="container col-span-5 bg-white rounded row-span-7 mx-auto overflow-auto p-6">




                <div class="card bg-white shadow-lg mb-6">
                    <div class="card-body">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex space-x-4">
                                <a href="rent_detail.php?rent_id=<?php echo  $rent_id ?>" class="btn btn-ghost mr-4">
                                    <i class="fas fa-arrow-left"></i>
                                </a>
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-teal-600 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-laptop text-white text-lg"></i>
                                    </div>
                                    <h2 class="card-title text-xl font-bold text-gray-800">รายการอุปกรณ์ที่เช่า</h2>
                                </div>
                            </div>
                            <div class="text-sm text-gray-600 bg-gray-100 px-3 py-1 rounded-full">
                                <i class="fas fa-boxes mr-2"></i>
                                <strong>รวม: <span id="totalCount"><?= count($rent_details) ?></span> รายการ</strong>
                            </div>
                        </div>

                        <?php if (!empty($rent_details)): ?>


                            <!-- DataTable -->
                            <div class="overflow-x-auto">
                                <table id="rentDetailsTable" class="table table-zebra w-full">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="text-center font-semibold">
                                                <i class="fas fa-hashtag mr-1"></i>ลำดับ
                                            </th>
                                            <th class="font-semibold">
                                                <i class="fas fa-barcode mr-1"></i>หมายเลขประจำเครื่อง
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
                                            <th
                                                class="text-center">action </th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableBody">
                                        <?php
                                        $i = 1;
                                        foreach ($rent_details as $index => $detail): ?>
                                            <tr class="hover:bg-gray-50 transition-colors">
                                                <td class="text-center font-medium"><?= $i ?></td>
                                                <td>
                                                    <div class="flex items-center">
                                                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                                            <i class="fas fa-desktop text-blue-600 text-sm"></i>
                                                        </div>
                                                        <div>
                                                            <div class="font-medium text-gray-900"><?= htmlspecialchars($detail['serial_number']) ?></div>
                                                            <div class="text-sm text-gray-500">Device ID: <?= $detail['device_id'] ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($detail['model_name']) ?></div>
                                                    <div class="text-sm text-gray-500">Model ID: <?= $detail['model_id'] ?></div>
                                                </td>
                                                <td>
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                                        <i class="fas fa-tag mr-1 text-xs"></i>
                                                        <?= htmlspecialchars($detail['type_name']) ?>
                                                    </span>
                                                </td>
                                                <?php if (!$detail['backup_device']): ?>
                                                    <td class="text-center">
                                                        <?php
                                                        $status_config = [
                                                            'ปกติ' => ['class' => 'bg-green-100 text-green-800', 'icon' => 'fas fa-check-circle'],
                                                            'ส่งเคลม' => ['class' => 'bg-red-100 text-red-800', 'icon' => 'fas fa-exclamation-triangle'],
                                                            'เสีย' => ['class' => 'bg-yellow-100 text-yellow-800', 'icon' => 'fas fa-tools'],
                                                            'กำลังใช้เครื่อง' => ['class' => 'bg-blue-100 text-blue-800', 'icon' => 'fas fa-laptop'],
                                                            'สำรอง' => ['class' => 'bg-gray-100 text-gray-800', 'icon' => 'fas fa-clock'] // เพิ่มสถานะสำรอง
                                                        ];


                                                        $config = $status_config[$detail['machine_status']] ?? ['class' => 'bg-gray-100 text-gray-800', 'icon' => 'fas fa-question'];
                                                        ?>
                                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $config['class'] ?>">

                                                            <i class="<?= $config['icon'] ?> mr-1 text-xs"></i>

                                                            <?= htmlspecialchars($detail['machine_status']) ?>
                                                        </span>
                                                    </td>
                                                <?php else:  ?>
                                                    <td class="text-center">

                                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                                            เครื่องสำรอง
                                                        </span>
                                                    </td>
                                                <?php endif; ?>
                                                <td class="text-center">
                                                    <?php if ($detail['machine_status'] === 'เสีย'): ?>
                                                        <!-- กรณีเครื่องเสีย แสดงกำลังซ่อมแซม -->
                                                        <span class="text-red-500">
                                                            <i class="fas fa-tools"></i> กำลังซ่อมแซม
                                                        </span>

                                                    <?php elseif ((!$detail['backup_device'] && $detail['machine_status'] === 'ปกติ') === true) : ?>
                                                        <!-- กรณีเครื่องปกติ ไม่มี backup และยังมีอุปกรณ์สำรอง -->
                                                        <?php if ($haveBackup === true):

                                                        ?>
                                                            <button class="btn btn-warning open-backup-modal" data-device-id="<?= $detail['device_id'] ?>">
                                                                <label for="backupModal" class="cursor-pointer">
                                                                    <i class="fas fa-cog mr-1"></i> ดำเนินการ
                                                                </label>
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="btn-disabled">
                                                                <i class="fas fa-tools"></i> ไม่มีอุปกรณ์สำรอง
                                                            </span>
                                                        <?php endif; ?>



                                                    <?php elseif ($detail['machine_status'] === 'กำลังใช้เครื่อง'): ?>
                                                        <!-- กรณีมี backup และกำลังใช้เครื่อง -->
                                                        <button class="btn btn-error open-cancel-backup-modal"
                                                            data-device-id="<?= $detail['device_id'] ?>">

                                                            <label for="cancelBackupModal">
                                                                <i class="fas fa-times-circle mr-1"></i>
                                                                ยกเลิกเครื่องสำรอง
                                                            </label>

                                                        </button>

                                                    <?php else: ?>
                                                        <span>-</span> <!-- กรณีอื่น ๆ -->
                                                    <?php endif; ?>
                                                </td>



                                            </tr>
                                        <?php
                                            $i++;

                                        endforeach;

                                        ?>
                                    </tbody>
                                </table>
                            </div>



                        <?php else: ?>
                            <div class="text-center py-12">
                                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-inbox text-4xl text-gray-400"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-600 mb-2">ไม่พบรายการอุปกรณ์</h3>
                                <p class="text-gray-500">ไม่มีอุปกรณ์ที่เช่าในสัญญานี้</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>



            </div>
        </div>
    </div>


    <!-- Modal เลือกเครื่องสำรอง -->
    <input type="checkbox" id="backupModal" class="modal-toggle" />
    <div class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg mb-4">เลือกเครื่องสำรอง</h3>
            <form id="backupForm" method="POST" action="">
                <input type="hidden" name="failed_device_id" id="failedDeviceId"> <!-- เครื่องเสีย -->
                <select name="backup_choice" class="select select-bordered w-full" required>
                    <option disabled selected>เลือกเครื่องสำรอง</option>
                    <?php
                    $backup_sql = "SELECT r.*, d.serial_number FROM `rent_detail` as r 
JOIN device as d on d.device_id = r.device_id
WHERE backup_device = 1 AND machine_status = 'สำรอง' AND r.rent_id = $rent_id";
                    $backup_result = mysqli_query($conn, $backup_sql);
                    while ($row = mysqli_fetch_assoc($backup_result)) {
                        echo "<option value='{$row['device_id']}'>{$row['serial_number']}</option>";
                    }
                    ?>
                </select>
                <div class="modal-action">
                    <label for="backupModal" class="btn">ยกเลิก</label>
                    <button type="submit" class="btn btn-primary">ยืนยัน</button>
                </div>
            </form>

        </div>
    </div>

    <!-- Modal ยกเลิกเครื่องสำรอง -->
    <input type="checkbox" id="cancelBackupModal" class="modal-toggle" />
    <div class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg mb-4">ยกเลิกเครื่องสำรอง</h3>
            <form id="cancelBackupForm" method="POST" action="">
                <input type="hidden" name="case" value="cancelBackupDeviceId">
                <input type="hidden" name="backup_device_id" id="cancelBackupDeviceId"> <!-- ใส่ device_id จากปุ่ม -->

                <p class="mb-3">เลือกเครื่องที่ต้องการยกเลิกจากสถานะเสีย:</p>
                <select name="failed_device" class="select select-bordered w-full" required>
                    <option disabled selected>เลือกเครื่องที่ต้องการยกเลิก</option>
                    <?php
                    $failed_sql = "SELECT r.*, d.serial_number
                       FROM rent_detail r 
                       JOIN device d ON r.device_id = d.device_id 
                       WHERE r.machine_status = 'เสีย' AND r.rent_id = $rent_id";
                    $failed_result = mysqli_query($conn, $failed_sql);
                    while ($row = mysqli_fetch_assoc($failed_result)) {
                        echo "<option value='{$row['device_id']}'>{$row['serial_number']}</option>";
                    }
                    ?>
                </select>

                <div class="modal-action">
                    <label for="cancelBackupModal" class="btn">ยกเลิก</label>
                    <button type="submit" class="btn btn-error">ยืนยันยกเลิก</button>
                </div>
            </form>

        </div>
    </div>



    <script>
        document.querySelectorAll(".open-cancel-backup-modal").forEach(btn => {
            btn.addEventListener("click", () => {
                const deviceId = btn.dataset.deviceId; // เอา id จากปุ่ม
                document.getElementById("cancelBackupDeviceId").value = deviceId; // ใส่ใน input hidden
                document.getElementById("cancelBackupModal").checked = true; // เปิด modal
            });
        });

        document.querySelectorAll(".open-backup-modal").forEach(btn => {
            btn.addEventListener("click", () => {
                const failedDeviceId = btn.dataset.deviceId;
                document.getElementById("failedDeviceId").value = failedDeviceId;
                document.getElementById("backupModal").checked = true;
            });
        });


        document.addEventListener('DOMContentLoaded', function() {
            const table = document.getElementById('rentDetailsTable');
            const tbody = document.getElementById('tableBody');
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
            globalSearch.addEventListener('input', applyFilters);
            modelFilter.addEventListener('change', applyFilters);
            typeFilter.addEventListener('change', applyFilters);
            statusFilter.addEventListener('change', applyFilters);
            clearFilters.addEventListener('click', () => {
                globalSearch.value = '';
                modelFilter.value = '';
                typeFilter.value = '';
                statusFilter.value = '';
                applyFilters();
            });
            prevPage.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    renderTable();
                }
            });
            nextPage.addEventListener('click', () => {
                if ((currentPage * rowsPerPage) < filteredRows.length) {
                    currentPage++;
                    renderTable();
                }
            });

            // Initial render
            applyFilters();
        });


        document.addEventListener("DOMContentLoaded", () => {
            const backupModal = document.getElementById("backupModal");
            const cancelBackupModal = document.getElementById("cancelBackupModal");
            const backupDeviceId = document.getElementById("backupDeviceId");
            const cancelBackupDeviceId = document.getElementById("cancelBackupDeviceId");

            document.querySelectorAll(".open-backup-modal").forEach(btn => {
                btn.addEventListener("click", () => {
                    backupDeviceId.value = btn.dataset.deviceId;
                    backupModal.showModal();
                });
            });

            document.querySelectorAll(".open-cancel-backup-modal").forEach(btn => {
                btn.addEventListener("click", () => {
                    cancelBackupDeviceId.value = btn.dataset.deviceId;
                    cancelBackupModal.showModal();
                });
            });
        });
    </script>





    <?php

    include('./../lib/toast.php')

    ?>



    <script src="./../scripts/main.js"></script>

</body>

</html>