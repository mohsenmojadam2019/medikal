<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\Notification\NotificationService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    use ApiResponse;

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        $notifications = $this->notificationService->getUserNotifications(
            auth()->id(),
            $request->get('per_page', 20)
        );

        $unreadCount = $this->notificationService->getUnreadCount(auth()->id());

        return $this->success([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    public function unread(Request $request)
    {
        $notifications = $this->notificationService->getUnreadNotifications(auth()->id());

        return $this->success([
            'notifications' => $notifications,
            'count' => $notifications->count(),
        ]);
    }

    public function unreadCount()
    {
        $count = $this->notificationService->getUnreadCount(auth()->id());

        return $this->success(['count' => $count]);
    }

    public function show($id)
    {
        $notification = Notification::byUser(auth()->id())->find($id);

        if (!$notification) {
            return $this->error('اعلان یافت نشد', 404);
        }

        if (!$notification->is_read) {
            $notification->markAsRead();
        }

        return $this->success($notification);
    }

    public function markAsRead($id)
    {
        $notification = Notification::byUser(auth()->id())->find($id);

        if (!$notification) {
            return $this->error('اعلان یافت نشد', 404);
        }

        $notification->markAsRead();

        return $this->success(null, 'اعلان با موفقیت خوانده شد');
    }

    public function markAllAsRead()
    {
        $count = $this->notificationService->markAllAsRead(auth()->id());

        return $this->success(
            ['count' => $count],
            "{$count} اعلان با موفقیت خوانده شد"
        );
    }

    public function destroy($id)
    {
        $notification = Notification::byUser(auth()->id())->find($id);

        if (!$notification) {
            return $this->error('اعلان یافت نشد', 404);
        }

        $notification->delete();

        return $this->success(null, 'اعلان با موفقیت حذف شد');
    }

    public function deleteRead()
    {
        $count = Notification::byUser(auth()->id())
            ->read()
            ->delete();

        return $this->success(
            ['count' => $count],
            "{$count} اعلان با موفقیت حذف شد"
        );
    }

    // ============================================================
    // ADMIN METHODS
    // ============================================================

    public function sendToUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'nullable|string',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $notification = $this->notificationService->sendToUser(
                $request->user_id,
                $request->title,
                $request->body,
                $request->data ?? [],
                $request->type ?? 'system',
                $request->priority ?? 'normal'
            );

            return $this->success($notification, 'اعلان با موفقیت ارسال شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function sendToUsers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'nullable|string',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $count = $this->notificationService->sendToUsers(
                $request->user_ids,
                $request->title,
                $request->body,
                $request->data ?? [],
                $request->type ?? 'system',
                $request->priority ?? 'normal'
            );

            return $this->success(
                ['count' => $count],
                "{$count} اعلان با موفقیت ارسال شد",
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function sendToRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|exists:roles,name',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'nullable|string',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $count = $this->notificationService->sendToRole(
                $request->role,
                $request->title,
                $request->body,
                $request->data ?? [],
                $request->type ?? 'system',
                $request->priority ?? 'normal'
            );

            return $this->success(
                ['count' => $count],
                "{$count} اعلان با موفقیت ارسال شد",
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function sendToAllDoctors(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'nullable|string',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $count = $this->notificationService->sendToAllDoctors(
                $request->title,
                $request->body,
                $request->data ?? [],
                $request->type ?? 'system',
                $request->priority ?? 'normal'
            );

            return $this->success(
                ['count' => $count],
                "{$count} اعلان با موفقیت به پزشکان ارسال شد",
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function sendToAllPatients(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'nullable|string',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $count = $this->notificationService->sendToAllPatients(
                $request->title,
                $request->body,
                $request->data ?? [],
                $request->type ?? 'system',
                $request->priority ?? 'normal'
            );

            return $this->success(
                ['count' => $count],
                "{$count} اعلان با موفقیت به بیماران ارسال شد",
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function sendToAll(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'nullable|string',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $count = $this->notificationService->sendToAll(
                $request->title,
                $request->body,
                $request->data ?? [],
                $request->type ?? 'system',
                $request->priority ?? 'normal'
            );

            return $this->success(
                ['count' => $count],
                "{$count} اعلان با موفقیت به همه کاربران ارسال شد",
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function sendToDoctorPatients(Request $request, $doctorId)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'nullable|string',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $count = $this->notificationService->sendToDoctorPatients(
                $doctorId,
                $request->title,
                $request->body,
                $request->data ?? [],
                $request->type ?? 'system',
                $request->priority ?? 'normal'
            );

            return $this->success(
                ['count' => $count],
                "{$count} اعلان با موفقیت به بیماران دکتر ارسال شد",
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function sendFiltered(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filters' => 'required|array',
            'filters.role' => 'nullable|exists:roles,name',
            'filters.roles' => 'nullable|array',
            'filters.roles.*' => 'exists:roles,name',
            'filters.specialty_id' => 'nullable|exists:specialties,id',
            'filters.province_id' => 'nullable|exists:provinces,id',
            'filters.city_id' => 'nullable|exists:cities,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'nullable|string',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $count = $this->notificationService->sendFiltered(
                $request->filters,
                $request->title,
                $request->body,
                $request->data ?? [],
                $request->type ?? 'system',
                $request->priority ?? 'normal'
            );

            return $this->success(
                ['count' => $count],
                "{$count} اعلان با موفقیت ارسال شد",
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function userNotifications(Request $request, $userId)
    {
        $notifications = $this->notificationService->getUserNotifications(
            $userId,
            $request->get('per_page', 20)
        );

        return $this->success($notifications);
    }
}
