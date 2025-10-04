<?php
// ตรวจสอบว่าเป็นหน้าที่ต้องการ redirect หรือไม่
$current_page = basename($_SERVER['PHP_SELF']); // เช่น index.php

// สมมติว่าหน้านี้คือหน้าที่ยังไม่พร้อมใช้งาน
if ($current_page == 'index.php') {
    header("Location: ../../home.php"); // ส่งไปหน้า dashboard
    exit(); // หยุดการทำงานของสคริปต์
}
?>
