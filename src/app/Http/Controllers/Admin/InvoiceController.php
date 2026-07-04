<?php
// app/Http/Controllers/Admin/InvoiceController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Enums\InvoiceStatusEnum;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
    use ApiResponse;

    /**
     * لیست فاکتورها
     */
    public function index(Request $request)
    {
        try {
            $tenantId = session('tenant_id', 1);

            $query = Invoice::where('tenant_id', $tenantId)
                ->with(['patient', 'patient.user', 'appointment']);

            // فیلتر بر اساس وضعیت
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // فیلتر بر اساس بیمار
            if ($request->has('patient_id')) {
                $query->where('patient_id', $request->patient_id);
            }

            // فیلتر بر اساس تاریخ
            if ($request->has('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            // جستجو
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('patient', function ($q2) use ($search) {
                            $q2->where('full_name', 'like', "%{$search}%");
                        });
                });
            }

            $invoices = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return $this->success($invoices);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * ایجاد فاکتور جدید
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'amount' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'status' => 'nullable|in:draft,issued,paid,cancelled,overdue',
            'due_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $request->all();
            $data['tenant_id'] = session('tenant_id', 1);

            // محاسبه مبلغ کل
            $amount = $data['amount'] ?? 0;
            $tax = $data['tax'] ?? 0;
            $discount = $data['discount'] ?? 0;
            $data['total_amount'] = $amount + $tax - $discount;
            $data['status'] = $data['status'] ?? InvoiceStatusEnum::DRAFT->value;
            $data['due_date'] = $data['due_date'] ?? now()->addDays(7)->toDateString();

            $invoice = Invoice::create($data);

            return $this->success(
                $invoice->load(['patient', 'appointment']),
                'فاکتور با موفقیت ایجاد شد',
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * نمایش فاکتور
     */
    public function show($id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $invoice = Invoice::where('tenant_id', $tenantId)
                ->with(['patient', 'patient.user', 'appointment', 'payments'])
                ->findOrFail($id);
            return $this->success($invoice);
        } catch (\Exception $e) {
            return $this->error('فاکتور یافت نشد', 404);
        }
    }

    /**
     * به‌روزرسانی فاکتور
     */
    public function update(Request $request, $id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $invoice = Invoice::where('tenant_id', $tenantId)->findOrFail($id);
        } catch (\Exception $e) {
            return $this->error('فاکتور یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'patient_id' => 'sometimes|exists:patients,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'amount' => 'sometimes|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'status' => 'sometimes|in:draft,issued,paid,cancelled,overdue',
            'due_date' => 'nullable|date',
            'paid_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $request->all();

            // محاسبه مجدد مبلغ کل
            if (isset($data['amount']) || isset($data['tax']) || isset($data['discount'])) {
                $amount = $data['amount'] ?? $invoice->amount;
                $tax = $data['tax'] ?? $invoice->tax;
                $discount = $data['discount'] ?? $invoice->discount;
                $data['total_amount'] = $amount + $tax - $discount;
            }

            $invoice->update($data);
            return $this->success(
                $invoice->fresh()->load(['patient', 'appointment']),
                'فاکتور با موفقیت به‌روزرسانی شد'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف فاکتور
     */
    public function destroy($id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $invoice = Invoice::where('tenant_id', $tenantId)->findOrFail($id);
            $invoice->delete();
            return $this->success(null, 'فاکتور با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تغییر وضعیت فاکتور
     */
    public function changeStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:draft,issued,paid,cancelled,overdue',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $tenantId = session('tenant_id', 1);
            $invoice = Invoice::where('tenant_id', $tenantId)->findOrFail($id);

            if ($request->status === 'paid') {
                $invoice->markAsPaid();
            } elseif ($request->status === 'issued') {
                $invoice->markAsIssued();
            } elseif ($request->status === 'cancelled') {
                $invoice->markAsCancelled();
            } elseif ($request->status === 'overdue') {
                $invoice->markAsOverdue();
            } else {
                $invoice->update(['status' => $request->status]);
            }

            return $this->success($invoice->fresh(), 'وضعیت فاکتور با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * چاپ فاکتور
     */
    public function print($id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $invoice = Invoice::where('tenant_id', $tenantId)
                ->with(['patient', 'patient.user', 'appointment'])
                ->findOrFail($id);

            $html = $this->generatePrintHtml($invoice);

            return response($html)->header('Content-Type', 'text/html');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * آمار فاکتورها
     */
    public function stats()
    {
        try {
            $tenantId = session('tenant_id', 1);

            $total_invoices = Invoice::where('tenant_id', $tenantId)->count();
            $total_revenue = Invoice::where('tenant_id', $tenantId)
                ->where('status', InvoiceStatusEnum::PAID->value)
                ->sum('total_amount');
            $paid_count = Invoice::where('tenant_id', $tenantId)
                ->where('status', InvoiceStatusEnum::PAID->value)
                ->count();
            $overdue_count = Invoice::where('tenant_id', $tenantId)
                ->where('status', InvoiceStatusEnum::OVERDUE->value)
                ->count();
            $draft_count = Invoice::where('tenant_id', $tenantId)
                ->where('status', InvoiceStatusEnum::DRAFT->value)
                ->count();
            $issued_count = Invoice::where('tenant_id', $tenantId)
                ->where('status', InvoiceStatusEnum::ISSUED->value)
                ->count();

            return $this->success([
                'total_invoices' => $total_invoices,
                'total_revenue' => $total_revenue,
                'paid_count' => $paid_count,
                'overdue_count' => $overdue_count,
                'draft_count' => $draft_count,
                'issued_count' => $issued_count,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }


    /**
     * تولید HTML برای چاپ
     */
    /**
     * تولید HTML برای چاپ
     */
    private function generatePrintHtml($invoice)
    {
        // ✅ دریافت مقدار string از Enum
        $statusValue = $invoice->status instanceof \App\Enums\InvoiceStatusEnum
            ? $invoice->status->value
            : $invoice->status;

        $statusLabels = [
            'draft' => 'پیش‌نویس',
            'issued' => 'صادر شده',
            'paid' => 'پرداخت شده',
            'cancelled' => 'لغو شده',
            'overdue' => 'سررسید گذشته',
        ];

        // ✅ تبدیل تاریخ به شمسی
        $createdAt = $invoice->created_at ? $this->convertToJalali($invoice->created_at) : '—';
        $dueDate = $invoice->due_date ? $this->convertToJalali($invoice->due_date) : '';

        // ✅ تبدیل اعداد به فارسی
        $amount = $this->convertToPersianNumber(number_format($invoice->amount ?? 0));
        $tax = $this->convertToPersianNumber(number_format($invoice->tax ?? 0));
        $discount = $this->convertToPersianNumber(number_format($invoice->discount ?? 0));
        $totalAmount = $this->convertToPersianNumber(number_format($invoice->total_amount ?? 0));

        // مقادیر دیگر
        $invoiceNumber = $invoice->invoice_number;
        $patientName = $invoice->patient->full_name ?? '—';
        $status = $statusLabels[$statusValue] ?? $statusValue ?? '—';
        $appointmentCode = $invoice->appointment->code ?? '—';
        $description = $invoice->description ?? 'خدمات پزشکی';

        // آیتم‌های جدول
        $itemsHtml = '';
        $items = $invoice->items ?? [];
        if (!empty($items)) {
            foreach ($items as $item) {
                $itemDesc = $item['description'] ?? '—';
                $itemAmount = $this->convertToPersianNumber(number_format($item['total'] ?? $item['amount'] ?? 0));
                $itemsHtml .= "
            <tr>
                <td>{$itemDesc}</td>
                <td>{$itemAmount}</td>
            </tr>
            ";
            }
        } else {
            $itemsHtml = "
        <tr>
            <td>{$description}</td>
            <td>{$amount}</td>
        </tr>
        ";
        }

        // مالیات
        $taxHtml = '';
        if (($invoice->tax ?? 0) > 0) {
            $taxHtml = "
        <tr>
            <td>مالیات</td>
            <td>{$tax}</td>
        </tr>
        ";
        }

        // تخفیف
        $discountHtml = '';
        if (($invoice->discount ?? 0) > 0) {
            $discountHtml = "
        <tr>
            <td>تخفیف</td>
            <td>-{$discount}</td>
        </tr>
        ";
        }

        // تاریخ سررسید
        $dueDateHtml = '';
        if (!empty($dueDate)) {
            $dueDateHtml = "
        <tr>
            <td colspan='2'><strong>تاریخ سررسید:</strong> {$dueDate}</td>
        </tr>
        ";
        }

        return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>فاکتور {$invoiceNumber}</title>
        <style>
            body { font-family: 'Tahoma', sans-serif; direction: rtl; padding: 40px; }
            .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
            .title { font-size: 24px; font-weight: bold; color: #2563eb; }
            .info { margin: 20px 0; width: 100%; }
            .info td { padding: 8px; }
            .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .table th, .table td { border: 1px solid #ddd; padding: 12px; text-align: center; }
            .table th { background-color: #f5f5f5; }
            .total { font-size: 18px; font-weight: bold; text-align: left; margin-top: 20px; }
            .footer { text-align: center; margin-top: 40px; border-top: 2px solid #333; padding-top: 20px; color: #666; }
        </style>
    </head>
    <body>
        <div class='header'>
            <div class='title'>🏥 فاکتور پزشکی</div>
            <div style='font-size: 16px; margin-top: 8px;'>شماره: <strong>{$invoiceNumber}</strong></div>
        </div>

        <table class='info'>
            <tr>
                <td><strong>بیمار:</strong> {$patientName}</td>
                <td><strong>تاریخ:</strong> {$createdAt}</td>
            </tr>
            <tr>
                <td><strong>وضعیت:</strong> {$status}</td>
                <td><strong>نوبت:</strong> {$appointmentCode}</td>
            </tr>
            {$dueDateHtml}
        </table>

        <table class='table'>
            <thead>
                <tr>
                    <th>شرح</th>
                    <th>مبلغ (تومان)</th>
                </tr>
            </thead>
            <tbody>
                {$itemsHtml}
                {$taxHtml}
                {$discountHtml}
            </tbody>
        </table>

        <div class='total'>
            مبلغ کل: {$totalAmount} تومان
        </div>

        <div class='footer'>
            با تشکر از اعتماد شما<br>
            <small>این فاکتور به صورت الکترونیکی صادر شده است.</small>
        </div>
    </body>
    </html>
    ";
    }

    /**
     * تبدیل تاریخ میلادی به شمسی
     */
    private function convertToJalali($date)
    {
        if (!$date) return '';

        $year = $date->format('Y');
        $month = $date->format('m');
        $day = $date->format('d');

        // استفاده از تابع تبدیل شمسی
        $jalali = $this->gregorianToJalali($year, $month, $day);

        return $jalali['year'] . '/' .
            str_pad($jalali['month'], 2, '0', STR_PAD_LEFT) . '/' .
            str_pad($jalali['day'], 2, '0', STR_PAD_LEFT);
    }

    /**
     * تبدیل تاریخ میلادی به شمسی
     */
    private function gregorianToJalali($g_y, $g_m, $g_d)
    {
        $g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];

        $gy = $g_y - 1600;
        $gm = $g_m - 1;
        $gd = $g_d - 1;

        $g_day_no = 365 * $gy + intdiv($gy + 3, 4) - intdiv($gy + 99, 100) + intdiv($gy + 399, 400);

        for ($i = 0; $i < $gm; ++$i) {
            $g_day_no += $g_days_in_month[$i];
        }

        if ($gm > 1 && (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0))) {
            $g_day_no++;
        }

        $g_day_no += $gd;

        $j_day_no = $g_day_no - 79;

        $j_np = intdiv($j_day_no, 12053);
        $j_day_no = $j_day_no % 12053;

        $jy = 979 + 33 * $j_np + 4 * intdiv($j_day_no, 1461);
        $j_day_no %= 1461;

        if ($j_day_no >= 366) {
            $jy += intdiv($j_day_no - 1, 365);
            $j_day_no = ($j_day_no - 1) % 365;
        }

        for ($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; ++$i) {
            $j_day_no -= $j_days_in_month[$i];
        }

        $jm = $i + 1;
        $jd = $j_day_no + 1;

        return ['year' => $jy, 'month' => $jm, 'day' => $jd];
    }

    /**
     * تبدیل اعداد انگلیسی به فارسی
     */
    private function convertToPersianNumber($number)
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        return str_replace($english, $persian, $number);
    }
}
