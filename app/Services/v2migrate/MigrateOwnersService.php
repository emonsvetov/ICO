<?php
namespace App\Services\v2migrate;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Exception;

use App\Services\v2migrate\MigrateSingleProgramsService;
use App\Services\v2migrate\MigrateAccountsService;
use App\Events\OrganizationCreated;
use App\Services\ProgramService;
use App\Models\Organization;
use App\Models\Program;


class MigrateOwnersService extends MigrationService
{
    private ProgramService $programService;

    public $offset = 0;
    public $limit = 9999;
    public $iteration = 0;
    public $count = 0;
    public bool $overwriteProgram = false;
    public int $importedProgramsCount = 0;
    public array $importedPrograms = [];
    public array $importMap = []; //This is the final map of imported objects with name is key. Ex. $importMap['program'][$v2_account_holder_id] = $v2ID;
    public array $cacheJournalEventsMap = [];
    public bool $isPrintSql = true;
    public $countPostings = 0;
    public $newPrograms = [];
    public $newProgramsCount = 0;
    public $superAdminsMigrated = false;

    public function __construct()
    {
        parent::__construct();
    }

    public function verifyOwner()  {
        $owner = \App\Models\Owner::join('account_holders', 'account_holders.id', '=', 'owners.account_holder_id')->where(['owners.id' => 1, 'account_holders.context' => 'Owner', 'owners.name' => 'Application Owner'])->first();

        if( !$owner ) {
            throw new Exception("Owner with id:1 does not exist");
            exit;
        }

        if( $owner ) {
            $accounts = \App\Models\Account::where('account_holder_id', $owner->account_holder_id)->get();
            if( !$accounts || count($accounts) < 2 )    {
                throw new Exception("Owner accounts do not exist. Please run `php artisan db:seed --class=OwnerSeeder` and try again.");
                exit;
            }
        }
    }

    public function migrate( $args = [] ) {

        $this->verifyOwner();
        $this->printf("Starting owner migration\n\n");

        (new \App\Services\v2migrate\MigrateAccountsService)->migrateOwnerAccounts();
        ## (new \App\Services\v2migrate\MigrateJournalEventsService)->migrateJournalEventsByV3Accounts('owners'); Do not pull owner journal events as those are spread among different programs. Journal events for Owner will be fetched when fetching for programs, user and merchants

        $this->printf("Migrating super admins, if any\n");
        $migrateUserService = app('App\Services\v2migrate\MigrateUsersService');
        $migrateUserService->migrateSuperAdmins();

        // $migrateUsersService = app('App\Services\v2migrate\MigrateUsersService');
        // $migrateAccountsService = app('App\Services\v2migrate\MigrateAccountsService');
        // $migrateJournalEventsService = app('App\Services\v2migrate\MigrateJournalEventsService');
        // $v3Model = \App\Models\User::find( 211 );
        // $v3Model = \App\Models\Program::find( 1 );
        // $migrateAccountsService->migrateByModel($v3Model);
        // $v2Model = $migrateUsersService->getV2UserById( 288496 );
        // $v2Model = $this->getV2ProgramById( 288308 );
        // $migrateJournalEventsService->migrateJournalEventsByModelAccounts($v3Model, $v2Model);
        // pr($this->importMap);

        // $v2User = $migrateUsersService->getV2UserById( 288496 );
        // $newUser = $migrateUsersService->migrateSingleUser( $v2User );
        // pr($v2User);
        // exit;
        // $migrateUsersService->migrateUserJournalEvents($v2User, $v3User);
        // exit;
    }
}
