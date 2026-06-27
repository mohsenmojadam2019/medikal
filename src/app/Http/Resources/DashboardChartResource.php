<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardChartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'appointments_trend' => [
                'labels' => $this['appointments_trend']['labels'] ?? [],
                'datasets' => [
                    [
                        'label' => 'نوبت‌ها',
                        'data' => $this['appointments_trend']['data'] ?? [],
                        'backgroundColor' => '#4F46E5',
                    ],
                    [
                        'label' => 'تکمیل شده',
                        'data' => $this['appointments_trend']['completed'] ?? [],
                        'backgroundColor' => '#22C55E',
                    ],
                    [
                        'label' => 'لغو شده',
                        'data' => $this['appointments_trend']['cancelled'] ?? [],
                        'backgroundColor' => '#EF4444',
                    ],
                ],
            ],
            'revenue_trend' => [
                'labels' => $this['revenue_trend']['labels'] ?? [],
                'datasets' => [
                    [
                        'label' => 'درآمد',
                        'data' => $this['revenue_trend']['data'] ?? [],
                        'backgroundColor' => '#10B981',
                    ],
                ],
            ],
            'appointment_status_distribution' => [
                'labels' => $this['appointment_status_distribution']['labels'] ?? [],
                'data' => $this['appointment_status_distribution']['data'] ?? [],
                'colors' => $this['appointment_status_distribution']['colors'] ?? [],
            ],
            'top_doctors' => $this['top_doctors'] ?? [],
            'recent_activities' => $this['recent_activities'] ?? [],
            'patient_growth' => [
                'labels' => $this['patient_growth']['labels'] ?? [],
                'data' => $this['patient_growth']['data'] ?? [],
            ],
        ];
    }
}
