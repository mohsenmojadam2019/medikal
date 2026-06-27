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
        $notes = $this->medicalNoteService->getAll($request->all());
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
            'type' => 'nullable|string|in:general,diagnosis,prescription,followup',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $note = $this->medicalNoteService->create($request->all());
        return $this->success($note, 'یادداشت پزشکی با موفقیت ایجاد شد', 201);
    }

    public function show($id)
    {
        $note = $this->medicalNoteService->find($id);
        if (!$note) {
            return $this->error('یادداشت یافت نشد', 404);
        }
        return $this->success($note);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'type' => 'sometimes|string|in:general,diagnosis,prescription,followup',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $note = $this->medicalNoteService->update($id, $request->all());
        if (!$note) {
            return $this->error('یادداشت یافت نشد', 404);
        }
        return $this->success($note, 'یادداشت پزشکی با موفقیت بروزرسانی شد');
    }

    public function destroy($id)
    {
        $deleted = $this->medicalNoteService->delete($id);
        if (!$deleted) {
            return $this->error('یادداشت یافت نشد', 404);
        }
        return $this->success(null, 'یادداشت پزشکی با موفقیت حذف شد');
    }

    public function patientNotes($patientId)
    {
        $notes = $this->medicalNoteService->getPatientNotes($patientId);
        return $this->success($notes);
    }

    public function doctorNotes($doctorId)
    {
        $notes = $this->medicalNoteService->getDoctorNotes($doctorId);
        return $this->success($notes);
    }
}
