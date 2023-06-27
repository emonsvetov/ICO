<?php

namespace App\Services\v2migrate;

use Illuminate\Support\Facades\DB;

class MigrationService
{
    public $v2db; //Database connection

    public bool $overwrite = false;
    public int $importedCount = 0;
    public array $imported = [];
    public bool $printSql = true;
    public string $v2Sql = '';
    public string $v3Sql = '';
    public bool $debug = true;

    protected function __construct()
    {
        $this->v2db = DB::connection('v2');
    }

    public function isPrintSql() {
        return $this->printSql;
    }

    protected function isDebug() {
        return filter_var($this->debug, FILTER_VALIDATE_BOOLEAN);
    }

    public function setDebug( mixed $debug )   {
        $this->debug = $debug;
    }

    protected function printf($string) {
        if(!$this->isDebug()) return;
        $argv = func_get_args();
        $format = array_shift( $argv );
        vprintf( $format, $argv );
    }
    protected  function prepareSQL($sql) {
        $sql = trim($sql);
        return $sql . (str_ends_with($sql, ';') ? '' : ';' );
    }
    protected function addV2SQL( $sql ) {
        $this->v2Sql .= "
        ". $this->prepareSQL($sql);
    }
    protected function addV3SQL( $sql ) {
        $this->v3Sql .= "
        ". $this->prepareSQL($sql);
    }

    protected function executeV2SQL()   {
        if( !empty( trim( $this->v2Sql)) )   {
            $this->v2db->unprepared( $this->v2db->raw($this->v2Sql) );
            $this->printf("Custom v2 DB:statement run.\n");
            $this->v2Sql = '';
        }
    }

    protected function executeV3SQL()   {
        if( !empty( trim( $this->v3Sql)) )   {
            DB::unprepared( DB::raw($this->v3Sql) );
            $this->printf("Custom v3 DB:statement run.\n");
            $this->v3Sql = '';
        }
    }
}
