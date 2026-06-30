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

// จัดการการลบคณะ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_faculty'])) {
    $faculty_id = (int)$_POST['faculty_id'];
    
    $delete_sql = "DELETE FROM faculties WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $faculty_id);

    if ($delete_stmt->execute()) {
        $message = 'ลบคณะสำเร็จ';
        $messageType = 'success';
    } else {
        $message = 'เกิดข้อผิดพลาดในการลบคณะ';
        $messageType = 'error';
    }
    $delete_stmt->close();
}

// จัดการการอัปเดตคณะ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_faculty'])) {
    $faculty_id = (int)$_POST['faculty_id'];
    $faculty_name = trim($_POST['faculty_name']);
    $faculty_code = trim($_POST['faculty_code']);
    $description = trim($_POST['description']);

    if (empty($faculty_name) || empty($faculty_code)) {
        $message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        $messageType = 'error';
    } else {
        $check_sql = "SELECT faculty_name FROM faculties WHERE (faculty_name = ? OR faculty_code = ?) AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ssi", $faculty_name, $faculty_code, $faculty_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $message = 'คณะหรือรหัสคณะนี้มีอยู่ในระบบแล้ว';
            $messageType = 'error';
        } else {
            $update_sql = "UPDATE faculties SET faculty_name = ?, faculty_code = ?, description = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("sssi", $faculty_name, $faculty_code, $description, $faculty_id);

            if ($update_stmt->execute()) {
                $message = 'อัปเดตคณะสำเร็จ';
                $messageType = 'success';
            } else {
                $message = 'เกิดข้อผิดพลาดในการอัปเดตคณะ';
                $messageType = 'error';
            }
            $update_stmt->close();
        }
        $check_stmt->close();
    }
}

// ดึงข้อมูลคณะทั้งหมด
$sql = "SELECT * FROM faculties ORDER BY faculty_name ASC";
$result = $conn->query($sql);
$faculty_list = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $faculty_list[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการคณะ - MSU Research Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/faculty_management.css">
</head>
<body class="gradient-bg min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-white mb-2">
                <i class="fas fa-university mr-3"></i>จัดการคณะ
            </h1>
            <p class="text-white/80">ระบบจัดการข้อมูลคณะในมหาวิทยาลัย</p>
        </div>

        <!-- Navigation -->
        <div class="flex justify-center mb-8">
            <a href="admin_dashboard.php" class="bg-white/20 text-white px-6 py-2 rounded-lg hover:bg-white/30 transition-all duration-300 mr-4">
                <i class="fas fa-arrow-left mr-2"></i>กลับไป Admin Dashboard
            </a>
            <a href="add_faculty.php" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-all duration-300 mr-4">
                <i class="fas fa-plus mr-2"></i>เพิ่มคณะใหม่
            </a>
            <a href="index.php" class="bg-white/20 text-white px-6 py-2 rounded-lg hover:bg-white/30 transition-all duration-300 mr-4">
                <i class="fas fa-home mr-2"></i>หน้าหลัก
            </a>
            <a href="controller/logout.php" class="bg-red-500 text-white px-6 py-2 rounded-lg hover:bg-red-600 transition-all duration-300">
                <i class="fas fa-sign-out-alt mr-2"></i>ออกจากระบบ
            </a>
        </div>

        <!-- Message Display -->
        <?php if (!empty($message)): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-red-100 text-red-800 border border-red-300'; ?>">
                <div class="flex items-center">
                    <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Faculty List -->
        <div class="card rounded-2xl shadow-2xl p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-list mr-3 text-blue-600"></i>
                รายการคณะทั้งหมด (<?php echo count($faculty_list); ?> คณะ)
            </h2>
            
            <!-- Search Box -->
            <div class="mb-6">
                <input type="text" id="searchInput" placeholder="ค้นหาคณะ..." 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">ลำดับ</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">รหัสคณะ</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">ชื่อคณะ</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">คำอธิบาย</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">วันที่สร้าง</th>
                            <th class="px-4 py-3 text-center text-sm font-medium text-gray-700">การดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody id="facultyTableBody">
                        <?php if (empty($faculty_list)): ?>
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">ไม่มีข้อมูลคณะในระบบ</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($faculty_list as $index => $faculty): ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-50 faculty-row">
                                    <td class="px-4 py-3 text-sm text-gray-700"><?php echo $index + 1; ?></td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($faculty['faculty_code']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($faculty['faculty_name']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <?php echo htmlspecialchars(substr($faculty['description'], 0, 50)) . (strlen($faculty['description']) > 50 ? '...' : ''); ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        <?php echo date('d/m/Y', strtotime($faculty['created_at'])); ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex justify-center space-x-2">
                                            <button onclick="editFaculty(<?php echo htmlspecialchars(json_encode($faculty)); ?>)" 
                                                    class="text-blue-600 hover:text-blue-800 p-1">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" class="inline" onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบคณะนี้?')">
                                                <input type="hidden" name="faculty_id" value="<?php echo $faculty['id']; ?>">
                                                <button type="submit" name="delete_faculty" class="text-red-600 hover:text-red-800 p-1">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
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

    <!-- Edit Faculty Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-gray-800 mb-4">แก้ไขคณะ</h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="faculty_id" id="edit_faculty_id">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">รหัสคณะ</label>
                        <input type="text" name="faculty_code" id="edit_faculty_code" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               style="text-transform: uppercase;">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อคณะ</label>
                        <input type="text" name="faculty_name" id="edit_faculty_name" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">คำอธิบาย</label>
                        <textarea name="description" id="edit_description" rows="3" 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-4 mt-6">
                    <button type="button" onclick="closeEditModal()" 
                            class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                        ยกเลิก
                    </button>
                    <button type="submit" name="update_faculty" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/faculty_management.js"></script>
</body>
</html> 