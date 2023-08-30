<?php

namespace App\Services;

use App\Jobs\CsvAutoImportJob;
use App\Jobs\GenerateVirtualInventoryJob;
use App\Jobs\Program\PostMonthlyChargesToInvoiceJob;
use App\Jobs\Program\AddProgramsToInvoiceJob;
use App\Jobs\Program\GenerateMonthlyInvoicesJob;
use App\Jobs\Program\SubmitTangoOrdersJob;
use App\Jobs\Program\SendActivationReminderJob;
// use App\Notifications\CronNotification; //Can be used for CronNotifications

class CronService
{
    public function postMonthlyChargesToInvoice()  {
        dispatch( new PostMonthlyChargesToInvoiceJob() );
    }
    public function addProgramsToInvoice() {
        dispatch( new AddProgramsToInvoiceJob() );
    }
    public function generateMonthlyInvoices() {
        dispatch( new GenerateMonthlyInvoicesJob() );
    }
    public function submitTangoOrders() {
        dispatch( new SubmitTangoOrdersJob() );
    }
    public function sendActivationReminder() {
        dispatch( new SendActivationReminderJob() );
    }
    public function generateVirtualInventoryJob() {
        dispatch( new GenerateVirtualInventoryJob() );
    }

    public function csvAutoImportJob() {
        dispatch( new csvAutoImportJob() );
    }
}
