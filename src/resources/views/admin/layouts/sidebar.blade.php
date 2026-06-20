{{-- ===== پیشخوان (همه کاربران) ===== --}}
<div class="nav-label">داشبورد</div>

<div class="nav-item">
    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <i class="fas fa-chart-pie"></i> پیشخوان
    </a>
</div>

{{-- ========================================== --}}
{{-- ===== آیتم‌های اختصاصی ادمین‌ها ===== --}}
{{-- ========================================== --}}
@role('admin|super-admin')

{{-- ===== مدیریت محصولات ===== --}}
<div class="nav-label">مدیریت فروشگاه</div>

<div class="nav-item">
    <div class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}" data-toggle="sub">
        <i class="fas fa-tag"></i> محصولات
        <span class="badge-count">۲۴۵</span>
        <span class="arrow"><i class="fas fa-chevron-down"></i></span>
    </div>
    <div class="sub-menu {{ request()->routeIs('admin.products.*') ? 'open' : '' }}">
{{--        <a href="{{ route('admin.products.index') }}" class="sub-item"><i class="fas fa-box-open"></i> لیست محصولات</a>--}}
{{--        <a href="{{ route('admin.products.create') }}" class="sub-item"><i class="fas fa-plus-circle"></i> افزودن محصول</a>--}}
        <a href="#" class="sub-item"><i class="fas fa-folder-tree"></i> دسته‌بندی‌ها</a>
    </div>
</div>

{{-- ===== مدیریت سفارشات ===== --}}
<div class="nav-item">
    <div class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" data-toggle="sub">
        <i class="fas fa-clipboard-list"></i> سفارشات
        <span class="badge-count danger">۱۸</span>
        <span class="arrow"><i class="fas fa-chevron-down"></i></span>
    </div>
    <div class="sub-menu {{ request()->routeIs('admin.orders.*') ? 'open' : '' }}">
{{--        <a href="{{ route('admin.orders.index') }}" class="sub-item"><i class="fas fa-list"></i> لیست سفارشات</a>--}}
        <a href="#" class="sub-item"><i class="fas fa-hourglass-half"></i> در انتظار تایید</a>
        <a href="#" class="sub-item"><i class="fas fa-check-circle"></i> تکمیل شده</a>
    </div>
</div>

{{-- ===== مدیریت کاربران ===== --}}
<div class="nav-label">مدیریت کاربران</div>

<div class="nav-item">
    <div class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" data-toggle="sub">
        <i class="fas fa-users-cog"></i> کاربران
        <span class="badge-count">{{ \App\Models\User::count() }}</span>
        <span class="arrow"><i class="fas fa-chevron-down"></i></span>
    </div>
    <div class="sub-menu {{ request()->routeIs('admin.users.*') ? 'open' : '' }}">
        <a href="{{ route('admin.users.index') }}" class="sub-item"><i class="fas fa-user-friends"></i> همه کاربران</a>
        <a href="{{ route('admin.users.create') }}" class="sub-item"><i class="fas fa-user-plus"></i> افزودن کاربر</a>
    </div>
</div>

{{-- ===== نقش‌ها و مجوزها ===== --}}
<div class="nav-item">
    <div class="nav-link {{ request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') ? 'active' : '' }}" data-toggle="sub">
        <i class="fas fa-shield-alt"></i> دسترسی‌ها
        <span class="badge-count info">{{ \Spatie\Permission\Models\Role::count() }}</span>
        <span class="arrow"><i class="fas fa-chevron-down"></i></span>
    </div>
    <div class="sub-menu {{ request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') ? 'open' : '' }}">
        <a href="{{ route('admin.roles.index') }}" class="sub-item"><i class="fas fa-list"></i> نقش‌ها</a>
        <a href="{{ route('admin.permissions.index') }}" class="sub-item"><i class="fas fa-key"></i> مجوزها</a>
    </div>
</div>

{{-- ===== تنظیمات ===== --}}
<div class="nav-label">سیستم</div>

<div class="nav-item">
    <div class="nav-link {{ request()->routeIs('admin.settings*') ? 'active' : '' }}" data-toggle="sub">
        <i class="fas fa-cog"></i> تنظیمات
        <span class="arrow"><i class="fas fa-chevron-down"></i></span>
    </div>
    <div class="sub-menu {{ request()->routeIs('admin.settings*') ? 'open' : '' }}">
        <a href="{{ route('admin.settings') }}" class="sub-item"><i class="fas fa-globe"></i> تنظیمات عمومی</a>
        <a href="#" class="sub-item"><i class="fas fa-envelope"></i> تنظیمات ایمیل</a>
    </div>
</div>

@endrole

{{-- ========================================== --}}
{{-- ===== آیتم‌های اختصاصی کاربران عادی ===== --}}
{{-- ========================================== --}}
@role('user')

<div class="nav-label">حساب کاربری</div>

{{-- سفارشات من --}}
<div class="nav-item">
{{--    <a href="{{ route('admin.my-orders') }}" class="nav-link {{ request()->routeIs('admin.my-orders*') ? 'active' : '' }}">--}}
{{--        <i class="fas fa-shopping-bag"></i> سفارشات من--}}
{{--        <span class="badge-count info">{{ auth()->user()->orders()->count() ?? 0 }}</span>--}}
{{--    </a>--}}
</div>

{{-- محصولات --}}
<div class="nav-item">
    <a href="#" class="nav-link">
        <i class="fas fa-store"></i> فروشگاه
    </a>
</div>

@endrole

{{-- ========================================== --}}
{{-- ===== آیتم‌های مشترک (همه کاربران) ===== --}}
{{-- ========================================== --}}

{{-- پروفایل (همه کاربران) --}}
<div class="nav-item">
    <a href="{{ route('admin.profile') }}" class="nav-link {{ request()->routeIs('admin.profile') ? 'active' : '' }}">
        <i class="fas fa-user"></i> پروفایل من
    </a>
</div>
