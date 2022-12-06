<?php

namespace App\Listeners;

use App\Events\InvitationAccepted;
use App\Notifications\InvitationAcceptedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class InvitationAcceptedListner
{
    /**
     * Handle the event.
     *
     * @param  \App\Events\InvitationAccepted  $event
     * @return void
     */
    public function handle(InvitationAccepted $event)
    {
        Notification::send($event->user, new InvitationAcceptedNotification( $event->user));
    }
}
