<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use App\Services\reports\ReportTrialBalanceService;

class UpdateTrialBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:trialbalance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Trial Balance Data';

    /**
     * ReportTrialBalanceService instance.
     */
    protected $trialBalanceService;

    /**
     * Create a new command instance.
     *
     * @param ReportTrialBalanceService $trialBalanceService
     * @return void
     */
    public function __construct(ReportTrialBalanceService $trialBalanceService)
    {
        parent::__construct();
        $this->trialBalanceService = $trialBalanceService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    
    public function handle()
    {
        \Log::info("Updating trial balance data...");
        try {
            $this->trialBalanceService->updateTrialBalance();
            \Log::info("Trial balance data updated successfully.");
        } catch (Exception $e) {
            \Log::error("Error updating trial balance data: " . $e->getMessage());
            return 1;
        }
        return 0;
    }
}
