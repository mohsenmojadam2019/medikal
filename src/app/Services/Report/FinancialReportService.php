<?php

namespace App\Services\Report;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Doctor;
use Carbon\Carbon;

class FinancialReportService
{
    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id');
    }

    public function getDoctorIncome($doctorId, $fromDate = null, $toDate = null)
    {
        $query = Invoice::where('tenant_id', $this->tenantId)
            ->whereHas('appointment', function ($q) use ($doctorId) {
                $q->where('doctor_id', $doctorId);
            })
            ->where('status', 'paid');

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        $invoices = $query->get();
        $total = $invoices->sum('total_amount');

        return [
            'total_income' => $total,
            'count' => $invoices->count(),
            'invoices' => $invoices,
            'average' => $invoices->count() > 0 ? $total / $invoices->count() : 0,
        ];
    }

    public function getDailyIncome($doctorId, $days = 30)
    {
        $startDate = Carbon::now()->subDays($days);
        $endDate = Carbon::now();

        $invoices = Invoice::where('tenant_id', $this->tenantId)
            ->whereHas('appointment', function ($q) use ($doctorId) {
                $q->where('doctor_id', $doctorId);
            })
            ->where('status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->groupBy(function ($invoice) {
                return $invoice->created_at->format('Y-m-d');
            });

        $result = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dateKey = $current->format('Y-m-d');
            $dailyInvoices = $invoices->get($dateKey, collect());

            $result[] = [
                'date' => $dateKey,
                'income' => $dailyInvoices->sum('total_amount'),
                'count' => $dailyInvoices->count(),
            ];

            $current->addDay();
        }

        return $result;
    }

    public function getMonthlyIncome($doctorId, $months = 12)
    {
        $startDate = Carbon::now()->subMonths($months)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $invoices = Invoice::where('tenant_id', $this->tenantId)
            ->whereHas('appointment', function ($q) use ($doctorId) {
                $q->where('doctor_id', $doctorId);
            })
            ->where('status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->groupBy(function ($invoice) {
                return $invoice->created_at->format('Y-m');
            });

        $result = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $monthKey = $current->format('Y-m');
            $monthlyInvoices = $invoices->get($monthKey, collect());

            $result[] = [
                'month' => $monthKey,
                'income' => $monthlyInvoices->sum('total_amount'),
                'count' => $monthlyInvoices->count(),
            ];

            $current->addMonth();
        }

        return $result;
    }

    public function getCancelledAppointments($doctorId, $fromDate = null, $toDate = null)
    {
        $query = Appointment::where('tenant_id', $this->tenantId)
            ->where('doctor_id', $doctorId)
            ->where('status', 'cancelled');

        if ($fromDate) {
            $query->whereDate('date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('date', '<=', $toDate);
        }

        $appointments = $query->with(['patient.user'])->get();

        return [
            'total' => $appointments->count(),
            'appointments' => $appointments,
            'by_reason' => $appointments->groupBy('cancellation_reason')->map->count(),
        ];
    }
}
