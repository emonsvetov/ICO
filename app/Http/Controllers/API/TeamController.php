<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\TeamRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\Team;
use App\Models\Program;

class TeamController extends Controller
{
    
    public function index( Organization $organization, Program $program )
    {
       $where = ['organization_id' => $organization->id, 'program_id' => $program->id];
       $teams =Team::where($where);
        if($teams->isNotEmpty() ) {
            return response($teams );
        }
        return response( [] );
    }

    public function store(TeamRequest $request, Organization $organization, Program $program )
    {
        $data = $request->validated();
        $newTeam =Team::create( 
            $data + 
            [
                'organization_id' => $organization->id,
                'program_id' => $program->id
            ]
        );

        if ( !$newTeam )
        {
            return response(['errors' => 'Team creation failed'], 422);
        }

        return response([ 'team' => $newTeam ]);
    }

    public function show( Organization $organization, Program $program,Team $team )
    {
        if ($team ) 
        {
           $team->load('status');
            return response($team );
        }

        return response( [] );
    }

    public function update(TeamRequest $request, Organization $organization, Program $program,Team $team )
    {
        $data = $request->validated();
        $team->update( $data );
        return response(['team' =>$team ]);
    }

    public function delete(Organization $organization, Program $program,Team $team)
    {
        $team->delete();
        return response(['success' => true]);
    }
}
