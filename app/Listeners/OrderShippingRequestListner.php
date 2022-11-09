<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

use App\Events\OrderShippingRequest;
use App\Notifications\OrderShippingRequestNotification;

class OrderShippingRequestListner
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\OrderShippingRequest  $event
     * @return void
     */
    public function handle(OrderShippingRequest $event)
    {
        Notification::send($event->physicalOrder, new OrderShippingRequestNotification( $event->shippingRequest, $event->physicalOrder));
    }
}
