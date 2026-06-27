<?php

namespace App\Models;

use App\Enums\AdmissionServiceTypeEnum;
use Illuminate\Database\Eloquent\Model;

class AdmissionService extends Model
{
    protected $fillable = [
        'admission_id',
        'service_name',
        'type',
        'description',
        'quantity',
        'unit_price',
        'price',
        'performed_at',
        'performed_by',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'type' => AdmissionServiceTypeEnum::class,
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'price' => 'decimal:2',
        'performed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type?->label() ?? 'نامشخص';
    }

    public function getTypeColorAttribute(): string
    {
        return $this->type?->color() ?? 'secondary';
    }

    public function getPriceDisplayAttribute(): string
    {
        return number_format($this->price ?? 0) . ' تومان';
    }
}
