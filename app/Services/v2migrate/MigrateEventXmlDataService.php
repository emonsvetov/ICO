<?php

namespace App\Services\v2migrate;

use App\Models\Event;
use App\Models\EventXmlData;
use App\Models\User;
use App\Models\UserV2User;
use Exception;

use App\Models\Program;

class MigrateEventXmlDataService extends MigrationService
{
    public array $importedEventXmlData = [];
    private MigrateUsersService $migrateUsersService;

    public function __construct(MigrateUsersService $migrateUsersService)
    {
        parent::__construct();
        $this->migrateUsersService = $migrateUsersService;
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

        $this->printf("Starting event xml data migration\n\n",);
        $v2RootPrograms = $this->read_list_all_root_program_ids($programArgs);
        if (!$v2RootPrograms) {
            throw new Exception("No program found. v2AccountHolderID: {$v2AccountHolderID}");
        }

        $this->migrateEventXmlData($v2RootPrograms);
        $this->executeV2SQL();

        return [
            'success' => TRUE,
            'info' => "migrated " . count($this->importedEventXmlData) . " items",
        ];

    }

    public function migrateEventXmlData(array $v2RootPrograms): void
    {
        foreach ($v2RootPrograms as $v2RootProgram) {
            $this->migrateForSingleProgram($v2RootProgram);

            $subPrograms = $this->read_list_children_heirarchy(( int )$v2RootProgram->account_holder_id);
            foreach ($subPrograms as $subProgram) {
                $this->migrateForSingleProgram($subProgram);
            }
        }
    }

    /**
     * @throws Exception
     */
    public function migrateForSingleProgram($v2Program)
    {
        $v3Program = Program::findOrFail($v2Program->v3_program_id);
        $v2UserIds = [];
        $v2Users = $this->v2_read_list_by_program($v2Program->account_holder_id);
        foreach ($v2Users as $v2User) {
            $v2UserIds[] = $v2User->account_holder_id;
        }

        $v2EventXmlList = $this->getEventXmlDataByAccountHolderId(array_merge([$v2Program->account_holder_id], $v2UserIds));
        foreach ($v2EventXmlList as $v2Data) {
            $this->syncOrCreateEventXmlData($v2Data, $v3Program);
        }
    }

    public function syncOrCreateEventXmlData($v2Data, $v3Program)
    {
        $userV2User = UserV2User::where('v2_user_account_holder_id', $v2Data->awarder_account_holder_id)->first();
        $v3UserTmp = $userV2User ? User::find($userV2User->user_id)->first() : null;
        if (!$v3UserTmp) {
            $v2User = $this->v2GetUserById($v2Data->awarder_account_holder_id);
            $v3UserTmp = $this->migrateUsersService->migrateOnlyUser($v2User, $v3Program);
        }
        $awarderAccountHolderId = $v3UserTmp->account_holder_id;

        $v3EventId = 0;
        if ($v2Data->event_template_id) {
            $v3EventTmp = Event::where('v2_event_id', $v2Data->event_template_id)->first();
            if ($v3EventTmp) {
                $v3EventId = $v3EventTmp->id;
            } else {
                $v2EventTmp = $this->getEventById($v2Data->event_template_id);
                if ($v2EventTmp) {
                    throw new Exception("V2 Event not imported: {$v2Data->event_template_id}");
                }
                $v3EventId = $this->minusPrefix . $v2Data->event_template_id;
            }
        }

        $data = [
            'v2_id' => $v2Data->id,
            'awarder_account_holder_id' => $awarderAccountHolderId,
            'name' => $v2Data->name,
            'award_level_name' => $v2Data->award_level_name,
            'amount_override' => $v2Data->amount_override,
            'notification_body' => $v2Data->notification_body,
            'notes' => $v2Data->notes,
            'referrer' => $v2Data->referrer,
            'email_template_id' => $this->minusPrefix . $v2Data->email_template_id,
            'event_type_id' => $v2Data->event_type_id,
            'event_template_id' => $v3EventId,
            'icon' => $v2Data->icon,
            'xml' => $v2Data->xml,
            'award_transaction_id' => $v2Data->award_transaction_id,
            'lease_number' => $v2Data->lease_number,
            'token' => $v2Data->token
        ];
        $dataSearch = $data;
        unset($dataSearch['award_level_name']);
        unset($dataSearch['notification_body']);
        unset($dataSearch['notes']);
        unset($dataSearch['referrer']);
        unset($dataSearch['email_template_id']);
        unset($dataSearch['event_type_id']);
        unset($dataSearch['event_template_id']);
        unset($dataSearch['xml']);
        unset($dataSearch['award_transaction_id']);
        unset($dataSearch['lease_number']);
        unset($dataSearch['token']);

        $v3EventXmlData = EventXmlData::where($dataSearch)->first();
        if (!$v3EventXmlData){
            $v3EventXmlData = EventXmlData::create($data);
        }
        $this->printf("EventXmlData done: {$v3EventXmlData->id}. Count= ".count($this->importedEventXmlData)." \n\n");

        if ($v3EventXmlData) {
            $this->addV2SQL(sprintf("UPDATE `event_xml_data` SET `v3_id`=%d WHERE `id`=%d", $v3EventXmlData->id, $v2Data->id));
            $this->importedEventXmlData[] = $v3EventXmlData->id;
        }
    }
}
