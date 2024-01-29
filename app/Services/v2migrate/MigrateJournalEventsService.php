<?php
namespace App\Services\v2migrate;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Exception;

use App\Models\AccountHolder;
use App\Models\EventXmlData;
use App\Models\JournalEvent;
use App\Models\Merchant;
use App\Models\Program;
use App\Models\Account;
use App\Models\Owner;
use App\Models\User;


class MigrateJournalEventsService extends MigrationService
{
    public $offset = 0;
    public $limit = 1;
    public $iteration = 0;
    public $count = 0;
    public bool $isPrintSql = false;
    public bool $resycnRoles = true;
    public array $v3UserCache = [];
    public $v2User = null;
    public $v3User = null;
    private $localImportMap = [];
    public array $cacheJournalEventsMap = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function migrate()  {
        $this->v2db->statement("SET SQL_MODE=''");
        // $this->migrateUserRoleService->migrate();
        // $this->migrateNonDuplicateUsers();
        $this->offset = $this->iteration = 0;
        $this->setDebug(true);
    }

    /**
     * This creates $sql query
     * To migrate journals for a merchant we must need to know bind prime_account_holder_id
     * @param account_holder_ids (array)
     * @param - v2_account_holder_id (int)
     * @param - v2_account_id (int)
     */

    public function buildMerchantQuery($account_holder_ids = [], $v2_account_holder_id, $v2_account_id) {
        if( !$account_holder_ids || !$v2_account_holder_id || !$v2_account_id) {
            throw new Exception("One or more invalid arguments passed");
        }
        $selectColumns = "postings.*, users.account_holder_id AS user_account_holder_id, users.v3_user_id, je.prime_account_holder_id, je.journal_event_timestamp, je.journal_event_type_id, je.notes, je.invoice_id, je.event_xml_data_id, je.parent_journal_event_id, je.is_read, je.v3_journal_event_id";
        $sql = sprintf("SELECT %s FROM accounts JOIN postings on postings.account_id=accounts.id JOIN journal_events je ON je.id=postings.journal_event_id LEFT JOIN users on users.account_holder_id=je.prime_account_holder_id WHERE accounts.account_holder_id=%d AND accounts.id=%d AND je.prime_account_holder_id IN(%s) ORDER BY je.journal_event_timestamp ASC, postings.posting_timestamp ASC;", $selectColumns, $v2_account_holder_id, $v2_account_id,  implode(',', $account_holder_ids));
        // pr($sql);
        //
        return $sql;
    }

    public function migrateMerchantsJournalEvents() {
        $v3MerchantAccounts = Account::join('merchants', 'merchants.account_holder_id', '=', 'accounts.account_holder_id')->whereNotNull('accounts.v2_account_id')->select(["accounts.*", 'merchants.id AS merchant_id', 'merchants.v2_account_holder_id AS v2_merchant_account_holder_id'])->get();
        // pr(count($v3MerchantAccounts));
        //
        if(count($v3MerchantAccounts)) {
            foreach($v3MerchantAccounts as $v3MerchantAccount)  {
                // $v3Merchant = new \App\Models\Merchant([
                //     'id' => $v3MerchantAccount['merchant_id'],
                //     'account_holder_id' => $v3MerchantAccount['account_holder_id'],
                //     'v2_account_holder_id' => $v3MerchantAccount['v2_merchant_account_holder_id'],
                // ]);
                $v3Merchant = \App\Models\Merchant::find( $v3MerchantAccount['merchant_id'] );
                if( $v3Merchant ) {
                    $this->migrateJournalEventsByModelAndAccount($v3Merchant, $v3MerchantAccount);
                }
            }
        }
    }

    public function migrateProgramsJournalEvents() {
        $v3ProgramAccounts = Account::join('programs', 'programs.account_holder_id', '=', 'accounts.account_holder_id')->whereNotNull('accounts.v2_account_id')->select(["accounts.*", 'programs.id AS program_id', 'programs.v2_account_holder_id AS v2_program_account_holder_id'])->get();
        // pr(count($v3ProgramAccounts));
        // pr($v3ProgramAccounts->toArray());
        //
        if(count($v3ProgramAccounts)) {
            foreach($v3ProgramAccounts as $v3ProgramAccount)  {
                // $v3Program = new \App\Models\Program([
                //     'id' => $v3ProgramAccount['program_id'],
                //     'account_holder_id' => $v3ProgramAccount['account_holder_id'],
                //     'v2_account_holder_id' => $v3ProgramAccount['v2_program_account_holder_id'],
                // ]);
                $v3Program = \App\Models\Program::find( $v3ProgramAccount['program_id']);
                if( $v3Program ) {
                    $this->migrateJournalEventsByModelAndAccount($v3Program, $v3ProgramAccount);
                }
            }
        }
    }

