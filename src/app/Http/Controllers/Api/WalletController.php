<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Wallet\WalletService;
use App\Traits\ApiResponse;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    use ApiResponse;

    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    // ========== USER METHODS ==========

    /**
     * دریافت موجودی کیف پول
     */
    public function balance()
    {
        $balance = $this->walletService->getBalance(auth()->id());
        return $this->success($balance);
    }

    /**
     * دریافت تاریخچه تراکنش‌ها
     */
    public function transactions(Request $request)
    {
        $transactions = $this->walletService->getTransactions(
            auth()->id(),
            $request->get('per_page', 20)
        );
        return $this->success($transactions);
    }

    /**
     * دریافت خلاصه تراکنش‌ها
     */
    public function summary()
    {
        $summary = $this->walletService->getTransactionsSummary(auth()->id());
        return $this->success($summary);
    }

    /**
     * شارژ کیف پول (شروع پرداخت)
     */
    public function deposit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1000',
            'gateway' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            // ایجاد تراکنش در انتظار
            $wallet = $this->walletService->getWallet(auth()->id());
            
            // در اینجا می‌تونی به درگاه پرداخت وصل بشی
            // فعلاً یک نمونه ساده
            
            $transaction = \App\Models\WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => auth()->id(),
                'transaction_id' => 'DEP-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(8)),
                'type' => 'deposit',
                'status' => 'pending',
                'amount' => $request->amount,
                'balance_before' => $wallet->balance,
                'balance_after' => $wallet->balance + $request->amount,
                'description' => 'شارژ کیف پول به مبلغ ' . number_format($request->amount) . ' تومان',
                'metadata' => ['gateway' => $request->gateway ?? 'local'],
            ]);

            // بازگشت لینک پرداخت (درگاه local برای تست)
            $paymentLink = route('wallet.payment.callback', ['transaction_id' => $transaction->transaction_id]);

            return $this->success([
                'transaction_id' => $transaction->transaction_id,
                'amount' => $request->amount,
                'payment_link' => $paymentLink,
                'message' => 'در حال انتقال به درگاه پرداخت...',
            ]);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تایید پرداخت شارژ کیف پول (Callback)
     */
    public function depositCallback(Request $request)
    {
        $transactionId = $request->input('transaction_id');
        $status = $request->input('status', 'success');

        $transaction = \App\Models\WalletTransaction::where('transaction_id', $transactionId)
            ->where('type', 'deposit')
            ->where('status', 'pending')
            ->first();

        if (!$transaction) {
            return $this->error('تراکنش یافت نشد', 404);
        }

        if ($status == 'success') {
            try {
                // تکمیل تراکنش
                $wallet = $transaction->wallet;
                
                // بروزرسانی موجودی
                $wallet->increment('balance', $transaction->amount);

                $transaction->update([
                    'status' => 'completed',
                    'balance_after' => $wallet->balance,
                    'completed_at' => now(),
                ]);

                return $this->success([
                    'transaction' => $transaction,
                    'new_balance' => $wallet->balance,
                ], 'کیف پول با موفقیت شارژ شد');

            } catch (\Exception $e) {
                $transaction->update(['status' => 'failed']);
                return $this->error($e->getMessage(), 400);
            }
        }

        $transaction->update(['status' => 'failed']);
        return $this->error('پرداخت ناموفق بود', 400);
    }

    /**
     * پرداخت هزینه نوبت با کیف پول
     */
    public function payAppointment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'appointment_id' => 'required|exists:appointments,id',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $result = $this->walletService->payAppointment(
                auth()->id(),
                $request->appointment_id
            );

            return $this->success($result, 'پرداخت با کیف پول با موفقیت انجام شد');

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ========== ADMIN METHODS ==========

    /**
     * لیست کیف‌پول‌ها (ادمین)
     */
    public function index(Request $request)
    {
        $wallets = $this->walletService->listWallets(
            $request->all(),
            $request->get('per_page', 20)
        );
        return $this->success($wallets);
    }

    /**
     * نمایش یک کیف پول (ادمین)
     */
    public function show($userId)
    {
        try {
            $wallet = $this->walletService->getWallet($userId);
            $transactions = $this->walletService->getTransactions($userId, 20);
            
            return $this->success([
                'wallet' => $wallet,
                'transactions' => $transactions,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * تغییر وضعیت کیف پول (ادمین)
     */
    public function toggleStatus($userId)
    {
        try {
            $wallet = $this->walletService->toggleStatus($userId);
            return $this->success($wallet, 'وضعیت کیف پول تغییر کرد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * اضافه کردن پاداش به کیف پول (ادمین)
     */
    public function addBonus(Request $request, $userId)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1000',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $transaction = $this->walletService->addBonus(
                $userId,
                $request->amount,
                $request->description
            );

            return $this->success($transaction, 'پاداش با موفقیت اضافه شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * آمار کیف‌پول‌ها (ادمین)
     */
    public function stats()
    {
        $stats = $this->walletService->getStats();
        return $this->success($stats);
    }
}
