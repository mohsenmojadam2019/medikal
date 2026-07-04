<?php
// app/Http/Controllers/Admin/ReminderController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reminder;
use App\Services\Reminder\ReminderService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReminderController extends Controller
{
    use ApiResponse;

    protected ReminderService $reminderService;

    public function __construct(ReminderService $reminderService)
    {
        $this->reminderService = $reminderService;
    }

    /**
     * لیست یادآوری‌ها
     */
    public function index(Request $request)
    {
        try {
            $tenantId = session('tenant_id', 1);

            $query = Reminder::where('tenant_id', $tenantId)
                ->with(['patient', 'appointment']);

            // فیلتر بر اساس وضعیت
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // فیلتر بر اساس نوع
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // فیلتر بر اساس بیمار
            if ($request->has('patient_id')) {
                $query->where('patient_id', $request->patient_id);
            }

            // جستجو
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('message', 'like', "%{$search}%")
                        ->orWhereHas('patient', function ($q2) use ($search) {
                            $q2->where('full_name', 'like', "%{$search}%");
                        });
                });
            }

            $reminders = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return $this->success($reminders);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * ایجاد یادآوری جدید
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'type' => 'required|in:sms,email',
            'message' => 'required|string',
            'scheduled_at' => 'required|date',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $request->all();
            $data['tenant_id'] = session('tenant_id', 1);
            $data['status'] = 'pending';
            $data['scheduled_at'] = now()->parse($data['scheduled_at']);

            $reminder = Reminder::create($data);

            return $this->success(
                $reminder->load(['patient', 'appointment']),
                'یادآوری با موفقیت ایجاد شد',
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * نمایش یادآوری
     */
    public function show($id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $reminder = Reminder::where('tenant_id', $tenantId)
                ->with(['patient', 'appointment'])
                ->findOrFail($id);
            return $this->success($reminder);
        } catch (\Exception $e) {
            return $this->error('یادآوری یافت نشد', 404);
        }
    }

    /**
     * به‌روزرسانی یادآوری
     */
    public function update(Request $request, $id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $reminder = Reminder::where('tenant_id', $tenantId)->findOrFail($id);
        } catch (\Exception $e) {
            return $this->error('یادآوری یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'patient_id' => 'sometimes|exists:patients,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'type' => 'sometimes|in:sms,email',
            'message' => 'sometimes|string',
            'scheduled_at' => 'sometimes|date',
            'status' => 'sometimes|in:pending,sent,failed',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $request->all();
            if (isset($data['scheduled_at'])) {
                $data['scheduled_at'] = now()->parse($data['scheduled_at']);
            }

            $reminder->update($data);
            return $this->success(
                $reminder->fresh()->load(['patient', 'appointment']),
                'یادآوری با موفقیت به‌روزرسانی شد'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف یادآوری
     */
    public function destroy($id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $reminder = Reminder::where('tenant_id', $tenantId)->findOrFail($id);
            $reminder->delete();
            return $this->success(null, 'یادآوری با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * پردازش یادآوری‌های آماده ارسال
     */
    public function process(Request $request)
    {
        try {
            $count = $this->reminderService->processPendingReminders();
            return $this->success(
                ['processed_count' => $count],
                "{$count} یادآوری با موفقیت پردازش شد"
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }


    /**
     * آمار یادآوری‌ها
     */
    public function stats()
    {
        try {
            $tenantId = session('tenant_id', 1);

            $total = Reminder::where('tenant_id', $tenantId)->count();
            $pending = Reminder::where('tenant_id', $tenantId)->where('status', 'pending')->count();
            $sent = Reminder::where('tenant_id', $tenantId)->where('status', 'sent')->count();
            $failed = Reminder::where('tenant_id', $tenantId)->where('status', 'failed')->count();

            return $this->success([
                'total' => $total,
                'pending' => $pending,
                'sent' => $sent,
                'failed' => $failed,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * دریافت تنظیمات یادآوری
     */
    public function settings()
    {
        try {
            // تنظیمات پیش‌فرض
            $settings = [
                'sms_enabled' => true,
                'email_enabled' => true,
                'reminder_time' => 24,
                'is_active' => true,
            ];

            // اگر جدول settings وجود دارد
            try {
                if (class_exists(\App\Models\Setting::class)) {
                    $dbSettings = \App\Models\Setting::where('key', 'reminder_settings')->first();
                    if ($dbSettings && $dbSettings->value) {
                        $savedSettings = json_decode($dbSettings->value, true);
                        if (is_array($savedSettings)) {
                            $settings = array_merge($settings, $savedSettings);
                        }
                    }
                }
            } catch (\Exception $e) {
                // جدول وجود ندارد - از تنظیمات پیش‌فرض استفاده کن
            }

            return $this->success($settings);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * ذخیره تنظیمات یادآوری
     */
    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sms_enabled' => 'sometimes|boolean',
            'email_enabled' => 'sometimes|boolean',
            'reminder_time' => 'sometimes|integer|min:1|max:72',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $settings = $request->all();

            // اگر مدل Setting وجود دارد و جدول موجود است
            if (class_exists(\App\Models\Setting::class)) {
                try {
                    \App\Models\Setting::updateOrCreate(
                        ['key' => 'reminder_settings'],
                        ['value' => json_encode($settings)]
                    );
                } catch (\Exception $e) {
                    // جدول وجود ندارد - در سشن ذخیره کن
                    session(['reminder_settings' => $settings]);
                }
            } else {
                // در سشن ذخیره کن
                session(['reminder_settings' => $settings]);
            }

            return $this->success($settings, 'تنظیمات با موفقیت ذخیره شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
