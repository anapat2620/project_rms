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

// จัดการการเพิ่มข่าวใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_news'])) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $date_posted = trim($_POST['date_posted'] ?? '');

    if (empty($title) || empty($content) || empty($date_posted)) {
        $message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        $messageType = 'error';
    } else {
        $insert_sql = "INSERT INTO news_board (title, content, date_posted) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("sss", $title, $content, $date_posted);

        if ($insert_stmt->execute()) {
            $message = 'เพิ่มข่าวใหม่สำเร็จ';
            $messageType = 'success';
            $_POST = array(); // Reset form
        } else {
            $message = 'เกิดข้อผิดพลาดในการเพิ่มข่าว: ' . $conn->error;
            $messageType = 'error';
        }
        $insert_stmt->close();
    }
}

// จัดการการลบข่าว
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_news'])) {
    $news_id = (int)$_POST['news_id'];
    
    $delete_sql = "DELETE FROM news_board WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $news_id);

    if ($delete_stmt->execute()) {
        $message = 'ลบข่าวสำเร็จ';
        $messageType = 'success';
    } else {
        $message = 'เกิดข้อผิดพลาดในการลบข่าว';
        $messageType = 'error';
    }
    $delete_stmt->close();
}

// จัดการการอัปเดตข่าว
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_news'])) {
    $news_id = (int)$_POST['news_id'];
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $date_posted = trim($_POST['date_posted']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($title) || empty($content) || empty($date_posted)) {
        $message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        $messageType = 'error';
    } else {
        $update_sql = "UPDATE news_board SET title = ?, content = ?, date_posted = ?, is_active = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sssii", $title, $content, $date_posted, $is_active, $news_id);

        if ($update_stmt->execute()) {
            $message = 'อัปเดตข่าวสำเร็จ';
            $messageType = 'success';
        } else {
            $message = 'เกิดข้อผิดพลาดในการอัปเดตข่าว';
            $messageType = 'error';
        }
        $update_stmt->close();
    }
}

// ดึงข้อมูลข่าวทั้งหมด
$sql = "SELECT * FROM news_board ORDER BY date_posted DESC, created_at DESC";
$result = $conn->query($sql);
$news_list = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $news_list[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการกระดานข่าว - MSU Research Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/news_management.css">
</head>
<body class="gradient-bg min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-white mb-2">
                <i class="fas fa-newspaper mr-3"></i>จัดการกระดานข่าว
            </h1>
            <p class="text-white/80">ระบบจัดการข่าวสารและประกาศ</p>
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
        <?php if (!empty($message)): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-red-100 text-red-800 border border-red-300'; ?>">
                <div class="flex items-center">
                    <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Add News Form -->
            <div class="card rounded-2xl shadow-2xl p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-plus-circle mr-3 text-green-600"></i>
                    เพิ่มข่าวใหม่
                </h2>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">หัวข้อข่าว</label>
                        <input type="text" name="title" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="กรอกหัวข้อข่าว">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">เนื้อหาข่าว</label>
                        <textarea name="content" rows="4" required 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="กรอกเนื้อหาข่าว"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">วันที่ประกาศ</label>
                        <input type="date" name="date_posted" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <button type="submit" name="add_news" 
                            class="w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors duration-300">
                        <i class="fas fa-plus mr-2"></i>เพิ่มข่าวใหม่
                    </button>
                </form>
            </div>

            <!-- News List -->
            <div class="card rounded-2xl shadow-2xl p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-list mr-3 text-blue-600"></i>
                    รายการข่าวทั้งหมด
                </h2>
                <div class="space-y-4 max-h-96 overflow-y-auto">
                    <?php if (empty($news_list)): ?>
                        <p class="text-gray-500 text-center py-8">ไม่มีข่าวในระบบ</p>
                    <?php else: ?>
                        <?php foreach ($news_list as $news): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($news['title']); ?></h3>
                                    <div class="flex space-x-2">
                                        <button onclick="editNews(<?php echo htmlspecialchars(json_encode($news)); ?>)" 
                                                class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="inline" onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบข่าวนี้?')">
                                            <input type="hidden" name="news_id" value="<?php echo $news['id']; ?>">
                                            <button type="submit" name="delete_news" class="text-red-600 hover:text-red-800">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars(substr($news['content'], 0, 100)) . (strlen($news['content']) > 100 ? '...' : ''); ?></p>
                                <div class="flex justify-between items-center text-xs text-gray-500">
                                    <span>วันที่: <?php echo date('d/m/Y', strtotime($news['date_posted'])); ?></span>
                                    <span class="<?php echo $news['is_active'] ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo $news['is_active'] ? 'แสดงผล' : 'ไม่แสดงผล'; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit News Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-gray-800 mb-4">แก้ไขข่าว</h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="news_id" id="edit_news_id">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">หัวข้อข่าว</label>
                        <input type="text" name="title" id="edit_title" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">เนื้อหาข่าว</label>
                        <textarea name="content" id="edit_content" rows="4" required 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">วันที่ประกาศ</label>
                        <input type="date" name="date_posted" id="edit_date_posted" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="edit_is_active" class="mr-2">
                        <label for="edit_is_active" class="text-sm text-gray-700">แสดงผล</label>
                    </div>
                </div>
                <div class="flex justify-end space-x-4 mt-6">
                    <button type="button" onclick="closeEditModal()" 
                            class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                        ยกเลิก
                    </button>
                    <button type="submit" name="update_news" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/news_management.js"></script>
</body>
</html> 