    public function migrateUsersJournalEvents() {
        // DB::enableQueryLog();
        DB::statement("SET SQL_MODE=''"); //Fix for groupBy error!
        $query = Account::join('users', 'users.account_holder_id', '=', 'accounts.account_holder_id')->join('user_v2_users', 'user_v2_users.user_id', '=', 'users.id')->join('account_v2_accounts AS a2a', 'a2a.account_id', '=', 'accounts.id')->select(['accounts.*', 'users.id AS user_id', 'user_v2_users.v2_user_account_holder_id', 'a2a.v2_account_id as a2a_v2_account_id']);
        // $query->where('users.id', 2520);
        // $query->groupBy('a2a_v2_account_id');
        $query->groupBy('accounts.id');
        $query->orderBy('accounts.id');
        $v3UserAccounts = $query->get(); //326 ach for 2520
        // select `accounts`.*, `users`.`id` as `user_id`, `users`.`v2_account_holder_id` as `v2_user_account_holder_id`  from `accounts` inner join `users` on `users`.`account_holder_id` = `accounts`.`account_holder_id` INNER JOIN user_v2_users ON users.id=user_v2_users.user_id GROUP BY accounts.id
        // pr(toSql(DB::getQueryLog()));
        // // pr(count($v3UserAccounts));
        // pr($v3UserAccounts->toArray());
        // // pr("Hell");
        //
        if(count($v3UserAccounts)) {
            foreach($v3UserAccounts as $v3UserAccount)  {
                // $v3User = new \App\Models\User([
                //     'id' => $v3UserAccount['user_id'],
                //     'account_holder_id' => $v3UserAccount['account_holder_id'],
                //     'v2_account_holder_id' => $v3UserAccount['v2_user_account_holder_id'],
                // ]);
                $v3User = \App\Models\User::find($v3UserAccount['user_id']);
                if( $v3User ) {
                    $this->migrateJournalEventsByModelAndAccount($v3User, $v3UserAccount);
                }
            }
        }
    }

    public function migrateJournalEventsByV3Accounts( $type = null ) {

        $type = is_array($type) ? $type : (is_string($type) ? [$type] : null);

        if( !sizeof( $type ) ) {
            $type = ['all'];
        }

        if(in_array('merchants', $type))    {
            $this->migrateMerchantsJournalEvents();
        }

        if(in_array('programs', $type))    {
            $this->migrateProgramsJournalEvents();
        }

        if(in_array('users', $type))    {
            $this->migrateUsersJournalEvents();
        }

        if(in_array('owners', $type))    {
            $this->migrateOwnersJournalEvents();
        }
    }

    /***
     * Imports Owner journal events
     * This is important to run since merchant's transactions are performed as account_holder_id=1 which is the system Owner
     * IMPORTANT: Before running this function "migrateOwnerAccounts" should have already been run otherwise it will not yeild anything
     */

    public function migrateOwnersJournalEvents()    {
        $v3OwnerAccounts = Account::join('owners', 'owners.account_holder_id', '=', 'accounts.account_holder_id')->whereNotNull('accounts.v2_account_id')->select(["accounts.*", 'owners.id AS owner_id'])->get();
        if(count($v3OwnerAccounts)) {
            $this->printf("%d owner accounts found. Migrating JournalEvents..\n", count($v3OwnerAccounts));
            foreach($v3OwnerAccounts as $v3OwnerAccount)  {
                $v3Owner = new \App\Models\Owner([
                    'id' => $v3OwnerAccount['owner_id'],
                    'account_holder_id' => $v3OwnerAccount['account_holder_id'],
                    'v2_account_holder_id' => 1,
                ]);
                $this->printf("Migrating JournalEvents for v2Owner:%d....\n", $v3Owner->id);
                $this->migrateJournalEventsByModelAndAccount($v3Owner, $v3OwnerAccount);
            }
        }
    }

    /***
     * Imports journal_events, postings, xml data etc via accounts, which seems like
     * the actual approach to import journal events and related data
     *
     * IMPORTANT: Before running it, do make sure that all "Accounts" have been a given model
     * using "MigrateAccountsService"
     *
     * @param $v3Model full v3 mode instance
     * @param $v2Model, optional - if null may need to create via v2_account_holder_id of v3 model
     * @return -
     */

