<?php

namespace App\Services\v2migrate;

use Illuminate\Support\Facades\DB;

class MigrationService
{
    public $v2db; //Database connection

    public bool $overwrite = false;
    public int $importedCount = 0;
    public array $imported = [];

    protected function __construct()
    {
        $this->v2db = DB::connection('v2');
    }
}
