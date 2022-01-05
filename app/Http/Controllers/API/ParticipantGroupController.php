<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\ParticipantGroupRequest;
use App\Models\Organization;
use App\Models\ParticipantGroup;


class ParticipantGroupController extends Controller
{
    
    public function index( Organization $organization )
    {
        
        if ( $organization )
        {
            $participantGroups = ParticipantGroup::where('organization_id', $organization->id)
                                                ->orderBy('name')
                                                ->get();
        }
        else
        {
            return response(['errors' => 'Invalid Organization'], 422);
        }
       

        if ( $participantGroups->isNotEmpty() ) 
        { 
            return response( $participantGroups );
        }

        return response( [] );
    }

    public function store(ParticipantGroupRequest $request, Organization $organization)
    {
        if ( $organization )
        {
            $newParticipantGroups = ParticipantGroup::create( 
                                                                $request->validated() + 
                                                                ['organization_id' => $organization->id] 
                                                            );
        }
        else
        {
            return response(['errors' => 'Invalid Organization'], 422);
        }
        

        if ( !$newParticipantGroups )
        {
            return response(['errors' => 'Participant Group Creation failed'], 422);
        }

        
        
        return response([ 'participantgroup' => $newParticipantGroups ]);
    }

    public function show( $organization, ParticipantGroup $participantGroup )
    {
        
        //dd( $participantGroup->organization->id . " +++++ " . $organization );
        
        
        if ( $participantGroup->organization->id == $organization ) 
        { 
            return response( $participantGroup );
        }

        return response( [] );
    }

    public function update(ParticipantGroupRequest $request, Organization $organization, ParticipantGroup $participantGroup )
    {
        if ( ! $participantGroup->exists ) 
        { 
            return response(['errors' => 'No Program Found'], 404);
        }

        $participantGroup->update( $request->validated() );

        return response([ 'participantgroup' => $participantGroup ]);
    }
}
