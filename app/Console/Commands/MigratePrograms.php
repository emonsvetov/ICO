<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Jobs\v2migrate\MigrateProgramsJob;
// use Illuminate\Support\Facades\Hash;

class MigratePrograms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'v2migrate:programs {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to migrate programs';

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
        //288308
        // print bcrypt('aaa') . '|||';exit;
        $program = array_filter(explode(',', $this->argument('id')), function($p) { return ( is_numeric($p) && (int) $p > 0 ); });
        dispatch(new MigrateProgramsJob(['program' => $program]));
        return 0;
    }
}
