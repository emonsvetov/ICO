<?php

namespace App\Services\v2migrate;

use Illuminate\Support\Facades\DB;

use App\Models\Program;

class MigrationService
{
    public $v2db; //Database connection

    public bool $overwrite = false;
    public int $importedCount = 0;
    public array $imported = [];
    public bool $isPrintSql = false;
    public string $v2Sql = '';
    public string $v3Sql = '';
    public bool $debug = true;
    public $v2pid = null;
    public $v3pid = null;
    public $v2Program;
    public Program $v3Program;
    public array $importMap;
    public $v2RefKey = null; //used for reference key in v3 db tables
    public $v3RefKey = null; //used for reference key in v2 db tables
    public $modelName = null; //v3 model name.
    public $v2Model = null; //The current v2 model being migrated
    public $v3Model = null; //The current v3 model being migrated
    public $idPrefix = 999999;
    public $minusPrefix = '-';
    public $countPostings = 0;
    public $countAccounts = 0;
    public $cachedPrimeAccountHolders = [];


    public function setV2Model( $v2Model ) {
        $this->v2Model = $v2Model;
    }

    public function setV3Model( $v3Model ) {
        $this->v3Model = $v3Model;
    }

    public function getRefKeyByModel( $version, $modelName, $custom='' )    {
        if( $custom ) {
            $field = $custom;
        }   else {
            if( $version == 'v2' )  {
                $field = 'account_holder_id';
            }   else if ( $version == 'v3' ) {
                $field = $modelName . '_id';
            }
        }

        return sprintf("%s_%s", $version, $field);
    }

    public function setRefByModel( $v3Model, $v2Model = null ) {

        $this->setV3Model( $v3Model );
        $this->setV2Model( $v2Model );

        $CLASS = get_class($v3Model);
        $this->modelName = strtolower(substr($CLASS, strrpos($CLASS, "\\") + 1 ));

        $this->v2RefKey = $this->getRefKeyByModel('v2', $this->modelName); //field name used in v3 table
        $this->v3RefKey = $this->getRefKeyByModel('v3', $this->modelName); //field name used in v2 table
    }

    public function setv2pid( $v2pid ) {
        $this->v2pid = $v2pid;
    }

    public function v2pid() {
        return $this->v2pid;
    }

    public function setv3pid( $v3pid ) {
        $this->v3pid = $v3pid;
    }

    public function setv3Program( $v3Program ) {
        $this->v3Program = $v3Program;
    }

    public function v3Program() {
        return $this->v3Program;
    }

    protected function __construct()
    {
        $this->v2db = DB::connection('v2');
    }

    public function isPrintSql() {
        return $this->isPrintSql;
    }

    public function printSql( $sql ) {
        if($this->isPrintSql()) {
            print("SQL:" . $sql . "\n");
        }
    }

    protected function isDebug() {
        return filter_var($this->debug, FILTER_VALIDATE_BOOLEAN);
    }

    public function setDebug( mixed $debug )   {
        $this->debug = $debug;
    }

    protected function printf($string) {
        $argv = func_get_args();
        print_r($argv);
        \Illuminate\Support\Facades\Log::channel('v2migration')->info(print_r($argv, true));
//        $format = array_shift( $argv );
//        vprintf( $format, $argv );
//        \Illuminate\Support\Facades\Log::channel('v2migration')->info(rtrim(vsprintf( $format, $argv )));
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

    public function getV2UserById( $v2_user_account_holder_id) {
        $result = $this->v2db->select(sprintf("SELECT * FROM `users` WHERE `account_holder_id`=%d", $v2_user_account_holder_id));
        if( $result ) return current($result);
    }

    public function getV2ProgramById( $v2_program_account_holder_id) {
        $result = $this->v2db->select(sprintf("SELECT * FROM `programs` WHERE `account_holder_id`=%d", $v2_program_account_holder_id));
        if( $result ) return current($result);
    }


}
