<?php
// app/Http/Controllers/Admin/ReferralController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReferralController extends Controller
{
    use ApiResponse;

    /**
     * لیست ارجاعات
     */
    public function index(Request $request)
    {
        $tenantId = session('tenant_id', 1);

        $query = Referral::where('tenant_id', $tenantId)
            ->with([
                'patient',
                'patient.user',
                'fromDoctor',
                'fromDoctor.user',
                'toDoctor',
                'toDoctor.user',
                'appointment'
            ]);

        // فیلتر بر اساس وضعیت
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // فیلتر بر اساس بیمار
        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        // فیلتر بر اساس پزشک مبدا
        if ($request->has('from_doctor_id')) {
            $query->where('from_doctor_id', $request->from_doctor_id);
        }

        // فیلتر بر اساس پزشک مقصد
        if ($request->has('to_doctor_id')) {
            $query->where('to_doctor_id', $request->to_doctor_id);
        }

        // جستجو
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($q2) use ($search) {
                        $q2->where('full_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('fromDoctor', function ($q2) use ($search) {
                        $q2->where('full_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('toDoctor', function ($q2) use ($search) {
                        $q2->where('full_name', 'like', "%{$search}%");
                    });
            });
        }

        $referrals = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success($referrals);
    }

    /**
     * نمایش ارجاع
     */
    public function show($id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $referral = Referral::where('tenant_id', $tenantId)
                ->with([
                    'patient',
                    'patient.user',
                    'fromDoctor',
                    'fromDoctor.user',
                    'toDoctor',
                    'toDoctor.user',
                    'appointment'
                ])
                ->findOrFail($id);
            return $this->success($referral);
        } catch (\Exception $e) {
            return $this->error('ارجاع یافت نشد', 404);
        }
    }

    /**
     * ایجاد ارجاع جدید
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'from_doctor_id' => 'required|exists:doctors,id',
            'to_doctor_id' => 'required|exists:doctors,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $request->all();
            $data['tenant_id'] = session('tenant_id', 1);
            $data['status'] = 'pending';

            $referral = Referral::create($data);

            return $this->success(
                $referral->load(['patient', 'fromDoctor', 'toDoctor']),
                'ارجاع با موفقیت ایجاد شد',
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * به‌روزرسانی ارجاع
     */
    public function update(Request $request, $id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $referral = Referral::where('tenant_id', $tenantId)->findOrFail($id);
        } catch (\Exception $e) {
            return $this->error('ارجاع یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'patient_id' => 'sometimes|exists:patients,id',
            'from_doctor_id' => 'sometimes|exists:doctors,id',
            'to_doctor_id' => 'sometimes|exists:doctors,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'reason' => 'sometimes|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'status' => 'sometimes|in:pending,accepted,rejected,completed',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $referral->update($request->all());
            return $this->success(
                $referral->fresh()->load(['patient', 'fromDoctor', 'toDoctor']),
                'ارجاع با موفقیت به‌روزرسانی شد'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف ارجاع
     */
    public function destroy($id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $referral = Referral::where('tenant_id', $tenantId)->findOrFail($id);
            $referral->delete();
            return $this->success(null, 'ارجاع با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * پذیرش ارجاع
     */
    public function accept($id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $referral = Referral::where('tenant_id', $tenantId)->findOrFail($id);
            $referral->accept();
            return $this->success($referral->fresh(), 'ارجاع با موفقیت پذیرفته شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * رد ارجاع
     */
    public function reject($id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $referral = Referral::where('tenant_id', $tenantId)->findOrFail($id);
            $referral->reject();
            return $this->success($referral->fresh(), 'ارجاع با موفقیت رد شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تکمیل ارجاع
     */
    public function complete($id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $referral = Referral::where('tenant_id', $tenantId)->findOrFail($id);
            $referral->complete();
            return $this->success($referral->fresh(), 'ارجاع با موفقیت تکمیل شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * آمار ارجاعات
     */
    public function stats()
    {
        try {
            $tenantId = session('tenant_id', 1);

            $total = Referral::where('tenant_id', $tenantId)->count();
            $pending = Referral::where('tenant_id', $tenantId)->where('status', 'pending')->count();
            $accepted = Referral::where('tenant_id', $tenantId)->where('status', 'accepted')->count();
            $rejected = Referral::where('tenant_id', $tenantId)->where('status', 'rejected')->count();
            $completed = Referral::where('tenant_id', $tenantId)->where('status', 'completed')->count();

            return $this->success([
                'total' => $total,
                'pending' => $pending,
                'accepted' => $accepted,
                'rejected' => $rejected,
                'completed' => $completed,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
