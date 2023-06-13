<?php

namespace App\Jobs\v2migrate;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Services\v2migrate\MigrateInvoiceJournalEventsService;

class MigrateInvoiceJournalEventsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
    public function handle( MigrateInvoiceJournalEventsService $migrateInvoiceJournalEventsService )
    {
        \Log::info("Migrate InvoiceJournalEvents Job starts!");
        $migrateInvoiceJournalEventsService->migrate();
    }
}
