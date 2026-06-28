<?php

namespace App\Services\Installment;

use App\Models\InstallmentSetting;
use App\Models\InstallmentContract;
use App\Models\Installment;
use App\Models\InstallmentPayment;
use App\Models\Patient;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InstallmentService
{
    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id');
    }

    public function getSettings(int $clinicId): InstallmentSetting
    {
        return InstallmentSetting::firstOrCreate(
            ['clinic_id' => $clinicId, 'tenant_id' => $this->tenantId],
            [
                'enable_installments' => true,
                'max_installments' => 12,
                'min_installment_amount' => 100000,
                'default_interest_rate' => 0,
                'default_penalty_rate' => 2,
                'grace_days' => 3,
                'available_gateways' => ['walleta', 'ezpay', 'toplend'],
                'require_down_payment' => false,
            ]
        );
    }

    public function updateSettings(int $clinicId, array $data): InstallmentSetting
    {
        $settings = $this->getSettings($clinicId);
        $settings->update($data);
        return $settings->fresh();
    }

    public function toggleInstallments(int $clinicId): InstallmentSetting
    {
        $settings = $this->getSettings($clinicId);
        $settings->toggle();
        return $settings->fresh();
    }

    public function isEnabled(int $clinicId): bool
    {
        return $this->getSettings($clinicId)->enable_installments;
    }

    public function createContract(array $data): InstallmentContract
    {
        return DB::transaction(function () use ($data) {
            $settings = $this->getSettings($data['clinic_id'] ?? 1);

            if (!$settings->enable_installments) {
                throw new \Exception('سیستم پرداخت اقساطی در حال حاضر غیرفعال است');
            }

            if ($data['number_of_installments'] > $settings->max_installments) {
                throw new \Exception("تعداد اقساط نمی‌تواند بیشتر از {$settings->max_installments} باشد");
            }

            $data['tenant_id'] = $this->tenantId;
            $contract = InstallmentContract::create([
                'tenant_id' => $this->tenantId,
                'patient_id' => $data['patient_id'],
                'appointment_id' => $data['appointment_id'] ?? null,
                'invoice_id' => $data['invoice_id'] ?? null,
                'total_amount' => $data['total_amount'],
                'down_payment' => $data['down_payment'] ?? 0,
                'installment_amount' => $this->calculateInstallmentAmount($data, $settings),
                'number_of_installments' => $data['number_of_installments'],
                'interest_rate' => $data['interest_rate'] ?? $settings->default_interest_rate,
                'total_interest' => $this->calculateTotalInterest($data, $settings),
                'penalty_rate' => $data['penalty_rate'] ?? $settings->default_penalty_rate,
                'gateway' => $data['gateway'] ?? null,
                'status' => 'pending',
                'start_date' => $data['start_date'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            $this->generateInstallments($contract);

            return $contract->load(['patient', 'installments']);
        });
    }

    public function generateInstallments(InstallmentContract $contract): void
    {
        $installmentAmount = $contract->installment_amount;
        $startDate = Carbon::parse($contract->start_date);

        $installments = [];
        for ($i = 1; $i <= $contract->number_of_installments; $i++) {
            $installments[] = [
                'tenant_id' => $this->tenantId,
                'contract_id' => $contract->id,
                'installment_number' => $i,
                'amount' => $installmentAmount,
                'due_date' => $startDate->copy()->addMonths($i),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Installment::insert($installments);
    }

    public function getContracts(array $filters = [], int $perPage = 20)
    {
        $query = InstallmentContract::where('tenant_id', $this->tenantId)
            ->with(['patient', 'installments']);

        if (isset($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_active'])) {
            $query->whereIn('status', ['active', 'pending']);
        }

        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getContract(int $id): InstallmentContract
    {
        return InstallmentContract::where('tenant_id', $this->tenantId)
            ->with(['patient', 'installments', 'appointment', 'invoice'])
            ->findOrFail($id);
    }

    public function getPatientContracts(int $patientId, array $filters = [], int $perPage = 15)
    {
        $filters['patient_id'] = $patientId;
        return $this->getContracts($filters, $perPage);
    }

    public function activateContract(int $id): InstallmentContract
    {
        $contract = InstallmentContract::where('tenant_id', $this->tenantId)->findOrFail($id);

        if ($contract->status !== 'pending') {
            throw new \Exception('فقط قراردادهای در انتظار قابل تایید هستند');
        }

        $contract->update(['status' => 'active']);
        return $contract->fresh();
    }

    public function completeContract(int $id): InstallmentContract
    {
        $contract = InstallmentContract::where('tenant_id', $this->tenantId)->findOrFail($id);
        $contract->update([
            'status' => 'completed',
            'end_date' => now(),
        ]);
        return $contract->fresh();
    }

    public function cancelContract(int $id): InstallmentContract
    {
        $contract = InstallmentContract::where('tenant_id', $this->tenantId)->findOrFail($id);

        if (!$contract->canBeCancelled()) {
            throw new \Exception('این قرارداد قابل لغو نیست');
        }

        $contract->update(['status' => 'cancelled']);
        return $contract->fresh();
    }

    public function markAsDefaulted(int $id): InstallmentContract
    {
        $contract = InstallmentContract::where('tenant_id', $this->tenantId)->findOrFail($id);
        $contract->update(['status' => 'defaulted']);
        return $contract->fresh();
    }

    public function getInstallments(array $filters = [], int $perPage = 20)
    {
        $query = Installment::where('tenant_id', $this->tenantId)
            ->with(['contract', 'contract.patient']);

        if (isset($filters['contract_id'])) {
            $query->where('contract_id', $filters['contract_id']);
        }

        if (isset($filters['patient_id'])) {
            $query->whereHas('contract', function ($q) use ($filters) {
                $q->where('patient_id', $filters['patient_id']);
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['due_soon'])) {
            $query->dueSoon($filters['due_soon']);
        }

        return $query->orderBy('due_date', 'asc')->paginate($perPage);
    }

    public function getPatientInstallments(int $patientId, array $filters = [], int $perPage = 20)
    {
        $filters['patient_id'] = $patientId;
        return $this->getInstallments($filters, $perPage);
    }

    public function getUpcomingInstallments(int $patientId, int $days = 7)
    {
        return Installment::where('tenant_id', $this->tenantId)
            ->whereHas('contract', function ($q) use ($patientId) {
                $q->where('patient_id', $patientId)
                    ->whereIn('status', ['active', 'pending']);
            })
            ->where('status', 'pending')
            ->whereBetween('due_date', [now(), now()->addDays($days)])
            ->with(['contract'])
            ->orderBy('due_date')
            ->get();
    }

    public function getOverdueInstallments(int $patientId)
    {
        return Installment::where('tenant_id', $this->tenantId)
            ->whereHas('contract', function ($q) use ($patientId) {
                $q->where('patient_id', $patientId)
                    ->whereIn('status', ['active', 'pending']);
            })
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->with(['contract'])
            ->orderBy('due_date')
            ->get();
    }

    public function payInstallment(int $installmentId, array $paymentData): Installment
    {
        return DB::transaction(function () use ($installmentId, $paymentData) {
            $installment = Installment::where('tenant_id', $this->tenantId)->findOrFail($installmentId);
            $contract = $installment->contract;

            if ($installment->status === 'paid') {
                throw new \Exception('این قسط قبلاً پرداخت شده است');
            }

            $penalty = $installment->calculatePenalty();
            $totalPayable = $installment->amount + $penalty;

            $installmentPayment = InstallmentPayment::create([
                'tenant_id' => $this->tenantId,
                'installment_id' => $installment->id,
                'payment_id' => $paymentData['payment_id'] ?? null,
                'amount' => $installment->amount,
                'penalty' => $penalty,
                'total_paid' => $totalPayable,
                'payment_method' => $paymentData['payment_method'] ?? 'online',
                'reference_code' => $paymentData['reference'] ?? null,
                'metadata' => $paymentData['metadata'] ?? null,
            ]);

            $installment->markAsPaid([
                'amount' => $installment->amount,
                'penalty' => $penalty,
                'reference' => $paymentData['reference'] ?? null,
                'metadata' => $paymentData['metadata'] ?? null,
            ]);

            $contract->increment('installments_paid');

            if ($contract->installments_paid >= $contract->number_of_installments) {
                $this->completeContract($contract->id);
            }

            return $installment->fresh();
        });
    }

    public function waiveInstallment(int $installmentId): Installment
    {
        $installment = Installment::where('tenant_id', $this->tenantId)->findOrFail($installmentId);

        if ($installment->status === 'paid') {
            throw new \Exception('این قسط قبلاً پرداخت شده است');
        }

        $installment->waive();
        return $installment->fresh();
    }

    public function calculateInstallmentAmount(array $data, ?InstallmentSetting $settings = null): float
    {
        $settings = $settings ?? $this->getSettings($data['clinic_id'] ?? 1);

        $total = $data['total_amount'] - ($data['down_payment'] ?? 0);
        $interestRate = $data['interest_rate'] ?? $settings->default_interest_rate;
        $interest = $total * ($interestRate / 100);
        $totalWithInterest = $total + $interest;

        return round($totalWithInterest / $data['number_of_installments'], 2);
    }

    public function calculateTotalInterest(array $data, ?InstallmentSetting $settings = null): float
    {
        $settings = $settings ?? $this->getSettings($data['clinic_id'] ?? 1);

        $total = $data['total_amount'] - ($data['down_payment'] ?? 0);
        $interestRate = $data['interest_rate'] ?? $settings->default_interest_rate;

        return round($total * ($interestRate / 100), 2);
    }

    public function calculatePenalty(Installment $installment): float
    {
        return $installment->calculatePenalty();
    }

    public function getContractSummary(int $contractId): array
    {
        $contract = $this->getContract($contractId);

        return [
            'contract' => $contract,
            'total_paid' => $contract->total_paid,
            'total_penalty' => $contract->total_penalty,
            'remaining' => $contract->remaining_amount,
            'progress' => $contract->progress,
            'next_due' => $contract->next_due,
            'overdue_count' => $contract->overdueInstallments()->count(),
            'total_installments' => $contract->number_of_installments,
            'paid_installments' => $contract->installments_paid,
            'pending_installments' => $contract->number_of_installments - $contract->installments_paid,
        ];
    }

    public function getStats(int $clinicId): array
    {
        $query = InstallmentContract::where('tenant_id', $this->tenantId)
            ->whereHas('appointment', function ($q) use ($clinicId) {
                $q->whereHas('doctor', function ($q2) use ($clinicId) {
                    $q2->where('clinic_id', $clinicId);
                });
            });

        return [
            'total_contracts' => $query->count(),
            'active_contracts' => (clone $query)->where('status', 'active')->count(),
            'completed_contracts' => (clone $query)->where('status', 'completed')->count(),
            'defaulted_contracts' => (clone $query)->where('status', 'defaulted')->count(),
            'total_amount' => (clone $query)->sum('total_amount'),
            'total_paid' => (clone $query)->sum('installments_paid'),
            'total_overdue' => Installment::where('tenant_id', $this->tenantId)
                ->whereHas('contract', function ($q) use ($clinicId) {
                    $q->whereHas('appointment', function ($q2) use ($clinicId) {
                        $q2->whereHas('doctor', function ($q3) use ($clinicId) {
                            $q3->where('clinic_id', $clinicId);
                        });
                    });
                })
                ->where('status', 'pending')
                ->where('due_date', '<', now())
                ->count(),
        ];
    }
}
