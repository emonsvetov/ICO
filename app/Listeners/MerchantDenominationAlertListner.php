<?php

namespace App\Listeners;

use App\Events\MerchantDenominationAlert;
use App\Notifications\MerchantDenominationAlertNotification;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;


class MerchantDenominationAlertListner
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
        Notification::route('mail', 'admin@incentco.com')
        ->route('nexmo', '5555555555')
        ->route('slack', 'https://hooks.slack.com/services/...')
        ->notify(new MerchantDenominationAlertNotification( $event->organization ) );
    }
}
