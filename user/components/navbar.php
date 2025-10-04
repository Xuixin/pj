<?php
// 
$current_file_name = basename($_SERVER['PHP_SELF']);




?>
<nav class="navbar bg-white shadow-lg sticky top-0 z-50">
    <div class="container flex mx-auto">
        <div class="navbar-start">
            <div class="dropdown">
                <div tabindex="0" role="button" class="btn btn-ghost lg:hidden">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16"></path>
                    </svg>
                </div>
                <ul tabindex="0" class="menu menu-sm dropdown-content bg-white mt-3 z-[1] p-2 shadow rounded-box w-52">
                    <li><a href="home.php">หน้าแรก</a></li>
                    <li><a href="devices.php">อุปกรณ์</a></li>
                    <li><a href="about.php">เกี่ยวกับเรา</a></li>
                    <li><a href="contact.php">ติดต่อเรา</a></li>
                </ul>
            </div>
            <a href="home.php" class="text-xl font-bold text-primary">
                <i class="fas fa-laptop-code mr-2"></i>AmiPro
            </a>
        </div>

        <div class="navbar-center hidden lg:flex">
            <ul class="menu menu-horizontal px-1">
                <li>
                    <a href="home.php"
                        class="<?= $current_file_name === 'home.php' ? 'text-primary font-bold' : 'hover:text-primary' ?>">
                        หน้าแรก
                    </a>
                </li>
                <li>
                    <a href="devices.php"
                        class="<?= $current_file_name === 'devices.php' ? 'text-primary font-bold' : 'hover:text-primary' ?>">
                        อุปกรณ์
                    </a>
                </li>
                <li>
                    <a href="about.php"
                        class="<?= $current_file_name === 'about.php' ? 'text-primary font-bold' : 'hover:text-primary' ?>">
                        เกี่ยวกับเรา
                    </a>
                </li>
                <li>
                    <a href="contact.php"
                        class="<?= $current_file_name === 'contact.php' ? 'text-primary font-bold' : 'hover:text-primary' ?>">
                        ติดต่อเรา
                    </a>
                </li>
            </ul>
        </div>

        <div class="navbar-end">
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="dropdown dropdown-end">
                   
                    <div class="avatar" tabindex="0" role="button">
                        <div class="w-10 rounded-full">
                            <img src="https://img.daisyui.com/images/profile/demo/yellingcat@192.webp" />
                        </div>
                    </div>
                    <ul tabindex="0" class="menu menu-sm dropdown-content bg-white mt-3 z-[1] p-2 shadow rounded-box w-52">
                        <li><span class="text-gray-600">สวัสดี, <?= htmlspecialchars($_SESSION['user_name']) ?></span></li>
                        <li><a href="my_contracts.php"><i class="fas fa-file-contract mr-2"></i>สัญญาของฉัน</a></li>
             
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt mr-2"></i>ออกจากระบบ</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <div class="flex gap-2">
                    <button onclick="login_modal.showModal()" class="btn btn-primary"> เข้าสู่ระบบ</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<dialog id="login_modal" class="modal" data-aos="zoom-out-down">
    <div class="modal-box max-w-[24rem] absolute top-20 right-4">
        <h3 class="text-lg font-bold mb-4">เข้าสู่ระบบ</h3>

        <?php if (isset($_SESSION['login_error'])): ?>
            <p class="text-red-500 mb-2"><?= $_SESSION['login_error'] ?></p>
            <?php unset($_SESSION['login_error']); ?>
        <?php endif; ?>

        <form method="POST" action="login_db.php" class="space-y-4">
            <div>
                <label class="label"><span class="label-text">ชื่อผู้ใช้</span></label>
                <input type="text" name="user_name" required class="input input-bordered w-full">
            </div>
            <div>
                <label class="label"><span class="label-text">เบอร์โทรศัพท์</span></label>
                <input type="text" name="phone" required class="input input-bordered w-full">
            </div>
            <div class="modal-action justify-between">
                <button type="submit" class="btn btn-primary">เข้าสู่ระบบ</button>
                <form method="dialog" >
                    <button class="btn" type="button">ปิด</button>
                </form>
            </div>
        </form>
    </div>
</dialog>