@extends('admin.layouts.app')

@section('title', 'ویرایش نقش')
@section('page_title', '✏️ ویرایش نقش')
@section('page_description', 'ویرایش اطلاعات نقش')

@section('content')
    <style>
        .form-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e8ecf1;
            padding: 24px;
            max-width: 700px;
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

        .permissions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 8px;
            max-height: 300px;
            overflow-y: auto;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
        }

        .permissions-grid .form-check {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 6px;
            transition: 0.15s;
        }

        .permissions-grid .form-check:hover {
            background: #e2e8f0;
        }

        .permissions-grid .form-check input[type="checkbox"] {
            width: 14px;
            height: 14px;
            accent-color: #2563eb;
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

        .select-all {
            margin-bottom: 10px;
        }

        .select-all label {
            font-weight: 600;
            color: #2563eb;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .select-all input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #2563eb;
            cursor: pointer;
        }
    </style>

    <div class="form-card">
        <form method="POST" action="{{ route('admin.roles.update', $role->id) }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="name">نام نقش</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror"
                       id="name" name="name" value="{{ old('name', $role->name) }}" required>
                @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label>مجوزها</label>
                <div class="select-all">
                    <label>
                        <input type="checkbox" id="selectAllPermissions">
                        انتخاب همه مجوزها
                    </label>
                </div>
                <div class="permissions-grid">
                    @foreach($permissions as $permission)
                        <div class="form-check">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                   id="perm-{{ $permission->id }}"
                                {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}>
                            <label for="perm-{{ $permission->id }}">{{ $permission->name }}</label>
                        </div>
                    @endforeach
                </div>
            </div>

            <div style="display:flex;gap:10px;margin-top:8px;">
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> بروزرسانی
                </button>
                <a href="{{ route('admin.roles.index') }}" class="btn-back">
                    <i class="fas fa-arrow-right"></i> بازگشت
                </a>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('selectAllPermissions')?.addEventListener('change', function() {
            document.querySelectorAll('input[name="permissions[]"]').forEach(cb => cb.checked = this.checked);
        });
    </script>
@endsection
