<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CronService;

class SubmitGiftCodesToTango extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:submit-gift-codes-to-tango';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to resubmit gift codes';

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
        $this->cronService->submitGiftCodesToTangoJob();
        return 0;
    }
}
