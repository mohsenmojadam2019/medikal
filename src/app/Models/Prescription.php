<?php

namespace App\Models;

use App\Enums\PrescriptionStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Prescription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'appointment_id',
        'patient_id',
        'doctor_id',
        'code',
        'drug_name',
        'dosage',
        'frequency',
        'duration',
        'start_date',
        'end_date',
        'instructions',
        'diagnosis',
        'status',
        'notes',
        'side_effects',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'frequency' => 'integer',
        'duration' => 'integer',
        'status' => PrescriptionStatusEnum::class,
        'metadata' => 'array',
    ];

    // ========== Relationships ==========
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    // ========== Accessors ==========
    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? 'نامشخص';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status?->color() ?? 'secondary';
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === PrescriptionStatusEnum::ACTIVE;
    }

    public function getDaysRemainingAttribute(): int
    {
        if (!$this->end_date) return 0;
        $now = now();
        $end = Carbon::parse($this->end_date);
        return max(0, $now->diffInDays($end, false));
    }

    public function getTotalDaysAttribute(): int
    {
        if (!$this->start_date || !$this->end_date) {
            return $this->duration ?? 0;
        }
        $start = Carbon::parse($this->start_date);
        $end = Carbon::parse($this->end_date);
        return $start->diffInDays($end) + 1;
    }

    public function getFrequencyLabelAttribute(): string
    {
        $labels = [
            1 => 'یک بار در روز',
            2 => 'دو بار در روز',
            3 => 'سه بار در روز',
            4 => 'چهار بار در روز',
        ];
        return $labels[$this->frequency] ?? "{$this->frequency} بار در روز";
    }

    public function getDailyTimesAttribute(): array
    {
        $times = [
            1 => ['08:00'],
            2 => ['08:00', '20:00'],
            3 => ['08:00', '14:00', '20:00'],
            4 => ['06:00', '12:00', '18:00', '24:00'],
        ];
        return $times[$this->frequency] ?? ['08:00'];
    }

    // ========== Scopes ==========
    public function scopeActive($query)
    {
        return $query->where('status', PrescriptionStatusEnum::ACTIVE);
    }

    public function scopePending($query)
    {
        return $query->where('status', PrescriptionStatusEnum::PENDING);
    }

    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeExpiringSoon($query, $days = 3)
    {
        return $query->where('status', PrescriptionStatusEnum::ACTIVE)
            ->whereDate('end_date', '<=', now()->addDays($days))
            ->whereDate('end_date', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('status', PrescriptionStatusEnum::ACTIVE)
            ->whereDate('end_date', '<', now());
    }

    // ========== Methods ==========
    public function generateCode(): string
    {
        $prefix = 'RX';
        $year = now()->format('y');
        $month = now()->format('m');
        $day = now()->format('d');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}{$month}{$day}-{$random}";
    }

    public function activate(): void
    {
        $this->update(['status' => PrescriptionStatusEnum::ACTIVE]);
    }

    public function complete(): void
    {
        $this->update(['status' => PrescriptionStatusEnum::COMPLETED]);
    }

    public function cancel(): void
    {
        $this->update(['status' => PrescriptionStatusEnum::CANCELLED]);
    }

    public function expire(): void
    {
        $this->update(['status' => PrescriptionStatusEnum::EXPIRED]);
    }

    public function generateReminders(): array
    {
        $reminders = [];
        $times = $this->daily_times;
        $startDate = Carbon::parse($this->start_date);
        $duration = $this->duration ?? 7;

        for ($day = 0; $day < $duration; $day++) {
            $date = $startDate->copy()->addDays($day);
            if ($date->isPast()) continue;

            foreach ($times as $time) {
                $reminders[] = [
                    'patient_id' => $this->patient_id,
                    'prescription_id' => $this->id,
                    'drug_name' => $this->drug_name,
                    'dosage' => $this->dosage,
                    'date' => $date->format('Y-m-d'),
                    'time' => $time,
                    'is_taken' => false,
                ];
            }
        }

        return $reminders;
    }

    public function getDrugInteractions(): array
    {
        // دریافت داروهای دیگر بیمار
        $otherDrugs = Prescription::where('patient_id', $this->patient_id)
            ->where('id', '!=', $this->id)
            ->where('status', PrescriptionStatusEnum::ACTIVE)
            ->pluck('drug_name')
            ->toArray();

        // اینجا می‌تونی با یک API یا دیتابیس تداخل دارویی چک کنی
        // فعلاً یک نمونه ساده
        $interactions = [];
        $knownInteractions = [
            'آموکسی‌سیلین' => ['متفورمین', 'لوزارتان'],
            'متفورمین' => ['آموکسی‌سیلین', 'ایبوپروفن'],
            'لوزارتان' => ['آموکسی‌سیلین', 'دیازپام'],
        ];

        foreach ($otherDrugs as $drug) {
            if (isset($knownInteractions[$this->drug_name]) &&
                in_array($drug, $knownInteractions[$this->drug_name])) {
                $interactions[] = [
                    'drug' => $drug,
                    'severity' => 'moderate',
                    'message' => "تداخل بین {$this->drug_name} و {$drug}",
                ];
            }
        }

        return $interactions;
    }

    // ========== Boot Methods ==========
    protected static function booted()
    {
        static::creating(function ($prescription) {
            if (empty($prescription->code)) {
                $prescription->code = $prescription->generateCode();
            }
            if (empty($prescription->status)) {
                $prescription->status = PrescriptionStatusEnum::ACTIVE;
            }
            if (empty($prescription->start_date)) {
                $prescription->start_date = now()->toDateString();
            }
            if (empty($prescription->end_date) && !empty($prescription->duration)) {
                $prescription->end_date = now()->addDays($prescription->duration)->toDateString();
            }
        });
    }
}
