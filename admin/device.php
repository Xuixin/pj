<?php
session_start();
require_once('./../conn.php');

$sql = "SELECT * FROM brand";
$brand = mysqli_query($conn, $sql);

$type = mysqli_query($conn, "SELECT * FROM type")

// Get models data (assuming you have a models table)

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Product Management</title>
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

        /* Enhanced Dialog Styles */
        .device-detail-dialog {
            backdrop-filter: blur(8px);
            background: rgba(0, 0, 0, 0.6);
        }

        .device-detail-content {
            max-width: 90vw;
            max-height: 90vh;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
        }

        .device-image-carousel {
            position: relative;
            height: 400px;
            overflow: hidden;
        }

        .carousel-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .carousel-nav:hover {
            background: white;
            transform: translateY(-50%) scale(1.1);
        }

        .carousel-nav.prev {
            left: 15px;
        }

        .carousel-nav.next {
            right: 15px;
        }

        .image-indicators {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
        }

        .indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .indicator.active {
            background: white;
            width: 24px;
            border-radius: 12px;
        }

        .serial-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .serial-item {
            transition: all 0.2s ease;
        }

        .serial-item:hover {
            background: rgba(59, 130, 246, 0.05);
            transform: translateX(4px);
        }

        /* Grid Animation */
        .device-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            transition: all 0.3s ease;
        }

        .device-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .device-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
        }

        .device-card.expanded {
            grid-column: 1 / -1;
            transform: scale(1.02);
        }

        .card-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.9) 0%, rgba(118, 75, 162, 0.9) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .device-card:hover .card-overlay {
            opacity: 1;
        }

        /* Status badges */
        .status-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-available {
            background: #10b981;
            color: white;
        }

        .status-out-of-stock {
            background: #ef4444;
            color: white;
        }

        /* Loading animation */
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f4f6;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .device-detail-content {
                max-width: 95vw;
                margin: 20px;
            }

            .device-image-carousel {
                height: 250px;
            }

            .device-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 15px;
            }
        }
    </style>
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
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-green-400 to-blue-500 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-white text-lg"></i>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-lg font-semibold text-gray-800">ยินดีต้อนรับ,
                            <?php echo $_SESSION['admin_name'] ?></h1>
                        <p class="text-sm text-gray-500">จัดการข้อมูลอุปกรณ์</p>
                    </div>
                </div>

            </div>

            <div class="col-span-5 row-span-7 bg-white rounded-2xl shadow-lg  grid grid-rows-8">
                <div class="row-span-1 header shadow-md flex justify-between items-center px-5">
                    <h3 class="text-xl font-semibold text-gray-800">
                        คลังอุปกรณ์
                    </h3>

                    <div class="flex space-x-5 justify-between items-center">


                        <form action="brand_db.php" class="flex w-full max-w-sm" method="post">
                            <input type="text" name="brand_name" placeholder="เพิ่มแบรนด์ใหม่"
                                class="input input-bordered w-full rounded-l-sm focus:outline-none focus:border-blue-500"
                                required>
                            <button type="submit" class="btn bg-blue-500 text-white rounded-r-sm hover:bg-blue-600">
                                เพิ่ม
                            </button>
                        </form>

                        <form method="GET" class="">
                            <select name="brand_id" id="brand_id" onchange="this.form.submit()"
                                class="select min-w-[10rem]">
                                <option value="">เรียงตามแบรนด์</option>
                                <?php

                                foreach ($brand as $row):
                                    $selected = (isset($_GET['brand_id']) && $_GET['brand_id'] == $row['brand_id']) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $row['brand_id']; ?>" <?php echo $selected; ?>>
                                        <?php echo $row['brand_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="" id="reloadPage">
                                    ค่าเริ่มต้น
                                </option>
                            </select>
                        </form>

                        <form method="GET" class="">
                            <select name="type_id" id="brand_id" onchange="this.form.submit()"
                                class="select min-w-[10rem]">
                                <option value="">เลือกหมวดหมู่อุปกรณ์</option>
                                <?php

                                foreach ($type as $row):
                                    $selected = (isset($_GET['type_id']) && $_GET['type_id'] == $row['type_id']) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $row['type_id']; ?>" <?php echo $selected; ?>>
                                        <?php echo $row['type_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="" id="reloadPage">
                                    ค่าเริ่มต้น
                                </option>
                            </select>
                        </form>

                        <script>
                            document.getElementById("reloadPage").addEventListener('click', () => {
                                location.reload();
                            })
                        </script>


                        <button class="btn btn-primary btn-md gradient-bg border-0 hover-scale shadow-lg"
                            onclick="document.getElementById('add_model_modal').showModal()">
                            <i class="fas fa-plus mr-2"></i>
                            เพิ่มโมเดลใหม่
                        </button>
                    </div>
                </div>

                <div class="row-span-7 p-6 overflow-x-auto">
                    <div id="deviceGrid" class="device-grid">
                        <!-- Sample device cards -->
                        <?php
                        $whereClause = '';
                        if (isset($_GET['brand_id']) && $_GET['brand_id'] !== '') {
                            $brand_id = intval($_GET['brand_id']);
                            $whereClause = "WHERE m.brand_id = $brand_id";
                        }
                        if (isset($_GET['type_id']) && $_GET['type_id'] !== '') {
                            $type_id = intval($_GET['type_id']);
                            $whereClause = "WHERE m.type_id = $type_id";
                        }

                        $model_sql = "SELECT 
                                    m.model_id,
                                    m.model_name,
                                    b.brand_name,
                                    m.price_per_month,
                                    MIN(m_img.img_path) AS img_path, -- รูปแรกตามลำดับตัวอักษร
                                    COUNT(DISTINCT d.device_id) AS serial_count,
                                    SUM(CASE WHEN d.status = 'ว่าง' THEN 1 ELSE 0 END) AS available_serial
                                FROM model AS m
                                LEFT JOIN brand AS b ON b.brand_id = m.brand_id
                                LEFT JOIN model_img AS m_img ON m_img.model_id = m.model_id
                                LEFT JOIN device AS d ON d.model_id = m.model_id
                                $whereClause
                                GROUP BY m.model_id
                                ";

                        $result = mysqli_query($conn, $model_sql);
                        $models = [];


                        foreach ($result as $row) {

                            $models[] = [
                                'model_id' => $row['model_id'],
                                'model_name' => $row['model_name'],
                                'brand_name' => $row['brand_name'],
                                'price_per_month' => $row['price_per_month'],
                                'img_path' => $row['img_path'], // ได้เพียง 1 รูป
                                'serial_count' => $row['serial_count'],
                                'available_serial' => $row['available_serial']
                            ];
                        }

                        foreach ($models as $device): ?>
                            <div class="device-card card bg-base-100 shadow-lg hover-scale"
                                onclick="window.location.href='device_detail.php?model_id=<?php echo $device['model_id']; ?>'">

                                <figure class="relative">
                                    <img src="<?php echo $device['img_path']; ?>" alt="<?php echo $device['model_name']; ?>"
                                        class="w-full h-48 object-cover" />
                                    <div class="card-overlay">
                                        <span><i class="fas fa-eye mr-2"></i>ดูรายละเอียด</span>
                                    </div>

                                </figure>
                                <div class="card-body p-4">
                                    <h2 class="card-title text-lg"><?php echo $device['model_name']; ?></h2>
                                    <p class="text-gray-600"><?php echo $device['brand_name']; ?> </p>
                                    <h3 class=" "> ราคาเช่าต่อเดือน: <span
                                            class="text-lg font-bold"><?= $device['price_per_month'] ?></span></h3>
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="text-sm text-gray-500">
                                            <i class="fas fa-hashtag mr-1"></i>
                                            <?php echo $device['serial_count']; ?> รายการ
                                        </span>
                                        <span
                                            class="badge text-white <?php echo $device['available_serial'] > 0 ? 'badge-success' : 'badge-error'; ?> text-xs px-3 py-1">
                                            <?php echo $device['available_serial'] > 0 ? 'ว่าง ' . $device['available_serial'] : 'ถูกเช่าหมดแล้ว'; ?>
                                        </span>

                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Model Modal (existing) -->
    <dialog id="add_model_modal" class="modal">
        <div class="modal-box w-11/12 max-w-3xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center mb-6">
                <div
                    class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-laptop text-white text-lg"></i>
                </div>
                <div class="ml-4">
                    <h3 class="font-bold text-2xl text-gray-800">เพิ่มโมเดลใหม่</h3>
                    <p class="text-gray-600">กรอกข้อมูลโมเดลใหม่ในระบบ</p>
                </div>
            </div>

            <form id="add_model_form" method="post" action="device_db.php" enctype="multipart/form-data"
                class="space-y-6">
                <input type="hidden" name="_method" value="POST">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Model Name -->
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">
                                <i class="fas fa-tag mr-2 text-blue-500"></i>
                                ชื่อโมเดล
                            </span>
                        </label>
                        <input type="text" name="model_name" placeholder="เช่น MacBook Pro M2"
                            class="input input-bordered w-full focus:border-blue-500 transition-colors" required>
                    </div>

                    <!-- Brand Dropdown -->
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">
                                <i class="fas fa-building mr-2 text-purple-500"></i>
                                แบรนด์
                            </span>
                        </label>
                        <select name="brand_id"
                            class="select select-bordered w-full focus:border-purple-500 transition-colors" required>
                            <option disabled selected>เลือกแบรนด์</option>
                            <?php foreach ($brand as $row): ?>
                                <option value="<?php echo $row['brand_id'] ?>"><?php echo $row['brand_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">
                                <i class="fas fa-building mr-2 text-purple-500"></i>
                                หมวดหมู่
                            </span>
                        </label>
                        <select name="type_id"
                            class="select select-bordered w-full focus:border-purple-500 transition-colors" required>
                            <option disabled selected>เลือกหมวดหมู่อุปกรณ์</option>
                            <?php foreach ($type as $row): ?>
                                <option value="<?php echo $row['type_id'] ?>"><?php echo $row['type_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">
                                <i class="fas fa-building mr-2 text-purple-500"></i>
                                ราคาเช่าต่อเดือน
                            </span>
                        </label>
                        <input type="number" min="1" slot="2" name="price_per_month" placeholder="1000.00"
                            class="input input-bordered w-full focus:border-blue-500 transition-colors" required>
                    </div>
                </div>

                <!-- Image Upload Section -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">
                            <i class="fas fa-images mr-2 text-green-500"></i>
                            รูปภาพโมเดล
                        </span>
                        <span class="label-text-alt text-gray-500">
                            อัพโหลดได้หลายรูป (PNG, JPG, JPEG)
                        </span>
                    </label>

                    <!-- Upload Area -->
                    <div class="upload-area border-2 border-dashed border-gray-300 rounded-xl p-8 text-center bg-gray-50 hover:border-blue-400 hover:bg-blue-50 transition-all duration-300 cursor-pointer"
                        onclick="document.getElementById('imageInput').click()">
                        <div class="space-y-4">
                            <div class="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-cloud-upload-alt text-2xl text-blue-500"></i>
                            </div>
                            <div>
                                <p class="text-lg font-medium text-gray-700">คลิกเพื่ออัพโหลดรูปภาพ</p>
                                <p class="text-sm text-gray-500">หรือลากไฟล์มาวางที่นี่</p>
                            </div>
                            <div class="flex items-center justify-center space-x-2 text-xs text-gray-400">
                                <i class="fas fa-info-circle"></i>
                                <span>PNG, JPG, JPEG ขนาดไม่เกิน 5MB ต่อไฟล์</span>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden File Input -->
                    <input type="file" id="imageInput" name="model_images[]" multiple accept="image/*" class="hidden"
                        onchange="previewImages(this)">
                </div>

                <!-- Image Preview Section -->
                <div id="imagePreviewContainer" class="hidden">
                    <label class="label">
                        <span class="label-text font-semibold">
                            <i class="fas fa-eye mr-2 text-orange-500"></i>
                            ตัวอย่างรูปภาพ
                        </span>
                        <span class="label-text-alt">
                            <span id="imageCount">0</span> รูป
                        </span>
                    </label>
                    <div id="imagePreviewGrid"
                        class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 p-4 bg-gray-50 rounded-lg max-h-60 overflow-y-auto">
                        <!-- Preview images will be inserted here -->
                    </div>
                </div>

                <!-- Specifications Section (Dynamic Add) -->
                <div class="border-t pt-6 mt-6">
                    <h4 class="font-bold text-lg mb-4 flex items-center text-gray-800">
                        <i class="fas fa-list mr-2 text-indigo-500"></i>
                        รายละเอียดสเปกสินค้า (เพิ่มได้ไม่จำกัด)
                    </h4>

                    <div id="specContainer" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 spec-item">
                            <div class="form-control">
                                <input type="text" name="spec_name[]" placeholder="ชื่อสเปก เช่น Screen Size"
                                    class="input input-bordered w-full" />
                            </div>
                            <div class="flex gap-2">
                                <input type="text" name="spec_value[]" placeholder="ค่าของสเปก เช่น 15.6 inch"
                                    class="input input-bordered w-full" />
                                <button type="button" class="btn btn-error btn-sm" onclick="removeSpec(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="button" class="btn btn-outline btn-primary btn-sm" onclick="addSpecField()">
                            <i class="fas fa-plus mr-2"></i> เพิ่มสเปก
                        </button>
                    </div>
                </div>


                <!-- Form Actions -->
                <div class="modal-action pt-6 border-t">
                    <button type="button" class="btn btn-ghost hover:bg-gray-100 transition-colors"
                        onclick="resetForm(); document.getElementById('add_model_modal').close()">
                        <i class="fas fa-times mr-2"></i>
                        ยกเลิก
                    </button>
                    <button type="submit" class="btn btn-primary gradient-bg border-0 hover:shadow-lg transition-all">
                        <i class="fas fa-save mr-2"></i>
                        บันทึกข้อมูล
                    </button>
                </div>
            </form>
        </div>

        <!-- Modal Backdrop -->
        <form method="dialog" class="modal-backdrop">
            <button type="button" onclick="resetForm()">close</button>
        </form>
    </dialog>





    <?php
    include('./../lib/toast.php')
    ?>

    <script src="./../scripts/main.js"></script>

    <script>
        function addSpecField() {
            const container = document.getElementById('specContainer');
            const specItem = document.createElement('div');
            specItem.className = 'grid grid-cols-1 md:grid-cols-2 gap-4 spec-item';
            specItem.innerHTML = `
    <div class="form-control">
      <input type="text" name="spec_name[]" placeholder="ชื่อสเปก เช่น Processor"
        class="input input-bordered w-full" />
    </div>
    <div class="flex gap-2">
      <input type="text" name="spec_value[]" placeholder="ค่าของสเปก เช่น AMD Ryzen 7"
        class="input input-bordered w-full" />
      <button type="button" class="btn btn-error btn-sm" onclick="removeSpec(this)">
        <i class="fas fa-trash"></i>
      </button>
    </div>
  `;
            container.appendChild(specItem);
        }

        function removeSpec(button) {
            button.closest('.spec-item').remove();
        }
    </script>


    <script>
        let selectedImages = [];
        let currentDeviceId = null;
        let currentImageIndex = 0;
        let deviceImages = [];

        // Device detail functions
        async function openDeviceDetail(deviceId) {
            currentDeviceId = deviceId;
            const modal = document.getElementById('device_detail_modal');
            const loading = document.getElementById('deviceDetailLoading');
            const content = document.getElementById('deviceDetailContent');

            // Show modal and loading state
            modal.showModal();
            loading.style.display = 'flex';
            content.classList.add('hidden');

            try {
                // Simulate API call - replace with actual data fetching
                await new Promise(resolve => setTimeout(resolve, 800));

                // Sample data - replace with actual API call
                const deviceData = await fetchDeviceData(deviceId);
                populateDeviceDetail(deviceData);

                loading.style.display = 'none';
                content.classList.remove('hidden');
            } catch (error) {
                console.error('Error loading device details:', error);
                loading.innerHTML = '<div class="text-red-500"><i class="fas fa-exclamation-triangle mr-2"></i>เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
            }
        }



        function populateDeviceDetail(data) {
            if (!data) return;

            // Populate basic info
            document.getElementById('deviceName').textContent = data.name;
            document.getElementById('deviceBrand').innerHTML = `<i class="fas fa-building mr-2"></i>${data.brand}`;
            document.getElementById('serialCount').textContent = data.serials.length;

            // Status badge
            const statusElement = document.getElementById('deviceStatus');
            if (data.status === 'available') {
                statusElement.className = 'badge badge-lg badge-success';
                statusElement.textContent = 'พร้อมใช้';
            } else {
                statusElement.className = 'badge badge-lg badge-error';
                statusElement.textContent = 'หมด';
            }

            // Populate images
            deviceImages = data.images;
            currentImageIndex = 0;
            setupImageCarousel();

            // Populate serial numbers
            const serialsList = document.getElementById('serialNumbersList');
            serialsList.innerHTML = '';

            if (data.serials.length === 0) {
                serialsList.innerHTML = '<div class="text-gray-500 text-center py-8"><i class="fas fa-inbox mr-2"></i>ไม่มีหมายเลขซีเรียล</div>';
            } else {
                data.serials.forEach(serial => {
                    const serialElement = createSerialElement(serial);
                    serialsList.appendChild(serialElement);
                });
            }
        }

        function setupImageCarousel() {
            const container = document.getElementById('deviceImageContainer');
            const indicators = document.getElementById('imageIndicators');

            container.innerHTML = '';
            indicators.innerHTML = '';

            if (deviceImages.length === 0) {
                container.innerHTML = '<div class="w-full h-full bg-gray-200 flex items-center justify-center text-gray-500"><i class="fas fa-image text-4xl"></i></div>';
                return;
            }

            // Create images
            deviceImages.forEach((image, index) => {
                const img = document.createElement('img');
                img.src = image;
                img.alt = `Device image ${index + 1}`;
                mg.className = `carousel-image absolute inset-0 ${index === 0 ? '' : 'hidden'}`;
                img.dataset.index = index;
                container.appendChild(img);

                const indicator = document.createElement('div');
                indicator.className = `indicator ${index === 0 ? 'active' : ''}`;
                indicator.dataset.index = index;
                indicator.onclick = () => showImage(index);
                indicators.appendChild(indicator);
            });

            // If multiple images, add navigation
            if (deviceImages.length > 1) {
                const prevBtn = document.createElement('button');
                prevBtn.className = 'carousel-nav prev';
                prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
                prevBtn.onclick = () => showImage(currentImageIndex - 1);
                container.appendChild(prevBtn);

                const nextBtn = document.createElement('button');
                nextBtn.className = 'carousel-nav next';
                nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
                nextBtn.onclick = () => showImage(currentImageIndex + 1);
                container.appendChild(nextBtn);
            }
        }

        function showImage(index) {
            const total = deviceImages.length;
            if (index < 0) index = total - 1;
            if (index >= total) index = 0;
            currentImageIndex = index;

            const images = document.querySelectorAll('#deviceImageContainer .carousel-image');
            const indicators = document.querySelectorAll('#imageIndicators .indicator');

            images.forEach(img => img.classList.add('hidden'));
            images[currentImageIndex].classList.remove('hidden');

            indicators.forEach(ind => ind.classList.remove('active'));
            indicators[currentImageIndex].classList.add('active');
        }

        function createSerialElement(serial) {
            const div = document.createElement('div');
            div.className = 'serial-item p-3 border rounded-lg bg-white shadow-sm flex justify-between items-center';

            const left = document.createElement('div');
            left.innerHTML = `
                <div class="font-semibold text-gray-800">${serial.serial}</div>
                <div class="text-sm text-gray-500 flex items-center space-x-2">
                    <span><i class="fas fa-map-marker-alt mr-1"></i>${serial.location}</span>
                    ${serial.assignedTo ? `<span><i class="fas fa-user mr-1"></i>${serial.assignedTo}</span>` : ''}
                </div>
            `;

            const right = document.createElement('div');
            right.innerHTML = `<span class="badge ${getStatusBadge(serial.status)}">${getStatusText(serial.status)}</span>`;

            div.appendChild(left);
            div.appendChild(right);

            return div;
        }

        function getStatusBadge(status) {
            switch (status) {
                case 'available':
                    return 'badge-success';
                case 'in-use':
                    return 'badge-warning';
                case 'maintenance':
                    return 'badge-error';
                default:
                    return 'badge-ghost';
            }
        }

        function getStatusText(status) {
            switch (status) {
                case 'available':
                    return 'พร้อมใช้';
                case 'in-use':
                    return 'ใช้งานอยู่';
                case 'maintenance':
                    return 'ซ่อมบำรุง';
                default:
                    return 'ไม่ระบุ';
            }
        }

        function closeDeviceDetail() {
            document.getElementById('device_detail_modal').close();
        }

        function resetForm() {
            document.getElementById('add_model_form').reset();
            document.getElementById('imageInput').value = '';
            document.getElementById('imagePreviewContainer').classList.add('hidden');
            document.getElementById('imagePreviewGrid').innerHTML = '';
            document.getElementById('imageCount').textContent = '0';
        }

        function previewImages(input) {
            const previewContainer = document.getElementById('imagePreviewContainer');
            const previewGrid = document.getElementById('imagePreviewGrid');
            const countSpan = document.getElementById('imageCount');

            previewGrid.innerHTML = '';
            const files = input.files;

            if (files.length > 0) {
                previewContainer.classList.remove('hidden');
                countSpan.textContent = files.length;
            }

            Array.from(files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'rounded-lg w-full h-32 object-cover';
                    previewGrid.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        }
    </script>
</body>

</html>