<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Jobs\v2migrate\MigrateInvoiceJournalEventsJob;

class MigrateInvoiceJournalEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'v2migrate:invoice-journal-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to migrate "invoice_journal_events" table relations. This SHOULD ONLY BE run after a successful migration of all programs.';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        dispatch(new MigrateInvoiceJournalEventsJob());
        return 0;
    }
}
