<?php

namespace App\Services\v2migrate;

use Illuminate\Support\Facades\DB;

use App\Models\User;

class MigrationService
{
    public $v2db;

    public function __construct()
    {
        $this->v2db = DB::connection('v2');
    }
}
