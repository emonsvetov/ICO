<?php

namespace App\Listeners;

use App\Events\UserInvited;
use App\Services\UserService;
use App\Notifications\UserInvitedNotifyAdmin;
use App\Notifications\UserInvitedNotifyUser;
use Illuminate\Support\Facades\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;


class UserInvitedListner
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
     * @param  \App\Events\UserInvited  $event
     * @return void
     */
    public function handle(UserInvited $event)
    {
        $superAdmins = (new UserService)->getSuperAdmins();
        Notification::send($superAdmins, new UserInvitedNotifyAdmin( $event->sender, $event->recepient, $event->program ));
        Notification::send($event->recepient, new UserInvitedNotifyUser( $event->sender, $event->recepient, $event->program ));
    }
}
