<?php
// ฟังก์ชันสำหรับจัดการการอัพโหลดไฟล์
function handleFileUpload($file, $targetDir, $allowedTypes = ['pdf']) {
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

    // สร้างชื่อไฟล์ใหม่เพื่อป้องกันการซ้ำ
    $newFileName = uniqid() . '_' . date('Ymd_His') . '.' . $fileType;
    $targetPath = $targetDir . '/' . $newFileName;

    // สร้างโฟลเดอร์ถ้ายังไม่มี
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // ย้ายไฟล์ไปยังโฟลเดอร์ปลายทาง
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception("ไม่สามารถย้ายไฟล์ที่อัพโหลดได้");
    }

    return $targetPath;
} 