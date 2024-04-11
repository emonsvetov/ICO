<?php

namespace App\Services\v2migrate;

use App\Models\EventXmlData;
use App\Models\User;
use App\Models\UserV2User;
use Illuminate\Support\Facades\DB;
use Exception;

use App\Models\JournalEvent;
use App\Models\Program;
use App\Models\Account;
use App\Models\Posting;

class MigrateJournalEventService extends MigrationService
{
    public array $importedJournalEvents = [];

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

        $this->printf("Starting journal event migration\n\n",);
        $v2RootPrograms = $this->read_list_all_root_program_ids($programArgs);
        if (!$v2RootPrograms) {
            throw new Exception("No program found. v2AccountHolderID: {$v2AccountHolderID}");
        }

        $this->migrateAll($v2RootPrograms);
        $this->executeV2SQL();

        return [
            'success' => TRUE,
            'info' => "migrated " . count($this->importedJournalEvents) . " items",
        ];

    }

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

        $this->migrateJournalEvents($accountHolderIds);
    }

    /**
     * @throws Exception
     */
    public function migrateJournalEvents($accountHolderIds)
    {
        $v2Data = $this->getJournalEventByAccountIds($accountHolderIds);
        foreach ($v2Data as $item) {
            $this->syncOrCreateJournalEvent($item);
        }
    }

    public function syncOrCreateJournalEvent($v2JournalEvent)
    {
        $parent_journal_event_id = null;
        if ((int)$v2JournalEvent->parent_journal_event_id > 0) {
            $v3ParentTmp = JournalEvent::where('v2_journal_event_id', $v2JournalEvent->parent_journal_event_id)->first();
            if ($v3ParentTmp) {
                $parent_journal_event_id = $v3ParentTmp->id;
            } else {
                // Yep, we have such broken rows in V2. So just close your eyes to this problem.
//                throw new Exception("Can`t found parent Journal Event: {$v2JournalEvent->parent_journal_event_id}");
            }
        }

        $data = [
            'v2_journal_event_id' => $v2JournalEvent->id,
            'prime_account_holder_id' => $v2JournalEvent->v3_user_id ?? 0,
            'journal_event_type_id' => $v2JournalEvent->journal_event_type_id,
            'notes' => $v2JournalEvent->notes,
            'event_xml_data_id' => $v2JournalEvent->event_xml_data_v3_id,
            'invoice_id' => $v2JournalEvent->invoice_id, //it is always null in v2, so I guess it is not in use and was replaced by invoice_journal_events table
            'parent_journal_event_id' => $parent_journal_event_id,
            'is_read' => $v2JournalEvent->is_read,
            'created_at' => $v2JournalEvent->journal_event_timestamp
        ];

        $dataSearch = $data;
        unset($dataSearch['notes']);
        unset($dataSearch['invoice_id']);

        $v3JournalEvent = JournalEvent::where($dataSearch)->first();
        if (!$v3JournalEvent){
            $v3JournalEvent = JournalEvent::create($data);
        }
        $this->printf("JournalEvent done: {$v3JournalEvent->id}. Count= ".count($this->importedJournalEvents)." \n\n");

        if ($v3JournalEvent) {
            $this->addV2SQL(sprintf("UPDATE `journal_events` SET `v3_journal_event_id`=%d WHERE `id`=%d", $v3JournalEvent->id, $v2JournalEvent->id));
            $this->importedJournalEvents[] = $v3JournalEvent->id;
        }
    }
}
