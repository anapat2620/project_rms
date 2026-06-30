<?php
session_start();

// Debug: ตรวจสอบค่าของ SESSION
if (!isset($_SESSION['Email'])) {
    header("Location: ../../login/index1.html"); // ถ้าไม่ได้ล็อกอิน ให้ไปที่หน้า Login
    exit();
}

// Debug: แสดงค่า SESSION เพื่อเช็คว่ามีค่า Position หรือไม่
if (!isset($_SESSION['Position'])) {
    die("Session 'Position' ไม่มีค่า กรุณาตรวจสอบการตั้งค่า Session");
}

// ตำแหน่งที่ได้รับอนุญาต
$allowed_positions = ['อธิการบดี']; 

if (!in_array($_SESSION['Position'], $allowed_positions)) {
    echo "<script>
            alert('คุณไม่ได้รับอนุญาตให้เข้าใช้งานฟังก์ชันนี้');
            window.location.href = 'index.php'; 
          </script>";
    exit();
}

// ❌ **ลบ** header("Location: admin_dashboard.php"); ออก  
// ✅ เพราะถ้าผู้ใช้ได้รับอนุญาต หน้า `admin_dashboard.php` จะโหลดเอง
?>
