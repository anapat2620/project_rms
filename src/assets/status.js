// ประกาศฟังก์ชันให้เป็น global
window.openModal = function(action, requestId) {
    // ใช้ prompt JS แทน modal
    let actionText = (action === 'approve') ? 'อนุมัติ' : 'ปฏิเสธ';
    let comment = '';
    while (true) {
        comment = window.prompt(`กรุณาใส่ความคิดเห็นสำหรับการ${actionText}:`);
        if (comment === null) return; // user กด cancel
        if (comment.trim() !== '') break; // ต้องไม่ว่าง
        alert('กรุณากรอกความคิดเห็นก่อนดำเนินการ');
    }

    // สร้างฟอร์มชั่วคราวเพื่อ submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'status.php';

    const inputRequestId = document.createElement('input');
    inputRequestId.type = 'hidden';
    inputRequestId.name = 'request_id';
    inputRequestId.value = requestId;
    form.appendChild(inputRequestId);

    const inputAction = document.createElement('input');
    inputAction.type = 'hidden';
    inputAction.name = 'action';
    inputAction.value = action;
    form.appendChild(inputAction);

    const inputComment = document.createElement('input');
    inputComment.type = 'hidden';
    inputComment.name = 'comment';
    inputComment.value = comment;
    form.appendChild(inputComment);

    document.body.appendChild(form);
    form.submit();
}

window.closeModal = function() {
    const modal = document.getElementById('commentModal');
    const form = document.getElementById('actionForm');
    
    // ซ่อน modal ด้วย animation
    modal.querySelector('.modal-content').classList.add('scale-95');
    modal.querySelector('.modal-content').classList.remove('scale-100');
    
    setTimeout(() => {
        modal.classList.add('hidden');
        form.reset();
    }, 200);
}

// เพิ่ม event listeners เมื่อ DOM โหลดเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    // ปิด modal เมื่อคลิกพื้นหลัง
    document.getElementById('commentModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    // ปิด modal เมื่อกด ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !document.getElementById('commentModal').classList.contains('hidden')) {
            closeModal();
        }
    });

    // รับค่าธีมจากหน้าหลักและนำมาใช้
    function applyTheme() {
        const theme = document.documentElement.getAttribute('data-theme') || 'light';
        document.documentElement.setAttribute('data-theme', theme);
    }

    // เรียกใช้ฟังก์ชันเมื่อโหลดหน้าและเมื่อมีการเปลี่ยนแปลงธีม
    applyTheme();
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.attributeName === 'data-theme') {
                applyTheme();
            }
        });
    });
    observer.observe(document.documentElement, { attributes: true });
}); 