<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CronService;

class CsvAutoImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'csv-auto-import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import csv files that have been uploaded to AWS';

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
        $this->cronService->csvAutoImportJob();
        \Log::info("------ MonthlyInvoicing > generateMonthlyInvoices done! ------");
        // pr($programs);
        return 0;
    }
}
