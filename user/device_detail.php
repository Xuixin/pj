<?php
session_start();
require_once('./../conn.php');

// Validate model_id
if (!isset($_GET['model_id'])) {
    header('Location: devices.php');
    exit;
}

$model_id = intval($_GET['model_id']);

// Get model info with spec
$sql = "SELECT 
            m.model_id,
            m.model_name,
            m.spec,
            m.price_per_month,
            b.brand_name,
            t.type_name,
            COUNT(d.device_id) AS total_devices,
            SUM(CASE WHEN d.status = 'ว่าง' THEN 1 ELSE 0 END) AS available_devices
        FROM model m 
        LEFT JOIN brand b ON m.brand_id = b.brand_id 
        LEFT JOIN type t ON t.type_id = m.type_id 
        LEFT JOIN device d ON m.model_id = d.model_id 
        WHERE m.model_id = $model_id
        GROUP BY m.model_id";

$result = mysqli_query($conn, $sql);
$model = mysqli_fetch_assoc($result);

if (!$model) {
    header('Location: devices.php?error=model_not_found');
    exit;
}

// Get model images
$images_sql = "SELECT img_path FROM model_img WHERE model_id = $model_id ORDER BY model_img_id";
$images_result = mysqli_query($conn, $images_sql);
$images = [];
while ($row = mysqli_fetch_assoc($images_result)) {
    $images[] = $row['img_path'];
}

