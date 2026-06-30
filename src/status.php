<!-- status.php -->
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Centralized DB configuration
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['Email']) || !isset($_SESSION['Position'])) {
    header("Location: login.php");
    exit();
}

$approverPositions = [
    'คณบดี',
    'รองคณบดี',
    'ผู้ช่วยคณบดี',
    'หัวหน้าภาควิชา',
    'ผู้อำนวยการหลักสูตร'
];
$userPosition = $_SESSION['Position'];
$userEmail = $_SESSION['Email'];

if (!in_array($userPosition, $approverPositions, true)) {
    http_response_code(403);
    echo '<div class="alert alert-error m-4"><span>ท่านไม่มีสิทธิ์เข้าถึงหน้านี้</span></div>';
    exit();
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = $_POST['request_id'] ?? null;
    $action = $_POST['action'] ?? null;
    $error_message = '';

    // Handle fund disbursement status updates (admin and approvers)
    if ($action === 'update_fund_disbursement' && in_array($userPosition, $approverPositions)) {
        $phase = $_POST['phase'] ?? null; // 1st, 2nd, 3rd
        $status = $_POST['fund_status'] ?? null; // รอการจ่าย, จ่ายแล้ว, ไม่จ่าย
        $amount = $_POST['amount'] ?? null;
        $comment = trim($_POST['fund_comment'] ?? '');
        
        if ($requestId && $phase && $status) {
            if (!isset($_SESSION['Username']) || empty($_SESSION['Username'])) {
                $error_message = 'Session ไม่มี Username กรุณา login ใหม่ หรือแจ้งผู้ดูแลระบบ';
            } else {
                $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                
                // Update the main status table
                $phase_field = 'fund_disbursement_' . $phase . '_status';
                $date_field = 'fund_disbursement_' . $phase . '_date';
                $amount_field = 'fund_disbursement_' . $phase . '_amount';
                $comment_field = 'fund_disbursement_' . $phase . '_comment';
                
                $now = date('Y-m-d H:i:s');
                $disbursement_date = ($status === 'จ่ายแล้ว') ? $now : null;
                
                $sql = "UPDATE research_requests_status SET 
                        {$phase_field} = ?, 
                        {$date_field} = ?, 
                        {$amount_field} = ?, 
                        {$comment_field} = ?,
                        fund_disbursement_updated_by = ?,
                        fund_disbursement_updated_date = ?
                        WHERE request_id = ?";
                
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("ssdsssi", $status, $disbursement_date, $amount, $comment, $_SESSION['Username'], $now, $requestId);
                    if ($stmt->execute()) {
                        // Insert into history table
                        $history_sql = "INSERT INTO fund_disbursement_history 
                                       (request_id, disbursement_phase, status, amount, disbursement_date, comment, updated_by) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $history_stmt = $conn->prepare($history_sql);
                        if ($history_stmt) {
                            $history_stmt->bind_param("issdsss", $requestId, $phase, $status, $amount, $disbursement_date, $comment, $_SESSION['Username']);
                            $history_stmt->execute();
                            $history_stmt->close();
                        }
                        
                        $stmt->close();
                        $conn->close();
                        header("Location: status.php?status=fund_updated");
                        exit();
                    } else {
                        $error_message = "เกิดข้อผิดพลาดในการอัปเดตสถานะการจ่ายเงิน: " . $stmt->error;
                    }
                } else {
                    $error_message = "เกิดข้อผิดพลาดในการเตรียม SQL: " . $conn->error;
                }
            }
        } else {
            $error_message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        }
    }

    // เพิ่มโค้ดสำหรับ approve/reject
    if ((($action === 'approve') || ($action === 'reject')) && in_array($userPosition, $approverPositions)) {
        $comment = trim($_POST['comment'] ?? '');
        if ($comment && $requestId) {
            if (!isset($_SESSION['Username']) || empty($_SESSION['Username'])) {
                $error_message = 'Session ไม่มี Username กรุณา login ใหม่ หรือแจ้งผู้ดูแลระบบ';
            } else {
                $status = ($action === 'approve') ? 'อนุมัติ' : 'ปฏิเสธ';
                $approver = $_SESSION['Username'];
                $now = date('Y-m-d H:i:s');
                $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                $stmt = $conn->prepare("UPDATE research_requests_status SET current_status=?, approver_username=?, action_date=?, comment=? WHERE request_id=?");
                if ($stmt) {
                    $stmt->bind_param("ssssi", $status, $approver, $now, $comment, $requestId);
                    if ($stmt->execute()) {
                        $stmt->close();
                        $conn->close();
                        header("Location: index.php?view=approve&status=success&action=" . urlencode($action));
                        exit();
                    } else {
                        $error_message = "เกิดข้อผิดพลาดในการอัปเดตสถานะ: " . $stmt->error;
                    }
                } else {
                    $error_message = "เกิดข้อผิดพลาดในการเตรียม SQL: " . $conn->error;
                }
            }
        } else {
            $error_message = 'กรุณากรอกหมายเหตุ/เหตุผล และระบุรหัสคำขอ';
        }
    }
    // ลบโค้ดที่เกี่ยวกับ approve/reject/cancel เหลือเฉพาะ add_comment
    if ($action === 'add_comment' && in_array($userPosition, $approverPositions)) {
        $comment = trim($_POST['comment'] ?? '');
        if ($comment && $requestId) {
            if (!isset($_SESSION['Username']) || empty($_SESSION['Username'])) {
                $error_message = 'Session ไม่มี Username กรุณา login ใหม่ หรือแจ้งผู้ดูแลระบบ';
            } else {
                $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                $stmt = $conn->prepare("INSERT INTO research_request_comments (request_id, commenter_email, commenter_name, comment) VALUES (?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("isss", $requestId, $userEmail, $_SESSION['Username'], $comment);
                    if ($stmt->execute()) {
                        $stmt->close();
                        $conn->close();
                        header("Location: index.php?status=check");
                        exit();
                    } else {
                        $error_message = "เกิดข้อผิดพลาดในการเพิ่มความคิดเห็น: " . $stmt->error . " กรุณาลองใหม่ หรือแจ้งผู้ดูแลระบบ";
                    }
                } else {
                    $error_message = "เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . $conn->error . " กรุณาลองใหม่ หรือแจ้งผู้ดูแลระบบ";
                }
            }
        }
    }
    // ถ้ามี error ให้แสดง alert ด้านบน
    if (!empty($error_message)) {
        echo "<script>alert('" . addslashes($error_message) . "');</script>";
    }
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$requests = [];
$items_per_page = 20; // จำนวนรายการต่อหน้า
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Query เพื่อนับจำนวนรายการทั้งหมด (filter ด้วย search ถ้ามี)
if ($search !== '') {
    $count_sql = "SELECT COUNT(*) as total FROM research_requests_status WHERE project_name LIKE ?";
    $stmt_count = $conn->prepare($count_sql);
    $like = '%' . $search . '%';
    $stmt_count->bind_param("s", $like);
    $stmt_count->execute();
    $count_result = $stmt_count->get_result();
    $total_rows = $count_result->fetch_assoc()['total'];
    $stmt_count->close();
} else {
    $count_sql = "SELECT COUNT(*) as total FROM research_requests_status";
    $count_result = $conn->query($count_sql);
    $total_rows = $count_result->fetch_assoc()['total'];
}
$total_pages = ceil($total_rows / $items_per_page);

