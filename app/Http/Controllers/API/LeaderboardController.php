<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaderboardRequest;
use App\Models\Organization;
use App\Models\Leaderboard;
use App\Models\Program;

class LeaderboardController extends Controller
{
    public function index( Organization $organization, Program $program )
    {
        $where = ['organization_id' => $organization->id, 'program_id' => $program->id];
        $leaderboards = Leaderboard::where($where)->get();
        if( $leaderboards->isNotEmpty() ) {
            return response( $leaderboards );
        }
        return response( [] );
    }

    public function store(LeaderboardRequest $request, Organization $organization, Program $program )
    {
        $data = $request->validated();
        if( empty($data['status_id']) )   {
            $data['status_id'] = Leaderboard::getActiveStatusId();
        }
        $newLeaderboard = Leaderboard::create( 
            $data + 
            [
                'organization_id' => $organization->id,
                'program_id' => $program->id
            ]
        );

        if ( !$newLeaderboard )
        {
            return response(['errors' => 'Leaderboard creation failed'], 422);
        }

        return response([ 'leaderboard' => $newLeaderboard ]);
    }

    public function show( Organization $organization, Program $program, Leaderboard $leaderboard )
    {
        if ( $leaderboard ) 
        {
            $leaderboard->load('status');
            return response( $leaderboard );
        }

        return response( [] );
    }

    public function update(LeaderboardRequest $request, Organization $organization, Program $program, Leaderboard $leaderboard )
    {
        $data = $request->validated();
        if( isset($data['enable']) )   {
            if( $data['enable'] )   {
                $data['status_id'] = Leaderboard::getActiveStatusId();
            }   else {
                $data['status_id'] = Leaderboard::getInactiveStatusId();
            }
            unset($data['enable']);
        }

        $leaderboard->update( $data );
        return response([ 'leaderboard' => $leaderboard ]);
    }

    public function delete(Organization $organization, Program $program, Leaderboard $leaderboard)
    {
        $leaderboard->delete();
        return response(['success' => true]);
    }
}
