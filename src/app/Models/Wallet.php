<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'balance',
        'frozen_balance',
        'currency',
        'is_active',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'frozen_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function getAvailableBalanceAttribute(): float
    {
        return $this->balance - $this->frozen_balance;
    }

    public function deposit(float $amount, string $description = null, array $metadata = []): WalletTransaction
    {
        return \DB::transaction(function () use ($amount, $description, $metadata) {
            $balanceBefore = $this->balance;
            $this->increment('balance', $amount);

            $transaction = WalletTransaction::create([
                'wallet_id' => $this->id,
                'user_id' => $this->user_id,
                'transaction_id' => $this->generateTransactionId(),
                'type' => 'deposit',
                'status' => 'completed',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $this->balance,
                'description' => $description ?? 'شارژ کیف پول',
                'metadata' => $metadata,
                'completed_at' => now(),
            ]);

            return $transaction;
        });
    }

    public function payment(float $amount, string $description = null, array $metadata = []): WalletTransaction
    {
        return \DB::transaction(function () use ($amount, $description, $metadata) {
            if ($this->available_balance < $amount) {
                throw new \Exception('موجودی کافی نیست');
            }

            $balanceBefore = $this->balance;
            $this->decrement('balance', $amount);

            $transaction = WalletTransaction::create([
                'wallet_id' => $this->id,
                'user_id' => $this->user_id,
                'transaction_id' => $this->generateTransactionId(),
                'type' => 'payment',
                'status' => 'completed',
                'amount' => -$amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $this->balance,
                'description' => $description ?? 'پرداخت با کیف پول',
                'metadata' => $metadata,
                'completed_at' => now(),
            ]);

            return $transaction;
        });
    }

    public function refund(float $amount, string $description = null, array $metadata = []): WalletTransaction
    {
        return \DB::transaction(function () use ($amount, $description, $metadata) {
            $balanceBefore = $this->balance;
            $this->increment('balance', $amount);

            $transaction = WalletTransaction::create([
                'wallet_id' => $this->id,
                'user_id' => $this->user_id,
                'transaction_id' => $this->generateTransactionId(),
                'type' => 'refund',
                'status' => 'completed',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $this->balance,
                'description' => $description ?? 'بازگشت وجه به کیف پول',
                'metadata' => $metadata,
                'completed_at' => now(),
            ]);

            return $transaction;
        });
    }

    public function withdraw(float $amount, string $description = null, array $metadata = []): WalletTransaction
    {
        return \DB::transaction(function () use ($amount, $description, $metadata) {
            if ($this->available_balance < $amount) {
                throw new \Exception('موجودی کافی نیست');
            }

            $balanceBefore = $this->balance;
            $this->decrement('balance', $amount);

            $transaction = WalletTransaction::create([
                'wallet_id' => $this->id,
                'user_id' => $this->user_id,
                'transaction_id' => $this->generateTransactionId(),
                'type' => 'withdraw',
                'status' => 'completed',
                'amount' => -$amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $this->balance,
                'description' => $description ?? 'برداشت از کیف پول',
                'metadata' => $metadata,
                'completed_at' => now(),
            ]);

            return $transaction;
        });
    }

    private function generateTransactionId(): string
    {
        return 'WLT-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(8));
    }

    protected static function booted()
    {
        static::creating(function ($wallet) {
            if (empty($wallet->currency)) {
                $wallet->currency = 'تومان';
            }
        });
    }
}
