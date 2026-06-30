<?php
session_start();
$approverPositions = [
    'คณบดี',
    'รองคณบดี',
    'ผู้ช่วยคณบดี',
    'หัวหน้าภาควิชา',
    'ผู้อำนวยการหลักสูตร'
];
if (!isset($_SESSION['Email']) || !isset($_SESSION['Position']) || !in_array($_SESSION['Position'], $approverPositions)) {
    header("Location: home.php");
    exit();
}

// DB connection (centralized via config.php)
require_once __DIR__ . '/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

// ดึงรายการคำขอที่รออนุมัติ
$sql = "SELECT * FROM research_requests_status WHERE current_status = 'รออนุมัติ' GROUP BY request_id ORDER BY submission_date DESC";
$result = $conn->query($sql);
$requests = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}
$conn->close();

// ประวัติการอนุมัติ/ปฏิเสธของตัวเอง
$history = [];
if (isset($_SESSION['Username'])) {
    $conn2 = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn2->connect_error) {
        $uname = $conn2->real_escape_string($_SESSION['Username']);
        $sql2 = "SELECT * FROM research_requests_status WHERE approver_username = '".$uname."' AND current_status != 'รออนุมัติ' GROUP BY request_id ORDER BY action_date DESC";
        $result2 = $conn2->query($sql2);
        if ($result2 && $result2->num_rows > 0) {
            while ($row2 = $result2->fetch_assoc()) {
                $history[] = $row2;
            }
        }
        $conn2->close();
    }
}
?>
<?php $isEmbed = isset($_GET['embed']) && $_GET['embed'] === '1'; ?>
<?php if (!$isEmbed): ?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>รายการการยื่นทุนที่ยื่นมา</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.24/dist/full.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/approve_requests.css">
</head>
<body class="bg-base-200 min-h-screen">
<?php endif; ?>
<div class="max-w-5xl mx-auto py-10">
    <div class="bg-base-100 rounded-xl shadow-lg p-8 mb-8">
        <h2 class="text-2xl font-bold text-primary mb-2">การยื่นทุนที่ฉันต้องดำเนินการ</h2>
        <p class="text-gray-500 mb-4">รายการคำขอทุนที่รอการอนุมัติ/ปฏิเสธ</p>
        <div class="divider"></div>
        <?php if (count($requests) > 0): ?>
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>รหัสคำขอ</th>
                            <th>ชื่อโครงการ</th>
                            <th>ผู้ยื่น</th>
                            <th>วันที่ยื่น</th>
                            <th>สถานะ</th>
                            <th>ดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                        <tr>
                            <td><?= htmlspecialchars($req['request_id']) ?></td>
                            <td><?= htmlspecialchars($req['project_name']) ?></td>
                            <td><?= htmlspecialchars($req['requesting_user_name']) ?><br><span class="text-xs text-gray-400"><?= htmlspecialchars($req['requesting_user_email']) ?></span></td>
                            <td><?= htmlspecialchars($req['submission_date']) ?></td>
                            <td><span class="badge badge-warning">รออนุมัติ</span></td>
                            <td class="flex gap-2">
                                <a href="details.php?request_id=<?= htmlspecialchars($req['request_id']) ?>" target="_blank" class="btn btn-info btn-xs">ดูรายละเอียด</a>
                                <button class="btn btn-success btn-xs" onclick="openApproveModal(<?= $req['request_id'] ?>)">อนุมัติ</button>
                                <button class="btn btn-error btn-xs" onclick="openRejectModal(<?= $req['request_id'] ?>)">ปฏิเสธ</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">ไม่มีรายการคำขอที่รออนุมัติ</div>
        <?php endif; ?>

        <div class="mt-12">
            <h3 class="text-xl font-bold text-primary mb-2">ประวัติการอนุมัติ/ปฏิเสธของฉัน</h3>
            <?php if (count($history) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full">
                        <thead>
                            <tr>
                                <th>รหัสคำขอ</th>
                                <th>ชื่อโครงการ</th>
                                <th>ผู้ยื่น</th>
                                <th>วันที่ยื่น</th>
                                <th>สถานะ</th>
                                <th>วันที่ดำเนินการ</th>
                                <th>หมายเหตุ/เหตุผล</th>
                                <th>ดูรายละเอียด</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $h): ?>
                            <tr>
                                <td><?= htmlspecialchars($h['request_id']) ?></td>
                                <td><?= htmlspecialchars($h['project_name']) ?></td>
                                <td><?= htmlspecialchars($h['requesting_user_name']) ?><br><span class="text-xs text-gray-400"><?= htmlspecialchars($h['requesting_user_email']) ?></span></td>
                                <td><?= htmlspecialchars($h['submission_date']) ?></td>
                                <td>
                                    <?php if ($h['current_status'] === 'อนุมัติ'): ?>
                                        <span class="badge badge-success">อนุมัติ</span>
                                    <?php elseif ($h['current_status'] === 'ปฏิเสธ'): ?>
                                        <span class="badge badge-error">ปฏิเสธ</span>
                                    <?php elseif ($h['current_status'] === 'ยกเลิกแล้ว'): ?>
                                        <span class="badge badge-neutral">ยกเลิกแล้ว</span>
                                    <?php else: ?>
                                        <span class="badge badge-ghost"><?= htmlspecialchars($h['current_status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($h['action_date']) ?></td>
                                <td><?= nl2br(htmlspecialchars($h['comment'])) ?></td>
                                <td><a href="details.php?request_id=<?= htmlspecialchars($h['request_id']) ?>" target="_blank" class="btn btn-info btn-xs">ดูรายละเอียด</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">ยังไม่มีประวัติการอนุมัติ/ปฏิเสธของคุณ</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- Approve Modal -->
<dialog id="approveModal" class="modal">
  <form method="post" action="status.php" class="modal-box" onsubmit="return validateApproveComment()">
    <h3 class="font-bold text-lg text-success mb-2">ยืนยันการอนุมัติ</h3>
    <input type="hidden" name="request_id" id="approveRequestId">
    <input type="hidden" name="action" value="approve">
    <label class="block mb-2">หมายเหตุ (จำเป็นต้องกรอก):
      <textarea name="comment" id="approveComment" class="textarea textarea-bordered w-full" rows="2" required></textarea>
    </label>
    <div class="modal-action">
      <button type="submit" class="btn btn-success">ยืนยัน</button>
      <button type="button" class="btn" onclick="this.closest('dialog').close()">ยกเลิก</button>
    </div>
  </form>
</dialog>
<!-- Reject Modal -->
<dialog id="rejectModal" class="modal">
  <form method="post" action="status.php" class="modal-box" onsubmit="return validateRejectComment()">
    <h3 class="font-bold text-lg text-error mb-2">ยืนยันการปฏิเสธ</h3>
    <input type="hidden" name="request_id" id="rejectRequestId">
    <input type="hidden" name="action" value="reject">
    <label class="block mb-2">เหตุผลการปฏิเสธ (จำเป็นต้องกรอก):
      <textarea name="comment" id="rejectComment" class="textarea textarea-bordered w-full" rows="2" required></textarea>
    </label>
    <div class="modal-action">
      <button type="submit" class="btn btn-error">ยืนยัน</button>
      <button type="button" class="btn" onclick="this.closest('dialog').close()">ยกเลิก</button>
    </div>
  </form>
</dialog>
<script src="assets/approve_requests.js"></script>
<?php if (!$isEmbed): ?>
</body>
</html>
<?php endif; ?>