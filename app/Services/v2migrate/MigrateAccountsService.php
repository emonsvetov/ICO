<?php
namespace App\Services\v2migrate;

use Illuminate\Support\Facades\DB;
use Exception;

use App\Models\JournalEvent;
use App\Models\Merchant;
use App\Models\Program;
use App\Models\Account;
use App\Models\Posting;
use App\Models\User;

class MigrateAccountsService extends MigrationService
{

    public $offset = 0;
    public $limit = 10;
    public $iteration = 0;
    public $count = 0;
    public array $importMap = [];
    public array $cacheJournalEventsMap = [];
    public bool $printSql = true;
    public bool $useTransactions = true;

    public function __construct()
    {
        parent::__construct();
    }

    public function migrateByModel( Program|User|Merchant $model ) {
        $CLASS = get_class($model);
        $modelName = strtolower(substr($CLASS, strrpos($CLASS, "\\") + 1 ));
        $v2PK = "v2_account_holder_id";
        $v2_account_holder_id = $model->{$v2PK};

        if( !$model ) {
            throw new Exception("Invalid model passed to \"MigrateAccountsService->migrate()\" \n");
        }
        if( !$v2_account_holder_id ) {
            throw new Exception(sprintf("Missing or null \"%s\" for %s\n", $v2PK, $modelName));
        }
        printf("Migrating accounts for %sID:\"%s\"\n", $modelName, $model->id);
        // if( $this->useTransactions ) {
        //     DB::beginTransaction();
        //     $this->v2db->beginTransaction();
        // }

        try {
            // pr($v2_account_holder_id);
            $sql = sprintf("SELECT * FROM accounts WHERE account_holder_id = %d", $v2_account_holder_id);
            $v2Accounts = $this->v2db->select($sql);
            if( ($countV2Accounts = sizeof($v2Accounts)) > 0 ) {
                printf("Found %d accounts for %s: \"%s\"\n", $countV2Accounts, $modelName, $model->id);
                // $v2AccountIds = collect($v2Accounts)->pluck('id');
                // pr(implode(",", $v2AccountIds->toArray()));
                // exit;
                foreach( $v2Accounts as $v2Account) {
                    $createNewAccount = true;
                    $newAccountCreated = false;
                    if( $v2Account->v3_account_id ) {
                        printf("\$v2Account->v3_account_id is non zero (%s) for v3:%d. Confirming v3 record..\n", $v2Account->v3_account_id, $v2Account->id);
                        $v3Account = Account::find($v2Account->v3_account_id);
                        if( $v3Account )    {
                            printf("Account entry found for v2:%d by '\$v2Account->v3_account_id'. Skipping creation.\n", $v2Account->id);
                            if( !$v3Account->v2_account_id )    { //if v2 ref is null
                                $v3Account->v2_account_id = $v2Account->id;
                                $v3Account->save();
                            }
                        }   else {
                            //check with v2->id
                            $v3Account = Account::where('v2_account_id', $v2Account->id )->first();
                            if( $v3Account )    {
                                printf("Account entry found for v2:%d by '\$v3Account->v2_account_id'.\n", $v2Account->id);
                                //found, need to update v2 record
                                $this->addV2SQL(sprintf("UPDATE `accounts` SET `v3_account_id`=%d WHERE `id`=%d", $v3Account->id, $v2Account->id));
                                $createNewAccount = false;
                            }
                        }
                    }   else {
                        $v3Account = Account::where('v2_account_id', $v2Account->id )->first();
                        if( $v3Account )    {
                            //found, need to update v2 record
                            $this->addV2SQL(sprintf("UPDATE `accounts` SET `v3_account_id`=%d WHERE `id`=%d", $v3Account->id, $v2Account->id));
                            $createNewAccount = false;
                        }
                    }
                    if( $createNewAccount ) {
                        $v3Account = Account::where([
                            'account_holder_id' => $model->account_holder_id,
                            'account_type_id' => $v2Account->account_type_id,
                            'finance_type_id' => $v2Account->finance_type_id,
                            'medium_type_id' => $v2Account->medium_type_id,
                            'currency_type_id' => $v2Account->currency_type_id,
                        ])->first();
                        if( $v3Account ) { //it exists anyhow!!! (impossible after above checks!!)
                            printf("Accounts combination %d-%d-%d-%d exists for v3model:\"%s\". Skipping..\n",$v2Account->account_type_id, $v2Account->finance_type_id, $v2Account->medium_type_id, $v2Account->currency_type_id, $model->id);
                            $v3AccountId = $v3Account->id;
                            // Sync anyway!!
                            $v3Account->v2_account_id = $v2Account->id;
                            $v3Account->save();
                            $this->addV2SQL(sprintf("UPDATE `accounts` SET `v3_account_id`=%d WHERE `id`=%d", $v3Account->id, $v2Account->id));

                            $createNewAccount = false;
                        }   else {
                            printf("Account v3 account does not exist for model %sID:%d. Creating..\n",$modelName, $model->id);
                            $v3AccountId = Account::withoutTimestamps()->insertGetId([
                                'account_holder_id' => $model->account_holder_id,
                                'account_type_id' => $v2Account->account_type_id,
                                'finance_type_id' => $v2Account->finance_type_id,
                                'medium_type_id' => $v2Account->medium_type_id,
                                'currency_type_id' => $v2Account->currency_type_id,
                                'v2_account_id' => $v2Account->id,
                            ]);

                            $this->addV2SQL(sprintf("UPDATE `accounts` SET `v3_account_id`=%d WHERE `id`=%d", $v3AccountId, $v2Account->id));
                            $newAccountCreated = true;

                            printf("Accounts created for v2model:\"%s\" with id:%d.\n", $model->id, $v3AccountId);
                        }
                    }

                    if( $newAccountCreated )    {
                        $this->importMap[$v3AccountId] = $v3AccountId;
                    }

                    //Journal Events should be imported seperately for a model using MigrateJournalEventsService

                    // if( $v3AccountId ) {
                    //     $sql = sprintf("SELECT postings.id AS posting_id, postings.*, je.* FROM accounts JOIN postings on postings.account_id=accounts.id JOIN journal_events je ON je.id=postings.journal_event_id where accounts.account_holder_id = %d AND accounts.id=%d AND je.v3_journal_event_id IS NULL ORDER BY je.journal_event_timestamp ASC, postings.posting_timestamp ASC", $model->v2_account_holder_id, $v2Account->id);
                    //     printf(" - Fetching journal_events+postings for program:\"%s\" & account:\"%s\". Please wait...\n", $model->id, $v2Account->id);
                    //     $results = $this->v2db->select($sql);
                    //     if( $this->printSql ) {
                    //         print($sql . "\n");
                    //     }
                    //     // pr($sql);
                    //     // pr($v2Account->id);
                    //     // pr(count($results));
                    //     // exit;
                    //     if( ($countJEAndPostings = sizeof($results)) > 0 ) {
                    //         printf(" - Found %d journal_events+postings for program:\"%s\" & account:\"%s\". Processing...\n",$countJEAndPostings, $model->id, $v2Account->id);
                    //         foreach( $results as $row) {
                    //             if( !$row->v3_journal_event_id &&  !$row->v3_posting_id) {
                    //                 $prime_account_holder_id = $row->prime_account_holder_id;
                    //                 $parent_id = isset($this->cacheJournalEventsMap[$row->parent_journal_event_id]) ? $this->cacheJournalEventsMap[$row->parent_journal_event_id] : null; //If parent is created in the very process
                    //                 if( $row->prime_account_holder_id > 0 ) {
                    //                     $v3User = \App\Models\User::where('v2_account_holder_id', $row->prime_account_holder_id)->first();
                    //                     if( $v3User )   {
                    //                         $prime_account_holder_id = $v3User->account_holder_id;
                    //                     }
                    //                 }
                    //                 $v3JournalEventId = JournalEvent::insertGetId(
                    //                     [
                    //                         'v2_journal_event_id' => $row->journal_event_id,
                    //                         'prime_account_holder_id' => $prime_account_holder_id,
                    //                         'v2_prime_account_holder_id' => $row->prime_account_holder_id,
                    //                         'journal_event_type_id' => $row->journal_event_type_id,
                    //                         'notes' => $row->notes,
                    //                         'event_xml_data_id' => $row->event_xml_data_id,
                    //                         'invoice_id' => $row->invoice_id,
                    //                         'parent_id' => $parent_id,
                    //                         'v2_parent_journal_event_id' => !$parent_id ? $row->parent_journal_event_id : null,
                    //                         'is_read' => $row->is_read,
                    //                         'created_at' => $row->journal_event_timestamp,
                    //                         'journal_event_type_id' => $row->journal_event_type_id,
                    //                     ]
                    //                 );

                    //                 printf(" - New Journal Event \"%d\" created for v2 journal event \"%d\"\n",$v3JournalEventId, $row->journal_event_id);

                    //                 $this->v2db->statement(sprintf("UPDATE `journal_events` SET `v3_journal_event_id`=%d WHERE `id`=%d", $v3JournalEventId, $row->journal_event_id));
                    //                 // $this->addV2SQL(sprintf("UPDATE `journal_events` SET `v3_journal_event_id`=%d WHERE `id`=%d", $v3JournalEventId, $row->journal_event_id));

                    //                 $this->cacheJournalEventsMap[$row->journal_event_id] = $v3JournalEventId;

                    //                 $v3PostingId = Posting::insertGetId([
                    //                     'v2_posting_id' => $row->posting_id,
                    //                     'journal_event_id' => $v3JournalEventId,
                    //                     'account_id' => $v3AccountId,
                    //                     'posting_amount' => $row->posting_amount,
                    //                     'is_credit' => $row->is_credit,
                    //                     'medium_info_id' => $row->medium_info_id,
                    //                     'qty' => $row->qty,
                    //                     'created_at' => $row->posting_timestamp
                    //                 ]);

                    //                 printf(" - New Posting \"%d\" created for v2 posting \"%d\"\n",$v3PostingId, $row->posting_id);

                    //                 $this->v2db->statement(sprintf("UPDATE `postings` SET `v3_posting_id`=%d WHERE `id`=%d", $v3PostingId, $row->posting_id));
                    //             }   else {
                    //                 printf(" - journal_events/postings already imported with v3_journal_event_id:%s, v3_posting_id:%s.\n", $row->v3_journal_event_id, $row->v3_posting_id);
                    //             }
                    //         }
                    //     }   else {
                    //         printf(" - No journal_events+postings found for program:\"%s\" & account:\"%s\".\n", $model->id, $v2Account->id);
                    //     }
                    //     if( $newAccountCreated ) {
                    //         $this->v2db->statement(sprintf("UPDATE `accounts` SET `v3_account_id`=%d WHERE `id`=%d", $v3AccountId, $v2Account->id));
                    //     }
                    // }
                }
                $this->executeV2SQL();
            }   else {
                printf("No accounts found for model: \"%s\"\n", $model->id);
            }
            return $this->importMap;
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

    // public function migrate() {
    //     $this->iteration++;
    //     printf("Migrating programAccounts, iteration:%d\n", $this->iteration);
    //     $v3Programs = Program::whereNotNull('v2_account_holder_id')->skip($this->offset)->take($this->limit)->select(['id', 'v2_account_holder_id', 'account_holder_id', 'name'])->get();
    //     if( ($countV3Programs = count($v3Programs)) > 0)
    //     foreach( $v3Programs as $v2Program ) {
    //         $this->migrateAccounts( $v2Program );
    //     }
    //     $this->offset = $this->offset + $this->limit;
    //     // if( $this->count >= 20 ) exit;
    //     if( $countV3Programs >= $this->limit) {
    //         $this->migrate();
    //     }
    // }
}
