<?php

namespace App\Services\Telemedicine;

use App\Models\TelemedicineSession;
use App\Models\TelemedicineMessage;
use App\Models\TelemedicineFile;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class TelemedicineService
{
    public function createSession(array $data): TelemedicineSession
    {
        return DB::transaction(function () use ($data) {
            $appointment = Appointment::findOrFail($data['appointment_id']);

            if ($appointment->type !== 'online') {
                throw new \Exception('این نوبت برای ویزیت آنلاین نیست');
            }

            $session = TelemedicineSession::create([
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $appointment->doctor_id,
                'status' => 'scheduled',
                'notes' => $data['notes'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            $this->sendSessionLink($session);

            return $session->load(['patient', 'doctor']);
        });
    }

    public function getSession($id): TelemedicineSession
    {
        return TelemedicineSession::with(['patient', 'doctor', 'appointment'])
            ->findOrFail($id);
    }

    public function getSessionByRoom(string $roomName): TelemedicineSession
    {
        return TelemedicineSession::with(['patient', 'doctor'])
            ->where('room_name', $roomName)
            ->firstOrFail();
    }

    public function getSessions(array $filters = [], int $perPage = 15)
    {
        $query = TelemedicineSession::with(['patient', 'doctor']);

        if (isset($filters['doctor_id'])) {
            $query->byDoctor($filters['doctor_id']);
        }

        if (isset($filters['patient_id'])) {
            $query->byPatient($filters['patient_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date'])) {
            $query->whereDate('created_at', $filters['date']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getDoctorSessions(int $doctorId, array $filters = [], int $perPage = 15)
    {
        $filters['doctor_id'] = $doctorId;
        return $this->getSessions($filters, $perPage);
    }

    public function getPatientSessions(int $patientId, array $filters = [], int $perPage = 15)
    {
        $filters['patient_id'] = $patientId;
        return $this->getSessions($filters, $perPage);
    }

    public function getActiveSessions(int $doctorId)
    {
        return TelemedicineSession::byDoctor($doctorId)
            ->active()
            ->with(['patient'])
            ->get();
    }

    public function startSession(int $sessionId): TelemedicineSession
    {
        $session = TelemedicineSession::findOrFail($sessionId);

        if ($session->status !== 'scheduled' && $session->status !== 'waiting') {
            throw new \Exception('این جلسه قابل شروع نیست');
        }

        $session->start();
        return $session->fresh();
    }

    public function completeSession(int $sessionId): TelemedicineSession
    {
        $session = TelemedicineSession::findOrFail($sessionId);

        if ($session->status !== 'in_progress') {
            throw new \Exception('این جلسه در حال برگزاری نیست');
        }

        $session->complete();
        return $session->fresh();
    }

    public function cancelSession(int $sessionId): TelemedicineSession
    {
        $session = TelemedicineSession::findOrFail($sessionId);
        $session->cancel();
        return $session->fresh();
    }

    public function joinSession(int $sessionId, int $userId): array
    {
        $session = TelemedicineSession::findOrFail($sessionId);

        $user = \App\Models\User::findOrFail($userId);
        $isPatient = $session->patient->user_id === $userId;
        $isDoctor = $session->doctor->user_id === $userId;

        if (!$isPatient && !$isDoctor && !$user->isAdmin()) {
            throw new \Exception('شما دسترسی به این جلسه ندارید');
        }

        if ($session->status === 'scheduled') {
            $session->markAsWaiting();
        }

        return [
            'session' => $session,
            'room_name' => $session->room_name,
            'room_url' => $session->room_url,
            'token' => $this->generateRoomToken($session, $userId),
        ];
    }

    public function sendMessage(array $data): TelemedicineMessage
    {
        return DB::transaction(function () use ($data) {
            $session = TelemedicineSession::findOrFail($data['session_id']);

            $message = TelemedicineMessage::create([
                'session_id' => $data['session_id'],
                'user_id' => $data['user_id'],
                'message' => $data['message'],
                'type' => $data['type'] ?? 'text',
                'file_path' => $data['file_path'] ?? null,
            ]);

            $this->sendMessageNotification($message);

            return $message->fresh(['user']);
        });
    }

    public function getMessages(int $sessionId, int $perPage = 50)
    {
        return TelemedicineMessage::bySession($sessionId)
            ->with(['user'])
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
    }

    public function markMessagesAsRead(int $sessionId, int $userId): void
    {
        TelemedicineMessage::where('session_id', $sessionId)
            ->where('user_id', '!=', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    public function getUnreadCount(int $sessionId, int $userId): int
    {
        return TelemedicineMessage::where('session_id', $sessionId)
            ->where('user_id', '!=', $userId)
            ->where('is_read', false)
            ->count();
    }

    public function uploadFile(array $data, $file): TelemedicineFile
    {
        return DB::transaction(function () use ($data, $file) {
            $session = TelemedicineSession::findOrFail($data['session_id']);

            $path = $file->store('telemedicine/' . $session->id, 'public');

            $teleFile = TelemedicineFile::create([
                'session_id' => $data['session_id'],
                'user_id' => $data['user_id'],
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'description' => $data['description'] ?? null,
            ]);

            return $teleFile;
        });
    }

    public function getFiles(int $sessionId)
    {
        return TelemedicineFile::where('session_id', $sessionId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function deleteFile(int $fileId): void
    {
        $file = TelemedicineFile::findOrFail($fileId);
        Storage::disk('public')->delete($file->file_path);
        $file->delete();
    }

    public function getStats(array $filters = []): array
    {
        $query = TelemedicineSession::query();

        if (isset($filters['doctor_id'])) {
            $query->byDoctor($filters['doctor_id']);
        }

        return [
            'total_sessions' => $query->count(),
            'active_sessions' => (clone $query)->active()->count(),
            'completed_today' => (clone $query)->where('status', 'completed')->today()->count(),
            'scheduled_today' => (clone $query)->where('status', 'scheduled')->today()->count(),
        ];
    }

    private function generateRoomToken(TelemedicineSession $session, int $userId): string
    {
        $payload = [
            'session_id' => $session->id,
            'user_id' => $userId,
            'room' => $session->room_name,
            'expires' => now()->addHours(2)->timestamp,
        ];

        return base64_encode(json_encode($payload));
    }

    private function sendSessionLink(TelemedicineSession $session): void
    {
        try {
            $patientLink = config('app.frontend_url') . '/telemedicine/join/' . $session->room_name;
            $doctorLink = config('app.frontend_url') . '/telemedicine/doctor/' . $session->room_name;

            Log::info('Telemedicine session created', [
                'session_id' => $session->id,
                'patient_link' => $patientLink,
                'doctor_link' => $doctorLink,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send session link: ' . $e->getMessage());
        }
    }

    private function sendMessageNotification(TelemedicineMessage $message): void
    {
        try {
            Log::info('Telemedicine message sent', [
                'session_id' => $message->session_id,
                'user_id' => $message->user_id,
                'message_id' => $message->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send message notification: ' . $e->getMessage());
        }
    }
}
