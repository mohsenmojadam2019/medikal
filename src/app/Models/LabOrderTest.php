<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabOrderTest extends Model
{
    protected $fillable = [
        'lab_order_id',
        'lab_test_id',
        'unit_price',
        'quantity',
        'discount',
        'total_price',
        'notes',
        'is_urgent',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity' => 'integer',
        'discount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'is_urgent' => 'boolean',
    ];

    // ========== Relationships ==========
    public function labOrder()
    {
        return $this->belongsTo(LabOrder::class);
    }

    public function labTest()
    {
        return $this->belongsTo(LabTest::class);
    }

    public function result()
    {
        return $this->hasOne(LabResult::class);
    }

    // ========== Accessors ==========
    public function getTotalPriceAttribute($value): float
    {
        return $value ?? ($this->unit_price * $this->quantity);
    }
}
