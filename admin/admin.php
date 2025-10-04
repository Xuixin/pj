<?php
session_start();

require_once('./../conn.php');
require_once('./../lib/format_date.php');


$isAdmin = $_SESSION['role'] === 'admin' ? true : false;


$sql = "SELECT * FROM admin WHERE role = 'employee' ";
$admin = mysqli_query($conn, $sql);


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
                        <h1 class="text-lg font-semibold text-gray-800">ยินดีต้อนรับ,
                            <?php echo $_SESSION['admin_name'] ?>
                        </h1>
                        <p class="text-sm text-gray-500">จัดการข้อมูลพนักงาน</p>

                    </div>
                </div>

            </div>




            <!-- Employee Table -->
            <div class="col-span-5  row-span-7 row-start-2 bg-white flex flex-col  rounded-2xl shadow-lg">
                <div class="flex justify-between items-center mb-6 shadow-md py-5 px-5">
                    <h3 class="text-xl font-semibold text-gray-800">
                        <i class="fas fa-table mr-2 text-blue-500"></i>
                        รายการพนักงาน
                    </h3>
                    <div class="flex items-center space-x-3">
                        <button class="btn btn-primary btn-md gradient-bg border-0 hover-scale shadow-lg"
                            onclick="document.getElementById('add_employee_modal').showModal()">
                            <i class="fas fa-plus mr-2"></i>
                            เพิ่มพนักงานใหม่
                        </button>
                        <div class="relative">
                            <input type="text" placeholder="ค้นหาพนักงาน..." class="input input-bordered w-64 pl-10"
                                id="search_input">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                        <button class="btn btn-outline btn-sm" onclick="loadEmployees()">
                            <i class="fas fa-refresh mr-1"></i>
                            รีเฟรช
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto px-6">
                    <table class="table border-none table-zebra w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="text-left">

                                    ID
                                </th>
                                <th class="text-left">
                                    <i class="fas fa-user mr-2"></i>
                                    ชื่อผู้ใช้
                                </th>
                                <th class="text-left">
                                    <i class="fas fa-user mr-2"></i>
                                    เบอร์โทร
                                </th>
                                <th class="text-left">
                                    <i class="fas fa-clock mr-2"></i>
                                    วันที่สร้าง
                                </th>


                                <?php if($isAdmin): ?>
                                    
                                <th class="text-center">
                                    <i class="fas fa-cogs mr-2"></i>
                                    จัดการ
                                </th>
                                <?php endif ?>
                            </tr>
                        </thead>
                        <tbody id="employee_table_body">
                            <?php
                            if (mysqli_num_rows($admin) > 0) {
                                $i = 1;
                                foreach ($admin as $row) {
                                    ?>
                                    <tr class="border-b">
                                        <td class="px-4 py-2 "><?php echo $i; ?></td>
                                        <td class="px-4 py-2 text-left"><?php echo $row['admin_name']; ?></td>
                                        <td class="px-4 py-2 text-left">
                                            <?= !empty($row['tel']) ? $row['tel'] : 'ยังไม่กรอก' ?>
                                        </td>

                                        <td class="px-4 py-2 text-left">
                                            <?php

                                            echo formatThaiShortDateTime($row['create_At']);
                                            ?>
                                        </td>
                                        <?php if($isAdmin): ?>
                                   
                                        <td class="flex space-x-2 justify-center">
                                            <button
                                                class="px-4 py-2 rounded-lg bg-yellow-400 text-white hover:bg-yellow-500 transition"
                                                onclick="openEditModal(<?= $row['admin_id'] ?>, '<?= $row['admin_name'] ?>')">
                                                แก้ไข
                                            </button>


                                            <!-- ปุ่มลบ -->
                                            <form action="admin_db.php" method="post" onsubmit=" return checkDelete()">
                                                <input type="hidden" name="_method" value="delete">
                                                <input type="hidden" name="admin_id" value="<?= $row['admin_id'] ?>">
                                                <button type="submit"
                                                    class="px-4 py-2 rounded-lg bg-red-500 text-white hover:bg-red-600 transition">
                                                    ลบ
                                                </button>
                                            </form>

                                            <script>
                                                function checkDelete() {
                                                    return confirm("คุณแน่ใจหรือไม่ว่าต้องการลบ?");
                                                }
                                            </script>
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

    <!-- Add Employee Modal -->
    <dialog id="add_employee_modal" class="modal">
        <div class="modal-box w-11/12 max-w-[28rem]">


            <div class="flex items-center mb-6">
                <div
                    class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-user-plus text-white text-lg"></i>
                </div>
                <div class="ml-4">
                    <h3 class="font-bold text-2xl text-gray-800">เพิ่มพนักงานใหม่</h3>
                    <p class="text-gray-600">กรอกข้อมูลพนักงานใหม่ในระบบ</p>
                </div>
            </div>

            <form name="add_employee_form" method="post" action="./admin_db.php" class="space-y-6">
                <input type="hidden" name="_method" value="POST">
                <div class="grid grid-cols-1 md:grid-cols-1 gap-6">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">
                                <i class="fas fa-user mr-2 text-blue-500"></i>
                                ชื่อผู้ใช้
                            </span>
                        </label>
                        <input type="text" name="admin_name" placeholder="กรอกชื่อผู้ใช้"
                            class="input input-bordered w-full focus:border-blue-500" required>
                    </div>
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">
                                <i class="fas fa-user mr-2 text-blue-500"></i>
                                เบอร์โทร
                            </span>
                        </label>
                        <input type="text" name="tel" placeholder="0000000000"
                            class="input input-bordered w-full focus:border-blue-500" required>
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">
                                <i class="fas fa-lock mr-2 text-blue-500"></i>
                                รหัสผ่าน
                            </span>
                        </label>
                        <div class="relative">
                            <input type="password" name="admin_password" placeholder="กรอกรหัสผ่าน"
                                class="input input-bordered w-full focus:border-blue-500 pr-12" required
                                id="password_input">
                            <button type="button" class="btn btn-ghost btn-sm absolute right-1 top-1"
                                onclick="togglePassword()">
                                <i class="fas fa-eye" id="password_toggle_icon"></i>
                            </button>
                        </div>
                    </div>
                </div>



                <div class="modal-action pt-6">
                    <button type="button" class="btn btn-ghost"
                        onclick="document.getElementById('add_employee_modal').close()">
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


    <!-- ! dialog แก้ไข -->
    <dialog id="edit_employee_modal" class="modal ">
        <div class="modal-box w-11/12 max-w-[28rem]">
            <div class="flex items-center mb-6">
                <div
                    class="w-12 h-12 bg-gradient-to-r from-yellow-400 to-yellow-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-user-edit text-white text-lg"></i>
                </div>
                <div class="ml-4">
                    <h3 class="font-bold text-2xl text-gray-800">แก้ไขพนักงาน</h3>
                    <p class="text-gray-600">อัปเดตข้อมูลผู้ใช้งาน</p>
                </div>
            </div>

            <form name="edit_employee_form" method="post" action="./admin_db.php" class="space-y-6">
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" name="admin_id" id="edit_admin_id">

                <div class="grid grid-cols-1 md:grid-cols-1 gap-6  ">
                    <div class="form-control  ">
                        <label class="label">
                            <span class="label-text font-semibold">
                                <i class="fas fa-user mr-2 text-yellow-500"></i>
                                ชื่อผู้ใช้
                            </span>
                        </label>
                        <input type="text" name="admin_name" id="edit_admin_name" placeholder="กรอกชื่อผู้ใช้"
                            class="input input-bordered w-full focus:border-yellow-500" required>
                    </div>
                    <div class="form-control  ">
                        <label class="label">
                            <span class="label-text font-semibold">
                                <i class="fas fa-user mr-2 text-yellow-500"></i>
                                เบอร์โทร
                            </span>
                        </label>
                        <input type="text" name="tel" id="edit_admin_tel" placeholder="0980000000"
                            class="input input-bordered w-full focus:border-yellow-500" required>
                    </div>

                    <div class="form-control  ">
                        <label class="label">
                            <span class="label-text font-semibold">
                                <i class="fas fa-lock mr-2 text-yellow-500"></i>
                                รหัสผ่าน (ปล่อยว่างหากไม่ต้องการเปลี่ยน)
                            </span>
                        </label>
                        <input type="password" name="admin_password" id="edit_admin_password"
                            placeholder="กรอกรหัสผ่านใหม่ (ถ้าต้องการ)" class="input input-bordered w-full">
                    </div>
                </div>

                <div class="modal-action pt-6">
                    <button type="button" class="btn btn-ghost"
                        onclick="document.getElementById('edit_employee_modal').close()">
                        <i class="fas fa-times mr-2"></i> ยกเลิก
                    </button>
                    <button type="submit" class="btn bg-yellow-500 hover:bg-yellow-600 text-white">
                        <i class="fas fa-save mr-2"></i> บันทึกการแก้ไข
                    </button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>




    <?php

    include('./../lib/toast.php')

        ?>



    <script src="./../scripts/main.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
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

        function openEditModal(id, name) {
            document.getElementById('edit_admin_id').value = id;
            document.getElementById('edit_admin_name').value = name;
            document.getElementById('edit_admin_password').value = '';
            document.getElementById('edit_employee_modal').showModal();
        }

        // Search functionality
        document.getElementById('search_input').addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#employee_table_body tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Load employees when page loads
        document.addEventListener('DOMContentLoaded', function () {
            loadEmployees();
        });
    </script>
</body>

</html>