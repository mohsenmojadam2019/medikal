@extends('admin.layouts.app')

@section('title', 'تنظیمات سیستم')
@section('page_title', '⚙️ تنظیمات سیستم')
@section('page_description', 'مدیریت تنظیمات عمومی برنامه')

@section('content')
    <style>
        .settings-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e8ecf1;
            padding: 24px;
            margin-bottom: 20px;
        }

        .settings-card .card-title {
            font-size: 1rem;
            font-weight: 600;
            color: #1a2332;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .settings-card .card-title i {
            color: #2563eb;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 5px;
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
            font-size: 0.8rem;
            margin-top: 4px;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .form-check input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #2563eb;
            border-radius: 4px;
            cursor: pointer;
        }

        .form-check .form-check-label {
            font-size: 0.9rem;
            color: #475569;
            cursor: pointer;
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

        .alert-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #059669;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success i {
            font-size: 1rem;
            color: #059669;
        }

        .alert-danger {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-danger i {
            font-size: 1rem;
            color: #dc2626;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="settings-grid">


        {{-- اطلاعات سیستم --}}
        <div class="settings-card">
            <div class="card-title">
                <i class="fas fa-server"></i>
                اطلاعات سیستم
            </div>

            <div style="display:flex;flex-direction:column;gap:12px;">
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                    <span style="color:#64748b;">نسخه Laravel</span>
                    <span style="font-weight:600;color:#1a2332;">{{ app()->version() }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                    <span style="color:#64748b;">نسخه PHP</span>
                    <span style="font-weight:600;color:#1a2332;">{{ phpversion() }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                    <span style="color:#64748b;">سیستم عامل</span>
                    <span style="font-weight:600;color:#1a2332;">{{ php_uname('s') }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                    <span style="color:#64748b;">حافظه مصرفی</span>
                    <span style="font-weight:600;color:#1a2332;">{{ number_format(memory_get_usage() / 1024 / 1024, 2) }} MB</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                    <span style="color:#64748b;">حداکثر حافظه</span>
                    <span style="font-weight:600;color:#1a2332;">{{ ini_get('memory_limit') }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                    <span style="color:#64748b;">زمان اجرا</span>
                    <span style="font-weight:600;color:#1a2332;">{{ ini_get('max_execution_time') }} ثانیه</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;">
                    <span style="color:#64748b;">وضعیت Cache</span>
                    <span style="font-weight:600;color:#1a2332;">
                    @if(config('cache.default') == 'file')
                            <span style="color:#059669;">✅ فایل</span>
                        @elseif(config('cache.default') == 'redis')
                            <span style="color:#7c3aed;">🔴 Redis</span>
                        @else
                            <span style="color:#d97706;">{{ config('cache.default') }}</span>
                        @endif
                </span>
                </div>
            </div>

            <hr style="border:1px solid #e8ecf1;margin:16px 0;">

            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button onclick="location.reload();" class="btn-save" style="background:#64748b;padding:8px 16px;font-size:0.8rem;">
                    <i class="fas fa-sync"></i> رفرش
                </button>
                <button onclick="if(confirm('آیا می‌خواهید کش سیستم را پاک کنید؟')){ location.href='{{ route('admin.settings.clear-cache') }}'; }"
                        class="btn-save" style="background:#d97706;padding:8px 16px;font-size:0.8rem;">
                    <i class="fas fa-broom"></i> پاک کردن کش
                </button>
            </div>
        </div>
    </div>
@endsection
