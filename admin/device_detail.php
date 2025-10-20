<?php
require_once('./../conn.php');
include("./../lib/format_date.php");

// ✅ Show errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Validate model_id
if (!isset($_GET['model_id'])) {
    header('Location: device.php');
    exit;
}

$model_id = intval($_GET['model_id']);

// Get brand list
$brands = [];
$brand_query = mysqli_query($conn, "SELECT brand_id, brand_name FROM brand ORDER BY brand_name");
if ($brand_query) {
    while ($row = mysqli_fetch_assoc($brand_query)) {
        $brands[] = $row;
    }
}

// Get model info
$model_data = null;
$images = [];

$sql = "SELECT 
            m.model_id,
            m.model_name,
            m.brand_id,
            b.brand_name,
            m_img.img_path
        FROM model AS m
        LEFT JOIN brand AS b ON m.brand_id = b.brand_id
        LEFT JOIN model_img AS m_img ON m_img.model_id = m.model_id
        WHERE m.model_id = $model_id";

$model_query = mysqli_query($conn, $sql);
if (!$model_query) {
    die("Model Query Error: " . mysqli_error($conn));
}

while ($row = mysqli_fetch_assoc($model_query)) {
    if ($model_data === null) {
        $model_data = [
            'model_id' => $row['model_id'],
            'model_name' => $row['model_name'],
            'brand_id' => $row['brand_id'],
            'brand_name' => $row['brand_name']
        ];
    }

    if (!empty($row['img_path'])) {
        $images[] = $row['img_path'];
    }
}

if ($model_data === null) {
    header('Location: device.php?error=model_not_found');
    exit;
}

// Get serial numbers
$device_data = [];
$device_sql = "SELECT device_id, serial_number, status, create_At 
               FROM device 
               WHERE model_id = $model_id 
               ORDER BY create_At DESC";

$device_result = mysqli_query($conn, $device_sql);
if (!$device_result) {
    die("Device Query Error: " . mysqli_error($conn));
}

while ($row = mysqli_fetch_assoc($device_result)) {
    $device_data[] = $row;
}

// Set model name
$model_name = $model_data['model_name'] ?? 'Unknown Model';
?>



