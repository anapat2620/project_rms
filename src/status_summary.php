<?php
// status_summary.php
// Note: This page is publicly accessible without login

require_once __DIR__ . '/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

// นับจำนวนคำขอทั้งหมด
$total_requests = 0;
$total_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM research_requests_status");
if ($total_stmt) {
    $total_stmt->execute();
    $total_result = $total_stmt->get_result();
    $total_requests = (int)($total_result->fetch_assoc()['total'] ?? 0);
    $total_stmt->close();
}

// นับจำนวนตามสถานะ
$status_counts = [
    'รออนุมัติ' => 0,
    'อนุมัติ' => 0,
    'ปฏิเสธ' => 0,
    'ยกเลิก' => 0,
];

$counts_stmt = $conn->prepare("SELECT current_status, COUNT(*) AS c FROM research_requests_status GROUP BY current_status");
if ($counts_stmt) {
    $counts_stmt->execute();
    $counts_result = $counts_stmt->get_result();
    while ($row = $counts_result->fetch_assoc()) {
        if (isset($status_counts[$row['current_status']])) {
            $status_counts[$row['current_status']] = (int)$row['c'];
        }
    }
    $counts_stmt->close();
}

// รวมจำนวนปฏิเสธและยกเลิก
$rejected_cancelled = ($status_counts['ปฏิเสธ'] ?? 0) + ($status_counts['ยกเลิก'] ?? 0);

// สรุปจำนวนการยื่นคำขอตามประเภทผู้ยื่น
$applicant_type_summary = [];
$type_labels = [
    'research_proposals' => 'นิสิต',
    'research_teacher' => 'อาจารย์',
    'research_personnel' => 'บุคลากร',
];
$applicant_type_sql = "SELECT original_table, COUNT(*) AS c FROM research_requests_status GROUP BY original_table";
$applicant_type_result = $conn->query($applicant_type_sql);
if ($applicant_type_result) {
    while ($row = $applicant_type_result->fetch_assoc()) {
        $original_table = $row['original_table'];
        $label = $type_labels[$original_table] ?? $original_table;
        $applicant_type_summary[$label] = (int)$row['c'];
    }
}

$applicantTypeLabels = array_keys($applicant_type_summary);
$applicantTypeCounts = array_values($applicant_type_summary);

// --- ข้อมูลการเบิกจ่ายทุน ---
$current_fiscal_year = date('Y') + 543; // ปี พ.ศ. ปัจจุบัน
if (isset($_GET['fiscal_year']) && ctype_digit($_GET['fiscal_year'])) {
    $current_fiscal_year = intval($_GET['fiscal_year']);
}

$available_years = [];
$years_sql = "SELECT fiscal_year FROM disbursement_summary UNION SELECT fiscal_year FROM disbursement_items ORDER BY fiscal_year DESC";
$years_result = $conn->query($years_sql);
if ($years_result) {
    while ($row = $years_result->fetch_assoc()) {
        $available_years[] = intval($row['fiscal_year']);
    }
}
if (empty($available_years)) {
    $available_years[] = $current_fiscal_year;
}
if (!in_array($current_fiscal_year, $available_years, true)) {
    $available_years[] = $current_fiscal_year;
    rsort($available_years);
}

$budget_amount = 25000000.00; // ค่าเริ่มต้น
$disbursed_amount = 14500000.00; // ค่าเริ่มต้น

// สร้างตารางถ้ายังไม่มี
$create_table_sql = "CREATE TABLE IF NOT EXISTS `disbursement_summary` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `fiscal_year` int(11) NOT NULL,
    `budget_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
    `disbursed_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
    `updated_by` varchar(255) NOT NULL,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_fiscal_year` (`fiscal_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
$conn->query($create_table_sql);

