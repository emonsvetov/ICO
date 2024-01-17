<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\MerchantAddSubMerchantRequest;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Support\Str;
use App\Models\Merchant;
Use Exception;

class SubmerchantController extends Controller
{
    public function index( Merchant $merchant )
    {
        if ( ! $merchant->exists )
        {
            return response(['errors' => 'Merchant Not Found'], 404);
        }

        $where = ['parent_id' => $merchant->id];
        $query = Merchant::where( $where );

        if ( request()->has('minimal') )
        {
            $merchants = $query->select('id', 'name')->with(['children' => function($query){
                return $query->select('id', 'name', 'parent_id');
            }])->get();
        } else {
            $merchants = $query->with('children')->paginate(request()->get('limit', 100));
        }

        if ( $merchants->isNotEmpty() )
        {
            return response( $merchants );
        }

        return response( [] );
    }

    public function store(MerchantAddSubMerchantRequest $request, Merchant $merchant)
    {
        if ( !$merchant->id  )
        {
            return response(['errors' => 'Invalid Merchant'], 422);
        }

        $subMerchant = Merchant::find( $request->get('merchant_id') );

        if( !$subMerchant->exists )   {
            return response(['errors' => 'Invalid Sub Merchant'], 422);
        }

        $anscestor_id = $request->get('anscestor_id');

        if( $anscestor_id )    {

            $anscestor = Merchant::find( $anscestor_id );
            if( !$anscestor->exists )   {
                return response(['errors' => 'Invalid Parent Sub Merchant'], 422);
            }

            $subMerchant->update( ['parent_id' => $anscestor->id]);
            return response([ 'success' => true ]);
        }

        $subMerchant->update( ['parent_id' => $merchant->id]);
        return response([ 'success' => true ]);
    }

    public function delete( Merchant $merchant,  Merchant $submerchant)
    {
        if ( !$merchant || !$submerchant )
        {
            return response(['errors' => 'Invalid Merchant or Sub Merchant'], 422);
        }
        if( request()->get('dt') == 'true' )    { //delete tree
            $submerchant->update( ['parent_id' => null]);
            $submerchants = $submerchant->children;
            while( $submerchants->isNotEmpty() )    {
                foreach( $submerchants as $submerchant) {
                    $submerchant->update( ['parent_id' => null]);
                    $submerchants = $submerchant->children;
                }
            }
        }   else    {
            $submerchant->update( ['parent_id' => null]);
        }
        return response(['success' => true]);
    }

    public function notInHierarchy(Merchant $merchant)
    {
        if ( ! $merchant->exists )
        {
            return response(['errors' => 'Merchant Not Found'], 404);
        }

        $merchants = Merchant::notInHierarchy($merchant);

        return response( $merchants );
    }

    public function inHierarchy(Merchant $merchant)
    {
        if ( ! $merchant->exists )
        {
            return response(['errors' => 'Merchant Not Found'], 404);
        }

        $merchants = Merchant::inHierarchy($merchant);

        return response( $merchants );
    }
}
