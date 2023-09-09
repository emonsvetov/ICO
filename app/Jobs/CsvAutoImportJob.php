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
use function Ramsey\Uuid\v1;

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

        $csvImports = CsvImport::all();
        $csvImportService->updateProcessedList($csvImports->pluck('name')->toArray());
        $csvImports = CsvImport::getAllIsProcessed();

        $successUsers = $errors = [];

        echo PHP_EOL . "Number of CSV files: " . count($csvImports) . PHP_EOL;

        foreach ($csvImports as $csvImport) {
            $result = $csvImportService->autoImportFile($csvImport, $awardService);
            if (isset($result['success']) && $result['success']) {
                $successUsers[] = $result;
            } else {
                $errors = $result;
            }
        }

        if ($successUsers){
            echo 'Users added successfully: ' . count($successUsers) . PHP_EOL;
        }

        if ($errors) {
            echo 'Errors: ' . PHP_EOL;
            print_r($errors);
        }

        echo PHP_EOL . "END on " . date('Y-m-d h:i:s') . PHP_EOL;
    }

}