    public function migrateJournalEventsByModelAndAccount(Owner|Program|User|Merchant $v3Model, Account $v3Account) {
        // pr($v3Model->toArray());
        // pr($v3Model->toArray());
        // pr($v3Model instanceof Merchant);
        // buildMerchantQuery

        if( $v3Model instanceof User)   {
            $v2_users = $v3Model->v2_users()->pluck('v2_user_account_holder_id');
            if( !$v2_users ) {
                throw new Exception(sprintf("No v2_users for v3User:%s. Are you sure this user was imported properly?\"\n", $v3Model->id));
            }
        }   else {
            if( !$v3Model->v2_account_holder_id || !$v3Account->v2_account_id)    {
                throw new Exception(sprintf("Invalid \"v2_account_holder_id\" in model \"%s\" or account. Are you sure this model and account was imported properly?\"\n\n", $this->modelName));
            }
        }

        if( $v3Model instanceof Merchant)   {

            //For merchants we need to get all postings by Owner (account_holder_id=1)
            // pr($v3Model->v2_account_holder_id);
            // pr($v3Account->v2_account_id);
            $sql = $this->buildMerchantQuery([1], $v3Model->v2_account_holder_id, $v3Account->v2_account_id);

            $results = $this->v2db->select($sql);
            if( $results )  {
                $count = 0;
                $this->countPostings += count($results);
                foreach( $results as $row) {
                    $this->migrateSingleJournalEventByPostingData( (object) $row );
                    $count++;
                }
            }

            // $this->printf($this->countPostings . " posts imported\n\n");

            //If migration is to be done for merchants then we need to limit the postings by existing account_holders in v3 so that we do not pull data for non existent users in v3
            $users = AccountHolder::join('accounts', 'accounts.account_holder_id', '=', 'account_holders.id')->join('users', 'users.account_holder_id', '=', 'account_holders.id')->join('user_v2_users', 'user_v2_users.user_id', '=', 'users.id')->where('account_holders.context', '=', 'User')->select('user_v2_users.v2_user_account_holder_id AS v2_account_holder_id')->groupBy('user_v2_users.v2_user_account_holder_id')->get();
            if( $users ) {
                // $account_holder_ids = $users->pluck('account_holder_id');
                $accountHolderIds = $users->pluck('v2_account_holder_id');
                // pr($accountHolderIds);
                if( $accountHolderIds ) {
                    $sql = $this->buildMerchantQuery($accountHolderIds->toArray(), $v3Model->v2_account_holder_id, $v3Account->v2_account_id);
                    $results = $this->v2db->select($sql);
                    if( $results )  {
                        $count = 0;
                        $this->countPostings += count($results);
                        foreach( $results as $row) {
                            $this->migrateSingleJournalEventByPostingData( (object) $row );
                            $count++;
                        }
                    }
                }
            }
        }   else {
            // $sql = sprintf("SELECT postings.id AS posting_id, postings.*, je.*, users.account_holder_id AS user_account_holder_id, users.v3_user_id FROM accounts JOIN postings on postings.account_id=accounts.id JOIN journal_events je ON je.id=postings.journal_event_id LEFT JOIN users on users.account_holder_id=je.prime_account_holder_id WHERE accounts.account_holder_id = %d AND accounts.id=%d ORDER BY je.journal_event_timestamp ASC, postings.posting_timestamp ASC", $v3Model->v2_account_holder_id, $v3Account->v2_account_id);

            if( $v3Model instanceof User )  {
                // pr($v3Model->id);
                $in__v2_account_holder_id_col = $v3Model->v2_users()->pluck('v2_user_account_holder_id');
                if( !$in__v2_account_holder_id_col ) {
                    throw new Exception(" - empty 'v2_user_account_holder_id'");
                }
                $in__v2_account_holder_id = implode(',', $in__v2_account_holder_id_col->toArray());
                pr($in__v2_account_holder_id);
                // pr($in__v2_account_holder_id->toArray());
                //
                $query = Account::join('users', 'users.account_holder_id', '=', 'accounts.account_holder_id')->join('user_v2_users', 'user_v2_users.user_id', '=', 'users.id')->join('account_v2_accounts AS a2a', 'a2a.account_id', '=', 'accounts.id')->select(['accounts.*', 'users.id AS user_id', 'user_v2_users.v2_user_account_holder_id', 'a2a.v2_account_id as a2a_v2_account_id']);
                $query->where('users.id', $v3Model->id);
                $query->groupBy('a2a_v2_account_id');
                // $query->groupBy('accounts.id');
                // $query->orderBy('accounts.id');
                $v3UserAccounts = $query->get(); //326 ach for 2520
                $in__v2_account_id_col = $v3UserAccounts->pluck('a2a_v2_account_id');
                if( !$in__v2_account_id_col ) {
                    throw new Exception(" - empty 'in__v2_account_holder_id'");
                }
                $in__v2_account_id = implode(',', $in__v2_account_id_col->toArray());
                pr($in__v2_account_id);
            }   else {
                $in__v2_account_holder_id = [$v3Model->v2_account_holder_id];
                $in__v2_account_id = [$v3Model->v2_account_id];
            }

            if( !$in__v2_account_holder_id || !$in__v2_account_id) {
                throw new Exception(" - empty 'in__v2_account_holder_id' or 'in__v2_account_id'");
            }

            // pr($in__v2_account_holder_id);
            //

            //Get a few fields only. Keeping above for debugging.
            $sql = "SELECT postings.id AS posting_id, postings.journal_event_id, je.prime_account_holder_id, je.journal_event_timestamp, je.journal_event_type_id, je.notes, je.invoice_id, je.event_xml_data_id, je.parent_journal_event_id, je.is_read, je.v3_journal_event_id, users.account_holder_id AS user_account_holder_id, users.v3_user_id FROM accounts JOIN postings on postings.account_id=accounts.id JOIN journal_events je ON je.id=postings.journal_event_id LEFT JOIN users on users.account_holder_id=je.prime_account_holder_id WHERE accounts.account_holder_id IN ($in__v2_account_holder_id) AND accounts.id IN ($in__v2_account_id) ORDER BY je.journal_event_timestamp ASC, postings.posting_timestamp ASC";

            $this->printf(" - Fetching journal_events+postings for model %s:\"%s\"\n", $this->modelName, $v3Model->id);
            $this->printSql($sql . "\n\n");
            //
            $results = $this->v2db->select($sql);

            // $this->printf($sql . "\n\n");

            if( $results )  {
                // pr(count($results));
                //
                $this->printf("%d journal_events+postings found.\n", count($results));
                $this->countPostings += count($results);
                foreach( $results as $row) {
                    // if($row->prime_account_holder_id != 286143) continue;
                    $this->printf(" - Migrating posting_id:%d\n", $row->posting_id);
                    $this->migrateSingleJournalEventByPostingData( (object) $row );
                }
            }   else {
                $this->printf(" -- No journal_events+postings found.. next..\n\n");
            }
        }
    }

