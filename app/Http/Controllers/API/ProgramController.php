<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\ProgramRequest;
use App\Models\Program;
use App\Models\Organization;

class ProgramController extends Controller
{
    public function index( Organization $organization )
    {
        
        if ( $organization )
        {
            $programs = Program::whereNull('program_id')
                                ->where('organization_id', $organization->id)
                                ->with('childrenPrograms')
                                ->get();
        }
        else
        {
            return response(['errors' => 'Invalid Organization'], 422);
        }
       

        if ( $programs->isNotEmpty() ) 
        { 
            return response( $programs );
        }

        return response( [] );
    }

    public function store(ProgramRequest $request, Organization $organization)
    {
        if ( $organization )
        {
            $newProgram = Program::create( 
                                        $request->validated() + 
                                        ['organization_id' => $organization->id] 
                                        );
        }
        else
        {
            return response(['errors' => 'Invalid Organization'], 422);
        }
        

        if ( !$newProgram )
        {
            return response(['errors' => 'Program Creation failed'], 422);
        }
        
        return response([ 'program' => $newProgram ]);
    }

    public function show( $organization, Program $program )
    {
        if ( $program ) 
        { 
            return response( $program );
        }

        return response( [] );
    }

    public function update(ProgramRequest $request, Organization $organization, Program $program )
    {
        if ( ! $program->exists ) 
        { 
            return response(['errors' => 'No Program Found'], 404);
        }

        $program->update( $request->validated() );

        return response([ 'program' => $program ]);
    }

}
