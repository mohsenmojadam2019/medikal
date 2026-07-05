<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Patient;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    use ApiResponse;

    /**
     * دریافت فاکتورهای من (بیمار)
     */
    public function myInvoices(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('لطفاً وارد شوید', 401);
        }

        $patient = Patient::where('user_id', $user->id)->first();
        if (!$patient) {
            return $this->error('بیمار یافت نشد', 404);
        }

        $query = Invoice::where('patient_id', $patient->id)
            ->with(['appointment', 'appointment.doctor', 'appointment.doctor.user'])
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $invoices = $query->paginate($request->get('per_page', 15));

        return $this->success($invoices);
    }

    /**
     * نمایش یک فاکتور
     */
    public function show($id)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return $this->error('لطفاً وارد شوید', 401);
            }

            $patient = Patient::where('user_id', $user->id)->first();
            if (!$patient) {
                return $this->error('بیمار یافت نشد', 404);
            }

            $invoice = Invoice::with(['appointment', 'appointment.doctor', 'payments'])
                ->findOrFail($id);

            if ($invoice->patient_id !== $patient->id && !$user->isAdmin()) {
                return $this->error('شما دسترسی به این فاکتور ندارید', 403);
            }

            return $this->success($invoice);
        } catch (\Exception $e) {
            return $this->error('فاکتور یافت نشد', 404);
        }
    }

    /**
     * ✅ دریافت فاکتور بر اساس appointment_id
     */
    public function getByAppointment($appointmentId)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return $this->error('لطفاً وارد شوید', 401);
            }

            $patient = Patient::where('user_id', $user->id)->first();
            if (!$patient) {
                return $this->error('بیمار یافت نشد', 404);
            }

            \Log::info('🔍 Searching invoice for appointment', [
                'appointment_id' => $appointmentId,
                'patient_id' => $patient->id,
            ]);

            $invoice = Invoice::where('appointment_id', $appointmentId)
                ->where('patient_id', $patient->id)
                ->first();

            if (!$invoice) {
                \Log::warning('❌ Invoice not found', [
                    'appointment_id' => $appointmentId,
                    'patient_id' => $patient->id,
                ]);
                return $this->error('فاکتور یافت نشد', 404);
            }

            \Log::info('✅ Invoice found', [
                'invoice_id' => $invoice->id,
                'appointment_id' => $appointmentId,
            ]);

            return $this->success($invoice);
        } catch (\Exception $e) {
            \Log::error('❌ Error fetching invoice: ' . $e->getMessage());
            return $this->error('خطا در دریافت فاکتور', 500);
        }
    }

    /**
     * آمار فاکتورها
     */
    public function stats()
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('لطفاً وارد شوید', 401);
        }

        $patient = Patient::where('user_id', $user->id)->first();
        if (!$patient) {
            return $this->error('بیمار یافت نشد', 404);
        }

        $total = Invoice::where('patient_id', $patient->id)->count();
        $paid = Invoice::where('patient_id', $patient->id)->where('status', 'paid')->count();
        $issued = Invoice::where('patient_id', $patient->id)->where('status', 'issued')->count();
        $cancelled = Invoice::where('patient_id', $patient->id)->where('status', 'cancelled')->count();

        $totalAmount = Invoice::where('patient_id', $patient->id)->sum('total_amount');
        $paidAmount = Invoice::where('patient_id', $patient->id)->where('status', 'paid')->sum('total_amount');

        return $this->success([
            'total' => $total,
            'paid' => $paid,
            'issued' => $issued,
            'cancelled' => $cancelled,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'pending_amount' => $totalAmount - $paidAmount,
        ]);
    }
}


