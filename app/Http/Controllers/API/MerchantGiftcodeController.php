<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\MerchantGiftcodeRequest;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Merchant;
use App\Models\Giftcode;
Use Exception;
// use DB;

class MerchantGiftcodeController extends Controller
{
    public function index( Merchant $merchant )
    {
        $keyword = request()->get('keyword');
        $sortby = request()->get('sortby', 'id');
        $direction = request()->get('direction', 'asc');

        // DB::enableQueryLog();

        $where = ['merchant_id' => $merchant->id];

        if( $sortby == "name" )
        {
            $collation =  "COLLATE utf8mb4_unicode_ci"; //COLLATION is required to support case insensitive ordering
            $orderByRaw = "{$sortby} {$collation} {$direction}";
        }
        else
        {
            $orderByRaw = "{$sortby} {$direction}";
        }

        $query = Giftcode::where( $where );

        if( $keyword )
        {
            $query = $query->where(function($query1) use($keyword) {
                $query1->orWhere('id', 'LIKE', "%{$keyword}%")
                ->orWhere('code', 'LIKE', "%{$keyword}%");
            });
        }

        $query = $query->orderByRaw($orderByRaw);

        if ( request()->has('minimal') )
        {
            $giftcodes = $query->select('id', 'code')
            ->get();
        } else {
            $giftcodes = $query->paginate(request()->get('limit', config('global.paginate_limit')));
        }

        // Log::debug("Query:", DB::getQueryLog());

        if ( $giftcodes->isNotEmpty() )
        {
            return response( $giftcodes );
        }

        return response( [] );
    }

    public function store( Merchant $merchant )
    {

        return request()->all();
        $validated = $request->validated();
        return response([ 'success' => true ]);
    }

    // public function delete(Organization $organization, Merchant $merchant, Merchant $merchant )
    // {
    //     if ( $organization->id != $merchant->organization_id )
    //     {
    //         return response(['errors' => 'Invalid Organization or Merchant'], 422);
    //     }

    //     try{
    //         $merchant->merchants()->detach( $merchant );
    //     }   catch( Exception $e) {
    //         return response(['errors' => 'Merchant removal failed', 'e' => $e->getMessage()], 422);
    //     }

    //     return response([ 'success' => true ]);
    // }

    // Do not remove, we may need it later on!

    // public function index( Organization $organization, Merchant $merchant )
    // {
    //     if ( !$organization || !$merchant )
    //     {
    //         return response(['errors' => 'Invalid Organization or Merchant'], 422);
    //     }

    //     if( !$merchant->merchants->isNotEmpty() ) return response( [] );

    //     $keyword = request()->get('keyword');
    //     $sortby = request()->get('sortby', 'id');
    //     $direction = request()->get('direction', 'asc');

    //     $merchantIds = [];
    //     $where = [];

    //     foreach($merchant->merchants as $merchant)    {
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
