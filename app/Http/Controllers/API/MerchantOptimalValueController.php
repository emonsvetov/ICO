<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\MerchantOptimalValueRequest;
use App\Http\Controllers\Controller;
use App\Models\OptimalValue;
use App\Models\Merchant;
Use Exception;

class MerchantOptimalValueController extends Controller
{
    public function index( Merchant $merchant )
    {
        $sortby = request()->get('sortby', 'id');
        $direction = request()->get('direction', 'asc');

        $optimalValues = OptimalValue::where( ['merchant_id' => $merchant->id] )
        ->orderByRaw("{$sortby} {$direction}")
        ->paginate(request()->get('limit', config('global.paginate_limit')));

        if ( $optimalValues->isNotEmpty() )
        {
            return response( $optimalValues );
        }

        return response( [] );
    }

    public function store( MerchantOptimalValueRequest $request, Merchant $merchant )
    {
        $optimalValue = OptimalValue::create(
            $request->validated() + ['merchant_id' => $merchant->id]
        );
        return response( $optimalValue );
    }

    public function update(MerchantOptimalValueRequest $request, Merchant $merchant, OptimalValue $optimalValue )
    {
        $optimalValue->update( $request->validated() );
        return response( $optimalValue );
    }

    public function destroy(Merchant $merchant, OptimalValue $optimalValue )
    {
        $optimalValue->delete();
        return response( ['deleted' => true] );
    }
}
