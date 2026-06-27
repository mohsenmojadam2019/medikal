<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Telemedicine\TelemedicineService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TelemedicineController extends Controller
{
    use ApiResponse;

    protected TelemedicineService $telemedicineService;

    public function __construct(TelemedicineService $telemedicineService)
    {
        $this->telemedicineService = $telemedicineService;
    }

    public function createSession(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required|exists:doctors,id',
            'patient_id' => 'required|exists:patients,id',
            'scheduled_at' => 'required|date|after:now',
            'duration_minutes' => 'required|integer|min:15|max:120',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $session = $this->telemedicineService->createSession($request->all());
        return $this->success($session, 'جلسه پزشکی از راه دور با موفقیت ایجاد شد', 201);
    }

    public function listSessions(Request $request)
    {
        $sessions = $this->telemedicineService->listSessions($request->all());
        return $this->success($sessions);
    }

    public function getSession($id)
    {
        $session = $this->telemedicineService->getSession($id);
        if (!$session) {
            return $this->error('جلسه یافت نشد', 404);
        }
        return $this->success($session);
    }

    public function getSessionByRoom($roomName)
    {
        $session = $this->telemedicineService->getSessionByRoom($roomName);
        if (!$session) {
            return $this->error('جلسه یافت نشد', 404);
        }
        return $this->success($session);
    }

    public function doctorSessions($doctorId)
    {
        $sessions = $this->telemedicineService->getDoctorSessions($doctorId);
        return $this->success($sessions);
    }

    public function patientSessions($patientId)
    {
        $sessions = $this->telemedicineService->getPatientSessions($patientId);
        return $this->success($sessions);
    }

    public function activeSessions($doctorId)
    {
        $sessions = $this->telemedicineService->getActiveSessions($doctorId);
        return $this->success($sessions);
    }

    public function startSession($id)
    {
        $session = $this->telemedicineService->startSession($id);
        if (!$session) {
            return $this->error('جلسه یافت نشد', 404);
        }
        return $this->success($session, 'جلسه با موفقیت شروع شد');
    }

    public function completeSession(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string',
            'prescription' => 'nullable|string',
            'diagnosis' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $session = $this->telemedicineService->completeSession($id, $request->all());
        if (!$session) {
            return $this->error('جلسه یافت نشد', 404);
        }
        return $this->success($session, 'جلسه با موفقیت تکمیل شد');
    }

    public function cancelSession($id)
    {
        $session = $this->telemedicineService->cancelSession($id);
        if (!$session) {
            return $this->error('جلسه یافت نشد', 404);
        }
        return $this->success($session, 'جلسه با موفقیت لغو شد');
    }
}
