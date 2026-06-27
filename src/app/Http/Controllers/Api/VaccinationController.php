<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Vaccination\VaccinationService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VaccinationController extends Controller
{
    use ApiResponse;

    protected VaccinationService $vaccinationService;

    public function __construct(VaccinationService $vaccinationService)
    {
        $this->vaccinationService = $vaccinationService;
    }

    // ============================================================
    // VACCINES
    // ============================================================

    public function index(Request $request)
    {
        $vaccines = $this->vaccinationService->getVaccines(
            $request->all(),
            $request->get('per_page', 20)
        );
        return $this->success($vaccines);
    }

    public function activeVaccines()
    {
        $vaccines = $this->vaccinationService->getActiveVaccines();
        return $this->success($vaccines);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'manufacturer' => 'required|string|max:255',
            'disease' => 'required|string|max:255',
            'doses_required' => 'required|integer|min:1|max:10',
            'interval_days' => 'nullable|integer|min:1',
            'age_min_months' => 'nullable|integer|min:0',
            'age_max_months' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'side_effects' => 'nullable|string',
            'storage_condition' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'is_required' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $vaccine = $this->vaccinationService->createVaccine($request->all());
            return $this->success($vaccine, 'واکسن با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function show($id)
    {
        try {
            $vaccine = Vaccine::findOrFail($id);
            return $this->success($vaccine);
        } catch (\Exception $e) {
            return $this->error('واکسن یافت نشد', 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $vaccine = Vaccine::findOrFail($id);
        } catch (\Exception $e) {
            return $this->error('واکسن یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'manufacturer' => 'sometimes|string|max:255',
            'disease' => 'sometimes|string|max:255',
            'doses_required' => 'sometimes|integer|min:1|max:10',
            'interval_days' => 'nullable|integer|min:1',
            'age_min_months' => 'nullable|integer|min:0',
            'age_max_months' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'side_effects' => 'nullable|string',
            'storage_condition' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'is_required' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $vaccine = $this->vaccinationService->updateVaccine($vaccine, $request->all());
            return $this->success($vaccine, 'واکسن با موفقیت بروزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function destroy($id)
    {
        try {
            $vaccine = Vaccine::findOrFail($id);
            $this->vaccinationService->deleteVaccine($vaccine);
            return $this->success(null, 'واکسن با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function toggleStatus($id)
    {
        try {
            $vaccine = Vaccine::findOrFail($id);
            $vaccine = $this->vaccinationService->toggleVaccineStatus($vaccine);
            return $this->success($vaccine, 'وضعیت واکسن با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // PATIENT VACCINATIONS
    // ============================================================

    public function record(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'vaccine_id' => 'required|exists:vaccines,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'dose_number' => 'required|integer|min:1',
            'administration_date' => 'nullable|date',
            'batch_number' => 'nullable|string|max:50',
            'administration_site' => 'nullable|string|max:50',
            'reaction_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $vaccination = $this->vaccinationService->recordVaccination($request->all());
            return $this->success($vaccination, 'واکسیناسیون با موفقیت ثبت شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function patientVaccinations(Request $request, $patientId)
    {
        $vaccinations = $this->vaccinationService->getPatientVaccinations(
            $patientId,
            $request->all(),
            $request->get('per_page', 20)
        );
        return $this->success($vaccinations);
    }

    public function patientSummary($patientId)
    {
        $summary = $this->vaccinationService->getPatientVaccinationSummary($patientId);
        return $this->success($summary);
    }

    public function upcoming($patientId)
    {
        $upcoming = $this->vaccinationService->getUpcomingVaccinations($patientId);
        return $this->success($upcoming);
    }

    public function overdue($patientId)
    {
        $overdue = $this->vaccinationService->getOverdueVaccinations($patientId);
        return $this->success($overdue);
    }

    // ============================================================
    // REMINDERS
    // ============================================================

    public function reminders(Request $request, $patientId)
    {
        $reminders = $this->vaccinationService->getPatientReminders(
            $patientId,
            $request->all(),
            $request->get('per_page', 20)
        );
        return $this->success($reminders);
    }

    public function processReminders()
    {
        try {
            $count = $this->vaccinationService->processReminders();
            return $this->success(['count' => $count], "{$count} یادآوری با موفقیت ارسال شد");
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // REPORTS
    // ============================================================

    public function stats(Request $request)
    {
        $stats = $this->vaccinationService->getStats($request->all());
        return $this->success($stats);
    }
}
