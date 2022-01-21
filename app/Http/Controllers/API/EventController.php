<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\EventRequest;
use App\Models\ParticipantGroup;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Event;
use DB;

class EventController extends Controller
{
    
    public function index( Organization $organization, Program $program )
    {
        
        if ( $organization->id != $program->organization_id )
        {
            return response(['errors' => 'Invalid Organization or Program'], 422);
        }
        
        $events = Event::where('organization_id', $organization->id)
                        ->where('program_id', $program->id)
                        ->orderBy('name')
                        ->with('icon')
                        ->get();

        if ( $events->isNotEmpty() ) 
        { 
            return response( $events );
        }

        return response( [] );
    }

    public function store(EventRequest $request, Organization $organization, Program $program )
    {
                 
        if ( !( $organization->id == $program->organization_id ) )
        {
            return response(['errors' => 'Invalid Organization or Program'], 422);
        }
        
        $newEvent = Event::create( 
                                    $request->validated() + 
                                    [
                                        'organization_id' => $organization->id,
                                        'program_id' => $program->id
                                    ] 
                                );

        if ( !$newEvent )
        {
            return response(['errors' => 'Event Creation failed'], 422);
        }

        
        
        return response([ 'event' => $newEvent ]);
    }

    public function show( Organization $organization, Program $program, Event $event )
    {
        if ( !( $organization->id == $program->organization_id && $program->id == $event->program_id ) )        
        {
            return response(['errors' => 'Invalid Organization or Program'], 422);
        }

        $event->icon = $event->icon->toArray();

        if ( $event ) 
        { 
            return response( $event );
        }

        return response( [] );
    }

    public function update(EventRequest $request, Organization $organization, Program $program, Event $event )
    {
        if ( !( $organization->id == $program->organization_id && $program->id == $event->program_id ) )
        {
            return response(['errors' => 'Invalid Organization or Program'], 422);
        }
        
        if ( $event->organization_id != $organization->id ) 
        { 
            return response(['errors' => 'No Program Found'], 404);
        }

        $event->update( $request->validated() );

        return response([ 'event' => $event ]);
    }
}
