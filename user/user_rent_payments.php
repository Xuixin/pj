<?php
// user_rent_payments.php
session_start();
require_once('./../conn.php');

$rent_id = isset($_GET['rent_id']) ? intval($_GET['rent_id']) : 0;
if ($rent_id <= 0) {
    header('Location: my_contracts.php');
    exit;
}

// Fetch rent info
$stmtRent = $conn->prepare("SELECT r.rent_id, r.user_id, u.user_name FROM rent r LEFT JOIN user u ON u.user_id = r.user_id WHERE r.rent_id = ?");
$stmtRent->bind_param('i', $rent_id);
$stmtRent->execute();
$rentRes = $stmtRent->get_result();
if ($rentRes->num_rows === 0) {
    header('Location: my_contracts.php');
    exit;
}
$rent = $rentRes->fetch_assoc();

// Fetch payments
$stmt = $conn->prepare("SELECT payment_id, due_date, amount, status, paid_at, type, slip_file FROM payment WHERE rent_id = ? ORDER BY due_date ASC, payment_id ASC");
$stmt->bind_param('i', $rent_id);
$stmt->execute();
$paymentsRes = $stmt->get_result();
$payments = [];
$totalAmount = 0;
$totalPaid = 0;
while ($row = $paymentsRes->fetch_assoc()) {
    $payments[] = $row;
    $totalAmount += (float)$row['amount'];
    if ($row['status'] === 'ชำระแล้ว') {
        $totalPaid += (float)$row['amount'];
    }
}
$totalRemaining = $totalAmount - $totalPaid;
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>การชำระเงิน - Rent ID: <?= $rent_id ?></title>
</head>

<body class="bg-gradient-to-br from-slate-100 to-slate-300">
    <div class="grid grid-cols-5 grid-rows-1 h-screen gap-2 row-h min-h-screen sticky">
        <div class="col-span-5">
            <?php include('./components/navbar.php') ?>
        </div>
        <div class="col-span-5 z-20 row-span-1 col-start-1 row-start-1 py-3 pr-3 grid grid-cols-5 grid-rows-8 gap-6">
            <div class="col-span-5 row-span-1 bg-white rounded-2xl shadow-lg flex items-center justify-between px-8">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-gradient-to-r from-green-400 to-blue-500 rounded-full flex items-center justify-center">
                        <i class="fas fa-receipt text-white text-lg"></i>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-lg font-semibold text-gray-800">การชำระเงินสัญญา #<?= $rent_id ?></h1>
                        <p class="text-sm text-gray-500">ผู้เช่า: <?= htmlspecialchars($rent['user_name'] ?? '-') ?></p>
                    </div>
                </div>
                <div>
                    <a href="contract_detail.php?rent_id=<?= $rent_id ?>" class="btn btn-ghost"><i class="fas fa-arrow-left mr-2"></i>กลับ</a>
                </div>
            </div>
            <div class="container col-span-5 bg-white rounded row-span-7 mx-auto overflow-auto p-6">
                <?php if (!empty($_SESSION['success'])): ?>
                    <div class="alert alert-success mb-4"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-error mb-4"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <!-- สรุปยอดเงิน -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="stats shadow">
                        <div class="stat">
                            <div class="stat-figure text-primary">
                                <i class="fas fa-money-bill-wave text-3xl"></i>
                            </div>
                            <div class="stat-title">จำนวนเงินสุทธิ</div>
                            <div class="stat-value text-primary"><?= number_format($totalAmount, 2) ?></div>
                            <div class="stat-desc">บาท</div>
                        </div>
                    </div>
                    <div class="stats shadow">
                        <div class="stat">
                            <div class="stat-figure text-success">
                                <i class="fas fa-check-circle text-3xl"></i>
                            </div>
                            <div class="stat-title">ชำระแล้ว</div>
                            <div class="stat-value text-success"><?= number_format($totalPaid, 2) ?></div>
                            <div class="stat-desc">บาท</div>
                        </div>
                    </div>
                    <div class="stats shadow">
                        <div class="stat">
                            <div class="stat-figure text-warning">
                                <i class="fas fa-exclamation-circle text-3xl"></i>
                            </div>
                            <div class="stat-title">คงเหลือ</div>
                            <div class="stat-value text-warning"><?= number_format($totalRemaining, 2) ?></div>
                            <div class="stat-desc">บาท</div>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>กำหนดชำระ</th>
                                <th>จำนวนเงิน</th>
                                <th>ประเภท</th>
                                <th>สถานะ</th>
                                <th>หลักฐาน</th>
                                <th>ดำเนินการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($payments as $p): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= date('d/m/Y', strtotime($p['due_date'])) ?></td>
                                    <td><?= number_format((float)$p['amount'], 2) ?> บาท</td>
                                    <td><?= htmlspecialchars($p['type']) ?></td>
                                    <td>
                                        <?php if ($p['status'] === 'ชำระแล้ว'): ?>
                                            <span class="badge badge-success">ชำระแล้ว</span><br>
                                            <small><?= $p['paid_at'] ? date('d/m/Y H:i', strtotime($p['paid_at'])) : '' ?></small>
                                        <?php else: ?>
                                            <span class="badge badge-warning">ยังไม่ชำระ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($p['slip_file'])): ?>
                                            <a class="link link-primary" target="_blank" href="../<?= htmlspecialchars($p['slip_file']) ?>">ดูไฟล์</a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($p['status'] !== 'ชำระแล้ว'): ?>
                                            <form method="POST" action="payment_pay.php" enctype="multipart/form-data" class="flex items-center gap-2">
                                                <input type="hidden" name="rent_id" value="<?= $rent_id ?>">
                                                <input type="hidden" name="payment_id" value="<?= $p['payment_id'] ?>">
                                                <input type="file" name="slip" accept=".pdf,.jpg,.jpeg,.png" class="file-input file-input-bordered file-input-sm" required>
                                                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-check mr-1"></i>ชำระ</button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-sm" disabled><i class="fas fa-check"></i></button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php include('./../lib/toast.php') ?>
    <script src="./../scripts/main.js"></script>
</body>
</html>
