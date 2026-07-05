<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Appointment;
use Illuminate\Http\Request;

class ZarinpalGateway extends BaseGateway
{
    public function __construct()
    {
        parent::__construct();
        $this->name = 'zarinpal';
    }

    public function initiate(Invoice $invoice): array
    {
        $transactionId = 'ZARIN-' . time() . '-' . rand(1000, 9999);
        $referenceCode = 'ZREF-' . time() . '-' . rand(1000, 9999);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'patient_id' => $invoice->patient_id,
            'transaction_id' => $transactionId,
            'reference_code' => $referenceCode,
            'amount' => $invoice->total_amount,
            'gateway' => 'zarinpal',
            'status' => Payment::STATUS_PENDING,
            'authority' => 'Z' . time() . rand(1000, 9999),
            'message' => 'در انتظار پرداخت',
        ]);

        return [
            'success' => true,
            'message' => 'در حال انتقال به درگاه زرین‌پال...',
            'payment_id' => $payment->id,
            'transaction_id' => $payment->transaction_id,
            'redirect_url' => env('FRONTEND_URL', 'http://localhost:3000') . '/payment/result?success=true&gateway=zarinpal',
        ];
    }

    public function verify(Request $request): array
    {
        // در محیط تست، پرداخت را موفق فرض می‌کنیم
        $invoiceId = $request->get('invoice_id');
        $invoice = $invoiceId ? Invoice::find($invoiceId) : null;

        if ($invoice) {
            // آپدیت وضعیت فاکتور
            $invoice->update([
                'is_paid' => true,
                'paid_at' => now(),
                'status' => 'paid',
            ]);

            // ✅ آپدیت وضعیت نوبت از pending به confirmed
            $appointment = Appointment::where('id', $invoice->appointment_id)->first();
            if ($appointment && $appointment->status === 'pending') {
                $appointment->status = 'confirmed';
                $appointment->save();
                
                \Log::info('✅ Appointment confirmed after zarinpal payment', [
                    'appointment_id' => $appointment->id,
                    'invoice_id' => $invoice->id,
                ]);
            }

            // آپدیت وضعیت پرداخت
            $payment = Payment::where('invoice_id', $invoice->id)
                ->where('status', Payment::STATUS_PENDING)
                ->first();
                
            if ($payment) {
                $payment->update([
                    'status' => Payment::STATUS_SUCCESS,
                    'message' => 'پرداخت با موفقیت انجام شد',
                    'payment_date' => now(),
                ]);
            }
        }

        return [
            'success' => true,
            'message' => 'پرداخت با موفقیت تایید شد',
            'transaction_id' => 'ZARIN-' . time(),
            'reference_code' => $request->get('Authority', 'ZREF-' . time()),
            'invoice' => $invoice,
        ];
    }
}
