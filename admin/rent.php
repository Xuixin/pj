<?php
session_start();
require_once('./../conn.php');
require_once('./../lib/format_date.php');
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

        .date-display {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 0.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-slate-100 to-slate-300">

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
                        <p class="text-sm text-gray-500">จัดการข้อมูลการเช่า</p>
                    </div>
                </div>

            </div>

            <!-- Main Content Area -->
            <div class="bg-white col-span-5 row-span-7 flex flex-col rounded-2xl border-gray-800 shadow-lg">
                <div class="flex justify-between shadow-md items-center p-5">
                    <p class="text-xl font-semibold text-gray-800">จัดการการเช่า</p>
                    <button class="btn btn-primary btn-md gradient-bg border-0 hover-scale shadow-lg"
                        onclick="window.location.href = 'rent_insert.php' ">
                        <i class="fas fa-plus mr-2"></i>
                        ทำรายการใหม่
                    </button>
                </div>

                <!-- Table Content Area -->
                <div class="flex-1 p-5 overflow-y-auto">
                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full">
                            <thead>
                                <tr>
                                    <th>ลำดับ</th>
                                    <th>ผู้ใช้</th>
                                    <th>ระยะเวลา</th>
                                    <th>วันที่อนุมัติ</th>
                                    <th>วันที่คืน</th>
                                    <th>สถานะการตรวจสภาพ</th>
                                    <th>ใบสัญญา</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                                $sql_data_rental = "SELECT r.* , u.user_name FROM rent r
                        LEFT JOIN user as u ON u.user_id = r.user_id
                        ORDER BY r.rent_id DESC";
                                $result = mysqli_query($conn, $sql_data_rental);

                                if (mysqli_num_rows($result) > 0):
                                    foreach ($result as $row):
                                ?>
                                        <tr>
                                            <td><?= $i++; ?></td>
                                            <td><?= htmlspecialchars($row['user_name']) ?></td>
                                            <td><?= calculateDateDiffThai($row['start_date'], $row['end_date']) ?></td>
                                            <td><?= formatThaiShortDateTime($row['start_date']) ?></td>
                                            <td><?= formatThaiShortDateTime($row['end_date']) ?></td>

                                            <!-- สถานะ PM -->
                                            <td>

                                                <?php
                                                switch ($row['rent_status']) {
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
                                                </form>
                                            </td>


                                            <!-- ใบสัญญา -->
                                            <td>
                                                <?php if ($row['file_lease'] === null || $row['file_lease'] === ''): ?>
                                                    <button
                                                        class="btn btn-sm btn-outline btn-primary"
                                                        onclick="openContractModal(
                                                                <?= $row['rent_id'] ?>,
                                                                '<?= htmlspecialchars(addslashes($row['user_name'])) ?>',
                                                                '<?= calculateDateDiffThai($row['start_date'], $row['end_date']) ?>',
                                                                '<?= formatThaiShortDateTime($row['pm_latest']) ?>'
                                                            )">
                                                        ➕ เพิ่ม
                                                    </button>
                                                <?php endif; ?>

                                                <?php if ($row['file_lease']): ?>
                                                    <a href="rent_payments.php?rent_id=<?= $row['rent_id'] ?>"
                                                        class="btn btn-sm btn-outline btn-primary">
                                                        <i class="fas fa-eye mr-1"></i> การเงิน
                                                    </a>

                                                <?php endif; ?>
                                            </td>

                                            <!-- จัดการ -->
                                            <td>
                                                <a href="./contract_viewer.php?rent_id=<?php echo $row['rent_id']   ?>" class="btn btn-sm btn-outline">📄 ดูรายละเอียด</a>
                                                <a href="rent_detail.php?rent_id=<?= $row['rent_id'] ?>" class="btn btn-sm btn-outline btn-info">จัดการ</a>
                                            </td>
                                        </tr>
                                    <?php
                                    endforeach;
                                else:
                                    ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-gray-500">ไม่มีข้อมูล</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>

                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add contact Modal -->
    <div id="contract_modal" class="modal">
        <div class="modal-box w-11/12 max-w-lg">
            <h3 class="font-bold text-lg mb-4">
                <i class="fas fa-file-contract mr-2"></i>
                อัปโหลดไฟล์ใบสัญญา
            </h3>

            <form id="contract_upload_form" method="post" action="contract_upload.php" enctype="multipart/form-data">
                <input type="hidden" id="rent_id_input" name="rent_id" value="">

                <div class="space-y-4">
                    <!-- ข้อมูลการเช่า -->
                    <div class="bg-base-200 p-4 rounded-lg">
                        <h4 class="font-semibold mb-2">ข้อมูลการเช่า</h4>
                        <div class="text-sm space-y-1">
                            <p><span class="font-medium">ผู้เช่า:</span> <span id="modal_user_name">-</span></p>
                            <p><span class="font-medium">ระยะเวลา:</span> <span id="modal_duration">-</span></p>
                            <p><span class="font-medium">วันที่อนุมัติ:</span> <span id="modal_approve_date">-</span></p>
                        </div>
                    </div>

                    <!-- อัปโหลดไฟล์ -->
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">เลือกไฟล์ใบสัญญา <span class="text-red-500">*</span></span>
                        </label>
                        <input type="file" name="contract_file" id="contract_file"
                            class="file-input file-input-bordered w-full"
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                        <label class="label">
                            <span class="label-text-alt">รองรับไฟล์: PDF, DOC, DOCX, JPG, PNG (ขนาดไม่เกิน 10MB)</span>
                        </label>
                    </div>

                    <!-- แสดงตัวอย่างไฟล์ที่เลือก -->
                    <div id="file_preview" class="hidden">
                        <div class="bg-base-100 border-2 border-dashed border-base-300 rounded-lg p-4">
                            <div class="flex items-center space-x-3">
                                <i id="file_icon" class="fas fa-file-alt text-2xl text-primary"></i>
                                <div>
                                    <p id="file_name" class="font-medium"></p>
                                    <p id="file_size" class="text-sm text-gray-500"></p>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>

                <!-- Progress Bar -->
                <div id="upload_progress" class="hidden mt-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="loading loading-spinner loading-sm"></span>
                        <span>กำลังอัปโหลดไฟล์...</span>
                    </div>
                    <progress class="progress progress-primary w-full" value="0" max="100" id="progress_bar"></progress>
                    <p class="text-sm text-gray-500 mt-1" id="progress_text">0%</p>
                </div>

                <div class="modal-action">
                    <button type="button" class="btn btn-ghost" onclick="closeContractModal()">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary" id="upload_contract_btn">
                        <i class="fas fa-upload mr-2"></i>
                        อัปโหลด
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openContractModal(rentId, userName, duration, approveDate) {
            document.getElementById('rent_id_input').value = rentId;
            document.getElementById('modal_user_name').textContent = userName;
            document.getElementById('modal_duration').textContent = duration;
            document.getElementById('modal_approve_date').textContent = approveDate;
            document.getElementById('contract_modal').classList.add('modal-open');
        }

        // Close Contract Modal
        function closeContractModal() {
            document.getElementById('contract_modal').classList.remove('modal-open');
            document.getElementById('contract_upload_form').reset();
            document.getElementById('file_preview').classList.add('hidden');
            document.getElementById('upload_progress').classList.add('hidden');
        }

        // Handle file selection
        document.getElementById('contract_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('file_preview');

            if (file) {
                // Show file preview
                preview.classList.remove('hidden');

                // Set file icon based on type
                const fileIcon = document.getElementById('file_icon');
                const fileName = document.getElementById('file_name');
                const fileSize = document.getElementById('file_size');

                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);

                // Set appropriate icon
                if (file.type.includes('pdf')) {
                    fileIcon.className = 'fas fa-file-pdf text-2xl text-red-500';
                } else if (file.type.includes('word') || file.name.endsWith('.doc') || file.name.endsWith('.docx')) {
                    fileIcon.className = 'fas fa-file-word text-2xl text-blue-500';
                } else if (file.type.includes('image')) {
                    fileIcon.className = 'fas fa-file-image text-2xl text-green-500';
                } else {
                    fileIcon.className = 'fas fa-file-alt text-2xl text-gray-500';
                }

                // Validate file size (10MB limit)
                if (file.size > 10 * 1024 * 1024) {
                    alert('ไฟล์มีขนาดใหญ่เกินไป (เกิน 10MB)');
                    e.target.value = '';
                    preview.classList.add('hidden');
                }
            } else {
                preview.classList.add('hidden');
            }
        });

        // Format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    </script>




    <?php
    include('./../lib/toast.php')
    ?>

    <script src="./../scripts/main.js"></script>

    <script>
        // Set today's date as default
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('approve_date').value = today;
        });

        // ผู้ใช้ autocomplete
        document.getElementById('user_name_input').addEventListener('input', function() {
            const inputVal = this.value;
            const options = document.querySelectorAll('#userList option');
            let found = false;

            options.forEach(option => {
                if (option.value === inputVal) {
                    document.getElementById('user_id_hidden').value = option.dataset.id;
                    found = true;
                }
            });

            if (!found) {
                document.getElementById('user_id_hidden').value = '';
            }
        });

        // ผู้อนุมัติ autocomplete
        document.getElementById('admin_name_input').addEventListener('input', function() {
            const inputVal = this.value;
            const options = document.querySelectorAll('#adminList option');
            let found = false;

            options.forEach(option => {
                if (option.value === inputVal) {
                    document.getElementById('admin_id_hidden').value = option.dataset.id;
                    found = true;
                }
            });

            if (!found) {
                document.getElementById('admin_id_hidden').value = '';
            }
        });

        // Calculate return date based on rent type and duration
        function calculateReturnDate() {
            const approveDate = document.getElementById('approve_date').value;
            const rentType = document.getElementById('rent_type').value;
            const duration = parseInt(document.getElementById('rent_duration').value);

            if (!approveDate || !rentType || !duration) {
                document.getElementById('return_date_display').textContent = 'กรุณาเลือกวันที่อนุมัติและประเภทการเช่าก่อน';
                document.getElementById('return_date_hidden').value = '';
                return;
            }

            const startDate = new Date(approveDate);
            let returnDate = new Date(startDate);

            switch (rentType) {
                case 'daily':
                    returnDate.setDate(startDate.getDate() + duration);
                    break;
                case 'monthly':
                    returnDate.setMonth(startDate.getMonth() + duration);
                    break;
                case 'yearly':
                    returnDate.setFullYear(startDate.getFullYear() + duration);
                    break;
            }

            const formattedDate = returnDate.toLocaleDateString('th-TH', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            document.getElementById('return_date_display').textContent = formattedDate;
            document.getElementById('return_date_hidden').value = returnDate.toISOString().split('T')[0];
        }

        // Recalculate when approve date changes
        document.getElementById('approve_date').addEventListener('change', calculateReturnDate);

        // Form validation
        document.getElementById('add_rent_form').addEventListener('submit', async function(e) {
            e.preventDefault(); // ต้องมีเพื่อป้องกันฟอร์มส่งซ้ำ

            const user_id = document.getElementById('user_id_hidden').value;
            const admin_id = document.getElementById('admin_id_hidden').value;
            const start_date = document.getElementById('approve_date').value;
            const return_date = document.getElementById('return_date_hidden').value;

            if (!user_id || !admin_id || !start_date || !return_date) {
                alert("กรุณากรอกข้อมูลให้ครบทุกช่องก่อนส่ง");
                return;
            }

            if (cart.length === 0) {
                alert("กรุณาเพิ่มอุปกรณ์อย่างน้อย 1 รายการ");
                return;
            }

            try {
                const response = await fetch('rent_db.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id,
                        admin_id,
                        start_date,
                        return_date,
                        cart
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // เปลี่ยนเส้นทางไปหน้า generate contract
                    window.location.href = 'generate_contract.php';
                } else {
                    alert(result.message || "เกิดข้อผิดพลาด");
                }
            } catch (error) {
                console.error('Fetch error:', error);
                alert('เกิดข้อผิดพลาดขณะส่งข้อมูล');
            }
        });
    </script>

</body>

</html>