<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Services\Referral\ReferralService;
use App\Models\Patient;
use App\Models\Doctor;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReferralController extends Controller
{
    use ApiResponse;

    protected ReferralService $referralService;

    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }

    /**
     * ایجاد ارجاع جدید
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $doctor = Doctor::where('user_id', $user->id)->first();

        if (!$doctor) {
            return $this->error('شما پزشک نیستید', 403);
        }

        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'to_doctor_id' => 'required|exists:doctors,id|different:doctor_id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'reason' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $request->all();
            $data['from_doctor_id'] = $doctor->id;

            $referral = $this->referralService->create($data);
            return $this->success($referral, 'ارجاع با موفقیت ثبت شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * لیست ارجاعات بیمار
     */
    public function patientReferrals($patientId)
    {
        try {
            $patient = Patient::findOrFail($patientId);
            $referrals = $this->referralService->getPatientReferrals($patientId);
            return $this->success($referrals);
        } catch (\Exception $e) {
            return $this->error('بیمار یافت نشد', 404);
        }
    }

    /**
     * لیست ارجاعات پزشک
     */
    public function doctorReferrals(Request $request)
    {
        $user = auth()->user();
        $doctor = Doctor::where('user_id', $user->id)->first();

        if (!$doctor) {
            return $this->error('شما پزشک نیستید', 403);
        }

        $type = $request->get('type', 'incoming');
        $referrals = $this->referralService->getDoctorReferrals($doctor->id, $type);

        return $this->success($referrals);
    }

    /**
     * پذیرش ارجاع
     */
    public function accept($id)
    {
        $user = auth()->user();
        $referral = Referral::findOrFail($id);
        $doctor = Doctor::where('user_id', $user->id)->first();

        if (!$doctor || $referral->to_doctor_id != $doctor->id) {
            return $this->error('شما دسترسی به این ارجاع را ندارید', 403);
        }

        try {
            $referral = $this->referralService->accept($id);
            return $this->success($referral, 'ارجاع با موفقیت پذیرفته شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * رد ارجاع
     */
    public function reject($id)
    {
        $user = auth()->user();
        $referral = Referral::findOrFail($id);
        $doctor = Doctor::where('user_id', $user->id)->first();

        if (!$doctor || $referral->to_doctor_id != $doctor->id) {
            return $this->error('شما دسترسی به این ارجاع را ندارید', 403);
        }

        try {
            $referral = $this->referralService->reject($id);
            return $this->success($referral, 'ارجاع با موفقیت رد شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تکمیل ارجاع
     */
    public function complete($id)
    {
        $user = auth()->user();
        $referral = Referral::findOrFail($id);
        $doctor = Doctor::where('user_id', $user->id)->first();

        if (!$doctor || ($referral->from_doctor_id != $doctor->id && $referral->to_doctor_id != $doctor->id)) {
            return $this->error('شما دسترسی به این ارجاع را ندارید', 403);
        }

        try {
            $referral = $this->referralService->complete($id);
            return $this->success($referral, 'ارجاع با موفقیت تکمیل شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
