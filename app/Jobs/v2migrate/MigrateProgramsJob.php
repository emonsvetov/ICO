<?php

namespace App\Jobs\v2migrate;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Services\v2migrate\MigrateProgramsService;

class MigrateProgramsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $migrationService;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle( MigrateProgramsService $migrateProgramsService )
    {
        \Log::info("Migrate Programs Job starts!");
        $migrateProgramsService->migrate();
    }
}
