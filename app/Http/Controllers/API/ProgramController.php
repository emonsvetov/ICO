<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ProgramMoveRequest;
use App\Http\Requests\ProgramRequest;
use App\Http\Controllers\Controller;
use App\Events\ProgramCreated;
use App\Models\Organization;
use Illuminate\Http\Request;
use App\Models\Program;

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

            $where[] = ['organization_id', $organization->id];

            if( $status )
            {
                $where[] = ['status', $status];
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

            if( $keyword )
            {
                $query = $query->where(function($query1) use($keyword) {
                    $query1->orWhere('id', 'LIKE', "%{$keyword}%")
                    ->orWhere('name', 'LIKE', "%{$keyword}%");
                });
            }

            $query = $query->orderByRaw($orderByRaw);
            
            if ( request()->has('minimal') )
            {
                $programs = $query->select('id', 'name')
                                  ->with(['children' => function($query){
                                      return $query->select('id','name','program_id');
                                  }])
                                  ->get();
            }
            else {
                $programs = $query->with('children')
                ->paginate(request()->get('limit', 10));
            }

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

    public function show( Organization $organization, Program $program )
    {
        if ( $organization->id != $program->organization_id )
        {
            return response(['errors' => 'Invalid Organization or Program'], 422);
        }

        if ( $program ) 
        { 
            $program->merchants;
            return response( $program );
        }

        return response( [] );
    }

    public function update(ProgramRequest $request, Organization $organization, Program $program )
    {
        if ( $organization->id != $program->organization_id )
        {
            return response(['errors' => 'Invalid Organization or Program'], 422);
        }

        $program->update( $request->validated() );

        return response([ 'program' => $program ]);
    }

    public function move(ProgramMoveRequest $request, Organization $organization, Program $program )
    {
        if ( $organization->id != $program->organization_id )
        {
            return response(['errors' => 'Invalid Organization or Program'], 422);
        }

        $program->update( $request->validated() );

        return response([ 'program' => $program ]);
    }
}
