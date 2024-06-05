<?php

namespace App\Listeners;

use App\Events\ParticipantAddedToProgram;
use App\Services\UserService;
use App\Notifications\ParticipantAddedNotifyUser;
use Illuminate\Support\Facades\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;


class ParticipantAddedToProgramListner
{
    private UserService $userService;
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
     * @param  \App\Events\UserInvited  $event
     * @return void
     */
    public function handle(ParticipantAddedToProgram $event)
    {
        // $superAdmins = $this->userService->getSuperAdmins();
        // Notification::send($superAdmins, new UserInvitedNotifyAdmin( $event->sender, $event->recepient, $event->program ));

        Notification::send($event->recepient, new ParticipantAddedNotifyUser( $event->sender, $event->recepient, $event->program));
    }
}
