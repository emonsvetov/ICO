<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\DomainRequest;
use App\Models\Organization;
use App\Models\Domain;

class DomainController extends Controller
{
    
    public function index( Organization $organization )
    {
        
        if ( !$organization->id )
        {
            return response(['errors' => 'Invalid Organization or Program'], 422);
        }

        $domains = Domain::where('organization_id', $organization->id)
                    ->orderBy('name')
                    ->get();

        if ( $domains->isNotEmpty() ) 
        {
            return response( $domains );
        }

        return response( [] );
    }

    public function store(DomainRequest $request, Organization $organization, Program $program )
    {
                 
        if ( !( $organization->id ) )
        {
            return response(['errors' => 'Invalid Organization or Program'], 422);
        }
        
        $newEvent = Domain::create( 
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

    public function show( Organization $organization, Domain $domain )
    {
        if ( !( $organization->id == $program->organization_id && $program->id == $event->program_id ) )        
        {
            return response(['errors' => 'Invalid Organization or Program'], 422);
        }

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
