<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PublicInquiry\PublicInquiryService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class PublicInquiryController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly PublicInquiryService $service) {}

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:home_doctor,medical_tourism,advertising,cooperation,map_feedback,contact,help',
            'locale' => 'required|in:fa,en,ar',
            'name' => 'required|string|max:120',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:190',
            'subject' => 'required|string|max:190',
            'message' => 'required|string|max:3000',
        ]);

        $data['ip_address'] = $request->ip();
        $data['user_agent'] = mb_substr((string) $request->userAgent(), 0, 500);
        $inquiry = $this->service->create($data);

        return $this->success(['id' => $inquiry->id, 'status' => $inquiry->status], 'درخواست با موفقیت ثبت شد', 201);
    }
}
