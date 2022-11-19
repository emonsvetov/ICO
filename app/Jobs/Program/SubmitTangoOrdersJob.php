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
use App\Services\Program\TangoOrderService;
use App\Services\UserService;

class SubmitTangoOrdersJob implements ShouldQueue, ShouldBeUnique
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
    public function handle(TangoOrderService $tangoOrderService, UserService $userService)
    {
        \Log::info("SubmitTangoOrdersJob starts!");
        $response = $tangoOrderService->submitOrders();
        \Log::info("SubmitTangoOrdersJob ends!");

        //Notification
        $superAdmins = $userService->getSuperAdmins();
        Notification::send($superAdmins, new SubmitTangoOrdersNotification( ['message' => "Job: SubmitTangoOrdersJob completed", 'response' => $response] ));
    }
}
