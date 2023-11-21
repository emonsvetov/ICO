<?php

namespace App\Services;

use App\Jobs\Program\BalanceNotificationJob;
use App\Jobs\CsvAutoImportJob;
use App\Jobs\GenerateTestGiftCodesJob;
use App\Jobs\GenerateVirtualInventoryJob;
use App\Jobs\SubmitGiftCodesToTangoJob;
use App\Jobs\Program\PostMonthlyChargesToInvoiceJob;
use App\Jobs\Program\AddProgramsToInvoiceJob;
use App\Jobs\Program\GenerateMonthlyInvoicesJob;
use App\Jobs\Program\SubmitTangoOrdersJob;
use App\Jobs\Program\SendActivationReminderJob;
use App\Jobs\SendMilestoneAward;
use App\Jobs\SyncGiftCodesV2Job;

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
    public function generateTestGiftCodesJob() {
        dispatch( new GenerateTestGiftCodesJob() );
    }
    public function csvAutoImportJob()
    {
        dispatch(new csvAutoImportJob());
    }
    public function sendMilestoneAward() {
        dispatch( new SendMilestoneAward() );
    }
    public function submitGiftCodesToTangoJob() {
        dispatch( new SubmitGiftCodesToTangoJob() );
    }
    public function balanceNotificationJob()
    {
        dispatch(new BalanceNotificationJob());
    }
    public function syncGiftCodesV2() {
        dispatch( new SyncGiftCodesV2Job() );
    }
}
