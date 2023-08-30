<?php

namespace App\Jobs;

use App\Models\CsvImport;
use App\Models\Giftcode;
use App\Models\Merchant;
use App\Models\TangoOrdersApi;
use App\Services\AwardService;
use App\Services\CSVimportService;
use Aws\S3\S3Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\Event;
use App\Notifications\CSVImportNotification;

use DB;
use Illuminate\Support\Facades\Validator;

class CsvAutoImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $errors = [];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(CSVimportService $csvImportService, AwardService $awardService)
    {
        echo PHP_EOL . "Csv auto import cron Started on " . date('Y-m-d h:i:s') . PHP_EOL;

        $csvImports = CsvImport::getAllIsProcessed();

        echo PHP_EOL . "Number of CSV files: " . count($csvImports) . PHP_EOL;

        foreach ($csvImports as $csvImport) {
            $importData = $csvImportService->autoImportFile($csvImport, $awardService);
        }

        echo PHP_EOL . "END on " . date('Y-m-d h:i:s') . PHP_EOL;
    }

}
