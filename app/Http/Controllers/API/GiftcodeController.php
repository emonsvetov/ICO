<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\GiftcodePurchaseRequest;
use App\Http\Controllers\Controller;
use App\Models\Giftcode;
use App\Services\GiftcodeService;
Use Exception;
use Illuminate\Http\Request;

class GiftcodeController extends Controller
{
    public function purchaseFromV2( GiftcodePurchaseRequest $request, GiftcodeService $giftcodeService)
    {
        $result['success'] = false;

        try {
            if (env('V2_GIFTCODE_SYNC_ENABLE')) {
                $code = $request->get('code');
                $giftcode = Giftcode::getByCode($code);

                $result['gift_code'] = $giftcode;
                $result['success'] = $giftcodeService->purchaseFromV2($giftcode);
            }
        } catch (\Exception $exception){
            $result['data'] = $exception->getMessage();
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
}
