<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Jobs\v2migrate\MigrateEventXmlDataJob;

class MigrateEventXmalData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'v2migrate:eventxmldata';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to migrate EventXmalData';

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
        dispatch(new MigrateEventXmlDataJob());
        return 0;
    }
}
