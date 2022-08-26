<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\Program;
use App\Notifications\CSVImportNotification;

use DB;

class ImportProgramJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $csvImport;
    public $importData;
    public $supplied_constants;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($csvImport, $importData, $supplied_constants)
    {
        $this->csvImport = $csvImport;
        $this->importData = $importData;
        $this->supplied_constants = $supplied_constants;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = $this->importData;
        $supplied_constants = $this->supplied_constants;

        try
        {
            $programIds = DB::transaction(function() use ($data, $supplied_constants) {
            
                $createdProgramIds = [];
                $program = new Program;

                foreach ($data['ProgramRequest'] as $key => $programData) 
                {    
                    $newProgram = $program->createAccount($programData + [
                        'organization_id' => $supplied_constants['organization_id']
                    ]);
                    $createdProgramIds[] = $newProgram->id;
                }
            });  

            $this->csvImport->update(['is_imported' => 1]);
        }
        catch (\Throwable $e)
        {
            $this->csvImport->notify(new CSVImportNotification(['errors' => $e->getMessage()]));
        } 
    }
}
