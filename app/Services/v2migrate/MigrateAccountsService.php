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

    public function migrateOwnerAccounts()  {
        $owner = \App\Models\Owner::join('account_holders', 'account_holders.id', '=', 'owners.account_holder_id')->where(['owners.id' => 1, 'account_holders.context' => 'Owner', 'owners.name' => 'Application Owner'])->first();

        if( !$owner ) {
            throw new Exception("Owner with id:1 does not exist");
            exit;
        }

        if( $owner ) {
            $accounts = \App\Models\Account::where('account_holder_id', $owner->account_holder_id)->get();
            if( !$accounts || count($accounts) < 2 )    {
                throw new Exception("Owner accounts do not exist. Please check and try again.");
                exit;
            }
            foreach( $accounts as $account )    {
                if( !$account->v2_account_id )  {
                    //Need to migrate v2 owner accounts
                    $this->migrateV2OwnerAccounts( $owner );
                }
            }
        }
    }

    public function migrateV2OwnerAccounts( $owner )    {
        $sql = "SELECT `accounts`.* FROM `accounts` JOIN `account_holders` ON account_holders.id=accounts.account_holder_id JOIN `owners` ON `owners`.account_holder_id=account_holders.id AND owners.name LIKE 'Application Owner'";
        $results = $this->v2db->select($sql);

        if( $results ) {
            foreach( $results as $v2Account ) {
                $this->migrateSingleAccount($v2Account, $owner->account_holder_id);
            }
        }
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
            pr($v2_account_holder_id);

            $sql = sprintf("SELECT * FROM accounts WHERE account_holder_id = %d", $v2_account_holder_id);
            $v2Accounts = $this->v2db->select($sql);
            $countV2Accounts = sizeof($v2Accounts);
            pr($countV2Accounts);
            $this->countAccounts += $countV2Accounts;
            if( $countV2Accounts > 0 ) {
                printf("Found %d accounts for %s: \"%s\"\n", $countV2Accounts, $modelName, $model->id);
                // $v2AccountIds = collect($v2Accounts)->pluck('id');
                // pr(implode(",", $v2AccountIds->toArray()));
                // exit;
                foreach( $v2Accounts as $v2Account) {
                    $this->migrateSingleAccount($v2Account, $model->account_holder_id);
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

    public function migrateSingleAccount( $v2Account, $v3_account_holder_id ) {
        $createNewAccount = true;
        $newAccountCreated = true;
        if( $v2Account->v3_account_id ) {
            printf("\$v2Account->v3_account_id is non zero (%s) for v3:%d. Confirming v3 record..\n", $v2Account->v3_account_id, $v2Account->id);
            $v3Account = Account::find($v2Account->v3_account_id);
            if( $v3Account )    {
                printf("Account entry found for v2:%d by '\$v2Account->v3_account_id'. Skipping creation.\n", $v2Account->id);
                if( !$v3Account->v2_account_id )    { //if v2 ref is null
                    $v3Account->v2_account_id = $v2Account->id;
                    $v3Account->save();
                }
                $createNewAccount = false;
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
                'account_holder_id' => $v3_account_holder_id,
                'account_type_id' => $v2Account->account_type_id,
                'finance_type_id' => $v2Account->finance_type_id,
                'medium_type_id' => $v2Account->medium_type_id,
                'currency_type_id' => $v2Account->currency_type_id,
            ])->first();
            if( $v3Account ) { //it exists anyhow!!! (impossible after above checks!!)
                printf("Accounts combination %d-%d-%d-%d exists for v3:ach:%d. Skipping..\n",$v2Account->account_type_id, $v2Account->finance_type_id, $v2Account->medium_type_id, $v2Account->currency_type_id, $v3_account_holder_id);

                // Sync anyway!!
                if( !$v3Account->v2_account_id ) {
                    $v3Account->v2_account_id = $v2Account->id;
                    $v3Account->save();
                }

                $this->addV2SQL(sprintf("UPDATE `accounts` SET `v3_account_id`=%d WHERE `id`=%d", $v3Account->id, $v2Account->id));

                $createNewAccount = false;
            }   else {
                $v3Account = Account::create([
                    'account_holder_id' => $v3_account_holder_id,
                    'account_type_id' => $v2Account->account_type_id,
                    'finance_type_id' => $v2Account->finance_type_id,
                    'medium_type_id' => $v2Account->medium_type_id,
                    'currency_type_id' => $v2Account->currency_type_id,
                    'v2_account_id' => $v2Account->id,
                ]);

                $this->addV2SQL(sprintf("UPDATE `accounts` SET `v3_account_id`=%d WHERE `id`=%d", $v3Account->id, $v2Account->id));
                $newAccountCreated = true;
            }
        }

        if( $newAccountCreated )    {
            $this->importMap[$v3Account->id] = $v3Account->id;
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
