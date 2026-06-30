// get_my_scholarships.js - Optimized for index window loading
(function() {
    'use strict';
    
    // Theme synchronization
    function syncTheme() {
        try {
            // Try to get theme from parent window (when loaded in index.php)
            const parentTheme = window.parent?.document?.documentElement?.getAttribute('data-theme');
            if (parentTheme) {
                document.documentElement.setAttribute('data-theme', parentTheme);
                return;
            }
        } catch (e) {
            // Cross-origin or not in iframe, continue with fallback
        }
        
        // Fallback: get from localStorage or opener
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
        } else if (window.opener) {
            try {
                const openerTheme = window.opener.document.documentElement.getAttribute('data-theme');
                if (openerTheme) {
                    document.documentElement.setAttribute('data-theme', openerTheme);
                }
            } catch (e) {
                // Cross-origin, use default
            }
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
            // Check if we're loaded in index window
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
    
    // Handle links with target="_blank" - use loadForm if available
    function optimizeLinks() {
        const links = document.querySelectorAll('a[target="_blank"]');
        links.forEach(link => {
            const href = link.getAttribute('href');
            if (href && !href.startsWith('http') && !href.startsWith('//')) {
                link.addEventListener('click', function(e) {
                    try {
                        if (window.parent && window.parent !== window && window.parent.loadForm) {
                            e.preventDefault();
                            // Extract filename from href
                            const filename = href.split('?')[0];
                            if (filename.endsWith('.php')) {
                                window.parent.loadForm(filename);
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
        
        // Handle summary button
        const summaryBtn = document.getElementById('btn-summary-view');
        if (summaryBtn) {
            summaryBtn.addEventListener('click', function(e) {
                e.preventDefault();
                try {
                    if (window.parent && window.parent !== window && window.parent.loadForm) {
                        window.parent.loadForm('status_summary.php');
                    } else {
                        window.open('scholarship_summary.php', '_blank');
                    }
                } catch (err) {
                    window.open('scholarship_summary.php', '_blank');
                }
            });
        }
    }
    
    // Initialize function
    function initMyScholarshipsPage() {
        syncTheme();
        handleCloseButton();
        optimizeLinks();
        console.log('My Scholarships page initialized');
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMyScholarshipsPage);
    } else {
        initMyScholarshipsPage();
    }
    
    // Export for external initialization
    window.initMyScholarshipsPage = initMyScholarshipsPage;
})(); 