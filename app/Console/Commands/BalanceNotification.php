<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CronService;

class BalanceNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'balance-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Balance Notification';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(CronService $cronService)
    {
        $this->cronService = $cronService;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        \Log::info("...... cron:balance-notification command running ......");
        $this->cronService->balanceNotificationJob();
        \Log::info("------ balance-notification done ------");
        return 0;
    }
}
