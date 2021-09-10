<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\ProgramGroupRequest;
use App\Models\Organization;
use App\Models\ProgramGroup;

class ProgramGroupController extends Controller
{
    public function index( Organization $organization )
    {
        
        if ( $organization )
        {
            $programGroups = ProgramGroup::where('organization_id', $organization->id)
                                                ->orderBy('name')
                                                ->get();
        }
        else
        {
            return response(['errors' => 'Invalid Organization'], 422);
        }
       

        if ( $programGroups->isNotEmpty() ) 
        { 
            return response( $programGroups );
        }

        return response( [] );
    }

    public function store(ProgramGroupRequest $request, Organization $organization)
    {
        if ( $organization )
        {
            $newProgramGroups = ProgramGroup::create( 
                                                    $request->validated() + 
                                                    ['organization_id' => $organization->id] 
                                                );
        }
        else
        {
            return response(['errors' => 'Invalid Organization'], 422);
        }
        

        if ( !$newProgramGroups )
        {
            return response(['errors' => 'Program Group Creation failed'], 422);
        }

        
        
        return response([ 'programgroup' => $newProgramGroups ]);
    }

    public function show( $organization, ProgramGroup $programGroup )
    {
                
        if ( $programGroup->organization->id == $organization ) 
        { 
            return response( $programGroup );
        }

        return response( [] );
    }

    public function update(ProgramGroupRequest $request, Organization $organization, ProgramGroup $programGroup )
    {
        if ( ! $programGroup->exists ) 
        { 
            return response(['errors' => 'No Program Found'], 404);
        }

        $programGroup->update( $request->validated() );

        return response([ 'programgroup' => $programGroup ]);
    }
}
