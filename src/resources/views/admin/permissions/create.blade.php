@extends('admin.layouts.app')

@section('title', 'ویرایش مجوز')
@section('page_title', '✏️ ویرایش مجوز')
@section('page_description', 'ویرایش اطلاعات مجوز دسترسی')

@section('content')
    <style>
        .form-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e8ecf1;
            padding: 28px 32px;
            max-width: 600px;
            margin: 0 auto;
        }

        .form-card .form-header {
            margin-bottom: 24px;
            text-align: center;
        }

        .form-card .form-header .icon {
            width: 56px;
            height: 56px;
            background: #f5f3ff;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 1.5rem;
            color: #7c3aed;
        }

        .form-card .form-header h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1a2332;
        }

        .form-card .form-header p {
            font-size: 0.8rem;
            color: #94a3b8;
            margin-top: 2px;
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
            direction: ltr;
        }

        .form-group .form-control:focus {
            border-color: #7c3aed;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.08);
        }

        .form-group .form-control.is-invalid {
            border-color: #dc2626;
        }

        .form-group .invalid-feedback {
            color: #dc2626;
            font-size: 0.78rem;
            margin-top: 4px;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 8px;
            flex-wrap: wrap;
        }

        .btn-save {
            padding: 11px 28px;
            background: #7c3aed;
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
            background: #6d28d9;
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(124, 58, 237, 0.25);
        }

        .btn-back {
            padding: 11px 28px;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-back:hover {
            background: #e2e8f0;
            color: #1a2332;
        }

        .permission-badge {
            display: inline-block;
            background: #f5f3ff;
            color: #7c3aed;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            border: 1px solid #e9d5ff;
        }

        @media (max-width: 480px) {
            .form-card {
                padding: 20px 16px;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-actions .btn-save,
            .form-actions .btn-back {
                justify-content: center;
            }
        }
    </style>

    <div class="form-card">
        <div class="form-header">
            <div class="icon"><i class="fas fa-pen"></i></div>
            <h3>ویرایش مجوز</h3>
            <p>در حال ویرایش: <span class="permission-badge">{{ $permission->name }}</span></p>
        </div>

        <form method="POST" action="{{ route('admin.permissions.update', $permission->id) }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="name">نام مجوز <span class="required">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror"
                       id="name" name="name" value="{{ old('name', $permission->name) }}" required>
                @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> بروزرسانی
                </button>
                <a href="{{ route('admin.permissions.index') }}" class="btn-back">
                    <i class="fas fa-arrow-right"></i> بازگشت
                </a>
            </div>
        </form>
    </div>
@endsection
