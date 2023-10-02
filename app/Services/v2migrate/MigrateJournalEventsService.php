<?php
namespace App\Services\v2migrate;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Exception;

use App\Models\EventXmlData;
use App\Models\JournalEvent;
use App\Models\Merchant;
use App\Models\Program;
use App\Models\Account;
use App\Models\AccountHolder;
use App\Models\User;


class MigrateJournalEventsService extends MigrationService
{
    public $offset = 0;
    public $limit = 1;
    public $iteration = 0;
    public $count = 0;
    public bool $printSql = true;
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

    // public function migrateByModel($v3Model, $v2Model = null)  {
    //     $this->setRefByModel($v3Model, $v2Model);
    //     if( !$v3Model->{$this->v2RefKey} && !$v2Model ) {
    //         $this->printf("Required argument missing in MigrateJournalEventsService->migrateByModel(%s).\n", $this->modelName);
    //         return;
    //     }

    //     // pr($v2Model);
    //     // exit;

    //     // //Migrate Journal Events for Model
    //     $this->printf("Attempting to import %s journal events\n", $this->modelName);
    //     // printf(" - Fetching journal_events for user:\"%s\"\n", $v2User->email);

    //     $this->recursivelyMigrateByModel($v3Model);
    //     $this->importMap[$v3Model->id]['journalEvents'] = @$this->localImportMap['journalEvents'];
    //     // // pr($v2User);
    //     // // pr($v3User);
    // }

    // public function recursivelyMigrateByModel( $v3Model, $parent_id = null ) {
    //     if( !$v3Model || !$v2_account_holder_id = $v3Model->{$this->v2RefKey})  { //Notice inline assignment!
    //         $this->printf("Invalid arugments in \"recursivelyMigrateByModel\" method while importing %s journal events\n", $this->modelName);
    //         return;
    //     }
    //     $sql = sprintf("SELECT * FROM `journal_events` WHERE `prime_account_holder_id`=%d", $v2_account_holder_id);
    //     if( !$parent_id )    {
    //         $sql .= " AND `parent_journal_event_id` IS NULL";
    //     }   else {
    //         $sql .= sprintf(" AND `parent_journal_event_id`=%d", $parent_id);
    //     }

    //     $this->printf("\n%s\n", $sql);

    //     $journalEvents = $this->v2db->select($sql);
    //     // pr(count($journalEvents));
    //     // exit;
    //     if( $journalEvents )    {
    //         foreach( $journalEvents as $journalEvent )  {
    //             $this->migrateSingleJournalEvent($journalEvent); //Migrate Single Journal
    //             // $this->recursivelyMigrateByModel($v3Model, $journalEvent->id); //Migrate Single Journal
    //         }
    //     }
    // }

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

