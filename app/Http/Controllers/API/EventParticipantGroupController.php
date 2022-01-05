<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\EventParticipantGroupRequest;
use App\Models\ParticipantGroup;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Event;

class EventParticipantGroupController extends Controller
{
    
    public function index( Organization $organization, Program $program, Event $event )
    {
        
        if ( !( $organization->id == $program->organization_id && $program->id == $event->program_id ) )
        {
            return response(['errors' => 'Invalid Organization or Program'], 422);
        }        
                       

        if ( $event->participant_groups->isNotEmpty() ) 
        { 
            return response( $event->participant_groups );
        }

        return response( [] );
    }

    public function store( EventParticipantGroupRequest $request, Organization $organization, Program $program, Event $event )
    {
                 
        if ( !( $organization->id == $program->organization_id && $program->id == $event->program_id ) )
        {
            return response(['errors' => 'Invalid Organization, Program or Event'], 422);
        }

        $participantGroupIds = $request->validated();
        $insert = [];
        $validParticipantGroupIds = [];

        foreach ( $participantGroupIds as $key => $participantGroup )
        {            
            $validParticipantGroupIds[] = $participantGroup['participant_group_id'];

            $insert[] = $participantGroup + [ 'event_id' => $event->id ];
        }

        $dbParticipantGroupCount = ParticipantGroup::where('organization_id', $organization->id)
                                                    ->whereIn('id', $validParticipantGroupIds )
                                                    ->count();
        
        if ( $dbParticipantGroupCount !== count( array_unique($validParticipantGroupIds) ))
        {
            return response(['errors' => 'Not all ParticipantGroups belong to the Organization'], 422);
        }


        $event->participant_groups()->newPivotQuery()->upsert( 
                $insert, 
                ['participant_group_id', 'event_id'], 
                ['participant_group_id', 'event_id']
        );

        if ( !$event->participant_groups )
        {
            return response(['errors' => 'Event Creation failed'], 422);
        }

        
        
        return response([ 'eventParticipantGroup' => $event->participant_groups ]);
    }

    public function destroy( EventParticipantGroupRequest $request, Organization $organization, Program $program, Event $event )
    {
        if ( !( $organization->id == $program->organization_id && $program->id == $event->program_id ) )
        {
            return response(['errors' => 'Invalid Organization, Program or Event'], 422);
        }

        $participantGroupIds = $request->validated();
        $delete = [];
        $validParticipantGroupIds = [];

        foreach ( $participantGroupIds as $key => $participantGroup )
        {            
            $validParticipantGroupIds[] = $participantGroup['participant_group_id'];

            $delete[] = $participantGroup + [ 'event_id' => $event->id ];
        }

        $dbParticipantGroupCount = ParticipantGroup::where('organization_id', $organization->id)
                                                    ->whereIn('id', $validParticipantGroupIds )
                                                    ->count();
        
        if ( $dbParticipantGroupCount !== count( array_unique($validParticipantGroupIds) ))
        {
            return response(['errors' => 'Not all ParticipantGroups belong to the Organization'], 422);
        }

        

        $event->participant_groups()->detach( $delete );

        if ( !$event->participant_groups )
        {
            return response(['errors' => 'Event Creation failed'], 422);
        }

        
        
        return response([ 'eventParticipantGroup' => $event->participant_groups ]);
    }
}
