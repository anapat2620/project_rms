<?php
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['Username'])) {
    header('Location: login.php');
    exit();
}

// ฟังก์ชันสำหรับดึงข้อมูลจาก get_my_scholarships.php แบบ AJAX
function fetchScholarshipData() {
    $ch = curl_init('get_my_scholarships.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-Requested-With: XMLHttpRequest'
    ));
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สรุปสถานะทุนวิจัย</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.24/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="w-full h-full">
    <div class="w-full h-full p-4">
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- ส่วนแสดงกราฟ donut -->
            <div class="flex justify-center">
                <div class="donut-chart w-[500px] h-[400px] bg-base-100 rounded-lg shadow p-4">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
            
            <!-- ส่วนแสดงข้อมูลสรุป -->
            <div id="summaryData" class="w-full"></div>
        </div>
    </div>

    <script src="assets/scholarship_summary.js"></script>
</body>
</html> 