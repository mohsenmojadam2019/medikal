<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Appointment;
use Illuminate\Http\Request;

class LocalGateway extends BaseGateway
{
    public function __construct()
    {
        parent::__construct();
        $this->name = 'local';
    }

    public function initiate(Invoice $invoice): array
    {
        $transactionId = 'LOCAL-' . time() . '-' . rand(1000, 9999);
        $referenceCode = 'REF-' . time() . '-' . rand(1000, 9999);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'patient_id' => $invoice->patient_id,
            'transaction_id' => $transactionId,
            'reference_code' => $referenceCode,
            'amount' => $invoice->total_amount,
            'gateway' => 'local',
            'status' => Payment::STATUS_SUCCESS,
            'message' => 'پرداخت تست با موفقیت انجام شد',
            'payment_date' => now(),
            'raw_data' => json_encode([
                'test' => true,
                'timestamp' => now()->toIso8601String(),
            ]),
        ]);

        // ✅ آپدیت وضعیت فاکتور
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
            
            \Log::info('✅ Appointment confirmed after local payment', [
                'appointment_id' => $appointment->id,
                'invoice_id' => $invoice->id,
            ]);
        }

        return [
            'success' => true,
            'message' => 'پرداخت با موفقیت انجام شد',
            'payment_id' => $payment->id,
            'transaction_id' => $payment->transaction_id,
            'reference_code' => $payment->reference_code,
            'invoice' => $invoice,
            'redirect_url' => null,
        ];
    }

    public function verify(Request $request): array
    {
        return [
            'success' => true,
            'message' => 'پرداخت تایید شد',
            'transaction_id' => $request->get('transaction_id', 'LOCAL-' . time()),
            'reference_code' => $request->get('ref_id', 'REF-' . time()),
        ];
    }
}
