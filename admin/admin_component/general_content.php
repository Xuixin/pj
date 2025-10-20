            <!-- Main Grid Layout -->
            <div class="  grid grid-cols-5 grid-rows-5 gap-4 ">
                <!-- Stats Cards Area (1) -->
                <div class="col-span-2 row-span-2">
                    <div class="grid grid-cols-2 gap-4 h-full">
                        <!-- Total Users -->
                        <div class="stat-card card p-4 cursor-pointer" onclick="window.location.href = '/pj/admin/user.php'">
                            <div class="text-center">
                                <i class="fas fa-users text-xl mb-2"></i>
                                <h3 class="text-xl font-bold"><?= number_format($userCount) ?></h3>
                                <p class="text-xs opacity-80">ลูกค้าทั้งหมด</p>
                            </div>
                        </div>

                        <!-- Total Devices -->
                        <div class="stat-card blue card p-4" onclick="window.location.href = '/pj/admin/device.php'">
                            <div class="text-center">
                                <i class="fas fa-laptop text-xl mb-2"></i>
                                <h3 class="text-xl font-bold"><?= array_sum($avalable_device) ?></h3>
                                <p class="text-xs opacity-80">อุปกรณ์ที่ว่างให้เช่า</p>
                            </div>
                        </div>

                        <!-- Normal Devices -->
                        <div class="stat-card green card p-4">
                            <div class="text-center">
                                <i class="fas fa-check-circle text-xl mb-2"></i>
                                <h3 class="text-xl font-bold"><?= array_sum($unavalable_device) ?></h3>
                                <p class="text-xs opacity-80">อุปกรณ์ที่ถูกเช่าแล้ว</p>
                            </div>
                        </div>

                        <!-- Broken Devices -->
                        <div class="stat-card red card p-4">
                            <div class="text-center">
                                <i class="fas fa-exclamation-triangle text-xl mb-2"></i>
                                <h3 class="text-xl font-bold"><?= $statusSummary['ส่งเคลม'] + $statusSummary['เสีย'] ?>
                                </h3>
                                <p class="text-xs opacity-80">อุปกรณ์ต้องซ่อม</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Expiring Contracts (2) -->
                <div class="col-span-2 row-span-3 col-start-1 row-start-3">
                    <div class="card p-6 h-full">
                        <div class="flex items-center mb-4">
                            <i class="fas fa-calendar-times text-orange-500 mr-2"></i>
                            <h2 class="text-lg font-semibold">สัญญาที่จะหมดอายุใน 30 วัน</h2>
                        </div>

                        <div class="overflow-y-auto h-64 scrollbar-thin">
                            <?php if (count($expiringContracts) === 0): ?>
                                <div class="text-center py-8">
                                    <i class="fas fa-calendar-check text-3xl text-gray-300 mb-2"></i>
                                    <p class="text-gray-500">ไม่มีสัญญาที่จะหมดอายุ</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach ($expiringContracts as $contract): ?>
                                        <div class="border border-gray-200 rounded-lg p-3  cursor-pointer" onclick="window.location.href='/pj/admin/contract_viewer.php?rent_id=<?= htmlspecialchars($contract['rent_id'], ENT_QUOTES) ?>'">
                                            <div class="flex justify-between items-center">
                                                <div>
                                                    <div class="font-medium text-gray-800 mb-1">
                                                        <i class="fas fa-file-contract text-blue-500 mr-1"></i>
                                                        รหัสสัญญา: <?= $contract['rent_id'] ?>
                                                    </div>
                                                    <div class="text-sm text-gray-600">
                                                        <i class="fas fa-user mr-1"></i>
                                                        ผู้เช่า: <?= $contract['user_name'] ?>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <div class="px-2 py-1 bg-red-100 text-red-600 rounded text-sm">
                                                        <?= formatDateThai($contract['end_date']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Broken Devices (3) -->
                <div class="col-span-3 row-span-3 col-start-3 row-start-1">
                    <div class="card p-6 h-full">
                        <div class="flex items-center mb-4">
                            <i class="fas fa-tools text-red-500 mr-2"></i>
                            <h2 class="text-lg font-semibold">อุปกรณ์ต้องซ่อม</h2>
                        </div>

                        <div class="overflow-y-auto h-64 scrollbar-thin">
                            <?php if (count($brokenDevices) === 0): ?>
                                <div class="text-center py-8">
                                    <i class="fas fa-check-circle text-3xl text-green-300 mb-2"></i>
                                    <p class="text-gray-500">ไม่มีอุปกรณ์เสีย</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach ($brokenDevices as $device): ?>
                                        <div class="border border-gray-200 rounded-lg p-3">
                                            <div class="flex justify-between items-start mb-2">
                                                <div>
                                                    <div class="font-medium text-gray-800 mb-1">
                                                        <?= htmlspecialchars($device['serial_number']) ?>
                                                    </div>
                                                    <div class="text-sm text-gray-600">
                                                        <i class="fas fa-desktop mr-1"></i>
                                                        <?= htmlspecialchars($device['model_name']) ?>
                                                    </div>
                                                </div>
                                                <span
                                                    class="status-badge <?= $device['machine_status'] === 'เสีย' ? 'status-broken' : 'status-claim' ?>">
                                                    <?= $device['machine_status'] ?>
                                                </span>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <i class="fas fa-calendar-alt mr-1"></i>
                                                สัญญาหมดอายุ: <?= formatDateThai($device['end_date']) ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Upcoming PM (4) -->
                <div class="col-span-3 row-span-2 col-start-3 row-start-4">
                    <div class="card p-6 h-full">
                        <div class="flex items-center mb-4">
                            <i class="fas fa-clipboard-check text-blue-500 mr-2"></i>
                            <h2 class="text-lg font-semibold">PM ที่จะถึงกำหนด</h2>
                        </div>

                        <div class="overflow-y-auto h-32 scrollbar-thin">
                            <?php if (count($upcomingPMs) === 0): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-calendar-check text-2xl text-blue-300 mb-2"></i>
                                    <p class="text-gray-500">ไม่มี PM ที่จะถึงกำหนด</p>
                                </div>
                            <?php else: ?>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <?php foreach ($upcomingPMs as $pm): ?>
                                        <div class="border border-gray-200 rounded-lg p-3">
                                            <div class="flex justify-between items-center">
                                                <a href="./contract_viewer.php?rent_id=<?= $pm['rent_id'] ?>">
                                                    <div class="font-medium text-gray-800 mb-1">
                                                        <i class="fas fa-file-alt text-blue-500 mr-1"></i>
                                                        รหัสสัญญา: <?= $pm['rent_id'] ?>
                                                    </div>
                                                    <div class="text-sm text-gray-600">
                                                        <i class="fas fa-user mr-1"></i>
                                                        ผู้เช่า: <?= $pm['user_name'] ?>
                                                    </div>
                                                </a>
                                                <div class="text-right">
                                                    <div class="px-2 py-1 bg-blue-100 text-blue-600 rounded text-sm">
                                                        <?= formatDateThai($pm['next_pm_date']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>