    public function migrateSingleJournalEventByPostingData( $postingData )    {
        $params = [];
        if( $postingData )  {
            if( $postingData->v3_user_id ) {
                $params['v3_prime_account_holder_id'] = $postingData->v3_user_id;
            }
            $v2JournalEvent = [
                'id' => $postingData->journal_event_id,
                'prime_account_holder_id' => $postingData->prime_account_holder_id,
                'journal_event_timestamp' => $postingData->journal_event_timestamp,
                'journal_event_type_id' => $postingData->journal_event_type_id,
                'notes' => $postingData->notes,
                'invoice_id' => $postingData->invoice_id,
                'event_xml_data_id' => $postingData->event_xml_data_id,
                'parent_journal_event_id' => $postingData->parent_journal_event_id,
                'is_read' => $postingData->is_read,
                'v3_journal_event_id' => $postingData->v3_journal_event_id
            ];

            $this->migrateSingleJournalEvent((object) $v2JournalEvent, $params);
        }
    }

    public function migrateJournalEventsByModelAccounts( Owner|Program|User|Merchant $v3Model, $v2Model = null )    {

        $this->setRefByModel($v3Model, $v2Model);

        if( $v3Model instanceof User)   {
            $userV2user = $v3Model->v2_users()->where('v2_user_account_holder_id', $v2Model->account_holder_id)->first();
            if( !$userV2user ) {
                throw new Exception(sprintf("Invalid \"userV2user\". Are you sure this user was imported properly?\"\n"));
            }
        } else {
            if( !$v3Model->v2_account_holder_id )    {
                throw new Exception(sprintf("Invalid \"v2_account_holder_id\" in model \"%s\". Are you sure this model was imported properly?\"\n", $this->modelName));
            }
            $accounts = \App\Models\Account::where('account_holder_id', $v3Model->account_holder_id)->get();

            if( count($accounts) > 0 )  {
                foreach( $accounts as $v3Account)    {
                    if( $v3Account->v2_account_id )  {
                        $this->migrateJournalEventsByModelAndAccount($v3Model, $v3Account);
                    }
                }
            }
        }
    }

