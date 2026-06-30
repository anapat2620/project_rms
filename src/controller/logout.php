<?php
session_start();

// ล้างข้อมูล session ทั้งหมด
session_unset();
session_destroy();

// redirect ไปยังหน้า login
header("Location: ../../login/index1.html");
exit();
?>
