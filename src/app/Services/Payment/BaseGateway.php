<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Payment;
use App\Enums\PaymentStatusEnum;
use Shetabit\Multipay\Invoice as ShetabitInvoice;
use Illuminate\Support\Facades\Log;

abstract class BaseGateway implements GatewayInterface
{
    protected Invoice $invoice;
    protected Payment $payment;

    abstract protected function getGatewayName(): string;

    protected function createShetabitInvoice(Invoice $invoice): ShetabitInvoice
    {
        $shetabitInvoice = new ShetabitInvoice();
        $shetabitInvoice->amount((int) $invoice->total_amount);

        if ($invoice->patient?->user) {
            if ($invoice->patient->user->email) {
                $shetabitInvoice->detail('email', $invoice->patient->user->email);
            }
            if ($invoice->patient->user->mobile) {
                $shetabitInvoice->detail('mobile', $invoice->patient->user->mobile);
            }
        }

        $shetabitInvoice->detail('metadata', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
        ]);

        return $shetabitInvoice;
    }

    protected function storePayment(Invoice $invoice, string $transactionId, array $extra = []): Payment
    {
        return Payment::create([
            'invoice_id' => $invoice->id,
            'patient_id' => $invoice->patient_id,
            'transaction_id' => $transactionId,
            'amount' => $invoice->total_amount,
            'gateway' => $this->getGatewayName(),
            'status' => PaymentStatusEnum::PENDING,
            'raw_data' => $extra,
        ]);
    }

    protected function getCallbackUrl(): string
    {
        $gateway = $this->getGatewayName();

        try {
            return route('payment.callback', ['gateway' => $gateway]);
        } catch (\Exception $e) {
            return config('app.url') . '/api/payment/callback/' . $gateway;
        }
    }

    protected function logError(string $message, array $context = []): void
    {
        Log::error($message, array_merge($context, [
            'gateway' => $this->getGatewayName(),
            'invoice_id' => $this->invoice->id ?? null,
        ]));
    }
}
