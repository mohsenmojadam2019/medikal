<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MedicalNote;
use App\Services\MedicalNote\MedicalNoteService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MedicalNoteController extends Controller
{
    use ApiResponse;

    protected MedicalNoteService $medicalNoteService;

    public function __construct(MedicalNoteService $medicalNoteService)
    {
        $this->medicalNoteService = $medicalNoteService;
    }

    /**
     * لیست تمام یادداشت‌ها (ادمین)
     */
    public function index(Request $request)
    {
        $query = MedicalNote::with(['patient', 'doctor', 'appointment'])
            ->orderBy('created_at', 'desc');

        // فیلتر بر اساس بیمار
        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        // فیلتر بر اساس پزشک
        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        // فیلتر بر اساس نوع
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // فیلتر بر اساس وضعیت
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('note_status', $request->status);
        }

        // جستجو
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('content', 'LIKE', "%{$search}%")
                    ->orWhere('subjective', 'LIKE', "%{$search}%")
                    ->orWhere('assessment', 'LIKE', "%{$search}%")
                    ->orWhere('plan', 'LIKE', "%{$search}%");
            });
        }

        $notes = $query->paginate($request->get('per_page', 20));

        return $this->success($notes);
    }

    /**
     * نمایش یک یادداشت (ادمین)
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
     * ایجاد یادداشت (ادمین)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'appointment_id' => 'nullable|exists:appointments,id',
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
            $data = $request->all();
            $note = $this->medicalNoteService->createNote($data);
            return $this->success($note->load(['patient', 'doctor']), 'یادداشت با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * بروزرسانی یادداشت (ادمین)
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
     * حذف یادداشت (ادمین)
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
     * تغییر وضعیت یادداشت (ادمین)
     */
    public function changeStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:draft,final,shared',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $note = MedicalNote::findOrFail($id);
            $note->update(['note_status' => $request->status]);
            return $this->success($note->fresh(), 'وضعیت یادداشت با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * لیست یادداشت‌های یک بیمار (ادمین)
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
     * آمار یادداشت‌ها (ادمین)
     */
    public function stats()
    {
        try {
            $total = MedicalNote::count();
            $byType = MedicalNote::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->get();
            $byStatus = MedicalNote::selectRaw('note_status, COUNT(*) as count')
                ->groupBy('note_status')
                ->get();
            $today = MedicalNote::whereDate('created_at', today())->count();
            $thisWeek = MedicalNote::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count();

            return $this->success([
                'total' => $total,
                'today' => $today,
                'this_week' => $thisWeek,
                'by_type' => $byType,
                'by_status' => $byStatus,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * جستجوی پیشرفته یادداشت‌ها (ادمین)
     */
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        $query = MedicalNote::with(['patient', 'doctor']);
        $search = $request->q;

        $query->where(function ($q) use ($search) {
            $q->where('title', 'LIKE', "%{$search}%")
                ->orWhere('content', 'LIKE', "%{$search}%")
                ->orWhere('subjective', 'LIKE', "%{$search}%")
                ->orWhere('objective', 'LIKE', "%{$search}%")
                ->orWhere('assessment', 'LIKE', "%{$search}%")
                ->orWhere('plan', 'LIKE', "%{$search}%")
                ->orWhereHas('patient', function ($q2) use ($search) {
                    $q2->where('full_name', 'LIKE', "%{$search}%")
                        ->orWhere('national_code', 'LIKE', "%{$search}%");
                })
                ->orWhereHas('doctor', function ($q2) use ($search) {
                    $q2->where('full_name', 'LIKE', "%{$search}%");
                });
        });

        $notes = $query->paginate($request->get('per_page', 20));

        return $this->success($notes);
    }

    /**
     * دریافت خلاصه پرونده بیمار (ادمین)
     */
    public function patientSummary($patientId)
    {
        try {
            $summary = $this->medicalNoteService->getPatientNoteSummary($patientId);
            return $this->success($summary);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * خروجی اکسل از یادداشت‌ها (ادمین)
     */
    public function export(Request $request)
    {
        // اینجا می‌توانید خروجی اکسل بسازید
        // با استفاده از Maatwebsite\Excel
        return $this->success(['message' => 'خروجی اکسل در حال ساخت...']);
    }
}
