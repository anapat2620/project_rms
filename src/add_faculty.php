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
    $faculty_name = trim($_POST['faculty_name'] ?? '');
    $faculty_code = trim($_POST['faculty_code'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // ตรวจสอบข้อมูล
    if (empty($faculty_name) || empty($faculty_code)) {
        $message = 'กรุณากรอกชื่อคณะและรหัสคณะให้ครบถ้วน';
        $messageType = 'error';
    } else {
        // ตรวจสอบว่าคณะซ้ำหรือไม่
        $check_sql = "SELECT faculty_name FROM faculties WHERE faculty_name = ? OR faculty_code = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $faculty_name, $faculty_code);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $message = 'คณะหรือรหัสคณะนี้มีอยู่ในระบบแล้ว';
            $messageType = 'error';
        } else {
            // สร้างตาราง faculties ถ้ายังไม่มี
            $create_table_sql = "CREATE TABLE IF NOT EXISTS faculties (
                id INT AUTO_INCREMENT PRIMARY KEY,
                faculty_name VARCHAR(255) NOT NULL UNIQUE,
                faculty_code VARCHAR(10) NOT NULL UNIQUE,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            if ($conn->query($create_table_sql)) {
                // เพิ่มข้อมูลคณะใหม่
                $insert_sql = "INSERT INTO faculties (faculty_name, faculty_code, description) VALUES (?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("sss", $faculty_name, $faculty_code, $description);
                
                if ($insert_stmt->execute()) {
                    $message = 'เพิ่มคณะใหม่สำเร็จ';
                    $messageType = 'success';
                    // รีเซ็ตฟอร์ม
                    $_POST = array();
                } else {
                    $message = 'เกิดข้อผิดพลาดในการเพิ่มคณะ: ' . $conn->error;
                    $messageType = 'error';
                }
                $insert_stmt->close();
            } else {
                $message = 'เกิดข้อผิดพลาดในการสร้างตาราง: ' . $conn->error;
                $messageType = 'error';
            }
        }
        $check_stmt->close();
    }
}

// ดึงข้อมูลคณะทั้งหมด
$faculties = [];
$sql = "SELECT faculty_name, faculty_code, description, created_at FROM faculties ORDER BY created_at DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $faculties[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มคณะใหม่ - MSU Research Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/add_faculty.css">
</head>
<body class="gradient-bg min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-white mb-2">เพิ่มคณะใหม่</h1>
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
                    <!-- Faculty Name -->
                    <div>
                        <label for="faculty_name" class="block text-sm font-medium text-gray-700 mb-2">
                            ชื่อคณะ <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="faculty_name" 
                               name="faculty_name" 
                               value="<?php echo htmlspecialchars($_POST['faculty_name'] ?? ''); ?>"
                               class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="เช่น คณะวิศวกรรมศาสตร์"
                               required>
                    </div>

                    <!-- Faculty Code -->
                    <div>
                        <label for="faculty_code" class="block text-sm font-medium text-gray-700 mb-2">
                            รหัสคณะ <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="faculty_code" 
                               name="faculty_code" 
                               value="<?php echo htmlspecialchars($_POST['faculty_code'] ?? ''); ?>"
                               class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="เช่น EN"
                               maxlength="10"
                               required>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            คำอธิบาย (ไม่บังคับ)
                        </label>
                        <textarea id="description" 
                                  name="description" 
                                  rows="4"
                                  class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="รายละเอียดเพิ่มเติมเกี่ยวกับคณะ..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex gap-4 pt-4">
                        <button type="submit" 
                                class="flex-1 bg-gradient-to-r from-green-500 to-blue-600 text-white py-3 px-6 rounded-lg font-semibold hover:from-green-600 hover:to-blue-700 transition-all duration-300 transform hover:scale-105">
                            เพิ่มคณะใหม่
                        </button>
                        <button type="reset" 
                                class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all duration-300">
                            รีเซ็ต
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Faculty List -->
        <div class="max-w-4xl mx-auto mt-12">
            <div class="form-container rounded-2xl shadow-2xl p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">รายการคณะในระบบ</h2>
                <div class="overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">รหัสคณะ</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">ชื่อคณะ</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">คำอธิบาย</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">วันที่เพิ่ม</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($faculties)): ?>
                                <tr>
                                    <td colspan="4" class="px-4 py-3 text-center text-gray-500">ยังไม่มีคณะในระบบ</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($faculties as $faculty): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($faculty['faculty_code']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($faculty['faculty_name']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($faculty['description'] ?: '-'); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-900"><?php echo date('d/m/Y H:i', strtotime($faculty['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Default Faculties Info -->
        <div class="max-w-4xl mx-auto mt-8">
            <div class="form-container rounded-2xl shadow-2xl p-8">
                <h3 class="text-xl font-bold text-gray-800 mb-4">คณะเริ่มต้นในระบบ</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php
                    $default_faculties = [
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
                    
                    foreach ($default_faculties as $faculty): ?>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <span class="text-sm text-gray-700"><?php echo htmlspecialchars($faculty); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="text-sm text-gray-600 mt-4">
                    * คณะเหล่านี้เป็นคณะเริ่มต้นที่สามารถใช้ได้ทันทีในการเพิ่มผู้ใช้
                </p>
            </div>
        </div>
    </div>

    <script src="assets/add_faculty.js"></script>
</body>
</html> 