<?php
session_start();
require_once('./../conn.php');

// ดึงประเภททั้งหมดเพื่อแสดงใน dropdown
$type_result = mysqli_query($conn, "SELECT * FROM type ORDER BY type_name ASC");
$types = mysqli_fetch_all($type_result, MYSQLI_ASSOC);

// รับ type_id จาก query string ถ้ามี
$type_id = isset($_GET['type_id']) ? (int)$_GET['type_id'] : 0;

// SQL แสดงอุปกรณ์พร้อม filter ประเภทถ้ามี
$sql = "SELECT 
            m.model_id,
            m.model_name, 
            b.brand_name, 
            mi.img_path, 
            t.type_name,
            COUNT(d.device_id) AS available 
        FROM model m 
        JOIN brand b ON m.brand_id = b.brand_id 
        LEFT JOIN type t ON t.type_id = m.type_id 
        LEFT JOIN model_img mi ON m.model_id = mi.model_id 
        LEFT JOIN device d ON m.model_id = d.model_id AND d.status = 'ว่าง' ";

if ($type_id > 0) {
    $sql .= "WHERE m.type_id = $type_id ";
}

$sql .= "GROUP BY m.model_id ORDER BY m.model_name ASC";

$result = mysqli_query($conn, $sql);
$devices = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechRent - เช่าอุปกรณ์ไอที คุณภาพสูง</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Custom gradient backgrounds */
        .hero-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.15);
        }
        
        .status-available {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .status-unavailable {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        .filter-section {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            backdrop-filter: blur(10px);
        }
        
        .device-image-container {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border: 2px solid transparent;
            background-clip: padding-box;
            position: relative;
        }
        
        .device-image-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: inherit;
            padding: 2px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask-composite: subtract;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .card-hover:hover .device-image-container::before {
            opacity: 1;
        }
        
        .brand-badge {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .type-badge {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-weight: 500;
            border: 1px solid rgba(102, 126, 234, 0.2);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen">

<!-- Navbar -->
<?php include('./components/navbar.php'); ?>

<!-- Hero Section -->
<div class="bg-gradient-to-br from-blue-600 to-purple-700 text-white py-16 mb-10">
    <div class="container mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">
            <i class="fas fa-laptop mr-3"></i>
            รายการอุปกรณ์ให้เช่า
        </h1>
        <p class="text-xl opacity-90 max-w-2xl mx-auto">
            เลือกอุปกรณ์ไอทีคุณภาพสูงสำหรับความต้องการของคุณ
        </p>
    </div>
</div>

<!-- Main Content -->
<div class="container mx-auto px-4 pb-16">
    
    <!-- Filter Section -->
    <div class="filter-section rounded-2xl p-6 mb-10 border border-white/20 shadow-lg">
        <form method="GET" class="max-w-md mx-auto">
            <label class="block mb-3 font-semibold text-gray-700 text-lg">
                <i class="fas fa-filter mr-2 text-purple-600"></i>
                กรองตามประเภทอุปกรณ์
            </label>
            <select name="type_id" class="select select-bordered w-full bg-white/80 backdrop-blur-sm border-2 border-purple-200 focus:border-purple-500 transition-all duration-300" onchange="this.form.submit()">
                <option value="0" <?= $type_id === 0 ? 'selected' : '' ?>>
                    <i class="fas fa-th-large"></i> แสดงทั้งหมด
                </option>
                <?php foreach ($types as $type): ?>
                    <option value="<?= $type['type_id'] ?>" <?= $type_id === (int)$type['type_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($type['type_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- Device Grid -->
    <div class="grid md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php foreach ($devices as $device): ?>
           <div class="card bg-white shadow-md hover:shadow-xl transition-shadow duration-300">
    <figure class="relative px-6 pt-6">
        <div class="w-full h-48 bg-gradient-to-br from-blue-100 to-purple-100 rounded-lg flex items-center justify-center relative overflow-hidden">
            <?php if (!empty($device['img_path'])): ?>
                <img src="<?= htmlspecialchars($device['img_path']) ?>" alt="<?= htmlspecialchars($device['model_name']) ?>" class="object-contain h-32 z-0">
            <?php else: ?>
                <span class="text-gray-500 z-0">ไม่มีรูป</span>
            <?php endif; ?>

            <!-- badge: ประเภท -->
            <div class="absolute top-2 left-2">
                <span class="badge badge-info badge-sm"><?= htmlspecialchars($device['type_name'] ?? 'ไม่ระบุ') ?></span>
            </div>

            <!-- badge: จำนวนว่าง -->
            <div class="absolute top-2 right-2">
                <span class="badge badge-success badge-sm">
                    ว่าง <?= (int)$device['available'] ?> เครื่อง
                </span>
            </div>
        </div>
    </figure>
    <div class="card-body items-center text-center">
        <h2 class="card-title text-lg font-semibold"><?= htmlspecialchars($device['model_name']) ?></h2>
        <p class="text-sm text-gray-600"><?= htmlspecialchars($device['brand_name']) ?></p>
        <div class="card-actions mt-4">
            <a href="device_detail.php?model_id=<?= $device['model_id'] ?>" class="btn btn-primary btn-sm">ดูรายละเอียด</a>
        </div>
    </div>
</div>

        <?php endforeach; ?>

        <?php if (count($devices) === 0): ?>
            <div class="col-span-full text-center py-16">
                <div class="max-w-md mx-auto">
                    <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-2xl font-bold text-gray-600 mb-2">ไม่พบอุปกรณ์</h3>
                    <p class="text-gray-500">ไม่มีอุปกรณ์ในหมวดหมู่นี้ในขณะนี้</p>
                    <a href="?" class="btn btn-primary mt-4 bg-gradient-to-r from-purple-600 to-blue-600 border-none text-white">
                        <i class="fas fa-refresh mr-2"></i>
                        ดูทั้งหมด
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Footer -->
<?php include('./components/footer.php'); ?>

</body>
</html>