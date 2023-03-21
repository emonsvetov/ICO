<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CronService;

class SendActivationReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:send-activation-reminder';
    protected $cronService;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to run to send weekly reminder to participants for activation';

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
        \Log::info("...... cron:send-activation-reminder command running ......");
        $this->cronService->sendActivationReminder();
        return 0;
    }
}
