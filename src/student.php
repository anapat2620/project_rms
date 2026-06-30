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

// ตรวจสอบ session
if (!isset($_SESSION['Email']) || !isset($_SESSION['Position'])) {
    header("Location: login.php");
    exit();
}

// รับพารามิเตอร์ระดับการศึกษาเพื่อให้เมนูสามารถแยกประเภททุนได้
$allowedStudentLevels = ['bachelor' => 'ปริญญาตรี', 'master' => 'ปริญญาโท', 'phd' => 'ปริญญาเอก'];
$selectedStudentLevel = $_GET['level'] ?? '';
if (!array_key_exists($selectedStudentLevel, $allowedStudentLevels)) {
    $selectedStudentLevel = '';
}
$selectedStudentLevelLabel = $selectedStudentLevel ? $allowedStudentLevels[$selectedStudentLevel] : '';

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
    <link rel="stylesheet" href="assets/student.css">
    <title>แบบฟอร์มขอรับทุนอุดหนุนและส่งเสริมการวิจัย (นิสิต)</title>
</head>
<body>
    <div class="max-w-5xl mx-auto bg-white p-8 rounded-xl shadow-lg my-8">
        <h2 class="text-3xl font-bold text-center mb-2">
            แบบเสนอขอรับทุนอุดหนุนและส่งเสริมการวิจัยของนิสิต
        </h2>
        <p class="text-center text-gray-600 mb-4">
            ประเภททุนวิจัยนิสิต ประจำปีงบประมาณ พ.ศ. 2568
            <?php if ($selectedStudentLevelLabel): ?>
                <br>ประเภท: <?php echo htmlspecialchars($selectedStudentLevelLabel); ?>
            <?php endif; ?>
        </p>
        <div class="flex items-center justify-between mb-6 text-sm md:text-base">
            <div class="step active text-blue-600 font-semibold text-center" aria-current="step">1. ข้อมูลทั่วไป</div>
            <div class="w-1/6 h-1 bg-gray-300 rounded-full mx-1"></div>
            <div class="step text-gray-500 text-center">2. นิสิต/อาจารย์ที่ปรึกษา</div>
            <div class="w-1/6 h-1 bg-gray-300 rounded-full mx-1"></div>
            <div class="step text-gray-500 text-center">3. ประเภท & Goals</div>
            <div class="w-1/6 h-1 bg-gray-300 rounded-full mx-1"></div>
            <div class="step text-gray-500 text-center">4. รายละเอียดโครงการ</div>
            <div class="w-1/6 h-1 bg-gray-300 rounded-full mx-1"></div>
            <div class="step text-gray-500 text-center">5. งบประมาณ & คำรับรอง</div>
        </div>
        <div class="w-full bg-gray-200 h-2 rounded-lg mb-6">
            <div id="progress-bar" class="bg-blue-500 h-2 rounded-lg" style="width: 16.6%;"></div>
        </div>
        <form id="multi-step-form" class="space-y-8" action="db_connect/db_connect.php?type=student" method="POST" enctype="multipart/form-data" novalidate>
            <!-- Step 1: ข้อมูลทั่วไป -->
            <div class="form-step active">
                <h3 class="text-xl font-bold mb-4">1. ข้อมูลทั่วไป</h3>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="project_thai_name">ชื่อโครงการวิจัย (ภาษาไทย) <span class="text-red-500">*</span></label>
                    <input type="text" id="project_thai_name" name="project_thai_name" placeholder="ชื่อโครงการวิจัย (ภาษาไทย)" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required />
                    <p class="error-message hidden" id="project_thai_name-error">กรุณากรอกชื่อโครงการวิจัย (ภาษาไทย)</p>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="project_english_name">ชื่อโครงการวิจัย (ภาษาอังกฤษ) <span class="text-red-500">*</span></label>
                    <input type="text" id="project_english_name" name="project_english_name" placeholder="ชื่อโครงการวิจัย (ภาษาอังกฤษ)" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required />
                    <p class="error-message hidden" id="project_english_name-error">กรุณากรอกชื่อโครงการวิจัย (ภาษาอังกฤษ)</p>
                </div>
                <div class="form-control w-full max-w-lg mb-4">
                    <label class="label">
                        <span class="label-text">ไฟล์เอกสารข้อเสนอโครงการวิจัย (PDF)</span>
                        <span class="label-text-alt text-red-500">*จำเป็น</span>
                    </label>
                    <input type="file" name="proposal_file" accept=".pdf" required class="file-input file-input-bordered w-full max-w-lg" />
                    <p class="error-message hidden" id="proposal_file-error">กรุณาแนบไฟล์ข้อเสนอโครงการวิจัย (เฉพาะไฟล์ PDF)</p>
                </div>
                <div class="form-control w-full max-w-lg mb-4">
                    <label class="label">
                        <span class="label-text">ไฟล์เอกสารประกอบเพิ่มเติม (PDF)</span>
                        <span class="label-text-alt">ถ้ามี</span>
                    </label>
                    <input type="file" name="additional_file" accept=".pdf" class="file-input file-input-bordered w-full max-w-lg" />
                    <p class="error-message hidden" id="additional_file-error">กรุณาแนบเฉพาะไฟล์ PDF</p>
                </div>
                <div class="form-control w-full max-w-lg mb-4">
                    <label class="label">
                        <span class="label-text">ไฟล์หลักฐานการตีพิมพ์ (PDF)</span>
                        <span class="label-text-alt">ถ้ามี</span>
                    </label>
                    <input type="file" name="publication_file" accept=".pdf" class="file-input file-input-bordered w-full max-w-lg" />
                </div>
                <div class="form-control w-full max-w-lg mb-4">
                    <label class="label">
                        <span class="label-text">ไฟล์หลักฐานการขอจริยธรรมวิจัยในมนุษย์ (PDF)</span>
                        <span class="label-text-alt">ถ้ามี</span>
                    </label>
                    <input type="file" name="ethics_file" accept=".pdf" class="file-input file-input-bordered w-full max-w-lg" />
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
            <!-- Step 2: นิสิต/อาจารย์ที่ปรึกษา -->
            <div class="form-step hidden">
                <h3 class="text-xl font-bold mb-4">2. นิสิต/อาจารย์ที่ปรึกษา</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                        <label class="block text-gray-700 font-medium mb-1" for="student_name">ชื่อ-นามสกุลนิสิต <span class="text-red-500">*</span></label>
                        <input type="text" id="student_name" name="student_name" placeholder="ชื่อ-นามสกุลนิสิต" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required />
                        <p class="error-message hidden" id="student_name-error">กรุณากรอกชื่อ-นามสกุลนิสิต</p>
                        </div>
                        <div>
                        <label class="block text-gray-700 font-medium mb-1" for="student_id">รหัสนิสิต <span class="text-red-500">*</span></label>
                        <input type="text" id="student_id" name="student_id" placeholder="รหัสนิสิต" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required />
                        <p class="error-message hidden" id="student_id-error">กรุณากรอกรหัสนิสิต</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                        <label class="block text-gray-700 font-medium mb-1" for="student_level">ระดับการศึกษา <span class="text-red-500">*</span></label>
                        <select id="student_level" name="student_level" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                                <option value="">เลือก</option>
                                <option value="phd"<?php echo $selectedStudentLevel === 'phd' ? ' selected' : ''; ?>>ปริญญาเอก</option>
                                <option value="master"<?php echo $selectedStudentLevel === 'master' ? ' selected' : ''; ?>>ปริญญาโท</option>
                                <option value="bachelor"<?php echo $selectedStudentLevel === 'bachelor' ? ' selected' : ''; ?>>ปริญญาตรี</option>
                            </select>
                        <p class="error-message hidden" id="student_level-error">กรุณาเลือกระดับการศึกษา</p>
                    </div>
                        <div>
                        <label class="block text-gray-700 font-medium mb-1" for="student_year">ชั้นปีที่ <span class="text-red-500">*</span></label>
                        <input type="number" id="student_year" name="student_year" placeholder="ชั้นปีที่" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required />
                        <p class="error-message hidden" id="student_year-error">กรุณากรอกชั้นปีที่</p>
                    </div>
                    </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                        <label class="block text-gray-700 font-medium mb-1" for="student_phone">โทรศัพท์มือถือ <span class="text-red-500">*</span></label>
                        <input type="tel" id="student_phone" name="student_phone" placeholder="เช่น 0812345678" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" pattern="[0-9]{10}" title="กรุณากรอกเบอร์โทรศัพท์ 10 หลัก" required />
                        <p class="error-message hidden" id="student_phone-error">กรุณากรอกเบอร์โทรศัพท์ 10 หลัก</p>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-1" for="student_email">E-mail <span class="text-red-500">*</span></label>
                        <input type="email" id="student_email" name="student_email" placeholder="email@example.com" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required />
                        <p class="error-message hidden" id="student_email-error">กรุณากรอกอีเมลที่ถูกต้อง</p>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="student_major">สาขาวิชา <span class="text-red-500">*</span></label>
                    <input type="text" id="student_major" name="student_major" placeholder="สาขาวิชา" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required />
                    <p class="error-message hidden" id="student_major-error">กรุณากรอกสาขาวิชา</p>
                        </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="student_faculty">คณะ <span class="text-red-500">*</span></label>
                    <input type="text" id="student_faculty" name="student_faculty" placeholder="คณะ" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required />
                    <p class="error-message hidden" id="student_faculty-error">กรุณากรอกคณะ</p>
                        </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="advisor_name">ชื่อ-นามสกุลอาจารย์ที่ปรึกษา <span class="text-red-500">*</span></label>
                    <input type="text" id="advisor_name" name="advisor_name" placeholder="ชื่อ-นามสกุลอาจารย์ที่ปรึกษา" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required />
                    <p class="error-message hidden" id="advisor_name-error">กรุณากรอกชื่อ-นามสกุลอาจารย์ที่ปรึกษา</p>
                    </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="advisor_position">ตำแหน่งทางวิชาการ <span class="text-red-500">*</span></label>
                    <input type="text" id="advisor_position" name="advisor_position" placeholder="ตำแหน่งทางวิชาการ" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required />
                    <p class="error-message hidden" id="advisor_position-error">กรุณากรอกตำแหน่งทางวิชาการ</p>
                    </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="advisor_department">สังกัดสาขาวิชา <span class="text-red-500">*</span></label>
                    <input type="text" id="advisor_department" name="advisor_department" placeholder="สังกัดสาขาวิชา" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required />
                    <p class="error-message hidden" id="advisor_department-error">กรุณากรอกสังกัดสาขาวิชา</p>
                        </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="advisor_faculty">คณะ <span class="text-red-500">*</span></label>
                    <input type="text" id="advisor_faculty" name="advisor_faculty" placeholder="คณะ" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required />
                    <p class="error-message hidden" id="advisor_faculty-error">กรุณากรอกคณะ</p>
                        </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="advisor_phone">โทรศัพท์มือถือ <span class="text-red-500">*</span></label>
                    <input type="tel" id="advisor_phone" name="advisor_phone" placeholder="เช่น 0812345678" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" pattern="[0-9]{10}" title="กรุณากรอกเบอร์โทรศัพท์ 10 หลัก" required />
                    <p class="error-message hidden" id="advisor_phone-error">กรุณากรอกเบอร์โทรศัพท์ 10 หลัก</p>
                    </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="advisor_email">E-mail <span class="text-red-500">*</span></label>
                    <input type="email" id="advisor_email" name="advisor_email" placeholder="email@example.com" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required />
                    <p class="error-message hidden" id="advisor_email-error">กรุณากรอกอีเมลที่ถูกต้อง</p>
                </div>
                <div class="flex justify-between mt-6">
                    <button type="button" class="prev-step bg-gray-400 text-white px-6 py-3 rounded-lg hover:bg-gray-500 transition-all">ย้อนกลับ</button>
                    <button type="button" class="next-step bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-all">ถัดไป</button>
                </div>
            </div>
            <!-- Step 3: ประเภทงานวิจัยและสาขาที่ทำการวิจัย -->
            <div class="form-step hidden">
                <h3 class="text-xl font-bold mb-4">3. ประเภทงานวิจัยและสาขาที่ทำการวิจัย</h3>
                <div class="mb-6">
                    <p class="text-lg font-semibold text-gray-800 mb-2">ประเภทของงานวิจัย</p>
                    <div class="space-y-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="research_type[]" value="พื้นฐาน" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">การวิจัยพื้นฐาน</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="research_type[]" value="ประยุกต์" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">การวิจัยประยุกต์</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="research_type[]" value="ทดลอง" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">การพัฒนาทดลอง</span>
                        </label>
                    </div>
                </div>
                <div class="mb-6">
                    <p class="text-lg font-semibold text-gray-800 mb-2">ประเภทงานวิจัยเพื่อพัฒนาการเรียนรู้</p>
                    <div class="space-y-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="learning_type[]" value="หลักสูตร" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">เพื่อพัฒนาหลักสูตร</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="learning_type[]" value="ฝึกปฏิบัติ" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">เพื่อพัฒนาการฝึกปฏิบัติงาน</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="learning_type[]" value="จัดการเรียน" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">เพื่อพัฒนาแนวทางการจัดการเรียนการสอน</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="learning_type[]" value="ประสบการณ์" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">เพื่อสร้างประสบการณ์จริง</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="learning_type[]" value="สื่อการสอน" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">เพื่อพัฒนาสื่อการสอน</span>
                        </label>
                    </div>
                </div>
                <div class="mb-6">
                    <p class="text-lg font-semibold text-gray-800 mb-2">เกี่ยวข้องกับกิจกรรม</p>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="นักศึกษา" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมการพัฒนานักศึกษา</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="ศิลปวัฒนธรรม" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมบำรุงศิลปวัฒนธรรม</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="กีฬา/สุขภาพ" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมกีฬาและสุขภาพอนามัย</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="ประกันคุณภาพ" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมประกันคุณภาพ</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="การเงิน" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมด้านการเงินและงบประมาณ</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="วางแผน" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมวางแผนและพัฒนา</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="บริหาร" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมด้านบริหารจัดการ</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="วิชาการ" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมวิชาการ</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="วิจัย" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมด้านการวิจัย</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="อนุรักษ์" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมอนุรักษ์และพัฒนาสิ่งแวดล้อม</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="ทั่วไป" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมทั่วไป</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="อาสา" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมอาสาพัฒนาชนบท</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="บริการ" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมบริการวิชาการสู่สังคมและชุมชน</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="อบรม" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมอบรมเพื่อพัฒนาตนเอง</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="คุณธรรม" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">กิจกรรมบำเพ็ญประโยชน์ คุณธรรม จริยธรรม</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="activities[]" value="ปรัชญา" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">ปรัชญา วิสัยทัศน์</span>
                        </label>
                    </div>
                </div>
                <div class="mb-6">
                    <p class="text-lg font-semibold text-gray-800 mb-2">สาขาที่ทำการวิจัย</p>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="research_field[]" value="วิทย์กายภาพ" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">วิทยาศาสตร์กายภาพและคณิตศาสตร์</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="research_field[]" value="นิติศาสตร์" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">สาขานิติศาสตร์</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="research_field[]" value="การแพทย์" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">วิทยาศาสตร์การแพทย์</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="research_field[]" value="รัฐศาสตร์" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">สาขารัฐศาสตร์และรัฐประศาสนศาสตร์</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="research_field[]" value="เคมี" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">วิทยาศาสตร์เคมีและเภสัช</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="research_field[]" value="เศรษฐศาสตร์" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">สาขาเศรษฐศาสตร์</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="research_field[]" value="เกษตรศาสตร์" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">สาขาเกษตรศาสตร์และชีววิทยา</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="research_field[]" value="สังคมวิทยา" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">สาขาสังคมวิทยา</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="research_field[]" value="วิศวกรรมศาสตร์" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">สาขาวิศวกรรมศาสตร์และอุตสาหกรรมวิจัย</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="research_field[]" value="เทคโนโลยีสารสนเทศ" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">สาขาเทคโนโลยีสารสนเทศและนิเทศศาสตร์</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="research_field[]" value="ปรัชญา" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">สาขาปรัชญา</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="research_field[]" value="การศึกษา" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">สาขาการศึกษา</span>
                        </label>
                    </div>
                </div>
                <div class="flex justify-between mt-6">
                    <button type="button" class="prev-step bg-gray-400 text-white px-6 py-3 rounded-lg hover:bg-gray-500 transition-all">ย้อนกลับ</button>
                    <button type="button" class="next-step bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-all">ถัดไป</button>
                </div>
            </div>

            <!-- Step 4: รายละเอียดโครงการ -->
            <div class="form-step hidden">
                <h3 class="text-xl font-bold mb-4">4. รายละเอียดโครงการ</h3>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="rationale">1. หลักการและเหตุผล (Rationale) <span class="text-red-500">*</span></label>
                    <textarea id="rationale" name="rationale" rows="4" placeholder="หลักการและเหตุผล" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required></textarea>
                    <p class="error-message hidden" id="rationale-error">กรุณากรอกหลักการและเหตุผล</p>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="objectives">2. วัตถุประสงค์ (Objectives) <span class="text-red-500">*</span></label>
                    <textarea id="objectives" name="objectives" rows="4" placeholder="วัตถุประสงค์" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required></textarea>
                    <p class="error-message hidden" id="objectives-error">กรุณากรอกวัตถุประสงค์</p>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="importance">3. ความสำคัญ (Importance) <span class="text-red-500">*</span></label>
                    <textarea id="importance" name="importance" rows="4" placeholder="ความสำคัญ" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required></textarea>
                    <p class="error-message hidden" id="importance-error">กรุณากรอกความสำคัญ</p>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="literature">4. การทบทวนวรรณกรรมที่เกี่ยวข้อง (Literature Review) <span class="text-red-500">*</span></label>
                    <textarea id="literature" name="literature" rows="4" placeholder="การทบทวนวรรณกรรมที่เกี่ยวข้อง" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required></textarea>
                    <p class="error-message hidden" id="literature-error">กรุณากรอกการทบทวนวรรณกรรมที่เกี่ยวข้อง</p>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="conceptual_framework">5. กรอบแนวคิด/สมมติฐาน (Conceptual Framework/Hypothesis) <span class="text-red-500">*</span></label>
                    <textarea id="conceptual_framework" name="conceptual_framework" rows="4" placeholder="กรอบแนวคิด/สมมติฐาน" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required></textarea>
                    <p class="error-message hidden" id="conceptual_framework-error">กรุณากรอกกรอบแนวคิด/สมมติฐาน</p>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="hypothesis">6. สมมติฐาน (Hypothesis) <span class="text-red-500">*</span></label>
                    <textarea id="hypothesis" name="hypothesis" rows="4" placeholder="สมมติฐาน" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required></textarea>
                    <p class="error-message hidden" id="hypothesis-error">กรุณากรอกสมมติฐาน</p>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="methodology">7. ระเบียบวิธีวิจัย (Methodology) <span class="text-red-500">*</span></label>
                    <textarea id="methodology" name="methodology" rows="4" placeholder="ระเบียบวิธีวิจัย" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required></textarea>
                    <p class="error-message hidden" id="methodology-error">กรุณากรอกระเบียบวิธีวิจัย</p>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="references_link">8. เอกสารอ้างอิง/ลิงก์เอกสารอ้างอิง (References/Links) <span class="text-red-500">*</span></label>
                    <textarea id="references_link" name="references_link" rows="4" placeholder="เอกสารอ้างอิง/ลิงก์เอกสารอ้างอิง" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required></textarea>
                    <p class="error-message hidden" id="references_link-error">กรุณากรอกเอกสารอ้างอิง/ลิงก์เอกสารอ้างอิง</p>
                </div>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 font-medium mb-1" for="research_start">9. ระยะเวลาที่เริ่มทำการวิจัย <span class="text-red-500">*</span></label>
                        <input type="date" id="research_start" name="research_start" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required />
                        <p class="error-message hidden" id="research_start-error">กรุณากรอกระยะเวลาที่เริ่มทำการวิจัย</p>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-1" for="research_end">ระยะเวลาที่สิ้นสุดทำการวิจัย <span class="text-red-500">*</span></label>
                        <input type="date" id="research_end" name="research_end" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required />
                        <p class="error-message hidden" id="research_end-error">กรุณากรอกระยะเวลาที่สิ้นสุดทำการวิจัย</p>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="research_schedule">10. แผนการดำเนินงานและระยะเวลา (Research Schedule) <span class="text-red-500">*</span></label>
                    <textarea id="research_schedule" name="research_schedule" rows="4" placeholder="แผนการดำเนินงานและระยะเวลา" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required></textarea>
                    <p class="error-message hidden" id="research_schedule-error">กรุณากรอกแผนการดำเนินงานและระยะเวลา</p>
                </div>
                <div class="flex justify-between mt-6">
                    <button type="button" class="prev-step bg-gray-400 text-white px-6 py-3 rounded-lg hover:bg-gray-500 transition-all">ย้อนกลับ</button>
                    <button type="button" class="next-step bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-all">ถัดไป</button>
                </div>
            </div>

            <!-- Step 5: งบประมาณและคำรับรอง -->
            <div class="form-step hidden">
                <h3 class="text-xl font-bold mb-4">5. งบประมาณและคำรับรอง</h3>
                <div class="mb-6">
                    <p class="text-lg font-semibold text-gray-800 mb-2">1. ตัวชี้วัดความสำเร็จของโครงการ (Success Indicators)</p>
                    <div class="space-y-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="success_indicators[]" value="รายงานวิจัย" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">ส่งรายงานการวิจัยฉบับสมบูรณ์ภายใน 1 ปี</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="success_indicators[]" value="ตีพิมพ์ระดับชาติ" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">ตีพิมพ์ผลงานวิจัยในวารสารระดับชาติหรือนำเสนอในที่ประชุมระดับชาติ (ถ้ามี)</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="success_indicators[]" value="ตีพิมพ์ระดับนานาชาติ" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">ตีพิมพ์ผลงานวิจัยในวารสารระดับนานาชาติหรือนำเสนอในที่ประชุมระดับนานาชาติ (ถ้ามี)</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="success_indicators[]" value="จดสิทธิบัตร" class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">จดสิทธิบัตร/อนุสิทธิบัตร (ถ้ามี)</span>
                        </label>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="publication_title">2. ถ้ามีการตีพิมพ์โปรดระบุชื่อเรื่อง/วารสาร <span class="text-red-500">*</span></label>
                    <input type="text" id="publication_title" name="publication_title" placeholder="ชื่อเรื่อง" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 mb-2" required />
                    <input type="text" name="journal_name" placeholder="ชื่อวารสาร" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="requested_budget">3. งบประมาณที่ขอ (บาท) <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" id="requested_budget" name="requested_budget" placeholder="จำนวนเงิน" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required />
                    <p class="error-message hidden" id="requested_budget-error">กรุณากรอกงบประมาณที่ขอ</p>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1" for="budget_details">4. รายละเอียดงบประมาณ <span class="text-red-500">*</span></label>
                    <textarea id="budget_details" name="budget_details" rows="4" placeholder="รายละเอียดงบประมาณ" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required></textarea>
                    <p class="error-message hidden" id="budget_details-error">กรุณากรอกรายละเอียดงบประมาณ</p>
                </div>
                <div class="flex justify-between mt-6">
                    <button type="button" class="prev-step bg-gray-400 text-white px-6 py-3 rounded-lg hover:bg-gray-500 transition-all">ย้อนกลับ</button>
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
    <script src="assets/student.js"></script>
    <script>
      window.initStudentMultiStep && window.initStudentMultiStep();
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