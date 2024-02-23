<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\v2migrate\MigrateMerchantsService;
use App\Services\v2migrate\MigrateProgramsService;
use App\Services\v2migrate\MigrationBaseService;
use Exception;
use Illuminate\Http\Request;

class MigrationController extends Controller
{
    private $migrationBaseService;

    public function __construct(MigrationBaseService $migrationBaseService) {
        $this->migrationBaseService = $migrationBaseService;
    }

    /**
     * Run migrations for a program.
     *
     * @param $account_holder_id
     */
    public function run($account_holder_id)
    {
        ini_set('max_execution_time', 360);

        $args = [];
        $args['v2AccountHolderID'] = $account_holder_id;
        $result = $this->migrationBaseService->migrate($args);

        return response($result);
    }

    /**
     * Run global migrations list.
     */
    public function runGlobal()
    {
        ini_set('max_execution_time', 360);

        $result = $this->migrationBaseService->migrateGlobal();
        return response($result);
    }

}
