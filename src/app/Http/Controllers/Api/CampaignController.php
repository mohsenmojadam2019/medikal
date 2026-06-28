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

    public function index(Request $request)
    {
        $campaigns = $this->campaignService->getCampaigns($request->all(), $request->get('per_page', 20));
        return $this->success($campaigns);
    }

    public function active()
    {
        $campaigns = $this->campaignService->getActiveCampaigns();
        return $this->success($campaigns);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:health,awareness,screening,vaccination',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'target_audience' => 'nullable|string|max:255',
            'target_count' => 'nullable|integer|min:1',
            'channels' => 'nullable|array',
            'content' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $campaign = $this->campaignService->createCampaign($request->all());
            return $this->success($campaign, 'کمپین با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function show($id)
    {
        try {
            $campaign = Campaign::findOrFail($id);
            return $this->success($campaign);
        } catch (\Exception $e) {
            return $this->error('کمپین یافت نشد', 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $campaign = Campaign::findOrFail($id);
        } catch (\Exception $e) {
            return $this->error('کمپین یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:health,awareness,screening,vaccination',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'target_audience' => 'nullable|string|max:255',
            'target_count' => 'nullable|integer|min:1',
            'channels' => 'nullable|array',
            'content' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $campaign = $this->campaignService->updateCampaign($campaign, $request->all());
            return $this->success($campaign, 'کمپین با موفقیت بروزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function destroy($id)
    {
        try {
            $campaign = Campaign::findOrFail($id);
            $this->campaignService->deleteCampaign($campaign);
            return $this->success(null, 'کمپین با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function activate($id)
    {
        try {
            $campaign = Campaign::findOrFail($id);
            $campaign = $this->campaignService->activateCampaign($campaign);
            return $this->success($campaign, 'کمپین با موفقیت فعال شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function pause($id)
    {
        try {
            $campaign = Campaign::findOrFail($id);
            $campaign = $this->campaignService->pauseCampaign($campaign);
            return $this->success($campaign, 'کمپین با موفقیت متوقف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function complete($id)
    {
        try {
            $campaign = Campaign::findOrFail($id);
            $campaign = $this->campaignService->completeCampaign($campaign);
            return $this->success($campaign, 'کمپین با موفقیت پایان یافت');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function trackInteraction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'campaign_id' => 'required|exists:campaigns,id',
            'channel' => 'required|in:sms,email,push,social',
            'action' => 'required|in:sent,opened,clicked,converted,bounced',
            'patient_id' => 'nullable|exists:patients,id',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $interaction = $this->campaignService->trackInteraction($request->all());
            return $this->success($interaction, 'تعامل با موفقیت ثبت شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function interactions(Request $request, $campaignId)
    {
        $interactions = $this->campaignService->getCampaignInteractions($campaignId, $request->all(), $request->get('per_page', 20));
        return $this->success($interactions);
    }

    public function stats($id)
    {
        try {
            $stats = $this->campaignService->getCampaignStats($id);
            return $this->success($stats);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    public function overallStats()
    {
        $stats = $this->campaignService->getOverallStats();
        return $this->success($stats);
    }
}
