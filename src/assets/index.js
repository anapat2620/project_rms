document.addEventListener('DOMContentLoaded', function() {
  const fundingTypeSelect = document.getElementById('funding-type');
  const formDiv = document.getElementById('form'); // อ้างอิงถึง div ที่ใช้โหลดฟอร์ม
  const searchInput = document.getElementById('search-grant'); // ช่องค้นหาใน Navbar
  const searchIcon = document.getElementById('search-icon'); // ไอคอนค้นหาใน Navbar
  const themeController = document.querySelector('.theme-controller');

  // เพิ่มฟังก์ชันสำหรับการส่งฟอร์มอนุมัติ/ปฏิเสธ
  window.submitAction = function(requestId, action) {
      const form = document.getElementById('form_' + requestId);
      const actionInput = document.getElementById('action_' + requestId);
      const comment = form.querySelector('textarea[name="comment"]');
      
      if (!form) {
          console.error('Form not found for request ID:', requestId);
          alert('เกิดข้อผิดพลาด: ไม่พบฟอร์มที่ต้องการ');
          return;
      }
      
      if (!actionInput) {
          console.error('Action input not found for request ID:', requestId);
          alert('เกิดข้อผิดพลาด: ไม่พบฟิลด์การดำเนินการ');
          return;
      }
      
      if (!comment || !comment.value.trim()) {
          alert('กรุณากรอกความคิดเห็น/ข้อเสนอแนะก่อนดำเนินการ');
          if (comment) comment.focus();
          return;
      }
      
      actionInput.value = action;
      
      // แสดง loading state
      const submitButton = form.querySelector('button[type="submit"]');
      if (submitButton) {
          const originalText = submitButton.textContent;
          submitButton.textContent = 'กำลังดำเนินการ...';
          submitButton.disabled = true;
          
          // รีเซ็ตหลังจาก 5 วินาที (fallback)
          setTimeout(() => {
              submitButton.textContent = originalText;
              submitButton.disabled = false;
          }, 5000);
      }
      
      form.submit();
  };

  // ฟังก์ชันสำหรับตรวจสอบการทำงานของระบบ
  window.checkSystemHealth = function() {
      const checks = {
          'Form element': !!document.getElementById('form'),
          'Search input (Navbar)': !!document.getElementById('search-grant'),
          'Search icon (Navbar)': !!document.getElementById('search-icon'),
          'Theme controller': !!document.querySelector('.theme-controller'),
          'Home button': !!document.getElementById('btn-home'),
          'Status button': !!document.getElementById('btn-status'),
          'Dropdown (funding-type)': !!document.getElementById('funding-type')
      };
      
      console.log('=== System Health Check ===');
      Object.entries(checks).forEach(([element, exists]) => {
          console.log(`${element}: ${exists ? '✅' : '❌'}`);
      });
      console.log('==========================');
      
      return checks;
  };

  function bindMultiStepFormNavigation() {
      const form = document.querySelector('#form #multi-step-form');
      if (!form || form.dataset.navBound === '1') return;
      form.dataset.navBound = '1';

      form.addEventListener('click', function(event) {
          const nextButton = event.target.closest('.next-step');
          const prevButton = event.target.closest('.prev-step');
          if (!nextButton && !prevButton) return;

          event.preventDefault();

          const steps = Array.prototype.slice.call(form.querySelectorAll('.form-step'));
          let currentStep = steps.findIndex(function(step) {
              return !step.classList.contains('hidden');
          });
          if (currentStep < 0) currentStep = 0;

          if (nextButton) {
              if (steps[currentStep]) {
                  steps[currentStep].classList.add('hidden');
              }
              currentStep += 1;
              if (steps[currentStep]) {
                  steps[currentStep].classList.remove('hidden');
              }
          } else if (prevButton) {
              if (steps[currentStep]) {
                  steps[currentStep].classList.add('hidden');
              }
              currentStep -= 1;
              if (steps[currentStep]) {
                  steps[currentStep].classList.remove('hidden');
              }
          }

          const progressBar = document.getElementById('progress-bar');
          if (progressBar && steps.length) {
              const progress = ((currentStep + 1) / steps.length) * 100;
              progressBar.style.width = `${progress}%`;
          }

          const progressSteps = document.querySelectorAll('.step');
          progressSteps.forEach(function(step, index) {
              if (index === currentStep) {
                  step.classList.add('active', 'text-blue-600', 'font-semibold');
                  step.classList.remove('text-gray-500');
                  step.setAttribute('aria-current', 'step');
              } else {
                  step.classList.remove('active', 'text-blue-600', 'font-semibold');
                  step.classList.add('text-gray-500');
                  step.removeAttribute('aria-current');
              }
          });
      });
  }

  // **จุดที่แก้ไข: เพิ่ม `initialSearchQuery` parameter และ logic การจัดการการกรอง**
  window.loadForm = async function(fileName, initialSearchQuery = '') { 
      return measurePerformance(`Loading ${fileName}`, async () => {
          const formElement = document.getElementById('form');
          if (!formElement) {
              throw new Error('Form element not found');
          }

          formElement.innerHTML = `
              <div class="loading-overlay">
                  <div class="flex flex-col items-center gap-4">
                      <div class="loading w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full"></div>
                      <p class="text-lg">กำลังโหลด ${fileName}...</p>
                  </div>
              </div>
          `;

          try {
              const isConnected = await checkConnection();
              if (!isConnected) {
                  throw new Error('ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้');
              }

              const normalizedFileName = (fileName || '').split('?')[0];

              // กำหนดว่าหน้านี้เป็นหน้าตารางที่สามารถกรองได้หรือไม่
              const isTablePage = ['status.php', 'get_my_scholarships.php', 'approve_requests.php'].includes(normalizedFileName);
              let fetchFileName = fileName;

              // หากเป็นหน้าตารางและมีคำค้นหาเริ่มต้น ให้ส่งไปที่ PHP ด้วย
              if (isTablePage && initialSearchQuery) {
                  fetchFileName = `${fileName}?search=${encodeURIComponent(initialSearchQuery)}`;
              }

              const response = await fetch(fetchFileName, {
                  method: 'GET',
                  cache: 'no-cache',
                  headers: {
                      'Cache-Control': 'no-cache'
                  }
              });
              
              if (!response.ok) {
                  throw new Error(`HTTP error! status: ${response.status}`);
              }
              
              const rawHtml = await response.text();
              let injectedHtml = rawHtml;
              try {
                  if (rawHtml.indexOf('<body') !== -1) {
                      const parser = new DOMParser();
                      const doc = parser.parseFromString(rawHtml, 'text/html');
                      injectedHtml = doc.body ? doc.body.innerHTML : rawHtml;
                  }
              } catch (_) {
                  injectedHtml = rawHtml;
              }
              formElement.innerHTML = injectedHtml;
              bindMultiStepFormNavigation();

              // โหลด CSS/JS เฉพาะหน้าแบบ dynamic
              await loadPageSpecificAssets(normalizedFileName);

              // Force re-init หลัง inject HTML เพื่อให้ฟอร์มเช่น student/teacher/personnel bind event ถูกต้อง
              if (normalizedFileName === 'student.php' && window.initStudentMultiStep && typeof window.initStudentMultiStep === 'function') {
                  setTimeout(() => {
                      if (document.querySelector('#form #multi-step-form')) {
                          window.initStudentMultiStep();
                      }
                  }, 0);
              } else if (normalizedFileName === 'teacher.php' && window.initTeacherMultiStep && typeof window.initTeacherMultiStep === 'function') {
                  setTimeout(() => {
                      if (document.querySelector('#form #multi-step-form')) {
                          window.initTeacherMultiStep();
                      }
                  }, 0);
              } else if (normalizedFileName === 'personnel.php' && window.initPersonnelMultiStep && typeof window.initPersonnelMultiStep === 'function') {
                  setTimeout(() => {
                      if (document.querySelector('#form #multi-step-form')) {
                          window.initPersonnelMultiStep();
                      }
                  }, 0);
              }

              // จัดการหน้าตาราง: ตั้งค่า search input และ event listener
              if (isTablePage) {
                  if (searchInput) {
                      searchInput.removeEventListener('input', window.filterStatusTable);
                      searchInput.addEventListener('input', window.filterStatusTable);
                      searchInput.value = initialSearchQuery || '';
                      if (initialSearchQuery) {
                          setTimeout(() => {
                              if (typeof window.filterStatusTable === 'function') {
                                  window.filterStatusTable.call(searchInput);
                              }
                          }, 0);
                      }
                  }
              } else if (searchInput) {
                  searchInput.value = '';
                  searchInput.removeEventListener('input', window.filterStatusTable);
              }

              // จัดการหน้าเฉพาะ: home.php และ status_summary.php
              if (normalizedFileName === 'home.php' && !window.Chart) {
                  await loadChartJS();
              } else if (normalizedFileName === 'status_summary.php') {
                  // ตรวจสอบและ retry ถ้าจำเป็น (รวม logic เดิมที่ซ้ำซ้อน)
                  const checkStatusSummary = () => {
                      const hasJson = !!document.getElementById('status-summary-data');
                      const hasCanvases = !!document.getElementById('statusPie') && !!document.getElementById('trendLine');
                      return hasJson && hasCanvases;
                  };
                  
                  if (!checkStatusSummary() && !window.__SS_RETRY__) {
                      window.__SS_RETRY__ = true;
                      setTimeout(async () => {
                          await loadPageSpecificAssets(normalizedFileName);
                          setTimeout(() => {
                              if (!checkStatusSummary()) {
                                  loadForm(fileName).catch(err => console.error('Retry status_summary failed:', err));
                              } else {
                                  window.__SS_RETRY__ = false;
                              }
                          }, 150);
                      }, 200);
                  } else {
                      window.__SS_RETRY__ = false;
                  }
              }

              return injectedHtml;
          } catch (error) {
              console.error('Error loading form:', error);
              showNotification('ท่านไม่มีสิทธิ์เข้าถึงหน้านี้', 'error');
          }
      });
  };

  // ฟังก์ชันโหลด CSS/JS เฉพาะหน้า
  // **จุดที่แก้ไข: เพิ่ม `init` function สำหรับหน้าตารางอื่นๆ**
  window.loadPageSpecificAssets = async function(fileName) {
      const normalizedFileName = (fileName || '').split('?')[0];
      const assetMap = {
          'student.php': { css: 'assets/student.css', js: 'assets/student.js', init: 'initStudentMultiStep' },
          'teacher.php': { css: 'assets/teacher.css', js: 'assets/teacher.js', init: 'initTeacherMultiStep' },
          'personnel.php': { css: 'assets/personnel.css', js: 'assets/personnel.js', init: 'initPersonnelMultiStep' },
          'status.php': { css: 'assets/status.css', js: 'assets/status.js', init: 'initStatusPage' }, // กำหนด init function
          'home.php': { css: 'assets/home.css', js: 'assets/home.js', init: 'loadScholarshipData' },
          'get_my_scholarships.php': { css: 'assets/get_my_scholarships.css', js: 'assets/get_my_scholarships.js', init: 'initMyScholarshipsPage' }, // เพิ่ม init function
          'approve_requests.php': { css: 'assets/approve_requests.css', js: 'assets/approve_requests.js', init: 'initApproveRequestsPage' },
          'status_summary.php': { css: 'assets/status_summary.css', js: 'assets/status_summary.js', init: 'initStatusSummary' }
      };

      if (assetMap[normalizedFileName]) {
          const assets = assetMap[normalizedFileName];
          
          if (assets.css) {
              const existingCss = document.querySelector(`link[href*='${assets.css.split('/').pop()}']`);
              if (existingCss) existingCss.remove();
              const link = document.createElement('link');
              link.rel = 'stylesheet';
              link.href = assets.css + '?v=' + Date.now();
              document.head.appendChild(link);
              console.log(`CSS injected: ${assets.css}`);
          }
          
          if (assets.js) {
              // Ensure Chart.js for status_summary
              if (normalizedFileName === 'status_summary.php' && !window.Chart) {
                  await window.loadChartJS();
              }
              await loadScriptDynamic(assets.js, assets.init);
          }
      }
  };

  // โหลด script แบบ dynamic แล้วเรียก init หลังโหลดเสร็จ
  // **จุดที่แก้ไข: ปรับปรุงการลบ script เดิมและการเรียก init function**
  window.loadScriptDynamic = async function(src, initFnName) {
      // ลบ script เดิมถ้ามี (ใช้ querySelectorAll เพื่อลบทั้งหมดที่อาจมี src เหมือนกัน)
      document.querySelectorAll(`script[src*='${src.split('/').pop()}']`).forEach(s => s.remove());
      
      await new Promise((resolve, reject) => {
          const script = document.createElement('script');
          script.src = src + '?v=' + Date.now(); // bust cache
          script.onload = resolve;
          script.onerror = () => reject(new Error(`Failed to load script: ${src}`));
          document.head.appendChild(script);
      });
      console.log(`Script loaded: ${src}`);

      // เรียกฟังก์ชัน init หลังจาก script โหลดและรัน
      if (initFnName && window[initFnName] && typeof window[initFnName] === 'function') {
          const runInit = () => {
              window[initFnName]();
              console.log(`Called init function: ${initFnName}`);
          };

          if (document.readyState === 'loading') {
              document.addEventListener('DOMContentLoaded', runInit, { once: true });
          } else {
              setTimeout(runInit, 0);
          }
      } else if (initFnName) {
          console.warn(`Init function '${initFnName}' not found or not a function for script '${src}'.`);
      }
  };

  // ฟังก์ชันสำหรับโหลด Chart.js
  window.loadChartJS = async function() {
      if (!window.Chart) {
          try {
              await new Promise((resolve, reject) => {
                  const script = document.createElement('script');
                  script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                  script.onload = resolve;
                  script.onerror = () => reject(new Error('Failed to load Chart.js'));
                  document.head.appendChild(script);
              });
              console.log('Chart.js loaded successfully');
          } catch (error) {
              console.error('Error loading Chart.js:', error);
              const formElement = document.getElementById('form');
              if (formElement) {
                  formElement.innerHTML += `
                      <div class="alert alert-warning mt-4">
                          <div class="flex-1">
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mx-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                              </svg>
                              <label>ไม่สามารถโหลดกราฟได้ กรุณารีเฟรชหน้าเว็บ</label>
                          </div>
                      </div>`;
              }
          }
      }
  };

  // ฟังก์ชันสำหรับ performance monitoring (optimized)
  window.measurePerformance = async function(operation, fn) {
      const start = performance.now();
      try {
          const result = await fn();
          const end = performance.now();
          const duration = (end - start).toFixed(2);
          if (duration > 1000) { // Log warnings for slow operations
              console.warn(`${operation} took ${duration}ms (slow)`);
          } else {
              console.log(`${operation} completed in ${duration}ms`);
          }
          return result;
      } catch (error) {
          const end = performance.now();
          console.error(`${operation} failed after ${(end - start).toFixed(2)}ms:`, error);
          throw error;
      }
  };

  // ฟังก์ชันสำหรับตรวจสอบการเชื่อมต่อ
  window.checkConnection = async function() {
      try {
          const response = await fetch('home.php', { 
              method: 'HEAD',
              cache: 'no-cache',
              headers: {
                  'Cache-Control': 'no-cache'
              }
          });
          return response.ok;
      } catch (error) {
          console.error('Connection check failed:', error);
          return false;
      }
  };

  // ฟังก์ชันสำหรับแสดงข้อความแจ้งเตือน (optimized)
  window.showNotification = function(message, type = 'info') {
      // ลบ notification เดิมถ้ามี (ป้องกันการซ้อนทับ)
      const existing = document.querySelector('.notification-alert');
      if (existing) existing.remove();
      
      const notification = document.createElement('div');
      notification.className = `alert alert-${type} fixed top-4 right-4 z-50 max-w-sm notification-alert`;
      notification.setAttribute('role', 'alert');
      notification.innerHTML = `
          <div class="flex-1">
              <label>${message}</label>
          </div>
          <div class="flex-none">
              <button class="btn btn-sm btn-ghost" aria-label="ปิด">✕</button>
          </div>
      `;
      
      // ใช้ event delegation สำหรับปุ่มปิด
      notification.querySelector('button').addEventListener('click', () => notification.remove());
      
      document.body.appendChild(notification);
      
      // Auto remove after 5 seconds
      setTimeout(() => notification.remove(), 5000);
  };

  // ฟังก์ชันสำหรับ filter ตาราง status (optimized)
  window.filterStatusTable = function() {
      const searchTerm = this.value.toLowerCase().trim();
      const table = formDiv?.querySelector('.table');
      
      if (!table) {
          console.warn('Table not found for filtering in current view (inside #form).');
          return;
      }
      
      const rows = table.querySelectorAll('tbody tr');
      if (!rows.length) return;
      
      let visibleCount = 0;
      const searchLower = searchTerm.toLowerCase();
      
      // ใช้ requestAnimationFrame เพื่อ optimize performance
      requestAnimationFrame(() => {
          rows.forEach(row => {
              const cells = row.querySelectorAll('td');
              const textContent = Array.from(cells, td => (td.textContent || td.innerText).trim())
                                       .join(' ')
                                       .toLowerCase();
              
              const isVisible = !searchLower || textContent.includes(searchLower);
              row.style.display = isVisible ? '' : 'none';
              if (isVisible) visibleCount++;
          });
          
          const resultCount = formDiv.querySelector('#result-count');
          if (resultCount) {
              resultCount.textContent = `พบ ${visibleCount} รายการ`;
          }
      });
  };

  // ฟังก์ชันสำหรับ debug
  window.debugInfo = function() {
      console.log('=== Debug Information ===');
      console.log('Current theme:', document.documentElement.getAttribute('data-theme'));
      console.log('Form element:', document.getElementById('form'));
      console.log('Search input (Navbar):', document.getElementById('search-grant'));
      console.log('Theme controller:', document.querySelector('.theme-controller'));
      console.log('All buttons:', document.querySelectorAll('button[id^="btn-"]'));
      console.log('========================');
  };

  // ฟังก์ชันสำหรับใช้ theme
  window.applyTheme = function(theme) {
      document.documentElement.setAttribute('data-theme', theme);
      localStorage.setItem('theme', theme);
      
      const themeController = document.querySelector('.theme-controller');
      if (themeController) {
          themeController.checked = (theme === 'dark');
      }
      
      const iframes = document.querySelectorAll('iframe');
      iframes.forEach(iframe => {
          if (iframe.contentWindow) {
              iframe.contentWindow.postMessage({ theme: theme }, '*');
          }
      });
  };

  // --- ส่วนของ Event Listeners หลักที่ทำงานเมื่อ DOMContentLoaded ---

  // **จุดที่แก้ไข: เปลี่ยน `DOMContentLoaded` เป็น `load` และปรับ logic การเรียก `loadScholarshipData`**
  window.addEventListener('load', () => { 
      checkSystemHealth(); 
      
      loadForm('home.php').then(() => {
          console.log('home.php loaded successfully on initial load.');
          // loadScholarshipData จะถูกเรียกโดย loadForm เองเมื่อ fileName เป็น home.php
      }).catch(error => {
          console.error('Error loading home.php on initial load:', error);
      });
  });

  // **จุดที่แก้ไข: เพิ่ม logic สำหรับการค้นหาจาก Navbar**
  function performSearch() {
      if (!window.CURRENT_USER?.canApprove) {
          showNotification('สิทธิ์ไม่เพียงพอ: การค้นหาสำหรับผู้มีสิทธิ์อนุมัติเท่านั้น', 'warning');
          return;
      }
      const query = searchInput.value.trim();
      if (query) {
          // เมื่อค้นหา ให้โหลดหน้าสถานะ (status.php) พร้อมส่งคำค้นหา
          loadForm('status.php', query).catch(error => {
              console.error('Error during search form load:', error);
          });
      } else {
          showNotification('กรุณาป้อนคำค้นหา', 'warning');
      }
  }

  if (searchIcon) {
      searchIcon.addEventListener('click', performSearch);
  }
  if (searchInput) {
      searchInput.addEventListener('keypress', function(e) {
          if (e.key === 'Enter') {
              performSearch();
          }
      });
  }

  // เพิ่ม event listeners สำหรับปุ่มต่างๆ (optimized with event delegation)
  const buttonMappings = {
      'btn-student': 'student.php',
      'btn-teacher': 'teacher.php',
      'btn-personnel': 'personnel.php',
      'btn-home': 'home.php', 
      'btn-status': 'status.php',
      'btn-history': 'get_my_scholarships.php',
      'btn-approve': 'approve_requests.php',
      'btn-download': 'mbs_downloads.php',
      'btn-summary': 'status_summary.php',
      'btn-status-summary': 'status_summary.php'
  };

  // Cache approver-only pages
  const APPROVER_ONLY_PAGES = ['status.php', 'approve_requests.php'];

  // ใช้ event delegation เพื่อลดจำนวน event listeners
  document.addEventListener('click', (e) => {
      const buttonId = e.target.closest('[id^="btn-"]')?.id;
      if (!buttonId || !buttonMappings[buttonId]) return;
      
      const fileName = buttonMappings[buttonId];
      
      if (APPROVER_ONLY_PAGES.includes(fileName) && !window.CURRENT_USER?.canApprove) {
          showNotification('สิทธิ์ไม่เพียงพอ: หน้านี้สำหรับผู้มีสิทธิ์อนุมัติเท่านั้น', 'warning');
          return;
      }

      loadForm(fileName).catch(error => {
          console.error(`Error loading ${fileName}:`, error);
      });
  });

  // เพิ่ม event listener สำหรับ dropdown 'เลือกประเภททุน'
  if (fundingTypeSelect) {
      fundingTypeSelect.addEventListener('change', function() {
          const selectedValue = this.value;
          if (selectedValue) {
              const normalizedValue = selectedValue.split('?')[0];
              if (['student.php', 'teacher.php', 'personnel.php'].includes(normalizedValue)) {
                  window.location.href = `index.php?view=${encodeURIComponent(selectedValue)}`;
                  return;
              }
              loadForm(selectedValue).catch(error => {
                  console.error(`Error loading ${selectedValue}:`, error);
              });
          }
      });
  }

  // เพิ่ม event listener สำหรับ theme toggle
  if (themeController) {
      themeController.addEventListener('change', function() {
          const theme = this.checked ? 'dark' : 'light';
          applyTheme(theme);
      });
  }

  // โหลด theme ที่บันทึกไว้
  const savedTheme = localStorage.getItem('theme');
  if (savedTheme) {
      applyTheme(savedTheme);
  } else {
      const hour = new Date().getHours();
      const defaultTheme = (hour >= 18 || hour < 6) ? 'dark' : 'light';
      applyTheme(defaultTheme);
  }

  // Unhandled promise rejection handler
  window.addEventListener('unhandledrejection', function(event) {
      console.error('Unhandled promise rejection:', event.reason);
      showNotification('เกิดข้อผิดพลาดในการโหลดข้อมูล', 'error');
  });

  // เรียกใช้ debug function ในโหมด development
  if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
      console.log('Debug mode enabled. Use window.debugInfo() to see system information.');
  }

}); // ปิด document.addEventListener('DOMContentLoaded')