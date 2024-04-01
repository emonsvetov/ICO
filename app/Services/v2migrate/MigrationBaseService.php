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
    private MigrateLeaderBoardsService $migrateLeaderBoardsService;
    private MigrateGoalPlansService $migrateGoalPlansService;
    private MigrateSocialWallPostService $migrateSocialWallPostService;
    private MigrateBudgetService $migrateBudgetService;
    private MigrateMerchantAccountsService $migrateMerchantAccountsService;
    private MigrateOwnerAccountsService $migrateOwnerAccountsService;
    private MigrateProgramMerchantService $migrateProgramMerchantService;

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
    const LEADERBOARDS= 'LeaderBoards';
    const GOALPLANS= 'Goal Plans';
    const MERCHANTS_AVAILABLE_GIFT_CODES = 'Merchants Available Gift Codes';
    const SOCIAL_WALL_POSTS = 'Social Wall Posts';
    const BUDGETS = 'Budgets';
    const MERCHANT_ACCOUNTS = 'Merchant Accounts';
    const OWNER_ACCOUNTS = 'Owner Accounts';
    const PROGRAM_MERCHANTS = 'Program Merchants';

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
        MigratePostingService $migratePostingService,
        MigrateLeaderBoardsService $migrateLeaderBoardsService,
        MigrateGoalPlansService $migrateGoalPlansService,
        MigrateSocialWallPostService $migrateSocialWallPostService,
        MigrateBudgetService $migrateBudgetService,
        MigrateMerchantAccountsService $migrateMerchantAccountsService,
        MigrateOwnerAccountsService $migrateOwnerAccountsService,
        MigrateProgramMerchantService $migrateProgramMerchantService
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
        $this->migrateLeaderBoardsService = $migrateLeaderBoardsService;
        $this->migrateGoalPlansService = $migrateGoalPlansService;
        $this->migrateSocialWallPostService = $migrateSocialWallPostService;
        $this->migrateBudgetService = $migrateBudgetService;
        $this->migrateMerchantAccountsService = $migrateMerchantAccountsService;
        $this->migrateOwnerAccountsService = $migrateOwnerAccountsService;
        $this->migrateProgramMerchantService = $migrateProgramMerchantService;
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
            self::MERCHANTS_AVAILABLE_GIFT_CODES,
        ];

        $arr = [];
        foreach ($migrations as $key => $migration) {
            $arr[$migration] = ['step' => $key + 1];
        }
        $migrations = $arr;

        $step = $args['step'];
        $nextStep = 0;

//        DB::beginTransaction();

        try {
            switch ($step) {
                case 'start':
                    $nextStep = 1;
                    break;

                case 1:
                    $result['migration'] = $this->migrateDomainsService->migrate();
                    break;

                case 2:
                    $result['migration'] = $this->migrateMerchantsService->migrate();
                    break;

                case 3:
                    $result['migration'] = $this->migrateMerchantsService->availableGiftCodes();
                    break;

                default:
                    $nextStep = 0;
                    break;

            }

            if ((int) $step < count($migrations)) {
                $nextStep = $step == 'start' ? 1 : $step + 1;
            }
            else {
                $nextStep = 0;
            }

//            DB::commit();
        } catch (Exception $e) {
            $result['success'] = FALSE;
            $result['nextStep'] = 0;
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
            self::PROGRAM_HIERARCHY,
            self::PROGRAM_ACCOUNTS,
            self::SYNC_EVENTS_TO_PROGRAM,
            self::USERS,
            self::USER_ACCOUNTS,
            self::OWNER_ACCOUNTS,
            self::USER_LOGS,
            self::PROGRAM_GIFT_CODES,
            self::SYNC_MERCHANTS_TO_PROGRAM,
            self::MERCHANT_ACCOUNTS,
            self::SYNC_DOMAINS_TO_PROGRAM,
            self::SYNC_AWARD_LEVELS_TO_PROGRAM,
            self::SYNC_PROGRAM_HIERARCHY_SETTINGS,
            self::EVENT_XML_DATA,
            self::PROGRAM_AND_USER_JOURNAL_EVENTS,
            self::PROGRAM_AND_USER_POSTINGS,
            self::SYNC_INVOICES_TO_PROGRAM,
            self::GOALPLANS,
            self::LEADERBOARDS,
            self::SOCIAL_WALL_POSTS,
            self::BUDGETS,
            self::PROGRAM_MERCHANTS,
        ];

        $arr = [];
        foreach ($migrations as $key => $migration) {
            $arr[$migration] = ['step' => $key + 1];
        }
        $migrations = $arr;

        $v2AccountHolderID = $args['v2AccountHolderID'] ?? null;
        $step = $args['step'] ?? 1;
        $nextStep = 0;

