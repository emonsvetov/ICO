<?php

namespace App\Jobs\Program;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

use App\Notifications\SubmitTangoOrdersNotification;
use App\Notifications\SendActivationReminderNotification;
use App\Services\UserService;

class SendActivationReminderJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(UserService $userService)
    {
        \Log::info("SendActivationReminder starts!");
        $response = $userService->sendActivationReminderToParticipants();
        \Log::info(json_encode($response));
        \Log::info("SendActivationReminder ends!");
    }
}
