<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\CheckoutRequest;
use App\Http\Controllers\Controller;
use App\Models\Giftcode;
use App\Services\CheckoutService;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Country;
use App\Models\State;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function store(CheckoutRequest $request, CheckoutService $checkoutService, Organization $organization, Program $program )
    {
        $cart = $request->validated();
        try {
            $response = $checkoutService->processOrder( $cart, $program );
            if( !empty($response['errors']) )   {
                return response(['errors' => $response['errors']], 422);
            }

            // alert V2
            if (env('V2_GIFTCODE_SYNC_ENABLE') && isset($response['gift_codes_redeemed_for'])) {
                $giftCodes = $response['gift_codes_redeemed_for'];
                foreach ($giftCodes as $codeItem) {
                    $giftCodeId = (int)$codeItem->id;

                    $giftCode = Giftcode::find($giftCodeId);
                    /**
                     *  "redeemed_program_id": 1,
                        "redeemed_user_id": 120,
                    $redeemed_program_id = null;
                    $redeemed_program = Program::find($giftCode->redeemed_program_id);
                    return response(['errors' => $redeemed_program], 422);
                     *
                     */

                    if($giftCode->v2_merchant_id){
                        $responseV2 = Http::withHeaders([
                            'X-API-KEY' => env('V2_API_KEY'),
                        ])->post(env('V2_API_URL') . '/rest/gift_codes/redeem', [
                            'code' => $giftCode->code,
                            'redeemed_merchant_account_holder_id' => $giftCode->v2_merchant_id
                        ]);
                        Log::info('V2: ' . $giftCode->code);
                        Log::debug($responseV2->body());
                    }
                }
            }

            return $response;
        }   catch (\Exception $e)    {
            return response(
                [
                    'errors' => sprintf('Error while processing checkout. Line: %d, Error: %s', $e->getLine(), $e->getMessage()),
                ],
                422
            );
        }
    }
}

