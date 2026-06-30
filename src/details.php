<?php
session_start();

// ตรวจสอบว่ามีการล็อกอินแล้วหรือไม่
if (!isset($_SESSION['Email']) || !isset($_SESSION['Position'])) {
    header("Location: login.php");
    exit();
}

// ค่าการเชื่อมต่อฐานข้อมูล (ใช้ config กลาง)
require_once __DIR__ . '/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("คุณไม่ได้รับอนุญาติให้ดำเนินการนี้: " . $conn->connect_error);
}

$approverPositions = [
    'Admin',
];
$userPosition = $_SESSION['Position'] ?? '';
$isAdmin = in_array($userPosition, $approverPositions);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin) {
    $action = $_POST['action'] ?? null;
    $requestId = $_POST['request_id'] ?? null;
    $error_message = '';

    if ($action === 'update_fund_disbursement' && $requestId) {
        // Validate request ID
        if (!is_numeric($requestId) || $requestId <= 0) {
            $error_message = 'คุณไม่ได้รับอนุญาติให้ดำเนินการนี้';
        } else {
            // Additional validation - check if request exists
            $check_sql = "SELECT request_id FROM research_requests_status WHERE request_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            if ($check_stmt) {
                $check_stmt->bind_param("i", $requestId);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                if ($check_result->num_rows === 0) {
                    $error_message = 'ไม่พบคำขอที่ระบุ';
                    $check_stmt->close();
                } else {
                    $check_stmt->close();
                    
                    // Process single installment
                    $phase = $_POST['phase'] ?? null;
                    $status = $_POST['fund_status'] ?? null;
                    $amount = $_POST['amount'] ?? null;
                    $comment = trim($_POST['comment'] ?? '');
                    $proof_link = trim($_POST['proof_link'] ?? '');
                    
                    // Validate phase
                    if (!in_array($phase, ['1st', '2nd', '3rd'])) {
                        $error_message = 'งวดการจ่ายเงินไม่ถูกต้อง';
                    } else {
                        // Validate amount if provided
                        if ($amount !== null && $amount !== '' && (!is_numeric($amount) || $amount < 0)) {
                            $error_message = "จำนวนเงินงวดที่ {$phase} ไม่ถูกต้อง";
                        } elseif ($status && in_array($status, ['รอการจ่าย', 'จ่ายแล้ว', 'ไม่จ่าย'])) {
                            // Validate proof link if status is 'จ่ายแล้ว' and proof_link is provided
                            if ($status === 'จ่ายแล้ว' && !empty($proof_link) && !filter_var($proof_link, FILTER_VALIDATE_URL)) {
                                $error_message = 'ลิงก์หลักฐานไม่ถูกต้อง กรุณาระบุ URL ที่ถูกต้อง';
                            } else {
                                $phase_field = 'fund_disbursement_' . $phase . '_status';
                                $date_field = 'fund_disbursement_' . $phase . '_date';
                                $amount_field = 'fund_disbursement_' . $phase . '_amount';
                                $comment_field = 'fund_disbursement_' . $phase . '_comment';
                                $proof_link_field = 'fund_disbursement_' . $phase . '_proof_link';
                                
                                $now = date('Y-m-d H:i:s');
                                $disbursement_date = ($status === 'จ่ายแล้ว') ? $now : null;
                                
                                // Convert amount to decimal or null
                                $amount_value = ($amount !== null && $amount !== '') ? (float)$amount : null;
                                
                                // Set proof_link to null if empty
                                $proof_link_value = !empty($proof_link) ? $proof_link : null;
                                
                                $sql = "UPDATE research_requests_status SET 
                                        {$phase_field} = ?, 
                                        {$date_field} = ?, 
                                        {$amount_field} = ?, 
                                        {$comment_field} = ?,
                                        {$proof_link_field} = ?,
                                        fund_disbursement_updated_by = ?,
                                        fund_disbursement_updated_date = ?
                                        WHERE request_id = ?";
                                
                                $stmt = $conn->prepare($sql);
                                if ($stmt) {
                                    $stmt->bind_param("ssdssssi", $status, $disbursement_date, $amount_value, $comment, $proof_link_value, $_SESSION['Username'], $now, $requestId);
                                    if ($stmt->execute()) {
                                        // Insert into history table
                                        $history_sql = "INSERT INTO fund_disbursement_history 
                                                       (request_id, disbursement_phase, status, amount, disbursement_date, comment, proof_link, updated_by) 
                                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                                        $history_stmt = $conn->prepare($history_sql);
                                        if ($history_stmt) {
                                            $history_stmt->bind_param("issdssss", $requestId, $phase, $status, $amount_value, $disbursement_date, $comment, $proof_link_value, $_SESSION['Username']);
                                            $history_stmt->execute();
                                            $history_stmt->close();
                                        }
                                        
                                        $stmt->close();
                                        
                                        // Redirect with success message and refresh once
                                        $phase_text = $phase === '1st' ? 'งวดที่ 1' : ($phase === '2nd' ? 'งวดที่ 2' : 'งวดที่ 3');
                                        $redirect_url = "details.php?request_id=" . (int)$requestId . "&status=fund_updated&success=1&phase=" . urlencode($phase_text) . "&refresh=1";
                                        header("Location: " . $redirect_url);
                                        exit();
                                    } else {
                                        $error_message = "เกิดข้อผิดพลาดในการอัปเดตสถานะการจ่ายเงินงวดที่ {$phase}: " . $stmt->error;
                                    }
                                } else {
                                    $error_message = "เกิดข้อผิดพลาดในการเตรียม SQL สำหรับงวดที่ {$phase}: " . $conn->error;
                                }
                            }
                        } else {
                            $error_message = 'กรุณาเลือกสถานะการจ่ายเงิน';
                        }
                    }
                }
            } else {
                $error_message = 'เกิดข้อผิดพลาดในการตรวจสอบคำขอ';
            }
        }
    } elseif ($action === 'close_period' && $requestId) {
        // Validate request ID
        if (!is_numeric($requestId) || $requestId <= 0) {
            $error_message = 'คุณไม่ได้รับอนุญาติให้ดำเนินการนี้';
        } else {
            // Check if request exists and get current status
            $check_sql = "SELECT fund_disbursement_1st_status, fund_disbursement_2nd_status, fund_disbursement_3rd_status,
                                 fund_disbursement_1st_amount, fund_disbursement_2nd_amount, fund_disbursement_3rd_amount,
                                 fund_disbursement_1st_comment, fund_disbursement_2nd_comment, fund_disbursement_3rd_comment,
                                 fund_disbursement_1st_proof_link, fund_disbursement_2nd_proof_link, fund_disbursement_3rd_proof_link
                          FROM research_requests_status WHERE request_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            if ($check_stmt) {
                $check_stmt->bind_param("i", $requestId);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                if ($check_result->num_rows === 0) {
                    $error_message = 'ไม่พบคำขอที่ระบุ';
                    $check_stmt->close();
                } else {
                    $current_data = $check_result->fetch_assoc();
                    $check_stmt->close();
                    
                    $now = date('Y-m-d H:i:s');
                    $updates = [];
                    $history_inserts = [];
                    
                    // Process each installment - set unpaid ones to paid
                    foreach (['1st', '2nd', '3rd'] as $phase) {
                        $status_field = "fund_disbursement_{$phase}_status";
                        $current_status = $current_data[$status_field] ?? 'รอการจ่าย';
                        
                        // Only update if not already paid
                        if ($current_status !== 'จ่ายแล้ว') {
                            $date_field = "fund_disbursement_{$phase}_date";
                            $amount_field = "fund_disbursement_{$phase}_amount";
                            $comment_field = "fund_disbursement_{$phase}_comment";
                            $proof_link_field = "fund_disbursement_{$phase}_proof_link";
                            
                            $current_amount = $current_data[$amount_field] ?? null;
                            $current_comment = $current_data[$comment_field] ?? '';
                            $current_proof_link = $current_data[$proof_link_field] ?? '';
                            
                            // If no amount set, keep it as null
                            $amount_value = ($current_amount !== null && $current_amount !== '') ? (float)$current_amount : null;
                            
                            // If no comment, add a default one
                            $comment_value = !empty($current_comment) ? $current_comment : 'ปิดงวดอัตโนมัติ';
                            
                            // Keep existing proof_link or set to null
                            $proof_link_value = !empty($current_proof_link) ? $current_proof_link : null;
                            
                            $updates[] = [
                                'status' => 'จ่ายแล้ว',
                                'date' => $now,
                                'amount' => $amount_value,
                                'comment' => $comment_value,
                                'proof_link' => $proof_link_value,
                                'phase' => $phase
                            ];
                            
                            $history_inserts[] = [
                                'phase' => $phase,
                                'status' => 'จ่ายแล้ว',
                                'amount' => $amount_value,
                                'date' => $now,
                                'comment' => $comment_value,
                                'proof_link' => $proof_link_value
                            ];
                        }
                    }
                    
                    if (!empty($updates)) {
                        // Build update SQL
                        $update_fields = [];
                        $update_values = [];
                        $types = '';
                        
                        foreach ($updates as $update) {
                            $phase = $update['phase'];
                            $update_fields[] = "fund_disbursement_{$phase}_status = ?";
                            $update_fields[] = "fund_disbursement_{$phase}_date = ?";
                            $update_fields[] = "fund_disbursement_{$phase}_amount = ?";
                            $update_fields[] = "fund_disbursement_{$phase}_comment = ?";
                            $update_fields[] = "fund_disbursement_{$phase}_proof_link = ?";
                            
                            $update_values[] = $update['status'];
                            $update_values[] = $update['date'];
                            $update_values[] = $update['amount'];
                            $update_values[] = $update['comment'];
                            $update_values[] = $update['proof_link'];
                            
                            $types .= 'ssdss'; // status (string), date (string), amount (decimal), comment (string), proof_link (string)
                        }
                        
                        // Add updated_by and updated_date
                        $update_fields[] = "fund_disbursement_updated_by = ?";
                        $update_fields[] = "fund_disbursement_updated_date = ?";
                        $update_values[] = $_SESSION['Username'];
                        $update_values[] = $now;
                        $types .= 'ss'; // username (string), date (string)
                        
                        // Add request_id for WHERE clause
                        $update_values[] = $requestId;
                        $types .= 'i'; // request_id (integer)
                        
                        $sql = "UPDATE research_requests_status SET " . implode(', ', $update_fields) . " WHERE request_id = ?";
                        $stmt = $conn->prepare($sql);
                        
                        if ($stmt) {
                            $stmt->bind_param($types, ...$update_values);
                            if ($stmt->execute()) {
                                // Insert into history table for each updated installment
                                $history_sql = "INSERT INTO fund_disbursement_history 
                                               (request_id, disbursement_phase, status, amount, disbursement_date, comment, proof_link, updated_by) 
                                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                                $history_stmt = $conn->prepare($history_sql);
                                
                                if ($history_stmt) {
                                    foreach ($history_inserts as $history) {
                                        $history_stmt->bind_param("issdssss", 
                                            $requestId, 
                                            $history['phase'], 
                                            $history['status'], 
                                            $history['amount'], 
                                            $history['date'], 
                                            $history['comment'], 
                                            $history['proof_link'],
                                            $_SESSION['Username']
                                        );
                                        $history_stmt->execute();
                                    }
                                    $history_stmt->close();
                                }
                                
                                $stmt->close();
                                
                                // Redirect with success message
                                $redirect_url = "details.php?request_id=" . (int)$requestId . "&status=period_closed&success=1&refresh=1";
                                header("Location: " . $redirect_url);
                                exit();
                            } else {
                                $error_message = "เกิดข้อผิดพลาดในการปิดงวด: " . $stmt->error;
                            }
                        } else {
                            $error_message = "เกิดข้อผิดพลาดในการเตรียม SQL: " . $conn->error;
                        }
                    } else {
                        $error_message = 'ทุกงวดได้ถูกจ่ายเงินเรียบร้อยแล้ว';
                    }
                }
            } else {
                $error_message = 'เกิดข้อผิดพลาดในการตรวจสอบคำขอ';
            }
        }
    } else {
        $error_message = 'ไม่ได้รับอนุญาตให้ดำเนินการนี้';
    }
    
    if (!empty($error_message)) {
        echo "<script>alert('" . addslashes($error_message) . "');</script>";
    }
}

