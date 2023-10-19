<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Jobs\v2migrate\MigrateUsersJob;

class MigrateUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'v2migrate:users {--id=*} {--p|program=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to migrate users';

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
        dispatch(new MigrateUsersJob( $this->options() ));
        return 0;
    }
}
