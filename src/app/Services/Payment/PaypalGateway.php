<?php
// app/Services/Payment/PaypalGateway.php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Shetabit\Payment\Facade\Payment;
use Shetabit\Multipay\Invoice;

class PaypalGateway extends BaseGateway
{
    protected function getGatewayName(): string
    {
        return 'paypal';
    }

    public function initiate($order, array $options = []): array
    {
        $this->order = $order;

        // تبدیل مبلغ به دلار
        $usdRate = app(\App\Services\Price\PriceManager::class)->getUsdPrice();
        $usdAmount = (int) round($order->total / $usdRate);

        $invoice = (new Invoice)->amount($usdAmount);

        if ($order->user) {
            if ($order->user->email) {
                $invoice->detail('email', $order->user->email);
            }
        }

        $invoice->detail('metadata', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'original_amount_rial' => $order->total,
        ]);

        $callbackUrl = $this->getCallbackUrl();

        try {
            $payment = Payment::via($this->getGatewayName())
                ->callbackUrl($callbackUrl)
                ->purchase($invoice, function($driver, $transactionId) use ($order) {
                    $this->storeTransaction($order, $transactionId, [
                        'paypal_payer_id' => null,
                        'amount_usd' => $invoice->getAmount(),
                    ]);
                });

            $result = $payment->pay();

            // PayPal یک redirect URL برمی‌گرداند
            $redirectUrl = null;
            if (method_exists($result, 'getActionUrl')) {
                $redirectUrl = $result->getActionUrl();
            } elseif (is_string($result)) {
                $redirectUrl = $result;
            }

            if (!$redirectUrl) {
                throw new \Exception('PayPal redirect URL not found');
            }

            return [
                'success' => true,
                'redirect_url' => $redirectUrl,
                'message' => 'در حال انتقال به درگاه پی‌پال...',
                'amount_usd' => $usdAmount,
                'exchange_rate' => $usdRate,
            ];

        } catch (\Exception $e) {
            \Log::error('PayPal initiation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در اتصال به درگاه پی‌پال: ' . $e->getMessage(),
            ];
        }
    }

    public function verify(Request $request): array
    {
        $paymentId = $request->input('paymentId');
        $payerId = $request->input('PayerID');
        $token = $request->input('token');

        \Log::info('PayPal verify', [
            'paymentId' => $paymentId,
            'PayerID' => $payerId,
            'token' => $token
        ]);

        if (!$paymentId || !$payerId) {
            return [
                'success' => false,
                'message' => 'پارامترهای پرداخت یافت نشد'
            ];
        }

        // پیدا کردن تراکنش
        $transaction = Transaction::where('transaction_id', $paymentId)
            ->orWhere('transaction_id', $token)
            ->first();

        if (!$transaction) {
            return [
                'success' => false,
                'message' => 'تراکنش یافت نشد'
            ];
        }

        $order = $transaction->order;

        if (!$order) {
            return [
                'success' => false,
                'message' => 'سفارش یافت نشد'
            ];
        }

        try {
            $receipt = Payment::via($this->getGatewayName())
                ->amount($transaction->amount)
                ->transactionId($paymentId)
                ->verify();

            $referenceId = $receipt->getReferenceId();

            $transaction->update([
                'status' => 'completed',
                'reference_id' => $referenceId,
                'message' => 'پرداخت با موفقیت انجام شد',
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'paypal_payer_id' => $payerId,
                    'paypal_payment_id' => $paymentId,
                ])
            ]);

            return [
                'success' => true,
                'reference_id' => $referenceId,
                'order' => $order,
                'transaction' => $transaction,
                'message' => 'پرداخت با موفقیت انجام شد',
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
