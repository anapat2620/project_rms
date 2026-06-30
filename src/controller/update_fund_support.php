<?php
// Prevent any output before JSON
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

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'update') {
    // อัปเดตข้อมูลที่มีอยู่
    $funID = isset($_POST['FunID']) ? trim($_POST['FunID']) : '';
    $funName = isset($_POST['FunName']) ? trim($_POST['FunName']) : '';
    $bh1 = isset($_POST['BH1']) ? intval($_POST['BH1']) : 0;
    $bh2 = isset($_POST['BH2']) ? intval($_POST['BH2']) : 0;
    $b3 = isset($_POST['B3']) ? intval($_POST['B3']) : 0;
    $thBath = isset($_POST['TH_Bath']) ? floatval($_POST['TH_Bath']) : 0;
    $year = isset($_POST['Year']) ? intval($_POST['Year']) : (date('Y') + 543);
    
    // ตรวจสอบข้อมูล
    if (empty($funID) || empty($funName)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'รหัสทุนและชื่อทุนต้องไม่ว่าง']);
        exit();
    }
    
    if ($bh1 < 0 || $bh1 > 100 || $bh2 < 0 || $bh2 > 100 || $b3 < 0 || $b3 > 100) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ค่า BH1, BH2, B3 ต้องอยู่ระหว่าง 0-100']);
        exit();
    }
    
    if ($thBath < 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'จำนวนเงินต้องมากกว่าหรือเท่ากับ 0']);
        exit();
    }
    
    if ($year < 2500 || $year > 2600) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ปีต้องอยู่ระหว่าง 2500-2600']);
        exit();
    }
    
    // ตรวจสอบว่ามีข้อมูลหรือไม่
    $check_sql = "SELECT FunID FROM fund_support WHERE FunID = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $funID);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // อัปเดตข้อมูลที่มีอยู่
        $update_sql = "UPDATE fund_support SET 
                        FunName = ?, 
                        BH1 = ?, 
                        BH2 = ?, 
                        B3 = ?, 
                        TH_Bath = ?, 
                        Year = ?
                        WHERE FunID = ?";
        $update_stmt = $conn->prepare($update_sql);
        if (!$update_stmt) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
            $check_stmt->close();
            $conn->close();
            exit();
        }
        // Format: FunName(string), BH1(int), BH2(int), B3(int), TH_Bath(double), Year(int), FunID(string) = 7 parameters
        // Format string: s-i-i-i-d-i-s = 7 characters
        // Parameters: FunName(s), BH1(i), BH2(i), B3(i), TH_Bath(d), Year(i), FunID(s)
        // Correct format: "siiidiss" (7 chars: s-i-i-i-d-i-s, NOT 8!)
        // Using substr to ensure exactly 7 characters
        $formatStr = substr("siiidiss", 0, 7);
        $bind_result = $update_stmt->bind_param($formatStr, $funName, $bh1, $bh2, $b3, $thBath, $year, $funID);
        if (!$bind_result) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Bind failed: ' . $update_stmt->error]);
            $update_stmt->close();
            $check_stmt->close();
            $conn->close();
            exit();
        }
        
        if ($update_stmt->execute()) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'อัปเดตข้อมูลทุนสนับสนุนสำเร็จ']);
        } else {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ไม่สามารถอัปเดตข้อมูลได้: ' . $update_stmt->error]);
        }
        $update_stmt->close();
    } else {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลทุนสนับสนุนที่ต้องการอัปเดต']);
    }
    $check_stmt->close();
    
} elseif ($action === 'add') {
    // เพิ่มข้อมูลใหม่
    $funID = isset($_POST['FunID']) ? trim($_POST['FunID']) : '';
    $funName = isset($_POST['FunName']) ? trim($_POST['FunName']) : '';
    $bh1 = isset($_POST['BH1']) ? intval($_POST['BH1']) : 0;
    $bh2 = isset($_POST['BH2']) ? intval($_POST['BH2']) : 0;
    $b3 = isset($_POST['B3']) ? intval($_POST['B3']) : 0;
    $thBath = isset($_POST['TH_Bath']) ? floatval($_POST['TH_Bath']) : 0;
    $year = isset($_POST['Year']) ? intval($_POST['Year']) : (date('Y') + 543);
    
    // ตรวจสอบข้อมูล
    if (empty($funID) || empty($funName)) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'รหัสทุนและชื่อทุนต้องไม่ว่าง']);
        exit();
    }
    
    if ($bh1 < 0 || $bh1 > 100 || $bh2 < 0 || $bh2 > 100 || $b3 < 0 || $b3 > 100) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ค่า BH1, BH2, B3 ต้องอยู่ระหว่าง 0-100']);
        exit();
    }
    
    if ($thBath < 0) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'จำนวนเงินต้องมากกว่าหรือเท่ากับ 0']);
        exit();
    }
    
    if ($year < 2500 || $year > 2600) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ปีต้องอยู่ระหว่าง 2500-2600']);
        exit();
    }
    
    // ตรวจสอบว่ามีรหัสทุนซ้ำหรือไม่
    $check_sql = "SELECT FunID FROM fund_support WHERE FunID = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $funID);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'รหัสทุนนี้มีอยู่ในระบบแล้ว']);
        $check_stmt->close();
        $conn->close();
        exit();
    }
    $check_stmt->close();
    
    // เพิ่มข้อมูลใหม่
    $insert_sql = "INSERT INTO fund_support (FunID, FunName, BH1, BH2, B3, TH_Bath, Year) 
                   VALUES (?, ?, ?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    if (!$insert_stmt) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        $conn->close();
        exit();
    }
    $insert_stmt->bind_param("ssiiidi", $funID, $funName, $bh1, $bh2, $b3, $thBath, $year);
    
    if ($insert_stmt->execute()) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'เพิ่มข้อมูลทุนสนับสนุนสำเร็จ']);
    } else {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถเพิ่มข้อมูลได้: ' . $insert_stmt->error]);
    }
    $insert_stmt->close();
    
} else {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();
?>
