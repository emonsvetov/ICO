<?php

namespace App\Listeners;

use App\Events\CommentsCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use Notification;
use App\Notifications\NewCommentsCreated;

class NewCommentsListner
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
     * @param  CommentsCreated  $event
     * @return void
     */
    public function handle(CommentsCreated $event)
    {        
        foreach($event->recepients as $recepient)   {
            // Notification::send($superAdmins, new UserInvitedNotifyAdmin( $event->sender, $recepient, $event->program )); //Uncomment later
            Notification::send($recepient, new NewCommentsCreated( $recepient, $event->comment));
        }
    }
}