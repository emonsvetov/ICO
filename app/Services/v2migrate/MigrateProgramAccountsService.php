<?php
namespace App\Services\v2migrate;

use Illuminate\Support\Facades\DB;
use Exception;

use App\Models\JournalEvent;
use App\Models\Program;
use App\Models\Account;
use App\Models\Posting;

class MigrateProgramAccountsService extends MigrationService
{

    public $offset = 0;
    public $limit = 10;
    public $iteration = 0;
    public $count = 0;
    public bool $overwriteProgram = false;
    public int $importedProgramsCount = 0;
    public array $importedPrograms = [];
    public array $importMap = []; //This is the final map of imported objects with name is key. Ex. $importMap['program'][$v2_account_holder_id] = $v2ID;
    public array $cacheJournalEventsMap = [];
    public bool $printSql = true;
    public bool $useTransactions = true;

    public function __construct()
    {
        parent::__construct();
    }

    public function migrateAccounts( Program $program ) {
        if( !$program ) {
            throw new Exception("Invalid program passed to \"migrateAccounts\" method\n");
        }
        if( !$program->v2_account_holder_id ) {
            throw new Exception("Missing or null \"v2_account_holder_id\"\n");
        }
        printf("Migrating accounts for program: \"%s\"\n", $program->name);
        // if( $this->useTransactions ) {
        //     DB::beginTransaction();
        //     $this->v2db->beginTransaction();
        // }

        try {
            $sql = sprintf("SELECT * FROM accounts WHERE account_holder_id = %d", $program->v2_account_holder_id);
            $v2Accounts = $this->v2db->select($sql);
            if( ($countV2Accounts = sizeof($v2Accounts)) > 0 ) {
                printf("Found %d accounts for program: \"%s\"\n", $countV2Accounts, $program->name);
                // $v2AccountIds = collect($v2Accounts)->pluck('id');
                // pr(implode(",", $v2AccountIds->toArray()));
                // exit;
                foreach( $v2Accounts as $v2Account) {
                    $createNewAccount = true;
                    $newAccountCreated = false;
                    if( $v2Account->v3_account_id ) {
                        printf("v2 account:%d exists in v3 as:%d. Skipping.\n", $v2Account->id, $v2Account->v3_account_id);
                        $createNewAccount = false;
                    }
                    if( $createNewAccount ) {
                        $v3Account = Account::where([
                            'account_holder_id' => $program->account_holder_id,
                            'account_type_id' => $v2Account->account_type_id,
                            'finance_type_id' => $v2Account->finance_type_id,
                            'medium_type_id' => $v2Account->medium_type_id,
                            'currency_type_id' => $v2Account->currency_type_id,
                        ])->first();
                        if( $v3Account ) {
                            printf("Accounts combination %d-%d-%d-%d exists for program: \"%s\". Skipping..\n",$v2Account->account_type_id, $v2Account->finance_type_id, $v2Account->medium_type_id, $v2Account->currency_type_id, $program->name);
                            $v3AccountId = $v3Account->id;
                            $createNewAccount = false;
                        }   else {
                            printf("Accounts combination %d-%d-%d-%d does not exist for program: \"%s\". Creating..\n",$v2Account->account_type_id, $v2Account->finance_type_id, $v2Account->medium_type_id, $v2Account->currency_type_id, $program->name);
                            $v3AccountId = Account::getIdByColumns([
                                'account_holder_id' => $program->account_holder_id,
                                'account_type_id' => $v2Account->account_type_id,
                                'finance_type_id' => $v2Account->finance_type_id,
                                'medium_type_id' => $v2Account->medium_type_id,
                                'currency_type_id' => $v2Account->currency_type_id,
                                'v2_account_id' => $v2Account->id,
                            ]);
                            $newAccountCreated = true;
                        }
                    }

                    if( $v3AccountId ) {
                        $sql = sprintf("SELECT postings.id AS posting_id, postings.*, je.* FROM accounts JOIN postings on postings.account_id=accounts.id JOIN journal_events je ON je.id=postings.journal_event_id where accounts.account_holder_id = %d AND accounts.id=%d AND je.v3_journal_event_id IS NULL ORDER BY je.journal_event_timestamp ASC, postings.posting_timestamp ASC", $program->v2_account_holder_id, $v2Account->id);
                        printf(" - Fetching journal_events+postings for program:\"%s\" & account:\"%s\". Please wait...\n", $program->name, $v2Account->id);
                        $results = $this->v2db->select($sql);
                        if( $this->printSql ) {
                            print($sql . "\n");
                        }
                        // pr($sql);
                        // pr($v2Account->id);
                        // pr(count($results));
                        // exit;
                        if( ($countJEAndPostings = sizeof($results)) > 0 ) {
                            printf(" - Found %d journal_events+postings for program:\"%s\" & account:\"%s\". Processing...\n",$countJEAndPostings, $program->name, $v2Account->id);
                            foreach( $results as $row) {
                                if( !$row->v3_journal_event_id &&  !$row->v3_posting_id) {
                                    $parent_id = isset($this->cacheJournalEventsMap[$row->parent_journal_event_id]) ? $this->cacheJournalEventsMap[$row->parent_journal_event_id] : null; //If parent is created in the very process
                                    $v3JournalEventId = JournalEvent::insertGetId(
                                        [
                                            'v2_journal_event_id' => $row->journal_event_id,
                                            'prime_account_holder_id' => 0,
                                            'v2_prime_account_holder_id' => $row->prime_account_holder_id,
                                            'journal_event_type_id' => $row->journal_event_type_id,
                                            'notes' => $row->notes,
                                            'event_xml_data_id' => $row->event_xml_data_id,
                                            'invoice_id' => $row->invoice_id,
                                            'parent_id' => $parent_id,
                                            'v2_parent_journal_event_id' => !$parent_id ? $row->parent_journal_event_id : null,
                                            'is_read' => $row->is_read,
                                            'created_at' => $row->journal_event_timestamp,
                                            'journal_event_type_id' => $row->journal_event_type_id,
                                        ]
                                    );

                                    printf(" - New Journal Event \"%d\" created for v2 journal event \"%d\"\n",$v3JournalEventId, $row->journal_event_id);

                                    $this->v2db->statement(sprintf("UPDATE `journal_events` SET `v3_journal_event_id`=%d WHERE `id`=%d", $v3JournalEventId, $row->journal_event_id));
                                    // $this->addV2SQL(sprintf("UPDATE `journal_events` SET `v3_journal_event_id`=%d WHERE `id`=%d", $v3JournalEventId, $row->journal_event_id));

                                    $this->cacheJournalEventsMap[$row->journal_event_id] = $v3JournalEventId;

                                    $v3PostingId = Posting::insertGetId([
                                        'v2_posting_id' => $row->posting_id,
                                        'journal_event_id' => $v3JournalEventId,
                                        'account_id' => $v3AccountId,
                                        'posting_amount' => $row->posting_amount,
                                        'is_credit' => $row->is_credit,
                                        'medium_info_id' => $row->medium_info_id,
                                        'qty' => $row->qty,
                                        'created_at' => $row->posting_timestamp
                                    ]);

                                    printf(" - New Posting \"%d\" created for v2 posting \"%d\"\n",$v3PostingId, $row->posting_id);

                                    $this->v2db->statement(sprintf("UPDATE `postings` SET `v3_posting_id`=%d WHERE `id`=%d", $v3PostingId, $row->posting_id));
                                }   else {
                                    printf(" - journal_events/postings already imported with v3_journal_event_id:%s, v3_posting_id:%s.\n", $row->v3_journal_event_id, $row->v3_posting_id);
                                }
                            }
                        }   else {
                            printf(" - No journal_events+postings found for program:\"%s\" & account:\"%s\".\n", $program->name, $v2Account->id);
                        }
                        if( $newAccountCreated ) {
                            $this->v2db->statement(sprintf("UPDATE `accounts` SET `v3_account_id`=%d WHERE `id`=%d", $v3AccountId, $v2Account->id));
                        }
                    }
                }
                $this->executeV2SQL();
            }   else {
                printf("No accounts found for program: \"%s\"\n", $program->name);
            }
            // if( $this->useTransactions ) {
            //     DB::commit();
            //     $this->v2db->commit();
            // }
        } catch (Exception $e) {
            // if( $this->useTransactions ) {
            //     DB::rollback();
            //     $this->v2db->rollBack();
            // }
            throw new Exception("Error migrating v2 accounts into v3. Error:{$e->getMessage()} in Line: {$e->getLine()} in File: {$e->getFile()}");
        }
    }

    public function migrate() {
        $this->iteration++;
        printf("Migrating programAccounts, iteration:%d\n", $this->iteration);
        $v3Programs = Program::whereNotNull('v2_account_holder_id')->skip($this->offset)->take($this->limit)->select(['id', 'v2_account_holder_id', 'account_holder_id', 'name'])->get();
        if( ($countV3Programs = count($v3Programs)) > 0)
        foreach( $v3Programs as $v2Program ) {
            $this->migrateAccounts( $v2Program );
        }
        $this->offset = $this->offset + $this->limit;
        // if( $this->count >= 20 ) exit;
        if( $countV3Programs >= $this->limit) {
            $this->migrate();
        }
    }
}
