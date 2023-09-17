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
        $query = EventType::query();
        if( !$program->allow_milestone_award )   {
            $query->where('type', '!=', EventType::EVENT_TYPE_MILESTONE_AWARD);
            $query->where('type', '!=', EventType::EVENT_TYPE_MILESTONE_BADGE);
        }
        $eventTypes = $query->get();
        if ( $eventTypes->isNotEmpty() )
        {
            return response( $eventTypes );
        }
        return response( [] );
    }
    public function milestoneFrequency(Organization $organization, Program $program)
    {
        if( !$program->allow_milestone_award )   {
            return([]);
        }
        return response( getMilestoneOptions() );
    }
}
