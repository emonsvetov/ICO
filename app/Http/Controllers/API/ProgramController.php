<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ProgramRequest;
use App\Http\Controllers\Controller;
use App\Events\ProgramCreated;
use App\Models\Organization;
use Illuminate\Http\Request;
use App\Models\Program;
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
                ->with(['children' => function($query){
                    return $query->select('id','name','program_id');
                }])
                ->get();
            }
            else {
                $programs = $query->with('children')
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

    public function update(Request $request, Organization $organization, Program $program )
    {
        if ( ! $program->exists ) 
        { 
            return response(['errors' => 'No Program Found'], 404);
        }

        if( $request->isMethod('patch') ) {
            $program->update( $request->all() );
        }
        else
        {
            Validator::make($request->all(), (new ProgramRequest())->rules())->validate();
        }

        return response([ 'program' => $program ]);
    }

}
