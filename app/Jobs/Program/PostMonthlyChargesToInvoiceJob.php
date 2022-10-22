<?php

namespace App\Jobs\Program;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

use App\Services\Program\PostMonthlyChargesToInvoiceService;
use App\Services\UserService;

class PostMonthlyChargesToInvoiceJob implements ShouldQueue
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
    public function handle(PostMonthlyChargesToInvoiceService $postMonthlyChargesToInvoiceService)
    {
        \Log::info("PostMonthlyChargesToInvoiceJob starts!");
        $postMonthlyChargesToInvoiceService->post();
        \Log::info("PostMonthlyChargesToInvoiceJob Ends!");

        //Notification
        // $superAdmins = (new UserService)->getSuperAdmins();
        // Notification::send($superAdmins, new AddProgramsToInvoiceNotification( ['message' => "Job: AddProgramsToInvoiceJob completed"] ));

        //The UserService() above not working for required argument of AccountService class
    }
}
