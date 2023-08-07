<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CronService;

class GenerateVirtualInventory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:generate-virtual-inventory';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to generate virtual inventory for tango';

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
        $this->cronService->generateVirtualInventoryJob();
        return 0;
    }
}
