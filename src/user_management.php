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

// จัดการการลบผู้ใช้
if (isset($_POST['delete_user']) && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    
    // ตรวจสอบว่าไม่ใช่ผู้ใช้ที่กำลัง login อยู่
    if ($user_id != $_SESSION['ID']) {
        $delete_sql = "DELETE FROM data WHERE ID = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $user_id);
        
        if ($delete_stmt->execute()) {
            $message = 'ลบข้อมูลผู้ใช้สำเร็จ';
            $messageType = 'success';
        } else {
            $message = 'เกิดข้อผิดพลาดในการลบข้อมูล';
            $messageType = 'error';
        }
        $delete_stmt->close();
    } else {
        $message = 'ไม่สามารถลบบัญชีของตัวเองได้';
        $messageType = 'error';
    }
}

// จัดการการอัปเดตข้อมูลผู้ใช้
if (isset($_POST['update_user'])) {
    $user_id = (int)$_POST['user_id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $faculty = trim($_POST['faculty']);
    $position = trim($_POST['position']);
    
    if (empty($username) || empty($email) || empty($faculty) || empty($position)) {
        $message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        $messageType = 'error';
    } else {
        // ตรวจสอบว่า email ซ้ำหรือไม่ (ยกเว้นผู้ใช้ปัจจุบัน)
        $check_sql = "SELECT Email FROM data WHERE Email = ? AND ID != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $email, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $message = 'อีเมลนี้มีอยู่ในระบบแล้ว';
            $messageType = 'error';
        } else {
            // อัปเดตข้อมูล
            $update_sql = "UPDATE data SET Username = ?, Email = ?, Facuity = ?, Position = ? WHERE ID = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssssi", $username, $email, $faculty, $position, $user_id);
            
            if ($update_stmt->execute()) {
                $message = 'อัปเดตข้อมูลผู้ใช้สำเร็จ';
                $messageType = 'success';
            } else {
                $message = 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล';
                $messageType = 'error';
            }
            $update_stmt->close();
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

// ดึงข้อมูลผู้ใช้ทั้งหมด
$users = [];
$sql = "SELECT ID, Username, Email, Facuity, Position, Quantity FROM data ORDER BY ID DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้ - MSU Research Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/user_management.css">
</head>
<body class="gradient-bg min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-white mb-2">จัดการผู้ใช้</h1>
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
            <div class="max-w-6xl mx-auto mb-6">
                <div class="p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- User List -->
        <div class="max-w-6xl mx-auto">
            <div class="content-container rounded-2xl shadow-2xl p-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">รายการผู้ใช้ทั้งหมด (<?php echo count($users); ?> คน)</h2>
                    <div class="flex gap-2">
                        <input type="text" id="searchInput" placeholder="ค้นหาผู้ใช้..." 
                               class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full table-auto" id="userTable">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">ID</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">ชื่อผู้ใช้</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">อีเมล</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">คณะ</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">ตำแหน่ง</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">จำนวนทุน</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" class="px-4 py-3 text-center text-gray-500">ไม่มีข้อมูลผู้ใช้</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr class="hover:bg-gray-50 user-row" data-username="<?php echo strtolower($user['Username']); ?>" 
                                        data-email="<?php echo strtolower($user['Email']); ?>" 
                                        data-faculty="<?php echo strtolower($user['Facuity']); ?>" 
                                        data-position="<?php echo strtolower($user['Position']); ?>">
                                        <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($user['ID']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($user['Username']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($user['Email']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($user['Facuity']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($user['Position']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($user['Quantity']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            <div class="flex gap-2">
                                                <button onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" 
                                                        class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 transition-colors">
                                                    แก้ไข
                                                </button>
                                                <?php if ($user['ID'] != $_SESSION['ID']): ?>
                                                    <form method="POST" class="inline" onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบผู้ใช้นี้?')">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['ID']; ?>">
                                                        <button type="submit" name="delete_user" 
                                                                class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 transition-colors">
                                                            ลบ
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-gray-400 px-3 py-1">(คุณ)</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">แก้ไขข้อมูลผู้ใช้</h3>
                <span class="cursor-pointer text-2xl" onclick="closeModal()">&times;</span>
            </div>
            
            <form method="POST" id="editForm">
                <input type="hidden" name="user_id" id="editUserId">
                <input type="hidden" name="update_user" value="1">
                
                <div class="space-y-4">
                    <div>
                        <label for="editUsername" class="block text-sm font-medium text-gray-700 mb-2">
                            ชื่อผู้ใช้ <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="editUsername" name="username" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="editEmail" class="block text-sm font-medium text-gray-700 mb-2">
                            อีเมล <span class="text-red-500">*</span>
                        </label>
                        <input type="email" id="editEmail" name="email" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="editFaculty" class="block text-sm font-medium text-gray-700 mb-2">
                            คณะ <span class="text-red-500">*</span>
                        </label>
                        <select id="editFaculty" name="faculty" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">เลือกคณะ</option>
                            <?php foreach ($faculties as $faculty): ?>
                                <option value="<?php echo htmlspecialchars($faculty); ?>">
                                    <?php echo htmlspecialchars($faculty); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="editPosition" class="block text-sm font-medium text-gray-700 mb-2">
                            ตำแหน่ง <span class="text-red-500">*</span>
                        </label>
                        <select id="editPosition" name="position" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">เลือกตำแหน่ง</option>
                            <?php foreach ($positions as $position): ?>
                                <option value="<?php echo htmlspecialchars($position); ?>">
                                    <?php echo htmlspecialchars($position); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="flex gap-4 mt-6">
                    <button type="submit" 
                            class="flex-1 bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition-colors">
                        บันทึกการเปลี่ยนแปลง
                    </button>
                    <button type="button" onclick="closeModal()"
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        ยกเลิก
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/user_management.js"></script>
</body>
</html> 