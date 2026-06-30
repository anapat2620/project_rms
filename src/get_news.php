<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// การเชื่อมต่อฐานข้อมูล (ใช้ config กลาง)
require_once __DIR__ . '/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['error' => 'การเชื่อมต่อฐานข้อมูลล้มเหลว']);
    exit();
}

// ดึงข้อมูลข่าวที่แสดงผล
$sql = "SELECT title, content, date_posted FROM news_board WHERE is_active = 1 ORDER BY date_posted DESC, created_at DESC LIMIT 5";
$result = $conn->query($sql);

$news = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $news[] = [
            'title' => $row['title'],
            'content' => $row['content'],
            'date' => date('d/m/Y', strtotime($row['date_posted']))
        ];
    }
}

$conn->close();

echo json_encode($news);
?> 