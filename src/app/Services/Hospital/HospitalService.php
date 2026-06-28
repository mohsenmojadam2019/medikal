<?php

namespace App\Services\Hospital;

use App\Models\Admission;
use App\Models\Ward;
use App\Models\Bed;
use App\Models\Discharge;
use App\Models\Invoice;
use App\Enums\AdmissionStatusEnum;
use App\Enums\BedStatusEnum;
use App\Enums\InvoiceStatusEnum;
use App\Services\Invoice\InvoiceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HospitalService
{
    protected $tenantId;
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->tenantId = session('tenant_id');
        $this->invoiceService = $invoiceService;
    }

    public function getWards(array $filters = [], int $perPage = 20)
    {
        $query = Ward::where('tenant_id', $this->tenantId)
            ->withCount(['beds', 'admissions']);

        if (isset($filters['search'])) {
            $query->where('name', 'LIKE', "%{$filters['search']}%")
                ->orWhere('code', 'LIKE', "%{$filters['search']}%");
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function createWard(array $data): Ward
    {
        $data['tenant_id'] = $this->tenantId;
        return Ward::create($data);
    }

    public function updateWard(Ward $ward, array $data): Ward
    {
        $ward->update($data);
        return $ward->fresh();
    }

    public function deleteWard(Ward $ward): void
    {
        $ward->delete();
    }

    public function getBeds(array $filters = [], int $perPage = 20)
    {
        $query = Bed::where('tenant_id', $this->tenantId)
            ->with(['ward']);

        if (isset($filters['ward_id'])) {
            $query->where('ward_id', $filters['ward_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('bed_number')->paginate($perPage);
    }

    public function createBed(array $data): Bed
    {
        $data['tenant_id'] = $this->tenantId;
        return Bed::create($data);
    }

    public function updateBed(Bed $bed, array $data): Bed
    {
        $bed->update($data);
        return $bed->fresh();
    }

    public function deleteBed(Bed $bed): void
    {
        $bed->delete();
    }

    public function changeBedStatus(Bed $bed, string $status): Bed
    {
        $bed->update(['status' => $status]);
        return $bed->fresh();
    }

    public function getAdmissions(array $filters = [], int $perPage = 15)
    {
        $query = Admission::where('tenant_id', $this->tenantId)
            ->with(['patient.user', 'doctor.user', 'ward', 'bed']);

        if (isset($filters['patient_id'])) {
            $query->byPatient($filters['patient_id']);
        }

        if (isset($filters['doctor_id'])) {
            $query->byDoctor($filters['doctor_id']);
        }

        if (isset($filters['ward_id'])) {
            $query->byWard($filters['ward_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['from_date'])) {
            $query->whereDate('admission_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('admission_date', '<=', $filters['to_date']);
        }

        if (isset($filters['search'])) {
            $query->where('admission_number', 'LIKE', "%{$filters['search']}%")
                ->orWhereHas('patient', function ($q) use ($filters) {
                    $q->where('national_code', 'LIKE', "%{$filters['search']}%");
                })
                ->orWhereHas('patient.user', function ($q) use ($filters) {
                    $q->where('name', 'LIKE', "%{$filters['search']}%");
                });
        }

        return $query->orderBy('admission_date', 'desc')->paginate($perPage);
    }

    public function getAdmission(int $id): Admission
    {
        return Admission::where('tenant_id', $this->tenantId)
            ->with([
                'patient.user',
                'doctor.user',
                'ward',
                'bed',
                'services',
                'drugs',
                'days',
                'discharge',
                'invoice'
            ])
            ->findOrFail($id);
    }

    public function createAdmission(array $data): Admission
    {
        return DB::transaction(function () use ($data) {
            if (isset($data['bed_id'])) {
                $bed = Bed::where('tenant_id', $this->tenantId)->findOrFail($data['bed_id']);
                if ($bed->status !== BedStatusEnum::AVAILABLE) {
                    throw new \Exception('تخت انتخاب شده در دسترس نیست');
                }
            }

            $data['tenant_id'] = $this->tenantId;
            $admission = Admission::create($data);

            if (isset($data['bed_id'])) {
                $bed->occupy();
            }

            if ($admission->ward) {
                $admission->ward->updateOccupancy();
            }

            $this->createAdmissionInvoice($admission);

            return $admission->fresh();
        });
    }

    public function updateAdmission(Admission $admission, array $data): Admission
    {
        return DB::transaction(function () use ($admission, $data) {
            if (isset($data['bed_id']) && $data['bed_id'] != $admission->bed_id) {
                if ($admission->bed) {
                    $admission->bed->free();
                }

                $newBed = Bed::where('tenant_id', $this->tenantId)->findOrFail($data['bed_id']);
                if ($newBed->status !== BedStatusEnum::AVAILABLE) {
                    throw new \Exception('تخت جدید در دسترس نیست');
                }
                $newBed->occupy();

                if ($admission->ward) {
                    $admission->ward->updateOccupancy();
                }
                if ($newBed->ward) {
                    $newBed->ward->updateOccupancy();
                }
            }

            $admission->update($data);
            return $admission->fresh();
        });
    }

    public function admitPatient(Admission $admission): Admission
    {
        return DB::transaction(function () use ($admission) {
            $admission->admit();
            if ($admission->bed) {
                $admission->bed->occupy();
            }
            if ($admission->ward) {
                $admission->ward->updateOccupancy();
            }
            return $admission->fresh();
        });
    }

    public function dischargePatient(Admission $admission, array $data): Discharge
    {
        return DB::transaction(function () use ($admission, $data) {
            $data['tenant_id'] = $this->tenantId;
            $discharge = Discharge::create([
                'tenant_id' => $this->tenantId,
                'admission_id' => $admission->id,
                'doctor_id' => $data['doctor_id'] ?? $admission->doctor_id,
                'final_diagnosis' => $data['final_diagnosis'] ?? null,
                'summary' => $data['summary'] ?? null,
                'medications_at_discharge' => $data['medications_at_discharge'] ?? null,
                'follow_up_instructions' => $data['follow_up_instructions'] ?? null,
                'follow_up_date' => $data['follow_up_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $totalCost = $this->calculateAdmissionCost($admission);

            $invoice = $admission->invoice;
            if ($invoice) {
                $invoice->update([
                    'amount' => $totalCost,
                    'total_amount' => $totalCost * 1.09,
                    'status' => InvoiceStatusEnum::ISSUED,
                ]);
            }

            $discharge->complete();

            if ($admission->bed) {
                $admission->bed->free();
            }
            if ($admission->ward) {
                $admission->ward->updateOccupancy();
            }

            return $discharge->fresh(['admission', 'doctor']);
        });
    }

    public function addAdmissionDay(array $data): AdmissionDay
    {
        $admission = Admission::where('tenant_id', $this->tenantId)
            ->findOrFail($data['admission_id']);

        if (!$admission->is_active) {
            throw new \Exception('بیمار بستری نیست');
        }

        $dayNumber = $admission->days()->count() + 1;

        $data['tenant_id'] = $this->tenantId;
        return AdmissionDay::create([
            'tenant_id' => $this->tenantId,
            'admission_id' => $data['admission_id'],
            'day_number' => $dayNumber,
            'date' => $data['date'] ?? now()->toDateString(),
            'temperature' => $data['temperature'] ?? null,
            'heart_rate' => $data['heart_rate'] ?? null,
            'respiratory_rate' => $data['respiratory_rate'] ?? null,
            'blood_pressure_systolic' => $data['blood_pressure_systolic'] ?? null,
            'blood_pressure_diastolic' => $data['blood_pressure_diastolic'] ?? null,
            'oxygen_saturation' => $data['oxygen_saturation'] ?? null,
            'pain_score' => $data['pain_score'] ?? null,
            'weight' => $data['weight'] ?? null,
            'height' => $data['height'] ?? null,
            'bmi' => $data['bmi'] ?? null,
            'consciousness_level' => $data['consciousness_level'] ?? null,
            'notes' => $data['notes'] ?? null,
            'nurse_id' => $data['nurse_id'] ?? auth()->id(),
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    public function getAdmissionDays(int $admissionId)
    {
        return AdmissionDay::where('tenant_id', $this->tenantId)
            ->where('admission_id', $admissionId)
            ->with(['nurse'])
            ->orderBy('day_number', 'asc')
            ->get();
    }

    public function addService(array $data): AdmissionService
    {
        $admission = Admission::where('tenant_id', $this->tenantId)
            ->findOrFail($data['admission_id']);

        if (!$admission->is_active) {
            throw new \Exception('بیمار بستری نیست');
        }

        $data['tenant_id'] = $this->tenantId;
        $service = AdmissionService::create([
            'tenant_id' => $this->tenantId,
            'admission_id' => $data['admission_id'],
            'service_name' => $data['service_name'],
            'type' => $data['type'] ?? 'other',
            'description' => $data['description'] ?? null,
            'quantity' => $data['quantity'] ?? 1,
            'unit_price' => $data['unit_price'] ?? 0,
            'price' => ($data['unit_price'] ?? 0) * ($data['quantity'] ?? 1),
            'performed_at' => $data['performed_at'] ?? now(),
            'performed_by' => $data['performed_by'] ?? auth()->id(),
            'notes' => $data['notes'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);

        $this->updateAdmissionInvoice($admission);
        return $service->fresh();
    }

    public function addDrug(array $data): AdmissionDrug
    {
        $admission = Admission::where('tenant_id', $this->tenantId)
            ->findOrFail($data['admission_id']);

        if (!$admission->is_active) {
            throw new \Exception('بیمار بستری نیست');
        }

        $totalPrice = ($data['unit_price'] ?? 0) * ($data['quantity'] ?? 1);

        $data['tenant_id'] = $this->tenantId;
        $drug = AdmissionDrug::create([
            'tenant_id' => $this->tenantId,
            'admission_id' => $data['admission_id'],
            'drug_name' => $data['drug_name'],
            'dosage' => $data['dosage'],
            'frequency' => $data['frequency'] ?? 1,
            'route' => $data['route'] ?? 'oral',
            'start_date' => $data['start_date'] ?? now()->toDateString(),
            'end_date' => $data['end_date'] ?? null,
            'quantity' => $data['quantity'] ?? 1,
            'unit_price' => $data['unit_price'] ?? 0,
            'total_price' => $totalPrice,
            'prescribed_by' => $data['prescribed_by'] ?? auth()->user()->doctor?->id,
            'notes' => $data['notes'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);

        $this->updateAdmissionInvoice($admission);
        return $drug->fresh();
    }

    public function calculateAdmissionCost(Admission $admission): float
    {
        $bedCost = 0;
        if ($admission->bed && $admission->admission_date) {
            $days = $admission->duration;
            $bedCost = $days * ($admission->bed->price_per_day ?? 0);
        }

        $servicesCost = $admission->services()->sum('price');
        $drugsCost = $admission->drugs()->sum('total_price');

        return $bedCost + $servicesCost + $drugsCost;
    }

    public function createAdmissionInvoice(Admission $admission): Invoice
    {
        $totalCost = $this->calculateAdmissionCost($admission);

        return Invoice::create([
            'tenant_id' => $this->tenantId,
            'patient_id' => $admission->patient_id,
            'invoice_number' => $this->generateInvoiceNumber(),
            'amount' => $totalCost,
            'tax' => $totalCost * 0.09,
            'discount' => 0,
            'total_amount' => $totalCost * 1.09,
            'description' => "فاکتور بستری - پذیرش {$admission->admission_number}",
            'status' => InvoiceStatusEnum::DRAFT,
            'due_date' => now()->addDays(7),
            'invoicable_type' => Admission::class,
            'invoicable_id' => $admission->id,
            'items' => $this->generateInvoiceItems($admission),
        ]);
    }

    public function updateAdmissionInvoice(Admission $admission): void
    {
        $invoice = $admission->invoice;
        if ($invoice) {
            $totalCost = $this->calculateAdmissionCost($admission);
            $invoice->update([
                'amount' => $totalCost,
                'total_amount' => $totalCost * 1.09,
                'items' => $this->generateInvoiceItems($admission),
            ]);
        }
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'HOS-INV';
        $year = now()->format('y');
        $month = now()->format('m');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$year}{$month}-{$random}";
    }

    private function generateInvoiceItems(Admission $admission): array
    {
        $items = [];

        if ($admission->bed) {
            $items[] = [
                'description' => "تخت {$admission->bed->bed_number} - بخش {$admission->ward->name}",
                'quantity' => $admission->duration,
                'unit_price' => $admission->bed->price_per_day ?? 0,
                'total' => $admission->duration * ($admission->bed->price_per_day ?? 0),
            ];
        }

        foreach ($admission->services as $service) {
            $items[] = [
                'description' => $service->service_name,
                'quantity' => $service->quantity,
                'unit_price' => $service->unit_price,
                'total' => $service->price,
            ];
        }

        foreach ($admission->drugs as $drug) {
            $items[] = [
                'description' => "{$drug->drug_name} ({$drug->dosage})",
                'quantity' => $drug->quantity,
                'unit_price' => $drug->unit_price,
                'total' => $drug->total_price,
            ];
        }

        return $items;
    }

    public function getStats(array $filters = []): array
    {
        $query = Admission::where('tenant_id', $this->tenantId);

        if (isset($filters['from_date'])) {
            $query->whereDate('admission_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('admission_date', '<=', $filters['to_date']);
        }

        return [
            'total_admissions' => $query->count(),
            'active_admissions' => (clone $query)->active()->count(),
            'discharged' => (clone $query)->discharged()->count(),
            'by_ward' => $this->getAdmissionsByWard($filters),
            'occupancy_rate' => $this->getOccupancyRate(),
            'total_revenue' => (clone $query)->discharged()->sum('total_cost'),
        ];
    }

    private function getAdmissionsByWard(array $filters): array
    {
        $query = Admission::where('tenant_id', $this->tenantId);

        if (isset($filters['from_date'])) {
            $query->whereDate('admission_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('admission_date', '<=', $filters['to_date']);
        }

        return $query->with('ward')
            ->selectRaw('ward_id, count(*) as total')
            ->groupBy('ward_id')
            ->get()
            ->map(function ($item) {
                return [
                    'ward_name' => $item->ward?->name ?? 'نامشخص',
                    'total' => $item->total,
                ];
            })
            ->toArray();
    }

    private function getOccupancyRate(): float
    {
        $totalCapacity = Ward::where('tenant_id', $this->tenantId)->sum('capacity');
        $totalOccupied = Bed::where('tenant_id', $this->tenantId)->where('status', BedStatusEnum::OCCUPIED)->count();

        if ($totalCapacity == 0) {
            return 0;
        }
        return round(($totalOccupied / $totalCapacity) * 100, 1);
    }
}
