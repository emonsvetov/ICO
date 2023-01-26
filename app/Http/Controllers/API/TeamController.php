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
        $upload = $this->handleTeamMediaUpload($request, $newTeam, true);
        if( $upload )   {
            $newTeam->update( $upload );
        }
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
        try {
            $team->update( $data );
            $upload = $this->handleTeamMediaUpload($request, $team, true);
            if( $upload )   {
                $team->update( $upload );
            }
        }
        catch(\Throwable $e)
        {
            return response(['errors' => 'Team Creation failed', 'e' => sprintf('Error %s in line  %d', $e->getMessage(), $e->getLine())], 422);
        }
        return response(['team' =>$team ]);
    }

    public function delete(Organization $organization, Program $program,Team $team)
    {
        $team->delete();
        return response(['success' => true]);
    }
}
