<?php
require_once 'db_connect.php'; 

if (!isset($conn) || $conn->connect_error) {
    die("เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $conn->connect_error);
}

$sql = "SELECT * FROM projects";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <section class="navmsu">
        <div class="logo">
            <img src="../asset/img/logomsu.png" alt="โลโก้มหาวิทยาลัย">
        </div>
        <div>
            <div class="s">
                <img src="../asset/img/icon/Frame.png" alt="">
                <input type="text" placeholder="ค้นหา">
            </div>
        </div>
        <div class="login">
            <button type="button" class="btn btn-warning" id="logout-button">ออกจากระบบ</button>
        </div>
    </section>

    <div id="thank-you-message" class="alert alert-info d-none text-center" role="alert">
        ขอบคุณ นิสิตและบุคลากรที่ทำวิจัยเพื่อความเป็นเลิศทางวิชาการ
    </div>

    <nav class="navbarr">
        <div class="btn-group">
            <button type="button" class="btn btn-warning dropdown-toggle" data-bs-toggle="dropdown">
                ขอทุนวิจัย
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="index.php">ทุนโครงการวิจัย (ทุนภายใน)</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="DataTable.php">ตรวจสอบข้อมูลทุนโครงการวิจัย</a></li>
                <li><a class="dropdown-item" href="admin_dashboard.php">การอนุมัติโครงการวิจัย</a></li>
            </ul>
        </div>
    </nav>

    <section class="container mt-5">
        <h2 class="mb-4">ข้อมูลโครงการวิจัย</h2>
        <table class="table table-bordered text-center">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>ปีงบประมาณ / ประเภททุน</th>
                    <th>ชื่อโครงการ (ไทย)</th>
                    <th>ชื่อโครงการ (English)</th>
                    <th>ประเภทวิจัย</th>
                    <th>เป้าหมายการวิจัย</th>
                    <th>งบประมาณที่ขอ</th>
                    <th>สถานะ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $id = htmlspecialchars($row['id']);
                        $budget_year = htmlspecialchars($row['budget_year']);
                        $project_type = htmlspecialchars($row['project_type']);
                        $project_name_th = htmlspecialchars($row['project_name_th']);
                        $project_name_en = htmlspecialchars($row['project_name_en']);
                        $research_type = htmlspecialchars($row['research_type']);
                        $research_goal = htmlspecialchars($row['research_goal']);
                        $budget_request = htmlspecialchars($row['budget_request']);
                        $status = htmlspecialchars($row['status']);
                        $status_class = "bg-warning";
                        if ($status == "อนุมัติ") {
                            $status_class = "bg-success";
                        } elseif ($status == "ปฏิเสธ") {
                            $status_class = "bg-danger";
                        }

                        echo "<tr>
                                <td>$id</td>
                                <td>$budget_year - $project_type</td>
                                <td>$project_name_th</td>
                                <td>$project_name_en</td>
                                <td>$research_type</td>
                                <td>$research_goal</td>
                                <td>$budget_request</td>
                                <td><span class='badge $status_class'>$status</span></td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='8' class='text-center text-muted'>ไม่มีข้อมูล</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('logout-button').addEventListener('click', function() {
            document.getElementById('thank-you-message').classList.remove('d-none');
            setTimeout(function() {
                window.location.href = "logout.php";
            }, 5000);
        });
    </script>
</body>
</html>
