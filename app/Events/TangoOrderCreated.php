<?php

namespace App\Events;

use App\Models\TangoOrder;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TangoOrderCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tangoOrder;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( TangoOrder $tangoOrder)
    {
        $this->tangoOrder = $tangoOrder;
    }
}
