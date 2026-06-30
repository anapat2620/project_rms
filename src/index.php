<?php
session_start();

// Debug: บันทึกข้อมูล session
error_log("Index page - Session data: " . json_encode($_SESSION));

// ตรวจสอบว่า login หรือยัง โดยดูว่า session ที่ต้องใช้มีอยู่หรือไม่
if (!isset($_SESSION['Email'])) {
    error_log("No email in session, redirecting to login");
    // ถ้ายังไม่ได้ login ให้ redirect ไปหน้า login
    header("Location: ../login/index1.html");
    exit();
}

$approverPositions = [
    'คณบดี',
    'รองคณบดี',
    'ผู้ช่วยคณบดี',
    'หัวหน้าภาควิชา',
    'ผู้อำนวยการหลักสูตร'
];
$canApprove = isset($_SESSION['Position']) && in_array($_SESSION['Position'], $approverPositions, true);

// กำหนดค่า auto-load สำหรับ JS (ไม่ redirect ออกไป ให้ JS โหลดใน #form แทนทุกกรณี)
// ถ้าไม่มี ?view → โหลด home.php เป็นหน้าแรกเสมอ
$autoLoad = 'home.php';
$statusMsg = null;
$statusAction = null;

if (isset($_GET['view'])) {
    switch ($_GET['view']) {
        case 'my':
            $autoLoad = 'get_my_scholarships.php';
            break;
        case 'approve':
            $autoLoad = 'approve_requests.php';
            if (isset($_GET['status'])) $statusMsg = $_GET['status'];
            if (isset($_GET['action'])) $statusAction = $_GET['action'];
            break;
        case 'downloads':
            $autoLoad = 'mbs_downloads.php';
            break;
        case 'home':
        default:
            $autoLoad = 'home.php';
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="./output.css" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.24/dist/full.min.css" rel="stylesheet" type="text/css" />
    <!-- CSS ของ sub-pages โหลดล่วงหน้า เพื่อให้ใช้งานได้เมื่อ inject HTML เข้า #form -->
    <link rel="stylesheet" href="assets/get_my_scholarships.css" />
    <link rel="stylesheet" href="assets/approve_requests.css" />
    <link rel="stylesheet" href="assets/mbs_downloads.css" />
    <link rel="stylesheet" href="assets/home.css" />
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <!-- Tailwind CDN สำรองสำหรับ sub-pages ที่ใช้ utility classes เพิ่มเติม -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        [data-theme="dark"] .loading-overlay {
            background: rgba(0, 0, 0, 0.8);
        }

        .loading {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    
    <title>หน้าหลัก - ระบบบริหารจัดการและจัดสรรทุนภายใน</title>
</head>

<body>
    <script>
      window.CURRENT_USER = {
        position: <?php echo json_encode($_SESSION['Position'] ?? ''); ?>,
        email: <?php echo json_encode($_SESSION['Email'] ?? ''); ?>,
        username: <?php echo json_encode($_SESSION['Username'] ?? ''); ?>,
        canApprove: <?php echo $canApprove ? 'true' : 'false'; ?>
      };
    </script>
    <?php
    // ====== Toast Notification (session flash) ======
    $toastMsg    = $_SESSION['toast_message'] ?? null;
    $toastStatus = $_SESSION['toast_status']  ?? 'info';
    unset($_SESSION['toast_message'], $_SESSION['toast_status']);

    // legacy support: form_message / form_status
    if (!$toastMsg && isset($_SESSION['form_message'])) {
        $toastMsg    = $_SESSION['form_message'];
        $toastStatus = ($_SESSION['form_status'] === 'success') ? 'success' : 'error';
        unset($_SESSION['form_message'], $_SESSION['form_status']);
    }
    ?>

    <?php if ($toastMsg): ?>
    <!-- Toast Container -->
    <div id="toast-container"
         style="position:fixed;top:1.5rem;right:1.5rem;z-index:9999;min-width:320px;max-width:420px;">

        <?php
        $icons = [
            'success' => '<svg xmlns="http://www.w3.org/2000/svg" class="shrink-0" style="width:28px;height:28px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
            'error'   => '<svg xmlns="http://www.w3.org/2000/svg" class="shrink-0" style="width:28px;height:28px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
            'warning' => '<svg xmlns="http://www.w3.org/2000/svg" class="shrink-0" style="width:28px;height:28px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>',
            'info'    => '<svg xmlns="http://www.w3.org/2000/svg" class="shrink-0" style="width:28px;height:28px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        ];
        $labels = [
            'success' => 'สำเร็จ!',
            'error'   => 'เกิดข้อผิดพลาด!',
            'warning' => 'คำเตือน!',
            'info'    => 'แจ้งเตือน',
        ];
        $colors = [
            'success' => '#22c55e',
            'error'   => '#ef4444',
            'warning' => '#f59e0b',
            'info'    => '#3b82f6',
        ];
        $bgColors = [
            'success' => '#f0fdf4',
            'error'   => '#fef2f2',
            'warning' => '#fffbeb',
            'info'    => '#eff6ff',
        ];
        $icon    = $icons[$toastStatus]    ?? $icons['info'];
        $label   = $labels[$toastStatus]   ?? 'แจ้งเตือน';
        $color   = $colors[$toastStatus]   ?? '#3b82f6';
        $bgColor = $bgColors[$toastStatus] ?? '#eff6ff';
        ?>

        <div id="toast-box"
             style="
                background:<?= $bgColor ?>;
                border-left: 5px solid <?= $color ?>;
                border-radius: 12px;
                box-shadow: 0 8px 32px rgba(0,0,0,0.15), 0 2px 8px rgba(0,0,0,0.08);
                padding: 1rem 1.25rem;
                display: flex;
                align-items: flex-start;
                gap: 0.875rem;
                font-family: 'Kanit', sans-serif;
                transform: translateX(120%);
                opacity: 0;
                transition: transform 0.4s cubic-bezier(.22,1,.36,1), opacity 0.4s ease;
                position: relative;
             ">

            <!-- Progress bar -->
            <div id="toast-progress"
                 style="
                    position:absolute;bottom:0;left:0;height:4px;
                    background:<?= $color ?>;border-radius:0 0 0 12px;
                    width:100%;transition:width linear;
                 "></div>

            <!-- Icon -->
            <div style="color:<?= $color ?>;margin-top:2px;flex-shrink:0;">
                <?= $icon ?>
            </div>

            <!-- Text -->
            <div style="flex:1;min-width:0;">
                <div style="font-weight:600;font-size:1rem;color:#1e293b;margin-bottom:2px;">
                    <?= htmlspecialchars($label) ?>
                </div>
                <div style="font-size:0.9rem;color:#475569;word-break:break-word;line-height:1.5;">
                    <?= htmlspecialchars($toastMsg) ?>
                </div>
            </div>

            <!-- Close button -->
            <button onclick="closeToast()"
                    style="
                       background:none;border:none;cursor:pointer;
                       color:#94a3b8;font-size:1.25rem;line-height:1;
                       padding:0;margin-top:1px;flex-shrink:0;
                    "
                    aria-label="ปิด">&#x2715;</button>
        </div>
    </div>

    <style>
        @keyframes toast-in  { from { transform:translateX(120%); opacity:0; } to { transform:translateX(0); opacity:1; } }
        @keyframes toast-out { from { transform:translateX(0);    opacity:1; } to { transform:translateX(120%); opacity:0; } }
    </style>

    <script>
    (function () {
        var DURATION = 5000; // ms
        var box      = document.getElementById('toast-box');
        var progress = document.getElementById('toast-progress');
        var timer;

        function openToast() {
            box.style.transform = 'translateX(0)';
            box.style.opacity   = '1';
            // shrink progress bar
            progress.style.transitionDuration = DURATION + 'ms';
            // slight delay so transition registers
            setTimeout(function () {
                progress.style.width = '0%';
            }, 30);
            timer = setTimeout(closeToast, DURATION);
        }

        window.closeToast = function () {
            clearTimeout(timer);
            box.style.transform = 'translateX(120%)';
            box.style.opacity   = '0';
            setTimeout(function () {
                var c = document.getElementById('toast-container');
                if (c) c.remove();
            }, 420);
        };

        // run after DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', openToast);
        } else {
            setTimeout(openToast, 80);
        }
    })();
    </script>
    <?php endif; ?>

    <div class="navbar bg-blue-400 flex justify-between">
        <div class="flex items-center  p-2 rounded">
            <img src="../Photo/mbslogo.png" alt="logombs" class="h-1/2" />
        </div>

        <div class="flex items-center gap-4">
            <?php if ($canApprove): ?>
            <div class="form-control group">
                <div class="relative">
                    <input
                        type="text"
                        placeholder="ค้นหา"
                        class="input input-bordered pr-10 w-40 focus:w-64 transition-all duration-300 bg-base-100 text-base-content"
                        id="search-grant"
                    />
                    <img
                        src="../Photo/icons.png"
                        alt="search-icon"
                        class="w-5 h-5 absolute right-3 top-1/2 transform -translate-y-1/2 hover:scale-110 transition-all duration-300"
                        id="search-icon" 
                    />
                </div>
            </div>
            <?php endif; ?>

            <button class="btn btn-warning" id="btn-home">
                กลับสู่หน้าแรก
            </button>

            <label
                class="swap swap-rotate mr-3 ml-3 hover:animate-bounce hover:scale-110 transform transition-transform duration-300"
            >
                <input type="checkbox" class="theme-controller" value="dark" />
                <svg
                    class="swap-off h-10 w-10 fill-current"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                >
                    <path
                        d="M5.64,17l-.71.71a1,1,0,0,0,0,1.41,1,1,0,0,0,1.41,0l.71-.71A1,1,0,0,0,5.64,17ZM5,12a1,1,0,0,0-1-1H3a1,1,0,0,0,0,2H4A1,1,0,0,0,5,12Zm7-7a1,1,0,0,0,1-1V3a1,1,0,0,0-2,0V4A1,1,0,0,0,12,5ZM5.64,7.05a1,1,0,0,0,.7.29,1,1,0,0,0,.71-.29,1,1,0,0,0,0-1.41l-.71-.71A1,1,0,0,0,4.93,6.34Zm12,.29a1,1,0,0,0,.7-.29l.71-.71a1,1,0,1,0-1.41-1.41L17,5.64a1,1,0,0,0,0,1.41A1,1,0,0,0,17.66,7.34ZM21,11H20a1,1,0,0,0,0,2h1a1,1,0,0,0,0-2Zm-9,8a1,1,0,0,0-1,1v1a1,1,0,0,0,2,0V20A1,1,0,0,0,12,19ZM18.36,17A1,1,0,0,0,17,18.36l.71.71a1,1,0,0,0,1.41,0,1,1,0,0,0,0-1.41ZM12,6.5A5.5,5.5,0,1,0,17.5,12,5.51,5.51,0,0,0,12,6.5Zm0,9A3.5,3.5,0,1,1,15.5,12,3.5,3.5,0,0,1,12,15.5Z"
                    />
                </svg>
                <svg
                    class="swap-on h-10 w-10 fill-current"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                >
                    <path
                        d="M21.64,13a1,1,0,0,0-1.05-.14,8.05,8.05,0,0,1-3.37.73A8.15,8.15,0,0,1,9.08,5.49a8.59,8.59,0,0,1,.25-2A1,1,0,0,0,8,2.36,10.14,10.14,0,1,0,22,14.05,1,1,0,0,0,21.64,13Zm-9.5,6.69A8.14,8.14,0,0,1,7.08,5.22v.27A10.15,10.15,0,0,0,17.22,15.63a9.79,9.79,0,0,0,2.1-.22A8.11,8.11,0,0,1,12.14,19.73Z"
                    />
                </svg>
            </label>
        </div>
    </div>

    <div class="navbar bg-neutral text-neutral-content">
        <div class="flex-1">
            <select id="funding-type" class="select select-bordered w-full max-w-xs bg-base-100 text-base-content">
                <option value="" disabled selected>เลือกประเภททุน</option>
                <option value="student.php">ทุนวิจัยของนิสิต</option>
                <option value="teacher.php">ทุนวิจัยของอาจารย์</option>
                <option value="personnel.php">ทุนวิจัยเพื่อพัฒนาองค์กรและพัฒนาบุคลากร</option>
            </select>

            <?php if ($canApprove): ?>
            <button class="ml-2 btn btn-warning text-white font-bold cursor-pointer hover:bg-accent-focus focus:outline-none focus:ring-2 focus:ring-accent focus:ring-opacity-50" id="btn-status">
                เช็คสถานะการยื่นทุน
            </button>
            <?php endif; ?>
            <button class="ml-2 btn btn-info text-white font-bold cursor-pointer hover:bg-accent-focus focus:outline-none focus:ring-2 focus:ring-accent focus:ring-opacity-50" id="btn-summary">
                สถานะการยื่นทุนทั้งหมด
            </button>
        </div>
    </div>

    <div class="mt-5" id="form"></div>

    <script>
      window.__AUTO_LOAD__        = <?= json_encode($autoLoad) ?>;
      window.__AUTO_LOAD_STATUS__ = <?= json_encode($statusMsg) ?>;
      window.__AUTO_LOAD_ACTION__ = <?= json_encode($statusAction) ?>;
    </script>

    <!--
      - เติม ?embed=1 ให้ทุก URL ที่เป็น sub-page ที่รองรับ
      - หลัง inject HTML ให้ re-execute <script> tags ทั้งหมดใน fragment
        (browser ไม่รัน <script> ที่มาจาก innerHTML โดยอัตโนมัติ)
    -->
    <script>
    /**
     * homeNav(page, viewKey)
     * ฟังก์ชัน global ที่ทุก sub-page (home.php, approve_requests.php ฯลฯ)
     * สามารถเรียกใช้ได้ผ่าน onclick โดยไม่ต้องกังวลว่าจะอยู่ใน scope ไหน
     * เพราะ define ไว้ที่ index.php ซึ่งเป็น top-level page เสมอ
     */
    function homeNav(page, viewKey) {
        if (typeof loadForm === 'function') {
            loadForm(page);
        } else {
            window.location.href = 'index.php?view=' + (viewKey || '');
        }
    }
    window.homeNav = homeNav;
    </script>

    <script>
    var EMBED_PAGES = ['home.php', 'get_my_scholarships.php', 'approve_requests.php', 'mbs_downloads.php'];

    function buildEmbedUrl(url) {
        if (!url) return url;
        var base = url.split('?')[0];
        var isEmbeddable = EMBED_PAGES.some(function(p) { return base.indexOf(p) !== -1; });
        if (!isEmbeddable) return url;
        var sep = url.indexOf('?') !== -1 ? '&' : '?';
        return url + sep + 'embed=1';
    }

    function reExecuteScripts(container) {
        var scripts = container.querySelectorAll('script');
        scripts.forEach(function(oldScript) {
            var newScript = document.createElement('script');
            // คัดลอก attributes ทั้งหมด (เช่น src, type)
            Array.from(oldScript.attributes).forEach(function(attr) {
                newScript.setAttribute(attr.name, attr.value);
            });
            if (!oldScript.src) {
                // inline script — copy content
                newScript.textContent = oldScript.textContent;
            }
            newScript.async = false;
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    }

    // override loadForm หลังจาก index.js define แล้ว
    // ใช้ DOMContentLoaded เพื่อให้แน่ใจว่า index.js โหลดก่อน
    document.addEventListener('DOMContentLoaded', function() {
        var _origLoadForm = window.loadForm;
        if (typeof _origLoadForm === 'function') {
            window.loadForm = function(url, options) {
                var embedUrl = buildEmbedUrl(url);
                var result = _origLoadForm(embedUrl, options);
                // รอให้ #form มีเนื้อหา แล้ว re-execute scripts
                if (result && typeof result.then === 'function') {
                    return result.then(function(val) {
                        var f = document.getElementById('form');
                        if (f) reExecuteScripts(f);
                        return val;
                    });
                }
                // fallback: MutationObserver
                var obs = new MutationObserver(function(mutations, o) {
                    var f = document.getElementById('form');
                    if (f && f.innerHTML.trim()) {
                        o.disconnect();
                        reExecuteScripts(f);
                    }
                });
                obs.observe(document.getElementById('form') || document.body, { childList: true, subtree: true });
                return result;
            };
        }
    });
    </script>

    <script src="assets/index.js"></script>
</body>
</html>