<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Shetabit\Payment\Facade\Payment;

class LocalGateway extends BaseGateway
{
    protected function getGatewayName(): string
    {
        return 'local';
    }

    public function initiate($order, array $options = []): array
    {
        $this->order = $order;

        $transactionId = 'LOCAL_' . $order->id . '_' . time();

        $this->storeTransaction($order, $transactionId);

        $callbackUrl = $this->getCallbackUrl();

        return [
            'success' => true,
            'form' => [
                'action' => $callbackUrl,
                'method' => 'POST',
                'inputs' => [
                    'transactionId' => $transactionId,
                    'order_id' => $order->id,
                    'title' => 'درگاه پرداخت تست',
                    'description' => 'این درگاه *صرفا* برای تست صحت روند پرداخت',
                    'orderLabel' => 'شماره سفارش',
                    'amountLabel' => 'مبلغ قابل پرداخت',
                    'payButton' => 'پرداخت موفق',
                    'cancelButton' => 'پرداخت ناموفق',
                ],
            ],
        ];
    }

    public function verify(Request $request): array
    {
        $transactionId = $request->input('transactionId');
        $orderId = $request->input('order_id');
        $cancel = $request->input('cancel');

        Log::info('LocalGateway verify called', [
            'transactionId' => $transactionId,
            'orderId' => $orderId,
            'cancel' => $cancel,
            'all_inputs' => $request->all()
        ]);

        if ($cancel) {
            return [
                'success' => false,
                'message' => 'پرداخت لغو شد',
                'cancelled' => true,
            ];
        }

        if (!$transactionId) {
            return [
                'success' => false,
                'message' => 'شناسه تراکنش یافت نشد',
            ];
        }

        $transaction = Transaction::where('transaction_id', $transactionId)->first();

        if (!$transaction) {
            Log::warning('Transaction not found', ['transaction_id' => $transactionId]);
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

        $referenceId = 'LOCAL_REF_' . $transactionId . '_' . time();

        $transaction->update([
            'status' => 'completed',
            'reference_id' => $referenceId,
            'message' => 'پرداخت تست با موفقیت انجام شد',
        ]);

        Log::info('LocalGateway payment successful', [
            'order_id' => $order->id,
            'reference_id' => $referenceId,
            'transaction_id' => $transactionId
        ]);

        return [
            'success' => true,
            'reference_id' => $referenceId,
            'order' => $order,
            'transaction' => $transaction,
            'message' => 'پرداخت با موفقیت انجام شد',
        ];
    }
}
