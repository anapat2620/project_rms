// approve_requests.js - Optimized for index window loading
(function() {
    'use strict';
    
    // Modal functions
    function openApproveModal(id) {
        const approveModal = document.getElementById('approveModal');
        const approveRequestId = document.getElementById('approveRequestId');
        if (approveModal && approveRequestId) {
            approveRequestId.value = id;
            approveModal.showModal();
        }
    }

    function openRejectModal(id) {
        const rejectModal = document.getElementById('rejectModal');
        const rejectRequestId = document.getElementById('rejectRequestId');
        if (rejectModal && rejectRequestId) {
            rejectRequestId.value = id;
            rejectModal.showModal();
        }
    }

    function validateApproveComment() {
        const approveComment = document.getElementById('approveComment');
        if (!approveComment) return false;
        const comment = approveComment.value.trim();
        if (!comment) {
            alert('กรุณากรอกความคิดเห็นก่อนยืนยันการอนุมัติ');
            approveComment.focus();
            return false;
        }
        return true;
    }

    function validateRejectComment() {
        const rejectComment = document.getElementById('rejectComment');
        if (!rejectComment) return false;
        const comment = rejectComment.value.trim();
        if (!comment) {
            alert('กรุณากรอกเหตุผลการปฏิเสธ');
            rejectComment.focus();
            return false;
        }
        return true;
    }
    
    // Theme synchronization
    function syncTheme() {
        try {
            const parentTheme = window.parent?.document?.documentElement?.getAttribute('data-theme');
            if (parentTheme) {
                document.documentElement.setAttribute('data-theme', parentTheme);
                return;
            }
        } catch (e) {
            // Cross-origin or not in iframe
        }
        
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
        }
    }
    
    // Listen for theme changes from parent
    window.addEventListener('message', function(event) {
        if (event.data && event.data.theme) {
            document.documentElement.setAttribute('data-theme', event.data.theme);
        }
    });
    
    // Handle window.close() - navigate back to home when loaded in index window
    function handleCloseButton() {
        const closeHandler = function(e) {
            e.preventDefault();
            e.stopPropagation();
            try {
                if (window.parent && window.parent !== window && window.parent.loadForm) {
                    window.parent.loadForm('home.php');
                } else {
                    window.close();
                }
            } catch (err) {
                window.close();
            }
        };
        
        // Handle button with ID
        const closeBtn = document.getElementById('btn-close-page');
        if (closeBtn) {
            closeBtn.removeAttribute('onclick');
            closeBtn.addEventListener('click', closeHandler);
        }
        
        // Handle buttons with onclick attribute
        const closeButtons = document.querySelectorAll('button[onclick*="window.close"]');
        closeButtons.forEach(btn => {
            if (btn.id !== 'btn-close-page') {
                btn.removeAttribute('onclick');
                btn.addEventListener('click', closeHandler);
            }
        });
    }
    
    // Handle links with target="_blank" - optimize for index window
    function optimizeLinks() {
        const links = document.querySelectorAll('a[target="_blank"]');
        links.forEach(link => {
            const href = link.getAttribute('href');
            if (href && !href.startsWith('http') && !href.startsWith('//')) {
                link.addEventListener('click', function(e) {
                    try {
                        if (window.parent && window.parent !== window && window.parent.loadForm) {
                            e.preventDefault();
                            const filename = href.split('?')[0];
                            if (filename.endsWith('.php')) {
                                // Preserve query parameters
                                const queryString = href.includes('?') ? href.split('?')[1] : '';
                                const fullPath = queryString ? `${filename}?${queryString}` : filename;
                                window.parent.loadForm(fullPath);
                            } else {
                                window.open(href, '_blank');
                            }
                        }
                    } catch (err) {
                        // Let default behavior happen
                    }
                });
            }
        });
    }
    
    // Handle form submission - reload page after successful submission
    function handleFormSubmission() {
        const forms = document.querySelectorAll('form[action="status.php"]');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                // Form will submit normally, but we can add loading state
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'กำลังดำเนินการ...';
                }
            });
        });
    }
    
    // Initialize function
    function initApproveRequestsPage() {
        syncTheme();
        handleCloseButton();
        optimizeLinks();
        handleFormSubmission();
        console.log('Approve Requests page initialized');
    }
    
    // Expose functions globally
    window.openApproveModal = openApproveModal;
    window.openRejectModal = openRejectModal;
    window.validateApproveComment = validateApproveComment;
    window.validateRejectComment = validateRejectComment;
    window.initApproveRequestsPage = initApproveRequestsPage;
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initApproveRequestsPage);
    } else {
        initApproveRequestsPage();
    }
})(); 