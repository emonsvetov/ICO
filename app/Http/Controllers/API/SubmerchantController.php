<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\MerchantAddSubMerchantRequest;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Support\Str;
use App\Models\Submerchant;
Use Exception;

class SubmerchantController extends Controller
{
    public function index( Organization $organization, Merchant $merchant )
    {

        return response( [] );
    }

    public function store(MerchantAddSubMerchantRequest $request, Merchant $merchant)
    {
        if ( !( $merchant->id ) )
        {
            return response(['errors' => 'Invalid Merchant'], 422);
        }
        
        $subMerchant = Submerchant::create(
            $request->validated() + 
            [
                'organization_id' => $organization->id
            ]
        );

        if ( !$newSubmerchant )
        {
            return response(['errors' => 'Submerchant creation failed'], 422);
        }

        return response([ 'domain' => $newDomain ]);
    }

    public function delete(Organization $organization, Domain $domain )
    {
        if ( !$organization || !$domain )
        {
            return response(['errors' => 'Invalid Organization or Domain'], 422);
        }
        $deleted = ['deleted' => 1];
        $domain->update( $deleted );
        return response()->json( $deleted );
    }

    public function generateSecretKey(Organization $organization, Domain $domain )
    {
        if ( !$organization OR !$domain )
        {
            return response(['errors' => 'Invalid Organization or Domain'], 422);
        }

        $secret_key = sha1 ( Str::random(10) );

        return response([ 'secret_key' => $secret_key ]);
    }
}
