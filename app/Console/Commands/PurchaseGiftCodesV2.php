<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CronService;

class PurchaseGiftCodesV2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:purchase-gift-codes-v2';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to purchase gift codes with v2';

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
        \Log::info("...... cron:purchase-gift-codes-v2 command running ......");
        $this->cronService->purchaseGiftCodesV2();
        \Log::info("------ cron:purchase-gift-codes-v2 done! ------");
        return 0;
    }
}
