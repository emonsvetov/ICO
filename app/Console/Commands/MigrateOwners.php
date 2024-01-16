<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Jobs\v2migrate\MigrateOwnersJob;

class MigrateOwners extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'v2migrate:owners';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to migrate owners and super admins';

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
        // pr($this->arguments());
        // pr($this->options());
        // exit;
        dispatch(new MigrateOwnersJob( $this->options() ));
        return 0;
    }
}
