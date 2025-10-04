<?php
// contract_viewer.php

require_once('./../conn.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$rent_id = isset($_GET['rent_id']) ? intval($_GET['rent_id']) : 0;

if ($rent_id <= 0) {
    header('Location: rent.php');
    exit;
}

// Get contract details
$stmt = mysqli_prepare($conn, "
    SELECT r.*, u.user_name
    FROM rent r
    LEFT JOIN user u ON u.user_id = r.user_id
    WHERE r.rent_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $rent_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    header('Location: rent.php');
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

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Contract Viewer - Rent ID: <?= $rent_id ?></title>
</head>

<body class="bg-gradient-to-br from-slate-100 to-slate-300">

    <div class="grid grid-cols-5 grid-rows-1 h-screen gap-2 row-h min-h-screen sticky">
        <!-- Sidebar -->
        <div class="">
            <div class="rounded-2xl inline-block h-full">
                <?php include('admin_component/sidebar.php') ?>
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
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <a href="rent.php" class="btn btn-ghost mr-4">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold">ใบสัญญาการเช่า</h1>
                            <p class="text-gray-600">Rent ID: <?= $rent_id ?></p>
                        </div>
                    </div>


                </div>

                <!-- Contract Info Card -->
                <div class="card bg-white shadow-lg mb-6">
                    <div class="card-body">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex">
                                <div class="w-10  h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-file-contract text-white text-lg"></i>
                                </div>
                                <h2 class="card-title text-xl font-bold text-gray-800">ข้อมูลการเช่า</h2>
                            </div>


                            <button class="btn btn-sm btn-outline btn-primary" onclick="document.getElementById('statusModal').showModal()">
                                <i class="fas fa-edit mr-1"></i> แก้ไขสถานะ
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Left Column -->
                            <div class="space-y-4">
                                <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-blue-500">
                                    <label class="text-sm font-semibold text-blue-600 uppercase tracking-wide">ผู้เช่า</label>
                                    <p class="text-lg font-medium text-gray-800 mt-1"><?= htmlspecialchars($contract_data['user_name']) ?></p>
                                </div>


                                <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-purple-500">
                                    <label class="text-sm font-semibold text-purple-600 uppercase tracking-wide">สถานะใบสัญญา</label>
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
                                <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-purple-500">
                                    <label class="text-sm font-semibold text-purple-600 uppercase tracking-wide">อุปกรณ์ที่เช่า</label>
                                    <div class="mt-2">
                                        <?php
                                        switch ($contract_data['rent_status']) {
                                            case 'อยู่ระหว่างการเช่า':
                                                echo '<span class="badge badge-warning">ประเภทอุปกรณ์</span>';
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
                                    <label class="text-sm font-semibold text-orange-600 uppercase tracking-wide">วันที่เริ่มต้น</label>
                                    <div class="flex items-center mt-1">
                                        <i class="fas fa-calendar-alt text-orange-500 mr-2"></i>
                                        <p class="text-lg font-medium text-gray-800"><?= date('d/m/Y', strtotime($contract_data['start_date'])) ?></p>
                                    </div>
                                </div>

                                <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-red-500">
                                    <label class="text-sm font-semibold text-red-600 uppercase tracking-wide">วันที่สิ้นสุด</label>
                                    <div class="flex items-center mt-1">
                                        <i class="fas fa-calendar-times text-red-500 mr-2"></i>
                                        <p class="text-lg font-medium text-gray-800"><?= date('d/m/Y', strtotime($contract_data['end_date'])) ?></p>
                                    </div>
                                </div>

                                <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-indigo-500">
                                    <label class="text-sm font-semibold text-indigo-600 uppercase tracking-wide">ระยะเวลาเช่า</label>
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

                        <!-- <?php // if ($contract_data['contract_notes']): 
                                ?>
                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-sticky-note text-blue-600 text-lg mt-1"></i>
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <label class="text-sm font-semibold text-blue-700 uppercase tracking-wide">หมายเหตุ</label>
                                            <p class="text-gray-700 mt-2 leading-relaxed"><?= htmlspecialchars($contract_data['contract_notes']) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <!-- <?php //endif; 
                                ?> -->

                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-tools text-green-600 text-lg mt-1"></i>
                                    </div>
                                    <div class="ml-3 flex-1 relative">
                                        <label class="text-sm font-semibold text-green-700 uppercase tracking-wide">ข้อมูลการตรวจสอบ PM ล่าสุด</label>
                                        <p class="text-gray-700 mt-2 leading-relaxed">
                                            <strong>วันที่:</strong> <?= date('d/m/Y', strtotime($last_pm_date)) ?><br>
                                            <strong>หมายเหตุ:</strong> <?= htmlspecialchars($last_pm_note) ?><br>
                                            <strong>รอบถัดไป:</strong> <?= $next_pm_date->format('d/m/Y') ?> (ทุก 1 เดือน)
                                        </p>
                                        <button class="btn btn-sm btn-warning  absolute top-0 right-0" onclick="document.getElementById('pmModal').showModal()">
                                            <i class="fas fa-tools mr-1"></i> บันทึกการตรวจ PM
                                        </button>

                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- pm modal  -->
                <dialog id="pmModal" class="modal modal-bottom sm:modal-middle">
                    <div class="modal-box max-w-md mx-auto shadow-2xl border border-base-300">
                        <!-- Header with gradient background -->
                        <div class="bg-gradient-to-r from-success/10 to-success/5 -mx-6 -mt-6 px-6 pt-6 pb-4 mb-6 border-b border-success/20">
                            <h3 class="font-bold text-xl text-success flex items-center">
                                <i class="fas fa-wrench mr-3 text-2xl"></i>
                                <span>บันทึกการตรวจสอบ PM</span>
                            </h3>
                            <p class="text-sm text-base-content/70 mt-1">กรุณากรอกข้อมูลการตรวจสอบ</p>
                        </div>

                        <form method="POST" action="pm_save.php" class="space-y-6">
                            <input type="hidden" name="rent_id" value="<?= $contract_data['rent_id'] ?>">

                            <!-- วันที่ตรวจ PM -->
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text font-semibold text-base flex items-center">
                                        <i class="fas fa-calendar-alt mr-2 text-primary"></i>
                                        วันที่ตรวจ PM
                                    </span>
                                    <span class="label-text-alt text-error">*</span>
                                </label>
                                <input
                                    type="date"
                                    name="pm_date"
                                    class="input input-bordered input-primary w-full focus:input-success transition-colors"
                                    required
                                    value="<?= date('Y-m-d') ?>">
                            </div>

                            <!-- หมายเหตุ -->
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text font-semibold text-base flex items-center">
                                        <i class="fas fa-clipboard-list mr-2 text-primary"></i>
                                        หมายเหตุ
                                    </span>
                                    <span class="label-text-alt text-error">*</span>
                                </label>
                                <textarea
                                    name="pm_note"
                                    class="textarea textarea-bordered textarea-primary w-full focus:textarea-success transition-colors resize-none"
                                    rows="4"
                                    placeholder="กรุณาระบุรายละเอียดการตรวจสอบ เช่น อุปกรณ์ที่ตรวจสอบ, สภาพอุปกรณ์, ปัญหาที่พบ..."
                                    required></textarea>
                                <label class="label">
                                    <span class="label-text-alt text-base-content/60">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        กรอกรายละเอียดให้ชัดเจนเพื่อการติดตาม
                                    </span>
                                </label>
                            </div>

                            <!-- Action buttons -->
                            <div class="modal-action pt-4 border-t border-base-300">
                                <div class="flex gap-3 w-full">
                                    <button
                                        type="submit"
                                        class="btn btn-success flex-1 shadow-lg hover:shadow-xl transition-shadow">
                                        <i class="fas fa-save mr-2"></i>
                                        บันทึกข้อมูล
                                    </button>

                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Click outside to close -->
                    <form method="dialog" class="modal-backdrop">
                        <button>close</button>
                    </form>
                </dialog>



                <!-- update status  -->
                <dialog id="statusModal" class="modal">
                    <div class="modal-box">
                        <h3 class="font-bold text-lg mb-4">
                            <i class="fas fa-exchange-alt mr-2"></i> เปลี่ยนสถานะใบสัญญา
                        </h3>
                        <form method="post" action="update_status.php" class="space-y-4">
                            <input type="hidden" name="rent_id" value="<?= $contract_data['rent_id'] ?>">

                            <label class="block text-sm font-medium text-gray-700">เลือกสถานะใหม่:</label>
                            <select name="new_status" class="select select-bordered w-full" required>
                                <option value="">-- เลือกสถานะ --</option>
                                <option value="อยู่ระหว่างการเช่า" <?= $contract_data['rent_status'] === 'อยู่ระหว่างการเช่า' ? 'selected' : '' ?>>อยู่ระหว่างการเช่า</option>
                                <option value="คืนอุปกรณ์เรียบร้อย" <?= $contract_data['rent_status'] === 'คืนอุปกรณ์เรียบร้อย' ? 'selected' : '' ?>>คืนอุปกรณ์เรียบร้อย</option>
                                <option value="เกินระยะเวลาคืน" <?= $contract_data['rent_status'] === 'เกินระยะเวลาคืน' ? 'selected' : '' ?>>เกินระยะเวลาคืน</option>
                            </select>

                            <div class="modal-action">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> บันทึกการเปลี่ยนแปลง
                                </button>
                                <button type="button" class="btn" onclick="document.getElementById('statusModal').close()">ยกเลิก</button>
                            </div>
                        </form>
                    </div>
                </dialog>


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
                                                    type="application/pdf"
                                                    width="100%"
                                                    height="500px"
                                                    class="border rounded-lg">
                                            </div>
                                        <?php elseif (in_array($file_extension, ['jpg', 'jpeg', 'png'])): ?>
                                            <i class="fas fa-file-image text-6xl text-green-500 mb-4"></i>
                                            <div class="mb-4">
                                                <img src="../<?= $contract_data['file_lease'] ?>"
                                                    alt="Contract Image"
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
                                            ขนาดไฟล์: <?= $file_size > 0 ? number_format($file_size / 1024, 2) . ' KB' : 'Unknown' ?>
                                        </p>


                                        <div class="mt-4">
                                            <a href="../<?= $contract_data['file_lease'] ?>"
                                                target="_blank"
                                                class="btn btn-primary">
                                                <i class="fas fa-external-link-alt mr-2"></i>
                                                เปิดในหน้าต่างใหม่
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!$contract_data['file_lease']): ?>
                            ยังไม่เพิ่มใบสัญญา
                        <?php endif; ?>

                    </div>
                </div>

                <!-- Rent Details Card -->



            </div>
        </div>
    </div>

    <?php include('./../lib/toast.php') ?>

    <script src="./../scripts/main.js"></script>

</body>

</html>