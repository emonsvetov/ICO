<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\GiftcodePurchaseRequest;
use App\Http\Controllers\Controller;
use App\Models\Giftcode;
use App\Services\GiftcodeService;
Use Exception;

class GiftcodeController extends Controller
{
    public function purchaseFromV2( GiftcodePurchaseRequest $request, GiftcodeService $giftcodeService)
    {
        $result['success'] = false;

        try {
            $code = $request->get('code');
            $giftcode = Giftcode::getByCode($code);
            $result['success'] = $giftcodeService->purchaseFromV2($giftcode);
        } catch (\Exception $exception){
            $result['data'] = $exception->getMessage();
        }

        return response( $result );
    }
}
