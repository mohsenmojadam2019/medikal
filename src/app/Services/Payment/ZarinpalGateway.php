<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Shetabit\Payment\Facade\Payment;
use Shetabit\Multipay\Invoice;

class ZarinpalGateway extends BaseGateway
{
    protected function getGatewayName(): string
    {
        return 'zarinpal';
    }

    public function initiate($order, array $options = []): array
    {
        $this->order = $order;

        $invoice = (new Invoice)->amount((int) $order->total);

        if ($order->user) {
            if ($order->user->email) {
                $invoice->detail('email', $order->user->email);
            }
            if ($order->user->phone) {
                $invoice->detail('mobile', $order->user->phone);
            }
        }

        $invoice->detail('metadata', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);

        $callbackUrl = $this->getCallbackUrl();

        try {
            $payment = Payment::via($this->getGatewayName())
                ->callbackUrl($callbackUrl)
                ->purchase($invoice, function($driver, $transactionId) use ($order) {
                    $this->storeTransaction($order, $transactionId, [
                        'authority' => $transactionId,
                    ]);
                });

            $result = $payment->pay();

            // ✅ اصلاح اینجا: بررسی نوع result
            $redirectUrl = null;

            if (method_exists($result, 'getActionUrl')) {
                $redirectUrl = $result->getActionUrl();
            } elseif (method_exists($result, 'getAction')) {
                $redirectUrl = $result->getAction();
            } elseif (is_string($result)) {
                $redirectUrl = $result;
            } elseif (is_array($result) && isset($result['url'])) {
                $redirectUrl = $result['url'];
            } else {
                // ساخت دستی URL زرین‌پال
                $transaction = Transaction::where('order_id', $order->id)
                    ->where('payment', 'zarinpal')
                    ->where('status', 'pending')
                    ->latest()
                    ->first();

                if ($transaction && $transaction->transaction_id) {
                    $mode = config('payment.drivers.zarinpal.mode', 'sandbox');
                    if ($mode === 'sandbox') {
                        $redirectUrl = 'https://sandbox.zarinpal.com/pg/StartPay/' . $transaction->transaction_id;
                    } else {
                        $redirectUrl = 'https://www.zarinpal.com/pg/StartPay/' . $transaction->transaction_id;
                    }
                }
            }

            if (!$redirectUrl) {
                throw new \Exception('آدرس درگاه پرداخت یافت نشد');
            }

            return [
                'success' => true,
                'redirect_url' => $redirectUrl,
                'message' => 'در حال انتقال به درگاه زرین‌پال...',
            ];

        } catch (\Exception $e) {
            \Log::error('Zarinpal initiation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در اتصال به درگاه زرین‌پال: ' . $e->getMessage(),
            ];
        }
    }

    public function verify(Request $request): array
    {
        $authority = $request->input('Authority');

        if (!$authority) {
            return [
                'success' => false,
                'message' => 'پارامتر Authority یافت نشد'
            ];
        }

        $transaction = Transaction::where('transaction_id', $authority)->first();

        if (!$transaction) {
            \Log::warning('Transaction not found in Zarinpal verify', ['authority' => $authority]);
            return [
                'success' => false,
                'message' => 'تراکنش یافت نشد',
                'authority' => $authority
            ];
        }

        $order = $transaction->order;

        if (!$order) {
            return [
                'success' => false,
                'message' => 'سفارش مربوطه یافت نشد'
            ];
        }

        try {
            $receipt = Payment::via($this->getGatewayName())
                ->amount((int) $order->total)
                ->transactionId($authority)
                ->verify();

            $referenceId = $receipt->getReferenceId();

            $transaction->update([
                'status' => 'completed',
                'reference_id' => $referenceId,
                'message' => 'پرداخت با موفقیت انجام شد',
            ]);

            \Log::info('Zarinpal payment successful', [
                'order_id' => $order->id,
                'authority' => $authority,
                'reference_id' => $referenceId
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

            \Log::error('Zarinpal verification failed', [
                'order_id' => $order->id,
                'authority' => $authority,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'order' => $order,
                'transaction' => $transaction,
            ];
        }
    }
}
