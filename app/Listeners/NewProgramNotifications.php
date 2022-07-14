<?php

namespace App\Listeners;

use App\Events\ProgramCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use Notification;
use App\Notifications\OrganizationProgramCreated;

class NewProgramNotifications
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
     * @param  ProgramCreated  $event
     * @return void
     */
    public function handle(ProgramCreated $event)
    {        
        Notification::send($event->program->organization, new OrganizationProgramCreated( $event->program ));
    }
}