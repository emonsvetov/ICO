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
    public array $importedProgramAccounts = [];

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

        $this->printf("Starting program accounts migration\n\n",);
        $v2RootPrograms = $this->read_list_all_root_program_ids($programArgs);
        if (!$v2RootPrograms) {
            throw new Exception("No program found. v2AccountHolderID: {$v2AccountHolderID}");
        }

        $this->migrateProgramAccounts($v2RootPrograms);

        return [
            'success' => TRUE,
            'info' => "migrated " . count($this->importedProgramAccounts) . " items",
        ];

    }

    public function migrateProgramAccounts(array $v2RootPrograms): void
    {
        foreach ($v2RootPrograms as $v2RootProgram) {
            $this->printf("Starting migrations for root program: {$v2RootProgram->account_holder_id}\n",);
            $sql = sprintf("SELECT * FROM accounts WHERE account_holder_id = %d", $v2RootProgram->account_holder_id);
            $v2Accounts = $this->v2db->select($sql);
            $this->syncOrCreateAccounts($v2RootProgram, $v2Accounts);

            $subPrograms = $this->read_list_children_heirarchy(( int )$v2RootProgram->account_holder_id);
            foreach ($subPrograms as $subProgram) {
                $sql = sprintf("SELECT * FROM accounts WHERE account_holder_id = %d", $subProgram->account_holder_id);
                $v2Accounts = $this->v2db->select($sql);
                $this->syncOrCreateAccounts($subProgram, $v2Accounts);
            }
        }
    }

    public function syncOrCreateAccounts($v2Program, $v2Accounts)
    {
        $v3Program = Program::findOrFail($v2Program->v3_program_id);
        foreach ($v2Accounts as $v2Account) {
            $v3Account = Account::where([
                'account_holder_id' => $v3Program->account_holder_id,
                'account_type_id' => $v2Account->account_type_id,
                'finance_type_id' => $v2Account->finance_type_id,
                'medium_type_id' => $v2Account->medium_type_id,
                'currency_type_id' => $v2Account->currency_type_id,
            ])->first();
            $v3AccountId = $v3Account->id ?? null;
            if (!$v3AccountId) {
                $v3AccountId = Account::getIdByColumns([
                    'account_holder_id' => $v3Program->account_holder_id,
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

            $this->importedProgramAccounts[] = $v3AccountId;
        }
    }
}
