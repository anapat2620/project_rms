<?php
session_start();

if (!isset($_SESSION['Email']) || !isset($_SESSION['Position'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

$request_id = isset($_GET['request_id']) ? (int)$_GET['request_id'] : 0;
if ($request_id <= 0) {
    die("รหัสคำขอไม่ถูกต้อง");
}

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
];

// --- Mapping table names to Thai ---
$table_name_thai = [
    'research_proposals' => 'ทุนวิจัยนักศึกษา',
    'research_personnel' => 'ทุนวิจัยบุคลากร',
    'research_teacher' => 'ทุนวิจัยอาจารย์',
];

// โหลดข้อมูลสถานะ (รวม fund_disbursement fields)
$sql_status = "SELECT *, 
               fund_disbursement_1st_status, fund_disbursement_1st_date, fund_disbursement_1st_amount, fund_disbursement_1st_comment, fund_disbursement_1st_proof_link,
               fund_disbursement_2nd_status, fund_disbursement_2nd_date, fund_disbursement_2nd_amount, fund_disbursement_2nd_comment, fund_disbursement_2nd_proof_link,
               fund_disbursement_3rd_status, fund_disbursement_3rd_date, fund_disbursement_3rd_amount, fund_disbursement_3rd_comment, fund_disbursement_3rd_proof_link,
               fund_disbursement_updated_by, fund_disbursement_updated_date
               FROM research_requests_status WHERE request_id = ?";
$stmt_status = $conn->prepare($sql_status);
if (!$stmt_status) {
    die("Prepare failed: " . $conn->error);
}
$stmt_status->bind_param("i", $request_id);
$stmt_status->execute();
$result_status = $stmt_status->get_result();
$status_data = $result_status->fetch_assoc();
$stmt_status->close();

$original_table_data = null;
$original_table_name = '';
$original_record_id = 0;

if ($status_data) {
    $original_table_name = $status_data['original_table'];
    $original_record_id = (int)$status_data['original_id'];
    $allowed_tables = ['research_proposals', 'research_personnel', 'research_teacher'];
    if (in_array($original_table_name, $allowed_tables) && $original_record_id > 0) {
        $pk = 'id';
        $sql_original = "SELECT * FROM `{$original_table_name}` WHERE `{$pk}` = ?";
        $stmt_original = $conn->prepare($sql_original);
        if ($stmt_original) {
            $stmt_original->bind_param("i", $original_record_id);
            $stmt_original->execute();
            $res_orig = $stmt_original->get_result();
            $original_table_data = $res_orig->fetch_assoc();
            $stmt_original->close();
        }
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

// Filter fields for status display (exclude fund_disbursement fields from main status section)
$status_display_order = ['request_id', 'original_table', 'original_id', 'project_name', 'submission_date', 
                        'requesting_user_email', 'requesting_user_name', 'current_status', 'approver_username', 
                        'action_date', 'comment'];
?>

<!DOCTYPE html>
<html lang="th" data-theme="cmyk">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>พิมพ์รายละเอียดคำขอ #<?php echo (int)$request_id; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
      :root {
        --border: #bbbbbb;
        --border-light: #dddddd;
        --text-main: #111111;
        --text-muted: #555555;
        --text-label: #333333;
        --bg-header: #2c2c2c;
        --bg-section-title: #f2f2f2;
        --bg-cell: #fafafa;
        --bg-inst-header: #eeeeee;
      }
      * { box-sizing: border-box; margin: 0; padding: 0; }
      body {
        font-family: 'Sarabun', 'TH Sarabun New', sans-serif;
        font-size: 14px;
        background: #e8e8e8;
        color: var(--text-main);
        line-height: 1.6;
      }

      /* ---- Top bar (no-print) ---- */
      .no-print-bar {
        background: var(--bg-header);
        padding: 0.7rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: sticky;
        top: 0;
        z-index: 100;
      }
      .no-print-bar span { color: #cccccc; font-size: 0.82rem; }
      .btn-print {
        background: #444444;
        color: #ffffff;
        padding: 0.4rem 1.1rem;
        border: 1px solid #888;
        font-family: 'Sarabun', sans-serif;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.4rem;
      }
      .btn-print:hover { background: #666; }
      .btn-close-print {
        background: transparent;
        color: #cccccc;
        padding: 0.4rem 1rem;
        border: 1px solid #666;
        font-family: 'Sarabun', sans-serif;
        font-size: 0.85rem;
        cursor: pointer;
      }
      .btn-close-print:hover { background: #444; }

      /* ---- Main page (A4-like) ---- */
      .print-page {
        max-width: 794px;
        margin: 1.5rem auto;
        background: white;
        border: 1px solid var(--border);
      }

      /* ---- Page header ---- */
      .print-header {
        border-bottom: 3px solid var(--bg-header);
        padding: 1.5rem 2rem 1.2rem;
        text-align: center;
      }
      .print-header h1 {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 0.25rem;
        letter-spacing: 0.01em;
      }
      .print-header p {
        font-size: 0.82rem;
        color: var(--text-muted);
      }

      /* ---- Sections ---- */
      .print-section { padding: 1.25rem 2rem; }
      .print-section + .print-section { border-top: 1px solid var(--border-light); }

      .section-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 0.85rem;
        padding: 0.35rem 0.75rem;
        background: var(--bg-section-title);
        border-left: 3px solid var(--bg-header);
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }
      /* Hide SVG icons from section titles */
      .section-title svg { display: none; }

      /* ---- Data grid ---- */
      .data-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 0;
        border-top: 1px solid var(--border-light);
        border-left: 1px solid var(--border-light);
      }
      .data-cell {
        background: white;
        padding: 0.5rem 0.75rem;
        border-right: 1px solid var(--border-light);
        border-bottom: 1px solid var(--border-light);
      }
      .data-cell:nth-child(odd) { background: var(--bg-cell); }
      .data-cell .label {
        font-size: 0.72rem;
        font-weight: 600;
        color: var(--text-label);
        margin-bottom: 0.15rem;
        text-transform: none;
        letter-spacing: 0;
      }
      .data-cell .value {
        font-size: 0.875rem;
        color: var(--text-main);
        word-break: break-word;
        line-height: 1.5;
      }

      /* ---- Status badge ---- */
      .status-chip {
        display: inline-block;
        padding: 0.1rem 0.6rem;
        font-size: 0.78rem;
        font-weight: 700;
        border: 1px solid currentColor;
      }
      .chip-approved { color: #1a6b3a; background: #e8f5ed; }
      .chip-pending  { color: #7a5a00; background: #fff8e0; }
      .chip-rejected { color: #8b1a1a; background: #fdeaea; }

      /* ---- Progress bar ---- */
      .progress-track {
        background: #dddddd;
        height: 6px;
        overflow: hidden;
        margin: 0.4rem 0;
      }
      .progress-fill {
        height: 100%;
        background: #444444;
      }

      /* ---- Installment cards ---- */
      .installment-grid {
        display: grid;
        gap: 0.75rem;
        margin-top: 0.75rem;
      }
      .installment-card-print {
        border: 1px solid var(--border);
        overflow: hidden;
      }
      .inst-header-print {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.45rem 0.85rem;
        background: var(--bg-inst-header);
        border-bottom: 1px solid var(--border);
      }
      .inst-phase-label {
        font-size: 0.88rem;
        font-weight: 700;
        color: var(--text-main);
      }
      .inst-badge {
        font-size: 0.72rem;
        font-weight: 700;
        padding: 0.1rem 0.55rem;
        border: 1px solid currentColor;
      }
      .inst-badge-paid    { color: #1a6b3a; background: #e8f5ed; }
      .inst-badge-pending { color: #7a5a00; background: #fff8e0; }
      .inst-badge-no      { color: #8b1a1a; background: #fdeaea; }
      .inst-body {
        padding: 0.6rem 0.85rem;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.3rem 1rem;
        font-size: 0.82rem;
      }
      .inst-row { display: flex; flex-direction: column; gap: 0.05rem; }
      .inst-label { color: var(--text-muted); font-size: 0.7rem; font-weight: 600; }
      .inst-value { color: var(--text-main); font-weight: 500; }
      .inst-value-amount { color: #1a6b3a; font-weight: 700; }

      /* ---- Footer ---- */
      .print-footer {
        padding: 0.65rem 2rem;
        background: #f2f2f2;
        border-top: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.72rem;
        color: var(--text-muted);
      }

      /* ---- Print media ---- */
      @media print {
        .no-print { display: none !important; }
        body { background: white; font-size: 12pt; }
        .print-page { margin: 0; border: none; }
        .no-print-bar { display: none !important; }
        a { text-decoration: none; color: inherit; }
        .print-section { padding: 0.9rem 1.5rem; }
        .data-grid { grid-template-columns: repeat(3, 1fr); }
        .installment-grid { grid-template-columns: repeat(3, 1fr); }
        @page { size: A4; margin: 1.2cm 1.5cm; }
      }

      @media (max-width: 640px) {
        .print-section { padding: 1rem; }
        .print-header { padding: 1rem; }
        .data-grid { grid-template-columns: 1fr; }
        .inst-body { grid-template-columns: 1fr; }
      }
    </style>
</head>
<body>
  <!-- Top bar (no-print) -->
  <div class="no-print-bar no-print">
    <span>เอกสารสำหรับบันทึกเป็น PDF — คำขอ #<?php echo (int)$request_id; ?></span>
    <div style="display:flex;gap:0.5rem;">
      <button class="btn-close-print" onclick="window.close()">ปิด</button>
      <button class="btn-print" onclick="window.print()">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        พิมพ์ / บันทึก PDF
      </button>
    </div>
  </div>

  <div class="print-page">
    <!-- Header -->
    <div class="print-header">
      <h1>ข้อมูลที่เกี่ยวข้องกับคำร้องขอ</h1>
      <p>รหัสคำขอ: <?php echo (int)$request_id; ?> &nbsp;·&nbsp; วันที่พิมพ์: <?php echo date('d/m/Y H:i'); ?></p>
    </div>

    <!-- Status Section -->
    <?php if ($status_data): ?>
      <div class="print-section">
        <h2 class="section-title">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          ข้อมูลสถานะคำขอ
        </h2>
        <div class="data-grid">
          <?php foreach ($status_display_order as $key):
              if (!isset($status_data[$key]) || $status_data[$key] === '' || $status_data[$key] === null) continue;
          ?>
            <div class="data-cell">
              <div class="label"><?= htmlspecialchars($field_labels[$key] ?? $key) ?></div>
              <div class="value">
                <?php
                if ($key === 'original_table' && isset($table_name_thai[$status_data[$key]])) {
                    echo htmlspecialchars($table_name_thai[$status_data[$key]]);
                } elseif ($key === 'current_status') {
                    $chipClass = $status_data[$key] === 'อนุมัติ' ? 'chip-approved' : ($status_data[$key] === 'ปฏิเสธ' ? 'chip-rejected' : 'chip-pending');
                    echo '<span class="status-chip '.$chipClass.'">'.htmlspecialchars($status_data[$key]).'</span>';
                } else {
                    echo nl2br(htmlspecialchars((string)$status_data[$key]));
                }
                ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php else: ?>
      <div class="print-section">
        <p style="color:#7a5a00;font-weight:600;">ไม่พบข้อมูลสถานะคำขอ</p>
      </div>
    <?php endif; ?>

    <!-- Original Data Section -->
    <?php if ($original_table_data): ?>
      <div class="print-section">
        <h2 class="section-title">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          รายละเอียดคำร้องขอ
          <?php if (isset($table_name_thai[$original_table_name])): ?>
            <span style="font-family:'Sarabun',sans-serif;font-weight:400;font-size:0.8rem;color:#555555;">(<?= htmlspecialchars($table_name_thai[$original_table_name]) ?>)</span>
          <?php endif; ?>
        </h2>
        <div class="data-grid">
          <?php foreach ($original_table_data as $k => $v):
              if (!isset($v) || $v === '' || $v === null) continue;
          ?>
            <div class="data-cell">
              <div class="label"><?= htmlspecialchars($field_labels[$k] ?? $k) ?></div>
              <div class="value"><?= nl2br(htmlspecialchars((string)$v)) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Fund Disbursement Section -->
    <?php if ($status_data && $status_data['current_status'] === 'อนุมัติ'):
        $active_installments = [];
        if ($bh1 > 0) $active_installments[] = '1st';
        if ($bh2 > 0) $active_installments[] = '2nd';
        if ($b3 > 0) $active_installments[] = '3rd';
        $total_installments = count($active_installments);
        $paid_installments = 0;
        $total_amount = 0;
        foreach ($active_installments as $phase) {
            $s = $status_data["fund_disbursement_{$phase}_status"] ?? '';
            $a = $status_data["fund_disbursement_{$phase}_amount"] ?? 0;
            if ($s === 'จ่ายแล้ว') { $paid_installments++; $total_amount += (float)$a; }
        }
        $progress_percentage = $total_installments > 0 ? ($paid_installments / $total_installments) * 100 : 0;
    ?>
      <div class="print-section">
        <h2 class="section-title">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>
          สถานะการจ่ายเงิน
        </h2>

        <!-- Progress -->
        <div style="background:#f2f2f2;border:1px solid #bbbbbb;padding:0.75rem 1rem;margin-bottom:1rem;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.4rem;">
            <span style="font-size:0.85rem;font-weight:600;color:#111111;">ความคืบหน้าการจ่ายเงิน</span>
            <span style="font-size:0.85rem;font-weight:600;color:#333333;"><?= $paid_installments ?>/<?= $total_installments ?> งวด</span>
          </div>
          <div class="progress-track">
            <div class="progress-fill" style="width:<?= $progress_percentage ?>%"></div>
          </div>
          <?php if ($total_amount > 0): ?>
            <div style="text-align:right;font-size:0.78rem;color:#1a6b3a;margin-top:0.3rem;font-weight:700;">รวมจ่ายแล้ว ฿<?= number_format($total_amount, 2) ?></div>
          <?php endif; ?>
        </div>

        <?php if (!empty($active_installments)): ?>
          <div class="installment-grid" style="grid-template-columns:repeat(<?= min(count($active_installments), 3) ?>, 1fr);">
            <?php foreach ($active_installments as $installment):
                $phase_num = $installment === '1st' ? '1' : ($installment === '2nd' ? '2' : '3');
                $st = $status_data["fund_disbursement_{$installment}_status"] ?? 'รอการจ่าย';
                $am = $status_data["fund_disbursement_{$installment}_amount"] ?? '';
                $co = $status_data["fund_disbursement_{$installment}_comment"] ?? '';
                $dt = $status_data["fund_disbursement_{$installment}_date"] ?? '';
                $pl = $status_data["fund_disbursement_{$installment}_proof_link"] ?? '';
                $badgeClass = $st === 'จ่ายแล้ว' ? 'inst-badge-paid' : ($st === 'ไม่จ่าย' ? 'inst-badge-no' : 'inst-badge-pending');
            ?>
              <div class="installment-card-print">
                <div class="inst-header-print">
                  <span class="inst-phase-label">งวดที่ <?= $phase_num ?></span>
                  <span class="inst-badge <?= $badgeClass ?>"><?= htmlspecialchars($st) ?></span>
                </div>
                <div class="inst-body">
                  <div class="inst-row">
                    <span class="inst-label">จำนวนเงิน</span>
                    <span class="inst-value <?= $am ? 'inst-value-amount' : '' ?>"><?= $am ? '฿'.number_format($am, 2) : '-' ?></span>
                  </div>
                  <div class="inst-row">
                    <span class="inst-label">วันที่จ่าย</span>
                    <span class="inst-value"><?= $dt ? date('d/m/Y H:i', strtotime($dt)) : '-' ?></span>
                  </div>
                  <?php if ($co): ?>
                  <div class="inst-row" style="grid-column:1/-1;">
                    <span class="inst-label">หมายเหตุ</span>
                    <span class="inst-value"><?= htmlspecialchars($co) ?></span>
                  </div>
                  <?php endif; ?>
                  <?php if ($pl): ?>
                  <div class="inst-row" style="grid-column:1/-1;">
                    <span class="inst-label">ลิงก์หลักฐาน</span>
                    <a href="<?= htmlspecialchars($pl) ?>" style="color:#1a4a8a;font-size:0.78rem;word-break:break-all;"><?= htmlspecialchars($pl) ?></a>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p style="color:#7a5a00;font-size:0.875rem;text-align:center;padding:0.75rem;">ไม่มีการกำหนดงวดการจ่ายเงินสำหรับทุนประเภทนี้</p>
        <?php endif; ?>

        <?php if ($status_data['fund_disbursement_updated_by'] || $status_data['fund_disbursement_updated_date']): ?>
          <div style="margin-top:0.75rem;padding:0.5rem 0.75rem;background:#f2f2f2;border:1px solid #cccccc;font-size:0.78rem;color:#555555;display:flex;gap:1.5rem;flex-wrap:wrap;">
            <?php if ($status_data['fund_disbursement_updated_by']): ?>
              <span><strong>ผู้อัปเดต:</strong> <?= htmlspecialchars($status_data['fund_disbursement_updated_by']) ?></span>
            <?php endif; ?>
            <?php if ($status_data['fund_disbursement_updated_date']): ?>
              <span><strong>อัปเดตล่าสุด:</strong> <?= date('d/m/Y H:i', strtotime($status_data['fund_disbursement_updated_date'])) ?></span>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="print-footer">
      <span>ระบบบริหารจัดการทุนวิจัย</span>
      <span>พิมพ์เมื่อ: <?= date('d/m/Y H:i:s') ?></span>
    </div>
  </div><!-- end .print-page -->

  <script>
    window.addEventListener('load', function(){
      setTimeout(() => window.print(), 400);
    });
  </script>
</body>
</html>