<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Payment;
use App\Enums\PaymentStatusEnum;
use Illuminate\Http\Request;
use Shetabit\Payment\Facade\Payment;
use Shetabit\Multipay\Invoice as ShetabitInvoice;

class AsanpardakhtGateway extends BaseGateway
{
    protected function getGatewayName(): string
    {
        return 'asanpardakht';
    }

    public function initiate(Invoice $invoice, array $options = []): array
    {
        $this->invoice = $invoice;

        $shetabitInvoice = $this->createShetabitInvoice($invoice);
        $callbackUrl = $this->getCallbackUrl();

        try {
            $payment = Payment::via($this->getGatewayName())
                ->callbackUrl($callbackUrl)
                ->purchase($shetabitInvoice, function ($driver, $transactionId) use ($invoice) {
                    $this->storePayment($invoice, $transactionId, [
                        'ref_id' => $transactionId,
                    ]);
                });

            $result = $payment->pay();

            return [
                'success' => true,
                'gateway' => $this->getGatewayName(),
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'amount' => $invoice->total_amount,
                'form' => [
                    'action' => $result->getAction(),
                    'method' => $result->getMethod(),
                    'inputs' => $result->getInputs(),
                ],
                'message' => 'در حال انتقال به درگاه آسان پرداخت...',
            ];

        } catch (\Exception $e) {
            $this->logError('Asanpardakht initiation failed', [
                'error' => $e->getMessage(),
                'invoice_id' => $invoice->id,
            ]);

            return [
                'success' => false,
                'message' => 'خطا در اتصال به درگاه آسان پرداخت: ' . $e->getMessage(),
                'gateway' => $this->getGatewayName(),
            ];
        }
    }

    public function verify(Request $request): array
    {
        $refId = $request->input('RefId');
        $payGateTranId = $request->input('PayGateTranID');

        $this->logInfo('Asanpardakht verify', [
            'RefId' => $refId,
            'PayGateTranID' => $payGateTranId,
        ]);

        if (!$refId && !$payGateTranId) {
            return [
                'success' => false,
                'message' => 'پارامترهای پرداخت یافت نشد',
                'gateway' => $this->getGatewayName(),
            ];
        }

        $payment = Payment::where('transaction_id', $refId)
            ->orWhere('reference_code', $payGateTranId)
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

        try {
            $receipt = Payment::via($this->getGatewayName())
                ->amount((int) $invoice->total_amount)
                ->transactionId($refId)
                ->verify();

            $referenceId = $receipt->getReferenceId();

            $payment->update([
                'status' => PaymentStatusEnum::SUCCESS,
                'reference_code' => $referenceId,
                'message' => 'پرداخت با موفقیت انجام شد',
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

        } catch (\Exception $e) {
            $payment->update([
                'status' => PaymentStatusEnum::FAILED,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'invoice' => $invoice,
                'payment' => $payment,
                'gateway' => $this->getGatewayName(),
            ];
        }
    }
}
