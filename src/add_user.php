<?php
session_start();

// ตรวจสอบสิทธิ์การเข้าถึง (เฉพาะ admin หรือผู้มีสิทธิ์)
if (!isset($_SESSION['Position']) || !in_array($_SESSION['Position'], ['Admin'])) {
    header('Location: index.php');
    exit();
}

// การเชื่อมต่อฐานข้อมูล (ใช้ config กลาง)
require_once __DIR__ . '/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

$message = '';
$messageType = '';

// จัดการการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $faculty = trim($_POST['faculty'] ?? '');
    $position = trim($_POST['position'] ?? '');
    
    // ตรวจสอบข้อมูล
    if (empty($username) || empty($email) || empty($password) || empty($faculty) || empty($position)) {
        $message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        $messageType = 'error';
    } else {
        // ตรวจสอบว่า email ซ้ำหรือไม่
        $check_sql = "SELECT Email FROM data WHERE Email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $message = 'อีเมลนี้มีอยู่ในระบบแล้ว';
            $messageType = 'error';
        } else {
            // เพิ่มข้อมูลใหม่
            $insert_sql = "INSERT INTO data (Username, Email, Password, Facuity, Position, Quantity) VALUES (?, ?, ?, ?, ?, 0)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sssss", $username, $email, $password, $faculty, $position);
            
            if ($insert_stmt->execute()) {
                $message = 'เพิ่มข้อมูลผู้ใช้สำเร็จ';
                $messageType = 'success';
                // รีเซ็ตฟอร์ม
                $_POST = array();
            } else {
                $message = 'เกิดข้อผิดพลาดในการเพิ่มข้อมูล: ' . $conn->error;
                $messageType = 'error';
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}

// ดึงข้อมูลคณะทั้งหมด
$faculties = [
    'คณะการบัญชี และการจัดการ',
    'คณะมนุษยศาสตร์และสังคมศาสตร์',
    'คณะวิทยาศาสตร์',
    'คณะวิศวกรรมศาสตร์',
    'คณะแพทยศาสตร์',
    'คณะทันตแพทยศาสตร์',
    'คณะเภสัชศาสตร์',
    'คณะพยาบาลศาสตร์',
    'คณะสาธารณสุขศาสตร์',
    'คณะสัตวแพทยศาสตร์',
    'คณะเกษตรศาสตร์',
    'คณะเทคโนโลยี',
    'คณะสถาปัตยกรรมศาสตร์',
    'คณะศิลปกรรมศาสตร์',
    'คณะศึกษาศาสตร์',
    'คณะนิติศาสตร์',
    'คณะรัฐศาสตร์',
    'คณะเศรษฐศาสตร์',
    'คณะบริหารธุรกิจ',
    'คณะการท่องเที่ยวและการโรงแรม'
];

