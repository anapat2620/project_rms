// mbs_downloads.js - Optimized for index window loading
(function() {
    'use strict';
    
    // Modal PDF viewer
    function openPdfModal(path) {
        const modal = document.getElementById('pdfModal');
        const frame = document.getElementById('pdfFrame');
        if (modal && frame) {
            frame.src = path;
            modal.showModal();
            modal.addEventListener('close', function() { 
                frame.src = ''; 
            }, { once: true });
        }
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
        } else {
            // Default to light theme
            document.documentElement.setAttribute('data-theme', 'light');
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
    
    // Handle external links (Google Drive) - keep target="_blank"
    function handleExternalLinks() {
        const externalLinks = document.querySelectorAll('a[href^="http"], a[href^="//"]');
        externalLinks.forEach(link => {
            // Keep default behavior for external links
            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener noreferrer');
        });
    }
    
    // Initialize function
    function initMbsDownloadsPage() {
        syncTheme();
        handleCloseButton();
        handleExternalLinks();
        console.log('MBS Downloads page initialized');
    }
    
    // Expose functions globally
    window.openPdfModal = openPdfModal;
    window.initMbsDownloadsPage = initMbsDownloadsPage;
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMbsDownloadsPage);
    } else {
        initMbsDownloadsPage();
    }
})(); 