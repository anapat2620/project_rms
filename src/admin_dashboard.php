<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'session_check.php';
require_once 'db_connect.php';

if (!isset($conn) || $conn->connect_error) {
    die("เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST['project_id']) && !empty($_POST['action'])) {
        $project_id = intval($_POST['project_id']); 
        $status = ($_POST['action'] === "approve") ? "อนุมัติ" : "ปฏิเสธ";

        $stmt = $conn->prepare("UPDATE projects SET status = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $status, $project_id);
            if ($stmt->execute()) {
                echo "<script>
                        alert('สถานะอัปเดตเรียบร้อยแล้ว');
                        window.location.href='admin_dashboard.php';
                      </script>";
            } else {
                echo "เกิดข้อผิดพลาด: " . $stmt->error;
            }
            $stmt->close();
        }
    }
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
            <img src="../asset/img/logomsu.png" alt="โลโก้">
        </div>
        <div class="s">
            <img src="../asset/img/icon/Frame.png" alt="ค้นหา">
            <input type="text" placeholder="ค้นหา">
        </div>
        <div class="login">
            <button type="button" class="btn btn-warning" id="logout-button">ออกจากระบบ</button>
        </div>
    </section>

    <div id="thank-you-message" class="alert alert-info d-none text-center">
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
                    <th>การกระทำ</th>
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
                                <td>
                                    <form method='POST' class='d-inline'>
                                        <input type='hidden' name='project_id' value='$id'>
                                        <button type='submit' name='action' value='approve' class='btn btn-success btn-sm'>อนุมัติ</button>
                                        <button type='submit' name='action' value='reject' class='btn btn-danger btn-sm'>ปฏิเสธ</button>
                                    </form>
                                </td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='9' class='text-center text-muted'>ไม่มีข้อมูล</td></tr>";
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
            }, 3000);
        });
    </script>

</body>
</html>
