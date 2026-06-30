// faculty_management.js
// Auto-hide messages
setTimeout(function() {
    const messages = document.querySelectorAll('.mb-6');
    messages.forEach(function(message) {
        message.style.display = 'none';
    });
}, 5000);

// Search functionality
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('.faculty-row');
        
        rows.forEach(function(row) {
            const facultyName = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            const facultyCode = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            
            if (facultyName.includes(searchTerm) || facultyCode.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Close modal when clicking outside
    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });

    // Auto-uppercase faculty code
    document.getElementById('edit_faculty_code').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
});

// Edit faculty function
function editFaculty(faculty) {
    document.getElementById('edit_faculty_id').value = faculty.id;
    document.getElementById('edit_faculty_code').value = faculty.faculty_code;
    document.getElementById('edit_faculty_name').value = faculty.faculty_name;
    document.getElementById('edit_description').value = faculty.description;
    
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editModal').classList.add('flex');
}

// Close edit modal
function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editModal').classList.remove('flex');
} 