    public function migrateJournalEventsByModelAndAccount(Program|User|Merchant $v3Model, Account $v3Account) {
        if( !$v3Model->v2_account_holder_id || !$v3Account->v2_account_id)    {
            throw new Exception(sprintf("Invalid \"v2_account_holder_id\" in model \"%s\" or account. Are you sure this model and account was imported properly?\"\n\n", $this->modelName));
        }
        $sql = sprintf("SELECT postings.id AS posting_id, postings.*, je.*, users.account_holder_id AS user_account_holder_id, users.v3_user_id FROM accounts JOIN postings on postings.account_id=accounts.id JOIN journal_events je ON je.id=postings.journal_event_id LEFT JOIN users on users.account_holder_id=je.prime_account_holder_id WHERE accounts.account_holder_id = %d AND accounts.id=%d ORDER BY je.journal_event_timestamp ASC, postings.posting_timestamp ASC", $v3Model->v2_account_holder_id, $v3Account->v2_account_id);

        $this->printf(" - Fetching journal_events+postings for model %s:\"%s\" & account:\"%s\".\n\n", $this->modelName, $v3Model->id, $v3Account->v2_account_id);
        $results = $this->v2db->select($sql);

        if( $this->printSql ) {
            $this->printf($sql . "\n\n");
        }

        if( $results )  {
            $this->countPostings += count($results);
            // exit;
            foreach( $results as $row) {
                // if($row->prime_account_holder_id != 286143) continue;
                $this->migrateSingleJournalEventByPostingData( (object) $row );
                // if( !$row->v3_journal_event_id &&  !$row->v3_posting_id) {
                //     $prime_account_holder_id = $row->prime_account_holder_id;
                //     $parent_id = isset($this->cacheJournalEventsMap[$row->parent_journal_event_id]) ? $this->cacheJournalEventsMap[$row->parent_journal_event_id] : null; //If parent is created in the very process
                //     if( $row->prime_account_holder_id > 0 ) {
                //         $v3User = \App\Models\User::where('v2_account_holder_id', $row->prime_account_holder_id)->first();
                //         if( $v3User )   {
                //             $prime_account_holder_id = $v3User->account_holder_id;
                //         }
                //     }
                // }
            }
        }   else {
            $this->printf(" -- No journal_events+postings found.. next..\n\n");
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

    public function migrateJournalEventsByModelAccounts( Program|User|Merchant $v3Model, $v2Model = null )    {

        $this->setRefByModel($v3Model, $v2Model);

        if( !$v3Model->v2_account_holder_id )    {
            throw new Exception(sprintf("Invalid \"v2_account_holder_id\" in model \"%s\". Are you sure this model was imported properly?\"\n", $this->modelName));
        }

        $accounts = \App\Models\Account::where('account_holder_id', $v3Model->account_holder_id)->get();

        if( count($accounts) > 0 )  {
            foreach( $accounts as $v3Account)    {
                if( $v3Account->v2_account_id ) {
                    $this->migrateJournalEventsByModelAndAccount($v3Model, $v3Account);
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
                printf(" - Journal Event \"%d\" exists for v2:%d\n", $v3JournalEvent->id, $v2JournalEvent->id);
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
                printf(" - Journal Event \"%d\" exists for v2: \"%d\", found via v2_journal_event_id search. Updating null v3_journal_event_id value.\n", $v3JournalEvent->id, $v2JournalEvent->v3_journal_event_id, $v2JournalEvent->id);
                //Patch link since missing
                $this->addV2SQL(sprintf("UPDATE `journal_events` SET `v3_journal_event_id`=%d WHERE `id`=%d", $v3JournalEvent->id, $v2JournalEvent->id));
                $create = false;
                $this->localImportMap['journalEvents'][$v3JournalEvent->id]['exists'] = 1;
                //Update??
            }
        }
        $newV3JournalEvent = null;
        if( $create )   {
            $parent_id = null;
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
                            $prime_account_holder_id = $this->idPrefix . $v2JournalEvent->prime_account_holder_id;
                        }
                    }
                }
            }

            //Get v3 parent_journal_event_id
            if( (int) $v2JournalEvent->parent_journal_event_id > 0 )  {
                $v3ParentTmp = JournalEvent::where('v2_journal_event_id', $v2JournalEvent->parent_journal_event_id)->first();
                if( $v3ParentTmp )  $parent_id = $v3ParentTmp->id;
                else   $parent_id = $this->idPrefix . $v2JournalEvent->parent_journal_event_id;
            }

            $v3_event_xml_data_id = null;
            if( $v2JournalEvent->event_xml_data_id > 0) {
                $v3EventXmlData =\App\Models\EventXmlData::where('v3_id', $v2JournalEvent->event_xml_data_id)->first();
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
                    'parent_id' => $parent_id,
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
                if( $v3JournalEvent->prime_account_holder_id == $v2JournalEvent->prime_account_holder_id)    {
                    if( !empty($v2JournalEvent->v3_user_id) )
                    {
                        $v3JournalEvent->prime_account_holder_id = $v2JournalEvent->v3_user_id;
                        $this->printf("v3:prime_account_holder_id==v2:prime_account_holder_id.. Updating\n\n");
                        $saveJournal = true;
                    }  else {
                        $v3User_tmp = User::where('v2_account_holder_id', $v2JournalEvent->prime_account_holder_id)->first();
                        if( $v3User_tmp )   {
                            $v3JournalEvent->prime_account_holder_id = $v3User_tmp->account_holder_id;
                            $saveJournal = true;
                        }   else {
                            if( strpos($v3JournalEvent->prime_account_holder_id, $this->idPrefix) == -1 ) {
                                $v3JournalEvent->prime_account_holder_id = $this->idPrefix . $v2JournalEvent->prime_account_holder_id;
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
                                $tmpUser = User::where('v2_account_holder_id', $v2_prime_account_holder_id)->first();
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
                    $v3AccountId = $this->idPrefix;
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
                            $v3AccountId .= $v2posting->account_id;
                        }
                    }

                    $v3MediumInfoId = $this->idPrefix;
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
                            $v3MediumInfoId .= $v2posting->medium_info_id;
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

                    $this->localImportMap['journalEvents'][$v3JournalEvent->id]['postings'][$newV3Posting->id]['created'] = 1;
                }
            }
        }

        $this->executeV2SQL();
        $this->recursivelyMigrateByV2ParentJournalEventId($v2JournalEvent->id );
        return $v3JournalEvent;
    }

