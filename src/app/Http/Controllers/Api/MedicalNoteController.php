<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

    public function index(Request $request)
    {
        $notes = $this->medicalNoteService->getNotes($request->all(), $request->get('per_page', 20));
        return $this->success($notes);
    }

    public function patientNotes(Request $request, $patientId)
    {
        $notes = $this->medicalNoteService->getPatientNotes($patientId, $request->all(), $request->get('per_page', 20));
        return $this->success($notes);
    }

    public function doctorNotes(Request $request, $doctorId)
    {
        $notes = $this->medicalNoteService->getDoctorNotes($doctorId, $request->all(), $request->get('per_page', 20));
        return $this->success($notes);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'nullable|in:general,prescription,diagnosis,follow_up,referral,emergency',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'is_private' => 'nullable|boolean',
            'is_shared' => 'nullable|boolean',
            'tags' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $request->all();
            $data['doctor_id'] = $data['doctor_id'] ?? auth()->user()->doctor?->id;
            $note = $this->medicalNoteService->createNote($data);
            return $this->success($note, 'یادداشت با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function show($id)
    {
        try {
            $note = $this->medicalNoteService->getNote($id);
            return $this->success($note);
        } catch (\Exception $e) {
            return $this->error('یادداشت یافت نشد', 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $note = $this->medicalNoteService->getNote($id);
        } catch (\Exception $e) {
            return $this->error('یادداشت یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'type' => 'nullable|in:general,prescription,diagnosis,follow_up,referral,emergency',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'is_private' => 'nullable|boolean',
            'is_shared' => 'nullable|boolean',
            'tags' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $note = $this->medicalNoteService->updateNote($note, $request->all());
            return $this->success($note, 'یادداشت با موفقیت بروزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function destroy($id)
    {
        try {
            $note = $this->medicalNoteService->getNote($id);
            $this->medicalNoteService->deleteNote($note);
            return $this->success(null, 'یادداشت با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function share($id)
    {
        try {
            $note = $this->medicalNoteService->shareNote($id);
            return $this->success($note, 'یادداشت با موفقیت به اشتراک گذاشته شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function unshare($id)
    {
        try {
            $note = $this->medicalNoteService->unshareNote($id);
            return $this->success($note, 'اشتراک‌گذاری یادداشت با موفقیت لغو شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function summary($patientId)
    {
        try {
            $summary = $this->medicalNoteService->getPatientNoteSummary($patientId);
            return $this->success($summary);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }
}
