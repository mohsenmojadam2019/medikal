<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'داشبورد مدیریت')</title>

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
{{--    <link rel="stylesheet" href="{{ asset('dashboard/css/all.min.css') }}">--}}

    {{-- Chart.js --}}
{{--    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>--}}
    <script src="{{ asset('dashboard/js/chart.umd.min.js') }}"></script>

    {{-- Custom Dashboard CSS --}}
    <link rel="stylesheet" href="{{ asset('dashboard/css/style.css') }}">

    @stack('styles')
</head>
<body>

{{-- Overlay --}}
<div class="overlay" id="overlay"></div>

{{-- Hamburger --}}
<div class="hamburger" id="hamburgerBtn">
    <i class="fas fa-bars"></i>
</div>

{{-- ===== Sidebar ===== --}}
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-print"></i>
        <div class="brand-text">
            <span>{{ config('app.name', 'چاپخانه') }}</span>
            <small>سیستم مدیریت جامع</small>
        </div>
    </div>

    <nav class="sidebar-nav">
        @include('admin.layouts.sidebar')
    </nav>
</aside>

{{-- ===== Main Content ===== --}}
<main class="main-content" id="mainContent">
    {{-- Top Bar --}}
    <div class="top-bar">
        <div class="top-bar-left">
            <h1>@yield('page_title', 'پیشخوان')</h1>
            <p>@yield('page_description', 'خلاصه وضعیت فروش')</p>
        </div>
        <div class="top-bar-right">
            <button class="icon-btn" title="اعلان‌ها">
                <i class="fas fa-bell"></i>
                <span class="dot"></span>
            </button>
            <button class="icon-btn" title="پیام‌ها">
                <i class="fas fa-envelope"></i>
            </button>
            <button class="icon-btn" id="toggleLtrBtn" title="تغییر جهت">
                <i class="fas fa-exchange-alt"></i>
            </button>
            <div class="clock-display">
                <i class="far fa-calendar-alt"></i>
                <span class="date-text" id="persianDate"></span>
                <span id="onlineClock"></span>
            </div>

            {{-- Profile Dropdown --}}
            <div class="profile-dropdown">
                <button class="profile-btn" id="profileBtn">
                    <div class="avatar">{{ auth()->user()?->name ? Str::upper(substr(auth()->user()->name, 0, 2)) : 'AD' }}</div>
                    <div class="info">
                        <div class="name">{{ auth()->user()?->name ?? 'مدیر سیستم' }}</div>
                        <div class="role">{{ auth()->user()?->roles->first()?->name ?? 'مدیر' }}</div>
                    </div>
                    <span class="arrow-down"><i class="fas fa-chevron-down"></i></span>
                </button>
                <div class="profile-menu" id="profileMenu">
                    <a href="{{ route('admin.profile') }}" class="menu-item">
                        <i class="fas fa-user"></i> پروفایل
                    </a>
                    <a href="{{ route('admin.settings') }}" class="menu-item">
                        <i class="fas fa-cog"></i> تنظیمات حساب
                    </a>
                    <div class="divider"></div>
                    <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                    <button class="menu-item danger" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="fas fa-sign-out-alt"></i> خروج
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Page Content ===== --}}
    @yield('content')

</main>

{{-- ===== Scripts ===== --}}
<script src="{{ asset('dashboard/js/app.js') }}"></script>

@stack('scripts')
</body>
</html>
