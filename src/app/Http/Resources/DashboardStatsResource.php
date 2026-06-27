<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'appointments' => [
                'total' => $this['appointments']['total'] ?? 0,
                'today' => $this['appointments']['today'] ?? 0,
                'this_week' => $this['appointments']['this_week'] ?? 0,
                'this_month' => $this['appointments']['this_month'] ?? 0,
                'pending' => $this['appointments']['pending'] ?? 0,
                'confirmed' => $this['appointments']['confirmed'] ?? 0,
                'completed' => $this['appointments']['completed'] ?? 0,
                'cancelled' => $this['appointments']['cancelled'] ?? 0,
                'no_show' => $this['appointments']['no_show'] ?? 0,
                'upcoming' => $this['appointments']['upcoming'] ?? 0,
            ],
            'patients' => [
                'total' => $this['patients']['total'] ?? 0,
                'active' => $this['patients']['active'] ?? 0,
                'new_today' => $this['patients']['new_today'] ?? 0,
                'new_this_week' => $this['patients']['new_this_week'] ?? 0,
                'new_this_month' => $this['patients']['new_this_month'] ?? 0,
                'verified' => $this['patients']['verified'] ?? 0,
            ],
            'doctors' => [
                'total' => $this['doctors']['total'] ?? 0,
                'active' => $this['doctors']['active'] ?? 0,
                'available' => $this['doctors']['available'] ?? 0,
                'verified' => $this['doctors']['verified'] ?? 0,
                'on_leave' => $this['doctors']['on_leave'] ?? 0,
            ],
            'revenue' => [
                'today' => $this['revenue']['today'] ?? 0,
                'this_week' => $this['revenue']['this_week'] ?? 0,
                'this_month' => $this['revenue']['this_month'] ?? 0,
                'this_year' => $this['revenue']['this_year'] ?? 0,
                'total' => $this['revenue']['total'] ?? 0,
                'pending' => $this['revenue']['pending'] ?? 0,
                'overdue' => $this['revenue']['overdue'] ?? 0,
            ],
            'hospital' => [
                'total_admissions' => $this['hospital']['total_admissions'] ?? 0,
                'active_admissions' => $this['hospital']['active_admissions'] ?? 0,
                'discharged_today' => $this['hospital']['discharged_today'] ?? 0,
                'available_beds' => $this['hospital']['available_beds'] ?? 0,
                'occupancy_rate' => $this['hospital']['occupancy_rate'] ?? 0,
            ],
            'laboratory' => [
                'total_orders' => $this['laboratory']['total_orders'] ?? 0,
                'pending_orders' => $this['laboratory']['pending_orders'] ?? 0,
                'completed_orders' => $this['laboratory']['completed_orders'] ?? 0,
                'critical_results' => $this['laboratory']['critical_results'] ?? 0,
                'abnormal_results' => $this['laboratory']['abnormal_results'] ?? 0,
            ],
            'pharmacy' => [
                'total_orders' => $this['pharmacy']['total_orders'] ?? 0,
                'pending_orders' => $this['pharmacy']['pending_orders'] ?? 0,
                'completed_orders' => $this['pharmacy']['completed_orders'] ?? 0,
                'low_stock_drugs' => $this['pharmacy']['low_stock_drugs'] ?? 0,
            ],
            'prescriptions' => [
                'total' => $this['prescriptions']['total'] ?? 0,
                'active' => $this['prescriptions']['active'] ?? 0,
                'expiring_soon' => $this['prescriptions']['expiring_soon'] ?? 0,
                'expired' => $this['prescriptions']['expired'] ?? 0,
            ],
            'wallet' => [
                'total_balance' => $this['wallet']['total_balance'] ?? 0,
                'total_transactions' => $this['wallet']['total_transactions'] ?? 0,
                'today_transactions' => $this['wallet']['today_transactions'] ?? 0,
                'active_wallets' => $this['wallet']['active_wallets'] ?? 0,
            ],
            'ratings' => [
                'average' => $this['ratings']['average'] ?? 0,
                'total' => $this['ratings']['total'] ?? 0,
                'today' => $this['ratings']['today'] ?? 0,
                'without_reply' => $this['ratings']['without_reply'] ?? 0,
            ],
            'alerts' => [
                'critical' => $this['alerts']['critical'] ?? 0,
                'unread' => $this['alerts']['unread'] ?? 0,
            ],
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}
