<?php

namespace App\Listeners;

use App\Mail\FunnyEmail;
use App\Events\OrganizationCreated;
use App\Notifications\NewOrganizationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NewOrganizationListner
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
     * @param  \App\Events\OrganizationCreated  $event
     * @return void
     */
    public function handle(OrganizationCreated $event)
    {
        Notification::send($event->organization, new NewOrganizationNotification( $event->organization ));
    }
}
