<?php

namespace App\Services\Reminder;

use App\Models\Reminder;
use App\Models\Appointment;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Services\Sms\SmsManager;

class ReminderService
{
    protected SmsManager $smsManager;

    public function __construct(SmsManager $smsManager)
    {
        $this->smsManager = $smsManager;
    }

    /**
     * ایجاد یادآوری برای نوبت
     */
    public function createReminders(Appointment $appointment): void
    {
        $patient = $appointment->patient;
        $doctor = $appointment->doctor;

        // ۱. یادآوری ۲۴ ساعت قبل
        $this->createReminder(
            $appointment,
            $patient,
            'sms',
            $appointment->date->copy()->subHours(24),
            $this->getReminderMessage($appointment, $patient, $doctor, '24h')
        );

        // ۲. یادآوری ۱ ساعت قبل
        $this->createReminder(
            $appointment,
            $patient,
            'sms',
            $appointment->date->copy()->subHours(1),
            $this->getReminderMessage($appointment, $patient, $doctor, '1h')
        );

        // ۳. یادآوری ایمیل (اختیاری)
        if ($patient->email) {
            $this->createReminder(
                $appointment,
                $patient,
                'email',
                $appointment->date->copy()->subHours(24),
                $this->getReminderMessage($appointment, $patient, $doctor, 'email')
            );
        }
    }

    /**
     * ایجاد یک یادآوری
     */
    private function createReminder(
        Appointment $appointment,
        Patient $patient,
        string $type,
        Carbon $scheduledAt,
        string $message
    ): void {
        Reminder::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'type' => $type,
            'status' => 'pending',
            'scheduled_at' => $scheduledAt,
            'message' => $message,
            'data' => [
                'appointment_code' => $appointment->code,
                'doctor_name' => $appointment->doctor->full_name,
                'date' => $appointment->date->format('Y/m/d'),
                'time' => $appointment->start_time->format('H:i'),
                'cancel_link' => route('appointment.cancel', $appointment->id),
                'reschedule_link' => route('appointment.reschedule', $appointment->id),
            ],
        ]);
    }

    /**
     * تولید متن پیامک یادآوری
     */
    private function getReminderMessage(
        Appointment $appointment,
        Patient $patient,
        $doctor,
        string $type
    ): string {
        $date = $appointment->date->format('Y/m/d');
        $time = $appointment->start_time->format('H:i');
        $cancelLink = route('appointment.cancel', $appointment->id);
        $rescheduleLink = route('appointment.reschedule', $appointment->id);

        if ($type === '24h') {
            return "🩺 یادآوری نوبت\n\n" .
                   "بیمار گرامی {$patient->full_name}\n" .
                   "نوبت شما با دکتر {$doctor->full_name}\n" .
                   "تاریخ: {$date}\n" .
                   "ساعت: {$time}\n\n" .
                   "🔗 لغو نوبت: {$cancelLink}\n" .
                   "🔗 تغییر زمان: {$rescheduleLink}\n\n" .
                   "در صورت عدم نیاز، نوبت را لغو کنید.";
        }

        if ($type === '1h') {
            return "🔔 یادآوری فوری نوبت\n\n" .
                   "بیمار گرامی {$patient->full_name}\n" .
                   "نوبت شما با دکتر {$doctor->full_name}\n" .
                   "ساعت {$time} امروز {$date}\n\n" .
                   "✅ لطفاً准时 حضور داشته باشید.";
        }

        if ($type === 'email') {
            return "یادآوری نوبت پزشکی\n\n" .
                   "بیمار گرامی {$patient->full_name}\n" .
                   "نوبت شما با دکتر {$doctor->full_name}\n" .
                   "تاریخ: {$date}\n" .
                   "ساعت: {$time}\n\n" .
                   "آدرس مطب: {$doctor->clinic_address}\n" .
                   "تلفن: {$doctor->clinic_phone}\n\n" .
                   "برای لغو نوبت: {$cancelLink}\n" .
                   "برای تغییر زمان: {$rescheduleLink}";
        }

        return "یادآوری نوبت دکتر {$doctor->full_name} در {$date} ساعت {$time}";
    }

    /**
     * پردازش یادآوری‌های آماده ارسال
     */
    public function processPendingReminders(): int
    {
        $reminders = Reminder::pending()->get();
        $count = 0;

        foreach ($reminders as $reminder) {
            try {
                $patient = $reminder->patient;
                $appointment = $reminder->appointment;

                if (!$patient || !$appointment) {
                    $reminder->markAsFailed();
                    continue;
                }

                if ($reminder->type === 'sms' && $patient->phone) {
                    $this->sendSmsReminder($reminder, $patient);
                    $reminder->markAsSent();
                    $count++;
                } elseif ($reminder->type === 'email' && $patient->email) {
                    $this->sendEmailReminder($reminder, $patient);
                    $reminder->markAsSent();
                    $count++;
                } else {
                    $reminder->markAsFailed();
                }

            } catch (\Exception $e) {
                Log::error('Reminder processing failed', [
                    'reminder_id' => $reminder->id,
                    'error' => $e->getMessage(),
                ]);
                $reminder->markAsFailed();
            }
        }

        return $count;
    }

    /**
     * ارسال پیامک یادآوری
     */
    private function sendSmsReminder(Reminder $reminder, Patient $patient): void
    {
        try {
            $this->smsManager->send($patient->phone, $reminder->message);
            Log::info('SMS reminder sent', [
                'reminder_id' => $reminder->id,
                'phone' => $patient->phone,
            ]);
        } catch (\Exception $e) {
            Log::error('SMS reminder failed', [
                'reminder_id' => $reminder->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * ارسال ایمیل یادآوری
     */
    private function sendEmailReminder(Reminder $reminder, Patient $patient): void
    {
        // استفاده از سیستم ایمیل لاراول
        try {
            \Illuminate\Support\Facades\Mail::raw($reminder->message, function ($message) use ($patient) {
                $message->to($patient->email)
                    ->subject('یادآوری نوبت پزشکی');
            });
            Log::info('Email reminder sent', [
                'reminder_id' => $reminder->id,
                'email' => $patient->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Email reminder failed', [
                'reminder_id' => $reminder->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * دریافت تعداد یادآوری‌های معوق
     */
    public function getPendingCount(): int
    {
        return Reminder::pending()->count();
    }
}
