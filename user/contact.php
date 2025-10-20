<?php
// contact.php - หน้าติดต่อเรา
session_start();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ติดต่อเรา - TechRent</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .contact-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .contact-card {
            transition: all 0.3s ease;
        }

        .contact-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.2);
        }

        .form-input {
            transition: all 0.3s ease;
        }

        .form-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .map-container {
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen">
    <!-- Navigation -->
    <?php include('components/navbar.php'); ?>

    <!-- Hero Section -->
    <section class="bg-gradient-to-br from-blue-600 to-purple-700 text-white py-20">
        <div class="container mx-auto px-4 text-center">
            <div class="max-w-3xl mx-auto">
                <h1 class="text-5xl md:text-6xl font-bold mb-6">
                    <i class="fas fa-phone-alt mr-3"></i>
                    ติดต่อเรา
                </h1>
                <p class="text-xl md:text-2xl opacity-90 leading-relaxed">
                    พร้อมให้คำปรึกษาและตอบข้อสงสัยเกี่ยวกับการเช่าอุปกรณ์ไอที
                </p>
                <div class="flex justify-center mt-8">
                    <div class="flex space-x-6">
                        <div class="text-center">
                            <div class="text-3xl font-bold">24/7</div>
                            <div class="text-sm opacity-80">บริการตลอด</div>
                        </div>
                        <div class="w-px bg-white/30"></div>
                        <div class="text-center">
                            <div class="text-3xl font-bold">15 นาที</div>
                            <div class="text-sm opacity-80">ตอบกลับเร็ว</div>
                        </div>
                        <div class="w-px bg-white/30"></div>
                        <div class="text-center">
                            <div class="text-3xl font-bold">100%</div>
                            <div class="text-sm opacity-80">ความพึงพอใจ</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Information Cards -->
    <section class="py-16 -mt-10 relative z-10">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-3 gap-8">
                <div class="contact-card card bg-white shadow-xl">
                    <div class="card-body text-center">
                        <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-phone text-white text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">โทรศัพท์</h3>
                        <p class="text-gray-600 mb-4">ติดต่อเราได้ตลอด 24 ชั่วโมง</p>
                        <a href="tel:02-123-4567" class="text-blue-600 font-semibold hover:text-blue-800 transition-colors">
                            02-123-4567
                        </a>
                        <br>
                        <a href="tel:089-123-4567" class="text-blue-600 font-semibold hover:text-blue-800 transition-colors">
                            089-123-4567
                        </a>
                    </div>
                </div>

                <div class="contact-card card bg-white shadow-xl">
                    <div class="card-body text-center">
                        <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-teal-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-envelope text-white text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">อีเมล</h3>
                        <p class="text-gray-600 mb-4">ส่งข้อความหาเราได้ทุกเวลา</p>
                        <a href="mailto:info@techrent.co.th" class="text-green-600 font-semibold hover:text-green-800 transition-colors">
                            info@techrent.co.th
                        </a>
                        <br>
                        <a href="mailto:support@techrent.co.th" class="text-green-600 font-semibold hover:text-green-800 transition-colors">
                            support@techrent.co.th
                        </a>
                    </div>
                </div>

                <div class="contact-card card bg-white shadow-xl">
                    <div class="card-body text-center">
                        <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-map-marker-alt text-white text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">ที่อยู่</h3>
                        <p class="text-gray-600 mb-4">มาเยี่ยมชมเราได้</p>
                        <address class="text-purple-600 font-semibold not-italic leading-relaxed">
                            652/8 ถ.เพชรเกษม ตำบล คอหงส์<br> อำเภอหาดใหญ่ สงขลา 90110
                        </address>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Form & Map -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="grid lg:grid-cols-2 gap-12 items-start">
                <!-- Contact Form -->
                <div>
                    <div class="mb-8">
                        <h2 class="text-4xl font-bold text-gray-800 mb-4">ส่งข้อความหาเรา</h2>
                        <p class="text-lg text-gray-600">
                            กรอกข้อมูลด้านล่าง เราจะติดต่อกลับโดยเร็วที่สุด
                        </p>
                    </div>

                    <form class="space-y-6" action="process_contact.php" method="POST">
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    ชื่อ-นามสกุล *
                                </label>
                                <input type="text" name="full_name" required
                                    class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none"
                                    placeholder="กรุณากรอกชื่อ-นามสกุล">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    เบอร์โทรศัพท์ *
                                </label>
                                <input type="tel" name="phone" required
                                    class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none"
                                    placeholder="08X-XXX-XXXX">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                อีเมล *
                            </label>
                            <input type="email" name="email" required
                                class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none"
                                placeholder="your@email.com">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                ประเภทการติดต่อ
                            </label>
                            <select name="contact_type"
                                class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none">
                                <option value="">-- เลือกประเภท --</option>
                                <option value="rental">สอบถามการเช่า</option>
                                <option value="support">ขอความช่วยเหลือ</option>
                                <option value="partnership">ความร่วมมือทางธุรกิจ</option>
                                <option value="complaint">ร้องเรียน/แจ้งปัญหา</option>
                                <option value="other">อื่นๆ</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                ข้อความ *
                            </label>
                            <textarea name="message" rows="6" required
                                class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none resize-none"
                                placeholder="กรุณาระบุรายละเอียดที่ต้องการสอบถาม..."></textarea>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" id="consent" name="consent" required
                                class="checkbox checkbox-primary mr-3">
                            <label for="consent" class="text-sm text-gray-600">
                                ยินยอมให้เก็บรวบรวมและใช้ข้อมูลส่วนบุคคลเพื่อติดต่อกลับ
                            </label>
                        </div>

                        <button type="submit"
                            class="btn w-full bg-gradient-to-r from-blue-600 to-purple-700 border-none text-white text-lg py-4 hover:from-blue-700 hover:to-purple-800 transition-all duration-300">
                            <i class="fas fa-paper-plane mr-2"></i>
                            ส่งข้อความ
                        </button>
                    </form>
                </div>

                <!-- Map & Additional Info -->
                <div>
                    <div class="mb-8">
                        <h2 class="text-4xl font-bold text-gray-800 mb-4">ที่ตั้งของเรา</h2>
              
                    </div>

                    <!-- Map Placeholder -->
                    <div class="map-container mb-8">
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3959.923516834469!2d100.48296457516949!3d7.018276392983321!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x304d29aabd5fa805%3A0x1e29fb3bbfd9d704!2z4Lia4Lij4Li04Lip4Lix4LiXIOC5gOC4reC4oeC4tC7guYLguJvguKMu4LiI4Liz4LiB4Lix4LiUIEFtaS4gUHJvLiBDb3JwLiwgTHRkLg!5e0!3m2!1sth!2sth!4v1750849078028!5m2!1sth!2sth"
                            width="100%"
                            height="400"
                            style="border:0;"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            class="w-full h-[400px] rounded-xl shadow"></iframe>
                    </div>


                    <!-- Business Hours -->
                    <div class="card bg-gradient-to-br from-blue-50 to-purple-50 border border-blue-100">
                        <div class="card-body">
                            <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-clock mr-2 text-blue-600"></i>
                                เวลาทำการ
                            </h3>
                            <div class="space-y-2 text-gray-700">
                                <div class="flex justify-between">
                                    <span>จันทร์ - ศุกร์</span>
                                    <span class="font-semibold">09:00 - 18:00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>เสาร์</span>
                                    <span class="font-semibold">09:00 - 16:00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>อาทิตย์ & วันหยุดนักขัตฤกษ์</span>
                                    <span class="font-semibold text-red-600">ปิด</span>
                                </div>
                            </div>
                            <div class="divider"></div>
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-info-circle mr-1 text-blue-500"></i>
                                บริการฉุกเฉิน 24/7 ผ่านโทรศัพท์และอีเมล
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-gray-800 mb-4">คำถามที่พบบ่อย</h2>
                <p class="text-lg text-gray-600">คำตอบสำหรับคำถามที่ลูกค้าสอบถามบ่อยที่สุด</p>
            </div>

            <div class="max-w-4xl mx-auto">
                <div class="join join-vertical w-full">
                    <div class="collapse collapse-arrow join-item border border-base-300">
                        <input type="radio" name="faq-accordion" checked="checked" />
                        <div class="collapse-title text-xl font-medium">
                            การเช่าอุปกรณ์ต้องใช้เอกสารอะไรบ้าง?
                        </div>
                        <div class="collapse-content">
                            <p class="text-gray-600">ต้องใช้บัตรประชาชน หนังสือรับรองบริษัท (สำหรับนิติบุคคล) และหลักฐานการชำระเงิน พร้อมวางเงินประกัน</p>
                        </div>
                    </div>

                    <div class="collapse collapse-arrow join-item border border-base-300">
                        <input type="radio" name="faq-accordion" />
                        <div class="collapse-title text-xl font-medium">
                            ระยะเวลาการเช่าขั้นต่ำเท่าไหร่?
                        </div>
                        <div class="collapse-content">
                            <p class="text-gray-600">การเช่าขั้นต่ำ 1 เดือน สำหรับการเช่าระยะสั้น (รายวัน/รายสัปดาห์) สามารถสอบถามเพิ่มเติมได้</p>
                        </div>
                    </div>

                    <div class="collapse collapse-arrow join-item border border-base-300">
                        <input type="radio" name="faq-accordion" />
                        <div class="collapse-title text-xl font-medium">
                            มีบริการจัดส่งและติดตั้งหรือไม่?
                        </div>
                        <div class="collapse-content">
                            <p class="text-gray-600">มีบริการจัดส่งฟรีในเขตกรุงเทพฯ และปริมณฑล พร้อมบริการติดตั้งและตั้งค่าเบื้องต้น</p>
                        </div>
                    </div>

                    <div class="collapse collapse-arrow join-item border border-base-300">
                        <input type="radio" name="faq-accordion" />
                        <div class="collapse-title text-xl font-medium">
                            หากอุปกรณ์เสียระหว่างการเช่าจะเป็นอย่างไร?
                        </div>
                        <div class="collapse-content">
                            <p class="text-gray-600">เรามีบริการซ่อมแซมและเปลี่ยนอุปกรณ์ทดแทนภายใน 24 ชั่วโมง (กรณีการใช้งานปกติ)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 contact-gradient">
        <div class="container mx-auto px-4 text-center">
            <div class="text-white max-w-3xl mx-auto">
                <h2 class="text-4xl font-bold mb-4">ยังมีคำถาม?</h2>
                <p class="text-xl opacity-90 mb-8">
                    ทีมงานของเราพร้อมให้คำปรึกษาและตอบข้อสงสัยทุกเรื่อง
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="tel:02-123-4567" class="btn btn-primary btn-lg bg-white text-blue-600 border-white hover:bg-gray-100">
                        <i class="fas fa-phone mr-2"></i>
                        โทรเลย 02-123-4567
                    </a>
                    <a href="https://line.me/ti/p/techrent" class="btn btn-outline btn-lg text-white border-white hover:bg-white hover:text-blue-600">
                        <i class="fab fa-line mr-2"></i>
                        LINE: @TechRent
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include('components/footer.php'); ?>
</body>

</html>