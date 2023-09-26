<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CronService;

class GenerateTestGiftCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:generate-test-gift-codes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to generate test gift codes both for qa and demo on production';

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
        $this->cronService->generateTestGiftCodesJob();
        return 0;
    }
}
