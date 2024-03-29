<?php

namespace App\Services\v2migrate;

use App\Models\Merchant;
use App\Models\Owner;
use App\Models\User;
use Exception;

use App\Models\Account;

class MigrateOwnerAccountsService extends MigrationService
{
    public array $importedOwnerAccounts = [];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return array
     * @throws Exception
     */
    public function migrate(): array
    {
        $owner = Owner::join('account_holders', 'account_holders.id', '=', 'owners.account_holder_id')->where(['owners.id' => 1, 'account_holders.context' => 'Owner', 'owners.name' => 'Application Owner'])->first();
        if( !$owner ) {
            throw new Exception("Owner with id:1 does not exist");
        }

        $this->syncOrCreateAccounts($owner);

        return [
            'success' => TRUE,
            'info' => "migrated " . count($this->importedOwnerAccounts) . " items",
        ];
    }

    /**
     * @param $owner
     * @return void
     */
    public function syncOrCreateAccounts($owner): void
    {
        $sql = sprintf("SELECT * FROM accounts WHERE account_holder_id = 1");
        $v2Accounts = $this->v2db->select($sql);

        foreach ($v2Accounts as $v2Account) {
            $v3Account = Account::where([
                'account_holder_id' => $owner->account_holder_id,
                'account_type_id' => $v2Account->account_type_id,
                'finance_type_id' => $v2Account->finance_type_id,
                'medium_type_id' => $v2Account->medium_type_id,
                'currency_type_id' => $v2Account->currency_type_id,
            ])->first();
            $v3AccountId = $v3Account->id ?? null;

            if (!$v3AccountId) {
                $v3AccountId = Account::getIdByColumns([
                    'account_holder_id' => $owner->account_holder_id,
                    'account_type_id' => $v2Account->account_type_id,
                    'finance_type_id' => $v2Account->finance_type_id,
                    'medium_type_id' => $v2Account->medium_type_id,
                    'currency_type_id' => $v2Account->currency_type_id,
                    'v2_account_id' => $v2Account->id,
                ]);

            }

            if ($v2Account->v3_account_id != $v3AccountId) {
                $this->v2db->statement(sprintf("UPDATE `accounts` SET `v3_account_id`=%d WHERE `id`=%d", $v3AccountId, $v2Account->id));
            }

            $this->importedOwnerAccounts[] = $v3AccountId;
        }
    }
}
