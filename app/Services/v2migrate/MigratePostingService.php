<?php

namespace App\Services\v2migrate;

use App\Models\EventXmlData;
use App\Models\Merchant;
use App\Models\User;
use App\Models\UserV2User;
use Illuminate\Support\Facades\DB;
use Exception;

use App\Models\JournalEvent;
use App\Models\Program;
use App\Models\Account;
use App\Models\Posting;

class MigratePostingService extends MigrationService
{
    public array $importedPostings = [];

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

        $this->printf("Starting posting migration\n\n",);
        $v2RootPrograms = $this->read_list_all_root_program_ids($programArgs);
        if (!$v2RootPrograms) {
            throw new Exception("No program found. v2AccountHolderID: {$v2AccountHolderID}");
        }

        $this->migrateAll($v2RootPrograms);
        $this->executeV2SQL();

        return [
            'success' => TRUE,
            'info' => "migrated " . count($this->importedPostings) . " items",
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
            $v2users = $this->v2_read_list_by_program($v2RootProgram->account_holder_id);
            foreach ($v2users as $v2User) {
                $accountHolderIds[] = $v2User->account_holder_id;
            }

            $subPrograms = $this->read_list_children_heirarchy(( int )$v2RootProgram->account_holder_id);
            foreach ($subPrograms as $subProgram) {
                $accountHolderIds[] = $subProgram->account_holder_id;
                $v2users = $this->v2_read_list_by_program($subProgram->account_holder_id);
                foreach ($v2users as $v2User) {
                    $accountHolderIds[] = $v2User->account_holder_id;
                }
            }
        }
        $accountHolderIds = array_unique($accountHolderIds);

        $this->migratePostings($accountHolderIds);
    }

    /**
     * @throws Exception
     */
    public function migratePostings($accountHolderIds)
    {
        $journalEvents = $this->getJournalEventsByIds($accountHolderIds);
        $journalEventIds = [];
        foreach ($journalEvents as $journalEvent){
            $journalEventIds[] = $journalEvent->id;
        }

        $v2Data = $this->getPostingsByJournalEventIds($journalEventIds);

        foreach ($v2Data as $item) {
            $this->syncOrCreatePosting($item);
        }
    }

    public function syncOrCreatePosting($v2posting)
    {
        $data = [
            'v2_posting_id' => $v2posting->id,
            'journal_event_id' => $v2posting->v3_journal_event_id,
            'account_id' => $v2posting->v3_account_id,
            'posting_amount' => $v2posting->posting_amount,
            'is_credit' => $v2posting->is_credit,
            'medium_info_id' => $v2posting->v3_medium_info_id,
            'qty' => $v2posting->qty,
            'created_at' => $v2posting->posting_timestamp
        ];

        $dataSearch = $data;

        $v3Posting = Posting::where($dataSearch)->first();
        if (!$v3Posting){
            $v3Posting = Posting::create($data);
        }
        $this->printf("Posting done: {$v3Posting->id}. Count= ".count($this->importedPostings)." \n\n");

        if ($v3Posting) {
            $this->addV2SQL(sprintf("UPDATE `postings` SET `v3_posting_id`=%d WHERE `id`=%d", $v3Posting->id, $v2posting->id));
            $this->importedPostings[] = $v3Posting->id;
        }
    }
}
