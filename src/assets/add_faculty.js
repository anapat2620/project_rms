// add_faculty.js
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

// Form validation and auto-uppercase
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('form').addEventListener('submit', function(e) {
        const facultyCode = document.getElementById('faculty_code').value;
        if (facultyCode.length < 2) {
            e.preventDefault();
            alert('รหัสคณะต้องมีความยาวอย่างน้อย 2 ตัวอักษร');
            return false;
        }
    });

    // Auto-uppercase faculty code
    document.getElementById('faculty_code').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
}); 