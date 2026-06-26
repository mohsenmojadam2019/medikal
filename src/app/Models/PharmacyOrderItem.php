<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PharmacyOrderItem extends Model
{
    protected $table = 'pharmacy_order_items';

    protected $fillable = [
        'order_id',
        'drug_id',
        'quantity',
        'unit_price',
        'total_price',
        'is_available',
        'unavailable_reason',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(PharmacyOrder::class, 'order_id');
    }

    public function drug()
    {
        return $this->belongsTo(Drug::class);
    }
}
