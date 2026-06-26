<?php
// app/Services/Payment/StripeGateway.php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Stripe\Stripe as StripeClient;

class StripeGateway extends BaseGateway
{
    protected function getGatewayName(): string
    {
        return 'stripe';
    }

    public function initiate($order, array $options = []): array
    {
        $this->order = $order;

        // تبدیل مبلغ به دلار
        $usdRate = app(\App\Services\Price\PriceManager::class)->getUsdPrice();
        $usdAmount = (int) round($order->total / $usdRate);

        // تنظیم Stripe
        $config = \App\Models\PaymentSetting::where('gateway', 'stripe')->first();
        StripeClient::setApiKey($config->config['secretKey'] ?? env('STRIPE_SECRET_KEY'));

        $callbackUrl = $this->getCallbackUrl();

        try {
            $checkoutSession = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => "سفارش #{$order->order_number}",
                            'description' => "پرداخت سفارش از فروشگاه " . config('app.name'),
                        ],
                        'unit_amount' => $usdAmount * 100, // Stripe به سنت نیاز دارد
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $callbackUrl . '?session_id={CHECKOUT_SESSION_ID}&success=true',
                'cancel_url' => $callbackUrl . '?cancel=true',
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => $order->user_id,
                    'original_amount_rial' => $order->total,
                ],
            ]);

            // ذخیره تراکنش
            $this->storeTransaction($order, $checkoutSession->id, [
                'session_id' => $checkoutSession->id,
                'amount_usd' => $usdAmount,
                'payment_intent' => null,
            ]);

            return [
                'success' => true,
                'redirect_url' => $checkoutSession->url,
                'session_id' => $checkoutSession->id,
                'message' => 'در حال انتقال به درگاه استرایپ...',
                'amount_usd' => $usdAmount,
                'exchange_rate' => $usdRate,
                'publishable_key' => $config->config['publishableKey'] ?? env('STRIPE_PUBLISHABLE_KEY'),
            ];

        } catch (\Exception $e) {
            \Log::error('Stripe initiation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در اتصال به درگاه استرایپ: ' . $e->getMessage(),
            ];
        }
    }

    public function verify(Request $request): array
    {
        $sessionId = $request->input('session_id');
        $cancel = $request->input('cancel');

        if ($cancel) {
            return [
                'success' => false,
                'message' => 'پرداخت لغو شد',
                'cancelled' => true,
            ];
        }

        if (!$sessionId) {
            return [
                'success' => false,
                'message' => 'شناسه جلسه پرداخت یافت نشد',
            ];
        }

        $transaction = Transaction::where('transaction_id', $sessionId)->first();

        if (!$transaction) {
            return [
                'success' => false,
                'message' => 'تراکنش یافت نشد',
            ];
        }

        $order = $transaction->order;

        if (!$order) {
            return [
                'success' => false,
                'message' => 'سفارش یافت نشد',
            ];
        }

        try {
            $config = \App\Models\PaymentSetting::where('gateway', 'stripe')->first();
            StripeClient::setApiKey($config->config['secretKey'] ?? env('STRIPE_SECRET_KEY'));

            $session = Session::retrieve($sessionId);

            if ($session->payment_status === 'paid') {
                $transaction->update([
                    'status' => 'completed',
                    'reference_id' => $session->payment_intent,
                    'message' => 'پرداخت با موفقیت انجام شد',
                ]);

                return [
                    'success' => true,
                    'reference_id' => $session->payment_intent,
                    'order' => $order,
                    'transaction' => $transaction,
                    'message' => 'پرداخت با موفقیت انجام شد',
                ];
            }

            return [
                'success' => false,
                'message' => 'پرداخت انجام نشده است',
                'order' => $order,
            ];

        } catch (\Exception $e) {
            $transaction->update([
                'status' => 'failed',
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'order' => $order,
            ];
        }
    }
}
