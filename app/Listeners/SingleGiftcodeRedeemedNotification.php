<?php

namespace App\Listeners;

use App\Events\SingleGiftcodeRedeemed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SingleGiftcodeRedeemedNotification
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
     * @param  \App\Events\SingleGiftcodeRedeemed  $event
     * @return void
     */
    public function handle(SingleGiftcodeRedeemed $event)
    {
        //
    }
}
