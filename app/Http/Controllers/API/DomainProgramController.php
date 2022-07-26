<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\DomainAddProgramRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\DomainRequest;
use App\Services\ProgramService;
use App\Models\Organization;
use Illuminate\Support\Str;
use App\Models\Program;
use App\Models\Domain;
Use Exception;

class DomainProgramController extends Controller
{
    public function index( Organization $organization, Domain $domain )
    {
        if( $domain->programs->isEmpty() ) return response( [] );

        $status = request()->get('status');
        $keyword = request()->get('keyword');
        $sortby = request()->get('sortby', 'id');
        $direction = request()->get('direction', 'asc');

        $where = [];

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
                        ->withOrganization($organization)
                        // ->with(['children' => function($query){
                        //     return $query->select('id','name','program_id');
                        // }])
                        ->get();
        } else {
            $programs = $query
            // ->with('children')
            ->withOrganization($organization)
            ->paginate(request()->get('limit', 10));
        }

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

    public function listAvailableProgramsToAdd(Organization $organization, Domain $domain, ProgramService $programService)
    {
        $programs = $programService->listAvailableProgramsToAdd( $organization, $domain);
        return response($programs);
    }
}
