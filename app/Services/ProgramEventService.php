<?php

namespace App\Services;

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
        $data = $this->_handleEmailTemplateData( $data );

        if($data['event_type_id'] == EventType::ET_BADGE){
            $data['max_awardable_amount']= 0;
        }
        return Event::create( $data );
    }
    /**
     * @param Event $event
     * @param array $data
     */
    public function update(Event $event, array $data)
    {
        $data = $this->_handleEmailTemplateData($data);
        $event->update( $data );
        return $event;
    }

    private function _handleEmailTemplateData(array $data): array
    {
        if( empty($data['email_template_type_id']) ) {

            $emailTemplateType_type = EmailTemplateType::EMAIL_TEMPLATE_TYPE_AWARD;

            $eventType = EventType::find($data['event_type_id']);

            if( $eventType->type == EventType::EVENT_TYPE_PEER2PEER_ALLOCATION )
            {
                $emailTemplateType_type = EmailTemplateType::EMAIL_TEMPLATE_TYPE_PEER_ALLOCATION;
            }
            if( $eventType->type == EventType::EVENT_TYPE_BADGE )
            {
                $emailTemplateType_type = EmailTemplateType::EMAIL_TEMPLATE_TYPE_AWARD_BADGE;
            }
            if( $eventType->type == EventType::EVENT_TYPE_PEER2PEER_BADGE )
            {
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
        if ($eventAwardLevel){
            $eventAwardLevel->award_level_id = $data['award_level_id'];
            $eventAwardLevel->amount = $data['amount'];
        }else{
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