$request_id = isset($_GET['request_id']) ? (int)$_GET['request_id'] : 0;
if ($request_id <= 0) {
    // Show a more user-friendly error page instead of just dying
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ข้อผิดพลาด - รหัสคำขอไม่ถูกต้อง</title>
        <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.24/dist/full.min.css" rel="stylesheet">
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    </head>
    <body class="bg-gray-50 font-['Kanit']">
        <div class="min-h-screen flex items-center justify-center">
            <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center">
                <div class="mb-6">
                    <svg class="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h1 class="text-2xl font-bold text-gray-800 mb-2">รหัสคำขอไม่ถูกต้อง</h1>
                    <p class="text-gray-600 mb-6">กรุณาระบุ request_id ใน URL เช่น ?request_id=8</p>
                </div>
                <div class="space-y-3">
                    <a href="status.php" class="btn btn-primary w-full">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        กลับไปหน้าสถานะ
                    </a>
                    <button onclick="history.back()" class="btn btn-outline w-full">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        กลับหน้าก่อนหน้า
                    </button>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

$research_request_status_data = null;
$original_table_name = '';
$original_record_id = 0;
$original_table_data = null;

// 1. ดึงข้อมูลจากตาราง research_requests_status

// --- Mapping label ภาษาไทย ---
$field_labels = [
    // research_requests_status
    'request_id' => 'รหัสคำขอ',
    'original_table' => 'ประเภทคำขอ',
    'original_id' => 'รหัสข้อมูลต้นฉบับ',
    'project_name' => 'ชื่อโครงการ',
    'submission_date' => 'วันที่ยื่นคำขอ',
    'requesting_user_email' => 'อีเมลผู้ยื่นคำขอ',
    'requesting_user_name' => 'ชื่อผู้ยื่นคำขอ',
    'current_status' => 'สถานะคำขอ',
    'approver_username' => 'ผู้อนุมัติ/ปฏิเสธ',
    'action_date' => 'วันที่อนุมัติ/ปฏิเสธ',
    'comment' => 'ความคิดเห็น/ข้อเสนอแนะ',
    // Fund disbursement fields
    'fund_disbursement_1st_status' => 'สถานะการจ่ายเงินงวดที่ 1',
    'fund_disbursement_1st_date' => 'วันที่จ่ายเงินงวดที่ 1',
    'fund_disbursement_1st_amount' => 'จำนวนเงินงวดที่ 1',
    'fund_disbursement_1st_comment' => 'หมายเหตุงวดที่ 1',
    'fund_disbursement_2nd_status' => 'สถานะการจ่ายเงินงวดที่ 2',
    'fund_disbursement_2nd_date' => 'วันที่จ่ายเงินงวดที่ 2',
    'fund_disbursement_2nd_amount' => 'จำนวนเงินงวดที่ 2',
    'fund_disbursement_2nd_comment' => 'หมายเหตุงวดที่ 2',
    'fund_disbursement_3rd_status' => 'สถานะการจ่ายเงินงวดที่ 3',
    'fund_disbursement_3rd_date' => 'วันที่จ่ายเงินงวดที่ 3',
    'fund_disbursement_3rd_amount' => 'จำนวนเงินงวดที่ 3',
    'fund_disbursement_3rd_comment' => 'หมายเหตุงวดที่ 3',
    'fund_disbursement_1st_proof_link' => 'ลิงก์หลักฐานการจ่ายเงินงวดที่ 1',
    'fund_disbursement_2nd_proof_link' => 'ลิงก์หลักฐานการจ่ายเงินงวดที่ 2',
    'fund_disbursement_3rd_proof_link' => 'ลิงก์หลักฐานการจ่ายเงินงวดที่ 3',
    'fund_disbursement_updated_by' => 'ผู้อัปเดตสถานะการจ่ายเงิน',
    'fund_disbursement_updated_date' => 'วันที่อัปเดตสถานะการจ่ายเงินล่าสุด',
    // research_proposals (นักศึกษา)
    'id' => 'รหัส',
    'project_th' => 'ชื่อโครงการ (ไทย)',
    'project_en' => 'ชื่อโครงการ (อังกฤษ)',
    'student_firstname' => 'ชื่อนักศึกษา',
    'student_lastname' => 'นามสกุลนักศึกษา',
    'student_level' => 'ระดับการศึกษา',
    'student_year' => 'ชั้นปี',
    'student_id' => 'รหัสนักศึกษา',
    'curriculum' => 'หลักสูตร',
    'major' => 'สาขาวิชา',
    'faculty' => 'คณะ',
    'student_phone' => 'เบอร์โทรนักศึกษา',
    'student_email' => 'อีเมลนักศึกษา',
    'student_ratio' => 'สัดส่วนนักศึกษา (%)',
    'advisor_firstname' => 'ชื่ออาจารย์ที่ปรึกษา',
    'advisor_lastname' => 'นามสกุลอาจารย์ที่ปรึกษา',
    'advisor_position' => 'ตำแหน่งอาจารย์ที่ปรึกษา',
    'advisor_department' => 'ภาควิชาอาจารย์ที่ปรึกษา',
    'advisor_faculty' => 'คณะอาจารย์ที่ปรึกษา',
    'advisor_phone' => 'เบอร์โทรอาจารย์ที่ปรึกษา',
    'advisor_email' => 'อีเมลอาจารย์ที่ปรึกษา',
    'advisor_ratio' => 'สัดส่วนอาจารย์ที่ปรึกษา (%)',
    'advisor_student_count' => 'จำนวนนักศึกษาที่ปรึกษา',
    'research_type' => 'ประเภทงานวิจัย',
    'learning_type' => 'รูปแบบการเรียนรู้',
    'activities' => 'กิจกรรม',
    'research_field' => 'สาขางานวิจัย',
    'rationale' => 'หลักการและเหตุผล',
    'objectives' => 'วัตถุประสงค์',
    'importance' => 'ความสำคัญ',
    'literature' => 'วรรณกรรมที่เกี่ยวข้อง',
    'conceptual_framework' => 'กรอบแนวคิด',
    'hypothesis' => 'สมมติฐาน',
    'methodology' => 'ระเบียบวิธีวิจัย',
    'references_link' => 'แหล่งอ้างอิง',
    'research_start' => 'วันที่เริ่มวิจัย',
    'research_end' => 'วันที่สิ้นสุดวิจัย',
    'research_schedule' => 'กำหนดการวิจัย',
    'success_indicators' => 'ตัวชี้วัดความสำเร็จ',
    'publication_title' => 'ชื่อผลงานตีพิมพ์',
    'journal_name' => 'วารสาร',
    'requested_budget' => 'งบประมาณที่ขอ',
    'budget_details' => 'รายละเอียดงบประมาณ',
    'created_at' => 'วันที่สร้างข้อมูล',
    'proposal_file_path' => 'ไฟล์ข้อเสนอโครงการ',
    'additional_file_path' => 'ไฟล์เอกสารประกอบเพิ่มเติม',
    'fund_support' => 'ประเภททุนสนับสนุน',
    // research_personnel (บุคลากร)
    'leader_firstname' => 'ชื่อหัวหน้าโครงการ',
    'leader_lastname' => 'นามสกุลหัวหน้าโครงการ',
    'leader_position' => 'ตำแหน่งหัวหน้าโครงการ',
    'leader_department' => 'สังกัด/ภาควิชา',
    'leader_phone' => 'เบอร์โทรหัวหน้าโครงการ',
    'leader_email' => 'อีเมลหัวหน้าโครงการ',
    'leader_ratio' => 'สัดส่วนหัวหน้าโครงการ (%)',
    'co_researchers' => 'ผู้ร่วมวิจัย',
    'msu_goals' => 'เป้าหมาย มมส.',
    'learning_research' => 'รูปแบบการเรียนรู้',
    // ... (field อื่นๆ ใช้ร่วมกับ proposals ด้านบน) ...
    // research_teacher (อาจารย์)
    'project_thai_name' => 'ชื่อโครงการ (ไทย)',
    'project_english_name' => 'ชื่อโครงการ (อังกฤษ)',
    'teacher_prefix_name' => 'คำนำหน้าชื่อ',
    'teacher_academic_position' => 'ตำแหน่งทางวิชาการ',
    'teacher_department' => 'ภาควิชา',
    'teacher_faculty_unit' => 'คณะ/หน่วยงาน',
    'teacher_mobile_phone' => 'เบอร์โทรศัพท์',
    'teacher_email' => 'อีเมล',
    'teacher_research_proportion' => 'สัดส่วนการทำวิจัย (%)',
    'teacher_expert_field' => 'สาขาที่เชี่ยวชาญ',
    'teacher_education_history' => 'ประวัติการศึกษา',
    'teacher_international_publications' => 'ผลงานตีพิมพ์ระดับนานาชาติ',
    'co_researchers_details' => 'ผู้ร่วมวิจัย',
    'student_co_researchers_details' => 'นักศึกษาร่วมวิจัย',
    'ethics_related' => 'เกี่ยวข้องกับจริยธรรมหรือไม่',
    'ethics_certification_number' => 'เลขที่รับรองจริยธรรม',
    'problem_significance' => 'ความสำคัญของปัญหา',
    'operation_plan' => 'แผนการดำเนินงาน',
    'expected_outcomes' => 'ผลลัพธ์ที่คาดหวัง',
    // ... (field อื่นๆ ใช้ร่วมกับ proposals/personnel ด้านบน) ...
];

// --- Mapping table names to Thai ---
$table_name_thai = [
    'research_proposals' => 'ทุนวิจัยนักศึกษา',
    'research_personnel' => 'ทุนวิจัยบุคลากร',
    'research_teacher' => 'ทุนวิจัยอาจารย์',
];

$sql_status = "SELECT *, 
               fund_disbursement_1st_status, fund_disbursement_1st_date, fund_disbursement_1st_amount, fund_disbursement_1st_comment, fund_disbursement_1st_proof_link,
               fund_disbursement_2nd_status, fund_disbursement_2nd_date, fund_disbursement_2nd_amount, fund_disbursement_2nd_comment, fund_disbursement_2nd_proof_link,
               fund_disbursement_3rd_status, fund_disbursement_3rd_date, fund_disbursement_3rd_amount, fund_disbursement_3rd_comment, fund_disbursement_3rd_proof_link,
               fund_disbursement_updated_by, fund_disbursement_updated_date
               FROM research_requests_status WHERE request_id = ?";
$stmt_status = $conn->prepare($sql_status);
if (!$stmt_status) {
    die("Prepare failed (research_requests_status query): " . $conn->error);
}
$stmt_status->bind_param("i", $request_id);
$stmt_status->execute();
$result_status = $stmt_status->get_result();

if ($result_status->num_rows > 0) {
    $research_request_status_data = $result_status->fetch_assoc();
    $original_table_name = $research_request_status_data['original_table']; 
    $original_record_id = (int)$research_request_status_data['original_id'];
}
$stmt_status->close();

// 2. ดึงข้อมูลจากตารางต้นฉบับ
if ($original_table_name && $original_record_id > 0) {
    $allowed_tables = ['research_proposals', 'research_personnel', 'research_teacher'];

    if (in_array($original_table_name, $allowed_tables)) {
        $primary_key_column = '';
        switch ($original_table_name) {
            case 'research_proposals':
                $primary_key_column = 'id';
                break;
            case 'research_personnel':
                $primary_key_column = 'id';  // แก้ไขเป็น 'id' ตามที่แจ้ง
                break;
            case 'research_teacher':
                $primary_key_column = 'id';
                break;
            default:
                die("ข้อผิดพลาด: ไม่สามารถระบุคอลัมน์ Primary Key สำหรับตาราง '{$original_table_name}' ได้");
        }

        if (empty($primary_key_column)) {
            die("เกิดข้อผิดพลาด: ไม่สามารถกำหนดคอลัมน์ Primary Key ได้สำหรับตาราง '{$original_table_name}'");
        }

        $sql_original = "SELECT * FROM `{$original_table_name}` WHERE `{$primary_key_column}` = ?";
        $stmt_original = $conn->prepare($sql_original);
        if (!$stmt_original) {
            die("Prepare failed (original table query for {$original_table_name}): " . $conn->error);
        }
        $stmt_original->bind_param("i", $original_record_id);
        $stmt_original->execute();
        $result_original = $stmt_original->get_result();

        if ($result_original->num_rows > 0) {
            $original_table_data = $result_original->fetch_assoc();
        }
        $stmt_original->close();
    } else {
        echo "<p class='text-red-500'>ข้อผิดพลาดด้านความปลอดภัย: ชื่อตารางต้นฉบับ ('" . htmlspecialchars($original_table_name) . "') ไม่ถูกต้องหรือไม่ได้รับอนุญาตให้เข้าถึง.</p>";
    }
}

// 3. ดึงข้อมูล Fund Support (BH1, BH2, B3) สำหรับคำขอนี้
$bh1 = 0;
$bh2 = 0;
$b3 = 0;
$fund_name = null;

if ($original_table_name && $original_record_id > 0) {
    // Get fund_name from fund_type_selections
    $fund_sql = "SELECT fund_name FROM fund_type_selections 
                 WHERE table_source = ? AND proposal_id = ? 
                 ORDER BY selected_date DESC LIMIT 1";
    $fund_stmt = $conn->prepare($fund_sql);
    if ($fund_stmt) {
        $fund_stmt->bind_param("si", $original_table_name, $original_record_id);
        $fund_stmt->execute();
        $fund_result = $fund_stmt->get_result();
        if ($fund_result->num_rows > 0) {
            $fund_row = $fund_result->fetch_assoc();
            $fund_name = $fund_row['fund_name'];
            
            // Get BH1, BH2, B3 from fund_support using fund_name (FunName)
            $fund_support_sql = "SELECT BH1, BH2, B3 FROM fund_support WHERE FunName = ? LIMIT 1";
            $fund_support_stmt = $conn->prepare($fund_support_sql);
            if ($fund_support_stmt) {
                $fund_support_stmt->bind_param("s", $fund_name);
                $fund_support_stmt->execute();
                $fund_support_result = $fund_support_stmt->get_result();
                if ($fund_support_result->num_rows > 0) {
                    $fund_support_row = $fund_support_result->fetch_assoc();
                    $bh1 = (int)$fund_support_row['BH1'];
                    $bh2 = (int)$fund_support_row['BH2'];
                    $b3 = (int)$fund_support_row['B3'];
                }
                $fund_support_stmt->close();
            }
        }
        $fund_stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>รายละเอียดข้อมูลที่เกี่ยวข้อง</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.24/dist/full.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&family=Kanit:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/details.css">
    <style>
        :root {
            --primary: #1e40af;
            --primary-light: #3b82f6;
            --primary-dark: #1e3a8a;
            --accent: #0ea5e9;
            --success: #059669;
            --warning: #d97706;
            --danger: #dc2626;
            --surface: #ffffff;
            --surface-2: #f8fafc;
            --border: #e2e8f0;
            --text-main: #1e293b;
            --text-muted: #64748b;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 50%, #f0f9ff 100%);
            min-height: 100vh;
            color: var(--text-main);
        }
        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 60%, var(--accent) 100%);
            border-radius: 20px;
            padding: 2rem 2.5rem;
            margin-bottom: 1.75rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(30, 64, 175, 0.3);
        }
        .page-header::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 220px; height: 220px;
            border-radius: 50%;
            background: rgba(255,255,255,0.07);
        }
        .page-header::after {
            content: '';
            position: absolute;
            bottom: -40px; left: 40%;
            width: 160px; height: 160px;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
        }
        .page-header h2 {
            font-family: 'Kanit', sans-serif;
            font-size: 1.75rem;
            font-weight: 700;
            color: #fff;
            margin: 0 0 0.35rem 0;
            position: relative; z-index: 1;
        }
        .page-header p {
            color: rgba(255,255,255,0.8);
            font-size: 0.95rem;
            margin: 0;
            position: relative; z-index: 1;
        }
        /* Quick Link Button */
        .payment-status-link-btn {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            padding: 0.7rem 1.2rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 4px 20px rgba(30, 64, 175, 0.4);
            display: flex;
            align-items: center;
            gap: 0.4rem;
            z-index: 50;
            transition: all 0.25s ease;
        }
        .payment-status-link-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(30, 64, 175, 0.5);
        }
        /* Alert Banners */
        .alert-success-banner {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            border: 1px solid #6ee7b7;
            border-left: 4px solid var(--success);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideDown 0.3s ease;
        }
        .alert-error-banner {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            border: 1px solid #fca5a5;
            border-left: 4px solid var(--danger);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        /* Section Cards */
        .data-section {
            background: var(--surface);
            border-radius: 18px;
            box-shadow: 0 2px 16px rgba(30,64,175,0.07), 0 1px 4px rgba(0,0,0,0.04);
            overflow: hidden;
            border: 1px solid var(--border);
            transition: box-shadow 0.2s;
        }
        .data-section:hover {
            box-shadow: 0 6px 28px rgba(30,64,175,0.12), 0 2px 8px rgba(0,0,0,0.05);
        }
        .data-section > h3 {
            font-family: 'Kanit', sans-serif;
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--primary);
            padding: 1.1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(90deg, #eff6ff, #f8fafc);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0;
        }
        /* Data Items Grid */
        .data-items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 0;
        }
        .data-item {
            padding: 0.85rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
            transition: background 0.15s;
        }
        .data-item:hover { background: #f8faff; }
        .data-item strong {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .data-item span {
            font-size: 0.9rem;
            color: var(--text-main);
            line-height: 1.5;
            word-break: break-word;
        }
        /* Status Badge in data-item */
        .status-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-approved  { background: #d1fae5; color: #065f46; }
        .status-pending   { background: #fef9c3; color: #854d0e; }
        .status-rejected  { background: #fee2e2; color: #991b1b; }
        /* Installment Cards */
        .installment-card {
            background: var(--surface);
            border-radius: 14px;
            border: 1px solid var(--border);
            overflow: hidden;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .installment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(30,64,175,0.12);
        }
        .installment-header {
            padding: 1rem 1.25rem 0.75rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .installment-title {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        .phase-number {
            font-family: 'Kanit', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            color: var(--primary);
        }
        .status-indicator { font-size: 1.1rem; }
        .status-badge {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            letter-spacing: 0.02em;
        }
        .status-badge.status-จ่ายแล้ว  { background: #d1fae5; color: #065f46; }
        .status-badge.status-รอการจ่าย { background: #fef9c3; color: #854d0e; }
        .status-badge.status-ไม่จ่าย   { background: #fee2e2; color: #991b1b; }
        /* Payment info inside card */
        .payment-info {
            padding: 0.85rem 1.25rem;
        }
        .payment-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.3rem 0;
            border-bottom: 1px dashed #f1f5f9;
            font-size: 0.85rem;
        }
        .payment-row:last-child { border-bottom: none; }
        .payment-label { color: var(--text-muted); }
        .payment-value { font-weight: 600; color: var(--text-main); }
        .payment-amount { color: #059669; font-size: 1rem; }
        /* Admin Form inside card */
        .admin-form-section {
            padding: 0.85rem 1.25rem;
            background: #f8faff;
            border-top: 1px solid var(--border);
        }
        .form-label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 0.3rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .form-control {
            width: 100%;
            padding: 0.55rem 0.85rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-family: 'Sarabun', sans-serif;
            font-size: 0.875rem;
            color: var(--text-main);
            background: white;
            transition: border-color 0.15s, box-shadow 0.15s;
            outline: none;
        }
        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.6rem 1.4rem;
            border-radius: 10px;
            font-family: 'Sarabun', sans-serif;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            text-decoration: none;
            white-space: nowrap;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            box-shadow: 0 2px 8px rgba(30,64,175,0.3);
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            box-shadow: 0 4px 14px rgba(30,64,175,0.4);
            transform: translateY(-1px);
        }
        .btn-outline {
            background: white;
            color: var(--primary);
            border: 1.5px solid var(--primary-light);
        }
        .btn-outline:hover {
            background: #eff6ff;
            border-color: var(--primary);
        }
        .btn-success-full {
            background: linear-gradient(135deg, #059669, #10b981);
            color: white;
            box-shadow: 0 2px 8px rgba(5,150,105,0.3);
        }
        .btn-success-full:hover {
            background: linear-gradient(135deg, #047857, #059669);
            box-shadow: 0 4px 14px rgba(5,150,105,0.4);
            transform: translateY(-1px);
        }
        .close-period-btn {
            background: linear-gradient(135deg, #059669, #10b981);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 12px;
            font-family: 'Sarabun', sans-serif;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            max-width: 360px;
            margin: 0 auto;
            box-shadow: 0 4px 14px rgba(5,150,105,0.35);
            transition: all 0.2s ease;
        }
        .close-period-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(5,150,105,0.45);
        }
        /* Progress bar custom */
        .progress-bar-track {
            background: #dbeafe;
            border-radius: 99px;
            height: 8px;
            overflow: hidden;
        }
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            border-radius: 99px;
            transition: width 0.6s cubic-bezier(.4,0,.2,1);
        }
        /* Period closed badge */
        .period-closed-badge {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            border: 1px solid #6ee7b7;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        /* Warning/info empty state in card */
        .card-empty-state {
            padding: 1.5rem;
            text-align: center;
        }
        .card-empty-state svg { opacity: 0.4; }
        /* Bottom Actions */
        .bottom-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-top: 2rem;
            padding: 1.25rem;
            background: white;
            border-radius: 14px;
            border: 1px solid var(--border);
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        /* Smooth scrolling */
        html { scroll-behavior: smooth; }
        /* Responsive tweaks */
        @media (max-width: 640px) {
            .page-header { padding: 1.5rem; }
            .page-header h2 { font-size: 1.4rem; }
            .data-items-grid { grid-template-columns: 1fr; }
            .bottom-actions { flex-direction: column; }
            .bottom-actions .btn { width: 100%; }
        }
    </style>
</head>
<body class="p-4 md:p-6" data-request-id="<?= (int)$request_id ?>">
    <!-- Payment Status Quick Link Button -->
    <?php if ($research_request_status_data && $research_request_status_data['current_status'] === 'อนุมัติ'): ?>
        <a href="#payment-status-section" class="payment-status-link-btn btn-sm">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
            </svg>
            สถานะการจ่ายเงิน
        </a>
    <?php endif; ?>
    
    <div class="max-w-5xl mx-auto">
        <div class="page-header">
            <h2 class="text-3xl font-bold mb-2">ข้อมูลที่เกี่ยวข้องกับคำร้องขอ</h2>
            <p class="text-blue-100">รหัสคำขอ: <?= htmlspecialchars($request_id) ?></p>
        </div>

        <div class="grid gap-6">
            <?php if ($research_request_status_data): ?>
                <div class="data-section">
                    <h3>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        ข้อมูลสถานะคำขอ
                    </h3>
                    <div class="data-items-grid">
                        <?php foreach ($research_request_status_data as $key => $value): ?>
                            <div class="data-item">
                                <strong><?= isset($field_labels[$key]) ? htmlspecialchars($field_labels[$key]) : htmlspecialchars($key) ?></strong>
                                <span>
                                    <?php 
                                    if ($key === 'original_table' && isset($table_name_thai[$value])) {
                                        echo htmlspecialchars($table_name_thai[$value]);
                                    } elseif ($key === 'current_status') {
                                        $statusClass = $value === 'อนุมัติ' ? 'status-approved' : ($value === 'ปฏิเสธ' ? 'status-rejected' : 'status-pending');
                                        echo '<span class="status-tag '.$statusClass.'">'.htmlspecialchars($value).'</span>';
                                    } else {
                                        echo $value ? nl2br(htmlspecialchars($value)) : '<span style="color:#94a3b8">-</span>';
                                    }
                                    ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                ไม่พบข้อมูลสำหรับรหัสคำขอสถานะนี้ (Request ID: <?= htmlspecialchars($request_id) ?>)
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($original_table_data): ?>
                <div class="data-section">
                    <h3>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        รายละเอียดคำร้องขอ
                    </h3>
                    <div class="data-items-grid">
                        <?php foreach ($original_table_data as $key => $value): ?>
                            <div class="data-item">
                                <strong><?= isset($field_labels[$key]) ? htmlspecialchars($field_labels[$key]) : htmlspecialchars($key) ?></strong>
                                <?php if (($key === 'proposal_file_path' || $key === 'file_path') && !empty($value)): ?>
                                    <span>
                                        <?= nl2br(htmlspecialchars($value)) ?>
                                        <div class="mt-2 flex gap-2 flex-wrap">
                                            <?php
                                            $file_path = 'uploads/' . htmlspecialchars($value);
                                            $file_exists = file_exists($file_path);
                                            if ($file_exists):
                                            ?>
                                                <a href="<?= $file_path ?>" target="_blank" class="btn btn-outline" style="padding:0.35rem 0.9rem;font-size:0.8rem;">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                    ดูไฟล์
                                                </a>
                                                <a href="<?= $file_path ?>" download class="btn btn-success-full" style="padding:0.35rem 0.9rem;font-size:0.8rem;">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                    ดาวน์โหลด
                                                </a>
                                            <?php else: ?>
                                                <span style="color:#ef4444;font-size:0.8rem;">ไม่พบไฟล์ในระบบ</span>
                                            <?php endif; ?>
                                        </div>
                                    </span>
                                <?php else: ?>
                                    <span><?= $value ? nl2br(htmlspecialchars($value)) : '<span style="color:#94a3b8">-</span>' ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php elseif ($research_request_status_data && !$original_table_data): ?>
                <div class="bg-red-50 border-l-4 border-red-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                ไม่พบข้อมูลในตารางต้นฉบับ (<?= htmlspecialchars($original_table_name) ?>) สำหรับ ID: <?= htmlspecialchars($original_record_id) ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Fund Disbursement Status Section -->
            <?php if ($research_request_status_data && $research_request_status_data['current_status'] === 'อนุมัติ'): ?>
                <div class="data-section" id="payment-status-section">
                    <h3>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                        สถานะการจ่ายเงิน
                        <?php if ($isAdmin): ?>
                            <span style="font-size:0.78rem;color:#3b82f6;font-weight:400;font-family:'Sarabun',sans-serif;">(สามารถแก้ไขได้)</span>
                        <?php endif; ?>
                    </h3>
                    <!-- Payment Progress Summary -->
                    <?php
                    // Determine active installments based on BH1, BH2, B3
                    $active_installments = [];
                    if ($bh1 > 0) $active_installments[] = '1st';
                    if ($bh2 > 0) $active_installments[] = '2nd';
                    if ($b3 > 0) $active_installments[] = '3rd';
                    
                    $total_installments = count($active_installments);
                    $paid_installments = 0;
                    $total_amount = 0;
                    
                    foreach ($active_installments as $phase) {
                        $status = $research_request_status_data["fund_disbursement_{$phase}_status"] ?? '';
                        $amount = $research_request_status_data["fund_disbursement_{$phase}_amount"] ?? 0;
                        
                        if ($status === 'จ่ายแล้ว') {
                            $paid_installments++;
                            $total_amount += (float)$amount;
                        }
                    }
                    
                    $progress_percentage = $total_installments > 0 ? ($paid_installments / $total_installments) * 100 : 0;
                    ?>
                    <div class="mb-5 p-4 rounded-14" style="background:linear-gradient(135deg,#eff6ff,#f0f9ff);border:1px solid #bfdbfe;border-radius:14px;">
                        <div class="flex items-center justify-between mb-2">
                            <h4 style="font-family:'Kanit',sans-serif;font-size:0.9rem;font-weight:600;color:#1e40af;margin:0;">ความคืบหน้าการจ่ายเงิน</h4>
                            <span style="font-size:0.85rem;font-weight:600;color:#3b82f6;"><?= $paid_installments ?>/<?= $total_installments ?> งวด</span>
                        </div>
                        <div class="progress-bar-track mb-2">
                            <div class="progress-bar-fill" style="width: <?= $progress_percentage ?>%"></div>
                        </div>
                        <div class="flex justify-between" style="font-size:0.78rem;color:#3b82f6;">
                            <span>จ่ายแล้ว: <?= $paid_installments ?> งวด</span>
                            <?php if ($total_amount > 0): ?>
                                <span style="font-weight:700;">รวม ฿<?= number_format($total_amount, 2) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                    // Check if period is closed (all active installments are paid) - calculate early
                    $period_closed = true;
                    $has_unpaid = false;
                    foreach ($active_installments as $phase) {
                        $status = $research_request_status_data["fund_disbursement_{$phase}_status"] ?? 'รอการจ่าย';
                        if ($status !== 'จ่ายแล้ว') {
                            $period_closed = false;
                            $has_unpaid = true;
                            break;
                        }
                    }
                    ?>
                    <?php
                    // Determine grid columns class based on number of installments
                    $grid_cols_class = 'grid-cols-1';
                    if ($total_installments === 2) {
                        $grid_cols_class .= ' md:grid-cols-2';
                    } elseif ($total_installments >= 3) {
                        $grid_cols_class .= ' md:grid-cols-3';
                    }
                    ?>
                    <div class="grid <?= $grid_cols_class ?> gap-4">
                        <?php
                        // Determine which installments to show based on BH1, BH2, B3 and user role
                        $installments_to_show = [];
                        
                        // First, filter by active installments (BH1, BH2, B3 > 0)
                        $available_installments = [];
                        if ($bh1 > 0) $available_installments[] = '1st';
                        if ($bh2 > 0) $available_installments[] = '2nd';
                        if ($b3 > 0) $available_installments[] = '3rd';
                        
                        if ($isAdmin) {
                            // For admins: progressive display (only show next installment if previous is paid)
                            // But only show installments that are active (BH1, BH2, B3 > 0)
                            foreach ($available_installments as $index => $installment) {
                                if ($index === 0) {
                                    // Always show first available installment
                                    $installments_to_show[] = $installment;
                                } else {
                                    // Show next installment only if previous is paid
                                    $prev_installment = $available_installments[$index - 1];
                                    if (($research_request_status_data["fund_disbursement_{$prev_installment}_status"] ?? '') === 'จ่ายแล้ว') {
                                        $installments_to_show[] = $installment;
                                    }
                                }
                            }
                        } else {
                            // For non-admins: show all active installments as read-only information
                            $installments_to_show = $available_installments;
                        }
                        
                        // If no installments are active, show a message
                        if (empty($installments_to_show)):
                    ?>
                        <div class="col-span-full p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-center">
                            <p class="text-yellow-800">ไม่มีการกำหนดงวดการจ่ายเงินสำหรับทุนประเภทนี้</p>
                        </div>
                    <?php else: ?>
                        <!-- Display installments in order -->
                        <?php foreach ($installments_to_show as $installment): 
                            $phase_num = $installment === '1st' ? '1' : ($installment === '2nd' ? '2' : '3');
                            $status = $research_request_status_data["fund_disbursement_{$installment}_status"] ?? 'รอการจ่าย';
                            $amount = $research_request_status_data["fund_disbursement_{$installment}_amount"] ?? '';
                            $comment = $research_request_status_data["fund_disbursement_{$installment}_comment"] ?? '';
                            $date = $research_request_status_data["fund_disbursement_{$installment}_date"] ?? '';
                            $proof_link = $research_request_status_data["fund_disbursement_{$installment}_proof_link"] ?? '';
                        ?>
                        <!-- <?= $installment ?> Installment -->
                        <div class="installment-card enhanced">
                            <div class="installment-header">
                                <div class="installment-title">
                                    <div class="phase-number">งวดที่ <?= $phase_num ?></div>
                                    <div class="status-indicator status-<?= htmlspecialchars($status) ?>">
                                        <?php 
                                        $statusIcon = $status === 'รอการจ่าย' ? '⏳' : ($status === 'จ่ายแล้ว' ? '✅' : '❌');
                                        echo $statusIcon;
                                        ?>
                                    </div>
                                </div>
                                <div class="status-badge status-<?= htmlspecialchars($status) ?>">
                                    <?= htmlspecialchars($status) ?>
                                </div>
                            </div>
                            
                            <!-- Payment Status Display -->
                            <div class="payment-info">
                                <div class="payment-row">
                                    <span class="payment-label">จำนวนเงิน</span>
                                    <?php if ($amount): ?>
                                        <span class="payment-value payment-amount">฿<?= number_format($amount, 2) ?></span>
                                    <?php else: ?>
                                        <span style="color:#94a3b8;font-size:0.85rem;">-</span>
                                    <?php endif; ?>
                                </div>
                                <div class="payment-row">
                                    <span class="payment-label">วันที่จ่าย</span>
                                    <?php if ($date): ?>
                                        <span class="payment-value"><?= date('d/m/Y H:i', strtotime($date)) ?></span>
                                    <?php else: ?>
                                        <span style="color:#94a3b8;font-size:0.85rem;">-</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($comment): ?>
                                <div class="payment-row" style="flex-direction:column;align-items:flex-start;gap:0.2rem;">
                                    <span class="payment-label">หมายเหตุ</span>
                                    <span style="font-size:0.83rem;color:#475569;line-height:1.5;"><?= htmlspecialchars($comment) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ($proof_link): ?>
                                <div class="payment-row" style="flex-direction:column;align-items:flex-start;gap:0.2rem;">
                                    <span class="payment-label">ลิงก์หลักฐาน</span>
                                    <a href="<?= htmlspecialchars($proof_link) ?>" target="_blank" rel="noopener noreferrer" style="font-size:0.8rem;color:#3b82f6;word-break:break-all;display:flex;align-items:center;gap:0.25rem;">
                                        <?= htmlspecialchars($proof_link) ?>
                                        <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($isAdmin): ?>
                                <!-- Update Form (only for admins, and only show if not paid and period not closed) -->
                                <?php if ($status !== 'จ่ายแล้ว' && !$period_closed): ?>
                                <form method="POST" action="details.php" class="admin-form-section" style="display:flex;flex-direction:column;gap:0.6rem;">
                                    <input type="hidden" name="action" value="update_fund_disbursement">
                                    <input type="hidden" name="request_id" value="<?= (int)$request_id ?>">
                                    <input type="hidden" name="phase" value="<?= $installment ?>">
                                    
                                    <div>
                                        <label class="form-label">สถานะ</label>
                                        <select name="fund_status" class="form-control" required>
                                            <option value="รอการจ่าย" <?= $status === 'รอการจ่าย' ? 'selected' : '' ?>>รอการจ่าย</option>
                                            <option value="จ่ายแล้ว" <?= $status === 'จ่ายแล้ว' ? 'selected' : '' ?>>จ่ายแล้ว</option>
                                            <option value="ไม่จ่าย" <?= $status === 'ไม่จ่าย' ? 'selected' : '' ?>>ไม่จ่าย</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label">จำนวนเงิน (บาท)</label>
                                        <input type="number" name="amount" step="0.01" min="0" 
                                               value="<?= htmlspecialchars($amount) ?>"
                                               class="form-control" 
                                               placeholder="0.00">
                                    </div>
                                    <div>
                                        <label class="form-label">หมายเหตุ</label>
                                        <textarea name="comment" rows="2" 
                                                  class="form-control" style="resize:none;"
                                                  placeholder="หมายเหตุเพิ่มเติม..."><?= htmlspecialchars($comment) ?></textarea>
                                    </div>
                                    <div>
                                        <label class="form-label">ลิงก์หลักฐาน (URL)</label>
                                        <input type="url" name="proof_link" 
                                               value="<?= htmlspecialchars($proof_link) ?>"
                                               class="form-control" 
                                               placeholder="https://example.com/proof">
                                        <p style="font-size:0.75rem;color:#94a3b8;margin-top:0.25rem;">กรุณาระบุลิงก์หลักฐานการจ่ายเงิน (ถ้ามี)</p>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-full" style="margin-top:0.25rem;">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        อัปเดตงวดที่ <?= $phase_num ?>
                                    </button>
                                </form>
                                <?php elseif ($status !== 'จ่ายแล้ว' && $period_closed): ?>
                                <!-- Period closed - show read-only info -->
                                <div class="text-center py-4">
                                    <div class="text-gray-400 mb-2">
                                        <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                    </div>
                                    <p class="text-sm text-gray-500 font-medium">งวดถูกปิดแล้ว</p>
                                    <p class="text-xs text-gray-400 mt-1">ไม่สามารถแก้ไขได้</p>
                                </div>
                                <?php else: ?>
                                <!-- Paid Status Display (for admins) -->
                                <div class="text-center py-4">
                                    <div class="text-green-600 mb-2">
                                        <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <p class="text-sm text-green-600 font-medium">จ่ายเงินเรียบร้อยแล้ว</p>
                                    <p class="text-xs text-gray-500 mt-1">งวดนี้จะไม่แสดงอีกต่อไป</p>
                                </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- Read-only display for non-admins -->
                                <div class="text-center py-3">
                                    <p class="text-xs text-gray-500">ข้อมูลการจ่ายเงิน</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </div>
                    
                    <!-- Close Period Button -->
                    <?php if ($isAdmin && $has_unpaid): ?>
                        <div class="mt-6 flex justify-center">
                            <form method="POST" action="details.php" id="close-period-form" class="w-full max-w-md">
                                <input type="hidden" name="action" value="close_period">
                                <input type="hidden" name="request_id" value="<?= (int)$request_id ?>">
                                <button type="submit" class="close-period-btn w-full">
                                    <svg class="w-5 h-5 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    ปิดงวด (จ่ายเงินทั้งหมดทันที)
                                </button>
                            </form>
                        </div>
                    <?php elseif ($isAdmin && $period_closed): ?>
                        <div class="mt-6 mx-6 mb-6">
                            <div class="period-closed-badge">
                                <svg class="w-6 h-6 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div>
                                    <p style="font-weight:600;color:#065f46;font-size:0.9rem;">งวดการจ่ายเงินถูกปิดแล้ว</p>
                                    <p style="font-size:0.8rem;color:#059669;margin-top:0.1rem;">ทุกงวดได้รับการจ่ายเงินเรียบร้อยแล้ว ไม่สามารถแก้ไขได้</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="bottom-actions">
                <a href="details_print.php?request_id=<?= (int)$request_id ?>" target="_blank" class="btn btn-outline">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    ดาวน์โหลด PDF
                </a>
                <button onclick="window.close()" class="btn btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    ปิดหน้าต่าง
                </button>
            </div>
        </div>
    </div>
    <script src="assets/details.js"></script>
</body>
</html>