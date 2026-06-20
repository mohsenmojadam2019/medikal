@extends('admin.layouts.app')

@section('title', 'ویرایش مجوز')
@section('page_title', '✏️ ویرایش مجوز')
@section('page_description', 'ویرایش اطلاعات مجوز')

@section('content')
    <style>
        .form-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e8ecf1;
            padding: 24px;
            max-width: 600px;
            margin: 0 auto;
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

        .btn-back {
            padding: 11px 28px;
            background: #64748b;
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
            text-decoration: none;
        }

        .btn-back:hover {
            background: #475569;
            color: #fff;
        }
    </style>

    <div class="form-card">
        <form method="POST" action="{{ route('admin.permissions.update', $permission->id) }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="name">نام مجوز</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror"
                       id="name" name="name" value="{{ old('name', $permission->name) }}" required>
                @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div style="display:flex;gap:10px;margin-top:8px;">
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
