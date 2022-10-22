<?php

namespace App\Jobs\Program;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

use App\Services\Program\GenerateMonthlyInvoicesService;
use App\Notifications\GenerateMonthlyInvoicesNotification;
use App\Services\UserService;

class GenerateMonthlyInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // public $programs;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->programs = $programs;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(GenerateMonthlyInvoicesService $generateMonthlyInvoicesService, UserService $userService)
    {
        \Log::info("GenerateMonthlyInvoicesJob starts!");
        $generateMonthlyInvoicesService->generate();
        \Log::info("GenerateMonthlyInvoicesJob Ends!");

        //Notification
        $superAdmins = $userService->getSuperAdmins();
        Notification::send($superAdmins, new GenerateMonthlyInvoicesNotification( ['message' => "Job: GenerateMonthlyInvoicesJob completed"] ));

        //The UserService() not working for required argument of AccountService class
    }
}
