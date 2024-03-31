<?php

namespace App\Services\v2migrate;

use App\Models\ProgramBudget;
use App\Models\SocialWallPost;
use Illuminate\Support\Facades\DB;
use Exception;

use App\Models\JournalEvent;
use App\Models\Program;

class MigrateBudgetService extends MigrationService
{
    public array $importedBudgets = [];

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

        $this->printf("Starting budget migration\n\n",);
        $v2RootPrograms = $this->read_list_all_root_program_ids($programArgs);
        if (!$v2RootPrograms) {
            throw new Exception("No program found. v2AccountHolderID: {$v2AccountHolderID}");
        }

        $this->migrateAll($v2RootPrograms);
        $this->executeV2SQL();

        return [
            'success' => TRUE,
            'info' => "migrated " . count($this->importedBudgets) . " items",
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

        $this->migrateBudgets($accountHolderIds);
    }

    /**
     * @throws Exception
     */
    public function migrateBudgets($accountHolderIds)
    {
        $v2Data = $this->getBudgetsByIds($accountHolderIds);

        foreach ($v2Data as $item) {
            $this->syncOrCreateBudget($item);
        }

    }

    public function syncOrCreateBudget($v2Budget)
    {
        $data = [
            'program_id' => $v2Budget->v3_program_id,
            'budget' => $v2Budget->budget,
            'month' => $v2Budget->month,
            'year' => $v2Budget->year,
            'is_notified' => $v2Budget->is_notified,
        ];

        $dataSearch = $data;

        $v3Budget = ProgramBudget::where($dataSearch)->first();
        if (!$v3Budget) {
            $v3Budget = ProgramBudget::create($data);
        }
        $this->printf("Budget done: {$v3Budget->id}. Count= " . count($this->importedBudgets) . " \n\n");

        if ($v3Budget) {
            $this->addV2SQL(sprintf("UPDATE `program_budget` SET `v3_id`=%d
                        WHERE
                            `program_account_holder_id`=%d
                            AND `year`=%d
                            AND `month`=%d
                            ",
                $v3Budget->id,
                $v2Budget->program_account_holder_id,
                $v2Budget->year,
                $v2Budget->month,
            ));
            $this->importedBudgets[] = $v3Budget->id;
        }
    }

}
