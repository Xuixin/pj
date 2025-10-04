<?php
session_start();
require_once('./../conn.php');

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
        <div class="col-span-4 row-span-1 col-start-2 row-start-1 py-3 pr-3 grid grid-cols-5 grid-rows-8 gap-6">
            <div class="col-span-5 row-span-1 bg-white rounded-2xl shadow-lg flex items-center justify-between px-8">
                <div class="flex items-center">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-green-400 to-blue-500 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-white text-lg"></i>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-lg font-semibold text-gray-800">ยินดีต้อนรับ, Admin</h1>
                        <p class="text-sm text-gray-500">จัดการข้อมูลพนักงาน</p>

                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-gray-300 rounded-full"></div>
                </div>
            </div>




           
        </div>
    </div>




    <?php

    include('./../lib/toast.php')

    ?>



    <script src="./../scripts/main.js"></script>

</body>

</html>