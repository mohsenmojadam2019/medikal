<?php

namespace App\Events\Pharmacy;

use App\Models\PharmacyOrder;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;

    public function __construct(PharmacyOrder $order)
    {
        $this->order = $order;
    }

    public function broadcastOn()
    {
        return new PresenceChannel('pharmacy.' . $this->order->pharmacy_id);
    }

    public function broadcastAs()
    {
        return 'order.created';
    }

    public function broadcastWith()
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'pharmacy_id' => $this->order->pharmacy_id,
            'total_amount' => $this->order->total_amount,
            'created_at' => $this->order->created_at->toDateTimeString(),
        ];
    }
}
