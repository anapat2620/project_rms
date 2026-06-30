<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <!-- Tailwind CSS ที่คอมไพล์ออกมาเป็น output.css (ถ้ามีไฟล์นี้อยู่แล้ว) -->
  <link href="./output.css" rel="stylesheet" />
  <!-- DaisyUI (ใช้งานผ่าน CDN) -->
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.24/dist/full.min.css" rel="stylesheet" />
  <title>ขอรับทุน - เลือกประเภท</title>

  <style>
    /* Animations (fade in/out) */
    @keyframes fadeOutLeft {
      from { opacity: 1; transform: translateX(0); }
      to   { opacity: 0; transform: translateX(-50px); }
    }
    @keyframes fadeInRight {
      from { opacity: 0; transform: translateX(50px); }
      to   { opacity: 1; transform: translateX(0); }
    }
    @keyframes fadeOutRight {
      from { opacity: 1; transform: translateX(0); }
      to   { opacity: 0; transform: translateX(50px); }
    }
    @keyframes fadeInLeft {
      from { opacity: 0; transform: translateX(-50px); }
      to   { opacity: 1; transform: translateX(0); }
    }
    .fade-out-left { animation: fadeOutLeft 0.5s forwards; }
    .fade-in-right { animation: fadeInRight 0.5s forwards; }
    .fade-out-right { animation: fadeOutRight 0.5s forwards; }
    .fade-in-left { animation: fadeInLeft 0.5s forwards; }
  </style>
</head>

