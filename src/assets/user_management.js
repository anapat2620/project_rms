// user_management.js
// Search functionality
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('.user-row');
        
        rows.forEach(row => {
            const username = row.getAttribute('data-username');
            const email = row.getAttribute('data-email');
            const faculty = row.getAttribute('data-faculty');
            const position = row.getAttribute('data-position');
            
            if (username.includes(searchTerm) || email.includes(searchTerm) || 
                faculty.includes(searchTerm) || position.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('editModal');
        if (event.target === modal) {
            closeModal();
        }
    }

    // Auto-hide messages after 5 seconds
    setTimeout(function() {
        const messages = document.querySelectorAll('.bg-green-100, .bg-red-100');
        messages.forEach(function(message) {
            message.style.opacity = '0';
            message.style.transition = 'opacity 0.5s ease';
            setTimeout(function() {
                message.remove();
            }, 500);
        });
    }, 5000);
});

// Modal functions
function editUser(userData) {
    document.getElementById('editUserId').value = userData.ID;
    document.getElementById('editUsername').value = userData.Username;
    document.getElementById('editEmail').value = userData.Email;
    document.getElementById('editFaculty').value = userData.Facuity;
    document.getElementById('editPosition').value = userData.Position;
    
    document.getElementById('editModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
} 