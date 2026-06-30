<?php
session_start();

require_once __DIR__ . '/../src/config.php';

// สร้างการเชื่อมต่อฐานข้อมูล
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

// รับค่าจากฟอร์ม POST
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

// ตรวจสอบข้อมูลเบื้องต้น
if (empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกอีเมลและรหัสผ่านให้ครบถ้วน']);
    exit();
}

// เตรียม SQL statement (ตรวจสอบเฉพาะ Email และ Password)
$sql = "SELECT ID, Email, Username, Facuity, Position, Quantity FROM data WHERE Email = ? AND Password = ?";

// เตรียมคำสั่ง
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare statement ล้มเหลว: " . $conn->error);
}

// ผูกค่าพารามิเตอร์
$stmt->bind_param("ss", $email, $password);

// รันคำสั่ง
$stmt->execute();

// รับผลลัพธ์
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // ดึงข้อมูลผู้ใช้
    $user = $result->fetch_assoc();

    // เก็บข้อมูลลง session
    $_SESSION['ID'] = $user['ID'];
    $_SESSION['Email'] = $user['Email'];
    $_SESSION['Username'] = $user['Username'];
    $_SESSION['Facuity'] = $user['Facuity'];
    $_SESSION['Position'] = $user['Position'];
    $_SESSION['Quantity'] = $user['Quantity'];

    // ตรวจสอบว่าเป็น admin หรือไม่
    $admin_positions = ['Admin'];
    $is_admin = in_array($user['Position'], $admin_positions);

    // ส่งข้อมูลกลับเป็น JSON
    $response = [
        'status' => 'success',
        'is_admin' => $is_admin,
        'position' => $user['Position'],
        'username' => $user['Username'],
        'id' => $user['ID'],
        'redirect_url' => $is_admin ? '../src/admin_dashboard.php' : '../src/index.php'
    ];
    
    // Debug: บันทึกข้อมูลลงไฟล์ log
    error_log("Login successful: " . json_encode($response));
    
    echo json_encode($response);
} else {
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลผู้ใช้หรือรหัสผ่านไม่ถูกต้อง']);
}

// ปิดคำสั่งและการเชื่อมต่อ
$stmt->close();
$conn->close();
?>
