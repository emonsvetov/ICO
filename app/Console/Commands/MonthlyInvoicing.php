<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CronService;

class MonthlyInvoicing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:monthly-invoicing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to run monthly invoicing';

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
        \Log::info("...... cron:monthly-invoicing command running ......");
        $this->cronService->addProgramsToInvoice();
        \Log::info("------ MonthlyInvoicing > addProgramsToInvoice done! ------");
        $this->cronService->postMonthlyChargesToInvoice();
        \Log::info("------ MonthlyInvoicing > postMonthlyChargesToInvoice done! ------");
        $this->cronService->generateMonthlyInvoices();
        \Log::info("------ MonthlyInvoicing > generateMonthlyInvoices done! ------");
        // pr($programs);
        return 0;
    }
}