// Parse spec JSON
$specs = [];
if (!empty($model['spec'])) {
    $specs = json_decode($model['spec'], true);
    if (!is_array($specs)) {
        $specs = [];
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($model['model_name']) ?> - TechRent</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to bottom, #f8fafc 0%, #f1f5f9 100%);
        }

        .card-subtle {
            background: white;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .card-subtle:hover {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border-color: #cbd5e1;
        }

        .image-carousel {
            position: relative;
            overflow: hidden;
            border-radius: 16px;
        }

        .carousel-image {
            transition: opacity 0.4s ease;
        }

        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 50%;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
            color: #475569;
        }

        .carousel-nav:hover {
            background: white;
            transform: translateY(-50%) scale(1.1);
            color: #1e293b;
        }

        .carousel-nav.prev {
            left: 16px;
        }

        .carousel-nav.next {
            right: 16px;
        }

        .image-indicators {
            position: absolute;
            bottom: 16px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 6px;
            background: rgba(0, 0, 0, 0.3);
            padding: 8px 12px;
            border-radius: 20px;
            backdrop-filter: blur(8px);
        }

        .indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .indicator.active {
            background: white;
            width: 24px;
            border-radius: 12px;
        }

        .spec-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .spec-table tr {
            border-bottom: 1px solid #f1f5f9;
        }

        .spec-table tr:last-child {
            border-bottom: none;
        }

        .spec-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .spec-table tr:hover {
            background-color: #f1f5f9;
        }

        .spec-table td {
            padding: 12px 16px;
            vertical-align: top;
        }

        .spec-name {
            font-weight: 600;
            color: #374151;
            width: 40%;
        }

        .spec-value {
            color: #6b7280;
            width: 60%;
        }

        .price-card {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .status-available {
            background: #dcfce7;
            color: #166534;
        }

        .status-limited {
            background: #fef3c7;
            color: #92400e;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }

        .thumbnail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 8px;
            margin-top: 12px;
        }

        .thumbnail {
            aspect-ratio: 1;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.2s ease;
        }

        .thumbnail:hover {
            border-color: #cbd5e1;
        }

        .thumbnail.active {
            border-color: #3b82f6;
        }

        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #3b82f6;
            font-size: 1.1rem;
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <?php include('./components/navbar.php'); ?>

    <!-- Breadcrumb -->
    <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="breadcrumbs text-sm">
            <ul>
                <li><a href="devices.php" class="text-blue-600 hover:text-blue-700">รายการอุปกรณ์</a></li>
                <li><span class="text-gray-500"><?= htmlspecialchars($model['model_name']) ?></span></li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 pb-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Left Column - Images (2 columns on large screens) -->
            <div class="lg:col-span-2 space-y-4">
                <div class="card-subtle rounded-2xl overflow-hidden">
                    <div class="image-carousel">
                        <?php if (!empty($images)): ?>
                            <div class="relative bg-gray-100" style="height: 500px;">
                                <?php foreach ($images as $index => $image): ?>
                                    <img src="<?= htmlspecialchars($image) ?>"
                                        alt="<?= htmlspecialchars($model['model_name']) ?>"
                                        class="carousel-image absolute inset-0 w-full h-full object-contain <?= $index === 0 ? '' : 'hidden' ?>"
                                        data-index="<?= $index ?>">
                                <?php endforeach; ?>

                                <?php if (count($images) > 1): ?>
                                    <button class="carousel-nav prev" onclick="previousImage()">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <button class="carousel-nav next" onclick="nextImage()">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>

                                    <div class="image-indicators">
                                        <?php foreach ($images as $index => $image): ?>
                                            <div class="indicator <?= $index === 0 ? 'active' : '' ?>"
                                                onclick="showImage(<?= $index ?>)"></div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if (count($images) > 1): ?>
                                <div class="p-4 bg-white">
                                    <div class="thumbnail-grid">
                                        <?php foreach ($images as $index => $image): ?>
                                            <div class="thumbnail <?= $index === 0 ? 'active' : '' ?>"
                                                onclick="showImage(<?= $index ?>)"
                                                data-thumbnail="<?= $index ?>">
                                                <img src="<?= htmlspecialchars($image) ?>"
                                                    alt="Thumbnail <?= $index + 1 ?>">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div style="height: 500px;" class="bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                                <div class="text-center text-gray-400">
                                    <i class="fas fa-image text-6xl mb-4"></i>
                                    <p>ไม่มีรูปภาพ</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Specifications Section -->
                <?php if (!empty($specs)): ?>
                    <div class="card-subtle rounded-2xl p-6">
                        <h2 class="section-title">
                            <i class="fas fa-microchip"></i>
                            คุณสมบัติสินค้า
                        </h2>
                        <table class="spec-table">
                            <?php foreach ($specs as $spec): ?>
                                <tr>
                                    <td class="spec-name"><?= htmlspecialchars($spec['name']) ?></td>
                                    <td class="spec-value"><?= htmlspecialchars($spec['value']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Column - Details & Actions (1 column on large screens) -->
            <div class="space-y-4">
                <!-- Product Info -->
                <div class="card-subtle rounded-2xl p-6">
                    <div class="mb-4">
                        <span class="badge badge-info badge-sm mb-2"><?= htmlspecialchars($model['type_name']) ?></span>
                        <h1 class="text-2xl font-bold text-gray-800 mb-1">
                            <?= htmlspecialchars($model['model_name']) ?>
                        </h1>
                        <p class="text-lg text-gray-600"><?= htmlspecialchars($model['brand_name']) ?></p>
                    </div>

                    <div class="info-row">
                        <span class="text-gray-600">สถานะ</span>
                        <?php if ($model['available_devices'] > 3): ?>
                            <span class="status-badge status-available">
                                <i class="fas fa-check-circle"></i>
                                พร้อมให้เช่า
                            </span>
                        <?php elseif ($model['available_devices'] > 0): ?>
                            <span class="status-badge status-limited">
                                <i class="fas fa-exclamation-circle"></i>
                                เหลือจำนวนจำกัด
                            </span>
                        <?php else: ?>
                            <span class="status-badge" style="background: #fee2e2; color: #991b1b;">
                                <i class="fas fa-times-circle"></i>
                                หมดสต็อก
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="info-row">
                        <span class="text-gray-600">จำนวนว่าง</span>
                        <span class="font-semibold text-gray-800">
                            <?= (int)$model['available_devices'] ?> / <?= (int)$model['total_devices'] ?> เครื่อง
                        </span>
                    </div>
                </div>

                <!-- Price Card -->
                <div class="price-card">
                    <div class="text-sm opacity-90 mb-1">ราคาเช่าต่อเดือน</div>
                    <div class="text-3xl font-bold">฿<?= number_format($model['price_per_month'], 2) ?></div>
                    <div class="text-xs opacity-75 mt-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        ราคาไม่รวมค่ามัดจำ
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card-subtle rounded-2xl p-6">
                    <div class="space-y-3">
                        <?php if ($model['available_devices'] > 0): ?>
                            <a href="rent.php?model_id=<?= $model['model_id'] ?>"
                                class="btn btn-primary-custom btn-lg w-full">
                                <i class="fas fa-shopping-cart mr-2"></i>
                                เช่าอุปกรณ์นี้
                            </a>
                        <?php else: ?>
                            <button class="btn btn-lg w-full" disabled style="background: #e5e7eb; color: #9ca3af;">
                                <i class="fas fa-times-circle mr-2"></i>
                                อุปกรณ์หมดสต็อก
                            </button>
                        <?php endif; ?>

                        <a href="devices.php"
                            class="btn btn-outline btn-lg w-full border-gray-300 hover:border-gray-400">
                            <i class="fas fa-arrow-left mr-2"></i>
                            กลับไปรายการอุปกรณ์
                        </a>
                    </div>
                </div>

                <!-- Additional Info -->
                <div class="card-subtle rounded-2xl p-6">
                    <h3 class="font-semibold text-gray-800 mb-3">
                        <i class="fas fa-shield-alt text-blue-600 mr-2"></i>
                        การรับประกัน
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check text-green-600 mt-1"></i>
                            <span>ตรวจสอบคุณภาพก่อนส่งมอบ</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check text-green-600 mt-1"></i>
                            <span>รับประกันการทำงานตลอดระยะเวลาเช่า</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check text-green-600 mt-1"></i>
                            <span>บริการซ่อมบำรุงฟรี</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include('./components/footer.php'); ?>

    <script>
        let currentImageIndex = 0;
        const totalImages = <?= count($images) ?>;

        function showImage(index) {
            if (index < 0) index = totalImages - 1;
            if (index >= totalImages) index = 0;

            currentImageIndex = index;

            document.querySelectorAll('.carousel-image').forEach(img => {
                img.classList.add('hidden');
            });

            document.querySelector(`[data-index="${index}"]`).classList.remove('hidden');

            document.querySelectorAll('.indicator').forEach((indicator, i) => {
                indicator.classList.toggle('active', i === index);
            });

            document.querySelectorAll('.thumbnail').forEach((thumb, i) => {
                thumb.classList.toggle('active', i === index);
            });
        }

        function nextImage() {
            showImage(currentImageIndex + 1);
        }

        function previousImage() {
            showImage(currentImageIndex - 1);
        }

        if (totalImages > 1) {
            setInterval(() => {
                nextImage();
            }, 5000);
        }
    </script>

</body>