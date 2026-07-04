<?php
// app/Http/Controllers/Admin/NotificationController.php

namespace App\Http\Controllers\Admin;

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

    /**
     * لیست اعلان‌ها
     */
    public function index(Request $request)
    {
        try {
            $userId = auth()->id();

            $query = Notification::where('user_id', $userId)
                ->with(['sender']);

            // فیلتر بر اساس خوانده شده/نخوانده
            if ($request->has('read')) {
                if ($request->read) {
                    $query->where('is_read', true);
                } else {
                    $query->where('is_read', false);
                }
            }

            // فیلتر بر اساس اولویت
            if ($request->has('priority')) {
                $query->where('priority', $request->priority);
            }

            // فیلتر بر اساس نوع
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // جستجو
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('body', 'like', "%{$search}%");
                });
            }

            $notifications = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return $this->success($notifications);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * نمایش اعلان
     */
    public function show($id)
    {
        try {
            $userId = auth()->id();

            $notification = Notification::where('user_id', $userId)
                ->with(['sender'])
                ->findOrFail($id);

            return $this->success($notification);
        } catch (\Exception $e) {
            return $this->error('اعلان یافت نشد', 404);
        }
    }

    /**
     * ارسال به همه کاربران
     */
    public function sendToAll(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'type' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $count = $this->notificationService->sendToAll(
                $request->title,
                $request->message,
                [],
                $request->type ?? 'system',
                $request->priority ?? 'medium'
            );

            return $this->success(
                ['sent_count' => $count],
                "اعلان برای {$count} کاربر ارسال شد"
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * ارسال به پزشکان
     */
    public function sendToDoctors(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'type' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $count = $this->notificationService->sendToAllDoctors(
                $request->title,
                $request->message,
                [],
                $request->type ?? 'system',
                $request->priority ?? 'medium'
            );

            return $this->success(
                ['sent_count' => $count],
                "اعلان برای {$count} پزشک ارسال شد"
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * ارسال به بیماران
     */
    public function sendToPatients(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'type' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $count = $this->notificationService->sendToAllPatients(
                $request->title,
                $request->message,
                [],
                $request->type ?? 'system',
                $request->priority ?? 'medium'
            );

            return $this->success(
                ['sent_count' => $count],
                "اعلان برای {$count} بیمار ارسال شد"
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * ارسال به کاربر خاص
     */
    public function sendToUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'type' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $notification = $this->notificationService->sendToUser(
                $request->user_id,
                $request->title,
                $request->message,
                [],
                $request->type ?? 'direct',
                $request->priority ?? 'medium'
            );

            return $this->success(
                $notification->load(['sender']),
                'اعلان با موفقیت ارسال شد'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * ارسال به بیماران یک پزشک خاص
     */
    public function sendToDoctorPatients(Request $request, $doctorId)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'type' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $count = $this->notificationService->sendToDoctorPatients(
                $doctorId,
                $request->title,
                $request->message,
                [],
                $request->type ?? 'system',
                $request->priority ?? 'medium'
            );

            return $this->success(
                ['sent_count' => $count],
                "اعلان برای {$count} بیمار ارسال شد"
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * علامت‌گذاری به عنوان خوانده شده
     */
    public function markAsRead($id)
    {
        try {
            $userId = auth()->id();

            $notification = Notification::where('user_id', $userId)
                ->findOrFail($id);

            $notification->markAsRead();

            return $this->success($notification->fresh(), 'اعلان به عنوان خوانده شده علامت‌گذاری شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * علامت‌گذاری همه به عنوان خوانده شده
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $userId = auth()->id();

            $count = $this->notificationService->markAllAsRead($userId);

            return $this->success(
                ['updated_count' => $count],
                'همه اعلان‌ها به عنوان خوانده شده علامت‌گذاری شدند'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف اعلان
     */
    public function destroy($id)
    {
        try {
            $userId = auth()->id();

            $notification = Notification::where('user_id', $userId)
                ->findOrFail($id);

            $notification->delete();

            return $this->success(null, 'اعلان با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف همه اعلان‌های خوانده شده
     */
    public function deleteAllRead(Request $request)
    {
        try {
            $userId = auth()->id();

            Notification::where('user_id', $userId)
                ->where('is_read', true)
                ->delete();

            return $this->success(null, 'همه اعلان‌های خوانده شده حذف شدند');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * آمار اعلان‌ها
     */
    public function stats()
    {
        try {
            $userId = auth()->id();

            $total = Notification::where('user_id', $userId)->count();
            $unread = Notification::where('user_id', $userId)
                ->where('is_read', false)
                ->count();
            $read = Notification::where('user_id', $userId)
                ->where('is_read', true)
                ->count();

            return $this->success([
                'total' => $total,
                'unread' => $unread,
                'read' => $read,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * اعلان‌های اخیر (برای داشبورد)
     */
    public function recent(Request $request)
    {
        try {
            $userId = auth()->id();
            $limit = $request->get('limit', 10);

            $notifications = Notification::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            return $this->success($notifications);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
