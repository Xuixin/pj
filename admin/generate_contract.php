<?php
session_start();
include('./../conn.php');
include('./../lib/format_date.php');


// ตรวจสอบ session
if (!isset($_SESSION['rental_preview'])) {
    echo "ไม่พบข้อมูลการเช่า";
    exit;
}

$rental = $_SESSION['rental_preview'];

// ดึงชื่อผู้ใช้
$user_id = $rental['user_id'];
$user_result = mysqli_query($conn, "SELECT user_name, phone FROM user WHERE user_id = $user_id");
$user_row = mysqli_fetch_assoc($user_result);
$username = $user_row['user_name'] ?? 'ไม่พบชื่อผู้ใช้';
$user_phone = $user_row['user_phone'] ?? '';


// สร้างเลขที่สัญญา
$contract_number = 'CON-' . date('Y') . '-' . str_pad($rental['rental_id'] ?? rand(1000, 9999), 4, '0', STR_PAD_LEFT);

// คำนวณจำนวนอุปกรณ์รวม
$total_items = 0;
$backup_items = 0;
$equipment_summary_bakup = [];
$equipment_summary = [];
foreach ($rental['items'] as $item) {
    $total_items += $item['quantity'];
    $key = $item['brand_name'] . ' - ' . $item['model_name'];
    if (isset($equipment_summary[$key])) {
        $equipment_summary[$key] += $item['quantity'];
    } else {
        $equipment_summary[$key] = $item['quantity'];
    }
}

foreach ($rental['backup_items'] as $item) {
    $total_items += $item['quantity'];
    $key = $item['brand_name'] . ' - ' . $item['model_name'];
    if (isset($equipment_summary_bakup[$key])) {
        $equipment_summary_bakup[$key] += $item['quantity'];
    } else {
        $equipment_summary_bakup[$key] = $item['quantity'];
    }
}

