<?php
namespace App\Services;

use App\Http\Resources\GiftcodeCollection;
use App\Models\Traits\IdExtractor;
use App\Models\Giftcode;

class GiftcodeService 
{
    use IdExtractor;

    public function getRedeemable( $merchant )   {
        $merchant_id = self::extractId($merchant);
        if( !$merchant_id ) return;
        $where = [
            'merchant_id' => $merchant_id,
            'redemption_date' => null,
        ];
        $query = Giftcode::where( $where );
        return new GiftcodeCollection($query->get());
    }
}
