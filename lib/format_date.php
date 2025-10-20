<?php
function formatThaiShortDateTime($dateStr) {
    $date = new DateTime($dateStr);

    $day = $date->format('j'); // 1-31
    $month = (int)$date->format('n'); // 1-12
    $year = (int)$date->format('Y') + 543; // พ.ศ.
    $time = $date->format('H:i'); // 24 ชั่วโมง เช่น 14:30

    $monthNames = [
        '', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
        'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'
    ];

    return "$day {$monthNames[$month]} $year ";
}

function calculateDateDiffThai($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $diff = $start->diff($end);

    // ถ้าวันเท่ากัน = 1 วัน
    $days = $diff->days + 1;

    return $days . ' วัน';
}

?>
