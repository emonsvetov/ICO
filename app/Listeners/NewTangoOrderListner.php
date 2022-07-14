<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use App\Events\TangoOrderCreated;
use App\Notifications\NewTangoOrderNotification;

class NewTangoOrderListner
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
     * @param  \App\Events\TangoOrderCreated  $event
     * @return void
     */
    public function handle(TangoOrderCreated $event)
    {
        Notification::send($event->tangoOrder, new NewTangoOrderNotification( $event->tangoOrder ));
    }
}