    public function migrateSingleJournalEvent($v2JournalEvent, $params = []) {
        $create = true;
        $v2Model = $this->v2Model; //optional
        $v3Model = $this->v3Model; //optional

        // pr($row);
        if( $v2JournalEvent->v3_journal_event_id )    { //find by v3 id
            $v3JournalEvent = JournalEvent::find( $v2JournalEvent->v3_journal_event_id );
            // pr($existing->toArray());
            if( $v3JournalEvent )   {
                $this->printf(" - Journal Event \"%d\" exists for v2:%d\n", $v3JournalEvent->id, $v2JournalEvent->id);
                if( !$v3JournalEvent->v2_journal_event_id ) { //patch missing link
                    $v3JournalEvent->v2_journal_event_id = $v2JournalEvent->id;
                    $v3JournalEvent->save();
                }
                $create = false;
                //Update??
                $this->localImportMap['journalEvents'][$v3JournalEvent->id]['exists'] = 1;
            }
        }   else {
            //find by v2 id
            $v3JournalEvent = JournalEvent::where('v2_journal_event_id', $v2JournalEvent->id )->first();
            if( $v3JournalEvent )   {
                $this->printf(" - Journal Event \"%d\" exists for v2: \"%d\", found via v2_journal_event_id search. Updating null v3_journal_event_id value.\n", $v3JournalEvent->id, $v2JournalEvent->v3_journal_event_id, $v2JournalEvent->id);
                //Patch link since missing
                $this->addV2SQL(sprintf("UPDATE `journal_events` SET `v3_journal_event_id`=%d WHERE `id`=%d", $v3JournalEvent->id, $v2JournalEvent->id));
                $create = false;
                $this->localImportMap['journalEvents'][$v3JournalEvent->id]['exists'] = 1;
                //Update??
            }
        }
        $newV3JournalEvent = null;
        if( $create )   {
            $parent_journal_event_id = null;
            $prime_account_holder_id = 0;

            //Get v3 prime_account_holder_id
            if( !empty($params['v3_prime_account_holder_id']) )  {
                $prime_account_holder_id = $params['v3_prime_account_holder_id'];
            }   else {
                if( (int) $v2JournalEvent->prime_account_holder_id > 0 )  {
                    if( $v2Model && $v3Model && $v3Model instanceof \App\Models\User  &&$v2Model->account_holder_id == $v2JournalEvent->prime_account_holder_id) {
                        $prime_account_holder_id = $v3Model->account_holder_id;
                    }   else {
                        $modelTmp = User::where('v2_account_holder_id', $v2JournalEvent->prime_account_holder_id)->first();
                        if( $modelTmp )  {
                            $prime_account_holder_id = $modelTmp->account_holder_id;
                        }   else {
                            $prime_account_holder_id = ($v2JournalEvent->prime_account_holder_id == 1) ? 1 : ($this->idPrefix . $v2JournalEvent->prime_account_holder_id);
                            //The Owner's account_holder_id 1 and it should remain same on v2 and v3.
                        }
                    }
                }
            }

            //Get v3 parent_journal_event_id
            if( (int) $v2JournalEvent->parent_journal_event_id > 0 )  {
                $v3ParentTmp = JournalEvent::where('v2_journal_event_id', $v2JournalEvent->parent_journal_event_id)->first();
                if( $v3ParentTmp )  $parent_journal_event_id = $v3ParentTmp->id;
                else   $parent_journal_event_id = $this->idPrefix . $v2JournalEvent->parent_journal_event_id;
            }

            $v3_event_xml_data_id = null;
            if( $v2JournalEvent->event_xml_data_id > 0) {
                $v3EventXmlData =\App\Models\EventXmlData::where('v2_id', $v2JournalEvent->event_xml_data_id)->first();
                if( $v3EventXmlData )   {
                    $v3_event_xml_data_id = $v3EventXmlData->id;
                }   else {
                    //create placeholder, to be replaced later
                    $v3_event_xml_data_id = $this->idPrefix . $v2JournalEvent->event_xml_data_id;
                }
            }

            $newV3JournalEvent = JournalEvent::create(
                [
                    'v2_journal_event_id' => $v2JournalEvent->id,
                    'prime_account_holder_id' => $prime_account_holder_id,
                    // 'v2_prime_account_holder_id' => $v2JournalEvent->prime_account_holder_id,
                    'journal_event_type_id' => $v2JournalEvent->journal_event_type_id,
                    'notes' => $v2JournalEvent->notes,
                    'event_xml_data_id' => $v3_event_xml_data_id,
                    'invoice_id' => $v2JournalEvent->invoice_id, //it is always null in v2, so I guess it is not in use and was replaced by invoice_journal_events table
                    'parent_journal_event_id' => $parent_journal_event_id,
                    // 'v2_parent_journal_event_id' => $v2JournalEvent->parent_journal_event_id,
                    'is_read' => $v2JournalEvent->is_read,
                    'created_at' => $v2JournalEvent->journal_event_timestamp
                ]
            );

            if( $newV3JournalEvent ) {
                printf(" - New Journal Event \"%d\" created for v2 journal event \"%d\"\n",$newV3JournalEvent->id, $v2JournalEvent->id);
                $this->v2db->statement(sprintf("UPDATE `journal_events` SET `v3_journal_event_id`=%d WHERE `id`=%d", $newV3JournalEvent->id, $v2JournalEvent->id));
                $this->localImportMap['journalEvents'][$newV3JournalEvent->id]['imported'] = 1;
            }
        }

        $v3JournalEvent = $v3JournalEvent ? $v3JournalEvent : $newV3JournalEvent;

        if( !$create )  {
            //Patch previously imported journals
            $saveJournal = false;
            $saveEventXml = false;

            if( (int) $v2JournalEvent->event_xml_data_id > 0 )   {
                $importXml = false;

                $v2_event_xml_data_id = 0;
                if( $v2JournalEvent->event_xml_data_id == $v3JournalEvent->event_xml_data_id) {
                    //The v2 id was imported as it is to v3, need to fix
                    $importXml = true;
                }   else if( strpos($v3JournalEvent->event_xml_data_id, $this->idPrefix) == 0 && (strlen($v3JournalEvent->event_xml_data_id) > strlen($this->idPrefix)) ) {
                    //The id was creaetd using placeholder perfix
                    $importXml = true;
                }
                // pr($v2JournalEvent->event_xml_data_id);
                //confirm that the row does not exist
                $v3EventXmlData = EventXmlData::where('v2_id', $v2JournalEvent->event_xml_data_id)->first();
                if( $v3EventXmlData ) {
                    $importXml = false;
                    // //nothing else, but later!
                }
                if( $importXml ) { //create
                    $sql = sprintf("SELECT * FROM `event_xml_data` WHERE `id`=%d", $v2JournalEvent->event_xml_data_id);
                    $results = $this->v2db->select($sql);
                    if( sizeof($results) > 0 )  {
                        $v2EventXmlData = current($results); //just one!

                        //Prepare xmlData

                        //Get Awarderer
                        if( (int) $v2EventXmlData->awarder_account_holder_id > 0 )  {
                            if( $v2Model && $v3Model && $v3Model instanceof \App\Models\User && $v2EventXmlData->awarder_account_holder_id == $v2Model->account_holder_id) {
                                $awarder_account_holder_id = $v3Model->account_holder_id;
                            }   else {
                                $v3User_tmp = User::where('v2_account_holder_id', $v2EventXmlData->awarder_account_holder_id)->first();
                                if( $v3User_tmp )   {
                                    $awarder_account_holder_id = $v3User_tmp->account_holder_id;
                                }   else {
                                    //User not imported, but we wont import it here otherwise we fall in a recursive loop, rather save user id with prefix of 999999999 so we can later pull it if required.
                                    $awarder_account_holder_id = (int) ($this->idPrefix.$v2EventXmlData->awarder_account_holder_id);
                                }
                            }
                        }

                        //Get eventID
                        $v3EventId = 0;
                        if( (int) $v2EventXmlData->event_template_id > 0 )   {
                            $v3Event_tmp = \App\Models\Event::where('v2_event_id', $v2EventXmlData->event_template_id)->first();
                            if( $v3Event_tmp )  {
                                $v3EventId = $v3Event_tmp->id;
                            }   else {
                                $v3EventId = (int) ($this->minusPrefix . $v2EventXmlData->event_template_id); //prefix of 999999 so we can later pull it if required.
                            }
                        }

                        $$v3EventXmlData = EventXmlData::create([
                            'v2_id' => $v2EventXmlData->id,
                            'awarder_account_holder_id' => $awarder_account_holder_id,
                            'name' => $v2EventXmlData->name,
                            'award_level_name' => $v2EventXmlData->award_level_name,
                            'amount_override' => $v2EventXmlData->amount_override,
                            'notification_body' => $v2EventXmlData->notification_body,
                            'notes' => $v2EventXmlData->notes,
                            'referrer' => $v2EventXmlData->referrer,
                            'email_template_id' => $this->minusPrefix . $v2EventXmlData->email_template_id,
                            'event_type_id' => $v2EventXmlData->event_type_id,
                            'event_template_id' => $v3EventId,
                            'icon' => $v2EventXmlData->icon,
                            'xml' => $v2EventXmlData->xml,
                            'award_transaction_id' => $v2EventXmlData->award_transaction_id,
                            'lease_number' => $v2EventXmlData->lease_number,
                            'token' => $v2EventXmlData->token
                        ]);

                        // pr($v3EventXmlDataId);

                        if( $v3EventXmlData ) {
                            $this->v2db->statement(sprintf("UPDATE `event_xml_data` SET `v3_id`=%d WHERE `id`=%d", $v3EventXmlData->id, $v2EventXmlData->id));
                        }
                    }
                }

                //Patch

                //Try once more to patch "user_id" for old records
                if( !empty($v2EventXmlData) && $v3EventXmlData )   {
                    //If the "awarder_account_holder_id" was "framed" previously
                    if( strpos($v3EventXmlData->awarder_account_holder_id, $this->idPrefix) == 0 ) {
                        $v3User_tmp = User::where('v2_account_holder_id', $v2EventXmlData->awarder_account_holder_id)->first();
                        if( $v3User_tmp )   {
                            $v3EventXmlData->awarder_account_holder_id = $v3User_tmp->account_holder_id;
                            $saveEventXml = true;
                        }
                    }
                }

                // new created entries for "event_xml_data_id"
                if( $v3EventXmlData ) {  //if record exists and it is not new

                    //Patch previously imported, can be buggy though, to be removed
                    if( $v3JournalEvent->event_xml_data_id == $v2JournalEvent->event_xml_data_id) {
                        $v3JournalEvent->event_xml_data_id = $v3EventXmlData->id;
                        $saveJournal = true;
                    }
                }

                if( $v3JournalEvent->event_xml_data_id == $v2JournalEvent->event_xml_data_id) {
                    $v3JournalEvent->event_xml_data_id = $v3EventXmlData->id;
                    $saveJournal = true;
                }
            }

            //for previously imported journals

            //confirm and correct "prime_account_holder_id" if required
            if( $v2JournalEvent->prime_account_holder_id > 0)
            {
                if( $v3JournalEvent->prime_account_holder_id == (int)($this->idPrefix . $v2JournalEvent->prime_account_holder_id))    {
                    if( !empty($v2JournalEvent->v3_user_id) )
                    {
                        $v3User = User::find($v2JournalEvent->v3_user_id);
                        if( $v3User ) {
                            $v3JournalEvent->prime_account_holder_id = $v3User->prime_account_holder_id;
                            $saveJournal = true;
                        }
                    }  else {
                        $v3User_tmp = User::where('v2_account_holder_id', $v2JournalEvent->prime_account_holder_id)->first();
                        if( $v3User_tmp )   {
                            $v3JournalEvent->prime_account_holder_id = $v3User_tmp->account_holder_id;
                            $saveJournal = true;
                        }   else {
                            if( strpos($v3JournalEvent->prime_account_holder_id, $this->idPrefix) == -1 ) {
                                $v3JournalEvent->prime_account_holder_id = ($v2JournalEvent->prime_account_holder_id==1) ? 1 : ($this->idPrefix . $v2JournalEvent->prime_account_holder_id);
                                $saveJournal = true;
                            }
                        }
                    }
                } else {
                    //If "prime_account_holder_id" is a placehoder id
                    if( strpos($v3JournalEvent->prime_account_holder_id, $this->idPrefix) == 0 ) { //it is in the beginning and is larger than the prefix
                        $this->printf("idPrefix detected in v3:prime_account_holder_id\n");
                        $v2_prime_account_holder_id = (int) str_replace($this->idPrefix, "", $v3JournalEvent->prime_account_holder_id);
                        if( $v2_prime_account_holder_id > 0 )   {
                            if( !empty($this->cachedPrimeAccountHolders[$v2_prime_account_holder_id]) ) {
                                $v3_prime_account_holder_id = $this->cachedPrimeAccountHolders[$v2_prime_account_holder_id];
                            }   else {
                                $tmpUser = User::join('user_v2_users', 'user_v2_users.user_id', '=', 'users.id')->where('user_v2_users.v2_user_account_holder_id', $v2_prime_account_holder_id)->first();
                                if( $tmpUser )  {
                                    $v3_prime_account_holder_id = $tmpUser->account_holder_id;
                                    $this->cachedPrimeAccountHolders[$v2_prime_account_holder_id] = $v3_prime_account_holder_id;
                                }
                            }
                            if( !empty($v3_prime_account_holder_id) ) {
                                $v3JournalEvent->prime_account_holder_id = $v3_prime_account_holder_id;
                                $saveJournal = true;
                            }
                        }
                    }
                }
            }

            if( $saveJournal ) {
                $this->printf(" -- saving v3JournalEvent\n");
                $v3JournalEvent->save();
            }
            if( $saveEventXml && !empty($v3EventXmlData) ) {
                $this->printf(" -- saving v3EventXmlData\n");
                $v3EventXmlData->save();
            }
        }

        //Migrate Postings as part of JournalEvent
        $v2postings = $this->v2db->select( sprintf("SELECT * FROM `postings` where `journal_event_id`=%d", $v2JournalEvent->id));

        // $v2postings = \App\Models\Posting::where('journal_event_id', $v2JournalEvent->id)->get();
        if( $v2postings ) {
            foreach( $v2postings as $v2posting )        {
                $createPosting = true;
                if( $v2posting->v3_posting_id )   {
                    //Confirm
                    $v3Posting = \App\Models\Posting::find( $v2posting->v3_posting_id );
                    // pr($v3Posting->toArray());
                    if( $v3Posting )   {
                        printf("   - Posting \"%d\" exists for v2: \"%d\"\n", $v3Posting->id, $v2JournalEvent->id);
                        $createPosting = false;
                        //Update??
                        $this->localImportMap['journalEvents'][$v3JournalEvent->id]['postings'][$v3Posting->id]['exists'] = 1;
                    }
                }   else {
                    //find by v2 id
                    $v3Posting = \App\Models\Posting::where('v2_posting_id', $v2posting->id )->first();
                    if( $v3Posting )   {
                        printf("   - Posting \"%d\" exists for v2: \"%d\", found via v2_posting_id search. Updating null v2_posting_id value.\n", $v3Posting->id, $v2posting->v3_posting_id, $v2JournalEvent->id);
                        $this->addV2SQL(sprintf("UPDATE `postings` SET `v3_posting_id`=%d WHERE `id`=%d", $v3Posting->id, $v2posting->id));
                        $createPosting = false;
                        //Update??
                        $this->localImportMap['journalEvents'][$v3JournalEvent->id]['postings'][$v3Posting->id]['exists'] = 1;
                    }
                }

                if( $createPosting )    {
                    $v3AccountId = 0;
                    if( $v2posting->account_id )  {
                        /***
                         * Here we will assume that the accounts are already imported.
                         * The MigrateJournalEvents should only be run after importing accounts.
                         * It will not import accounts in case and will prefix v2 account id with
                         * 999999.
                        */
                        $v3Account = \App\Models\Account::where('v2_account_id', $v2posting->account_id)->first();
                        if( $v3Account )    {
                            $v3AccountId = $v3Account->id;
                        }   else {
                            $v3AccountId = $this->idPrefix . $v2posting->account_id;
                        }
                    }

                    $v3MediumInfoId = 0;
                    if( $v2posting->medium_info_id )  {
                        /***
                         * Here we will assume that the accounts are already imported.
                         * The MigrateJournalEvents should only be run after importing accounts.
                         * It will not import accounts in case and will prefix v2 account id with
                         * 999999.
                        */
                        $v3MediumInfo = \App\Models\MediumInfo::where('v2_medium_info_id', $v2posting->medium_info_id)->first();
                        if( $v3MediumInfo )    {
                            $v3MediumInfoId = $v3MediumInfo->id;
                        }   else {
                            $v3MediumInfoId = $this->idPrefix . $v2posting->medium_info_id;
                        }
                    }   else {
                        $v3MediumInfoId = $v2posting->medium_info_id;
                    }

                    $newV3Posting = \App\Models\Posting::create([
                        'v2_posting_id' => $v2posting->id,
                        'journal_event_id' => $v3JournalEvent->id,
                        'account_id' => $v3AccountId,
                        'posting_amount' => $v2posting->posting_amount,
                        'is_credit' => $v2posting->is_credit,
                        'medium_info_id' => $v3MediumInfoId,
                        'qty' => $v2posting->qty,
                        'created_at' => $v2posting->posting_timestamp
                    ]);

                    $this->v2db->statement(sprintf("UPDATE `postings` SET `v3_posting_id`=%d WHERE `id`=%d", $newV3Posting->id, $v2posting->id));

                    $this->localImportMap['journalEvents'][$v3JournalEvent->id]['postings'][$newV3Posting->id]['created'] = 1;
                }
            }
        }

        $this->executeV2SQL();
        $this->recursivelyMigrateByV2ParentJournalEventId($v2JournalEvent->id );
        return $v3JournalEvent;
    }

