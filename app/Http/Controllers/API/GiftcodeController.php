<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\GiftcodePurchaseRequest;
use App\Http\Controllers\Controller;
use App\Models\Giftcode;
use App\Services\GiftcodeService;
Use Exception;
use Illuminate\Http\Request;
use App\Models\User;

class GiftcodeController extends Controller
{
    public function purchaseFromV2( GiftcodePurchaseRequest $request, GiftcodeService $giftcodeService, User $user)
    {
        $result['success'] = false;
        try {
            if (env('V2_GIFTCODE_SYNC_ENABLE')) {
                $v2_medium_info_id = $request->get('v2_medium_info_id');
                $code = $request->get('code');
                $redeemed_merchant_id = $request->get('redeemed_merchant_id');

                $giftcode = Giftcode::getByCode($code);
                $result = $giftcodeService->purchaseFromV2($giftcode, $user, $v2_medium_info_id, $redeemed_merchant_id);
                $result['gift_code'] = $giftcode;
            }
        } catch (\Exception $exception){
            $result['error'] = $exception->getMessage();
        }

        return response( $result );
    }

    public function purchaseCodes( Request $request, GiftcodeService $giftcodeService)
    {
        $date = $request->get('date');
        $dateFrom = date('Y-m-d', strtotime($date)) . ' 00:00:00';
        $dateTo = date('Y-m-d', strtotime($date)) . ' 23:59:59';

        $giftcodes = Giftcode::whereNotNull('redeemed_user_id')
            ->where('redemption_date', '>=', $dateFrom)
            ->where('redemption_date', '<=', $dateTo)
            ->get()
            ->pluck('id');

        return response( $giftcodes );
    }

    public function getCodes(Request $request, GiftcodeService $giftcodeService)
    {
        $res = $giftcodeService->getMediumInfoForRedemption();
        return response($res);
    }

//    public function delCodes(Request $request, GiftcodeService $giftcodeService)
//    {
//        $res = $giftcodeService->delCodes($request->codes);
//        return response($res);
//    }

    public function addCodes(Request $request, GiftcodeService $giftcodeService)
    {
        $cods = json_decode($request->codes,true);
        $res = $giftcodeService->addCodes($cods);
        return response($res);
    }
}
