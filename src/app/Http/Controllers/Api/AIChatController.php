<?php
// app/Http/Controllers/Api/AIChatController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiChat\AIChatService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AIChatController extends Controller
{
    use ApiResponse;

    public function __construct(private AIChatService $chatService) {}

    /**
     * شروع جلسه چت جدید
     */
    public function start(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'provider' => 'nullable|string|in:ollama,openai,gemini',
            'model' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $user = auth()->user();
            $session = $this->chatService->startSession(
                $user,
                $request->title,
                $request->provider,
                $request->model
            );

            return $this->success([
                'session' => $session->only(['id', 'session_token', 'status', 'expires_at']),
                'provider' => $session->provider,
                'model_used' => $session->model_used,
            ], 'جلسه چت با موفقیت شروع شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت جلسه فعال
     */
    public function active(Request $request)
    {
        try {
            $user = auth()->user();
            $session = $this->chatService->getActiveSession($user, $request->session_token);

            if (!$session) {
                return $this->error('جلسه فعالی یافت نشد', 404);
            }

            return $this->success([
                'session' => $session->only(['id', 'session_token', 'status', 'expires_at']),
                'message_count' => $session->message_count,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * ارسال پیام
     */
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:2000',
            'session_token' => 'nullable|string',
            'provider' => 'nullable|string|in:ollama,openai,gemini',
            'model' => 'nullable|string',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer|min:1|max:4096',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $user = auth()->user();
            $result = $this->chatService->sendMessage(
                $user,
                $request->message,
                $request->session_token,
                $request->only(['provider', 'model', 'temperature', 'max_tokens'])
            );

            return $this->success($result, 'پاسخ با موفقیت دریافت شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت تاریخچه پیام‌های جلسه
     */
    public function history(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_token' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $user = auth()->user();
            $session = $this->chatService->getActiveSession($user, $request->session_token);

            if (!$session) {
                return $this->error('جلسه یافت نشد', 404);
            }

            $messages = $this->chatService->getMessages(
                $session,
                $request->limit ?? 50,
                $request->offset ?? 0
            );

            return $this->success([
                'messages' => $messages,
                'total' => $session->message_count,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * بستن جلسه
     */
    public function close(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_token' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $user = auth()->user();
            $session = $this->chatService->getActiveSession($user, $request->session_token);

            if (!$session) {
                return $this->error('جلسه یافت نشد', 404);
            }

            $this->chatService->closeSession($session);
            return $this->success(null, 'جلسه چت با موفقیت بسته شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تمدید جلسه
     */
    public function extend(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_token' => 'nullable|string',
            'minutes' => 'nullable|integer|min:1|max:4320',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $user = auth()->user();
            $session = $this->chatService->getActiveSession($user, $request->session_token);

            if (!$session) {
                return $this->error('جلسه یافت نشد', 404);
            }

            $this->chatService->extendSession($session, $request->minutes ?? 1440);
            return $this->success([
                'expires_at' => $session->expires_at,
            ], 'جلسه با موفقیت تمدید شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف جلسه
     */
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $user = auth()->user();
            $session = $this->chatService->getActiveSession($user, $request->session_token);

            if (!$session) {
                return $this->error('جلسه یافت نشد', 404);
            }

            $this->chatService->deleteSession($session);
            return $this->success(null, 'جلسه با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * ثبت بازخورد
     */
    public function feedback(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message_id' => 'required|exists:chat_messages,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $user = auth()->user();
            $message = \App\Models\AiChat\ChatMessage::findOrFail($request->message_id);

            $result = $this->chatService->submitFeedback(
                $user,
                $message,
                $request->rating,
                $request->comment
            );

            if (!$result) {
                return $this->error('ثبت بازخورد ناموفق بود', 400);
            }

            return $this->success(null, 'بازخورد با موفقیت ثبت شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت لیست providerهای فعال
     */
    public function providers()
    {
        try {
            $providers = $this->chatService->getActiveProviders();
            $default = $this->chatService->getDefaultProvider();

            return $this->success([
                'available' => $providers,
                'default' => $default,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