    // public function migrateByUser($v2User, $v3User)  {
    //     if( !$v2User->v3_user_id || !$v3User->v2_account_holder_id ) {
    //         $this->printf("Required argument missing in MigrateUserService->migrateUserJournalEvents().\n");
    //         return;
    //     }

    //     //Migrate Journal Events for User
    //     $this->printf("Attempt to import user journal events\n");
    //     printf(" - Fetching journal_events for user:\"%s\"\n", $v2User->email);

    //     $this->recursivelyMigrateByUser($v2User, $v3User, null);
    //     // pr($v2User);
    //     // pr($v3User);
    // }

    // public function recursivelyMigrateByUser( $v2User, $v3User, $parent_id = null )  {
    //     $this->v2User = $v2User;
    //     $this->v3User = $v3User;
    //     $sql = sprintf("SELECT *, `id` AS `journal_event_id` FROM `journal_events` WHERE `prime_account_holder_id`=%d", $v2User->account_holder_id);
    //     if( !$parent_id )    {
    //         $sql .= " AND `parent_journal_event_id` IS NULL";
    //     }   else {
    //         $sql .= sprintf(" AND `parent_journal_event_id`=%d", $parent_id);
    //     }
    //     $journalEvents = $this->v2db->select($sql);
    //     // pr($journalEvents[0]);
    //     // exit;
    //     if( $journalEvents )    {
    //         foreach( $journalEvents as $row )  {
    //             $this->migrateSingleJournalEvent($row); //Migrate Single Journal
    //             $this->recursivelyMigrateByUser($v2User, $v3User, $row->id); //Migrate Single Journal
    //         }
    //     }
    // }

    public function recursivelyMigrateByV2ParentJournalEventId( $v2_journal_event_id )  {
        $sql = sprintf("SELECT *  FROM `journal_events` WHERE `parent_journal_event_id`=%d", $v2_journal_event_id);
        $journalEvents = $this->v2db->select($sql);
        if( $journalEvents )    {
            foreach( $journalEvents as $row )  {
                $this->migrateSingleJournalEvent( (object) $row); //Migrate Single Journal
            }
        }
    }

    public function fixPostingsAccoundIds() {
        // DB::enableQueryLog();
        $results = \App\Models\Posting::where('account_id', 'LIKE', $this->idPrefix . '%')->get();
        // pr(sizeof($results));
        // pr(toSql(DB::getQueryLog()));
        if( sizeof($results) > 0 )  {
            foreach( $results as $v3Posting )   {
                if( !$v3Posting->v2_posting_id) {
                    continue;
                }
                $create = false;
                $exists = false;
                $v2_account_id = (int) str_replace($this->idPrefix, '', $v3Posting->account_id);
                if( $v2_account_id ) {
                    $sql = sprintf("SELECT * FROM `postings` WHERE `account_id`=%d AND id=%d", $v2_account_id, $v3Posting->v2_posting_id) ;
                    $v2Posting = current($this->v2db->select($sql));
                    if( $v2Posting ) {
                        $v3Account = Account::where('v2_account_id', $v2_account_id)->first();
                        if( !$v3Account ) {
                            $sql = sprintf("SELECT * FROM `accounts` WHERE `id`=%d", $v2_account_id) ;
                            $result = $this->v2db->select($sql);
                            if( sizeof($result) > 0) {
                                $v2Account = current($result);
                                if( $v2Account )    {
                                    if( $v2Account->v3_account_id ) {
                                        $v3Account = Account::find($v2Account->v3_account_id);
                                        if( !$v3Account ) {
                                            $create = true;
                                        } else {
                                            $exists = true;
                                        }
                                    }   else {
                                        $create = true;
                                    }
                                }
                            }
                        }   else {
                            $exists = true;
                        }
                        if( $create ) {
                            $v2_account_holder_id = $v2Account->account_holder_id;
                            pr("create");
                            // pr($v2_account_holder_id);
                            $v3Account = Account::create([
                                'account_holder_id' => $this->idPrefix . $v2_account_holder_id,
                                'account_type_id' => $v2Account->account_type_id,
                                'finance_type_id' => $v2Account->finance_type_id,
                                'medium_type_id' => $v2Account->medium_type_id,
                                'currency_type_id' => $v2Account->currency_type_id,
                                'v2_account_id' => $v2Account->id,
                            ]);
                        }

                        if($create || $exists ) {
                            $v3Posting->account_id = $v3Account->id;
                            $v3Posting->save();
                        }
                    }
                }
            }
        }
    }
}
