<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\ProgramRequest;
use App\Models\Program;
use App\Models\Organization;

use App\Events\ProgramCreated;
use DB;

class ProgramController extends Controller
{
    public function index( Organization $organization )
    {
        
        if ( $organization )
        {
            $status = request()->get('status');
            $keyword = request()->get('keyword');
            $sortby = request()->get('sortby', 'id');
            $direction = request()->get('direction', 'asc');

            $where = [
                'organization_id'=>$organization->id
            ];
            if( $status )    {
                $where['status'] = $status;
            }

            if( $sortby == "name" ) 
            {
                $collation =  "COLLATE utf8mb4_unicode_ci"; //COLLATION is required to support case insensitive ordering
                $orderByRaw = "{$sortby} {$collation} {$direction}";
            }
            else
            {
                $orderByRaw = "{$sortby} {$direction}";
            }
            
            $query = Program::whereNull('program_id')
            ->where($where);

            if( $keyword )    {
                $query = $query->where('name', 'LIKE', "%{$keyword}%");
            }

            $query = $query->orderByRaw($orderByRaw);
            // DB::enableQueryLog();
            
            $programs = $query
                        ->with('childrenPrograms')
                        ->paginate(request()->get('limit', 10));
            // return (DB::getQueryLog());
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

        
        ProgramCreated::dispatch( $newProgram );
        
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
