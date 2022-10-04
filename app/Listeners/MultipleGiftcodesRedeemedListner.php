<?php

namespace App\Listeners;

use App\Events\MultipleGiftcodesRedeemed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class MultipleGiftcodesRedeemedListner
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
     * @param  \App\Events\MultipleGiftcodesRedeemed  $event
     * @return void
     */
    public function handle(MultipleGiftcodesRedeemed $event)
    {
        //
    }
}
