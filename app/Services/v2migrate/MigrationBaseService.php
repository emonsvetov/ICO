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
    private MigrateAwardLevelService $migrateAwardLevelService;
    private MigrateInvoiceService $migrateInvoiceService;
    private MigrateEventXmlDataService $migrateEventXmlDataService;
    private MigrateJournalEventService $migrateJournalEventService;
    private MigratePostingService $migratePostingService;

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
    const SYNC_AWARD_LEVELS_TO_PROGRAM = 'Award Levels';
    const SYNC_PROGRAM_HIERARCHY_SETTINGS = 'Sync program hierarchy settings';
    const SYNC_INVOICES_TO_PROGRAM = 'Sync invoices to a program';
    const EVENT_XML_DATA= 'Event Xml Data';
    const PROGRAM_AND_USER_JOURNAL_EVENTS= 'Program and User Journal Events';
    const PROGRAM_AND_USER_POSTINGS= 'Program and User Postings';

    public function __construct(
        MigrateMerchantsService $migrateMerchantsService,
        MigrateProgramsService $migrateProgramsService,
        MigrateProgramAccountsService $migrateProgramAccountsService,
        MigrateUsersService $migrateUsersService,
        MigrateUserAccountsService $migrateUserAccountsService,
        MigrateDomainsService $migrateDomainsService,
        MigrateUserLogsService $migrateUserLogsService,
        MigrateEventService $migrateEventService,
        MigrateProgramGiftCodesService $migrateProgramGiftCodesService,
        MigrateAwardLevelService $migrateAwardLevelService,
        MigrateInvoiceService $migrateInvoiceService,
        MigrateEventXmlDataService $migrateEventXmlDataService,
        MigrateJournalEventService $migrateJournalEventService,
        MigratePostingService $migratePostingService
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
        $this->migrateAwardLevelService = $migrateAwardLevelService;
        $this->migrateInvoiceService = $migrateInvoiceService;
        $this->migrateEventXmlDataService = $migrateEventXmlDataService;
        $this->migrateJournalEventService = $migrateJournalEventService;
        $this->migratePostingService = $migratePostingService;
    }

    /**
     * Run global migrations.
     */
    public function migrateGlobal($args)
    {
        $result = [];
        $result['success'] = TRUE;
        $result['error'] = NULL;
        $migrations = [
            self::MIGRATE_DOMAINS,
            self::MIGRATE_MERCHANTS,
        ];

        $arr = [];
        foreach ($migrations as $key => $migration) {
            $arr[$migration] = ['step' => $key + 1];
        }
        $migrations = $arr;

        $step = $args['step'] ?? 1;
        $nextStep = 0;


//        DB::beginTransaction();

        try {
            switch ($step) {
                case 'start':
                    $nextStep = 1;
                    break;

                case 1:
                    $result['migration'] = $this->migrateDomainsService->migrate();
                    $nextStep = $step + 1;
                    break;

                case 2:
                    $result['migration'] = $this->migrateMerchantsService->migrate();
                    $nextStep = $step + 1;
                    break;

                case 'stop':
                default:
                    $nextStep = 0;
                    break;

            }

//            DB::commit();
        } catch (Exception $e) {
            $result['success'] = FALSE;
            $result['nextStep'] = 'stop';
            $result['error'] = $e->getMessage();
//            DB::rollback();
        }

        $result['migrations'] = $migrations;
        $result['nextStep'] = $nextStep;
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
            self::PROGRAM_HIERARCHY => ['step' => 2],
            self::PROGRAM_ACCOUNTS => ['step' => 3],
            self::SYNC_EVENTS_TO_PROGRAM => ['step' => 4],
            self::USERS => ['step' => 5],
            self::USER_ACCOUNTS => ['step' => 6],
            self::USER_LOGS => ['step' => 7],
            self::PROGRAM_GIFT_CODES => ['step' => 8],
            self::SYNC_MERCHANTS_TO_PROGRAM => ['step' => 9],
            self::SYNC_DOMAINS_TO_PROGRAM => ['step' => 10],
            self::SYNC_AWARD_LEVELS_TO_PROGRAM => ['step' => 11],
            self::SYNC_PROGRAM_HIERARCHY_SETTINGS => ['step' => 12],
            self::EVENT_XML_DATA => ['step' => 13],
            self::PROGRAM_AND_USER_JOURNAL_EVENTS => ['step' => 14],
            self::PROGRAM_AND_USER_POSTINGS => ['step' => 15],
            self::SYNC_INVOICES_TO_PROGRAM => ['step' => 16],
        ];

        $v2AccountHolderID = $args['v2AccountHolderID'] ?? null;
        $step = $args['step'] ?? 1;

