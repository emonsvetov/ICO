<?php

namespace App\Services\v2migrate;

use App\Models\User;
use Exception;

use App\Models\Account;

class MigrateUserAccountsService extends MigrationService
{
    public array $importedUserAccounts = [];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param int $v2AccountHolderID
     * @return array
     * @throws Exception
     */
    public function migrate(int $v2AccountHolderID): array
    {
        if (!$v2AccountHolderID) {
            throw new Exception("Wrong data provided. v2AccountHolderID: {$v2AccountHolderID}");
        }
        $programArgs = ['program' => $v2AccountHolderID];

        $this->printf("Starting user accounts migration\n\n",);
        $v2RootPrograms = $this->read_list_all_root_program_ids($programArgs);
        if (!$v2RootPrograms) {
            throw new Exception("No program found. v2AccountHolderID: {$v2AccountHolderID}");
        }

        $this->migrateUserAccounts($v2RootPrograms);

        return $this->importedUserAccounts;
    }

    /**
     * @throws Exception
     */
    public function migrateUserAccounts(array $v2RootPrograms): void
    {
        foreach ($v2RootPrograms as $v2RootProgram) {
            $this->syncOrCreateAccounts($v2RootProgram);

            $subPrograms = $this->read_list_children_heirarchy(( int )$v2RootProgram->account_holder_id);
            foreach ($subPrograms as $subProgram) {
                $this->syncOrCreateAccounts($subProgram);
            }
        }
    }

    /**
     * @throws Exception
     */
    public function syncOrCreateAccounts($v2Program)
    {
        $v2users = $this->v2_read_list_by_program($v2Program->account_holder_id);

        foreach ($v2users as $v2User) {
            $v3User = User::findOrFail($v2User->v3_user_id);
            $this->migrateSingleUserAccounts($v2User, $v3User);
        }
    }

    /**
     * @param object $v2User
     * @param User $v3User
     * @return void
     */
    public function migrateSingleUserAccounts(object $v2User, User $v3User): void
    {
        $sql = sprintf("SELECT * FROM accounts WHERE account_holder_id = %d", $v2User->account_holder_id);
        $v2Accounts = $this->v2db->select($sql);

        foreach ($v2Accounts as $v2Account) {
            $v3Account = Account::where([
                'account_holder_id' => $v3User->account_holder_id,
                'account_type_id' => $v2Account->account_type_id,
                'finance_type_id' => $v2Account->finance_type_id,
                'medium_type_id' => $v2Account->medium_type_id,
                'currency_type_id' => $v2Account->currency_type_id,
            ])->first();
            $v3AccountId = $v3Account->id ?? null;
            if (!$v3AccountId) {
                $v3AccountId = Account::getIdByColumns([
                    'account_holder_id' => $v3User->account_holder_id,
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

            $this->importedUserAccounts[] = $v3AccountId;
        }
    }
}
