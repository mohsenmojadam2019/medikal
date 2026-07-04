<?php
// app/Http/Controllers/Admin/WalletController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Wallet\WalletService;
use App\Traits\ApiResponse;
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

    /**
     * لیست کیف پول‌ها
     */
    public function index(Request $request)
    {
        try {
            $wallets = $this->walletService->listWallets(
                $request->all(),
                $request->get('per_page', 20)
            );

            return $this->success($wallets);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * نمایش کیف پول کاربر
     */
    public function show($userId)
    {
        try {
            $wallet = $this->walletService->getWallet($userId);
            $transactions = $this->walletService->getTransactions($userId, 10);

            return $this->success([
                'wallet' => $wallet,
                'transactions' => $transactions,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * تغییر وضعیت کیف پول
     */
    public function toggleStatus($userId)
    {
        try {
            $wallet = $this->walletService->toggleStatus($userId);
            return $this->success($wallet, 'وضعیت کیف پول با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * افزودن پاداش به کیف پول
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
     * آمار کیف پول‌ها
     */
    public function stats()
    {
        try {
            $stats = $this->walletService->getStats();
            return $this->success($stats);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * تراکنش‌های کاربر
     */
    public function transactions(Request $request, $userId)
    {
        try {
            $transactions = $this->walletService->getTransactions(
                $userId,
                $request->get('per_page', 20)
            );

            return $this->success($transactions);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }
}
