<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Campaign\CampaignService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CampaignController extends Controller
{
    use ApiResponse;

    protected CampaignService $campaignService;

    public function __construct(CampaignService $campaignService)
    {
        $this->campaignService = $campaignService;
    }

    public function index()
    {
        $campaigns = $this->campaignService->getAll();
        return $this->success($campaigns);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|string|in:email,sms,push,advertising',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'target_audience' => 'nullable|json',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $campaign = $this->campaignService->create($request->all());
        return $this->success($campaign, 'کمپین با موفقیت ایجاد شد', 201);
    }

    public function show($id)
    {
        $campaign = $this->campaignService->find($id);
        if (!$campaign) {
            return $this->error('کمپین یافت نشد', 404);
        }
        return $this->success($campaign);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'type' => 'sometimes|string|in:email,sms,push,advertising',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'target_audience' => 'nullable|json',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $campaign = $this->campaignService->update($id, $request->all());
        if (!$campaign) {
            return $this->error('کمپین یافت نشد', 404);
        }
        return $this->success($campaign, 'کمپین با موفقیت بروزرسانی شد');
    }

    public function destroy($id)
    {
        $campaign = $this->campaignService->delete($id);
        if (!$campaign) {
            return $this->error('کمپین یافت نشد', 404);
        }
        return $this->success(null, 'کمپین با موفقیت حذف شد');
    }

    public function overallStats()
    {
        $stats = $this->campaignService->getOverallStats();
        return $this->success($stats);
    }
}
