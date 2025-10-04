<?php
function uploadfile($files) {
    $targetDir = "../image/";
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    $uploadedPaths = [];
    $errorMessage = null;

    foreach ($files['name'] as $key => $name) {
        $tmpName = $files['tmp_name'][$key];
        $size = $files['size'][$key];
        $error = $files['error'][$key];

        if ($error !== UPLOAD_ERR_OK) {
            $errorMessage = "เกิดข้อผิดพลาดในการอัปโหลดไฟล์ $name";
            continue;
        }

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedTypes)) {
            $errorMessage = "ไฟล์ $name ไม่ใช่รูปภาพที่รองรับ";
            continue;
        }

        if ($size > 5 * 1024 * 1024) {
            $errorMessage = "ไฟล์ $name มีขนาดเกิน 5MB";
            continue;
        }

        $newName = uniqid("img_") . "." . $ext;
        $savePath = $targetDir . $newName;

        if (move_uploaded_file($tmpName, $savePath)) {
            $uploadedPaths[] = $savePath;
        } else {
            $errorMessage = "ไม่สามารถบันทึกไฟล์ $name ได้";
        }
    }

    return [
        'path' => $uploadedPaths,
        'error' => $errorMessage
    ];
}