<body>
  <!-- Top Navbar using DaisyUI -->
  <div class="navbar bg-blue-400 flex justify-between">
    <!-- Left side: Logo -->
    <div class="flex items-center">
      <img src="../Photo/mbslogo.png" alt="logombs" class="h-1/2" />
    </div>

    <!-- Right side: Search, Avatar, Home Button, Theme Toggle -->
    <div class="flex items-center gap-4">
      <!-- Search box -->
      <div class="form-control group">
        <div class="relative">
          <input
            type="text"
            placeholder="ค้นหา"
            class="input input-bordered pr-10 w-40 focus:w-64 transition-all duration-300"
          />
          <img
            src="../Photo/icons.png"
            alt="search-icon"
            class="w-5 h-5 absolute right-3 top-1/2 transform -translate-y-1/2 hover:scale-110 transition-all duration-300"
          />
        </div>
      </div>

      <!-- Avatar -->
      <div class="dropdown dropdown-end">
        <label tabindex="0" class="btn btn-ghost btn-circle avatar">
          <div class="w-10 rounded-full">
            <!-- รูปโปรไฟล์ -->
          </div>
        </label>
      </div>

      <!-- Home button -->
      <button class="btn btn-warning" id="btn-home">
        หน้าแรก
      </button>

      <!-- Theme Toggle -->
      <!-- Theme Toggle -->
      <label
        class="swap swap-rotate mr-3 ml-3 hover:animate-bounce hover:scale-110 transform transition-transform duration-300"
      >
        <input type="checkbox" class="theme-controller" value="dark" />
        <!-- sun icon -->
        <svg
          class="swap-off h-10 w-10 fill-current"
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
        >
          <path
            d="M5.64,17l-.71.71a1,1,0,0,0,0,1.41,1,1,0,0,0,1.41,0l.71-.71A1,1,0,0,0,5.64,17ZM5,12a1,1,0,0,0-1-1H3a1,1,0,0,0,0,2H4A1,1,0,0,0,5,12Zm7-7a1,1,0,0,0,1-1V3a1,1,0,0,0-2,0V4A1,1,0,0,0,12,5ZM5.64,7.05a1,1,0,0,0,.7.29,1,1,0,0,0,.71-.29,1,1,0,0,0,0-1.41l-.71-.71A1,1,0,0,0,4.93,6.34Zm12,.29a1,1,0,0,0,.7-.29l.71-.71a1,1,0,1,0-1.41-1.41L17,5.64a1,1,0,0,0,0,1.41A1,1,0,0,0,17.66,7.34ZM21,11H20a1,1,0,0,0,0,2h1a1,1,0,0,0,0-2Zm-9,8a1,1,0,0,0-1,1v1a1,1,0,0,0,2,0V20A1,1,0,0,0,12,19ZM18.36,17A1,1,0,0,0,17,18.36l.71.71a1,1,0,0,0,1.41,0,1,1,0,0,0,0-1.41ZM12,6.5A5.5,5.5,0,1,0,17.5,12,5.51,5.51,0,0,0,12,6.5Zm0,9A3.5,3.5,0,1,1,15.5,12,3.5,3.5,0,0,1,12,15.5Z"
          />
        </svg>
        <!-- moon icon -->
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

  <!-- Secondary Navbar -->
  <div class="navbar bg-neutral text-neutral-content">
    <div class="flex-1">
      <select id="funding-type" class="select select-bordered w-full max-w-xs">
        <option value="" disabled selected>เลือกประเภททุน</option>
        <option value="student.html">ทุนวิจัยของนิสิต</option>
        <option value="teacher.html">ทุนวิจัยของอาจารย์</option>
        <option value="personnel.html">ทุนวิจัยเพื่อพัฒนาองค์กรและพัฒนาบุคลากร</option>
      </select>

      <!-- เพิ่มปุ่มเช็คสถานะ พร้อม id="btn-status" -->
      <button class="ml-2 btn btn-info btn-xl font-bold cursor-pointer" id="btn-status">
        เช็คสถานะการยื่นทุน
      </button>
    </div>
  </div>

  <!-- พื้นที่สำหรับแสดงเนื้อหา (Partial) -->
  <div class="mt-5" id="form"></div>

  <script>
    // ฟังก์ชันโหลดไฟล์ Partial เข้ามาใน #form
    function loadForm(fileName) {
      fetch(fileName)
        .then(res => res.text())
        .then(data => {
          document.getElementById('form').innerHTML = data;
          initMultiStep(); // เรียกถ้ามี multi-step
        })
        .catch(err => console.error('Error loading form:', err));
    }

    // ฟังก์ชัน initMultiStep() (เรียกหลังโหลด partial)
    function initMultiStep() {
      const steps = document.querySelectorAll('.form-step');
      const progressBar = document.getElementById('progress-bar');
      if (!steps.length || !progressBar) return;

      let currentStep = 0;
      steps.forEach((step, i) => {
        if (i !== 0) step.classList.add('hidden');
      });
      progressBar.style.width = ((currentStep + 1) / steps.length * 100) + '%';

      function transitionToStep(newStep, outAnim, inAnim) {
        const current = steps[currentStep];
        current.classList.add(outAnim);
        current.addEventListener('animationend', function handler() {
          current.classList.remove(outAnim);
          current.classList.add('hidden');
          current.removeEventListener('animationend', handler);

          currentStep = newStep;
          const next = steps[newStep];
          next.classList.remove('hidden');
          next.classList.add(inAnim);
          next.addEventListener('animationend', function handler2() {
            next.classList.remove(inAnim);
            next.removeEventListener('animationend', handler2);
          });
          progressBar.style.width = ((newStep + 1) / steps.length * 100) + '%';
        });
      }

      document.querySelectorAll('.next-step').forEach(btn => {
        btn.addEventListener('click', () => {
          if (currentStep < steps.length - 1) {
            transitionToStep(currentStep + 1, 'fade-out-left', 'fade-in-right');
          }
        });
      });
      document.querySelectorAll('.prev-step').forEach(btn => {
        btn.addEventListener('click', () => {
          if (currentStep > 0) {
            transitionToStep(currentStep - 1, 'fade-out-right', 'fade-in-left');
          }
        });
      });
    }

    // โหลดหน้า home.html เป็นค่าเริ่มต้น
    window.addEventListener('DOMContentLoaded', () => {
      loadForm('home.html');
    });

    // ปุ่มหน้าแรก
    document.getElementById('btn-home').addEventListener('click', () => {
      loadForm('home.html');
    });

    // ปุ่มเช็คสถานะ => โหลด status.html
    document.getElementById('btn-status').addEventListener('click', () => {
      loadForm('status.html');
    });

    // เมื่อเปลี่ยนค่าใน dropdown ให้โหลดฟอร์มใหม่
    document.getElementById('funding-type').addEventListener('change', function() {
      loadForm(this.value);
    });

    // Theme Toggle
    function applyTheme(theme) {
      document.documentElement.setAttribute('data-theme', theme);
      const themeController = document.querySelector('.theme-controller');
      if (themeController) themeController.checked = (theme === 'dark');
    }

    let savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
      applyTheme(savedTheme);
    } else {
      const hour = new Date().getHours();
      applyTheme((hour >= 18 || hour < 6) ? 'dark' : 'light');
    }

    document.querySelector('.theme-controller').addEventListener('change', function() {
      const theme = this.checked ? 'dark' : 'light';
      applyTheme(theme);
      localStorage.setItem('theme', theme);
    });
  </script>
</body>
</html>
