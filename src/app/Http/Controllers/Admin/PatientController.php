<?php
// app/Http/Controllers/Admin/PatientController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Patient\PatientService;
use App\Http\Requests\Admin\StorePatientRequest;
use App\Http\Requests\Admin\UpdatePatientRequest;
use App\Traits\ApiResponse;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    use ApiResponse;

    protected PatientService $patientService;

    public function __construct(PatientService $patientService)
    {
        $this->patientService = $patientService;
    }

    /**
     * لیست بیماران (با فیلتر استان و شهر)
     */
    public function index(Request $request)
    {
        $filters = $request->all();

        // فیلتر بر اساس استان
        if ($request->has('province_id') && $request->province_id) {
            $filters['province_id'] = $request->province_id;
        }

        // فیلتر بر اساس شهر
        if ($request->has('city_id') && $request->city_id) {
            $filters['city_id'] = $request->city_id;
        }

        $patients = $this->patientService->list($filters, $request->get('per_page', 15));
        return $this->success($patients);
    }

    /**
     * ایجاد بیمار جدید
     */
    public function store(StorePatientRequest $request)
    {
        try {
            $patient = $this->patientService->create($request->validated());
            return $this->success(
                $patient->load(['user', 'doctor', 'province', 'city']),
                'بیمار با موفقیت ایجاد شد',
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * نمایش بیمار با تاریخچه کامل (پرونده سلامت)
     */
    public function show($id)
    {
        try {
            $patient = $this->patientService->show($id);
            return $this->success($patient);
        } catch (\Exception $e) {
            return $this->error('بیمار یافت نشد', 404);
        }
    }

    /**
     * به‌روزرسانی بیمار
     */
    public function update(UpdatePatientRequest $request, $id)
    {
        try {
            $patient = Patient::with(['user', 'doctor', 'province', 'city'])->findOrFail($id);
            $patient = $this->patientService->update($patient, $request->validated());
            return $this->success(
                $patient->load(['user', 'doctor', 'province', 'city']),
                'بیمار با موفقیت به‌روزرسانی شد'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف بیمار
     */
    public function destroy($id)
    {
        try {
            $patient = Patient::findOrFail($id);
            $this->patientService->delete($patient);
            return $this->success(null, 'بیمار با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تغییر وضعیت بیمار
     */
    public function toggleStatus($id)
    {
        try {
            $patient = Patient::findOrFail($id);
            $patient = $this->patientService->toggleStatus($patient);
            return $this->success(
                $patient->load(['user', 'doctor', 'province', 'city']),
                'وضعیت بیمار با موفقیت تغییر کرد'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تایید بیمار
     */
    public function verify($id)
    {
        try {
            $patient = Patient::findOrFail($id);
            $patient = $this->patientService->verify($patient);
            return $this->success(
                $patient->load(['user', 'doctor', 'province', 'city']),
                'بیمار با موفقیت تایید شد'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * لغو تایید بیمار
     */
    public function unverify($id)
    {
        try {
            $patient = Patient::findOrFail($id);
            $patient = $this->patientService->unverify($patient);
            return $this->success(
                $patient->load(['user', 'doctor', 'province', 'city']),
                'تایید بیمار با موفقیت لغو شد'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * اختصاص پزشک به بیمار
     */
    public function assignDoctor(Request $request, $id)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
        ]);

        try {
            $patient = Patient::findOrFail($id);
            $patient = $this->patientService->assignDoctor($patient, $request->doctor_id);
            return $this->success(
                $patient->load(['user', 'doctor', 'province', 'city']),
                'پزشک با موفقیت به بیمار اختصاص داده شد'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تاریخچه پزشکی بیمار (پرونده سلامت کامل)
     */
    public function medicalHistory($id)
    {
        try {
            $patient = Patient::findOrFail($id);
            $history = $this->patientService->getMedicalHistory($patient);
            return $this->success($history);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * آمار بیمار
     */
    public function statistics($id)
    {
        try {
            $patient = Patient::findOrFail($id);
            $stats = $this->patientService->getStatistics($patient);
            return $this->success($stats);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * جستجوی بیمار با کدملی
     */
    public function findByNationalCode(Request $request)
    {
        $request->validate([
            'national_code' => 'required|string|size:10',
        ]);

        $patient = $this->patientService->findByNationalCode($request->national_code);
        if (!$patient) {
            return $this->error('بیمار با این کدملی یافت نشد', 404);
        }
        return $this->success($patient->load(['user', 'doctor', 'province', 'city']));
    }

    /**
     * جستجوی بیمار با موبایل
     */
    public function findByMobile(Request $request)
    {
        $request->validate([
            'mobile' => 'required|regex:/^09[0-9]{9}$/',
        ]);

        $patient = $this->patientService->findByMobile($request->mobile);
        if (!$patient) {
            return $this->error('بیمار با این شماره موبایل یافت نشد', 404);
        }
        return $this->success($patient->load(['user', 'doctor', 'province', 'city']));
    }

    /**
     * بیماران بدون پزشک
     */
    public function withoutDoctor()
    {
        $patients = $this->patientService->getPatientsWithoutDoctor();
        return $this->success($patients);
    }

    /**
     * بیماران پرمراجعه
     */
    public function topPatients(Request $request)
    {
        $limit = $request->get('limit', 10);
        $patients = $this->patientService->getTopPatients($limit);
        return $this->success($patients);
    }

    /**
     * بیماران من (پزشک جاری)
     */
    public function myPatients(Request $request)
    {
        $user = auth()->user();
        $doctor = \App\Models\Doctor::where('user_id', $user->id)->first();

        if (!$doctor) {
            return $this->error('شما پزشک نیستید', 403);
        }

        $filters = $request->all();
        $filters['doctor_id'] = $doctor->id;

        $patients = $this->patientService->list($filters, $request->get('per_page', 15));
        return $this->success($patients);
    }
}
