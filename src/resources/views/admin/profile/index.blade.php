@extends('admin.layouts.app')

@section('title', 'پروفایل کاربر')
@section('page_title', '👤 پروفایل کاربر')
@section('page_description', 'مدیریت اطلاعات شخصی و تنظیمات حساب کاربری')

@section('content')
    <style>
        .profile-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 24px;
            max-width: 1100px;
            margin: 0 auto;
        }

        /* ===== Profile Card ===== */
        .profile-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e8ecf1;
            padding: 24px;
            text-align: center;
            height: fit-content;
        }

        .profile-card .avatar-wrapper {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 16px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid #e8ecf1;
            transition: all 0.3s;
        }

        .profile-card .avatar-wrapper:hover {
            border-color: #2563eb;
        }

        .profile-card .avatar-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-card .avatar-wrapper .avatar-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            color: #fff;
            font-size: 3rem;
            font-weight: 700;
        }

        .profile-card .avatar-wrapper .upload-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.6);
            color: #fff;
            padding: 8px;
            font-size: 0.7rem;
            cursor: pointer;
            transition: all 0.3s;
            opacity: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        .profile-card .avatar-wrapper:hover .upload-overlay {
            opacity: 1;
        }

        .profile-card .avatar-wrapper .upload-overlay input[type="file"] {
            display: none;
        }

        .profile-card .user-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1a2332;
        }

        .profile-card .user-email {
            font-size: 0.85rem;
            color: #94a3b8;
            margin-top: 2px;
        }

        .profile-card .user-role {
            display: inline-block;
            background: #dbeafe;
            color: #2563eb;
            padding: 2px 16px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-top: 8px;
        }

        .profile-card .divider {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 16px 0;
        }

        .profile-card .info-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 0.82rem;
        }

        .profile-card .info-row .label {
            color: #94a3b8;
        }

        .profile-card .info-row .value {
            color: #1a2332;
            font-weight: 500;
        }

        /* ===== Form Card ===== */
        .form-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e8ecf1;
            padding: 24px 28px;
        }

        .form-card .form-title {
            font-size: 1rem;
            font-weight: 600;
            color: #1a2332;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-card .form-title i {
            color: #2563eb;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 5px;
        }

        .form-group label .required {
            color: #dc2626;
        }

        .form-group .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.9rem;
            color: #1a2332;
            background: #f8fafc;
            transition: all 0.2s;
            outline: none;
        }

        .form-group .form-control:focus {
            border-color: #2563eb;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08);
        }

        .form-group .form-control.is-invalid {
            border-color: #dc2626;
        }

        .form-group .invalid-feedback {
            color: #dc2626;
            font-size: 0.78rem;
            margin-top: 4px;
        }

        .form-group .help-text {
            color: #94a3b8;
            font-size: 0.75rem;
            margin-top: 4px;
        }

        .form-group .help-text i {
            font-size: 0.7rem;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 8px;
            flex-wrap: wrap;
        }

        .btn-save {
            padding: 11px 28px;
            background: #2563eb;
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-save:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.25);
        }

        .btn-save-purple {
            background: #7c3aed;
        }

        .btn-save-purple:hover {
            background: #6d28d9;
            box-shadow: 0 8px 24px rgba(124, 58, 237, 0.25);
        }

        .alert-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #059669;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success i {
            font-size: 1rem;
            color: #059669;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error i {
            font-size: 1rem;
            color: #dc2626;
        }

        .upload-loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.3);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .upload-loading .spinner {
            background: #fff;
            padding: 30px 40px;
            border-radius: 16px;
            text-align: center;
        }

        .upload-loading .spinner i {
            font-size: 2.5rem;
            color: #2563eb;
            animation: spin 1s linear infinite;
        }

        .upload-loading .spinner p {
            margin-top: 12px;
            color: #475569;
            font-weight: 500;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .profile-container {
                grid-template-columns: 1fr;
            }

            .profile-card {
                max-width: 400px;
                margin: 0 auto;
            }

            .form-card {
                padding: 20px 16px;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-actions .btn-save {
                justify-content: center;
            }
        }
    </style>

    {{-- Loading Overlay --}}
    <div class="upload-loading" id="uploadLoading">
        <div class="spinner">
            <i class="fas fa-spinner"></i>
            <p>در حال آپلود عکس...</p>
        </div>
    </div>

    <div class="profile-container">

        {{-- ===== Profile Card ===== --}}
        <div class="profile-card">
            <div class="avatar-wrapper">
                @php
                    $avatar = $user->getFirstMediaUrl('avatar');
                @endphp
                @if($avatar)
                    <img src="{{ $avatar }}" alt="{{ $user->name }}">
                @else
                    <div class="avatar-placeholder">
                        {{ strtoupper(substr($user->name ?? $user->email, 0, 2)) }}
                    </div>
                @endif
                <div class="upload-overlay" onclick="document.getElementById('avatarInput').click()">
                    <i class="fas fa-camera"></i> تغییر عکس
                </div>
                <form id="avatarForm" action="{{ route('admin.profile.upload-avatar') }}" method="POST" enctype="multipart/form-data" style="display:none;">
                    @csrf
                    <input type="file" id="avatarInput" name="avatar" accept="image/*" onchange="uploadAvatar(this)">
                </form>
            </div>

            <div class="user-name">{{ $user->name ?? 'کاربر' }}</div>
            <div class="user-email">{{ $user->email }}</div>
            <span class="user-role">{{ $user->roles->first()?->name ?? 'بدون نقش' }}</span>

            <hr class="divider">

            <div class="info-row">
                <span class="label"><i class="fas fa-calendar-alt"></i> عضو از</span>
                <span class="value">{{ $user->created_at?->format('Y/m/d') ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="label"><i class="fas fa-clock"></i> آخرین ورود</span>
                <span class="value">{{ $user->last_login_at?->diffForHumans() ?? 'اولین بار' }}</span>
            </div>
            <div class="info-row">
                <span class="label"><i class="fas fa-shield-alt"></i> وضعیت</span>
                <span class="value">
                <span style="color:{{ $user->is_active ? '#059669' : '#dc2626' }};">
                    {{ $user->is_active ? 'فعال' : 'غیرفعال' }}
                </span>
            </span>
            </div>
        </div>

        {{-- ===== Form Section ===== --}}
        <div>

            {{-- Update Profile --}}
            <div class="form-card" style="margin-bottom:20px;">
                <div class="form-title">
                    <i class="fas fa-user-edit"></i> ویرایش اطلاعات شخصی
                </div>

                @if(session('profile_success'))
                    <div class="alert-success">
                        <i class="fas fa-check-circle"></i>
                        {{ session('profile_success') }}
                    </div>
                @endif

                @if(session('profile_error'))
                    <div class="alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ session('profile_error') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.profile.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="name">نام کامل <span class="required">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                               id="name" name="name" value="{{ old('name', $user->name) }}" required>
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="email">آدرس ایمیل <span class="required">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                               id="email" name="email" value="{{ old('email', $user->email) }}" required>
                        @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save"></i> ذخیره تغییرات
                        </button>
                    </div>
                </form>
            </div>

            {{-- Change Password --}}
            <div class="form-card">
                <div class="form-title">
                    <i class="fas fa-lock" style="color:#7c3aed;"></i> تغییر رمز عبور
                </div>

                @if(session('password_success'))
                    <div class="alert-success">
                        <i class="fas fa-check-circle"></i>
                        {{ session('password_success') }}
                    </div>
                @endif

                @if(session('password_error'))
                    <div class="alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ session('password_error') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.profile.update-password') }}">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="current_password">رمز عبور فعلی <span class="required">*</span></label>
                        <input type="password" class="form-control @error('current_password') is-invalid @enderror"
                               id="current_password" name="current_password" placeholder="رمز عبور فعلی را وارد کنید" required>
                        @error('current_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password">رمز عبور جدید <span class="required">*</span></label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                               id="password" name="password" placeholder="حداقل ۸ کاراکتر" required>
                        @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="help-text">
                            <i class="fas fa-info-circle"></i>
                            رمز عبور باید حداقل ۸ کاراکتر باشد.
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation">تکرار رمز عبور جدید <span class="required">*</span></label>
                        <input type="password" class="form-control"
                               id="password_confirmation" name="password_confirmation" placeholder="تکرار رمز عبور جدید" required>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-save btn-save-purple">
                            <i class="fas fa-key"></i> تغییر رمز عبور
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <script>
        function uploadAvatar(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                const maxSize = 2 * 1024 * 1024; // 2MB

                if (!allowedTypes.includes(file.type)) {
                    alert('فرمت فایل مجاز نیست. فقط JPEG, PNG, GIF, WEBP مجاز است.');
                    input.value = '';
                    return;
                }

                if (file.size > maxSize) {
                    alert('حجم فایل نباید بیشتر از ۲ مگابایت باشد.');
                    input.value = '';
                    return;
                }

                const form = document.getElementById('avatarForm');
                const formData = new FormData(form);

                document.getElementById('uploadLoading').style.display = 'flex';

                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('uploadLoading').style.display = 'none';
                        if (data.success) {
                            location.reload();
                        } else {
                            alert(data.message || 'خطا در آپلود عکس');
                        }
                    })
                    .catch(error => {
                        document.getElementById('uploadLoading').style.display = 'none';
                        alert('خطا در ارتباط با سرور');
                    });
            }
        }
    </script>
@endsection
