<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Jobs\v2migrate\MigrateProgramAccountsJob;

class MigrateProgramsAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'v2migrate:program-accounts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to migrate program accounts';

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
        dispatch(new MigrateProgramAccountsJob());
        return 0;
    }
}
