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
            $findById = request()->get('findById', false);

            $where[] = ['organization_id', $organization->id];

            $orWhere = [];

            if( $status )
            {
                $where[] = ['status', $status];
            }

            if( $keyword && !$findById )
            {
                $where[] = ['name', 'LIKE', "%{$keyword}%"];
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
            // DB::enableQueryLog();

            function orWhere( $keyword ) {
                return $query->orWhere(function($query1) {
                    $query1->where('id', 'LIKE', "%{$keyword}%")
                    ->where('name', 'LIKE', "%{$keyword}%");
                    return $query1;
                });
            }
            
            $query = Program::whereNull('program_id')
                            ->where($where);

            if( $keyword && $findById )
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
                                  ->with('childrenPrograms:program_id,id,name')
                                  ->get();
            }
            else {
                $programs = $query->with('childrenPrograms')
                                  ->paginate(request()->get('limit', 10));
            }

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
