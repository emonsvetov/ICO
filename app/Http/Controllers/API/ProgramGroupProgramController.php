<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\ProgramGroupProgramRequest;
use App\Models\ProgramGroup;
use App\Models\Organization;
use App\Models\Program;

class ProgramGroupProgramController extends Controller
{
    public function index( Organization $organization, ProgramGroup $programGroup )
    {
        
        if ( !( $organization->id == $programGroup->organization_id ) )
        {
            return response(['errors' => 'Invalid Organization or Program Group'], 422);
        }        
                       

        if ( $programGroup->programs->isNotEmpty() ) 
        { 
            return response( $programGroup->programs );
        }

        return response( [] );
    }

    public function store( ProgramGroupProgramRequest $request, Organization $organization, ProgramGroup $programGroup )
    {
                 
        if ( !( $organization->id == $programGroup->organization_id ) )
        {
            return response(['errors' => 'Invalid Organization or Program Group'], 422);
        }

        $programIds = $request->validated();
        $insert = [];
        $validProgramIds = [];

        foreach ( $programIds as $key => $programId )
        {            
            $validProgramIds[] = $programId['program_id'];

            $insert[] = $programId + [ 'program_group_id' => $programGroup->id ];
        }

        $dbProgramCount = Program::where('organization_id', $organization->id)
                                ->whereIn('id', $validProgramIds )
                                ->count();
        
        if ( $dbProgramCount !== count( array_unique($validProgramIds) ))
        {
            return response(['errors' => 'Not all Programs belong to the Organization'], 422);
        }


        $programGroup->programs()->newPivotQuery()->upsert( 
                        $insert, 
                        ['program_id', 'program_group_id'], 
                        ['program_id', 'program_group_id']
        );


        if ( !$programGroup->programs )
        {
            return response(['errors' => 'Program Creation failed'], 422);
        }

        
        
        return response([ 'programGroupPrograms' => $programGroup->programs ]);
    }

    public function destroy( ProgramGroupProgramRequest $request, Organization $organization, ProgramGroup $programGroup )
    {
        if ( !( $organization->id == $programGroup->organization_id ) )
        {
            return response(['errors' => 'Invalid Organization or Program Group'], 422);
        }

        $programIds = $request->validated();
        $delete = [];
        $validProgramIds = [];

        foreach ( $programIds as $key => $programId )
        {            
            $validProgramIds[] = $programId['program_id'];

            $delete[] = $programId + [ 'program_group_id' => $programGroup->id ];
        }

        $dbProgramCount = Program::where('organization_id', $organization->id)
                                                ->whereIn('id', $validProgramIds )
                                                ->count();
        
        if ( $dbProgramCount !== count( array_unique($validProgramIds) ))
        {
            return response(['errors' => 'Not all Programs belong to the Organization'], 422);
        }

        

        $programGroup->programs()->detach( $delete );

        if ( !$programGroup->programs )
        {
            return response(['errors' => 'Event Creation failed'], 422);
        }
        
        return response([ 'programGroupPrograms' => $programGroup->programs ]);
    }
}
