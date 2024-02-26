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
    private MigrateUserAccountsService $migrateUserAccountsService;
    private MigrateUserLogsService $migrateUserLogsService;
    private $migrateEventService;
    private MigrateProgramGiftCodesService $migrateProgramGiftCodesService;

    const SYNC_MERCHANTS_TO_PROGRAM = 'Sync merchants to a program';
    const SYNC_DOMAINS_TO_PROGRAM = 'Sync domains to a program';
    const MIGRATE_MERCHANTS = 'Migrate merchants';
    const PROGRAM_HIERARCHY = 'Program Hierarchy';
    const PROGRAM_ACCOUNTS = 'Program Accounts';
    const USERS = 'Users';
    const MIGRATE_DOMAINS = 'Migrate domains';
    const USER_ACCOUNTS = 'User Accounts';
    const USER_LOGS = 'User Logs';
    const SYNC_EVENTS_TO_PROGRAM = 'Sync events to a program';
    const PROGRAM_GIFT_CODES = 'Program Gift Codes';

    public function __construct(
        MigrateMerchantsService $migrateMerchantsService,
        MigrateProgramsService $migrateProgramsService,
        MigrateProgramAccountsService $migrateProgramAccountsService,
        MigrateUsersService $migrateUsersService,
        MigrateUserAccountsService $migrateUserAccountsService,
        MigrateDomainsService $migrateDomainsService,
        MigrateUserLogsService $migrateUserLogsService,
        MigrateEventService $migrateEventService,
        MigrateProgramGiftCodesService $migrateProgramGiftCodesService
    )
    {
        $this->migrateMerchantsService = $migrateMerchantsService;
        $this->migrateProgramsService = $migrateProgramsService;
        $this->migrateProgramAccountsService = $migrateProgramAccountsService;
        $this->migrateUsersService = $migrateUsersService;
        $this->migrateUserAccountsService = $migrateUserAccountsService;
        $this->migrateUserLogsService = $migrateUserLogsService;
        $this->migrateDomainsService = $migrateDomainsService;
        $this->migrateEventService = $migrateEventService;
        $this->migrateProgramGiftCodesService = $migrateProgramGiftCodesService;
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
            self::SYNC_EVENTS_TO_PROGRAM => FALSE,
            self::USERS => FALSE,
            self::USER_ACCOUNTS => FALSE,
            self::USER_LOGS => FALSE,
            self::PROGRAM_GIFT_CODES => FALSE,
            self::SYNC_MERCHANTS_TO_PROGRAM => FALSE,
            self::SYNC_DOMAINS_TO_PROGRAM => FALSE,
        ];

        $v2AccountHolderID = $args['v2AccountHolderID'] ?? null;

        DB::beginTransaction();

        try {
            $migrations[self::PROGRAM_HIERARCHY] = $this->migrateProgramsService->migrate($v2AccountHolderID);
            $migrations[self::PROGRAM_ACCOUNTS] = $this->migrateProgramAccountsService->migrate($v2AccountHolderID);
            $migrations[self::SYNC_EVENTS_TO_PROGRAM] = $this->migrateEventService->syncProgramEventsRelations($v2AccountHolderID);
            $migrations[self::USERS] = $this->migrateUsersService->migrate($v2AccountHolderID);
            $migrations[self::USER_ACCOUNTS] = $this->migrateUserAccountsService->migrate($v2AccountHolderID);
            $migrations[self::USER_LOGS] = $this->migrateUserLogsService->migrate($v2AccountHolderID);
            $migrations[self::PROGRAM_GIFT_CODES] = $this->migrateProgramGiftCodesService->migrate($v2AccountHolderID);
            $migrations[self::SYNC_MERCHANTS_TO_PROGRAM] = $this->migrateMerchantsService->syncProgramMerchantRelations($v2AccountHolderID);
            $migrations[self::SYNC_DOMAINS_TO_PROGRAM] = $this->migrateDomainsService->syncProgramDomainRelations($v2AccountHolderID);

            DB::commit();
        } catch (Exception $e) {
            $result['success'] = FALSE;
            $file = basename($e->getFile());
            $result['error'] = $e->getMessage(). ". File: {$file}" . ". Line: {$e->getLine()}";
            DB::rollback();
        }

        $result['migrations'] = $migrations;
        return $result;
    }

}
