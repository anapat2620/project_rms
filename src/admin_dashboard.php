<?php
session_start();

// Debug: บันทึกข้อมูล session
error_log("Admin dashboard - Session data: " . json_encode($_SESSION));

// ตรวจสอบสิทธิ์การเข้าถึง (เฉพาะ admin หรือผู้มีสิทธิ์)
if (!isset($_SESSION['Position']) || !in_array($_SESSION['Position'], ['Admin'])) {
    error_log("Access denied - Position: " . ($_SESSION['Position'] ?? 'not set'));
    header('Location: index.php');
    exit();
}

// การเชื่อมต่อฐานข้อมูล (ใช้ config กลาง)
require_once __DIR__ . '/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

// ใช้การเชื่อมต่อเดียวกันสำหรับ disbursement summary
$research_conn = $conn;

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
$research_conn->query($create_table_sql);

// สร้างตาราง disbursement_items สำหรับรายการเบิกจ่ายแต่ละรายการ
$create_items_table_sql = "CREATE TABLE IF NOT EXISTS `disbursement_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `fiscal_year` int(11) NOT NULL,
    `description` varchar(500) NOT NULL COMMENT 'รายละเอียดการเบิกจ่าย',
    `amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'จำนวนเงิน',
    `disbursement_date` date NOT NULL COMMENT 'วันที่เบิกจ่าย',
    `created_by` varchar(255) NOT NULL COMMENT 'ผู้สร้าง',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_fiscal_year` (`fiscal_year`),
    KEY `idx_disbursement_date` (`disbursement_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
$research_conn->query($create_items_table_sql);

// ดึงข้อมูล disbursement summary
$current_fiscal_year = date('Y') + 543;
$disbursement_data = [
    'fiscal_year' => $current_fiscal_year,
    'budget_amount' => 25000000.00,
    'disbursed_amount' => 14500000.00
];

$disbursement_sql = "SELECT * FROM disbursement_summary WHERE fiscal_year = ?";
$disbursement_stmt = $research_conn->prepare($disbursement_sql);
if ($disbursement_stmt) {
    $disbursement_stmt->bind_param("i", $current_fiscal_year);
    $disbursement_stmt->execute();
    $disbursement_result = $disbursement_stmt->get_result();
    if ($disbursement_result->num_rows > 0) {
        $disbursement_data = $disbursement_result->fetch_assoc();
    }
    $disbursement_stmt->close();
}

// ดึงรายการเบิกจ่ายแต่ละรายการ
$disbursement_items = [];
$items_sql = "SELECT * FROM disbursement_items WHERE fiscal_year = ? ORDER BY disbursement_date DESC, id DESC";
$items_stmt = $research_conn->prepare($items_sql);
if ($items_stmt) {
    $items_stmt->bind_param("i", $current_fiscal_year);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    while ($row = $items_result->fetch_assoc()) {
        $disbursement_items[] = $row;
    }
    $items_stmt->close();
}

// คำนวณยอดเบิกจ่ายรวมจากรายการทั้งหมด
$calculated_disbursed = 0;
foreach ($disbursement_items as $item) {
    $calculated_disbursed += floatval($item['amount']);
}

// อัปเดต disbursed_amount ใน disbursement_data ถ้ามีรายการ
if (count($disbursement_items) > 0) {
    $disbursement_data['disbursed_amount'] = $calculated_disbursed;
}
// ไม่ต้องปิดการเชื่อมต่อเพราะใช้ $conn เดียวกัน

// ดึงสถิติต่างๆ
$stats = [];

// จำนวนผู้ใช้ทั้งหมด
$sql = "SELECT COUNT(*) as total_users FROM data";
$result = $conn->query($sql);
$stats['total_users'] = $result->fetch_assoc()['total_users'];

// จำนวนผู้ใช้แยกตามตำแหน่ง
$sql = "SELECT Position, COUNT(*) as count FROM data GROUP BY Position ORDER BY count DESC";
$result = $conn->query($sql);
$stats['users_by_position'] = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $stats['users_by_position'][] = $row;
    }
}

// จำนวนคณะทั้งหมด
$sql = "SELECT COUNT(*) as total_faculties FROM faculties";
$result = $conn->query($sql);
$stats['total_faculties'] = $result ? $result->fetch_assoc()['total_faculties'] : 0;

// จำนวนข่าวทั้งหมด
$sql = "SELECT COUNT(*) as total_news FROM news_board";
$result = $conn->query($sql);
$stats['total_news'] = $result ? $result->fetch_assoc()['total_news'] : 0;

// ผู้ใช้ล่าสุด
$sql = "SELECT Username, Email, Position, Facuity FROM data ORDER BY ID DESC LIMIT 5";
$result = $conn->query($sql);
$stats['recent_users'] = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $stats['recent_users'][] = $row;
    }
}

// ดึงข้อมูล fund_support
$fund_support_data = [];
$fund_support_sql = "SELECT * FROM fund_support ORDER BY FunID";
$fund_support_result = $conn->query($fund_support_sql);
if ($fund_support_result && $fund_support_result->num_rows > 0) {
    while ($row = $fund_support_result->fetch_assoc()) {
        $fund_support_data[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MSU Research Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin_dashboard.css">
</head>
<body class="gradient-bg min-h-screen">
    <div class="container mx-auto px-4 py-6 max-w-7xl">
        <!-- Header -->
        <div class="text-center mb-6 fade-in">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-2 drop-shadow-lg">
                <i class="fas fa-shield-alt mr-3"></i>Admin Dashboard
            </h1>
            <p class="text-white/90 text-lg">ระบบจัดการทุนวิจัย มหาวิทยาลัยมหาสารคาม</p>
            <div class="mt-3 inline-flex items-center gap-2 bg-white/20 backdrop-blur-sm px-4 py-2 rounded-full">
                <i class="fas fa-user-circle text-white"></i>
                <p class="text-white font-medium">ยินดีต้อนรับ <?php echo htmlspecialchars($_SESSION['Username']); ?> (<?php echo htmlspecialchars($_SESSION['Position']); ?>)</p>
            </div>
        </div>

        <!-- Navigation -->
        <div class="flex justify-center mb-6 gap-3 fade-in">
            <a href="index.php" class="nav-btn bg-white/20 backdrop-blur-sm text-white px-6 py-3 rounded-xl hover:bg-white/30 transition-all duration-300 shadow-lg hover:shadow-xl">
                <i class="fas fa-home mr-2"></i>ไปหน้าหลัก
            </a>
            <a href="controller/logout.php" class="nav-btn bg-red-500/90 backdrop-blur-sm text-white px-6 py-3 rounded-xl hover:bg-red-600 transition-all duration-300 shadow-lg hover:shadow-xl">
                <i class="fas fa-sign-out-alt mr-2"></i>ออกจากระบบ
            </a>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="stat-card-item card rounded-xl shadow-xl p-5 stat-card hover-scale">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="p-3 rounded-xl bg-white/25 backdrop-blur-sm">
                            <i class="fas fa-users text-2xl text-white"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-white/70 text-xs uppercase tracking-wide">ผู้ใช้ทั้งหมด</p>
                            <p class="text-3xl font-bold text-white mt-1"><?php echo number_format($stats['total_users']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="stat-card-item card rounded-xl shadow-xl p-5 stat-card hover-scale">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="p-3 rounded-xl bg-white/25 backdrop-blur-sm">
                            <i class="fas fa-university text-2xl text-white"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-white/70 text-xs uppercase tracking-wide">คณะทั้งหมด</p>
                            <p class="text-3xl font-bold text-white mt-1"><?php echo number_format($stats['total_faculties']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="stat-card-item card rounded-xl shadow-xl p-5 stat-card hover-scale">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="p-3 rounded-xl bg-white/25 backdrop-blur-sm">
                            <i class="fas fa-newspaper text-2xl text-white"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-white/70 text-xs uppercase tracking-wide">ข่าวสาร</p>
                            <p class="text-3xl font-bold text-white mt-1"><?php echo number_format($stats['total_news']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="stat-card-item card rounded-xl shadow-xl p-5 stat-card hover-scale">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="p-3 rounded-xl bg-white/25 backdrop-blur-sm">
                            <i class="fas fa-check-circle text-2xl text-white"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-white/70 text-xs uppercase tracking-wide">สถานะระบบ</p>
                            <p class="text-lg font-bold text-white mt-1">พร้อมใช้งาน</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
            <!-- Users by Position -->
            <div class="card rounded-xl shadow-xl p-6 hover-lift">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-chart-pie mr-3 text-purple-600"></i>
                    ผู้ใช้แยกตามตำแหน่ง
                </h2>
                <div class="space-y-2 max-h-64 overflow-y-auto custom-scrollbar">
                    <?php if (empty($stats['users_by_position'])): ?>
                        <p class="text-gray-500 text-center py-4">ไม่มีข้อมูล</p>
                    <?php else: ?>
                        <?php foreach ($stats['users_by_position'] as $position): ?>
                            <div class="flex justify-between items-center p-3 bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg hover:from-blue-50 hover:to-blue-100 transition-all duration-300 border border-gray-200 hover:border-blue-300">
                                <span class="font-medium text-gray-700"><?php echo htmlspecialchars($position['Position']); ?></span>
                                <span class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-4 py-1.5 rounded-full text-sm font-semibold shadow-md">
                                    <?php echo number_format($position['count']); ?> คน
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="card rounded-xl shadow-xl p-6 hover-lift">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-clock mr-3 text-orange-600"></i>
                    ผู้ใช้ล่าสุด
                </h2>
                <div class="space-y-2 max-h-64 overflow-y-auto custom-scrollbar">
                    <?php if (empty($stats['recent_users'])): ?>
                        <p class="text-gray-500 text-center py-4">ไม่มีข้อมูล</p>
                    <?php else: ?>
                        <?php foreach ($stats['recent_users'] as $user): ?>
                            <div class="flex items-center p-3 bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg hover:from-orange-50 hover:to-orange-100 transition-all duration-300 border border-gray-200 hover:border-orange-300">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white font-semibold mr-3 shadow-md">
                                    <?php echo strtoupper(substr($user['Username'], 0, 1)); ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-800 truncate"><?php echo htmlspecialchars($user['Username']); ?></p>
                                    <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($user['Email']); ?></p>
                                </div>
                                <span class="text-xs bg-orange-100 text-orange-800 px-2 py-1 rounded-md font-medium ml-2 whitespace-nowrap">
                                    <?php echo htmlspecialchars($user['Position']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mb-6">
            <div class="card rounded-xl shadow-xl p-6 hover-lift">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-bolt mr-3 text-yellow-600"></i>
                    การดำเนินการด่วน
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <a href="add_user.php" class="quick-action-btn bg-gradient-to-br from-blue-500 to-blue-600 text-white p-4 rounded-xl text-center hover:from-blue-600 hover:to-blue-700 transition-all duration-300 shadow-lg hover:shadow-xl hover-scale">
                        <i class="fas fa-user-plus text-2xl mb-2"></i>
                        <p class="font-semibold text-sm">เพิ่มผู้ใช้</p>
                    </a>
                    <a href="add_faculty.php" class="quick-action-btn bg-gradient-to-br from-green-500 to-green-600 text-white p-4 rounded-xl text-center hover:from-green-600 hover:to-green-700 transition-all duration-300 shadow-lg hover:shadow-xl hover-scale">
                        <i class="fas fa-university text-2xl mb-2"></i>
                        <p class="font-semibold text-sm">เพิ่มคณะ</p>
                    </a>
                    <a href="news_management.php" class="quick-action-btn bg-gradient-to-br from-purple-500 to-purple-600 text-white p-4 rounded-xl text-center hover:from-purple-600 hover:to-purple-700 transition-all duration-300 shadow-lg hover:shadow-xl hover-scale">
                        <i class="fas fa-newspaper text-2xl mb-2"></i>
                        <p class="font-semibold text-sm">จัดการข่าว</p>
                    </a>
                    <a href="user_management.php" class="quick-action-btn bg-gradient-to-br from-orange-500 to-orange-600 text-white p-4 rounded-xl text-center hover:from-orange-600 hover:to-orange-700 transition-all duration-300 shadow-lg hover:shadow-xl hover-scale">
                        <i class="fas fa-cogs text-2xl mb-2"></i>
                        <p class="font-semibold text-sm">จัดการผู้ใช้งาน</p>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- ระบบทุนวิจัย - ลิงก์ด่วนล่าสุด -->
        <div class="mb-6">
            <div class="card rounded-xl shadow-xl p-6 hover-lift">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-link mr-3 text-blue-600"></i>
                    ระบบทุนวิจัย - ลิงก์ด่วน
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <a href="status_summary.php" class="research-link-btn bg-white border-2 border-blue-200 hover:border-blue-400 hover:bg-blue-50 p-4 rounded-xl text-center transition-all duration-300 shadow-md hover:shadow-lg hover-scale">
                        <i class="fas fa-chart-pie text-2xl mb-2 text-blue-600"></i>
                        <p class="font-semibold text-sm text-gray-800">สรุปสถานะ</p>
                    </a>
                    <a href="#fundSupportSection" onclick="showSection('fundSupportSection'); return false;" class="research-link-btn bg-white border-2 border-pink-200 hover:border-pink-400 hover:bg-pink-50 p-4 rounded-xl text-center transition-all duration-300 shadow-md hover:shadow-lg hover-scale">
                        <i class="fas fa-hand-holding-heart text-2xl mb-2 text-pink-600"></i>
                        <p class="font-semibold text-sm text-gray-800">จัดการทุนสนับสนุน</p>
                    </a>
                    <a href="#disbursementSection" onclick="showSection('disbursementSection'); return false;" class="research-link-btn bg-white border-2 border-green-200 hover:border-green-400 hover:bg-green-50 p-4 rounded-xl text-center transition-all duration-300 shadow-md hover:shadow-lg hover-scale">
                        <i class="fas fa-money-bill-wave text-2xl mb-2 text-green-600"></i>
                        <p class="font-semibold text-sm text-gray-800">จัดการเบิกจ่าย</p>
                    </a>
                </div>
            </div>
        </div>

        <!-- จัดการข้อมูลทุนสนับสนุน (Fund Support) -->
        <div id="fundSupportSection" class="mb-6 hidden">
            <div class="card rounded-xl shadow-xl p-6 hover-lift">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center">
                        <i class="fas fa-hand-holding-heart mr-3 text-pink-600"></i>
                        จัดการข้อมูลทุนสนับสนุน (Fund Support)
                    </h2>
                    <button onclick="hideSection('fundSupportSection')" class="text-gray-500 hover:text-gray-700 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <p class="text-sm text-gray-600 mb-4">แก้ไขข้อมูลทุนสนับสนุนต่างๆ ในระบบ</p>
                
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gradient-to-r from-pink-500 to-pink-600 text-white">
                                <th class="px-4 py-3 text-left border border-pink-300">รหัสทุน</th>
                                <th class="px-4 py-3 text-left border border-pink-300">ชื่อทุน</th>
                                <th class="px-4 py-3 text-center border border-pink-300">BH1 (%)</th>
                                <th class="px-4 py-3 text-center border border-pink-300">BH2 (%)</th>
                                <th class="px-4 py-3 text-center border border-pink-300">B3 (%)</th>
                                <th class="px-4 py-3 text-right border border-pink-300">จำนวนเงิน (บาท)</th>
                                <th class="px-4 py-3 text-center border border-pink-300">ปี (พ.ศ.)</th>
                                <th class="px-4 py-3 text-center border border-pink-300">การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="fundSupportTableBody">
                            <?php if (empty($fund_support_data)): ?>
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-500 border border-gray-200">
                                        ไม่มีข้อมูลทุนสนับสนุน
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($fund_support_data as $fund): ?>
                                    <tr class="fund-row hover:bg-gray-50 transition-colors border-b border-gray-200" data-funid="<?php echo htmlspecialchars($fund['FunID']); ?>">
                                        <td class="px-4 py-3 border border-gray-200">
                                            <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($fund['FunID']); ?></span>
                                        </td>
                                        <td class="px-4 py-3 border border-gray-200">
                                            <input 
                                                type="text" 
                                                name="FunName" 
                                                value="<?php echo htmlspecialchars($fund['FunName']); ?>" 
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm"
                                                maxlength="50"
                                            />
                                        </td>
                                        <td class="px-4 py-3 border border-gray-200">
                                            <input 
                                                type="number" 
                                                name="BH1" 
                                                value="<?php echo htmlspecialchars($fund['BH1']); ?>" 
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm text-center"
                                                min="0"
                                                max="100"
                                            />
                                        </td>
                                        <td class="px-4 py-3 border border-gray-200">
                                            <input 
                                                type="number" 
                                                name="BH2" 
                                                value="<?php echo htmlspecialchars($fund['BH2']); ?>" 
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm text-center"
                                                min="0"
                                                max="100"
                                            />
                                        </td>
                                        <td class="px-4 py-3 border border-gray-200">
                                            <input 
                                                type="number" 
                                                name="B3" 
                                                value="<?php echo htmlspecialchars($fund['B3']); ?>" 
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm text-center"
                                                min="0"
                                                max="100"
                                            />
                                        </td>
                                        <td class="px-4 py-3 border border-gray-200">
                                            <input 
                                                type="number" 
                                                name="TH_Bath" 
                                                value="<?php echo number_format($fund['TH_Bath'], 2, '.', ''); ?>" 
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm text-right"
                                                min="0"
                                                step="0.01"
                                            />
                                        </td>
                                        <td class="px-4 py-3 border border-gray-200">
                                            <input 
                                                type="number" 
                                                name="Year" 
                                                value="<?php echo htmlspecialchars($fund['Year']); ?>" 
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm text-center"
                                                min="2500"
                                                max="2600"
                                            />
                                        </td>
                                        <td class="px-4 py-3 border border-gray-200">
                                            <button 
                                                type="button" 
                                                onclick="updateFundSupport('<?php echo htmlspecialchars($fund['FunID']); ?>')"
                                                class="px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all duration-300 shadow-md hover:shadow-lg text-sm font-semibold"
                                            >
                                                <i class="fas fa-save mr-1"></i>บันทึก
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- เพิ่มทุนใหม่ -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-plus-circle mr-2 text-green-600"></i>
                        เพิ่มทุนสนับสนุนใหม่
                    </h3>
                    <form id="addFundSupportForm" class="grid grid-cols-1 md:grid-cols-7 gap-3">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">รหัสทุน *</label>
                            <input 
                                type="text" 
                                name="FunID" 
                                id="newFunID"
                                class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm"
                                required
                                maxlength="10"
                                placeholder="เช่น F011"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">ชื่อทุน *</label>
                            <input 
                                type="text" 
                                name="FunName" 
                                id="newFunName"
                                class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm"
                                required
                                maxlength="50"
                                placeholder="ชื่อทุน"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">BH1 (%) *</label>
                            <input 
                                type="number" 
                                name="BH1" 
                                id="newBH1"
                                class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm text-center"
                                required
                                min="0"
                                max="100"
                                value="0"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">BH2 (%) *</label>
                            <input 
                                type="number" 
                                name="BH2" 
                                id="newBH2"
                                class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm text-center"
                                required
                                min="0"
                                max="100"
                                value="0"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">B3 (%) *</label>
                            <input 
                                type="number" 
                                name="B3" 
                                id="newB3"
                                class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm text-center"
                                required
                                min="0"
                                max="100"
                                value="0"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">จำนวนเงิน (บาท) *</label>
                            <input 
                                type="number" 
                                name="TH_Bath" 
                                id="newTH_Bath"
                                class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm text-right"
                                required
                                min="0"
                                step="0.01"
                                value="0.00"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">ปี (พ.ศ.) *</label>
                            <input 
                                type="number" 
                                name="Year" 
                                id="newYear"
                                class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm text-center"
                                required
                                min="2500"
                                max="2600"
                                value="<?php echo date('Y') + 543; ?>"
                            />
                        </div>
                        <div class="md:col-span-7 flex justify-end gap-3 pt-2">
                            <button 
                                type="button" 
                                onclick="resetAddForm()" 
                                class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all duration-300 font-semibold"
                            >
                                <i class="fas fa-undo mr-2"></i>รีเซ็ต
                            </button>
                            <button 
                                type="submit" 
                                class="px-6 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:from-green-600 hover:to-green-700 transition-all duration-300 shadow-lg hover:shadow-xl font-semibold"
                            >
                                <i class="fas fa-plus mr-2"></i>เพิ่มทุนใหม่
                            </button>
                        </div>
                    </form>
                    <div id="addFundMessage" class="hidden mt-4 p-4 rounded-lg"></div>
                </div>
                
                <div id="fundSupportMessage" class="hidden mt-4 p-4 rounded-lg"></div>
            </div>
        </div>

       <!-- จัดการสรุปการเบิกจ่ายทุน -->
       <div id="disbursementSection" class="mb-6 hidden">
            <div class="card rounded-xl shadow-xl p-6 hover-lift">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center">
                        <i class="fas fa-money-bill-wave mr-3 text-green-600"></i>
                        จัดการสรุปการเบิกจ่ายทุน
                    </h2>
                    <button onclick="hideSection('disbursementSection')" class="text-gray-500 hover:text-gray-700 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <p class="text-sm text-gray-600 mb-4">แก้ไขข้อมูลสรุปการเบิกจ่ายทุนที่แสดงในหน้า status_summary</p>
                
                <form id="disbursementForm" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-calendar-alt mr-2 text-blue-600"></i>ปีงบประมาณ (พ.ศ.)
                            </label>
                            <input 
                                type="number" 
                                name="fiscal_year" 
                                id="fiscal_year"
                                value="<?php echo htmlspecialchars($disbursement_data['fiscal_year']); ?>" 
                                class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all"
                                required
                                min="2500"
                                max="2600"
                            />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-wallet mr-2 text-purple-600"></i>งบประมาณทั้งหมด (บาท)
                            </label>
                            <input 
                                type="number" 
                                name="budget_amount" 
                                id="budget_amount"
                                value="<?php echo number_format($disbursement_data['budget_amount'], 2, '.', ''); ?>" 
                                class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-purple-500 focus:ring-2 focus:ring-purple-200 transition-all"
                                required
                                min="0"
                                step="0.01"
                                placeholder="0.00"
                            />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-hand-holding-usd mr-2 text-green-600"></i>เบิกจ่ายไปแล้ว (บาท)
                            </label>
                            <input 
                                type="text" 
                                id="disbursed_amount_display"
                                value="<?php echo number_format($disbursement_data['disbursed_amount'], 2); ?>" 
                                class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed"
                                readonly
                            />
                            <input 
                                type="hidden" 
                                name="disbursed_amount" 
                                id="disbursed_amount"
                                value="<?php echo number_format($disbursement_data['disbursed_amount'], 2, '.', ''); ?>"
                            />
                            <p class="text-xs text-gray-500 mt-1">* คำนวณอัตโนมัติจากรายการเบิกจ่าย</p>
                        </div>
                    </div>
                    
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                            <span class="font-semibold text-blue-800">สรุปข้อมูล</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                            <div>
                                <span class="text-gray-600">เบิกจ่ายรวม:</span>
                                <span class="font-bold text-green-700 ml-2" id="total_disbursed_summary"><?php echo number_format($disbursement_data['disbursed_amount'], 2); ?></span>
                                <span class="text-gray-600">บาท</span>
                            </div>
                            <div>
                                <span class="text-gray-600">คงเหลือ:</span>
                                <span class="font-bold text-blue-700 ml-2" id="remaining_amount"><?php echo number_format($disbursement_data['budget_amount'] - $disbursement_data['disbursed_amount'], 2); ?></span>
                                <span class="text-gray-600">บาท</span>
                            </div>
                            <div>
                                <span class="text-gray-600">เปอร์เซ็นต์ที่เบิกจ่าย:</span>
                                <span class="font-bold text-green-700 ml-2" id="disbursement_percentage">
                                    <?php 
                                    $percentage = $disbursement_data['budget_amount'] > 0 
                                        ? round(($disbursement_data['disbursed_amount'] / $disbursement_data['budget_amount']) * 100) 
                                        : 0;
                                    echo $percentage > 100 ? 100 : $percentage;
                                    ?>
                                </span>
                                <span class="text-gray-600">%</span>
                            </div>
                        </div>
                        <div class="mt-2 text-xs text-gray-600">
                            <span>อัปเดตล่าสุด:</span>
                            <span class="font-medium text-gray-700 ml-2">
                                <?php echo isset($disbursement_data['updated_at']) ? date('d/m/Y H:i', strtotime($disbursement_data['updated_at'])) : 'ยังไม่มีการอัปเดต'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button 
                            type="button" 
                            onclick="resetBudgetForm()" 
                            class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all duration-300 font-semibold"
                        >
                            <i class="fas fa-undo mr-2"></i>รีเซ็ต
                        </button>
                        <button 
                            type="submit" 
                            class="px-6 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:from-green-600 hover:to-green-700 transition-all duration-300 shadow-lg hover:shadow-xl font-semibold"
                        >
                            <i class="fas fa-save mr-2"></i>บันทึกข้อมูล
                        </button>
                    </div>
                    
                    <div id="formMessage" class="hidden mt-4 p-4 rounded-lg"></div>
                </form>
                
                <!-- ตารางรายการเบิกจ่าย -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-list mr-2 text-blue-600"></i>
                        รายการเบิกจ่ายแต่ละรายการ
                    </h3>
                    
                    <div class="overflow-x-auto mb-4">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-gradient-to-r from-green-500 to-green-600 text-white">
                                    <th class="px-4 py-3 text-left border border-green-300">วันที่เบิกจ่าย</th>
                                    <th class="px-4 py-3 text-left border border-green-300">รายละเอียด</th>
                                    <th class="px-4 py-3 text-right border border-green-300">จำนวนเงิน (บาท)</th>
                                    <th class="px-4 py-3 text-center border border-green-300">ผู้สร้าง</th>
                                    <th class="px-4 py-3 text-center border border-green-300">การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody id="disbursementItemsTableBody">
                                <?php if (empty($disbursement_items)): ?>
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 border border-gray-200">
                                            ไม่มีรายการเบิกจ่าย
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($disbursement_items as $item): ?>
                                        <tr class="hover:bg-gray-50 transition-colors border-b border-gray-200" data-item-id="<?php echo htmlspecialchars($item['id']); ?>">
                                            <td class="px-4 py-3 border border-gray-200">
                                                <input 
                                                    type="date" 
                                                    name="disbursement_date" 
                                                    value="<?php echo htmlspecialchars($item['disbursement_date']); ?>" 
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm"
                                                />
                                            </td>
                                            <td class="px-4 py-3 border border-gray-200">
                                                <input 
                                                    type="text" 
                                                    name="description" 
                                                    value="<?php echo htmlspecialchars($item['description']); ?>" 
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm"
                                                    maxlength="500"
                                                />
                                            </td>
                                            <td class="px-4 py-3 border border-gray-200">
                                                <input 
                                                    type="number" 
                                                    name="amount" 
                                                    value="<?php echo number_format($item['amount'], 2, '.', ''); ?>" 
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm text-right"
                                                    min="0"
                                                    step="0.01"
                                                />
                                            </td>
                                            <td class="px-4 py-3 border border-gray-200 text-center text-sm text-gray-600">
                                                <?php echo htmlspecialchars($item['created_by']); ?>
                                            </td>
                                            <td class="px-4 py-3 border border-gray-200">
                                                <div class="flex justify-center gap-2">
                                                    <button 
                                                        type="button" 
                                                        onclick="updateDisbursementItem(<?php echo htmlspecialchars($item['id']); ?>)"
                                                        class="px-3 py-1.5 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all duration-300 shadow-md hover:shadow-lg text-sm font-semibold"
                                                    >
                                                        <i class="fas fa-save mr-1"></i>บันทึก
                                                    </button>
                                                    <button 
                                                        type="button" 
                                                        onclick="deleteDisbursementItem(<?php echo htmlspecialchars($item['id']); ?>)"
                                                        class="px-3 py-1.5 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg hover:from-red-600 hover:to-red-700 transition-all duration-300 shadow-md hover:shadow-lg text-sm font-semibold"
                                                    >
                                                        <i class="fas fa-trash mr-1"></i>ลบ
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-100 font-semibold">
                                    <td colspan="2" class="px-4 py-3 border border-gray-200 text-right">รวมทั้งสิ้น:</td>
                                    <td class="px-4 py-3 border border-gray-200 text-right" id="total_disbursed">
                                        <?php echo number_format($calculated_disbursed, 2); ?>
                                    </td>
                                    <td colspan="2" class="px-4 py-3 border border-gray-200"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <!-- เพิ่มรายการใหม่ -->
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <h4 class="text-md font-bold text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-plus-circle mr-2 text-green-600"></i>
                            เพิ่มรายการเบิกจ่ายใหม่
                        </h4>
                        <form id="addDisbursementItemForm" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">วันที่เบิกจ่าย *</label>
                                <input 
                                    type="date" 
                                    name="disbursement_date" 
                                    id="newDisbursementDate"
                                    value="<?php echo date('Y-m-d'); ?>"
                                    class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">รายละเอียด *</label>
                                <input 
                                    type="text" 
                                    name="description" 
                                    id="newDescription"
                                    class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm"
                                    required
                                    maxlength="500"
                                    placeholder="รายละเอียดการเบิกจ่าย"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">จำนวนเงิน (บาท) *</label>
                                <input 
                                    type="number" 
                                    name="amount" 
                                    id="newAmount"
                                    class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm text-right"
                                    required
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                />
                            </div>
                            <div class="flex items-end gap-2">
                                <button 
                                    type="button" 
                                    onclick="resetAddItemForm()" 
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all duration-300 font-semibold text-sm"
                                >
                                    <i class="fas fa-undo mr-1"></i>รีเซ็ต
                                </button>
                                <button 
                                    type="submit" 
                                    class="px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:from-green-600 hover:to-green-700 transition-all duration-300 shadow-lg hover:shadow-xl font-semibold text-sm"
                                >
                                    <i class="fas fa-plus mr-1"></i>เพิ่มรายการ
                                </button>
                            </div>
                        </form>
                        <div id="addItemMessage" class="hidden mt-4 p-4 rounded-lg"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/admin_dashboard.js"></script>
    <script>
        // คำนวณและอัปเดตข้อมูลสรุปแบบ real-time
        function updateSummary() {
            const budgetInput = document.getElementById('budget_amount');
            const remainingEl = document.getElementById('remaining_amount');
            const totalDisbursedEl = document.getElementById('total_disbursed');
            const totalDisbursedSummaryEl = document.getElementById('total_disbursed_summary');
            const percentageEl = document.getElementById('disbursement_percentage');
            const disbursedAmountInput = document.getElementById('disbursed_amount');
            const disbursedAmountDisplay = document.getElementById('disbursed_amount_display');
            
            if (!budgetInput) return;
            
            const budget = parseFloat(budgetInput.value) || 0;
            // คำนวณยอดเบิกจ่ายรวมจากรายการทั้งหมด
            let totalDisbursed = 0;
            const amountInputs = document.querySelectorAll('#disbursementItemsTableBody input[name="amount"]');
            amountInputs.forEach(input => {
                totalDisbursed += parseFloat(input.value) || 0;
            });
            
            const remaining = budget - totalDisbursed;
            const percentage = budget > 0 ? Math.round((totalDisbursed / budget) * 100) : 0;
            
            if (remainingEl) {
                remainingEl.textContent = remaining.toLocaleString('th-TH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
            
            if (totalDisbursedEl) {
                totalDisbursedEl.textContent = totalDisbursed.toLocaleString('th-TH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
            
            if (totalDisbursedSummaryEl) {
                totalDisbursedSummaryEl.textContent = totalDisbursed.toLocaleString('th-TH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
            
            if (disbursedAmountInput) {
                disbursedAmountInput.value = totalDisbursed.toFixed(2);
            }
            
            if (disbursedAmountDisplay) {
                disbursedAmountDisplay.value = totalDisbursed.toLocaleString('th-TH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
            
            if (percentageEl) {
                percentageEl.textContent = percentage > 100 ? 100 : percentage;
                percentageEl.className = percentage > 80 ? 'font-bold text-red-700 ml-2' : 
                                         percentage > 50 ? 'font-bold text-orange-700 ml-2' : 
                                         'font-bold text-green-700 ml-2';
            }
        }
        
        // เพิ่ม event listeners เมื่อ DOM พร้อม
        document.addEventListener('DOMContentLoaded', function() {
            const budgetInput = document.getElementById('budget_amount');
            if (budgetInput) {
                budgetInput.addEventListener('input', updateSummary);
            }
            
            // เพิ่ม event listener สำหรับทุก input amount ในตาราง
            document.addEventListener('input', function(e) {
                if (e.target.name === 'amount' && e.target.closest('#disbursementItemsTableBody')) {
                    updateSummary();
                }
            });
            
            // เรียก updateSummary ครั้งแรกเพื่อคำนวณค่าเริ่มต้น
            updateSummary();
        });
        
        // จัดการ form submission สำหรับงบประมาณ
        document.addEventListener('DOMContentLoaded', function() {
            const disbursementForm = document.getElementById('disbursementForm');
            if (disbursementForm) {
                disbursementForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    // ไม่ต้องส่ง disbursed_amount เพราะจะคำนวณจากรายการ
                    formData.delete('disbursed_amount');
                    
                    const messageDiv = document.getElementById('formMessage');
                    if (messageDiv) {
                        messageDiv.classList.remove('hidden');
                        messageDiv.innerHTML = '<div class="flex items-center"><i class="fas fa-spinner fa-spin mr-2"></i>กำลังบันทึกข้อมูล...</div>';
                        messageDiv.className = 'mt-4 p-4 rounded-lg bg-blue-100 text-blue-800';
                    }
                    
                    try {
                        const response = await fetch('controller/update_disbursement_summary.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (messageDiv) {
                            if (result.success) {
                                messageDiv.innerHTML = '<div class="flex items-center"><i class="fas fa-check-circle mr-2"></i>' + result.message + '</div>';
                                messageDiv.className = 'mt-4 p-4 rounded-lg bg-green-100 text-green-800';
                                
                                // รีเฟรชหน้าเว็บหลังจาก 2 วินาที
                                setTimeout(() => {
                                    window.location.reload();
                                }, 2000);
                            } else {
                                messageDiv.innerHTML = '<div class="flex items-center"><i class="fas fa-exclamation-circle mr-2"></i>' + result.message + '</div>';
                                messageDiv.className = 'mt-4 p-4 rounded-lg bg-red-100 text-red-800';
                            }
                        }
                    } catch (error) {
                        if (messageDiv) {
                            messageDiv.innerHTML = '<div class="flex items-center"><i class="fas fa-exclamation-triangle mr-2"></i>เกิดข้อผิดพลาด: ' + error.message + '</div>';
                            messageDiv.className = 'mt-4 p-4 rounded-lg bg-red-100 text-red-800';
                        }
                    }
                });
            }
        });
        
        // ฟังก์ชันรีเซ็ตฟอร์มงบประมาณ
        function resetBudgetForm() {
            const form = document.getElementById('disbursementForm');
            if (form) {
                form.reset();
            }
            updateSummary();
            const messageDiv = document.getElementById('formMessage');
            if (messageDiv) {
                messageDiv.classList.add('hidden');
            }
        }
        
        // ฟังก์ชันอัปเดตรายการเบิกจ่าย
        async function updateDisbursementItem(itemId) {
            const row = document.querySelector(`tr[data-item-id="${itemId}"]`);
            if (!row) {
                alert('ไม่พบรายการที่ต้องการอัปเดต');
                return;
            }
            
            const fiscalYearInput = document.getElementById('fiscal_year');
            if (!fiscalYearInput) {
                alert('ไม่พบข้อมูลปีงบประมาณ');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('item_id', itemId);
            formData.append('fiscal_year', fiscalYearInput.value);
            
            const dateInput = row.querySelector('input[name="disbursement_date"]');
            const descInput = row.querySelector('input[name="description"]');
            const amountInput = row.querySelector('input[name="amount"]');
            
            if (!dateInput || !descInput || !amountInput) {
                alert('ไม่พบข้อมูลในฟอร์ม');
                return;
            }
            
            formData.append('disbursement_date', dateInput.value);
            formData.append('description', descInput.value);
            formData.append('amount', amountInput.value);
            
            try {
                const response = await fetch('controller/manage_disbursement_items.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('อัปเดตรายการสำเร็จ');
                    window.location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + result.message);
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาด: ' + error.message);
            }
        }
        
        // ฟังก์ชันลบรายการเบิกจ่าย
        async function deleteDisbursementItem(itemId) {
            if (!confirm('คุณแน่ใจหรือไม่ว่าต้องการลบรายการนี้?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('item_id', itemId);
            
            try {
                const response = await fetch('controller/manage_disbursement_items.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('ลบรายการสำเร็จ');
                    window.location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + result.message);
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาด: ' + error.message);
            }
        }
        
        // จัดการ form เพิ่มรายการใหม่
        document.addEventListener('DOMContentLoaded', function() {
            const addDisbursementItemForm = document.getElementById('addDisbursementItemForm');
            if (addDisbursementItemForm) {
                addDisbursementItemForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    formData.append('action', 'add');
                    const fiscalYearInput = document.getElementById('fiscal_year');
                    if (fiscalYearInput) {
                        formData.append('fiscal_year', fiscalYearInput.value);
                    }
                    
                    const messageDiv = document.getElementById('addItemMessage');
                    if (messageDiv) {
                        messageDiv.classList.remove('hidden');
                        messageDiv.innerHTML = '<div class="flex items-center"><i class="fas fa-spinner fa-spin mr-2"></i>กำลังเพิ่มรายการ...</div>';
                        messageDiv.className = 'mt-4 p-4 rounded-lg bg-blue-100 text-blue-800';
                    }
                    
                    try {
                        const response = await fetch('controller/manage_disbursement_items.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (messageDiv) {
                            if (result.success) {
                                messageDiv.innerHTML = '<div class="flex items-center"><i class="fas fa-check-circle mr-2"></i>' + result.message + '</div>';
                                messageDiv.className = 'mt-4 p-4 rounded-lg bg-green-100 text-green-800';
                                
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1500);
                            } else {
                                messageDiv.innerHTML = '<div class="flex items-center"><i class="fas fa-exclamation-circle mr-2"></i>' + result.message + '</div>';
                                messageDiv.className = 'mt-4 p-4 rounded-lg bg-red-100 text-red-800';
                            }
                        }
                    } catch (error) {
                        if (messageDiv) {
                            messageDiv.innerHTML = '<div class="flex items-center"><i class="fas fa-exclamation-triangle mr-2"></i>เกิดข้อผิดพลาด: ' + error.message + '</div>';
                            messageDiv.className = 'mt-4 p-4 rounded-lg bg-red-100 text-red-800';
                        }
                    }
                });
            }
        });
        
        // ฟังก์ชันรีเซ็ตฟอร์มเพิ่มรายการ
        function resetAddItemForm() {
            const form = document.getElementById('addDisbursementItemForm');
            if (form) {
                form.reset();
            }
            const dateInput = document.getElementById('newDisbursementDate');
            if (dateInput) {
                dateInput.value = '<?php echo date('Y-m-d'); ?>';
            }
            const messageDiv = document.getElementById('addItemMessage');
            if (messageDiv) {
                messageDiv.classList.add('hidden');
            }
        }
        
        // ฟังก์ชันอัปเดต fund_support
        async function updateFundSupport(funID) {
            const row = document.querySelector(`tr[data-funid="${funID}"]`);
            if (!row) return;
            
            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('FunID', funID);
            formData.append('FunName', row.querySelector('input[name="FunName"]').value);
            formData.append('BH1', row.querySelector('input[name="BH1"]').value);
            formData.append('BH2', row.querySelector('input[name="BH2"]').value);
            formData.append('B3', row.querySelector('input[name="B3"]').value);
            formData.append('TH_Bath', row.querySelector('input[name="TH_Bath"]').value);
            formData.append('Year', row.querySelector('input[name="Year"]').value);
            
            const messageDiv = document.getElementById('fundSupportMessage');
            messageDiv.classList.remove('hidden');
            messageDiv.innerHTML = '<div class="flex items-center"><i class="fas fa-spinner fa-spin mr-2"></i>กำลังบันทึกข้อมูล...</div>';
            messageDiv.className = 'mt-4 p-4 rounded-lg bg-blue-100 text-blue-800';
            
            try {
                const response = await fetch('controller/update_fund_support.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    messageDiv.innerHTML = '<div class="flex items-center"><i class="fas fa-check-circle mr-2"></i>' + result.message + '</div>';
                    messageDiv.className = 'mt-4 p-4 rounded-lg bg-green-100 text-green-800';
                    
                    // รีเฟรชหน้าเว็บหลังจาก 2 วินาที
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    messageDiv.innerHTML = '<div class="flex items-center"><i class="fas fa-exclamation-circle mr-2"></i>' + result.message + '</div>';
                    messageDiv.className = 'mt-4 p-4 rounded-lg bg-red-100 text-red-800';
                }
            } catch (error) {
                messageDiv.innerHTML = '<div class="flex items-center"><i class="fas fa-exclamation-triangle mr-2"></i>เกิดข้อผิดพลาด: ' + error.message + '</div>';
                messageDiv.className = 'mt-4 p-4 rounded-lg bg-red-100 text-red-800';
            }
        }
        
        // จัดการ form เพิ่มทุนใหม่
        document.addEventListener('DOMContentLoaded', function() {
            const addFundSupportForm = document.getElementById('addFundSupportForm');
            if (addFundSupportForm) {
                addFundSupportForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    formData.append('action', 'add');
                    
                    const messageDiv = document.getElementById('addFundMessage');
                    if (messageDiv) {
                        messageDiv.classList.remove('hidden');
                        messageDiv.innerHTML = '<div class="flex items-center"><i class="fas fa-spinner fa-spin mr-2"></i>กำลังเพิ่มข้อมูล...</div>';
                        messageDiv.className = 'mt-4 p-4 rounded-lg bg-blue-100 text-blue-800';
                    }
                    
                    try {
                        const response = await fetch('controller/update_fund_support.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (messageDiv) {
                            if (result.success) {
                                messageDiv.innerHTML = '<div class="flex items-center"><i class="fas fa-check-circle mr-2"></i>' + result.message + '</div>';
                                messageDiv.className = 'mt-4 p-4 rounded-lg bg-green-100 text-green-800';
                                
                                // รีเฟรชหน้าเว็บหลังจาก 2 วินาที
                                setTimeout(() => {
                                    window.location.reload();
                                }, 2000);
                            } else {
                                messageDiv.innerHTML = '<div class="flex items-center"><i class="fas fa-exclamation-circle mr-2"></i>' + result.message + '</div>';
                                messageDiv.className = 'mt-4 p-4 rounded-lg bg-red-100 text-red-800';
                            }
                        }
                    } catch (error) {
                        if (messageDiv) {
                            messageDiv.innerHTML = '<div class="flex items-center"><i class="fas fa-exclamation-triangle mr-2"></i>เกิดข้อผิดพลาด: ' + error.message + '</div>';
                            messageDiv.className = 'mt-4 p-4 rounded-lg bg-red-100 text-red-800';
                        }
                    }
                });
            }
        });
        
        // ฟังก์ชันรีเซ็ตฟอร์มเพิ่มทุนใหม่
        function resetAddForm() {
            document.getElementById('addFundSupportForm').reset();
            document.getElementById('newYear').value = <?php echo date('Y') + 543; ?>;
            document.getElementById('addFundMessage').classList.add('hidden');
        }
        
        // ฟังก์ชันแสดง/ซ่อน section
        function showSection(sectionId) {
            const section = document.getElementById(sectionId);
            if (section) {
                section.classList.remove('hidden');
                // Scroll to section smoothly
                section.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
        
        function hideSection(sectionId) {
            const section = document.getElementById(sectionId);
            if (section) {
                section.classList.add('hidden');
            }
        }
        
        // ตรวจสอบ URL hash และแสดง section ที่เกี่ยวข้อง
        window.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash;
            if (hash === '#fundSupportSection') {
                showSection('fundSupportSection');
            } else if (hash === '#disbursementSection') {
                showSection('disbursementSection');
            }
        });
    </script>
</body>
</html>