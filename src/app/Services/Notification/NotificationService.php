<?php

namespace App\Services\Notification;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function sendToUser(
        int $userId,
        string $title,
        string $body,
        array $data = [],
        string $type = 'system',
        string $priority = 'normal',
        ?int $senderId = null
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'sender_id' => $senderId ?? auth()->id(),
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'type' => $type,
            'priority' => $priority,
            'is_read' => false,
            'sent_at' => now(),
        ]);
    }

    public function sendToUsers(
        array $userIds,
        string $title,
        string $body,
        array $data = [],
        string $type = 'system',
        string $priority = 'normal',
        ?int $senderId = null
    ): int {
        $notifications = [];
        $now = now();

        foreach ($userIds as $userId) {
            $notifications[] = [
                'user_id' => $userId,
                'sender_id' => $senderId ?? auth()->id(),
                'title' => $title,
                'body' => $body,
                'data' => json_encode($data),
                'type' => $type,
                'priority' => $priority,
                'is_read' => false,
                'sent_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return Notification::insert($notifications) ? count($notifications) : 0;
    }

    public function sendToRole(
        string $role,
        string $title,
        string $body,
        array $data = [],
        string $type = 'system',
        string $priority = 'normal',
        ?int $senderId = null
    ): int {
        $userIds = User::role($role)->pluck('id')->toArray();

        if (empty($userIds)) {
            return 0;
        }

        return $this->sendToUsers($userIds, $title, $body, $data, $type, $priority, $senderId);
    }

    public function sendToAllDoctors(
        string $title,
        string $body,
        array $data = [],
        string $type = 'system',
        string $priority = 'normal'
    ): int {
        return $this->sendToRole('doctor', $title, $body, $data, $type, $priority);
    }

    public function sendToAllPatients(
        string $title,
        string $body,
        array $data = [],
        string $type = 'system',
        string $priority = 'normal'
    ): int {
        return $this->sendToRole('patient', $title, $body, $data, $type, $priority);
    }

    public function sendToAll(
        string $title,
        string $body,
        array $data = [],
        string $type = 'system',
        string $priority = 'normal',
        ?int $senderId = null
    ): int {
        $userIds = User::where('is_active', true)->pluck('id')->toArray();

        if (empty($userIds)) {
            return 0;
        }

        return $this->sendToUsers($userIds, $title, $body, $data, $type, $priority, $senderId);
    }

    public function sendToDoctorPatients(
        int $doctorId,
        string $title,
        string $body,
        array $data = [],
        string $type = 'system',
        string $priority = 'normal'
    ): int {
        $userIds = \App\Models\Patient::where('doctor_id', $doctorId)
            ->with('user')
            ->get()
            ->pluck('user.id')
            ->filter()
            ->toArray();

        if (empty($userIds)) {
            return 0;
        }

        return $this->sendToUsers($userIds, $title, $body, $data, $type, $priority);
    }

    public function getUserNotifications(int $userId, int $perPage = 20)
    {
        return Notification::byUser($userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getUnreadNotifications(int $userId)
    {
        return Notification::byUser($userId)
            ->unread()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getUnreadCount(int $userId): int
    {
        return Notification::byUser($userId)->unread()->count();
    }

    public function markAllAsRead(int $userId): int
    {
        return Notification::byUser($userId)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    public function deleteOldNotifications(int $days = 30): int
    {
        return Notification::where('created_at', '<', now()->subDays($days))
            ->delete();
    }
}
