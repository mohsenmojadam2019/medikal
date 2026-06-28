<?php

namespace App\Services\Invoice;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Appointment;
use App\Enums\InvoiceStatusEnum;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id');
    }

    public function list(array $filters = [], int $perPage = 15)
    {
        $query = Invoice::where('tenant_id', $this->tenantId)
            ->with(['patient.user', 'appointment']);

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
            $taxRate = 0.09;
            $tax = $amount * $taxRate;
            $discount = $appointment->discount ?? 0;
            $total = $amount + $tax - $discount;

            $invoice = Invoice::create([
                'tenant_id' => $this->tenantId,
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
        return Invoice::where('tenant_id', $this->tenantId)
            ->with([
                'patient.user',
                'appointment',
                'appointment.doctor.user',
                'payments'
            ])
            ->findOrFail($id);
    }

    public function patientInvoices(Patient $patient, array $filters = [], int $perPage = 15)
    {
        $query = Invoice::where('tenant_id', $this->tenantId)
            ->where('patient_id', $patient->id)
            ->with(['appointment']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getOverdueInvoices()
    {
        return Invoice::where('tenant_id', $this->tenantId)
            ->overdue()
            ->with(['patient.user'])
            ->get();
    }

    public function getTotalStats(): array
    {
        return [
            'total_invoices' => Invoice::where('tenant_id', $this->tenantId)->count(),
            'total_paid' => Invoice::where('tenant_id', $this->tenantId)->paid()->sum('total_amount'),
            'total_unpaid' => Invoice::where('tenant_id', $this->tenantId)->issued()->sum('total_amount'),
            'total_overdue' => Invoice::where('tenant_id', $this->tenantId)->overdue()->count(),
        ];
    }
}
