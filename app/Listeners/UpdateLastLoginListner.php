<?php

namespace App\Listeners;

use App\Events\UserInvited;
use App\Services\UserService;
use App\Notifications\UserInvitedNotifyAdmin;
use App\Notifications\UserInvitedNotifyUser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;


class UpdateLastLoginListner
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $event->user->last_login = Carbon::now();
        $event->user->save();
    }
}
