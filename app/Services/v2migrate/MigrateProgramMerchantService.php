<?php

namespace App\Services\v2migrate;

use App\Models\EventXmlData;
use App\Models\Merchant;
use App\Models\ProgramMerchant;
use App\Models\User;
use App\Models\UserV2User;
use Illuminate\Support\Facades\DB;
use Exception;

use App\Models\JournalEvent;
use App\Models\Program;
use App\Models\Account;
use App\Models\Posting;

class MigrateProgramMerchantService extends MigrationService
{
    public array $importedProgramMerchants = [];

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

        $this->printf("Starting program merchants migration\n\n",);
        $v2RootPrograms = $this->read_list_all_root_program_ids($programArgs);
        if (!$v2RootPrograms) {
            throw new Exception("No program found. v2AccountHolderID: {$v2AccountHolderID}");
        }

        $this->migrateAll($v2RootPrograms);
        $this->executeV2SQL();

        return [
            'success' => TRUE,
            'info' => "migrated " . count($this->importedProgramMerchants) . " items",
        ];

    }

    /**
     * @throws Exception
     */
    public function migrateAll(array $v2RootPrograms): void
    {
        $accountHolderIds = [];
        foreach ($v2RootPrograms as $v2RootProgram) {
            $accountHolderIds[] = $v2RootProgram->account_holder_id;

            $subPrograms = $this->read_list_children_heirarchy(( int )$v2RootProgram->account_holder_id);
            foreach ($subPrograms as $subProgram) {
                $accountHolderIds[] = $subProgram->account_holder_id;
            }
        }
        $accountHolderIds = array_unique($accountHolderIds);

        $this->migrateProgramMerchants($accountHolderIds);
    }

    /**
     * @throws Exception
     */
    public function migrateProgramMerchants($accountHolderIds)
    {
        $v2Data = $this->getProgramMerchantsByAccountIds($accountHolderIds);

        foreach ($v2Data as $item) {
            $this->syncOrCreateProgramMerchant($item);
        }
    }

    public function syncOrCreateProgramMerchant($v2ProgramMerchant)
    {
        $data = [
            'program_id' => $v2ProgramMerchant->v3_program_id,
            'merchant_id' => $v2ProgramMerchant->v3_merchant_id,
            'featured' => (int)$v2ProgramMerchant->featured,
            'cost_to_program' => (int)$v2ProgramMerchant->cost_to_program,
        ];
        $dataSearch = $data;

        $v3ProgramMerchant = ProgramMerchant::where($dataSearch)->first();
        if (!$v3ProgramMerchant){
            $v3ProgramMerchant = ProgramMerchant::create($data);
        }
        $this->printf("Program Merchant done: {$v3ProgramMerchant->program_id}. Count= ".count($this->importedProgramMerchants)." \n\n");

        if ($v3ProgramMerchant) {
            $this->importedProgramMerchants[] = $v3ProgramMerchant->program_id . '_' . $v3ProgramMerchant->merchant_id;
        }
    }
}

