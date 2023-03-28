<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\ProgramMerchantRequest;
use App\Http\Resources\GiftcodeCollection;
use App\Http\Controllers\Controller;
use App\Services\GiftcodeService;
use App\Models\ProgramMerchant;
use App\Models\Organization;
use App\Models\Merchant;
use App\Models\Program;
Use Exception;
use DB;

class ProgramMerchantController extends Controller
{

    function __construct(Organization $organization, Program $program, Merchant $merchant)  {
        $this->organization = $organization;
        $this->program = $program;
        $this->merchant = $merchant;
    }

    public function index( Organization $organization, Program $program )
    {
        if ( $program->merchants->isNotEmpty() )
        {
            return response( $program->merchants );
        }

        return response( [] );
    }

    public function view( Organization $organization, Program $program, Merchant $merchant )
    {
        $user = auth()->user();
        $programMerchant = $program->merchants->find($merchant->id);

        if ( $programMerchant )
        {
            return response( $programMerchant );
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

    public function giftcodes( Organization $organization, Program $program, Merchant $merchant )
    {
        return $this->merchant->getGiftcodes( $merchant );
    }

    public function redeemable(GiftcodeService $giftcodeService, Organization $organization, Program $program, Merchant $merchant )
    {
        return $giftcodeService->getRedeemable( $merchant, $program->is_demo );
    }
}
