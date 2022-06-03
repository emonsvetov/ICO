<?php

namespace App\Listeners;

use App\Events\TangoOrderCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NewTangoOrderNotification
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
        //
    }
}
