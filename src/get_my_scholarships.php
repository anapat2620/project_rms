<?php
// แสดง error ทั้งหมดเพื่อการ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['Username'])) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'กรุณาเข้าสู่ระบบ'
        ]);
    } else {
        header('Location: login.php');
    }
    exit();
}

// ดึง username และ email จาก session (แปลงเป็นตัวพิมพ์เล็กและ trim)
$username = strtolower(trim($_SESSION['Username'] ?? ''));
$email = strtolower(trim($_SESSION['Email'] ?? ''));

$requests = [];
$error_message = null;

try {
    // เชื่อมต่อฐานข้อมูล research_db (ใช้ config กลาง)
    require_once __DIR__ . '/config.php';
    $research_db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
    );
    $research_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // สร้าง query สำหรับดึงข้อมูลจากทุกตารางที่เกี่ยวข้อง (ใช้ทั้งชื่อและอีเมล, insensitive case/space)
    $sql = "SELECT 
                rs.*,
                CASE 
                    WHEN rs.original_table = 'research_proposals' THEN rp.project_th
                    WHEN rs.original_table = 'research_teacher' THEN rt.project_thai_name
                    WHEN rs.original_table = 'research_personnel' THEN rpe.project_th
                END as project_name,
                CASE 
                    WHEN rs.original_table = 'research_proposals' THEN CONCAT(rp.student_firstname, ' ', rp.student_lastname)
                    WHEN rs.original_table = 'research_teacher' THEN rt.teacher_prefix_name
                    WHEN rs.original_table = 'research_personnel' THEN CONCAT(rpe.leader_firstname, ' ', rpe.leader_lastname)
                END as requester_name,
                CASE 
                    WHEN rs.original_table = 'research_proposals' THEN rp.proposal_file_path
                    WHEN rs.original_table = 'research_teacher' THEN rt.proposal_file_path
                    WHEN rs.original_table = 'research_personnel' THEN rpe.proposal_file_path
                END as proposal_file,
                CASE 
                    WHEN rs.original_table = 'research_proposals' THEN rp.additional_file_path
                    WHEN rs.original_table = 'research_teacher' THEN rt.additional_file_path
                    WHEN rs.original_table = 'research_personnel' THEN rpe.additional_file_path
                END as additional_file
            FROM research_requests_status rs
            LEFT JOIN research_proposals rp ON rs.original_table = 'research_proposals' AND rs.original_id = rp.id
            LEFT JOIN research_teacher rt ON rs.original_table = 'research_teacher' AND rs.original_id = rt.id
            LEFT JOIN research_personnel rpe ON rs.original_table = 'research_personnel' AND rs.original_id = rpe.id
            WHERE (TRIM(LOWER(rs.requesting_user_name)) = :username
                OR TRIM(LOWER(rs.requesting_user_email)) = :email)
            ORDER BY rs.submission_date DESC";
    
    $stmt = $research_db->prepare($sql);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ถ้าเป็น AJAX request ให้ส่งข้อมูลกลับเป็น JSON
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'user_info' => [
                'username' => $_SESSION['Username'] ?? '',
                'position' => $_SESSION['Position'] ?? '',
                'faculty' => $_SESSION['Facuity'] ?? ''
            ],
            'requests' => $requests
        ]);
        exit();
    }

} catch (PDOException $e) {
    $error_message = 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: ' . $e->getMessage();
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => $error_message
        ]);
        exit();
    }
}
// ตรวจว่าถูกเรียกแบบ embed (จาก loadForm ใน index.php) หรือ standalone
$isEmbed = isset($_GET['embed']) && $_GET['embed'] === '1';
?>
<?php if (!$isEmbed): ?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการยื่นขอทุนของฉัน</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.24/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/get_my_scholarships.css">
</head>
<body class="min-h-screen bg-gray-100">
<?php endif; ?>
    <div class="container mx-auto px-4 py-8">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error mb-4">
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php else: ?>
            <!-- ประวัติการยื่นขอทุน -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold">ประวัติการยื่นขอทุนของฉัน</h2>
                </div>
                <?php if (empty($requests)): ?>
                    <p class="text-gray-500 text-center py-4">ยังไม่มีประวัติการยื่นขอทุน</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($requests as $request): ?>
                            <div class="border rounded-lg p-4">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="font-bold text-lg mb-3">
                                            <?php echo htmlspecialchars($request['project_name']); ?>
                                        </h3>
                                        <div class="grid grid-cols-3 gap-6">
                                            <!-- ประเภท -->
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                                </svg>
                                                <div>
                                                    <span class="text-xs text-gray-500">ประเภท</span>
                                                    <p class="text-sm font-medium">
                                                        <?php 
                                                        switch($request['original_table']) {
                                                            case 'research_proposals':
                                                                echo 'นักศึกษา';
                                                                break;
                                                            case 'research_teacher':
                                                                echo 'อาจารย์';
                                                                break;
                                                            case 'research_personnel':
                                                                echo 'บุคลากร';
                                                                break;
                                                            default:
                                                                echo htmlspecialchars($request['original_table']);
                                                        }
                                                        ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <!-- ผู้ยื่น -->
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                                <div>
                                                    <span class="text-xs text-gray-500">ผู้ยื่น</span>
                                                    <p class="text-sm font-medium">
                                                        <?php echo htmlspecialchars($request['requesting_user_name']); ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <!-- วันที่ยื่น -->
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                                <div>
                                                    <span class="text-xs text-gray-500">วันที่ยื่น</span>
                                                    <p class="text-sm font-medium">
                                                        <?php echo date('d/m/Y H:i', strtotime($request['submission_date'])); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="status-badge status-<?php echo $request['current_status']; ?>">
                                        <?php echo htmlspecialchars($request['current_status']); ?>
                                    </span>
                                </div>
                                <div class="flex justify-end mt-2">
                                    <a href="details.php?request_id=<?php echo $request['request_id']; ?>" target="_blank"
                                       class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        รายละเอียดเพิ่มเติม
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

<?php if (!$isEmbed): ?>
    <script src="assets/get_my_scholarships.js"></script>
</body>
</html>
<?php else: ?>
    <script>
    /* re-init JS สำหรับ embed mode — โหลด script แบบ dynamic */
    (function() {
        var s = document.createElement('script');
        s.src = 'assets/get_my_scholarships.js';
        s.async = false;
        document.getElementById('form') ? document.getElementById('form').appendChild(s) : document.body.appendChild(s);
    })();
    </script>
<?php endif; ?>