// ดึงข้อมูลจากฐานข้อมูล (สรุปงบ)
$disbursement_sql = "SELECT * FROM disbursement_summary WHERE fiscal_year = ?";
$disbursement_stmt = $conn->prepare($disbursement_sql);
if ($disbursement_stmt) {
    $disbursement_stmt->bind_param("i", $current_fiscal_year);
    $disbursement_stmt->execute();
    $disbursement_result = $disbursement_stmt->get_result();
    if ($disbursement_result->num_rows > 0) {
        $disbursement_row = $disbursement_result->fetch_assoc();
        $budget_amount = floatval($disbursement_row['budget_amount']);
        $current_fiscal_year = intval($disbursement_row['fiscal_year']);
    }
    $disbursement_stmt->close();
}

// คำนวณยอดเบิกจ่ายรวม
$disbursed_amount = 0;
$items_sum_sql = "SELECT SUM(amount) as total FROM disbursement_items WHERE fiscal_year = ?";
$items_sum_stmt = $conn->prepare($items_sum_sql);
if ($items_sum_stmt) {
    $items_sum_stmt->bind_param("i", $current_fiscal_year);
    $items_sum_stmt->execute();
    $items_sum_result = $items_sum_stmt->get_result();
    if ($items_sum_result->num_rows > 0) {
        $items_sum_row = $items_sum_result->fetch_assoc();
        $disbursed_amount = floatval($items_sum_row['total'] ?? 0);
    }
    $items_sum_stmt->close();
}

// คำนวณเปอร์เซ็นต์และยอดคงเหลือ
$disbursement_percentage = $budget_amount > 0 ? round(($disbursed_amount / $budget_amount) * 100) : 0;
if ($disbursement_percentage > 100) { $disbursement_percentage = 100; } 
$remaining_amount = $budget_amount - $disbursed_amount;

// --- ดึงข้อมูล fund_type_selections ---
$create_fund_table_sql = "CREATE TABLE IF NOT EXISTS `fund_type_selections` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `fund_name` varchar(50) NOT NULL,
    `selection_count` int(11) NOT NULL DEFAULT 0,
    `table_source` varchar(50) DEFAULT NULL,
    `proposal_id` int(11) DEFAULT NULL,
    `selected_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `year` int(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_fund_name` (`fund_name`),
    KEY `idx_year` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
$conn->query($create_fund_table_sql);

$current_year = date('Y') + 543;
$fund_summary_sql = "SELECT 
    fund_name, 
    COUNT(*) as total_selections,
    COUNT(DISTINCT proposal_id) as unique_proposals
FROM fund_type_selections 
WHERE year = ?
GROUP BY fund_name
ORDER BY total_selections DESC";