<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <title>จัดการโมเดล - <?php echo htmlspecialchars($model_name); ?></title>
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

        .upload-area {
            position: relative;
            overflow: hidden;
        }

        .upload-area:hover {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-available {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-in-use {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-maintenance {
            background-color: #fee2e2;
            color: #991b1b;
        }

        tbody tr:nth-child(2n) {
            background-color: #eff6ff !important;
        }

        tbody tr td {
            color: black !important;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-slate-100 to-slate-300">

    <div class="grid grid-cols-5 grid-rows-1 h-screen gap-2 min-h-screen">
        <!-- Sidebar -->
        <div class="col-span-1">
            <div class="rounded-2xl h-full">
                <?php include('admin_component/sidebar.php'); ?>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-span-4 z-20 py-3 pr-3 grid grid-cols-5 grid-rows-8 gap-6">
            <!-- Header -->
            <div class="col-span-5 row-span-1 bg-white rounded-2xl shadow-lg flex items-center justify-between px-8">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-gradient-to-r from-green-400 to-blue-500 rounded-full flex items-center justify-center">
                        <i class="fas fa-laptop text-white text-lg"></i>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-lg font-semibold text-gray-800">จัดการโมเดล</h1>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($model_data['brand_name']); ?> - <?php echo htmlspecialchars($model_name); ?></p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-gray-600"></i>
                    </div>
                </div>
            </div>

            <!-- Main Table -->
            <div class="col-span-5 row-span-7 bg-white flex flex-col rounded-2xl shadow-lg">
                <div class="flex justify-between items-center mb-6 shadow-md py-5 px-5">
                    <h3 class="text-xl font-semibold text-gray-800">
                        <i class="fas fa-table mr-2 text-blue-500"></i>
                        รายการ Serial สำหรับ: <span class="text-primary"><?php echo htmlspecialchars($model_name); ?></span>
                    </h3>

                    <div class="flex items-center space-x-3">


                        <button class="btn btn-success btn-md hover-scale text-white shadow-lg"
                            onclick="document.getElementById('add_serial_modal').showModal()">
                            <i class="fas fa-plus mr-2"></i>
                            เพิ่ม Serial
                        </button>

                        <button class="btn btn-outline btn-sm" onclick="location.reload()">
                            <i class="fas fa-refresh mr-1"></i>
                            รีเฟรช
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto px-6 flex-1">
                    <table class="table table-zebra w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="text-left">ลำดับ</th>
                                <th class="text-left">
                                    <i class="fas fa-barcode mr-2"></i>
                                    Serial Number
                                </th>
                                <th class="text-left">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    สถานะ
                                </th>
                                <th class="text-left">
                                    <i class="fas fa-clock mr-2"></i>
                                    วันที่เพิ่ม
                                </th>

                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($device_data) > 0): ?>
                                <?php foreach ($device_data as $index => $row): ?>
                                    <tr class="bg-white hover:bg-gray-50">
                                        <td class="px-4 py-3"><?php echo $index + 1; ?></td>
                                        <td class="px-4 py-3 font-mono text-sm"><?php echo htmlspecialchars($row['serial_number']); ?></td>
                                        <td class="px-4 py-3">
                                            <?php
                                            $status_class = '';
                                            $status_text = '';
                                            switch (strtolower($row['status'])) {
                                                case 'ว่าง':
                                                    $status_class = 'status-available';
                                                    $status_text = 'ว่าง';
                                                    break;
                                                case 'เช่าแล้ว':
                                                    $status_class = 'status-in-use';
                                                    $status_text = 'ถูกเช่าแล้ว';
                                                    break;

                                                default:
                                                    $status_class = 'status-available';
                                                    $status_text = htmlspecialchars($row['status']);
                                            }
                                            ?>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <?php echo formatThaiShortDateTime($row['create_At']); ?>
                                        </td>

                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-gray-500 py-8">
                                        <i class="fas fa-inbox text-4xl text-gray-300 mb-2"></i>
                                        <p>ยังไม่มีข้อมูล Serial Number</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>


    <!-- Add device Modal -->
    <dialog id="add_serial_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg mb-4">เพิ่ม Serial Number</h3>
            <form method="post" action="device_detail_db.php" class="space-y-4">
                <input type="hidden" name="_method" value="ADD">
                <input type="hidden" name="model_id" value="<?php echo $model_id; ?>">



                <div class="form-control">
                    <label class="label">
                        <span class="label-text">จำนวนที่ต้องการเพิ่ม</span>
                    </label>
                    <input type="number" name="quantity" min="1" max="100" value="1"
                        class="input input-bordered" required>
                </div>

                <div class="modal-action">
                    <button type="button" class="btn btn-ghost"
                        onclick="document.getElementById('add_serial_modal').close()">
                        ยกเลิก
                    </button>
                    <button type="submit" class="btn btn-primary">
                        เพิ่ม Serial
                    </button>
                </div>
            </form>
        </div>
    </dialog>



    <!-- Toast Notifications -->
    <?php include('./../lib/toast.php'); ?>

    <script>
        // Edit Serial Modal Function
        function openEditSerialModal(serialId, serialNumber, status) {
            // Create modal HTML dynamically
            const modalHtml = `
                <dialog id="edit_serial_modal" class="modal">
                    <div class="modal-box">
                        <h3 class="font-bold text-lg mb-4">แก้ไข Serial Number</h3>
                        <form method="post" action="serial_db.php" class="space-y-4">
                            <input type="hidden" name="_method" value="UPDATE">
                            <input type="hidden" name="serial_id" value="${serialId}">
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Serial Number</span>
                                </label>
                                <input type="text" name="serial_number" value="${serialNumber}" 
                                       class="input input-bordered" required>
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">สถานะ</span>
                                </label>
                                <select name="status" class="select select-bordered" required>
                                    <option value="available" ${status === 'available' ? 'selected' : ''}>พร้อมใช้งาน</option>
                                    <option value="in_use" ${status === 'in_use' ? 'selected' : ''}>กำลังใช้งาน</option>
                                    <option value="maintenance" ${status === 'maintenance' ? 'selected' : ''}>ซ่อมบำรุง</option>
                                </select>
                            </div>
                            
                            <div class="modal-action">
                                <button type="button" class="btn btn-ghost" 
                                        onclick="document.getElementById('edit_serial_modal').close()">
                                    ยกเลิก
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    บันทึก
                                </button>
                            </div>
                        </form>
                    </div>
                </dialog>
            `;

            // Remove existing modal if any
            const existingModal = document.getElementById('edit_serial_modal');
            if (existingModal) {
                existingModal.remove();
            }

            // Add modal to body
            document.body.insertAdjacentHTML('beforeend', modalHtml);

            // Show modal
            document.getElementById('edit_serial_modal').showModal();
        }

        // Confirm Delete Function
        function confirmDeleteSerial(serialId, serialNumber) {
            if (confirm(`คุณแน่ใจหรือไม่ที่จะลบ Serial Number: ${serialNumber}?`)) {
                // Create form and submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'serial_db.php';

                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';

                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'serial_id';
                idInput.value = serialId;

                form.appendChild(methodInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Remove Image Function
        function removeImage(imagePath) {
            if (confirm('คุณแน่ใจหรือไม่ที่จะลบรูปภาพนี้?')) {
                // You can implement image removal logic here
                // This might involve an AJAX call to remove the image
                console.log('Removing image:', imagePath);
            }
        }
    </script>
</body>

</html>