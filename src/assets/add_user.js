// add_user.js
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

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('form').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        if (password.length < 3) {
            e.preventDefault();
            alert('รหัสผ่านต้องมีความยาวอย่างน้อย 3 ตัวอักษร');
            return false;
        }
    });
}); 