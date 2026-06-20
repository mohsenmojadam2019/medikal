@extends('admin.layouts.app')

@section('title', 'مدیریت نقش‌ها')
@section('page_title', '🎯 مدیریت نقش‌ها')
@section('page_description', 'لیست و مدیریت نقش‌های کاربران')

@section('content')
    <style>
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
            gap: 10px;
        }

        .btn-primary {
            padding: 8px 18px;
            background: #2563eb;
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
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
            padding: 4px 12px;
            font-size: 0.75rem;
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

        .btn-permission {
            background: #e0e7ff;
            color: #4f46e5;
        }
        .btn-permission:hover { background: #c7d2fe; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        table thead {
            background: #f8fafc;
        }

        table th {
            padding: 12px 14px;
            text-align: right;
            font-weight: 600;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
        }

        table td {
            padding: 10px 14px;
            border-bottom: 1px solid #f1f5f9;
            color: #1a2332;
        }

        table tr:hover {
            background: #f8fafc;
        }

        .badge {
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .badge-info {
            background: #dbeafe;
            color: #2563eb;
        }

        .badge-success {
            background: #d1fae5;
            color: #059669;
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

        .text-center {
            text-align: center;
        }

        .text-muted {
            color: #94a3b8;
        }

        .empty-state {
            padding: 40px 20px;
            text-align: center;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 3rem;
            color: #e2e8f0;
            display: block;
            margin-bottom: 12px;
        }
    </style>

    <div class="table-container">
        <div class="table-header">
            <h3 style="font-size:1rem;font-weight:600;color:#1a2332;">
                <i class="fas fa-shield-alt" style="color:#2563eb;"></i>
                لیست نقش‌ها
            </h3>
            <a href="{{ route('admin.roles.create') }}" class="btn-primary">
                <i class="fas fa-plus"></i> نقش جدید
            </a>
        </div>

        @if(session('success'))
            <div class="alert-success">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
            </div>
        @endif

        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>نام نقش</th>
                <th>تعداد مجوزها</th>
                <th>تعداد کاربران</th>
                <th>تاریخ ایجاد</th>
                <th>عملیات</th>
            </tr>
            </thead>
            <tbody>
            @forelse($roles as $role)
                <tr>
                    <td>{{ $role->id }}</td>
                    <td>
                        <span class="badge badge-info">{{ $role->name }}</span>
                    </td>
                    <td>{{ $role->permissions->count() }}</td>
                    <td>{{ $role->users->count() }}</td>
                    <td>{{ $role->created_at?->format('Y-m-d') ?? '-' }}</td>
                    <td>
                        <div style="display:flex;gap:4px;flex-wrap:wrap;">
                            <a href="{{ route('admin.roles.permissions', $role->id) }}" class="btn-sm btn-permission">
                                <i class="fas fa-key"></i>
                            </a>
                            <a href="{{ route('admin.roles.edit', $role->id) }}" class="btn-sm btn-edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            @if($role->name !== 'super-admin')
                                <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('آیا از حذف این نقش مطمئن هستید؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-sm btn-delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">
                        <div class="empty-state">
                            <i class="fas fa-shield-alt"></i>
                            نقش‌ای یافت نشد.
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
