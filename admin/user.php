<?php
session_start();
require_once('./../conn.php');
require_once('./../lib/format_date.php');

$isAdmin = $_SESSION['role'] === 'admin' ? true : false;


$sql = "SELECT * FROM `user`";
$user = mysqli_query($conn, $sql);


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>user Management</title>
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
    </style>
</head>

<body class="bg-gradient-to-br from-slate-100 to-slate-300 ">


    <div class="grid grid-cols-5 grid-rows-1 h-screen gap-2 row-h   min-h-screen sticky">
        <!-- Sidebar -->
        <div class="">
            <div class="rounded-2xl inline-block  h-full">
                <?php
                include('admin_component/sidebar.php')
                ?>
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
                        <h1 class="text-lg font-semibold text-gray-800">ยินดีต้อนรับ, <?php echo $_SESSION['admin_name'] ?></h1>
                        <p class="text-sm text-gray-500">จัดการข้อมูลผู้เช่า</p>

                    </div>
                </div>
               
            </div>





            <!-- Employee Table -->
            <div class="col-span-5  row-span-7 row-start-2 bg-white flex flex-col  rounded-2xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold text-gray-800">
                        <i class="fas fa-table mr-2 text-blue-500"></i>
                        รายการผู้เช่า
                    </h3>
                    <div class="flex items-center space-x-3">
                        <button class="btn btn-primary btn-md gradient-bg border-0 hover-scale shadow-lg"
                            onclick="document.getElementById('add_user_modal').showModal()">
                            <i class="fas fa-plus mr-2"></i>
                            เพิ่มผู้ใช้ใหม่
                        </button>
                        <div class="relative">
                            <input type="text" placeholder="ค้นหาผู้เช่า..." class="input input-bordered w-64 pl-10"
                                id="search_input">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                        <button class="btn btn-outline btn-sm" onclick="loadEmployees()">
                            <i class="fas fa-refresh mr-1"></i>
                            รีเฟรช
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="text-left">
                                    <i class="fas fa-hashtag mr-2"></i>
                                </th>
                                <th class="text-left">
                                    <i class="fas fa-user mr-2 "></i>
                                    ชื่อผู้ใช้
                                </th>
                                <th class="text-left">
                                    <i class="fas fa-phone-alt mr-2 "></i>
                                    เบอร์โทรศัพท์
                                </th>
                                <th class="text-left">
                                    <i class="fas fa-map-marker-alt mr-2 "></i>
                                    ที่อยู่
                                </th>
                                <th class="text-left">
                                    <i class="fas fa-calendar-alt mr-2 "></i>
                                    วันที่สร้าง
                                </th>
<?php if($isAdmin): ?>

                                <th class="text-center">
                                    <i class="fas fa-cogs mr-2 text-gray-700"></i>
                                    จัดการ
                                </th>
