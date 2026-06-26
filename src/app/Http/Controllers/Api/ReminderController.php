<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reminder;
use App\Services\Reminder\ReminderService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ReminderController extends Controller
{
    use ApiResponse;

    protected ReminderService $reminderService;

    public function __construct(ReminderService $reminderService)
    {
        $this->reminderService = $reminderService;
    }

    /**
     * لیست یادآوری‌های من
     */
    public function myReminders(Request $request)
    {
        $user = auth()->user();
        $patient = \App\Models\Patient::where('user_id', $user->id)->first();

        if (!$patient) {
            return $this->error('بیمار یافت نشد', 404);
        }

        $reminders = Reminder::where('patient_id', $patient->id)
            ->orderBy('scheduled_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success($reminders);
    }

    /**
     * اجرای دستی پردازش یادآوری‌ها (ادمین)
     */
    public function process(Request $request)
    {
        $user = auth()->user();

        if (!$user->isAdmin()) {
            return $this->error('شما دسترسی به این بخش را ندارید', 403);
        }

        try {
            $count = $this->reminderService->processPendingReminders();
            return $this->success(['count' => $count], "{$count} یادآوری با موفقیت ارسال شد");
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تعداد یادآوری‌های معوق (ادمین)
     */
    public function pendingCount(Request $request)
    {
        $user = auth()->user();

        if (!$user->isAdmin()) {
            return $this->error('شما دسترسی به این بخش را ندارید', 403);
        }

        $count = $this->reminderService->getPendingCount();
        return $this->success(['count' => $count]);
    }
}
