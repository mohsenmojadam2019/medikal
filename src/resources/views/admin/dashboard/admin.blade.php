@extends('admin.layouts.app')

@section('title', 'داشبورد مدیریت')
@section('page_title', '🏠 داشبورد مدیریت')
@section('page_description', 'خلاصه وضعیت سیستم')

@section('content')
    <style>
        .stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px; margin-bottom:20px; }
        .stat-card { background:#fff; padding:16px 18px; border-radius:12px; border:1px solid #e8ecf1; }
        .stat-card .stat-label { font-size:0.75rem; color:#94a3b8; font-weight:500; }
        .stat-card .stat-value { font-size:1.3rem; font-weight:700; color:#1a2332; }
        .activity-list { background:#fff; border-radius:12px; border:1px solid #e8ecf1; padding:16px; }
        .activity-item { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #f1f5f9; }
        .activity-item:last-child { border-bottom:none; }
        .activity-item .title { font-weight:500; color:#1a2332; }
        .activity-item .time { color:#94a3b8; font-size:0.8rem; }
    </style>

    <div class="stats-grid">
{{--        <div class="stat-card"><div class="stat-label">👥 کل کاربران</div><div class="stat-value">{{ $stats['total_users'] }}</div></div>--}}
{{--        <div class="stat-card"><div class="stat-label">📦 کل محصولات</div><div class="stat-value">{{ $stats['total_products'] }}</div></div>--}}
{{--        <div class="stat-card"><div class="stat-label">🛒 کل سفارشات</div><div class="stat-value">{{ $stats['total_orders'] }}</div></div>--}}
{{--        <div class="stat-card"><div class="stat-label">⏳ سفارشات در انتظار</div><div class="stat-value">{{ $stats['pending_orders'] }}</div></div>--}}
    </div>

    <div class="activity-list">
        <h4 style="margin-bottom:12px;">📋 آخرین سفارشات</h4>
{{--        @foreach($stats['recent_orders'] as $order)--}}
            <div class="activity-item">
{{--                <span class="title">#{{ $order->id }} - {{ $order->user?->name ?? 'کاربر' }}</span>--}}
{{--                <span class="time">{{ $order->created_at?->diffForHumans() }}</span>--}}
            </div>
{{--        @endforeach--}}
    </div>
@endsection
