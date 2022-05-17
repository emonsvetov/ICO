<?php
namespace App\Services;

use App\Http\Resources\GiftcodeCollection;
use App\Models\Traits\IdExtractor;
use App\Models\JournalEventType;
use App\Models\JournalEvent;
use App\Models\FinanceType;
use App\Models\MediumType;
use App\Models\Currency;
use App\Models\Giftcode;
use App\Models\Merchant;
use App\Models\Account;
use App\Models\Owner;
// use DB;

class GiftcodeService 
{
    use IdExtractor;

    public function getRedeemable(Merchant $merchant)   {
        $merchant_id = self::extractId($merchant);
        if( !$merchant_id ) return;
        $where = [
            'merchant_id' => $merchant_id,
            'redemption_date' => null,
        ];
        $giftcodes = Giftcode::getRedeemableListByMerchant($merchant, $where );
        // pr(DB::getQueryLog());
        return new GiftcodeCollection( $giftcodes );
    }

    public function createGiftcode( $merchant, $giftcode )    {
        $response = [];
        try{
            $removable = ['supplier_code', 'someurl']; //handling 'unwanted' keys
            foreach($removable as $key) {
                if( isset($giftcode[$key]) ) unset( $giftcode[$key] );
            }
            return Giftcode::create(
                auth()->user(), 
                $merchant,
                $giftcode
            );
        }   catch (Exception $e)    {
            $response['errors'] = sprintf('Exception while creating giftcode. Error:%s in line %d ', $e->getMessage(), $e->getLine());
        }
        $response;
    }
}
