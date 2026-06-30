<?php
function updateQuantity($email) {
    // เชื่อมต่อกับฐานข้อมูล research_db
    require_once __DIR__ . '/../config.php';

    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            throw new Exception("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
        }

        // อัพเดท Quantity โดยเพิ่มค่าขึ้น 1
        $sql = "UPDATE data SET Quantity = Quantity + 1 WHERE Email = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("การเตรียมคำสั่ง SQL ล้มเหลว: " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        $result = $stmt->execute();

        if (!$result) {
            throw new Exception("การอัพเดท Quantity ล้มเหลว: " . $stmt->error);
        }

        // อัพเดท session Quantity
        if (isset($_SESSION['Quantity'])) {
            $_SESSION['Quantity']++;
        }

        $stmt->close();
        $conn->close();

        return true;
    } catch (Exception $e) {
        error_log("Error updating quantity: " . $e->getMessage());
        return false;
    }
} 