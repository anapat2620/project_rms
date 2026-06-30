<?php
// ฟังก์ชันสำหรับจัดการการอัพโหลดไฟล์
function handleFileUpload($file, $targetDir, $allowedTypes = ['pdf'], $prefix = null) {
    // ถ้าไม่มีไฟล์ถูกอัพโหลด
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    // ตรวจสอบข้อผิดพลาดในการอัพโหลด
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("เกิดข้อผิดพลาดในการอัพโหลดไฟล์: " . $file['error']);
    }

    // ตรวจสอบประเภทไฟล์
    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception("ไม่อนุญาตให้อัพโหลดไฟล์ประเภทนี้ อนุญาตเฉพาะ: " . implode(', ', $allowedTypes));
    }

    // สร้างโฟลเดอร์ถ้ายังไม่มี
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // สร้างชื่อไฟล์ใหม่ตาม prefix และเลขลำดับ
    if ($prefix) {
        $prefix = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $prefix));
        $pattern = $targetDir . '/' . $prefix . '-*.' . $fileType;
        $existingFiles = glob($pattern);
        $maxIndex = 0;
        foreach ($existingFiles as $existingFile) {
            $baseName = pathinfo($existingFile, PATHINFO_FILENAME);
            if (preg_match('/' . preg_quote($prefix, '/') . '-(\d{3})$/', $baseName, $matches)) {
                $maxIndex = max($maxIndex, intval($matches[1]));
            }
        }
        $nextIndex = str_pad($maxIndex + 1, 3, '0', STR_PAD_LEFT);
        $newFileName = $prefix . '-' . $nextIndex . '.' . $fileType;
    } else {
        $newFileName = uniqid() . '_' . date('Ymd_His') . '.' . $fileType;
    }

    $targetPath = $targetDir . '/' . $newFileName;

    // ย้ายไฟล์ไปยังโฟลเดอร์ปลายทาง
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception("ไม่สามารถย้ายไฟล์ที่อัพโหลดได้");
    }

    return $targetPath;
} 