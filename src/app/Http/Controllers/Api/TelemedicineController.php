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
            'appointment_id' => 'required|exists:appointments,id',
            'notes' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $session = $this->telemedicineService->createSession($request->all());
            return $this->success($session, 'جلسه ویزیت آنلاین با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function getSession($id)
    {
        try {
            $session = $this->telemedicineService->getSession($id);
            return $this->success($session);
        } catch (\Exception $e) {
            return $this->error('جلسه یافت نشد', 404);
        }
    }

    public function getSessionByRoom($roomName)
    {
        try {
            $session = $this->telemedicineService->getSessionByRoom($roomName);
            return $this->success($session);
        } catch (\Exception $e) {
            return $this->error('جلسه یافت نشد', 404);
        }
    }

    public function listSessions(Request $request)
    {
        $sessions = $this->telemedicineService->getSessions($request->all(), $request->get('per_page', 15));
        return $this->success($sessions);
    }

    public function doctorSessions(Request $request, $doctorId)
    {
        $sessions = $this->telemedicineService->getDoctorSessions($doctorId, $request->all(), $request->get('per_page', 15));
        return $this->success($sessions);
    }

    public function patientSessions(Request $request, $patientId)
    {
        $sessions = $this->telemedicineService->getPatientSessions($patientId, $request->all(), $request->get('per_page', 15));
        return $this->success($sessions);
    }

    public function activeSessions($doctorId)
    {
        $sessions = $this->telemedicineService->getActiveSessions($doctorId);
        return $this->success($sessions);
    }

    public function startSession($id)
    {
        try {
            $session = $this->telemedicineService->startSession($id);
            return $this->success($session, 'جلسه با موفقیت شروع شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function completeSession($id)
    {
        try {
            $session = $this->telemedicineService->completeSession($id);
            return $this->success($session, 'جلسه با موفقیت پایان یافت');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function cancelSession($id)
    {
        try {
            $session = $this->telemedicineService->cancelSession($id);
            return $this->success($session, 'جلسه با موفقیت لغو شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function joinSession($id)
    {
        try {
            $result = $this->telemedicineService->joinSession($id, auth()->id());
            return $this->success($result, 'اتصال به جلسه با موفقیت انجام شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|exists:telemedicine_sessions,id',
            'message' => 'required|string|max:1000',
            'type' => 'nullable|in:text,image,file,prescription',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $request->all();
            $data['user_id'] = auth()->id();
            $message = $this->telemedicineService->sendMessage($data);
            return $this->success($message, 'پیام با موفقیت ارسال شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function getMessages(Request $request, $sessionId)
    {
        $messages = $this->telemedicineService->getMessages($sessionId, $request->get('per_page', 50));
        $this->telemedicineService->markMessagesAsRead($sessionId, auth()->id());
        return $this->success($messages);
    }

    public function unreadCount($sessionId)
    {
        $count = $this->telemedicineService->getUnreadCount($sessionId, auth()->id());
        return $this->success(['count' => $count]);
    }

    public function uploadFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|exists:telemedicine_sessions,id',
            'file' => 'required|file|max:20480',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $request->except('file');
            $data['user_id'] = auth()->id();
            $file = $this->telemedicineService->uploadFile($data, $request->file('file'));
            return $this->success($file, 'فایل با موفقیت آپلود شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function getFiles($sessionId)
    {
        $files = $this->telemedicineService->getFiles($sessionId);
        return $this->success($files);
    }

    public function deleteFile($id)
    {
        try {
            $this->telemedicineService->deleteFile($id);
            return $this->success(null, 'فایل با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function stats(Request $request)
    {
        $stats = $this->telemedicineService->getStats($request->all());
        return $this->success($stats);
    }
}
