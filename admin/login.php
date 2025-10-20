<?php
session_start();
require_once './../conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_name = trim($_POST['admin_name'] ?? '');
    $admin_password = trim($_POST['password'] ?? '');

    if ($admin_name && $admin_password) {
        $sql = "SELECT * FROM admin WHERE admin_name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $admin_name);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $admin = $res->fetch_assoc();

            // ตรวจสอบรหัสผ่าน (ใช้ password_verify)
            if (password_verify($admin_password, $admin['admin_password'])) {
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_name'] = $admin['admin_name'];
                $_SESSION['role'] = $admin['role'] ;

                header("Location: dashboard.php");
                exit;
            }
        }

        // กรณีไม่พบหรือรหัสผ่านไม่ถูกต้อง
        $_SESSION['error'] = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
    } else {
        $_SESSION['error'] = "กรุณากรอกข้อมูลให้ครบถ้วน";
    }

    header("Location: login.php"); // หรือไฟล์ฟอร์ม login ของคุณ
    exit;
}
?>


<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>

    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="min-h-screen bg-base-200 flex items-center justify-center">
    <div class="card w-96 bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title text-2xl font-bold text-center mb-6">Admin Login</h2>
            <form method="POST" action="">
                <div class="form-control w-full max-w-xs mb-4">
                    <label class="label">
                        <span class="label-text">Admin Name</span>
                    </label>
                    <input type="text" name="admin_name" placeholder="Enter admin name" class="input input-bordered w-full max-w-xs" required />
                </div>

                <div class="form-control w-full max-w-xs mb-6">
                    <label class="label">
                        <span class="label-text">Password</span>
                    </label>
                    <input type="password" name="password" placeholder="Enter password" class="input input-bordered w-full max-w-xs" required />
                </div>

                <div class="form-control mt-6">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </form>
        </div>
    </div>


    <?php

    include('./../lib/toast.php')

    ?>
    <script src="./../scripts/main.js"></script>
</body>

</html>