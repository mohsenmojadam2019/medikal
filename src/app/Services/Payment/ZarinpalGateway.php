<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Enums\PaymentStatusEnum;
use App\Enums\InvoiceStatusEnum;
use Illuminate\Http\Request;
use Shetabit\Payment\Facade\Payment;
use Shetabit\Multipay\Invoice as ShetabitInvoice;

class ZarinpalGateway extends BaseGateway
{
    protected function getGatewayName(): string
    {
        return 'zarinpal';
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
                        'authority' => $transactionId,
                    ]);
                });

            $result = $payment->pay();

            $redirectUrl = null;
            if (method_exists($result, 'getActionUrl')) {
                $redirectUrl = $result->getActionUrl();
            } elseif (method_exists($result, 'getAction')) {
                $redirectUrl = $result->getAction();
            } elseif (is_string($result)) {
                $redirectUrl = $result;
            }

            if (!$redirectUrl) {
                throw new \Exception('آدرس درگاه پرداخت یافت نشد');
            }

            return [
                'success' => true,
                'redirect_url' => $redirectUrl,
                'gateway' => $this->getGatewayName(),
                'message' => 'در حال انتقال به درگاه زرین‌پال...',
            ];

        } catch (\Exception $e) {
            $this->logError('Zarinpal initiation failed', [
                'error' => $e->getMessage(),
                'invoice_id' => $invoice->id,
            ]);

            return [
                'success' => false,
                'message' => 'خطا در اتصال به درگاه زرین‌پال: ' . $e->getMessage(),
                'gateway' => $this->getGatewayName(),
            ];
        }
    }

    public function verify(Request $request): array
    {
        $authority = $request->input('Authority');

        if (!$authority) {
            return [
                'success' => false,
                'message' => 'پارامتر Authority یافت نشد',
            ];
        }

        // پیدا کردن پرداخت
        $payment = Payment::where('transaction_id', $authority)
            ->where('gateway', $this->getGatewayName())
            ->first();

        if (!$payment) {
            return [
                'success' => false,
                'message' => 'تراکنش یافت نشد',
                'authority' => $authority,
            ];
        }

        $invoice = $payment->invoice;

        if (!$invoice) {
            return [
                'success' => false,
                'message' => 'فاکتور یافت نشد',
            ];
        }

        try {
            $receipt = Payment::via($this->getGatewayName())
                ->amount((int) $invoice->total_amount)
                ->transactionId($authority)
                ->verify();

            $referenceId = $receipt->getReferenceId();

            $payment->update([
                'status' => PaymentStatusEnum::SUCCESS,
                'reference_code' => $referenceId,
                'message' => 'پرداخت با موفقیت انجام شد',
                'payment_date' => now(),
            ]);

            // بروزرسانی فاکتور
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
