<?php

namespace App\Http\Controllers\Api\AiChat;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    use ApiResponse;

    public function start(Request $request)
    {
        return $this->success([
            'id' => 'session_' . time(),
            'model' => $request->input('model', 'qwen3:14b'),
            'messages' => [],
            'created_at' => now()->toISOString()
        ],
//            'چت با موفقیت شروع شد'
        );
    }

    public function active(Request $request)
    {
        return $this->success([
            'id' => 'session_' . time(),
            'is_active' => true,
            'messages' => []
        ]);
    }

    public function send(Request $request)
    {
        $message = $request->input('message', '');

        return $this->success([
            'id' => 'msg_' . time(),
            'response' => 'این یک پاسخ آزمایشی است. شما گفتید: ' . $message,
            'created_at' => now()->toISOString()
        ]);
    }

    public function close(Request $request)
    {
        return $this->success(null, 'چت با موفقیت بسته شد');
    }

    public function extend(Request $request)
    {
        return $this->success(null, 'جلسه چت تمدید شد');
    }

    public function destroy(Request $request)
    {
        return $this->success(null, 'جلسه چت حذف شد');
    }

    public function history(Request $request)
    {
        return $this->success([
            'messages' => [],
            'total' => 0
        ]);
    }

    public function feedback(Request $request)
    {
        return $this->success(null, 'بازخورد با موفقیت ثبت شد');
    }
}