// Query หลัก (filter ด้วย search ถ้ามี)
if ($search !== '') {
    $sql_select = "SELECT r.request_id AS id, r.project_name AS item, r.submission_date AS date_submitted, 
                   r.current_status AS status, r.requesting_user_email, r.approver_username, r.action_date, r.comment,
                   r.fund_disbursement_1st_status, r.fund_disbursement_1st_date, r.fund_disbursement_1st_amount, r.fund_disbursement_1st_comment,
                   r.fund_disbursement_2nd_status, r.fund_disbursement_2nd_date, r.fund_disbursement_2nd_amount, r.fund_disbursement_2nd_comment,
                   r.fund_disbursement_3rd_status, r.fund_disbursement_3rd_date, r.fund_disbursement_3rd_amount, r.fund_disbursement_3rd_comment,
                   r.fund_disbursement_updated_by, r.fund_disbursement_updated_date
                   FROM research_requests_status r 
                   WHERE r.project_name LIKE ?
                   GROUP BY r.request_id
                   ORDER BY r.submission_date DESC 
                   LIMIT ? OFFSET ?";
    $stmt_select = $conn->prepare($sql_select);
    $like = '%' . $search . '%';
    $stmt_select->bind_param("sii", $like, $items_per_page, $offset);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    while ($row = $result_select->fetch_assoc()) {
        $row['can_cancel'] = ($row['status'] === 'รออนุมัติ' && $row['requesting_user_email'] === $userEmail);
        $requests[] = $row;
    }
    $stmt_select->close();
} else {
    $sql_select = "SELECT r.request_id AS id, r.project_name AS item, r.submission_date AS date_submitted, 
                   r.current_status AS status, r.requesting_user_email, r.approver_username, r.action_date, r.comment,
                   r.fund_disbursement_1st_status, r.fund_disbursement_1st_date, r.fund_disbursement_1st_amount, r.fund_disbursement_1st_comment,
                   r.fund_disbursement_2nd_status, r.fund_disbursement_2nd_date, r.fund_disbursement_2nd_amount, r.fund_disbursement_2nd_comment,
                   r.fund_disbursement_3rd_status, r.fund_disbursement_3rd_date, r.fund_disbursement_3rd_amount, r.fund_disbursement_3rd_comment,
                   r.fund_disbursement_updated_by, r.fund_disbursement_updated_date
                   FROM research_requests_status r 
                   GROUP BY r.request_id
                   ORDER BY r.submission_date DESC 
                   LIMIT ? OFFSET ?";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("ii", $items_per_page, $offset);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    while ($row = $result_select->fetch_assoc()) {
        $row['can_cancel'] = ($row['status'] === 'รออนุมัติ' && $row['requesting_user_email'] === $userEmail);
        $requests[] = $row;
    }
    $stmt_select->close();
}

