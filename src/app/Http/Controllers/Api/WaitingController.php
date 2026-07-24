<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WaitingList;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WaitingController extends Controller
{
    use ApiResponse;

    // 1. دریافت وضعیت صف بیمار (نفر چندم هست)
    public function getStatus($appointmentId)
    {
        try {
            $appointment = Appointment::with(['patient', 'doctor'])->findOrFail($appointmentId);

            // بررسی دسترسی (فقط خود بیمار یا ادمین)
            $user = auth()->user();
            if (!$user->isAdmin() && $appointment->patient->user_id !== $user->id) {
                return $this->error('شما دسترسی به این نوبت ندارید', 403);
            }

            $waiting = WaitingList::where('appointment_id', $appointmentId)->first();

            if (!$waiting) {
                return $this->success([
                    'in_queue' => false,
                    'message' => 'شما در صف نیستید. لطفاً به مطب مراجعه کنید.',
                ]);
            }

            // تعداد افرادی که جلوی این بیمار هستند
            $peopleAhead = WaitingList::where('doctor_id', $appointment->doctor_id)
                ->where('status', 'waiting')
                ->where('queue_number', '<', $waiting->queue_number)
                ->count();

            // تعداد کل افراد در صف
            $totalWaiting = WaitingList::where('doctor_id', $appointment->doctor_id)
                ->where('status', 'waiting')
                ->count();

            // تخمین زمان انتظار (هر نفر 15 دقیقه)
            $estimatedWaitMinutes = $peopleAhead * 15;

            return $this->success([
                'in_queue' => true,
                'queue_number' => $waiting->queue_number,
                'people_ahead' => $peopleAhead,
                'total_waiting' => $totalWaiting,
                'estimated_wait_minutes' => $estimatedWaitMinutes,
                'estimated_wait_text' => $this->getWaitTimeText($estimatedWaitMinutes),
                'your_turn' => $peopleAhead === 0,
                'status' => $waiting->status,
                'doctor_name' => $appointment->doctor->full_name,
                'appointment_time' => $appointment->start_time->format('H:i'),
            ]);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    // 2. دریافت لیست صف برای نمایش در تلویزیون
    public function getQueue($doctorId, Request $request)
    {
        try {
            $doctor = Doctor::findOrFail($doctorId);

            $waitingList = WaitingList::where('doctor_id', $doctorId)
                ->whereIn('status', ['waiting', 'in_progress'])
                ->orderBy('queue_number', 'asc')
                ->with(['patient', 'patient.user'])
                ->get();

            $queue = $waitingList->map(function ($item, $index) {
                return [
                    'queue_number' => $item->queue_number,
                    'patient_name' => $item->patient->full_name ?? 'بیمار',
                    'status' => $item->status,
                    'is_current' => $item->status === 'in_progress',
                    'is_completed' => $item->status === 'completed',
                ];
            });

            $current = $queue->firstWhere('is_current', true);

            return $this->success([
                'doctor' => [
                    'id' => $doctor->id,
                    'name' => $doctor->full_name,
                    'specialty' => $doctor->specialty?->name,
                ],
                'queue' => $queue,
                'current_patient' => $current,
                'total_waiting' => $queue->count(),
                'last_updated' => now()->toDateTimeString(),
            ]);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    // 3. صدا زدن بیمار بعدی (منشی)
    public function callNext($doctorId)
    {
        try {
            $doctor = Doctor::findOrFail($doctorId);

            // پیدا کردن اولین بیمار در صف
            $next = WaitingList::where('doctor_id', $doctorId)
                ->where('status', 'waiting')
                ->orderBy('queue_number', 'asc')
                ->first();

            if (!$next) {
                return $this->error('هیچ بیماری در صف نیست', 404);
            }

            // آپدیت وضعیت
            $next->update([
                'status' => 'in_progress',
                'called_at' => now(),
                'started_at' => now(),
            ]);

            // بروزرسانی نوبت
            $appointment = Appointment::find($next->appointment_id);
            if ($appointment) {
                $appointment->update(['status' => 'in_progress']);
            }

            return $this->success([
                'patient' => [
                    'id' => $next->patient_id,
                    'name' => $next->patient->full_name,
                    'queue_number' => $next->queue_number,
                ],
                'message' => 'بیمار صدا زده شد',
            ]);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // 4. تکمیل ویزیت بیمار
    public function complete($waitingId)
    {
        try {
            $waiting = WaitingList::findOrFail($waitingId);

            $waiting->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // بروزرسانی نوبت
            $appointment = Appointment::find($waiting->appointment_id);
            if ($appointment) {
                $appointment->update(['status' => 'completed']);
            }

            return $this->success([
                'patient_name' => $waiting->patient->full_name,
                'queue_number' => $waiting->queue_number,
                'message' => 'ویزیت بیمار تکمیل شد',
            ]);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // 5. افزودن بیمار به صف (وقتی بیمار میرسه مطب)
    public function addToQueue(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'appointment_id' => 'required|exists:appointments,id',
            'type' => 'nullable|in:walk_in,phone,online',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $appointment = Appointment::findOrFail($request->appointment_id);

            // بررسی اینکه قبلاً در صف نیست
            $exists = WaitingList::where('appointment_id', $appointment->id)->exists();
            if ($exists) {
                return $this->error('این نوبت قبلاً در صف ثبت شده است', 400);
            }

            // پیدا کردن آخرین شماره صف
            $lastQueue = WaitingList::where('doctor_id', $appointment->doctor_id)
                ->max('queue_number') ?? 0;

            $waiting = WaitingList::create([
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $appointment->doctor_id,
                'appointment_id' => $appointment->id,
                'queue_number' => $lastQueue + 1,
                'status' => 'waiting',
                'type' => $request->type ?? 'walk_in',
                'entered_at' => now(),
            ]);

            return $this->success([
                'queue_number' => $waiting->queue_number,
                'people_ahead' => 0,
                'message' => 'با موفقیت به صف اضافه شدید',
            ], 'به صف انتظار اضافه شدید');

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // 6. لغو صف (بیمار انصراف داد)
    public function cancel($waitingId)
    {
        try {
            $waiting = WaitingList::findOrFail($waitingId);
            $waiting->update(['status' => 'cancelled']);

            return $this->success(null, 'بیمار از صف حذف شد');

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // تابع کمکی برای نمایش زمان انتظار
    private function getWaitTimeText($minutes)
    {
        if ($minutes < 1) return 'کمتر از یک دقیقه';
        if ($minutes < 60) return "حدود {$minutes} دقیقه";
        $hours = floor($minutes / 60);
        $remainMinutes = $minutes % 60;
        if ($remainMinutes === 0) return "حدود {$hours} ساعت";
        return "حدود {$hours} ساعت و {$remainMinutes} دقیقه";
    }
}
