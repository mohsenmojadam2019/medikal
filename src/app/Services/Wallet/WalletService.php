<?php

namespace App\Services\Wallet;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletService
{
    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id');
    }

    public function getWallet(int $userId): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $userId, 'tenant_id' => $this->tenantId],
            [
                'balance' => 0,
                'frozen_balance' => 0,
                'currency' => 'تومان',
                'is_active' => true,
            ]
        );
    }

    public function getBalance(int $userId): array
    {
        $wallet = $this->getWallet($userId);

        return [
            'total_balance' => $wallet->balance,
            'frozen_balance' => $wallet->frozen_balance,
            'available_balance' => $wallet->available_balance,
            'currency' => $wallet->currency,
            'is_active' => $wallet->is_active,
        ];
    }

    public function deposit(int $userId, float $amount, string $description = null, array $metadata = []): WalletTransaction
    {
        if ($amount <= 0) {
            throw new \Exception('مبلغ باید بیشتر از صفر باشد');
        }

        $wallet = $this->getWallet($userId);

        if (!$wallet->is_active) {
            throw new \Exception('کیف پول شما غیرفعال است');
        }

        return $wallet->deposit($amount, $description, $metadata);
    }

    public function withdraw(int $userId, float $amount, string $description = null, array $metadata = []): WalletTransaction
    {
        if ($amount <= 0) {
            throw new \Exception('مبلغ باید بیشتر از صفر باشد');
        }

        $wallet = $this->getWallet($userId);

        if (!$wallet->is_active) {
            throw new \Exception('کیف پول شما غیرفعال است');
        }

        return $wallet->withdraw($amount, $description, $metadata);
    }

    public function pay(int $userId, float $amount, string $description = null, array $metadata = []): WalletTransaction
    {
        if ($amount <= 0) {
            throw new \Exception('مبلغ باید بیشتر از صفر باشد');
        }

        $wallet = $this->getWallet($userId);

        if (!$wallet->is_active) {
            throw new \Exception('کیف پول شما غیرفعال است');
        }

        return $wallet->payment($amount, $description, $metadata);
    }

    public function refund(int $userId, float $amount, string $description = null, array $metadata = []): WalletTransaction
    {
        if ($amount <= 0) {
            throw new \Exception('مبلغ باید بیشتر از صفر باشد');
        }

        $wallet = $this->getWallet($userId);

        if (!$wallet->is_active) {
            throw new \Exception('کیف پول شما غیرفعال است');
        }

        return $wallet->refund($amount, $description, $metadata);
    }

    public function getTransactions(int $userId, int $perPage = 20)
    {
        return WalletTransaction::where('tenant_id', $this->tenantId)
            ->where('user_id', $userId)
            ->with(['appointment', 'invoice'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getTransactionsSummary(int $userId): array
    {
        $wallet = $this->getWallet($userId);

        return [
            'total_deposits' => WalletTransaction::where('tenant_id', $this->tenantId)
                ->where('user_id', $userId)
                ->where('type', 'deposit')
                ->where('status', 'completed')
                ->sum('amount'),
            'total_payments' => WalletTransaction::where('tenant_id', $this->tenantId)
                ->where('user_id', $userId)
                ->where('type', 'payment')
                ->where('status', 'completed')
                ->sum('amount') * -1,
            'total_refunds' => WalletTransaction::where('tenant_id', $this->tenantId)
                ->where('user_id', $userId)
                ->where('type', 'refund')
                ->where('status', 'completed')
                ->sum('amount'),
            'total_withdraws' => WalletTransaction::where('tenant_id', $this->tenantId)
                ->where('user_id', $userId)
                ->where('type', 'withdraw')
                ->where('status', 'completed')
                ->sum('amount') * -1,
            'current_balance' => $wallet->balance,
            'transaction_count' => WalletTransaction::where('tenant_id', $this->tenantId)
                ->where('user_id', $userId)
                ->count(),
        ];
    }

    public function payAppointment(int $userId, int $appointmentId): array
    {
        return DB::transaction(function () use ($userId, $appointmentId) {
            $appointment = Appointment::where('tenant_id', $this->tenantId)
                ->with(['doctor'])
                ->findOrFail($appointmentId);

            $wallet = $this->getWallet($userId);

            if ($appointment->patient->user_id != $userId) {
                throw new \Exception('شما دسترسی به این نوبت ندارید');
            }

            if ($appointment->payment_status == 'paid') {
                throw new \Exception('این نوبت قبلاً پرداخت شده است');
            }

            $amount = $appointment->final_price;

            if ($amount <= 0) {
                throw new \Exception('مبلغ نوبت صفر است');
            }

            $transaction = $this->pay(
                $userId,
                $amount,
                "پرداخت هزینه نوبت شماره {$appointment->code} - دکتر {$appointment->doctor->full_name}",
                ['appointment_id' => $appointment->id, 'type' => 'appointment_payment']
            );

            $appointment->update([
                'payment_status' => 'paid',
            ]);

            return [
                'transaction' => $transaction,
                'appointment' => $appointment->fresh(),
            ];
        });
    }

    public function toggleStatus(int $userId): Wallet
    {
        $wallet = $this->getWallet($userId);
        $wallet->update(['is_active' => !$wallet->is_active]);
        return $wallet->fresh();
    }

    public function addBonus(int $userId, float $amount, string $description = null): WalletTransaction
    {
        return $this->deposit($userId, $amount, $description ?? 'پاداش ویژه', ['type' => 'bonus']);
    }

    public function listWallets(array $filters = [], int $perPage = 20)
    {
        $query = Wallet::where('tenant_id', $this->tenantId)->with(['user']);

        if (isset($filters['search'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('name', 'LIKE', "%{$filters['search']}%")
                    ->orWhere('mobile', 'LIKE', "%{$filters['search']}%")
                    ->orWhere('email', 'LIKE', "%{$filters['search']}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['min_balance'])) {
            $query->where('balance', '>=', $filters['min_balance']);
        }

        if (isset($filters['max_balance'])) {
            $query->where('balance', '<=', $filters['max_balance']);
        }

        return $query->orderBy('balance', 'desc')->paginate($perPage);
    }

    public function getStats(): array
    {
        return [
            'total_wallets' => Wallet::where('tenant_id', $this->tenantId)->count(),
            'active_wallets' => Wallet::where('tenant_id', $this->tenantId)->where('is_active', true)->count(),
            'total_balance' => Wallet::where('tenant_id', $this->tenantId)->sum('balance'),
            'total_frozen' => Wallet::where('tenant_id', $this->tenantId)->sum('frozen_balance'),
            'total_available' => Wallet::where('tenant_id', $this->tenantId)->sum(DB::raw('balance - frozen_balance')),
            'today_transactions' => WalletTransaction::where('tenant_id', $this->tenantId)->whereDate('created_at', today())->count(),
            'today_volume' => WalletTransaction::where('tenant_id', $this->tenantId)->whereDate('created_at', today())->sum('amount'),
        ];
    }
}
