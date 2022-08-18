<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\Event;
use App\Notifications\CSVImportNotification;

use DB;

class ImportEventJob implements ShouldQueue
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
            $eventIds = DB::transaction(function() use ($data, $supplied_constants) {
            
                $createdEventIds = [];
                $event = new Event;

                foreach ($data['EventRequest'] as $key => $eventData) 
                {    
                    $newEvent = $event->create($eventData + [
                        'organization_id' => $supplied_constants['organization_id'],
                        'program_id' => $data['CSVProgramRequest'][$key]['program_id']
                    ]);
                    $createdEventIds[] = $newEvent->id;
                }
            });  

            $this->csvImport->update(['is_imported' => 1]);
            
            //What to do with award?
        }
        catch (\Throwable $e)
        {
            $this->csvImport->notify(new CSVImportNotification(['errors' => $e->getMessage()]));
        } 
    }
}
