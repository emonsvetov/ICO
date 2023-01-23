<?php
namespace App\Http\Controllers\API;

use App\Http\Requests\TeamRequest;
//use Illuminate\Support\Facades\Request;
use App\Http\Traits\TeamUploadTrait;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Team;
use DB;

class TeamController extends Controller
{
    use TeamUploadTrait;
    public function index( Organization $organization, Program $program, Request $request )
    {
        return response(Team::getIndexData($organization, $program, $request->all()) ?? []);
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
        $uploads = $this->handleTeamMediaUpload($request, $newTeam, true);
        return response([ 'team' => $newTeam ]);
    }

    public function show( Organization $organization, Program $program,Team $team )
    {
        if ($team ) 
        {
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
