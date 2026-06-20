@extends('admin.layouts.app')

@section('title', 'مدیریت کاربران')
@section('page_title', '👥 مدیریت کاربران')
@section('page_description', 'لیست و مدیریت کاربران سیستم')

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

        .table-header .search-box {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .table-header .search-box input {
            padding: 8px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.85rem;
            outline: none;
            transition: 0.2s;
            background: #f8fafc;
        }

        .table-header .search-box input:focus {
            border-color: #2563eb;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08);
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

        .btn-edit:hover {
            background: #bfdbfe;
        }

        .btn-delete {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-delete:hover {
            background: #fecaca;
        }

        .btn-toggle {
            background: #fef3c7;
            color: #d97706;
        }

        .btn-toggle:hover {
            background: #fde68a;
        }

        .btn-toggle.active {
            background: #d1fae5;
            color: #059669;
        }

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

        .badge-success {
            background: #d1fae5;
            color: #059669;
        }

        .badge-danger {
            background: #fee2e2;
            color: #dc2626;
        }

        .badge-warning {
            background: #fef3c7;
            color: #d97706;
        }

        .badge-info {
            background: #dbeafe;
            color: #2563eb;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 4px;
            margin-top: 16px;
        }

        .pagination a, .pagination span {
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            color: #475569;
            text-decoration: none;
            font-size: 0.8rem;
            transition: 0.15s;
        }

        .pagination a:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .pagination .active span {
            background: #2563eb;
            color: #fff;
            border-color: #2563eb;
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
                <i class="fas fa-users" style="color:#2563eb;"></i>
                لیست کاربران
            </h3>

            <div class="search-box">
                <form method="GET" action="{{ route('admin.users.index') }}" style="display:flex;gap:8px;align-items:center;">
                    <input type="text" name="search" placeholder="جستجو..." value="{{ request('search') }}">
                    <button type="submit" class="btn-primary" style="padding:8px 14px;">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                <a href="{{ route('admin.users.create') }}" class="btn-primary">
                    <i class="fas fa-plus"></i> کاربر جدید
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert-success">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert-success" style="background:#fef2f2;border-color:#fecaca;color:#dc2626;">
                <i class="fas fa-exclamation-circle"></i>
                {{ session('error') }}
            </div>
        @endif

        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>نام</th>
                <th>ایمیل</th>
                <th>شماره موبایل</th>
                <th>نقش</th>
                <th>وضعیت</th>
                <th>تاریخ ثبت</th>
                <th>عملیات</th>
            </tr>
            </thead>
            <tbody>
            @forelse($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name ?? '-' }}</td>
                    <td>{{ $user->email ?? '-' }}</td>
                    <td>{{ $user->phone ?? '-' }}</td>
                    <td>
                        @foreach($user->roles as $role)
                            <span class="badge badge-info">{{ $role->name }}</span>
                        @endforeach
                        @if($user->roles->isEmpty())
                            <span class="badge badge-warning">بدون نقش</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $user->is_active ? 'badge-success' : 'badge-danger' }}">
                            {{ $user->is_active ? 'فعال' : 'غیرفعال' }}
                        </span>
                    </td>
                    <td>{{ $user->created_at?->format('Y-m-d') ?? '-' }}</td>
                    <td>
                        <div style="display:flex;gap:4px;flex-wrap:wrap;">
                            <a href="{{ route('admin.users.edit', $user->id) }}" class="btn-sm btn-edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.users.toggle-status', $user->id) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn-sm btn-toggle {{ $user->is_active ? 'active' : '' }}">
                                    <i class="fas {{ $user->is_active ? 'fa-check-circle' : 'fa-times-circle' }}"></i>
                                </button>
                            </form>
                            @if($user->id !== auth()->id())
                                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('آیا از حذف این کاربر مطمئن هستید؟')">
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
                    <td colspan="8" class="text-center text-muted">
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            کاربری یافت نشد.
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <div class="pagination">
            {{ $users->links() }}
        </div>
    </div>
@endsection
