<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CronService;

class SubmitTangoOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:submit-tango-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to submit tango orders';

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
        \Log::info("...... cron:submit-tango-orders command running ......");
        $this->cronService->submitTangoOrders();
        \Log::info("------ MonthlyInvoicing > generateMonthlyInvoices done! ------");
        // pr($programs);
        return 0;
    }
}
