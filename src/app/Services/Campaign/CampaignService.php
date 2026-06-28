<?php

namespace App\Services\Campaign;

use App\Models\Campaign;
use App\Models\CampaignInteraction;

class CampaignService
{
    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id');
    }

    public function getCampaigns(array $filters = [], int $perPage = 20)
    {
        $query = Campaign::where('tenant_id', $this->tenantId);

        if (isset($filters['search'])) {
            $query->where('name', 'LIKE', "%{$filters['search']}%")
                ->orWhere('description', 'LIKE', "%{$filters['search']}%");
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['from_date'])) {
            $query->where('start_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('end_date', '<=', $filters['to_date']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getActiveCampaigns()
    {
        return Campaign::where('tenant_id', $this->tenantId)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->get();
    }

    public function createCampaign(array $data): Campaign
    {
        $data['tenant_id'] = $this->tenantId;
        return Campaign::create($data);
    }

    public function updateCampaign(Campaign $campaign, array $data): Campaign
    {
        $campaign->update($data);
        return $campaign->fresh();
    }

    public function deleteCampaign(Campaign $campaign): void
    {
        $campaign->delete();
    }

    public function activateCampaign(Campaign $campaign): Campaign
    {
        $campaign->update([
            'status' => 'active',
            'start_date' => $campaign->start_date ?? now(),
        ]);
        return $campaign->fresh();
    }

    public function pauseCampaign(Campaign $campaign): Campaign
    {
        $campaign->update(['status' => 'paused']);
        return $campaign->fresh();
    }

    public function completeCampaign(Campaign $campaign): Campaign
    {
        $campaign->update([
            'status' => 'completed',
            'end_date' => $campaign->end_date ?? now(),
        ]);
        return $campaign->fresh();
    }

    public function trackInteraction(array $data): CampaignInteraction
    {
        $campaign = Campaign::where('tenant_id', $this->tenantId)
            ->findOrFail($data['campaign_id']);

        if ($data['action'] === 'converted' && isset($data['patient_id'])) {
            $campaign->increment('current_count');
        }

        $data['tenant_id'] = $this->tenantId;
        return CampaignInteraction::create($data);
    }

    public function getCampaignInteractions(int $campaignId, array $filters = [], int $perPage = 20)
    {
        $query = CampaignInteraction::where('tenant_id', $this->tenantId)
            ->where('campaign_id', $campaignId);

        if (isset($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (isset($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
        }

        return $query->orderBy('occurred_at', 'desc')->paginate($perPage);
    }

    public function getCampaignStats(int $campaignId): array
    {
        $campaign = Campaign::where('tenant_id', $this->tenantId)
            ->with(['interactions'])
            ->findOrFail($campaignId);

        return [
            'campaign' => $campaign,
            'total_interactions' => $campaign->interactions->count(),
            'by_channel' => $campaign->interactions->groupBy('channel')->map->count(),
            'by_action' => $campaign->interactions->groupBy('action')->map->count(),
            'conversion_rate' => $campaign->target_count ?
                round(($campaign->current_count / $campaign->target_count) * 100, 1) :
                0,
            'progress' => $campaign->progress,
        ];
    }

    public function getOverallStats(): array
    {
        return [
            'total_campaigns' => Campaign::where('tenant_id', $this->tenantId)->count(),
            'active_campaigns' => Campaign::where('tenant_id', $this->tenantId)->where('status', 'active')->count(),
            'completed_campaigns' => Campaign::where('tenant_id', $this->tenantId)->where('status', 'completed')->count(),
            'total_interactions' => CampaignInteraction::where('tenant_id', $this->tenantId)->count(),
            'total_conversions' => CampaignInteraction::where('tenant_id', $this->tenantId)->where('action', 'converted')->count(),
            'average_conversion_rate' => $this->calculateAverageConversionRate(),
            'by_type' => $this->getStatsByType(),
        ];
    }

    private function calculateAverageConversionRate(): float
    {
        $campaigns = Campaign::where('tenant_id', $this->tenantId)
            ->where('status', 'completed')
            ->get();

        if ($campaigns->isEmpty()) {
            return 0;
        }

        $rates = $campaigns->map(function ($campaign) {
            return $campaign->target_count ?
                ($campaign->current_count / $campaign->target_count) * 100 :
                0;
        });

        return round($rates->average(), 1);
    }

    private function getStatsByType(): array
    {
        return Campaign::where('tenant_id', $this->tenantId)
            ->selectRaw('type, count(*) as total, sum(current_count) as conversions')
            ->groupBy('type')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->type,
                    'total' => $item->total,
                    'conversions' => $item->conversions ?? 0,
                ];
            })
            ->toArray();
    }
}
