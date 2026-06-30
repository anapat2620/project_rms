<?php
require_once __DIR__ . '/../config.php';

session_start();

require_once '../controller/update_quantity.php';
require_once '../controller/file_upload_handler.php';

// --- Detect form type from query string ---
$formType = $_GET['type'] ?? '';
$allowedTypes = ['student', 'teacher', 'personnel'];

if (!in_array($formType, $allowedTypes, true)) {
    echo 'Invalid form type.';
    exit();
}

// --- Database Config ---
// Use central configuration from `src/config.php` (DB_HOST, DB_USER, DB_PASS, DB_NAME).

// --- Default redirect by form type ---
$defaultRedirectMap = [
    'student'   => '../student.php',
    'teacher'   => '../teacher.php',
    'personnel' => '../personnel.php',
];

$defaultRedirect = $defaultRedirectMap[$formType] ?? '../index.php';

function show_alert_and_redirect($message, $redirect_url = null, $status = 'error') {
    global $defaultRedirect;
    if ($redirect_url === null) {
        $redirect_url = $defaultRedirect;
    }

    $_SESSION['toast_message'] = $message;
    $_SESSION['toast_status']  = $status; // 'success' | 'error' | 'warning'

    header("Location: $redirect_url");
    exit();
}

// --- Basic request/session validation ---
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    show_alert_and_redirect('Invalid request method. โปรดส่งข้อมูลด้วยเมธอด POST.');
}

if (!isset($_SESSION['Username'], $_SESSION['Email'])) {
    show_alert_and_redirect('Session หมดอายุ หรือยังไม่ได้เข้าสู่ระบบ');
}

// --- ตรวจสอบ fund_support (server-side) ---
if (trim($_POST['fund_support'] ?? '') === '') {
    show_alert_and_redirect('กรุณาเลือกประเภททุนสนับสนุนก่อนยื่นแบบฟอร์ม', $defaultRedirect, 'error');
}

// --- Connect to DB with PDO ---
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    show_alert_and_redirect('ERROR: ไม่สามารถเชื่อมต่อฐานข้อมูลได้: ' . $e->getMessage());
}

