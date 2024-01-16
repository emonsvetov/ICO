<?php

namespace App\Jobs\v2migrate;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Services\v2migrate\MigrateUsersService;

class MigrateUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $migrationService;
    public $options = null;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( $options = null)
    {
        $this->options = $options;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle( MigrateUsersService $migrateUsersService )
    {
        print("Migrate Users Job starts!\n");
        $migrateUsersService->migrate( $this->options );
    }
}
