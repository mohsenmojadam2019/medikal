<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ورود به پنل مدیریت</title>

{{-- Font Awesome --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* ===== Reset ===== */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    }

    body {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f0f2f5;
        padding: 20px;
    }

    /* ===== Container ===== */
    .login-container {
        width: 100%;
        max-width: 440px;
    }

    /* ===== Card ===== */
    .login-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 40px 35px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
        border: 1px solid #e8ecf1;
    }

    /* ===== Brand ===== */
    .brand {
        text-align: center;
        margin-bottom: 32px;
    }

    .brand .icon {
        width: 64px;
        height: 64px;
        background: #eff6ff;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 14px;
        font-size: 28px;
        color: #2563eb;
    }

    .brand h1 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1a2332;
        letter-spacing: -0.5px;
    }

    .brand p {
        color: #94a3b8;
        font-size: 0.85rem;
        margin-top: 4px;
    }

    /* ===== Form ===== */
    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: #475569;
        margin-bottom: 6px;
    }

    .form-group .input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        transition: all 0.2s;
    }

    .form-group .input-wrapper:focus-within {
        border-color: #2563eb;
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08);
    }

    .form-group .input-wrapper .input-icon {
        padding: 0 14px 0 4px;
        color: #94a3b8;
        font-size: 0.9rem;
        flex-shrink: 0;
    }

    .form-group .input-wrapper input {
        width: 100%;
        padding: 12px 0 12px 14px;
        border: none;
        background: transparent;
        font-size: 0.9rem;
        color: #1a2332;
        outline: none;
        direction: ltr;
    }

    .form-group .input-wrapper input::placeholder {
        color: #cbd5e1;
        font-size: 0.85rem;
    }

    .form-group .input-wrapper .toggle-password {
        padding: 0 14px 0 8px;
        color: #94a3b8;
        cursor: pointer;
        font-size: 0.85rem;
        transition: 0.15s;
        background: none;
        border: none;
    }

    .form-group .input-wrapper .toggle-password:hover {
        color: #475569;
    }

    /* ===== Remember & Forgot ===== */
    .form-options {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 4px 0 24px;
    }

    .form-options .remember {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.8rem;
        color: #64748b;
        cursor: pointer;
    }

    .form-options .remember input[type="checkbox"] {
        width: 16px;
        height: 16px;
        accent-color: #2563eb;
        border-radius: 4px;
        cursor: pointer;
    }

    .form-options .forgot-link {
        font-size: 0.8rem;
        color: #2563eb;
        text-decoration: none;
        font-weight: 500;
    }

    .form-options .forgot-link:hover {
        text-decoration: underline;
    }

    /* ===== Button ===== */
    .btn-login {
        width: 100%;
        padding: 13px;
        background: #2563eb;
        color: #ffffff;
        border: none;
        border-radius: 12px;
        font-size: 0.95rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-login:hover {
        background: #1d4ed8;
        transform: translateY(-1px);
        box-shadow: 0 8px 24px rgba(37, 99, 235, 0.25);
    }

    .btn-login:active {
        transform: translateY(0);
    }

    /* ===== Register Link ===== */
    .register-link {
        text-align: center;
        margin-top: 20px;
        font-size: 0.85rem;
        color: #94a3b8;
    }

    .register-link a {
        color: #2563eb;
        text-decoration: none;
        font-weight: 500;
    }

    .register-link a:hover {
        text-decoration: underline;
    }

    /* ===== Error Messages ===== */
    .alert-error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #dc2626;
        padding: 12px 16px;
        border-radius: 12px;
        font-size: 0.8rem;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-error i {
        font-size: 1rem;
        color: #dc2626;
    }

    .alert-error ul {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .alert-error ul li {
        margin-bottom: 2px;
    }

    /* ===== Responsive ===== */
    @media (max-width: 480px) {
        .login-card {
            padding: 28px 20px;
        }

        .brand h1 {
            font-size: 1.2rem;
        }

        .form-options {
            flex-direction: column;
            gap: 12px;
            align-items: flex-start;
        }

        .form-options .forgot-link {
            align-self: flex-start;
        }
    }

    /* ===== Animation ===== */
    @keyframes fadeUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .login-card {
        animation: fadeUp 0.4s ease;
    }
</style>
</head>
<body>

<div class="login-container">
    <div class="login-card">

        {{-- Brand --}}
        <div class="brand">
            <div class="icon">
                <i class="fas fa-print"></i>
            </div>
            <h1>پنل مدیریت</h1>
            <p>{{ config('app.name', 'چاپخانه') }}</p>
        </div>

        {{-- Errors --}}
        @if ($errors->any())
            <div class="alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Session Error --}}
        @if (session('error'))
            <div class="alert-error">
                <i class="fas fa-exclamation-circle"></i>
                {{ session('error') }}
            </div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('admin.login') }}" id="loginForm">
            @csrf

            {{-- Email --}}
            <div class="form-group">
                <label for="email">آدرس ایمیل</label>
                <div class="input-wrapper">
                    <span class="input-icon"><i class="fas fa-envelope"></i></span>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="example@domain.com"
                        value="{{ old('email') }}"
                        required
                        autofocus
                    >
                </div>
            </div>

            {{-- Password --}}
            <div class="form-group">
                <label for="password">رمز عبور</label>
                <div class="input-wrapper">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="رمز عبور خود را وارد کنید"
                        required
                    >
                    <button type="button" class="toggle-password" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            {{-- Options --}}
            <div class="form-options">
                <label class="remember">
                    <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    مرا به خاطر بسپار
                </label>
                <a href="#" class="forgot-link">رمز عبور را فراموش کرده‌اید؟</a>
            </div>

            {{-- Submit --}}
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                ورود به پنل مدیریت
            </button>

        </form>

        {{-- Register Link --}}
        <div class="register-link">
            حساب کاربری ندارید؟ <a href="{{ route('admin.register') }}">ثبت‌نام کنید</a>
        </div>

    </div>
</div>

<script>
    // ===== Toggle Password =====
    const toggleBtn = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    toggleBtn.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
    });

    // ===== Auto-dismiss errors on focus =====
    document.querySelectorAll('input').forEach(input => {
        input.addEventListener('focus', function() {
            const alert = document.querySelector('.alert-error');
            if (alert) {
                alert.style.transition = 'opacity 0.3s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }
        });
    });
</script>

</body>
</html>
