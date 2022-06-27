<?php

namespace App\Listeners;

use App\Events\InvitationCreated;
use App\Notifications\InvitationNotification;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;


class InvitationListner
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
     * @param  \App\Events\InvitationCreated  $event
     * @return void
     */
    public function handle(InvitationCreated $event)
    {
       // Notification::route('mail', 'admin@incentco.com')
       // ->route('nexmo', '5555555555')
       // ->route('slack', 'https://hooks.slack.com/services/...')
       // ->notify(new InvitationNotification( $event->organization ) );
    }
}
