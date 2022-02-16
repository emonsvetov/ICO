<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMerchantProgramRequest;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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
    public function destroy(Merchant  $merchant,Request $request)
    {
        $validator =  Validator::make($request->all(),[
            'program_id' => 'required|exists:App\Models\Program,id',
        ]);
        if($validator->fails())
        {
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 422);
        }
        $merchant->programs()->detach($request->program_id);

		return response(['deleted' => true]);
    }
}
