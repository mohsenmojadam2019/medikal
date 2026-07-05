<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Enums\PaymentStatusEnum;
use Illuminate\Http\Request;
use Shetabit\Payment\Facade\Payment;
use Shetabit\Multipay\Invoice as ShetabitInvoice;

class IdpayGateway extends BaseGateway
{
    protected function getGatewayName(): string
    {
        return 'idpay';
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
                    $this->storePayment($invoice, $transactionId);
                });

            $result = $payment->pay();

            $redirectUrl = null;
            if (method_exists($result, 'getActionUrl')) {
                $redirectUrl = $result->getActionUrl();
            } elseif (method_exists($result, 'getAction')) {
                $redirectUrl = $result->getAction();
            } elseif (is_string($result)) {
                $redirectUrl = $result;
            } elseif (is_array($result) && isset($result['url'])) {
                $redirectUrl = $result['url'];
            }

            if (!$redirectUrl) {
                throw new \Exception('آدرس درگاه پرداخت یافت نشد');
            }

            return [
                'success' => true,
                'redirect_url' => $redirectUrl,
                'gateway' => $this->getGatewayName(),
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'amount' => $invoice->total_amount,
                'message' => 'در حال انتقال به درگاه آیدی پی...',
            ];

        } catch (\Exception $e) {
            $this->logError('Idpay initiation failed', [
                'error' => $e->getMessage(),
                'invoice_id' => $invoice->id,
            ]);

            return [
                'success' => false,
                'message' => 'خطا در اتصال به درگاه آیدی پی: ' . $e->getMessage(),
                'gateway' => $this->getGatewayName(),
            ];
        }
    }

    public function verify(Request $request): array
    {
        $id = $request->input('id');
        $orderId = $request->input('order_id');

        if (!$id && !$orderId) {
            return [
                'success' => false,
                'message' => 'پارامترهای پرداخت یافت نشد',
                'gateway' => $this->getGatewayName(),
            ];
        }

        $payment = Payment::where('transaction_id', $id)
            ->orWhere('transaction_id', $orderId)
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
                ->transactionId($id)
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
