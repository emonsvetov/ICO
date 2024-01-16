<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Jobs\v2migrate\MigrateMerchantsJob;

class MigrateMerchants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'v2migrate:merchants';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to migrate merchants';

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
        dispatch(new MigrateMerchantsJob());
        return 0;
    }
}
