<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vaccine extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'manufacturer',
        'disease',
        'doses_required',
        'interval_days',
        'age_min_months',
        'age_max_months',
        'description',
        'side_effects',
        'storage_condition',
        'is_active',
        'is_required',
        'metadata',
    ];

    protected $casts = [
        'doses_required' => 'integer',
        'interval_days' => 'integer',
        'age_min_months' => 'integer',
        'age_max_months' => 'integer',
        'is_active' => 'boolean',
        'is_required' => 'boolean',
        'metadata' => 'array',
    ];

    public function patientVaccinations()
    {
        return $this->hasMany(PatientVaccination::class);
    }

    public function reminders()
    {
        return $this->hasMany(VaccinationReminder::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'فعال' : 'غیرفعال';
    }

    public function getRequiredLabelAttribute(): string
    {
        return $this->is_required ? 'اجباری' : 'اختیاری';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeByDisease($query, $disease)
    {
        return $query->where('disease', 'LIKE', "%{$disease}%");
    }

    public function generateCode(): string
    {
        $prefix = 'VAC';
        $year = now()->format('y');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$year}-{$random}";
    }

    protected static function booted()
    {
        static::creating(function ($vaccine) {
            if (empty($vaccine->code)) {
                $vaccine->code = $vaccine->generateCode();
            }
        });
    }
}