$fund_summary_stmt = $conn->prepare($fund_summary_sql);
$fund_summary = [];
if ($fund_summary_stmt) {
    $fund_summary_stmt->bind_param("i", $current_year);
    $fund_summary_stmt->execute();
    $fund_summary_result = $fund_summary_stmt->get_result();
    while ($row = $fund_summary_result->fetch_assoc()) {
        $fund_summary[] = $row;
    }
    $fund_summary_stmt->close();
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/status_summary.css" />
    <title>สรุปสถานะการยื่นขอทุน</title>
    <style>
        body { font-family: 'Kanit', sans-serif; }
    </style>
</head>
<body class="bg-base-200/40 text-base-content selection:bg-primary/30">
    <div class="min-h-screen p-4 md:p-8 lg:p-10">

        <div class="w-full max-w-6xl mx-auto mb-8">
            <div class="relative rounded-3xl bg-base-100 p-6 md:p-8 shadow-sm border border-base-300 overflow-hidden group hover:shadow-md transition-shadow">
                <div class="absolute -top-10 -right-10 w-40 h-40 bg-primary/5 rounded-full blur-3xl group-hover:bg-primary/10 transition-colors duration-500"></div>
                <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-info/5 rounded-full blur-3xl group-hover:bg-info/10 transition-colors duration-500"></div>
                
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
                    <div class="space-y-1">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-2.5 h-2.5 bg-success rounded-full status-indicator shadow-[0_0_8px_rgba(54,211,153,0.6)]"></div>
                            <span class="text-xs font-semibold text-success tracking-wider uppercase">Online System</span>
                        </div>
                        <h1 class="text-2xl md:text-4xl font-bold text-base-content tracking-tight">สรุปสถานะการยื่นขอทุน</h1>
                        <p class="text-sm md:text-base text-base-content/60 font-medium">ภาพรวมข้อมูลจากหน้าแสดงสถานะคำขอ อัปเดตแบบเรียลไทม์</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-8">
            
            <div class="flex flex-col gap-6 md:gap-8">
                <div class="rounded-2xl bg-base-100 border border-base-300 shadow-sm hover:shadow-md transition-shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-base-200 bg-base-100/50">
                        <h2 class="text-lg font-semibold flex items-center gap-2">
                            <span>📊</span> สัดส่วนสถานะคำขอ
                        </h2>
                    </div>
                    <div class="p-4 md:p-6">
                        <div class="chart-container relative h-[300px] w-full"><canvas id="statusPie"></canvas></div>
                    </div>
                </div>

                <div class="rounded-2xl bg-base-100 border border-base-300 shadow-sm hover:shadow-md transition-shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-base-200 bg-base-100/50">
                        <h2 class="text-lg font-semibold flex items-center gap-2">
                            <span>📈</span> จำนวนผู้ยื่นตามประเภทผู้ยื่น
                        </h2>
                    </div>
                    <div class="p-6 scroll-area max-h-[350px] overflow-y-auto">
                        <?php if (empty($applicant_type_summary)): ?>
                            <div class="text-center py-8 text-base-content/40">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p>ยังไม่มีข้อมูลการยื่นคำขอ</p>
                            </div>
                        <?php else: ?>
                            <?php $total_applicant_type = array_sum($applicant_type_summary); ?>
                            <div class="space-y-5">
                                <?php foreach ($applicant_type_summary as $label => $count): ?>
                                    <?php $percentage = $total_applicant_type > 0 ? round(($count / $total_applicant_type) * 100, 1) : 0; ?>
                                    <div>
                                        <div class="flex justify-between items-end mb-2">
                                            <div>
                                                <div class="font-medium text-base-content leading-tight"><?php echo htmlspecialchars($label); ?></div>
                                                <div class="text-xs text-base-content/50 mt-1">ยื่นแล้ว <?php echo number_format($count); ?> ครั้ง</div>
                                            </div>
                                            <div class="text-sm font-bold text-base-content/80"><?php echo $percentage; ?>%</div>
                                        </div>
                                        <div class="w-full bg-base-200 rounded-full h-2 overflow-hidden">
                                            <div class="bg-primary h-full rounded-full transition-all duration-700 ease-out" style="width: <?php echo $percentage; ?>%;"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-6 md:gap-8">
                
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    
                <div class="bg-base-100 rounded-2xl border border-base-200 p-5 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all flex flex-col justify-between">
                    <div class="flex justify-between items-start mb-2">
                        <div class="text-sm font-medium text-base-content/60 mt-1">คำขอทั้งหมด</div>
                        <div class="p-2 bg-primary/10 rounded-xl text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-primary"><?php echo number_format($total_requests); ?></div>
                </div>
                
                <div class="bg-base-100 rounded-2xl border border-base-200 p-5 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all flex flex-col justify-between">
                    <div class="flex justify-between items-start mb-2">
                        <div class="text-sm font-medium text-base-content/60 mt-1">รออนุมัติ</div>
                        <div class="p-2 bg-warning/10 rounded-xl text-warning">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-warning"><?php echo number_format($status_counts['รออนุมัติ']); ?></div>
                </div>

                <div class="bg-base-100 rounded-2xl border border-base-200 p-5 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all flex flex-col justify-between">
                    <div class="flex justify-between items-start mb-2">
                        <div class="text-sm font-medium text-base-content/60 mt-1">อนุมัติ</div>
                        <div class="p-2 bg-success/10 rounded-xl text-success">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-success"><?php echo number_format($status_counts['อนุมัติ']); ?></div>
                </div>

                <div class="bg-base-100 rounded-2xl border border-base-200 p-5 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all flex flex-col justify-between">
                    <div class="flex justify-between items-start mb-2">
                        <div class="text-sm font-medium text-base-content/60 mt-1">ปฏิเสธ</div>
                        <div class="p-2 bg-error/10 rounded-xl text-error">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-error"><?php echo number_format($rejected_cancelled); ?></div>
                </div>

                 </div>

                <div class="rounded-2xl bg-base-100 border border-base-300 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-base-200 bg-base-100/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <h2 class="text-lg font-semibold flex items-center gap-2">
                            <span>💰</span> สรุปการเบิกจ่าย
                        </h2>
                        <form method="get" class="flex items-center gap-2">
                            <select name="fiscal_year" class="select select-bordered select-sm bg-base-100">
                                <?php foreach ($available_years as $year): ?>
                                    <option value="<?php echo $year; ?>" <?php echo $year === $current_fiscal_year ? 'selected' : ''; ?>>ปี <?php echo $year; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm">ดูปี</button>
                        </form>
                    </div>
                    
                    <div class="p-6 md:p-8 text-center">
                        <div class="text-sm text-base-content/60 mb-2">ปีงบประมาณ</div>
                        <div class="text-3xl font-bold text-base-content mb-4"><?php echo $current_fiscal_year; ?></div>
                        <div class="text-sm text-base-content/60 mb-2">ยอดที่เบิกแล้ว</div>
                        <div class="text-3xl font-bold text-primary"><?php echo number_format($disbursed_amount, 2); ?> บาท</div>
                    </div>
                </div>

                <div class="rounded-2xl bg-base-100 border border-base-300 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-base-200 bg-base-100/50">
                        <h2 class="text-lg font-semibold flex items-center gap-2">
                            <span>📌</span> ประเภททุนที่ถูกเลือก (ปี <?php echo $current_year; ?>)
                        </h2>
                    </div>
                    
                    <div class="p-6 scroll-area max-h-[350px] overflow-y-auto">
                        <?php if (empty($fund_summary)): ?>
                            <div class="text-center py-8 text-base-content/40">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p>ยังไม่มีข้อมูลการเลือกประเภททุน</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-5">
                                <?php 
                                $total_all_selections = array_sum(array_column($fund_summary, 'total_selections'));
                                foreach ($fund_summary as $fund): 
                                    $percentage = $total_all_selections > 0 ? round(($fund['total_selections'] / $total_all_selections) * 100, 1) : 0;
                                ?>
                                    <div>
                                        <div class="flex justify-between items-end mb-2">
                                            <div>
                                                <div class="font-medium text-base-content leading-tight"><?php echo htmlspecialchars($fund['fund_name']); ?></div>
                                                <div class="text-xs text-base-content/50 mt-1">ถูกเลือก <?php echo number_format($fund['total_selections']); ?> ครั้ง</div>
                                            </div>
                                            <div class="text-sm font-bold text-base-content/80"><?php echo $percentage; ?>%</div>
                                        </div>
                                        <div class="w-full bg-base-200 rounded-full h-2 overflow-hidden">
                                            <div class="bg-primary h-full rounded-full transition-all duration-700 ease-out" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <script id="status-summary-data" type="application/json">
      <?php echo json_encode([
        'statusCounts' => $status_counts,
        'applicantTypeLabels' => $applicantTypeLabels,
        'applicantTypeCounts' => $applicantTypeCounts,
      ], JSON_UNESCAPED_UNICODE); ?>
    </script>
    <script src="assets/status_summary.js"></script>
</body>
</html>