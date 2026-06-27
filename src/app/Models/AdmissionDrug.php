<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionDrug extends Model
{
    protected $fillable = [
        'admission_id',
        'drug_name',
        'dosage',
        'frequency',
        'route',
        'start_date',
        'end_date',
        'quantity',
        'unit_price',
        'total_price',
        'prescribed_by',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    public function prescriber()
    {
        return $this->belongsTo(Doctor::class, 'prescribed_by');
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

    public function getRouteLabelAttribute(): string
    {
        $labels = [
            'oral' => 'خوراکی',
            'iv' => 'داخل وریدی',
            'im' => 'داخل عضلانی',
            'sc' => 'زیر جلدی',
            'topical' => 'موضعی',
            'inhalation' => 'استنشاقی',
        ];
        return $labels[$this->route] ?? $this->route;
    }

    public function getPriceDisplayAttribute(): string
    {
        return number_format($this->total_price ?? 0) . ' تومان';
    }
}
