<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Prescription\PrescriptionService;
use App\Http\Requests\Api\StorePrescriptionRequest;
use App\Http\Requests\Api\UpdatePrescriptionRequest;
use App\Traits\ApiResponse;
use App\Models\Prescription;
use App\Models\Appointment;
use Illuminate\Http\Request;

class PrescriptionController extends Controller
{
    use ApiResponse;

    protected PrescriptionService $prescriptionService;

    public function __construct(PrescriptionService $prescriptionService)
    {
        $this->prescriptionService = $prescriptionService;
    }

    /**
     * لیست نسخه‌ها
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user->isAdmin()) {
            return $this->error('شما دسترسی به این بخش را ندارید', 403);
        }

        $prescriptions = $this->prescriptionService->list($request->all(), $request->get('per_page', 15));
        return $this->success($prescriptions);
    }

    /**
     * ایجاد نسخه جدید
     */
    public function store(StorePrescriptionRequest $request)
    {
        try {
            $appointment = Appointment::findOrFail($request->appointment_id);

            $user = auth()->user();
            if (!$user->isAdmin() && $appointment->doctor->user_id != $user->id) {
                return $this->error('شما دسترسی به این نوبت ندارید', 403);
            }

            $prescription = $this->prescriptionService->createFromAppointment($appointment, $request->validated());
            return $this->success($prescription, 'نسخه با موفقیت ثبت شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * نمایش نسخه
     */
    public function show($id)
    {
        try {
            $prescription = $this->prescriptionService->show($id);

            $user = auth()->user();
            if (!$user->isAdmin() &&
                $prescription->patient->user_id != $user->id &&
                $prescription->doctor->user_id != $user->id) {
                return $this->error('شما دسترسی به این نسخه ندارید', 403);
            }

            // بررسی تداخل دارویی
            $interactions = $this->prescriptionService->checkInteractions($prescription);

            return $this->success([
                'prescription' => $prescription,
                'interactions' => $interactions,
                'daily_times' => $prescription->daily_times,
            ]);
        } catch (\Exception $e) {
            return $this->error('نسخه یافت نشد', 404);
        }
    }

    /**
     * به‌روزرسانی نسخه
     */
    public function update(UpdatePrescriptionRequest $request, $id)
    {
        try {
            $prescription = Prescription::findOrFail($id);

            $user = auth()->user();
            if (!$user->isAdmin() && $prescription->doctor->user_id != $user->id) {
                return $this->error('شما دسترسی به این نسخه ندارید', 403);
            }

            $prescription = $this->prescriptionService->update($prescription, $request->validated());
            return $this->success($prescription, 'نسخه با موفقیت به‌روزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تغییر وضعیت نسخه
     */
    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:activate,complete,cancel,expire',
        ]);

        try {
            $prescription = Prescription::findOrFail($id);

            $user = auth()->user();
            if (!$user->isAdmin() && $prescription->doctor->user_id != $user->id) {
                return $this->error('شما دسترسی به این نسخه ندارید', 403);
            }

            $prescription = $this->prescriptionService->changeStatus($prescription, $request->status);
            return $this->success($prescription, 'وضعیت نسخه با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف نسخه
     */
    public function destroy($id)
    {
        try {
            $prescription = Prescription::findOrFail($id);

            $user = auth()->user();
            if (!$user->isAdmin() && $prescription->doctor->user_id != $user->id) {
                return $this->error('شما دسترسی به این نسخه ندارید', 403);
            }

            $this->prescriptionService->delete($prescription);
            return $this->success(null, 'نسخه با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * نسخه‌های من (بیمار)
     */
    public function myPrescriptions(Request $request)
    {
        $user = auth()->user();
        $patient = \App\Models\Patient::where('user_id', $user->id)->first();

        if (!$patient) {
            return $this->error('بیمار یافت نشد', 404);
        }

        $prescriptions = $this->prescriptionService->patientPrescriptions(
            $patient->id,
            $request->all(),
            $request->get('per_page', 15)
        );
        return $this->success($prescriptions);
    }

    /**
     * نسخه‌های پزشک جاری
     */
    public function myDoctorPrescriptions(Request $request)
    {
        $user = auth()->user();
        $doctor = \App\Models\Doctor::where('user_id', $user->id)->first();

        if (!$doctor) {
            return $this->error('پزشک یافت نشد', 404);
        }

        $prescriptions = $this->prescriptionService->doctorPrescriptions(
            $doctor->id,
            $request->all(),
            $request->get('per_page', 15)
        );
        return $this->success($prescriptions);
    }

    /**
     * نسخه‌های یک بیمار (ادمین/پزشک)
     */
    public function patientPrescriptions(Request $request, $patientId)
    {
        $user = auth()->user();

        // بررسی دسترسی
        $patient = \App\Models\Patient::find($patientId);
        if (!$patient) {
            return $this->error('بیمار یافت نشد', 404);
        }

        if (!$user->isAdmin() && $patient->doctor_id != $user->id) {
            return $this->error('شما دسترسی به این بیمار ندارید', 403);
        }

        $prescriptions = $this->prescriptionService->patientPrescriptions(
            $patientId,
            $request->all(),
            $request->get('per_page', 15)
        );
        return $this->success($prescriptions);
    }

    /**
     * بررسی تداخل دارویی
     */
    public function checkInteractions($id)
    {
        try {
            $prescription = Prescription::findOrFail($id);
            $interactions = $this->prescriptionService->checkInteractions($prescription);
            return $this->success($interactions);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * چاپ نسخه
     */
    public function print($id)
    {
        try {
            $prescription = Prescription::findOrFail($id);

            $user = auth()->user();
            if (!$user->isAdmin() &&
                $prescription->patient->user_id != $user->id &&
                $prescription->doctor->user_id != $user->id) {
                return $this->error('شما دسترسی به این نسخه ندارید', 403);
            }

            $data = $this->prescriptionService->getPrintData($prescription);
            return $this->success($data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * آمار نسخه‌ها
     */
    public function stats(Request $request)
    {
        $user = auth()->user();
        if (!$user->isAdmin()) {
            return $this->error('شما دسترسی به این بخش را ندارید', 403);
        }

        $stats = $this->prescriptionService->getStats();
        return $this->success($stats);
    }
}
