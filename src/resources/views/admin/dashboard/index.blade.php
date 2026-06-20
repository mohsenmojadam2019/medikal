@extends('admin.layouts.app')

@section('title', 'داشبورد مدیریت چاپخانه')
@section('page_title', '🏭 پیشخوان چاپخانه')
@section('page_description', 'خلاصه وضعیت تولید، فروش و عملکرد امروز')

@section('content')
    {{-- Stats --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-dollar-sign"></i></div>
            <div class="stat-label">فروش امروز</div>
            <div class="stat-value">{{ number_format(18450000) }}</div>
            <span class="stat-change"><i class="fas fa-arrow-up"></i> ۱۴.۵%</span>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-print"></i></div>
            <div class="stat-label">سفارشات چاپ</div>
            <div class="stat-value">۸۴</div>
            <span class="stat-change"><i class="fas fa-arrow-up"></i> ۸.۲%</span>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fas fa-industry"></i></div>
            <div class="stat-label">تولید امروز</div>
            <div class="stat-value">۳,۴۵۶</div>
            <span class="stat-change"><i class="fas fa-arrow-up"></i> ۵.۷%</span>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fas fa-boxes"></i></div>
            <div class="stat-label">مواد اولیه</div>
            <div class="stat-value">۸۹۲</div>
            <span class="stat-change down"><i class="fas fa-arrow-down"></i> ۲.۳%</span>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><i class="fas fa-clock"></i></div>
            <div class="stat-label">در حال انجام</div>
            <div class="stat-value">۲۴</div>
            <span class="stat-change"><i class="fas fa-arrow-up"></i> ۳.۱%</span>
        </div>
    </div>

    {{-- Charts --}}
    <div class="charts-grid">
        <div class="chart-card">
            <div class="chart-header">
                <h3><i class="fas fa-chart-line" style="color:#2563eb;"></i> روند فروش چاپ</h3>
            </div>
            <canvas id="salesChart"></canvas>
        </div>
        <div class="chart-card">
            <div class="chart-header">
                <h3><i class="fas fa-chart-pie" style="color:#7c3aed;"></i> توزیع محصولات چاپی</h3>
            </div>
            <canvas id="pieChart"></canvas>
        </div>
    </div>

    {{-- Recent Activity --}}
    <div class="activity-section">
        <div class="section-header">
            <h3><i class="fas fa-clock" style="color:#2563eb;"></i> آخرین فعالیت‌های چاپخانه</h3>
            <a href="#">مشاهده همه</a>
        </div>
        <div class="activity-list">
            <div class="activity-item">
                <div class="activity-icon blue"><i class="fas fa-print"></i></div>
                <div class="activity-content">
                    <div class="title">سفارش چاپ #۱۲۳۴ تکمیل شد</div>
                    <div class="desc">کاتالوگ بروشور - ۵۰۰ نسخه</div>
                </div>
                <span class="activity-time">۲ دقیقه پیش</span>
            </div>
            <div class="activity-item">
                <div class="activity-icon green"><i class="fas fa-user-plus"></i></div>
                <div class="activity-content">
                    <div class="title">مشتری جدید ثبت نام کرد</div>
                    <div class="desc">شرکت بازرگانی امید</div>
                </div>
                <span class="activity-time">۱۵ دقیقه پیش</span>
            </div>
            <div class="activity-item">
                <div class="activity-icon purple"><i class="fas fa-file-invoice"></i></div>
                <div class="activity-content">
                    <div class="title">فاکتور جدید صادر شد</div>
                    <div class="desc">فاکتور #۵۶۷۸ - مبلغ ۴,۲۵۰,۰۰۰</div>
                </div>
                <span class="activity-time">۱ ساعت پیش</span>
            </div>
            <div class="activity-item">
                <div class="activity-icon orange"><i class="fas fa-box"></i></div>
                <div class="activity-content">
                    <div class="title">مواد اولیه جدید وارد شد</div>
                    <div class="desc">کاغذ تحریر A4 - ۵۰ بسته</div>
                </div>
                <span class="activity-time">۳ ساعت پیش</span>
            </div>
            <div class="activity-item">
                <div class="activity-icon blue"><i class="fas fa-truck"></i></div>
                <div class="activity-content">
                    <div class="title">ارسال سفارش #۱۲۳۰</div>
                    <div class="desc">بسته چاپی به تهران</div>
                </div>
                <span class="activity-time">۵ ساعت پیش</span>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // ===== Charts =====
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Chart !== 'undefined') {
                // Sales Chart
                const salesCtx = document.getElementById('salesChart');
                if (salesCtx) {
                    new Chart(salesCtx, {
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

                // Pie Chart
                const pieCtx = document.getElementById('pieChart');
                if (pieCtx) {
                    new Chart(pieCtx, {
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
        });
    </script>
@endpush
