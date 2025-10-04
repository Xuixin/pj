<?php
// index.php - หน้าแรก
session_start();
require_once('./../conn.php');

// ดึงข้อมูลอุปกรณ์ที่ยังว่างให้เช่า (featured items)
$sql_featured = "SELECT d.*, m.model_name, t.type_name, t.type_id, MIN(img.img_path) AS img_path 
                    FROM device d 
                    LEFT JOIN model m ON m.model_id = d.model_id 
                    LEFT JOIN type t ON t.type_id = m.type_id 
                    LEFT JOIN model_img img ON img.model_id = m.model_id 
                    WHERE d.status = 'ว่าง' 
                    GROUP BY d.device_id 
                    ORDER BY d.device_id DESC 
                    LIMIT 6";
$featured_result = mysqli_query($conn, $sql_featured);
$featured_devices = [];
if ($featured_result) {
    while ($row = mysqli_fetch_assoc($featured_result)) {
        $featured_devices[] = $row;
    }
}

// ดึงข้อมูลประเภทอุปกรณ์
$sql_types = "SELECT * FROM type ORDER BY type_name LIMIT 8";
$types_result = mysqli_query($conn, $sql_types);
$types = [];
if ($types_result) {
    while ($row = mysqli_fetch_assoc($types_result)) {
        $types[] = $row;
    }
}
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
    <!-- AOS CSS -->
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">

</head>

