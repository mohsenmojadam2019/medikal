@extends('admin.layouts.app')

@section('title', 'مدیریت مجوزهای نقش')
@section('page_title', '🔑 مدیریت مجوزهای نقش')
@section('page_description', 'مدیریت مجوزهای نقش: ' . $role->name)

@section('content')
    <style>
        .form-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e8ecf1;
            padding: 24px;
            max-width: 800px;
            margin: 0 auto;
        }

        .form-card .role-info {
            background: #f8fafc;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .form-card .role-info .role-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1a2332;
        }

        .form-card .role-info .role-name .badge {
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #dbeafe;
            color: #2563eb;
        }

        .form-card .role-info .role-count {
            color: #64748b;
            font-size: 0.85rem;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .permissions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 8px;
            max-height: 400px;
            overflow-y: auto;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
        }

        .permissions-grid .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.82rem;
            cursor: pointer;
            padding: 6px 10px;
            border-radius: 6px;
            transition: 0.15s;
        }

        .permissions-grid .form-check:hover {
            background: #e2e8f0;
        }

        .permissions-grid .form-check input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #2563eb;
            cursor: pointer;
            flex-shrink: 0;
        }

        .permissions-grid .form-check label {
            cursor: pointer;
            color: #475569;
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
            margin-bottom: 12px;
            padding: 8px 12px;
            background: #f1f5f9;
            border-radius: 8px;
        }

        .select-all label {
            font-weight: 600;
            color: #2563eb;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .select-all input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #2563eb;
            cursor: pointer;
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

        .permission-group {
            margin-bottom: 12px;
        }

        .permission-group .group-title {
            font-weight: 600;
            color: #1a2332;
            padding: 6px 0;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 6px;
            font-size: 0.85rem;
        }

        .permission-group .group-items {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
            gap: 4px;
            padding: 4px 0;
        }
    </style>

    <div class="form-card">
        @if(session('success'))
            <div class="alert-success">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
            </div>
        @endif

        <div class="role-info">
            <div>
            <span class="role-name">
                <i class="fas fa-shield-alt" style="color:#2563eb;"></i>
                {{ $role->name }}
                <span class="badge">{{ $role->permissions->count() }} مجوز</span>
            </span>
            </div>
            <div class="role-count">
                <i class="fas fa-users"></i>
                {{ $role->users->count() }} کاربر
                <span style="margin:0 8px;">|</span>
                <i class="fas fa-calendar-alt"></i>
                {{ $role->created_at?->format('Y-m-d') ?? '-' }}
            </div>
        </div>

        <form method="POST" action="{{ route('admin.roles.sync-permissions', $role->id) }}">
            @csrf

            <div class="select-all">
                <label>
                    <input type="checkbox" id="selectAllPermissions">
                    <i class="fas fa-check-double"></i>
                    انتخاب همه مجوزها
                </label>
            </div>

            <div class="permissions-grid">
                @php
                    $groups = [];
                    foreach($permissions as $permission) {
                        $parts = explode('_', $permission->name);
                        $group = count($parts) > 1 ? $parts[0] : 'other';
                        if (!isset($groups[$group])) {
                            $groups[$group] = [];
                        }
                        $groups[$group][] = $permission;
                    }
                @endphp

                @foreach($groups as $group => $perms)
                    <div class="permission-group">
                        <div class="group-title">
                            <i class="fas fa-folder-open" style="color:#2563eb;font-size:0.7rem;"></i>
                            {{ ucfirst($group) }}
                        </div>
                        <div class="group-items">
                            @foreach($perms as $permission)
                                <div class="form-check">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                           id="perm-{{ $permission->id }}"
                                        {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}>
                                    <label for="perm-{{ $permission->id }}">{{ $permission->name }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="display:flex;gap:10px;margin-top:16px;flex-wrap:wrap;">
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> ذخیره مجوزها
                </button>
                <a href="{{ route('admin.roles.index') }}" class="btn-back">
                    <i class="fas fa-arrow-right"></i> بازگشت به لیست نقش‌ها
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
