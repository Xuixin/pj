<?php
require_once('./../conn.php');

if (!$_GET['rent_id']) {
    header('location: rent.php');
}

$rent_id = (int)$_GET['rent_id']; // ป้องกัน SQL injection เล็กน้อย

$sql_model_summary = "
    SELECT 
        m.model_id,
        m.model_name,
        b.brand_name,
        t.type_name,
        COUNT(rd.rent_detail_id) AS total_devices
    FROM rent_detail rd
    LEFT JOIN device d ON d.device_id = rd.device_id
    LEFT JOIN model m ON m.model_id = d.model_id
    LEFT JOIN type t ON t.type_id = m.type_id
    LEFT JOIN brand b ON b.brand_id = m.brand_id
    WHERE rd.rent_id = $rent_id
    GROUP BY m.model_id, m.model_name, b.brand_name, t.type_name
    ORDER BY b.brand_name, m.model_name
";

$result = mysqli_query($conn, $sql_model_summary);

$rent_models = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $rent_models[] = $row;
    }
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
                    <div class="w-12 h-12 bg-gradient-to-r from-green-400 to-blue-500 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-white text-lg"></i>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-lg font-semibold text-gray-800">ยินดีต้อนรับ, <?php echo $_SESSION['admin_name'] ?></h1>
                    </div>
                </div>

            </div>

            <div class="container col-span-5 bg-white rounded row-span-7 mx-auto overflow-auto p-6">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <a href="rent.php" class="btn btn-ghost mr-4">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold">รายการอุปกรณ์ที่เช่า</h1>
                        </div>
                    </div>

                </div>



                <div class="card bg-white shadow-lg mb-6">
                    <div class="card-body">


                        <?php if (!empty($rent_models)): ?>
                            <table id="rentModelsTable" class="table table-zebra w-full">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="font-semibold">
                                            <i class="fas fa-industry mr-1"></i>ยี่ห้อ
                                        </th>
                                        <th class="font-semibold">
                                            <i class="fas fa-laptop mr-1"></i>รุ่น
                                        </th>
                                        <th class="font-semibold">
                                            <i class="fas fa-tag mr-1"></i>ประเภท
                                        </th>
                                        <th class="text-center font-semibold">
                                            <i class="fas fa-boxes mr-1"></i>จำนวนเครื่อง
                                        </th>
                                        <th class="text-center font-semibold">
                                            <i class="fas fa-cogs mr-1"></i>Action
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rent_models as $model): ?>
                                        <tr class="hover:bg-gray-50 transition-colors cursor-pointer"
                                            onclick="window.location.href = 'rent_detail_device.php?rent_id=<?= $rent_id ?>&model_id=<?= $model['model_id'] ?>'">

                                            <!-- Brand -->
                                            <td>
                                                <div class="flex items-center">
                                                    <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                                                        <i class="fas fa-industry text-purple-600 text-sm"></i>
                                                    </div>
                                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($model['brand_name']) ?></div>
                                                </div>
                                            </td>

                                            <!-- Model -->
                                            <td>
                                                <div class="font-medium text-gray-900"><?= htmlspecialchars($model['model_name']) ?></div>
                                                <div class="text-sm text-gray-500">Model ID: <?= $model['model_id'] ?></div>
                                            </td>

                                            <!-- Type -->
                                            <td>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                                    <i class="fas fa-tag mr-1 text-xs"></i>
                                                    <?= htmlspecialchars($model['type_name']) ?>
                                                </span>
                                            </td>

                                            <!-- จำนวนเครื่อง -->
                                            <td class="text-center">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                                                    <i class="fas fa-box mr-1 text-xs"></i>
                                                    <?= $model['total_devices'] ?> เครื่อง
                                                </span>
                                            </td>

                                            <!-- Action -->
                                            <td class="text-center" onclick="event.stopPropagation();">
                                                <a href="rent_detail_device.php?rent_id=<?= $rent_id ?>&model_id=<?= $model['model_id'] ?>"
                                                    class="btn btn-sm btn-outline btn-primary">
                                                    <i class="fas fa-eye mr-1"></i> ดูรายละเอียด
                                                </a>

                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-gray-600">ไม่พบข้อมูลรุ่นที่เช่า</p>
                        <?php endif; ?>


                    </div>
                </div>



            </div>
        </div>
    </div>



    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const table = document.getElementById('rentDetailsTable');
            const tbody = document.getElementById('tableBody');
            const originalRows = Array.from(tbody.querySelectorAll('tr'));
            let filteredRows = [...originalRows];
            let currentPage = 1;
            const rowsPerPage = 10;

            // Filter elements
            const globalSearch = document.getElementById('globalSearch');
            const modelFilter = document.getElementById('modelFilter');
            const typeFilter = document.getElementById('typeFilter');
            const statusFilter = document.getElementById('statusFilter');
            const clearFilters = document.getElementById('clearFilters');

            // Pagination elements
            const prevPage = document.getElementById('prevPage');
            const nextPage = document.getElementById('nextPage');
            const pageNumbers = document.getElementById('pageNumbers');
            const showingStart = document.getElementById('showingStart');
            const showingEnd = document.getElementById('showingEnd');
            const totalItems = document.getElementById('totalItems');
            const totalCount = document.getElementById('totalCount');

            // Filter functions
            function applyFilters() {
                const searchTerm = globalSearch.value.toLowerCase();
                const modelValue = modelFilter.value.toLowerCase();
                const typeValue = typeFilter.value.toLowerCase();
                const statusValue = statusFilter.value;

                filteredRows = originalRows.filter(row => {
                    const serialText = row.cells[1].innerText.toLowerCase();
                    const modelText = row.cells[2].innerText.toLowerCase();
                    const typeText = row.cells[3].innerText.toLowerCase();
                    const statusText = row.cells[4].innerText;

                    const matchesSearch = serialText.includes(searchTerm) || modelText.includes(searchTerm);
                    const matchesModel = !modelValue || modelText.includes(modelValue);
                    const matchesType = !typeValue || typeText.includes(typeValue);
                    const matchesStatus = !statusValue || statusText.includes(statusValue);

                    return matchesSearch && matchesModel && matchesType && matchesStatus;
                });

                currentPage = 1;
                renderTable();
            }

            // Render filtered table rows based on pagination
            function renderTable() {
                const startIndex = (currentPage - 1) * rowsPerPage;
                const endIndex = startIndex + rowsPerPage;
                tbody.innerHTML = '';
                const rowsToShow = filteredRows.slice(startIndex, endIndex);
                rowsToShow.forEach(row => tbody.appendChild(row));

                showingStart.textContent = filteredRows.length === 0 ? 0 : startIndex + 1;
                showingEnd.textContent = Math.min(endIndex, filteredRows.length);
                totalItems.textContent = filteredRows.length;
                totalCount.textContent = filteredRows.length;

                prevPage.disabled = currentPage === 1;
                nextPage.disabled = endIndex >= filteredRows.length;

                renderPageNumbers();
            }

            // Generate pagination buttons
            function renderPageNumbers() {
                pageNumbers.innerHTML = '';
                const totalPages = Math.ceil(filteredRows.length / rowsPerPage);

                for (let i = 1; i <= totalPages; i++) {
                    const btn = document.createElement('button');
                    btn.className = `btn btn-sm ${i === currentPage ? 'btn-primary' : 'btn-outline'}`;
                    btn.textContent = i;
                    btn.addEventListener('click', () => {
                        currentPage = i;
                        renderTable();
                    });
                    pageNumbers.appendChild(btn);
                }
            }

            // Event listeners
            globalSearch.addEventListener('input', applyFilters);
            modelFilter.addEventListener('change', applyFilters);
            typeFilter.addEventListener('change', applyFilters);
            statusFilter.addEventListener('change', applyFilters);
            clearFilters.addEventListener('click', () => {
                globalSearch.value = '';
                modelFilter.value = '';
                typeFilter.value = '';
                statusFilter.value = '';
                applyFilters();
            });
            prevPage.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    renderTable();
                }
            });
            nextPage.addEventListener('click', () => {
                if ((currentPage * rowsPerPage) < filteredRows.length) {
                    currentPage++;
                    renderTable();
                }
            });

            // Initial render
            applyFilters();
        });
    </script>





    <?php

    include('./../lib/toast.php')

    ?>



    <script src="./../scripts/main.js"></script>

</body>

</html>