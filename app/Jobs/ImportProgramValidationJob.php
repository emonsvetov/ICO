<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Jobs\ImportProgramJob;
use App\Services\CSVimportService;

use App\Notifications\CSVImportNotification;

class ImportProgramValidationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $csvImport;
    public $fieldsToMap;
    public $supplied_constants;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($csvImport, $fieldsToMap, $supplied_constants)
    {
        $this->csvImport = $csvImport;
        $this->fieldsToMap = $fieldsToMap;
        $this->supplied_constants = $supplied_constants;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(CSVimportService $csvService)
    {
        $importData = $csvService->importFile( $this->csvImport, $this->fieldsToMap, $this->supplied_constants );
                
        if ( empty($importData['errors']) )
        {
            //import data
            ImportProgramJob::dispatch($this->csvImport, $importData, $this->supplied_constants);
        }
        else {
            $this->csvImport->notify(new CSVImportNotification($importData));
        }
    }
}
