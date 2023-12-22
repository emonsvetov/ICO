<?php

namespace App\Jobs\v2migrate;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Services\v2migrate\MigrateOwnersService;

class MigrateOwnersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $arguments;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( $arguments = [])
    {
        $this->arguments = $arguments;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle( MigrateOwnersService $migrateOwnersService )
    {
        \Log::info("Migrate Owners Job starts!");
        $migrateOwnersService->migrate( $this->arguments );
    }
}
