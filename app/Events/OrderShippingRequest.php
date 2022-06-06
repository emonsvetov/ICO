<?php

namespace App\Events;

use App\Models\PhysicalOrder;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderShippingRequest
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $shippingRequest;
    public $physicalOrder;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( $shippingRequest, PhysicalOrder $physicalOrder )
    {
        $this->shippingRequest = $shippingRequest;
        $this->physicalOrder = $physicalOrder;
    }
}