//        DB::beginTransaction();

        try {
            switch ($step) {
                case 1:
                    break;

                case 2:
                    $migrations[self::PROGRAM_HIERARCHY] = $this->migrateProgramsService->migrate($v2AccountHolderID);
                    break;

                case 3:
                    $migrations[self::PROGRAM_ACCOUNTS] = $this->migrateProgramAccountsService->migrate($v2AccountHolderID);
                    break;

                case 4:
                    $migrations[self::SYNC_EVENTS_TO_PROGRAM] = $this->migrateEventService->migrate($v2AccountHolderID);
                    break;

                case 5:
                    $migrations[self::USERS] = $this->migrateUsersService->migrate($v2AccountHolderID);
                    break;

                case 6:
                    $migrations[self::USER_ACCOUNTS] = $this->migrateUserAccountsService->migrate($v2AccountHolderID);
                    break;

                case 7:
                    $migrations[self::USER_LOGS] = $this->migrateUserLogsService->migrate($v2AccountHolderID);
                    break;

                case 8:
                    $migrations[self::PROGRAM_GIFT_CODES] = $this->migrateProgramGiftCodesService->migrate($v2AccountHolderID);
                    break;

                case 9:
                    $migrations[self::SYNC_AWARD_LEVELS_TO_PROGRAM] = $this->migrateAwardLevelService->migrate($v2AccountHolderID);
                    break;

                case 10:
                    $migrations[self::SYNC_MERCHANTS_TO_PROGRAM] = $this->migrateMerchantsService->syncProgramMerchantRelations($v2AccountHolderID);
                    break;

                case 11:
                    $migrations[self::SYNC_DOMAINS_TO_PROGRAM] = $this->migrateDomainsService->syncProgramDomainRelations($v2AccountHolderID);
                    break;

                case 12:
                    $migrations[self::SYNC_PROGRAM_HIERARCHY_SETTINGS] = $this->migrateProgramAccountsService->syncProgramHierarchySettings($v2AccountHolderID);
                    break;

                case 13:
                    $migrations[self::EVENT_XML_DATA] = $this->migrateEventXmlDataService->migrate($v2AccountHolderID);
                    break;

                case 14:
                    $migrations[self::PROGRAM_AND_USER_JOURNAL_EVENTS] = $this->migrateJournalEventService->migrate($v2AccountHolderID);
                    break;

                case 15:
                    $migrations[self::PROGRAM_AND_USER_POSTINGS] = $this->migratePostingService->migrate($v2AccountHolderID);
                    break;

                case 16:
                    $migrations[self::SYNC_INVOICES_TO_PROGRAM] = $this->migrateInvoiceService->migrate($v2AccountHolderID);
                    break;

//            DB::commit();
                default:
                    $step = 0;
                    break;
            }


//            $migrations[self::PROGRAM_HIERARCHY] = $this->migrateProgramsService->migrate($v2AccountHolderID);
//            $migrations[self::PROGRAM_ACCOUNTS] = $this->migrateProgramAccountsService->migrate($v2AccountHolderID);
//            $migrations[self::SYNC_EVENTS_TO_PROGRAM] = $this->migrateEventService->migrate($v2AccountHolderID);
//            $migrations[self::USERS] = $this->migrateUsersService->migrate($v2AccountHolderID);
//            $migrations[self::USER_ACCOUNTS] = $this->migrateUserAccountsService->migrate($v2AccountHolderID);
//            $migrations[self::USER_LOGS] = $this->migrateUserLogsService->migrate($v2AccountHolderID);
//            $migrations[self::PROGRAM_GIFT_CODES] = $this->migrateProgramGiftCodesService->migrate($v2AccountHolderID);
//            $migrations[self::SYNC_AWARD_LEVELS_TO_PROGRAM] = $this->migrateAwardLevelService->migrate($v2AccountHolderID);
//            $migrations[self::SYNC_MERCHANTS_TO_PROGRAM] = $this->migrateMerchantsService->syncProgramMerchantRelations($v2AccountHolderID);
//            $migrations[self::SYNC_DOMAINS_TO_PROGRAM] = $this->migrateDomainsService->syncProgramDomainRelations($v2AccountHolderID);
//            $migrations[self::SYNC_PROGRAM_HIERARCHY_SETTINGS] = $this->migrateProgramAccountsService->syncProgramHierarchySettings($v2AccountHolderID);
//            $migrations[self::EVENT_XML_DATA] = $this->migrateEventXmlDataService->migrate($v2AccountHolderID);
//            $migrations[self::PROGRAM_AND_USER_JOURNAL_EVENTS] = $this->migrateJournalEventService->migrate($v2AccountHolderID);
//            $migrations[self::PROGRAM_AND_USER_POSTINGS] = $this->migratePostingService->migrate($v2AccountHolderID);
//            $migrations[self::SYNC_INVOICES_TO_PROGRAM] = $this->migrateInvoiceService->migrate($v2AccountHolderID);


//            DB::commit();
        } catch (Exception $e) {
            $result['success'] = FALSE;
            $file = basename($e->getFile());
            $result['error'] = $e->getMessage(). ". File: {$file}" . ". Line: {$e->getLine()}";
//            DB::rollback();
        }

        $result['migrations'] = $migrations;
        $result['nextStep'] = $step ? ($step + 1) : 0;
        return $result;
    }

}
