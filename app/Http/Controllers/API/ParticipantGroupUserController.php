<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\ParticipantGroupUserRequest;
use App\Models\ParticipantGroup;
use App\Models\Organization;
use App\Models\User;

class ParticipantGroupUserController extends Controller
{
    public function index( Organization $organization, ParticipantGroup $participantGroup )
    {
        
        if ( !( $organization->id == $participantGroup->organization_id ) )
        {
            return response(['errors' => 'Invalid Organization or Participant Group'], 422);
        }        
                       

        if ( $participantGroup->users->isNotEmpty() ) 
        { 
            return response( $participantGroup->users );
        }

        return response( [] );
    }

    public function store( ParticipantGroupUserRequest $request, Organization $organization, ParticipantGroup $participantGroup )
    {
                 
        if ( !( $organization->id == $participantGroup->organization_id ) )
        {
            return response(['errors' => 'Invalid Organization or Participant Group'], 422);
        }

        $userIds = $request->validated();
        $insert = [];
        $validUserIds = [];

        foreach ( $userIds as $key => $userId )
        {            
            $validUserIds[] = $userId['user_id'];

            $insert[] = $userId + [ 'participant_group_id' => $participantGroup->id ];
        }

        $dbUserCount = User::where('organization_id', $organization->id)
                                                ->whereIn('id', $validUserIds )
                                                ->count();
        
        if ( $dbUserCount !== count( array_unique($validUserIds) ))
        {
            return response(['errors' => 'Not all Users belong to the Organization'], 422);
        }


        $participantGroup->users()->newPivotQuery()->upsert( 
            $insert, 
            ['user_id', 'participant_group_id'], 
            ['user_id', 'participant_group_id']
    );
        

        if ( !$participantGroup->users )
        {
            return response(['errors' => 'User Creation failed'], 422);
        }

        
        
        return response([ 'participantGroupUsers' => $participantGroup->users ]);
    }

    public function destroy( ParticipantGroupUserRequest $request, Organization $organization, ParticipantGroup $participantGroup )
    {
        if ( !( $organization->id == $participantGroup->organization_id ) )
        {
            return response(['errors' => 'Invalid Organization or Participant Group'], 422);
        }

        $userIds = $request->validated();
        $delete = [];
        $validUserIds = [];

        foreach ( $userIds as $key => $userId )
        {            
            $validUserIds[] = $userId['user_id'];

            $delete[] = $userId + [ 'participant_group_id' => $participantGroup->id ];
        }

        $dbUserCount = User::where('organization_id', $organization->id)
                                                ->whereIn('id', $validUserIds )
                                                ->count();
        
        if ( $dbUserCount !== count( array_unique($validUserIds) ))
        {
            return response(['errors' => 'Not all Users belong to the Organization'], 422);
        }

        

        $participantGroup->users()->detach( $delete );

        if ( !$participantGroup->users )
        {
            return response(['errors' => 'Event Creation failed'], 422);
        }

        
        
        return response([ 'participantGroupUsers' => $participantGroup->users ]);
    }
}