// ดึง comment history สำหรับ request ทั้งหมด
$commentsByRequest = [];
if (!empty($requests)) {
    $requestIds = array_column($requests, 'id');
    $in = implode(',', array_fill(0, count($requestIds), '?'));
    $types = str_repeat('i', count($requestIds));
    $sql_comments = "SELECT * FROM research_request_comments WHERE request_id IN ($in) ORDER BY created_at ASC";
    $stmt_comments = $conn->prepare($sql_comments);
    $stmt_comments->bind_param($types, ...$requestIds);
    $stmt_comments->execute();
    $result_comments = $stmt_comments->get_result();
    while ($row = $result_comments->fetch_assoc()) {
        $commentsByRequest[$row['request_id']][] = $row;
    }
    $stmt_comments->close();
}

// ดึงข้อมูล Fund Support (BH1, BH2, B3) สำหรับแต่ละ request
$fundSupportData = [];
if (!empty($requests)) {
    foreach ($requests as $req) {
        $requestId = $req['id'];
        
        // Get original_table and original_id from research_requests_status
        $status_sql = "SELECT original_table, original_id FROM research_requests_status WHERE request_id = ?";
        $status_stmt = $conn->prepare($status_sql);
        if ($status_stmt) {
            $status_stmt->bind_param("i", $requestId);
            $status_stmt->execute();
            $status_result = $status_stmt->get_result();
            if ($status_result->num_rows > 0) {
                $status_row = $status_result->fetch_assoc();
                $original_table = $status_row['original_table'];
                $original_id = $status_row['original_id'];
                
                // Get fund_name from fund_type_selections
                $fund_sql = "SELECT fund_name FROM fund_type_selections 
                             WHERE table_source = ? AND proposal_id = ? 
                             ORDER BY selected_date DESC LIMIT 1";
                $fund_stmt = $conn->prepare($fund_sql);
                if ($fund_stmt) {
                    $fund_stmt->bind_param("si", $original_table, $original_id);
                    $fund_stmt->execute();
                    $fund_result = $fund_stmt->get_result();
                    if ($fund_result->num_rows > 0) {
                        $fund_row = $fund_result->fetch_assoc();
                        $fund_name = $fund_row['fund_name'];
                        
                        // Get BH1, BH2, B3 from fund_support
                        $fund_support_sql = "SELECT BH1, BH2, B3 FROM fund_support WHERE FunName = ? LIMIT 1";
                        $fund_support_stmt = $conn->prepare($fund_support_sql);
                        if ($fund_support_stmt) {
                            $fund_support_stmt->bind_param("s", $fund_name);
                            $fund_support_stmt->execute();
                            $fund_support_result = $fund_support_stmt->get_result();
                            if ($fund_support_result->num_rows > 0) {
                                $fund_support_row = $fund_support_result->fetch_assoc();
                                $fundSupportData[$requestId] = [
                                    'BH1' => (int)$fund_support_row['BH1'],
                                    'BH2' => (int)$fund_support_row['BH2'],
                                    'B3' => (int)$fund_support_row['B3']
                                ];
                            }
                            $fund_support_stmt->close();
                        }
                    }
                    $fund_stmt->close();
                }
            }
            $status_stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.24/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/status.css" />
    <script src="assets/status.js" defer></script>
    <title>สถานะการยื่นขอทุน</title>
    <style>
        body {
            font-family: 'Kanit', sans-serif;
        }
    </style>
</head>
<body>
    <div class="min-h-screen p-8">

    <div class="h-10 w-full flex flex-col items-center justify-center">
            <h1 class="text-center bg-base-100 rounded-full px-2 py-3 shadow-lg">
                สถานะการยื่นขอทุน
            </h1>
        </div>

        <!-- Modal for comments -->
        <div id="commentModal" class="modal fixed inset-0 bg-black/50 z-50 hidden">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="modal-content bg-white rounded-lg shadow-xl w-96 max-w-full transform scale-95 transition-all duration-200">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4" id="modalTitle"></h3>
                        <form id="actionForm" method="POST" action="status.php">
                            <input type="hidden" id="modalRequestId" name="request_id">
                            <input type="hidden" id="modalAction" name="action">
                            <div class="mb-4">
                                <label for="modalComment" class="block text-sm font-medium text-gray-700 mb-2">กรุณาใส่ความคิดเห็น/ข้อเสนอแนะ</label>
                                <textarea id="modalComment" name="comment" rows="4" 
                                    class="w-full px-3 py-2 bg-gray-50/50 border-transparent rounded-lg focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition-all duration-200 text-sm resize-none custom-scrollbar"
                                    required></textarea>
                            </div>
                            <div class="flex justify-end space-x-2">
                                <button type="button" onclick="window.closeModal()" 
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 transition-colors duration-200">
                                    ยกเลิก
                                </button>
                                <button type="submit" id="confirmButton"
                                    class="px-4 py-2 text-sm font-medium text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200">
                                    ยืนยัน
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-center">
            <div class="rounded-xl overflow-hidden border border-gray-200 w-full max-w-6xl mt-5 shadow-xl table-container bg-base-100">
                <table class="w-full table-fixed text-center divide-y divide-gray-200">
                    <thead>
                        <tr class="table-header bg-blue-900 text-white text-base">
                            <th class="py-3 px-2 w-10 rounded-tl-xl">ลำดับที่</th>
                            <th class="py-3 px-2 w-[18%]">รายการขอทุน</th>
                            <th class="py-3 px-2 w-20">วันที่</th>
                            <th class="py-3 px-2 w-16">สถานะ</th>
                            <th class="py-3 px-2 w-[15%]">การจัดการ</th>
                            <th class="py-3 px-2 w-[20%]">งวดปัจจุบัน</th>
                            <th class="py-3 px-2 w-[20%]">ความคิดเห็น</th>
                            <th class="py-3 px-2 w-20 rounded-tr-xl">รายละเอียด</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($requests)): ?>
                            <tr class="table-row">
                                <td colspan="8" class="py-4 px-4 text-gray-500">ไม่มีรายการขอทุน</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($requests as $index => $req): ?>
                                <tr class="table-row hover:bg-gray-50 transition-colors duration-150">
                                    <td class="py-4 px-4"><?= $index + 1 ?></td>
                                    <td class="py-4 px-4">
                                        <?php if (mb_strlen($req['item']) > 50): ?>
                                            <div class="w-full text-center bg-gray-50/50 border-transparent rounded p-2 text-sm custom-scrollbar" style="max-height:60px; max-width:220px; overflow:auto;">
                                                <?= htmlspecialchars($req['item']) ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center"><?= htmlspecialchars($req['item']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-4 text-sm text-center">
                                        <?= date('d/m/Y', strtotime($req['date_submitted'])) ?>
                                        <br>
                                        <span class="text-gray-500">
                                            <?= date('H:i', strtotime($req['date_submitted'])) ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <p class="status-badge-<?= htmlspecialchars($req['status']) ?> px-3 py-1 rounded-full text-sm shadow-sm inline-block">
                                            <?= htmlspecialchars($req['status']) ?>
                                        </p>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="flex items-center justify-center space-x-2">
                                            <?php if ($req['status'] === 'รออนุมัติ'): ?>
                                                <span class="text-yellow-600 text-sm">อยู่ระหว่างดำเนินการ</span>
                                            <?php elseif ($req['status'] === 'อนุมัติ' || $req['status'] === 'ปฏิเสธ'): ?>
                                                <?php 
                                                $statusInfo = htmlspecialchars($req['status']) . " โดย " . 
                                                            htmlspecialchars($req['approver_username']) . "\n" .
                                                            "เมื่อ " . date('d/m/Y H:i', strtotime($req['action_date']));
                                                if (mb_strlen($statusInfo) > 50):
                                                ?>
                                                    <textarea readonly class="w-full text-center bg-gray-50/50 border-transparent rounded p-2 text-sm resize-none overflow-y-auto custom-scrollbar h-[60px] hover:border-gray-200 transition-colors duration-200"><?= $statusInfo ?></textarea>
                                                <?php else: ?>
                                                    <div class="text-gray-600 text-sm text-center">
                                                        <p><?= htmlspecialchars($req['status']) ?> โดย</p>
                                                        <p class="font-medium"><?= htmlspecialchars($req['approver_username']) ?></p>
                                                        <p class="text-gray-500 text-xs mt-1">
                                                            เมื่อ <?= date('d/m/Y H:i', strtotime($req['action_date'])) ?>
                                                        </p>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-gray-600 text-sm">ยกเลิกแล้ว</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <!-- Current Installment Status Column -->
                                    <td class="py-2 px-2 align-top">
                                        <?php if ($req['status'] === 'อนุมัติ'): ?>
                                            <?php
                                            // Get fund support data for this request
                                            $fundData = $fundSupportData[$req['id']] ?? null;
                                            $bh1 = $fundData['BH1'] ?? 0;
                                            $bh2 = $fundData['BH2'] ?? 0;
                                            $b3 = $fundData['B3'] ?? 0;
                                            
                                            // Determine active installments based on BH1, BH2, B3
                                            $active_installments = [];
                                            if ($bh1 > 0) $active_installments[] = '1st';
                                            if ($bh2 > 0) $active_installments[] = '2nd';
                                            if ($b3 > 0) $active_installments[] = '3rd';
                                            
                                            // Determine the current installment that needs attention
                                            $currentPhase = null;
                                            $currentStatus = null;
                                            $currentAmount = '';
                                            $currentDate = '';
                                            
                                            // Check only active installments in order
                                            foreach ($active_installments as $phase) {
                                                $status = $req["fund_disbursement_{$phase}_status"] ?? 'รอการจ่าย';
                                                if ($status !== 'จ่ายแล้ว') {
                                                    $currentPhase = $phase;
                                                    $currentStatus = $status;
                                                    $currentAmount = $req["fund_disbursement_{$phase}_amount"] ?? '';
                                                    $currentDate = $req["fund_disbursement_{$phase}_date"] ?? '';
                                                    break;
                                                }
                                            }
                                            
                                            // If all active installments are paid
                                            if ($currentPhase === null && !empty($active_installments)) {
                                                $currentPhase = 'completed';
                                                $currentStatus = 'completed';
                                            }
                                            
                                            // If no active installments are configured
                                            if (empty($active_installments)) {
                                                $currentPhase = 'no_installments';
                                                $currentStatus = 'no_installments';
                                            }
                                            
                                            if ($currentPhase === 'completed'): ?>
                                                <div class="payment-status-card items-center completed">
                                                    <div class="status-icon">
                                                        <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                    </div>
                                                    <div class="status-content">
                                                        <div class="status-title">เสร็จสิ้น</div>
                                                        <div class="status-subtitle">จ่ายครบทุกงวดแล้ว</div>
                                                    </div>
                                                </div>
                                            <?php elseif ($currentPhase === 'no_installments'): ?>
                                                <div class="payment-status-card items-center" style="border-color: #9ca3af; background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);">
                                                    <div class="status-content">
                                                        <div class="status-title text-gray-500">ไม่มีการกำหนดงวด</div>
                                                        <div class="status-subtitle">ไม่มีข้อมูลการจ่ายเงิน</div>
                                                    </div>
                                                </div>
                                            <?php else: 
                                                $phaseText = $currentPhase === '1st' ? 'งวดที่ 1' : ($currentPhase === '2nd' ? 'งวดที่ 2' : 'งวดที่ 3');
                                                $statusIcon = $currentStatus === 'รอการจ่าย' ? '⏳' : ($currentStatus === 'จ่ายแล้ว' ? '✅' : '❌');
                                                $statusColor = $currentStatus === 'รอการจ่าย' ? 'pending' : ($currentStatus === 'จ่ายแล้ว' ? 'paid' : 'rejected');
                                            ?>
                                                <div class="payment-status-card <?= $statusColor ?>">
                                                    <div class="status-header">
                                                        <div class="phase-badge"><?= $phaseText ?></div>
                                                        <div class="status-icon"><?= $statusIcon ?></div>
                                                    </div>
                                                    <div class="status-content">
                                                        <div class="status-title"><?= htmlspecialchars($currentStatus) ?></div>
                                                        <?php if ($currentDate): ?>
                                                            <div class="status-detail">
                                                                <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                                </svg>
                                                                <?= date('d/m/Y', strtotime($currentDate)) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if ($currentAmount): ?>
                                                            <div class="status-detail amount">
                                                                <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                                                </svg>
                                                                ฿<?= number_format($currentAmount, 2) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-xs">-</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="py-2 px-2 align-top">
                                        <div style="max-height: 80px; overflow-y: auto; min-height: 28px; background: #f8fafc; border-radius: 0.5rem; padding: 0.25rem;" class="custom-scrollbar shadow-inner">
                                            <?php
                                            $comments = $commentsByRequest[$req['id']] ?? [];
                                            foreach ($comments as $c) {
                                                echo '<div class="text-xs text-left mb-0.5" style="font-size: 11px; line-height: 1.2;"><b>' . htmlspecialchars($c['commenter_name']) . '</b> (' . date('d/m/Y H:i', strtotime($c['created_at'])) . ')<br>' . nl2br(htmlspecialchars($c['comment'])) . '</div>';
                                            }
                                            ?>
                                        </div>
                                        <?php if (in_array($userPosition, $approverPositions) && in_array($req['status'], ['อนุมัติ', 'ปฏิเสธ'])): ?>
                                            <form method="POST" action="status.php" class="mt-1 flex flex-col gap-1">
                                                <input type="hidden" name="request_id" value="<?= (int)$req['id'] ?>">
                                                <input type="hidden" name="action" value="add_comment">
                                                <textarea name="comment" rows="1" class="w-full px-2 py-1 text-xs border rounded" placeholder="เพิ่มความคิดเห็นเพิ่มเติม..." required></textarea>
                                                <button type="submit" class="btn btn-xs btn-primary">เพิ่มความคิดเห็น</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-4">
                                        <a href="details.php?request_id=<?= (int)$req['id'] ?>" target="_blank"
                                           class="action-button btn btn-sm btn-info text-white rounded-full px-4 py-1 transition duration-300 shadow-sm flex justify-center items-center">
                                           ดูรายละเอียด
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <style>
                    .custom-scrollbar {
                        scrollbar-width: thin;
                        scrollbar-color: rgba(156, 163, 175, 0.3) transparent;
                    }
                    
                    .custom-scrollbar::-webkit-scrollbar {
                        width: 6px;
                        height: 6px;
                    }
                    
                    .custom-scrollbar::-webkit-scrollbar-track {
                        background: transparent;
                    }
                    
                    .custom-scrollbar::-webkit-scrollbar-thumb {
                        background-color: rgba(156, 163, 175, 0.3);
                        border-radius: 3px;
                    }
                    
                    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
                        background-color: rgba(156, 163, 175, 0.5);
                    }

                    textarea.custom-scrollbar {
                        cursor: default;
                        outline: none;
                    }

                    textarea.custom-scrollbar:focus {
                        outline: none;
                        border-color: rgba(156, 163, 175, 0.2);
                    }

                    /* Beautiful Payment Status Cards */
                    .payment-status-card {
                        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
                        border: 1px solid #e2e8f0;
                        border-radius: 12px;
                        padding: 12px;
                        min-height: 80px;
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                        transition: all 0.3s ease;
                        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
                        position: relative;
                        overflow: hidden;
                    }
                    
                    .payment-status-card::before {
                        content: '';
                        position: absolute;
                        top: 0;
                        left: 0;
                        right: 0;
                        height: 3px;
                        background: linear-gradient(90deg, #e2e8f0, #cbd5e1);
                        transition: all 0.3s ease;
                    }
                    
                    .payment-status-card:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
                    }
                    
                    /* Status-specific colors */
                    .payment-status-card.pending {
                        border-color: #f59e0b;
                        background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
                    }
                    
                    .payment-status-card.pending::before {
                        background: linear-gradient(90deg, #f59e0b, #d97706);
                    }
                    
                    .payment-status-card.paid {
                        border-color: #10b981;
                        background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
                    }
                    
                    .payment-status-card.paid::before {
                        background: linear-gradient(90deg, #10b981, #059669);
                    }
                    
                    .payment-status-card.rejected {
                        border-color: #ef4444;
                        background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
                    }
                    
                    .payment-status-card.rejected::before {
                        background: linear-gradient(90deg, #ef4444, #dc2626);
                    }
                    
                    .payment-status-card.completed {
                        border-color: #10b981;
                        background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
                    }
                    
                    .payment-status-card.completed::before {
                        background: linear-gradient(90deg, #10b981, #059669);
                    }
                    
                    /* Status header */
                    .status-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 8px;
                    }
                    
                    .phase-badge {
                        background: rgba(255, 255, 255, 0.8);
                        color: #374151;
                        font-size: 10px;
                        font-weight: 600;
                        padding: 4px 8px;
                        border-radius: 6px;
                        border: 1px solid rgba(0, 0, 0, 0.1);
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                    }
                    
                    .status-icon {
                        font-size: 16px;
                        line-height: 1;
                    }
                    
                    /* Status content */
                    .status-content {
                        flex: 1;
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                    }
                    
                    .status-title {
                        font-size: 12px;
                        font-weight: 600;
                        color: #374151;
                        margin-bottom: 4px;
                        line-height: 1.2;
                    }
                    
                    .status-subtitle {
                        font-size: 10px;
                        color: #6b7280;
                        line-height: 1.2;
                    }
                    
                    .status-detail {
                        font-size: 10px;
                        color: #6b7280;
                        margin-top: 2px;
                        display: flex;
                        align-items: center;
                        line-height: 1.2;
                    }
                    
                    .status-detail.amount {
                        color: #059669;
                        font-weight: 600;
                    }
                    
                    /* Legacy support for old badges */
                    .fund-status-badge-รอการจ่าย {
                        background-color: #fef3c7;
                        color: #92400e;
                        border: 1px solid #f59e0b;
                    }
                    
                    .fund-status-badge-จ่ายแล้ว {
                        background-color: #d1fae5;
                        color: #065f46;
                        border: 1px solid #10b981;
                    }
                    
                    .fund-status-badge-ไม่จ่าย {
                        background-color: #fee2e2;
                        color: #991b1b;
                        border: 1px solid #ef4444;
                    }
                    
                    .admin-only-button {
                        position: relative;
                        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
                        color: white;
                        border: 1px solid #2563eb;
                        font-weight: 500;
                        transition: all 0.2s ease;
                    }
                    
                    .admin-only-button:hover {
                        background: linear-gradient(135deg, #2563eb, #1e40af);
                        transform: translateY(-1px);
                        box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
                    }
                    
                    .admin-only-button::before {
                        content: '';
                        position: absolute;
                        top: -2px;
                        left: -2px;
                        right: -2px;
                        bottom: -2px;
                        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
                        border-radius: inherit;
                        z-index: -1;
                        opacity: 0;
                        transition: opacity 0.2s ease;
                    }
                    
                    .admin-only-button:hover::before {
                        opacity: 0.2;
                    }

                    @media (max-width: 900px) {
                      .table-container { width: 100% !important; }
                      table { font-size: 0.95rem; }
                      th, td { padding-left: 0.3rem !important; padding-right: 0.3rem !important; }
                    }
                </style>
                
                <!-- เพิ่มส่วนควบคุมการแบ่งหน้า -->
                <?php if ($total_pages > 1): ?>
                <div class="flex justify-center items-center space-x-2 p-4 bg-base-200">
                    <?php if ($current_page > 1): ?>
                        <a href="?page=<?= $current_page - 1 ?>" class="btn btn-sm">
                            ← หน้าก่อนหน้า
                        </a>
                    <?php endif; ?>

                    <div class="join">
                        <?php
                        // แสดงปุ่มตัวเลขหน้า
                        for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++) {
                            $active_class = ($i === $current_page) ? 'btn-active' : '';
                            echo "<a href='?page={$i}' class='btn btn-sm join-item {$active_class}'>{$i}</a>";
                        }
                        ?>
                    </div>

                    <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?= $current_page + 1 ?>" class="btn btn-sm">
                            หน้าถัดไป →
                        </a>
                    <?php endif; ?>
                    
                    <span class="text-sm">
                        หน้า <?= $current_page ?> จาก <?= $total_pages ?> หน้า
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>