<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicalNote;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Doctor;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MedicalNoteController extends Controller
{
    use ApiResponse;

    /**
     * لیست یادداشت‌ها
     */
    public function index(Request $request)
    {
        $query = MedicalNote::with(['patient', 'doctor', 'appointment'])
            ->orderBy('created_at', 'desc');

        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('content', 'LIKE', "%{$search}%")
                    ->orWhere('subjective', 'LIKE', "%{$search}%")
                    ->orWhere('assessment', 'LIKE', "%{$search}%");
            });
        }

        $notes = $query->paginate($request->get('per_page', 20));

        return $this->success($notes);
    }

    /**
     * ذخیره یادداشت جدید
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'subjective' => 'nullable|string',
            'objective' => 'nullable|string',
            'assessment' => 'nullable|string',
            'plan' => 'nullable|string',
            'diagnoses' => 'nullable|array',
            'prescriptions' => 'nullable|array',
            'lab_requests' => 'nullable|array',
            'imaging_requests' => 'nullable|array',
            'referrals' => 'nullable|array',
            'type' => 'nullable|in:general,prescription,diagnosis,follow_up,referral,emergency',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'is_private' => 'nullable|boolean',
            'is_shared' => 'nullable|boolean',
            'note_status' => 'nullable|in:draft,final,shared',
            'tags' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $user = auth()->user();
            
            // اگر doctor_id ارسال نشده، از کاربر فعلی بگیر
            if (!$request->has('doctor_id') || empty($request->doctor_id)) {
                $doctor = Doctor::where('user_id', $user->id)->first();
                if ($doctor) {
                    $request->merge(['doctor_id' => $doctor->id]);
                }
            }

            // اگر patient_id ارسال نشده، از appointment بگیر
            if (!$request->has('patient_id') && $request->has('appointment_id')) {
                $appointment = Appointment::find($request->appointment_id);
                if ($appointment) {
                    $request->merge(['patient_id' => $appointment->patient_id]);
                }
            }

            $data = $request->all();
            $data['tenant_id'] = session('tenant_id', 1);

            $note = MedicalNote::create($data);

            return $this->success(
                $note->load(['patient', 'doctor']),
                'یادداشت با موفقیت ایجاد شد',
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * نمایش یک یادداشت
     */
    public function show($id)
    {
        try {
            $note = MedicalNote::with(['patient', 'doctor', 'appointment'])
                ->findOrFail($id);
            return $this->success($note);
        } catch (\Exception $e) {
            return $this->error('یادداشت یافت نشد', 404);
        }
    }

    /**
     * بروزرسانی یادداشت
     */
    public function update(Request $request, $id)
    {
        try {
            $note = MedicalNote::findOrFail($id);
        } catch (\Exception $e) {
            return $this->error('یادداشت یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'content' => 'nullable|string',
            'subjective' => 'nullable|string',
            'objective' => 'nullable|string',
            'assessment' => 'nullable|string',
            'plan' => 'nullable|string',
            'diagnoses' => 'nullable|array',
            'prescriptions' => 'nullable|array',
            'lab_requests' => 'nullable|array',
            'imaging_requests' => 'nullable|array',
            'referrals' => 'nullable|array',
            'type' => 'nullable|in:general,prescription,diagnosis,follow_up,referral,emergency',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'is_private' => 'nullable|boolean',
            'is_shared' => 'nullable|boolean',
            'note_status' => 'nullable|in:draft,final,shared',
            'tags' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $note->update($request->all());
            return $this->success($note->fresh(), 'یادداشت با موفقیت بروزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف یادداشت
     */
    public function destroy($id)
    {
        try {
            $note = MedicalNote::findOrFail($id);
            $note->delete();
            return $this->success(null, 'یادداشت با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * یادداشت‌های یک بیمار
     */
    public function patientNotes($patientId)
    {
        try {
            $notes = MedicalNote::where('patient_id', $patientId)
                ->with(['doctor', 'appointment'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);
            return $this->success($notes);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * یادداشت‌های یک پزشک
     */
    public function doctorNotes($doctorId)
    {
        try {
            $notes = MedicalNote::where('doctor_id', $doctorId)
                ->with(['patient', 'appointment'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);
            return $this->success($notes);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * یادداشت‌های من (پزشک)
     */
    public function myNotes(Request $request)
    {
        $user = auth()->user();
        $doctor = Doctor::where('user_id', $user->id)->first();

        if (!$doctor) {
            return $this->error('شما پزشک نیستید', 403);
        }

        $notes = MedicalNote::where('doctor_id', $doctor->id)
            ->with(['patient', 'appointment'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return $this->success($notes);
    }

    /**
     * یادداشت‌های من (بیمار)
     */
    public function myPatientNotes(Request $request)
    {
        $user = auth()->user();
        $patient = Patient::where('user_id', $user->id)->first();

        if (!$patient) {
            return $this->error('بیمار یافت نشد', 404);
        }

        $notes = MedicalNote::where('patient_id', $patient->id)
            ->where('note_status', 'final')
            ->with(['doctor', 'appointment'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return $this->success($notes);
    }

    /**
     * یادداشت یک نوبت
     */
    public function appointmentNote($appointmentId)
    {
        $user = auth()->user();
        $appointment = Appointment::findOrFail($appointmentId);

        // بررسی دسترسی
        $patient = Patient::where('user_id', $user->id)->first();
        $doctor = Doctor::where('user_id', $user->id)->first();

        if ($appointment->patient_id !== $patient->id &&
            $appointment->doctor_id !== $doctor->id &&
            !$user->isAdmin()) {
            return $this->error('شما دسترسی به این یادداشت ندارید', 403);
        }

        $note = MedicalNote::where('appointment_id', $appointmentId)
            ->with(['patient', 'doctor'])
            ->first();

        if (!$note) {
            return $this->success(null, 'یادداشتی برای این نوبت وجود ندارد');
        }

        return $this->success($note);
    }

    /**
     * خلاصه یادداشت‌های بیمار
     */
    public function summary($patientId)
    {
        try {
            $notes = MedicalNote::where('patient_id', $patientId)
                ->where('note_status', 'final')
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->success([
                'total_notes' => $notes->count(),
                'by_type' => $notes->groupBy('type')->map->count(),
                'latest_notes' => $notes->take(5),
                'diagnoses' => $notes->pluck('diagnoses')->filter()->flatten(1)->unique('name')->values(),
                'prescriptions' => $notes->pluck('prescriptions')->filter()->flatten(1)->unique('name')->values(),
                'lab_requests' => $notes->pluck('lab_requests')->filter()->flatten(1)->unique('name')->values(),
                'imaging_requests' => $notes->pluck('imaging_requests')->filter()->flatten(1)->unique('name')->values(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * اشتراک‌گذاری یادداشت
     */
    public function share($id)
    {
        try {
            $note = MedicalNote::findOrFail($id);
            $note->update([
                'is_shared' => true,
                'note_status' => 'shared',
            ]);
            return $this->success($note, 'یادداشت با موفقیت به اشتراک گذاشته شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * لغو اشتراک‌گذاری یادداشت
     */
    public function unshare($id)
    {
        try {
            $note = MedicalNote::findOrFail($id);
            $note->update([
                'is_shared' => false,
                'note_status' => 'final',
            ]);
            return $this->success($note, 'اشتراک‌گذاری یادداشت با موفقیت لغو شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * نهایی کردن یادداشت
     */
    public function finalize($id)
    {
        try {
            $note = MedicalNote::findOrFail($id);
            $note->update(['note_status' => 'final']);
            return $this->success($note, 'یادداشت با موفقیت نهایی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * اضافه کردن آزمایش به یادداشت
     */
    public function addLabRequest(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:100',
            'priority' => 'nullable|in:normal,urgent,stat',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $note = MedicalNote::findOrFail($id);
            
            $labRequests = $note->lab_requests ?? [];
            $labRequests[] = [
                'name' => $request->name,
                'type' => $request->type ?? 'عمومی',
                'priority' => $request->priority ?? 'normal',
                'note' => $request->note,
                'status' => 'pending',
                'ordered_at' => now()->toDateTimeString(),
            ];
            
            $note->update(['lab_requests' => $labRequests]);

            return $this->success($note, 'آزمایش با موفقیت اضافه شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * اضافه کردن تصویربرداری به یادداشت
     */
    public function addImagingRequest(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:100',
            'priority' => 'nullable|in:normal,urgent,stat',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $note = MedicalNote::findOrFail($id);
            
            $imagingRequests = $note->imaging_requests ?? [];
            $imagingRequests[] = [
                'name' => $request->name,
                'type' => $request->type ?? 'عمومی',
                'priority' => $request->priority ?? 'normal',
                'note' => $request->note,
                'status' => 'pending',
                'ordered_at' => now()->toDateTimeString(),
            ];
            
            $note->update(['imaging_requests' => $imagingRequests]);

            return $this->success($note, 'تصویربرداری با موفقیت اضافه شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * اضافه کردن ارجاع به یادداشت
     */
    public function addReferral(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'to_doctor_id' => 'required|exists:doctors,id',
            'reason' => 'required|string|max:500',
            'priority' => 'nullable|in:normal,urgent',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $note = MedicalNote::findOrFail($id);
            
            $referrals = $note->referrals ?? [];
            $referrals[] = [
                'to_doctor_id' => $request->to_doctor_id,
                'reason' => $request->reason,
                'priority' => $request->priority ?? 'normal',
                'note' => $request->note,
                'status' => 'pending',
                'referred_at' => now()->toDateTimeString(),
            ];
            
            $note->update(['referrals' => $referrals]);

            return $this->success($note, 'ارجاع با موفقیت اضافه شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
