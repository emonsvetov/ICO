<?php

namespace App\Services;

use App\Models\AwardLevel;
use App\Models\Event;
use App\Models\EventAwardLevel;
use App\Models\EventType;
use App\Models\EmailTemplateType;
use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class ProgramEventService
{

    public function __construct()
    {
    }

    /**
     * @param array $data
     */
    public function create(array $data)
    {
        $data = $this->_handleEmailTemplateData($data);

        if ($data['event_type_id'] == EventType::ET_BADGE || $data['event_type_id'] == EventType::ET_CUSTOM) {
            $data['max_awardable_amount'] = 0;
        }
        return Event::create($data);
    }

    /**
     * @param Event $event
     * @param array $data
     */
    public function update(Event $event, array $data)
    {
        $data = $this->_handleEmailTemplateData($data);
        $event->update($data);
        return $event;
    }

    /**
     * @param Event $event
     * @param array $data
     * @return array
     */
    public function updateHierarchyPrepare(Event $event, array $data): array
    {
        $program = Program::where('id', $data['program_id'])->first();
        $topProgramId = $program->getRoot('id')->id;
        $topLevelProgram = Program::where('id', $topProgramId)->first();
        $programs = $topLevelProgram->descendantsAndSelf()->get()->toArray();

        $eventsHierarchy = Event::readListInHierarchyByName($event->name, $topProgramId)->toArray();
        $eventsForView = [];
        foreach ($eventsHierarchy as $eventHierarchy) {
            $eventHierarchy = (object)$eventHierarchy;
            $eventsForView[$eventHierarchy->program_id] = $eventHierarchy;
        }

        $programsForView = [];
        foreach ($programs as $program) {
            $program = (object)$program;
            $program->added = false;
            $program->updated = false;
            $program->event_will_be_updated = isset($eventsForView[$program->id]);
            $program->result = '';
            $programsForView[$program->id] = $program;
        }

        sort($programsForView);

        return $programsForView;
    }

    /**
     * @param Event $event
     * @param array $data
     */
    public function updateHierarchy(Event $event, array $data, array $programsToCopy)
    {
        $program = Program::where('id', $data['program_id'])->first();
        $topProgramId = $program->getRoot('id')->id;
        $topLevelProgram = Program::where('id', $topProgramId)->first();
        $programs = $topLevelProgram->descendantsAndSelf()->get()->toArray();

        $eventsHierarchy = Event::readListInHierarchyByName($event->name, $topProgramId)->toArray();
        $eventsForView = [];
        foreach ($eventsHierarchy as $eventHierarchy) {
            $eventHierarchy = (object)$eventHierarchy;
            $eventsForView[$eventHierarchy->program_id] = $eventHierarchy;
        }

        $eventAwardLevels = AwardLevel::readAllAwardLevelsByEvent($program->id, $event->id)->toArray();
        $eventAwardLevelsToCopy = [];
        foreach ($eventAwardLevels as $eventAwardLevel) {
            $eventAwardLevel = (object)$eventAwardLevel;
            $eventAwardLevelsToCopy[$eventAwardLevel->name] = $eventAwardLevel;
        }

        $programsForView = [];
        foreach ($programs as $program) {
            $program = (object)$program;
            $program->added = false;
            $program->updated = false;
            $program->event_will_be_updated = isset($eventsForView[$program->id]);
            $program->result = '';
            $programsForView[$program->id] = $program;
        }

        $resultProgramsForView = [];
        foreach ($programsToCopy as $programId) {
            $resultProgramsForView[$programId] = $programsForView[$programId];

            try {
                $data['program_id'] = $programId;
                $data = $this->_handleEmailTemplateData($data);

                if ($resultProgramsForView[$programId]->event_will_be_updated) {
                    $eventToCopy = $eventsForView[$programId];
                    $currentAwardLevels = $this->getEventAwardsLevel($eventToCopy->id);
                    foreach ($currentAwardLevels as $currentAwardLevel) {
                        $tmp = EventAwardLevel::find($currentAwardLevel->id);
                        $tmp->delete();
                    }
                    $eventToCopyModel = Event::find($eventToCopy->id);
                    $eventToCopyModel->update($data);
                    $resultProgramsForView[$programId]->updated = true;
                    $resultProgramsForView[$programId]->result = 'updated';
                } else {
                    $eventToCopy = $this->create($data);
                    $resultProgramsForView[$programId]->added = true;
                    $resultProgramsForView[$programId]->result = 'added';
                }

                foreach ($eventAwardLevelsToCopy as $eventAwardLevel) {
                    $awardLevelData = [];
                    $awardLevelData['id'] = 0;
                    $awardLevelData['event_id'] = $eventToCopy->id;
                    $awardLevelData['award_level_id'] = $eventAwardLevel->id;
                    $awardLevelData['amount'] = $eventAwardLevel->amount;
                    $this->storeAwardLevel($awardLevelData);
                }

            } catch (\Exception $exception) {
                $resultProgramsForView[$programId]->result = 'Error';
            }
        }

        sort($resultProgramsForView);
        return $resultProgramsForView;
    }

    private function _handleEmailTemplateData(array $data): array
    {
        if (empty($data['email_template_type_id'])) {

            $emailTemplateType_type = EmailTemplateType::EMAIL_TEMPLATE_TYPE_AWARD;

            $eventType = EventType::find($data['event_type_id']);

            if ($eventType->type == EventType::EVENT_TYPE_PEER2PEER_ALLOCATION) {
                $emailTemplateType_type = EmailTemplateType::EMAIL_TEMPLATE_TYPE_PEER_ALLOCATION;
            }
            if ($eventType->type == EventType::EVENT_TYPE_BADGE) {
                $emailTemplateType_type = EmailTemplateType::EMAIL_TEMPLATE_TYPE_AWARD_BADGE;
            }
            if ($eventType->type == EventType::EVENT_TYPE_PEER2PEER_BADGE) {
                $emailTemplateType_type = EmailTemplateType::EMAIL_TEMPLATE_TYPE_AWARD_BADGE;
            }

            $data['email_template_type_id'] = EmailTemplateType::getIdByType($emailTemplateType_type);
        }
        return $data;
    }

    public function getEventAwardsLevel($eventId)
    {
        $eventAwardLevels = DB::table('event_award_level')
            ->join('award_levels', 'event_award_level.award_level_id', '=', 'award_levels.id')
            ->select('event_award_level.*', 'award_levels.name')
            ->where('event_award_level.event_id', $eventId)
            ->get();
        return $eventAwardLevels;
    }

    public function storeAwardLevel($data)
    {
        $eventAwardLevel = EventAwardLevel::where('id', $data['id'])->first();
        if ($eventAwardLevel) {
            $eventAwardLevel->award_level_id = $data['award_level_id'];
            $eventAwardLevel->amount = $data['amount'];
        } else {
            $eventAwardLevel = new EventAwardLevel();
            $eventAwardLevel->event_id = $data['event_id'];
            $eventAwardLevel->award_level_id = $data['award_level_id'];
            $eventAwardLevel->amount = $data['amount'];

        }
        return $eventAwardLevel->save();
    }

    public function deleteAwardLevel($data)
    {
        $res = false;
        $eventAwardLevel = EventAwardLevel::where('id', $data['id'])->first();
        if ($eventAwardLevel) {
            $res = $eventAwardLevel->delete();
        }
        return $res;
    }
}
