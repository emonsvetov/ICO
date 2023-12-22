<?php

namespace App\Jobs\v2migrate;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Services\v2migrate\MigrateJournalEventsService;

class MigrateJournalEventsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $type;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( $type = [])
    {
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle( MigrateJournalEventsService $migrateJournalEventsService )
    {
        \Log::info("Migrate JournalEvents Job starts!");
        $migrateJournalEventsService->migrateJournalEventsByV3Accounts( $this->type );
    }
}
