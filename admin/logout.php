<?php
session_start();

// ล้าง session ทั้งหมด
$_SESSION = [];
session_destroy();

// กลับไปหน้าแรก หรือ login
header('Location: login.php');
exit;
