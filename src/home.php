<?php
session_start();

if (!isset($_SESSION['Email'])) {
    header("Location: ../login/index1.html");
    exit();
}
if (!isset($_SESSION['Username']) || !isset($_SESSION['Position'])) {
    header("Location: ../login/index1.html");
    exit();
}

$isEmbed = isset($_GET['embed']) && $_GET['embed'] === '1';
?>
<?php if (!$isEmbed): ?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>หน้าแรก - ระบบบริหารจัดการงานวิจัย</title>
  <link href="./output.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.24/dist/full.min.css" rel="stylesheet" type="text/css" />
  <link rel="stylesheet" href="assets/home.css" />
</head>
<body>
<?php endif; ?>

  <!-- home-wrapper:
       standalone  → height: 100vh (CSS default)
       embedded    → JS เติม class "embedded" → height: calc(100vh - 8rem) -->
  <div class="flex home-wrapper" id="homeWrapper">
    <!-- Left Section -->
    <div class="home-sidebar">
      <!-- Profile Card -->
      <div class="p-6">
        <div class="bg-base-100 rounded-xl shadow p-6 mb-6">
          <div class="status-info hover:shadow-xl transition-shadow duration-300">
            <div class="flex flex-col gap-4 justify-center items-center">
              <div class="avatar online">
                <div class="w-24 rounded-full ring ring-primary ring-offset-base-100 ring-offset-2 shadow-md">
                  <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTe95QazGSpiGTt81FqpdhszrqP7Ya7bG4fdA&s" />
                </div>
              </div>
              <div class="status-info-grid w-full">
                <div class="status-item">
                  <span class="status-label">สถานะ</span>
                  <div class="status-badge status-approved">กำลังออนไลน์</div>
                </div>

                <div class="status-item">
                  <span class="status-label">ชื่อ</span>
                  <div class="name-display"><?= htmlspecialchars($_SESSION['Username'] ?? '' ); ?></div>
                </div>

                <div class="status-item">
                  <span class="status-label">ระดับ</span>
                  <div class="level-indicator"><?= htmlspecialchars($_SESSION['Position'] ?? '' ); ?></div>
                </div>

                <div class="status-item">
                  <span class="status-label">คณะ</span>
                  <div class="faculty-badge"><?= htmlspecialchars($_SESSION['Facuity'] ?? '' ); ?></div>
                </div>

                <div class="status-item">
                  <span class="status-label">จำนวนทุนที่ยื่นแล้ว</span>
                  <div class="grant-amount"><?= htmlspecialchars($_SESSION['Quantity'] ?? '' ); ?></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-base-100 rounded-xl shadow p-6">
          <h3 class="text-lg font-bold mb-4">Menu</h3>
          <div class="flex flex-col gap-3">

            <button
              onclick="homeNav('get_my_scholarships.php', 'my')"
              class="menu-btn btn btn-block font-bold justify-start w-full gap-2"
              style="background-color:#2563eb; color:#fff">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="#fff">
                <circle cx="11" cy="11" r="7" stroke-width="2" stroke="#fff" fill="none"/>
                <path stroke-width="2" stroke="#fff" d="M21 21l-4.35-4.35"/>
              </svg>
              ประวัติขอทุนของฉัน
            </button>

            <?php
            $approverPositions = ['คณบดี', 'รองคณบดี', 'ผู้ช่วยคณบดี', 'หัวหน้าภาควิชา', 'ผู้อำนวยการหลักสูตร'];
            if (isset($_SESSION['Position']) && in_array($_SESSION['Position'], $approverPositions)):
            ?>
            <button
              onclick="homeNav('approve_requests.php', 'approve')"
              class="menu-btn btn btn-block font-bold justify-start w-full gap-2"
              style="background-color:#22c55e; color:#fff">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="#fff">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              อนุมัติ/ปฏิเสธคำขอทุน
            </button>
            <?php endif; ?>

            <?php if (isset($_SESSION['Position']) && $_SESSION['Position'] === 'Admin'): ?>
            <a
              href="admin_dashboard.php"
              class="menu-btn btn btn-block font-bold justify-start w-full gap-2"
              style="background-color:#9333ea; color:#fff">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="#fff">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              แดชบอร์ดผู้ดูแลระบบ
            </a>
            <?php endif; ?>

            <button
              onclick="homeNav('mbs_downloads.php', 'downloads')"
              class="menu-btn btn btn-block font-bold justify-start w-full gap-2"
              style="background-color:#38bdf8; color:#fff">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="#fff">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5 5 5-5M12 4v12" />
              </svg>
              ดาวน์โหลดแบบฟอร์มทุน MBS
            </button>

            <button
              onclick="document.getElementById('my_modal_1').showModal()"
              class="menu-btn btn btn-block font-bold justify-start w-full gap-2"
              style="background-color:#fb7185; color:#fff">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="#fff">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
              ออกจากระบบ
            </button>

          </div>
        </div>
      </div>
    </div>

    <!-- Right Section -->
    <div class="flex-1">
      <!-- Scholarship Status -->
      <div class="h-full flex flex-col">
        <div class="status-header-container bg-gradient-to-r from-blue-600 to-purple-600 rounded-t-xl p-4 shadow-lg">
          <h2 class="text-xl font-bold text-white flex items-center gap-3">
            สถานะการขอทุนวิจัยของฉัน
          </h2>
        </div>
        <div class="status-content-container bg-white rounded-b-xl shadow-xl border border-gray-200 flex-1 overflow-hidden">
          <iframe 
            src="scholarship_summary.php" 
            class="w-full h-full"
            id="scholarshipFrame"
            scrolling="auto"
            style="border: none;"
          ></iframe>
        </div>
      </div>
    </div>
  </div>

  <!-- Logout Modal -->
  <dialog id="my_modal_1" class="modal">
    <div class="modal-box bg-base-100 shadow-2xl border border-base-200">
      <h3 class="text-lg font-bold flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        ยืนยันการออกจากระบบ
      </h3>
      <p class="py-4 text-base-content/70">คุณต้องการออกจากระบบใช่หรือไม่?</p>
      <div class="modal-action">
        <form action="controller/logout.php" method="post" class="flex gap-2">
          <button type="submit" class="btn btn-error">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            ออกจากระบบ
          </button>
          <button type="button" class="btn btn-ghost" onclick="my_modal_1.close()">ยกเลิก</button>
        </form>
      </div>
    </div>
  </dialog>


  <!-- Theme Toggle Script -->
  <script src="assets/home.js"></script>

  <script>
    /*
     * homeNav ถูก define ไว้ใน index.php (top-level page) แล้ว
     * home.php ไม่ต้อง define ซ้ำ — เรียกได้โดยตรงเพราะอยู่ใน scope เดียวกัน
     * เมื่อ home.php ถูก standalone ให้ define fallback ไว้กันกรณีเปิดตรง
     */
    if (typeof homeNav === 'undefined') {
      window.homeNav = function(page, viewKey) {
        if (typeof loadForm === 'function') {
          loadForm(page);
        } else {
          window.location.href = 'index.php?view=' + (viewKey || '');
        }
      };
    }

    // Embed-detection: ตรวจสอบว่าถูกโหลดผ่าน loadForm ของ index.php หรือไม่
    // วิธีที่เชื่อถือได้ที่สุดคือดูว่า window.CURRENT_USER ถูก inject โดย index.php หรือเปล่า
    (function() {
      var isEmbedded = (typeof window.CURRENT_USER !== 'undefined')
                    || (document.getElementById('homeWrapper') &&
                        document.getElementById('homeWrapper').closest('#form') !== null);
      if (isEmbedded) {
        var w = document.getElementById('homeWrapper');
        if (w) w.classList.add('embedded');
        // sync theme จาก document root (index.php ตั้งค่าไว้แล้ว)
        var theme = document.documentElement.getAttribute('data-theme');
        if (theme && typeof window.applyTheme === 'function') {
          window.applyTheme(theme);
        }
      }
    })();

    // รับ theme จาก parent หรือ index.php ผ่าน postMessage
    window.addEventListener('message', function(event) {
      if (event.data && event.data.theme) {
        document.documentElement.setAttribute('data-theme', event.data.theme);
        var ctrl = document.querySelector('.theme-controller');
        if (ctrl) ctrl.checked = (event.data.theme === 'dark');
      }
    });
  </script>
<?php if (!$isEmbed): ?>
</body>
</html>
<?php endif; ?>