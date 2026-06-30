// news_management.js
// Auto-hide messages
setTimeout(function() {
    const messages = document.querySelectorAll('.mb-6');
    messages.forEach(function(message) {
        message.style.display = 'none';
    });
}, 5000);

// Edit news function
function editNews(news) {
    document.getElementById('edit_news_id').value = news.id;
    document.getElementById('edit_title').value = news.title;
    document.getElementById('edit_content').value = news.content;
    document.getElementById('edit_date_posted').value = news.date_posted;
    document.getElementById('edit_is_active').checked = news.is_active == 1;
    
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editModal').classList.add('flex');
}

// Close edit modal
function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editModal').classList.remove('flex');
}

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });
}); 