    public function recursivelyMigrateByV2ParentJournalEventId( $v2_journal_event_id )  {
        $sql = sprintf("SELECT *  FROM `journal_events` WHERE `parent_journal_event_id`=%d", $v2_journal_event_id);
        $journalEvents = $this->v2db->select($sql);
        if( $journalEvents )    {
            foreach( $journalEvents as $row )  {
                $this->migrateSingleJournalEvent( (object) $row); //Migrate Single Journal
            }
        }
    }

    // public function fixPostingsAccoundIds() {
    //     // DB::enableQueryLog();
    //     $results = \App\Models\Posting::where('account_id', 'LIKE', $this->idPrefix . '%')->get();
    //     // pr(sizeof($results));
    //     // pr(toSql(DB::getQueryLog()));
    //     if( sizeof($results) > 0 )  {
    //         foreach( $results as $v3Posting )   {
    //             if( !$v3Posting->v2_posting_id) {
    //                 continue;
    //             }
    //             $create = false;
    //             $exists = false;
    //             $v2_account_id = (int) str_replace($this->idPrefix, '', $v3Posting->account_id);
    //             if( $v2_account_id ) {
    //                 $sql = sprintf("SELECT * FROM `postings` WHERE `account_id`=%d AND id=%d", $v2_account_id, $v3Posting->v2_posting_id) ;
    //                 $v2Posting = current($this->v2db->select($sql));
    //                 if( $v2Posting ) {
    //                     $v3Account = Account::where('v2_account_id', $v2_account_id)->first();
    //                     if( !$v3Account ) {
    //                         $sql = sprintf("SELECT * FROM `accounts` WHERE `id`=%d", $v2_account_id) ;
    //                         $result = $this->v2db->select($sql);
    //                         if( sizeof($result) > 0) {
    //                             $v2Account = current($result);
    //                             if( $v2Account )    {
    //                                 if( $v2Account->v3_account_id ) {
    //                                     $v3Account = Account::find($v2Account->v3_account_id);
    //                                     if( !$v3Account ) {
    //                                         $create = true;
    //                                     } else {
    //                                         $exists = true;
    //                                     }
    //                                 }   else {
    //                                     $create = true;
    //                                 }
    //                             }
    //                         }
    //                     }   else {
    //                         $exists = true;
    //                     }
    //                     if( $create ) {
    //                         $v2_account_holder_id = $v2Account->account_holder_id;
    //                         pr("create");
    //                         // pr($v2_account_holder_id);
    //                         $v3Account = Account::create([
    //                             'account_holder_id' => $this->idPrefix . $v2_account_holder_id,
    //                             'account_type_id' => $v2Account->account_type_id,
    //                             'finance_type_id' => $v2Account->finance_type_id,
    //                             'medium_type_id' => $v2Account->medium_type_id,
    //                             'currency_type_id' => $v2Account->currency_type_id,
    //                             'v2_account_id' => $v2Account->id,
    //                         ]);
    //                     }

    //                     if($create || $exists ) {
    //                         $v3Posting->account_id = $v3Account->id;
    //                         $v3Posting->save();
    //                     }
    //                 }
    //             }
    //         }
    //     }
    // }
}
