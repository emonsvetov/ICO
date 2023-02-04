<?php

namespace App\Listeners;

use App\Events\OrganizationCreated;
use App\Services\UserService;

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
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\OrganizationCreated  $event
     * @return void
     */
    public function handle(OrganizationCreated $event)
    {
        $superAdmins = $this->userService->getSuperAdmins();
        //Notification::send($superAdmins, new NewOrganizationNotification( $event->organization )); comment out for now and auto confirm email TODO: Add mail verification email with link
    }
}
