<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Jobs\v2migrate\MigrateJournalEventsJob;

class MigrateJournalEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'v2migrate:journalevents {--type=}';
    //Example: php artisan v2migrate:journalevents --type=merchants
    //Example: php artisan v2migrate:journalevents --type=programs
    //Example: php artisan v2migrate:journalevents --type=users

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to migrate journal events by accounts';

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
        $type = array_filter(explode(',', $this->option('type')), function($p) { return in_array($p, ['all', 'merchants', 'programs', 'users']); });
        dispatch(new MigrateJournalEventsJob($type));
        return 0;
    }
}
