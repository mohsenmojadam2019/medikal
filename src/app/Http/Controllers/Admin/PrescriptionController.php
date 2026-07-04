<?php
// app/Http/Controllers/Admin/PrescriptionController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PrescriptionController extends Controller
{
    use ApiResponse;

    /**
     * لیست نسخه‌ها (ادمین)
     */
    public function index(Request $request)
    {
        $tenantId = session('tenant_id', 1);

        $query = Prescription::where('tenant_id', $tenantId)
            ->with(['patient', 'doctor', 'patient.user', 'doctor.user']);

        // فیلتر بر اساس وضعیت
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // فیلتر بر اساس پزشک
        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        // فیلتر بر اساس بیمار
        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        // جستجو - فقط روی code و drug_name
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('drug_name', 'like', "%{$search}%");
            });
        }

        $prescriptions = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success($prescriptions);
    }

    /**
     * ایجاد نسخه جدید (توسط ادمین)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'drug_name' => 'required|string|max:255',
            'dosage' => 'required|string|max:255',
            'frequency' => 'nullable|integer|min:1|max:24',
            'duration' => 'nullable|integer|min:1|max:365',
            'start_date' => 'nullable|date',
            'instructions' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:pending,active,completed,cancelled,expired',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $request->all();
            $data['code'] = $this->generatePrescriptionCode();
            $data['status'] = $data['status'] ?? 'pending';
            $data['start_date'] = $data['start_date'] ?? now()->toDateString();
            $data['duration'] = $data['duration'] ?? 7;
            $data['end_date'] = now()->parse($data['start_date'])->addDays($data['duration'])->toDateString();
            $data['tenant_id'] = session('tenant_id', 1);

            $prescription = Prescription::create($data);

            return $this->success(
                $prescription->load(['patient', 'doctor']),
                'نسخه با موفقیت ایجاد شد',
                201
            );
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
            $tenantId = session('tenant_id', 1);
            $prescription = Prescription::where('tenant_id', $tenantId)
                ->with(['patient', 'doctor', 'patient.user', 'doctor.user'])
                ->findOrFail($id);
            return $this->success($prescription);
        } catch (\Exception $e) {
            return $this->error('نسخه یافت نشد', 404);
        }
    }

    /**
     * به‌روزرسانی نسخه
     */
    public function update(Request $request, $id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $prescription = Prescription::where('tenant_id', $tenantId)->findOrFail($id);
        } catch (\Exception $e) {
            return $this->error('نسخه یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'patient_id' => 'sometimes|exists:patients,id',
            'doctor_id' => 'sometimes|exists:doctors,id',
            'drug_name' => 'sometimes|string|max:255',
            'dosage' => 'sometimes|string|max:255',
            'frequency' => 'nullable|integer|min:1|max:24',
            'duration' => 'nullable|integer|min:1|max:365',
            'start_date' => 'nullable|date',
            'instructions' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:pending,active,completed,cancelled,expired',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $request->all();

            if (isset($data['start_date']) || isset($data['duration'])) {
                $startDate = $data['start_date'] ?? $prescription->start_date;
                $duration = $data['duration'] ?? $prescription->duration;
                $data['end_date'] = now()->parse($startDate)->addDays($duration)->toDateString();
            }

            $prescription->update($data);
            return $this->success(
                $prescription->fresh()->load(['patient', 'doctor']),
                'نسخه با موفقیت به‌روزرسانی شد'
            );
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
            $tenantId = session('tenant_id', 1);
            $prescription = Prescription::where('tenant_id', $tenantId)->findOrFail($id);
            $prescription->delete();
            return $this->success(null, 'نسخه با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تغییر وضعیت نسخه
     */
    public function changeStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,active,completed,cancelled,expired',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $tenantId = session('tenant_id', 1);
            $prescription = Prescription::where('tenant_id', $tenantId)->findOrFail($id);
            $prescription->update(['status' => $request->status]);
            return $this->success($prescription->fresh(), 'وضعیت نسخه با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * آمار نسخه‌ها
     */
    public function stats()
    {
        try {
            $tenantId = session('tenant_id', 1);

            $total = Prescription::where('tenant_id', $tenantId)->count();
            $pending = Prescription::where('tenant_id', $tenantId)->where('status', 'pending')->count();
            $active = Prescription::where('tenant_id', $tenantId)->where('status', 'active')->count();
            $completed = Prescription::where('tenant_id', $tenantId)->where('status', 'completed')->count();
            $cancelled = Prescription::where('tenant_id', $tenantId)->where('status', 'cancelled')->count();
            $expired = Prescription::where('tenant_id', $tenantId)->where('status', 'expired')->count();

            return $this->success([
                'total' => $total,
                'pending' => $pending,
                'active' => $active,
                'completed' => $completed,
                'cancelled' => $cancelled,
                'expired' => $expired,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * تولید کد نسخه
     */
    private function generatePrescriptionCode()
    {
        $prefix = 'PRS';
        $random = Str::random(8);
        $code = $prefix . '-' . $random;

        while (Prescription::where('code', $code)->exists()) {
            $random = Str::random(8);
            $code = $prefix . '-' . $random;
        }

        return $code;
    }
}
