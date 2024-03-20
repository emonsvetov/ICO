<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\EventType;
use App\Models\Program;

class EventTypeController extends Controller
{
    public function index(Organization $organization, Program $program)
    {
        $excludedEventTypes = [];

        if (!$program->allow_milestone_award) {
            $excludedEventTypes[] = EventType::EVENT_TYPE_MILESTONE_AWARD;
            $excludedEventTypes[] = EventType::EVENT_TYPE_MILESTONE_BADGE;
        }
        if (!$program->uses_peer2peer) {
            $excludedEventTypes[] = EventType::EVENT_TYPE_PEER2PEER;
        }

        $excludedEventTypes = array_merge($excludedEventTypes, [
            EventType::EVENT_TYPE_PROMOTIONAL_AWARD,
            EventType::EVENT_TYPE_AUTO_AWARD,
        ]);

        $eventTypes = EventType::whereNotIn('type', $excludedEventTypes)->get();

        if ($eventTypes->isNotEmpty()) {
            return response($eventTypes);
        }

        return response([]);
    }

    public function milestoneFrequency(Organization $organization, Program $program)
    {
        if( !$program->allow_milestone_award )   {
            return([]);
        }
        return response( getMilestoneOptions() );
    }
}
