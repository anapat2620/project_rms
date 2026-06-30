<?php
session_start();

// ตรวจสอบสิทธิ์การเข้าถึง (เฉพาะ admin)
if (!isset($_SESSION['Position']) || $_SESSION['Position'] !== 'Admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit();
}

// ตรวจสอบว่าเป็น POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// การเชื่อมต่อฐานข้อมูล
require_once __DIR__ . '/../config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'การเชื่อมต่อฐานข้อมูลล้มเหลว']);
    exit();
}

// รับข้อมูลจาก POST
$budget_amount = isset($_POST['budget_amount']) ? floatval($_POST['budget_amount']) : 0;
$fiscal_year = isset($_POST['fiscal_year']) ? intval($_POST['fiscal_year']) : (date('Y') + 543);

// คำนวณยอดเบิกจ่ายรวมจากรายการทั้งหมด (ไม่ต้องรับจาก POST)
$disbursed_amount = 0;
$sum_items_sql = "SELECT SUM(amount) as total FROM disbursement_items WHERE fiscal_year = ?";
$sum_items_stmt = $conn->prepare($sum_items_sql);
if ($sum_items_stmt) {
    $sum_items_stmt->bind_param("i", $fiscal_year);
    $sum_items_stmt->execute();
    $sum_items_result = $sum_items_stmt->get_result();
    if ($sum_items_result->num_rows > 0) {
        $sum_row = $sum_items_result->fetch_assoc();
        $disbursed_amount = floatval($sum_row['total'] ?? 0);
    }
    $sum_items_stmt->close();
}

// ตรวจสอบข้อมูล
if ($budget_amount < 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'จำนวนเงินต้องมากกว่าหรือเท่ากับ 0']);
    exit();
}

// สร้างตารางถ้ายังไม่มี
$create_table_sql = "CREATE TABLE IF NOT EXISTS `disbursement_summary` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `fiscal_year` int(11) NOT NULL,
    `budget_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
    `disbursed_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
    `updated_by` varchar(255) NOT NULL,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_fiscal_year` (`fiscal_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!$conn->query($create_table_sql)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถสร้างตารางได้: ' . $conn->error]);
    exit();
}

// ตรวจสอบว่ามีข้อมูลสำหรับปีงบประมาณนี้หรือไม่
$check_sql = "SELECT id FROM disbursement_summary WHERE fiscal_year = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $fiscal_year);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    // อัปเดตข้อมูลที่มีอยู่ (disbursed_amount จะคำนวณจากรายการ)
    $update_sql = "UPDATE disbursement_summary SET 
                    budget_amount = ?, 
                    disbursed_amount = ?, 
                    updated_by = ?,
                    updated_at = NOW()
                    WHERE fiscal_year = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ddsi", $budget_amount, $disbursed_amount, $_SESSION['Username'], $fiscal_year);
    
    if ($update_stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'อัปเดตข้อมูลสำเร็จ']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถอัปเดตข้อมูลได้: ' . $conn->error]);
    }
    $update_stmt->close();
} else {
    // เพิ่มข้อมูลใหม่ (disbursed_amount จะคำนวณจากรายการ)
    $insert_sql = "INSERT INTO disbursement_summary (fiscal_year, budget_amount, disbursed_amount, updated_by) 
                   VALUES (?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("idds", $fiscal_year, $budget_amount, $disbursed_amount, $_SESSION['Username']);
    
    if ($insert_stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'บันทึกข้อมูลสำเร็จ']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถบันทึกข้อมูลได้: ' . $conn->error]);
    }
    $insert_stmt->close();
}

$check_stmt->close();
$conn->close();
?>

