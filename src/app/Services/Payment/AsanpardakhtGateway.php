<?php


namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Shetabit\Payment\Facade\Payment;
use Shetabit\Multipay\Invoice;

class AsanpardakhtGateway extends BaseGateway
{
    protected function getGatewayName(): string
    {
        return 'asanpardakht';
    }

    public function initiate($order, array $options = []): array
    {
        $this->order = $order;

        $invoice = (new Invoice)->amount((int)$order->total);

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
                ->purchase($invoice, function ($driver, $transactionId) use ($order) {
                    $this->storeTransaction($order, $transactionId, [
                        'ref_id' => $transactionId,
                    ]);
                });

            $result = $payment->pay();

            // آسان‌پرداخت فرم POST برمی‌گرداند
            return [
                'success' => true,
                'form' => [
                    'action' => $result->getAction(),
                    'method' => $result->getMethod(),
                    'inputs' => $result->getInputs(),
                ],
                'message' => 'در حال انتقال به درگاه آسان پرداخت...',
            ];

        } catch (\Exception $e) {
            \Log::error('Asanpardakht initiation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در اتصال به درگاه آسان پرداخت: ' . $e->getMessage(),
            ];
        }
    }

    public function verify(Request $request): array
    {
        // دریافت پارامترهای کال‌بک
        $refId = $request->input('RefId');
        $payGateTranId = $request->input('PayGateTranID');

        \Log::info('Asanpardakht verify', [
            'RefId' => $refId,
            'PayGateTranID' => $payGateTranId,
            'all' => $request->all()
        ]);

        if (!$refId && !$payGateTranId) {
            return [
                'success' => false,
                'message' => 'پارامترهای پرداخت یافت نشد'
            ];
        }

        // پیدا کردن تراکنش
        $transaction = Transaction::where('transaction_id', $refId)
            ->orWhere('reference_id', $payGateTranId)
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
            // تأیید پرداخت با پکیج
            $receipt = Payment::via($this->getGatewayName())
                ->amount((int)$order->total)
                ->transactionId($refId)
                ->verify();

            $referenceId = $receipt->getReferenceId();

            $transaction->update([
                'status' => 'completed',
                'reference_id' => $referenceId,
                'message' => 'پرداخت با موفقیت انجام شد',
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
                'transaction' => $transaction,
            ];
        }
    }
}
