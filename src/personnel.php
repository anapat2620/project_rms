<?php
session_start();
// --- แสดง toast จาก session (เช่น error จาก db_connect.php) ---
$_toast_message = '';
$_toast_status  = '';
if (!empty($_SESSION['toast_message'])) {
    $_toast_message = $_SESSION['toast_message'];
    $_toast_status  = $_SESSION['toast_status'] ?? 'error';
    unset($_SESSION['toast_message'], $_SESSION['toast_status']);
}

require_once 'check_limit/check_request_limits.php';

if (!isset($_SESSION['Email']) || !isset($_SESSION['Position'])) {
    header("Location: login.php");
    exit();
}

// ตรวจสอบสิทธิ์การยื่นขอทุน (ใช้ logic เดิม)
$checkType = checkRequestType($_SESSION['Position'], 'personnel');
if (!$checkType['can_submit']) {
    $_SESSION['toast_message'] = $checkType['message'];
    $_SESSION['toast_status']  = 'warning';
    header("Location: index.php");
    exit();
}

// ดึงข้อมูล fund_support จากฐานข้อมูล
$fund_support_options = [];
try {
    require_once __DIR__ . '/config.php';
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn->connect_error) {
        $conn->set_charset("utf8mb4");
        $fund_sql = "SELECT FunID, FunName FROM fund_support ORDER BY FunID";
        $fund_result = $conn->query($fund_sql);
        if ($fund_result && $fund_result->num_rows > 0) {
            while ($row = $fund_result->fetch_assoc()) {
                $fund_support_options[] = $row;
            }
        }
        $conn->close();
    }
} catch (Exception $e) {
    // Silently fail - dropdown will be empty
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/personnel.css">
    <title>แบบฟอร์มขอรับทุนอุดหนุนและส่งเสริมการวิจัย (บุคลากร)</title>
</head>
<body>
    <div class="max-w-5xl mx-auto bg-white p-8 rounded-xl shadow-lg">
        <h2 class="text-3xl font-bold text-center mb-2">
            แบบเสนอขอรับทุนสนับสนุนการวิจัยเพื่อพัฒนาองค์กรและพัฒนาบุคลากร
        </h2>
        <p class="text-center text-gray-600 mb-4">
            สายสนับสนุน ประจำปีงบประมาณ พ.ศ. 2568
        </p>
        <div class="flex items-center justify-between mb-6">
            <div class="step active text-blue-600 font-semibold">1. ข้อมูลทั่วไป</div>
            <div class="w-1/7 h-1 bg-gray-300 rounded-full"></div>
            <div class="step text-gray-500">2. หัวหน้าโครงการ</div>
            <div class="w-1/7 h-1 bg-gray-300 rounded-full"></div>
            <div class="step text-gray-500">3. เป้าหมาย (MSU Goals)</div>
            <div class="w-1/7 h-1 bg-gray-300 rounded-full"></div>
            <div class="step text-gray-500">4. ประเภทงานวิจัย</div>
            <div class="w-1/7 h-1 bg-gray-300 rounded-full"></div>
            <div class="step text-gray-500">5. วิจัยและแผนดำเนินงาน</div>
            <div class="w-1/7 h-1 bg-gray-300 rounded-full"></div>
            <div class="step text-gray-500">6. เป้าหมาย & งบประมาณ</div>
        </div>
        <div class="w-full bg-gray-200 h-2 rounded-lg mb-6">
            <div id="progress-bar" class="bg-blue-500 h-2 rounded-lg" style="width: 16.6%;"></div>
        </div>
        <!-- ใช้ไฟล์เชื่อมต่อฐานข้อมูลรวม db_connect.php -->
        <form id="multi-step-form" class="space-y-8" action="db_connect/db_connect.php?type=personnel" method="POST" enctype="multipart/form-data">
            <div class="form-step active">
                <h3 class="text-xl font-bold mb-4">1. ข้อมูลทั่วไป</h3>
                <div class="mb-4">
                    <label for="project_th" class="block text-gray-700 font-medium">ชื่อโครงการ (ภาษาไทย) <span class="text-red-500">*</span></label>
                    <input type="text" name="project_th" id="project_th" placeholder="ชื่อโครงการ (ภาษาไทย)" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                    <p class="error-message hidden" id="project_th-error">กรุณากรอกชื่อโครงการ (ภาษาไทย)</p>
                </div>
                <div class="mb-4">
                    <label for="project_en" class="block text-gray-700 font-medium">ชื่อโครงการ (ภาษาอังกฤษ) <span class="text-red-500">*</span></label>
                    <input type="text" name="project_en" id="project_en" placeholder="ชื่อโครงการ (ภาษาอังกฤษ)" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                    <p class="error-message hidden" id="project_en-error">กรุณากรอกชื่อโครงการ (ภาษาอังกฤษ)</p>
                </div>
                <div class="form-control w-full max-w-lg mb-4">
                    <label for="proposal_file" class="label">
                        <span class="label-text">ไฟล์เอกสารข้อเสนอโครงการวิจัย (PDF)</span>
                        <span class="label-text-alt text-red-500">*จำเป็น</span>
                    </label>
                    <input type="file" name="proposal_file" id="proposal_file" accept=".pdf" required
                        class="file-input file-input-bordered w-full max-w-lg" />
                    <p class="error-message hidden" id="proposal_file-error">กรุณาแนบไฟล์ข้อเสนอโครงการวิจัย (เฉพาะไฟล์ PDF)</p>
                </div>
                <div class="form-control w-full max-w-lg mb-4">
                    <label for="additional_file" class="label">
                        <span class="label-text">ไฟล์เอกสารประกอบเพิ่มเติม (PDF)</span>
                        <span class="label-text-alt">ถ้ามี</span>
                    </label>
                    <input type="file" name="additional_file" id="additional_file" accept=".pdf"
                        class="file-input file-input-bordered w-full max-w-lg" />
                    <p class="error-message hidden" id="additional_file-error">กรุณาแนบเฉพาะไฟล์ PDF</p>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="fund_support">ประเภททุนสนับสนุน <span class="text-red-500">*</span></label>
                    <select id="fund_support" name="fund_support" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                        <option value="">เลือกประเภททุนสนับสนุน</option>
                        <?php foreach ($fund_support_options as $fund): ?>
                            <option value="<?php echo htmlspecialchars($fund['FunName']); ?>"><?php echo htmlspecialchars($fund['FunName']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="error-message hidden" id="fund_support-error">กรุณาเลือกประเภททุนสนับสนุน</p>
                </div>
                <div class="flex justify-end">
                    <button type="button" class="next-step bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-all">ถัดไป</button>
                </div>
            </div>

            <div class="form-step hidden">
                <h3 class="text-xl font-bold mb-4">2. หัวหน้าโครงการวิจัย</h3>
                <h4 class="text-lg font-semibold text-gray-800 mb-2">2.1 ผู้รับทุน (หัวหน้าโครงการวิจัย)</h4>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="leader_firstname" class="block text-gray-700 font-medium">ชื่อ (นาย/นาง/นางสาว) <span class="text-red-500">*</span></label>
                        <input type="text" name="leader_firstname" id="leader_firstname" placeholder="ชื่อ" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                        <p class="error-message hidden" id="leader_firstname-error">กรุณากรอกชื่อ</p>
                    </div>
                    <div>
                        <label for="leader_lastname" class="block text-gray-700 font-medium">นามสกุล <span class="text-red-500">*</span></label>
                        <input type="text" name="leader_lastname" id="leader_lastname" placeholder="นามสกุล" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                        <p class="error-message hidden" id="leader_lastname-error">กรุณากรอกนามสกุล</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="leader_position" class="block text-gray-700 font-medium">ตำแหน่ง <span class="text-red-500">*</span></label>
                        <input type="text" name="leader_position" id="leader_position" placeholder="ตำแหน่ง" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                        <p class="error-message hidden" id="leader_position-error">กรุณากรอกตำแหน่ง</p>
                    </div>
                    <div>
                        <label for="leader_department" class="block text-gray-700 font-medium">ฝ่ายงาน/กลุ่มงาน <span class="text-red-500">*</span></label>
                        <input type="text" name="leader_department" id="leader_department" placeholder="ฝ่ายงาน/กลุ่มงาน" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                        <p class="error-message hidden" id="leader_department-error">กรุณากรอกฝ่ายงาน/กลุ่มงาน</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="leader_phone" class="block text-gray-700 font-medium">โทรศัพท์มือถือ <span class="text-red-500">*</span></label>
                        <input type="text" name="leader_phone" id="leader_phone" placeholder="โทรศัพท์มือถือ" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                        <p class="error-message hidden" id="leader_phone-error">กรุณากรอกเบอร์โทรศัพท์</p>
                    </div>
                    <div>
                        <label for="leader_email" class="block text-gray-700 font-medium">E-mail <span class="text-red-500">*</span></label>
                        <input type="email" name="leader_email" id="leader_email" placeholder="E-mail" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                        <p class="error-message hidden" id="leader_email-error">กรุณากรอกอีเมลที่ถูกต้อง</p>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="leader_ratio" class="block text-gray-700 font-medium">สัดส่วนการทำงานวิจัย (%)</label>
                    <input type="number" name="leader_ratio" id="leader_ratio" placeholder="%" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <h4 class="text-lg font-semibold text-gray-800 mb-2">2.2 ผู้ร่วมวิจัย (ถ้ามี)</h4>
                <textarea name="co_researchers" id="co_researchers" rows="2" placeholder="ระบุชื่อ, ตำแหน่ง, สังกัด, โทรศัพท์, E-mail, สัดส่วน ฯลฯ" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 mb-4"></textarea>
                <div class="flex justify-between mt-6">
                    <button type="button" class="prev-step bg-gray-400 text-white px-6 py-3 rounded-lg hover:bg-gray-500 transition-all">ย้อนกลับ</button>
                    <button type="button" class="next-step bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-all">ถัดไป</button>
                </div>
            </div>

            <div class="form-step hidden">
                <h3 class="text-xl font-bold mb-4">3. เป้าหมาย (MSU Goals)</h3>
                <div class="mb-6">
                    <p class="text-lg font-semibold text-gray-800 mb-2">เลือกเป้าหมายประเด็นการวิจัย</p>
                    <div class="space-y-2">
                        <label for="msu_goals_1" class="inline-flex items-center">
                            <input type="checkbox" name="msu_goals[]" value="Goals 1" id="msu_goals_1" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">Goals 1</span>
                        </label>
                        <label for="msu_goals_2" class="inline-flex items-center">
                            <input type="checkbox" name="msu_goals[]" value="Goals 2" id="msu_goals_2" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">Goals 2</span>
                        </label>
                        <label for="msu_goals_3" class="inline-flex items-center">
                            <input type="checkbox" name="msu_goals[]" value="Goals 3" id="msu_goals_3" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">Goals 3</span>
                        </label>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mb-4">(หากมีรายละเอียดเพิ่มเติม โปรดระบุในขั้นตอนถัดไป)</p>
                <div class="flex justify-between mt-6">
                    <button type="button" class="prev-step bg-gray-400 text-white px-6 py-3 rounded-lg hover:bg-gray-500 transition-all">ย้อนกลับ</button>
                    <button type="button" class="next-step bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-all">ถัดไป</button>
                </div>
            </div>

            <div class="form-step hidden">
                <h3 class="text-xl font-bold mb-4">4. ประเภทของงานวิจัย</h3>
                <div class="mb-6">
                    <p class="text-lg font-semibold text-gray-800 mb-2">ประเภทของงานวิจัย</p>
                    <div class="space-y-2">
                        <label for="research_type_fundamental" class="inline-flex items-center">
                            <input type="checkbox" name="research_type[]" value="พื้นฐาน" id="research_type_fundamental" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">การวิจัยพื้นฐาน</span>
                        </label>
                        <label for="research_type_applied" class="inline-flex items-center">
                            <input type="checkbox" name="research_type[]" value="ประยุกต์" id="research_type_applied" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">การวิจัยประยุกต์</span>
                        </label>
                        <label for="research_type_experimental" class="inline-flex items-center">
                            <input type="checkbox" name="research_type[]" value="ทดลอง" id="research_type_experimental" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">การพัฒนาทดลอง</span>
                        </label>
                    </div>
                </div>
                <div class="mb-6">
                    <p class="text-lg font-semibold text-gray-800 mb-2">ประเภทงานวิจัยเพื่อพัฒนาการเรียนรู้</p>
                    <div class="space-y-2">
                        <label for="learning_research_curriculum" class="inline-flex items-center">
                            <input type="checkbox" name="learning_research[]" value="หลักสูตร" id="learning_research_curriculum" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">เพื่อพัฒนาหลักสูตร</span>
                        </label>
                        <label for="learning_research_practice" class="inline-flex items-center">
                            <input type="checkbox" name="learning_research[]" value="ฝึกปฏิบัติ" id="learning_research_practice" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">เพื่อพัฒนาการฝึกปฏิบัติงาน</span>
                        </label>
                        <label for="learning_research_management" class="inline-flex items-center">
                            <input type="checkbox" name="learning_research[]" value="จัดการเรียน" id="learning_research_management" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">เพื่อพัฒนาแนวทางการจัดการเรียนการสอน</span>
                        </label>
                        <label for="learning_research_experience" class="inline-flex items-center">
                            <input type="checkbox" name="learning_research[]" value="ประสบการณ์" id="learning_research_experience" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">เพื่อสร้างประสบการณ์จริง</span>
                        </label>
                        <label for="learning_research_media" class="inline-flex items-center">
                            <input type="checkbox" name="learning_research[]" value="สื่อการสอน" id="learning_research_media" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">เพื่อพัฒนาสื่อการสอน</span>
                        </label>
                    </div>
                </div>
                <div class="mb-6">
                    <p class="text-lg font-semibold text-gray-800 mb-2">เกี่ยวข้องกับกิจกรรม</p>
                    <div class="grid grid-cols-2 gap-4">
                        <label for="activities_student" class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="นักศึกษา" id="activities_student" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมการพัฒนานักศึกษา</span>
                        </label>
                        <label for="activities_culture" class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="ศิลปวัฒนธรรม" id="activities_culture" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมบำรุงศิลปวัฒนธรรม</span>
                        </label>
                        <label for="activities_sports" class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="กีฬา/สุขภาพ" id="activities_sports" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมกีฬาและสุขภาพอนามัย</span>
                        </label>
                        <label for="activities_quality" class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="ประกันคุณภาพ" id="activities_quality" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมประกันคุณภาพ</span>
                        </label>
                        <label for="activities_finance" class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="การเงิน" id="activities_finance" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมด้านการเงินและงบประมาณ</span>
                        </label>
                        <label for="activities_planning" class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="วางแผน" id="activities_planning" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมวางแผนและพัฒนา</span>
                        </label>
                        <label for="activities_management" class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="บริหาร" id="activities_management" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมด้านบริหารจัดการ</span>
                        </label>
                        <label for="activities_academic" class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="วิชาการ" id="activities_academic" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมวิชาการ</span>
                        </label>
                        <label for="activities_research" class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="วิจัย" id="activities_research" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมด้านการวิจัย</span>
                        </label>
                        <label for="activities_conservation" class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="อนุรักษ์" id="activities_conservation" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมอนุรักษ์และพัฒนาสิ่งแวดล้อม</span>
                        </label>
                        <label for="activities_general" class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="ทั่วไป" id="activities_general" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมทั่วไป</span>
                        </label>
                        <label for="activities_volunteer" class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="อาสา" id="activities_volunteer" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมอาสาพัฒนาชนบท</span>
                        </label>
                        <label for="activities_service" class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="บริการ" id="activities_service" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมบริการวิชาการสู่สังคมและชุมชน</span>
                        </label>
                        <label for="activities_training" class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="อบรม" id="activities_training" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมอบรมเพื่อพัฒนาตนเอง</span>
                        </label>
                        <label for="activities_virtue" class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="คุณธรรม" id="activities_virtue" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมบำเพ็ญประโยชน์ คุณธรรม จริยธรรม</span>
                        </label>
                        <label for="activities_philosophy" class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="ปรัชญา" id="activities_philosophy" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">ปรัชญา วิสัยทัศน์</span>
                        </label>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="research_field" class="block text-gray-700 font-medium">สาขา/กลุ่มวิชาการวิจัย</label>
                    <input type="text" name="research_field" id="research_field" placeholder="ระบุสาขา/กลุ่มวิชาการวิจัย" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex justify-between mt-6">
                    <button type="button" class="prev-step bg-gray-400 text-white px-6 py-3 rounded-lg hover:bg-gray-500 transition-all">ย้อนกลับ</button>
                    <button type="button" class="next-step bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-all">ถัดไป</button>
                </div>
            </div>

            <div class="form-step hidden">
                <h3 class="text-xl font-bold mb-4">5. วิจัยและแผนดำเนินงาน</h3>
                <div class="mb-4">
                    <label for="problem_importance" class="block text-gray-700 font-medium">5.1 ความสำคัญของปัญหาที่ทำการวิจัย <span class="text-red-500">*</span></label>
                    <textarea name="problem_importance" id="problem_importance" rows="4" placeholder="ระบุความสำคัญของปัญหา" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required></textarea>
                    <p class="error-message hidden" id="problem_importance-error">กรุณากรอกความสำคัญของปัญหาที่ทำการวิจัย</p>
                </div>
                <div class="mb-4">
                    <label for="objectives" class="block text-gray-700 font-medium">5.2 วัตถุประสงค์ <span class="text-red-500">*</span></label>
                    <textarea name="objectives" id="objectives" rows="4" placeholder="ระบุวัตถุประสงค์" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required></textarea>
                    <p class="error-message hidden" id="objectives-error">กรุณากรอกวัตถุประสงค์</p>
                </div>
                <div class="mb-4">
                    <label for="literature_review" class="block text-gray-700 font-medium">5.3 การทบทวนวรรณกรรมที่เกี่ยวข้อง <span class="text-red-500">*</span></label>
                    <textarea name="literature_review" id="literature_review" rows="4" placeholder="ระบุการทบทวนวรรณกรรม" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required></textarea>
                    <p class="error-message hidden" id="literature_review-error">กรุณากรอกการทบทวนวรรณกรรมที่เกี่ยวข้อง</p>
                </div>
                <div class="mb-4">
                    <label for="methodology" class="block text-gray-700 font-medium">5.4 ระเบียบวิธีวิจัย <span class="text-red-500">*</span></label>
                    <textarea name="methodology" id="methodology" rows="4" placeholder="ระบุระเบียบวิธีวิจัย" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required></textarea>
                    <p class="error-message hidden" id="methodology-error">กรุณากรอกระเบียบวิธีวิจัย</p>
                </div>
                <div class="mb-4">
                    <label for="research_schedule" class="block text-gray-700 font-medium">5.5 แผนการดำเนินงานวิจัย <span class="text-red-500">*</span></label>
                    <textarea name="research_schedule" id="research_schedule" rows="4" placeholder="ระบุแผนการดำเนินงานวิจัย (เช่น ระยะเวลา, ขั้นตอน)" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required></textarea>
                    <p class="error-message hidden" id="research_schedule-error">กรุณากรอกแผนการดำเนินงานวิจัย</p>
                </div>
                <div class="flex justify-between mt-6">
                    <button type="button" class="prev-step bg-gray-400 text-white px-6 py-3 rounded-lg hover:bg-gray-500 transition-all">ย้อนกลับ</button>
                    <button type="button" class="next-step bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-all">ถัดไป</button>
                </div>
            </div>
            <div class="form-step hidden">
                <h3 class="text-xl font-bold mb-4">6. เป้าหมาย & งบประมาณ</h3>
                <div class="mb-4">
                    <label for="success_indicators" class="block text-gray-700 font-medium">6.1 ตัวชี้วัดความสำเร็จของงานวิจัย <span class="text-red-500">*</span></label>
                    <textarea name="success_indicators" id="success_indicators" rows="4" placeholder="ระบุตัวชี้วัดความสำเร็จ (เช่น ผลผลิต, ผลลัพธ์, ผลกระทบ)" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required></textarea>
                    <p class="error-message hidden" id="success_indicators-error">กรุณากรอกตัวชี้วัดความสำเร็จของงานวิจัย</p>
                </div>
                <div class="mb-4">
                    <label for="budget_details" class="block text-gray-700 font-medium">6.2 รายละเอียดงบประมาณ <span class="text-red-500">*</span></label>
                    <textarea name="budget_details" id="budget_details" rows="4" placeholder="ระบุรายละเอียดงบประมาณที่ขอรับ (เช่น หมวดค่าตอบแทน, ค่าใช้สอย, ค่าวัสดุ, ค่าครุภัณฑ์)" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required></textarea>
                    <p class="error-message hidden" id="budget_details-error">กรุณากรอกรายละเอียดงบประมาณ</p>
                </div>
                <div class="flex justify-between mt-6">
                    <button type="button" class="prev-step bg-gray-400 text-white px-6 py-3 rounded-lg hover:bg-gray-500 transition-all">ย้อนกลับ</button>
                    <button type="submit" id="submit-form" class="bg-green-500 text-white px-6 py-3 rounded-lg hover:bg-green-600 transition-all">ส่งแบบฟอร์ม</button>
                </div>
            </div>
            </form>
        </div>
    <script src="assets/personnel.js"></script>
    <script>
      window.initPersonnelMultiStep && window.initPersonnelMultiStep();
    </script>
    <!-- Toast Notification -->
    <?php if (!empty($_toast_message)): ?>
    <div id="toast-notify" style="
        position:fixed; top:1.25rem; right:1.25rem; z-index:9999;
        display:flex; align-items:flex-start; gap:0.75rem;
        background:#fff; border-radius:12px;
        box-shadow:0 8px 32px rgba(0,0,0,0.14);
        padding:1rem 1.25rem; min-width:280px; max-width:420px;
        border-left:5px solid <?php echo $_toast_status==='success'?'#22c55e':($_toast_status==='warning'?'#f59e0b':'#ef4444'); ?>;
        animation:toastIn .35s cubic-bezier(.4,0,.2,1);
    ">
        <span style="font-size:1.3rem;line-height:1;">
            <?php if ($_toast_status === 'success'): ?>✅
            <?php elseif ($_toast_status === 'warning'): ?>⚠️
            <?php else: ?>❌<?php endif; ?>
        </span>
        <div style="flex:1;">
            <div style="font-weight:700;font-size:0.85rem;color:#374151;margin-bottom:0.2rem;">
                <?php if ($_toast_status === 'success'): ?>สำเร็จ
                <?php elseif ($_toast_status === 'warning'): ?>คำเตือน
                <?php else: ?>เกิดข้อผิดพลาด<?php endif; ?>
            </div>
            <div style="font-size:0.88rem;color:#4b5563;line-height:1.5;">
                <?php echo htmlspecialchars($_toast_message); ?>
            </div>
        </div>
        <button onclick="document.getElementById('toast-notify').remove()"
            style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:1.1rem;line-height:1;padding:0;margin-left:0.25rem;">✕</button>
    </div>
    <style>
        @keyframes toastIn {
            from { opacity:0; transform:translateX(40px); }
            to   { opacity:1; transform:translateX(0); }
        }
    </style>
    <script>
        // ปิด toast อัตโนมัติหลัง 5 วินาที
        setTimeout(function(){
            var t = document.getElementById('toast-notify');
            if (t) {
                t.style.transition = 'opacity .4s';
                t.style.opacity = '0';
                setTimeout(function(){ if(t) t.remove(); }, 400);
            }
        }, 5000);
    </script>
    <?php endif; ?>
</body>
</html>