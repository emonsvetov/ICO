<?php
namespace App\Services\v2migrate;

use App\Models\Program;
use Exception;
use Illuminate\Support\Facades\DB;

class MigrationBaseService extends MigrationService
{
    private $migrateMerchantsService;

    const SYNC_MERCHANTS_TO_PROGRAM = 'Sync merchants to a program';
    const MIGRATE_MERCHANTS = 'Migrate merchants';

    public function __construct(MigrateMerchantsService $migrateMerchantsService)
    {
        $this->migrateMerchantsService = $migrateMerchantsService;
    }

    /**
     * Run global migrations.
     */
    public function migrateGlobal()
    {
        $result = [];
        $result['success'] = TRUE;
        $result['error'] = NULL;
        $migrations = [
            self::MIGRATE_MERCHANTS => FALSE,
        ];

        DB::beginTransaction();

        try {
            $migrations[self::MIGRATE_MERCHANTS] = $this->migrateMerchantsService->migrate();

            DB::commit();
        } catch (Exception $e) {
            $result['success'] = FALSE;
            $result['error'] = $e->getMessage();
            DB::rollback();
        }

        $result['migrations'] = $migrations;
        return $result;
    }

    /**
     * Run migration for a program.
     */
    public function migrate($args)
    {
        $result = [];
        $result['success'] = TRUE;
        $result['error'] = NULL;
        $migrations = [
            self::SYNC_MERCHANTS_TO_PROGRAM => FALSE,
        ];

        $v2AccountHolderID = $args['v2AccountHolderID'];
        $v3Program = Program::where('v2_account_holder_id', $v2AccountHolderID)->first();

        $v3AccountHolderID = $v3Program->account_holder_id ?? NULL;
        DB::beginTransaction();

        try {

            $migrations[self::SYNC_MERCHANTS_TO_PROGRAM] = $this->migrateMerchantsService->syncProgramMerchantRelations($v2AccountHolderID, $v3AccountHolderID);


            DB::commit();
        } catch (Exception $e) {
            $result['success'] = FALSE;
            $result['error'] = $e->getMessage();
            DB::rollback();
        }

        $result['migrations'] = $migrations;
        return $result;
    }

}
