<?php

namespace App\Jobs\Program;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

use App\Services\Program\AddProgramsToInvoiceService;
use App\Notifications\AddProgramsToInvoiceNotification;
use App\Services\UserService;

class AddProgramsToInvoiceJob implements ShouldQueue
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
    public function handle(AddProgramsToInvoiceService $addProgramsToInvoiceService, UserService $userService)
    {
        \Log::info("AddProgramsToInvoiceJob starts!");
        $addProgramsToInvoiceService->add();
        \Log::info("AddProgramsToInvoiceJob Ends!");

        //Notification
        $superAdmins = $userService->getSuperAdmins();
        Notification::send($superAdmins, new AddProgramsToInvoiceNotification( ['message' => "Job: AddProgramsToInvoiceJob completed"] ));

        //The UserService() not working for required argument of AccountService class
    }
}
