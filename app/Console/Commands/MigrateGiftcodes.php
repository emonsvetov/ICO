<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Jobs\v2migrate\MigrateGiftcodesJob;

class MigrateGiftcodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'v2migrate:giftcodes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to migrate giftcodes';

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
        dispatch(new MigrateGiftcodesJob());
        return 0;
    }
}