// ข้อมูลการชำระเงินจาก session
$rent_months = $_SESSION['rent_months'] ?? null;
$total_amount = isset($_SESSION['total_amount']) ? (int)$_SESSION['total_amount'] : null;
$payment_type = $_SESSION['payment_type'] ?? null; // 'full' | 'installment'
$installment_months = isset($_SESSION['installment_months']) ? (int)$_SESSION['installment_months'] : 0;
$payment_schedule = $_SESSION['payment_schedule'] ?? [];
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สัญญาเช่าอุปกรณ์</title>
    <style>
        /* @font-face {
            font-family: 'Sarabun', sans-serif;
        } */

        @media print {
            body {
                font-size: 14px;
            }

            .no-print {
                display: none;
            }
        }

        body {
            font-family: 'Sarabun', Arial, sans-serif;
            line-height: 1.5;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
            font-size: 14px;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .contract-title {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 3px solid #007bff;
        }

        .equipment-summary {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .equipment-summary h4 {
            margin: 0 0 10px 0;
            color: #333;
        }

        .equipment-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px dotted #ccc;
        }

        .equipment-item:last-child {
            border-bottom: none;
        }

        .terms {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 25px;
            font-size: 13px;
        }

        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 40px;
        }

        .signature-box {
            text-align: center;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
        }

        .signature-line {
            border-bottom: 1px solid #333;
            height: 50px;
            margin: 15px 0;
        }

        .print-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .print-btn:hover {
            background: #0056b3;
        }

        .highlight {
            background: #fff3cd;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }

        .total-highlight {
            background: #d4edda;
            color: #155724;
            padding: 8px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            font-size: 16px;
        }

        .total-highlight-backup {
            background: rgb(236, 237, 212);
            color: rgb(76, 87, 21);
            padding: 8px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            font-size: 16px;
        }
    </style>
</head>

<body>
    <div class="container" id="contract-content">
        <button class="print-btn no-print" onclick="window.print()">
            🖨️ พิมพ์สัญญา
        </button>

        <form method="post" action="save_contract_pdf.php">
            <button class="print-btn no-print" type="submit">
                💾 ยืนยันการทำสัญญา
            </button>
        </form>


        <div class="header">
            <div class="contract-title">สัญญาเช่าอุปกรณ์</div>
            <div>เลขที่: <?= $contract_number ?> | วันที่: <?= date('d/m/Y') ?></div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <strong>ผู้ให้เช่า:</strong> [ชื่อองค์กร]<br>
                <strong>ผู้อนุมัติ:</strong> 
            </div>
            <div class="info-box">
                <strong>ผู้เช่า:</strong> <?= htmlspecialchars($username) ?><br>
                <?php if ($user_phone): ?>
                    <strong>โทร:</strong> <?= htmlspecialchars($user_phone) ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <strong>วันที่เริ่มเช่า:</strong><br>
                <span class="highlight"><?= formatThaiShortDateTime($rental['start_date']) ?></span>
            </div>
            <div class="info-box">
                <strong>วันที่คืน:</strong><br>
                <span class="highlight"><?= formatThaiShortDateTime($rental['return_date']) ?></span>
            </div>
        </div>

        <div class="equipment-summary">
            <h4>📋 รายการอุปกรณ์ที่เช่า</h4>
            <?php foreach ($equipment_summary as $equipment => $quantity): ?>
                <div class="equipment-item">
                    <span><?= htmlspecialchars($equipment) ?></span>
                    <strong><?= $quantity ?> เครื่อง</strong>
                </div>
            <?php endforeach; ?>
            <h4 style="padding-top: 2;">📋 รายการอุปกรณ์สำรอง</h4>
            <?php foreach ($equipment_summary_bakup as $equipment => $quantity): ?>
                <div class="equipment-item">
                    <span><?= htmlspecialchars($equipment) ?></span>
                    <strong><?= $quantity ?> เครื่อง</strong>
                </div>
            <?php endforeach; ?>

            <div class="total-highlight" style="margin-top: 10px;">
                รวมทั้งหมด: <?= $total_items ?> เครื่อง
            </div>

            <div style="margin-top: 10px; font-size: 12px; color: #666;">
                * รายละเอียดหมายเลขเครื่อง (Serial Number) อ้างอิงตามเอกสารแนบ
            </div>
        </div>

        <!-- Payment Summary -->
        <div class="equipment-summary">
            <h4>💳 สรุปการชำระเงิน</h4>
            <div class="equipment-item">
                <span>ระยะเวลาเช่า (จำนวนเดือน)</span>
                <strong><?= $rent_months !== null ? intval($rent_months) . ' เดือน' : '-' ?></strong>
            </div>
            <div class="equipment-item">
                <span>ยอดรวมทั้งหมด</span>
                <strong><?= $total_amount !== null ? number_format($total_amount) . ' บาท' : '-' ?></strong>
            </div>
            <div class="equipment-item">
                <span>รูปแบบการชำระ</span>
                <strong>
                    <?php if ($payment_type === 'installment'): ?>ผ่อนชำระ<?php elseif ($payment_type === 'full'): ?>ชำระเต็มจำนวน<?php else: ?>-<?php endif; ?>
                </strong>
            </div>
            <?php if ($payment_type === 'installment'): ?>
                <div class="equipment-item">
                    <span>จำนวนงวด</span>
                    <strong><?= $installment_months ?> งวด</strong>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($payment_schedule)): ?>
            <div class="equipment-summary">
                <h4>📆 ตารางกำหนดชำระ</h4>
                <?php foreach ($payment_schedule as $row): ?>
                    <div class="equipment-item">
                        <?php if ($payment_type === 'installment') { ?>
                            <span>งวดที่ <?= intval($row['installment']) ?> | กำหนดชำระ: <?= htmlspecialchars(date('d/m/Y', strtotime($row['due_date']))) ?></span>
                        <?php } else { ?>
                            <span>จ่ายเต็มจำนวน | กำหนดชำระ: <?= htmlspecialchars(date('d/m/Y', strtotime($row['due_date']))) ?></span>
                        <?php } ?>
                        <strong><?= number_format((int)$row['amount']) ?> บาท</strong>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="terms">
            <strong>📝 เงื่อนไขสำคัญ:</strong><br>
            1. <strong>ดูแลรักษา:</strong> ผู้เช่าต้องดูแลอุปกรณ์ด้วยความระมัดระวัง<br>
            2. <strong>คืนตามกำหนด:</strong> ส่งคืนตามวันเวลาที่กำหนดในสภาพดี<br>
            3. <strong>ค่าเสียหาย:</strong> รับผิดชอบค่าเสียหายหากอุปกรณ์เสียหายหรือสูญหาย<br>
            4. <strong>ข้อห้าม:</strong> ห้ามโอนหรือให้เช่าช่วงโดยไม่ได้รับอนุญาต<br>
            5. <strong>การยกเลิก:</strong> หากผิดสัญญา ผู้ให้เช่าสามารถยกเลิกสัญญาได้ทันที
        </div>

        <div style="text-align: center; margin: 20px 0; font-weight: bold;">
            คู่สัญญาได้อ่านและเข้าใจเงื่อนไขแล้ว จึงได้ลงลายมือชื่อไว้เป็นสำคัญ
        </div>

        <div class="signature-section">
            <div class="signature-box">
                <div><strong>ผู้ให้เช่า</strong></div>
                <div class="signature-line"></div>
                <div>( )</div>
                <div style="margin-top: 10px;">วันที่ ....../....../......</div>
            </div>

            <div class="signature-box">
                <div><strong>ผู้เช่า</strong></div>
                <div class="signature-line"></div>
                <div>( <?= htmlspecialchars($username) ?> )</div>
                <div style="margin-top: 10px;">วันที่ ....../....../......</div>
            </div>
        </div>

        <div style="margin-top: 20px; padding: 10px; background: #e9ecef; border-radius: 5px; font-size: 12px; color: #6c757d;">
            <strong>หมายเหตุ:</strong> สัญญาฉบับนี้จัดทำ 2 ฉบับ คู่สัญญาถือไว้ฝ่ายละ 1 ฉบับ |
            รายละเอียดครุภัณฑ์และหมายเลขเครื่องดูเอกสารแนบ
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

</body>

</html>