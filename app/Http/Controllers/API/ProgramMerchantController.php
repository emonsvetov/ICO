<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\ProgramMerchantRequest;
use App\Http\Controllers\Controller;
use App\Models\ProgramMerchant;
use App\Models\Organization;
use App\Models\Merchant;
use App\Models\Program;
Use Exception;
// use DB;

class ProgramMerchantController extends Controller
{
    public function index( Organization $organization, Program $program )
    {
        if ( $organization->id != $program->organization_id )
        {
            return response(['errors' => 'Invalid Organization or Program'], 422);
        }
        
        if ( $program->merchants->isNotEmpty() ) 
        { 
            return response( $program->merchants );
        }

        return response( [] );
    }

    public function store( ProgramMerchantRequest $request, Organization $organization, Program $program )
    {
        $validated = $request->validated();

        $columns = [];

        if( isset( $validated['featured'] ) )
        {
            $columns['featured'] = $validated['featured'];
        }

        if( isset( $validated['cost_to_program'] ) )
        {
            $columns['cost_to_program'] = $validated['cost_to_program'];
        }
        
        try{
            $program->merchants()->sync( [ $validated['merchant_id'] => $columns ], false);
        }   catch( Exception $e) {
            return response(['errors' => 'Merchant adding failed', 'e' => $e->getMessage()], 422);
        }

        return response([ 'success' => true ]);
    }

    public function delete(Organization $organization, Program $program, Merchant $merchant )
    {
        try{
            $program->merchants()->detach( $merchant );
        }   catch( Exception $e) {
            return response(['errors' => 'Merchant removal failed', 'e' => $e->getMessage()], 422);
        }

        return response([ 'success' => true ]);
    }

    // Do not remove, we may need it later on!

    // public function index( Organization $organization, Program $program )
    // {
    //     if ( !$organization || !$program )
    //     {
    //         return response(['errors' => 'Invalid Organization or Program'], 422);
    //     }

    //     if( !$program->merchants->isNotEmpty() ) return response( [] );

    //     $keyword = request()->get('keyword');
    //     $sortby = request()->get('sortby', 'id');
    //     $direction = request()->get('direction', 'asc');

    //     $merchantIds = [];
    //     $where = [];

    //     foreach($program->merchants as $merchant)    {
    //         $merchantIds[] = $merchant->id;
    //     }

    //     if( $sortby == "name" ) 
    //     {
    //         $collation =  "COLLATE utf8mb4_unicode_ci"; //COLLATION is required to support case insensitive ordering
    //         $orderByRaw = "{$sortby} {$collation} {$direction}";
    //     }
    //     else
    //     {
    //         $orderByRaw = "{$sortby} {$direction}";
    //     }

    //     $query = Merchant::whereIn('id', $merchantIds)->where($where);

    //     if( $keyword )
    //     {
    //         $query = $query->where(function($query1) use($keyword) {
    //             $query1->orWhere('id', 'LIKE', "%{$keyword}%")
    //             ->orWhere('name', 'LIKE', "%{$keyword}%");
    //         });
    //     }

    //     $query = $query->orderByRaw($orderByRaw);
        
    //     if ( request()->has('minimal') )
    //     {
    //         $merchants = $query->select('id', 'name')
    //         ->with(['programs' => function($query){
    //             return $query->select('id','name');
    //         }])
    //         ->get();
    //     }
    //     else {
    //         $merchants = $query->with(['programs' => function($query){
    //             return $query->select('id','name');
    //         }])
    //         ->paginate(request()->get('limit', 10));
    //     }

    //     if ( $merchants->isNotEmpty() ) 
    //     { 
    //         return response( $merchants );
    //     }

    //     return response( [] );
    // }
}
