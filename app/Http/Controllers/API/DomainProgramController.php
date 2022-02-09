<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\DomainAddProgramRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\DomainRequest;
use App\Models\Organization;
use Illuminate\Support\Str;
use App\Models\Program;
use App\Models\Domain;
Use Exception;
// use DB;

class DomainProgramController extends Controller
{
    public function index( Organization $organization, Domain $domain )
    {
        if ( $organization )
        {
            // DB::enableQueryLog();

            if( !$domain->programs->isNotEmpty() ) return response( [] );

            $status = request()->get('status');
            $keyword = request()->get('keyword');
            $sortby = request()->get('sortby', 'id');
            $direction = request()->get('direction', 'asc');

            $where[] = ['organization_id', $organization->id];

            $programIds = [];

            foreach($domain->programs as $program)    {
                $programIds[] = $program->id;
            }

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

            $query = Program::whereIn('id', $programIds)
                        ->whereNull('program_id')
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

        // return (DB::getQueryLog());


        if ( $programs->isNotEmpty() ) 
        { 
            return response( $programs );
        }

        return response( [] );
    }

    public function store( DomainAddProgramRequest $request, Organization $organization, Domain $domain )
    {
        if ( !$organization || !$domain )
        {
            return response(['errors' => 'Invalid Organization or Domain'], 422);
        }

        $validated = $request->validated();

        try{
            $domain->programs()->sync( [$validated['program_id'] ], false);
        }   catch( Exception $e) {
            return response(['errors' => 'Program adding failed', 'e' => $e->getMessage()], 422);
        }

        return response([ 'success' => true ]);
    }

    public function delete(Organization $organization, Domain $domain, Program $program )
    {
        if ( !$organization || !$domain || !$program )
        {
            return response(['errors' => 'Invalid Organization or Domain or Program'], 422);
        }

        try{
            $domain->programs()->detach( $program->id );
        }   catch( Exception $e) {
            return response(['errors' => 'Program removal failed', 'e' => $e->getMessage()], 422);
        }

        return response([ 'success' => true ]);
    }
}
