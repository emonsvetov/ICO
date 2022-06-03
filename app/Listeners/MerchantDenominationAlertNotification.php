<?php

namespace App\Listeners;

use App\Events\MerchantDenominationAlert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class MerchantDenominationAlertNotification
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
     * @param  \App\Events\MerchantDenominationAlert  $event
     * @return void
     */
    public function handle(MerchantDenominationAlert $event)
    {
        //
    }
}
