// home.js - Optimized version
// ฟังก์ชันสำหรับดึงข้อมูลทุน (removed - unused function)

// Theme management (consolidated and optimized)
(function() {
  'use strict';
  
  // Cache DOM elements
  let themeController = null;
  let scholarshipFrame = null;
  
  // Status class mapping (optimized with object lookup)
  const STATUS_CLASSES = {
    'อนุมัติ': 'badge-success',
    'ปฏิเสธ': 'badge-error',
    'รออนุมัติ': 'badge-warning'
  };
  
  // Theme application function
  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    
    if (!themeController) {
      themeController = document.querySelector('.theme-controller');
    }
    if (themeController) {
      themeController.checked = (theme === 'dark');
    }
    
    // ส่งข้อมูลธีมไปยัง iframe
    if (!scholarshipFrame) {
      scholarshipFrame = document.getElementById('scholarshipFrame');
    }
    if (scholarshipFrame?.contentWindow) {
      scholarshipFrame.contentWindow.postMessage({ theme: theme }, '*');
    }
  }
  
  // Initialize theme on DOM ready
  function initTheme() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
      applyTheme(savedTheme);
    } else {
      const hour = new Date().getHours();
      applyTheme((hour >= 18 || hour < 6) ? 'dark' : 'light');
    }
    
    themeController = document.querySelector('.theme-controller');
    if (themeController) {
      themeController.addEventListener('change', function() {
        const theme = this.checked ? 'dark' : 'light';
        applyTheme(theme);
        localStorage.setItem('theme', theme);
      });
    }
  }
  
  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTheme);
  } else {
    initTheme();
  }
  
  // Export for external use if needed
  window.applyTheme = applyTheme;
})(); 