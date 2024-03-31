<?php

namespace App\Services\v2migrate;

use App\Models\Merchant;
use App\Models\User;
use Exception;

use App\Models\Account;

class MigrateMerchantAccountsService extends MigrationService
{
    public array $importedMerchantAccounts = [];

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
        $v2MerchantHierarchyList = $this->read_list_hierarchy();

        foreach ($v2MerchantHierarchyList as $v2Merchant) {
            $v3Merchant = Merchant::findOrFail($v2Merchant->v3_merchant_id);
            $this->syncOrCreateMerchant($v2Merchant, $v3Merchant);
        }

        return [
            'success' => TRUE,
            'info' => "migrated " . count($this->importedMerchantAccounts) . " items",
        ];
    }

    /**
     * @param object $v2Merchant
     * @param Merchant $v3Merchant
     * @return void
     */
    public function syncOrCreateMerchant(object $v2Merchant, Merchant $v3Merchant): void
    {
        $sql = sprintf("SELECT * FROM accounts WHERE account_holder_id = %d", $v2Merchant->account_holder_id);
        $v2Accounts = $this->v2db->select($sql);

        foreach ($v2Accounts as $v2Account) {
            $v3Account = Account::where([
                'account_holder_id' => $v3Merchant->account_holder_id,
                'account_type_id' => $v2Account->account_type_id,
                'finance_type_id' => $v2Account->finance_type_id,
                'medium_type_id' => $v2Account->medium_type_id,
                'currency_type_id' => $v2Account->currency_type_id,
            ])->first();
            $v3AccountId = $v3Account->id ?? null;

            if (!$v3AccountId) {
                $v3AccountId = Account::getIdByColumns([
                    'account_holder_id' => $v3Merchant->account_holder_id,
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

            $this->importedMerchantAccounts[] = $v3AccountId;
        }
    }
}
