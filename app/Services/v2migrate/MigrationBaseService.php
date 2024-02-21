<?php
namespace App\Services\v2migrate;

use App\Models\Program;
use Exception;
use Illuminate\Support\Facades\DB;

class MigrationBaseService extends MigrationService
{
    private $migrateMerchantsService;
    private MigrateProgramsService $migrateProgramsService;
    private MigrateProgramAccountsService $migrateProgramAccountsService;
    private MigrateUsersService $migrateUsersService;

    const SYNC_MERCHANTS_TO_PROGRAM = 'Sync merchants to a program';
    const MIGRATE_MERCHANTS = 'Migrate merchants';
    const PROGRAM_HIERARCHY = 'Program Hierarchy';
    const PROGRAM_ACCOUNTS = 'Program Accounts';
    const USERS = 'Users';

    public function __construct(
        MigrateMerchantsService $migrateMerchantsService,
        MigrateProgramsService $migrateProgramsService,
        MigrateProgramAccountsService $migrateProgramAccountsService,
        MigrateUsersService $migrateUsersService
    )
    {
        $this->migrateMerchantsService = $migrateMerchantsService;
        $this->migrateProgramsService = $migrateProgramsService;
        $this->migrateProgramAccountsService = $migrateProgramAccountsService;
        $this->migrateUsersService = $migrateUsersService;
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
            self::PROGRAM_HIERARCHY => FALSE,
            self::PROGRAM_ACCOUNTS => FALSE,
            self::USERS => FALSE,
            self::SYNC_MERCHANTS_TO_PROGRAM => FALSE,
        ];

        $v2AccountHolderID = $args['v2AccountHolderID'] ?? null;

        DB::beginTransaction();

        try {
            $migrations[self::PROGRAM_HIERARCHY] = (bool)$this->migrateProgramsService->migrate($v2AccountHolderID);
            $migrations[self::PROGRAM_ACCOUNTS] = (bool)$this->migrateProgramAccountsService->migrate($v2AccountHolderID);
            $migrations[self::USERS] = (bool)$this->migrateUsersService->migrate($v2AccountHolderID);
            $migrations[self::SYNC_MERCHANTS_TO_PROGRAM] = $this->migrateMerchantsService->syncProgramMerchantRelations($v2AccountHolderID);

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
