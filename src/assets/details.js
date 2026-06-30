// details.js

// Handle success message and page refresh
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    
    // Handle fund update success
    if (urlParams.get('status') === 'fund_updated' && urlParams.get('success') === '1') {
        const phase = urlParams.get('phase') || 'ข้อมูล';
        const refresh = urlParams.get('refresh');
        
        // Get request_id from data attribute or URL
        const requestIdElement = document.body.getAttribute('data-request-id');
        const requestId = requestIdElement || urlParams.get('request_id');
        
        showNotification(`อัปเดตสถานะการจ่ายเงินเรียบร้อยแล้ว (${phase})`, 'success');
        
        // Clean URL and refresh page once
        if (requestId) {
            window.history.replaceState({}, document.title, window.location.pathname + '?request_id=' + requestId);
        }
        
        // Refresh page once after showing notification
        if (refresh === '1') {
            setTimeout(() => {
                window.location.reload();
            }, 2000); // Refresh after 2 seconds
        }
    }
    
    // Handle period closed success
    if (urlParams.get('status') === 'period_closed' && urlParams.get('success') === '1') {
        const refresh = urlParams.get('refresh');
        
        // Get request_id from data attribute or URL
        const requestIdElement = document.body.getAttribute('data-request-id');
        const requestId = requestIdElement || urlParams.get('request_id');
        
        showNotification('ปิดงวดเรียบร้อยแล้ว - ทุกงวดได้ถูกจ่ายเงินทันที', 'success');
        
        // Clean URL and refresh page once
        if (requestId) {
            window.history.replaceState({}, document.title, window.location.pathname + '?request_id=' + requestId);
        }
        
        // Refresh page once after showing notification
        if (refresh === '1') {
            setTimeout(() => {
                window.location.reload();
            }, 2000); // Refresh after 2 seconds
        }
    }

    // Form validation and loading state for individual installment forms
    const forms = document.querySelectorAll('form[method="POST"]:not(#close-period-form)');
    
    forms.forEach(form => {
        const submitButton = form.querySelector('button[type="submit"]');
        
        if (submitButton) {
            form.addEventListener('submit', function(e) {
                // Add loading state
                submitButton.disabled = true;
                const originalText = submitButton.innerHTML;
                submitButton.innerHTML = `
                    <svg class="w-4 h-4 mr-2 inline-block animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    กำลังบันทึก...
                `;
                
                // Basic validation
                const statusSelect = form.querySelector('select[name="fund_status"]');
                
                if (!statusSelect || !statusSelect.value) {
                    e.preventDefault();
                    showNotification('กรุณาเลือกสถานะการจ่ายเงิน', 'error');
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                }
            });
        }
    });
    
    // Handle Close Period button
    const closePeriodForm = document.getElementById('close-period-form');
    if (closePeriodForm) {
        const closePeriodButton = closePeriodForm.querySelector('button[type="submit"]');
        
        closePeriodForm.addEventListener('submit', function(e) {
            // Confirm before closing period
            if (!confirm('คุณแน่ใจหรือไม่ว่าต้องการปิดงวด? การดำเนินการนี้จะทำให้ทุกงวดที่ยังไม่จ่ายเงินถูกจ่ายเงินทันทีและไม่สามารถแก้ไขได้อีกต่อไป')) {
                e.preventDefault();
                return false;
            }
            
            // Add loading state
            if (closePeriodButton) {
                closePeriodButton.disabled = true;
                const originalText = closePeriodButton.innerHTML;
                closePeriodButton.innerHTML = `
                    <svg class="w-5 h-5 mr-2 inline-block animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    กำลังปิดงวด...
                `;
            }
        });
    }
});

// Notification system
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification-box');
    existingNotifications.forEach(notification => notification.remove());

    // Create notification box
    const notification = document.createElement('div');
    notification.className = `notification-box fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transform transition-all duration-300 ease-in-out translate-x-full`;
    
    // Set colors based on type
    if (type === 'success') {
        notification.className += ' bg-green-100 border border-green-400 text-green-700';
    } else if (type === 'error') {
        notification.className += ' bg-red-100 border border-red-400 text-red-700';
    } else {
        notification.className += ' bg-blue-100 border border-blue-400 text-blue-700';
    }

    notification.innerHTML = `
        <div class="flex items-center">
            <div class="flex-shrink-0">
                ${type === 'success' ? 
                    '<svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>' :
                    '<svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>'
                }
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium">${message}</p>
            </div>
            <div class="ml-auto pl-3">
                <button onclick="this.parentElement.parentElement.parentElement.remove()" class="inline-flex text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600 transition ease-in-out duration-150">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    `;

    // Add to page
    document.body.appendChild(notification);

    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);

    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 300);
    }, 5000);
}

