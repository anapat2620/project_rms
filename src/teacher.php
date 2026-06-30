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

// Check if user is logged in
if (!isset($_SESSION['Email']) || !isset($_SESSION['Position'])) {
    header("Location: login.php");
    exit();
}

// Check position restrictions
$checkType = checkRequestType($_SESSION['Position'], 'teacher');
if (!$checkType['can_submit']) {
    echo "<script>
        alert('" . $checkType['message'] . "');
        window.location.href = 'index.php';
    </script>";
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
    <link rel="stylesheet" href="assets/teacher.css">
    <title>แบบฟอร์มขอรับทุนอุดหนุนและส่งเสริมการวิจัย (อาจารย์)</title>
</head>
<body>

    <div class="max-w-5xl mx-auto bg-white p-8 rounded-xl shadow-lg my-8">

        <h2 class="text-3xl font-bold text-center mb-2">
            แบบเสนอขอรับทุนอุดหนุนและส่งเสริมการวิจัยของอาจารย์
        </h2>
        <p class="text-center text-gray-600 mb-4">
            ประเภทส่งเสริมการตีพิมพ์ในวารสารระดับนานาชาติ (Fast Track) ประจำปีงบประมาณ พ.ศ. 2568
        </p>

        <div class="flex items-center justify-between mb-6 text-sm md:text-base">
            <div class="step active text-blue-600 font-semibold text-center" aria-current="step">1. ข้อมูลทั่วไป</div>
            <div class="w-1/6 h-1 bg-gray-300 rounded-full mx-1"></div>
            <div class="step text-gray-500 text-center">2. หัวหน้าโครงการ</div>
            <div class="w-1/6 h-1 bg-gray-300 rounded-full mx-1"></div>
            <div class="step text-gray-500 text-center">3. ประเภท & Goals</div>
            <div class="w-1/6 h-1 bg-gray-300 rounded-full mx-1"></div>
            <div class="step text-gray-500 text-center">4. จริยธรรม & ความสำคัญ</div>
            <div class="w-1/6 h-1 bg-gray-300 rounded-full mx-1"></div>
            <div class="step text-gray-500 text-center">5. ทบทวน & ขั้นตอน</div>
            <div class="w-1/6 h-1 bg-gray-300 rounded-full mx-1"></div>
            <div class="step text-gray-500 text-center">6. เป้าหมาย & งบประมาณ</div>
        </div>

        <div class="w-full bg-gray-200 h-2 rounded-lg mb-6">
            <div id="progress-bar" class="bg-blue-500 h-2 rounded-lg" style="width: 16.6%;"></div>
        </div>

        <form id="multi-step-form" class="space-y-8" action="db_connect/db_connect.php?type=teacher" method="POST" enctype="multipart/form-data" novalidate>

            <div class="form-step active">
                <h3 class="text-xl font-bold mb-4">1. ข้อมูลทั่วไป</h3>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="project_thai_name">ชื่อโครงการวิจัย (ภาษาไทย) <span class="text-red-500">*</span></label>
                    <input
                        type="text"
                        id="project_thai_name"
                        name="project_thai_name"
                        placeholder="ชื่อโครงการวิจัย (ภาษาไทย)"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        required
                    />
                    <p class="error-message hidden" id="project_thai_name-error">กรุณากรอกชื่อโครงการวิจัย (ภาษาไทย)</p>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="project_english_name">ชื่อโครงการวิจัย (ภาษาอังกฤษ) <span class="text-red-500">*</span></label>
                    <input
                        type="text"
                        id="project_english_name"
                        name="project_english_name"
                        placeholder="ชื่อโครงการวิจัย (ภาษาอังกฤษ)"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        required
                    />
                    <p class="error-message hidden" id="project_english_name-error">กรุณากรอกชื่อโครงการวิจัย (ภาษาอังกฤษ)</p>
                </div>
                <div class="form-control w-full max-w-lg mb-4">
                    <label class="label">
                        <span class="label-text">ไฟล์เอกสารข้อเสนอโครงการวิจัย (PDF)</span>
                        <span class="label-text-alt text-red-500">*จำเป็น</span>
                    </label>
                    <input type="file" name="proposal_file" accept=".pdf" required
                        class="file-input file-input-bordered w-full max-w-lg" />
                    <p class="error-message hidden" id="proposal_file-error">กรุณาแนบไฟล์ข้อเสนอโครงการวิจัย (เฉพาะไฟล์ PDF)</p>
                </div>
                <div class="form-control w-full max-w-lg mb-4">
                    <label class="label">
                        <span class="label-text">ไฟล์เอกสารประกอบเพิ่มเติม (PDF)</span>
                        <span class="label-text-alt">ถ้ามี</span>
                    </label>
                    <input type="file" name="additional_file" accept=".pdf"
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
                    <button
                    type="button"
                    class="next-step bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-all"
                    >
                    ถัดไป
                </button>
                </div>
            </div>

            <div class="form-step hidden">
                <h3 class="text-xl font-bold mb-4">2. หัวหน้าโครงการวิจัย / ผู้ช่วยวิจัย</h3>

                <h4 class="text-lg font-semibold text-gray-800 mb-2">2.1.1 อาจารย์ผู้รับทุน (หัวหน้าโครงการ)</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 font-medium mb-1" for="teacher_prefix_name">คำนำหน้าชื่อ (นาย/นาง/นางสาว) <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            id="teacher_prefix_name"
                            name="teacher_prefix_name"
                            placeholder="นาย/นาง/นางสาว"
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            required
                        />
                        <p class="error-message hidden" id="teacher_prefix_name-error">กรุณากรอกคำนำหน้าชื่อ</p>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-1" for="teacher_academic_position">ตำแหน่งทางวิชาการ <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            id="teacher_academic_position"
                            name="teacher_academic_position"
                            placeholder="เช่น อาจารย์, ผู้ช่วยศาสตราจารย์"
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            required
                        />
                        <p class="error-message hidden" id="teacher_academic_position-error">กรุณากรอกตำแหน่งทางวิชาการ</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 font-medium mb-1" for="teacher_department">สังกัดภาควิชา/สาขา <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            id="teacher_department"
                            name="teacher_department"
                            placeholder="ภาควิชา/สาขา"
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            required
                        />
                        <p class="error-message hidden" id="teacher_department-error">กรุณากรอกสังกัดภาควิชา/สาขา</p>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-1" for="teacher_faculty_unit">คณะ/หน่วยงาน <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            id="teacher_faculty_unit"
                            name="teacher_faculty_unit"
                            placeholder="คณะ/หน่วยงาน"
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            required
                        />
                        <p class="error-message hidden" id="teacher_faculty_unit-error">กรุณากรอกคณะ/หน่วยงาน</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 font-medium mb-1" for="teacher_mobile_phone">โทรศัพท์มือถือ <span class="text-red-500">*</span></label>
                        <input
                            type="tel"
                            id="teacher_mobile_phone"
                            name="teacher_mobile_phone"
                            placeholder="เช่น 0812345678"
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            pattern="[0-9]{10}"
                            title="กรุณากรอกเบอร์โทรศัพท์ 10 หลัก"
                            required
                        />
                        <p class="error-message hidden" id="teacher_mobile_phone-error">กรุณากรอกเบอร์โทรศัพท์ 10 หลัก</p>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-1" for="teacher_email">E-mail <span class="text-red-500">*</span></label>
                        <input
                            type="email"
                            id="teacher_email"
                            name="teacher_email"
                            placeholder="email@example.com"
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            required
                        />
                        <p class="error-message hidden" id="teacher_email-error">กรุณากรอกอีเมลที่ถูกต้อง</p>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="teacher_research_proportion">สัดส่วนการทำงานวิจัย (%)</label>
                    <input
                        type="number"
                        id="teacher_research_proportion"
                        name="teacher_research_proportion"
                        placeholder="เช่น 100"
                        min="0"
                        max="100"
                        step="0.01"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    />
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="teacher_expert_field">สาขาวิจัยที่ชำนาญ</label>
                    <input
                        type="text"
                        id="teacher_expert_field"
                        name="teacher_expert_field"
                        placeholder="เช่น ปัญญาประดิษฐ์, วิทยาศาสตร์สิ่งแวดล้อม"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    />
                </div>

                <h4 class="text-lg font-semibold text-gray-800 mb-2">2.1.2 ประวัติการศึกษา</h4>
                <p class="text-gray-600 mb-2 text-sm">ระบุระดับการศึกษา, ปีที่จบ, สาขา, สถาบัน, ประเทศที่จบ (**ขึ้นบรรทัดใหม่สำหรับแต่ละรายการ**)</p>
                <textarea
                    id="teacher_education_history"
                    name="teacher_education_history"
                    rows="3"
                    placeholder="ตัวอย่าง:
ป.ตรี (ปี 2550) วิศวกรรมศาสตร์ ม.ขอนแก่น ประเทศไทย
ป.โท (ปี 2553) คอมพิวเตอร์ ม.มหาสารคาม ประเทศไทย"
                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 mb-4"
                ></textarea>

                <h4 class="text-lg font-semibold text-gray-800 mb-2">
                    2.1.3 ผลงานวิจัยที่ตีพิมพ์ในวารสารนานาชาติ (ปี ค.ศ. 2019 – ปัจจุบัน)
                </h4>
                <p class="text-gray-600 mb-2 text-sm">ระบุชื่อเจ้าของผลงาน, ชื่อเรื่อง, ชื่อวารสาร, Issue/Vol./No., Impact Factor, ฐานข้อมูล (**ขึ้นบรรทัดใหม่สำหรับแต่ละผลงาน**)</p>
                <textarea
                    id="teacher_international_publications"
                    name="teacher_international_publications"
                    rows="3"
                    placeholder="ตัวอย่าง:
1. Promkaew, S., & Green, A. (2022). 'Novel Approach to Sustainable Energy'. Journal of Renewable Energy, 10(2), 123-130. (IF: 5.2, Scopus)
2. Smith, J., & Promkaew, S. (2023). 'Impact of Climate Change on Agriculture'. Environmental Science, 15(1), 45-50. (IF: 3.8, ISI Web of Science)"
                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 mb-4"
                ></textarea>

                <h4 class="text-lg font-semibold text-gray-800 mb-2">2.2 ผู้ร่วมวิจัย (ถ้ามี)</h4>
                <p class="text-gray-600 mb-2 text-sm">ระบุ ชื่อ, สังกัด, โทรศัพท์, E-mail, สัดส่วนการทำงานวิจัย ฯลฯ (**ขึ้นบรรทัดใหม่สำหรับแต่ละคน**)</p>
                <textarea
                    id="co_researchers_details"
                    name="co_researchers_details"
                    rows="2"
                    placeholder="ตัวอย่าง:
นายสมชาย รักดี, สังกัด คณะวิทยาศาสตร์, โทร 08x-xxx-xxxx, E-mail: somchai@mail.com, สัดส่วน 20%
นางสาวสมศรี ใจดี, สังกัด คณะแพทยศาสตร์, โทร 09x-xxx-xxxx, E-mail: somsi@mail.com, สัดส่วน 10%"
                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 mb-4"
                ></textarea>

                <h4 class="text-lg font-semibold text-gray-800 mb-2">2.3 นิสิตผู้ร่วมวิจัย (ถ้ามี)</h4>
                <p class="text-gray-600 mb-2 text-sm">ระบุ ชื่อ, ระดับปริญญา, รหัสนิสิต, สังกัด, โทรศัพท์, E-mail, สัดส่วนการทำงานวิจัย ฯลฯ (**ขึ้นบรรทัดใหม่สำหรับแต่ละคน**)</p>
                <textarea
                    id="student_co_researchers_details"
                    name="student_co_researchers_details"
                    rows="2"
                    placeholder="ตัวอย่าง:
นายมานะ เรียนเก่ง, ป.โท, รหัส 65xxxxxxx, สังกัด ภาควิชาเคมี, โทร 08x-xxx-xxxx, E-mail: mana@mail.com, สัดส่วน 5%
นางสาววิไล ตั้งใจ, ป.เอก, รหัส 64xxxxxxx, สังกัด ภาควิชาฟิสิกส์, โทร 09x-xxx-xxxx, E-mail: vilai@mail.com, สัดส่วน 5%"
                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                ></textarea>

                <div class="flex justify-between mt-6">
                    <button
                        type="button"
                        class="prev-step bg-gray-400 text-white px-6 py-3 rounded-lg hover:bg-gray-500 transition-all"
                    >
                        ย้อนกลับ
                    </button>
                    <button
                        type="button"
                        class="next-step bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-all"
                    >
                        ถัดไป
                    </button>
                </div>
            </div>

            <div class="form-step hidden">
                <h3 class="text-xl font-bold mb-4">3. ประเภทของงานวิจัย & เป้าหมาย (MSU Goals)</h3>

                <div class="mb-6 checkbox-group-container">
                    <p class="text-lg font-semibold text-gray-800 mb-2">ประเภทของงานวิจัย <span class="text-red-500">*</span></p>
                    <div class="space-y-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="research_type[]" class="form-checkbox h-5 w-5 text-blue-600" value="พื้นฐาน" />
                            <span class="ml-2 text-gray-700">การวิจัยพื้นฐาน</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="research_type[]" class="form-checkbox h-5 w-5 text-blue-600" value="ประยุกต์" />
                            <span class="ml-2 text-gray-700">การวิจัยประยุกต์</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="research_type[]" class="form-checkbox h-5 w-5 text-blue-600" value="ทดลอง" />
                            <span class="ml-2 text-gray-700">การพัฒนาทดลอง</span>
                        </label>
                    </div>
                    <p class="error-message hidden" id="research_type-error">กรุณาเลือกประเภทของงานวิจัยอย่างน้อยหนึ่งข้อ</p>
                </div>

                <div class="mb-6 checkbox-group-container">
                    <p class="text-lg font-semibold text-gray-800 mb-2">เป้าหมายประเด็นการวิจัย (MSU Goals) <span class="text-red-500">*</span></p>
                    <div class="space-y-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="msu_goals[]" class="form-checkbox h-5 w-5 text-blue-600" value="Goals 1" />
                            <span class="ml-2 text-gray-700">Goals 1</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="msu_goals[]" class="form-checkbox h-5 w-5 text-blue-600" value="Goals 2" />
                            <span class="ml-2 text-gray-700">Goals 2</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="msu_goals[]" class="form-checkbox h-5 w-5 text-blue-600" value="Goals 3" />
                            <span class="ml-2 text-gray-700">Goals 3</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="msu_goals[]" class="form-checkbox h-5 w-5 text-blue-600" value="BCG" />
                            <span class="ml-2 text-gray-700">BCG Model (Bio-Circular-Green Economy)</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="msu_goals[]" class="form-checkbox h-5 w-5 text-blue-600" value="SDG" />
                            <span class="ml-2 text-gray-700">Sustainable Development Goals (SDG)</span>
                        </label>
                    </div>
                    <p class="error-message hidden" id="msu_goals-error">กรุณาเลือกเป้าหมายประเด็นการวิจัยอย่างน้อยหนึ่งข้อ</p>
                    <p class="text-sm text-gray-600 mt-2">
                        (หากเกี่ยวข้องกับรายละเอียด เช่น เกษตร, อาหาร, BCG ฯลฯ โปรดระบุในรายละเอียดโครงการ)
                    </p>
                </div>

                <div class="flex justify-between mt-6">
                    <button
                        type="button"
                        class="prev-step bg-gray-400 text-white px-6 py-3 rounded-lg hover:bg-gray-500 transition-all"
                    >
                        ย้อนกลับ
                    </button>
                    <button
                        type="button"
                        class="next-step bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-all"
                    >
                        ถัดไป
                    </button>
                </div>
            </div>

            <div class="form-step hidden">
                <h3 class="text-xl font-bold mb-4">4. จริยธรรม & ความสำคัญของปัญหาวิจัย</h3>

                <div class="mb-6 radio-group-container">
                    <p class="text-lg font-semibold text-gray-800 mb-2">5. จริยธรรมที่เกี่ยวข้อง (ถ้ามี) <span class="text-red-500">*</span></p>
                    <label class="inline-flex items-center mb-2">
                        <input type="radio" name="ethics_related" class="form-radio h-5 w-5 text-blue-600" value="ไม่เกี่ยวข้อง" checked required />
                        <span class="ml-2 text-gray-700">ไม่เกี่ยวข้อง</span>
                    </label>
                    <br />
                    <label class="inline-flex items-center mb-2">
                        <input type="radio" name="ethics_related" class="form-radio h-5 w-5 text-blue-600" value="คน" />
                        <span class="ml-2 text-gray-700">จริยธรรมการวิจัยในคน</span>
                    </label>
                    <br />
                    <label class="inline-flex items-center mb-2">
                        <input type="radio" name="ethics_related" class="form-radio h-5 w-5 text-blue-600" value="สัตว์" />
                        <span class="ml-2 text-gray-700">จริยธรรมการวิจัยในสัตว์</span>
                    </label>
                    <br />
                    <label class="inline-flex items-center mb-2">
                        <input type="radio" name="ethics_related" class="form-radio h-5 w-5 text-blue-600" value="ความปลอดภัยชีวภาพ" />
                        <span class="ml-2 text-gray-700">ความปลอดภัยทางชีวภาพระดับสถาบัน</span>
                    </label>
                    <br />
                    <label class="inline-flex items-center mb-2">
                        <input type="radio" name="ethics_related" class="form-radio h-5 w-5 text-blue-600" value="อยู่ระหว่างการยื่นขอ" />
                        <span class="ml-2 text-gray-700">อยู่ระหว่างการยื่นขอ</span>
                    </label>
                    <p class="error-message hidden" id="ethics_related-error">กรุณาเลือกจริยธรรมที่เกี่ยวข้อง</p>
                    <div class="mt-2">
                        <label class="block text-gray-700 font-medium mb-1" for="ethics_certification_number">เลขที่การรับรอง (ถ้ามี)</label>
                        <input
                            type="text"
                            id="ethics_certification_number"
                            name="ethics_certification_number"
                            placeholder="เลขที่การรับรอง (ถ้ามี)"
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="problem_significance">6. ความสำคัญและที่มาของปัญหาที่ทำการวิจัย <span class="text-red-500">*</span></label>
                    <textarea
                        id="problem_significance"
                        name="problem_significance"
                        rows="3"
                        placeholder="อธิบายความจำเป็นและความสำคัญของการวิจัย"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        required
                    ></textarea>
                    <p class="error-message hidden" id="problem_significance-error">กรุณากรอกความสำคัญและที่มาของปัญหาที่ทำการวิจัย</p>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="objectives">7. วัตถุประสงค์ <span class="text-red-500">*</span></label>
                    <textarea
                        id="objectives"
                        name="objectives"
                        rows="3"
                        placeholder="อธิบายวัตถุประสงค์การวิจัย (เช่น 1. เพื่อ..., 2. เพื่อ...)"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        required
                    ></textarea>
                    <p class="error-message hidden" id="objectives-error">กรุณากรอกวัตถุประสงค์</p>
                </div>

                <div class="flex justify-between mt-6">
                    <button
                        type="button"
                        class="prev-step bg-gray-400 text-white px-6 py-3 rounded-lg hover:bg-gray-500 transition-all"
                    >
                        ย้อนกลับ
                    </button>
                    <button
                        type="button"
                        class="next-step bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-all"
                    >
                        ถัดไป
                    </button>
                </div>
            </div>

            <div class="form-step hidden">
                <h3 class="text-xl font-bold mb-4">5. ทบทวนเอกสาร & ขั้นตอนวิจัย</h3>

                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="literature_review">8. ทบทวนเอกสารงานวิจัยที่เกี่ยวข้อง <span class="text-red-500">*</span></label>
                    <textarea
                        id="literature_review"
                        name="literature_review"
                        rows="3"
                        placeholder="ระบุความเชื่อมโยง พัฒนาการของงานวิจัยที่เกี่ยวข้อง"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        required
                    ></textarea>
                    <p class="error-message hidden" id="literature_review-error">กรุณากรอกทบทวนเอกสารงานวิจัยที่เกี่ยวข้อง</p>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="methodology">9. ขั้นตอนการดำเนินงาน/ระเบียบวิธีวิจัย <span class="text-red-500">*</span></label>
                    <textarea
                        id="methodology"
                        name="methodology"
                        rows="4"
                        placeholder="อธิบายกิจกรรม ทดลอง ทดสอบ อย่างละเอียด"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        required
                    ></textarea>
                    <p class="error-message hidden" id="methodology-error">กรุณากรอกขั้นตอนการดำเนินงาน/ระเบียบวิธีวิจัย</p>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="research_period">10. ระยะเวลาการทำวิจัย (ตั้งแต่...ถึง กันยายน 2568) <span class="text-red-500">*</span></label>
                    <input
                        type="text"
                        id="research_period"
                        name="research_period"
                        placeholder="ตัวอย่าง: ตั้งแต่ มกราคม 2568 ถึง กันยายน 2568 (รวม 9 เดือน)"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 mb-2"
                        required
                    />
                    <p class="error-message hidden" id="research_period-error">กรุณากรอกระยะเวลาการทำวิจัย</p>
                    <label class="block text-gray-700 font-medium mb-1" for="operation_plan">แผนการดำเนินงาน (ตารางกิจกรรม) <span class="text-red-500">*</span></label>
                    <textarea
                        id="operation_plan"
                        name="operation_plan"
                        rows="3"
                        placeholder="ตัวอย่าง:
เดือน 1-2: รวบรวมข้อมูล
เดือน 3-5: วิเคราะห์ข้อมูล
เดือน 6-9: เขียนรายงานและตีพิมพ์"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        required
                    ></textarea>
                    <p class="error-message hidden" id="operation_plan-error">กรุณากรอกแผนการดำเนินงาน</p>
                </div>

                <div class="flex justify-between mt-6">
                    <button
                        type="button"
                        class="prev-step bg-gray-400 text-white px-6 py-3 rounded-lg hover:bg-gray-500 transition-all"
                    >
                        ย้อนกลับ
                    </button>
                    <button
                        type="button"
                        class="next-step bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-all"
                    >
                        ถัดไป
                    </button>
                </div>
            </div>

            <div class="form-step hidden">
                <h3 class="text-xl font-bold mb-4">6. เป้าหมาย / ตัวชี้วัด & งบประมาณ</h3>

                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="expected_outcomes">
                        11. เป้าหมาย/ ตัวชี้วัดความสำเร็จของโครงการที่คาดว่าจะได้รับ <span class="text-red-500">*</span>
                    </label>
                    <p class="text-sm text-gray-600 mb-2">
                        ตัวอย่าง: ตีพิมพ์ในวารสารวิชาการระดับนานาชาติ (ISI, SCOPUS) ระบุชื่อวารสาร, ISSN, Impact Factor, Quartile, ชื่อผลงานวิจัย
                    </p>
                    <textarea
                        id="expected_outcomes"
                        name="expected_outcomes"
                        rows="3"
                        placeholder="ตัวอย่าง:
ตีพิมพ์ในวารสารวิชาการระดับนานาชาติ (SCOPUS, Q1)
ชื่อวารสาร: International Journal of Research
ISSN: XXXX-XXXX
Impact Factor: 4.5
ชื่อผลงานวิจัย: A Study on Advanced AI Algorithms"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        required
                    ></textarea>
                    <p class="error-message hidden" id="expected_outcomes-error">กรุณากรอกเป้าหมาย/ตัวชี้วัดความสำเร็จ</p>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="budget_details">12. งบประมาณ <span class="text-red-500">*</span></label>
                    <textarea
                        id="budget_details"
                        name="budget_details"
                        rows="3"
                        placeholder="ตัวอย่าง:
หมวดค่าตอบแทน: 80,000 บาท
ค่าใช้สอย: 50,000 บาท
ค่าวัสดุ: 20,000 บาท
รวมงบประมาณ: 150,000 บาท"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        required
                    ></textarea>
                    <p class="error-message hidden" id="budget_details-error">กรุณากรอกรายละเอียดงบประมาณ</p>
                </div>

                <div class="flex justify-between mt-6">
                    <button
                        type="button"
                        class="prev-step bg-gray-400 text-white px-6 py-3 rounded-lg hover:bg-gray-500 transition-all"
                    >
                        ย้อนกลับ
                    </button>
                    <button
                        type="submit"
                        id="submit-form"
                        class="bg-green-500 text-white px-6 py-3 rounded-lg hover:bg-green-600 transition-all"
                    >
                        ส่งแบบฟอร์ม
                    </button>
                </div>
            </div>

        </form>
    </div>
    <script src="assets/teacher.js"></script>
    <script>
      window.initTeacherMultiStep && window.initTeacherMultiStep();
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