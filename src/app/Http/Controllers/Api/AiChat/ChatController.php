<?php

namespace App\Http\Controllers\Api\AiChat;

use App\Http\Controllers\Controller;
use App\Services\AiChat\Chat\ChatService;
use App\Services\AiChat\Medical\MedicalFilterService;
use App\Services\AiChat\System\ConfigManager;
use App\Services\AiChat\System\MetricsCollector;
use App\Exceptions\AiChat\SessionExpiredException;
use App\Exceptions\AiChat\NonMedicalQuestionException;
use App\Exceptions\AiChat\EmergencyException;
use App\Models\AiChat\ChatSession;
use App\Models\AiChat\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;

class ChatController extends Controller
{
    public function __construct(
        private ChatService $chatService,
        private MedicalFilterService $medicalFilter,
        private ConfigManager $configManager,
        private MetricsCollector $metricsCollector
    ) {}

    /**
     * شروع جلسه چت جدید
     */
    public function start(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'model' => 'nullable|string|in:' . implode(',', $this->configManager->getArray('models.available', ['qwen3:14b'])),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $session = $this->chatService->startSession(
                auth()->user(),
                $request->title,
                $request->model
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'session' => [
                        'id' => $session->id,
                        'session_token' => $session->session_token,
                        'title' => $session->title,
                        'status' => $session->status->value,
                        'expires_at' => $session->expires_at->toDateTimeString(),
                        'model_used' => $session->model_used,
                    ],
                    'message' => [
                        'role' => 'system',
                        'content' => 'سلام! من "دکتر آنلاین" هستم. چطور می‌توانم به شما کمک کنم؟',
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ارسال پیام به هوش مصنوعی
     */
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:5000',
            'session_token' => 'nullable|string|exists:chat_sessions,session_token',
            'model' => 'nullable|string',
            'temperature' => 'nullable|numeric|min:0|max:1',
            'max_tokens' => 'nullable|integer|min:1|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // بررسی محدودیت نرخ درخواست
        $key = 'chat:' . auth()->id() . ':' . now()->format('Y-m-d-H');
        if (RateLimiter::tooManyAttempts($key, $this->configManager->getInt('rate_limit.per_hour', 100))) {
            return response()->json([
                'success' => false,
                'message' => 'تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً بعداً تلاش کنید.',
                'retry_after' => RateLimiter::availableIn($key),
            ], 429);
        }
        RateLimiter::hit($key);

        try {
            $response = $this->chatService->sendMessage(
                auth()->user(),
                $request->message,
                $request->session_token,
                [
                    'model' => $request->model,
                    'temperature' => $request->temperature,
                    'max_tokens' => $request->max_tokens,
                ]
            );

            return response()->json([
                'success' => true,
                'data' => $response,
            ]);

        } catch (EmergencyException $e) {
            return response()->json([
                'success' => false,
                'is_emergency' => true,
                'message' => $e->getMessage(),
                'data' => $e->getEmergencyData(),
                'action' => 'تماس با اورژانس 115',
            ], 429);

        } catch (NonMedicalQuestionException $e) {
            return response()->json([
                'success' => false,
                'is_medical' => false,
                'message' => $e->getMessage(),
                'suggestions' => $this->medicalFilter->getSuggestions($request->message),
            ], 400);

        } catch (SessionExpiredException $e) {
            return response()->json([
                'success' => false,
                'is_expired' => true,
                'message' => $e->getMessage(),
                'action' => 'شروع جلسه جدید',
            ], 401);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * دریافت تاریخچه پیام‌های جلسه
     */
    public function history(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_token' => 'required|string|exists:chat_sessions,session_token',
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $session = ChatSession::where('session_token', $request->session_token)
            ->where('user_id', auth()->id())
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'جلسه‌ای با این توکن یافت نشد',
            ], 404);
        }

        $messages = $this->chatService->getMessages(
            $session,
            $request->limit ?? 50,
            $request->offset ?? 0
        );

        return response()->json([
            'success' => true,
            'data' => [
                'session' => [
                    'id' => $session->id,
                    'session_token' => $session->session_token,
                    'title' => $session->title,
                    'status' => $session->status->value,
                    'expires_at' => $session->expires_at->toDateTimeString(),
                    'message_count' => $session->message_count,
                ],
                'messages' => $messages,
                'pagination' => [
                    'limit' => $request->limit ?? 50,
                    'offset' => $request->offset ?? 0,
                    'total' => $session->messages()->count(),
                ],
            ],
        ]);
    }

    /**
     * بستن جلسه چت
     */
    public function close(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_token' => 'required|string|exists:chat_sessions,session_token',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $session = ChatSession::where('session_token', $request->session_token)
            ->where('user_id', auth()->id())
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'جلسه‌ای با این توکن یافت نشد',
            ], 404);
        }

        $this->chatService->closeSession($session);

        return response()->json([
            'success' => true,
            'message' => 'جلسه با موفقیت بسته شد',
        ]);
    }

    /**
     * تمدید اعتبار جلسه
     */
    public function extend(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_token' => 'required|string|exists:chat_sessions,session_token',
            'minutes' => 'nullable|integer|min:60|max:10080',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $session = ChatSession::where('session_token', $request->session_token)
            ->where('user_id', auth()->id())
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'جلسه‌ای با این توکن یافت نشد',
            ], 404);
        }

        $minutes = $request->minutes ?? $this->configManager->getInt('session.lifetime', 1440);
        $this->chatService->extendSession($session, $minutes);

        return response()->json([
            'success' => true,
            'message' => 'اعتبار جلسه تمدید شد',
            'data' => [
                'expires_at' => $session->fresh()->expires_at->toDateTimeString(),
            ],
        ]);
    }

    /**
     * حذف جلسه
     */
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_token' => 'required|string|exists:chat_sessions,session_token',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $session = ChatSession::where('session_token', $request->session_token)
            ->where('user_id', auth()->id())
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'جلسه‌ای با این توکن یافت نشد',
            ], 404);
        }

        $this->chatService->deleteSession($session);

        return response()->json([
            'success' => true,
            'message' => 'جلسه با موفقیت حذف شد',
        ]);
    }

    /**
     * ثبت بازخورد
     */
    public function feedback(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message_id' => 'required|integer|exists:chat_messages,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $message = ChatMessage::with('session')->find($request->message_id);

        if (!$message || $message->session->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'شما اجازه ثبت بازخورد برای این پیام را ندارید',
            ], 403);
        }

        $result = $this->chatService->submitFeedback(
            auth()->user(),
            $message,
            $request->rating,
            $request->comment
        );

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'ثبت بازخورد ناموفق بود. ممکن است قبلاً ثبت شده باشد.',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'بازخورد شما با موفقیت ثبت شد. متشکریم!',
        ]);
    }

    /**
     * دریافت اطلاعات جلسه فعال
     */
    public function active(Request $request)
    {
        $session = $this->chatService->getActiveSession(auth()->user());

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'هیچ جلسه فعالی یافت نشد',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'session' => [
                    'id' => $session->id,
                    'session_token' => $session->session_token,
                    'title' => $session->title,
                    'status' => $session->status->value,
                    'expires_at' => $session->expires_at->toDateTimeString(),
                    'message_count' => $session->message_count,
                    'last_activity' => $session->last_activity?->toDateTimeString(),
                ],
                'is_expired' => $session->isExpired(),
                'remaining_minutes' => $session->isExpired() ? 0 : now()->diffInMinutes($session->expires_at),
            ],
        ]);
    }
}
