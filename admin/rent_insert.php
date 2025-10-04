<?php
session_start();
require_once('./../conn.php');

// Initialize cart if not exists
if (!isset($_SESSION['rental_cart'])) {
    $_SESSION['rental_cart'] = [];
}



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

        .cart-item {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="bg-gradient-to-br from-slate-100 to-slate-300">

    <div class="grid grid-cols-5 grid-rows-1 h-screen gap-2 row-h min-h-screen sticky">
        <!-- Sidebar -->
        <div class="">
            <div class="rounded-2xl inline-block h-full">
                <?php include('admin_component/sidebar.php') ?>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-span-4 z-20 row-span-1 col-start-2 row-start-1 py-3 pr-3 grid grid-cols-5 grid-rows-8 gap-6">
            <div class="col-span-5 row-span-1 bg-white rounded-2xl shadow-lg flex items-center justify-between px-8">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-gradient-to-r from-green-400 to-blue-500 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-white text-lg"></i>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-lg font-semibold text-gray-800">ยินดีต้อนรับ, <?php echo $_SESSION['admin_name'] ?></h1>
                        <p class="text-sm text-gray-500">จัดการข้อมูลพนักงาน</p>
                    </div>
                </div>

            </div>

            <!-- Main Form -->
            <div class="bg-white col-span-5 rounded-2xl shadow-lg p-8 h-full min-h-[42rem] w-full max-h-[42rem] overflow-y-auto">
                <div class="flex items-center mb-6 shadow-sm p-3">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-handshake text-white text-lg"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="font-bold text-2xl text-gray-800">ทำรายการสัญญาเช่าใหม่</h3>
                        <p class="text-gray-600">กรอกข้อมูลการเช่า-ยืมในระบบ</p>
                    </div>
                </div>

                <form id="add_rent_form" method="post" action="rent_db.php" class="space-y-6 ">
                    <input type="hidden" name="_method" value="POST">

                    <?php
                    $users = mysqli_query($conn, "SELECT * FROM user ORDER BY user_name");
                    $admins = mysqli_query($conn, "SELECT * FROM admin ORDER BY admin_name");
                    $brands = mysqli_query($conn, "SELECT * FROM brand ORDER BY brand_name");
                    ?>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- ผู้ใช้ -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">ชื่อผู้ใช้</span>
                            </label>
                            <input type="text" list="userList" id="user_name_input" name="user_name"
                                placeholder="กรอกชื่อผู้ใช้"
                                class="input input-bordered w-full focus:border-blue-500" required>
                            <datalist id="userList">
                                <?php while ($user = mysqli_fetch_assoc($users)) : ?>
                                    <option data-id="<?= $user['user_id'] ?>" value="<?= htmlspecialchars($user['user_name']) ?>"></option>
                                <?php endwhile; ?>
                            </datalist>
                            <input type="hidden" name="user_id" id="user_id_hidden">
                        </div>

                        <!-- ผู้อนุมัติ
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">ผู้อนุมัติ</span>
                            </label>
                            <input type="text" list="adminList" id="admin_name_input" name="admin_name"
                                placeholder="กรอกชื่อผู้อนุมัติ"
                                class="input input-bordered w-full focus:border-blue-500" required>
                            <datalist id="adminList">
                                <?php while ($admin = mysqli_fetch_assoc($admins)) : ?>
                                    <option data-id="<?= $admin['admin_id'] ?>" value="<?= htmlspecialchars($admin['admin_name']) ?>"></option>
                                <?php endwhile; ?>
                            </datalist>
                            <input type="hidden" name="admin_id" id="admin_id_hidden">
                        </div> -->

                        <!-- วันที่อนุมัติ -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">วันที่เริ่มเช่า</span>
                            </label>
                            <input type="date" id="approve_date" name="start_date"
                                class="input input-bordered w-full focus:border-blue-500" required />
                        </div>

                        <!-- ประเภทการเช่า -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">ประเภทการเช่า</span>
                            </label>
                            <select name="rent_type" id="rent_type" onchange="calculateReturnDate()" class="select select-bordered w-full" required>
                                <option value="" disabled selected>เลือกประเภทการเช่า</option>
                                <!-- <option value="daily">รายวัน</option> -->
                                <option value="monthly">รายเดือน</option>
                                <option value="yearly">รายปี</option>
                            </select>
                        </div>

                        <!-- จำนวน -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">จำนวน</span>
                            </label>
                            <input type="number" id="rent_duration" name="rent_duration" min="1" max="365"
                                placeholder="กรอกจำนวน" onchange="calculateReturnDate()"
                                class="input input-bordered w-full focus:border-blue-500" required>
                        </div>

                        <!-- วันที่คืน -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">วันที่คืน</span>
                            </label>
                            <div id="return_date_display" class="p-3 bg-gray-50 rounded-lg text-gray-600 border">
                                กรุณาเลือกวันที่อนุมัติและประเภทการเช่าก่อน
                            </div>
                            <input type="hidden" name="return_date" id="return_date_hidden">
                        </div>
                    </div>

                    <!-- Equipment Selection Section -->
                    <div class="border-t pt-6">
                        <h4 class="font-semibold text-lg mb-4 text-gray-800">เลือกอุปกรณ์</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text font-semibold">เลือกแบรนด์</span>
                                </label>
                                <select name="brand_select" id="brand_select" class="select select-bordered" onchange="loadModels()">
                                    <option value="" disabled selected>เลือกแบรนด์</option>
                                    <?php while ($brand = mysqli_fetch_assoc($brands)) : ?>
                                        <option value="<?= $brand['brand_id'] ?>"><?= htmlspecialchars($brand['brand_name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text font-semibold">เลือกโมเดล</span>
                                </label>
                                <select name="model_select" id="model_select" class="select select-bordered" disabled>
                                    <option value="" disabled selected>เลือกโมเดลก่อน</option>
                                </select>
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text font-semibold">จำนวนเครื่อง</span>
                                </label>
                                <input type="number" id="equipment_quantity" min="1" max="100" placeholder="จำนวน"
                                    class="input input-bordered w-full">
                            </div>

                            <button type="button" onclick="addToCart()" class="btn btn-success">
                                <i class="fas fa-plus mr-2"></i>เพิ่ม
                            </button>
                        </div>
                    </div>

                    <!-- Cart Display -->
                    <div class="border-t pt-6">
                        <h4 class="font-semibold text-lg mb-4 text-gray-800">รายการอุปกรณ์ที่เลือก</h4>
                        <div id="cart_display" class="space-y-3">
                            <!-- Cart items will be displayed here -->
                        </div>
                        <input type="hidden" name="cart_data" id="cart_data_hidden">
                    </div>

                    <!-- ? back up device zone -->
                    <!-- Backup Device Selection Section -->
                    <div class="border-t pt-6">
                        <h4 class="font-semibold text-lg mb-4 text-gray-800 flex items-center">
                            <i class="fas fa-shield-alt mr-2 text-orange-500"></i>
                            เลือกอุปกรณ์สำรอง
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text font-semibold">เลือกแบรนด์</span>
                                </label>
                                <select name="backup_brand_select" id="backup_brand_select" class="select select-bordered" onchange="loadBackupModels()">
                                    <option value="" disabled selected>เลือกแบรนด์</option>
                                    <?php
                                    // Reset brands query
                                    $brands = mysqli_query($conn, "SELECT * FROM brand ORDER BY brand_name");
                                    while ($brand = mysqli_fetch_assoc($brands)) : ?>
                                        <option value="<?= $brand['brand_id'] ?>"><?= htmlspecialchars($brand['brand_name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text font-semibold">เลือกโมเดล</span>
                                </label>
                                <select name="backup_model_select" id="backup_model_select" class="select select-bordered" disabled>
                                    <option value="" disabled selected>เลือกโมเดลก่อน</option>
                                </select>
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text font-semibold">จำนวนเครื่อง</span>
                                </label>
                                <input type="number" id="backup_equipment_quantity" min="1" max="100" placeholder="จำนวน"
                                    class="input input-bordered w-full">
                            </div>

                            <button type="button" onclick="addBackupToCart()" class="btn btn-warning">
                                <i class="fas fa-plus mr-2"></i>เพิ่มสำรอง
                            </button>
                        </div>
                    </div>

                    <!-- Backup Cart Display -->
                    <div class="border-t pt-6">
                        <h4 class="font-semibold text-lg mb-4 text-gray-800 flex items-center">
                            <i class="fas fa-shield-alt mr-2 text-orange-500"></i>
                            รายการอุปกรณ์สำรองที่เลือก
                        </h4>
                        <div id="backup_cart_display" class="space-y-3">
                            <!-- Backup cart items will be displayed here -->
                        </div>
                        <input type="hidden" name="backup_cart_data" id="backup_cart_data_hidden">
                    </div>


                    <div class="payment-section border-t pt-6 p-6">
                        <h4 class="font-semibold text-lg mb-4 text-gray-800 flex items-center">
                            <i class="fas fa-credit-card mr-2 text-green-600"></i>
                            รูปแบบการชำระเงิน
                        </h4>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text font-semibold">ประเภทการชำระ</span>
                                </label>
                                <select name="payment_type" id="payment_type" class="select select-bordered w-full"
                                    onchange="toggleInstallmentFields()" required>
                                    <option value="" disabled selected>เลือกรูปแบบการชำระ</option>
                                    <option value="full">จ่ายเต็มจำนวน</option>
                                    <option value="installment">จ่ายเป็นงวด</option>
                                </select>
                            </div>

                            <div class="form-control" id="installment_field" style="display: none;">
                                <label class="label">
                                    <span class="label-text font-semibold">จำนวนงวด</span>
                                </label>
                                <select name="installment_count" id="installment_count"
                                    class="select select-bordered w-full" onchange="calculateInstallmentAmount()">
                                    <option value="" disabled selected>เลือกจำนวนงวด</option>
                                    <option value="3">3 งวด</option>
                                    <option value="6">6 งวด</option>
                                    <option value="12">12 งวด</option>
                                    <option value="24">24 งวด</option>
                                    <option value="36">36 งวด</option>
                                </select>
                            </div>
                        </div>

                        <!-- Payment Summary -->
                        <div id="payment_summary" class="mt-6 p-4 bg-white rounded-lg border hidden">
                            <h5 class="font-semibold text-md mb-3 text-gray-700">สรุปการชำระเงิน</h5>
                            <div id="payment_details"></div>
                        </div>

                        <input type="hidden" name="installment_amount" id="installment_amount_hidden">
                        <input type="hidden" name="total_amount" id="total_amount_hidden">
                    </div>


                    <!-- Submit Buttons -->
                    <div class="flex justify-end space-x-4 pt-6 border-t">
                        <button type="button" onclick="clearCart()" class="btn btn-ghost">
                            <i class="fas fa-trash mr-2"></i>ล้างรายการ
                        </button>
                        <button type="reset" class="btn btn-ghost">
                            <i class="fas fa-times mr-2"></i>ยกเลิก
                        </button>
                        <button type="submit" class="btn btn-primary gradient-bg border-0 hover-scale">
                            <i class="fas fa-save mr-2"></i>บันทึก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include('./../lib/toast.php') ?>

    <script src="./../scripts/main.js"></script>

    <script>
        // Cart management
        let cart = [];
        let backupCart = [];
        let allModels = null
        let allBackupModels = null
        let total_price = 0

        // Set today's date as default
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('approve_date').value = today;
            displayCart();
            displayBackupCart();
        });

        // User autocomplete
        document.getElementById('user_name_input').addEventListener('input', function() {
            const inputVal = this.value;
            const options = document.querySelectorAll('#userList option');
            let found = false;

            options.forEach(option => {
                if (option.value === inputVal) {
                    document.getElementById('user_id_hidden').value = option.dataset.id;
                    found = true;
                }
            });

            if (!found) {
                document.getElementById('user_id_hidden').value = '';
            }
        });

        // Admin autocomplete
        document.getElementById('admin_name_input').addEventListener('input', function() {
            const inputVal = this.value;
            const options = document.querySelectorAll('#adminList option');
            let found = false;

            options.forEach(option => {
                if (option.value === inputVal) {
                    document.getElementById('admin_id_hidden').value = option.dataset.id;
                    found = true;
                }
            });

            if (!found) {
                document.getElementById('admin_id_hidden').value = '';
            }
        });

        // Load models based on selected brand
        async function loadModels() {
            const brandId = document.getElementById('brand_select').value;
            const modelSelect = document.getElementById('model_select');

            if (!brandId) {
                modelSelect.innerHTML = '<option value="" disabled selected>เลือกแบรนด์ก่อน</option>';
                modelSelect.disabled = true;
                return;
            }

            try {
                const response = await fetch('get_models.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'brand_id=' + brandId
                });


                const models = await response.json();
                allModels = models



                modelSelect.innerHTML = '<option value="" disabled selected>เลือกโมเดล</option>';
                models.forEach(model => {
                    modelSelect.innerHTML += `<option value="${model.model_id}" data-available="${model.available_quantity}">${model.model_name} (คงเหลือ: ${model.available_quantity})</option>`;
                });

                modelSelect.disabled = false;
            } catch (error) {
                console.error('Error loading models:', error);
                modelSelect.innerHTML = '<option value="" disabled selected>เกิดข้อผิดพลาด</option>';
            }
        }

        // Load backup models based on selected brand
        async function loadBackupModels() {
            const brandId = document.getElementById('backup_brand_select').value;
            const modelSelect = document.getElementById('backup_model_select');

            if (!brandId) {
                modelSelect.innerHTML = '<option value="" disabled selected>เลือกแบรนด์ก่อน</option>';
                modelSelect.disabled = true;
                return;
            }

            try {
                const response = await fetch('get_models.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'brand_id=' + brandId
                });

                const models = await response.json();
                allBackupModels = models;

                modelSelect.innerHTML = '<option value="" disabled selected>เลือกโมเดล</option>';
                models.forEach(model => {
                    modelSelect.innerHTML += `<option value="${model.model_id}" data-available="${model.available_quantity}">${model.model_name} (คงเหลือ: ${model.available_quantity})</option>`;
                });

                modelSelect.disabled = false;
            } catch (error) {
                console.error('Error loading backup models:', error);
                modelSelect.innerHTML = '<option value="" disabled selected>เกิดข้อผิดพลาด</option>';
            }
        }

        // Add item to cart
        function addToCart() {
            const brandSelect = document.getElementById('brand_select');
            const modelSelect = document.getElementById('model_select');
            const quantity = parseInt(document.getElementById('equipment_quantity').value);




            if (!brandSelect.value || !modelSelect.value || !quantity) {
                alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                return;
            }

            const selectedModel = modelSelect.options[modelSelect.selectedIndex];
            const availableQuantity = parseInt(selectedModel.dataset.available);

            if (quantity > availableQuantity) {
                alert(`จำนวนที่เลือกเกินจำนวนที่มีอยู่ (คงเหลือ: ${availableQuantity})`);
                return;
            }

            const valuselect = allModels.find(i => String(i.model_id) === String(modelSelect.value))


            // Check if item already exists in cart
            const existingItemIndex = cart.findIndex(item => item.model_id === modelSelect.value);

            if (existingItemIndex !== -1) {
                cart[existingItemIndex].quantity += quantity;
            } else {
                cart.push({
                    brand_id: brandSelect.value,
                    brand_name: brandSelect.options[brandSelect.selectedIndex].text,
                    model_id: modelSelect.value,
                    model_name: modelSelect.options[modelSelect.selectedIndex].text.split(' (คงเหลือ:')[0],
                    quantity: quantity,
                    available: availableQuantity,
                    price_per_month: Number(valuselect && valuselect.price_per_month ? valuselect.price_per_month : 0)
                });
            }

            // Reset form
            document.getElementById('equipment_quantity').value = '';
            displayCart();
            recalcTotalPrice();
        }

        // Display cart items
        function displayCart() {
            const cartDisplay = document.getElementById('cart_display');

            if (cart.length === 0) {
                cartDisplay.innerHTML = '<div class="text-gray-500 text-center py-8">ยังไม่มีรายการอุปกรณ์</div>';
                document.getElementById('cart_data_hidden').value = '';
                return;
            }

            let cartHTML = '';
            cart.forEach((item, index) => {
                cartHTML += `
                    <div class="cart-item flex items-center justify-between p-4 bg-gray-50 rounded-lg border">
                        <div class="flex-1">
                            <div class="font-semibold">${item.brand_name} - ${item.model_name}</div>
                            <div class="text-sm text-gray-600">จำนวน: ${item.quantity} เครื่อง</div>
                        </div>
                        <button type="button" onclick="removeFromCart(${index})" class="btn btn-sm btn-error">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
            });

            cartDisplay.innerHTML = cartHTML;
            document.getElementById('cart_data_hidden').value = JSON.stringify(cart);
        }

        // Remove item from cart
        function removeFromCart(index) {
            cart.splice(index, 1);
            displayCart();
            recalcTotalPrice();
        }

        // Clear cart
        function clearCart() {
            cart = [];
            backupCart = [];
            displayCart();
            displayBackupCart();
            recalcTotalPrice();
        }

        // Add backup item to cart
        function addBackupToCart() {
            const brandSelect = document.getElementById('backup_brand_select');
            const modelSelect = document.getElementById('backup_model_select');
            const quantity = parseInt(document.getElementById('backup_equipment_quantity').value);

            if (!brandSelect.value || !modelSelect.value || !quantity) {
                alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                return;
            }

            const selectedModel = modelSelect.options[modelSelect.selectedIndex];
            const availableQuantity = parseInt(selectedModel.dataset.available);

            if (quantity > availableQuantity) {
                alert(`จำนวนที่เลือกเกินจำนวนที่มีอยู่ (คงเหลือ: ${availableQuantity})`);
                return;
            }

            const valuselect = allBackupModels.find(i => String(i.model_id) === String(modelSelect.value));

            // Check if item already exists in backup cart
            const existingItemIndex = backupCart.findIndex(item => item.model_id === modelSelect.value);

            if (existingItemIndex !== -1) {
                backupCart[existingItemIndex].quantity += quantity;
            } else {
                backupCart.push({
                    brand_id: brandSelect.value,
                    brand_name: brandSelect.options[brandSelect.selectedIndex].text,
                    model_id: modelSelect.value,
                    model_name: modelSelect.options[modelSelect.selectedIndex].text.split(' (คงเหลือ:')[0],
                    quantity: quantity,
                    available: availableQuantity,
                    price_per_month: Number(valuselect && valuselect.price_per_month ? valuselect.price_per_month : 0),
                    is_backup: true
                });
            }

            // Reset form
            document.getElementById('backup_equipment_quantity').value = '';
            displayBackupCart();
            recalcTotalPrice();
        }

        // Display backup cart items
        function displayBackupCart() {
            const cartDisplay = document.getElementById('backup_cart_display');

            if (backupCart.length === 0) {
                cartDisplay.innerHTML = '<div class="text-gray-500 text-center py-8">ยังไม่มีรายการอุปกรณ์สำรอง</div>';
                document.getElementById('backup_cart_data_hidden').value = '';
                return;
            }

            let cartHTML = '';
            backupCart.forEach((item, index) => {
                cartHTML += `
                    <div class="cart-item flex items-center justify-between p-4 bg-orange-50 rounded-lg border border-orange-200">
                        <div class="flex-1">
                            <div class="font-semibold text-orange-800">${item.brand_name} - ${item.model_name}</div>
                            <div class="text-sm text-orange-600">จำนวน: ${item.quantity} เครื่อง (สำรอง)</div>
                            <div class="text-sm text-green-600 font-semibold">ราคา: ฟรี</div>
                        </div>
                        <button type="button" onclick="removeBackupFromCart(${index})" class="btn btn-sm btn-warning">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
            });

            cartDisplay.innerHTML = cartHTML;
            document.getElementById('backup_cart_data_hidden').value = JSON.stringify(backupCart);
        }

        // Remove backup item from cart
        function removeBackupFromCart(index) {
            backupCart.splice(index, 1);
            displayBackupCart();
            recalcTotalPrice();
        }

        // Calculate return date and recalc total
        function calculateReturnDate() {
            const approveDate = document.getElementById('approve_date').value;
            const rentType = document.getElementById('rent_type').value;
            const duration = parseInt(document.getElementById('rent_duration').value);

            if (!approveDate || !rentType || !duration) {
                document.getElementById('return_date_display').textContent = 'กรุณาเลือกวันที่อนุมัติและประเภทการเช่าก่อน';
                document.getElementById('return_date_hidden').value = '';
                return;
            }

            const startDate = new Date(approveDate);
            let returnDate = new Date(startDate);

            switch (rentType) {
                case 'daily':
                    returnDate.setDate(startDate.getDate() + duration);
                    break;
                case 'monthly':
                    returnDate.setMonth(startDate.getMonth() + duration);
                    break;
                case 'yearly':
                    returnDate.setFullYear(startDate.getFullYear() + duration);
                    break;
            }

            const formattedDate = returnDate.toLocaleDateString('th-TH', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            document.getElementById('return_date_display').textContent = formattedDate;
            document.getElementById('return_date_hidden').value = returnDate.toISOString().split('T')[0];

            // Recalculate total based on duration change
            recalcTotalPrice();
        }

        // Recalculate total price using rent type and duration
        function recalcTotalPrice() {
            const rentType = document.getElementById('rent_type').value;
            const duration = parseInt(document.getElementById('rent_duration').value) || 0;

            let months = 0;
            if (rentType === 'monthly') {
                months = duration;
            } else if (rentType === 'yearly') {
                months = duration * 12;
            } else {
                months = 0;
            }

            let newTotal = 0;

            // Calculate regular devices only (backup devices are free)
            cart.forEach(item => {
                const pricePerMonth = Number(item.price_per_month || 0);
                newTotal += pricePerMonth * (Number(item.quantity) || 0) * months;
            });

            // Backup devices are free - no price calculation needed
            // backupCart.forEach(item => {
            //     const pricePerMonth = Number(item.price_per_month || 0);
            //     newTotal += pricePerMonth * (Number(item.quantity) || 0) * months;
            // });

            total_price = isNaN(newTotal) ? 0 : newTotal;
            // keep hidden input in sync for server submit
            const totalHidden = document.getElementById('total_amount_hidden');
            if (totalHidden) {
                totalHidden.value = String(isNaN(total_price) ? 0 : total_price);
            }
            // Update payment summary if visible
            calculateInstallmentAmount();
        }

        // Recalculate when approve date changes
        document.getElementById('approve_date').addEventListener('change', calculateReturnDate);

        function toggleInstallmentFields() {
            const paymentType = document.getElementById('payment_type').value;
            const installmentField = document.getElementById('installment_field');
            const paymentSummary = document.getElementById('payment_summary');

            if (paymentType === 'installment') {
                installmentField.style.display = 'block';
                document.getElementById('installment_count').required = true;
            } else {
                installmentField.style.display = 'none';
                document.getElementById('installment_count').required = false;
                document.getElementById('installment_count').value = '';
                paymentSummary.classList.add('hidden');
            }

            calculateInstallmentAmount();
        }

        function calculateInstallmentAmount() {
            const paymentType = document.getElementById('payment_type').value;
            const installmentCount = parseInt(document.getElementById('installment_count').value);

            const paymentSummary = document.getElementById('payment_summary');
            const paymentDetails = document.getElementById('payment_details');

            if (!paymentType || total_price === 0) {
                paymentSummary.classList.add('hidden');
                return;
            }

            if (paymentType === 'full') {
                paymentDetails.innerHTML = `
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-gray-600">ประเภทการชำระ:</div>
                        <div class="font-semibold text-green-600">จ่ายเต็มจำนวน</div>
                        <div class="text-gray-600">ยอดรวมทั้งหมด:</div>
                        <div class="font-bold text-xl text-green-600">${numberWithCommas(total_price)} บาท</div>
                    </div>
                `;
                document.getElementById('installment_amount_hidden').value = total_price;
                paymentSummary.classList.remove('hidden');
            } else if (paymentType === 'installment' && installmentCount) {
                const installmentAmount = Math.ceil(total_price / installmentCount);

                paymentDetails.innerHTML = `
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-gray-600">ประเภทการชำระ:</div>
                        <div class="font-semibold text-blue-600">จ่ายเป็นงวด</div>
                        <div class="text-gray-600">ยอดรวมทั้งหมด:</div>
                        <div class="font-bold text-lg">${numberWithCommas(total_price)} บาท</div>
                        <div class="text-gray-600">จำนวนงวด:</div>
                        <div class="font-semibold">${installmentCount} งวด</div>
                        <div class="text-gray-600">ยอดต่องวด:</div>
                        <div class="font-bold text-xl text-blue-600">${numberWithCommas(installmentAmount)} บาท</div>
                    </div>
                    <div class="mt-4 p-3 bg-yellow-50 rounded-lg border-l-4 border-yellow-400">
                        <p class="text-sm text-yellow-700">
                            <i class="fas fa-info-circle mr-1"></i>
                            งวดสุดท้ายอาจมีการปรับปรุงยอดเล็กน้อยเพื่อให้ครบตามยอดรวม
                        </p>
                    </div>
                `;
                document.getElementById('installment_amount_hidden').value = installmentAmount;
                paymentSummary.classList.remove('hidden');
            } else {
                paymentSummary.classList.add('hidden');
            }
        }

        function numberWithCommas(x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }


        // Form validation
        document.getElementById('add_rent_form').addEventListener('submit', function(e) {
            // Force latest total calculation before submit
            try {
                recalcTotalPrice();
            } catch (err) {}
            const user_id = document.getElementById('user_id_hidden').value;
            const returnDate = document.getElementById('return_date_hidden').value;
            const start_date = document.getElementById('approve_date').value;

            if (!user_id) {
                e.preventDefault();
                alert('กรุณาเลือกผู้ใช้ที่ถูกต้อง');
                return;
            }

            if (!returnDate) {
                e.preventDefault();
                alert('กรุณาตรวจสอบการคำนวณวันที่คืน');
                return;
            }

            if (cart.length === 0 && backupCart.length === 0) {
                e.preventDefault();
                alert('กรุณาเพิ่มอุปกรณ์อย่างน้อย 1 รายการ');
                return;
            }

            // Set cart data in hidden fields before form submission
            document.getElementById('cart_data_hidden').value = JSON.stringify(cart);
            document.getElementById('backup_cart_data_hidden').value = JSON.stringify(backupCart);
            // Ensure total amount is present for server to persist to session
            const totalHidden = document.getElementById('total_amount_hidden');
            if (totalHidden) {
                totalHidden.value = String(total_price || 0);
            }
        })
    </script>

</body>

</html>