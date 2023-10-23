<?php
namespace App\Services\v2migrate;

use Illuminate\Support\Facades\DB;
use Exception;

use App\Models\Merchant;
use App\Models\Program;
use App\Models\Account;
use App\Models\User;
use App\Models\AccountV2Account;

class MigrateAccountsService extends MigrationService
{

    public $offset = 0;
    public $limit = 10;
    public $iteration = 0;
    public $count = 0;
    public array $importMap = [];
    public array $cacheJournalEventsMap = [];
    public bool $isPrintSql = true;
    public bool $useTransactions = true;
    public $modelName = null;


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
            $this->printf("Owner account:%d found\n", $owner->id);
            $this->printf("Finding accounts for Owner:%d in v3\n", $owner->id);
            $accounts = \App\Models\Account::where('account_holder_id', $owner->account_holder_id)->get();
            if( !$accounts || count($accounts) < 2 )    {
                throw new Exception("Owner accounts do not exist. Please check and try again.");
                exit;
            }
            $this->printf("v3- %d accounts found for Owner:%d\n", count($accounts), $owner->id);
            $pullOwnerAccounts = false;
            foreach( $accounts as $account )    {
                if( !$account->v2_account_id )  {
                    //Need to migrate v2 owner accounts
                    $pullOwnerAccounts = true;
                    break;
                }
            }
            if( $pullOwnerAccounts ) {
                $this->printf("Need to pull owner accounts for:%d\n", $owner->id);
                $this->migrateV2OwnerAccounts( $owner );
            }
        }
    }

    public function migrateV2OwnerAccounts( $owner )    {
        $sql = "SELECT `accounts`.* FROM `accounts` JOIN `account_holders` ON account_holders.id=accounts.account_holder_id JOIN `owners` ON `owners`.account_holder_id=account_holders.id AND owners.name LIKE 'Application Owner'";
        $results = $this->v2db->select($sql);

        if( $results ) {
            $this->printf("Found %d owner accounts.\n", count($results));
            foreach( $results as $v2Account ) {
                $this->printf("Migrating single account v2account:%d.\n", $v2Account->id);
                $this->migrateSingleAccount($v2Account, $owner->account_holder_id);
            }
        }
    }

    public function migrateByModel( Program|User|Merchant $model, int $v2_account_holder_id = null ) {
        $CLASS = get_class($model);
        $modelName = strtolower(substr($CLASS, strrpos($CLASS, "\\") + 1 ));
        $this->modelName = $modelName;
        $v2PK = "v2_account_holder_id";
        $v2_account_holder_id = $v2_account_holder_id ?: $model->{$v2PK};

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
            $countV2Accounts = sizeof($v2Accounts);
            // pr($countV2Accounts);
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
        // pr($this->modelName);
        // exit;
        $createNewAccount = true;
        $newAccountCreated = true;
        if( $v2Account->v3_account_id ) {
            $this->printf("\$v2Account->v3_account_id is non zero (%s) for v3:%d. Confirming v3 record..\n", $v2Account->v3_account_id, $v2Account->id);
            $v3Account = Account::find($v2Account->v3_account_id);
            if( $v3Account )    {
                $this->printf("v2Account:v3_account_id IS NOT NULL.\n", $v2Account->id);
                if( $this->modelName == 'user' )    {
                    /**
                     * Here we need to save v3Account <=> V2Account into "account_v2_accounts" table.
                     * There are multiple user accounts for one user in v2 so we need to migrate their accounts into one single account in v3.
                     * */
                    $this->syncAccountAssoc( $v2Account, $v3Account );
                }   else { //if not type user (i.e. it is a program or merchant)
                    if( !$v3Account->v2_account_id )    { //if v2 ref is null
                        $v3Account->v2_account_id = $v2Account->id;
                        $v3Account->save();
                    }
                }
                $createNewAccount = false;
            }   else {
                if( $this->modelName == 'user' )    {
                    /**
                     * Check via account_v2_accounts table
                     * Remove v2 column from accounts table
                     */
                    $accountV2account = AccountV2account::where('v2_account_id', $v2Account->id)->first();
                    if( $accountV2account ) {
                        $v3Account = $accountV2account->user();
                        $createNewAccount = false;
                        $this->printf("Account found by '\$AccountV2account->v2_account_id'.\n");
                        if( !$v2Account->v3_account_id ) {
                            $this->addV2SQL(sprintf("UPDATE `accounts` SET `v3_account_id`=%d WHERE `id`=%d", $v3Account->id, $v2Account->id));
                        }
                    }
                }   else {
                    //check with v2->id
                    $v3Account = Account::where('v2_account_id', $v2Account->id )->first();
                    if( $v3Account )    {
                        $this->printf("Account entry found for v2:%d by '\$v3Account->v2_account_id'.\n", $v2Account->id);
                        //found, need to update v2 record
                        $this->addV2SQL(sprintf("UPDATE `accounts` SET `v3_account_id`=%d WHERE `id`=%d", $v3Account->id, $v2Account->id));
                        $createNewAccount = false;
                    }
                }
            }
        }   else {
            if( $this->modelName == 'user' )    {
                $accountV2account = AccountV2account::where('v2_account_id', $v2Account->id)->first();
                if( $accountV2account ) {
                    $v3Account = $accountV2account->account();
                    $createNewAccount = false;
                }
            }   else {
                $v3Account = Account::where('v2_account_id', $v2Account->id )->first();
                if( $v3Account )    {
                    //found, need to update v2 record
                    $this->addV2SQL(sprintf("UPDATE `accounts` SET `v3_account_id`=%d WHERE `id`=%d", $v3Account->id, $v2Account->id));
                    $createNewAccount = false;
                }
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
                $this->printf("Accounts combination %d-%d-%d-%d exists for v3:ach:%d. Skipping..\n",$v2Account->account_type_id, $v2Account->finance_type_id, $v2Account->medium_type_id, $v2Account->currency_type_id, $v3_account_holder_id);

                // Sync anyway!!
                if( $this->modelName == 'user' )    {
                    $this->syncAccountAssoc($v2Account, $v3Account);
                }   else {
                    if( !$v3Account->v2_account_id ) {
                        $v3Account->v2_account_id = $v2Account->id;
                        $v3Account->save();
                    }
                }

                if( !$v2Account->v3_account_id )    {
                    $this->addV2SQL(sprintf("UPDATE `accounts` SET `v3_account_id`=%d WHERE `id`=%d", $v3Account->id, $v2Account->id));
                }
                $createNewAccount = false;
            }
            if( $createNewAccount ) {
                $v3Account = Account::create([
                    'account_holder_id' => $v3_account_holder_id,
                    'account_type_id' => $v2Account->account_type_id,
                    'finance_type_id' => $v2Account->finance_type_id,
                    'medium_type_id' => $v2Account->medium_type_id,
                    'currency_type_id' => $v2Account->currency_type_id,
                    'v2_account_id' => $this->modelName == 'user' ? null : $v2Account->id,
                ]);

                if( $this->modelName == 'user' )    {
                    $this->syncAccountAssoc($v2Account, $v3Account);
                }

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

    public function syncAccountAssoc($v2Account, $v3Account) {
        $accountV2account = $v3Account->v2_accounts()->where('v2_account_id', $v2Account->id)->first();
        // pr($accountV2account);
        if( $accountV2account ) {
            // pr($accountV2account->toArray());
            $this->printf(" -- accountV2Account assoc found for account v2:%d and v3:%s\n", $v2Account->id, $v3Account->id);
        }   else {
            $newAssoc = new AccountV2Account(['v2_account_id' => $v2Account->id]);
            $v3Account->v2_accounts()->save($newAssoc);
            $this->printf(" -- New accountV2Account assoc added for account v2:%d and v3:%s\n", $v2Account->id, $v3Account->id);
        }
        if( $v3Account->v2_account_id ) {
            $v3Account->v2_account_id = null; //We save them in assocication table
            $v3Account->save();
        }
    }
}
