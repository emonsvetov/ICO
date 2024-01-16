<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CronService;

class SendBirthdayAward extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:send-birthday-award';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to run to send birthday awards to participants';

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
        \Log::info("...... cron:send-birthday-award command running ......");
        $cronService->sendBirthdayAward();
        return 0;
    }
}
