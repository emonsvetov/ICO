<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CronService;

class SendMilestoneAward extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:send-milestone-award';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to run to send milestone awards to participants';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $cronService = new CronService;
        \Log::info("...... cron:send-activation-reminder command running ......");
        $cronService->sendMilestoneAward();
        return 0;
    }
}
