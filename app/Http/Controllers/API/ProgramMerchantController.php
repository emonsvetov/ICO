<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\ProgramAddMerchantRequest;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Merchant;
use App\Models\Program;
Use Exception;
use DB;

class ProgramMerchantController extends Controller
{
    public function index( Organization $organization, Program $program )
    {
        if ( !$organization || !$program )
        {
            return response(['errors' => 'Invalid Organization or Program'], 422);
        }

        DB::enableQueryLog();

        return($program->merchants);

        return (DB::getQueryLog());

        if( !$program->merchants->isNotEmpty() ) return response( [] );

        $keyword = request()->get('keyword');
        $sortby = request()->get('sortby', 'id');
        $direction = request()->get('direction', 'asc');

        $merchantIds = [];

        foreach($program->merchants as $merchant)    {
            $merchantIds[] = $merchant->id;
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

        $query = Merchant::whereIn('id', $merchantIds)
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
            $merchants = $query->select('id', 'name')
            ->with(['children' => function($query){
                return $query->select('id','name','parent_id');
            }])
            ->get();
        }
        else {
            $merchants = $query->with('children')
            ->paginate(request()->get('limit', 10));
        }

        if ( $merchants->isNotEmpty() ) 
        { 
            return response( $merchants );
        }

        return response( [] );
    }

    public function store( ProgramAddMerchantRequest $request, Organization $organization, Program $program )
    {
        if ( !$organization || !$program )
        {
            return response(['errors' => 'Invalid Organization or Program'], 422);
        }

        $validated = $request->validated();

        try{
            $program->merchants()->sync( [$validated['merchant_id'] ], false);
        }   catch( Exception $e) {
            return response(['errors' => 'Merchant adding failed', 'e' => $e->getMessage()], 422);
        }

        return response([ 'success' => true ]);
    }

    public function delete(Organization $organization, Program $program, Merchant $merchant )
    {
        if ( !$organization || !$program || !$merchant )
        {
            return response(['errors' => 'Invalid Organization or Program or Merchant'], 422);
        }

        try{
            $program->merchants()->detach( $merchant->id );
        }   catch( Exception $e) {
            return response(['errors' => 'Merchant removal failed', 'e' => $e->getMessage()], 422);
        }

        return response([ 'success' => true ]);
    }
}
