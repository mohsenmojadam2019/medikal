@extends('admin.layouts.app')

@section('title', 'داشبورد کاربری')
@section('page_title', '👤 داشبورد کاربری')
@section('page_description', 'خلاصه فعالیت‌های شما')

@section('content')
    <style>
        .stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px; margin-bottom:20px; }
        .stat-card { background:#fff; padding:16px 18px; border-radius:12px; border:1px solid #e8ecf1; }
        .stat-card .stat-label { font-size:0.75rem; color:#94a3b8; font-weight:500; }
        .stat-card .stat-value { font-size:1.3rem; font-weight:700; color:#1a2332; }
        .welcome { font-size:1.1rem; font-weight:600; color:#1a2332; margin-bottom:16px; }
        .welcome small { font-size:0.85rem; font-weight:400; color:#94a3b8; display:block; margin-top:4px; }
        .activity-list { background:#fff; border-radius:12px; border:1px solid #e8ecf1; padding:16px; }
        .activity-item { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #f1f5f9; }
        .activity-item:last-child { border-bottom:none; }
        .activity-item .title { font-weight:500; color:#1a2332; }
        .activity-item .time { color:#94a3b8; font-size:0.8rem; }
        .empty-state { text-align:center; padding:20px; color:#94a3b8; }
    </style>

    <div class="welcome">
        👋 خوش آمدید، {{ $user->name ?? 'کاربر عزیز' }}
        <small>به پنل کاربری خود خوش آمدید</small>
    </div>

    <div class="stats-grid">
{{--        <div class="stat-card"><div class="stat-label">🛒 کل سفارشات</div><div class="stat-value">{{ $stats['total_orders'] }}</div></div>--}}
{{--        <div class="stat-card"><div class="stat-label">⏳ در انتظار</div><div class="stat-value">{{ $stats['pending_orders'] }}</div></div>--}}
{{--        <div class="stat-card"><div class="stat-label">✅ تکمیل شده</div><div class="stat-value">{{ $stats['completed_orders'] }}</div></div>--}}
    </div>

    <div class="activity-list">
        <h4 style="margin-bottom:12px;">📋 آخرین سفارشات شما</h4>
        @if($stats['recent_orders']->count() > 0)
            @foreach($stats['recent_orders'] as $order)
                <div class="activity-item">
{{--                    <span class="title">#{{ $order->id }} - {{ $order->status }}</span>--}}
{{--                    <span class="time">{{ $order->created_at?->diffForHumans() }}</span>--}}
                </div>
            @endforeach
        @else
            <div class="empty-state">
                <i class="fas fa-shopping-bag" style="font-size:2rem;color:#e2e8f0;display:block;margin-bottom:8px;"></i>
                شما هنوز سفارشی ثبت نکرده‌اید.
            </div>
        @endif
    </div>
@endsection
