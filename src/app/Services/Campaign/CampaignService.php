<?php

namespace App\Services\Campaign;

use App\Models\Campaign;
use App\Models\CampaignInteraction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampaignService
{
    public function getCampaigns(array $filters = [], int $perPage = 20)
    {
        $query = Campaign::query();

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
        return Campaign::where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->get();
    }

    public function createCampaign(array $data): Campaign
    {
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
        $campaign = Campaign::findOrFail($data['campaign_id']);

        if ($data['action'] === 'converted' && isset($data['patient_id'])) {
            $campaign->increment('current_count');
        }

        return CampaignInteraction::create([
            'campaign_id' => $data['campaign_id'],
            'patient_id' => $data['patient_id'] ?? null,
            'channel' => $data['channel'],
            'action' => $data['action'],
            'content' => $data['content'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'occurred_at' => now(),
        ]);
    }

    public function getCampaignInteractions(int $campaignId, array $filters = [], int $perPage = 20)
    {
        $query = CampaignInteraction::where('campaign_id', $campaignId);

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
        $campaign = Campaign::with(['interactions'])->findOrFail($campaignId);

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
            'total_campaigns' => Campaign::count(),
            'active_campaigns' => Campaign::where('status', 'active')->count(),
            'completed_campaigns' => Campaign::where('status', 'completed')->count(),
            'total_interactions' => CampaignInteraction::count(),
            'total_conversions' => CampaignInteraction::where('action', 'converted')->count(),
            'average_conversion_rate' => $this->calculateAverageConversionRate(),
            'by_type' => $this->getStatsByType(),
        ];
    }

    private function calculateAverageConversionRate(): float
    {
        $campaigns = Campaign::where('status', 'completed')->get();
        if ($campaigns->isEmpty()) return 0;

        $rates = $campaigns->map(function ($campaign) {
            return $campaign->target_count ?
                ($campaign->current_count / $campaign->target_count) * 100 :
                0;
        });

        return round($rates->average(), 1);
    }

    private function getStatsByType(): array
    {
        return Campaign::selectRaw('type, count(*) as total, sum(current_count) as conversions')
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
