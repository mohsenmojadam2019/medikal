<?php

namespace App\Events\Pharmacy;

use App\Models\PharmacyProduct;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LowStockAlert implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $product;

    public function __construct(PharmacyProduct $product)
    {
        $this->product = $product;
    }

    public function broadcastOn()
    {
        return new Channel('pharmacy.' . $this->product->pharmacy_id . '.alerts');
    }

    public function broadcastAs()
    {
        return 'stock.low';
    }

    public function broadcastWith()
    {
        return [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'current_stock' => $this->product->stock,
            'min_stock' => $this->product->min_stock,
            'pharmacy_id' => $this->product->pharmacy_id,
        ];
    }
}