//        DB::beginTransaction();

        try {
            switch ($step) {
                case 'start':
                    $nextStep = 1;
                    break;

                case 1:
                     $result['migration'] = $this->migrateProgramsService->migrate($v2AccountHolderID);
                    break;

                case 2:
                     $result['migration'] = $this->migrateProgramAccountsService->migrate($v2AccountHolderID);
                    break;

                case 3:
                     $result['migration'] = $this->migrateEventService->migrate($v2AccountHolderID);
                    break;

                case 4:
                     $result['migration'] = $this->migrateUsersService->migrate($v2AccountHolderID);
                    break;

                case 5:
                     $result['migration'] = $this->migrateUserAccountsService->migrate($v2AccountHolderID);
                    break;

                case 6:
                     $result['migration'] = $this->migrateOwnerAccountsService->migrate();
                    break;

                case 7:
                     $result['migration'] = $this->migrateUserLogsService->migrate($v2AccountHolderID);
                    break;

                case 8:
                     $result['migration'] = $this->migrateProgramGiftCodesService->migrate($v2AccountHolderID);
                    break;

                case 9:
                     $result['migration'] = $this->migrateMerchantsService->syncProgramMerchantRelations($v2AccountHolderID);
                    break;

                case 10:
                     $result['migration'] = $this->migrateMerchantAccountsService->migrate();
                    break;

                case 11:
                     $result['migration'] = $this->migrateDomainsService->syncProgramDomainRelations($v2AccountHolderID);
                    break;

                case 12:
                     $result['migration'] = $this->migrateAwardLevelService->migrate($v2AccountHolderID);
                    break;

                case 13:
                     $result['migration'] = $this->migrateProgramAccountsService->syncProgramHierarchySettings($v2AccountHolderID);
                    break;

                case 14:
                     $result['migration'] = $this->migrateEventXmlDataService->migrate($v2AccountHolderID);
                    break;

                case 15:
                     $result['migration'] = $this->migrateJournalEventService->migrate($v2AccountHolderID);
                    break;

                case 16:
                     $result['migration'] = $this->migratePostingService->migrate($v2AccountHolderID);
                    break;

                case 17:
                     $result['migration'] = $this->migrateInvoiceService->migrate($v2AccountHolderID);
                    break;

                case 18:
                     $result['migration'] = $this->migrateGoalPlansService->migrate($v2AccountHolderID);
                    break;

                case 19:
                     $result['migration'] = $this->migrateLeaderBoardsService->migrate($v2AccountHolderID);
                    break;

                case 20:
                     $result['migration'] = $this->migrateSocialWallPostService->migrate($v2AccountHolderID);
                    break;

                case 21:
                     $result['migration'] = $this->migrateBudgetService->migrate($v2AccountHolderID);
                    break;

                case 22:
                    $result['migration'] = $this->migrateProgramMerchantService->migrate($v2AccountHolderID);
                    break;

                default:
                    $nextStep = 0;
                    break;
            }

            if ((int) $step < count($migrations)) {
                $nextStep = $step == 'start' ? 1 : $step + 1;
            }
            else {
                $nextStep = 0;
            }

//            DB::commit();
        } catch (Exception $e) {
            $result['success'] = FALSE;
            $result['nextStep'] = 0;
            $file = basename($e->getFile());
            $result['error'] = $e->getMessage(). ". File: {$file}" . ". Line: {$e->getLine()}";
//            DB::rollback();
        }

        $result['migrations'] = $migrations;
        $result['nextStep'] = $nextStep;
        return $result;
    }

}
