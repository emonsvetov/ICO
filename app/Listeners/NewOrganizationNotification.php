<?php

namespace App\Listeners;

use App\Events\OrganizationCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NewOrganizationNotification
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
        //
    }
}
