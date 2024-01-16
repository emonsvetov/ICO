<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Jobs\v2migrate\MigrateUsersJob;
use Exception;

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
    protected $description = 'Command to migrate users by program Id or ids. Run `php artisan v2migrate:users --program=[Single or Comma separated Ids of v2 programs]`.';

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
        $programOption = $this->option('program');
        if( !$programOption ) {
            throw new Exception('Missing required option "program"');
            exit;
        }
        $programId = current($programOption);
        if( !$programId ) {
            throw new Exception('"programId" must not be empty');
            exit;
        }
        $program = array_filter(explode(',', $programId), function($p) { return ( is_numeric($p) && (int) $p > 0 ); });
        if( !$program ) {
            throw new Exception('Invalid or null program ids.');
            exit;
        }
        dispatch(new MigrateUsersJob( ['program' => $program] ));
        return 0;
    }
}
