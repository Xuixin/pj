<?php
session_start();
require_once('./../conn.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// รับค่าจาก $_POST แทน JSON
$user_id = $_POST['user_id'] ?? null;
$start_date = $_POST['start_date'] ?? null;
$return_date = $_POST['return_date'] ?? null;
$cart_data = $_POST['cart_data'] ?? null;
$backup_cart_data = $_POST['backup_cart_data'] ?? null;

$paymentType = $_POST['payment_type'] ?? '';
$installmentCount = isset($_POST['installment_count']) ? (int)$_POST['installment_count'] : 0;

// คำนวณจำนวนเดือนของสัญญาเช่า + normalize ถ้าเลือกเป็นรายเดือนเกิน 16 เดือน ให้เป็นรายปี
$rentType = $_POST['rent_type'] ?? '';
$rentDuration = isset($_POST['rent_duration']) ? (int)$_POST['rent_duration'] : 0; // จำนวนเดือนถ้า monthly, จำนวนปีถ้า yearly
$rentMonths = 0;
$rentYears = 0;

// Normalize: monthly >= 16 -> yearly (ปัดขึ้นเป็นปี)
if ($rentType === 'monthly' && $rentDuration >= 16) {
    $rentType = 'yearly';
    $rentYears = (int)ceil($rentDuration / 12);
}

if ($rentType === 'monthly') {
    // ยังเป็นรายเดือนปกติ
    $rentMonths = $rentDuration;
} elseif ($rentType === 'yearly') {
    // ถ้า normalize แล้วจะมี $rentYears, ถ้าเดิมเป็น yearly จะใช้ $rentDuration เป็นจำนวนปี
    $years = $rentYears > 0 ? $rentYears : $rentDuration;
    $rentMonths = $years * 12;
}

if ($paymentType !== 'installment') {
    $installmentCount = 0;
}

// Persist only required keys for next steps
$_SESSION['payment_type'] = $paymentType;
$_SESSION['installment_months'] = $installmentCount;
$_SESSION['rent_months'] = $rentMonths;
$_SESSION['total_amount'] = isset($_POST['total_amount']) ? (int)$_POST['total_amount'] : 0;
// Ensure old keys are not left around
unset($_SESSION['rent_type'], $_SESSION['rent_years']);


// Decode carts early and recompute total before building payment schedule
$cart = json_decode($cart_data, true);
$backup_cart = json_decode($backup_cart_data, true) ?? [];

// Server-side fallback: recalc total from cart and months if client total is invalid/zero
$recalcTotal = 0;
if (is_array($cart)) {
    foreach ($cart as $item) {
        $price = isset($item['price_per_month']) ? (float)$item['price_per_month'] : 0;
        $qty = isset($item['quantity']) ? (int)$item['quantity'] : 0;
        $recalcTotal += $price * $qty * max(0, (int)($_SESSION['rent_months'] ?? 0));
    }
}
// backup cart is free
if ((int)($_SESSION['total_amount'] ?? 0) === 0 && $recalcTotal > 0) {
    $_SESSION['total_amount'] = (int)$recalcTotal;
}

// สร้างตารางกำหนดชำระ (วันที่ต้องจ่าย และจำนวนเงิน)
$paymentSchedule = [];
$totalAmount = (int)($_SESSION['total_amount'] ?? 0);
if ($totalAmount < 0) {
    $totalAmount = 0;
}

if ($paymentType === 'installment' && $installmentCount > 0) {
    // กระจายงวดให้ครบก่อนวันสิ้นสุดสัญญา (วันสุดท้าย = 1 วันก่อน return_date)
    $startDt = DateTime::createFromFormat('Y-m-d', $start_date) ?: new DateTime();
    $endDt = DateTime::createFromFormat('Y-m-d', $return_date) ?: clone $startDt;
    $lastDueDt = clone $endDt;
    $lastDueDt->modify('-1 day');
    if ($lastDueDt < $startDt) {
        $lastDueDt = clone $startDt;
    }

    $startTs = $startDt->getTimestamp();
    $endTs = $lastDueDt->getTimestamp();
    $range = max(0, $endTs - $startTs);

    // คำนวณยอดต่องวดแบบเพดาน แล้วปรับงวดสุดท้ายให้พอดีกับยอดรวม
    $perInstallment = (int)ceil($totalAmount / $installmentCount);
    $runningTotal = 0;
    $prevDueTs = null;

    for ($i = 0; $i < $installmentCount; $i++) {
        if ($installmentCount === 1) {
            $due = clone $lastDueDt;
        } else if ($i === $installmentCount - 1) {
            $due = clone $lastDueDt; // งวดสุดท้ายก่อนวันคืน
        } else {
            // กระจายแบบ linear ระหว่าง start..lastDue
            $ratio = $i / ($installmentCount - 1);
            $ts = (int)floor($startTs + $ratio * $range);
            $due = (new DateTime())->setTimestamp($ts);
        }

        // ให้วันครบถ้วนและไล่เรียงกัน (อย่างน้อย +1 วันจากงวดก่อน)
        if ($prevDueTs !== null) {
            if ($due->getTimestamp() <= $prevDueTs) {
                $due = (new DateTime())->setTimestamp($prevDueTs);
                $due->modify('+1 day');
                if ($due > $lastDueDt) {
                    $due = clone $lastDueDt;
                }
            }
        }
        $prevDueTs = $due->getTimestamp();

        // ปรับยอดงวดสุดท้าย
        $amount = ($i < $installmentCount - 1) ? $perInstallment : max(0, $totalAmount - $runningTotal);
        $runningTotal += $amount;

        $paymentSchedule[] = [
            'installment' => $i + 1,
            'due_date' => $due->format('Y-m-d'),
            'amount' => $amount,
        ];
    }
} else {
    // ชำระครั้งเดียวเต็มจำนวน ที่วันเริ่มสัญญา
    $dt = DateTime::createFromFormat('Y-m-d', $start_date) ?: new DateTime();
    $paymentSchedule[] = [
        'installment' => 1,
        'due_date' => $dt->format('Y-m-d'),
        'amount' => $totalAmount,
    ];
}

$_SESSION['payment_schedule'] = $paymentSchedule;

if (!$user_id || !$start_date || !$return_date || (!$cart && !$backup_cart)) {
    echo "ข้อมูลไม่ครบหรือไม่ถูกต้อง";
    exit;
}


// เตรียมเก็บ serial ที่ได้
$selected_serials = [];
$selected_backup_serials = [];


// Process regular devices
if ($cart) {
    foreach ($cart as $item) {
        $model_id = $item['model_id'];
        $quantity = $item['quantity'];

        $sql = "SELECT device_id, serial_number FROM device WHERE model_id = ? AND status = 'ว่าง' LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $model_id, $quantity);
        $stmt->execute();
        $result = $stmt->get_result();

        $serials = [];
        while ($row = $result->fetch_assoc()) {
            $serials[] = $row;
        }

        // ถ้า serial ไม่พอ
        if (count($serials) < $quantity) {
            echo "อุปกรณ์โมเดล {$item['model_name']} มีไม่เพียงพอ";
            exit;
        }

        $selected_serials[] = [
            'brand_id' => $item['brand_id'],
            'brand_name' => $item['brand_name'],
            'model_id' => $model_id,
            'model_name' => $item['model_name'],
            'quantity' => $quantity,
            'serials' => $serials,
            'is_backup' => false
        ];
    }
}

// Process backup devices
if ($backup_cart) {
    foreach ($backup_cart as $item) {
        $model_id = $item['model_id'];
        $quantity = $item['quantity'];

        $sql = "SELECT device_id, serial_number FROM device WHERE model_id = ? AND status = 'ว่าง' LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $model_id, $quantity);
        $stmt->execute();
        $result = $stmt->get_result();

        $serials = [];
        while ($row = $result->fetch_assoc()) {
            $serials[] = $row;
        }

        // ถ้า serial ไม่พอ
        if (count($serials) < $quantity) {
            echo "อุปกรณ์สำรองโมเดล {$item['model_name']} มีไม่เพียงพอ";
            exit;
        }

        $selected_backup_serials[] = [
            'brand_id' => $item['brand_id'],
            'brand_name' => $item['brand_name'],
            'model_id' => $model_id,
            'model_name' => $item['model_name'],
            'quantity' => $quantity,
            'serials' => $serials,
            'is_backup' => true
        ];
    }
}

// เก็บข้อมูลไว้ใน session เพื่อส่งไปหน้า generate contract
$_SESSION['rental_preview'] = [
    'user_id' => $user_id,
    'start_date' => $start_date,
    'return_date' => $return_date,
    'items' => $selected_serials,
    'backup_items' => $selected_backup_serials
];

// echo "<pre>";
// echo json_encode($_SESSION['rental_preview'], JSON_PRETTY_PRINT);
// echo "</pre>";

// Redirect ไปหน้า generate_contract.php
header('Location: generate_contract.php');
exit;