<body class="bg-gray-50">
    <!-- Navigation -->
    <?php include('./components/navbar.php'); ?>

    <!-- Hero Section -->
    <section id="home" class="hero min-h-screen bg-gradient-to-br from-blue-600 to-purple-700">
        <div class="hero-content flex-col lg:flex-row-reverse px-4 py-8 lg:py-0">
            <div class="flex-1 w-full max-w-md lg:max-w-lg">
                <img src="https://images.unsplash.com/photo-1531297484001-80022131f5a1?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                    class="w-full max-w-xs sm:max-w-sm lg:max-w-md rounded-lg shadow-2xl mx-auto" alt="Technology Equipment">
            </div>
            <div class="flex-1 text-white text-center lg:text-left mt-8 lg:mt-0">
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-4 lg:mb-6">เช่าอุปกรณ์ไอที<br>คุณภาพสูง</h1>
                <p class="text-base sm:text-lg lg:text-xl mb-6 lg:mb-8 opacity-90 px-4 lg:px-0">
                    บริการเช่าอุปกรณ์เทคโนโลยี คอมพิวเตอร์ โน้ตบุ๊ค และอุปกรณ์ไอทีชั้นนำ
                    พร้อมบริการดูแลและซ่อมบำรุง
                </p>
                <div class="flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4 justify-center lg:justify-start px-4 lg:px-0">
                    <a href="devices.php" class="btn btn-primary btn-md lg:btn-lg w-full sm:w-auto">
                        <i class="fas fa-laptop mr-2"></i>ดูอุปกรณ์ทั้งหมด
                    </a>
                    <a href="contact.php" class="btn btn-outline btn-md lg:btn-lg border-white text-white hover:bg-white hover:text-primary w-full sm:w-auto">
                        <i class="fas fa-phone mr-2"></i>ติดต่อเรา
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Promotion Banner -->
    <section class="py-12 sm:py-16 bg-gradient-to-r from-green-400 to-blue-500">
        <div class="container mx-auto px-4">
            <div class="text-center text-white">
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold mb-3 sm:mb-4">
                    <i class="fas fa-gift mr-2 sm:mr-3"></i>โปรโมชั่นพิเศษ!
                </h2>
                <p class="text-base sm:text-lg lg:text-xl mb-4 sm:mb-6">สำหรับลูกค้าใหม่ที่เช่าครั้งแรก</p>
                <div class="bg-white/20 backdrop-blur-sm rounded-xl sm:rounded-2xl p-4 sm:p-6 lg:p-8 max-w-4xl mx-auto">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
                        <div class="text-center">
                            <div class="w-12 h-12 sm:w-16 sm:h-16 bg-white/30 rounded-full flex items-center justify-center mx-auto mb-3 sm:mb-4">
                                <i class="fas fa-percent text-lg sm:text-2xl"></i>
                            </div>
                            <h3 class="text-lg sm:text-xl lg:text-2xl font-bold mb-1 sm:mb-2">ลด 5%</h3>
                            <p class="text-sm sm:text-base">สำหรับการเช่าครั้งแรก</p>
                        </div>
                        <div class="text-center">
                            <div class="w-12 h-12 sm:w-16 sm:h-16 bg-white/30 rounded-full flex items-center justify-center mx-auto mb-3 sm:mb-4">
                                <i class="fas fa-shipping-fast text-lg sm:text-2xl"></i>
                            </div>
                            <h3 class="text-lg sm:text-xl lg:text-2xl font-bold mb-1 sm:mb-2">ส่งฟรี</h3>
                            <p class="text-sm sm:text-base">ส่งและรับอุปกรณ์ถึงที่</p>
                        </div>
                        <div class="text-center">
                            <div class="w-12 h-12 sm:w-16 sm:h-16 bg-white/30 rounded-full flex items-center justify-center mx-auto mb-3 sm:mb-4">
                                <i class="fas fa-tools text-lg sm:text-2xl"></i>
                            </div>
                            <h3 class="text-lg sm:text-xl lg:text-2xl font-bold mb-1 sm:mb-2">ดูแลฟรี</h3>
                            <p class="text-sm sm:text-base">บริการดูแลและซ่อมบำรุง</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Devices -->
    <section class="py-12 sm:py-16 bg-white" data-aos="fade-up">
        <div class="container mx-auto px-4">
            <div class="text-center mb-8 sm:mb-12">
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-800 mb-3 sm:mb-4">อุปกรณ์แนะนำ</h2>
                <p class="text-base sm:text-lg lg:text-xl text-gray-600">อุปกรณ์คุณภาพสูงที่พร้อมให้บริการ</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
                <?php foreach ($featured_devices as $device): ?>
                    <div class="card bg-white shadow-lg hover:shadow-xl transition-shadow duration-300">
                        <figure class="px-6 pt-6">
                            <div class="w-full h-48 bg-gradient-to-br from-blue-100 to-purple-100 rounded-lg flex items-center justify-center">
                                <img src="<?= $device['img_path'] ?>" alt="<?= $device['device_id'] ?>" class="max-h-full max-w-full object-contain" />

                            </div>
                        </figure>
                        <div class="card-body">
                            <h3 class="card-title text-lg">
                                <?= htmlspecialchars($device['model_name']) ?>
                                <div class="badge badge-secondary"><?= htmlspecialchars($device['type_name']) ?></div>
                            </h3>
                            <p class="text-gray-600">
                                <i class="fas fa-barcode mr-2"></i>
                                <?= htmlspecialchars($device['serial_number']) ?>
                            </p>
                            <div class="card-actions justify-end mt-4">
                                <span class="badge badge-success">พร้อมให้เช่า</span>
                                <a href="devices.php?filter=<?= $device['type_id'] ?>" class="btn btn-primary btn-sm">
                                    ดูรายละเอียด
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center mt-8 sm:mt-12">
                <a href="devices.php" class="btn btn-primary btn-md lg:btn-lg">
                    <i class="fas fa-arrow-right mr-2"></i>ดูอุปกรณ์ทั้งหมด
                </a>
            </div>
        </div>
    </section>

    <!-- Device Categories -->
    <section class="py-12 sm:py-16 bg-gray-100" data-aos="fade-up">
        <div class="container mx-auto px-4">
            <div class="text-center mb-8 sm:mb-12">
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-800 mb-3 sm:mb-4">หมวดหมู่อุปกรณ์</h2>
                <p class="text-base sm:text-lg lg:text-xl text-gray-600">เลือกประเภทอุปกรณ์ที่คุณต้องการ</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-6">
                <?php foreach ($types as $type): ?>
                    <a href="devices.php?type=<?= $type['type_id'] ?>"
                        class="card bg-white shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105">
                        <div class="card-body text-center">
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-laptop text-white text-2xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($type['type_name']) ?></h3>
                            <p class="text-gray-600">ดูอุปกรณ์ทั้งหมด</p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Why Choose Us -->
    <section class="py-12 sm:py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-8 sm:mb-12">
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-800 mb-3 sm:mb-4">ทำไมต้องเลือกเรา</h2>
                <p class="text-base sm:text-lg lg:text-xl text-gray-600">เหตุผลที่ลูกค้าไว้วางใจเรา</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8">
                <div class="text-center">
                    <div class="w-20 h-20 bg-gradient-to-br from-green-400 to-blue-500 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-award text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">คุณภาพสูง</h3>
                    <p class="text-gray-600">อุปกรณ์ทุกชิ้นผ่านการตรวจสอบคุณภาพ</p>
                </div>

                <div class="text-center">
                    <div class="w-20 h-20 bg-gradient-to-br from-purple-400 to-pink-500 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-clock text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">บริการรวดเร็ว</h3>
                    <p class="text-gray-600">ส่งมอบอุปกรณ์ภายใน 24 ชั่วโมง</p>
                </div>

                <div class="text-center">
                    <div class="w-20 h-20 bg-gradient-to-br from-orange-400 to-red-500 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-headset text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">ซัพพอร์ต 24/7</h3>
                    <p class="text-gray-600">ทีมงานพร้อมช่วยเหลือตลอด 24 ชั่วโมง</p>
                </div>

                <div class="text-center">
                    <div class="w-20 h-20 bg-gradient-to-br from-teal-400 to-cyan-500 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-shield-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">ประกันครบถ้วน</h3>
                    <p class="text-gray-600">ประกันอุปกรณ์และบริการซ่อมบำรุง</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-12 sm:py-16 bg-gradient-to-r from-blue-600 to-purple-700">
        <div class="container mx-auto px-4 text-center text-white">
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold mb-4 sm:mb-6">พร้อมเริ่มต้นเช่าอุปกรณ์แล้วใช่ไหม?</h2>
            <p class="text-base sm:text-lg lg:text-xl mb-6 sm:mb-8 opacity-90 px-4">
                เริ่มต้นการเช่าอุปกรณ์ไอทีคุณภาพสูงได้ตั้งแต่วันนี้
            </p>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="devices.php" class="btn btn-primary btn-md lg:btn-lg">
                    <i class="fas fa-laptop mr-2"></i>เลือกอุปกรณ์เลย
                </a>
            <?php else: ?>
                <div class="flex flex-col sm:flex-row justify-center gap-3 sm:gap-4 px-4">
                    <a href="register.php" class="btn btn-primary btn-md lg:btn-lg w-full sm:w-auto">
                        <i class="fas fa-user-plus mr-2"></i>สมัครสมาชิก
                    </a>
                    <a href="devices.php" class="btn btn-outline btn-md lg:btn-lg border-white text-white hover:bg-white hover:text-primary w-full sm:w-auto">
                        <i class="fas fa-eye mr-2"></i>ดูอุปกรณ์ก่อน
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <?php include('components/footer.php'); ?>

    <!-- AOS JS -->
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>

</body>

</html>