<?php
session_start(); // เริ่มต้น session

$host = 'localhost:3307';
$dbuser = 'root';  
$dbpass = '';  
$dbname = 'login_data';  

$conn = new mysqli($host, $dbuser, $dbpass, $dbname);

if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

$email = trim($_POST['email']);
$password = trim($_POST['password']);
$position = trim($_POST['position']);

if (empty($email) || empty($password) || empty($position)) {
    echo "error";
    exit();
}

$sql = "SELECT * FROM data WHERE Email = ? AND Password = ? AND Position = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $email, $password, $position);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

    // เก็บข้อมูลใน session
    $_SESSION['Email'] = $user['Email'];
    $_SESSION['Position'] = $user['Position'];

    echo "success";
} else {
    echo "error";
}

$stmt->close();
$conn->close();
?>
