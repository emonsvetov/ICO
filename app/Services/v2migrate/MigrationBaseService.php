<?php
namespace App\Services\v2migrate;

use App\Models\Program;
use Exception;
use Illuminate\Support\Facades\DB;

class MigrationBaseService extends MigrationService
{
    private MigrateMerchantsService $migrateMerchantsService;
    private MigrateProgramsService $migrateProgramsService;
    private MigrateProgramAccountsService $migrateProgramAccountsService;
    private MigrateUsersService $migrateUsersService;
    private MigrateDomainsService $migrateDomainsService;

    const SYNC_MERCHANTS_TO_PROGRAM = 'Sync merchants to a program';
    const SYNC_DOMAINS_TO_PROGRAM = 'Sync domains to a program';
    const MIGRATE_MERCHANTS = 'Migrate merchants';
    const PROGRAM_HIERARCHY = 'Program Hierarchy';
    const PROGRAM_ACCOUNTS = 'Program Accounts';
    const USERS = 'Users';
    const MIGRATE_DOMAINS = 'Migrate domains';

    public function __construct(
        MigrateMerchantsService $migrateMerchantsService,
        MigrateProgramsService $migrateProgramsService,
        MigrateProgramAccountsService $migrateProgramAccountsService,
        MigrateUsersService $migrateUsersService,
        MigrateDomainsService $migrateDomainsService
    )
    {
        $this->migrateMerchantsService = $migrateMerchantsService;
        $this->migrateProgramsService = $migrateProgramsService;
        $this->migrateProgramAccountsService = $migrateProgramAccountsService;
        $this->migrateUsersService = $migrateUsersService;
        $this->migrateDomainsService = $migrateDomainsService;
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
            self::MIGRATE_DOMAINS => FALSE,
        ];

        DB::beginTransaction();

        try {
            $migrations[self::MIGRATE_MERCHANTS] = $this->migrateMerchantsService->migrate();
            $migrations[self::MIGRATE_DOMAINS] = $this->migrateDomainsService->migrate();

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
     * Run migrations for a program.
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
            self::SYNC_DOMAINS_TO_PROGRAM => FALSE,
        ];

        $v2AccountHolderID = $args['v2AccountHolderID'] ?? null;

        DB::beginTransaction();

        try {
            $migrations[self::PROGRAM_HIERARCHY] = $this->migrateProgramsService->migrate($v2AccountHolderID);
            $migrations[self::PROGRAM_ACCOUNTS] = $this->migrateProgramAccountsService->migrate($v2AccountHolderID);
            $migrations[self::USERS] = $this->migrateUsersService->migrate($v2AccountHolderID);
            $migrations[self::SYNC_MERCHANTS_TO_PROGRAM] = $this->migrateMerchantsService->syncProgramMerchantRelations($v2AccountHolderID);
            $migrations[self::SYNC_DOMAINS_TO_PROGRAM] = $this->migrateDomainsService->syncProgramDomainRelations($v2AccountHolderID);

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
