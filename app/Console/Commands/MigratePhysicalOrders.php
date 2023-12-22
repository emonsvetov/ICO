<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Jobs\v2migrate\MigratePhysicalOrdersJob;

class MigratePhysicalOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'v2migrate:physicalorders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to migrate physical orders';

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
        dispatch(new MigratePhysicalOrdersJob());
        return 0;
    }
}
