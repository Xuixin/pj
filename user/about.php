<?php
// about.php - หน้าเกี่ยวกับเรา
session_start();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เกี่ยวกับเรา - TechRent</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <?php include('components/navbar.php'); ?>

    <!-- Hero Section -->
    <section class="hero min-h-96 bg-gradient-to-r from-blue-600 to-purple-700">
        <div class="hero-content text-center text-white">
            <div class="max-w-md">
                <h1 class="text-5xl font-bold mb-6">เกี่ยวกับเรา</h1>
                <p class="text-xl opacity-90">
                    ผู้นำด้านการให้บริการเช่าอุปกรณ์ไอทีคุณภาพสูง
                </p>
            </div>
        </div>
    </section>

    <!-- Company Story -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h2 class="text-4xl font-bold text-gray-800 mb-6">เรื่องราวของเรา</h2>
                    <p class="text-lg text-gray-600 mb-6 leading-relaxed">
                       AmiPro ก่อตั้งขึ้นในปี 2020 ด้วยวิสัยทัศน์ในการทำให้เทคโนโลยีที่ทันสมัยและมีคุณภาพสูง
                        เข้าถึงได้ง่ายสำหรับทุกคน ไม่ว่าจะเป็นธุรกิจขนาดเล็ก สตาร์ทอัพ หรือองค์กรขนาดใหญ่
                    </p>
                    <p class="text-lg text-gray-600 mb-6 leading-relaxed">
                        เราเชื่อว่าการเช่าอุปกรณ์ไอทีเป็นทางเลือกที่ชิงหนักสำหรับธุรกิจที่ต้องการความยืดหยุ่น
                        ประหยัดต้นทุน และเข้าถึงเทคโนโลยีล่าสุดโดยไม่ต้องลงทุนสูง
                    </p>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-primary">500+</div>
                            <div class="text-gray-600">ลูกค้าที่ไว้วางใจ</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-primary">1000+</div>
                            <div class="text-gray-600">อุปกรณ์ให้บริการ</div>
                        </div>
                    </div>
                </div>
                <div>
                    <img src="https://images.unsplash.com/photo-1560472354-b33ff0c44a43?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                         alt="Our Office" class="rounded-lg shadow-lg">
                </div>
            </div>
        </div>
    </section>

    <!-- Mission & Vision -->
    <section class="py-16 bg-gray-100">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-gray-800 mb-4">ภารกิจและวิสัยทัศน์</h2>
            </div>
            
            <div class="grid md:grid-cols-2 gap-8">
                <div class="card bg-white shadow-lg">
                    <div class="card-body">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center mr-4">
                                <i class="fas fa-bullseye text-white text-xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-800">ภารกิจ</h3>
                        </div>
                        <p class="text-gray-600 leading-relaxed">
                            มุ่งมั่นให้บริการเช่าอุปกรณ์ไอทีคุณภาพสูง พร้อมบริการดูแลและซ่อมบำรุงที่ครบถ้วน 
                            เพื่อช่วยให้ธุรกิจของลูกค้าสามารถเข้าถึงเทคโนโลยีที่ทันสมัยได้อย่างคุ้มค่า
                        </p>
                    </div>
                </div>
                
                <div class="card bg-white shadow-lg">
                    <div class="card-body">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center mr-4">
                                <i class="fas fa-eye text-white text-xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-800">วิสัยทัศน์</h3>
                        </div>
                        <p class="text-gray-600 leading-relaxed">
                            เป็นผู้นำด้านการให้บริการเช่าอุปกรณ์ไอทีในประเทศไทย พร้อมขยายบริการไปยังภูมิภาคเอเชียตะวันออกเฉียงใต้ 
                            ด้วยมาตรฐานการบริการที่เป็นเลิศ
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Values -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-gray-800 mb-4">ค่านิยมของเรา</h2>
                <p class="text-lg text-gray-600">หลักการที่เรายึดถือในการให้บริการ</p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-shield-alt text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">ความน่าเชื่อถือ</h3>
                    <p class="text-gray-600">
                        เราให้ความสำคัญกับการรักษาคำมั่นสัญญาและสร้างความไว้วางใจกับลูกค้าทุกท่าน
                    </p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-award text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">คุณภาพเป็นเลิศ</h3>
                    <p class="text-gray-600">
                        อุปกรณ์ทุกชิ้นได้รับการตรวจสอบอย่างละเอียดเพื่อให้มั่นใจในคุณภาพสูงสุด
                    </p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-users text-purple-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">ลูกค้าเป็นศูนย์กลาง</h3>
                    <p class="text-gray-600">
                        เราพัฒนาบริการและแก้ไขปัญหาโดยใส่ใจความต้องการของลูกค้าเป็นอันดับแรก
                    </p>
                </div>
            </div>
        </div>
    </section>

  

    <!-- Contact CTA -->
    <section class="py-16 bg-gradient-to-r from-blue-600 to-purple-700">
        <div class="container mx-auto px-4 text-center">
            <div class="text-white">
                <h2 class="text-4xl font-bold mb-4">พร้อมให้บริการคุณ</h2>
                <p class="text-xl opacity-90 mb-8">
                    ติดต่อเราวันนี้เพื่อปรึกษาความต้องการและรับคำแนะนำที่เหมาะสม
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="contact.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-phone mr-2"></i>
                        ติดต่อเรา
                    </a>
                    <a href="products.php" class="btn btn-outline btn-lg text-white border-white hover:bg-white hover:text-blue-600">
                        <i class="fas fa-laptop mr-2"></i>
                        ดูสินค้า
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include('components/footer.php'); ?>


</body>
</html>