(function() {
    'use strict';

    // ===== Sidebar =====
    const sidebar = document.getElementById('sidebar');
    const hamburger = document.getElementById('hamburgerBtn');
    const overlay = document.getElementById('overlay');

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('active');
        hamburger.innerHTML = '<i class="fas fa-times"></i>';
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        hamburger.innerHTML = '<i class="fas fa-bars"></i>';
    }

    if (hamburger) {
        hamburger.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
        });
    }

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('open')) closeSidebar();
    });

    // ===== Sub Menus =====
    document.querySelectorAll('.nav-link[data-toggle="sub"]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.stopPropagation();
            const parent = this.closest('.nav-item');
            if (!parent) return;
            const sub = parent.querySelector('.sub-menu');
            if (!sub) return;

            sub.classList.toggle('open');
            const arrow = this.querySelector('.arrow i');
            if (arrow) {
                arrow.classList.toggle('fa-chevron-down');
                arrow.classList.toggle('fa-chevron-up');
            }
            this.classList.toggle('active');
        });
    });

    // ===== Profile Dropdown =====
    const profileBtn = document.getElementById('profileBtn');
    const profileMenu = document.getElementById('profileMenu');

    if (profileBtn) {
        profileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (profileMenu) profileMenu.classList.toggle('open');
        });
    }

    document.addEventListener('click', function(e) {
        if (profileMenu && profileBtn && !profileBtn.contains(e.target) && !profileMenu.contains(e.target)) {
            profileMenu.classList.remove('open');
        }
    });

    // ===== Profile Menu Items =====
    document.querySelectorAll('.profile-menu .menu-item').forEach(item => {
        item.addEventListener('click', function() {
            if (this.classList.contains('danger')) {
                if (confirm('آیا مطمئن هستید که می‌خواهید خارج شوید؟')) {
                    document.getElementById('logout-form')?.submit();
                }
            }
            if (profileMenu) profileMenu.classList.remove('open');
        });
    });

    // ===== LTR / RTL =====
    const toggleLtrBtn = document.getElementById('toggleLtrBtn');
    let isLtr = false;

    if (toggleLtrBtn) {
        toggleLtrBtn.addEventListener('click', function() {
            isLtr = !isLtr;
            document.body.classList.toggle('ltr', isLtr);
        });
    }

    // ===== Clock & Date =====
    function updateClockAndDate() {
        const now = new Date();
        const optionsDate = {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            weekday: 'long',
            timeZone: 'Asia/Tehran'
        };
        const persianDate = now.toLocaleDateString('fa-IR', optionsDate);
        const dateEl = document.getElementById('persianDate');
        if (dateEl) dateEl.textContent = persianDate;

        const timeStr = now.toLocaleTimeString('fa-IR', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            timeZone: 'Asia/Tehran'
        });
        const clockEl = document.getElementById('onlineClock');
        if (clockEl) clockEl.textContent = timeStr;
    }

    updateClockAndDate();
    setInterval(updateClockAndDate, 1000);

    // ===== Charts =====
    if (typeof Chart !== 'undefined') {
        const salesChartEl = document.getElementById('salesChart');
        if (salesChartEl) {
            const ctx1 = salesChartEl.getContext('2d');
            new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'],
                    datasets: [{
                        label: 'فروش چاپ',
                        data: [12, 18, 14, 22, 16, 25, 20],
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37,99,235,0.05)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#2563eb',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                    }, {
                        label: 'تولید',
                        data: [8, 12, 10, 16, 12, 18, 14],
                        borderColor: '#7c3aed',
                        backgroundColor: 'rgba(124,58,237,0.05)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#7c3aed',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 12,
                                font: { size: 10 }
                            }
                        }
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        const pieChartEl = document.getElementById('pieChart');
        if (pieChartEl) {
            const ctx2 = pieChartEl.getContext('2d');
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: ['چاپ افست', 'چاپ دیجیتال', 'چاپ فلکسو', 'چاپ سیلک'],
                    datasets: [{
                        data: [40, 30, 18, 12],
                        backgroundColor: ['#2563eb', '#7c3aed', '#059669', '#d97706'],
                        borderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 10,
                                font: { size: 10 }
                            }
                        }
                    }
                }
            });
        }
    }

    // ===== Responsive =====
    function handleResponsive() {
        if (window.innerWidth >= 992) {
            if (sidebar) {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
                hamburger.innerHTML = '<i class="fas fa-bars"></i>';
            }
        }
    }

    handleResponsive();
    window.addEventListener('resize', handleResponsive);

    // ===== Close on sub-item click (mobile) =====
    document.querySelectorAll('.sub-item').forEach(item => {
        item.addEventListener('click', function() {
            if (window.innerWidth < 992) closeSidebar();
        });
    });

    // ===== Nav item click =====
    document.querySelectorAll('.nav-link:not([data-toggle="sub"])').forEach(link => {
        link.addEventListener('click', function() {
            document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            if (window.innerWidth < 992) closeSidebar();
        });
    });

})();