$positions = [
    'ปริญญาตรี',
    'ปริญญาโท',
    'ปริญญาเอก',
    'บุคลากรวิชาการ',
    'คณบดี',
    'รองคณบดี',
    'ผู้ช่วยคณบดี',
    'หัวหน้าภาควิชา',
    'ผู้อำนวยการหลักสูตร',
    'Admin'
];

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มข้อมูลผู้ใช้ - MSU Research Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/add_user.css">
</head>
<body class="gradient-bg min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-white mb-2">เพิ่มข้อมูลผู้ใช้</h1>
            <p class="text-white/80">ระบบจัดการทุนวิจัย มหาวิทยาลัยมหาสารคาม</p>
        </div>

        <!-- Navigation -->
        <div class="flex justify-center mb-8">
            <a href="admin_dashboard.php" class="bg-white/20 text-white px-6 py-2 rounded-lg hover:bg-white/30 transition-all duration-300 mr-4">
                <i class="fas fa-arrow-left mr-2"></i>กลับไป Admin Dashboard
            </a>
            <a href="index.php" class="bg-white/20 text-white px-6 py-2 rounded-lg hover:bg-white/30 transition-all duration-300 mr-4">
                <i class="fas fa-home mr-2"></i>หน้าหลัก
            </a>
            <a href="controller/logout.php" class="bg-red-500 text-white px-6 py-2 rounded-lg hover:bg-red-600 transition-all duration-300">
                <i class="fas fa-sign-out-alt mr-2"></i>ออกจากระบบ
            </a>
        </div>

        <!-- Message Display -->
        <?php if ($message): ?>
            <div class="max-w-2xl mx-auto mb-6">
                <div class="p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Form Container -->
        <div class="max-w-2xl mx-auto">
            <div class="form-container rounded-2xl shadow-2xl p-8">
                <form method="POST" class="space-y-6">
                    <!-- Username -->
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                            ชื่อผู้ใช้ <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                               class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="กรอกชื่อผู้ใช้"
                               required>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            อีเมล <span class="text-red-500">*</span>
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="example@msu.ac.th"
                               required>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            รหัสผ่าน <span class="text-red-500">*</span>
                        </label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="กรอกรหัสผ่าน"
                               required>
                    </div>

                    <!-- Faculty -->
                    <div>
                        <label for="faculty" class="block text-sm font-medium text-gray-700 mb-2">
                            คณะ <span class="text-red-500">*</span>
                        </label>
                        <select id="faculty" 
                                name="faculty" 
                                class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                required>
                            <option value="">เลือกคณะ</option>
                            <?php foreach ($faculties as $faculty_option): ?>
                                <option value="<?php echo htmlspecialchars($faculty_option); ?>" 
                                        <?php echo (isset($_POST['faculty']) && $_POST['faculty'] === $faculty_option) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($faculty_option); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Position -->
                    <div>
                        <label for="position" class="block text-sm font-medium text-gray-700 mb-2">
                            ตำแหน่ง <span class="text-red-500">*</span>
                        </label>
                        <select id="position" 
                                name="position" 
                                class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                required>
                            <option value="">เลือกตำแหน่ง</option>
                            <?php foreach ($positions as $position_option): ?>
                                <option value="<?php echo htmlspecialchars($position_option); ?>" 
                                        <?php echo (isset($_POST['position']) && $_POST['position'] === $position_option) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($position_option); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex gap-4 pt-4">
                        <button type="submit" 
                                class="flex-1 bg-gradient-to-r from-blue-500 to-purple-600 text-white py-3 px-6 rounded-lg font-semibold hover:from-blue-600 hover:to-purple-700 transition-all duration-300 transform hover:scale-105">
                            เพิ่มข้อมูลผู้ใช้
                        </button>
                        <button type="reset" 
                                class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all duration-300">
                            รีเซ็ต
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- User List Preview -->
        <div class="max-w-4xl mx-auto mt-12">
            <div class="form-container rounded-2xl shadow-2xl p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">รายการผู้ใช้ในระบบ</h2>
                <div class="overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">ชื่อผู้ใช้</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">อีเมล</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">คณะ</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">ตำแหน่ง</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">จำนวนทุน</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php
                            // ดึงข้อมูลผู้ใช้ทั้งหมด
                            $conn = new mysqli($host, $dbuser, $dbpass, $dbname);
                            if (!$conn->connect_error) {
                                $sql = "SELECT Username, Email, Facuity, Position, Quantity FROM data ORDER BY ID DESC LIMIT 10";
                                $result = $conn->query($sql);
                                
                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr class='hover:bg-gray-50'>";
                                        echo "<td class='px-4 py-3 text-sm text-gray-900'>" . htmlspecialchars($row['Username']) . "</td>";
                                        echo "<td class='px-4 py-3 text-sm text-gray-900'>" . htmlspecialchars($row['Email']) . "</td>";
                                        echo "<td class='px-4 py-3 text-sm text-gray-900'>" . htmlspecialchars($row['Facuity']) . "</td>";
                                        echo "<td class='px-4 py-3 text-sm text-gray-900'>" . htmlspecialchars($row['Position']) . "</td>";
                                        echo "<td class='px-4 py-3 text-sm text-gray-900'>" . htmlspecialchars($row['Quantity']) . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='px-4 py-3 text-center text-gray-500'>ไม่มีข้อมูลผู้ใช้</td></tr>";
                                }
                                $conn->close();
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/add_user.js"></script>
</body>
</html> 