@extends('admin.layouts.app')

@section('title', 'مدیریت مجوزها')
@section('page_title', '🔑 مدیریت مجوزهای سیستم')
@section('page_description', 'لیست و مدیریت مجوزهای دسترسی کاربران')

@section('content')
    <style>
        /* ===== Stats Cards ===== */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }

        .stat-box {
            background: #ffffff;
            border-radius: 14px;
            padding: 16px 18px;
            border: 1px solid #e8ecf1;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all 0.2s;
        }

        .stat-box:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
        }

        .stat-box .icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .stat-box .icon.blue { background: #eff6ff; color: #2563eb; }
        .stat-box .icon.purple { background: #f5f3ff; color: #7c3aed; }
        .stat-box .icon.green { background: #ecfdf5; color: #059669; }
        .stat-box .icon.orange { background: #fffbeb; color: #d97706; }

        .stat-box .info .number {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1a2332;
            line-height: 1.2;
        }

        .stat-box .info .label {
            font-size: 0.7rem;
            color: #94a3b8;
            font-weight: 500;
        }

        /* ===== Table Container ===== */
        .table-container {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e8ecf1;
            padding: 20px;
            overflow-x: auto;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .table-header .title-section h3 {
            font-size: 1rem;
            font-weight: 600;
            color: #1a2332;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .table-header .title-section h3 i {
            color: #2563eb;
        }

        .table-header .title-section p {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 2px;
        }

        .table-header .actions {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        /* ===== Buttons ===== */
        .btn-primary {
            padding: 8px 18px;
            background: #2563eb;
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(37, 99, 235, 0.25);
            color: #fff;
        }

        .btn-sm {
            padding: 4px 10px;
            font-size: 0.7rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: 0.15s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
        }

        .btn-edit {
            background: #dbeafe;
            color: #2563eb;
        }
        .btn-edit:hover { background: #bfdbfe; }

        .btn-delete {
            background: #fee2e2;
            color: #dc2626;
        }
        .btn-delete:hover { background: #fecaca; }

        /* ===== Alert ===== */
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

        .alert-success i { font-size: 1rem; color: #059669; }

        /* ===== Permission Groups ===== */
        .permission-group {
            margin-bottom: 20px;
            background: #f8fafc;
            border-radius: 12px;
            padding: 12px 16px;
            border: 1px solid #eef2f6;
            transition: all 0.2s;
        }

        .permission-group:hover {
            border-color: #e2e8f0;
        }

        .permission-group .group-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            cursor: pointer;
            user-select: none;
        }

        .permission-group .group-header .group-title {
            font-weight: 600;
            font-size: 0.85rem;
            color: #1a2332;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .permission-group .group-header .group-title i {
            color: #2563eb;
            font-size: 0.9rem;
        }

        .permission-group .group-header .group-count {
            font-size: 0.7rem;
            color: #94a3b8;
            background: #e2e8f0;
            padding: 2px 12px;
            border-radius: 20px;
            font-weight: 600;
        }

        .permission-group .group-items {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .permission-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ffffff;
            padding: 5px 12px 5px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
            color: #475569;
            border: 1px solid #e2e8f0;
            transition: all 0.15s;
            font-weight: 500;
        }

        .permission-tag:hover {
            border-color: #2563eb;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.08);
        }

        .permission-tag .tag-icon {
            color: #94a3b8;
            font-size: 0.6rem;
        }

        .permission-tag .tag-actions {
            display: inline-flex;
            gap: 2px;
            margin-right: 4px;
        }

        .permission-tag .tag-actions a {
            color: #94a3b8;
            transition: color 0.15s;
            font-size: 0.6rem;
        }

        .permission-tag .tag-actions a:hover { color: #2563eb; }
        .permission-tag .tag-actions .delete-btn {
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 0.6rem;
            padding: 0;
            transition: color 0.15s;
        }
        .permission-tag .tag-actions .delete-btn:hover { color: #dc2626; }

        /* ===== Empty State ===== */
        .empty-state {
            padding: 50px 20px;
            text-align: center;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 3.5rem;
            color: #e2e8f0;
            display: block;
            margin-bottom: 12px;
        }

        .empty-state h4 {
            color: #475569;
            font-size: 1rem;
            margin-bottom: 4px;
        }

        .empty-state p {
            font-size: 0.85rem;
        }

        /* ===== Responsive ===== */
        @media (max-width: 600px) {
            .stats-row {
                grid-template-columns: 1fr 1fr;
            }

            .table-header {
                flex-direction: column;
                align-items: stretch;
            }

            .table-header .actions {
                justify-content: flex-start;
            }

            .permission-group .group-items {
                gap: 4px;
            }

            .permission-tag {
                font-size: 0.7rem;
                padding: 4px 10px 4px 8px;
            }
        }

        @media (max-width: 400px) {
            .stats-row {
                grid-template-columns: 1fr;
            }
        }
    </style>

    {{-- ===== Stats ===== --}}
    <div class="stats-row">
        <div class="stat-box">
            <div class="icon blue"><i class="fas fa-key"></i></div>
            <div class="info">
                <div class="number">{{ $permissions->count() }}</div>
                <div class="label">کل مجوزها</div>
            </div>
        </div>
        <div class="stat-box">
            <div class="icon purple"><i class="fas fa-layer-group"></i></div>
            <div class="info">
                <div class="number">{{ count($groupedPermissions) }}</div>
                <div class="label">گروه‌های مجوز</div>
            </div>
        </div>
        <div class="stat-box">
            <div class="icon green"><i class="fas fa-shield-alt"></i></div>
            <div class="info">
                <div class="number">{{ \Spatie\Permission\Models\Role::count() }}</div>
                <div class="label">نقش‌های فعال</div>
            </div>
        </div>
        <div class="stat-box">
            <div class="icon orange"><i class="fas fa-users"></i></div>
            <div class="info">
                <div class="number">{{ \App\Models\User::count() }}</div>
                <div class="label">کاربران سیستم</div>
            </div>
        </div>
    </div>

    {{-- ===== Table ===== --}}
    <div class="table-container">
        <div class="table-header">
            <div class="title-section">
                <h3><i class="fas fa-key"></i> مجوزهای دسترسی</h3>
                <p>مدیریت مجوزهای دسترسی کاربران به بخش‌های مختلف سیستم</p>
            </div>
            <div class="actions">
                <a href="{{ route('admin.permissions.create') }}" class="btn-primary">
                    <i class="fas fa-plus"></i> مجوز جدید
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert-success">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
            </div>
        @endif

        @if(isset($groupedPermissions) && count($groupedPermissions) > 0)
            @foreach($groupedPermissions as $group => $perms)
                <div class="permission-group">
                    <div class="group-header" onclick="toggleGroup(this)">
                        <div class="group-title">
                            <i class="fas fa-folder"></i>
                            {{ ucfirst(str_replace('_', ' ', $group)) }}
                        </div>
                        <span class="group-count">{{ count($perms) }} مجوز</span>
                    </div>
                    <div class="group-items">
                        @foreach($perms as $permission)
                            <span class="permission-tag">
                            <span class="tag-icon"><i class="fas fa-circle" style="font-size:0.3rem;"></i></span>
                            {{ $permission->name }}
                            <span class="tag-actions">
                                <a href="{{ route('admin.permissions.edit', $permission->id) }}" title="ویرایش">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @if(!in_array($permission->name, ['view_users', 'edit_users', 'delete_users', 'view_products', 'edit_products', 'delete_products']))
                                    <form action="{{ route('admin.permissions.destroy', $permission->id) }}" method="POST" style="display:inline;"
                                          onsubmit="return confirm('آیا از حذف مجوز «{{ $permission->name }}» مطمئن هستید؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="delete-btn" title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </span>
                        </span>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @else
            <div class="empty-state">
                <i class="fas fa-key"></i>
                <h4>هیچ مجوزی یافت نشد</h4>
                <p>برای شروع، اولین مجوز را ایجاد کنید.</p>
                <a href="{{ route('admin.permissions.create') }}" class="btn-primary" style="margin-top:12px;">
                    <i class="fas fa-plus"></i> ایجاد مجوز
                </a>
            </div>
        @endif
    </div>

    <script>
        function toggleGroup(header) {
            const items = header.nextElementSibling;
            if (items) {
                items.style.display = items.style.display === 'none' ? 'flex' : 'none';
            }
        }
    </script>
@endsection
