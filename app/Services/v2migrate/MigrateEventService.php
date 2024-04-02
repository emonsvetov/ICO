<?php

namespace App\Services\v2migrate;

use App\Models\AwardLevel;
use App\Models\Event;
use App\Models\EventAwardLevel;
use App\Models\EventLedgerCode;
use App\Models\Program;
use Illuminate\Support\Facades\DB;
use Exception;

class MigrateEventService extends MigrationService
{
    public function __construct()
    {
        parent::__construct();
    }

    public function migrate($v2AccountHolderID)
    {
        $res['success'] = true;
        $res['itemsCount'] = 0;
        $v2Helper = new V2Helper();
        $v2SubPrograms = $v2Helper->read_list_children_heirarchy($v2AccountHolderID);
        $eMigrate = $this->syncProgramEventsRelations($v2AccountHolderID);
        $res['success'] = $eMigrate['success'];
        $res['itemsCount'] += $eMigrate['itemsCount'];
        foreach ($v2SubPrograms as $subProgram) {
            $eMigrate = $this->syncProgramEventsRelations($subProgram->account_holder_id);
            $res['success'] = $eMigrate['success'];
            $res['itemsCount'] += $eMigrate['itemsCount'];
        }
        return [
            'success' => $res['success'],
            'info' => "number of lines ". $res['itemsCount'],
        ];
    }

    public function migrateEventAwardLevel($eventIdV3, $eventIdV2)
    {
        $v2ProgramEventAwardLevel = $this->v2db->select(
            sprintf("select * from event_award_level where event_award_level.event_id=%d", $eventIdV2)
        );

        if (count($v2ProgramEventAwardLevel)) {
            foreach ($v2ProgramEventAwardLevel as $item) {
                $eventAwardLevel = EventAwardLevel::where('event_id', $eventIdV3)->first();
                $awardLevel = AwardLevel::where('v2id', $item->award_level_id)->first();

                if ($eventAwardLevel) {
                    $eventAwardLevel->amount = $item->amount;
                    $eventAwardLevel->award_level_id = $awardLevel->id; //todo
                    $eventAwardLevel->save();
                } else {
                    $eventAwardLevel = new EventAwardLevel();
                    $eventAwardLevel->event_id = $eventIdV3;
                    $eventAwardLevel->award_level_id = $awardLevel->id; //todo
                    $eventAwardLevel->amount = $item->amount;
                    $eventAwardLevel->save();
                }
            }
        }
    }

    public function migrateEventLedgerCodes($v2AccountHolderID, $v3AccountHolderID)
    {
        $v2ProgramEventLedgerCodes = $this->v2db->select(
            sprintf("select * from event_ledger_codes where event_ledger_codes.program_id=%d", $v2AccountHolderID)
        );

        if (count($v2ProgramEventLedgerCodes)) {
            foreach ($v2ProgramEventLedgerCodes as $item) {
                $eventAwardLevel = EventLedgerCode::where('ledger_code', $item->ledger_code)
                    ->where('program_id', $v3AccountHolderID)
                    ->first();
                if ($eventAwardLevel) {
                    $eventAwardLevel->ledger_code = $item->ledger_code;
                    $eventAwardLevel->event_ledger_codes_v2id = $item->id;
                } else {
                    $eventAwardLevel = new EventLedgerCode();
                    $eventAwardLevel->program_id = $v3AccountHolderID;
                    $eventAwardLevel->ledger_code = $item->ledger_code;
                    $eventAwardLevel->event_ledger_codes_v2id = $item->id;
                    $eventAwardLevel->save();
                }
            }
        }
    }

    public function syncProgramEventsRelations($v2AccountHolderID)
    {
        $res = true;
        $itemsCount = 0;
        $v2Program = $this->v2db->select(
            sprintf("select * from programs where account_holder_id = %d", $v2AccountHolderID)
        )[0];

        $program = Program::where('name', $v2Program->name)->first();
        if (!$program) {
            return [
                'success' => $res,
                'itemsCount' => $itemsCount,
            ];
        }
        $v2ProgramEvents = $this->v2db->select(
            sprintf("select event_templates.*, state_types.state from event_templates
                            left join state_types on event_templates.event_state_id = state_types.id
                           where program_account_holder_id = %d", $v2AccountHolderID)
        );


        $this->migrateEventLedgerCodes($v2AccountHolderID, $program->id);
        $itemsCount = count($v2ProgramEvents);

        foreach ($v2ProgramEvents as $item) {
            $event = Event::where('name', $item->name)
                ->where('organization_id', $program->organization_id)
                ->where('program_id', $program->id)
                ->first();

            if ($event) {
                if ($item->state == 'Active') {
                    $event->enable = true;
                } else {
                    $event->enable = false;
                }

                $event->v2_event_id = $item->id;
                $event->event_type_id = $item->event_type_id;
                $event->post_to_social_wall = $item->post_to_social_wall;
                $event->email_template_type_id = $item->email_template_id;
                $event->award_message_editable = $item->award_message_editable;
                if ($item->ledger_code) {
                    $eventAwardLevel = EventLedgerCode::where('event_ledger_codes_v2id', $item->ledger_code)
                        ->where('program_id', $program->id)
                        ->first();
                    if ($eventAwardLevel) {
                        $event->ledger_code = $eventAwardLevel->id;
                    } else {
                        $event->ledger_code = null;
                    }

                } else {
                    $event->ledger_code = null;
                }

                $event->max_awardable_amount = 0;
                $event->event_icon_id = 2;
                $event->icon = $item->icon;
                $event->only_internal_redeemable = $item->only_internal_redeemable;
                $event->message = $item->notification_body;
                $res = $event->save();
                if (!$res) {
                    break;
                }
                $this->migrateEventAwardLevel($event->id, $item->id);
            } else {
                $event = new Event();
                $event->organization_id = $program->organization_id;
                $event->program_id = $program->id;
                $event->v2_event_id = $item->id;

                if ($item->state == 'Active') {
                    $event->enable = true;
                } else {
                    $event->enable = false;
                }
                $event->name = $item->name;
                $event->post_to_social_wall = $item->post_to_social_wall;
                $event->event_type_id = $item->event_type_id;
                if ($item->ledger_code) {
                    $eventAwardLevel = EventLedgerCode::where('event_ledger_codes_v2id', $item->ledger_code)
                        ->where('program_id', $program->id)
                        ->first();

                    if ($eventAwardLevel) {
                        $event->ledger_code = $eventAwardLevel->id;
                    } else {
                        $event->ledger_code = null;
                    }

                } else {
                    $event->ledger_code = null;
                }
                $event->email_template_type_id = $item->email_template_id;
                $event->award_message_editable = $item->award_message_editable;
                $event->max_awardable_amount = 0;
                $event->event_icon_id = 2;
                $event->icon = $item->icon;
                $event->only_internal_redeemable = $item->only_internal_redeemable;
                $event->message = $item->notification_body;
                $res = $event->save();
                if (!$res) {
                    break;
                }
                $this->migrateEventAwardLevel($event->id, $item->id);
            }
        }

        return [
            'success' => $res,
            'itemsCount' => $itemsCount,
        ];
    }
}
