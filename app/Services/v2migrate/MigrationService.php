<?php

namespace App\Services\v2migrate;

use Illuminate\Support\Facades\DB;

use App\Services\ProgramService;
use App\Models\User;

class MigrationService
{
    public $v2db;

    public $programService;
    public function __construct(ProgramService $programService)
    {
        $this->programService = $programService;
        $this->v2db = DB::connection('v2');
    }
}
