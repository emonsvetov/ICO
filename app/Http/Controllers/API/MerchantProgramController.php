<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMerchantProgramRequest;
use App\Models\Merchant;
use Illuminate\Support\Facades\Auth;

class MerchantProgramController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  App\Http\Requests\StoreMerchantProgramRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreMerchantProgramRequest $request)
    {
        $merchant = Merchant::find($request->merchant_id);
        $merchant->programs()->sync($request->get('program_id'));
        
        return response([ 'merchant' => $merchant ]);
    }

    /**
     * Display he specified resource.
     *
     * @param  \App\Models\Merchant  $merchant
     * @return \Illuminate\Http\Response
     */
    public function show(Merchant $merchant)
    {
        return response([ 'merchant' => $merchant, 'programs' => $merchant->programs()->get() ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  App\Http\Requests\StoreMerchantProgramRequest  $request
     * @param  \App\Models\Merchant  $merchant
     * @return \Illuminate\Http\Response
     */
    public function update(StoreMerchantProgramRequest $request, Merchant  $merchant)
    {
        $merchant->programs()->sync($request->get('program_id'));

        return response([ 'merchant' => $merchant, 'programs' => $merchant->programs()->get() ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Merchant  $merchant
     * @return \Illuminate\Http\Response
     */
    public function destroy(Merchant  $merchant)
    {
        $merchant->programs()->detach();

        return response(['deleted' => true]);
    }
}