try {
    // --- Upload directory per form type ---
    $uploadBase = '../uploads';
    $uploadDirMap = [
        'student'   => $uploadBase . '/student',
        'teacher'   => $uploadBase . '/teacher',
        'personnel' => $uploadBase . '/personnel',
    ];

    $uploadDir = $uploadDirMap[$formType] ?? ($uploadBase . '/misc');

    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $proposalFilePath   = null;
    $additionalFilePath = null;
    $publicationFilePath = null;
    $ethicsFilePath = null;

    $uploadPrefixMap = [
        'teacher' => 'teacher-mbs',
        'personnel' => 'personnel-mbp',
    ];

    if ($formType === 'student') {
        $studentLevel = strtolower(trim($_POST['student_level'] ?? ''));
        $studentLevelMap = [
            'bachelor' => 'student-msb-bachelor',
            'master'   => 'student-msb-master',
            'phd'      => 'student-msb-phd',
        ];
        $uploadPrefix = $studentLevelMap[$studentLevel] ?? 'student-msb';
    } else {
        $uploadPrefix = $uploadPrefixMap[$formType] ?? null;
    }

    if (isset($_FILES['proposal_file']) && $_FILES['proposal_file']['error'] === UPLOAD_ERR_OK) {
        $proposalFilePath = handleFileUpload($_FILES['proposal_file'], $uploadDir, ['pdf'], $uploadPrefix);
    }

    if (isset($_FILES['additional_file']) && $_FILES['additional_file']['error'] === UPLOAD_ERR_OK) {
        $additionalFilePath = handleFileUpload($_FILES['additional_file'], $uploadDir, ['pdf'], $uploadPrefix);
    }

    if (isset($_FILES['publication_file']) && $_FILES['publication_file']['error'] === UPLOAD_ERR_OK) {
        $publicationFilePath = handleFileUpload($_FILES['publication_file'], $uploadDir, ['pdf'], $uploadPrefix);
    }

    if (isset($_FILES['ethics_file']) && $_FILES['ethics_file']['error'] === UPLOAD_ERR_OK) {
        $ethicsFilePath = handleFileUpload($_FILES['ethics_file'], $uploadDir, ['pdf'], $uploadPrefix);
    }

    // --- Branch by form type ---
    if ($formType === 'student') {
        // ===== STUDENT (research_proposals) =====
        $student_name        = $_POST['student_name'] ?? '';
        $student_name_parts  = explode(' ', $student_name, 2);
        $student_firstname   = $student_name_parts[0] ?? '';
        $student_lastname    = $student_name_parts[1] ?? '';

        $advisor_name        = $_POST['advisor_name'] ?? '';
        $advisor_name_parts  = explode(' ', $advisor_name, 2);
        $advisor_firstname   = $advisor_name_parts[0] ?? '';
        $advisor_lastname    = $advisor_name_parts[1] ?? '';

        $sql = "INSERT INTO research_proposals (
            project_th, project_en,
            student_firstname, student_lastname, student_level, student_year, student_id, major, faculty, student_phone, student_email,
            advisor_firstname, advisor_lastname, advisor_position, advisor_department, advisor_faculty, advisor_phone, advisor_email,
            research_type, learning_type, activities, research_field,
            rationale, objectives, importance, literature, conceptual_framework, hypothesis, methodology, references_link,
            research_start, research_end, research_schedule,
            success_indicators, publication_title, journal_name, requested_budget, budget_details,
            proposal_file_path, additional_file_path, publication_file_path, ethics_file_path, fund_support
        ) VALUES (
            :project_th, :project_en,
            :student_firstname, :student_lastname, :student_level, :student_year, :student_id, :major, :faculty, :student_phone, :student_email,
            :advisor_firstname, :advisor_lastname, :advisor_position, :advisor_department, :advisor_faculty, :advisor_phone, :advisor_email,
            :research_type, :learning_type, :activities, :research_field,
            :rationale, :objectives, :importance, :literature, :conceptual_framework, :hypothesis, :methodology, :references_link,
            :research_start, :research_end, :research_schedule,
            :success_indicators, :publication_title, :journal_name, :requested_budget, :budget_details,
            :proposal_file_path, :additional_file_path, :publication_file_path, :ethics_file_path, :fund_support
        )";

        $stmt = $pdo->prepare($sql);
        $params = [
            ':project_th'          => $_POST['project_thai_name'] ?? '',
            ':project_en'          => $_POST['project_english_name'] ?? '',
            ':student_firstname'   => $student_firstname,
            ':student_lastname'    => $student_lastname,
            ':student_level'       => $_POST['student_level'] ?? '',
            ':student_year'        => is_numeric($_POST['student_year'] ?? '') ? $_POST['student_year'] : null,
            ':student_id'          => $_POST['student_id'] ?? '',
            ':major'               => $_POST['student_major'] ?? '',
            ':faculty'             => $_POST['student_faculty'] ?? '',
            ':student_phone'       => $_POST['student_phone'] ?? '',
            ':student_email'       => $_POST['student_email'] ?? '',
            ':advisor_firstname'   => $advisor_firstname,
            ':advisor_lastname'    => $advisor_lastname,
            ':advisor_position'    => $_POST['advisor_position'] ?? '',
            ':advisor_department'  => $_POST['advisor_department'] ?? '',
            ':advisor_faculty'     => $_POST['advisor_faculty'] ?? '',
            ':advisor_phone'       => $_POST['advisor_phone'] ?? '',
            ':advisor_email'       => $_POST['advisor_email'] ?? '',
            ':research_type'       => isset($_POST['research_type']) ? implode(", ", $_POST['research_type']) : '',
            ':learning_type'       => isset($_POST['learning_type']) ? implode(", ", $_POST['learning_type']) : '',
            ':activities'          => isset($_POST['activities']) ? implode(", ", $_POST['activities']) : '',
            ':research_field'      => isset($_POST['research_field']) ? implode(", ", $_POST['research_field']) : '',
            ':rationale'           => $_POST['rationale'] ?? '',
            ':objectives'          => $_POST['objectives'] ?? '',
            ':importance'          => $_POST['importance'] ?? '',
            ':literature'          => $_POST['literature'] ?? '',
            ':conceptual_framework'=> $_POST['conceptual_framework'] ?? '',
            ':hypothesis'          => $_POST['hypothesis'] ?? '',
            ':methodology'         => $_POST['methodology'] ?? '',
            ':references_link'     => $_POST['references_link'] ?? '',
            ':research_start'      => $_POST['research_start'] ?? null,
            ':research_end'        => $_POST['research_end'] ?? null,
            ':research_schedule'   => $_POST['research_schedule'] ?? '',
            ':success_indicators'  => isset($_POST['success_indicators']) ? implode(", ", $_POST['success_indicators']) : '',
            ':publication_title'   => $_POST['publication_title'] ?? '',
            ':journal_name'        => $_POST['journal_name'] ?? '',
            ':requested_budget'    => is_numeric($_POST['requested_budget'] ?? '') ? $_POST['requested_budget'] : null,
            ':budget_details'      => $_POST['budget_details'] ?? '',
            ':proposal_file_path'  => $proposalFilePath,
            ':additional_file_path'=> $additionalFilePath,
            ':publication_file_path' => $publicationFilePath,
            ':ethics_file_path'     => $ethicsFilePath,
            ':fund_support'        => $_POST['fund_support'] ?? '',
        ];

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        if (!$stmt->execute()) {
            $errorInfo = $stmt->errorInfo();
            show_alert_and_redirect('เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . ($errorInfo[2] ?? 'ไม่ทราบข้อผิดพลาด'));
        }

        $lastId = $pdo->lastInsertId();
        if (!$lastId) {
            throw new Exception('ไม่สามารถดึง ID ล่าสุดได้');
        }

        $sqlStatus = "INSERT INTO research_requests_status 
            (original_table, original_id, project_name, submission_date, requesting_user_email, requesting_user_name, current_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmtStatus = $pdo->prepare($sqlStatus);
        $stmtStatus->execute([
            'research_proposals',
            $lastId,
            $params[':project_th'],
            date('Y-m-d H:i:s'),
            $params[':student_email'],
            $_SESSION['Username'],
            'รออนุมัติ'
        ]);

        if (!updateQuantity($_SESSION['Email'])) {
            error_log("Failed to update request count for user: " . $_SESSION['Email']);
        }

        if (!empty($_POST['fund_support'])) {
            $fundName    = $_POST['fund_support'];
            $currentYear = date('Y') + 543; // Thai Buddhist year
            $sqlFund = "INSERT INTO fund_type_selections (fund_name, selection_count, table_source, proposal_id, year) 
                        VALUES (?, 1, 'research_proposals', ?, ?)";
            $stmtFund = $pdo->prepare($sqlFund);
            $stmtFund->execute([$fundName, $lastId, $currentYear]);
        }

    } elseif ($formType === 'teacher') {
        // ===== TEACHER (research_teacher) =====
        $sql = "INSERT INTO research_teacher (
            project_thai_name, project_english_name,
            teacher_prefix_name, teacher_academic_position, teacher_department, teacher_faculty_unit, teacher_mobile_phone, teacher_email, teacher_research_proportion, teacher_expert_field,
            teacher_education_history, teacher_international_publications, co_researchers_details, student_co_researchers_details,
            research_type, msu_goals, ethics_related, ethics_certification_number, problem_significance, objectives, literature_review, methodology, research_period, operation_plan, expected_outcomes, budget_details,
            proposal_file_path, additional_file_path, publication_file_path, ethics_file_path, fund_support
        ) VALUES (
            :project_thai_name, :project_english_name,
            :teacher_prefix_name, :teacher_academic_position, :teacher_department, :teacher_faculty_unit, :teacher_mobile_phone, :teacher_email, :teacher_research_proportion, :teacher_expert_field,
            :teacher_education_history, :teacher_international_publications, :co_researchers_details, :student_co_researchers_details,
            :research_type, :msu_goals, :ethics_related, :ethics_certification_number, :problem_significance, :objectives, :literature_review, :methodology, :research_period, :operation_plan, :expected_outcomes, :budget_details,
            :proposal_file_path, :additional_file_path, :publication_file_path, :ethics_file_path, :fund_support
        )";

        $stmt = $pdo->prepare($sql);
        $params = [
            ':project_thai_name'             => $_POST['project_thai_name'] ?? '',
            ':project_english_name'          => $_POST['project_english_name'] ?? '',
            ':teacher_prefix_name'           => $_POST['teacher_prefix_name'] ?? '',
            ':teacher_academic_position'     => $_POST['teacher_academic_position'] ?? '',
            ':teacher_department'            => $_POST['teacher_department'] ?? '',
            ':teacher_faculty_unit'          => $_POST['teacher_faculty_unit'] ?? '',
            ':teacher_mobile_phone'          => $_POST['teacher_mobile_phone'] ?? '',
            ':teacher_email'                 => $_POST['teacher_email'] ?? '',
            ':teacher_research_proportion'   => is_numeric($_POST['teacher_research_proportion'] ?? '') ? $_POST['teacher_research_proportion'] : 0,
            ':teacher_expert_field'          => $_POST['teacher_expert_field'] ?? '',
            ':teacher_education_history'     => $_POST['teacher_education_history'] ?? '',
            ':teacher_international_publications' => $_POST['teacher_international_publications'] ?? '',
            ':co_researchers_details'        => $_POST['co_researchers_details'] ?? '',
            ':student_co_researchers_details'=> $_POST['student_co_researchers_details'] ?? '',
            ':research_type'                 => isset($_POST['research_type']) ? implode(", ", $_POST['research_type']) : '',
            ':msu_goals'                     => isset($_POST['msu_goals']) ? implode(", ", $_POST['msu_goals']) : '',
            ':ethics_related'                => $_POST['ethics_related'] ?? 'ไม่เกี่ยวข้อง',
            ':ethics_certification_number'   => $_POST['ethics_certification_number'] ?? '',
            ':problem_significance'          => $_POST['problem_significance'] ?? '',
            ':objectives'                    => $_POST['objectives'] ?? '',
            ':literature_review'             => $_POST['literature_review'] ?? '',
            ':methodology'                   => $_POST['methodology'] ?? '',
            ':research_period'               => $_POST['research_period'] ?? '',
            ':operation_plan'                => $_POST['operation_plan'] ?? '',
            ':expected_outcomes'             => $_POST['expected_outcomes'] ?? '',
            ':budget_details'                => $_POST['budget_details'] ?? '',
            ':proposal_file_path'            => $proposalFilePath,
            ':additional_file_path'          => $additionalFilePath,
            ':publication_file_path'         => $publicationFilePath,
            ':ethics_file_path'              => $ethicsFilePath,
            ':fund_support'                  => $_POST['fund_support'] ?? '',
        ];

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        if (!$stmt->execute()) {
            $errorInfo = $stmt->errorInfo();
            show_alert_and_redirect('เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . ($errorInfo[2] ?? 'ไม่ทราบข้อผิดพลาด'));
        }

        $lastId = $pdo->lastInsertId();
        if (!$lastId) {
            throw new Exception('ไม่สามารถดึง ID ล่าสุดได้');
        }

        $sqlStatus = "INSERT INTO research_requests_status 
            (original_table, original_id, project_name, submission_date, requesting_user_email, requesting_user_name, current_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmtStatus = $pdo->prepare($sqlStatus);
        $stmtStatus->execute([
            'research_teacher',
            $lastId,
            $params[':project_thai_name'],
            date('Y-m-d H:i:s'),
            $params[':teacher_email'],
            $_SESSION['Username'],
            'รออนุมัติ'
        ]);

        if (!updateQuantity($_SESSION['Email'])) {
            error_log("Failed to update request count for user: " . $_SESSION['Email']);
        }

        if (!empty($_POST['fund_support'])) {
            $fundName    = $_POST['fund_support'];
            $currentYear = date('Y') + 543; // Thai Buddhist year
            $sqlFund = "INSERT INTO fund_type_selections (fund_name, selection_count, table_source, proposal_id, year) 
                        VALUES (?, 1, 'research_teacher', ?, ?)";
            $stmtFund = $pdo->prepare($sqlFund);
            $stmtFund->execute([$fundName, $lastId, $currentYear]);
        }

    } elseif ($formType === 'personnel') {
        // ===== PERSONNEL (research_personnel) =====
        $sql = "INSERT INTO research_personnel (
            project_th, project_en,
            leader_firstname, leader_lastname, leader_position, leader_department, leader_phone, leader_email, leader_ratio, co_researchers,
            msu_goals, research_type, learning_research, activities, research_field,
            problem_importance, objectives, literature_review, methodology, research_schedule,
            success_indicators, budget_details,
            proposal_file_path, additional_file_path, publication_file_path, ethics_file_path, fund_support
        ) VALUES (
            :project_th, :project_en,
            :leader_firstname, :leader_lastname, :leader_position, :leader_department, :leader_phone, :leader_email, :leader_ratio, :co_researchers,
            :msu_goals, :research_type, :learning_research, :activities, :research_field,
            :problem_importance, :objectives, :literature_review, :methodology, :research_schedule,
            :success_indicators, :budget_details,
            :proposal_file_path, :additional_file_path, :publication_file_path, :ethics_file_path, :fund_support
        )";

        $stmt = $pdo->prepare($sql);
        $params = [
            ':project_th'           => $_POST['project_th'] ?? '',
            ':project_en'           => $_POST['project_en'] ?? '',
            ':leader_firstname'     => $_POST['leader_firstname'] ?? '',
            ':leader_lastname'      => $_POST['leader_lastname'] ?? '',
            ':leader_position'      => $_POST['leader_position'] ?? '',
            ':leader_department'    => $_POST['leader_department'] ?? '',
            ':leader_phone'         => $_POST['leader_phone'] ?? '',
            ':leader_email'         => $_POST['leader_email'] ?? '',
            ':leader_ratio'         => is_numeric($_POST['leader_ratio'] ?? '') ? $_POST['leader_ratio'] : null,
            ':co_researchers'       => $_POST['co_researchers'] ?? '',
            ':msu_goals'            => isset($_POST['msu_goals']) ? implode(", ", $_POST['msu_goals']) : '',
            ':research_type'        => isset($_POST['research_type']) ? implode(", ", $_POST['research_type']) : '',
            ':learning_research'    => isset($_POST['learning_research']) ? implode(", ", $_POST['learning_research']) : '',
            ':activities'           => isset($_POST['activities']) ? implode(", ", $_POST['activities']) : '',
            ':research_field'       => $_POST['research_field'] ?? '',
            ':problem_importance'   => $_POST['problem_importance'] ?? '',
            ':objectives'           => $_POST['objectives'] ?? '',
            ':literature_review'    => $_POST['literature_review'] ?? '',
            ':methodology'          => $_POST['methodology'] ?? '',
            ':research_schedule'    => $_POST['research_schedule'] ?? '',
            ':success_indicators'   => $_POST['success_indicators'] ?? '',
            ':budget_details'       => $_POST['budget_details'] ?? '',
            ':proposal_file_path'   => $proposalFilePath,
            ':additional_file_path' => $additionalFilePath,
            ':publication_file_path'=> $publicationFilePath,
            ':ethics_file_path'     => $ethicsFilePath,
            ':fund_support'         => $_POST['fund_support'] ?? '',
        ];

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        if (!$stmt->execute()) {
            $errorInfo = $stmt->errorInfo();
            show_alert_and_redirect('เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . ($errorInfo[2] ?? 'ไม่ทราบข้อผิดพลาด'));
        }

        $lastId = $pdo->lastInsertId();
        if (!$lastId) {
            throw new Exception('ไม่สามารถดึง ID ล่าสุดได้');
        }

        $sqlStatus = "INSERT INTO research_requests_status 
            (original_table, original_id, project_name, submission_date, requesting_user_email, requesting_user_name, current_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmtStatus = $pdo->prepare($sqlStatus);
        $stmtStatus->execute([
            'research_personnel',
            $lastId,
            $params[':project_th'],
            date('Y-m-d H:i:s'),
            $params[':leader_email'],
            $_SESSION['Username'],
            'รออนุมัติ'
        ]);

        if (!updateQuantity($_SESSION['Email'])) {
            error_log("Failed to update request count for user: " . $_SESSION['Email']);
        }

        if (!empty($_POST['fund_support'])) {
            $fundName    = $_POST['fund_support'];
            $currentYear = date('Y') + 543; // Thai Buddhist year
            $sqlFund = "INSERT INTO fund_type_selections (fund_name, selection_count, table_source, proposal_id, year) 
                        VALUES (?, 1, 'research_personnel', ?, ?)";
            $stmtFund = $pdo->prepare($sqlFund);
            $stmtFund->execute([$fundName, $lastId, $currentYear]);
        }
    }

    // --- Common success redirect ---
    show_alert_and_redirect('ยื่นแบบฟอร์มเรียบร้อยแล้ว!', '../index.php', 'success');

} catch (Exception $e) {
    if (!empty($proposalFilePath) && file_exists($proposalFilePath)) {
        unlink($proposalFilePath);
    }
    if (!empty($additionalFilePath) && file_exists($additionalFilePath)) {
        unlink($additionalFilePath);
    }
    error_log("Exception: " . $e->getMessage());
    show_alert_and_redirect('เกิดข้อผิดพลาด: ' . $e->getMessage());
}