// scholarship_summary.js
// Plugin สำหรับแสดงจำนวนรวมตรงกลาง donut
Chart.register({
  id: 'centerText',
  afterDraw(chart) {
    if (chart.config.type !== 'doughnut') return;
    const {ctx, chartArea: area} = chart;
    const total = chart.data.datasets[0].data.reduce((a, b) => a + b, 0);

    // ใช้สีขาวใน dark mode แบบเด็ดขาด
    const theme = document.documentElement.getAttribute('data-theme');
    let mainColor, subColor;
    if (theme === 'dark') {
      mainColor = getComputedStyle(document.documentElement).getPropertyValue('--text-color') || '#fff';
      subColor = '#aaa';
    } else {
      mainColor = getComputedStyle(document.documentElement).getPropertyValue('--text-color') || '#222';
      subColor = '#888';
    }

    ctx.save();
    ctx.font = 'bold 2.2rem sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillStyle = mainColor;
    const centerX = (area.left + area.right) / 2;
    const centerY = (area.top + area.bottom) / 2;
    ctx.fillText(total, centerX, centerY - 12);

    ctx.font = 'normal 1rem sans-serif';
    ctx.fillStyle = subColor;
    ctx.fillText('จำนวนคำขอทั้งหมด', centerX, centerY + 18);
    ctx.restore();
  }
});

let statusChartInstance = null;

// ฟังก์ชันสำหรับสร้างกราฟโดนัท
function createDonutChart(data) {
    const statusCounts = {
        'รออนุมัติ': 0,
        'อนุมัติ': 0,
        'ปฏิเสธ': 0
    };

    // นับจำนวนแต่ละสถานะ (ไม่นับ 'ยกเลิกแล้ว')
    data.requests.forEach(request => {
        if (request.current_status in statusCounts) {
            statusCounts[request.current_status]++;
        }
    });

    const totalRequests = Object.values(statusCounts).reduce((a, b) => a + b, 0);

    // สีสำหรับแต่ละสถานะ
    const colors = {
        'รออนุมัติ': '#fbbf24',
        'อนุมัติ': '#34d399',
        'ปฏิเสธ': '#ef4444'
    };

    const ctx = document.getElementById('statusChart').getContext('2d');
    if (statusChartInstance) {
        statusChartInstance.destroy();
    }
    statusChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(statusCounts),
            datasets: [{
                data: Object.values(statusCounts),
                backgroundColor: Object.keys(statusCounts).map(status => colors[status]),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'right',
                    align: 'center',
                    labels: {
                        boxWidth: 15,
                        padding: 15,
                        font: {
                            size: 13
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const percentage = ((value / totalRequests) * 100).toFixed(1);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

function displaySummary(data) {
    const summaryDiv = document.getElementById('summaryData');
    const totalRequests = data.requests.length;
    const typeCount = data.requests.reduce((acc, req) => {
        const type = {
            'research_proposals': 'นักศึกษา',
            'research_teacher': 'อาจารย์',
            'research_personnel': 'บุคลากร'
        }[req.original_table];
        acc[type] = (acc[type] || 0) + 1;
        return acc;
    }, {});

    const summaryHTML = `
        <div class="stats stats-vertical sm:stats-horizontal shadow w-full bg-base-100">
            <div class="stat px-4 py-2">
                <div class="stat-title text-sm">จำนวนคำขอทั้งหมด</div>
                <div class="stat-value text-xl">${totalRequests}</div>
            </div>
            ${Object.entries(typeCount).map(([type, count]) => `
                <div class="stat px-4 py-2">
                    <div class="stat-title text-sm">จำนวน${type}</div>
                    <div class="stat-value text-xl">${count}</div>
                    <div class="stat-desc text-xs">คิดเป็น ${((count/totalRequests)*100).toFixed(1)}%</div>
                </div>
            `).join('')}
        </div>
    `;

    summaryDiv.innerHTML = summaryHTML;
}

// ฟังก์ชันสำหรับอัปเดตธีม
function updateChartTheme() {
    if (statusChartInstance) {
        statusChartInstance.update();
    }
}

// --- ซิงค์ธีมกับ parent (index.php) ---
function syncThemeFromParent() {
    try {
        const parentTheme = window.parent.document.documentElement.getAttribute('data-theme');
        if (parentTheme) {
            document.documentElement.setAttribute('data-theme', parentTheme);
        }
    } catch (e) {}
}

// ฟัง event message จาก parent (index.php) เพื่ออัปเดตธีม
window.addEventListener('message', function(event) {
    if (event.data && event.data.theme) {
        document.documentElement.setAttribute('data-theme', event.data.theme);
    }
});

// ปรับแต่งการแสดงผลกราฟ
Chart.defaults.font.size = 13;
Chart.defaults.plugins.legend.position = 'bottom';
Chart.defaults.plugins.legend.labels.padding = 12;
Chart.defaults.plugins.legend.labels.boxWidth = 15;
Chart.defaults.plugins.legend.labels.usePointStyle = true;

// อัพเดตสไตล์ของกราฟให้เข้ากับธีม
Chart.defaults.color = getComputedStyle(document.documentElement).getPropertyValue('--text-base-content');
Chart.defaults.borderColor = getComputedStyle(document.documentElement).getPropertyValue('--border-base-content');

// ดึงข้อมูลและแสดงผล
document.addEventListener('DOMContentLoaded', function() {
    // เรียกทันทีเมื่อโหลด
    syncThemeFromParent();
    
    fetch('get_my_scholarships.php', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        console.log('DATA:', data); // Debug: log ข้อมูลที่ได้
        if (data.status === 'success') {
            if (!data.requests || data.requests.length === 0) {
                // ซ่อน donut chart แล้วแสดงข้อความแทน
                const donutDiv = document.querySelector('.donut-chart');
                if (donutDiv) {
                    donutDiv.innerHTML = `<div class="flex items-center justify-center h-full w-full text-gray-400 text-xl">ไม่มีประวัติการยื่นขอทุน</div>`;
                }
                // ไม่ต้องเรียก createDonutChart
            } else {
                createDonutChart(data);
            }
            displaySummary(data);
        } else {
            alert('เกิดข้อผิดพลาดในการดึงข้อมูล: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการดึงข้อมูล');
    });
}); 