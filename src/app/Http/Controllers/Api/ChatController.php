<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use App\Notifications\NewMessageNotification;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
    }

    /**
     * لیست مکالمات کاربر
     */
    public function conversations(Request $request)
    {
        $user = auth()->user();

        // دریافت کاربرانی که با آنها مکالمه داشته
        $userIds = Message::where('sender_id', $user->id)
            ->orWhere('receiver_id', $user->id)
            ->pluck('sender_id')
            ->merge(Message::where('sender_id', $user->id)
                ->orWhere('receiver_id', $user->id)
                ->pluck('receiver_id'))
            ->unique()
            ->filter(fn($id) => $id != $user->id)
            ->values();

        $conversations = User::whereIn('id', $userIds)
            ->get()
            ->map(function ($user) {
                $lastMessage = Message::where(function ($q) use ($user) {
                    $q->where('sender_id', auth()->id())->where('receiver_id', $user->id);
                })->orWhere(function ($q) use ($user) {
                    $q->where('sender_id', $user->id)->where('receiver_id', auth()->id());
                })->latest()->first();

                $unreadCount = Message::where('sender_id', $user->id)
                    ->where('receiver_id', auth()->id())
                    ->where('is_read', false)
                    ->count();

                return [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'role' => $user->role,
                        'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=2b6cb0&color=fff&size=50',
                    ],
                    'last_message' => $lastMessage?->message,
                    'last_message_time' => $lastMessage?->created_at?->diffForHumans(),
                    'unread_count' => $unreadCount,
                ];
            })
            ->sortByDesc('last_message_time')
            ->values();

        return $this->success($conversations);
    }

    /**
     * دریافت پیام‌های یک مکالمه
     */
    public function messages(Request $request, $userId)
    {
        $user = auth()->user();

        $validator = Validator::make(['user_id' => $userId], [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->error('کاربر یافت نشد', 404);
        }

        $messages = Message::conversation($user->id, $userId)
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->paginate($request->get('per_page', 50));

        // علامت‌گذاری پیام‌های خوانده نشده
        Message::where('sender_id', $userId)
            ->where('receiver_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return $this->success($messages);
    }

    /**
     * ارسال پیام جدید
     */
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string|max:1000',
            'type' => 'nullable|in:text,image,file',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $sender = auth()->user();
            $receiver = User::find($request->receiver_id);

            $message = Message::create([
                'sender_id' => $sender->id,
                'receiver_id' => $request->receiver_id,
                'message' => $request->message,
                'type' => $request->type ?? 'text',
            ]);

            // ارسال از طریق Reverb
            broadcast(new MessageSent($message))->toOthers();

            // ارسال نوتیفیکیشن به دریافت‌کننده
            $receiver->notify(new NewMessageNotification($message));

            return $this->success($message->load(['sender', 'receiver']), 'پیام با موفقیت ارسال شد');

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت تعداد پیام‌های خوانده نشده
     */
    public function unreadCount()
    {
        $user = auth()->user();
        $count = Message::unread($user->id)->count();

        return $this->success(['count' => $count]);
    }

    /**
     * علامت‌گذاری همه پیام‌ها به عنوان خوانده شده
     */
    public function markAllAsRead($userId)
    {
        $user = auth()->user();

        Message::where('sender_id', $userId)
            ->where('receiver_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return $this->success(null, 'همه پیام‌ها به عنوان خوانده شده علامت‌گذاری شدند');
    }

    /**
     * دریافت آخرین پیام‌های اخیر (برای پنل)
     */
    public function recent(Request $request)
    {
        $user = auth()->user();

        $messages = Message::where('sender_id', $user->id)
            ->orWhere('receiver_id', $user->id)
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'desc')
            ->limit($request->get('limit', 10))
            ->get();

        return $this->success($messages);
    }
}
