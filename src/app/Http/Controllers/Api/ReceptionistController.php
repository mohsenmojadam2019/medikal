<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Receptionist\ReceptionistService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReceptionistController extends Controller
{
    use ApiResponse;

    protected ReceptionistService $receptionistService;

    public function __construct(ReceptionistService $receptionistService)
    {
        $this->receptionistService = $receptionistService;
    }

    // ============================================================
    // WAITING LIST
    // ============================================================

    /**
     * افزودن بیمار به صف انتظار
     */
    public function addToWaitingList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'type' => 'nullable|in:walk_in,phone,online',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $waiting = $this->receptionistService->addToWaitingList($request->all());
            return $this->success($waiting, 'بیمار با موفقیت به صف انتظار اضافه شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت لیست صف انتظار
     */
    public function waitingList(Request $request, int $doctorId)
    {
        $list = $this->receptionistService->getWaitingList(
            $doctorId,
            $request->only(['status', 'type']),
            $request->get('per_page', 20)
        );
        return $this->success($list);
    }

    /**
     * دریافت تعداد افراد در صف
     */
    public function waitingCount(int $doctorId)
    {
        $count = $this->receptionistService->getCurrentWaitingCount($doctorId);
        return $this->success(['count' => $count]);
    }

    /**
     * صدا زدن بیمار (شروع ویزیت)
     */
    public function callPatient(int $waitingId)
    {
        try {
            $waiting = $this->receptionistService->callPatient($waitingId);
            return $this->success($waiting, 'بیمار با موفقیت صدا زده شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تکمیل ویزیت بیمار
     */
    public function completePatient(int $waitingId)
    {
        try {
            $waiting = $this->receptionistService->completePatient($waitingId);
            return $this->success($waiting, 'ویزیت بیمار با موفقیت تکمیل شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * لغو صف انتظار
     */
    public function cancelWaiting(int $waitingId)
    {
        try {
            $waiting = $this->receptionistService->cancelWaiting($waitingId);
            return $this->success($waiting, 'بیمار از صف انتظار حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // PHONE APPOINTMENTS
    // ============================================================

    /**
     * ثبت نوبت تلفنی
     */
    public function createPhoneAppointment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required|exists:doctors,id',
            'patient_name' => 'required_without:patient_id|string|max:255',
            'patient_id' => 'nullable|exists:patients,id',
            'mobile' => 'nullable|regex:/^09[0-9]{9}$/',
            'national_code' => 'nullable|string|size:10',
            'caller_name' => 'nullable|string|max:255',
            'caller_phone' => 'nullable|string|max:20',
            'caller_relation' => 'nullable|string|max:50',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
            'reason' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $phoneAppointment = $this->receptionistService->createPhoneAppointment($request->all());
            return $this->success($phoneAppointment, 'نوبت تلفنی با موفقیت ثبت شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تایید نوبت تلفنی
     */
    public function confirmPhoneAppointment(int $phoneAppointmentId)
    {
        try {
            $phoneAppointment = $this->receptionistService->confirmPhoneAppointment($phoneAppointmentId);
            return $this->success($phoneAppointment, 'نوبت تلفنی با موفقیت تایید شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * لغو نوبت تلفنی
     */
    public function cancelPhoneAppointment(int $phoneAppointmentId)
    {
        try {
            $phoneAppointment = $this->receptionistService->cancelPhoneAppointment($phoneAppointmentId);
            return $this->success($phoneAppointment, 'نوبت تلفنی با موفقیت لغو شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * لیست نوبت‌های تلفنی
     */
    public function phoneAppointments(Request $request)
    {
        $appointments = $this->receptionistService->getPhoneAppointments(
            $request->all(),
            $request->get('per_page', 20)
        );
        return $this->success($appointments);
    }

    // ============================================================
    // APPOINTMENT CARDS
    // ============================================================

    /**
     * تولید کارت نوبت
     */
    public function generateCard(int $appointmentId)
    {
        try {
            $card = $this->receptionistService->generateAppointmentCard($appointmentId);
            return $this->success($card, 'کارت نوبت با موفقیت تولید شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * چاپ کارت نوبت
     */
    public function printCard(int $cardId)
    {
        try {
            $card = $this->receptionistService->printAppointmentCard($cardId);
            return $this->success($card, 'کارت نوبت با موفقیت چاپ شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت کارت نوبت
     */
    public function getCard(int $appointmentId)
    {
        $card = $this->receptionistService->getAppointmentCard($appointmentId);
        if (!$card) {
            return $this->error('کارت نوبت یافت نشد', 404);
        }
        return $this->success($card);
    }

    // ============================================================
    // SETTINGS
    // ============================================================

    /**
     * دریافت تنظیمات پنل منشی
     */
    public function getSettings(Request $request)
    {
        $clinicId = $request->get('clinic_id', 1);
        $settings = $this->receptionistService->getSettings($clinicId);
        return $this->success($settings);
    }

    /**
     * بروزرسانی تنظیمات پنل منشی
     */
    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clinic_id' => 'required|exists:clinics,id',
            'allow_walk_in' => 'nullable|boolean',
            'allow_phone_booking' => 'nullable|boolean',
            'print_appointment_card' => 'nullable|boolean',
            'max_walk_in_per_day' => 'nullable|integer|min:1|max:100',
            'default_appointment_duration' => 'nullable|integer|min:15|max:120',
            'notification_settings' => 'nullable|array',
            'display_settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $settings = $this->receptionistService->updateSettings(
                $request->clinic_id,
                $request->except('clinic_id')
            );
            return $this->success($settings, 'تنظیمات با موفقیت بروزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // DASHBOARD
    // ============================================================

    /**
     * دریافت آمار پنل منشی
     */
    public function dashboard(Request $request, int $doctorId)
    {
        $stats = $this->receptionistService->getDashboardStats($doctorId);
        return $this->success($stats);
    }
}
