<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Services\Advertising\AdvertisingEventService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
class AdvertisingEventController extends Controller
{
    use ApiResponse;
    public function __construct(private readonly AdvertisingEventService $service) {}
    public function store(Request $request)
    {
        $data = $request->validate(['campaign_id'=>'required|string|max:100','event_type'=>'required|in:impression,click,video','placement'=>'required|string|max:100','locale'=>'nullable|in:fa,en,ar']);
        $data['campaign_key'] = $data['campaign_id']; unset($data['campaign_id']);
        $data['ip_address'] = $request->ip(); $data['user_agent'] = mb_substr((string)$request->userAgent(),0,500);
        $event = $this->service->record($data);
        return $this->success(['id'=>$event->id], 'رویداد ثبت شد', 201);
    }
}