<?php endif ?>
                            </tr>
                        </thead>
                        <tbody id="user_table_body">
                            <?php
                            if (mysqli_num_rows($user) > 0) {

                                $i = 1;
                                foreach ($user as $row) {
                            ?>
                                    <tr class="border-b">
                                        <td class="px-4 py-2 "><?php echo $i; ?></td>
                                        <td class="px-4 py-2 text-left"><?php echo $row['user_name']; ?></td>
                                        <td class="px-4 py-2 text-left"><?php echo $row['phone']; ?></td>
                                        <td class="px-4 py-2 text-left"><?php echo $row['location']; ?></td>
                                        <td class="px-4 py-2 text-left">
                                            <?php
                                            echo formatThaiShortDateTime($row['create_At']);
                                            ?>
                                        </td>
<?php if($isAdmin): ?>
                                        <td class="flex space-x-2 justify-center">
                                            <button class="px-4 py-2 rounded-lg bg-yellow-400 text-white hover:bg-yellow-500 transition"
                                                onclick="
                                                    openEditModal(
                                                        '<?= $row['user_id'] ?>',
                                                        '<?= $row['user_name'] ?>',
                                                        '<?= $row['phone'] ?>',
                                                        '<?= $row['location'] ?>'
                                                    )
                                                ">
                                                แก้ไข
                                            </button>


                                            <!-- ปุ่มลบ -->
                                            <form action="user_db.php" method="post">
                                                <input type="hidden" name="_method" value="delete">
                                                <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                                <button type="submit" class="px-4 py-2 rounded-lg bg-red-500 text-white hover:bg-red-600 transition">
                                                    ลบ
                                                </button>
                                            </form>
                                        </td>
 <?php endif ?>
                                    </tr>
                                <?php
                                    $i++;
                                }
                            } else {
                                ?>
                                <tr>
                                    <td colspan="4" class="text-center text-gray-500 py-4">ไม่มีข้อมูล</td>
                                </tr>
                            <?php
                            }
                            ?>

                        </tbody>
                    </table>
                </div>

             

            </div>
        </div>
    </div>

    <!-- Add user Modal -->
    <dialog id="add_user_modal" class="modal">

        <div class="modal-box w-11/12 max-w-2xl">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-user-plus text-white text-lg"></i>
                </div>
                <div class="ml-4">
                    <h3 class="font-bold text-2xl text-gray-800">เพิ่มผู้ใช้ใหม่</h3>
                    <p class="text-gray-600">กรอกข้อมูลผู้ใช้งานใหม่ในระบบ</p>
                </div>
            </div>

            <form id="add_user_form" method="post" action="user_db.php" class="space-y-6 ">
                <input type="hidden" name="_method" value="POST">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">
                                <i class="fas fa-user mr-2 text-blue-500"></i>
                                ชื่อผู้ใช้
                            </span>
                        </label>
                        <input type="text" name="user_name" placeholder="กรอกชื่อผู้ใช้"
                            class="input input-bordered w-full focus:border-blue-500" required>
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">
                                <i class="fas fa-phone mr-2 text-blue-500"></i>
                                เบอร์โทรศัพท์
                            </span>
                        </label>
                        <input type="text" name="phone" placeholder="กรอกเบอร์โทรศัพท์"
                            class="input input-bordered w-full focus:border-blue-500" required>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-2 gap-6 w-full">
                    <div class="form-control col-span-2">
                        <label class="label">
                            <span class="label-text font-semibold">
                                <i class="fas fa-map-marker-alt mr-2 text-blue-500"></i>
                                ที่อยู่
                            </span>
                        </label>
                        <textarea name="localtion" placeholder="กรอกที่อยู่"
                            class="textarea textarea-bordered w-full focus:border-blue-500 resize-none"
                            rows="3" required></textarea>
                    </div>
                </div>



                <div class="modal-action pt-6">
                    <button type="button" class="btn btn-ghost"
                        onclick="document.getElementById('add_user_modal').close()">
                        <i class="fas fa-times mr-2"></i>
                        ยกเลิก
                    </button>
                    <button type="submit" class="btn btn-primary gradient-bg border-0">
                        <i class="fas fa-save mr-2"></i>
                        บันทึกข้อมูล
                    </button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- edit -->
    <dialog id="edit_user_modal" class="modal">
        <div class="modal-box w-11/12 max-w-2xl">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 bg-gradient-to-r from-yellow-400 to-red-500 rounded-lg flex items-center justify-center">
                    <i class="fas fa-user-edit text-white text-lg"></i>
                </div>
                <div class="ml-4">
                    <h3 class="font-bold text-2xl text-gray-800">แก้ไขข้อมูลผู้ใช้</h3>
                    <p class="text-gray-600">ปรับปรุงข้อมูลผู้ใช้งานในระบบ</p>
                </div>
            </div>

            <form id="edit_user_form" method="post" action="user_db.php" class="space-y-6">
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" name="user_id" id="edit_user_id"> <!-- ซ่อน user_id -->

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">
                                <i class="fas fa-user mr-2 text-yellow-500"></i>
                                ชื่อผู้ใช้
                            </span>
                        </label>
                        <input type="text" name="user_name" id="edit_user_name" class="input input-bordered w-full focus:border-yellow-500" required>
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">
                                <i class="fas fa-phone mr-2 text-yellow-500"></i>
                                เบอร์โทรศัพท์
                            </span>
                        </label>
                        <input type="text" name="phone" id="edit_phone" class="input input-bordered w-full focus:border-yellow-500" required>
                    </div>
                </div>

                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">
                            <i class="fas fa-map-marker-alt mr-2 text-yellow-500"></i>
                            ที่อยู่
                        </span>
                    </label>
                    <textarea name="location" id="edit_location" class="textarea textarea-bordered w-full focus:border-yellow-500 resize-none" rows="3" required></textarea>
                </div>

                <div class="modal-action pt-6">
                    <button type="button" class="btn btn-ghost"
                        onclick="document.getElementById('edit_user_modal').close()">
                        <i class="fas fa-times mr-2"></i>
                        ยกเลิก
                    </button>
                    <button type="submit" class="btn btn-warning text-white border-0">
                        <i class="fas fa-save mr-2"></i>
                        บันทึกการแก้ไข
                    </button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>




    <?php
    include('./../lib/toast.php');
    ?>




    <script src="./../scripts/main.js"></script>

    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password_input');
            const toggleIcon = document.getElementById('password_toggle_icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }



        function openEditModal(id, name, phone, location) {
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_user_name').value = name;
            document.getElementById('edit_phone').value = phone;
            document.getElementById('edit_location').value = location;
            document.getElementById('edit_user_modal').showModal();
        }


        // Search functionality
        document.getElementById('search_input').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#user_table_body tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });


        function loadEmployees() {
            location.reload()
        }
    </script>
</body>

</html>