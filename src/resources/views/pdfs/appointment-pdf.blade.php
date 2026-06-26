<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>گزارش نوبت‌ها</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 20px;
            margin: 0;
        }
        .header .sub {
            font-size: 14px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th {
            background: #f0f0f0;
            padding: 8px 10px;
            border: 1px solid #ddd;
            text-align: center;
            font-weight: bold;
        }
        td {
            padding: 6px 10px;
            border: 1px solid #ddd;
            text-align: center;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #999;
            font-size: 11px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .status-pending { color: #f59e0b; }
        .status-confirmed { color: #3b82f6; }
        .status-completed { color: #22c55e; }
        .status-cancelled { color: #ef4444; }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 گزارش نوبت‌ها</h1>
        <div class="sub">
            تاریخ: {{ now()->format('Y/m/d H:i') }}
            @if(isset($filters['from_date']) && isset($filters['to_date']))
                <br>از {{ $filters['from_date'] }} تا {{ $filters['to_date'] }}
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>کد نوبت</th>
                <th>بیمار</th>
                <th>پزشک</th>
                <th>تاریخ</th>
                <th>ساعت</th>
                <th>وضعیت</th>
                <th>هزینه</th>
            </tr>
        </thead>
        <tbody>
            @foreach($appointments as $index => $appointment)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $appointment->code }}</td>
                <td>{{ $appointment->patient->full_name ?? '—' }}</td>
                <td>{{ $appointment->doctor->full_name ?? '—' }}</td>
                <td>{{ $appointment->date->format('Y/m/d') }}</td>
                <td>{{ $appointment->start_time->format('H:i') }}</td>
                <td>
                    <span class="badge" style="background: {{ $appointment->status_color }}">
                        {{ $appointment->status_label }}
                    </span>
                </td>
                <td>{{ number_format($appointment->fee ?? 0) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>تعداد کل نوبت‌ها: {{ $appointments->count() }}</p>
        <p>این گزارش توسط سامانه مدیریت سلامت تولید شده است.</p>
    </div>
</body>
</html>
