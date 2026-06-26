<?php

namespace App\Services\Invoice;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Appointment;
use App\Enums\InvoiceStatusEnum;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function list(array $filters = [], int $perPage = 15)
    {
        $query = Invoice::with(['patient.user', 'appointment']);

        if (isset($filters['patient_id'])) {
            $query->byPatient($filters['patient_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function createFromAppointment(Appointment $appointment): Invoice
    {
        return DB::transaction(function () use ($appointment) {
            $amount = $appointment->fee ?? 0;
            $taxRate = 0.09; // ۹٪
            $tax = $amount * $taxRate;
            $discount = $appointment->discount ?? 0;
            $total = $amount + $tax - $discount;

            $invoice = Invoice::create([
                'patient_id' => $appointment->patient_id,
                'appointment_id' => $appointment->id,
                'amount' => $amount,
                'tax' => $tax,
                'discount' => $discount,
                'total_amount' => $total,
                'description' => "فاکتور نوبت دکتر {$appointment->doctor->full_name}",
                'status' => InvoiceStatusEnum::ISSUED,
                'due_date' => now()->addDays(7),
            ]);

            // اضافه کردن آیتم‌ها
            $invoice->addItem([
                'description' => "ویزیت دکتر {$appointment->doctor->full_name}",
                'amount' => $amount,
                'quantity' => 1,
                'total' => $amount,
            ]);

            if ($discount > 0) {
                $invoice->addItem([
                    'description' => 'تخفیف',
                    'amount' => -$discount,
                    'quantity' => 1,
                    'total' => -$discount,
                ]);
            }

            return $invoice->fresh(['patient.user', 'appointment']);
        });
    }

    public function show(int $id): Invoice
    {
        return Invoice::with([
            'patient.user',
            'appointment',
            'appointment.doctor.user',
            'payments'
        ])->findOrFail($id);
    }

    public function patientInvoices(Patient $patient, array $filters = [], int $perPage = 15)
    {
        $query = Invoice::where('patient_id', $patient->id)
            ->with(['appointment']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getOverdueInvoices()
    {
        return Invoice::overdue()->with(['patient.user'])->get();
    }

    public function getTotalStats(): array
    {
        return [
            'total_invoices' => Invoice::count(),
            'total_paid' => Invoice::paid()->sum('total_amount'),
            'total_unpaid' => Invoice::issued()->sum('total_amount'),
            'total_overdue' => Invoice::overdue()->count(),
        ];
    }
}
