<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Enums\PaymentStatusEnum;
use App\Enums\InvoiceStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LocalGateway extends BaseGateway
{
    protected function getGatewayName(): string
    {
        return 'local';
    }

    public function initiate(Invoice $invoice, array $options = []): array
    {
        $this->invoice = $invoice;

        $transactionId = 'LOCAL_' . $invoice->id . '_' . time();

        $this->storePayment($invoice, $transactionId);

        $callbackUrl = $this->getCallbackUrl();

        return [
            'success' => true,
            'gateway' => $this->getGatewayName(),
            'form' => [
                'action' => $callbackUrl,
                'method' => 'POST',
                'inputs' => [
                    'transactionId' => $transactionId,
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'title' => 'درگاه پرداخت تست',
                    'description' => 'این درگاه *صرفا* برای تست صحت روند پرداخت',
                    'amount' => $invoice->total_amount,
                    'payButton' => 'پرداخت موفق',
                    'cancelButton' => 'پرداخت ناموفق',
                ],
            ],
            'message' => 'در حال انتقال به درگاه تست...',
        ];
    }

    public function verify(Request $request): array
    {
        $transactionId = $request->input('transactionId');
        $invoiceId = $request->input('invoice_id');
        $cancel = $request->input('cancel');

        Log::info('LocalGateway verify called', [
            'transactionId' => $transactionId,
            'invoiceId' => $invoiceId,
            'cancel' => $cancel,
        ]);

        if ($cancel) {
            return [
                'success' => false,
                'message' => 'پرداخت لغو شد',
                'cancelled' => true,
                'gateway' => $this->getGatewayName(),
            ];
        }

        if (!$transactionId) {
            return [
                'success' => false,
                'message' => 'شناسه تراکنش یافت نشد',
                'gateway' => $this->getGatewayName(),
            ];
        }

        $payment = Payment::where('transaction_id', $transactionId)
            ->where('gateway', $this->getGatewayName())
            ->first();

        if (!$payment) {
            return [
                'success' => false,
                'message' => 'تراکنش یافت نشد',
                'gateway' => $this->getGatewayName(),
            ];
        }

        $invoice = $payment->invoice;

        if (!$invoice) {
            return [
                'success' => false,
                'message' => 'فاکتور یافت نشد',
                'gateway' => $this->getGatewayName(),
            ];
        }

        $referenceId = 'LOCAL_REF_' . $transactionId . '_' . time();

        $payment->update([
            'status' => PaymentStatusEnum::SUCCESS,
            'reference_code' => $referenceId,
            'message' => 'پرداخت تست با موفقیت انجام شد',
            'payment_date' => now(),
        ]);

        $invoice->markAsPaid();

        return [
            'success' => true,
            'reference_id' => $referenceId,
            'invoice' => $invoice,
            'payment' => $payment,
            'message' => 'پرداخت با موفقیت انجام شد',
            'gateway' => $this->getGatewayName(),
        ];
    }
}
