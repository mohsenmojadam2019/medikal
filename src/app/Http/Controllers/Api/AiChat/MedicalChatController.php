<?php

namespace App\Http\Controllers\Api\AiChat;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class MedicalChatController extends Controller
{
    use ApiResponse;

    public function ask(Request $request)
    {
        return $this->success([
            'response' => 'پاسخ به سوال پزشکی شما: ' . $request->input('question', ''),
            'sources' => ['منبع پزشکی معتبر']
        ]);
    }

    public function symptomCheck(Request $request)
    {
        return $this->success([
            'possible_conditions' => ['احتمالاً سرماخوردگی'],
            'severity' => 'خفیف',
            'recommendation' => 'استراحت و مایعات کافی'
        ]);
    }

    public function history(Request $request)
    {
        return $this->success([
            'history' => [],
            'total' => 0
        ]);
    }

    public function categories(Request $request)
    {
        return $this->success([
            'categories' => ['عمومی', 'داخلی', 'قلب', 'مغز']
        ]);
    }

    public function stats(Request $request)
    {
        return $this->success([
            'total_questions' => 0,
            'total_sessions' => 0
        ]);
    }
}
