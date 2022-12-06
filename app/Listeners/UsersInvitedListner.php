<?php

namespace App\Listeners;

use App\Events\UsersInvited;
use App\Services\UserService;
use App\Notifications\UserInvitedNotifyAdmin;
use App\Notifications\UserInvitedNotifyUser;
use Illuminate\Support\Facades\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UsersInvitedListner
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
     * @param  \App\Events\UsersInvited  $event
     * @return void
     */
    public function handle(UsersInvited $event)
    {
        $superAdmins = $this->userService->getSuperAdmins();
        foreach($event->recepients as $recepient)   {
            // Notification::send($superAdmins, new UserInvitedNotifyAdmin( $event->sender, $recepient, $event->program )); //Uncomment later
            Notification::send($recepient, new UserInvitedNotifyUser( $event->sender, $recepient, $event->program, $recepient->token ));
        }
    }
}
