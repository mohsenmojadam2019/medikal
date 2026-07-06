<?php

namespace App\Events\Pharmacy;

use App\Models\PrescriptionRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrescriptionRequested implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $prescriptionRequest;

    public function __construct(PrescriptionRequest $prescriptionRequest)
    {
        $this->prescriptionRequest = $prescriptionRequest;
    }

    public function broadcastOn()
    {
        return new Channel('admin.prescriptions');
    }

    public function broadcastAs()
    {
        return 'prescription.requested';
    }

    public function broadcastWith()
    {
        return [
            'request_id' => $this->prescriptionRequest->id,
            'user_id' => $this->prescriptionRequest->user_id,
            'pharmacy_id' => $this->prescriptionRequest->pharmacy_id,
            'created_at' => $this->prescriptionRequest->created_at->toDateTimeString(),
        ];
    }
}
