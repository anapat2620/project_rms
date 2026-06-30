<?php
// Suppress error output to prevent breaking JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();
session_start();

// ตรวจสอบสิทธิ์การเข้าถึง (เฉพาะ admin)
if (!isset($_SESSION['Position']) || $_SESSION['Position'] !== 'Admin') {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit();
}

// ตรวจสอบว่าเป็น POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// การเชื่อมต่อฐานข้อมูล
require_once __DIR__ . '/../config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'การเชื่อมต่อฐานข้อมูลล้มเหลว']);
    exit();
}

// สร้างตารางถ้ายังไม่มี
$create_items_table_sql = "CREATE TABLE IF NOT EXISTS `disbursement_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `fiscal_year` int(11) NOT NULL,
    `description` varchar(500) NOT NULL COMMENT 'รายละเอียดการเบิกจ่าย',
    `amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'จำนวนเงิน',
    `disbursement_date` date NOT NULL COMMENT 'วันที่เบิกจ่าย',
    `created_by` varchar(255) NOT NULL COMMENT 'ผู้สร้าง',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_fiscal_year` (`fiscal_year`),
    KEY `idx_disbursement_date` (`disbursement_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!$conn->query($create_items_table_sql)) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถสร้างตารางได้: ' . $conn->error]);
    exit();
}

// ฟังก์ชันอัปเดตยอดเบิกจ่ายรวม
function updateDisbursementTotal($conn, $fiscal_year) {
    // คำนวณยอดรวมจากรายการทั้งหมด
    $sum_sql = "SELECT SUM(amount) as total FROM disbursement_items WHERE fiscal_year = ?";
    $sum_stmt = $conn->prepare($sum_sql);
    if (!$sum_stmt) {
        return false;
    }
    $sum_stmt->bind_param("i", $fiscal_year);
    $sum_stmt->execute();
    $sum_result = $sum_stmt->get_result();
    $sum_row = $sum_result->fetch_assoc();
    $total_disbursed = floatval($sum_row['total'] ?? 0);
    $sum_stmt->close();
    
    // ตรวจสอบว่ามีข้อมูลใน disbursement_summary หรือไม่
    $check_sql = "SELECT id FROM disbursement_summary WHERE fiscal_year = ?";
    $check_stmt = $conn->prepare($check_sql);
    if (!$check_stmt) {
        return false;
    }
    $check_stmt->bind_param("i", $fiscal_year);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $check_stmt->close();
    
    if ($check_result->num_rows > 0) {
        // อัปเดตข้อมูลที่มีอยู่
        $update_summary_sql = "UPDATE disbursement_summary SET 
                              disbursed_amount = ?,
                              updated_by = ?,
                              updated_at = NOW()
                              WHERE fiscal_year = ?";
        $update_summary_stmt = $conn->prepare($update_summary_sql);
        if (!$update_summary_stmt) {
            return false;
        }
        $update_summary_stmt->bind_param("dsi", $total_disbursed, $_SESSION['Username'], $fiscal_year);
        $result = $update_summary_stmt->execute();
        $update_summary_stmt->close();
        return $result;
    } else {
        // สร้างข้อมูลใหม่ (ใช้ budget_amount = 0 ถ้ายังไม่มี)
        $insert_summary_sql = "INSERT INTO disbursement_summary (fiscal_year, budget_amount, disbursed_amount, updated_by) 
                               VALUES (?, 0, ?, ?)";
        $insert_summary_stmt = $conn->prepare($insert_summary_sql);
        if (!$insert_summary_stmt) {
            return false;
        }
        $insert_summary_stmt->bind_param("ids", $fiscal_year, $total_disbursed, $_SESSION['Username']);
        $result = $insert_summary_stmt->execute();
        $insert_summary_stmt->close();
        return $result;
    }
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'add') {
    // เพิ่มรายการใหม่
    $fiscal_year = isset($_POST['fiscal_year']) ? intval($_POST['fiscal_year']) : (date('Y') + 543);
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $disbursement_date = isset($_POST['disbursement_date']) ? trim($_POST['disbursement_date']) : date('Y-m-d');
    
    // ตรวจสอบข้อมูล
    if (empty($description)) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'กรุณากรอกรายละเอียด']);
        exit();
    }
    
    if ($amount <= 0) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'จำนวนเงินต้องมากกว่า 0']);
        exit();
    }
    
    if (empty($disbursement_date)) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'กรุณาเลือกวันที่เบิกจ่าย']);
        exit();
    }
    
    // เพิ่มรายการ
    $insert_sql = "INSERT INTO disbursement_items (fiscal_year, description, amount, disbursement_date, created_by) 
                   VALUES (?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    if (!$insert_stmt) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        $conn->close();
        exit();
    }
    
    $insert_stmt->bind_param("isdss", $fiscal_year, $description, $amount, $disbursement_date, $_SESSION['Username']);
    
    if ($insert_stmt->execute()) {
        // อัปเดตยอดเบิกจ่ายรวมใน disbursement_summary
        @updateDisbursementTotal($conn, $fiscal_year);
        
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'เพิ่มรายการสำเร็จ']);
        $insert_stmt->close();
        $conn->close();
        exit();
    } else {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถเพิ่มรายการได้: ' . $insert_stmt->error]);
        $insert_stmt->close();
        $conn->close();
        exit();
    }
    
} elseif ($action === 'update') {
    // อัปเดตรายการ
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    $fiscal_year = isset($_POST['fiscal_year']) ? intval($_POST['fiscal_year']) : (date('Y') + 543);
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $disbursement_date = isset($_POST['disbursement_date']) ? trim($_POST['disbursement_date']) : date('Y-m-d');
    
    // ตรวจสอบข้อมูล
    if ($item_id <= 0) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
        exit();
    }
    
    if (empty($description)) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'กรุณากรอกรายละเอียด']);
        exit();
    }
    
    if ($amount <= 0) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'จำนวนเงินต้องมากกว่า 0']);
        exit();
    }
    
    // อัปเดตรายการ
    $update_sql = "UPDATE disbursement_items SET 
                    description = ?, 
                    amount = ?, 
                    disbursement_date = ?,
                    updated_at = NOW()
                    WHERE id = ? AND fiscal_year = ?";
    $update_stmt = $conn->prepare($update_sql);
    if (!$update_stmt) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        $conn->close();
        exit();
    }
    
    $update_stmt->bind_param("sdsii", $description, $amount, $disbursement_date, $item_id, $fiscal_year);
    
    if ($update_stmt->execute()) {
        // อัปเดตยอดเบิกจ่ายรวมใน disbursement_summary
        @updateDisbursementTotal($conn, $fiscal_year);
        
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'อัปเดตรายการสำเร็จ']);
        $update_stmt->close();
        $conn->close();
        exit();
    } else {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถอัปเดตรายการได้: ' . $update_stmt->error]);
        $update_stmt->close();
        $conn->close();
        exit();
    }
    
} elseif ($action === 'delete') {
    // ลบรายการ
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    
    if ($item_id <= 0) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
        exit();
    }
    
    // ดึงข้อมูล fiscal_year ก่อนลบ
    $get_sql = "SELECT fiscal_year FROM disbursement_items WHERE id = ?";
    $get_stmt = $conn->prepare($get_sql);
    $get_stmt->bind_param("i", $item_id);
    $get_stmt->execute();
    $get_result = $get_stmt->get_result();
    
    if ($get_result->num_rows === 0) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่พบรายการที่ต้องการลบ']);
        $get_stmt->close();
        $conn->close();
        exit();
    }
    
    $row = $get_result->fetch_assoc();
    $fiscal_year = $row['fiscal_year'];
    $get_stmt->close();
    
    // ลบรายการ
    $delete_sql = "DELETE FROM disbursement_items WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $item_id);
    
    if ($delete_stmt->execute()) {
        // อัปเดตยอดเบิกจ่ายรวมใน disbursement_summary
        @updateDisbursementTotal($conn, $fiscal_year);
        
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'ลบรายการสำเร็จ']);
        $delete_stmt->close();
        $conn->close();
        exit();
    } else {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถลบรายการได้: ' . $delete_stmt->error]);
        $delete_stmt->close();
        $conn->close();
        exit();
    }
    
} else {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    $conn->close();
    exit();
}

// Should never reach here, but just in case
ob_clean();
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Unexpected error']);
$conn->close();
exit();
?>
